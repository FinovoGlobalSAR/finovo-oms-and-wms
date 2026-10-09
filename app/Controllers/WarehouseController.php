<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../Models/Warehouse.php';
require_once __DIR__ . '/../Models/Product.php';
require_once __DIR__ . '/../Models/ProductVariant.php';
require_once __DIR__ . '/../Models/Store.php';
require_once __DIR__ . '/../../core/Paginator.php';
require_once __DIR__ . '/../Services/InventoryService.php';

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
        // Naya warehouse banana (aur store se jodna) sirf Admin / Manager
        if (!$this->isAdminOrManager()) {
            $this->redirect('/warehouses?error=' . urlencode('Only Admin or Manager can create warehouses.'));
            return;
        }

        $name = trim($_POST['name'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $storeIds = array_map('intval', $_POST['store_ids'] ?? []);

        if ($name === '') {
            $this->redirect('/warehouses?error=' . urlencode('Warehouse name is required.'));
            return;
        }

        $id = $this->warehouseModel->create($name, $location ?: null);

        // Naya warehouse store ka default TABHI banta hai jab store ka koi default na ho (linkToStore khud dekhta hai).
        // Pehle pehle store ka default seedha badal jata tha → us store ke naye orders khaali warehouse se stock maangte.
        foreach ($storeIds as $storeId) {
            if ($storeId <= 0 || !$this->storeModel->find($storeId)) continue;
            $this->warehouseModel->linkToStore($id, $storeId);
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

        $allProducts = $this->productModel->productsInWarehouse($id);
        // Variant counts ek hi query mein (pehle har product pe alag query)
        $variantCounts = $this->productModel->variantCountsForProducts(array_map(fn($p) => (int) $p['id'], $allProducts));
        foreach ($allProducts as &$p) {
            $p['has_variants'] = !empty($variantCounts[(int) $p['id']]);
        }
        unset($p);

        // Search (?q=) + stock level (?level=) server pe, phir 25-25 ke pages
        $search = trim((string) ($_GET['q'] ?? ''));
        $levelFilter = in_array($_GET['level'] ?? '', ['ok', 'low', 'out', 'variants'], true) ? $_GET['level'] : '';
        $needle = mb_strtolower($search);
        $filtered = array_filter($allProducts, function ($p) use ($needle, $levelFilter) {
            if ($needle !== '' && !str_contains(mb_strtolower($p['name'] . ' ' . ($p['sku'] ?? '')), $needle)) {
                return false;
            }
            if ($levelFilter === '') {
                return true;
            }
            $stock = (int) $p['warehouse_stock'];
            $level = $p['has_variants'] ? 'variants' : ($stock <= 0 ? 'out' : ($stock <= 5 ? 'low' : 'ok'));
            return $level === $levelFilter;
        });
        $pager = Paginator::make($filtered);

        $this->view('warehouses/view', [
            'warehouse' => $warehouse,
            'linkedStores' => $this->warehouseModel->storesLinkedTo($id),
            'products' => $pager['items'],
            'allProducts' => $allProducts,
            'pager' => $pager,
            'search' => $search,
            'levelFilter' => $levelFilter,
            'received' => $_GET['received'] ?? null,
            'error' => $_GET['error'] ?? null,
        ]);
    }

    public function receiveStock(): void
    {
        $productId = (int) ($_POST['product_id'] ?? 0);
        $warehouseId = (int) ($_POST['warehouse_id'] ?? 0);
        $quantity = (int) ($_POST['quantity'] ?? 0);

        if ($productId <= 0 || $warehouseId <= 0 || $quantity <= 0 || !$this->warehouseModel->find($warehouseId) || !$this->productModel->find($productId)) {
            $this->redirect('/warehouses/view?id=' . $warehouseId . '&error=' . urlencode('Please select a product and enter a valid quantity.'));
            return;
        }
        // Variant wale product ka stock variant pe hota hai — product level pe daalne se hisaab bigadta
        if ($this->productModel->hasVariants($productId)) {
            $this->redirect('/warehouses/view?id=' . $warehouseId . '&error=' . urlencode('This product has variants — use "Manage variants" to add stock to a specific variant.'));
            return;
        }

        // InventoryService: row lock + ledger entry (pehle history mein kuch nahi likha jata tha)
        (new InventoryService())->release($productId, null, $warehouseId, $quantity, 'warehouse_receive', "warehouse:{$warehouseId}", $_SESSION['user']['name'] ?? 'system');
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
        $quantity = max(0, (int) ($_POST['stock_quantity'] ?? 0));

        // Variant wale product ka stock yahan se nahi (har variant ka alag form hai)
        if ($productId > 0 && $warehouseId > 0 && $this->warehouseModel->find($warehouseId)
            && $this->productModel->find($productId) && !$this->productModel->hasVariants($productId)) {
            (new InventoryService())->setStock($productId, null, $warehouseId, $quantity, 'manual_set', "warehouse:{$warehouseId}", $_SESSION['user']['name'] ?? 'system');
        }

        $this->redirect('/warehouses/product-stock?product_id=' . $productId . '&updated=1');
    }

    public function updateVariantStock(): void
    {
        $variantId = (int) ($_POST['variant_id'] ?? 0);
        $warehouseId = (int) ($_POST['warehouse_id'] ?? 0);
        $quantity = max(0, (int) ($_POST['stock_quantity'] ?? 0));
        $productId = (int) ($_POST['product_id'] ?? 0);

        // Variant isi product ka ho (product id form se aata hai — us pe bharosa nahi, variant se nikalte hain)
        $variant = $this->variantModel->find($variantId);
        if ($variant && $warehouseId > 0 && $this->warehouseModel->find($warehouseId)) {
            $productId = (int) $variant['product_id'];
            (new InventoryService())->setStock($productId, $variantId, $warehouseId, $quantity, 'manual_set', "warehouse:{$warehouseId}", $_SESSION['user']['name'] ?? 'system');
        }

        $this->redirect('/warehouses/product-stock?product_id=' . $productId . '&updated=1');
    }
}