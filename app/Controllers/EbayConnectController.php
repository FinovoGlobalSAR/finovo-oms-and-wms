<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../Models/Store.php';
require_once __DIR__ . '/../Services/EbayAuthService.php';
require_once __DIR__ . '/../Services/AuditLogService.php';

class EbayConnectController extends Controller
{
    private Store $storeModel;

    public function __construct()
    {
        parent::__construct();
        $this->requireRole(['admin', 'manager']);
        $this->storeModel = new Store();
    }

    public function connect(): void
    {
        $storeId = (int) ($_GET['store_id'] ?? $_POST['store_id'] ?? 0);

        if ($storeId <= 0 || !$this->storeModel->find($storeId)) {
            $this->redirect('/stores?error=' . urlencode('Store not found.'));
            return;
        }

        if (!EbayAuthService::isConfigured()) {
            $missing = [];
            foreach (['EBAY_APP_ID', 'EBAY_CERT_ID', 'EBAY_RUNAME'] as $key) {
                if (trim((string) env($key, '')) === '') {
                    $missing[] = $key;
                }
            }
            $this->redirect('/stores?error=' . urlencode('eBay is not configured. Missing in .env: ' . implode(', ', $missing) . '. Fill them in .env and save the file.'));
            return;
        }

        $state = bin2hex(random_bytes(16));
        $_SESSION['ebay_oauth_state'] = $state;
        $_SESSION['ebay_oauth_store_id'] = $storeId;

        header('Location: ' . EbayAuthService::consentUrl($state));
        exit;
    }

    public function callback(): void
    {
        $state = $_GET['state'] ?? '';
        $code = $_GET['code'] ?? '';
        $storeId = (int) ($_SESSION['ebay_oauth_store_id'] ?? 0);

        if ($state === '' || !hash_equals((string) ($_SESSION['ebay_oauth_state'] ?? ''), $state) || $storeId <= 0) {
            $this->redirect('/stores?error=' . urlencode('eBay connection expired or invalid. Please click "Connect eBay" again.'));
            return;
        }
        unset($_SESSION['ebay_oauth_state'], $_SESSION['ebay_oauth_store_id']);

        if ($code === '') {
            $this->redirect('/stores?error=' . urlencode('eBay access was not granted.'));
            return;
        }

        $result = EbayAuthService::exchangeCode($code);
        if (!$result['ok'] || empty($result['refresh_token'])) {
            $this->redirect('/stores?error=' . urlencode($result['message'] ?? 'eBay did not return a refresh token.'));
            return;
        }

        $this->storeModel->updateEbayOAuthTokens(
            $storeId,
            $result['access_token'],
            $result['expires_in'],
            $result['refresh_token'],
            $result['refresh_token_expires_in']
        );

        (new AuditLogService())->log($storeId, 'credential_update', 'store', (string) $storeId, 'eBay connected via OAuth (' . (EbayAuthService::isProduction() ? 'production' : 'sandbox') . ').');

        $this->redirect('/stores?ebay_connected=1');
    }
}