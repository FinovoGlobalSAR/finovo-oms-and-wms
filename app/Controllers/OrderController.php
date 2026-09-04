<?php
// app/Controllers/OrderController.php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../Models/Order.php';

class OrderController extends Controller
{
    private Order $orderModel;

    public function __construct()
    {
        $this->orderModel = new Order();
    }

    public function index(): void
    {
        $db = Database::getConnection();
        $settings = $db->query("SELECT api_key FROM settings LIMIT 1")->fetch();
        $orders = $this->orderModel->all();

        $this->view('orders/index', ['orders' => $orders, 'apiKey' => $settings['api_key']]);
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
}