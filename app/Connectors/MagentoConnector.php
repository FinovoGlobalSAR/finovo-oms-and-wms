<?php

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
    public function fetchProducts(int $pageSize = 100): array
    {
        $result = $this->fetchAllPages('/products', ['pageSize' => $pageSize]);
        if (!$result['ok']) {
            return $result;
        }

        $products = [];
        foreach ($result['items'] as $item) {
            $type = $item['type_id'] ?? 'simple';
            if (!in_array($type, ['simple', 'virtual', 'downloadable'], true)) {
                continue;
            }

            // null = Magento is product ka stock ginta hi nahi ("Manage Stock" = No)
            $qty = 0;
            if (!empty($item['sku'])) {
                $stock = $this->request('/stockItems/' . rawurlencode($item['sku']));
                if ($stock['ok']) {
                    $managed = $stock['data']['manage_stock'] ?? true;
                    $inStock = $stock['data']['is_in_stock'] ?? true;
                    $qty = ($managed === false && $inStock) ? null : (int) ($stock['data']['qty'] ?? 0);
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
    public function fetchOrders(int $pageSize = 100): array
    {
        $result = $this->fetchAllPages('/orders', [
            'pageSize' => $pageSize,
            'sortOrders' => [['field' => 'created_at', 'direction' => 'DESC']],
        ]);
        if (!$result['ok']) {
            return $result;
        }

        return ['ok' => true, 'orders' => $result['items']];
    }

    /** searchCriteria[currentPage] = 1, 2, 3... jab tak total_count poora na ho (pehle sirf pehla page aata tha) */
    private function fetchAllPages(string $path, array $criteria, int $maxPages = 40): array
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(300);
        }

        $items = [];
        for ($page = 1; $page <= $maxPages; $page++) {
            $result = $this->request($path . '?' . http_build_query(['searchCriteria' => $criteria + ['currentPage' => $page]]));
            if (!$result['ok']) {
                if ($page === 1) {
                    return $result;
                }
                break;
            }

            $rows = $result['data']['items'] ?? [];
            foreach ($rows as $row) {
                $items[] = $row;
            }
            $total = (int) ($result['data']['total_count'] ?? 0);
            // Magento aakhri page ke baad bhi aakhri page dobara bhej deta hai — is liye total_count se roko
            if ($rows === [] || count($items) >= $total || count($rows) < (int) $criteria['pageSize']) {
                break;
            }
        }

        return ['ok' => true, 'items' => $items];
    }
}