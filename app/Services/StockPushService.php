<?php
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/WooCommerceAuth.php';
require_once __DIR__ . '/IntegrationErrorService.php';

/**
 * Stock wapas platform pe — Finovo mein stock badle to store ke platform pe bhi wahi stock.
 *
 * Pehle: order aaya, stock adjust kiya, warehouse mein maal aaya → sirf Finovo mein badalta tha,
 * Shopify / WooCommerce pe purana stock rehta tha aur khatam hui cheez bikti rehti thi (overselling).
 *
 * Kaise chalta hai:
 *   1) Product::recomputeStock() har stock badlaav pe StockPushService::queue($productId) bulata hai.
 *   2) Request ke AAKHIR mein (shutdown) har product sirf EK dafa push hota hai — chahe stock 10 dafa badla ho.
 *   3) Platform se stock LAATE waqt (product sync) push band rehta hai (pause/resume) — warna wahi
 *      stock wapas bhejte.
 *
 * Hifazat: agar platform pe us product ka stock gina hi nahi jata (WooCommerce "Manage stock" off,
 * Shopify "Track quantity" off, BigCommerce tracking "none", Magento "Manage Stock" = No), to hum
 * use NAHI chhedte — warna Finovo ka 0 bhej kar live store pe product band ho jata.
 *
 * Platforms: Shopify, WooCommerce (variants ke saath), BigCommerce, PrestaShop, Magento.
 * Wix, eBay, OpenCart, osCommerce, CJ, Custom Store pe stock push nahi hota (wahan stock wahin badlo).
 *
 * .env mein STOCK_PUSH=false likh kar yeh band kiya ja sakta hai.
 */
class StockPushService
{
    public const SUPPORTED = ['shopify', 'woocommerce', 'bigcommerce', 'prestashop', 'magento'];

    /** Sirf tests ke liye: function(string $method, string $url, array $headers, ?string $body, ?string $userpwd): array{code:int, body:string} */
    public static $transport = null;

    /** Sirf tests ke liye: platform base URLs (BigCommerce) */
    public static string $bigCommerceBase = 'https://api.bigcommerce.com';
    /** Sirf tests ke liye: Shopify 'http' pe mock server */
    public static string $shopifyScheme = 'https';

    private static array $queue = [];
    private static int $paused = 0;
    private static bool $shutdownRegistered = false;

    // ---------------------------------------------------------------------
    //  Queue
    // ---------------------------------------------------------------------

    public static function queue(int $productId): void
    {
        if (self::$paused > 0 || !self::enabled() || $productId <= 0) {
            return;
        }
        self::$queue[$productId] = true;

        if (!self::$shutdownRegistered) {
            self::$shutdownRegistered = true;
            register_shutdown_function([self::class, 'flushAtShutdown']);
        }
    }

    /** Platform se stock laate waqt push band (yeh jodi mein chalte hain: pause → kaam → resume) */
    public static function pause(): void
    {
        self::$paused++;
    }

    public static function resume(): void
    {
        self::$paused = max(0, self::$paused - 1);
    }

    public static function pending(): array
    {
        return array_keys(self::$queue);
    }

    public static function clearQueue(): void
    {
        self::$queue = [];
    }

    public static function enabled(): bool
    {
        $flag = function_exists('env') ? env('STOCK_PUSH', 'true') : 'true';
        return !in_array(strtolower((string) $flag), ['false', '0', 'no', 'off'], true);
    }

    /** Request khatam hone pe: pehle browser ko jawab bhej do (jahan ho sake), phir push */
    public static function flushAtShutdown(): void
    {
        if (empty(self::$queue)) {
            return;
        }
        if (function_exists('fastcgi_finish_request')) {
            @fastcgi_finish_request();
        }
        ignore_user_abort(true);
        try {
            self::flush();
        } catch (Throwable $e) {
            error_log('Stock push failed: ' . $e->getMessage());
        }
    }

