<?php
require_once __DIR__ . '/WooCommerceAuth.php';

class OrderPushService
{
    // Order mein address/email na ho to platforms phir bhi ye fields maangte hain — yeh default bharte hain
    public const DEFAULT_COUNTRY_ISO2 = 'PK';
    public const DEFAULT_COUNTRY_NAME = 'Pakistan';
    public const DEFAULT_CITY = 'N/A';
    public const DEFAULT_POSTCODE = '00000';
    public const DEFAULT_PHONE = '0000000000';
    public const DEFAULT_EMAIL = 'no-reply@finovoglobal.com';

    private const SHOPIFY_API_VERSION = '2026-07';

    // Wix Stores app ka fixed ID (catalogReference mein lagta hai)
    private const WIX_STORES_APP_ID = '215238eb-22a5-4c36-9e7b-e7c08025e04e';

    private string $bigCommerceBase;
    private string $wixBase;
    private string $ebayBase;
    private string $shopifyScheme;

    /**
     * $overrides sirf tests ke liye: ['bigcommerce' => url, 'wix' => url, 'ebay' => url, 'shopify_scheme' => 'http']
     */
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

    public static function splitName(?string $fullName): array
    {
        $fullName = trim((string) $fullName);
        if ($fullName === '') {
            return ['Customer', '-'];
        }
        $parts = preg_split('/\s+/', $fullName, 2);
        $first = $parts[0];
        $last = trim($parts[1] ?? '');
        return [$first, $last === '' ? '-' : $last];
    }

    /** Order ka address (shipping pehle, phir billing), warna "Not provided" */
    public static function orderAddress(array $order): string
    {
        $address = trim((string) ($order['shipping_address'] ?? ''));
        if ($address === '') {
            $address = trim((string) ($order['billing_address'] ?? ''));
        }
        return $address === '' ? 'Not provided' : $address;
    }

    public static function orderEmail(array $order): string
    {
        $email = trim((string) ($order['customer_email'] ?? ''));
        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : self::DEFAULT_EMAIL;
    }

    public static function orderPhone(array $order): string
    {
        $phone = trim((string) ($order['customer_phone'] ?? ''));
        return $phone === '' ? self::DEFAULT_PHONE : $phone;
    }

    public static function linesFromRows(array $rows, array $productsById = []): array
    {
        $lines = [];
        foreach ($rows as $row) {
            $key = ($row['product_id'] ?? '') . '|' . ($row['variant_id'] ?? '') . '|' . ($row['product_name'] ?? '') . '|' . ($row['price'] ?? '');
            if (!isset($lines[$key])) {
                $lines[$key] = [
                    'name' => (string) ($row['product_name'] ?? 'Item'),
                    'quantity' => 0,
                    'unit_price' => round((float) ($row['price'] ?? 0), 2),
                    'product' => $productsById[(int) ($row['product_id'] ?? 0)] ?? null,
                ];
            }
            $lines[$key]['quantity'] += max(1, (int) ($row['quantity'] ?? 1));
        }
        return array_values($lines);
    }

    public static function linesSubtotal(array $lines): float
    {
        $sum = 0.0;
        foreach ($lines as $line) {
            $sum += (float) $line['unit_price'] * (int) $line['quantity'];
        }
        return round($sum, 2);
    }

    /** Orders table mein "price" EK item ki price hai; total = sab lines + shipping + tax − discount */
    public static function orderTotal(array $order, array $lines): float
    {
        $total = self::linesSubtotal($lines)
            + (float) ($order['shipping_cost'] ?? 0)
            + (float) ($order['tax'] ?? 0)
            - (float) ($order['discount'] ?? 0);
        return round(max(0, $total), 2);
    }

    public static function isPaid(array $order): bool
    {
        return strtolower((string) ($order['payment_status'] ?? '')) === 'paid';
    }

    private static function money(float $value): string
    {
        return number_format($value, 2, '.', '');
    }

    /** PrestaShop naam mein digits / ajeeb characters qubool nahi karta */
    public static function cleanPrestaShopName(string $name): string
    {
        $clean = trim(preg_replace('/[0-9!<>,;?=+()@#"°{}_$%:\/\\\\*^|~]+/u', ' ', $name));
        $clean = trim(preg_replace('/\s+/', ' ', $clean));
        return $clean === '' ? 'Customer' : mb_substr($clean, 0, 60);
    }

    /** PrestaShop address mein bhi kuch characters mana hain */
    public static function cleanPrestaShopAddress(string $text): string
    {
        $clean = trim(preg_replace('/[!<>?=+@{}_$%]+/u', ' ', $text));
        $clean = trim(preg_replace('/\s+/', ' ', $clean));
        return $clean === '' ? 'Not provided' : mb_substr($clean, 0, 128);
    }

