<?php
require_once __DIR__ . '/../app/Services/OrderImportService.php';
require_once __DIR__ . '/../app/Services/PlatformOrderMapper.php';

use PHPUnit\Framework\TestCase;

/**
 * Platform se aaye order mein kai products hon to SAB Finovo mein aane chahiye,
 * har ek ka stock kam hona chahiye, aur kuch bhi gadbad ho to poora order skip + stock wapas.
 */
class OrderImportTest extends TestCase
{
    private OrderImportService $importer;
    private Product $productModel;
    private int $storeId;
    private int $warehouseId;
    private array $productIds = [];
    private string $externalId;

    protected function setUp(): void
    {
        $this->importer = new OrderImportService();
        $this->productModel = new Product();
        $db = Database::getConnection();

        $this->storeId = (int) $db->query("SELECT id FROM stores LIMIT 1")->fetchColumn();
        $this->warehouseId = (int) (new Warehouse())->getDefault($this->storeId)['id'];
        $this->externalId = 'PHPUNIT-' . bin2hex(random_bytes(4));

        // Do products: Shirt (10 stock, platform ID 9001) aur Cap (1 stock)
        $insert = $db->prepare("INSERT INTO products (store_id, name, price, external_product_id) VALUES (?, ?, ?, ?)");
        $insert->execute([$this->storeId, 'PHPUnit Import Shirt', 15.5, '9001']);
        $this->productIds['shirt'] = (int) $db->lastInsertId();
        $insert->execute([$this->storeId, 'PHPUnit Import Cap', 5, null]);
        $this->productIds['cap'] = (int) $db->lastInsertId();

        $this->productModel->setWarehouseStock($this->productIds['shirt'], $this->warehouseId, 10);
        $this->productModel->setWarehouseStock($this->productIds['cap'], $this->warehouseId, 1);
    }

    protected function tearDown(): void
    {
        $db = Database::getConnection();
        $db->prepare("DELETE FROM orders WHERE external_order_id = ? AND store_id = ?")->execute([$this->externalId, $this->storeId]);
        $db->prepare("DELETE FROM integration_errors WHERE external_id = ?")->execute([$this->externalId]);
        foreach ($this->productIds as $id) {
            $db->prepare("DELETE FROM product_warehouse_stock WHERE product_id = ?")->execute([$id]);
            $db->prepare("DELETE FROM inventory_ledger WHERE product_id = ?")->execute([$id]);
            $db->prepare("DELETE FROM products WHERE id = ?")->execute([$id]);
        }
    }

    private function header(): array
    {
        return ['customer_name' => 'PHPUnit Buyer', 'status' => 'processing', 'payment_status' => 'paid', 'payment_method' => 'Card'];
    }

    private function rows(): array
    {
        $stmt = Database::getConnection()->prepare("SELECT * FROM orders WHERE external_order_id = ? AND store_id = ? ORDER BY id");
        $stmt->execute([$this->externalId, $this->storeId]);
        return $stmt->fetchAll();
    }

    private function stock(string $product): int
    {
        return $this->productModel->getWarehouseStock($this->productIds[$product], $this->warehouseId);
    }

    public function testEveryLineOfAMultiProductOrderIsImported(): void
    {
        $result = $this->importer->import($this->storeId, 'shopify_pull', $this->externalId, $this->header(), [
            ['name' => 'Some other title', 'quantity' => 2, 'unit_price' => 15.5, 'external_product_id' => '9001'], // ID se milega
            ['name' => 'PHPUnit Import Cap', 'quantity' => 1, 'unit_price' => 5],                                    // naam se milega
        ]);

        $this->assertTrue($result['ok']);
        $rows = $this->rows();
        $this->assertCount(2, $rows, 'Both products must become order rows');
        $this->assertSame($rows[0]['order_group'], $rows[1]['order_group'], 'Rows of one order share an order_group');
        $this->assertEquals(15.5, (float) $rows[0]['price'], 'Price is the unit price, not the order total');
        $this->assertSame(8, $this->stock('shirt'));
        $this->assertSame(0, $this->stock('cap'));
    }

    public function testUnknownProductSkipsWholeOrderInStrictMode(): void
    {
        $result = $this->importer->import($this->storeId, 'shopify_pull', $this->externalId, $this->header(), [
            ['name' => 'PHPUnit Import Shirt', 'quantity' => 1, 'unit_price' => 15.5],
            ['name' => 'Product That Does Not Exist', 'quantity' => 1, 'unit_price' => 1],
        ]);

        $this->assertFalse($result['ok']);
        $this->assertCount(0, $this->rows());
        $this->assertSame(10, $this->stock('shirt'), 'No stock may be taken for a skipped order');
    }