    /** Queue ke saare products push karo. @return array<int, array> productId => result */
    public static function flush(): array
    {
        $ids = array_keys(self::$queue);
        self::$queue = [];

        $service = new self();
        $results = [];
        foreach ($ids as $productId) {
            $results[$productId] = $service->pushProduct($productId);
        }
        return $results;
    }

    // ---------------------------------------------------------------------
    //  Ek product
    // ---------------------------------------------------------------------

    /**
     * @return array{ok: bool, skipped?: bool, message: string}
     *   skipped = kuch karna hi nahi tha (platform support nahi, product platform pe nahi, ya stock track nahi hota)
     */
    public function pushProduct(int $productId): array
    {
        $db = Database::getConnection();
        $product = $db->prepare("SELECT * FROM products WHERE id = ?");
        $product->execute([$productId]);
        $product = $product->fetch(PDO::FETCH_ASSOC);
        if (!$product) {
            return ['ok' => true, 'skipped' => true, 'message' => 'Product not found.'];
        }

        $store = $db->prepare("SELECT * FROM stores WHERE id = ?");
        $store->execute([(int) $product['store_id']]);
        $store = $store->fetch(PDO::FETCH_ASSOC);
        $platform = strtolower((string) ($store['platform'] ?? ''));

        if (!$store || !in_array($platform, self::SUPPORTED, true)) {
            return ['ok' => true, 'skipped' => true, 'message' => 'Stock push is not available for this platform.'];
        }

        $variants = $db->prepare("SELECT * FROM product_variants WHERE product_id = ?");
        $variants->execute([$productId]);
        $variants = $variants->fetchAll(PDO::FETCH_ASSOC);

        $result = match ($platform) {
            'shopify'     => $this->pushShopify($store, $product, $variants),
            'woocommerce' => $this->pushWooCommerce($store, $product, $variants),
            'bigcommerce' => $this->pushBigCommerce($store, $product),
            'prestashop'  => $this->pushPrestaShop($store, $product),
            'magento'     => $this->pushMagento($store, $product),
        };

        if (!$result['ok']) {
            (new IntegrationErrorService())->logFailure(
                (int) $store['id'], $platform, 'push_stock', null, (string) $productId,
                $result['code'] ?? null, 'Stock push for "' . $product['name'] . '" failed: ' . $result['message']
            );
        }
        return $result;
    }

    // ---------------------------------------------------------------------
    //  Shopify — inventory item ki quantity us ki location pe set karo
    // ---------------------------------------------------------------------

