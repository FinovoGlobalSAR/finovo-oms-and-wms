<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../Models/ReturnRequest.php';
require_once __DIR__ . '/../Models/Product.php';
require_once __DIR__ . '/../Models/ProductVariant.php';
require_once __DIR__ . '/../Services/InventoryService.php';

class ReturnController extends Controller
{
    private ReturnRequest $returnModel;
    private Product $productModel;
    private ProductVariant $variantModel;
    private InventoryService $inventoryService;

    private array $externalSources = ['shopify_pull', 'woocommerce_pull', 'bigcommerce_pull', 'prestashop_pull', 'opencart_pull', 'oscommerce_pull'];

    public function __construct()
    {
        parent::__construct();
        $this->requireRole(['admin', 'manager', 'sales staff']);
        $this->requireStoreContext();
        $this->returnModel = new ReturnRequest();
        $this->productModel = new Product();
        $this->variantModel = new ProductVariant();
        $this->inventoryService = new InventoryService();
    }

    public function index(): void
    {
        $store = $this->getCurrentStore();
        $db = Database::getConnection();

        $alreadyReturned = $this->returnModel->returnedOrderIds((int) $store['id']);

        $stmt = $db->prepare("SELECT * FROM orders WHERE store_id = ? ORDER BY id DESC LIMIT 100");
        $stmt->execute([(int) $store['id']]);
        $allOrders = $stmt->fetchAll();

        $recentOrders = array_values(array_filter($allOrders, function ($o) use ($alreadyReturned) {
            return !in_array((int) $o['id'], $alreadyReturned, true);
        }));

        $returns = $this->returnModel->all((int) $store['id']);
        foreach ($returns as &$r) {
            $r['currency_symbol'] = in_array($r['order_source'] ?? 'manual', $this->externalSources, true) ? '$' : 'Rs.';
        }
        unset($r);

        $this->view('returns/index', [
            'returns' => $returns,
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
        $actor = $_SESSION['user']['name'] ?? 'system';

        $return = $this->returnModel->find($id);

        if (!$return) {
            $this->redirect('/returns?error=' . urlencode('Return not found.'));
            return;
        }

        if ($status === 'completed' && $return['status'] !== 'completed' && $condition === 'resellable') {
            if (!empty($return['product_id'])) {
                $this->inventoryService->release(
                    (int) $return['product_id'],
                    !empty($return['variant_id']) ? (int) $return['variant_id'] : null,
                    (int) $return['warehouse_id'],
                    (int) $return['quantity'],
                    'return_completed',
                    "return:{$id}",
                    $actor
                );
            }
        }

        $this->returnModel->updateStatus($id, $status, $condition);
        $this->redirect('/returns?updated=1');
    }
}