<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../Models/Store.php';
require_once __DIR__ . '/../Connectors/EbayConnector.php';
require_once __DIR__ . '/../Services/EbayAuthService.php';
require_once __DIR__ . '/../Services/EbayOrderImporter.php';
require_once __DIR__ . '/../Services/AuditLogService.php';

/**
 * eBay real-time order notifications (Notification API, topic ORDER_CONFIRMATION).
 *
 * eBay 2 tarah se is URL ko call karta hai:
 *  1) GET  /webhooks/ebay?challenge_code=XYZ  → eBay check karta hai ke URL humara hai
 *  2) POST /webhooks/ebay                      → naya order aaya (x-ebay-signature header ke saath)
 *
 * .env mein chahiye:
 *   EBAY_NOTIFICATION_ENDPOINT=https://YOUR-DOMAIN/webhooks/ebay   (bilkul wahi jo eBay pe register hai)
 *   EBAY_NOTIFICATION_VERIFICATION_TOKEN=32-80 characters (a-z A-Z 0-9 _ -)
 *
 * Login nahi hota (eBay ka server call karta hai) — isliye requireLogin/requireRole nahi.
 */
class EbayWebhookController extends Controller
{
    private Store $storeModel;

    public function __construct()
    {
        parent::__construct();
        $this->storeModel = new Store();
    }

    public function handle(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
            $this->challenge();
            return;
        }

