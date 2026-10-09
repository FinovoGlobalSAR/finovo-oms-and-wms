<?php
require_once __DIR__ . '/../app/Services/StockPushService.php';
require_once __DIR__ . '/../app/Models/ProductVariant.php';

use PHPUnit\Framework\TestCase;

/**
 * Finovo mein stock badle → platform pe bhi. Lekin jis product ka stock platform pe gina hi nahi
 * jata, use kabhi nahi chhedte (warna live store pe product "out of stock" ho jata).
 */
class StockPushTest extends TestCase
{
    private PDO $db;
    private string $tag;
    private array $calls = [];
    private array $responses = [];

    protected function setUp(): void
    {
        $this->db = Database::getConnection();
        $this->tag = 'phpunit' . bin2hex(random_bytes(3));
        StockPushService::clearQueue();
        StockPushService::$transport = function ($method, $url, $headers, $body, $userpwd) {
            $this->calls[] = ['method' => $method, 'url' => $url, 'body' => $body];
            foreach ($this->responses as $pattern => $response) {
                [$m, $p] = explode(' ', $pattern, 2);
                if ($m === $method && str_contains($url, $p)) {
                    return ['code' => $response[0], 'body' => is_string($response[1]) ? $response[1] : json_encode($response[1])];
                }
            }
            return ['code' => 404, 'body' => '{}'];
        };
    }

    protected function tearDown(): void
    {
        StockPushService::$transport = null;
        StockPushService::clearQueue();
        $_ENV['STOCK_PUSH'] = 'false';
        $like = '%' . $this->tag . '%';
        $this->db->prepare("DELETE FROM product_variants WHERE product_id IN (SELECT id FROM products WHERE name LIKE ?)")->execute([$like]);
        $this->db->prepare("DELETE FROM products WHERE name LIKE ?")->execute([$like]);
        $this->db->prepare("DELETE FROM integration_errors WHERE error_message LIKE ?")->execute([$like]);
        $this->db->prepare("DELETE FROM stores WHERE name LIKE ?")->execute([$like]);
    }

    private function store(string $platform, array $cols): int
    {
        $cols = ['name' => "Store {$this->tag}", 'platform' => $platform] + $cols;
        $names = implode(',', array_keys($cols));
        $marks = implode(',', array_fill(0, count($cols), '?'));
        $this->db->prepare("INSERT INTO stores ({$names}) VALUES ({$marks})")->execute(array_values($cols));
        return (int) $this->db->lastInsertId();
    }

    private function product(int $storeId, array $cols, int $stock = 0): int
    {
        $cols = ['store_id' => $storeId, 'name' => "Item {$this->tag}", 'price' => 10, 'stock_quantity' => $stock] + $cols;
        $names = implode(',', array_keys($cols));
        $marks = implode(',', array_fill(0, count($cols), '?'));
        $this->db->prepare("INSERT INTO products ({$names}) VALUES ({$marks})")->execute(array_values($cols));
        return (int) $this->db->lastInsertId();
    }

    private function callsOf(string $method): array
    {
        return array_values(array_filter($this->calls, fn($c) => $c['method'] === $method));
    }

    // ---------------- WooCommerce ----------------

    public function testWooCommerceManagedProductGetsStock(): void
    {
        $store = $this->store('woocommerce', ['woocommerce_store_url' => 'http://wp.test', 'woocommerce_consumer_key' => 'ck', 'woocommerce_consumer_secret' => 'cs']);
        $id = $this->product($store, ['external_wc_product_id' => '55'], 8);
        $this->responses = [
            'GET /products/55' => [200, ['id' => 55, 'manage_stock' => true, 'stock_quantity' => 3]],
            'PUT /products/55' => [200, ['id' => 55]],
        ];

        $res = (new StockPushService())->pushProduct($id);

        $this->assertTrue($res['ok']);
        $this->assertEmpty($res['skipped'] ?? false);
        $put = $this->callsOf('PUT');
        $this->assertCount(1, $put);
        $this->assertSame(['stock_quantity' => 8], json_decode($put[0]['body'], true));
    }

    public function testWooCommerceUnmanagedProductIsNeverTouched(): void
    {
        $store = $this->store('woocommerce', ['woocommerce_store_url' => 'http://wp.test', 'woocommerce_consumer_key' => 'ck', 'woocommerce_consumer_secret' => 'cs']);
        $id = $this->product($store, ['external_wc_product_id' => '56'], 0);
        $this->responses = ['GET /products/56' => [200, ['id' => 56, 'manage_stock' => false, 'stock_status' => 'instock']]];

        $res = (new StockPushService())->pushProduct($id);

        $this->assertTrue($res['ok']);
        $this->assertTrue($res['skipped']);
        $this->assertCount(0, $this->callsOf('PUT')); // live store pe "out of stock" nahi kiya
    }

