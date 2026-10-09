<?php
// app/Controllers/OrderController.php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Paginator.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../Models/Order.php';
require_once __DIR__ . '/../Models/Store.php';
require_once __DIR__ . '/../Models/Customer.php';
require_once __DIR__ . '/../Models/Product.php';
require_once __DIR__ . '/../Models/Warehouse.php';
require_once __DIR__ . '/../Models/ProductVariant.php';
require_once __DIR__ . '/../Models/Shipment.php';
require_once __DIR__ . '/../Services/PlatformFetcher.php';
require_once __DIR__ . '/../Services/InventoryService.php';
require_once __DIR__ . '/../Services/OrderStockService.php';
require_once __DIR__ . '/../Services/AuditLogService.php';
require_once __DIR__ . '/../Middlewares/CanonicalMapper.php';
require_once __DIR__ . '/../Middlewares/ProductAvailabilityGuard.php';
require_once __DIR__ . '/../Connectors/WixConnector.php';
require_once __DIR__ . '/../Connectors/EbayConnector.php';
require_once __DIR__ . '/../Services/EbayAuthService.php';
require_once __DIR__ . '/../Services/EbayOrderImporter.php';
require_once __DIR__ . '/../Services/OrderImportService.php';
require_once __DIR__ . '/../Services/PlatformOrderMapper.php';
require_once __DIR__ . '/../Services/WooCommerceAuth.php';

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
    private ProductAvailabilityGuard $availabilityGuard;
    private OrderImportService $importer;

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
        $this->availabilityGuard = new ProductAvailabilityGuard();
        $this->importer = new OrderImportService();
    }

    private function externalDbConnection(string $dbName): PDO
    {
        $host = $_ENV['DB_EXTERNAL_HOST'] ?? 'localhost';
        $user = $_ENV['DB_EXTERNAL_USER'] ?? 'root';
        $password = $_ENV['DB_EXTERNAL_PASSWORD'] ?? '';

        $pdo = new PDO("mysql:host={$host};dbname={$dbName};charset=utf8mb4", $user, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    }

    public function index(): void
    {
        $store = $this->getCurrentStore();

        $platform = $_GET['platform'] ?? 'all';

        $sourceMap = [
            'shopify'     => 'shopify_pull',
            'woocommerce' => 'woocommerce_pull',
            'bigcommerce' => 'bigcommerce_pull',
            'prestashop'  => 'prestashop_pull',
            'opencart'    => 'opencart_pull',
            'oscommerce'  => 'oscommerce_pull',
            'wix'         => 'wix_pull',
            'ebay'        => 'ebay_pull',
            'magento'     => 'magento_pull',
            'custom'      => 'api_push',
            'manual'      => 'manual',
            'csv'         => 'csv_import',
        ];

        if ($platform !== 'all' && isset($sourceMap[$platform])) {
            $orders = $this->orderModel->filterBySourceAndStore($sourceMap[$platform], (int) $store['id']);
        } else {
            $orders = $this->orderModel->allByStore((int) $store['id']);
        }

        $search = trim($_GET['search'] ?? '');
        if ($search !== '') {
            $orders = array_filter($orders, function ($order) use ($search) {
                $haystack = strtolower(($order['customer_name'] ?? '') . ' ' . ($order['product_name'] ?? ''));
                return str_contains($haystack, strtolower($search));
            });
            $orders = array_values($orders);
        }

        // Status filter ab server pe (pehle sirf browser mein — dusre page ke orders filter nahi hote the)
        $status = trim($_GET['status'] ?? '');
        if ($status !== '') {
            $orders = array_values(array_filter($orders, fn($o) => ($o['status'] ?? 'pending') === $status));
        }

        // Upar ke numbers POORI list se (sirf is page se nahi)
        $shipmentMap = [];
        foreach (array_chunk(array_map(fn($o) => (int) $o['id'], $orders), 1000) as $chunk) {
            $shipmentMap += $this->shipmentModel->shipmentsByOrderIds($chunk);
        }
        $stats = ['total' => count($orders), 'pending' => 0, 'unpaid' => 0, 'notDispatched' => 0];
        foreach ($orders as $o) {
            if (($o['status'] ?? 'pending') === 'pending') { $stats['pending']++; }
            if (($o['payment_status'] ?? 'unpaid') !== 'paid') { $stats['unpaid']++; }
            if (empty($shipmentMap[(int) $o['id']])) { $stats['notDispatched']++; }
        }

        // Sirf is page ki 25 rows (pehle saari rows render hoti thin, har row pe ek alag query)
        $pager = Paginator::make($orders);
        $orders = $pager['items'];

        $externalSources = externalOrderSources();
        foreach ($orders as &$order) {
            $isExternalSource = in_array($order['source'] ?? 'manual', $externalSources, true);
            $isExternalProduct = !empty($order['product_id']) && $this->productModel->isExternal((int) $order['product_id']);
            $order['currency_symbol'] = ($isExternalSource || $isExternalProduct) ? '$' : 'Rs.';
        }
        unset($order);

        $this->view('orders/index', [
            'orders' => $orders,
            'pager' => $pager,
            'stats' => $stats,
            'selectedStatus' => $status,
            'store' => $store,
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

        foreach ($items as &$item) {
            $product = $item['product'];
            $variant = $item['variant'];
            $quantity = $item['quantity'];

            $result = $this->inventoryService->reserveAcrossWarehouses(
                (int) $product['id'],
                $variant ? (int) $variant['id'] : null,
                (int) $store['id'],
                $quantity,
                'manual_order',
                "order_group:{$orderGroup}",
                $actor
            );

            if (!$result['ok']) {
                foreach ($reservedForRollback as $done) {
                    foreach ($done['allocations'] as $alloc) {
                        $this->inventoryService->release(
                            $done['product_id'],
                            $done['variant_id'],
                            $alloc['warehouse_id'],
                            $alloc['quantity'],
                            'order_rollback',
                            "order_group:{$orderGroup}",
                            $actor
                        );
                    }
                }

                $this->view('orders/create', [
                    'error' => "\"{$item['label']}\" is out of stock — requested {$result['requested']}, only {$result['available']} available across all linked warehouses.",
                    'products' => $this->getAvailableProductsForForm(),
                    'paymentMethods' => self::$paymentMethods,
                ]);
                return;
            }

            $item['allocations'] = $result['allocations'];

            $reservedForRollback[] = [
                'product_id' => (int) $product['id'],
                'variant_id' => $variant ? (int) $variant['id'] : null,
                'allocations' => $result['allocations'],
            ];
        }
        unset($item);

        // Manual order ka customer bhi Customers page pe aaye
        $customerId = (new Customer())->upsertFromOrder((int) $store['id'], $customer, [], 'manual');

        foreach ($items as $item) {
            $product = $item['product'];
            $variant = $item['variant'];
            $allocations = $item['allocations'];

            foreach ($allocations as $alloc) {
                $stmt = $db->prepare(
                    "INSERT INTO orders (store_id, customer_name, product_name, quantity, price, source, order_group, variant_id, variant_label, product_id, warehouse_id, payment_method, customer_id)
                     VALUES (?, ?, ?, ?, ?, 'manual', ?, ?, ?, ?, ?, ?, ?)"
                );
                $stmt->execute([
                    $store['id'],
                    $customer,
                    $product['name'],
                    $alloc['quantity'],
                    $item['price'],
                    $orderGroup,
                    $variant ? $variant['id'] : null,
                    $variant ? $variant['label'] : null,
                    $product['id'],
                    $alloc['warehouse_id'],
                    $paymentMethod,
                    $customerId,
                ]);
            }
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

        // Customer ki details (optional): customer_email, customer_phone, shipping_address
        $customerDetails = [
            'email' => (string) ($data['customer_email'] ?? ''),
            'phone' => (string) ($data['customer_phone'] ?? ''),
            'address' => (string) ($data['shipping_address'] ?? ''),
        ];
        $customerId = (new Customer())->upsertFromOrder((int) $store['id'], $customerName, $customerDetails, 'api_push');

        $stmt = $db->prepare(
            "INSERT INTO orders (store_id, customer_name, product_name, quantity, price, source, product_id, warehouse_id, payment_method,
                                 customer_id, customer_email, customer_phone, shipping_address)
             VALUES (?, ?, ?, ?, ?, 'api_push', ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $store['id'], $customerName, $productName, $quantity, $price, $productId, $defaultWarehouse['id'], $paymentMethod,
            $customerId,
            Customer::cleanEmail($customerDetails['email']),
            mb_substr(trim($customerDetails['phone']), 0, 50) ?: null,
            trim($customerDetails['address']) ?: null,
        ]);
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

        $allowedStatuses = ['pending', 'processing', 'delivered', 'completed', 'cancelled'];
        if (!in_array($status, $allowedStatuses, true)) {
            $status = $order['status'];
        }

        $oldQuantity = (int) $order['quantity'];
        $diff = $newQuantity - $oldQuantity;
        $warehouseId = (int) ($order['warehouse_id'] ?? 0);

        if ($warehouseId <= 0) {
            $defaultWarehouse = $this->warehouseModel->getDefault((int) $order['store_id']);
            $warehouseId = (int) $defaultWarehouse['id'];
        }

        // Quantity badli: stock ka farq sirf tab jab order ka stock pakda hua ho
        // (cancelled ya "stock warning" wale order mein stock kata hi nahi hai)
        if ($diff !== 0 && OrderStockService::holdsStock($order) && $status !== OrderStockService::CANCELLED) {
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

        // Status badla: Cancelled → jitna stock pakda hua tha (purani quantity) wapas;
        // Cancelled se wapas → nayi quantity ka stock dobara kat-ta hai
        $stockWarning = (int) ($order['stock_warning'] ?? 0);
        if ($status !== $order['status']) {
            $qtyForStock = $status === OrderStockService::CANCELLED ? $oldQuantity : $newQuantity;
            $stockResult = (new OrderStockService())->beforeStatusChange(
                array_merge($order, ['quantity' => $qtyForStock, 'warehouse_id' => $warehouseId]),
                $status,
                $actor,
                true
            );
            if (!$stockResult['ok']) {
                $this->redirect('/orders/edit?id=' . $id . '&error=' . urlencode($stockResult['message']));
                return;
            }
            $stockWarning = (int) $stockResult['stock_warning'];
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
            "UPDATE orders SET quantity = ?, status = ?, payment_status = ?, payment_method = ?, stock_warning = ? WHERE id = ?"
        );
        $update->execute([$newQuantity, $status, $paymentStatus, $paymentMethod, $stockWarning, $id]);

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

            // Stock sirf tab wapas jab order ne pakda hua ho — cancelled order ka stock pehle hi wapas aa chuka hai
            // (warna delete pe dobara jud jata aur stock asal se zyada dikhta)
            if (OrderStockService::holdsStock($order)) {
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

        $bcStoreHash = trim($_POST['bigcommerce_store_hash'] ?? '');
        $bcAccessToken = trim($_POST['bigcommerce_access_token'] ?? '');
        if ($bcStoreHash !== '' || $bcAccessToken !== '') {
            $this->storeModel->updateBigCommerceCredentials((int) $store['id'], $bcStoreHash, $bcAccessToken);
        }

        $psStoreUrl = trim($_POST['prestashop_store_url'] ?? '');
        $psApiKey = trim($_POST['prestashop_api_key'] ?? '');
        if ($psStoreUrl !== '' || $psApiKey !== '') {
            $this->storeModel->updatePrestaShopCredentials((int) $store['id'], $psStoreUrl, $psApiKey);
        }

        $ocStoreUrl = trim($_POST['opencart_store_url'] ?? '');
        if ($ocStoreUrl !== '') {
            $this->storeModel->updateOpenCartCredentials((int) $store['id'], $ocStoreUrl, '', '');
        }

        $oscStoreUrl = trim($_POST['oscommerce_store_url'] ?? '');
        if ($oscStoreUrl !== '') {
            $this->storeModel->updateOsCommerceCredentials((int) $store['id'], $oscStoreUrl, '', '');
        }

        $wixSiteId = trim($_POST['wix_site_id'] ?? '');
        $wixApiKey = trim($_POST['wix_api_key'] ?? '');
        if ($wixSiteId !== '' || $wixApiKey !== '') {
            $this->storeModel->updateWixCredentials((int) $store['id'], $wixSiteId, $wixApiKey);
        }

        $ebayUserToken = trim($_POST['ebay_user_token'] ?? '');
        if ($ebayUserToken !== '') {
            $this->storeModel->updateEbayCredentials((int) $store['id'], $ebayUserToken);
        }

        $shopifyWebhookSecret = trim($_POST['shopify_webhook_secret'] ?? '');
        $wcWebhookSecret = trim($_POST['woocommerce_webhook_secret'] ?? '');

        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE stores SET shopify_webhook_secret = ?, woocommerce_webhook_secret = ? WHERE id = ?");
        $stmt->execute([$shopifyWebhookSecret ?: null, $wcWebhookSecret ?: null, (int) $store['id']]);

        $this->auditService->log((int) $store['id'], 'credential_update', 'store', (string) $store['id'], 'Store credentials updated.');

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

        $ch = curl_init(WooCommerceAuth::url('GET', $apiUrl, $consumerKey, $consumerSecret));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $auth);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, curlVerifySsl());
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
        $store = $this->getCurrentStore();

        if (empty($store['store_url']) || empty($store['access_token'])) {
            $this->redirect('/orders/settings?error=' . urlencode('Please save this store\'s Shopify Store URL and Access Token first.'));
            return;
        }

        // Saare orders, 250-250 kar ke (pehle sirf aakhri 20 aate the)
        $fetch = PlatformFetcher::shopify($store['store_url'], $store['access_token'], 'orders', 'status=any');

        if (!$fetch['ok']) {
            $httpCode = $fetch['code'];
            $this->storeModel->markUnhealthy((int) $store['id'], "Shopify API error (HTTP {$httpCode})");
            $this->redirect('/orders/settings?error=' . urlencode("Shopify API error (HTTP {$httpCode})."));
            return;
        }

        $shopifyOrders = $fetch['items'];
        $importedCount = 0;

        foreach ($shopifyOrders as $order) {
            $externalId = (string) $order['id'];

            $fulfillmentStatus = $order['fulfillment_status'] ?? 'pending';
            $financialStatus = $order['financial_status'] ?? 'pending';
            $paymentStatus = CanonicalMapper::paymentStatusFromShopify($financialStatus);
            $canonicalStatus = CanonicalMapper::fromShopify($fulfillmentStatus, $financialStatus);
            $orderStatus = CanonicalMapper::toInternalOrderStatus($canonicalStatus);

            if ($this->importer->exists((int) $store['id'], 'shopify_pull', $externalId)) {
                $this->importer->updateStatus((int) $store['id'], 'shopify_pull', $externalId, $orderStatus, $paymentStatus);
                continue;
            }

            $customerName = trim(($order['customer']['first_name'] ?? '') . ' ' . ($order['customer']['last_name'] ?? ''));
            if ($customerName === '') {
                $customerName = trim(($order['billing_address']['name'] ?? '') ?: ($order['shipping_address']['name'] ?? ''));
            }
            if ($customerName === '') {
                $customerName = $order['email'] ?? ('Order #' . ($order['order_number'] ?? $order['id']));
            }

            // Order ke SAARE products (pehle sirf pehla aata tha)
            $result = $this->importer->import((int) $store['id'], 'shopify_pull', $externalId, [
                'customer_name' => $customerName,
                'status' => $orderStatus,
                'payment_status' => $paymentStatus,
                'payment_method' => $order['gateway'] ?? 'Card',
                'customer' => PlatformOrderMapper::customer('shopify', $order),
            ], PlatformOrderMapper::shopifyLines($order));

            if ($result['ok']) {
                $importedCount++;
            }
        }

        $this->storeModel->markHealthy((int) $store['id']);
        $this->redirect('/orders?synced=' . $importedCount);
    }

    // ---------- WooCommerce: Orders Pull ----------

    public function syncWooCommerce(): void
    {
        $store = $this->getCurrentStore();

        if (empty($store['woocommerce_store_url']) || empty($store['woocommerce_consumer_key']) || empty($store['woocommerce_consumer_secret'])) {
            $this->redirect('/orders/settings?error=' . urlencode('Please save this store\'s WooCommerce Store URL, Consumer Key, and Secret first.'));
            return;
        }

        // Saare orders, 100-100 kar ke (pehle sirf aakhri 20). http:// site pe OAuth signature PlatformFetcher lagata hai.
        $fetch = PlatformFetcher::woo(
            $store['woocommerce_store_url'],
            $store['woocommerce_consumer_key'],
            $store['woocommerce_consumer_secret'],
            '/wp-json/wc/v3/orders'
        );

        if (!$fetch['ok']) {
            $httpCode = $fetch['code'];
            $this->storeModel->markUnhealthy((int) $store['id'], "WooCommerce API error (HTTP {$httpCode})");
            $this->redirect('/orders/settings?error=' . urlencode("WooCommerce API error (HTTP {$httpCode}). Please check the store URL and keys."));
            return;
        }

        $wcOrders = $fetch['items'];
        $importedCount = 0;

        foreach ($wcOrders as $order) {
            $externalId = (string) $order['id'];

            $wcStatus = $order['status'] ?? 'pending';
            $paymentStatus = CanonicalMapper::paymentStatusFromWooCommerce($wcStatus);
            $canonicalStatus = CanonicalMapper::fromWooCommerce($wcStatus);
            $orderStatus = CanonicalMapper::toInternalOrderStatus($canonicalStatus);

            // Pehle se aaya order → sirf status update (pehle yahan kuch bhi nahi hota tha)
            if ($this->importer->exists((int) $store['id'], 'woocommerce_pull', $externalId)) {
                $this->importer->updateStatus((int) $store['id'], 'woocommerce_pull', $externalId, $orderStatus, $paymentStatus);
                continue;
            }

            $billing = $order['billing'] ?? [];
            $customerName = trim(($billing['first_name'] ?? '') . ' ' . ($billing['last_name'] ?? ''));
            if ($customerName === '') {
                $customerName = $billing['email'] ?? 'Unknown';
            }

            $result = $this->importer->import((int) $store['id'], 'woocommerce_pull', $externalId, [
                'customer_name' => $customerName,
                'status' => $orderStatus,
                'payment_status' => $paymentStatus,
                'payment_method' => $order['payment_method_title'] ?? 'Other',
                'customer' => PlatformOrderMapper::customer('woocommerce', $order),
            ], PlatformOrderMapper::wooLines($order));

            if ($result['ok']) {
                $importedCount++;
            }
        }

        $this->storeModel->markHealthy((int) $store['id']);
        $this->redirect('/orders?synced=' . $importedCount);
    }

    // ---------- BigCommerce: Orders Pull ----------

    public function syncBigCommerce(): void
    {
        $store = $this->getCurrentStore();

        if (empty($store['bigcommerce_store_hash']) || empty($store['bigcommerce_access_token'])) {
            $this->redirect('/orders/settings?error=' . urlencode('Please save this store\'s BigCommerce Store Hash and Access Token first.'));
            return;
        }

        // Saare orders, 250-250 kar ke (pehle sirf aakhri 20)
        $fetch = PlatformFetcher::bigCommerceV2($store['bigcommerce_store_hash'], $store['bigcommerce_access_token'], '/v2/orders', 'sort=date_created:desc');

        if (!$fetch['ok']) {
            $httpCode = $fetch['code'];
            $this->storeModel->markUnhealthy((int) $store['id'], "BigCommerce API error (HTTP {$httpCode})");
            $this->redirect('/orders/settings?error=' . urlencode("BigCommerce API error (HTTP {$httpCode})."));
            return;
        }

        $bcOrders = $fetch['items'];
        $importedCount = 0;

        foreach ($bcOrders as $order) {
            $externalId = (string) ($order['id'] ?? '');
            if ($externalId === '') continue;

            [$orderStatus, $paymentStatus] = PlatformOrderMapper::bigCommerceStatus($order['status'] ?? 'Pending');

            if ($this->importer->exists((int) $store['id'], 'bigcommerce_pull', $externalId)) {
                $this->importer->updateStatus((int) $store['id'], 'bigcommerce_pull', $externalId, $orderStatus, $paymentStatus);
                continue;
            }

            $productsUrl = 'https://api.bigcommerce.com/stores/' . $store['bigcommerce_store_hash'] . "/v2/orders/{$externalId}/products";
            $pch = curl_init($productsUrl);
            curl_setopt($pch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($pch, CURLOPT_HTTPHEADER, [
                'X-Auth-Token: ' . $store['bigcommerce_access_token'],
                'Accept: application/json',
            ]);
            curl_setopt($pch, CURLOPT_TIMEOUT, 15);
            $productsResponse = curl_exec($pch);
            curl_close($pch);

            $billing = $order['billing_address'] ?? [];
            $customerName = trim(($billing['first_name'] ?? '') . ' ' . ($billing['last_name'] ?? ''));
            if ($customerName === '') {
                $customerName = $billing['email'] ?? ('Order #' . $externalId);
            }

            $result = $this->importer->import((int) $store['id'], 'bigcommerce_pull', $externalId, [
                'customer_name' => $customerName,
                'status' => $orderStatus,
                'payment_status' => $paymentStatus,
                'payment_method' => $order['payment_method'] ?? null,
                'customer' => PlatformOrderMapper::customer('bigcommerce', $order),
            ], PlatformOrderMapper::bigCommerceLines(json_decode((string) $productsResponse, true) ?? []));

            if ($result['ok']) {
                $importedCount++;
            }
        }

        $this->storeModel->markHealthy((int) $store['id']);
        $this->redirect('/orders?synced=' . $importedCount);
    }

    // ---------- PrestaShop: Orders Pull ----------

    public function syncPrestaShop(): void
    {
        $store = $this->getCurrentStore();

        if (empty($store['prestashop_store_url']) || empty($store['prestashop_api_key'])) {
            $this->redirect('/orders/settings?error=' . urlencode('Please save this store\'s PrestaShop Store URL and API Key first.'));
            return;
        }

        // display=full → order ke saath us ke products (associations → order_rows) bhi aate hain.
        // Pehle sirf order ka total aata tha aur product ka naam "PrestaShop Order #12" ban jata tha,
        // jo Finovo mein kabhi milta hi nahi tha — is liye PrestaShop ke orders import hi nahi hote the.
        $baseUrl = rtrim($store['prestashop_store_url'], '/');
        // Saare orders, 100-100 kar ke (pehle sirf aakhri 20)
        $fetch = PlatformFetcher::prestaShop($baseUrl, 'orders', [
            'ws_key' => $store['prestashop_api_key'],
            'display' => 'full',
            'sort' => '[id_DESC]',
        ]);

        if ($fetch['code'] === 404) {
            $this->storeModel->markHealthy((int) $store['id']);
            $this->redirect('/orders?synced=0');
            return;
        }

        if (!$fetch['ok']) {
            $httpCode = $fetch['code'];
            $this->storeModel->markUnhealthy((int) $store['id'], "PrestaShop API error (HTTP {$httpCode})");
            $this->redirect('/orders/settings?error=' . urlencode("PrestaShop API error (HTTP {$httpCode})."));
            return;
        }

        $psOrders = $fetch['items'];
        $importedCount = 0;

        foreach ($psOrders as $order) {
            $externalId = (string) ($order['id'] ?? '');
            if ($externalId === '') continue;

            $stateId = (int) ($order['current_state'] ?? 0);
            $orderStatus = match (true) {
                in_array($stateId, [5], true) => 'delivered',
                in_array($stateId, [6, 7, 8], true) => 'cancelled',
                in_array($stateId, [2, 3, 4], true) => 'processing',
                default => 'pending',
            };
            $paymentStatus = in_array($stateId, [2, 3, 4, 5], true) ? 'paid' : 'unpaid';

            if ($this->importer->exists((int) $store['id'], 'prestashop_pull', $externalId)) {
                $this->importer->updateStatus((int) $store['id'], 'prestashop_pull', $externalId, $orderStatus, $paymentStatus);
                continue;
            }

            $customerId = (int) ($order['id_customer'] ?? 0);
            $customerName = 'Order #' . $externalId;
            $customerDetails = ['external_customer_id' => $customerId > 0 ? (string) $customerId : ''];

            if ($customerId > 0) {
                $custUrl = $baseUrl . '/api/customers/' . $customerId . '?ws_key=' . urlencode($store['prestashop_api_key']) . '&output_format=JSON&display=[firstname,lastname,email]';
                $cch = curl_init($custUrl);
                curl_setopt($cch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($cch, CURLOPT_TIMEOUT, 10);
                curl_setopt($cch, CURLOPT_SSL_VERIFYPEER, curlVerifySsl());
                $custResponse = curl_exec($cch);
                curl_close($cch);

                $custData = json_decode((string) $custResponse, true);
                $cust = $custData['customer'] ?? [];
                $fullName = trim(($cust['firstname'] ?? '') . ' ' . ($cust['lastname'] ?? ''));
                if ($fullName !== '') {
                    $customerName = $fullName;
                }
                $customerDetails['email'] = (string) ($cust['email'] ?? '');
            }

            // Delivery address (phone bhi isi mein hota hai)
            $addressId = (int) ($order['id_address_delivery'] ?? 0);
            if ($addressId > 0) {
                $addrUrl = $baseUrl . '/api/addresses/' . $addressId . '?ws_key=' . urlencode($store['prestashop_api_key']) . '&output_format=JSON&display=[address1,address2,city,postcode,phone,phone_mobile]';
                $ach = curl_init($addrUrl);
                curl_setopt($ach, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ach, CURLOPT_TIMEOUT, 10);
                curl_setopt($ach, CURLOPT_SSL_VERIFYPEER, curlVerifySsl());
                $addrResponse = curl_exec($ach);
                curl_close($ach);

                $addr = json_decode((string) $addrResponse, true)['address'] ?? [];
                if (is_array($addr) && $addr) {
                    $customerDetails['address'] = PlatformOrderMapper::joinAddress([$addr['address1'] ?? '', $addr['address2'] ?? '', $addr['city'] ?? '', $addr['postcode'] ?? '']);
                    $customerDetails['city'] = (string) ($addr['city'] ?? '');
                    $customerDetails['phone'] = (string) (($addr['phone_mobile'] ?? '') ?: ($addr['phone'] ?? ''));
                }
            }

            $result = $this->importer->import((int) $store['id'], 'prestashop_pull', $externalId, [
                'customer_name' => $customerName,
                'status' => $orderStatus,
                'payment_status' => $paymentStatus,
                'payment_method' => $order['payment'] ?? null,
                'customer' => $customerDetails,
            ], PlatformOrderMapper::prestaShopLines($order));

            if ($result['ok']) {
                $importedCount++;
            }
        }

        $this->storeModel->markHealthy((int) $store['id']);
        $this->redirect('/orders?synced=' . $importedCount);
    }

    // ---------- OpenCart: Orders Pull (direct DB read) ----------

    public function syncOpenCart(): void
    {
        $store = $this->getCurrentStore();

        $ocDbName = $store['opencart_store_url'] ?? '';
        if ($ocDbName === '') {
            $this->redirect('/orders/settings?error=' . urlencode('Please save this store\'s OpenCart database name first.'));
            return;
        }

        try {
            $ocDb = $this->externalDbConnection($ocDbName);
        } catch (PDOException $e) {
            // Database ki andar ki detail (SQLSTATE...) user ko nahi dikhate
            error_log('OpenCart DB connection failed: ' . $e->getMessage());
            $this->storeModel->markUnhealthy((int) $store['id'], 'Could not connect to OpenCart database.');
            $this->redirect('/orders/settings?error=' . urlencode('Could not connect to the OpenCart database. Please check the database name in Stores → Edit.'));
            return;
        }

        // SAARE orders (pehle sirf aakhri 20), phir un ke saare products alag query se.
        // SELECT * — OpenCart ke versions mein columns thode alag hain; jo column na ho us ki wajah se sync na ruke
        @set_time_limit(300);
        $ocOrders = $ocDb->query(
            "SELECT *
             FROM oc_order
             WHERE order_status_id > 0
             ORDER BY order_id DESC"
        )->fetchAll(PDO::FETCH_ASSOC);

        $linesByOrder = [];
        // 500-500 orders ke products ek query mein (bahut lambi IN list se bachne ke liye)
        foreach (array_chunk(array_map(fn($o) => (int) $o['order_id'], $ocOrders), 500) as $ids) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $ocDb->prepare("SELECT order_id, product_id, name, quantity, price, tax FROM oc_order_product WHERE order_id IN ({$placeholders})");
            $stmt->execute($ids);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $op) {
                $linesByOrder[(int) $op['order_id']][] = [
                    'name' => $op['name'] ?? '',
                    'quantity' => (int) ($op['quantity'] ?? 0),
                    'unit_price' => (float) ($op['price'] ?? 0) + (float) ($op['tax'] ?? 0),
                    'external_product_id' => (string) ($op['product_id'] ?? ''),
                ];
            }
        }

        $importedCount = 0;

        foreach ($ocOrders as $order) {
            $externalId = (string) ($order['order_id'] ?? '');
            if ($externalId === '') continue;

            $statusId = (int) ($order['order_status_id'] ?? 0);
            $orderStatus = match (true) {
                in_array($statusId, [5, 3], true) => 'delivered',
                in_array($statusId, [7, 8, 9], true) => 'cancelled',
                in_array($statusId, [2, 10], true) => 'processing',
                default => 'pending',
            };
            $paymentStatus = $statusId >= 2 ? 'paid' : 'unpaid';

            if ($this->importer->exists((int) $store['id'], 'opencart_pull', $externalId)) {
                $this->importer->updateStatus((int) $store['id'], 'opencart_pull', $externalId, $orderStatus, $paymentStatus);
                continue;
            }

            $customerName = trim(($order['firstname'] ?? '') . ' ' . ($order['lastname'] ?? ''));
            if ($customerName === '') {
                $customerName = 'Order #' . $externalId;
            }

            $result = $this->importer->import((int) $store['id'], 'opencart_pull', $externalId, [
                'customer_name' => $customerName,
                'status' => $orderStatus,
                'payment_status' => $paymentStatus,
                'payment_method' => is_string($order['payment_method'] ?? null) ? mb_substr($order['payment_method'], 0, 100) : null,
                'customer' => self::openCartCustomer($order),
            ], $linesByOrder[(int) $externalId] ?? []);

            if ($result['ok']) {
                $importedCount++;
            }
        }

        $this->storeModel->markHealthy((int) $store['id']);
        $this->redirect('/orders?synced=' . $importedCount);
    }

    /** OpenCart order row → customer details (shipping address, na ho to payment/billing address) */
    private static function openCartCustomer(array $order): array
    {
        $prefix = trim((string) ($order['shipping_address_1'] ?? '')) !== '' ? 'shipping_' : 'payment_';
        return [
            'email' => (string) ($order['email'] ?? ''),
            'phone' => (string) ($order['telephone'] ?? ''),
            'address' => PlatformOrderMapper::joinAddress([
                $order[$prefix . 'address_1'] ?? '', $order[$prefix . 'address_2'] ?? '',
                $order[$prefix . 'city'] ?? '', $order[$prefix . 'postcode'] ?? '',
            ]),
            'city' => (string) ($order[$prefix . 'city'] ?? ''),
            'country' => (string) ($order[$prefix . 'country'] ?? ''),
            'external_customer_id' => !empty($order['customer_id']) ? (string) $order['customer_id'] : '',
        ];
    }

    // ---------- osCommerce: Orders Pull (direct DB read) ----------

    public function syncOsCommerce(): void
    {
        $store = $this->getCurrentStore();

        $oscDbName = $store['oscommerce_store_url'] ?? '';
        if ($oscDbName === '') {
            $this->redirect('/orders/settings?error=' . urlencode('Please save this store\'s osCommerce database name first.'));
            return;
        }

        try {
            $oscDb = $this->externalDbConnection($oscDbName);
        } catch (PDOException $e) {
            error_log('osCommerce DB connection failed: ' . $e->getMessage());
            $this->storeModel->markUnhealthy((int) $store['id'], 'Could not connect to osCommerce database.');
            $this->redirect('/orders/settings?error=' . urlencode('Could not connect to the osCommerce database. Please check the database name in Stores → Edit.'));
            return;
        }

        // SAARE orders (pehle sirf aakhri 20), phir un ke saare products alag query se
        // SELECT * — osCommerce ke versions mein columns alag hain (kisi mein customers_firstname hai, kisi mein nahi)
        @set_time_limit(300);
        $oscOrders = $oscDb->query(
            "SELECT *
             FROM orders
             ORDER BY orders_id DESC"
        )->fetchAll(PDO::FETCH_ASSOC);

        $linesByOrder = [];
        foreach (array_chunk(array_map(fn($o) => (int) $o['orders_id'], $oscOrders), 500) as $ids) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $oscDb->prepare("SELECT orders_id, products_id, products_name, products_price, products_quantity FROM orders_products WHERE orders_id IN ({$placeholders})");
            $stmt->execute($ids);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $op) {
                $linesByOrder[(int) $op['orders_id']][] = [
                    'name' => $op['products_name'] ?? '',
                    'quantity' => (int) ($op['products_quantity'] ?? 0),
                    'unit_price' => (float) ($op['products_price'] ?? 0),
                    'external_product_id' => (string) ($op['products_id'] ?? ''),
                ];
            }
        }

        $importedCount = 0;

        foreach ($oscOrders as $order) {
            $externalId = (string) ($order['orders_id'] ?? '');
            if ($externalId === '') continue;

            $statusId = (int) ($order['orders_status'] ?? 0);
            $orderStatus = match ($statusId) {
                3 => 'delivered',
                4 => 'cancelled',
                2 => 'processing',
                default => 'pending',
            };
            $paymentStatus = $statusId >= 2 ? 'paid' : 'unpaid';

            if ($this->importer->exists((int) $store['id'], 'oscommerce_pull', $externalId)) {
                $this->importer->updateStatus((int) $store['id'], 'oscommerce_pull', $externalId, $orderStatus, $paymentStatus);
                continue;
            }

            $customerName = trim($order['customers_name'] ?? '');
            if ($customerName === '') {
                $customerName = trim(($order['customers_firstname'] ?? '') . ' ' . ($order['customers_lastname'] ?? ''));
            }
            if ($customerName === '') {
                $customerName = 'Order #' . $externalId;
            }

            $result = $this->importer->import((int) $store['id'], 'oscommerce_pull', $externalId, [
                'customer_name' => $customerName,
                'status' => $orderStatus,
                'payment_status' => $paymentStatus,
                'customer' => [
                    'email' => (string) ($order['customers_email_address'] ?? ''),
                    'phone' => (string) ($order['customers_telephone'] ?? ''),
                    'address' => PlatformOrderMapper::joinAddress([$order['delivery_street_address'] ?? '', $order['delivery_suburb'] ?? '', $order['delivery_city'] ?? '', $order['delivery_postcode'] ?? '']),
                    'city' => (string) ($order['delivery_city'] ?? ''),
                    'country' => (string) ($order['delivery_country'] ?? ''),
                    'external_customer_id' => !empty($order['customers_id']) ? (string) $order['customers_id'] : '',
                ],
            ], $linesByOrder[(int) $externalId] ?? []);

            if ($result['ok']) {
                $importedCount++;
            }
        }

        $this->storeModel->markHealthy((int) $store['id']);
        $this->redirect('/orders?synced=' . $importedCount);
    }

    // ---------- Wix: Orders Pull ----------

    public function syncWix(): void
    {
        $store = $this->getCurrentStore();

        if (empty($store['wix_site_id']) || empty($store['wix_api_key'])) {
            $this->redirect('/orders/settings?error=' . urlencode('Please save this store\'s Wix Site ID and API Key first.'));
            return;
        }

        $connector = new WixConnector($store['wix_api_key'], $store['wix_site_id']);
        $result = $connector->fetchOrders();

        if (!$result['ok']) {
            $this->storeModel->markUnhealthy((int) $store['id'], $result['message']);
            $this->redirect('/orders/settings?error=' . urlencode($result['message']));
            return;
        }

        $importedCount = 0;

        foreach ($result['orders'] as $order) {
            $externalId = (string) ($order['id'] ?? '');
            if ($externalId === '') continue;

            [$orderStatus, $paymentStatus] = PlatformOrderMapper::wixStatus($order);

            if ($this->importer->exists((int) $store['id'], 'wix_pull', $externalId)) {
                $this->importer->updateStatus((int) $store['id'], 'wix_pull', $externalId, $orderStatus, $paymentStatus);
                continue;
            }

            $import = $this->importer->import((int) $store['id'], 'wix_pull', $externalId, [
                'customer_name' => PlatformOrderMapper::wixCustomerName($order, $externalId),
                'status' => $orderStatus,
                'payment_status' => $paymentStatus,
                'customer' => PlatformOrderMapper::customer('wix', $order),
            ], PlatformOrderMapper::wixLines($order));

            if ($import['ok']) {
                $importedCount++;
            }
        }

        $this->storeModel->markHealthy((int) $store['id']);
        $this->redirect('/orders?synced=' . $importedCount);
    }

    // ---------- eBay: Orders Pull ----------

    public function syncEbay(): void
    {
        $store = $this->getCurrentStore();

        // Connect eBay (OAuth) wala token khud refresh hota hai; purana manual token bhi chalta hai
        $token = EbayAuthService::getValidAccessToken($store);

        if ($token === null) {
            $this->redirect('/orders/settings?error=' . urlencode('This store is not connected to eBay. Use "Connect eBay" on the Stores page (or save an eBay User Token).'));
            return;
        }

        $connector = new EbayConnector($token);
        $result = $connector->fetchOrders();

        if (!$result['ok']) {
            $this->storeModel->markUnhealthy((int) $store['id'], $result['message']);
            $this->redirect('/orders/settings?error=' . urlencode($result['message']));
            return;
        }

        $importer = new EbayOrderImporter();
        $importedCount = $importer->importOrders($store, $result['orders']);

        $this->storeModel->markHealthy((int) $store['id']);
        $this->redirect('/orders?synced=' . $importedCount);
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