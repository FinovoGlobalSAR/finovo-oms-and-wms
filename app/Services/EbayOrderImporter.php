<?php
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../Models/Warehouse.php';
require_once __DIR__ . '/InventoryService.php';
require_once __DIR__ . '/../Middlewares/ProductAvailabilityGuard.php';

class EbayOrderImporter
{
    private Warehouse $warehouseModel;
    private InventoryService $inventoryService;
    private ProductAvailabilityGuard $availabilityGuard;

    public function __construct()
    {
        $this->warehouseModel = new Warehouse();
        $this->inventoryService = new InventoryService();
        $this->availabilityGuard = new ProductAvailabilityGuard();
    }

    /**
     * @return int kitne naye orders aaye
     */
    public function importOrders(array $store, array $orders): int
    {
        $db = Database::getConnection();
        $storeId = (int) $store['id'];
        $defaultWarehouse = $this->warehouseModel->getDefault($storeId);

        if (!$defaultWarehouse) {
            error_log("eBay import: store {$storeId} has no default warehouse.");
            return 0;
        }

        $importedCount = 0;

        foreach ($orders as $order) {
            $externalId = (string) ($order['orderId'] ?? '');
            if ($externalId === '') continue;

            // Pehle se aaya hua order dobara mat daalo
            $check = $db->prepare("SELECT id FROM orders WHERE external_ebay_order_id = ? AND store_id = ?");
            $check->execute([$externalId, $storeId]);
            if ($check->fetch()) continue;

            $lineItems = $order['lineItems'][0] ?? [];
            $productName = $lineItems['title'] ?? 'Unknown product';
            $quantity = max(1, (int) ($lineItems['quantity'] ?? 1));

            $guard = $this->availabilityGuard->check($storeId, $productName);
            if (!$guard['allowed']) {
                continue;
            }
            $productId = $guard['product_id'];

            $available = $this->inventoryService->checkAvailability($productId, null, (int) $defaultWarehouse['id']);
            if ($available < $quantity) {
                continue;
            }

            $buyer = $order['buyer']['username'] ?? ('Order #' . $externalId);
            $price = (float) ($order['pricingSummary']['total']['value'] ?? 0);

            $ebayStatus = $order['orderFulfillmentStatus'] ?? 'NOT_STARTED';
            $orderStatus = match ($ebayStatus) {
                'FULFILLED' => 'delivered',
                'IN_PROGRESS' => 'processing',
                default => 'pending',
            };
            $paymentStatus = ($order['orderPaymentStatus'] ?? '') === 'PAID' ? 'paid' : 'unpaid';

            $reserve = $this->inventoryService->reserve($productId, null, (int) $defaultWarehouse['id'], $quantity, 'ebay_pull', "external:{$externalId}", 'ebay_sync');
            if (!$reserve['ok']) {
                continue;
            }

            $stmt = $db->prepare(
                "INSERT INTO orders (store_id, customer_name, product_name, quantity, price, source, external_ebay_order_id, status, payment_status, product_id, warehouse_id)
                 VALUES (?, ?, ?, ?, ?, 'ebay_pull', ?, ?, ?, ?, ?)"
            );
            $stmt->execute([$storeId, $buyer, $productName, $quantity, $price, $externalId, $orderStatus, $paymentStatus, $productId, $defaultWarehouse['id']]);

            $importedCount++;
        }

        return $importedCount;
    }
}