    public static function buildShopifyPayload(array $order, array $lines): array
    {
        [$first, $last] = self::splitName($order['customer_name'] ?? '');

        $lineItems = [];
        foreach ($lines as $line) {
            $lineItems[] = [
                'title' => $line['name'],
                'price' => self::money($line['unit_price']),
                'quantity' => (int) $line['quantity'],
            ];
        }

        $payload = [
            'order' => [
                'line_items' => $lineItems,
                'customer' => ['first_name' => $first, 'last_name' => $last],
                'financial_status' => self::isPaid($order) ? 'paid' : 'pending',
                'note' => 'Created from Finovo OMS (order #' . (int) $order['id'] . ')',
            ],
        ];
        if (self::orderEmail($order) !== self::DEFAULT_EMAIL) {
            $payload['order']['email'] = self::orderEmail($order);
        }
        return $payload;
    }

    public static function buildWooCommercePayload(array $order, array $lineItems): array
    {
        [$first, $last] = self::splitName($order['customer_name'] ?? '');
        return [
            'payment_method' => 'manual',
            'payment_method_title' => 'Manual (Finovo)',
            'set_paid' => self::isPaid($order),
            'billing' => [
                'first_name' => $first,
                'last_name' => $last,
                'address_1' => self::orderAddress($order),
                'email' => self::orderEmail($order),
                'phone' => self::orderPhone($order),
            ],
            'line_items' => $lineItems,
            'customer_note' => 'Created from Finovo OMS (order #' . (int) $order['id'] . ')',
        ];
    }

    public static function buildBigCommercePayload(array $order, array $lines): array
    {
        [$first, $last] = self::splitName($order['customer_name'] ?? '');

        $products = [];
        foreach ($lines as $line) {
            if (!empty($line['product']['external_bc_product_id'])) {
                $products[] = ['product_id' => (int) $line['product']['external_bc_product_id'], 'quantity' => (int) $line['quantity']];
            } else {
                $products[] = [
                    'name' => $line['name'],
                    'quantity' => (int) $line['quantity'],
                    'price_inc_tax' => (float) $line['unit_price'],
                    'price_ex_tax' => (float) $line['unit_price'],
                ];
            }
        }

        return [
            // 11 = Awaiting Fulfillment (paid), 1 = Pending (unpaid)
            'status_id' => self::isPaid($order) ? 11 : 1,
            'customer_id' => 0,
            'billing_address' => [
                'first_name' => $first,
                'last_name' => $last,
                'street_1' => mb_substr(self::orderAddress($order), 0, 255),
                'city' => self::DEFAULT_CITY,
                'state' => self::DEFAULT_CITY,
                'zip' => self::DEFAULT_POSTCODE,
                'country' => self::DEFAULT_COUNTRY_NAME,
                'country_iso2' => self::DEFAULT_COUNTRY_ISO2,
                'email' => self::orderEmail($order),
                'phone' => self::orderPhone($order),
            ],
            'products' => $products,
            'shipping_cost_inc_tax' => round((float) ($order['shipping_cost'] ?? 0), 2),
            'shipping_cost_ex_tax' => round((float) ($order['shipping_cost'] ?? 0), 2),
            'discount_amount' => round((float) ($order['discount'] ?? 0), 2),
            'payment_method' => (string) ($order['payment_method'] ?? 'Manual'),
            'staff_notes' => 'Created from Finovo OMS (order #' . (int) $order['id'] . ')',
        ];
    }

    public static function buildWixPayload(array $order, array $lines): array
    {
        [$first, $last] = self::splitName($order['customer_name'] ?? '');

        $lineItems = [];
        foreach ($lines as $line) {
            $item = [
                'productName' => ['original' => $line['name']],
                'quantity' => (int) $line['quantity'],
                'itemType' => ['preset' => 'PHYSICAL'],
                'price' => ['amount' => self::money($line['unit_price'])],
                'taxDetails' => ['taxRate' => '0'],
            ];
            if (!empty($line['product']['external_wix_product_id'])) {
                $item['catalogReference'] = [
                    'catalogItemId' => (string) $line['product']['external_wix_product_id'],
                    'appId' => self::WIX_STORES_APP_ID,
                ];
            }
            $lineItems[] = $item;
        }

        return [
            'order' => [
                'lineItems' => $lineItems,
                'priceSummary' => [
                    'subtotal' => ['amount' => self::money(self::linesSubtotal($lines))],
                    'total' => ['amount' => self::money(self::orderTotal($order, $lines))],
                ],
                'buyerInfo' => ['email' => self::orderEmail($order)],
                'billingInfo' => [
                    'address' => [
                        'addressLine1' => self::orderAddress($order),
                        'city' => self::DEFAULT_CITY,
                        'country' => self::DEFAULT_COUNTRY_ISO2,
                    ],
                    'contactDetails' => [
                        'firstName' => $first,
                        'lastName' => $last,
                        'phone' => self::orderPhone($order),
                    ],
                ],
                'channelInfo' => [
                    'type' => 'OTHER_PLATFORM',
                    'externalOrderId' => 'finovo-' . (int) $order['id'],
                ],
                'paymentStatus' => self::isPaid($order) ? 'PAID' : 'NOT_PAID',
                'status' => 'APPROVED',
            ],
        ];
    }

    public static function buildMagentoAddress(array $order): array
    {
        [$first, $last] = self::splitName($order['customer_name'] ?? '');
        return [
            'firstname' => $first,
            'lastname' => $last,
            'street' => [mb_substr(self::orderAddress($order), 0, 255)],
            'city' => self::DEFAULT_CITY,
            'postcode' => self::DEFAULT_POSTCODE,
            'country_id' => self::DEFAULT_COUNTRY_ISO2,
            'telephone' => self::orderPhone($order),
            'email' => self::orderEmail($order),
        ];
    }

