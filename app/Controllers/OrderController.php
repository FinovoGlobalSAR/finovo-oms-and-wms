<?php
// app/Controllers/OrderController.php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../Models/Order.php';
require_once __DIR__ . '/../Models/Store.php';
require_once __DIR__ . '/../Models/Customer.php';
require_once __DIR__ . '/../Models/Product.php';
require_once __DIR__ . '/../Models/Warehouse.php';
require_once __DIR__ . '/../Models/ProductVariant.php';
require_once __DIR__ . '/../Models/Shipment.php';
require_once __DIR__ . '/../Services/InventoryService.php';
require_once __DIR__ . '/../Services/AuditLogService.php';
require_once __DIR__ . '/../Middlewares/CanonicalMapper.php';

class OrderController extends Controller
{
    private Order $orderModel;
    private Store $storeModel;
    private Product $productModel;
    private Warehouse $warehouseModel;
    private ProductVariant $variantModel;
    private Shipment $shipmentModel;
    private InventoryService $inventoryService;
    private AuditLogService $auditService;

    public static array $paymentMethods = ['COD', 'Bank Transfer', 'JazzCash', 'Easypaisa', 'Card', 'Other'];

    public function __construct()
    {
        parent::__construct();
        $this->requireRole(['admin', 'manager', 'sales staff']);
        $this->requireStoreContext();
        $this->orderModel = new Order();
        $this->storeModel = new Store();
        $this->productModel = new Product();
        $this->warehouseModel = new Warehouse();
        $this->variantModel = new ProductVariant();
        $this->shipmentModel = new Shipment();
        $this->inventoryService = new InventoryService();
        $this->auditService = new AuditLogService();
    }

    public function index(): void
    {
        $store = $this->getCurrentStore();

        $platform = $_GET['platform'] ?? 'all';

        $sourceMap = [
            'shopify'     => 'shopify_pull',
            'woocommerce' => 'woocommerce_pull',
            'custom'      => 'api_push',
            'manual'      => 'manual',
            'csv'         => 'csv_import',
        ];

        if ($platform !== 'all' && isset($sourceMap[$platform])) {
            $orders = $this->orderModel->filterBySource($sourceMap[$platform]);
        } else {
            $orders = $this->orderModel->all();
        }

        $orders = array_filter($orders, fn($o) => (int) $o['store_id'] === (int) $store['id']);
        $orders = array_values($orders);

        $search = trim($_GET['search'] ?? '');
        if ($search !== '') {
            $orders = array_filter($orders, function ($order) use ($search) {
                $haystack = strtolower(($order['customer_name'] ?? '') . ' ' . ($order['product_name'] ?? ''));
                return str_contains($haystack, strtolower($search));
            });
            $orders = array_values($orders);
        }

        $externalSources = ['shopify_pull', 'woocommerce_pull'];
        foreach ($orders as &$order) {
            $isExternalSource = in_array($order['source'] ?? 'manual', $externalSources, true);
            $isExternalProduct = !empty($order['product_id']) && $this->productModel->isExternal((int) $order['product_id']);
            $order['currency_symbol'] = ($isExternalSource || $isExternalProduct) ? '$' : 'Rs.';
        }
        unset($order);

        $orderIds = array_map(fn($o) => (int) $o['id'], $orders);
        $shipmentMap = $this->shipmentModel->shipmentsByOrderIds($orderIds);

        $this->view('orders/index', [
            'orders' => $orders,
            'shipmentMap' => $shipmentMap,
            'apiKey' => $store['api_key'] ?? null,
            'synced' => $_GET['synced'] ?? null,
            'stockWarningCount' => $_GET['stock_warning'] ?? null,
            'exported' => $_GET['exported'] ?? null,
            'imported' => $_GET['imported'] ?? null,
            'error' => $_GET['error'] ?? null,
            'selectedPlatform' => $platform,
        ]);
    }