    private function pushShopify(array $store, array $product, array $variants): array
    {
        if (empty($product['external_product_id']) || empty($store['store_url']) || empty($store['access_token'])) {
            return ['ok' => true, 'skipped' => true, 'message' => 'Product is not linked to Shopify.'];
        }
        $base = self::$shopifyScheme . '://' . preg_replace('#^https?://#', '', rtrim($store['store_url'], '/')) . '/admin/api/2026-07';
        $headers = ['X-Shopify-Access-Token: ' . $store['access_token'], 'Content-Type: application/json', 'Accept: application/json'];

        // Kaun se Shopify variants, kitna stock: variants hain to har variant ka apna, warna product ka
        $targets = [];
        if (!empty($variants)) {
            foreach ($variants as $v) {
                if (!empty($v['external_variant_id'])) {
                    $targets[(string) $v['external_variant_id']] = (int) $v['stock_quantity'];
                }
            }
        } else {
            $res = $this->http('GET', $base . '/products/' . rawurlencode($product['external_product_id']) . '.json?fields=variants', $headers);
            if ($res['code'] !== 200) {
                return ['ok' => false, 'code' => $res['code'], 'message' => 'Could not read the product from Shopify (HTTP ' . $res['code'] . ').'];
            }
            $first = json_decode($res['body'], true)['product']['variants'][0] ?? null;
            if ($first) {
                $targets[(string) $first['id']] = (int) $product['stock_quantity'];
            }
        }

        $done = 0;
        foreach ($targets as $variantId => $qty) {
            $res = $this->http('GET', $base . '/variants/' . rawurlencode($variantId) . '.json', $headers);
            $variant = json_decode($res['body'], true)['variant'] ?? null;
            if ($res['code'] !== 200 || !$variant) {
                return ['ok' => false, 'code' => $res['code'], 'message' => 'Could not read variant ' . $variantId . ' from Shopify (HTTP ' . $res['code'] . ').'];
            }
            if (($variant['inventory_management'] ?? null) === null) {
                continue; // Shopify pe "Track quantity" off — mat chhedo
            }

            $itemId = $variant['inventory_item_id'] ?? null;
            $levels = $this->http('GET', $base . '/inventory_levels.json?inventory_item_ids=' . rawurlencode((string) $itemId), $headers);
            $levels = json_decode($levels['body'], true)['inventory_levels'] ?? [];
            if (count($levels) !== 1) {
                // Kai locations — kul stock ko locations mein baantna Finovo ko nahi pata
                return ['ok' => false, 'code' => 0, 'message' => 'This product is stocked at ' . count($levels) . ' Shopify locations; set its stock in Shopify admin.'];
            }

            $set = $this->http('POST', $base . '/inventory_levels/set.json', $headers, json_encode([
                'location_id' => $levels[0]['location_id'],
                'inventory_item_id' => $itemId,
                'available' => max(0, $qty),
            ]));
            if ($set['code'] !== 200) {
                return ['ok' => false, 'code' => $set['code'], 'message' => 'Shopify did not accept the stock (HTTP ' . $set['code'] . ').'];
            }
            $done++;
        }

        return $done > 0
            ? ['ok' => true, 'message' => "Stock updated on Shopify ({$done} item(s))."]
            : ['ok' => true, 'skipped' => true, 'message' => 'Shopify does not track stock for this product.'];
    }

    // ---------------------------------------------------------------------
    //  WooCommerce — product ya har variation ka stock_quantity
    // ---------------------------------------------------------------------

    private function pushWooCommerce(array $store, array $product, array $variants): array
    {
        if (empty($product['external_wc_product_id']) || empty($store['woocommerce_store_url']) || empty($store['woocommerce_consumer_key'])) {
            return ['ok' => true, 'skipped' => true, 'message' => 'Product is not linked to WooCommerce.'];
        }
        $base = rtrim($store['woocommerce_store_url'], '/') . '/wp-json/wc/v3/products/' . rawurlencode($product['external_wc_product_id']);
        $key = $store['woocommerce_consumer_key'];
        $secret = $store['woocommerce_consumer_secret'];

        // [url => qty]
        $targets = [];
        if (!empty($variants)) {
            foreach ($variants as $v) {
                if (!empty($v['external_wc_variant_id'])) {
                    $targets[$base . '/variations/' . rawurlencode($v['external_wc_variant_id'])] = (int) $v['stock_quantity'];
                }
            }
        } else {
            $targets[$base] = (int) $product['stock_quantity'];
        }

        $done = 0;
        foreach ($targets as $url => $qty) {
            $res = $this->woo('GET', $url, $key, $secret);
            if ($res['code'] !== 200) {
                return ['ok' => false, 'code' => $res['code'], 'message' => 'Could not read the product from WooCommerce (HTTP ' . $res['code'] . ').'];
            }
            // Sirf tab jab WooCommerce khud is ka stock ginta ho ("parent" = parent product ginta hai — us ko mat chhedo)
            if ((json_decode($res['body'], true)['manage_stock'] ?? false) !== true) {
                continue;
            }
            $put = $this->woo('PUT', $url, $key, $secret, json_encode(['stock_quantity' => max(0, $qty)]));
            if ($put['code'] !== 200) {
                return ['ok' => false, 'code' => $put['code'], 'message' => 'WooCommerce did not accept the stock (HTTP ' . $put['code'] . ').'];
            }
            $done++;
        }

        return $done > 0
            ? ['ok' => true, 'message' => "Stock updated on WooCommerce ({$done} item(s))."]
            : ['ok' => true, 'skipped' => true, 'message' => 'WooCommerce does not manage stock for this product.'];
    }

