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
        $isHttps = (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
            || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        $scheme = $isHttps ? 'https' : 'http';
        return $scheme . '://' . $_SERVER['HTTP_HOST'];
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

        // Kis Finovo store se link karein? Agar yeh store_hash pehle se kisi
        // store mein save hai to wahi update karo, warna abhi jo store session
        // mein "current" hai usse link kar do (single-tenant testing ke liye).
        $db = Database::getConnection();
        $existing = $db->prepare("SELECT id FROM stores WHERE bigcommerce_store_hash = ?");
        $existing->execute([$storeHash]);
        $matchedStore = $existing->fetch();

        if ($matchedStore) {
            $this->storeModel->updateBigCommerceCredentials((int) $matchedStore['id'], $storeHash, $accessToken);
        } elseif (!empty($_SESSION['current_store_id'])) {
            $this->storeModel->updateBigCommerceCredentials((int) $_SESSION['current_store_id'], $storeHash, $accessToken);
        } else {
            // Koi bhi store linked nahi mila — pehla store le lo (fallback)
            $first = $db->query("SELECT id FROM stores ORDER BY id ASC LIMIT 1")->fetch();
            if ($first) {
                $this->storeModel->updateBigCommerceCredentials((int) $first['id'], $storeHash, $accessToken);
            }
        }

        echo "<h2>BigCommerce connected successfully!</h2><p>Store Hash: {$storeHash}</p><p>You can close this tab and return to Finovo.</p>";
    }

    /**
     * Load Callback — jab merchant apne BigCommerce control panel se app
     * "open" karta hai, BigCommerce yahan signed_payload_jwt ke sath redirect
     * karta hai. Hum yahan bas Finovo Stores page pe bhej dete hain.
     */
    public function loadCallback(): void
    {
        $this->redirect('/stores');
    }

    /**
     * Uninstall Callback — jab merchant app uninstall karta hai, BigCommerce
     * yahan notify karta hai. Hum us store ke credentials clear kar dete hain.
     */
    public function uninstallCallback(): void
    {
        $context = $_GET['context'] ?? '';
        $storeHash = str_replace('stores/', '', $context);

        if ($storeHash !== '') {
            $db = Database::getConnection();
            $stmt = $db->prepare("UPDATE stores SET bigcommerce_store_hash = NULL, bigcommerce_access_token = NULL WHERE bigcommerce_store_hash = ?");
            $stmt->execute([$storeHash]);
        }

        http_response_code(200);
        echo json_encode(['status' => 'uninstalled']);
    }
}