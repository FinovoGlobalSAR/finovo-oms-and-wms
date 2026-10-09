<?php
require_once __DIR__ . '/WooCommerceAuth.php';

/**
 * Platforms se SAARA data laana — page by page.
 *
 * Pehle har sync sirf pehla page laata tha (20 orders / 50 products), is liye bade store ke
 * baaki orders aur products kabhi Finovo mein nahi aate the. Yeh class har platform ke apne
 * tareeqe se agla page maangti rehti hai jab tak data khatam na ho:
 *   - Shopify:      Link header ka rel="next" (page_info)
 *   - WooCommerce:  ?page=1,2,3... (per_page=100) — X-WP-TotalPages header tak
 *   - BigCommerce:  v3 → meta.pagination.total_pages, v2 → page=1,2,3... (khali page / 204 pe ruk jao)
 *   - PrestaShop:   limit=offset,100
 *
 * Har method yeh wapas karta hai:
 *   ['ok' => bool, 'code' => pehle page ka HTTP code, 'body' => pehle page ka jawab, 'items' => [...saare records]]
 * Pehla page hi fail ho to ok=false (sync wala code wahi purana error dikhata hai).
 * Beech ka koi page fail ho to jitna aa gaya woh wapas — 'partial' => true.
 *
 * Bahut bade store pe request lambi na chale is liye zyada se zyada MAX_PAGES pages.
 */
class PlatformFetcher
{
    public const MAX_PAGES = 40;

    /**
     * Sirf tests ke liye: function(string $url, array $headers, ?string $userpwd): array{code:int, body:string, headers:array}
     * (null = asli curl)
     */
    public static $transport = null;

    // ---------------------------------------------------------------------
    //  Asli HTTP GET
    // ---------------------------------------------------------------------

