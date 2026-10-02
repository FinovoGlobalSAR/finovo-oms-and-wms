<?php
require_once __DIR__ . '/../../core/Database.php';

/**
 * CJdropshipping API (v2).
 *
 * CJ ek supplier/fulfillment platform hai:
 *  - Products sync  → CJ ke products Finovo mein laao
 *  - Order push     → Finovo ka order CJ ko bhejo, CJ product pack karke customer ko bhejta hai
 *
 * Login: sirf API key se access token milta hai (CJ ne email/password wala login band kar diya).
 * CJ baar baar login allow nahi karta, is liye token stores table mein save karke dobara use hota hai.
 * CJ ek second mein sirf 1 request allow karta hai — throttle() khud 1 second ka gap rakhta hai.
 */
class CjDropshippingConnector
{
    private string $email;
    private string $apiKey;
    private ?int $storeId;
    private string $baseUrl = 'https://developers.cjdropshipping.com/api2.0/v1';
    private static float $lastCallAt = 0.0;

    public function __construct(string $email, string $apiKey, ?int $storeId = null)
    {
        $this->email = $email;
        $this->apiKey = $apiKey;
        $this->storeId = $storeId;
    }

    /**
     * CJ: 1 request per second. Pichli call ko 1 second nahi hua to thoda ruk jao.
     */
    private function throttle(): void
    {
        $wait = 1.1 - (microtime(true) - self::$lastCallAt);
        if ($wait > 0) {
            usleep((int) ($wait * 1000000));
        }
        self::$lastCallAt = microtime(true);
    }

    // ---------------------------------------------------------------- Login