    private function getAvailableProductsForForm(): array
    {
        $store = $this->getCurrentStore();
        $defaultWarehouse = $this->warehouseModel->getDefault($store['id']);

        $simpleProducts = $this->productModel->availableForOrder($store['id'], $defaultWarehouse['id']);
        $variants = $this->variantModel->availableForOrder($store['id'], $defaultWarehouse['id']);

        $items = [];

        foreach ($simpleProducts as $p) {
            $currency = !empty($p['external_product_id']) || !empty($p['external_wc_product_id']) ? '$' : 'Rs.';
            $items[] = [
                'label' => $p['name'],
                'price' => $p['price'],
                'stock' => $p['warehouse_stock'],
                'product_id' => $p['id'],
                'variant_id' => 0,
                'product_name' => $p['name'],
                'variant_label' => null,
                'currency' => $currency,
            ];
        }

        foreach ($variants as $v) {
            $currency = !empty($v['external_product_id']) || !empty($v['external_wc_product_id']) ? '$' : 'Rs.';
            $items[] = [
                'label' => $v['product_name'] . ' — ' . $v['label'],
                'price' => $v['price'],
                'stock' => $v['warehouse_stock'],
                'product_id' => $v['product_id'],
                'variant_id' => $v['variant_id'],
                'product_name' => $v['product_name'],
                'variant_label' => $v['label'],
                'currency' => $currency,
            ];
        }

        return $items;
    }

    public function showCreateForm(): void
    {
        $this->view('orders/create', [
            'error' => null,
            'products' => $this->getAvailableProductsForForm(),
            'paymentMethods' => self::$paymentMethods,
        ]);
    }

