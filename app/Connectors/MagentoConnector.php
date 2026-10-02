<?php
/**
 * Magento 2 REST API.
 *
 * Magento Admin mein: System → Extensions → Integrations → "Add New Integration"
 *   → API tab mein "Resource Access: All" → Save → "Activate" → "Access Token" copy karo.
 *
 * Note (Magento 2.4.4+): Integration token ko "Bearer" ki tarah use karne ke liye
 * Magento admin mein ye setting ON karni padti hai:
 *   Stores → Configuration → Services → OAuth → Consumer Settings →
 *   "Allow OAuth Access Tokens to be used as standalone Bearer tokens" = Yes
 */
class MagentoConnector
{
    private string $baseUrl;
    private string $accessToken;

    public function __construct(string $storeUrl, string $accessToken)
    {
        $this->baseUrl = rtrim($storeUrl, '/') . '/rest/V1';
        $this->accessToken = $accessToken;
    }

    private function request(string $path): array
    {
        $ch = curl_init($this->baseUrl . $path);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->accessToken,
            'Accept: application/json',
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, curlVerifySsl());
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            return ['ok' => false, 'code' => 0, 'message' => 'Magento connection error: ' . $curlError];
        }

        $data = json_decode((string) $response, true);

        if ($httpCode !== 200) {
            $message = is_array($data) ? ($data['message'] ?? $response) : $response;
            if ($httpCode === 401) {
                $message .= ' (Check the Access Token, and in Magento enable "Allow OAuth Access Tokens to be used as standalone Bearer tokens".)';
            }
            return ['ok' => false, 'code' => $httpCode, 'message' => "Magento API error (HTTP {$httpCode}): " . $message];
        }

        return ['ok' => true, 'code' => 200, 'data' => $data];
    }

    /**
     * Products (sirf simple/virtual/downloadable — configurable "parent" products ka stock/price nahi hota).
     * Har product ke saath uska stock (qty) bhi.
     */
    public function fetchProducts(int $limit = 50): array
    {
        $result = $this->request('/products?' . http_build_query([
            'searchCriteria' => ['pageSize' => $limit, 'currentPage' => 1],
        ]));
        if (!$result['ok']) {
            return $result;
        }

        $products = [];
        foreach (($result['data']['items'] ?? []) as $item) {
            $type = $item['type_id'] ?? 'simple';
            if (!in_array($type, ['simple', 'virtual', 'downloadable'], true)) {
                continue;
            }

            $qty = 0;
            if (!empty($item['sku'])) {
                $stock = $this->request('/stockItems/' . rawurlencode($item['sku']));
                if ($stock['ok']) {
                    $qty = (int) ($stock['data']['qty'] ?? 0);
                }
            }

            $products[] = [
                'id' => (string) ($item['id'] ?? ''),
                'sku' => $item['sku'] ?? null,
                'name' => $item['name'] ?? 'Unknown product',
                'price' => (float) ($item['price'] ?? 0),
                'qty' => $qty,
            ];
        }

        return ['ok' => true, 'products' => $products];
    }

    /**
     * Naye orders (sab se naye pehle).
     */
    public function fetchOrders(int $limit = 20): array
    {
        $result = $this->request('/orders?' . http_build_query([
            'searchCriteria' => [
                'pageSize' => $limit,
                'currentPage' => 1,
                'sortOrders' => [['field' => 'created_at', 'direction' => 'DESC']],
            ],
        ]));
        if (!$result['ok']) {
            return $result;
        }

        return ['ok' => true, 'orders' => $result['data']['items'] ?? []];
    }
}