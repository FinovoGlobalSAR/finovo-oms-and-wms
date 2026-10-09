<?php
require_once __DIR__ . '/../app/Services/OrderImportService.php';
require_once __DIR__ . '/../app/Services/OrderStockService.php';
require_once __DIR__ . '/../app/Services/InventoryService.php';
require_once __DIR__ . '/../app/Models/ReturnRequest.php';
require_once __DIR__ . '/../app/Models/PurchaseOrder.php';
require_once __DIR__ . '/../app/Models/Warehouse.php';
require_once __DIR__ . '/../app/Models/Product.php';

use PHPUnit\Framework\TestCase;

/**
 * Stock ki hifazat wale qaide:
 *   - Return complete → baad mein order cancel / delete pe wahi maal dobara nahi judta
 *   - Return / PO ka status ulta nahi chalta (stock do dafa na jude)
 *   - InventoryService: 0 / minus quantity rad, aur bahar ki transaction ke andar chal sakta hai
 *   - Naya warehouse store ka default nahi chheenta
 *   - Ek hi platform order do dafa import nahi hota
 */
class StockSafetyTest extends TestCase
{
    private PDO $db;
    private Product $products;
    private InventoryService $inventory;
    private int $storeId;
    private int $warehouseId;
    private int $productId;
    private array $externalIds = [];
    private array $warehouseIds = [];

    protected function setUp(): void
    {
        $this->db = Database::getConnection();
        $this->products = new Product();
        $this->inventory = new InventoryService();
        $this->storeId = (int) $this->db->query("SELECT id FROM stores LIMIT 1")->fetchColumn();
        $this->warehouseId = (int) (new Warehouse())->getDefault($this->storeId)['id'];

        $this->db->prepare("INSERT INTO products (store_id, name, price, external_product_id) VALUES (?, 'PHPUnit Safety Mug', 10, ?)")
            ->execute([$this->storeId, 'SAFE-' . bin2hex(random_bytes(3))]);
        $this->productId = (int) $this->db->lastInsertId();
        $this->products->setWarehouseStock($this->productId, $this->warehouseId, 10);
    }

    protected function tearDown(): void
    {
        foreach ($this->externalIds as $ext) {
            $this->db->prepare("DELETE FROM orders WHERE external_order_id = ? AND store_id = ?")->execute([$ext, $this->storeId]);
            $this->db->prepare("DELETE FROM integration_errors WHERE external_id = ?")->execute([$ext]);
        }
        $this->db->prepare("DELETE FROM returns WHERE product_id = ?")->execute([$this->productId]);
        $this->db->prepare("DELETE FROM orders WHERE product_id = ?")->execute([$this->productId]);
        $this->db->prepare("DELETE FROM product_warehouse_stock WHERE product_id = ?")->execute([$this->productId]);
        $this->db->prepare("DELETE FROM inventory_ledger WHERE product_id = ?")->execute([$this->productId]);
        $this->db->prepare("DELETE FROM products WHERE id = ?")->execute([$this->productId]);

        // Test ke warehouses hatao, aur store ka asal default wapas
        foreach ($this->warehouseIds as $wid) {
            $this->db->prepare("DELETE FROM store_warehouses WHERE warehouse_id = ?")->execute([$wid]);
            $this->db->prepare("DELETE FROM warehouses WHERE id = ?")->execute([$wid]);
        }
        $this->db->prepare("UPDATE store_warehouses SET is_default = 1 WHERE store_id = ? AND warehouse_id = ?")
            ->execute([$this->storeId, $this->warehouseId]);
    }

    private function stock(): int
    {
        return $this->products->getWarehouseStock($this->productId, $this->warehouseId);
    }

    private function importOrder(int $qty): array
    {
        $ext = 'PHPUNIT-S-' . bin2hex(random_bytes(4));
        $this->externalIds[] = $ext;
        $res = (new OrderImportService())->import($this->storeId, 'shopify_pull', $ext,
            ['customer_name' => 'PHPUnit Safety Buyer', 'status' => 'processing', 'payment_status' => 'paid'],
            [['name' => 'PHPUnit Safety Mug', 'quantity' => $qty, 'unit_price' => 10]]);
        $this->assertTrue($res['ok'], $res['reason'] ?? '');

        $stmt = $this->db->prepare("SELECT * FROM orders WHERE external_order_id = ? AND store_id = ?");
        $stmt->execute([$ext, $this->storeId]);
        return ['ext' => $ext, 'row' => $stmt->fetch(PDO::FETCH_ASSOC)];
    }

    /** Return complete (maal wapas aaya) → phir order cancel: sirf baqi maal wapas, return wala dobara nahi */
    public function testCancelAfterCompletedReturnDoesNotDoubleRestock(): void
    {
        $order = $this->importOrder(3);
        $this->assertSame(7, $this->stock());

        // 2 ka return complete (resellable) → 2 wapas
        $returnId = (new ReturnRequest())->create($this->storeId, (int) $order['row']['id'], $this->productId, null, $this->warehouseId, 2, 'test', 0);
        $this->inventory->release($this->productId, null, $this->warehouseId, 2, 'return_completed', "return:{$returnId}", 'phpunit');
        (new ReturnRequest())->updateStatus($returnId, 'completed', 'resellable');
        $this->assertSame(9, $this->stock());

        $service = new OrderStockService();
        $this->assertSame(1, $service->heldQuantity($order['row']));

        // Order cancel → sirf 1 wapas (pehle poore 3 wapas aate → 12, asal se zyada)
        (new OrderImportService())->updateStatus($this->storeId, 'shopify_pull', $order['ext'], 'cancelled', 'unpaid');
        $this->assertSame(10, $this->stock());

        // Cancel se wapas khula → sirf 1 dobara kata
        (new OrderImportService())->updateStatus($this->storeId, 'shopify_pull', $order['ext'], 'processing', 'paid');
        $this->assertSame(9, $this->stock());
    }

