<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/SignedToken.php';
require_once __DIR__ . '/../Models/Store.php';

class ShopifyConnectController extends Controller
{
    private Store $storeModel;

    // *_fulfillment_orders: order ko tracking number ke saath "Fulfilled" karne ke liye ("Send tracking to Shopify").
    // Purane connected stores ko ek baar dobara "Connect Shopify" karna padega taake yeh nayi permission mile.
    private const SCOPES = 'read_products,write_products,read_orders,write_orders,read_inventory,write_inventory,read_customers,write_customers,'
        . 'read_merchant_managed_fulfillment_orders,write_merchant_managed_fulfillment_orders,read_assigned_fulfillment_orders,write_assigned_fulfillment_orders';

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

        // state = Finovo ka signed token (store + shop, 1 ghanta) + ek nonce jo isi browser ke session mein bhi save hai.
        // Pehle state sirf "random.storeId" tha aur kahin save nahi hota tha — koi bhi apni Shopify shop
        // se "state=xyz.5" bhej kar store 5 ka connection apni shop se badal sakta tha.
        $nonce = bin2hex(random_bytes(16));
        $_SESSION['shopify_oauth_nonce'] = $nonce;
        $state = SignedToken::make(['purpose' => 'shopify_connect', 'store_id' => $storeId, 'shop' => $shopUrl, 'nonce' => $nonce], 3600);

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

        // State: Finovo ka token ho, isi browser ka nonce ho, aur shop wahi ho jo Connect karte waqt di thi
        $token = SignedToken::verify($state, 'shopify_connect');
        $sessionNonce = (string) ($_SESSION['shopify_oauth_nonce'] ?? '');
        unset($_SESSION['shopify_oauth_nonce']);   // ek dafa hi chalta hai

        if ($token === null || $sessionNonce === '' || !hash_equals($sessionNonce, (string) ($token['nonce'] ?? ''))) {
            http_response_code(403);
            echo "This connect link is invalid or has expired. Please click \"Connect Shopify\" again in Finovo (in the same browser).";
            return;
        }
        if (!preg_match('/^[a-z0-9][a-z0-9-]*\.myshopify\.com$/', $shop) || strcasecmp($shop, (string) $token['shop']) !== 0) {
            http_response_code(403);
            echo "The Shopify store does not match the one you started connecting.";
            return;
        }

        $storeId = (int) $token['store_id'];
        if ($storeId <= 0 || !$this->storeModel->find($storeId)) {
            echo "Store not found.";
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
        // .env ke APP_URL se (config/app.php → appUrl())
        return appUrl();
    }
}