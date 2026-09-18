<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../Models/Store.php';
require_once __DIR__ . '/../Models/Warehouse.php';

class StoreController extends Controller
{
    private Store $storeModel;
    private Warehouse $warehouseModel;

    public function __construct()
    {
        parent::__construct();
        $this->requireRole(['admin', 'manager']);
        $this->storeModel = new Store();
        $this->warehouseModel = new Warehouse();
    }

    public function index(): void
    {
        $stores = $this->storeModel->all();
        foreach ($stores as &$s) {
            $s['warehouses'] = $this->warehouseModel->allByStore((int) $s['id']);
        }
        unset($s);

        $this->view('stores/index', [
            'stores' => $stores,
            'allWarehouses' => $this->warehouseModel->all(),
            'currentStoreId' => $this->getCurrentStore()['id'],
            'storeContextActive' => !empty($_SESSION['store_context_active']),
            'created' => $_GET['created'] ?? null,
            'updated' => $_GET['updated'] ?? null,
            'deleted' => $_GET['deleted'] ?? null,
            'error' => $_GET['error'] ?? null,
        ]);
    }

    public function create(): void
    {
        $name = trim($_POST['name'] ?? '');
        $platform = trim($_POST['platform'] ?? '') ?: 'manual';
        $storeUrl = trim($_POST['store_url'] ?? '');
        $wcStoreUrl = trim($_POST['woocommerce_store_url'] ?? '');
        $wcConsumerKey = trim($_POST['woocommerce_consumer_key'] ?? '');
        $wcConsumerSecret = trim($_POST['woocommerce_consumer_secret'] ?? '');
        $bridgeUrl = trim($_POST['bridge_url'] ?? '');
        $bridgeApiKey = trim($_POST['bridge_api_key'] ?? '');
        $bridgeSharedSecret = trim($_POST['bridge_shared_secret'] ?? '');
        $warehouseIds = array_map('intval', $_POST['warehouse_ids'] ?? []);

        if ($name === '') {
            $this->redirect('/stores?error=' . urlencode('Store name is required.'));
            return;
        }

        $storeId = $this->storeModel->create($name, $platform, $storeUrl ?: null);

        if ($wcStoreUrl !== '' || $wcConsumerKey !== '' || $wcConsumerSecret !== '') {
            $this->storeModel->updateWooCommerceCredentials($storeId, $wcStoreUrl, $wcConsumerKey, $wcConsumerSecret);
        }

        if ($bridgeUrl !== '') {
            $this->storeModel->updateBridgeCredentials($storeId, $bridgeUrl, $bridgeApiKey, $bridgeSharedSecret);
        }

        foreach ($warehouseIds as $index => $warehouseId) {
            if ($warehouseId <= 0) continue;
            $this->warehouseModel->linkToStore($warehouseId, $storeId, $index === 0);
        }

        $this->redirect('/stores?created=1');
    }

    public function update(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $platform = trim($_POST['platform'] ?? '') ?: 'manual';
        $storeUrl = trim($_POST['store_url'] ?? '');
        $wcStoreUrl = trim($_POST['woocommerce_store_url'] ?? '');
        $wcConsumerKey = trim($_POST['woocommerce_consumer_key'] ?? '');
        $wcConsumerSecret = trim($_POST['woocommerce_consumer_secret'] ?? '');
        $bridgeUrl = trim($_POST['bridge_url'] ?? '');
        $bridgeApiKey = trim($_POST['bridge_api_key'] ?? '');
        $bridgeSharedSecret = trim($_POST['bridge_shared_secret'] ?? '');
        $warehouseIds = array_map('intval', $_POST['warehouse_ids'] ?? []);

        if ($id <= 0 || $name === '' || !$this->storeModel->find($id)) {
            $this->redirect('/stores?error=' . urlencode('Invalid store data.'));
            return;
        }

        $this->storeModel->update($id, $name, $platform, $storeUrl ?: null);
        $this->storeModel->updateWooCommerceCredentials($id, $wcStoreUrl, $wcConsumerKey, $wcConsumerSecret);
        $this->storeModel->updateBridgeCredentials($id, $bridgeUrl, $bridgeApiKey, $bridgeSharedSecret);

        $current = $this->warehouseModel->allByStore($id);
        $currentIds = array_map(fn($w) => (int) $w['id'], $current);

        foreach ($warehouseIds as $wid) {
            if ($wid > 0 && !in_array($wid, $currentIds, true)) {
                $this->warehouseModel->linkToStore($wid, $id);
            }
        }
        foreach ($currentIds as $cid) {
            if (!in_array($cid, $warehouseIds, true)) {
                $this->warehouseModel->unlinkFromStore($cid, $id);
            }
        }

        $this->redirect('/stores?updated=1');
    }

    public function delete(): void
    {
        $id = (int) ($_POST['id'] ?? 0);

        if ($id <= 0 || !$this->storeModel->find($id)) {
            $this->redirect('/stores?error=' . urlencode('Store not found.'));
            return;
        }

        if ($this->storeModel->productCount($id) > 0 || $this->storeModel->orderCount($id) > 0) {
            $this->redirect('/stores?error=' . urlencode('This store has products or orders — remove/move them first.'));
            return;
        }

        $this->storeModel->delete($id);

        if (($_SESSION['current_store_id'] ?? null) == $id) {
            unset($_SESSION['current_store_id'], $_SESSION['store_context_active']);
        }

        $this->redirect('/stores?deleted=1');
    }

    public function switchStore(): void
    {
        $id = (int) ($_GET['store_id'] ?? 0);
        if ($id > 0 && $this->storeModel->find($id)) {
            $_SESSION['current_store_id'] = $id;
        }
        $this->redirect($_GET['redirect'] ?? '/orders');
    }

    public function manage(): void
    {
        $id = (int) ($_GET['store_id'] ?? 0);

        if ($id <= 0 || !$this->storeModel->find($id)) {
            $this->redirect('/stores?error=' . urlencode('Store not found.'));
            return;
        }

        $_SESSION['current_store_id'] = $id;
        $_SESSION['store_context_active'] = true;

        $this->redirect('/dashboard');
    }

    public function exitManagement(): void
    {
        unset($_SESSION['store_context_active']);
        $this->redirect('/dashboard');
    }
}