    private function getAccessToken(): array
    {
        $db = Database::getConnection();

        // 1) Saved token abhi valid hai? (1 ghanta margin rakha hai)
        if ($this->storeId) {
            $stmt = $db->prepare("SELECT cj_access_token, cj_token_expires_at FROM stores WHERE id = ?");
            $stmt->execute([$this->storeId]);
            $saved = $stmt->fetch();

            if (!empty($saved['cj_access_token']) && !empty($saved['cj_token_expires_at'])
                && strtotime($saved['cj_token_expires_at']) > time() + 3600) {
                return ['ok' => true, 'token' => $saved['cj_access_token']];
            }
        }

        // 2) Naya login
        $this->throttle();
        $ch = curl_init($this->baseUrl . '/authentication/getAccessToken');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'apiKey' => $this->apiKey,
        ]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, curlVerifySsl());
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = json_decode((string) $response, true);

        if ($httpCode !== 200 || empty($data['data']['accessToken'])) {
            $message = $data['message'] ?? $response;
            return ['ok' => false, 'message' => "CJ login failed (HTTP {$httpCode}): " . $message];
        }

        $token = $data['data']['accessToken'];
        $expiresAt = !empty($data['data']['accessTokenExpiryDate'])
            ? date('Y-m-d H:i:s', strtotime($data['data']['accessTokenExpiryDate']))
            : date('Y-m-d H:i:s', time() + 10 * 86400);

        if ($this->storeId) {
            $db->prepare("UPDATE stores SET cj_access_token = ?, cj_token_expires_at = ? WHERE id = ?")
               ->execute([$token, $expiresAt, $this->storeId]);
        }

        return ['ok' => true, 'token' => $token];
    }

    /**
     * Har CJ call isi se hoti hai. CJ ka jawab: { code, result, message, data }
     */
    private function request(string $method, string $path, ?array $body = null): array
    {
        $auth = $this->getAccessToken();
        if (!$auth['ok']) {
            return $auth;
        }

        $this->throttle();
        $ch = curl_init($this->baseUrl . $path);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'CJ-Access-Token: ' . $auth['token'],
            'Content-Type: application/json',
        ]);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, curlVerifySsl());
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            return ['ok' => false, 'message' => 'CJ connection error: ' . $curlError];
        }

        $data = json_decode($response, true);

        if ($httpCode !== 200 || !is_array($data) || (int) ($data['code'] ?? 0) !== 200 || ($data['result'] ?? false) !== true) {
            $message = is_array($data) ? ($data['message'] ?? $response) : $response;
            return ['ok' => false, 'message' => "CJ API error (HTTP {$httpCode}): " . $message];
        }

        return ['ok' => true, 'data' => $data['data'] ?? null];
    }

    // ------------------------------------------------------------- Products

    public function fetchProducts(): array
    {
        // Naya endpoint (listV2)
        $result = $this->request('GET', '/product/listV2?page=1&size=50');
        if ($result['ok']) {
            $products = [];
            foreach (($result['data']['content'] ?? []) as $block) {
                foreach (($block['productList'] ?? []) as $p) {
                    $products[] = [
                        'pid' => $p['id'] ?? '',
                        'productNameEn' => $p['nameEn'] ?? '',
                        'sellPrice' => $p['sellPrice'] ?? 0,
                    ];
                }
            }
            return ['ok' => true, 'products' => $products];
        }

        // Purana endpoint (backup)
        $old = $this->request('GET', '/product/list?pageNum=1&pageSize=50');
        if (!$old['ok']) {
            return $result;
        }

        return ['ok' => true, 'products' => $old['data']['list'] ?? []];
    }

    /**
     * Ek CJ product ke saare variants (har variant ka apna vid hota hai — order isi se banta hai).
     */
    public function getProductVariants(string $pid): array
    {
        $result = $this->request('GET', '/product/query?pid=' . urlencode($pid));
        if (!$result['ok']) {
            return $result;
        }

        $variants = [];
        foreach (($result['data']['variants'] ?? []) as $v) {
            if (empty($v['vid'])) continue;
            $variants[] = [
                'vid' => (string) $v['vid'],
                'sku' => $v['variantSku'] ?? '',
                'name' => $v['variantNameEn'] ?? ($v['variantKey'] ?? $v['variantSku'] ?? $v['vid']),
                'price' => (float) ($v['variantSellPrice'] ?? 0),
            ];
        }

        return ['ok' => true, 'variants' => $variants];
    }

    // ---------------------------------------------------------- Fulfillment

    /**
     * Shipping ke options (kaunsi courier, kitne din, kitna kharcha).
     * $products = [['vid' => '...', 'quantity' => 2], ...]
     */
    public function freightCalculate(string $fromCountryCode, string $toCountryCode, string $zip, array $products): array
    {
        $body = [
            'startCountryCode' => strtoupper($fromCountryCode),
            'endCountryCode' => strtoupper($toCountryCode),
            'products' => $products,
        ];
        if ($zip !== '') {
            $body['zip'] = $zip;
        }

        $result = $this->request('POST', '/logistic/freightCalculate', $body);
        if (!$result['ok']) {
            return $result;
        }

        $options = [];
        foreach (($result['data'] ?? []) as $o) {
            if (empty($o['logisticName'])) continue;
            $options[] = [
                'name' => $o['logisticName'],
                'price' => (float) ($o['logisticPrice'] ?? 0),
                'days' => $o['logisticAging'] ?? '',
            ];
        }

        return ['ok' => true, 'options' => $options];
    }

    /**
     * CJ pe order banao. payType = 3 → "sirf order banao", payment CJ dashboard se hoti hai
     * (koi paisa khud se nahi katta).
     */
    public function createOrder(array $payload): array
    {
        $payload['payType'] = 3;

        $result = $this->request('POST', '/shopping/order/createOrderV2', $payload);
        if (!$result['ok']) {
            return $result;
        }

        return [
            'ok' => true,
            'cj_order_id' => (string) ($result['data']['orderId'] ?? ''),
            'status' => $result['data']['orderStatus'] ?? 'CREATED',
        ];
    }

    public function getOrderDetail(string $cjOrderId): array
    {
        $result = $this->request('GET', '/shopping/order/getOrderDetail?orderId=' . urlencode($cjOrderId));
        if (!$result['ok']) {
            return $result;
        }

        return [
            'ok' => true,
            'status' => $result['data']['orderStatus'] ?? null,
            'tracking_number' => $result['data']['trackNumber'] ?? null,
            'logistic_name' => $result['data']['logisticName'] ?? null,
        ];
    }
}