        $this->notification();
    }

    // ---------- 1) Endpoint verification ----------
    private function challenge(): void
    {
        $challengeCode = (string) ($_GET['challenge_code'] ?? '');
        $verificationToken = (string) env('EBAY_NOTIFICATION_VERIFICATION_TOKEN', '');
        $endpoint = (string) env('EBAY_NOTIFICATION_ENDPOINT', '');

        if ($challengeCode === '' || $verificationToken === '' || $endpoint === '') {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Missing challenge_code or eBay notification settings.']);
            return;
        }

        http_response_code(200);
        header('Content-Type: application/json');
        echo json_encode([
            'challengeResponse' => hash('sha256', $challengeCode . $verificationToken . $endpoint),
        ]);
    }

    // ---------- 2) Order notification ----------
    private function notification(): void
    {
        $rawBody = (string) file_get_contents('php://input');
        $message = json_decode($rawBody, true);
        $signatureHeader = (string) ($_SERVER['HTTP_X_EBAY_SIGNATURE'] ?? '');

        if (!is_array($message)) {
            http_response_code(400);
            return;
        }

        if (!$this->verifySignature($rawBody, $message, $signatureHeader)) {
            // 412 = eBay ko batata hai signature galat hai
            http_response_code(412);
            return;
        }

        $topic = (string) ($message['metadata']['topic'] ?? '');
        if ($topic !== 'ORDER_CONFIRMATION') {
            // Koi aur topic — bas "mil gaya" bol do
            http_response_code(204);
            return;
        }

        $orderId = $this->findOrderId($message['notification']['data'] ?? []);
        if ($orderId === null) {
            error_log('eBay webhook: ORDER_CONFIRMATION without orderId: ' . substr($rawBody, 0, 500));
            http_response_code(204);
            return;
        }

        // Kaunsa store? Har eBay-connected store ke token se order mangao — jiska 200 aaye, order usi ka.
        foreach ($this->storeModel->allEbayConnected() as $store) {
            $token = EbayAuthService::getValidAccessToken($store);
            if ($token === null) continue;

            $result = (new EbayConnector($token))->fetchOrder($orderId);
            if (!$result['ok']) continue;

            // Duplicate notification (eBay retry) → dobara process mat karo
            if (!$this->recordWebhookEvent((int) $store['id'], $orderId)) {
                http_response_code(204);
                return;
            }

            $imported = (new EbayOrderImporter())->importOrders($store, [$result['order']]);

            (new AuditLogService())->log(
                (int) $store['id'],
                'ebay_webhook_order',
                'order',
                $orderId,
                $imported > 0 ? 'eBay order imported in real time' : 'eBay order received but not imported (duplicate / unmatched product / no stock)'
            );

            $this->storeModel->markHealthy((int) $store['id']);
            http_response_code(204);
            return;
        }

        error_log("eBay webhook: order {$orderId} did not match any connected store.");
        http_response_code(204);
    }

    /**
     * eBay ka x-ebay-signature header check karo (eBay ke official SDK wala tareeqa).
     * Header = base64( {"alg":"ecdsa","kid":"...","signature":"...","digest":"SHA1"} )
     */
    private function verifySignature(string $rawBody, array $message, string $signatureHeader): bool
    {
        if ($signatureHeader === '') {
            return false;
        }

        $header = json_decode((string) base64_decode($signatureHeader, true), true);
        if (!is_array($header) || empty($header['kid']) || empty($header['signature'])) {
            return false;
        }

        $publicKey = $this->getPublicKey((string) $header['kid']);
        if ($publicKey === null) {
            return false;
        }

        $signature = base64_decode((string) $header['signature'], true);
        if ($signature === false) {
            return false;
        }

        // eBay SDK json_encode karke check karta hai; hum raw body bhi try karte hain
        $candidates = array_unique([
            $rawBody,
            json_encode($message),
            json_encode($message, JSON_UNESCAPED_SLASHES),
            json_encode($message, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ]);

        foreach ($candidates as $data) {
            if ($data !== false && openssl_verify($data, $signature, $publicKey, OPENSSL_ALGO_SHA1) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * eBay se public key lao (1 ghante ke liye cache).
     */
    private function getPublicKey(string $kid): ?string
    {
        if (!preg_match('/^[A-Za-z0-9_\-]+$/', $kid)) {
            return null;
        }

        $cacheFile = sys_get_temp_dir() . '/finovo_ebay_pubkey_' . md5($kid) . '.pem';
        if (is_readable($cacheFile) && filemtime($cacheFile) > time() - 3600) {
            return (string) file_get_contents($cacheFile);
        }

        $appToken = EbayAuthService::getApplicationToken();
        if ($appToken === null) {
            return null;
        }

        $ch = curl_init(EbayAuthService::apiBase() . '/commerce/notification/v1/public_key/' . $kid);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $appToken,
            'Accept: application/json',
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, curlVerifySsl());
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = json_decode((string) $response, true);
        if ($httpCode !== 200 || empty($data['key'])) {
            error_log("eBay public key fetch failed (HTTP {$httpCode})");
            return null;
        }

        $pem = self::formatPem((string) $data['key']);
        @file_put_contents($cacheFile, $pem);

        return $pem;
    }

    /**
     * eBay key ek hi line mein deta hai — usko sahi PEM format mein badlo.
     */
    public static function formatPem(string $key): string
    {
        $body = str_replace(['-----BEGIN PUBLIC KEY-----', '-----END PUBLIC KEY-----', "\r", "\n", ' '], '', $key);
        return "-----BEGIN PUBLIC KEY-----\n" . chunk_split($body, 64, "\n") . "-----END PUBLIC KEY-----\n";
    }

    /**
     * Notification ke "data" mein kahin bhi "orderId" dhoondo.
     */
    private function findOrderId($data): ?string
    {
        if (!is_array($data)) {
            return null;
        }

        foreach ($data as $key => $value) {
            if ($key === 'orderId' && is_scalar($value) && (string) $value !== '') {
                return (string) $value;
            }
            if (is_array($value)) {
                $found = $this->findOrderId($value);
                if ($found !== null) {
                    return $found;
                }
            }
        }

        return null;
    }

    private function recordWebhookEvent(int $storeId, string $orderId): bool
    {
        try {
            Database::getConnection()
                ->prepare("INSERT INTO webhook_events (store_id, source, external_id, event_type) VALUES (?, 'ebay', ?, 'ORDER_CONFIRMATION')")
                ->execute([$storeId, $orderId]);
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
}