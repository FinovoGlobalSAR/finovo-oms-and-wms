<?php
use PHPUnit\Framework\TestCase;

class InventoryServiceTest extends TestCase
{
    private InventoryService $inventoryService;
    private Product $productModel;
    private Warehouse $warehouseModel;
    private int $testProductId;
    private int $testWarehouseId;

    protected function setUp(): void
    {
        $this->inventoryService = new InventoryService();
        $this->productModel = new Product();
        $this->warehouseModel = new Warehouse();

        $db = Database::getConnection();

        $store = $db->query("SELECT id FROM stores LIMIT 1")->fetch();
        $storeId = (int) $store['id'];

        $warehouse = $db->query("SELECT id FROM warehouses LIMIT 1")->fetch();
        $this->testWarehouseId = (int) $warehouse['id'];

        $stmt = $db->prepare("INSERT INTO products (store_id, name, price) VALUES (?, ?, ?)");
        $stmt->execute([$storeId, 'PHPUnit Test Product', 100]);
        $this->testProductId = (int) $db->lastInsertId();

        $this->productModel->setWarehouseStock($this->testProductId, $this->testWarehouseId, 10);
    }

    protected function tearDown(): void
    {
        // Test ke baad khud apna banaya hua data saaf kar deta hai
        $db = Database::getConnection();
        $db->prepare("DELETE FROM product_warehouse_stock WHERE product_id = ?")->execute([$this->testProductId]);
        $db->prepare("DELETE FROM inventory_ledger WHERE product_id = ?")->execute([$this->testProductId]);
        $db->prepare("DELETE FROM products WHERE id = ?")->execute([$this->testProductId]);
    }

    public function testReserveSucceedsWhenStockIsSufficient(): void
    {
        $result = $this->inventoryService->reserve(
            $this->testProductId, null, $this->testWarehouseId, 5, 'test', 'phpunit', 'tester'
        );

        $this->assertTrue($result['ok']);
        $this->assertEquals(5, $result['available']);
    }

    public function testReserveFailsWhenStockIsInsufficient(): void
    {
        $result = $this->inventoryService->reserve(
            $this->testProductId, null, $this->testWarehouseId, 999, 'test', 'phpunit', 'tester'
        );

        $this->assertFalse($result['ok']);
        $this->assertEquals('INVENTORY_UNAVAILABLE', $result['code']);
    }

    public function testReleaseAddsStockBack(): void
    {
        $this->inventoryService->reserve($this->testProductId, null, $this->testWarehouseId, 3, 'test', 'phpunit', 'tester');
        $availableAfterReserve = $this->inventoryService->checkAvailability($this->testProductId, null, $this->testWarehouseId);

        $this->inventoryService->release($this->testProductId, null, $this->testWarehouseId, 3, 'test', 'phpunit', 'tester');
        $availableAfterRelease = $this->inventoryService->checkAvailability($this->testProductId, null, $this->testWarehouseId);

        $this->assertEquals($availableAfterReserve + 3, $availableAfterRelease);
    }
}