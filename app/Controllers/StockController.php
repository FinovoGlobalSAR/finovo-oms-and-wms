<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../Models/StockTransfer.php';
require_once __DIR__ . '/../Models/StockAdjustment.php';
require_once __DIR__ . '/../Models/Warehouse.php';
require_once __DIR__ . '/../Models/Product.php';

class StockController extends Controller
{
    private StockTransfer $transferModel;
    private StockAdjustment $adjustmentModel;
    private Warehouse $warehouseModel;
    private Product $productModel;

    public function __construct()
    {
        parent::__construct();
        $this->requireRole(['admin', 'manager', 'warehouse staff']);
        $this->requireStoreContext();
        $this->transferModel = new StockTransfer();
        $this->adjustmentModel = new StockAdjustment();
        $this->warehouseModel = new Warehouse();
        $this->productModel = new Product();
    }

    public function index(): void
    {
        $store = $this->getCurrentStore();

        $this->view('stock/index', [
            'transfers' => $this->transferModel->all((int) $store['id']),
            'adjustments' => $this->adjustmentModel->all((int) $store['id']),
            'warehouses' => $this->warehouseModel->allByStore((int) $store['id']),
            'products' => $this->productModel->all((int) $store['id']),
            'error' => $_GET['error'] ?? null,
            'transferred' => $_GET['transferred'] ?? null,
            'adjusted' => $_GET['adjusted'] ?? null,
        ]);
    }

    public function transfer(): void
    {
        $productId = (int) ($_POST['product_id'] ?? 0);
        $fromWarehouseId = (int) ($_POST['from_warehouse_id'] ?? 0);
        $toWarehouseId = (int) ($_POST['to_warehouse_id'] ?? 0);
        $quantity = (int) ($_POST['quantity'] ?? 0);
        $notes = trim($_POST['notes'] ?? '');

        if ($productId <= 0 || $fromWarehouseId <= 0 || $toWarehouseId <= 0 || $quantity <= 0 || $fromWarehouseId === $toWarehouseId) {
            $this->redirect('/stock?error=' . urlencode('Please select a product, two different warehouses, and a valid quantity.'));
            return;
        }

        $available = $this->productModel->getWarehouseStock($productId, $fromWarehouseId);
        if ($available < $quantity) {
            $this->redirect('/stock?error=' . urlencode("Only {$available} units available in the source warehouse."));
            return;
        }

        $this->productModel->decreaseWarehouseStock($productId, $fromWarehouseId, $quantity);
        $this->productModel->increaseWarehouseStock($productId, $toWarehouseId, $quantity);
        $this->transferModel->create($productId, null, $fromWarehouseId, $toWarehouseId, $quantity, $notes ?: null);

        $this->redirect('/stock?transferred=1');
    }

    public function adjust(): void
    {
        $productId = (int) ($_POST['product_id'] ?? 0);
        $warehouseId = (int) ($_POST['warehouse_id'] ?? 0);
        $quantityChange = (int) ($_POST['quantity_change'] ?? 0);
        $reason = trim($_POST['reason'] ?? '');

        if ($productId <= 0 || $warehouseId <= 0 || $quantityChange === 0 || $reason === '') {
            $this->redirect('/stock?error=' . urlencode('Please fill in all fields correctly.'));
            return;
        }

        if ($quantityChange > 0) {
            $this->productModel->increaseWarehouseStock($productId, $warehouseId, $quantityChange);
        } else {
            $this->productModel->decreaseWarehouseStock($productId, $warehouseId, abs($quantityChange));
        }

        $this->adjustmentModel->create($productId, null, $warehouseId, $quantityChange, $reason);

        $this->redirect('/stock?adjusted=1');
    }
}