    /** eBay ko courier ka naam uske code mein chahiye; jo list mein nahi woh "Other" */
    public static function ebayCarrierCode(?string $courierName): string
    {
        $name = strtolower(trim((string) $courierName));
        $map = [
            'dhl' => 'DHL', 'fedex' => 'FedEx', 'ups' => 'UPS', 'usps' => 'USPS',
            'aramex' => 'Aramex', 'tnt' => 'TNT', 'royal mail' => 'RoyalMail',
        ];
        foreach ($map as $needle => $code) {
            if ($name !== '' && str_contains($name, $needle)) {
                return $code;
            }
        }
        return 'Other';
    }

    public static function buildEbayFulfillmentPayload(array $ebayLineItems, string $trackingNumber, ?string $courierName, ?string $shippedAt = null): array
    {
        $lines = [];
        foreach ($ebayLineItems as $li) {
            if (!empty($li['lineItemId'])) {
                $lines[] = ['lineItemId' => (string) $li['lineItemId'], 'quantity' => max(1, (int) ($li['quantity'] ?? 1))];
            }
        }

        $time = $shippedAt ? strtotime($shippedAt) : time();
        return [
            'lineItems' => $lines,
            'shippedDate' => gmdate('Y-m-d\TH:i:s.000\Z', $time ?: time()),
            'shippingCarrierCode' => self::ebayCarrierCode($courierName),
            'trackingNumber' => $trackingNumber,
        ];
    }

    // =====================================================================
    //  1) NAYA ORDER BANANA
    // =====================================================================

    public function pushShopify(array $store, array $order, array $lines): array
    {
        if (empty($store['store_url']) || empty($store['access_token'])) {
            return ['ok' => false, 'message' => 'Please connect this store to Shopify first (Stores → Edit → Connect Shopify).'];
        }

        $res = $this->http('POST', $this->shopifyUrl($store, '/orders.json'), $this->shopifyHeaders($store),
            json_encode(self::buildShopifyPayload($order, $lines)));

        $id = $res['data']['order']['id'] ?? null;
        if ($res['code'] !== 201 || empty($id)) {
            return ['ok' => false, 'message' => 'Shopify push failed ' . self::describe($res)];
        }
        return ['ok' => true, 'external_id' => (string) $id];
    }

    public function pushWooCommerce(array $store, array $order, array $lines): array
    {
        if (empty($store['woocommerce_store_url']) || empty($store['woocommerce_consumer_key']) || empty($store['woocommerce_consumer_secret'])) {
            return ['ok' => false, 'message' => 'Please connect this store to WooCommerce first (Stores → Edit → Connect WooCommerce).'];
        }

        $base = rtrim($store['woocommerce_store_url'], '/') . '/wp-json/wc/v3';
        $auth = $store['woocommerce_consumer_key'] . ':' . $store['woocommerce_consumer_secret'];

        // Har line ka WooCommerce product: juda hua ID → naam se search → na mile to bana do
        $lineItems = [];
        foreach ($lines as $line) {
            $productId = (int) ($line['product']['external_wc_product_id'] ?? 0);

            if ($productId <= 0) {
                $res = $this->http('GET', $base . '/products?search=' . urlencode($line['name']), [], null, $auth);
                foreach ((is_array($res['data']) ? $res['data'] : []) as $wp) {
                    if (isset($wp['name']) && strcasecmp($wp['name'], $line['name']) === 0) {
                        $productId = (int) $wp['id'];
                        break;
                    }
                }
            }

            if ($productId <= 0) {
                $res = $this->http('POST', $base . '/products', ['Content-Type: application/json'], json_encode([
                    'name' => $line['name'],
                    'type' => 'simple',
                    'regular_price' => self::money($line['unit_price']),
                ]), $auth);
                $productId = (int) ($res['data']['id'] ?? 0);
                if ($res['code'] !== 201 || $productId <= 0) {
                    return ['ok' => false, 'message' => "Could not create product \"{$line['name']}\" on WooCommerce " . self::describe($res)];
                }
            }

            $lineItems[] = [
                'product_id' => $productId,
                'quantity' => (int) $line['quantity'],
                'subtotal' => self::money($line['unit_price'] * $line['quantity']),
                'total' => self::money($line['unit_price'] * $line['quantity']),
            ];
        }

        $res = $this->http('POST', $base . '/orders', ['Content-Type: application/json'],
            json_encode(self::buildWooCommercePayload($order, $lineItems)), $auth);

        $id = $res['data']['id'] ?? null;
        if ($res['code'] !== 201 || empty($id)) {
            return ['ok' => false, 'message' => 'WooCommerce push failed ' . self::describe($res)];
        }
        return ['ok' => true, 'external_id' => (string) $id];
    }