    private function woo(string $method, string $url, string $key, string $secret, ?string $body = null): array
    {
        return $this->http($method, WooCommerceAuth::url($method, $url, $key, $secret), ['Content-Type: application/json'], $body, $key . ':' . $secret);
    }

    // ---------------------------------------------------------------------
    //  BigCommerce — inventory_level (sirf jab tracking "product" ho)
    // ---------------------------------------------------------------------

    private function pushBigCommerce(array $store, array $product): array
    {
        if (empty($product['external_bc_product_id']) || empty($store['bigcommerce_store_hash']) || empty($store['bigcommerce_access_token'])) {
            return ['ok' => true, 'skipped' => true, 'message' => 'Product is not linked to BigCommerce.'];
        }
        $url = rtrim(self::$bigCommerceBase, '/') . '/stores/' . rawurlencode($store['bigcommerce_store_hash'])
            . '/v3/catalog/products/' . rawurlencode($product['external_bc_product_id']);
        $headers = ['X-Auth-Token: ' . $store['bigcommerce_access_token'], 'Accept: application/json', 'Content-Type: application/json'];

        $res = $this->http('GET', $url, $headers);
        if ($res['code'] !== 200) {
            return ['ok' => false, 'code' => $res['code'], 'message' => 'Could not read the product from BigCommerce (HTTP ' . $res['code'] . ').'];
        }
        $tracking = json_decode($res['body'], true)['data']['inventory_tracking'] ?? 'none';
        if ($tracking !== 'product') {
            // "none" = gina nahi jata; "variant" = har variant alag (Finovo variants BigCommerce se sync nahi hote)
            return ['ok' => true, 'skipped' => true, 'message' => 'BigCommerce does not track stock at product level for this product.'];
        }

        $put = $this->http('PUT', $url, $headers, json_encode(['inventory_level' => max(0, (int) $product['stock_quantity'])]));
        if ($put['code'] !== 200) {
            return ['ok' => false, 'code' => $put['code'], 'message' => 'BigCommerce did not accept the stock (HTTP ' . $put['code'] . ').'];
        }
        return ['ok' => true, 'message' => 'Stock updated on BigCommerce.'];
    }

    // ---------------------------------------------------------------------
    //  PrestaShop — stock_availables record ki quantity
    // ---------------------------------------------------------------------

