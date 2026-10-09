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
require_once __DIR__ . '/../Services/OrderImportService.php';
require_once __DIR__ . '/../Services/PlatformOrderMapper.php';

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
        $this->requireRole(['admin', 'manager', 'warehouse staff']);
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
        $updatedCount = 0;
        $untracked = 0;

        foreach ($result['products'] as $mp) {
            if ($mp['id'] === '') continue;

            $check = $db->prepare("SELECT id FROM products WHERE store_id = ? AND external_magento_product_id = ?");
            $check->execute([$store['id'], $mp['id']]);
            $existingId = $check->fetchColumn();

            if ($existingId === false) {
                $stmt = $db->prepare("INSERT INTO products (store_id, name, sku, price, external_magento_product_id) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$store['id'], $mp['name'], $mp['sku'], $mp['price'], $mp['id']]);
                $productId = (int) $db->lastInsertId();
                $importedCount++;
            } else {
                $productId = (int) $existingId;
                $updatedCount++;
            }

            // Har sync pe stock bhi (pehle sirf naye product pe aata tha). null = Magento stock ginta hi nahi.
            if ($mp['qty'] === null) {
                $untracked++;
            } elseif ($defaultWarehouse) {
                // Stock Magento se hi aaya hai — wapas Magento pe mat bhejo
                StockPushService::pause();
                try {
                    $this->productModel->applyPlatformStock($productId, (int) $defaultWarehouse['id'], (int) $mp['qty']);
                } finally {
                    StockPushService::resume();
                }
            }
        }

        $this->errorService->markResolved((int) $store['id'], 'magento', 'sync_products', null);
        $this->storeModel->markHealthy((int) $store['id']);
        $this->redirect('/products?synced=' . $importedCount . '&stock_synced=' . $updatedCount . ($untracked > 0 ? '&untracked=' . $untracked : ''));
    }

    // ---------- Orders Pull ----------

    public function syncOrders(): void
    {
        $this->requireRole(['admin', 'manager', 'sales staff']);
        $store = $this->getCurrentStore();
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

        $importer = new OrderImportService();
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

            // Pehle se aaya hua order → sirf status update (us ki saari rows ka)
            if ($importer->exists((int) $store['id'], 'magento_pull', $externalId)) {
                $importer->updateStatus((int) $store['id'], 'magento_pull', $externalId, $orderStatus, $paymentStatus);
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

            // Order ke SAARE products (pehle sirf pehla aata tha)
            $import = $importer->import((int) $store['id'], 'magento_pull', $externalId, [
                'customer_name' => $customerName,
                'status' => $orderStatus,
                'payment_status' => $paymentStatus,
                'payment_method' => $order['payment']['method'] ?? 'Other',
                'customer' => PlatformOrderMapper::customer('magento', $order),
            ], PlatformOrderMapper::magentoLines($order), 'strict', 'magento_sync');

            if ($import['ok']) {
                $importedCount++;
            }
        }

        $this->errorService->markResolved((int) $store['id'], 'magento', 'sync_orders', null);
        $this->storeModel->markHealthy((int) $store['id']);
        $this->redirect('/orders?synced=' . $importedCount);
    }
}