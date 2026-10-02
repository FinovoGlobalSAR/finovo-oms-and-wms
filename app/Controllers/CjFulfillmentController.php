<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../Connectors/CjDropshippingConnector.php';
require_once __DIR__ . '/../Services/AuditLogService.php';

/**
 * Finovo ka order CJdropshipping ko fulfillment ke liye bhejna.
 *
 * Flow:
 *  1. /orders/cj-fulfill?id=..      → form: customer ka address + har product ka CJ variant
 *  2. "Get shipping options"        → CJ se courier options aur kharcha (freightCalculate)
 *  3. "Send to CJ"                  → CJ pe order ban jata hai (payType 3 = sirf order banao,
 *                                     payment CJ dashboard se hoti hai — khud paisa nahi katta)
 *  4. /orders/cj-refresh?id=..      → CJ se status + tracking number wapas lao (ek order)
 *  5. /orders/sync-cj               → "Sync CJ" button: saare CJ orders ka status + tracking ek saath
 *
 * Sirf woh products bheje ja sakte hain jo CJ se sync hue hain (external_cj_product_id).
 */
class CjFulfillmentController extends Controller
{
    private AuditLogService $auditService;

    public function __construct()
    {
        parent::__construct();
        $this->requireRole(['admin', 'manager', 'sales staff']);
        $this->requireStoreContext();
        $this->auditService = new AuditLogService();
    }

    // ------------------------------------------------------------ Step 1: form

    public function form(): void
    {
        $store = $this->getCurrentStore();
        $orderId = (int) ($_GET['id'] ?? 0);

        $this->renderForm($store, $orderId, $this->defaultAddress($store, $orderId));
    }

    // ------------------------------------------------- Step 2: shipping options

    public function quote(): void
    {
        $store = $this->getCurrentStore();
        $orderId = (int) ($_POST['order_id'] ?? 0);
        $address = $this->addressFromPost();

        $lines = $this->loadLines((int) $store['id'], $orderId);
        if ($lines === null) {
            $this->redirect('/orders?error=' . urlencode('Order not found.'));
            return;
        }

        $this->saveChosenVariants((int) $store['id'], $lines);
        $lines = $this->loadLines((int) $store['id'], $orderId);

        $error = $this->validate($address, $lines, false);
        if ($error) {
            $this->renderForm($store, $orderId, $address, [], $error);
            return;
        }

        $connector = $this->connector($store);
        $result = $connector->freightCalculate(
            $address['from_country_code'],
            $address['country_code'],
            $address['zip'],
            $this->cjProducts($lines)
        );

        if (!$result['ok']) {
            $this->renderForm($store, $orderId, $address, [], $result['message']);
            return;
        }

        if (empty($result['options'])) {
            $this->renderForm($store, $orderId, $address, [], 'CJ has no shipping option for this country / product combination.');
            return;
        }

        $this->renderForm($store, $orderId, $address, $result['options']);
    }

    // --------------------------------------------------------- Step 3: submit

