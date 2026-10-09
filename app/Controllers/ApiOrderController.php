<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../Models/Store.php';
require_once __DIR__ . '/../Models/Customer.php';
require_once __DIR__ . '/../Models/Product.php';
require_once __DIR__ . '/../Models/Warehouse.php';
require_once __DIR__ . '/../Services/InventoryService.php';

/**
 * POST /api/orders — bahar ka system (apni website, app) Finovo mein order bhejta hai.
 *
 * Pehle yeh OrderController ke andar tha, aur OrderController ka constructor LOGIN maangta hai —
 * is liye API request (jis ke paas login nahi hota) seedha login page pe redirect ho jati thi.
 * Ab alag controller: login nahi, sirf store ki API key (Orders page pe dikhti hai).
 *
 * JSON body:
 *   { "api_key": "...", "store_id": 1, "customer_name": "Ali", "product_name": "Mug",
 *     "quantity": 2, "price": 500, "payment_method": "COD",
 *     "customer_email": "...", "customer_phone": "...", "shipping_address": "..." }
 */
class ApiOrderController extends Controller
{
    private const PAYMENT_METHODS = ['COD', 'Bank Transfer', 'JazzCash', 'Easypaisa', 'Card', 'Other'];

    private function reply(int $code, array $body): void
    {
        http_response_code($code);
        echo json_encode($body);
    }

    public function create(): void
    {
        header('Content-Type: application/json');

        $data = json_decode((string) file_get_contents('php://input'), true);
        if (!is_array($data)) {
            $this->reply(400, ['error' => 'Invalid JSON.']);
            return;
        }

        $apiKey = (string) ($data['api_key'] ?? '');
        $storeId = (int) ($data['store_id'] ?? 0);
        if ($storeId <= 0) {
            $this->reply(422, ['error' => 'store_id is required.']);
            return;
        }

        $store = (new Store())->find($storeId);
        // Store na mile ya key ghalat — dono ka ek hi jawab (bahar wale ko pata na chale kaunsa store id asli hai)
        // hash_equals: time-safe compare
        if (!$store || $apiKey === '' || empty($store['api_key']) || !hash_equals((string) $store['api_key'], $apiKey)) {
            $this->reply(401, ['error' => 'Invalid or missing api_key.']);
            return;
        }

        $customerName = trim((string) ($data['customer_name'] ?? ''));
        $productName = trim((string) ($data['product_name'] ?? ''));
        $quantity = (int) ($data['quantity'] ?? 1);
        $price = (float) ($data['price'] ?? 0);
        $paymentMethod = trim((string) ($data['payment_method'] ?? ''));
        if (!in_array($paymentMethod, self::PAYMENT_METHODS, true)) {
            $paymentMethod = 'COD';
        }

        // Quantity 0 ya minus ho to "reserve" asal mein stock BADHA deta — is liye 1 se kam mana
        if ($customerName === '' || $productName === '' || $price <= 0 || $quantity <= 0) {
            $this->reply(422, ['error' => 'Missing or invalid order fields (customer_name, product_name, price > 0, quantity >= 1).']);
            return;
        }

        $productModel = new Product();
        $defaultWarehouse = (new Warehouse())->getDefault($storeId);
        if (empty($defaultWarehouse['id'])) {
            $this->reply(409, ['error' => 'This store has no warehouse yet.']);
            return;
        }

        $existingProduct = $productModel->findByName($storeId, $productName);
        $productId = $existingProduct ? (int) $existingProduct['id'] : $productModel->findOrCreate($storeId, $productName, $price);

        // Variant wale product ka stock variant pe hota hai — API mein variant nahi aata
        if ($productModel->hasVariants($productId)) {
            $this->reply(422, ['error' => 'This product has variants (sizes / colours). Orders for it cannot be created through this API.']);
            return;
        }

        $db = Database::getConnection();
        $db->beginTransaction();
        try {
            $result = (new InventoryService())->reserve($productId, null, (int) $defaultWarehouse['id'], $quantity, 'api_push', "store:{$storeId}", 'api');
            if (!$result['ok']) {
                $db->rollBack();
                $this->reply(409, ['error' => 'Product out of stock.', 'requested' => $result['requested'] ?? $quantity, 'available' => $result['available'] ?? 0]);
                return;
            }

            $customerDetails = [
                'email' => (string) ($data['customer_email'] ?? ''),
                'phone' => (string) ($data['customer_phone'] ?? ''),
                'address' => (string) ($data['shipping_address'] ?? ''),
            ];
            $customerId = (new Customer())->upsertFromOrder($storeId, $customerName, $customerDetails, 'api_push');

            $stmt = $db->prepare(
                "INSERT INTO orders (store_id, customer_name, product_name, quantity, price, source, product_id, warehouse_id, payment_method,
                                     customer_id, customer_email, customer_phone, shipping_address)
                 VALUES (?, ?, ?, ?, ?, 'api_push', ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $storeId, $customerName, $productName, $quantity, $price, $productId, (int) $defaultWarehouse['id'], $paymentMethod,
                $customerId,
                Customer::cleanEmail($customerDetails['email']),
                mb_substr(trim($customerDetails['phone']), 0, 50) ?: null,
                trim($customerDetails['address']) ?: null,
            ]);
            $orderId = (int) $db->lastInsertId();

            // Ledger mein "store:1" ki jagah asal order number
            $db->prepare("UPDATE inventory_ledger SET reference = ? WHERE reference = ? AND source = 'api_push' AND product_id = ? ORDER BY id DESC LIMIT 1")
                ->execute(["order:{$orderId}", "store:{$storeId}", $productId]);

            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log('API order create failed: ' . $e->getMessage());
            $this->reply(500, ['error' => 'Order could not be created.']);
            return;
        }

        $this->reply(201, ['success' => true, 'order_id' => $orderId, 'message' => 'Order created successfully.']);
    }
}