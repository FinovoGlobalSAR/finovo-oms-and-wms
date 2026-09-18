<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../Models/PurchaseOrder.php';
require_once __DIR__ . '/../Models/Supplier.php';
require_once __DIR__ . '/../Models/Warehouse.php';
require_once __DIR__ . '/../Models/Product.php';

class PurchaseOrderController extends Controller
{
    private PurchaseOrder $poModel;
    private Supplier $supplierModel;
    private Warehouse $warehouseModel;
    private Product $productModel;

    public function __construct()
    {
        parent::__construct();
        $this->requireRole(['admin', 'manager', 'warehouse staff']);
        $this->requireStoreContext();
        $this->poModel = new PurchaseOrder();
        $this->supplierModel = new Supplier();
        $this->warehouseModel = new Warehouse();
        $this->productModel = new Product();
    }

    public function index(): void
    {
        $store = $this->getCurrentStore();
        $purchaseOrders = $this->poModel->all((int) $store['id']);
        foreach ($purchaseOrders as &$po) {
            $po['items'] = $this->poModel->items((int) $po['id']);
        }
        unset($po);

        $this->view('purchase-orders/index', [
            'purchaseOrders' => $purchaseOrders,
            'suppliers' => $this->supplierModel->all((int) $store['id']),
            'warehouses' => $this->warehouseModel->allByStore((int) $store['id']),
            'products' => $this->productModel->all((int) $store['id']),
            'statuses' => PurchaseOrder::$statuses,
            'created' => $_GET['created'] ?? null,
            'updated' => $_GET['updated'] ?? null,
            'error' => $_GET['error'] ?? null,
        ]);
    }

    public function create(): void
    {
        $store = $this->getCurrentStore();
        $supplierId = (int) ($_POST['supplier_id'] ?? 0);
        $warehouseId = (int) ($_POST['warehouse_id'] ?? 0);
        $notes = trim($_POST['notes'] ?? '');
        $productIds = $_POST['product_id'] ?? [];
        $quantities = $_POST['quantity'] ?? [];
        $costs = $_POST['unit_cost'] ?? [];

        if ($supplierId <= 0 || $warehouseId <= 0 || empty($productIds)) {
            $this->redirect('/purchase-orders?error=' . urlencode('Please select a supplier, warehouse, and at least one product.'));
            return;
        }

        $poId = $this->poModel->create((int) $store['id'], $supplierId, $warehouseId, $notes ?: null);

        foreach ($productIds as $i => $productId) {
            $productId = (int) $productId;
            $quantity = (int) ($quantities[$i] ?? 0);
            $unitCost = (float) ($costs[$i] ?? 0);
            if ($productId > 0 && $quantity > 0) {
                $this->poModel->addItem($poId, $productId, $quantity, $unitCost);
            }
        }

        $this->redirect('/purchase-orders?created=1');
    }

    public function updateStatus(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        $status = trim($_POST['status'] ?? '');

        if ($id > 0 && in_array($status, PurchaseOrder::$statuses, true)) {
            $po = $this->poModel->find($id);

            if ($status === 'received' && $po && $po['status'] !== 'received') {
                $items = $this->poModel->items($id);
                foreach ($items as $item) {
                    $this->productModel->receiveStock((int) $item['product_id'], (int) $po['warehouse_id'], (int) $item['quantity']);
                }
            }

            $this->poModel->updateStatus($id, $status);
        }

        $this->redirect('/purchase-orders?updated=1');
    }
}