    /** @return array{code:int, body:string, headers:array<string,string>} headers ke naam chhote huruf mein */
    public static function get(string $url, array $headers = [], ?string $userpwd = null): array
    {
        if (self::$transport !== null) {
            return (self::$transport)($url, $headers, $userpwd);
        }

        $responseHeaders = [];
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, function_exists('curlVerifySsl') ? curlVerifySsl() : true);
        if ($userpwd !== null) {
            curl_setopt($ch, CURLOPT_USERPWD, $userpwd);
        }
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($ch, $line) use (&$responseHeaders) {
            $parts = explode(':', $line, 2);
            if (count($parts) === 2) {
                $responseHeaders[strtolower(trim($parts[0]))] = trim($parts[1]);
            }
            return strlen($line);
        });
        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ['code' => $code, 'body' => $body === false ? '' : (string) $body, 'headers' => $responseHeaders];
    }

    // ---------------------------------------------------------------------
    //  Shopify — Link: <https://...page_info=xyz>; rel="next"
    // ---------------------------------------------------------------------

    /**
     * $resource = 'products' ya 'orders', $query = baaki filters (jaise 'status=any').
     * Shopify page_info wale link mein sirf limit chalta hai, is liye agla URL seedha Link header se lete hain.
     */
    public static function shopify(string $shopDomain, string $accessToken, string $resource, string $query = '', string $apiVersion = '2026-07'): array
    {
        self::extendTimeLimit();
        $host = preg_replace('#^https?://#', '', rtrim($shopDomain, '/'));
        $url = 'https://' . $host . '/admin/api/' . $apiVersion . '/' . $resource . '.json?' . ltrim($query . '&limit=250', '&');
        $headers = ['X-Shopify-Access-Token: ' . $accessToken, 'Accept: application/json'];

        $items = [];
        $first = null;
        for ($page = 0; $page < self::MAX_PAGES && $url !== null; $page++) {
            $res = self::get($url, $headers);
            $first ??= $res;

            if ($res['code'] !== 200) {
                return self::finish($first, $items, $page > 0);
            }
            $data = json_decode($res['body'], true);
            foreach ($data[$resource] ?? [] as $row) {
                $items[] = $row;
            }
            $url = self::nextLink($res['headers']['link'] ?? '');
        }

        return self::finish($first, $items, false);
    }

    /** Link header se rel="next" wala URL (na ho to null) */
    public static function nextLink(string $linkHeader): ?string
    {
        foreach (explode(',', $linkHeader) as $part) {
            if (preg_match('/<([^>]+)>\s*;\s*rel="?next"?/i', trim($part), $m)) {
                return $m[1];
            }
        }
        return null;
    }

    // ---------------------------------------------------------------------
    //  WooCommerce — ?page=N&per_page=100
    // ---------------------------------------------------------------------

    /** $path jaise '/wp-json/wc/v3/orders' ; http:// site pe OAuth signature lagta hai (WooCommerceAuth) */
    public static function woo(string $baseUrl, string $consumerKey, string $consumerSecret, string $path, string $query = ''): array
    {
        self::extendTimeLimit();
        $perPage = 100;
        $items = [];
        $first = null;

        for ($page = 1; $page <= self::MAX_PAGES; $page++) {
            $url = rtrim($baseUrl, '/') . $path . '?' . ltrim($query . '&per_page=' . $perPage . '&page=' . $page, '&');
            $res = self::get(WooCommerceAuth::url('GET', $url, $consumerKey, $consumerSecret), [], $consumerKey . ':' . $consumerSecret);
            $first ??= $res;

            if ($res['code'] !== 200) {
                return self::finish($first, $items, $page > 1);
            }
            $rows = json_decode($res['body'], true);
            if (!is_array($rows) || $rows === []) {
                break;
            }
            foreach ($rows as $row) {
                $items[] = $row;
            }

            $totalPages = (int) ($res['headers']['x-wp-totalpages'] ?? 0);
            if (count($rows) < $perPage || ($totalPages > 0 && $page >= $totalPages)) {
                break;
            }
        }

        return self::finish($first, $items, false);
    }

    // ---------------------------------------------------------------------
    //  BigCommerce
    // ---------------------------------------------------------------------

    /** v3 (catalog/products): { data: [...], meta: { pagination: { total_pages } } } */
    public static function bigCommerceV3(string $storeHash, string $accessToken, string $path, string $query = '', string $base = 'https://api.bigcommerce.com'): array
    {
        self::extendTimeLimit();
        $headers = ['X-Auth-Token: ' . $accessToken, 'Accept: application/json'];
        $items = [];
        $first = null;

        for ($page = 1; $page <= self::MAX_PAGES; $page++) {
            $url = rtrim($base, '/') . '/stores/' . $storeHash . $path . '?' . ltrim($query . '&limit=250&page=' . $page, '&');
            $res = self::get($url, $headers);
            $first ??= $res;

            if ($res['code'] !== 200) {
                return self::finish($first, $items, $page > 1);
            }
            $data = json_decode($res['body'], true);
            foreach ($data['data'] ?? [] as $row) {
                $items[] = $row;
            }
            $totalPages = (int) ($data['meta']['pagination']['total_pages'] ?? 1);
            if ($page >= $totalPages || empty($data['data'])) {
                break;
            }
        }

        return self::finish($first, $items, false);
    }

    /** v2 (orders): seedha array; khali page ya 204 = data khatam */
    public static function bigCommerceV2(string $storeHash, string $accessToken, string $path, string $query = '', string $base = 'https://api.bigcommerce.com'): array
    {
        self::extendTimeLimit();
        $headers = ['X-Auth-Token: ' . $accessToken, 'Accept: application/json'];
        $perPage = 250;
        $items = [];
        $first = null;

        for ($page = 1; $page <= self::MAX_PAGES; $page++) {
            $url = rtrim($base, '/') . '/stores/' . $storeHash . $path . '?' . ltrim($query . '&limit=' . $perPage . '&page=' . $page, '&');
            $res = self::get($url, $headers);
            $first ??= $res;

            if ($res['code'] === 204) {
                break; // is page pe kuch nahi
            }
            if ($res['code'] !== 200) {
                return self::finish($first, $items, $page > 1);
            }
            $rows = json_decode($res['body'], true);
            if (!is_array($rows) || $rows === []) {
                break;
            }
            foreach ($rows as $row) {
                $items[] = $row;
            }
            if (count($rows) < $perPage) {
                break;
            }
        }

        return self::finish($first, $items, false);
    }

    // ---------------------------------------------------------------------
    //  PrestaShop — limit=offset,count
    // ---------------------------------------------------------------------

    /** $params = baaki query (ws_key, display, sort...) ; $resource = 'products' / 'orders' */
    public static function prestaShop(string $baseUrl, string $resource, array $params): array
    {
        self::extendTimeLimit();
        $perPage = 100;
        $items = [];
        $first = null;

        for ($page = 0; $page < self::MAX_PAGES; $page++) {
            $query = http_build_query($params + ['output_format' => 'JSON']) . '&limit=' . ($page * $perPage) . ',' . $perPage;
            $res = self::get(rtrim($baseUrl, '/') . '/api/' . $resource . '?' . $query, ['Accept: application/json']);
            $first ??= $res;

            if ($res['code'] !== 200) {
                return self::finish($first, $items, $page > 0);
            }
            $data = json_decode($res['body'], true);
            // PrestaShop khali result pe [] bhejta hai (object nahi)
            $rows = is_array($data[$resource] ?? null) ? $data[$resource] : [];
            foreach ($rows as $row) {
                $items[] = $row;
            }
            if (count($rows) < $perPage) {
                break;
            }
        }

        return self::finish($first, $items, false);
    }

    // ---------------------------------------------------------------------

    private static function finish(?array $first, array $items, bool $partial): array
    {
        $first ??= ['code' => 0, 'body' => '', 'headers' => []];
        // Pehla page hi fail → ok=false (purana error message chalta rahe). 204/404 khali store hai, error nahi.
        $firstOk = in_array($first['code'], [200, 204], true);
        return [
            'ok' => $firstOk || $partial,
            'code' => $first['code'],
            'body' => $first['body'],
            'items' => $items,
            'partial' => $partial,
        ];
    }

    /** Bade store ka sync lamba chal sakta hai — PHP ka 30 second wala time limit badha do */
    private static function extendTimeLimit(): void
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(300);
        }
    }
}