    public function pushBigCommerce(array $store, array $order, array $lines): array
    {
        if (empty($store['bigcommerce_store_hash']) || empty($store['bigcommerce_access_token'])) {
            return ['ok' => false, 'message' => 'This store is not connected to BigCommerce (Store Hash / Access Token missing).'];
        }

        $res = $this->http('POST', $this->bigCommerceUrl($store, '/orders'), $this->bigCommerceHeaders($store),
            json_encode(self::buildBigCommercePayload($order, $lines)));

        if (!in_array($res['code'], [200, 201], true) || empty($res['data']['id'])) {
            return ['ok' => false, 'message' => 'BigCommerce push failed ' . self::describe($res)];
        }
        return ['ok' => true, 'external_id' => (string) $res['data']['id']];
    }

    public function pushWix(array $store, array $order, array $lines): array
    {
        if (empty($store['wix_site_id']) || empty($store['wix_api_key'])) {
            return ['ok' => false, 'message' => 'This store is not connected to Wix (Site ID / API Key missing).'];
        }

        $res = $this->http('POST', $this->wixBase . '/ecom/v1/orders', [
            'Authorization: ' . $store['wix_api_key'],
            'wix-site-id: ' . $store['wix_site_id'],
            'Content-Type: application/json',
        ], json_encode(self::buildWixPayload($order, $lines)));

        $orderId = $res['data']['order']['id'] ?? null;
        if ($res['code'] !== 200 || empty($orderId)) {
            $hint = $res['code'] === 429 ? ' (Wix allows only a few Create Order calls per hour for apps that are not published.)' : '';
            return ['ok' => false, 'message' => 'Wix push failed ' . self::describe($res) . $hint];
        }
        return ['ok' => true, 'external_id' => (string) $orderId];
    }

    /** Magento 2 — guest checkout ke steps: cart → products → shipping → payment → order */
    public function pushMagento(array $store, array $order, array $lines): array
    {
        if (empty($store['magento_store_url']) || empty($store['magento_access_token'])) {
            return ['ok' => false, 'message' => 'This store is not connected to Magento (Store URL / Access Token missing).'];
        }

        foreach ($lines as $line) {
            if (trim((string) ($line['product']['sku'] ?? '')) === '') {
                return ['ok' => false, 'message' => "Product \"{$line['name']}\" has no SKU. Sync products from Magento first, so the product exists in Magento with the same SKU."];
            }
        }

        $base = rtrim($store['magento_store_url'], '/') . '/rest/V1';
        $headers = $this->magentoHeaders($store);
        $address = self::buildMagentoAddress($order);

        // 1) Khali cart banao
        $res = $this->http('POST', $base . '/guest-carts', $headers, '{}');
        $cartId = is_string($res['data']) ? $res['data'] : null;
        if ($res['code'] !== 200 || !$cartId) {
            return ['ok' => false, 'message' => 'Magento push failed at "create cart" ' . self::describe($res)];
        }
        $cartPath = $base . '/guest-carts/' . rawurlencode($cartId);

        // 2) Har product cart mein daalo (Magento apni catalog price lagata hai)
        foreach ($lines as $line) {
            $sku = trim((string) $line['product']['sku']);
            $res = $this->http('POST', $cartPath . '/items', $headers, json_encode([
                'cartItem' => ['sku' => $sku, 'qty' => (int) $line['quantity'], 'quote_id' => $cartId],
            ]));
            if ($res['code'] !== 200) {
                return ['ok' => false, 'message' => "Magento push failed at \"add product (SKU {$sku})\" " . self::describe($res)];
            }
        }

        // 3) Jo shipping method available hai woh lo (flatrate, freeshipping, ...)
        $res = $this->http('POST', $cartPath . '/estimate-shipping-methods', $headers, json_encode(['address' => $address]));
        $method = null;
        foreach ((is_array($res['data']) ? $res['data'] : []) as $m) {
            if (!empty($m['available']) && !empty($m['carrier_code']) && !empty($m['method_code'])) {
                $method = $m;
                break;
            }
        }
        if ($res['code'] !== 200 || !$method) {
            return ['ok' => false, 'message' => 'Magento push failed: no shipping method is available. Enable e.g. "Flat Rate" in Magento (Stores → Configuration → Sales → Delivery Methods).'];
        }

        $res = $this->http('POST', $cartPath . '/shipping-information', $headers, json_encode([
            'addressInformation' => [
                'shipping_address' => $address,
                'billing_address' => $address,
                'shipping_carrier_code' => $method['carrier_code'],
                'shipping_method_code' => $method['method_code'],
            ],
        ]));
        if ($res['code'] !== 200) {
            return ['ok' => false, 'message' => 'Magento push failed at "shipping information" ' . self::describe($res)];
        }

        // 4) Payment method — "Check / Money order" ho to woh, warna jo pehla available ho
        $paymentMethods = $res['data']['payment_methods'] ?? [];
        $paymentCode = null;
        foreach ($paymentMethods as $pm) {
            if (($pm['code'] ?? '') === 'checkmo') {
                $paymentCode = 'checkmo';
                break;
            }
        }
        if ($paymentCode === null && !empty($paymentMethods[0]['code'])) {
            $paymentCode = $paymentMethods[0]['code'];
        }
        if ($paymentCode === null) {
            return ['ok' => false, 'message' => 'Magento push failed: no payment method is available. Enable "Check / Money Order" in Magento (Stores → Configuration → Sales → Payment Methods).'];
        }

        // 5) Order place karo — jawab mein order ka ID (entity_id) aata hai
        $res = $this->http('PUT', $cartPath . '/order', $headers, json_encode([
            'paymentMethod' => ['method' => $paymentCode],
        ]));
        $orderId = (is_int($res['data']) || (is_string($res['data']) && ctype_digit($res['data']))) ? (string) $res['data'] : null;
        if ($res['code'] !== 200 || !$orderId) {
            return ['ok' => false, 'message' => 'Magento push failed at "place order" ' . self::describe($res)];
        }

        return ['ok' => true, 'external_id' => $orderId];
    }

