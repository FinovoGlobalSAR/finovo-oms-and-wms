<?php
/**
 * Wix Stores REST API — API key based, koi OAuth flow nahi chahiye. Har
 * request mein wix-site-id header dena zaroori hai (API key kisi ek site
 * se bound nahi hota, isliye target site batana padta hai).
 */
class WixConnector
{
    private string $apiKey;
    private string $siteId;

    public function __construct(string $apiKey, string $siteId)
    {
        $this->apiKey = $apiKey;
        $this->siteId = $siteId;
    }

    private function request(string $method, string $url, ?array $body = null): array
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: ' . $this->apiKey,
            'wix-site-id: ' . $this->siteId,
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);

        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ['code' => $httpCode, 'data' => json_decode($response, true)];
    }

    public function fetchProducts(): array
    {
        $result = $this->request('POST', 'https://www.wixapis.com/stores/v3/products/search', [
            'search' => ['cursorPaging' => ['limit' => 50]],
        ]);

        if ($result['code'] !== 200) {
            return ['ok' => false, 'message' => "Wix API error (HTTP {$result['code']}): " . json_encode($result['data'])];
        }

        return ['ok' => true, 'products' => $result['data']['products'] ?? []];
    }

    public function fetchOrders(): array
    {
        $result = $this->request('POST', 'https://www.wixapis.com/ecom/v1/orders/search', [
            'search' => ['cursorPaging' => ['limit' => 20]],
        ]);

        if ($result['code'] !== 200) {
            return ['ok' => false, 'message' => "Wix API error (HTTP {$result['code']}): " . json_encode($result['data'])];
        }

        return ['ok' => true, 'orders' => $result['data']['orders'] ?? []];
    }
}