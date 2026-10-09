<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/SignedToken.php';
require_once __DIR__ . '/../Models/Store.php';

/**
 * WooCommerce "Connect" — customer apni WordPress site pe "Approve" dabata hai, WooCommerce keys
 * seedha Finovo ke callback pe bhejta hai.
 *
 * Hifazat: callback URL mein ek signed token hota hai (store + site + 1 ghanta). Pehle callback
 * pe koi check nahi tha — koi bhi kisi store ki keys apni fake site se badal sakta tha.
 */
class WooCommerceConnectController extends Controller
{
    private Store $storeModel;

    public function __construct()
    {
        parent::__construct();
        $this->storeModel = new Store();
    }

    // Step 1 — Customer ko WooCommerce ke authorize page pe bhejo
    public function connect(): void
    {
        $this->requireRole(['admin', 'manager']);

        $storeId = (int) ($_POST['store_id'] ?? $_GET['store_id'] ?? 0);
        $wpUrl = trim($_POST['wp_url'] ?? $_GET['wp_url'] ?? '');

        if ($storeId <= 0 || $wpUrl === '') {
            $this->redirect('/stores?error=' . urlencode('Please enter your WordPress site URL.'));
            return;
        }

        if (!$this->storeModel->find($storeId)) {
            $this->redirect('/stores?error=' . urlencode('Store not found.'));
            return;
        }

        $wpUrl = rtrim($wpUrl, '/');

        // Sirf Finovo ka banaya token callback chala sakta hai (store + site us ke andar, 1 ghante ke liye)
        $token = SignedToken::make(['purpose' => 'woo_connect', 'store_id' => $storeId, 'wp_url' => $wpUrl], 3600);
        $callbackUrl = $this->getBaseUrl() . '/woocommerce-connect/callback?token=' . urlencode($token);
        $returnUrl = $this->getBaseUrl() . '/stores?woo_connected=1';

        $params = http_build_query([
            'app_name'     => 'Finovo OMS/WMS',
            'scope'        => 'read_write',
            'user_id'      => $storeId,
            'return_url'   => $returnUrl,
            'callback_url' => $callbackUrl,
        ]);

        $authorizeUrl = $wpUrl . '/wc-auth/v1/authorize?' . $params;

        header('Location: ' . $authorizeUrl);
        exit;
    }
    
    public function callback(): void
    {
        header('Content-Type: application/json');
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        $rawBody = file_get_contents('php://input');
        $data = json_decode($rawBody, true);

        if (!$data || empty($data['consumer_key']) || empty($data['consumer_secret'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing consumer_key or consumer_secret.']);
            return;
        }

        // Store aur site token se aate hain (URL ke store_id / wp_url pe bharosa nahi)
        $token = SignedToken::verify((string) ($_GET['token'] ?? ''), 'woo_connect');
        if ($token === null) {
            http_response_code(403);
            echo json_encode(['error' => 'Invalid or expired connect link. Please click "Connect WooCommerce" again in Finovo.']);
            return;
        }
        $storeId = (int) $token['store_id'];
        $wpUrl = (string) $token['wp_url'];

        if ($storeId <= 0 || !$this->storeModel->find($storeId)) {
            http_response_code(400);
            echo json_encode(['error' => 'Store not found.']);
            return;
        }

        $this->storeModel->updateWooCommerceCredentials(
            $storeId,
            $wpUrl,
            $data['consumer_key'],
            $data['consumer_secret']
        );

        http_response_code(200);
        echo json_encode(['success' => true]);
    }

    private function getBaseUrl(): string
    {
        return appUrl();
    }
}