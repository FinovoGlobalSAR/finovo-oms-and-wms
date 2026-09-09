<?php
// app/Controllers/OrderController.php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../Models/Order.php';
require_once __DIR__ . '/../Models/Store.php';
require_once __DIR__ . '/../Models/Customer.php';
require_once __DIR__ . '/../Models/Product.php';

class OrderController extends Controller
{
    private Order $orderModel;
    private Store $storeModel;

    public function __construct()
    {
        $this->orderModel = new Order();
        $this->storeModel = new Store();
    }

    public function index(): void
    {
        $db = Database::getConnection();
        $settings = $db->query("SELECT api_key FROM settings LIMIT 1")->fetch();

        $platform = $_GET['platform'] ?? 'all';

        $sourceMap = [
            'shopify'     => 'shopify_pull',
            'custom'      => 'api_push',
            'woocommerce' => 'woocommerce_pull',
            'manual'      => 'manual',
            'csv'         => 'csv_import',
        ];

        if ($platform !== 'all' && isset($sourceMap[$platform])) {
            $orders = $this->orderModel->filterBySource($sourceMap[$platform]);
        } else {
            $orders = $this->orderModel->all();
        }

        $this->view('orders/index', [
            'orders' => $orders,
            'apiKey' => $settings['api_key'],
            'synced' => $_GET['synced'] ?? null,
            'exported' => $_GET['exported'] ?? null,
            'imported' => $_GET['imported'] ?? null,
            'error' => $_GET['error'] ?? null,
            'selectedPlatform' => $platform,
        ]);
    }

    public function showCreateForm(): void
    {
        $this->view('orders/create', ['error' => null]);
    }

    public function handleCreate(): void
    {
        $customer = trim($_POST['customer_name'] ?? '');
        $product  = trim($_POST['product_name'] ?? '');
        $quantity = (int) ($_POST['quantity'] ?? 1);
        $price    = (float) ($_POST['price'] ?? 0);

        if ($customer === '' || $product === '' || $price <= 0) {
            $this->view('orders/create', ['error' => 'Please fill in all fields correctly.']);
            return;
        }

        $this->orderModel->create($customer, $product, $quantity, $price);
        $this->redirect('/orders');
    }

    public function apiCreate(): void
    {
        header('Content-Type: application/json');

        $db = Database::getConnection();

        $rawBody = file_get_contents('php://input');
        $data = json_decode($rawBody, true);

        if ($data === null) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON.']);
            return;
        }

        $apiKey = $data['api_key'] ?? '';
        $settings = $db->query("SELECT api_key FROM settings LIMIT 1")->fetch();

        if ($apiKey === '' || $apiKey !== $settings['api_key']) {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid or missing api_key.']);
            return;
        }

        $customerName = trim($data['customer_name'] ?? '');
        $productName  = trim($data['product_name'] ?? '');
        $quantity     = (int) ($data['quantity'] ?? 1);
        $price        = (float) ($data['price'] ?? 0);

        if ($customerName === '' || $productName === '' || $price <= 0) {
            http_response_code(422);
            echo json_encode(['error' => 'Missing or invalid order fields.']);
            return;
        }

        $orderId = $this->orderModel->create($customerName, $productName, $quantity, $price, 'api_push');