    public function testReturnAndPurchaseOrderStatusesOnlyMoveForward(): void
    {
        $this->assertTrue(ReturnRequest::canMove('requested', 'completed'));
        $this->assertTrue(ReturnRequest::canMove('approved', 'rejected'));
        $this->assertFalse(ReturnRequest::canMove('completed', 'requested'));
        $this->assertFalse(ReturnRequest::canMove('completed', 'completed'));
        $this->assertFalse(ReturnRequest::canMove('rejected', 'approved'));

        $this->assertTrue(PurchaseOrder::canMove('draft', 'ordered'));
        $this->assertTrue(PurchaseOrder::canMove('ordered', 'received'));
        $this->assertFalse(PurchaseOrder::canMove('received', 'draft'));
        $this->assertFalse(PurchaseOrder::canMove('received', 'received'));
        $this->assertFalse(PurchaseOrder::canMove('ordered', 'draft'));
    }

    public function testZeroOrNegativeQuantityNeverChangesStock(): void
    {
        $result = $this->inventory->reserve($this->productId, null, $this->warehouseId, -5, 'phpunit');
        $this->assertFalse($result['ok']);
        $this->assertSame('INVALID_QUANTITY', $result['code']);
        $this->assertFalse($this->inventory->reserve($this->productId, null, $this->warehouseId, 0, 'phpunit')['ok']);

        $this->inventory->release($this->productId, null, $this->warehouseId, -5, 'phpunit');
        $this->inventory->release($this->productId, null, $this->warehouseId, 0, 'phpunit');
        $this->assertSame(10, $this->stock());
    }

    /** Bahar ki transaction rollback → andar ka reserve / release bhi wapas (aadha kaam nahi) */
    public function testInventoryRunsInsideOuterTransaction(): void
    {
        $this->db->beginTransaction();
        $this->assertTrue($this->inventory->reserve($this->productId, null, $this->warehouseId, 4, 'phpunit')['ok']);
        $this->inventory->release($this->productId, null, $this->warehouseId, 1, 'phpunit');
        $this->assertTrue($this->db->inTransaction(), 'InventoryService ne bahar ki transaction commit kar di');
        $this->assertSame(7, $this->stock());
        $this->db->rollBack();

        $this->assertSame(10, $this->stock());
    }

    public function testSetStockWritesLedger(): void
    {
        $before = $this->inventory->setStock($this->productId, null, $this->warehouseId, 4, 'manual_set', 'phpunit', 'phpunit');
        $this->assertSame(10, $before);
        $this->assertSame(4, $this->stock());

        $row = $this->db->query("SELECT quantity_change, quantity_after FROM inventory_ledger WHERE product_id = {$this->productId} AND source = 'manual_set' ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        $this->assertSame(-6, (int) $row['quantity_change']);
        $this->assertSame(4, (int) $row['quantity_after']);
    }

    public function testNewWarehouseDoesNotStealStoreDefault(): void
    {
        $warehouses = new Warehouse();
        $newId = $warehouses->create('PHPUnit Extra Warehouse', null);
        $this->warehouseIds[] = $newId;

        $warehouses->linkToStore($newId, $this->storeId);
        $this->assertSame($this->warehouseId, (int) $warehouses->getDefault($this->storeId)['id']);

        // Seedha default bolo to banta hai
        $warehouses->linkToStore($newId, $this->storeId, true);
        $this->assertSame($newId, (int) $warehouses->getDefault($this->storeId)['id']);
    }

    public function testWarehouseUsageCountsStoreStock(): void
    {
        $usage = (new Warehouse())->storeUsage($this->warehouseId, $this->storeId);
        $this->assertGreaterThanOrEqual(10, $usage['stock']);
    }

    /** Same order dobara import (webhook + sync ek saath) → doosri dafa rad, stock ek hi dafa kata */
    public function testSameOrderIsNotImportedTwice(): void
    {
        $order = $this->importOrder(2);
        $this->assertSame(8, $this->stock());

        $again = (new OrderImportService())->import($this->storeId, 'shopify_pull', $order['ext'],
            ['customer_name' => 'PHPUnit Safety Buyer', 'status' => 'processing', 'payment_status' => 'paid'],
            [['name' => 'PHPUnit Safety Mug', 'quantity' => 2, 'unit_price' => 10]]);
        $this->assertFalse($again['ok']);
        $this->assertSame(8, $this->stock());

        $count = $this->db->prepare("SELECT COUNT(*) FROM orders WHERE external_order_id = ? AND store_id = ?");
        $count->execute([$order['ext'], $this->storeId]);
        $this->assertSame(1, (int) $count->fetchColumn());
    }
}