    /** PrestaShop (Webservice — data XML mein bhejna padta hai): customer → address → cart → order */
    public function pushPrestaShop(array $store, array $order, array $lines): array
    {
        if (empty($store['prestashop_store_url']) || empty($store['prestashop_api_key'])) {
            return ['ok' => false, 'message' => 'This store is not connected to PrestaShop (Store URL / API Key missing).'];
        }

        foreach ($lines as $line) {
            if ((int) ($line['product']['external_ps_product_id'] ?? 0) <= 0) {
                return ['ok' => false, 'message' => "Product \"{$line['name']}\" is not linked to a PrestaShop product. Sync products from PrestaShop first."];
            }
        }

        $api = rtrim($store['prestashop_store_url'], '/') . '/api';
        $key = $store['prestashop_api_key'];

        // Shop ki default language, currency, country aur order statuses
        $config = $this->psConfig($api, $key, ['PS_LANG_DEFAULT', 'PS_CURRENCY_DEFAULT', 'PS_COUNTRY_DEFAULT', 'PS_OS_CHEQUE', 'PS_OS_PAYMENT']);
        if (empty($config['PS_LANG_DEFAULT']) || empty($config['PS_CURRENCY_DEFAULT'])) {
            return ['ok' => false, 'message' => 'PrestaShop push failed: could not read shop settings. In PrestaShop → Advanced Parameters → Webservice, give this key GET access to "configurations", "countries", "carriers", "customers" and "orders", and POST access to "customers", "addresses", "carts", "orders" and "order_histories".'];
        }

        $countryId = $this->psFirstId($api, $key, 'countries', ['iso_code' => self::DEFAULT_COUNTRY_ISO2]) ?: (int) ($config['PS_COUNTRY_DEFAULT'] ?? 0);
        $carrierId = $this->psFirstId($api, $key, 'carriers', ['active' => '1', 'deleted' => '0']);
        if (!$countryId || !$carrierId) {
            return ['ok' => false, 'message' => 'PrestaShop push failed: no country or active carrier found in the shop.'];
        }

        // 1) Customer — same email wala pehle se ho to wohi, warna naya
        [$first, $last] = self::splitName($order['customer_name'] ?? '');
        $first = self::cleanPrestaShopName($first);
        $last = self::cleanPrestaShopName($last === '-' ? $first : $last);
        $email = self::orderEmail($order);

        $customerId = $this->psFirstId($api, $key, 'customers', ['email' => $email]);
        if (!$customerId) {
            $res = $this->psPost($api, $key, 'customer', [
                'lastname' => $last,
                'firstname' => $first,
                'email' => $email,
                'passwd' => bin2hex(random_bytes(8)),
                'active' => '1',
            ]);
            if (!$res['ok']) {
                return ['ok' => false, 'message' => 'PrestaShop push failed at "create customer": ' . $res['message']];
            }
            $customerId = $res['id'];
        }

        // 2) Address
        $res = $this->psPost($api, $key, 'address', [
            'id_customer' => $customerId,
            'id_country' => $countryId,
            'alias' => 'Finovo order ' . (int) $order['id'],
            'lastname' => $last,
            'firstname' => $first,
            'address1' => self::cleanPrestaShopAddress(self::orderAddress($order)),
            'city' => self::DEFAULT_CITY,
            'postcode' => self::DEFAULT_POSTCODE,
            'phone' => preg_replace('/[^0-9+\-\s().]/', '', self::orderPhone($order)),
        ]);
        if (!$res['ok']) {
            return ['ok' => false, 'message' => 'PrestaShop push failed at "create address": ' . $res['message']];
        }
        $addressId = $res['id'];

        // 3) Cart (saare products ke saath)
        $rowsXml = '';
        foreach ($lines as $line) {
            $rowsXml .= '<cart_row>'
                . '<id_product>' . (int) $line['product']['external_ps_product_id'] . '</id_product>'
                . '<id_product_attribute>0</id_product_attribute>'
                . '<id_address_delivery>' . $addressId . '</id_address_delivery>'
                . '<quantity>' . (int) $line['quantity'] . '</quantity>'
                . '</cart_row>';
        }
        $res = $this->psPost($api, $key, 'cart', [
            'id_currency' => $config['PS_CURRENCY_DEFAULT'],
            'id_lang' => $config['PS_LANG_DEFAULT'],
            'id_customer' => $customerId,
            'id_address_delivery' => $addressId,
            'id_address_invoice' => $addressId,
            'id_carrier' => $carrierId,
        ], '<associations><cart_rows>' . $rowsXml . '</cart_rows></associations>');
        if (!$res['ok']) {
            return ['ok' => false, 'message' => 'PrestaShop push failed at "create cart": ' . $res['message']];
        }
        $cartId = $res['id'];

        // 4) Order
        $total = self::money(self::orderTotal($order, $lines));
        $productsTotal = self::money(self::linesSubtotal($lines));
        $wantedState = self::isPaid($order)
            ? (int) ($config['PS_OS_PAYMENT'] ?? 2)
            : (int) ($config['PS_OS_CHEQUE'] ?? 1);

        $res = $this->psPost($api, $key, 'order', [
            'id_address_delivery' => $addressId,
            'id_address_invoice' => $addressId,
            'id_cart' => $cartId,
            'id_currency' => $config['PS_CURRENCY_DEFAULT'],
            'id_lang' => $config['PS_LANG_DEFAULT'],
            'id_customer' => $customerId,
            'id_carrier' => $carrierId,
            'current_state' => $wantedState,
            'module' => 'ps_checkpayment',
            'payment' => 'Finovo OMS',
            'total_paid' => $total,
            'total_paid_real' => $total,
            'total_products' => $productsTotal,
            'total_products_wt' => $productsTotal,
            'conversion_rate' => '1',
        ]);
        if (!$res['ok']) {
            return ['ok' => false, 'message' => 'PrestaShop push failed at "create order": ' . $res['message'] . ' (The "Payments by check" module must be installed and enabled.)'];
        }
        $orderId = $res['id'];

        // 5) PrestaShop total khud dobara jorta hai (shipping samait); match na ho to "Payment error"
        //    status laga deta hai. Aisa ho to hamara status set kar do (yeh fail ho to bhi order ban chuka hai)
        $created = $this->psFirstRow($api, $key, 'orders', ['id' => (string) $orderId], '[id,current_state]');
        if ((int) ($created['current_state'] ?? $wantedState) !== $wantedState) {
            $this->psPost($api, $key, 'order_history', ['id_order' => $orderId, 'id_order_state' => $wantedState]);
        }

        return ['ok' => true, 'external_id' => (string) $orderId];
    }

