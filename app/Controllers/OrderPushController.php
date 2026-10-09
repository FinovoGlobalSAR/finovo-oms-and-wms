<?php
// app/Controllers/OrderPushController.php
//
// Orders page ke do kaam (har platform ke liye ek hi jagah):
//   /orders/push?id=..           → Finovo ka order (saare products ke saath) store ke platform pe banao
//   /orders/push-tracking?id=..  → platform se aaye order ka tracking number wapas platform pe bhejo ("Shipped")
// Kaunsa platform? Jo store abhi Manage ho raha hai uska.
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../Services/OrderPushService.php';
require_once __DIR__ . '/../Services/AuditLogService.php';
require_once __DIR__ . '/../Services/EbayAuthService.php';

class OrderPushController extends Controller
{
    // platform → [label, orders table ka external order ID column]
    public const PLATFORMS = [
        'shopify'     => ['label' => 'Shopify',     'column' => 'external_order_id'],
        'woocommerce' => ['label' => 'WooCommerce', 'column' => 'external_wc_order_id'],
        'bigcommerce' => ['label' => 'BigCommerce', 'column' => 'external_bc_order_id'],
        'wix'         => ['label' => 'Wix',         'column' => 'external_wix_order_id'],
        'magento'     => ['label' => 'Magento',     'column' => 'external_magento_order_id'],
        'prestashop'  => ['label' => 'PrestaShop',  'column' => 'external_ps_order_id'],
        'ebay'        => ['label' => 'eBay',        'column' => 'external_ebay_order_id'],
    ];

    // Kin platforms pe naya order ban sakta hai / tracking ja sakti hai
    public const CAN_CREATE = ['shopify', 'woocommerce', 'bigcommerce', 'wix', 'magento', 'prestashop'];
    public const CAN_TRACK  = ['shopify', 'woocommerce', 'bigcommerce', 'magento', 'ebay'];

    private OrderPushService $pushService;
    private AuditLogService $auditService;

    public function __construct()
    {
        parent::__construct();
        $this->requireRole(['admin', 'manager', 'sales staff']);
        $this->requireStoreContext();
        $this->pushService = new OrderPushService();
        $this->auditService = new AuditLogService();
    }

    // ---------- 1) Naya order platform pe ----------

    public function push(): void
    {
        $db = Database::getConnection();
        $store = $this->getCurrentStore();
        $platform = strtolower((string) ($store['platform'] ?? ''));

        if ($platform === 'ebay') {
            $this->redirect('/orders?error=' . urlencode('eBay does not allow creating orders from outside eBay. For orders that came from eBay, use "Send tracking to eBay".'));
            return;
        }
        if (!in_array($platform, self::CAN_CREATE, true)) {
            $this->redirect('/orders?error=' . urlencode('Order push is not available for this store\'s platform.'));
            return;
        }
        $target = self::PLATFORMS[$platform];

        $rows = $this->orderRows($db, (int) ($_GET['id'] ?? 0), (int) $store['id']);
        if (empty($rows)) {
            $this->redirect('/orders?error=' . urlencode('Order not found.'));
            return;
        }

        // Jo order us platform se aaya ya pehle push ho chuka, dobara bhejne se wahan duplicate banta
        foreach ($rows as $row) {
            if (!empty($row[$target['column']])) {
                $this->redirect('/orders?error=' . urlencode("This order is already in {$target['label']}."));
                return;
            }
        }

        $lines = OrderPushService::linesFromRows($rows, $this->productsFor($db, $rows, (int) $store['id']));
        $order = $rows[0];

        $result = match ($platform) {
            'shopify'     => $this->pushService->pushShopify($store, $order, $lines),
            'woocommerce' => $this->pushService->pushWooCommerce($store, $order, $lines),
            'bigcommerce' => $this->pushService->pushBigCommerce($store, $order, $lines),
            'wix'         => $this->pushService->pushWix($store, $order, $lines),
            'magento'     => $this->pushService->pushMagento($store, $order, $lines),
            'prestashop'  => $this->pushService->pushPrestaShop($store, $order, $lines),
        };

        if (!$result['ok']) {
            $this->redirect('/orders?error=' . urlencode($result['message']));
            return;
        }

        // Order ki SAARI rows pe platform ka order ID (column ka naam upar wali fixed list se — user input nahi)
        $ids = array_map(fn($r) => (int) $r['id'], $rows);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $db->prepare("UPDATE orders SET {$target['column']} = ? WHERE id IN ({$placeholders})")
           ->execute(array_merge([$result['external_id']], $ids));

        $this->auditService->log(
            (int) $store['id'],
            'order_push',
            'order',
            (string) $order['id'],
            "Pushed to {$target['label']} (external order #{$result['external_id']})."
        );

        $this->redirect('/orders?pushed=' . urlencode($target['label']));
    }

    // ---------- 2) Tracking number platform pe ----------

