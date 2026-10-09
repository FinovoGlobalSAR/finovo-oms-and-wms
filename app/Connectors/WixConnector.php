<?php

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

    /** Saare products — Wix ek page mein 100 tak deta hai, agla page "cursor" se */
    public function fetchProducts(): array
    {
        return $this->searchAll('https://www.wixapis.com/stores/v3/products/search', 'products');
    }

    /** Saare orders (pehle sirf 20 aate the) */
    public function fetchOrders(): array
    {
        return $this->searchAll('https://www.wixapis.com/ecom/v1/orders/search', 'orders');
    }

    /** Wix search API: pagingMetadata.cursors.next mile to agla page maango */
    private function searchAll(string $url, string $key, int $maxPages = 40): array
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(300);
        }

        $items = [];
        $cursor = null;
        for ($page = 0; $page < $maxPages; $page++) {
            $paging = ['limit' => 100];
            if ($cursor !== null) {
                $paging['cursor'] = $cursor;
            }
            $result = $this->request('POST', $url, ['search' => ['cursorPaging' => $paging]]);

            if ($result['code'] !== 200) {
                if ($page === 0) {
                    return ['ok' => false, 'message' => "Wix API error (HTTP {$result['code']}): " . json_encode($result['data'])];
                }
                break; // jitna aa gaya woh rakho
            }

            foreach ($result['data'][$key] ?? [] as $row) {
                $items[] = $row;
            }
            $cursor = $result['data']['pagingMetadata']['cursors']['next'] ?? null;
            if (empty($cursor) || empty($result['data'][$key])) {
                break;
            }
        }

        return ['ok' => true, $key => $items];
    }
}