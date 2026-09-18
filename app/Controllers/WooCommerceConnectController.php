<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../Models/Store.php';

/**
 * WooCommerce ka apna official "REST API Authorization" flow use karta hai
 * (/wc-auth/v1/authorize). Customer sirf apna store URL likhta hai, WooCommerce
 * khud "Approve" page kholta hai, aur automatically Consumer Key/Secret
 * generate karke Finovo ko bhej deta hai — customer ko kuch copy-paste nahi karna.
 *
 * getBaseUrl() yahan bhi dynamic hai — jis bhi domain se request aayi ho
 * (localhost, ngrok, ya live domain), khud usi ko use karega.
 */
class WooCommerceConnectController extends Controller
{
    private Store $storeModel;

    public function __construct()
    {
        parent::__construct();
        $this->requireRole(['admin', 'manager']);
        $this->storeModel = new Store();
    }

    // Step 1 — Customer ko WooCommerce ke authorize page pe bhejo
    public function connect(): void
    {
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

        // Ek random token banate hain — jab callback aayega, isse verify karenge ke
        // yeh request genuinely humne bheji thi, koi fake callback nahi hai
        $state = bin2hex(random_bytes(16));
        $_SESSION['wc_connect_state'] = $state;
        $_SESSION['wc_connect_store_id'] = $storeId;
        $_SESSION['wc_connect_wp_url'] = $wpUrl;

        $callbackUrl = $this->getBaseUrl() . '/woocommerce-connect/callback?state=' . $state;
        $returnUrl = $this->getBaseUrl() . '/stores?woo_connected=1';

        $params = http_build_query([
            'app_name'     => 'Finovo OMS/WMS',
            'scope'        => 'read_write',
            'user_id'      => $storeId,
            'return_url'   => $returnUrl,
            'callback_url' => $callbackUrl,
        ]);

        // WooCommerce ka apna official authorize endpoint
        $authorizeUrl = $wpUrl . '/wc-auth/v1/authorize?' . $params;

        header('Location: ' . $authorizeUrl);
        exit;
    }

    // Step 2 — WooCommerce yahan automatically Consumer Key/Secret POST karta hai
    public function callback(): void
    {
        $rawBody = file_get_contents('php://input');
        $data = json_decode($rawBody, true);

        if (!$data || empty($data['consumer_key']) || empty($data['consumer_secret'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing consumer_key or consumer_secret.']);
            return;
        }

        $storeId = (int) ($_SESSION['wc_connect_store_id'] ?? 0);
        $wpUrl = $_SESSION['wc_connect_wp_url'] ?? '';

        if ($storeId <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'No pending connection found.']);
            return;
        }

        $this->storeModel->updateWooCommerceCredentials(
            $storeId,
            $wpUrl,
            $data['consumer_key'],
            $data['consumer_secret']
        );

        unset($_SESSION['wc_connect_state'], $_SESSION['wc_connect_store_id'], $_SESSION['wc_connect_wp_url']);

        http_response_code(200);
        echo json_encode(['success' => true]);
    }

    private function getBaseUrl(): string
    {
        // Dynamic — jis domain se request aayi (localhost, ngrok, live domain),
        // khud usi ko use karega. Koi hardcoding nahi.
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        return $scheme . '://' . $_SERVER['HTTP_HOST'];
    }
}