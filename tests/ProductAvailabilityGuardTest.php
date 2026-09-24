<?php
require_once __DIR__ . '/../app/Middlewares/ProductAvailabilityGuard.php';

use PHPUnit\Framework\TestCase;

class ProductAvailabilityGuardTest extends TestCase
{
    private ProductAvailabilityGuard $guard;
    private Product $productModel;
    private int $storeId;
    private array $createdProductIds = [];

    protected function setUp(): void
    {
        $this->guard = new ProductAvailabilityGuard();
        $this->productModel = new Product();

        $db = Database::getConnection();
        $store = $db->query("SELECT id FROM stores LIMIT 1")->fetch();
        $this->storeId = (int) $store['id'];
    }

    protected function tearDown(): void
    {
        $db = Database::getConnection();
        foreach ($this->createdProductIds as $productId) {
            $db->prepare("DELETE FROM products WHERE id = ?")->execute([$productId]);
        }
    }

    public function testAllowsOrderWhenProductAlreadyExists(): void
    {
        $productId = $this->productModel->create($this->storeId, 'PHPUnit Guard Existing Product', 150);
        $this->createdProductIds[] = $productId;

        $result = $this->guard->check($this->storeId, 'PHPUnit Guard Existing Product');

        $this->assertTrue($result['allowed']);
        $this->assertEquals($productId, $result['product_id']);
    }

    public function testBlocksOrderWhenProductDoesNotExist(): void
    {
        // Ye product Finovo mein kabhi bana hi nahi — sync order isko
        // reject karna chahiye, naya product khud nahi banana chahiye.
        $result = $this->guard->check($this->storeId, 'PHPUnit Nonexistent Product ' . uniqid());

        $this->assertFalse($result['allowed']);
        $this->assertNull($result['product_id']);
    }
}