    // =====================================================================
    //  2) TRACKING PUSH (order platform pe "Shipped")
    // =====================================================================

    /** Shopify: order ke "fulfillment orders" ko tracking ke saath fulfill karo */
    public function pushShopifyTracking(array $store, string $shopifyOrderId, string $trackingNumber, ?string $courierName): array
    {
        if (empty($store['store_url']) || empty($store['access_token'])) {
            return ['ok' => false, 'message' => 'This store is not connected to Shopify.'];
        }

        $res = $this->http('GET', $this->shopifyUrl($store, '/orders/' . rawurlencode($shopifyOrderId) . '/fulfillment_orders.json'), $this->shopifyHeaders($store));
        if ($res['code'] !== 200) {
            $hint = in_array($res['code'], [401, 403], true) ? ' Reconnect Shopify (Stores → Edit → Connect Shopify) so Finovo gets the new "fulfillment" permission.' : '';
            return ['ok' => false, 'message' => 'Could not read the order from Shopify ' . self::describe($res) . $hint];
        }

        $open = [];
        foreach (($res['data']['fulfillment_orders'] ?? []) as $fo) {
            if (in_array($fo['status'] ?? '', ['open', 'in_progress'], true)) {
                $open[] = ['fulfillment_order_id' => $fo['id']];
            }
        }
        if (empty($open)) {
            return ['ok' => true, 'already' => true];
        }

        $res = $this->http('POST', $this->shopifyUrl($store, '/fulfillments.json'), $this->shopifyHeaders($store), json_encode([
            'fulfillment' => [
                'line_items_by_fulfillment_order' => $open,
                'tracking_info' => ['number' => $trackingNumber, 'company' => (string) ($courierName ?: 'Other')],
                'notify_customer' => true,
            ],
        ]));
        if ($res['code'] !== 201) {
            return ['ok' => false, 'message' => 'Shopify tracking push failed ' . self::describe($res)];
        }
        return ['ok' => true];
    }

    /** WooCommerce: tracking ka note (customer ko email bhi jata hai) + order "Completed" */
    public function pushWooCommerceTracking(array $store, string $wcOrderId, string $trackingNumber, ?string $courierName): array
    {
        if (empty($store['woocommerce_store_url']) || empty($store['woocommerce_consumer_key']) || empty($store['woocommerce_consumer_secret'])) {
            return ['ok' => false, 'message' => 'This store is not connected to WooCommerce.'];
        }

        $base = rtrim($store['woocommerce_store_url'], '/') . '/wp-json/wc/v3/orders/' . rawurlencode($wcOrderId);
        $auth = $store['woocommerce_consumer_key'] . ':' . $store['woocommerce_consumer_secret'];

        $note = 'Your order has been shipped' . ($courierName ? " via {$courierName}" : '') . ". Tracking number: {$trackingNumber}";
        $res = $this->http('POST', $base . '/notes', ['Content-Type: application/json'],
            json_encode(['note' => $note, 'customer_note' => true]), $auth);
        if ($res['code'] !== 201) {
            return ['ok' => false, 'message' => 'WooCommerce tracking push failed ' . self::describe($res)];
        }

        $res = $this->http('PUT', $base, ['Content-Type: application/json'], json_encode(['status' => 'completed']), $auth);
        if ($res['code'] !== 200) {
            return ['ok' => false, 'message' => 'Tracking note was added, but marking the WooCommerce order "Completed" failed ' . self::describe($res)];
        }
        return ['ok' => true];
    }

