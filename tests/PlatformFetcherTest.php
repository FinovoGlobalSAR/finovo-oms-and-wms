<?php
require_once __DIR__ . '/../app/Services/PlatformFetcher.php';
require_once __DIR__ . '/../app/Services/PlatformStock.php';

use PHPUnit\Framework\TestCase;

/**
 * Sync ab SAARE pages laata hai (pehle sirf 20 orders / 50 products),
 * aur stock "track nahi hota" (null) ko 0 nahi samajhta.
 * Internet ki jagah nakli platform (transport) — har URL ke liye jawab hum dete hain.
 */
class PlatformFetcherTest extends TestCase
{
    private array $requested = [];

    protected function tearDown(): void
    {
        PlatformFetcher::$transport = null;
    }

    /** $handler(url) → [code, body, headers] */
    private function fake(callable $handler): void
    {
        PlatformFetcher::$transport = function (string $url, array $headers, ?string $userpwd) use ($handler) {
            $this->requested[] = $url;
            [$code, $body, $resHeaders] = $handler($url) + [2 => []];
            return ['code' => $code, 'body' => is_string($body) ? $body : json_encode($body), 'headers' => $resHeaders];
        };
    }

    private static function rows(int $from, int $count): array
    {
        return array_map(fn($i) => ['id' => $i], range($from, $from + $count - 1));
    }

    public function testShopifyFollowsLinkHeaderUntilNoNextPage(): void
    {
        $this->fake(function (string $url) {
            if (str_contains($url, 'page_info=p2')) {
                return [200, ['orders' => self::rows(251, 10)], ['link' => '<https://shop.test/admin/api/2026-07/orders.json?limit=250&page_info=p1>; rel="previous"']];
            }
            return [200, ['orders' => self::rows(1, 250)], ['link' => '<https://shop.test/admin/api/2026-07/orders.json?limit=250&page_info=p2>; rel="next"']];
        });

        $res = PlatformFetcher::shopify('shop.test', 'tok', 'orders', 'status=any');

        $this->assertTrue($res['ok']);
        $this->assertCount(260, $res['items']);
        $this->assertCount(2, $this->requested);
        $this->assertStringContainsString('status=any&limit=250', $this->requested[0]);
    }

    public function testShopifyFirstPageErrorIsReported(): void
    {
        $this->fake(fn() => [401, '{"errors":"Invalid API key"}']);
        $res = PlatformFetcher::shopify('shop.test', 'bad', 'products');
        $this->assertFalse($res['ok']);
        $this->assertSame(401, $res['code']);
    }

    public function testWooCommerceReadsPagesUntilShortPage(): void
    {
        $this->fake(function (string $url) {
            parse_str((string) parse_url($url, PHP_URL_QUERY), $q);
            $page = (int) $q['page'];
            return [200, $page === 1 ? self::rows(1, 100) : ($page === 2 ? self::rows(101, 100) : self::rows(201, 7))];
        });

        $res = PlatformFetcher::woo('http://wp.test', 'ck_x', 'cs_x', '/wp-json/wc/v3/orders');

        $this->assertCount(207, $res['items']);
        $this->assertCount(3, $this->requested);
        $this->assertStringContainsString('per_page=100', $this->requested[0]);
    }

    public function testWooCommerceLaterPageFailureKeepsWhatCame(): void
    {
        $this->fake(function (string $url) {
            parse_str((string) parse_url($url, PHP_URL_QUERY), $q);
            return (int) $q['page'] === 1 ? [200, self::rows(1, 100)] : [500, 'error'];
        });
        $res = PlatformFetcher::woo('http://wp.test', 'ck', 'cs', '/wp-json/wc/v3/products');
        $this->assertTrue($res['ok']);
        $this->assertTrue($res['partial']);
        $this->assertCount(100, $res['items']);
    }

