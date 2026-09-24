<?php
use PHPUnit\Framework\TestCase;

class MultiWarehouseTest extends TestCase
{
    private InventoryService $inventoryService;
    private Product $productModel;
    private int $testProductId;
    private int $storeId;
    private array $warehouseIds = [];

    protected function setUp(): void
    {
        $this->inventoryService = new InventoryService();
        $this->productModel = new Product();

        $db = Database::getConnection();

        $store = $db->query("SELECT id FROM stores LIMIT 1")->fetch();
        $this->storeId = (int) $store['id'];

        // Store se linked 2 warehouses dhundte hain testing ke liye
        $stmt = $db->prepare(
            "SELECT w.id FROM warehouses w
             INNER JOIN store_warehouses sw ON sw.warehouse_id = w.id
             WHERE sw.store_id = ? LIMIT 2"
        );
        $stmt->execute([$this->storeId]);
        $warehouses = $stmt->fetchAll();

        if (count($warehouses) < 2) {
            $this->markTestSkipped('Is store ke saath kam se kam 2 warehouses linked nahi hain — test skip.');
        }

        foreach ($warehouses as $w) {
            $this->warehouseIds[] = (int) $w['id'];
        }

        $insert = $db->prepare("INSERT INTO products (store_id, name, price) VALUES (?, ?, ?)");
        $insert->execute([$this->storeId, 'PHPUnit Multi-Warehouse Product', 100]);
        $this->testProductId = (int) $db->lastInsertId();

        // 2 units warehouse 1 mein, 3 units warehouse 2 mein — total 5
        $this->productModel->setWarehouseStock($this->testProductId, $this->warehouseIds[0], 2);
        $this->productModel->setWarehouseStock($this->testProductId, $this->warehouseIds[1], 3);
    }

    protected function tearDown(): void
    {
        $db = Database::getConnection();
        $db->prepare("DELETE FROM product_warehouse_stock WHERE product_id = ?")->execute([$this->testProductId]);
        $db->prepare("DELETE FROM inventory_ledger WHERE product_id = ?")->execute([$this->testProductId]);
        $db->prepare("DELETE FROM products WHERE id = ?")->execute([$this->testProductId]);
    }

    public function testAllocationSplitsAcrossWarehousesWhenNeeded(): void
    {
        // 5 quantity maangte hain — na to Warehouse 1 (2) na Warehouse 2 (3)
        // akele poora kar sakta hai, dono milke hi 5 hote hain.
        $result = $this->inventoryService->reserveAcrossWarehouses(
            $this->testProductId, null, $this->storeId, 5, 'test', 'phpunit', 'tester'
        );

        $this->assertTrue($result['ok']);
        $this->assertCount(2, $result['allocations']);

        $totalAllocated = array_sum(array_column($result['allocations'], 'quantity'));
        $this->assertEquals(5, $totalAllocated);
    }

    public function testAllocationFailsWhenTotalAcrossWarehousesInsufficient(): void
    {
        // Total available sirf 5 hai (2+3), 100 maangna fail hona chahiye
        $result = $this->inventoryService->reserveAcrossWarehouses(
            $this->testProductId, null, $this->storeId, 100, 'test', 'phpunit', 'tester'
        );

        $this->assertFalse($result['ok']);
        $this->assertEquals(5, $result['available']);
    }

    public function testPartialFailureRollsBackAlreadyReservedStock(): void
    {
        // Pehle poori tarah reserve karke dekhte hain koi bhi stock reh na jaye
        $before1 = $this->inventoryService->checkAvailability($this->testProductId, null, $this->warehouseIds[0]);
        $before2 = $this->inventoryService->checkAvailability($this->testProductId, null, $this->warehouseIds[1]);

        // Fail hone wali request bhejo (100 units, jab sirf 5 available hain)
        $this->inventoryService->reserveAcrossWarehouses(
            $this->testProductId, null, $this->storeId, 100, 'test', 'phpunit', 'tester'
        );

        // Confirm karo stock waisa hi hai jaisa pehle tha — koi partial deduction nahi hua
        $after1 = $this->inventoryService->checkAvailability($this->testProductId, null, $this->warehouseIds[0]);
        $after2 = $this->inventoryService->checkAvailability($this->testProductId, null, $this->warehouseIds[1]);

        $this->assertEquals($before1, $after1);
        $this->assertEquals($before2, $after2);
    }
}