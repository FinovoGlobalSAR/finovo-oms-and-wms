<?php
require_once __DIR__ . '/WooCommerceAuth.php';

/**
 * Product Push — Finovo ka product store ke APNE platform pe banana.
 *
 * Har store ka platform alag hai, is liye "Push to ..." button bhi store ke hisaab se hai:
 *      Shopify, WooCommerce, BigCommerce, Wix, Magento 2, PrestaShop, eBay
 *
 * OpenCart / osCommerce (seedha database se jude hain), CJdropshipping (supplier hai, wahan
 * product nahi bana sakte), Custom Store aur Manual store pe push nahi hota.
 *
 * $product = products table ki row; $stock = Finovo mein us product ka kul stock.
 * Har method ['ok' => true, 'external_id' => '...', 'warning' => '...'(optional)]
 * ya ['ok' => false, 'message' => '...'] wapas karta hai.
 */
class ProductPushService
{
    /**
     * Kaun se platform pe push hota hai:
     *   label  = button pe naam
     *   column = products table ka woh column jis mein platform wala product ID save hota hai
     */
    public const PLATFORMS = [
        'shopify'     => ['label' => 'Shopify',     'column' => 'external_product_id'],
        'woocommerce' => ['label' => 'WooCommerce', 'column' => 'external_wc_product_id'],
        'bigcommerce' => ['label' => 'BigCommerce', 'column' => 'external_bc_product_id'],
        'wix'         => ['label' => 'Wix',         'column' => 'external_wix_product_id'],
        'magento'     => ['label' => 'Magento',     'column' => 'external_magento_product_id'],
        'prestashop'  => ['label' => 'PrestaShop',  'column' => 'external_ps_product_id'],
        'ebay'        => ['label' => 'eBay',        'column' => 'external_ebay_product_id'],
    ];

    /** Jin platforms pe push nahi hota — wajah (products page pe dikhti hai) */
    public const NOT_SUPPORTED = [
        'opencart'   => 'OpenCart is linked through its database, so products cannot be pushed from Finovo. Add the product in OpenCart admin, then click "Sync OpenCart".',
        'oscommerce' => 'osCommerce is linked through its database, so products cannot be pushed from Finovo. Add the product in osCommerce admin, then click "Sync osCommerce".',
        'cj'         => 'CJdropshipping is a supplier, products cannot be created there.',
        'custom'     => 'Custom stores do not accept new products from Finovo.',
    ];

    private const SHOPIFY_API_VERSION = '2026-07';

    private string $bigCommerceBase;
    private string $wixBase;
    private string $ebayBase;
    private string $shopifyScheme;

    /** $overrides sirf tests ke liye: ['bigcommerce' => url, 'wix' => url, 'ebay' => url, 'shopify_scheme' => 'http'] */
    public function __construct(array $overrides = [])
    {
        $this->bigCommerceBase = rtrim($overrides['bigcommerce'] ?? 'https://api.bigcommerce.com', '/');
        $this->wixBase = rtrim($overrides['wix'] ?? 'https://www.wixapis.com', '/');
        $this->shopifyScheme = $overrides['shopify_scheme'] ?? 'https';

        $defaultEbay = strtolower((string) env('EBAY_ENV', 'sandbox')) === 'production'
            ? 'https://api.ebay.com'
            : 'https://api.sandbox.ebay.com';
        $this->ebayBase = rtrim($overrides['ebay'] ?? $defaultEbay, '/');
    }

    // =====================================================================
    //  Chhote helpers (public static — PHPUnit tests inhe seedha check karte hain)
    // =====================================================================

    public static function money(float $value): string
    {
        return number_format($value, 2, '.', '');
    }

    /** Magento aur eBay pe SKU hi product ki pehchaan hai — product ka SKU na ho to "FINOVO-<id>" */
    public static function skuFor(array $product): string
    {
        $sku = trim((string) ($product['sku'] ?? ''));
        return $sku !== '' ? $sku : 'FINOVO-' . (int) $product['id'];
    }

