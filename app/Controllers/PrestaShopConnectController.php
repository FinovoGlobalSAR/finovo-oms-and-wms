<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Database.php';
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

    /**
     * PrestaShop module install hone par yahan POST request aati hai:
     * { store_id: 5, store_url: "http://localhost/prestashop", api_key: "..." }
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

        $storeId = (int) ($data['store_id'] ?? 0);
        $storeUrl = trim($data['store_url'] ?? '');
        $apiKey = trim($data['api_key'] ?? '');

        if ($storeId <= 0 || $storeUrl === '' || $apiKey === '') {
            http_response_code(422);
            echo json_encode(['error' => 'Missing store_id, store_url, or api_key.']);
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