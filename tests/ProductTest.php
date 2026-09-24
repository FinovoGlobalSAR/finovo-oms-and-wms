<?php
require_once __DIR__ . '/../app/Models/ProductVariant.php';

use PHPUnit\Framework\TestCase;

class ProductTest extends TestCase
{
    private Product $productModel;
    private int $storeId;
    private int $warehouseId;
    private array $createdProductIds = [];

    protected function setUp(): void
    {
        $this->productModel = new Product();

        $db = Database::getConnection();
        $store = $db->query("SELECT id FROM stores LIMIT 1")->fetch();
        $this->storeId = (int) $store['id'];

        $warehouse = $db->query("SELECT id FROM warehouses LIMIT 1")->fetch();
        $this->warehouseId = (int) $warehouse['id'];
    }

    protected function tearDown(): void
    {
        $db = Database::getConnection();
        foreach ($this->createdProductIds as $productId) {
            $db->prepare("DELETE FROM product_warehouse_stock WHERE product_id = ?")->execute([$productId]);
            $db->prepare("DELETE FROM products WHERE id = ?")->execute([$productId]);
        }
    }

    public function testCreateProductSavesCorrectData(): void
    {
        $productId = $this->productModel->create($this->storeId, 'PHPUnit Create Test', 500, 'PHPUNIT-SKU-1');
        $this->createdProductIds[] = $productId;

        $product = $this->productModel->find($productId);

        $this->assertNotNull($product);
        $this->assertEquals('PHPUnit Create Test', $product['name']);
        $this->assertEquals('PHPUNIT-SKU-1', $product['sku']);
        $this->assertEquals(500, (float) $product['price']);
    }

    public function testFindOrCreateReturnsExistingProductInsteadOfDuplicating(): void
    {
        $firstId = $this->productModel->create($this->storeId, 'PHPUnit Duplicate Test', 100);
        $this->createdProductIds[] = $firstId;

        // Same naam se dobara "findOrCreate" karo — naya product nahi banna chahiye
        $secondId = $this->productModel->findOrCreate($this->storeId, 'PHPUnit Duplicate Test', 100);

        $this->assertEquals($firstId, $secondId);
    }

    public function testSetWarehouseStockUpdatesRecomputedTotal(): void
    {
        $productId = $this->productModel->create($this->storeId, 'PHPUnit Stock Test', 100);
        $this->createdProductIds[] = $productId;

        $this->productModel->setWarehouseStock($productId, $this->warehouseId, 25);

        $product = $this->productModel->find($productId);
        $this->assertEquals(25, (int) $product['stock_quantity']);
    }

    public function testVariantCountsForProductsReturnsCorrectCounts(): void
    {
        $productId = $this->productModel->create($this->storeId, 'PHPUnit Variant Count Test', 100);
        $this->createdProductIds[] = $productId;

        $variantModel = new ProductVariant();
        $variantModel->findOrCreate($productId, 'Small', 'Size', 'SKU-S', 100, null, null, null);
        $variantModel->findOrCreate($productId, 'Large', 'Size', 'SKU-L', 100, null, null, null);

        $counts = $this->productModel->variantCountsForProducts([$productId]);

        $this->assertEquals(2, $counts[$productId]);

        // Cleanup variants
        $db = Database::getConnection();
        $db->prepare("DELETE FROM product_variants WHERE product_id = ?")->execute([$productId]);
    }
}