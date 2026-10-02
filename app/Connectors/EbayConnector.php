<?php
/**
 * eBay API (sandbox ya production, .env ke EBAY_ENV se) — Sell Inventory (products) aur Sell Fulfillment (orders).
 * Token ab EbayAuthService deta hai ("Connect eBay" ke baad khud refresh hota hai).
 */
class EbayConnector
{
    private string $userToken;
    private string $baseUrl;

    public function __construct(string $userToken)
    {
        $this->userToken = $userToken;

        // .env mein EBAY_ENV=production likhne pe asli eBay, warna sandbox (testing)
        $this->baseUrl = strtolower((string) env('EBAY_ENV', 'sandbox')) === 'production'
            ? 'https://api.ebay.com'
            : 'https://api.sandbox.ebay.com';
    }

    private function request(string $method, string $url): array
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->userToken,
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, curlVerifySsl());
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ['code' => $httpCode, 'data' => json_decode((string) $response, true)];
    }

    public function fetchProducts(): array
    {
        $result = $this->request('GET', $this->baseUrl . '/sell/inventory/v1/inventory_item?limit=50');

        if ($result['code'] !== 200) {
            return ['ok' => false, 'message' => "eBay API error (HTTP {$result['code']}): " . json_encode($result['data'])];
        }

        return ['ok' => true, 'products' => $result['data']['inventoryItems'] ?? []];
    }

    public function fetchOrders(): array
    {
        $result = $this->request('GET', $this->baseUrl . '/sell/fulfillment/v1/order?limit=50');

        if ($result['code'] !== 200) {
            return ['ok' => false, 'message' => "eBay API error (HTTP {$result['code']}): " . json_encode($result['data'])];
        }

        return ['ok' => true, 'orders' => $result['data']['orders'] ?? []];
    }

    /**
     * Ek order ki poori detail (webhook ke baad isse lete hain).
     */
    public function fetchOrder(string $orderId): array
    {
        $result = $this->request('GET', $this->baseUrl . '/sell/fulfillment/v1/order/' . rawurlencode($orderId));

        if ($result['code'] !== 200) {
            return ['ok' => false, 'code' => $result['code'], 'message' => "eBay API error (HTTP {$result['code']}): " . json_encode($result['data'])];
        }

        return ['ok' => true, 'order' => $result['data']];
    }
}