    /** BigCommerce: saare products ki ek shipment (tracking ke saath) → order "Shipped" */
    public function pushBigCommerceTracking(array $store, string $bcOrderId, string $trackingNumber, ?string $courierName): array
    {
        if (empty($store['bigcommerce_store_hash']) || empty($store['bigcommerce_access_token'])) {
            return ['ok' => false, 'message' => 'This store is not connected to BigCommerce.'];
        }

        $headers = $this->bigCommerceHeaders($store);
        $orderPath = '/orders/' . rawurlencode($bcOrderId);

        $res = $this->http('GET', $this->bigCommerceUrl($store, $orderPath . '/shipping_addresses'), $headers);
        $addressId = (int) ($res['data'][0]['id'] ?? 0);
        if ($res['code'] !== 200 || $addressId <= 0) {
            return ['ok' => false, 'message' => 'Could not read the order\'s shipping address from BigCommerce ' . self::describe($res)];
        }

        $res = $this->http('GET', $this->bigCommerceUrl($store, $orderPath . '/products'), $headers);
        $items = [];
        foreach ((is_array($res['data']) ? $res['data'] : []) as $p) {
            $left = (int) ($p['quantity'] ?? 0) - (int) ($p['quantity_shipped'] ?? 0);
            if (!empty($p['id']) && $left > 0) {
                $items[] = ['order_product_id' => (int) $p['id'], 'quantity' => $left];
            }
        }
        if ($res['code'] !== 200) {
            return ['ok' => false, 'message' => 'Could not read the order\'s products from BigCommerce ' . self::describe($res)];
        }
        if (empty($items)) {
            return ['ok' => true, 'already' => true];
        }

        $res = $this->http('POST', $this->bigCommerceUrl($store, $orderPath . '/shipments'), $headers, json_encode([
            'order_address_id' => $addressId,
            'tracking_number' => $trackingNumber,
            'comments' => $courierName ? "Courier: {$courierName}" : 'Shipped from Finovo OMS',
            'items' => $items,
        ]));
        if (!in_array($res['code'], [200, 201], true)) {
            return ['ok' => false, 'message' => 'BigCommerce tracking push failed ' . self::describe($res)];
        }
        return ['ok' => true];
    }

    /** Magento: order ki shipment (saare items) tracking ke saath */
    public function pushMagentoTracking(array $store, string $magentoOrderId, string $trackingNumber, ?string $courierName): array
    {
        if (empty($store['magento_store_url']) || empty($store['magento_access_token'])) {
            return ['ok' => false, 'message' => 'This store is not connected to Magento.'];
        }

        $url = rtrim($store['magento_store_url'], '/') . '/rest/V1/order/' . rawurlencode($magentoOrderId) . '/ship';
        $res = $this->http('POST', $url, $this->magentoHeaders($store), json_encode([
            'notify' => true,
            'tracks' => [[
                'track_number' => $trackingNumber,
                'title' => (string) ($courierName ?: 'Courier'),
                'carrier_code' => 'custom',
            ]],
        ]));
        if ($res['code'] !== 200) {
            return ['ok' => false, 'message' => 'Magento tracking push failed ' . self::describe($res)];
        }
        return ['ok' => true];
    }

    /** eBay: order ko tracking ke saath "Shipped" (shipping fulfillment) */
    public function pushEbayTracking(string $accessToken, string $ebayOrderId, string $trackingNumber, ?string $courierName, ?string $shippedAt = null): array
    {
        $headers = ['Authorization: Bearer ' . $accessToken, 'Content-Type: application/json', 'Accept: application/json'];
        $orderUrl = $this->ebayBase . '/sell/fulfillment/v1/order/' . rawurlencode($ebayOrderId);

        // eBay ko line item ID chahiye — order se le lo
        $res = $this->http('GET', $orderUrl, $headers);
        if ($res['code'] !== 200) {
            return ['ok' => false, 'message' => 'Could not read the order from eBay ' . self::describe($res)];
        }
        if (($res['data']['orderFulfillmentStatus'] ?? '') === 'FULFILLED') {
            return ['ok' => true, 'already' => true];
        }

        $payload = self::buildEbayFulfillmentPayload($res['data']['lineItems'] ?? [], $trackingNumber, $courierName, $shippedAt);
        if (empty($payload['lineItems'])) {
            return ['ok' => false, 'message' => 'eBay returned no line items for this order.'];
        }

        $res = $this->http('POST', $orderUrl . '/shipping_fulfillment', $headers, json_encode($payload));
        if (!in_array($res['code'], [200, 201], true)) {
            return ['ok' => false, 'message' => 'eBay tracking push failed ' . self::describe($res)];
        }
        return ['ok' => true];
    }

    // =====================================================================
    //  Platform URL / header helpers
    // =====================================================================