    private function pushPrestaShop(array $store, array $product): array
    {
        if (empty($product['external_ps_product_id']) || empty($store['prestashop_store_url']) || empty($store['prestashop_api_key'])) {
            return ['ok' => true, 'skipped' => true, 'message' => 'Product is not linked to PrestaShop.'];
        }
        $api = rtrim($store['prestashop_store_url'], '/') . '/api';
        $key = $store['prestashop_api_key'];
        $psId = (int) $product['external_ps_product_id'];

        $res = $this->http('GET', $api . '/stock_availables?' . http_build_query([
            'ws_key' => $key, 'output_format' => 'JSON', 'display' => 'full',
            'filter[id_product]' => '[' . $psId . ']', 'filter[id_product_attribute]' => '[0]',
        ]), ['Accept: application/json']);
        $sa = json_decode($res['body'], true)['stock_availables'][0] ?? null;
        if ($res['code'] !== 200 || !is_array($sa) || empty($sa['id'])) {
            return ['ok' => false, 'code' => $res['code'], 'message' => 'Could not read the stock record from PrestaShop (the key needs GET and PUT access to "stock_availables").'];
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?><prestashop xmlns:xlink="http://www.w3.org/1999/xlink"><stock_available>'
            . '<id>' . (int) $sa['id'] . '</id>'
            . '<id_product>' . $psId . '</id_product>'
            . '<id_product_attribute>' . (int) ($sa['id_product_attribute'] ?? 0) . '</id_product_attribute>'
            . '<id_shop>' . (int) ($sa['id_shop'] ?? 1) . '</id_shop>'
            . '<id_shop_group>' . (int) ($sa['id_shop_group'] ?? 0) . '</id_shop_group>'
            . '<quantity>' . max(0, (int) $product['stock_quantity']) . '</quantity>'
            . '<depends_on_stock>' . (int) ($sa['depends_on_stock'] ?? 0) . '</depends_on_stock>'
            . '<out_of_stock>' . (int) ($sa['out_of_stock'] ?? 2) . '</out_of_stock>'
            . '</stock_available></prestashop>';

        $put = $this->http('PUT', $api . '/stock_availables/' . (int) $sa['id'] . '?ws_key=' . urlencode($key), ['Content-Type: text/xml'], $xml);
        if (!in_array($put['code'], [200, 201], true)) {
            return ['ok' => false, 'code' => $put['code'], 'message' => 'PrestaShop did not accept the stock (HTTP ' . $put['code'] . ').'];
        }
        return ['ok' => true, 'message' => 'Stock updated on PrestaShop.'];
    }

    // ---------------------------------------------------------------------
    //  Magento — stockItems (SKU se)
    // ---------------------------------------------------------------------

    private function pushMagento(array $store, array $product): array
    {
        $sku = trim((string) ($product['sku'] ?? ''));
        if (empty($product['external_magento_product_id']) || $sku === '' || empty($store['magento_store_url']) || empty($store['magento_access_token'])) {
            return ['ok' => true, 'skipped' => true, 'message' => 'Product is not linked to Magento (or has no SKU).'];
        }
        $api = rtrim($store['magento_store_url'], '/') . '/rest/V1';
        $headers = ['Authorization: Bearer ' . $store['magento_access_token'], 'Accept: application/json', 'Content-Type: application/json'];

        $res = $this->http('GET', $api . '/stockItems/' . rawurlencode($sku), $headers);
        $item = json_decode($res['body'], true);
        if ($res['code'] !== 200 || !is_array($item)) {
            return ['ok' => false, 'code' => $res['code'], 'message' => 'Could not read the stock from Magento (HTTP ' . $res['code'] . ').'];
        }
        if (($item['manage_stock'] ?? true) === false) {
            return ['ok' => true, 'skipped' => true, 'message' => 'Magento does not manage stock for this product.'];
        }

        $qty = max(0, (int) $product['stock_quantity']);
        $put = $this->http('PUT', $api . '/products/' . rawurlencode($sku) . '/stockItems/' . (int) ($item['item_id'] ?? 1), $headers, json_encode([
            'stockItem' => ['qty' => $qty, 'is_in_stock' => $qty > 0],
        ]));
        if ($put['code'] !== 200) {
            return ['ok' => false, 'code' => $put['code'], 'message' => 'Magento did not accept the stock (HTTP ' . $put['code'] . ').'];
        }
        return ['ok' => true, 'message' => 'Stock updated on Magento.'];
    }

    // ---------------------------------------------------------------------

    private function http(string $method, string $url, array $headers, ?string $body = null, ?string $userpwd = null): array
    {
        if (self::$transport !== null) {
            return (self::$transport)($method, $url, $headers, $body, $userpwd);
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, function_exists('curlVerifySsl') ? curlVerifySsl() : true);
        if ($userpwd !== null) {
            curl_setopt($ch, CURLOPT_USERPWD, $userpwd);
        }
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
        $raw = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ['code' => $raw === false ? 0 : $code, 'body' => $raw === false ? '' : (string) $raw];
    }
}