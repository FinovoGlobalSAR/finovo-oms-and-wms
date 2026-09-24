<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../Models/Store.php';

class ShopifyConnectController extends Controller
{
    private Store $storeModel;

    private const SCOPES = 'read_products,write_products,read_orders,write_orders,read_inventory,write_inventory,read_customers,write_customers';

    public function __construct()
    {
        parent::__construct();
        $this->storeModel = new Store();
    }

    public function connect(): void
    {
        $this->requireRole(['admin', 'manager']);

        $storeId = (int) ($_POST['store_id'] ?? $_GET['store_id'] ?? 0);
        $shopUrl = trim($_POST['shop_url'] ?? $_GET['shop_url'] ?? '');

        if ($storeId <= 0 || $shopUrl === '') {
            $this->redirect('/stores?error=' . urlencode('Please enter your Shopify store URL.'));
            return;
        }

        if (!$this->storeModel->find($storeId)) {
            $this->redirect('/stores?error=' . urlencode('Store not found.'));
            return;
        }

        $shopUrl = preg_replace('#^https?://#', '', rtrim($shopUrl, '/'));
        if (!str_ends_with($shopUrl, '.myshopify.com')) {
            $shopUrl .= '.myshopify.com';
        }

        $state = bin2hex(random_bytes(16)) . '.' . $storeId;

        $redirectUri = $this->getBaseUrl() . '/shopify-connect/callback';

        $params = http_build_query([
            'client_id'    => self::clientId(),
            'scope'        => self::SCOPES,
            'redirect_uri' => $redirectUri,
            'state'        => $state,
        ]);

        $authorizeUrl = 'https://' . $shopUrl . '/admin/oauth/authorize?' . $params;

        header('Location: ' . $authorizeUrl);
        exit;
    }

    public function callback(): void
    {
        $shop = $_GET['shop'] ?? '';
        $code = $_GET['code'] ?? '';
        $state = $_GET['state'] ?? '';
        $hmac = $_GET['hmac'] ?? '';

        if ($shop === '' || $code === '' || $state === '') {
            echo "Invalid callback — missing parameters.";
            return;
        }

        // HMAC verify karo — confirm karo ye request genuinely Shopify se aayi hai
        if (!$this->verifyHmac($_GET, $hmac)) {
            http_response_code(401);
            echo "Invalid signature.";
            return;
        }

        // State se store_id nikalo
        $parts = explode('.', $state);
        $storeId = (int) ($parts[1] ?? 0);

        if ($storeId <= 0) {
            echo "Invalid state — could not determine store.";
            return;
        }

        $tokenUrl = 'https://' . $shop . '/admin/oauth/access_token';

        $ch = curl_init($tokenUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'client_id'     => self::clientId(),
            'client_secret' => self::clientSecret(),
            'code'          => $code,
        ]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            echo "Failed to obtain access token from Shopify (HTTP {$httpCode}).";
            return;
        }

        $data = json_decode($response, true);
        $accessToken = $data['access_token'] ?? null;

        if (!$accessToken) {
            echo "Shopify did not return an access token.";
            return;
        }

        $this->storeModel->updateCredentials($storeId, $shop, $accessToken);

        $this->redirect('/stores?shopify_connected=1');
    }

    private function verifyHmac(array $params, string $hmac): bool
    {
        unset($params['hmac'], $params['signature']);
        ksort($params);
        $computedString = http_build_query($params);
        $computed = hash_hmac('sha256', $computedString, self::clientSecret());
        return hash_equals($computed, $hmac);
    }

    private static function clientId(): string
    {
        return $_ENV['SHOPIFY_CLIENT_ID'] ?? getenv('SHOPIFY_CLIENT_ID') ?: '';
    }

    private static function clientSecret(): string
    {
        return $_ENV['SHOPIFY_CLIENT_SECRET'] ?? getenv('SHOPIFY_CLIENT_SECRET') ?: '';
    }

    private function getBaseUrl(): string
    {
        if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
            $scheme = $_SERVER['HTTP_X_FORWARDED_PROTO'];
        } else {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        }
        return $scheme . '://' . $_SERVER['HTTP_HOST'];
    }
}