    public function handleCreate(): void
    {
        $customer = trim($_POST['customer_name'] ?? '');
        $paymentMethod = trim($_POST['payment_method'] ?? '');
        $productIds = $_POST['product_id'] ?? [];
        $variantIds = $_POST['variant_id'] ?? [];
        $quantities = $_POST['quantity'] ?? [];

        if ($customer === '' || empty($productIds)) {
            $this->view('orders/create', [
                'error' => 'Please enter a customer name and select at least one product.',
                'products' => $this->getAvailableProductsForForm(),
                'paymentMethods' => self::$paymentMethods,
            ]);
            return;
        }

        if (!in_array($paymentMethod, self::$paymentMethods, true)) {
            $paymentMethod = 'COD';
        }

        $db = Database::getConnection();
        $store = $this->getCurrentStore();
        $defaultWarehouse = $this->warehouseModel->getDefault($store['id']);
        $actor = $_SESSION['user']['name'] ?? 'system';

        $items = [];

        foreach ($productIds as $i => $productId) {
            $productId = (int) $productId;
            $variantId = (int) ($variantIds[$i] ?? 0);
            $quantity = (int) ($quantities[$i] ?? 0);

            if ($productId <= 0 || $quantity <= 0) {
                continue;
            }

            $product = $this->productModel->find($productId);
            if (!$product) {
                continue;
            }

            $variant = null;

            if ($variantId > 0) {
                $variant = $this->variantModel->find($variantId);
                if (!$variant) {
                    continue;
                }
                $itemPrice = $variant['price'] ?: $product['price'];
                $itemLabel = $product['name'] . ' — ' . $variant['label'];
            } else {
                $itemPrice = $product['price'];
                $itemLabel = $product['name'];
            }

            $items[] = [
                'product' => $product,
                'variant' => $variant,
                'quantity' => $quantity,
                'price' => $itemPrice,
                'label' => $itemLabel,
            ];
        }

        if (empty($items)) {
            $this->view('orders/create', [
                'error' => 'Please select at least one valid product.',
                'products' => $this->getAvailableProductsForForm(),
                'paymentMethods' => self::$paymentMethods,
            ]);
            return;
        }

        $orderGroup = 'ORD-' . date('YmdHis') . '-' . random_int(100, 999);
        $reservedForRollback = [];

        foreach ($items as $item) {
            $product = $item['product'];
            $variant = $item['variant'];
            $quantity = $item['quantity'];

            $result = $this->inventoryService->reserve(
                (int) $product['id'],
                $variant ? (int) $variant['id'] : null,
                (int) $defaultWarehouse['id'],
                $quantity,
                'manual_order',
                "order_group:{$orderGroup}",
                $actor
            );

            if (!$result['ok']) {
                foreach ($reservedForRollback as $done) {
                    $this->inventoryService->release(
                        $done['product_id'],
                        $done['variant_id'],
                        (int) $defaultWarehouse['id'],
                        $done['quantity'],
                        'order_rollback',
                        "order_group:{$orderGroup}",
                        $actor
                    );
                }

                $this->view('orders/create', [
                    'error' => "\"{$item['label']}\" is out of stock — requested {$result['requested']}, only {$result['available']} available.",
                    'products' => $this->getAvailableProductsForForm(),
                    'paymentMethods' => self::$paymentMethods,
                ]);
                return;
            }

            $reservedForRollback[] = [
                'product_id' => (int) $product['id'],
                'variant_id' => $variant ? (int) $variant['id'] : null,
                'quantity' => $quantity,
            ];
        }

        foreach ($items as $item) {
            $product = $item['product'];
            $variant = $item['variant'];
            $quantity = $item['quantity'];

            $stmt = $db->prepare(
                "INSERT INTO orders (store_id, customer_name, product_name, quantity, price, source, order_group, variant_id, variant_label, product_id, warehouse_id, payment_method)
                 VALUES (?, ?, ?, ?, ?, 'manual', ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $store['id'],
                $customer,
                $product['name'],
                $quantity,
                $item['price'],
                $orderGroup,
                $variant ? $variant['id'] : null,
                $variant ? $variant['label'] : null,
                $product['id'],
                $defaultWarehouse['id'],
                $paymentMethod,
            ]);
        }

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
        $storeId = (int) ($data['store_id'] ?? 0);

        if ($storeId <= 0) {
            http_response_code(422);
            echo json_encode(['error' => 'store_id is required.']);
            return;
        }

        $store = $this->storeModel->find($storeId);

        if (!$store) {
            http_response_code(404);
            echo json_encode(['error' => 'Store not found.']);
            return;
        }

        if ($apiKey === '' || $apiKey !== $store['api_key']) {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid or missing api_key.']);
            return;
        }

        $customerName = trim($data['customer_name'] ?? '');
        $productName  = trim($data['product_name'] ?? '');
        $quantity     = (int) ($data['quantity'] ?? 1);
        $price        = (float) ($data['price'] ?? 0);
        $paymentMethod = trim($data['payment_method'] ?? '');
        if (!in_array($paymentMethod, self::$paymentMethods, true)) {
            $paymentMethod = 'COD';
        }

        if ($customerName === '' || $productName === '' || $price <= 0) {
            http_response_code(422);
            echo json_encode(['error' => 'Missing or invalid order fields.']);
            return;
        }

        $defaultWarehouse = $this->warehouseModel->getDefault($store['id']);
        $existingProduct = $this->productModel->findByName($store['id'], $productName);
        $productId = $existingProduct ? (int) $existingProduct['id'] : $this->productModel->findOrCreate($store['id'], $productName, $price);

        $result = $this->inventoryService->reserve(
            $productId,
            null,
            (int) $defaultWarehouse['id'],
            $quantity,
            'api_push',
            "store:{$storeId}",
            'api'
        );

        if (!$result['ok']) {
            http_response_code(409);
            echo json_encode(['error' => 'Product out of stock.', 'requested' => $result['requested'], 'available' => $result['available']]);
            return;
        }

        $stmt = $db->prepare(
            "INSERT INTO orders (store_id, customer_name, product_name, quantity, price, source, product_id, warehouse_id, payment_method)
             VALUES (?, ?, ?, ?, ?, 'api_push', ?, ?, ?)"
        );
        $stmt->execute([$store['id'], $customerName, $productName, $quantity, $price, $productId, $defaultWarehouse['id'], $paymentMethod]);
        $orderId = $db->lastInsertId();

        http_response_code(201);
        echo json_encode([
            'success' => true,
            'order_id' => $orderId,
            'message' => 'Order created successfully.',
        ]);
    }

    // ---------- Edit / Delete (CRUD) ----------

    public function editForm(): void
    {
        $db = Database::getConnection();
        $id = (int) ($_GET['id'] ?? 0);

        $stmt = $db->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->execute([$id]);
        $order = $stmt->fetch();

        if (!$order) {
            echo "Order not found.";
            exit;
        }

        $this->view('orders/edit', [
            'order' => $order,
            'paymentMethods' => self::$paymentMethods,
            'error' => $_GET['error'] ?? null,
        ]);
    }

