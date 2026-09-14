<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../Models/ReturnRequest.php';
require_once __DIR__ . '/../Models/Product.php';
require_once __DIR__ . '/../Models/ProductVariant.php';

class ReturnController extends Controller
{
    private ReturnRequest $returnModel;
    private Product $productModel;
    private ProductVariant $variantModel;

    public function __construct()
    {
        parent::__construct();
        $this->requireRole(['admin', 'manager', 'sales staff']);
        $this->returnModel = new ReturnRequest();
        $this->productModel = new Product();
        $this->variantModel = new ProductVariant();
    }

    public function index(): void
    {
        $store = $this->getCurrentStore();
        $db = Database::getConnection();

        $stmt = $db->prepare("SELECT * FROM orders WHERE store_id = ? ORDER BY id DESC LIMIT 100");
        $stmt->execute([(int) $store['id']]);
        $recentOrders = $stmt->fetchAll();

        $this->view('returns/index', [
            'returns' => $this->returnModel->all((int) $store['id']),
            'recentOrders' => $recentOrders,
            'statuses' => ReturnRequest::$statuses,
            'conditions' => ReturnRequest::$conditions,
            'created' => $_GET['created'] ?? null,
            'updated' => $_GET['updated'] ?? null,
            'error' => $_GET['error'] ?? null,
        ]);
    }

    public function create(): void
    {
        $store = $this->getCurrentStore();
        $db = Database::getConnection();

        $orderId = (int) ($_POST['order_id'] ?? 0);
        $quantity = (int) ($_POST['quantity'] ?? 0);
        $reason = trim($_POST['reason'] ?? '');
        $refundAmount = (float) ($_POST['refund_amount'] ?? 0);

        if ($orderId <= 0 || $quantity <= 0) {
            $this->redirect('/returns?error=' . urlencode('Please select an order and a valid quantity.'));
            return;
        }

        $stmt = $db->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();

        if (!$order) {
            $this->redirect('/returns?error=' . urlencode('Order not found.'));
            return;
        }

        $warehouseId = (int) ($order['warehouse_id'] ?? 0);

        $this->returnModel->create(
            (int) $store['id'],
            $orderId,
            $order['product_id'] ? (int) $order['product_id'] : null,
            $order['variant_id'] ? (int) $order['variant_id'] : null,
            $warehouseId,
            $quantity,
            $reason ?: null,
            $refundAmount
        );

        $this->redirect('/returns?created=1');
    }

    public function updateStatus(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        $status = trim($_POST['status'] ?? '');
        $condition = trim($_POST['condition_status'] ?? 'resellable');

        $return = $this->returnModel->find($id);

        if (!$return) {
            $this->redirect('/returns?error=' . urlencode('Return not found.'));
            return;
        }

        // Jab return "completed" ho aur product resellable ho, tabhi stock wapas warehouse mein jaye
        if ($status === 'completed' && $return['status'] !== 'completed' && $condition === 'resellable') {
            if (!empty($return['variant_id'])) {
                $this->variantModel->increaseWarehouseStock((int) $return['variant_id'], (int) $return['warehouse_id'], (int) $return['quantity']);
            } elseif (!empty($return['product_id'])) {
                $this->productModel->increaseWarehouseStock((int) $return['product_id'], (int) $return['warehouse_id'], (int) $return['quantity']);
            }
        }

        $this->returnModel->updateStatus($id, $status, $condition);
        $this->redirect('/returns?updated=1');
    }
}