    public function testBigCommerceV3UsesTotalPages(): void
    {
        $this->fake(function (string $url) {
            $page = str_contains($url, 'page=2') ? 2 : 1;
            return [200, ['data' => self::rows($page * 1000, 3), 'meta' => ['pagination' => ['total_pages' => 2]]]];
        });
        $res = PlatformFetcher::bigCommerceV3('abc', 'tok', '/v3/catalog/products');
        $this->assertCount(6, $res['items']);
        $this->assertCount(2, $this->requested);
    }

    public function testBigCommerceV2StopsOnNoContent(): void
    {
        $this->fake(fn(string $url) => str_contains($url, 'page=1') ? [200, self::rows(1, 250)] : [204, '']);
        $res = PlatformFetcher::bigCommerceV2('abc', 'tok', '/v2/orders', 'sort=date_created:desc');
        $this->assertTrue($res['ok']);
        $this->assertCount(250, $res['items']);

        // Store mein koi order hi nahi (pehla page 204) — error nahi, khali list
        $this->requested = [];
        $this->fake(fn() => [204, '']);
        $empty = PlatformFetcher::bigCommerceV2('abc', 'tok', '/v2/orders');
        $this->assertTrue($empty['ok']);
        $this->assertSame([], $empty['items']);
    }

    public function testPrestaShopUsesOffsetLimit(): void
    {
        $this->fake(function (string $url) {
            return str_contains($url, 'limit=0,100') ? [200, ['orders' => self::rows(1, 100)]] : [200, ['orders' => self::rows(101, 1)]];
        });
        $res = PlatformFetcher::prestaShop('http://ps.test', 'orders', ['ws_key' => 'K', 'display' => 'full']);
        $this->assertCount(101, $res['items']);
        $this->assertStringContainsString('limit=100,100', $this->requested[1]);
    }

    public function testNextLinkParsing(): void
    {
        $this->assertSame('https://x/a?page_info=2', PlatformFetcher::nextLink('<https://x/a?page_info=1>; rel="previous", <https://x/a?page_info=2>; rel="next"'));
        $this->assertNull(PlatformFetcher::nextLink('<https://x/a?page_info=1>; rel="previous"'));
        $this->assertNull(PlatformFetcher::nextLink(''));
    }

    // ---------------- Stock: gina jata hai ya nahi ----------------

    public function testWooCommerceStock(): void
    {
        $this->assertSame(12, PlatformStock::wooCommerce(['manage_stock' => true, 'stock_quantity' => 12]));
        $this->assertSame(4, PlatformStock::wooCommerce(['manage_stock' => 'parent', 'stock_quantity' => 4]));
        // "Manage stock" off — quantity hai hi nahi (pehle 0 ban jata tha → "out of stock")
        $this->assertNull(PlatformStock::wooCommerce(['manage_stock' => false, 'stock_quantity' => null, 'stock_status' => 'instock']));
        $this->assertSame(0, PlatformStock::wooCommerce(['manage_stock' => false, 'stock_quantity' => null, 'stock_status' => 'outofstock']));
    }

    public function testShopifyBigCommerceWixStock(): void
    {
        $this->assertSame(7, PlatformStock::shopifyVariant(['inventory_management' => 'shopify', 'inventory_quantity' => 7]));
        $this->assertNull(PlatformStock::shopifyVariant(['inventory_management' => null, 'inventory_quantity' => 0]));

        $this->assertSame(9, PlatformStock::bigCommerce(['inventory_tracking' => 'product', 'inventory_level' => 9]));
        $this->assertNull(PlatformStock::bigCommerce(['inventory_tracking' => 'none', 'inventory_level' => 0]));

        $this->assertSame(3, PlatformStock::wix(['inventory' => ['availableQuantity' => 3]]));
        $this->assertNull(PlatformStock::wix(['inventory' => ['availabilityStatus' => 'IN_STOCK']]));
        $this->assertSame(0, PlatformStock::wix(['inventory' => ['availabilityStatus' => 'OUT_OF_STOCK']]));
    }
}