    public function update(): void
    {
        $db = Database::getConnection();
        $id = (int) ($_POST['id'] ?? 0);
        $newQuantity = (int) ($_POST['quantity'] ?? 0);
        $status = trim($_POST['status'] ?? 'pending');
        $paymentStatus = trim($_POST['payment_status'] ?? 'unpaid');
        $paymentMethod = trim($_POST['payment_method'] ?? '');
        $overrideReason = trim($_POST['override_reason'] ?? '');
        $actor = $_SESSION['user']['name'] ?? 'system';

        if (!in_array($paymentMethod, self::$paymentMethods, true)) {
            $paymentMethod = 'COD';
        }

        $stmt = $db->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->execute([$id]);
        $order = $stmt->fetch();

        if (!$order) {
            echo "Order not found.";
            exit;
        }

        if ($newQuantity <= 0) {
            $this->redirect('/orders/edit?id=' . $id . '&error=' . urlencode('Quantity must be at least 1.'));
            return;
        }

        $oldQuantity = (int) $order['quantity'];
        $diff = $newQuantity - $oldQuantity;
        $warehouseId = (int) ($order['warehouse_id'] ?? 0);

        if ($warehouseId <= 0) {
            $defaultWarehouse = $this->warehouseModel->getDefault((int) $order['store_id']);
            $warehouseId = (int) $defaultWarehouse['id'];
        }

        if ($diff !== 0 && $order['product_id']) {
            $variantId = !empty($order['variant_id']) ? (int) $order['variant_id'] : null;

            if ($diff > 0) {
                $result = $this->inventoryService->reserve(
                    (int) $order['product_id'],
                    $variantId,
                    $warehouseId,
                    $diff,
                    'order_edit',
                    "order:{$id}",
                    $actor
                );

                if (!$result['ok']) {
                    $this->redirect('/orders/edit?id=' . $id . '&error=' . urlencode("Cannot increase quantity — only {$result['available']} more available in stock."));
                    return;
                }
            } else {
                $this->inventoryService->release(
                    (int) $order['product_id'],
                    $variantId,
                    $warehouseId,
                    abs($diff),
                    'order_edit',
                    "order:{$id}",
                    $actor
                );
            }
        }

        if ($status !== $order['status']) {
            $reasonText = $overrideReason !== '' ? $overrideReason : 'No reason provided';
            $this->auditService->log(
                (int) $order['store_id'],
                'manual_override',
                'order',
                (string) $id,
                "Status changed {$order['status']} -> {$status}. Reason: {$reasonText}"
            );
        }

        $update = $db->prepare(
            "UPDATE orders SET quantity = ?, status = ?, payment_status = ?, payment_method = ? WHERE id = ?"
        );
        $update->execute([$newQuantity, $status, $paymentStatus, $paymentMethod, $id]);

        $this->redirect('/orders?stock_updated=1');
    }

    public function delete(): void
    {
        $db = Database::getConnection();
        $id = (int) ($_POST['id'] ?? 0);
        $actor = $_SESSION['user']['name'] ?? 'system';

        $stmt = $db->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->execute([$id]);
        $order = $stmt->fetch();

        if ($order) {
            $warehouseId = (int) ($order['warehouse_id'] ?? 0);
            if ($warehouseId <= 0) {
                $defaultWarehouse = $this->warehouseModel->getDefault((int) $order['store_id']);
                $warehouseId = (int) $defaultWarehouse['id'];
            }

            if ($order['product_id']) {
                $this->inventoryService->release(
                    (int) $order['product_id'],
                    !empty($order['variant_id']) ? (int) $order['variant_id'] : null,
                    $warehouseId,
                    (int) $order['quantity'],
                    'order_delete',
                    "order:{$id}",
                    $actor
                );
            }

            $delStmt = $db->prepare("DELETE FROM orders WHERE id = ?");
            $delStmt->execute([$id]);
        }

        $this->redirect('/orders?deleted=1');
    }

    // ---------- Settings ----------

    public function showSettingsForm(): void
    {
        $store = $this->getCurrentStore();

        $this->view('orders/settings', [
            'settings' => $store,
            'error' => $_GET['error'] ?? null,
            'saved' => $_GET['saved'] ?? null,
        ]);
    }