    public function submit(): void
    {
        $store = $this->getCurrentStore();
        $orderId = (int) ($_POST['order_id'] ?? 0);
        $address = $this->addressFromPost();

        $lines = $this->loadLines((int) $store['id'], $orderId);
        if ($lines === null) {
            $this->redirect('/orders?error=' . urlencode('Order not found.'));
            return;
        }

        $this->saveChosenVariants((int) $store['id'], $lines);
        $lines = $this->loadLines((int) $store['id'], $orderId);

        $error = $this->validate($address, $lines, true);
        if ($error) {
            $this->renderForm($store, $orderId, $address, [], $error);
            return;
        }

        if (!empty($lines[0]['cj_order_id'])) {
            $this->redirect('/orders?error=' . urlencode('This order was already sent to CJ (CJ order ' . $lines[0]['cj_order_id'] . ').'));
            return;
        }

        $mainId = (int) $lines[0]['id'];
        $payload = [
            'orderNumber' => 'FINOVO-' . $mainId,
            'shippingCountryCode' => $address['country_code'],
            'shippingCountry' => $address['country'],
            'shippingProvince' => $address['province'],
            'shippingCity' => $address['city'],
            'shippingCustomerName' => $address['customer_name'],
            'shippingAddress' => $address['address'],
            'shippingAddress2' => $address['address2'],
            'shippingZip' => $address['zip'],
            'shippingPhone' => $address['phone'],
            'logisticName' => $address['logistic_name'],
            'fromCountryCode' => $address['from_country_code'],
            'products' => $this->cjProducts($lines),
        ];

        $result = $this->connector($store)->createOrder($payload);

        if (!$result['ok']) {
            $this->auditService->log((int) $store['id'], 'cj_order_push_failed', 'order', (string) $mainId, $result['message']);
            $this->renderForm($store, $orderId, $address, [], $result['message']);
            return;
        }

        $db = Database::getConnection();
        $update = $db->prepare(
            "UPDATE orders SET cj_order_id = ?, cj_order_status = ?, cj_logistic_name = ?, cj_pushed_at = NOW() WHERE id = ? AND store_id = ?"
        );
        foreach ($lines as $line) {
            $update->execute([$result['cj_order_id'], $result['status'], $address['logistic_name'], (int) $line['id'], (int) $store['id']]);
        }

        $this->auditService->log(
            (int) $store['id'],
            'cj_order_pushed',
            'order',
            (string) $mainId,
            'Sent to CJdropshipping. CJ order ID: ' . $result['cj_order_id'] . ' (pay for it in the CJ dashboard).'
        );

        $this->redirect('/orders?updated=1&cj_sent=' . urlencode($result['cj_order_id']));
    }

    // ------------------------------------------------ Step 4: status refresh

    public function refresh(): void
    {
        $store = $this->getCurrentStore();
        $orderId = (int) ($_GET['id'] ?? 0);
        $lines = $this->loadLines((int) $store['id'], $orderId);

        if ($lines === null || empty($lines[0]['cj_order_id'])) {
            $this->redirect('/orders?error=' . urlencode('This order has not been sent to CJ yet.'));
            return;
        }

        $result = $this->connector($store)->getOrderDetail($lines[0]['cj_order_id']);
        if (!$result['ok']) {
            $this->redirect('/orders/cj-fulfill?id=' . $orderId . '&error=' . urlencode($result['message']));
            return;
        }

        $db = Database::getConnection();
        $update = $db->prepare(
            "UPDATE orders SET cj_order_status = ?, cj_tracking_number = COALESCE(?, cj_tracking_number), cj_logistic_name = COALESCE(?, cj_logistic_name) WHERE id = ? AND store_id = ?"
        );
        foreach ($lines as $line) {
            $update->execute([$result['status'], $result['tracking_number'] ?: null, $result['logistic_name'] ?: null, (int) $line['id'], (int) $store['id']]);
        }

        $this->redirect('/orders/cj-fulfill?id=' . $orderId . '&refreshed=1');
    }

    // ------------------------------------- Step 5: "Sync CJ" (saare orders)

    /**
     * Orders page ka "Sync CJ" button.
     * Jo orders CJ ko bheje ja chuke hain aur abhi deliver/cancel nahi hue,
     * un sab ka status aur tracking number CJ se la ke update karta hai.
     */
    public function syncAll(): void
    {
        $store = $this->getCurrentStore();

        if (empty($store['cj_email']) || empty($store['cj_api_key'])) {
            $this->redirect('/orders?error=' . urlencode('Please save this store\'s CJdropshipping Email and API Key first (Stores → Edit).'));
            return;
        }

        $db = Database::getConnection();
        // CJ 1 second mein 1 request leta hai, is liye ek dafa mein max 20 orders
        $stmt = $db->prepare(
            "SELECT DISTINCT cj_order_id FROM orders
             WHERE store_id = ? AND cj_order_id IS NOT NULL AND cj_order_id <> ''
               AND (cj_order_status IS NULL OR cj_order_status NOT IN ('DELIVERED', 'CANCELLED'))
             ORDER BY cj_pushed_at DESC
             LIMIT 20"
        );
        $stmt->execute([(int) $store['id']]);
        $cjOrderIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($cjOrderIds)) {
            $this->redirect('/orders?cj_synced=0');
            return;
        }

