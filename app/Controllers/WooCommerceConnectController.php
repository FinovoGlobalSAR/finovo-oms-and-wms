<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../Models/Store.php';


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

        $callbackUrl = $this->getBaseUrl() . '/woocommerce-connect/callback?store_id=' . $storeId . '&wp_url=' . urlencode($wpUrl);
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

    // Step 2 — WooCommerce yahan automatically Consumer Key/Secret POST karta hai.
    // Session ki zaroorat nahi — turant chhod dete hain, taaki koi lock wait na ho.
    public function callback(): void
    {
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

        $storeId = (int) ($_GET['store_id'] ?? 0);
        $wpUrl = $_GET['wp_url'] ?? '';

        if ($storeId <= 0 || !$this->storeModel->find($storeId)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid or missing store_id.']);
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
        if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
            $scheme = $_SERVER['HTTP_X_FORWARDED_PROTO'];
        } else {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        }
        return $scheme . '://' . $_SERVER['HTTP_HOST'];
    }
}