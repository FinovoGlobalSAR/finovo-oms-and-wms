<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/SignedToken.php';
require_once __DIR__ . '/../Models/Store.php';

class PrestaShopConnectController extends Controller
{
    private Store $storeModel;

    public function __construct()
    {
        parent::__construct();
        // Login-protected nahi — PrestaShop module server-se-server yahan
        // request bhejta hai, browser session nahi hota.
        $this->storeModel = new Store();
    }

    /** Store ka "PrestaShop connection code" (Stores → Edit mein dikhta hai). Module mein Store ID ki jagah yahi daalo. */
    public static function connectCode(int $storeId): string
    {
        return SignedToken::make(['purpose' => 'ps_connect', 'store_id' => $storeId]);
    }

    /**
     * PrestaShop module install hone par yahan POST request aati hai:
     * { connect_token: "<connection code>", store_url: "http://localhost/prestashop", api_key: "..." }
     *
     * Hifazat: pehle sirf { store_id: 5 } chahiye tha — koi bhi kisi store ki keys badal sakta tha.
     * Ab Finovo ka diya hua "connection code" zaroori hai (connect_token mein, ya module ke purane
     * store_id field mein bhi chalega). Sirf number (5) ab qubool nahi.
     */
    public function receiveKey(): void
    {
        header('Content-Type: application/json');

        $rawBody = file_get_contents('php://input');
        $data = json_decode($rawBody, true);

        if ($data === null) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON.']);
            return;
        }

        $code = (string) ($data['connect_token'] ?? $data['store_id'] ?? '');
        $token = SignedToken::verify($code, 'ps_connect');
        if ($token === null) {
            http_response_code(403);
            echo json_encode(['error' => 'Invalid connection code. Copy the PrestaShop connection code from Finovo (Stores → Edit) into the module.']);
            return;
        }

        $storeId = (int) $token['store_id'];
        $storeUrl = trim((string) ($data['store_url'] ?? ''));
        $apiKey = trim((string) ($data['api_key'] ?? ''));

        if ($storeUrl === '' || $apiKey === '' || !preg_match('#^https?://#i', $storeUrl)) {
            http_response_code(422);
            echo json_encode(['error' => 'Missing or invalid store_url, or missing api_key.']);
            return;
        }

        $store = $this->storeModel->find($storeId);
        if (!$store) {
            http_response_code(404);
            echo json_encode(['error' => 'Store not found.']);
            return;
        }

        $this->storeModel->updatePrestaShopCredentials($storeId, $storeUrl, $apiKey);

        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'PrestaShop connected successfully.']);
    }
}