    public function saveSettings(): void
    {
        $store = $this->getCurrentStore();

        $storeUrl = trim($_POST['shopify_store_url'] ?? '');
        $accessToken = trim($_POST['shopify_access_token'] ?? '');
        $this->storeModel->updateCredentials((int) $store['id'], $storeUrl, $accessToken);

        $wcStoreUrl = trim($_POST['woocommerce_store_url'] ?? '');
        $wcConsumerKey = trim($_POST['woocommerce_consumer_key'] ?? '');
        $wcConsumerSecret = trim($_POST['woocommerce_consumer_secret'] ?? '');
        $this->storeModel->updateWooCommerceCredentials((int) $store['id'], $wcStoreUrl, $wcConsumerKey, $wcConsumerSecret);

        $shopifyWebhookSecret = trim($_POST['shopify_webhook_secret'] ?? '');
        $wcWebhookSecret = trim($_POST['woocommerce_webhook_secret'] ?? '');

        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE stores SET shopify_webhook_secret = ?, woocommerce_webhook_secret = ? WHERE id = ?");
        $stmt->execute([$shopifyWebhookSecret ?: null, $wcWebhookSecret ?: null, (int) $store['id']]);

        $this->auditService->log((int) $store['id'], 'credential_update', 'store', (string) $store['id'], 'Shopify/WooCommerce credentials updated.');

        $this->redirect('/orders/settings?saved=1');
    }

    // ---------- Connection Test ----------