    /** "Blue Shirt (Large)!" → "blue-shirt-large" (PrestaShop ka link_rewrite) */
    public static function slug(string $name): string
    {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $name), '-'));
        return $slug === '' ? 'product' : mb_substr($slug, 0, 120);
    }

    /**
     * Finovo ke variants → platform ke options.
     *   attributes = "Size, Color", label = "M / Red"  →  names [Size, Color], values [M, Red]
     * Naam aur values ki ginti na mile (ya 3 se zyada hon — Shopify sirf 3 options leta hai)
     * to ek hi option "Variant" jis ki value poora label.
     * @return array{names: string[], rows: array<int, array{variant: array, values: string[]}>}
     */
    public static function variantOptions(array $variants): array
    {
        $names = null;
        $rows = [];
        foreach ($variants as $v) {
            $values = array_map('trim', explode('/', (string) $v['label']));
            $attrNames = array_values(array_filter(array_map('trim', explode(',', (string) ($v['attributes'] ?? '')))));
            $rows[] = ['variant' => $v, 'values' => $values, 'attrNames' => $attrNames];
        }

        $first = $rows[0]['attrNames'] ?? [];
        $consistent = !empty($first) && count($first) <= 3;
        foreach ($rows as $r) {
            if ($r['attrNames'] !== $first || count($r['values']) !== count($first)) {
                $consistent = false;
            }
        }

        $out = [];
        foreach ($rows as $r) {
            $out[] = ['variant' => $r['variant'], 'values' => $consistent ? $r['values'] : [trim((string) $r['variant']['label'])]];
        }
        return ['names' => $consistent ? $first : ['Variant'], 'rows' => $out];
    }

    public static function buildShopifyPayload(array $product, array $variants = []): array
    {
        if (!empty($variants)) {
            $opts = self::variantOptions($variants);
            $shopifyVariants = [];
            foreach ($opts['rows'] as $row) {
                $v = $row['variant'];
                $sv = [
                    'price' => self::money((float) ($v['price'] ?? 0) > 0 ? (float) $v['price'] : (float) $product['price']),
                    'inventory_management' => 'shopify',
                ];
                foreach ($row['values'] as $i => $value) {
                    $sv['option' . ($i + 1)] = $value;
                }
                if (trim((string) ($v['sku'] ?? '')) !== '') {
                    $sv['sku'] = trim($v['sku']);
                }
                $shopifyVariants[] = $sv;
            }
            return ['product' => [
                'title' => $product['name'],
                'status' => 'active',
                'options' => array_map(fn($n) => ['name' => $n], $opts['names']),
                'variants' => $shopifyVariants,
            ]];
        }

        $variant = [
            'price' => self::money((float) $product['price']),
            'inventory_management' => 'shopify',
        ];
        if (trim((string) ($product['sku'] ?? '')) !== '') {
            $variant['sku'] = trim($product['sku']);
        }
        return ['product' => ['title' => $product['name'], 'status' => 'active', 'variants' => [$variant]]];
    }

    public static function buildWooCommercePayload(array $product, int $stock): array
    {
        return [
            'name' => $product['name'],
            'type' => 'simple',
            'regular_price' => self::money((float) $product['price']),
            'sku' => trim((string) ($product['sku'] ?? '')),
            'manage_stock' => true,
            'stock_quantity' => $stock,
        ];
    }

    public static function buildBigCommercePayload(array $product, int $stock): array
    {
        $payload = [
            'name' => $product['name'],
            'type' => 'physical',
            'weight' => 0,
            'price' => round((float) $product['price'], 2),
            'inventory_tracking' => 'product',
            'inventory_level' => $stock,
            'is_visible' => true,
        ];
        if (trim((string) ($product['sku'] ?? '')) !== '') {
            $payload['sku'] = trim($product['sku']);
        }
        return $payload;
    }

    /** Wix Catalog V3 — product ka kam se kam ek variant zaroori hai (bina options ke product = ek variant) */
    public static function buildWixPayload(array $product, int $stock): array
    {
        $variant = [
            'choices' => [],
            'price' => ['actualPrice' => ['amount' => self::money((float) $product['price'])]],
            'inventoryItem' => ['quantity' => $stock],
        ];
        if (trim((string) ($product['sku'] ?? '')) !== '') {
            $variant['sku'] = trim($product['sku']);
        }
        return [
            'product' => [
                'name' => $product['name'],
                'productType' => 'PHYSICAL',
                'physicalProperties' => (object) [],
                'variantsInfo' => ['variants' => [$variant]],
            ],
        ];
    }

    public static function buildMagentoPayload(array $product, int $stock): array
    {
        return [
            'product' => [
                'sku' => self::skuFor($product),
                'name' => $product['name'],
                'price' => round((float) $product['price'], 2),
                'status' => 1,             // 1 = Enabled
                'visibility' => 4,         // 4 = Catalog, Search
                'type_id' => 'simple',
                'attribute_set_id' => 4,   // 4 = "Default" attribute set
                'weight' => 1,
                'extension_attributes' => [
                    'website_ids' => [1],
                    'stock_item' => ['qty' => $stock, 'is_in_stock' => $stock > 0],
                ],
            ],
        ];
    }

    public static function buildEbayPayload(array $product, int $stock): array
    {
        return [
            'product' => [
                'title' => mb_substr((string) $product['name'], 0, 80),
                'description' => (string) $product['name'],
            ],
            'condition' => 'NEW',
            'availability' => ['shipToLocationAvailability' => ['quantity' => $stock]],
        ];
    }

    /** PrestaShop product ka XML (naam aur link har language ke liye alag hote hain) */
    public static function buildPrestaShopXml(array $product, int $langId): string
    {
        $cdata = fn($v) => '<![CDATA[' . str_replace(']]>', ']]]]><![CDATA[>', (string) $v) . ']]>';
        $lang = fn($v) => '<language id="' . $langId . '">' . $cdata($v) . '</language>';

        return '<?xml version="1.0" encoding="UTF-8"?><prestashop xmlns:xlink="http://www.w3.org/1999/xlink"><product>'
            . '<name>' . $lang(mb_substr((string) $product['name'], 0, 128)) . '</name>'
            . '<link_rewrite>' . $lang(self::slug((string) $product['name'])) . '</link_rewrite>'
            . '<price>' . self::money((float) $product['price']) . '</price>'
            . '<reference>' . $cdata(mb_substr(trim((string) ($product['sku'] ?? '')), 0, 64)) . '</reference>'
            . '<id_category_default>2</id_category_default>'   // 2 = "Home" category
            . '<active>1</active>'
            . '<state>1</state>'
            . '<available_for_order>1</available_for_order>'
            . '<show_price>1</show_price>'
            . '<minimal_quantity>1</minimal_quantity>'
            . '<associations><categories><category><id>2</id></category></categories></associations>'
            . '</product></prestashop>';
    }

    // =====================================================================
    //  Har platform pe product banana
    // =====================================================================

    /**
     * $variants = Finovo ke variants (product_variants rows). Hon to Shopify pe har variant alag banta hai
     * (options ke saath) aur har ek ka apna stock; jawab mein 'variant_ids' => [Finovo variant id => Shopify variant id].
     */
    public function pushShopify(array $store, array $product, int $stock, array $variants = []): array
    {
        if (empty($store['store_url']) || empty($store['access_token'])) {
            return ['ok' => false, 'message' => 'Please connect this store to Shopify first (Stores → Edit → Connect Shopify).'];
        }

        $headers = ['X-Shopify-Access-Token: ' . $store['access_token'], 'Content-Type: application/json', 'Accept: application/json'];
        $res = $this->http('POST', $this->shopifyUrl($store, '/products.json'), $headers, json_encode(self::buildShopifyPayload($product, $variants)));

        $id = $res['data']['product']['id'] ?? null;
        if ($res['code'] !== 201 || empty($id)) {
            return ['ok' => false, 'message' => 'Shopify push failed ' . self::describe($res)];
        }

        // Kaun sa Shopify variant, kitna stock (variants nahi → ek hi variant, kul stock)
        $created = $res['data']['product']['variants'] ?? [];
        $stockTargets = [];   // [inventory_item_id => qty]
        $variantIds = [];     // [Finovo variant id => Shopify variant id]
        if (!empty($variants)) {
            $opts = self::variantOptions($variants);
            foreach ($opts['rows'] as $row) {
                foreach ($created as $sv) {
                    $svValues = array_values(array_filter([$sv['option1'] ?? null, $sv['option2'] ?? null, $sv['option3'] ?? null], fn($x) => $x !== null));
                    if ($svValues === $row['values']) {
                        $variantIds[(int) $row['variant']['id']] = (string) $sv['id'];
                        if (!empty($sv['inventory_item_id'])) {
                            $stockTargets[(string) $sv['inventory_item_id']] = max(0, (int) ($row['variant']['stock_quantity'] ?? 0));
                        }
                    }
                }
            }
        } elseif (!empty($created[0]['inventory_item_id'])) {
            $stockTargets[(string) $created[0]['inventory_item_id']] = $stock;
        }

        // Stock: naya inventory item Shopify ki main location pe hota hai — wahan quantity set karo
        $warning = null;
        $locationId = null;
        foreach ($stockTargets as $inventoryItemId => $qty) {
            if ($locationId === null) {
                $levels = $this->http('GET', $this->shopifyUrl($store, '/inventory_levels.json?inventory_item_ids=' . rawurlencode((string) $inventoryItemId)), $headers);
                $locationId = $levels['data']['inventory_levels'][0]['location_id'] ?? 0;
            }
            $set = $locationId ? $this->http('POST', $this->shopifyUrl($store, '/inventory_levels/set.json'), $headers, json_encode([
                'location_id' => $locationId,
                'inventory_item_id' => (int) $inventoryItemId,
                'available' => $qty,
            ])) : ['code' => 0];
            if ($set['code'] !== 200) {
                $warning = 'Product was created, but its stock could not be set on Shopify. Set the quantity in Shopify admin.';
            }
        }

        return ['ok' => true, 'external_id' => (string) $id, 'warning' => $warning, 'variant_ids' => $variantIds];
    }

    /**
     * Variants hon to "variable" product banta hai + har variant ek "variation" (apne price, SKU, stock ke saath).
     * Jawab mein 'variant_ids' => [Finovo variant id => WooCommerce variation id].
     */
    public function pushWooCommerce(array $store, array $product, int $stock, array $variants = []): array
    {
        if (empty($store['woocommerce_store_url']) || empty($store['woocommerce_consumer_key']) || empty($store['woocommerce_consumer_secret'])) {
            return ['ok' => false, 'message' => 'Please connect this store to WooCommerce first (Stores → Edit → Connect WooCommerce).'];
        }

        $url = rtrim($store['woocommerce_store_url'], '/') . '/wp-json/wc/v3/products';
        $auth = $store['woocommerce_consumer_key'] . ':' . $store['woocommerce_consumer_secret'];
        $payload = empty($variants)
            ? self::buildWooCommercePayload($product, $stock)
            : self::buildWooCommerceVariablePayload($product, $variants);
        $res = $this->http('POST', $url, ['Content-Type: application/json'], json_encode($payload), $auth);

        $id = $res['data']['id'] ?? null;
        if ($res['code'] !== 201 || empty($id)) {
            return ['ok' => false, 'message' => 'WooCommerce push failed ' . self::describe($res)];
        }
        if (empty($variants)) {
            return ['ok' => true, 'external_id' => (string) $id];
        }

        // Variations ek hi request mein (batch)
        $opts = self::variantOptions($variants);
        $create = [];
        foreach ($opts['rows'] as $row) {
            $create[] = self::buildWooCommerceVariation($product, $row, $opts['names']);
        }
        $batch = $this->http('POST', $url . '/' . rawurlencode((string) $id) . '/variations/batch', ['Content-Type: application/json'], json_encode(['create' => $create]), $auth);

        $variantIds = [];
        $failed = 0;
        foreach (($batch['data']['create'] ?? []) as $i => $created) {
            if (!empty($created['id']) && empty($created['error']) && isset($opts['rows'][$i])) {
                $variantIds[(int) $opts['rows'][$i]['variant']['id']] = (string) $created['id'];
            } else {
                $failed++;
            }
        }
        $warning = null;
        if (!in_array($batch['code'], [200, 201], true) || $failed > 0 || count($variantIds) < count($opts['rows'])) {
            $warning = 'Product was created, but some of its variants could not be added on WooCommerce. Check the product in WooCommerce admin.';
        }

        return ['ok' => true, 'external_id' => (string) $id, 'warning' => $warning, 'variant_ids' => $variantIds];
    }

    public static function buildWooCommerceVariablePayload(array $product, array $variants): array
    {
        $opts = self::variantOptions($variants);
        $attributes = [];
        foreach ($opts['names'] as $i => $name) {
            $values = array_values(array_unique(array_map(fn($r) => $r['values'][$i], $opts['rows'])));
            $attributes[] = ['name' => $name, 'options' => $values, 'visible' => true, 'variation' => true];
        }
        return [
            'name' => $product['name'],
            'type' => 'variable',
            'sku' => trim((string) ($product['sku'] ?? '')),
            'attributes' => $attributes,
        ];
    }

    public static function buildWooCommerceVariation(array $product, array $row, array $names): array
    {
        $v = $row['variant'];
        $price = (float) ($v['price'] ?? 0) > 0 ? (float) $v['price'] : (float) $product['price'];
        $variation = [
            'regular_price' => self::money($price),
            'manage_stock' => true,
            'stock_quantity' => max(0, (int) ($v['stock_quantity'] ?? 0)),
            'attributes' => array_map(fn($name, $value) => ['name' => $name, 'option' => $value], $names, $row['values']),
        ];
        if (trim((string) ($v['sku'] ?? '')) !== '') {
            $variation['sku'] = trim($v['sku']);
        }
        return $variation;
    }

    public function pushBigCommerce(array $store, array $product, int $stock): array
    {
        if (empty($store['bigcommerce_store_hash']) || empty($store['bigcommerce_access_token'])) {
            return ['ok' => false, 'message' => 'This store is not connected to BigCommerce (Store Hash / Access Token missing).'];
        }

        $url = $this->bigCommerceBase . '/stores/' . rawurlencode($store['bigcommerce_store_hash']) . '/v3/catalog/products';
        $res = $this->http('POST', $url, [
            'X-Auth-Token: ' . $store['bigcommerce_access_token'],
            'Accept: application/json',
            'Content-Type: application/json',
        ], json_encode(self::buildBigCommercePayload($product, $stock)));

        $id = $res['data']['data']['id'] ?? null;
        if (!in_array($res['code'], [200, 201], true) || empty($id)) {
            return ['ok' => false, 'message' => 'BigCommerce push failed ' . self::describe($res)];
        }
        return ['ok' => true, 'external_id' => (string) $id];
    }

    public function pushWix(array $store, array $product, int $stock): array
    {
        if (empty($store['wix_site_id']) || empty($store['wix_api_key'])) {
            return ['ok' => false, 'message' => 'This store is not connected to Wix (Site ID / API Key missing).'];
        }

        $res = $this->http('POST', $this->wixBase . '/stores/v3/products-with-inventory', [
            'Authorization: ' . $store['wix_api_key'],
            'wix-site-id: ' . $store['wix_site_id'],
            'Content-Type: application/json',
        ], json_encode(self::buildWixPayload($product, $stock)));

        $id = $res['data']['product']['id'] ?? null;
        if ($res['code'] !== 200 || empty($id)) {
            return ['ok' => false, 'message' => 'Wix push failed ' . self::describe($res)];
        }
        return ['ok' => true, 'external_id' => (string) $id];
    }

    public function pushMagento(array $store, array $product, int $stock): array
    {
        if (empty($store['magento_store_url']) || empty($store['magento_access_token'])) {
            return ['ok' => false, 'message' => 'This store is not connected to Magento (Store URL / Access Token missing).'];
        }

        $url = rtrim($store['magento_store_url'], '/') . '/rest/V1/products';
        $res = $this->http('POST', $url, [
            'Authorization: Bearer ' . $store['magento_access_token'],
            'Accept: application/json',
            'Content-Type: application/json',
        ], json_encode(self::buildMagentoPayload($product, $stock)));

        $id = $res['data']['id'] ?? null;
        if ($res['code'] !== 200 || empty($id)) {
            return ['ok' => false, 'message' => 'Magento push failed ' . self::describe($res)];
        }
        return ['ok' => true, 'external_id' => (string) $id];
    }

    /** PrestaShop: product (XML) banao, phir uska stock set karo */
    public function pushPrestaShop(array $store, array $product, int $stock): array
    {
        if (empty($store['prestashop_store_url']) || empty($store['prestashop_api_key'])) {
            return ['ok' => false, 'message' => 'This store is not connected to PrestaShop (Store URL / API Key missing).'];
        }

        $api = rtrim($store['prestashop_store_url'], '/') . '/api';
        $key = $store['prestashop_api_key'];

        // Shop ki default language (naam usi language mein jata hai)
        $res = $this->http('GET', $api . '/configurations?' . http_build_query([
            'ws_key' => $key, 'output_format' => 'JSON', 'display' => '[name,value]', 'filter[name]' => '[PS_LANG_DEFAULT]',
        ]), ['Accept: application/json']);
        $langId = (int) ($res['data']['configurations'][0]['value'] ?? 0);
        if ($langId <= 0) {
            return ['ok' => false, 'message' => 'PrestaShop push failed: could not read shop settings. In PrestaShop → Advanced Parameters → Webservice, give this key GET access to "configurations" and "stock_availables", POST access to "products" and PUT access to "stock_availables".'];
        }

        // 1) Product
        $res = $this->http('POST', $api . '/products?ws_key=' . urlencode($key), ['Content-Type: text/xml'], self::buildPrestaShopXml($product, $langId));
        $productId = self::prestaShopId($res, 'product');
        if ($productId <= 0) {
            return ['ok' => false, 'message' => 'PrestaShop push failed: ' . self::prestaShopError($res)];
        }

        // 2) Stock — PrestaShop product ke saath ek "stock_available" record khud bana deta hai, usi ki quantity badlo
        $warning = 'Product was created, but its stock could not be set on PrestaShop. Set the quantity in PrestaShop admin.';
        $res = $this->http('GET', $api . '/stock_availables?' . http_build_query([
            'ws_key' => $key, 'output_format' => 'JSON', 'display' => 'full',
            'filter[id_product]' => '[' . $productId . ']', 'filter[id_product_attribute]' => '[0]',
        ]), ['Accept: application/json']);
        $sa = $res['data']['stock_availables'][0] ?? null;

        if (is_array($sa) && !empty($sa['id'])) {
            $xml = '<?xml version="1.0" encoding="UTF-8"?><prestashop xmlns:xlink="http://www.w3.org/1999/xlink"><stock_available>'
                . '<id>' . (int) $sa['id'] . '</id>'
                . '<id_product>' . $productId . '</id_product>'
                . '<id_product_attribute>' . (int) ($sa['id_product_attribute'] ?? 0) . '</id_product_attribute>'
                . '<id_shop>' . (int) ($sa['id_shop'] ?? 1) . '</id_shop>'
                . '<id_shop_group>' . (int) ($sa['id_shop_group'] ?? 0) . '</id_shop_group>'
                . '<quantity>' . $stock . '</quantity>'
                . '<depends_on_stock>' . (int) ($sa['depends_on_stock'] ?? 0) . '</depends_on_stock>'
                . '<out_of_stock>' . (int) ($sa['out_of_stock'] ?? 2) . '</out_of_stock>'
                . '</stock_available></prestashop>';
            $res = $this->http('PUT', $api . '/stock_availables/' . (int) $sa['id'] . '?ws_key=' . urlencode($key), ['Content-Type: text/xml'], $xml);
            if (in_array($res['code'], [200, 201], true)) {
                $warning = null;
            }
        }

        return ['ok' => true, 'external_id' => (string) $productId, 'warning' => $warning];
    }

    /**
     * eBay: product "inventory item" ban jata hai (SKU ke naam se). Live listing ke liye eBay ko
     * category, price aur shipping/payment/return policies chahiye — woh Seller Hub mein "offer" se banti hai.
     */
    public function pushEbay(string $accessToken, array $product, int $stock): array
    {
        $sku = self::skuFor($product);
        $res = $this->http('PUT', $this->ebayBase . '/sell/inventory/v1/inventory_item/' . rawurlencode($sku), [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json',
            'Content-Language: en-US',
            'Accept: application/json',
        ], json_encode(self::buildEbayPayload($product, $stock)));

        if (!in_array($res['code'], [200, 201, 204], true)) {
            return ['ok' => false, 'message' => 'eBay push failed ' . self::describe($res)];
        }
        return [
            'ok' => true,
            'external_id' => $sku,
            'warning' => 'Product was added to your eBay inventory (SKU ' . $sku . '). To sell it, create a listing for this SKU in eBay Seller Hub (price, category and shipping are set there).',
        ];
    }

    // =====================================================================
    //  Helpers
    // =====================================================================

    private function shopifyUrl(array $store, string $path): string
    {
        $host = preg_replace('#^https?://#', '', rtrim($store['store_url'], '/'));
        return $this->shopifyScheme . '://' . $host . '/admin/api/' . self::SHOPIFY_API_VERSION . $path;
    }

    private static function prestaShopId(array $res, string $resource): int
    {
        if (!in_array($res['code'], [200, 201], true) || $res['raw'] === '') {
            return 0;
        }
        $prev = libxml_use_internal_errors(true);
        $doc = simplexml_load_string($res['raw'], 'SimpleXMLElement', LIBXML_NONET | LIBXML_NOCDATA);
        libxml_use_internal_errors($prev);
        return ($doc !== false && isset($doc->{$resource}->id)) ? (int) $doc->{$resource}->id : 0;
    }

    private static function prestaShopError(array $res): string
    {
        if ($res['raw'] !== '') {
            $prev = libxml_use_internal_errors(true);
            $doc = simplexml_load_string($res['raw'], 'SimpleXMLElement', LIBXML_NONET | LIBXML_NOCDATA);
            libxml_use_internal_errors($prev);
            if ($doc !== false && isset($doc->errors->error)) {
                $messages = [];
                foreach ($doc->errors->error as $e) {
                    $messages[] = trim((string) $e->message);
                }
                return "HTTP {$res['code']}: " . implode(' | ', array_filter($messages));
            }
        }
        return self::describe($res);
    }

    private function http(string $method, string $url, array $headers, ?string $body = null, ?string $basicAuth = null): array
    {
        // $basicAuth sirf WooCommerce deta hai — http:// site pe URL pe OAuth signature lagta hai
        if ($basicAuth !== null) {
            [$consumerKey, $consumerSecret] = array_pad(explode(':', $basicAuth, 2), 2, '');
            $url = WooCommerceAuth::url($method, $url, $consumerKey, $consumerSecret);
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 25);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, curlVerifySsl());
        if ($basicAuth !== null) {
            curl_setopt($ch, CURLOPT_USERPWD, $basicAuth);
        }
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $raw = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            return ['code' => 0, 'raw' => '', 'data' => null, 'error' => $error];
        }
        return ['code' => $code, 'raw' => (string) $raw, 'data' => json_decode((string) $raw, true), 'error' => ''];
    }

    /** Error message: HTTP code + platform ka apna message (lamba ho to chhota karke) */
    private static function describe(array $res): string
    {
        if ($res['code'] === 0) {
            return '(could not connect: ' . $res['error'] . ')';
        }

        $data = $res['data'];
        $message = '';
        if (is_array($data)) {
            $message = $data['message'] ?? $data['title'] ?? $data[0]['message'] ?? $data['errors'][0]['message']
                ?? $data['details']['applicationError']['description'] ?? $data['errors'] ?? '';
            if (is_array($message)) {
                $message = json_encode($message);
            }
            if (!empty($data['parameters']) && is_string($message)) {
                // Magento: "%1 is required" + parameters
                foreach ((array) $data['parameters'] as $i => $p) {
                    if (is_scalar($p)) {
                        $message = str_replace('%' . (is_int($i) ? $i + 1 : $i), (string) $p, $message);
                    }
                }
            }
        }
        if ($message === '') {
            $message = strip_tags($res['raw']);
        }

        return "(HTTP {$res['code']}): " . mb_substr(trim((string) $message), 0, 300);
    }
}