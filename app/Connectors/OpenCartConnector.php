<?php
/**
 * OpenCart ka REST API login-token based hai — pehle login karke ek token
 * milta hai, phir har request mein wo token bhejna padta hai (query param
 * ya header ke through). Ye class ye poora flow handle karta hai.
 */
class OpenCartConnector
{
    private string $baseUrl;
    private string $apiUsername;
    private string $apiKey;
    private ?string $token = null;

    public function __construct(string $baseUrl, string $apiUsername, string $apiKey)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->apiUsername = $apiUsername;
        $this->apiKey = $apiKey;
    }

    /**
     * Login karke token le aata hai. Fail hone par null return karta hai.
     */
    public function login(): ?string
    {
        $url = $this->baseUrl . '/index.php?route=api/login';

        $payload = json_encode([
            'username' => $this->apiUsername,
            'key' => $this->apiKey,
        ]);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            return null;
        }

        $data = json_decode($response, true);
        $this->token = $data['token'] ?? null;

        return $this->token;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    /**
     * Login ke baad products list laata hai.
     */
    public function fetchProducts(): array
    {
        if (!$this->token) {
            return [];
        }

        $url = $this->baseUrl . '/index.php?route=api/product&api_token=' . urlencode($this->token);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            return [];
        }

        $data = json_decode($response, true);
        return $data['data'] ?? [];
    }
}