<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../Models/Warehouse.php';
require_once __DIR__ . '/../Models/Product.php';
require_once __DIR__ . '/../Models/ProductVariant.php';
require_once __DIR__ . '/../Models/Store.php';

class WarehouseController extends Controller
{
    private Warehouse $warehouseModel;
    private Product $productModel;
    private ProductVariant $variantModel;
    private Store $storeModel;

    public function __construct()
    {
        parent::__construct();
        $this->requireRole(['admin', 'manager', 'warehouse staff']);
        $this->warehouseModel = new Warehouse();
        $this->productModel = new Product();
        $this->variantModel = new ProductVariant();
        $this->storeModel = new Store();
    }

    public function index(): void
    {
        $warehouses = $this->warehouseModel->all();

        $db = Database::getConnection();
        foreach ($warehouses as &$w) {
            $stmt = $db->prepare(
                "SELECT COUNT(DISTINCT product_id) as product_count, COALESCE(SUM(stock_quantity),0) as total_stock
                 FROM product_warehouse_stock WHERE warehouse_id = ?"
            );
            $stmt->execute([$w['id']]);
            $summary = $stmt->fetch();
            $w['product_count'] = (int) $summary['product_count'];
            $w['total_stock'] = (int) $summary['total_stock'];
            $w['stores'] = $this->warehouseModel->storesLinkedTo((int) $w['id']);
        }
        unset($w);

        $this->view('warehouses/index', [
            'warehouses' => $warehouses,
            'allStores' => $this->storeModel->all(),
            'created' => $_GET['created'] ?? null,
            'error' => $_GET['error'] ?? null,
        ]);
    }

    public function create(): void
    {
        $name = trim($_POST['name'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $storeIds = array_map('intval', $_POST['store_ids'] ?? []);

        if ($name === '') {
            $this->redirect('/warehouses?error=' . urlencode('Warehouse name is required.'));
            return;
        }

        $id = $this->warehouseModel->create($name, $location ?: null);

        foreach ($storeIds as $index => $storeId) {
            if ($storeId <= 0) continue;
            $this->warehouseModel->linkToStore($id, $storeId, $index === 0);
        }

        $this->redirect('/warehouses?created=1');
    }

    public function showWarehouse(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        $warehouse = $this->warehouseModel->find($id);

        if (!$warehouse) {
            echo "Warehouse not found.";
            exit;
        }

        $products = $this->productModel->productsInWarehouse($id);
        foreach ($products as &$p) {
            $p['has_variants'] = $this->productModel->hasVariants((int) $p['id']);
        }
        unset($p);

        $this->view('warehouses/view', [
            'warehouse' => $warehouse,
            'linkedStores' => $this->warehouseModel->storesLinkedTo($id),
            'products' => $products,
            'received' => $_GET['received'] ?? null,
            'error' => $_GET['error'] ?? null,
        ]);
    }

    public function receiveStock(): void
    {
        $productId = (int) ($_POST['product_id'] ?? 0);
        $warehouseId = (int) ($_POST['warehouse_id'] ?? 0);
        $quantity = (int) ($_POST['quantity'] ?? 0);

        if ($productId <= 0 || $warehouseId <= 0 || $quantity <= 0) {
            $this->redirect('/warehouses/view?id=' . $warehouseId . '&error=' . urlencode('Please select a product and enter a valid quantity.'));
            return;
        }

        $this->productModel->receiveStock($productId, $warehouseId, $quantity);
        $this->redirect('/warehouses/view?id=' . $warehouseId . '&received=1');
    }

    public function productStock(): void
    {
        $id = (int) ($_GET['product_id'] ?? 0);
        $product = $this->productModel->find($id);

        if (!$product) {
            echo "Product not found.";
            exit;
        }

        $hasVariants = $this->productModel->hasVariants($id);
        $stockList = $hasVariants ? [] : $this->productModel->warehouseStockList($id);
        $variantMatrix = $hasVariants ? $this->variantModel->warehouseStockMatrix($id) : [];

        $this->view('warehouses/product_stock', [
            'product' => $product,
            'hasVariants' => $hasVariants,
            'stockList' => $stockList,
            'variantMatrix' => $variantMatrix,
            'updated' => $_GET['updated'] ?? null,
        ]);
    }

    public function updateStock(): void
    {
        $productId = (int) ($_POST['product_id'] ?? 0);
        $warehouseId = (int) ($_POST['warehouse_id'] ?? 0);
        $quantity = (int) ($_POST['stock_quantity'] ?? 0);

        if ($productId > 0 && $warehouseId > 0) {
            $this->productModel->setWarehouseStock($productId, $warehouseId, $quantity);
        }

        $this->redirect('/warehouses/product-stock?product_id=' . $productId . '&updated=1');
    }

    public function updateVariantStock(): void
    {
        $variantId = (int) ($_POST['variant_id'] ?? 0);
        $warehouseId = (int) ($_POST['warehouse_id'] ?? 0);
        $quantity = (int) ($_POST['stock_quantity'] ?? 0);
        $productId = (int) ($_POST['product_id'] ?? 0);

        if ($variantId > 0 && $warehouseId > 0) {
            $this->variantModel->setWarehouseStock($variantId, $warehouseId, $quantity);
        }

        $this->redirect('/warehouses/product-stock?product_id=' . $productId . '&updated=1');
    }
}