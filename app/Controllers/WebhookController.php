<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../Models/Store.php';
require_once __DIR__ . '/../Models/Product.php';
require_once __DIR__ . '/../Models/Warehouse.php';
require_once __DIR__ . '/../Services/InventoryService.php';
require_once __DIR__ . '/../Middleware/CanonicalMapper.php';

/**
 * Webhooks external duniya se aate hain — koi login session nahi hota.
 * Isliye ye Controller extend to karta hai (DB/helpers ke liye) lekin
 * requireLogin/requireRole KABHI call nahi karta.
 */
class WebhookController extends Controller
{
    private Store $storeModel;
    private Product $productModel;
    private Warehouse $warehouseModel;
    private InventoryService $inventoryService;

    public function __construct()
    {
        parent::__construct();
        $this->storeModel = new Store();
        $this->productModel = new Product();
        $this->warehouseModel = new Warehouse();
        $this->inventoryService = new InventoryService();
    }

    // ---------- Shopify Webhook ----------

    public function shopify(): void
    {
        $storeId = (int) ($_GET['store_id'] ?? 0);
        $store = $this->storeModel->find($storeId);

        if (!$store || empty($store['shopify_webhook_secret'])) {
            http_response_code(404);
            echo json_encode(['error' => 'Unknown store or webhook not configured.']);
            return;
        }

        $rawBody = file_get_contents('php://input');
        $signature = $_SERVER['HTTP_X_SHOPIFY_HMAC_SHA256'] ?? '';

        if (!$this->verifyShopifySignature($rawBody, $signature, $store['shopify_webhook_secret'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid signature.']);
            return;
        }

        $data = json_decode($rawBody, true);
        if (!$data || empty($data['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid payload.']);
            return;
        }

        $externalId = (string) $data['id'];
        $eventType = $_SERVER['HTTP_X_SHOPIFY_TOPIC'] ?? 'orders/create';

        if (!$this->recordWebhookEvent($storeId, 'shopify', $externalId, $eventType)) {
            http_response_code(200);
            echo json_encode(['status' => 'already_processed']);
            return;
        }

        $this->ingestShopifyOrder($store, $data, $externalId);

        http_response_code(200);
        echo json_encode(['status' => 'ok']);
    }

    private function verifyShopifySignature(string $rawBody, string $signature, string $secret): bool
    {
        if ($signature === '') {
            return false;
        }
        $computed = base64_encode(hash_hmac('sha256', $rawBody, $secret, true));
        return hash_equals($computed, $signature);
    }

    private function ingestShopifyOrder(array $store, array $order, string $externalId): void
    {
        $db = Database::getConnection();
        $defaultWarehouse = $this->warehouseModel->getDefault((int) $store['id']);

        $check = $db->prepare("SELECT id FROM orders WHERE external_order_id = ? AND store_id = ?");
        $check->execute([$externalId, $store['id']]);
        if ($check->fetch()) {
            return;
        }

        $customerName = trim(($order['customer']['first_name'] ?? '') . ' ' . ($order['customer']['last_name'] ?? ''));
        if ($customerName === '') {
            $customerName = $order['email'] ?? ('Order #' . ($order['order_number'] ?? $order['id']));
        }

        $productName = $order['line_items'][0]['name'] ?? 'Unknown product';
        $quantity = $order['line_items'][0]['quantity'] ?? 1;
        $price = (float) ($order['total_price'] ?? 0);
        $gateway = $order['gateway'] ?? 'Card';

        $financialStatus = $order['financial_status'] ?? 'pending';
        $paymentStatus = CanonicalMapper::paymentStatusFromShopify($financialStatus);

        $productId = $this->productModel->findOrCreate((int) $store['id'], $productName, $price);

        $result = $this->inventoryService->reserve($productId, null, (int) $defaultWarehouse['id'], $quantity, 'shopify_webhook', "external:{$externalId}", 'webhook');
        $stockWarning = $result['ok'] ? 0 : 1;

        $stmt = $db->prepare(
            "INSERT INTO orders (store_id, customer_name, product_name, quantity, price, source, external_order_id, status, payment_status, product_id, warehouse_id, stock_warning, payment_method)
             VALUES (?, ?, ?, ?, ?, 'shopify_pull', ?, 'processing', ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$store['id'], $customerName, $productName, $quantity, $price, $externalId, $paymentStatus, $productId, $defaultWarehouse['id'], $stockWarning, $gateway]);
    }

    // ---------- WooCommerce Webhook ----------

    public function woocommerce(): void
    {
        $storeId = (int) ($_GET['store_id'] ?? 0);
        $store = $this->storeModel->find($storeId);

        if (!$store || empty($store['woocommerce_webhook_secret'])) {
            http_response_code(404);
            echo json_encode(['error' => 'Unknown store or webhook not configured.']);
            return;
        }

        $rawBody = file_get_contents('php://input');
        $signature = $_SERVER['HTTP_X_WC_WEBHOOK_SIGNATURE'] ?? '';

        if (!$this->verifyWooSignature($rawBody, $signature, $store['woocommerce_webhook_secret'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid signature.']);
            return;
        }

        $data = json_decode($rawBody, true);
        if (!$data || empty($data['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid payload.']);
            return;
        }

        $externalId = (string) $data['id'];
        $eventType = $_SERVER['HTTP_X_WC_WEBHOOK_TOPIC'] ?? 'order.created';

        if (!$this->recordWebhookEvent($storeId, 'woocommerce', $externalId, $eventType)) {
            http_response_code(200);
            echo json_encode(['status' => 'already_processed']);
            return;
        }

        $this->ingestWooOrder($store, $data, $externalId);

        http_response_code(200);
        echo json_encode(['status' => 'ok']);
    }

    private function verifyWooSignature(string $rawBody, string $signature, string $secret): bool
    {
        if ($signature === '') {
            return false;
        }
        $computed = base64_encode(hash_hmac('sha256', $rawBody, $secret, true));
        return hash_equals($computed, $signature);
    }

    private function ingestWooOrder(array $store, array $order, string $externalId): void
    {
        $db = Database::getConnection();
        $defaultWarehouse = $this->warehouseModel->getDefault((int) $store['id']);

        $check = $db->prepare("SELECT id FROM orders WHERE external_wc_order_id = ? AND store_id = ?");
        $check->execute([$externalId, $store['id']]);
        if ($check->fetch()) {
            return;
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

        $productId = $this->productModel->findOrCreate((int) $store['id'], $productName, $price);

        $result = $this->inventoryService->reserve($productId, null, (int) $defaultWarehouse['id'], $quantity, 'woocommerce_webhook', "external:{$externalId}", 'webhook');
        $stockWarning = $result['ok'] ? 0 : 1;

        $stmt = $db->prepare(
            "INSERT INTO orders (store_id, customer_name, product_name, quantity, price, source, external_wc_order_id, status, payment_status, product_id, warehouse_id, stock_warning, payment_method)
             VALUES (?, ?, ?, ?, ?, 'woocommerce_pull', ?, 'processing', ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$store['id'], $customerName, $productName, $quantity, $price, $externalId, $paymentStatus, $productId, $defaultWarehouse['id'], $stockWarning, $paymentMethodTitle]);
    }

    // ---------- Custom Bridge Webhook ----------

    public function customBridge(): void
    {
        $storeId = (int) ($_GET['store_id'] ?? 0);
        $store = $this->storeModel->find($storeId);

        if (!$store || empty($store['bridge_api_key']) || empty($store['bridge_shared_secret'])) {
            http_response_code(404);
            echo json_encode(['error' => 'Unknown store or bridge not configured.']);
            return;
        }

        $apiKey = $_SERVER['HTTP_X_BRIDGE_API_KEY'] ?? '';
        $timestamp = $_SERVER['HTTP_X_BRIDGE_TIMESTAMP'] ?? '';
        $nonce = $_SERVER['HTTP_X_BRIDGE_NONCE'] ?? '';
        $signature = $_SERVER['HTTP_X_BRIDGE_SIGNATURE'] ?? '';
        $rawBody = file_get_contents('php://input');

        if (!$this->verifyBridgeSignature($store, $apiKey, $timestamp, $nonce, $signature, $rawBody)) {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid or expired signature.']);
            return;
        }

        $data = json_decode($rawBody, true);
        if (!$data || empty($data['order_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid payload — order_id is required.']);
            return;
        }

        $externalId = (string) $data['order_id'];
        $eventType = 'order.created';

        if (!$this->recordWebhookEvent($storeId, 'custom_bridge', $externalId, $eventType)) {
            http_response_code(200);
            echo json_encode(['status' => 'already_processed']);
            return;
        }

        $this->ingestBridgeOrder($store, $data, $externalId);

        http_response_code(200);
        echo json_encode(['status' => 'ok']);
    }

    private function verifyBridgeSignature(array $store, string $apiKey, string $timestamp, string $nonce, string $signature, string $rawBody): bool
    {
        if ($apiKey === '' || $apiKey !== $store['bridge_api_key']) {
            return false;
        }

        if (abs(time() - (int) $timestamp) > 300) {
            return false;
        }

        $expectedSignature = hash_hmac('sha256', $timestamp . $nonce . $rawBody, $store['bridge_shared_secret']);

        return hash_equals($expectedSignature, $signature);
    }

    private function ingestBridgeOrder(array $store, array $order, string $externalId): void
    {
        $db = Database::getConnection();
        $defaultWarehouse = $this->warehouseModel->getDefault((int) $store['id']);

        $check = $db->prepare("SELECT id FROM orders WHERE external_order_id = ? AND store_id = ?");
        $check->execute([$externalId, $store['id']]);
        if ($check->fetch()) {
            return;
        }

        $customerName = $order['customer_name'] ?? 'Unknown';
        $externalProductId = (string) ($order['product_id'] ?? '');
        $quantity = (int) ($order['quantity'] ?? 1);
        $price = (float) ($order['total_price'] ?? 0);

        $bridgeStatus = $order['status'] ?? 'pending';
        $canonicalStatus = CanonicalMapper::fromCustomBridge($bridgeStatus);
        $status = CanonicalMapper::toInternalOrderStatus($canonicalStatus);

        $productCheck = $db->prepare("SELECT id FROM products WHERE store_id = ? AND external_product_id = ?");
        $productCheck->execute([$store['id'], $externalProductId]);
        $productRow = $productCheck->fetch();

        if ($productRow) {
            $productId = (int) $productRow['id'];
        } else {
            $productId = $this->productModel->findOrCreate((int) $store['id'], 'Bridge Product #' . $externalProductId, $price, $externalProductId);
        }

        $result = $this->inventoryService->reserve($productId, null, (int) $defaultWarehouse['id'], $quantity, 'custom_bridge_webhook', "external:{$externalId}", 'webhook');
        $stockWarning = $result['ok'] ? 0 : 1;

        $stmt = $db->prepare(
            "INSERT INTO orders (store_id, customer_name, product_name, quantity, price, source, external_order_id, status, product_id, warehouse_id, stock_warning)
             VALUES (?, ?, ?, ?, ?, 'custom_bridge_pull', ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$store['id'], $customerName, 'Bridge Product #' . $externalProductId, $quantity, $price, $externalId, $status, $productId, $defaultWarehouse['id'], $stockWarning]);
    }

    // ---------- Shared: Idempotency / Replay Protection ----------

    private function recordWebhookEvent(int $storeId, string $source, string $externalId, string $eventType): bool
    {
        $db = Database::getConnection();
        try {
            $stmt = $db->prepare(
                "INSERT INTO webhook_events (store_id, source, external_id, event_type) VALUES (?, ?, ?, ?)"
            );
            $stmt->execute([$storeId, $source, $externalId, $eventType]);
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
}