<?php
require_once __DIR__ . '/../app/Services/OrderImportService.php';
require_once __DIR__ . '/../app/Services/OrderStockService.php';
require_once __DIR__ . '/../app/Models/ProductVariant.php';

use PHPUnit\Framework\TestCase;

/**
 * Order cancel → stock warehouse mein wapas. Cancel se wapas → stock dobara kat-ta hai.
 * Jo order platform pe pehle hi cancel tha us ka stock kat-ta hi nahi.
 */
class OrderCancelStockTest extends TestCase
{
    private PDO $db;
    private OrderImportService $importer;
    private Product $products;
    private int $storeId;
    private int $warehouseId;
    private int $productId;
    private array $externalIds = [];

    protected function setUp(): void
    {
        $this->db = Database::getConnection();
        $this->importer = new OrderImportService();
        $this->products = new Product();
        $this->storeId = (int) $this->db->query("SELECT id FROM stores LIMIT 1")->fetchColumn();
        $this->warehouseId = (int) (new Warehouse())->getDefault($this->storeId)['id'];

        $this->db->prepare("INSERT INTO products (store_id, name, price, external_product_id) VALUES (?, 'PHPUnit Cancel Mug', 9, ?)")
            ->execute([$this->storeId, 'CANCEL-' . bin2hex(random_bytes(3))]);
        $this->productId = (int) $this->db->lastInsertId();
        $this->products->setWarehouseStock($this->productId, $this->warehouseId, 10);
    }

    protected function tearDown(): void
    {
        foreach ($this->externalIds as $ext) {
            $this->db->prepare("DELETE FROM orders WHERE external_order_id = ? AND store_id = ?")->execute([$ext, $this->storeId]);
            $this->db->prepare("DELETE FROM integration_errors WHERE external_id = ?")->execute([$ext]);
        }
        $this->db->prepare("DELETE FROM orders WHERE product_id = ?")->execute([$this->productId]);
        $this->db->prepare("DELETE FROM product_warehouse_stock WHERE product_id = ?")->execute([$this->productId]);
        $this->db->prepare("DELETE FROM inventory_ledger WHERE product_id = ?")->execute([$this->productId]);
        $this->db->prepare("DELETE FROM products WHERE id = ?")->execute([$this->productId]);
    }

    private function stock(): int
    {
        return $this->products->getWarehouseStock($this->productId, $this->warehouseId);
    }

    private function importOrder(int $qty, string $status): string
    {
        $ext = 'PHPUNIT-C-' . bin2hex(random_bytes(4));
        $this->externalIds[] = $ext;
        $res = $this->importer->import($this->storeId, 'shopify_pull', $ext,
            ['customer_name' => 'PHPUnit Cancel Buyer', 'status' => $status, 'payment_status' => 'paid'],
            [['name' => 'PHPUnit Cancel Mug', 'quantity' => $qty, 'unit_price' => 9]]);
        $this->assertTrue($res['ok'], $res['reason'] ?? '');
        return $ext;
    }

    public function testPlatformCancelReturnsStockAndReopenTakesItAgain(): void
    {
        $ext = $this->importOrder(3, 'processing');
        $this->assertSame(7, $this->stock());

        // Shopify pe order cancel hua → sync
        $this->importer->updateStatus($this->storeId, 'shopify_pull', $ext, 'cancelled', 'unpaid');
        $this->assertSame(10, $this->stock());

        // Dobara sync (wahi cancelled) → stock dobara nahi judta
        $this->importer->updateStatus($this->storeId, 'shopify_pull', $ext, 'cancelled', 'unpaid');
        $this->assertSame(10, $this->stock());

        // Order wapas khul gaya → stock phir kata
        $this->importer->updateStatus($this->storeId, 'shopify_pull', $ext, 'processing', 'paid');
        $this->assertSame(7, $this->stock());
    }

    public function testAlreadyCancelledOrderDoesNotTakeStock(): void
    {
        $this->importOrder(4, 'cancelled');
        $this->assertSame(10, $this->stock());
    }

    public function testReopenWithoutStockMarksWarningInSync(): void
    {
        $ext = $this->importOrder(3, 'processing');
        $this->importer->updateStatus($this->storeId, 'shopify_pull', $ext, 'cancelled', 'unpaid');
        $this->products->setWarehouseStock($this->productId, $this->warehouseId, 1); // beech mein stock bik gaya

        $this->importer->updateStatus($this->storeId, 'shopify_pull', $ext, 'processing', 'paid');

        $row = $this->db->query("SELECT * FROM orders WHERE external_order_id = " . $this->db->quote($ext))->fetch(PDO::FETCH_ASSOC);
        $this->assertSame('processing', $row['status']);
        $this->assertSame(1, (int) $row['stock_warning']);   // stock kata nahi — warning
        $this->assertSame(1, $this->stock());

        // Warning wala order cancel ho → kuch wapas nahi judta (kata hi nahi tha)
        $this->importer->updateStatus($this->storeId, 'shopify_pull', $ext, 'cancelled', 'unpaid');
        $this->assertSame(1, $this->stock());
    }

    public function testManualReopenFailsWhenNoStock(): void
    {
        $ext = $this->importOrder(3, 'processing');
        $this->importer->updateStatus($this->storeId, 'shopify_pull', $ext, 'cancelled', 'unpaid');
        $this->products->setWarehouseStock($this->productId, $this->warehouseId, 2);

        $row = $this->db->query("SELECT * FROM orders WHERE external_order_id = " . $this->db->quote($ext))->fetch(PDO::FETCH_ASSOC);
        $res = (new OrderStockService())->beforeStatusChange($row, 'pending', 'phpunit', true);

        $this->assertFalse($res['ok']);
        $this->assertStringContainsString('only 2 available', $res['message']);
        $this->assertSame(2, $this->stock());
    }

    public function testVariantReserveKeepsVariantAndProductTotalsRight(): void
    {
        $this->db->prepare("INSERT INTO product_variants (product_id, label, stock_quantity) VALUES (?, 'Red', 0)")->execute([$this->productId]);
        $variantId = (int) $this->db->lastInsertId();
        (new ProductVariant())->setWarehouseStock($variantId, $this->warehouseId, 5);

        $inv = new InventoryService();
        $this->assertTrue($inv->reserve($this->productId, $variantId, $this->warehouseId, 2, 'phpunit')['ok']);

        $this->assertSame(3, (int) $this->db->query("SELECT stock_quantity FROM product_variants WHERE id = {$variantId}")->fetchColumn());
        // Variant wale product ka kul = variants ka jor (pehle 0 ho jata tha)
        $this->assertSame(3, (int) $this->products->find($this->productId)['stock_quantity']);

        $inv->release($this->productId, $variantId, $this->warehouseId, 2, 'phpunit');
        $this->assertSame(5, (int) $this->products->find($this->productId)['stock_quantity']);

        $this->db->prepare("DELETE FROM variant_warehouse_stock WHERE variant_id = ?")->execute([$variantId]);
        $this->db->prepare("DELETE FROM product_variants WHERE id = ?")->execute([$variantId]);
    }
}