    private function shopifyUrl(array $store, string $path): string
    {
        $host = preg_replace('#^https?://#', '', rtrim($store['store_url'], '/'));
        return $this->shopifyScheme . '://' . $host . '/admin/api/' . self::SHOPIFY_API_VERSION . $path;
    }

    private function shopifyHeaders(array $store): array
    {
        return ['X-Shopify-Access-Token: ' . $store['access_token'], 'Content-Type: application/json', 'Accept: application/json'];
    }

    private function bigCommerceUrl(array $store, string $path): string
    {
        return $this->bigCommerceBase . '/stores/' . rawurlencode($store['bigcommerce_store_hash']) . '/v2' . $path;
    }

    private function bigCommerceHeaders(array $store): array
    {
        return ['X-Auth-Token: ' . $store['bigcommerce_access_token'], 'Accept: application/json', 'Content-Type: application/json'];
    }

    private function magentoHeaders(array $store): array
    {
        return ['Authorization: Bearer ' . $store['magento_access_token'], 'Accept: application/json', 'Content-Type: application/json'];
    }

    // =====================================================================
    //  PrestaShop helpers
    // =====================================================================

    /** Configuration values: ['PS_LANG_DEFAULT' => '1', ...] */
    private function psConfig(string $api, string $key, array $names): array
    {
        $url = $api . '/configurations?' . http_build_query([
            'ws_key' => $key,
            'output_format' => 'JSON',
            'display' => '[name,value]',
            'filter[name]' => '[' . implode('|', $names) . ']',
        ]);
        $res = $this->http('GET', $url, ['Accept: application/json']);

        $values = [];
        foreach (($res['data']['configurations'] ?? []) as $row) {
            if (isset($row['name']) && !isset($values[$row['name']])) {
                $values[$row['name']] = (string) ($row['value'] ?? '');
            }
        }
        return $values;
    }

    /** Filter laga ke pehla record ka ID (na mile to 0) */
    private function psFirstId(string $api, string $key, string $resource, array $filters): int
    {
        return (int) ($this->psFirstRow($api, $key, $resource, $filters)['id'] ?? 0);
    }

    /** Filter laga ke pehla record (na mile to khali array) */
    private function psFirstRow(string $api, string $key, string $resource, array $filters, string $display = '[id]'): array
    {
        $query = ['ws_key' => $key, 'output_format' => 'JSON', 'display' => $display, 'limit' => '1'];
        foreach ($filters as $field => $value) {
            $query["filter[{$field}]"] = '[' . $value . ']';
        }
        $res = $this->http('GET', $api . '/' . $resource . '?' . http_build_query($query), ['Accept: application/json']);

        $rows = $res['data'][$resource] ?? [];
        return is_array($rows[0] ?? null) ? $rows[0] : [];
    }

    /**
     * Ek resource (customer/address/cart/order/order_history) XML mein POST karo.
     * $fields → <field>value</field>;  $extraXml → associations jaisa raw XML.
     */
    private function psPost(string $api, string $key, string $resource, array $fields, string $extraXml = ''): array
    {
        $inner = '';
        foreach ($fields as $name => $value) {
            $inner .= '<' . $name . '><![CDATA[' . str_replace(']]>', ']]]]><![CDATA[>', (string) $value) . ']]></' . $name . '>';
        }
        $xml = '<?xml version="1.0" encoding="UTF-8"?><prestashop xmlns:xlink="http://www.w3.org/1999/xlink">'
            . '<' . $resource . '>' . $inner . $extraXml . '</' . $resource . '>'
            . '</prestashop>';

        $plural = $resource === 'address' ? 'addresses' : ($resource === 'order_history' ? 'order_histories' : $resource . 's');
        $res = $this->http('POST', $api . '/' . $plural . '?ws_key=' . urlencode($key), ['Content-Type: text/xml'], $xml);

        $id = 0;
        if (in_array($res['code'], [200, 201], true) && $res['raw'] !== '') {
            $prev = libxml_use_internal_errors(true);
            $doc = simplexml_load_string($res['raw'], 'SimpleXMLElement', LIBXML_NONET | LIBXML_NOCDATA);
            libxml_use_internal_errors($prev);
            if ($doc !== false && isset($doc->{$resource}->id)) {
                $id = (int) $doc->{$resource}->id;
            }
        }

        if ($id <= 0) {
            return ['ok' => false, 'message' => self::prestaShopError($res)];
        }
        return ['ok' => true, 'id' => $id];
    }

    /** PrestaShop ke XML error mein se asli message nikaalo */
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

    // =====================================================================
    //  HTTP
    // =====================================================================

    private function http(string $method, string $url, array $headers, ?string $body = null, ?string $basicAuth = null): array
    {
        // $basicAuth sirf WooCommerce deta hai ("key:secret"). http:// site pe WooCommerce Basic Auth nahi maanta,
        // wahan URL pe OAuth signature lagta hai (https pe URL waisa hi rehta hai)
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
            // Har platform ka error alag shakal mein aata hai
            $message = $data['message'] ?? $data['title'] ?? $data[0]['message'] ?? $data['errors'][0]['message'] ?? $data['details']['applicationError']['description'] ?? $data['errors'] ?? '';
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