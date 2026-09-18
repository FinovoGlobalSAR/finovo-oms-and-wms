<?php
require_once __DIR__ . '/ConnectorInterface.php';

/**
 * Custom Store Bridge se baat karne wala connector — koi bhi PHP/MySQL
 * website jismein oms-bridge package install hua ho, isse connect hota hai.
 */
class BridgeConnector implements ConnectorInterface
{
    private string $baseUrl;
    private string $apiKey;
    private string $sharedSecret;

    public function __construct(string $baseUrl, string $apiKey, string $sharedSecret)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->apiKey = $apiKey;
        $this->sharedSecret = $sharedSecret;
    }

    private function signedRequest(string $endpoint, string $method = 'GET', array $body = []): array
    {
        $timestamp = (string) time();
        $nonce = bin2hex(random_bytes(16));
        $bodyJson = !empty($body) ? json_encode($body) : '';

        $signature = hash_hmac('sha256', $timestamp . $nonce . $bodyJson, $this->sharedSecret);

        $headers = [
            'X-Bridge-Api-Key: ' . $this->apiKey,
            'X-Bridge-Timestamp: ' . $timestamp,
            'X-Bridge-Nonce: ' . $nonce,
            'X-Bridge-Signature: ' . $signature,
            'Content-Type: application/json',
        ];

        $ch = curl_init($this->baseUrl . $endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $bodyJson);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            'ok' => $httpCode >= 200 && $httpCode < 300,
            'http_code' => $httpCode,
            'data' => json_decode($response, true) ?? [],
        ];
    }

    public function testConnection(): array
    {
        $result = $this->signedRequest('/api/health.php');
        return [
            'ok' => $result['ok'],
            'message' => $result['ok'] ? 'Bridge connected successfully.' : 'Bridge connection failed (HTTP ' . $result['http_code'] . ').',
        ];
    }

    public function fetchProducts(): array
    {
        $result = $this->signedRequest('/api/products.php');
        return $result['data']['products'] ?? [];
    }

    public function fetchOrders(): array
    {
        $result = $this->signedRequest('/api/orders.php');
        return $result['data']['orders'] ?? [];
    }

    public function updateOrderStatus(string $externalOrderId, string $status): array
    {
        return $this->signedRequest('/api/status.php', 'POST', [
            'order_id' => $externalOrderId,
            'status' => $status,
        ]);
    }

    public function updateInventory(string $externalProductId, int $quantity): array
    {
        return $this->signedRequest('/api/inventory.php', 'POST', [
            'product_id' => $externalProductId,
            'quantity' => $quantity,
        ]);
    }

    public function getHealth(): string
    {
        $result = $this->testConnection();
        return $result['ok'] ? 'connected' : 'sync_error';
    }
}