        $connector = $this->connector($store);
        $update = $db->prepare(
            "UPDATE orders SET cj_order_status = ?, cj_tracking_number = COALESCE(?, cj_tracking_number), cj_logistic_name = COALESCE(?, cj_logistic_name)
             WHERE cj_order_id = ? AND store_id = ?"
        );

        $updated = 0;
        foreach ($cjOrderIds as $cjOrderId) {
            $result = $connector->getOrderDetail((string) $cjOrderId);

            if (!$result['ok']) {
                // Pehla hi fail ho (jaise login galat) to wahin ruk ke error dikhao
                if ($updated === 0) {
                    $this->storeModelMarkUnhealthy((int) $store['id'], $result['message']);
                    $this->redirect('/orders?error=' . urlencode($result['message']));
                    return;
                }
                continue;
            }

            $update->execute([
                $result['status'],
                $result['tracking_number'] ?: null,
                $result['logistic_name'] ?: null,
                (string) $cjOrderId,
                (int) $store['id'],
            ]);
            $updated++;
        }

        $this->auditService->log((int) $store['id'], 'cj_status_sync', 'order', null, "CJ status synced for {$updated} order(s).");
        $this->redirect('/orders?cj_synced=' . $updated);
    }

    private function storeModelMarkUnhealthy(int $storeId, string $message): void
    {
        Database::getConnection()
            ->prepare("UPDATE stores SET health_status = 'sync_error', last_error = ? WHERE id = ?")
            ->execute([$message, $storeId]);
    }

    // ================================================================ helpers

    private function connector(array $store): CjDropshippingConnector
    {
        return new CjDropshippingConnector((string) $store['cj_email'], (string) $store['cj_api_key'], (int) $store['id']);
    }

    /**
     * Order ki saari lines (ek order_group ki) + har line ke product ki CJ IDs.
     */
    private function loadLines(int $storeId, int $orderId): ?array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM orders WHERE id = ? AND store_id = ?");
        $stmt->execute([$orderId, $storeId]);
        $main = $stmt->fetch();

        if (!$main) {
            return null;
        }

        $sql = "SELECT o.*, p.name AS p_name, p.external_cj_product_id, p.external_cj_variant_id
                FROM orders o LEFT JOIN products p ON p.id = o.product_id
                WHERE o.store_id = ? AND ";
        if (!empty($main['order_group'])) {
            $stmt = $db->prepare($sql . "o.order_group = ? ORDER BY o.id ASC");
            $stmt->execute([$storeId, $main['order_group']]);
        } else {
            $stmt = $db->prepare($sql . "o.id = ?");
            $stmt->execute([$storeId, $orderId]);
        }

        return $stmt->fetchAll();
    }

    private function defaultAddress(array $store, int $orderId): array
    {
        $lines = $this->loadLines((int) $store['id'], $orderId) ?? [];
        $main = $lines[0] ?? [];

        return [
            'customer_name' => $main['customer_name'] ?? '',
            'phone' => $main['customer_phone'] ?? '',
            'address' => trim((string) ($main['shipping_address'] ?? $main['billing_address'] ?? '')),
            'address2' => '',
            'city' => '',
            'province' => '',
            'zip' => '',
            'country_code' => '',
            'country' => '',
            'from_country_code' => 'CN',
            'logistic_name' => '',
        ];
    }

    private function addressFromPost(): array
    {
        $get = fn($key, $max) => mb_substr(trim((string) ($_POST[$key] ?? '')), 0, $max);

        return [
            'customer_name' => $get('customer_name', 50),
            'phone' => $get('phone', 20),
            'address' => $get('address', 500),
            'address2' => $get('address2', 500),
            'city' => $get('city', 50),
            'province' => $get('province', 50),
            'zip' => $get('zip', 20),
            'country_code' => strtoupper($get('country_code', 2)),
            'country' => $get('country', 50),
            'from_country_code' => strtoupper($get('from_country_code', 2)) ?: 'CN',
            'logistic_name' => $get('logistic_name', 100),
        ];
    }

    /**
     * Form mein jo variant chuna gaya (variant[product_id] = vid), use product pe save karo
     * taake agli baar dobara na chunna pade.
     */
    private function saveChosenVariants(int $storeId, array $lines): void
    {
        $chosen = $_POST['variant'] ?? [];
        if (!is_array($chosen)) {
            return;
        }

        $db = Database::getConnection();
        $update = $db->prepare("UPDATE products SET external_cj_variant_id = ? WHERE id = ? AND store_id = ?");

        foreach ($lines as $line) {
            $pid = (int) ($line['product_id'] ?? 0);
            $vid = trim((string) ($chosen[$pid] ?? ''));
            if ($pid > 0 && $vid !== '') {
                $update->execute([$vid, $pid, $storeId]);
            }
        }
    }

    private function validate(array $address, array $lines, bool $needLogistic): ?string
    {
        foreach ($lines as $line) {
            if (empty($line['external_cj_product_id'])) {
                return '"' . ($line['product_name'] ?? 'Product') . '" is not a CJdropshipping product. Only products synced from CJ can be sent to CJ.';
            }
            if (empty($line['external_cj_variant_id'])) {
                return 'Please choose the CJ variant for "' . ($line['product_name'] ?? 'Product') . '".';
            }
        }

        $required = [
            'customer_name' => 'Customer name',
            'address' => 'Address',
            'city' => 'City',
            'province' => 'Province / State',
            'country_code' => 'Country code',
            'country' => 'Country name',
        ];
        foreach ($required as $key => $label) {
            if ($address[$key] === '') {
                return "{$label} is required.";
            }
        }

        if (!preg_match('/^[A-Z]{2}$/', $address['country_code']) || !preg_match('/^[A-Z]{2}$/', $address['from_country_code'])) {
            return 'Country codes must be 2 letters, for example SA, US, PK, CN.';
        }

        if ($needLogistic && $address['logistic_name'] === '') {
            return 'Please click "Get shipping options" and choose a shipping method first.';
        }

        if (count($lines) > 20) {
            return 'CJ accepts a maximum of 20 products per order.';
        }

        return null;
    }

    private function cjProducts(array $lines): array
    {
        $products = [];
        foreach ($lines as $line) {
            $products[] = [
                'vid' => (string) $line['external_cj_variant_id'],
                'quantity' => max(1, (int) $line['quantity']),
                'storeLineItemId' => (string) $line['id'],
            ];
        }
        return $products;
    }

    private function renderForm(array $store, int $orderId, array $address, array $shippingOptions = [], ?string $error = null): void
    {
        $lines = $this->loadLines((int) $store['id'], $orderId);
        if ($lines === null) {
            $this->redirect('/orders?error=' . urlencode('Order not found.'));
            return;
        }

        $cjReady = !empty($store['cj_email']) && !empty($store['cj_api_key']);

        $variantChoices = [];
        if ($cjReady && empty($lines[0]['cj_order_id'])) {
            $connector = $this->connector($store);
            foreach ($lines as $line) {
                if (!empty($line['external_cj_product_id']) && empty($line['external_cj_variant_id'])) {
                    $result = $connector->getProductVariants($line['external_cj_product_id']);
                    if ($result['ok']) {
                        $variantChoices[(int) $line['product_id']] = $result['variants'];
                    } elseif (!$error) {
                        $error = $result['message'];
                    }
                }
            }
        }

        $this->view('orders/cj_fulfill', [
            'orderId' => $orderId,
            'lines' => $lines,
            'address' => $address,
            'shippingOptions' => $shippingOptions,
            'variantChoices' => $variantChoices,
            'cjReady' => $cjReady,
            'error' => $error ?? ($_GET['error'] ?? null),
            'refreshed' => isset($_GET['refreshed']),
        ]);
    }
}