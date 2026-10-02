<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../Models/Store.php';
require_once __DIR__ . '/../Models/Product.php';
require_once __DIR__ . '/../Models/Warehouse.php';
require_once __DIR__ . '/../Services/InventoryService.php';
require_once __DIR__ . '/../Services/IntegrationErrorService.php';
require_once __DIR__ . '/../Middlewares/ProductAvailabilityGuard.php';
require_once __DIR__ . '/../Connectors/MagentoConnector.php';

/**
 * Magento 2: Products pull + Orders pull.
 *   /products/sync-magento  → Magento ke products (price + stock) Finovo mein
 *   /orders/sync-magento    → Magento ke naye orders Finovo mein (stock reserve ke saath)
 *
 * Store pe "Magento Store URL" aur "Magento Access Token" save hone chahiye (Stores → Edit).
 */
class MagentoController extends Controller
{
    private Store $storeModel;
    private Product $productModel;
    private Warehouse $warehouseModel;
    private InventoryService $inventoryService;
    private IntegrationErrorService $errorService;
    private ProductAvailabilityGuard $availabilityGuard;

    public function __construct()
    {
        parent::__construct();
        $this->requireRole(['admin', 'manager', 'sales staff', 'warehouse staff']);
        $this->requireStoreContext();
        $this->storeModel = new Store();
        $this->productModel = new Product();
        $this->warehouseModel = new Warehouse();
        $this->inventoryService = new InventoryService();
        $this->errorService = new IntegrationErrorService();
        $this->availabilityGuard = new ProductAvailabilityGuard();
    }

    private function connector(array $store): ?MagentoConnector
    {
        if (empty($store['magento_store_url']) || empty($store['magento_access_token'])) {
            return null;
        }
        return new MagentoConnector($store['magento_store_url'], $store['magento_access_token']);
    }

    // ---------- Products Pull ----------