    public function pushTracking(): void
    {
        $db = Database::getConnection();
        $store = $this->getCurrentStore();
        $platform = strtolower((string) ($store['platform'] ?? ''));

        if (!in_array($platform, self::CAN_TRACK, true)) {
            $this->redirect('/orders?error=' . urlencode('Sending tracking is not available for this store\'s platform.'));
            return;
        }
        $target = self::PLATFORMS[$platform];

        $rows = $this->orderRows($db, (int) ($_GET['id'] ?? 0), (int) $store['id']);
        if (empty($rows)) {
            $this->redirect('/orders?error=' . urlencode('Order not found.'));
            return;
        }

        $externalId = '';
        foreach ($rows as $row) {
            if (!empty($row[$target['column']])) {
                $externalId = (string) $row[$target['column']];
                break;
            }
        }
        if ($externalId === '') {
            $this->redirect('/orders?error=' . urlencode("This order is not in {$target['label']} yet, so there is no order there to mark as shipped."));
            return;
        }

        foreach ($rows as $row) {
            if (!empty($row['platform_tracking_pushed_at'])) {
                $this->redirect('/orders?error=' . urlencode("Tracking for this order was already sent to {$target['label']}."));
                return;
            }
        }

        $tracking = $this->trackingFor($db, $rows, (int) $store['id']);
        if ($tracking === null) {
            $this->redirect('/orders?error=' . urlencode('This order has no tracking number yet. Create a shipment with a tracking (AWB) number first.'));
            return;
        }

        if ($platform === 'ebay') {
            $token = EbayAuthService::getValidAccessToken($store);
            if ($token === null) {
                $this->redirect('/orders?error=' . urlencode('This store is not connected to eBay. Use "Connect eBay" on the Stores page.'));
                return;
            }
            $result = $this->pushService->pushEbayTracking($token, $externalId, $tracking['number'], $tracking['courier'], $tracking['shipped_at']);
        } else {
            $result = match ($platform) {
                'shopify'     => $this->pushService->pushShopifyTracking($store, $externalId, $tracking['number'], $tracking['courier']),
                'woocommerce' => $this->pushService->pushWooCommerceTracking($store, $externalId, $tracking['number'], $tracking['courier']),
                'bigcommerce' => $this->pushService->pushBigCommerceTracking($store, $externalId, $tracking['number'], $tracking['courier']),
                'magento'     => $this->pushService->pushMagentoTracking($store, $externalId, $tracking['number'], $tracking['courier']),
            };
        }

        if (!$result['ok']) {
            $this->redirect('/orders?error=' . urlencode($result['message']));
            return;
        }

        // Is platform order ki saari rows pe "tracking bhej di" likh do
        $db->prepare("UPDATE orders SET platform_tracking_pushed_at = NOW() WHERE {$target['column']} = ? AND store_id = ?")
           ->execute([$externalId, (int) $store['id']]);

        $this->auditService->log(
            (int) $store['id'],
            'tracking_push',
            'order',
            (string) $rows[0]['id'],
            "Tracking {$tracking['number']} sent to {$target['label']} (order {$externalId})."
        );

        $this->redirect('/orders?pushed=' . urlencode($target['label'] . ' (tracking)'));
    }

    // ---------- Helpers ----------

    /** Order ki saari rows (ek order mein kai products = kai rows, ek hi order_group). Sirf isi store ki. */
    private function orderRows(PDO $db, int $id, int $storeId): array
    {
        $stmt = $db->prepare("SELECT * FROM orders WHERE id = ? AND store_id = ?");
        $stmt->execute([$id, $storeId]);
        $order = $stmt->fetch();
        if (!$order) {
            return [];
        }
        if (empty($order['order_group'])) {
            return [$order];
        }

        $stmt = $db->prepare("SELECT * FROM orders WHERE order_group = ? AND store_id = ? ORDER BY id ASC");
        $stmt->execute([$order['order_group'], $storeId]);
        return $stmt->fetchAll() ?: [$order];
    }

    /** Rows ke products (isi store ke) — [product_id => row] */
    private function productsFor(PDO $db, array $rows, int $storeId): array
    {
        $products = [];
        foreach ($rows as $row) {
            $productId = (int) ($row['product_id'] ?? 0);
            if ($productId > 0 && !isset($products[$productId])) {
                $stmt = $db->prepare("SELECT * FROM products WHERE id = ? AND store_id = ?");
                $stmt->execute([$productId, $storeId]);
                if ($p = $stmt->fetch()) {
                    $products[$productId] = $p;
                }
            }
        }
        return $products;
    }

    /** Tracking: pehle Finovo shipment se, warna CJdropshipping wala. Na ho to null. */
    private function trackingFor(PDO $db, array $rows, int $storeId): ?array
    {
        foreach ($rows as $row) {
            if (empty($row['shipment_id'])) {
                continue;
            }
            $s = $db->prepare("SELECT tracking_number, courier_name, dispatched_at FROM shipments WHERE id = ? AND store_id = ?");
            $s->execute([(int) $row['shipment_id'], $storeId]);
            $shipment = $s->fetch();
            if ($shipment && trim((string) $shipment['tracking_number']) !== '') {
                return [
                    'number' => trim((string) $shipment['tracking_number']),
                    'courier' => $shipment['courier_name'],
                    'shipped_at' => $shipment['dispatched_at'],
                ];
            }
        }

        foreach ($rows as $row) {
            if (!empty($row['cj_tracking_number'])) {
                return [
                    'number' => trim((string) $row['cj_tracking_number']),
                    'courier' => $row['cj_logistic_name'] ?? null,
                    'shipped_at' => null,
                ];
            }
        }
        return null;
    }
}