<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../Models/Store.php';

class BigCommerceConnectController extends Controller
{
    private Store $storeModel;

    public function __construct()
    {
        parent::__construct();
        // NOTE: constructor mein requireRole() NAHI hai — kyunki authCallback()
        // BigCommerce ki taraf se seedha aata hai, koi login session nahi hota
        // (bilkul WooCommerce callback ki tarah).
        $this->storeModel = new Store();
    }

    private function clientId(): string
    {
        return $_ENV['BIGCOMMERCE_CLIENT_ID'] ?? '';
    }

    private function clientSecret(): string
    {
        return $_ENV['BIGCOMMERCE_CLIENT_SECRET'] ?? '';
    }

    private function getBaseUrl(): string
    {
        // .env ke APP_URL se (config/app.php → appUrl())
        return appUrl();
    }

    /**
     * Auth Callback — BigCommerce install/authorize hone par yahan redirect
     * karta hai: ?code=...&scope=...&context=stores/{store_hash}
     */
    public function authCallback(): void
    {
        $code = $_GET['code'] ?? '';
        $scope = $_GET['scope'] ?? '';
        $context = $_GET['context'] ?? '';

        if ($code === '' || $context === '') {
            http_response_code(400);
            echo "Missing code or context from BigCommerce.";
            return;
        }

        // context format: "stores/{store_hash}"
        $storeHash = str_replace('stores/', '', $context);

        $tokenUrl = 'https://login.bigcommerce.com/oauth2/token';
        $payload = json_encode([
            'client_id' => $this->clientId(),
            'client_secret' => $this->clientSecret(),
            'redirect_uri' => $this->getBaseUrl() . '/bigcommerce-connect/callback',
            'grant_type' => 'authorization_code',
            'code' => $code,
            'scope' => $scope,
            'context' => $context,
        ]);

        $ch = curl_init($tokenUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json',
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            http_response_code(500);
            echo "Token exchange failed (HTTP {$httpCode}): " . htmlspecialchars($response);
            return;
        }

        $data = json_decode($response, true);
        $accessToken = $data['access_token'] ?? '';

        if ($accessToken === '') {
            http_response_code(500);
            echo "No access_token received from BigCommerce.";
            return;
        }

        // Kis Finovo store se link karein?
        //   1) yeh store_hash pehle se kisi store mein save hai → wahi (app dobara install hui)
        //   2) Finovo mein login Admin / Manager ka "current" store → us se link
        //   3) warna KUCH nahi — pehle "pehla store le lo" hota tha, is liye koi bhi bahar wala apni
        //      BigCommerce shop pe app install kar ke store #1 ki keys badal sakta tha
        $db = Database::getConnection();
        $existing = $db->prepare("SELECT id FROM stores WHERE bigcommerce_store_hash = ?");
        $existing->execute([$storeHash]);
        $matchedStore = $existing->fetch();

        $role = strtolower(trim($_SESSION['user']['role'] ?? ''));
        $loggedInManager = !empty($_SESSION['user']['id']) && in_array($role, ['admin', 'manager', 'company admin'], true);

        if ($matchedStore) {
            $this->storeModel->updateBigCommerceCredentials((int) $matchedStore['id'], $storeHash, $accessToken);
        } elseif ($loggedInManager && !empty($_SESSION['current_store_id']) && $this->storeModel->find((int) $_SESSION['current_store_id'])) {
            $this->storeModel->updateBigCommerceCredentials((int) $_SESSION['current_store_id'], $storeHash, $accessToken);
        } else {
            http_response_code(403);
            echo "<h2>Could not link this BigCommerce store.</h2><p>Log in to Finovo as an Admin or Manager, click \"Manage\" on the store you want to connect, then install the app again from BigCommerce in the same browser.</p>";
            return;
        }

        echo "<h2>BigCommerce connected successfully!</h2><p>Store Hash: " . htmlspecialchars($storeHash) . "</p><p>You can close this tab and return to Finovo.</p>";
    }

    /**
     * Load Callback — jab merchant apne BigCommerce control panel se app
     * "open" karta hai, BigCommerce yahan signed_payload_jwt ke sath redirect
     * karta hai. Hum yahan bas Finovo Stores page pe bhej dete hain.
     */
    public function loadCallback(): void
    {
        if ($this->verifiedStoreHash() === null) {
            http_response_code(403);
            echo "Invalid request.";
            return;
        }
        $this->redirect('/stores');
    }

    /**
     * Uninstall Callback — jab merchant app uninstall karta hai, BigCommerce
     * yahan notify karta hai. Hum us store ke credentials clear kar dete hain.
     */
    public function uninstallCallback(): void
    {
        // Store hash sirf BigCommerce ke SIGNED payload se (pehle ?context= se leta tha — store hash
        // public hota hai, koi bhi kisi store ka connection hata sakta tha)
        $storeHash = $this->verifiedStoreHash();
        if ($storeHash === null) {
            http_response_code(403);
            echo json_encode(['error' => 'Invalid signed payload.']);
            return;
        }

        if ($storeHash !== '') {
            $db = Database::getConnection();
            $stmt = $db->prepare("UPDATE stores SET bigcommerce_store_hash = NULL, bigcommerce_access_token = NULL WHERE bigcommerce_store_hash = ?");
            $stmt->execute([$storeHash]);
        }

        http_response_code(200);
        echo json_encode(['status' => 'uninstalled']);
    }

    /**
     * BigCommerce load / uninstall callbacks ?signed_payload_jwt= bhejta hai (JWT, HS256, app ke
     * client secret se sign). Sahi ho to store hash ("stores/abc123" → "abc123"), warna null.
     */
    private function verifiedStoreHash(): ?string
    {
        $jwt = (string) ($_GET['signed_payload_jwt'] ?? '');
        $secret = $this->clientSecret();
        $parts = explode('.', $jwt);
        if ($secret === '' || count($parts) !== 3) {
            return null;
        }

        $b64 = fn(string $raw) => rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
        $expected = $b64(hash_hmac('sha256', $parts[0] . '.' . $parts[1], $secret, true));
        if (!hash_equals($expected, $parts[2])) {
            return null;
        }

        $payload = json_decode((string) base64_decode(strtr($parts[1], '-_', '+/')), true);
        if (!is_array($payload) || (isset($payload['exp']) && (int) $payload['exp'] < time() - 300)) {
            return null;
        }
        $hash = str_replace('stores/', '', (string) ($payload['sub'] ?? ''));
        return $hash !== '' ? $hash : null;
    }
}