    public function syncProducts(): void
    {
        $store = $this->getCurrentStore();
        $defaultWarehouse = $this->warehouseModel->getDefault((int) $store['id']);
        $connector = $this->connector($store);

        if (!$connector) {
            $this->redirect('/products?error=' . urlencode('Please save this store\'s Magento Store URL and Access Token first (Stores → Edit).'));
            return;
        }

        $result = $connector->fetchProducts();
        if (!$result['ok']) {
            $this->errorService->logFailure((int) $store['id'], 'magento', 'sync_products', null, null, $result['code'] ?? 0, $result['message']);
            $this->storeModel->markUnhealthy((int) $store['id'], $result['message']);
            $this->redirect('/products?error=' . urlencode($result['message']));
            return;
        }

        $db = Database::getConnection();
        $importedCount = 0;

        foreach ($result['products'] as $mp) {
            if ($mp['id'] === '') continue;

            $check = $db->prepare("SELECT id FROM products WHERE store_id = ? AND external_magento_product_id = ?");
            $check->execute([$store['id'], $mp['id']]);
            if ($check->fetch()) continue;

            $stmt = $db->prepare("INSERT INTO products (store_id, name, sku, price, external_magento_product_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$store['id'], $mp['name'], $mp['sku'], $mp['price'], $mp['id']]);
            $productId = (int) $db->lastInsertId();
            $importedCount++;

            if ($defaultWarehouse) {
                $this->productModel->setWarehouseStock($productId, (int) $defaultWarehouse['id'], $mp['qty']);
            }
        }

        $this->errorService->markResolved((int) $store['id'], 'magento', 'sync_products', null);
        $this->storeModel->markHealthy((int) $store['id']);
        $this->redirect('/products?synced=' . $importedCount);
    }

    // ---------- Orders Pull ----------

    public function syncOrders(): void
    {
        $store = $this->getCurrentStore();
        $defaultWarehouse = $this->warehouseModel->getDefault((int) $store['id']);
        $connector = $this->connector($store);

        if (!$connector) {
            $this->redirect('/orders?error=' . urlencode('Please save this store\'s Magento Store URL and Access Token first (Stores → Edit).'));
            return;
        }

        $result = $connector->fetchOrders();
        if (!$result['ok']) {
            $this->errorService->logFailure((int) $store['id'], 'magento', 'sync_orders', null, null, $result['code'] ?? 0, $result['message']);
            $this->storeModel->markUnhealthy((int) $store['id'], $result['message']);
            $this->redirect('/orders?error=' . urlencode($result['message']));
            return;
        }

        $db = Database::getConnection();
        $importedCount = 0;

        foreach ($result['orders'] as $order) {
            $externalId = (string) ($order['entity_id'] ?? '');
            if ($externalId === '') continue;

            $magentoStatus = strtolower((string) ($order['status'] ?? 'pending'));
            $orderStatus = match ($magentoStatus) {
                'complete' => 'delivered',
                'processing' => 'processing',
                'canceled', 'closed' => 'cancelled',
                default => 'pending',
            };
            $paymentStatus = in_array($magentoStatus, ['processing', 'complete'], true) ? 'paid' : 'unpaid';

            // Pehle se aaya hua order → sirf status update
            $check = $db->prepare("SELECT id FROM orders WHERE external_magento_order_id = ? AND store_id = ?");
            $check->execute([$externalId, $store['id']]);
            $existing = $check->fetch();
            if ($existing) {
                $db->prepare("UPDATE orders SET status = ?, payment_status = ? WHERE id = ?")
                   ->execute([$orderStatus, $paymentStatus, $existing['id']]);
                continue;
            }

            // Pehla asli line item (configurable ke "child" rows chhod do)
            $line = null;
            foreach (($order['items'] ?? []) as $item) {
                if (empty($item['parent_item_id'])) {
                    $line = $item;
                    break;
                }
            }
            if (!$line) continue;

            $productName = $line['name'] ?? 'Unknown product';
            $quantity = max(1, (int) ($line['qty_ordered'] ?? 1));

            $guard = $this->availabilityGuard->check((int) $store['id'], $productName);
            if (!$guard['allowed'] || !$defaultWarehouse) {
                continue;
            }
            $productId = $guard['product_id'];

            $available = $this->inventoryService->checkAvailability($productId, null, (int) $defaultWarehouse['id']);
            if ($available < $quantity) {
                continue;
            }

            $customerName = trim(($order['customer_firstname'] ?? '') . ' ' . ($order['customer_lastname'] ?? ''));
            if ($customerName === '') {
                $billing = $order['billing_address'] ?? [];
                $customerName = trim(($billing['firstname'] ?? '') . ' ' . ($billing['lastname'] ?? ''));
            }
            if ($customerName === '') {
                $customerName = $order['customer_email'] ?? ('Order #' . ($order['increment_id'] ?? $externalId));
            }

            $price = (float) ($order['grand_total'] ?? 0);
            $paymentMethod = $order['payment']['method'] ?? 'Other';

            $reserve = $this->inventoryService->reserve($productId, null, (int) $defaultWarehouse['id'], $quantity, 'magento_pull', "external:{$externalId}", 'magento_sync');
            if (!$reserve['ok']) {
                continue;
            }

            $stmt = $db->prepare(
                "INSERT INTO orders (store_id, customer_name, product_name, quantity, price, source, external_magento_order_id, status, payment_status, product_id, warehouse_id, payment_method)
                 VALUES (?, ?, ?, ?, ?, 'magento_pull', ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([$store['id'], $customerName, $productName, $quantity, $price, $externalId, $orderStatus, $paymentStatus, $productId, $defaultWarehouse['id'], $paymentMethod]);

            $importedCount++;
        }

        $this->errorService->markResolved((int) $store['id'], 'magento', 'sync_orders', null);
        $this->storeModel->markHealthy((int) $store['id']);
        $this->redirect('/orders?synced=' . $importedCount);
    }
}