        http_response_code(201);
        echo json_encode([
            'success' => true,
            'order_id' => $orderId,
            'message' => 'Order created successfully.',
        ]);
    }

    // ---------- Shopify Settings ----------

    public function showSettingsForm(): void
    {
        $db = Database::getConnection();
        $settings = $db->query("SELECT * FROM settings LIMIT 1")->fetch();
        $this->view('orders/settings', [
            'settings' => $settings,
            'error' => $_GET['error'] ?? null,
            'saved' => $_GET['saved'] ?? null,
        ]);
    }

    public function saveSettings(): void
    {
        $db = Database::getConnection();
        $storeUrl = trim($_POST['shopify_store_url'] ?? '');
        $accessToken = trim($_POST['shopify_access_token'] ?? '');

        $stmt = $db->prepare("UPDATE settings SET shopify_store_url = ?, shopify_access_token = ?");
        $stmt->execute([$storeUrl, $accessToken]);

        $store = $this->storeModel->first();
        if ($store) {
            $updateStore = $db->prepare("UPDATE stores SET store_url = ?, access_token = ? WHERE id = ?");
            $updateStore->execute([$storeUrl, $accessToken, $store['id']]);
        }

        $this->redirect('/orders/settings?saved=1');
    }

    // ---------- Shopify: Orders Pull ----------

    public function syncShopify(): void
    {
        $db = Database::getConnection();
        $settings = $db->query("SELECT * FROM settings LIMIT 1")->fetch();
        $store = $this->storeModel->first();

        if (empty($settings['shopify_store_url']) || empty($settings['shopify_access_token'])) {
            $this->redirect('/orders/settings?error=' . urlencode('Pehle Shopify Store URL aur Access Token save karo.'));
            return;
        }

        $apiUrl = 'https://' . rtrim($settings['shopify_store_url'], '/') . '/admin/api/2026-07/orders.json?status=any&limit=20';

        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-Shopify-Access-Token: ' . $settings['shopify_access_token']]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            $this->redirect('/orders/settings?error=' . urlencode("Shopify API error (HTTP {$httpCode})."));
            return;
        }

        $data = json_decode($response, true);
        $shopifyOrders = $data['orders'] ?? [];
        $importedCount = 0;

        foreach ($shopifyOrders as $order) {
            $externalId = (string) $order['id'];

            $check = $db->prepare("SELECT id FROM orders WHERE external_order_id = ?");
            $check->execute([$externalId]);
            if ($check->fetch()) {
                continue;
            }

            $customerName = trim(($order['customer']['first_name'] ?? '') . ' ' . ($order['customer']['last_name'] ?? ''));
            if ($customerName === '') {
                $customerName = trim(($order['billing_address']['name'] ?? '') ?: ($order['shipping_address']['name'] ?? ''));
            }
            if ($customerName === '') {
                $customerName = $order['email'] ?? 'Unknown';
            }

            $productName = $order['line_items'][0]['name'] ?? 'Unknown product';
            $quantity = $order['line_items'][0]['quantity'] ?? 1;
            $price = (float) ($order['total_price'] ?? 0);

            $stmt = $db->prepare(
                "INSERT INTO orders (store_id, customer_name, product_name, quantity, price, source, external_order_id)
                 VALUES (?, ?, ?, ?, ?, 'shopify_pull', ?)"
            );
            $stmt->execute([$store['id'], $customerName, $productName, $quantity, $price, $externalId]);
            $importedCount++;
        }

        $this->redirect('/orders?synced=' . $importedCount);
    }

    // ---------- Shopify: Order Push (Export) ----------

    public function exportToShopify(): void
    {
        $db = Database::getConnection();
        $settings = $db->query("SELECT * FROM settings LIMIT 1")->fetch();
        $id = (int) ($_GET['id'] ?? 0);

        if (empty($settings['shopify_store_url']) || empty($settings['shopify_access_token'])) {
            $this->redirect('/orders?error=' . urlencode('Pehle Shopify Settings save karo.'));
            return;
        }

        $stmt = $db->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->execute([$id]);
        $order = $stmt->fetch();

        if (!$order) {
            $this->redirect('/orders?error=' . urlencode('Order not found.'));
            return;
        }

        $apiUrl = 'https://' . rtrim($settings['shopify_store_url'], '/') . '/admin/api/2026-07/orders.json';

        $nameParts = explode(' ', $order['customer_name'], 2);
        $firstName = $nameParts[0] ?? 'Customer';
        $lastName = $nameParts[1] ?? '';
        if (trim($lastName) === '') {
            $lastName = '-';
        }

        $payload = json_encode([
            'order' => [
                'line_items' => [
                    [
                        'title' => $order['product_name'],
                        'price' => (string) $order['price'],
                        'quantity' => (int) $order['quantity'],
                    ],
                ],
                'customer' => [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                ],
                'financial_status' => 'pending',
            ],
        ]);

        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'X-Shopify-Access-Token: ' . $settings['shopify_access_token'],
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 201) {
            $this->redirect('/orders?error=' . urlencode("Shopify export failed (HTTP {$httpCode}): " . $response));
            return;
        }

        $data = json_decode($response, true);
        $externalId = (string) ($data['order']['id'] ?? '');

        if ($externalId) {
            $update = $db->prepare("UPDATE orders SET external_order_id = ? WHERE id = ?");
            $update->execute([$externalId, $id]);
        }

        $this->redirect('/orders?exported=1');
    }

    // ---------- CSV Export ----------

    public function exportCsv(): void
    {
        $orders = $this->orderModel->all();

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="orders.csv"');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['customer_name', 'product_name', 'quantity', 'price']);
        foreach ($orders as $o) {
            fputcsv($out, [$o['customer_name'], $o['product_name'], $o['quantity'], $o['price']]);
        }
        fclose($out);
        exit;
    }

    // ---------- CSV Import (Orders) ----------

    public function showImportForm(): void
    {
        $this->redirect('/orders');
    }

    public function handleImport(): void
    {
        $db = Database::getConnection();
        $store = $this->storeModel->first();

        if (empty($_FILES['csv_file']['tmp_name'])) {
            $this->redirect('/orders?error=' . urlencode('Please choose a CSV file.'));
            return;
        }

        $handle = fopen($_FILES['csv_file']['tmp_name'], 'r');
        if (!$handle) {
            $this->redirect('/orders?error=' . urlencode('Could not read the file.'));
            return;
        }

        require_once __DIR__ . '/../Models/Customer.php';
        require_once __DIR__ . '/../Models/Product.php';
        $customerModel = new Customer();
        $productModel = new Product();

        fgetcsv($handle);
        $importedCount = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 4) {
                continue;
            }

            [$customerName, $productName, $quantity, $price] = $row;
            $customerName = trim($customerName);
            $productName = trim($productName);
            $quantity = (int) trim($quantity);
            $price = (float) trim($price);

            if ($customerName === '' || $productName === '' || $price <= 0) {
                continue;
            }

            $customerId = $customerModel->findOrCreate($store['id'], $customerName);
            $productId = $productModel->findOrCreate($store['id'], $productName, $price);

            $stmt = $db->prepare(
                "INSERT INTO orders (store_id, customer_id, product_id, customer_name, product_name, quantity, price, source)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 'csv_import')"
            );
            $stmt->execute([$store['id'], $customerId, $productId, $customerName, $productName, $quantity ?: 1, $price]);
            $importedCount++;
        }

        fclose($handle);
        $this->redirect('/orders?imported=' . $importedCount);
    }
}