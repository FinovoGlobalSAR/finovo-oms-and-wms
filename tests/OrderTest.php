<?php
require_once __DIR__ . '/../app/Models/Order.php';
require_once __DIR__ . '/../app/Models/Store.php';

use PHPUnit\Framework\TestCase;

class OrderTest extends TestCase
{
    private Order $orderModel;
    private Product $productModel;
    private InventoryService $inventoryService;
    private int $storeId;
    private int $warehouseId;
    private int $testProductId;
    private array $createdOrderIds = [];

    protected function setUp(): void
    {
        $this->orderModel = new Order();
        $this->productModel = new Product();
        $this->inventoryService = new InventoryService();

        $db = Database::getConnection();

        $store = $db->query("SELECT id FROM stores LIMIT 1")->fetch();
        $this->storeId = (int) $store['id'];

        $warehouse = $db->query("SELECT id FROM warehouses LIMIT 1")->fetch();
        $this->warehouseId = (int) $warehouse['id'];

        $insert = $db->prepare("INSERT INTO products (store_id, name, price) VALUES (?, ?, ?)");
        $insert->execute([$this->storeId, 'PHPUnit Order Test Product', 250]);
        $this->testProductId = (int) $db->lastInsertId();

        $this->productModel->setWarehouseStock($this->testProductId, $this->warehouseId, 10);
    }

    protected function tearDown(): void
    {
        $db = Database::getConnection();
        foreach ($this->createdOrderIds as $orderId) {
            $db->prepare("DELETE FROM orders WHERE id = ?")->execute([$orderId]);
        }
        $db->prepare("DELETE FROM product_warehouse_stock WHERE product_id = ?")->execute([$this->testProductId]);
        $db->prepare("DELETE FROM inventory_ledger WHERE product_id = ?")->execute([$this->testProductId]);
        $db->prepare("DELETE FROM products WHERE id = ?")->execute([$this->testProductId]);
    }

    public function testCreatingOrderReducesStock(): void
    {
        $availableBefore = $this->inventoryService->checkAvailability($this->testProductId, null, $this->warehouseId);

        $result = $this->inventoryService->reserve(
            $this->testProductId, null, $this->warehouseId, 3, 'manual_order', 'phpunit', 'tester'
        );
        $this->assertTrue($result['ok']);

        $db = Database::getConnection();
        $stmt = $db->prepare(
            "INSERT INTO orders (store_id, customer_name, product_name, quantity, price, source, product_id, warehouse_id)
             VALUES (?, 'PHPUnit Customer', 'PHPUnit Order Test Product', 3, 250, 'manual', ?, ?)"
        );
        $stmt->execute([$this->storeId, $this->testProductId, $this->warehouseId]);
        $this->createdOrderIds[] = (int) $db->lastInsertId();

        $availableAfter = $this->inventoryService->checkAvailability($this->testProductId, null, $this->warehouseId);

        $this->assertEquals($availableBefore - 3, $availableAfter);
    }

    public function testAllByStoreOnlyReturnsThatStoresOrders(): void
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            "INSERT INTO orders (store_id, customer_name, product_name, quantity, price, source, product_id, warehouse_id)
             VALUES (?, 'PHPUnit Customer 2', 'PHPUnit Order Test Product', 1, 250, 'manual', ?, ?)"
        );
        $stmt->execute([$this->storeId, $this->testProductId, $this->warehouseId]);
        $orderId = (int) $db->lastInsertId();
        $this->createdOrderIds[] = $orderId;

        $orders = $this->orderModel->allByStore($this->storeId);
        $orderIds = array_map(fn($o) => (int) $o['id'], $orders);

        $this->assertContains($orderId, $orderIds);

        foreach ($orders as $order) {
            $this->assertEquals($this->storeId, (int) $order['store_id']);
        }
    }

    public function testFilterBySourceAndStoreOnlyReturnsMatchingSource(): void
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            "INSERT INTO orders (store_id, customer_name, product_name, quantity, price, source, product_id, warehouse_id)
             VALUES (?, 'PHPUnit Customer 3', 'PHPUnit Order Test Product', 1, 250, 'csv_import', ?, ?)"
        );
        $stmt->execute([$this->storeId, $this->testProductId, $this->warehouseId]);
        $this->createdOrderIds[] = (int) $db->lastInsertId();

        $orders = $this->orderModel->filterBySourceAndStore('csv_import', $this->storeId);

        $this->assertNotEmpty($orders);
        foreach ($orders as $order) {
            $this->assertEquals('csv_import', $order['source']);
        }
    }
}