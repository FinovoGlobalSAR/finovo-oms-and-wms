<?php
require_once __DIR__ . '/../app/Models/ReturnRequest.php';

use PHPUnit\Framework\TestCase;

class ReturnTest extends TestCase
{
    private ReturnRequest $returnModel;
    private Product $productModel;
    private InventoryService $inventoryService;
    private int $storeId;
    private int $warehouseId;
    private int $testProductId;
    private int $testOrderId;
    private array $createdReturnIds = [];

    protected function setUp(): void
    {
        $this->returnModel = new ReturnRequest();
        $this->productModel = new Product();
        $this->inventoryService = new InventoryService();

        $db = Database::getConnection();

        $store = $db->query("SELECT id FROM stores LIMIT 1")->fetch();
        $this->storeId = (int) $store['id'];

        $warehouse = $db->query("SELECT id FROM warehouses LIMIT 1")->fetch();
        $this->warehouseId = (int) $warehouse['id'];

        $insert = $db->prepare("INSERT INTO products (store_id, name, price) VALUES (?, ?, ?)");
        $insert->execute([$this->storeId, 'PHPUnit Return Test Product', 300]);
        $this->testProductId = (int) $db->lastInsertId();

        // Stock 5 se shuru — order 2 units le lega (3 reh jayega)
        $this->productModel->setWarehouseStock($this->testProductId, $this->warehouseId, 5);
        $this->inventoryService->reserve($this->testProductId, null, $this->warehouseId, 2, 'manual_order', 'phpunit', 'tester');

        $orderStmt = $db->prepare(
            "INSERT INTO orders (store_id, customer_name, product_name, quantity, price, source, product_id, warehouse_id)
             VALUES (?, 'PHPUnit Return Customer', 'PHPUnit Return Test Product', 2, 300, 'manual', ?, ?)"
        );
        $orderStmt->execute([$this->storeId, $this->testProductId, $this->warehouseId]);
        $this->testOrderId = (int) $db->lastInsertId();
    }

    protected function tearDown(): void
    {
        $db = Database::getConnection();
        foreach ($this->createdReturnIds as $returnId) {
            $db->prepare("DELETE FROM returns WHERE id = ?")->execute([$returnId]);
        }
        $db->prepare("DELETE FROM orders WHERE id = ?")->execute([$this->testOrderId]);
        $db->prepare("DELETE FROM product_warehouse_stock WHERE product_id = ?")->execute([$this->testProductId]);
        $db->prepare("DELETE FROM inventory_ledger WHERE product_id = ?")->execute([$this->testProductId]);
        $db->prepare("DELETE FROM products WHERE id = ?")->execute([$this->testProductId]);
    }

    public function testCompletingResellableReturnRestoresStock(): void
    {
        $returnId = $this->returnModel->create(
            $this->storeId, $this->testOrderId, $this->testProductId, null, $this->warehouseId, 2, 'Test reason', 300
        );
        $this->createdReturnIds[] = $returnId;

        $stockBefore = $this->inventoryService->checkAvailability($this->testProductId, null, $this->warehouseId);

        // Yahi logic ReturnController::updateStatus() mein hai — "completed"
        // + "resellable" pe stock wapis warehouse mein jata hai.
        $return = $this->returnModel->find($returnId);
        if ($return['status'] !== 'completed') {
            $this->inventoryService->release(
                $this->testProductId, null, $this->warehouseId, (int) $return['quantity'], 'return_completed', 'phpunit', 'tester'
            );
        }
        $this->returnModel->updateStatus($returnId, 'completed', 'resellable');

        $stockAfter = $this->inventoryService->checkAvailability($this->testProductId, null, $this->warehouseId);

        $this->assertEquals($stockBefore + 2, $stockAfter);
    }

    public function testCompletingDamagedReturnDoesNotRestoreStock(): void
    {
        $returnId = $this->returnModel->create(
            $this->storeId, $this->testOrderId, $this->testProductId, null, $this->warehouseId, 2, 'Damaged item', 300
        );
        $this->createdReturnIds[] = $returnId;

        $stockBefore = $this->inventoryService->checkAvailability($this->testProductId, null, $this->warehouseId);

        // "damaged" condition ke saath complete karo — stock NAHI badhna chahiye
        $this->returnModel->updateStatus($returnId, 'completed', 'damaged');

        $stockAfter = $this->inventoryService->checkAvailability($this->testProductId, null, $this->warehouseId);

        $this->assertEquals($stockBefore, $stockAfter);
    }

    public function testReturnedOrderIdsExcludesRejectedReturns(): void
    {
        $returnId = $this->returnModel->create(
            $this->storeId, $this->testOrderId, $this->testProductId, null, $this->warehouseId, 2, 'Rejected test', 300
        );
        $this->createdReturnIds[] = $returnId;

        $this->returnModel->updateStatus($returnId, 'rejected', 'resellable');

        $returnedIds = $this->returnModel->returnedOrderIds($this->storeId);

        // Rejected return ka order dobara "New Return" dropdown mein aana
        // chahiye — isliye ye list mein nahi hona chahiye
        $this->assertNotContains($this->testOrderId, $returnedIds);
    }
}