    public function testShopifyConnection(): void
    {
        header('Content-Type: application/json');

        $storeUrl = trim($_POST['store_url'] ?? '');
        $accessToken = trim($_POST['access_token'] ?? '');

        if ($storeUrl === '' || $accessToken === '') {
            echo json_encode(['ok' => false, 'message' => 'Please enter both Store URL and Access Token.']);
            return;
        }

        $apiUrl = 'https://' . rtrim($storeUrl, '/') . '/admin/api/2026-07/shop.json';

        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-Shopify-Access-Token: ' . $accessToken]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            $data = json_decode($response, true);
            $shopName = $data['shop']['name'] ?? 'Unknown shop';
            echo json_encode(['ok' => true, 'message' => "Connected successfully to \"{$shopName}\"."]);
        } else {
            echo json_encode(['ok' => false, 'message' => "Connection failed (HTTP {$httpCode}). Please check your credentials."]);
        }
    }

    public function testWooCommerceConnection(): void
    {
        header('Content-Type: application/json');

        $storeUrl = trim($_POST['store_url'] ?? '');
        $consumerKey = trim($_POST['consumer_key'] ?? '');
        $consumerSecret = trim($_POST['consumer_secret'] ?? '');

        if ($storeUrl === '' || $consumerKey === '' || $consumerSecret === '') {
            echo json_encode(['ok' => false, 'message' => 'Please enter Store URL, Consumer Key, and Consumer Secret.']);
            return;
        }

        $apiUrl = rtrim($storeUrl, '/') . '/wp-json/wc/v3/system_status';
        $auth = $consumerKey . ':' . $consumerSecret;

        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $auth);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            echo json_encode(['ok' => true, 'message' => 'Connected successfully to WooCommerce store.']);
        } else {
            echo json_encode(['ok' => false, 'message' => "Connection failed (HTTP {$httpCode}). Please check your credentials."]);
        }
    }

    // ---------- Shopify: Orders Pull ----------

    public function syncShopify(): void
    {
        $db = Database::getConnection();
        $store = $this->getCurrentStore();
        $defaultWarehouse = $this->warehouseModel->getDefault($store['id']);

        if (empty($store['store_url']) || empty($store['access_token'])) {
            $this->redirect('/orders/settings?error=' . urlencode('Please save this store\'s Shopify Store URL and Access Token first.'));
            return;
        }

        $apiUrl = 'https://' . rtrim($store['store_url'], '/') . '/admin/api/2026-07/orders.json?status=any&limit=20';

        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-Shopify-Access-Token: ' . $store['access_token']]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            $this->storeModel->markUnhealthy((int) $store['id'], "Shopify API error (HTTP {$httpCode})");
            $this->redirect('/orders/settings?error=' . urlencode("Shopify API error (HTTP {$httpCode})."));
            return;
        }

        $data = json_decode($response, true);
        $shopifyOrders = $data['orders'] ?? [];
        $importedCount = 0;
        $warningCount = 0;

        foreach ($shopifyOrders as $order) {
            $externalId = (string) $order['id'];

            $fulfillmentStatus = $order['fulfillment_status'] ?? 'pending';
            $financialStatus = $order['financial_status'] ?? 'pending';
            $paymentStatus = CanonicalMapper::paymentStatusFromShopify($financialStatus);
            $canonicalStatus = CanonicalMapper::fromShopify($fulfillmentStatus, $financialStatus);
            $orderStatus = CanonicalMapper::toInternalOrderStatus($canonicalStatus);
            $gateway = $order['gateway'] ?? 'Card';

            $check = $db->prepare("SELECT id FROM orders WHERE external_order_id = ? AND store_id = ?");
            $check->execute([$externalId, $store['id']]);
            $existing = $check->fetch();

            if ($existing) {
                $updateStmt = $db->prepare("UPDATE orders SET status = ?, payment_status = ? WHERE id = ?");
                $updateStmt->execute([$orderStatus, $paymentStatus, $existing['id']]);
                continue;
            }

            $customerName = trim(($order['customer']['first_name'] ?? '') . ' ' . ($order['customer']['last_name'] ?? ''));
            if ($customerName === '') {
                $customerName = trim(($order['billing_address']['name'] ?? '') ?: ($order['shipping_address']['name'] ?? ''));
            }
            if ($customerName === '') {
                $customerName = $order['email'] ?? ('Order #' . ($order['order_number'] ?? $order['id']));
            }

            $productName = $order['line_items'][0]['name'] ?? 'Unknown product';
            $quantity = $order['line_items'][0]['quantity'] ?? 1;
            $price = (float) ($order['total_price'] ?? 0);

            $productId = $this->productModel->findOrCreate($store['id'], $productName, $price);

            $result = $this->inventoryService->reserve($productId, null, (int) $defaultWarehouse['id'], $quantity, 'shopify_pull', "external:{$externalId}", 'shopify_sync');
            $stockWarning = $result['ok'] ? 0 : 1;
            if ($stockWarning) {
                $warningCount++;
            }

            $stmt = $db->prepare(
                "INSERT INTO orders (store_id, customer_name, product_name, quantity, price, source, external_order_id, status, payment_status, product_id, warehouse_id, stock_warning, payment_method)
                 VALUES (?, ?, ?, ?, ?, 'shopify_pull', ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([$store['id'], $customerName, $productName, $quantity, $price, $externalId, $orderStatus, $paymentStatus, $productId, $defaultWarehouse['id'], $stockWarning, $gateway]);

            $importedCount++;
        }

        $redirectUrl = '/orders?synced=' . $importedCount;
        if ($warningCount > 0) {
            $redirectUrl .= '&stock_warning=' . $warningCount;
        }
        $this->storeModel->markHealthy((int) $store['id']);
        $this->redirect($redirectUrl);
    }

    // ---------- Shopify: Order Push ----------

    public function exportToShopify(): void
    {
        $db = Database::getConnection();
        $store = $this->getCurrentStore();
        $id = (int) ($_GET['id'] ?? 0);

        if (empty($store['store_url']) || empty($store['access_token'])) {
            $this->redirect('/orders?error=' . urlencode('Please save this store\'s Shopify Settings first.'));
            return;
        }

        $stmt = $db->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->execute([$id]);
        $order = $stmt->fetch();

        if (!$order) {
            $this->redirect('/orders?error=' . urlencode('Order not found.'));
            return;
        }

        $apiUrl = 'https://' . rtrim($store['store_url'], '/') . '/admin/api/2026-07/orders.json';

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
            'X-Shopify-Access-Token: ' . $store['access_token'],
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

    // ---------- WooCommerce: Orders Pull ----------

    public function syncWooCommerce(): void
    {
        $db = Database::getConnection();
        $store = $this->getCurrentStore();
        $defaultWarehouse = $this->warehouseModel->getDefault($store['id']);

        if (empty($store['woocommerce_store_url']) || empty($store['woocommerce_consumer_key']) || empty($store['woocommerce_consumer_secret'])) {
            $this->redirect('/orders/settings?error=' . urlencode('Please save this store\'s WooCommerce Store URL, Consumer Key, and Secret first.'));
            return;
        }

        $auth = $store['woocommerce_consumer_key'] . ':' . $store['woocommerce_consumer_secret'];
        $apiUrl = rtrim($store['woocommerce_store_url'], '/') . '/wp-json/wc/v3/orders?per_page=20';

        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $auth);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            $this->storeModel->markUnhealthy((int) $store['id'], "WooCommerce API error (HTTP {$httpCode})");
            $this->redirect('/orders/settings?error=' . urlencode("WooCommerce API error (HTTP {$httpCode}): " . $response));
            return;
        }

        $wcOrders = json_decode($response, true) ?? [];
        $importedCount = 0;
        $warningCount = 0;

        foreach ($wcOrders as $order) {
            $externalId = (string) $order['id'];

            $check = $db->prepare("SELECT id FROM orders WHERE external_wc_order_id = ? AND store_id = ?");
            $check->execute([$externalId, $store['id']]);
            if ($check->fetch()) {
                continue;
            }

            $billing = $order['billing'] ?? [];
            $customerName = trim(($billing['first_name'] ?? '') . ' ' . ($billing['last_name'] ?? ''));
            if ($customerName === '') {
                $customerName = $billing['email'] ?? 'Unknown';
            }

            $lineItems = $order['line_items'][0] ?? [];
            $productName = $lineItems['name'] ?? 'Unknown product';
            $quantity = $lineItems['quantity'] ?? 1;
            $price = (float) ($order['total'] ?? 0);
            $paymentMethodTitle = $order['payment_method_title'] ?? 'Other';

            $wcStatus = $order['status'] ?? 'pending';
            $paymentStatus = CanonicalMapper::paymentStatusFromWooCommerce($wcStatus);
            $canonicalStatus = CanonicalMapper::fromWooCommerce($wcStatus);
            $orderStatus = CanonicalMapper::toInternalOrderStatus($canonicalStatus);

            $productId = $this->productModel->findOrCreate($store['id'], $productName, $price);

            $result = $this->inventoryService->reserve($productId, null, (int) $defaultWarehouse['id'], $quantity, 'woocommerce_pull', "external:{$externalId}", 'woocommerce_sync');
            $stockWarning = $result['ok'] ? 0 : 1;
            if ($stockWarning) {
                $warningCount++;
            }

            $stmt = $db->prepare(
                "INSERT INTO orders (store_id, customer_name, product_name, quantity, price, source, external_wc_order_id, status, payment_status, product_id, warehouse_id, stock_warning, payment_method)
                 VALUES (?, ?, ?, ?, ?, 'woocommerce_pull', ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([$store['id'], $customerName, $productName, $quantity, $price, $externalId, $orderStatus, $paymentStatus, $productId, $defaultWarehouse['id'], $stockWarning, $paymentMethodTitle]);

            $importedCount++;
        }

        $redirectUrl = '/orders?synced=' . $importedCount;
        if ($warningCount > 0) {
            $redirectUrl .= '&stock_warning=' . $warningCount;
        }
        $this->storeModel->markHealthy((int) $store['id']);
        $this->redirect($redirectUrl);
    }

    // ---------- WooCommerce: Order Push ----------

    public function exportToWooCommerce(): void
    {
        $db = Database::getConnection();
        $store = $this->getCurrentStore();
        $id = (int) ($_GET['id'] ?? 0);

        if (empty($store['woocommerce_store_url']) || empty($store['woocommerce_consumer_key']) || empty($store['woocommerce_consumer_secret'])) {
            $this->redirect('/orders?error=' . urlencode('Please save this store\'s WooCommerce Settings first.'));
            return;
        }

        $stmt = $db->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->execute([$id]);
        $order = $stmt->fetch();

        if (!$order) {
            $this->redirect('/orders?error=' . urlencode('Order not found.'));
            return;
        }

        $baseUrl = rtrim($store['woocommerce_store_url'], '/');
        $auth = $store['woocommerce_consumer_key'] . ':' . $store['woocommerce_consumer_secret'];

        $searchUrl = $baseUrl . '/wp-json/wc/v3/products?search=' . urlencode($order['product_name']);
        $ch = curl_init($searchUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $auth);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $searchResponse = curl_exec($ch);
        curl_close($ch);

        $searchResults = json_decode($searchResponse, true) ?? [];
        $productId = null;

        foreach ($searchResults as $wp) {
            if (strcasecmp($wp['name'], $order['product_name']) === 0) {
                $productId = $wp['id'];
                break;
            }
        }

        if (!$productId) {
            $createProductPayload = json_encode([
                'name' => $order['product_name'],
                'type' => 'simple',
                'regular_price' => (string) $order['price'],
            ]);

            $ch = curl_init($baseUrl . '/wp-json/wc/v3/products');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $createProductPayload);
            curl_setopt($ch, CURLOPT_USERPWD, $auth);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $createResponse = curl_exec($ch);
            $createHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($createHttpCode !== 201) {
                $this->redirect('/orders?error=' . urlencode("Could not create product on WooCommerce (HTTP {$createHttpCode})."));
                return;
            }

            $createData = json_decode($createResponse, true);
            $productId = $createData['id'] ?? null;
        }

        if (!$productId) {
            $this->redirect('/orders?error=' . urlencode('Could not resolve a WooCommerce product for this order.'));
            return;
        }

        $nameParts = explode(' ', $order['customer_name'], 2);
        $firstName = $nameParts[0] ?? 'Customer';
        $lastName = $nameParts[1] ?? '-';

        $payload = json_encode([
            'payment_method' => 'manual',
            'payment_method_title' => 'Manual (Finovo)',
            'set_paid' => false,
            'billing' => [
                'first_name' => $firstName,
                'last_name' => $lastName,
            ],
            'line_items' => [
                [
                    'product_id' => $productId,
                    'quantity' => (int) $order['quantity'],
                    'total' => (string) ($order['price'] * $order['quantity']),
                ],
            ],
        ]);

        $ch = curl_init($baseUrl . '/wp-json/wc/v3/orders');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_USERPWD, $auth);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 201) {
            $this->redirect('/orders?error=' . urlencode("WooCommerce export failed (HTTP {$httpCode}): " . $response));
            return;
        }

        $data = json_decode($response, true);
        $externalId = (string) ($data['id'] ?? '');

        if ($externalId) {
            $update = $db->prepare("UPDATE orders SET external_wc_order_id = ? WHERE id = ?");
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
        $store = $this->getCurrentStore();
        $defaultWarehouse = $this->warehouseModel->getDefault($store['id']);

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
        $customerModel = new Customer();

        fgetcsv($handle);
        $importedCount = 0;
        $skippedCount = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 4) {
                continue;
            }

            [$customerName, $productName, $quantity, $price] = $row;
            $customerName = trim($customerName);
            $productName = trim($productName);
            $quantity = (int) trim($quantity) ?: 1;
            $price = (float) trim($price);

            if ($customerName === '' || $productName === '' || $price <= 0) {
                continue;
            }

            $productId = $this->productModel->findOrCreate($store['id'], $productName, $price);

            $result = $this->inventoryService->reserve($productId, null, (int) $defaultWarehouse['id'], $quantity, 'csv_import', 'csv_import', $_SESSION['user']['name'] ?? 'system');

            if (!$result['ok']) {
                $skippedCount++;
                continue;
            }

            $customerId = $customerModel->findOrCreate($store['id'], $customerName);

            $stmt = $db->prepare(
                "INSERT INTO orders (store_id, customer_id, product_id, customer_name, product_name, quantity, price, source, warehouse_id)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 'csv_import', ?)"
            );
            $stmt->execute([$store['id'], $customerId, $productId, $customerName, $productName, $quantity, $price, $defaultWarehouse['id']]);

            $importedCount++;
        }

        fclose($handle);

        if ($skippedCount > 0) {
            $this->redirect('/orders?imported=' . $importedCount . '&error=' . urlencode("{$skippedCount} row(s) skipped due to insufficient stock."));
            return;
        }

        $this->redirect('/orders?imported=' . $importedCount);
    }
}