    public function testNotEnoughStockRollsBackEarlierLines(): void
    {
        $result = $this->importer->import($this->storeId, 'shopify_pull', $this->externalId, $this->header(), [
            ['name' => 'PHPUnit Import Shirt', 'quantity' => 3, 'unit_price' => 15.5],
            ['name' => 'PHPUnit Import Cap', 'quantity' => 5, 'unit_price' => 5], // sirf 1 hai
        ]);

        $this->assertFalse($result['ok']);
        $this->assertCount(0, $this->rows());
        $this->assertSame(10, $this->stock('shirt'), 'Shirt stock must be given back');
        $this->assertSame(1, $this->stock('cap'));
    }

    public function testWebhookModeImportsWithStockWarningInsteadOfSkipping(): void
    {
        $result = $this->importer->import($this->storeId, 'shopify_pull', $this->externalId, $this->header(), [
            ['name' => 'PHPUnit Import Cap', 'quantity' => 5, 'unit_price' => 5],
        ], 'webhook', 'webhook');

        $this->assertTrue($result['ok']);
        $rows = $this->rows();
        $this->assertCount(1, $rows);
        $this->assertSame(1, (int) $rows[0]['stock_warning']);
        $this->assertSame(1, $this->stock('cap'), 'Stock is not taken when it is not available');
    }

    public function testStatusUpdateChangesAllRowsOfTheOrder(): void
    {
        $this->importer->import($this->storeId, 'shopify_pull', $this->externalId, $this->header(), [
            ['name' => 'PHPUnit Import Shirt', 'quantity' => 1, 'unit_price' => 15.5],
            ['name' => 'PHPUnit Import Cap', 'quantity' => 1, 'unit_price' => 5],
        ]);

        $this->assertTrue($this->importer->exists($this->storeId, 'shopify_pull', $this->externalId));
        $this->importer->updateStatus($this->storeId, 'shopify_pull', $this->externalId, 'delivered', 'paid');

        foreach ($this->rows() as $row) {
            $this->assertSame('delivered', $row['status']);
        }
    }

    public function testPrestaShopRowsAreReadFromAssociations(): void
    {
        $lines = PlatformOrderMapper::prestaShopLines([
            'associations' => ['order_rows' => [
                ['product_id' => '9', 'product_name' => 'Mug', 'product_quantity' => '2', 'unit_price_tax_incl' => '8.000000'],
                ['product_id' => '10', 'product_name' => 'Plate', 'product_quantity' => '1', 'unit_price_tax_incl' => '4.5'],
            ]],
        ]);

        $this->assertCount(2, $lines);
        $this->assertSame('Mug', $lines[0]['name']);
        $this->assertSame(2, $lines[0]['quantity']);
        $this->assertSame('9', $lines[0]['external_product_id']);
    }

    public function testMagentoConfigurableUsesChildProductWithParentPrice(): void
    {
        $lines = PlatformOrderMapper::magentoLines(['items' => [
            ['item_id' => 1, 'name' => 'T-Shirt', 'product_id' => 50, 'qty_ordered' => 2, 'price' => 20, 'product_type' => 'configurable'],
            ['item_id' => 2, 'parent_item_id' => 1, 'name' => 'T-Shirt Red M', 'product_id' => 51, 'qty_ordered' => 2, 'price' => 0],
            ['item_id' => 3, 'name' => 'Socks', 'product_id' => 60, 'qty_ordered' => 1, 'price' => 3],
        ]]);

        $this->assertCount(2, $lines);
        $this->assertSame('T-Shirt Red M', $lines[0]['name']);
        $this->assertSame('51', $lines[0]['external_product_id']);
        $this->assertSame(20.0, $lines[0]['unit_price']);
        $this->assertSame('Socks', $lines[1]['name']);
    }

    public function testEbayLineCostIsDividedIntoUnitPrice(): void
    {
        $lines = PlatformOrderMapper::ebayLines(['lineItems' => [
            ['title' => 'Phone Case', 'quantity' => 4, 'sku' => 'PC-1', 'lineItemCost' => ['value' => '20.00']],
        ]]);

        $this->assertSame(5.0, $lines[0]['unit_price']);
        $this->assertSame('PC-1', $lines[0]['external_product_id']);
    }
}