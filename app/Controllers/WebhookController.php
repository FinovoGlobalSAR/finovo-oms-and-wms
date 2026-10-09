<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../Models/Store.php';
require_once __DIR__ . '/../Models/Product.php';
require_once __DIR__ . '/../Models/Warehouse.php';
require_once __DIR__ . '/../Services/InventoryService.php';
require_once __DIR__ . '/../Middlewares/CanonicalMapper.php';
require_once __DIR__ . '/../Connectors/WixConnector.php';
require_once __DIR__ . '/../Services/OrderImportService.php';
require_once __DIR__ . '/../Services/PlatformOrderMapper.php';

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
    private OrderImportService $importer;

    public function __construct()
    {
        parent::__construct();
        $this->storeModel = new Store();
        $this->productModel = new Product();
        $this->warehouseModel = new Warehouse();
        $this->inventoryService = new InventoryService();
        $this->importer = new OrderImportService();
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

        $this->processEvent($storeId, 'shopify', $externalId, $eventType, fn() => $this->ingestShopifyOrder($store, $data, $externalId));
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
        // Pehle se aaya order (orders/updated, orders/cancelled) → status update, Sync jaisa hi qaida.
        // Pehle yahan kuch nahi hota tha — Shopify pe cancel hua order Finovo mein stock pakde rehta tha.
        if ($this->importer->exists((int) $store['id'], 'shopify_pull', $externalId)) {
            $financialStatus = $order['financial_status'] ?? 'pending';
            $status = CanonicalMapper::toInternalOrderStatus(CanonicalMapper::fromShopify($order['fulfillment_status'] ?? 'pending', $financialStatus));
            if (!empty($order['cancelled_at'])) {
                $status = 'cancelled';
            }
            $this->importer->updateStatus((int) $store['id'], 'shopify_pull', $externalId, $status, CanonicalMapper::paymentStatusFromShopify($financialStatus));
            return;
        }

        $customerName = trim(($order['customer']['first_name'] ?? '') . ' ' . ($order['customer']['last_name'] ?? ''));
        if ($customerName === '') {
            $customerName = $order['email'] ?? ('Order #' . ($order['order_number'] ?? $order['id']));
        }

        // Webhook mode: product na ho to ban jata hai, stock kam ho to "stock warning" ke saath aata hai (pehle jaisa)
        $this->importer->import((int) $store['id'], 'shopify_pull', $externalId, [
            'customer_name' => $customerName,
            'status' => 'processing',
            'payment_status' => CanonicalMapper::paymentStatusFromShopify($order['financial_status'] ?? 'pending'),
            'payment_method' => $order['gateway'] ?? 'Card',
            'customer' => PlatformOrderMapper::customer('shopify', $order),
        ], PlatformOrderMapper::shopifyLines($order), 'webhook', 'webhook');
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

        $this->processEvent($storeId, 'woocommerce', $externalId, $eventType, fn() => $this->ingestWooOrder($store, $data, $externalId));
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
        // Pehle se aaya order (order.updated) → status update, Sync jaisa hi qaida
        if ($this->importer->exists((int) $store['id'], 'woocommerce_pull', $externalId)) {
            $wcStatus = $order['status'] ?? 'pending';
            $this->importer->updateStatus(
                (int) $store['id'],
                'woocommerce_pull',
                $externalId,
                CanonicalMapper::toInternalOrderStatus(CanonicalMapper::fromWooCommerce($wcStatus)),
                CanonicalMapper::paymentStatusFromWooCommerce($wcStatus)
            );
            return;
        }

        $billing = $order['billing'] ?? [];
        $customerName = trim(($billing['first_name'] ?? '') . ' ' . ($billing['last_name'] ?? ''));
        if ($customerName === '') {
            $customerName = $billing['email'] ?? 'Unknown';
        }

        $this->importer->import((int) $store['id'], 'woocommerce_pull', $externalId, [
            'customer_name' => $customerName,
            'status' => 'processing',
            'payment_status' => CanonicalMapper::paymentStatusFromWooCommerce($order['status'] ?? 'pending'),
            'payment_method' => $order['payment_method_title'] ?? 'Other',
            'customer' => PlatformOrderMapper::customer('woocommerce', $order),
        ], PlatformOrderMapper::wooLines($order), 'webhook', 'webhook');
    }

    // ---------- BigCommerce Webhook ----------

    public function bigcommerce(): void
    {
        $storeId = (int) ($_GET['store_id'] ?? 0);
        $store = $this->storeModel->find($storeId);

        if (!$store || empty($store['bigcommerce_access_token'])) {
            http_response_code(404);
            echo json_encode(['error' => 'Unknown store or BigCommerce not configured.']);
            return;
        }

        $rawBody = file_get_contents('php://input');
        $data = json_decode($rawBody, true);

        if (!$data || empty($data['data']['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid payload.']);
            return;
        }

        $externalId = (string) $data['data']['id'];
        $eventType = $data['scope'] ?? 'store/order/created';

        $this->processEvent($storeId, 'bigcommerce', $externalId, $eventType, fn() => $this->ingestBigCommerceOrder($store, $externalId));
    }

    private function ingestBigCommerceOrder(array $store, string $externalId): void
    {
        if ($this->importer->exists((int) $store['id'], 'bigcommerce_pull', $externalId)) {
            return;
        }

        $apiUrl = 'https://api.bigcommerce.com/stores/' . $store['bigcommerce_store_hash'] . "/v2/orders/{$externalId}";
        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-Auth-Token: ' . $store['bigcommerce_access_token'], 'Accept: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $response = curl_exec($ch);
        curl_close($ch);

        $order = json_decode((string) $response, true);
        if (!$order) {
            return;
        }

        $productsUrl = 'https://api.bigcommerce.com/stores/' . $store['bigcommerce_store_hash'] . "/v2/orders/{$externalId}/products";
        $pch = curl_init($productsUrl);
        curl_setopt($pch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($pch, CURLOPT_HTTPHEADER, ['X-Auth-Token: ' . $store['bigcommerce_access_token'], 'Accept: application/json']);
        curl_setopt($pch, CURLOPT_TIMEOUT, 15);
        $productsResponse = curl_exec($pch);
        curl_close($pch);

        $billing = $order['billing_address'] ?? [];
        $customerName = trim(($billing['first_name'] ?? '') . ' ' . ($billing['last_name'] ?? ''));
        if ($customerName === '') {
            $customerName = $billing['email'] ?? ('Order #' . $externalId);
        }

        [, $paymentStatus] = PlatformOrderMapper::bigCommerceStatus($order['status'] ?? 'Pending');

        $this->importer->import((int) $store['id'], 'bigcommerce_pull', $externalId, [
            'customer_name' => $customerName,
            'status' => 'processing',
            'payment_status' => $paymentStatus,
            'payment_method' => $order['payment_method'] ?? null,
            'customer' => PlatformOrderMapper::customer('bigcommerce', $order),
        ], PlatformOrderMapper::bigCommerceLines(json_decode((string) $productsResponse, true) ?? []), 'webhook', 'webhook');
    }

    // ---------- Wix Webhook ----------

    public function wix(): void
    {
        $storeId = (int) ($_GET['store_id'] ?? 0);
        $store = $this->storeModel->find($storeId);

        if (!$store) {
            http_response_code(404);
            echo json_encode(['error' => 'Unknown store.']);
            return;
        }

        $rawBody = trim(file_get_contents('php://input'));

        // Hifazat: (1) .env mein WIX_WEBHOOK_PUBLIC_KEY ho to JWT ka signature check (RS256).
        // (2) Hamesha: webhook sirf order ka ID deta hai — asli order Wix API se store ki apni key se
        //     laate hain. Pehle webhook ka data seedha maan liya jata tha, is liye koi bhi
        //     /webhooks/wix?store_id=1 pe fake order bhej kar stock kata sakta tha.
        if (!$this->verifyWixJwt($rawBody)) {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid signature.']);
            return;
        }
        if (empty($store['wix_api_key']) || empty($store['wix_site_id'])) {
            http_response_code(404);
            echo json_encode(['error' => 'Wix is not connected for this store.']);
            return;
        }

        // Wix webhooks JWT format mein aate hain (header.payload.signature).
        // JWT payload ka "data" field JSON-string hai, aur uske andar bhi
        // ek "data" field hota hai jo AGAIN JSON-string hai — 2 baar nested.
        // Sirf tab jaake genuine order (entityId + createdEvent) milta hai.
        $jwtParts = explode('.', $rawBody);
        $order = null;
        $externalId = '';

        if (count($jwtParts) === 3) {
            $b64 = strtr($jwtParts[1], '-_', '+/');
            $remainder = strlen($b64) % 4;
            if ($remainder > 0) {
                $b64 .= str_repeat('=', 4 - $remainder);
            }
            $payload = json_decode(base64_decode($b64), true);

            if (is_array($payload) && isset($payload['data'])) {
                $level1 = json_decode($payload['data'], true);
                $level2 = (is_array($level1) && isset($level1['data']))
                    ? json_decode($level1['data'], true)
                    : $level1;

                if (is_array($level2)) {
                    $order = $level2['createdEvent']['entity'] ?? null;
                    $externalId = (string) ($level2['entityId'] ?? $order['id'] ?? '');
                }
            }
        }

        if ($externalId === '') {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid payload.']);
            return;
        }

        // Asli order Wix se (webhook wala $order sirf ID ke liye tha)
        $fetched = (new WixConnector($store['wix_api_key'], $store['wix_site_id']))->fetchOrder($externalId);
        if (!$fetched['ok']) {
            http_response_code(404);
            echo json_encode(['error' => 'Order not found on Wix.']);
            return;
        }
        $order = $fetched['order'];

        $this->processEvent($storeId, 'wix', $externalId, 'OrderCreated', fn() => $this->ingestWixOrderFromPayload($store, $order, $externalId));
    }

    /**
     * Wix JWT (RS256) ka signature — sirf tab check jab .env mein WIX_WEBHOOK_PUBLIC_KEY ho
     * (Wix app dashboard → Webhooks → "Public key"). Key na ho to true (phir bhi order Wix API se hi aata hai).
     */
    private function verifyWixJwt(string $rawBody): bool
    {
        $publicKey = trim((string) env('WIX_WEBHOOK_PUBLIC_KEY', ''));
        if ($publicKey === '') {
            return true;
        }
        $parts = explode('.', $rawBody);
        if (count($parts) !== 3) {
            return false;
        }
        $signature = base64_decode(strtr($parts[2], '-_', '+/') . str_repeat('=', (4 - strlen($parts[2]) % 4) % 4));
        $key = openssl_pkey_get_public(str_replace('\\n', "\n", $publicKey));
        return $key !== false && openssl_verify($parts[0] . '.' . $parts[1], $signature, $key, OPENSSL_ALGO_SHA256) === 1;
    }

    private function ingestWixOrderFromPayload(array $store, array $order, string $externalId): void
    {
        if ($this->importer->exists((int) $store['id'], 'wix_pull', $externalId)) {
            return;
        }

        [, $paymentStatus] = PlatformOrderMapper::wixStatus($order);

        $this->importer->import((int) $store['id'], 'wix_pull', $externalId, [
            'customer_name' => PlatformOrderMapper::wixCustomerName($order, $externalId),
            'status' => 'processing',
            'payment_status' => $paymentStatus,
            'customer' => PlatformOrderMapper::customer('wix', $order),
        ], PlatformOrderMapper::wixLines($order), 'webhook', 'webhook');
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

        $this->processEvent($storeId, 'custom_bridge', $externalId, $eventType, fn() => $this->ingestBridgeOrder($store, $data, $externalId));
    }

    private function verifyBridgeSignature(array $store, string $apiKey, string $timestamp, string $nonce, string $signature, string $rawBody): bool
    {
        // hash_equals: time-safe compare (seedha !== se key ka andaza lagaya ja sakta hai)
        if ($apiKey === '' || !hash_equals((string) $store['bridge_api_key'], $apiKey)) {
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
        if ($this->importer->exists((int) $store['id'], 'custom_bridge_pull', $externalId)) {
            return;
        }

        $canonicalStatus = CanonicalMapper::fromCustomBridge($order['status'] ?? 'pending');

        // Ek order mein kai products: "items" list (naya), ya purana ek-product format — dono chalte hain
        $this->importer->import((int) $store['id'], 'custom_bridge_pull', $externalId, [
            'customer_name' => $order['customer_name'] ?? 'Unknown',
            'status' => CanonicalMapper::toInternalOrderStatus($canonicalStatus),
            'payment_status' => 'unpaid',
            'customer' => PlatformOrderMapper::customer('bridge', $order),
        ], PlatformOrderMapper::bridgeLines($order), 'webhook', 'webhook');
    }

    // ---------- Shared: Idempotency / Replay Protection ----------

    /**
     * Webhook ka kaam ek jagah:
     *   - Order pehle se Finovo mein hai (status update wala event) → seedha update. Status update dobara chale
     *     to bhi koi nuqsan nahi; pehle "orders/updated" ek dafa record hone ke baad us order ke AGLE saare
     *     updates (jaise baad mein cancel) "already processed" keh kar chhoot jate the.
     *   - Naya order → event record (platform ka duplicate retry ruk jaye), phir import.
     *     Import mein error aaye → record hata kar 500 (platform dobara bhejega).
     *     Order import na ho saka (product / stock nahi) → record hata do, taake product sync ke baad
     *     agla event ya retry is order ko la sake. Pehle record pehle hota tha aur fail hone pe order hamesha ke liye ruk jata.
     */
    private function processEvent(int $storeId, string $source, string $externalId, string $eventType, callable $ingest): void
    {
        $pullSource = $source . '_pull';

        if ($this->importer->exists($storeId, $pullSource, $externalId)) {
            $ingest();
            http_response_code(200);
            echo json_encode(['status' => 'ok']);
            return;
        }

        if (!$this->recordWebhookEvent($storeId, $source, $externalId, $eventType)) {
            http_response_code(200);
            echo json_encode(['status' => 'already_processed']);
            return;
        }

        try {
            $ingest();
        } catch (Throwable $e) {
            $this->forgetWebhookEvent($storeId, $source, $externalId, $eventType);
            error_log("Webhook {$source} order {$externalId} failed: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Order could not be processed. Please retry.']);
            return;
        }

        if (!$this->importer->exists($storeId, $pullSource, $externalId)) {
            $this->forgetWebhookEvent($storeId, $source, $externalId, $eventType);
        }

        http_response_code(200);
        echo json_encode(['status' => 'ok']);
    }

    private function forgetWebhookEvent(int $storeId, string $source, string $externalId, string $eventType): void
    {
        Database::getConnection()
            ->prepare("DELETE FROM webhook_events WHERE store_id = ? AND source = ? AND external_id = ? AND event_type = ?")
            ->execute([$storeId, $source, $externalId, $eventType]);
    }

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