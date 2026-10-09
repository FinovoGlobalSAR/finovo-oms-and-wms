<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Paginator.php';
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

    /** Search (?q=) + status (?status=) — $text(row) = row ka search wala text */
    private static function filterList(array $rows, callable $text): array
    {
        $q = mb_strtolower(trim((string) ($_GET['q'] ?? '')));
        $status = trim((string) ($_GET['status'] ?? ''));
        return array_values(array_filter($rows, function ($r) use ($q, $status, $text) {
            if ($status !== '' && ($r['status'] ?? '') !== $status) {
                return false;
            }
            return $q === '' || str_contains(mb_strtolower($text($r)), $q);
        }));
    }

    public function index(): void
    {
        $store = $this->getCurrentStore();
        $db = Database::getConnection();

        $alreadyReturned = $this->returnModel->returnedOrderIds((int) $store['id']);

        $stmt = $db->prepare("SELECT * FROM orders WHERE store_id = ? ORDER BY id DESC LIMIT 100");
        $stmt->execute([(int) $store['id']]);
        $allOrders = $stmt->fetchAll();

        // "New Return" ki list: jin ka return pehle se nahi, jo cancel nahi (cancel order ka stock pehle hi wapas hai),
        // aur jin pe "stock warning" nahi (un ka stock kata hi nahi tha)
        $recentOrders = array_values(array_filter($allOrders, function ($o) use ($alreadyReturned) {
            return !in_array((int) $o['id'], $alreadyReturned, true)
                && ($o['status'] ?? '') !== 'cancelled'
                && empty($o['stock_warning']);
        }));

        $allReturns = $this->returnModel->all((int) $store['id']);

        // Search + status filter server pe, phir 25-25 ke pages
        $filtered = self::filterList($allReturns, fn($r) => '#' . $r['id'] . ' #' . $r['order_id'] . ' ' . ($r['customer_name'] ?? '') . ' ' . ($r['product_name'] ?? ''));
        $pager = Paginator::make($filtered);

        $returns = $pager['items'];
        foreach ($returns as &$r) {
            $r['currency_symbol'] = in_array($r['order_source'] ?? 'manual', externalOrderSources(), true) ? '$' : 'Rs.';
        }
        unset($r);

        $this->view('returns/index', [
            'returns' => $returns,
            'allReturns' => $allReturns,
            'pager' => $pager,
            'search' => trim((string) ($_GET['q'] ?? '')),
            'statusFilter' => trim((string) ($_GET['status'] ?? '')),
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
        $refundAmount = round((float) ($_POST['refund_amount'] ?? 0), 2);

        if ($orderId <= 0 || $quantity <= 0) {
            $this->redirect('/returns?error=' . urlencode('Please select an order and a valid quantity.'));
            return;
        }

        // Order sirf isi store ka (pehle kisi bhi store ka order id de kar return ban jata tha)
        $stmt = $db->prepare("SELECT * FROM orders WHERE id = ? AND store_id = ?");
        $stmt->execute([$orderId, (int) $store['id']]);
        $order = $stmt->fetch();

        if (!$order) {
            $this->redirect('/returns?error=' . urlencode('Order not found.'));
            return;
        }

        // Cancelled order ka stock pehle hi warehouse mein wapas aa chuka hai — return se dobara judta
        if (($order['status'] ?? '') === 'cancelled') {
            $this->redirect('/returns?error=' . urlencode('This order is cancelled, its stock is already back in the warehouse. A return is not needed.'));
            return;
        }
        // "Stock warning" order se stock kata hi nahi tha — return complete hone pe stock hawa mein se ban jata
        if (!empty($order['stock_warning'])) {
            $this->redirect('/returns?error=' . urlencode('This order has a stock warning (no stock was taken for it), so a return cannot add stock back. Fix the order first.'));
            return;
        }

        // Is order ke saare returns mila kar bhi order ki quantity se zyada nahi
        $alreadyReturned = $this->returnModel->returnedQuantityForOrder($orderId);
        $remaining = (int) $order['quantity'] - $alreadyReturned;
        if ($quantity > $remaining) {
            $this->redirect('/returns?error=' . urlencode("Only {$remaining} more can be returned for this order (ordered " . (int) $order['quantity'] . ", already returned {$alreadyReturned})."));
            return;
        }

        // Refund: minus nahi, aur is order line ki qeemat (price × quantity) se zyada nahi
        $lineTotal = round((float) $order['price'] * (int) $order['quantity'], 2);
        if ($refundAmount < 0) {
            $this->redirect('/returns?error=' . urlencode('Refund amount cannot be negative.'));
            return;
        }
        if ($lineTotal > 0 && $refundAmount > $lineTotal) {
            $this->redirect('/returns?error=' . urlencode('Refund amount cannot be more than the order amount (' . number_format($lineTotal, 2) . ').'));
            return;
        }

        $warehouseId = (int) ($order['warehouse_id'] ?? 0);
        if ($warehouseId <= 0) {
            require_once __DIR__ . '/../Models/Warehouse.php';
            $warehouseId = (int) ((new Warehouse())->getDefault((int) $store['id'])['id'] ?? 0);
        }

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
        $store = $this->getCurrentStore();
        $db = Database::getConnection();
        $id = (int) ($_POST['id'] ?? 0);
        $status = trim($_POST['status'] ?? '');
        $condition = trim($_POST['condition_status'] ?? 'resellable');
        $actor = $_SESSION['user']['name'] ?? 'system';

        if (!in_array($status, ReturnRequest::$statuses, true) || !in_array($condition, ReturnRequest::$conditions, true)) {
            $this->redirect('/returns?error=' . urlencode('Invalid status or condition.'));
            return;
        }

        $db->beginTransaction();
        try {
            // Row lock: do log ek saath "Completed" dabayein to bhi stock sirf EK dafa jude
            $stmt = $db->prepare("SELECT * FROM returns WHERE id = ? AND store_id = ? FOR UPDATE");
            $stmt->execute([$id, (int) $store['id']]);
            $return = $stmt->fetch();

            if (!$return) {
                $db->rollBack();
                $this->redirect('/returns?error=' . urlencode('Return not found.'));
                return;
            }

            // Completed / Rejected aakhri hain — pehle "completed → requested → completed" kar ke stock baar baar judta tha
            if (!ReturnRequest::canMove((string) $return['status'], $status)) {
                $db->rollBack();
                $this->redirect('/returns?error=' . urlencode("A {$return['status']} return cannot be changed to {$status}."));
                return;
            }

            if ($status === 'completed') {
                $orderStmt = $db->prepare("SELECT status, stock_warning FROM orders WHERE id = ?");
                $orderStmt->execute([(int) $return['order_id']]);
                $order = $orderStmt->fetch();

                // Order cancel ho chuka → us ka stock pehle hi wapas aa gaya; stock warning → stock kata hi nahi tha
                if ($order && (($order['status'] ?? '') === 'cancelled' || !empty($order['stock_warning']))) {
                    $db->rollBack();
                    $this->redirect('/returns?error=' . urlencode('This order is cancelled or has a stock warning — its stock is not held, so the return cannot be completed. Reject it instead.'));
                    return;
                }

                if ($condition === 'resellable' && !empty($return['product_id']) && (int) $return['quantity'] > 0) {
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
            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log('Return status update failed: ' . $e->getMessage());
            $this->redirect('/returns?error=' . urlencode('Return could not be updated. Please try again.'));
            return;
        }

        $this->redirect('/returns?updated=1');
    }
}