    public function testWooCommerceVariationsEachGetTheirOwnStock(): void
    {
        $store = $this->store('woocommerce', ['woocommerce_store_url' => 'http://wp.test', 'woocommerce_consumer_key' => 'ck', 'woocommerce_consumer_secret' => 'cs']);
        $id = $this->product($store, ['external_wc_product_id' => '60'], 9);
        $this->db->prepare("INSERT INTO product_variants (product_id, label, stock_quantity, external_wc_variant_id) VALUES (?, 'S', 4, '61'), (?, 'L', 5, '62')")->execute([$id, $id]);
        $this->responses = [
            'GET /variations/61' => [200, ['manage_stock' => true]],
            'GET /variations/62' => [200, ['manage_stock' => 'parent']], // parent ginta hai — mat chhedo
            'PUT /variations/61' => [200, ['id' => 61]],
        ];

        $res = (new StockPushService())->pushProduct($id);

        $this->assertTrue($res['ok']);
        $put = $this->callsOf('PUT');
        $this->assertCount(1, $put);
        $this->assertStringContainsString('/variations/61', $put[0]['url']);
        $this->assertSame(['stock_quantity' => 4], json_decode($put[0]['body'], true));
    }

    // ---------------- Shopify ----------------

    public function testShopifySetsAvailableAtTheOnlyLocation(): void
    {
        $store = $this->store('shopify', ['store_url' => 'shop.test', 'access_token' => 'tok']);
        $id = $this->product($store, ['external_product_id' => '900'], 6);
        $this->responses = [
            'GET /products/900.json' => [200, ['product' => ['variants' => [['id' => 901]]]]],
            'GET /variants/901.json' => [200, ['variant' => ['id' => 901, 'inventory_management' => 'shopify', 'inventory_item_id' => 444]]],
            'GET /inventory_levels.json' => [200, ['inventory_levels' => [['location_id' => 77, 'available' => 2]]]],
            'POST /inventory_levels/set.json' => [200, ['inventory_level' => []]],
        ];

        $res = (new StockPushService())->pushProduct($id);

        $this->assertTrue($res['ok']);
        $set = json_decode($this->callsOf('POST')[0]['body'], true);
        $this->assertSame(['location_id' => 77, 'inventory_item_id' => 444, 'available' => 6], $set);
    }

    public function testShopifyUntrackedAndMultiLocation(): void
    {
        $store = $this->store('shopify', ['store_url' => 'shop.test', 'access_token' => 'tok']);
        $id = $this->product($store, ['external_product_id' => '910'], 6);
        $this->responses = [
            'GET /products/910.json' => [200, ['product' => ['variants' => [['id' => 911]]]]],
            'GET /variants/911.json' => [200, ['variant' => ['id' => 911, 'inventory_management' => null]]],
        ];
        $res = (new StockPushService())->pushProduct($id);
        $this->assertTrue($res['skipped']);
        $this->assertCount(0, $this->callsOf('POST'));

        // Do locations → Finovo stock baant nahi sakta: error (Integration Errors mein aata hai)
        $this->calls = [];
        $this->responses['GET /variants/911.json'] = [200, ['variant' => ['id' => 911, 'inventory_management' => 'shopify', 'inventory_item_id' => 5]]];
        $this->responses['GET /inventory_levels.json'] = [200, ['inventory_levels' => [['location_id' => 1], ['location_id' => 2]]]];
        $res = (new StockPushService())->pushProduct($id);
        $this->assertFalse($res['ok']);
        $this->assertStringContainsString('2 Shopify locations', $res['message']);
        $this->assertCount(0, $this->callsOf('POST'));
    }

    // ---------------- BigCommerce ----------------

    public function testBigCommerceOnlyWhenTrackedByProduct(): void
    {
        $store = $this->store('bigcommerce', ['bigcommerce_store_hash' => 'abc', 'bigcommerce_access_token' => 'tok']);
        $id = $this->product($store, ['external_bc_product_id' => '77'], 12);

        $this->responses = ['GET /catalog/products/77' => [200, ['data' => ['inventory_tracking' => 'none']]]];
        $this->assertTrue((new StockPushService())->pushProduct($id)['skipped']);
        $this->assertCount(0, $this->callsOf('PUT'));

        $this->responses = [
            'GET /catalog/products/77' => [200, ['data' => ['inventory_tracking' => 'product']]],
            'PUT /catalog/products/77' => [200, ['data' => []]],
        ];
        $this->assertTrue((new StockPushService())->pushProduct($id)['ok']);
        $this->assertSame(['inventory_level' => 12], json_decode($this->callsOf('PUT')[0]['body'], true));
    }

    // ---------------- Kab push hota hai ----------------

    public function testStockChangeQueuesProductButNotWhilePaused(): void
    {
        $_ENV['STOCK_PUSH'] = 'true';
        $store = $this->store('manual', []);
        $id = $this->product($store, []);
        $products = new Product();

        $products->recomputeStock($id);
        $this->assertSame([$id], StockPushService::pending());

        StockPushService::clearQueue();
        StockPushService::pause();
        $products->recomputeStock($id);   // jaise platform se stock aate waqt
        StockPushService::resume();
        $this->assertSame([], StockPushService::pending());

        $_ENV['STOCK_PUSH'] = 'false';     // .env se band
        $products->recomputeStock($id);
        $this->assertSame([], StockPushService::pending());
    }

    public function testManualAndUnsupportedStoresAreSkipped(): void
    {
        $store = $this->store('wix', ['wix_site_id' => 'x', 'wix_api_key' => 'y']);
        $id = $this->product($store, ['external_wix_product_id' => 'w1'], 3);
        $res = (new StockPushService())->pushProduct($id);
        $this->assertTrue($res['skipped']);
        $this->assertSame([], $this->calls);
    }
}