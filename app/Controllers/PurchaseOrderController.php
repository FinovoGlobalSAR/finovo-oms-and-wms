<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Paginator.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../Models/PurchaseOrder.php';
require_once __DIR__ . '/../Models/Supplier.php';
require_once __DIR__ . '/../Models/Warehouse.php';
require_once __DIR__ . '/../Models/Product.php';
require_once __DIR__ . '/../Services/InventoryService.php';

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
        $allPurchaseOrders = $this->poModel->all((int) $store['id']);

        // Search mein product ke naam bhi hain — is liye items tabhi pehle laate hain jab search ho
        $searching = trim((string) ($_GET['q'] ?? '')) !== '';
        if ($searching) {
            foreach ($allPurchaseOrders as &$po) {
                $po['items'] = $this->poModel->items((int) $po['id']);
            }
            unset($po);
        }

        $filtered = self::filterList($allPurchaseOrders, function ($po) {
            $items = implode(' ', array_map(fn($i) => $i['product_name'], $po['items'] ?? []));
            return '#' . $po['id'] . ' ' . $po['supplier_name'] . ' ' . $po['warehouse_name'] . ' ' . $items;
        });
        $pager = Paginator::make($filtered);

        // Items sirf is page ke POs ke (agar pehle nahi aaye)
        $purchaseOrders = $pager['items'];
        foreach ($purchaseOrders as &$po) {
            $po['items'] ??= $this->poModel->items((int) $po['id']);
        }
        unset($po);

        $this->view('purchase-orders/index', [
            'purchaseOrders' => $purchaseOrders,
            'allPurchaseOrders' => $allPurchaseOrders,
            'pager' => $pager,
            'search' => trim((string) ($_GET['q'] ?? '')),
            'statusFilter' => trim((string) ($_GET['status'] ?? '')),
            'suppliers' => $this->supplierModel->all((int) $store['id']),
            'warehouses' => $this->warehouseModel->allByStore((int) $store['id']),
            'products' => $this->simpleProducts((int) $store['id']),
            'statuses' => PurchaseOrder::$statuses,
            'created' => $_GET['created'] ?? null,
            'updated' => $_GET['updated'] ?? null,
            'error' => $_GET['error'] ?? null,
        ]);
    }

    /**
     * PO mein sirf bina-variant wale products. PO item mein variant ka khana (column) nahi hai, aur
     * variant wale product ka stock variant pe hota hai — product level pe daalne se hisaab bigadta tha.
     * Variant ka maal aaye to Stock page se "Adjustment" (+) kar ke variant chun kar daalo.
     */
    private function simpleProducts(int $storeId): array
    {
        $products = $this->productModel->all($storeId);
        $counts = $this->productModel->variantCountsForProducts(array_map(fn($p) => (int) $p['id'], $products));
        $list = [];
        foreach ($products as $p) {
            if (empty($counts[(int) $p['id']])) {
                $list[] = ['id' => (int) $p['id'], 'name' => $p['name']];
            }
        }
        return $list;
    }

    public function create(): void
    {
        $store = $this->getCurrentStore();
        $storeId = (int) $store['id'];
        $supplierId = (int) ($_POST['supplier_id'] ?? 0);
        $warehouseId = (int) ($_POST['warehouse_id'] ?? 0);
        $notes = trim($_POST['notes'] ?? '');
        $productIds = (array) ($_POST['product_id'] ?? []);
        $quantities = (array) ($_POST['quantity'] ?? []);
        $costs = (array) ($_POST['unit_cost'] ?? []);

        if ($supplierId <= 0 || $warehouseId <= 0 || empty($productIds)) {
            $this->redirect('/purchase-orders?error=' . urlencode('Please select a supplier, warehouse, and at least one product.'));
            return;
        }

        // Supplier aur warehouse isi store ke hon
        $supplierIds = array_map(fn($s) => (int) $s['id'], $this->supplierModel->all($storeId));
        $warehouseIds = array_map(fn($w) => (int) $w['id'], $this->warehouseModel->allByStore($storeId));
        if (!in_array($supplierId, $supplierIds, true) || !in_array($warehouseId, $warehouseIds, true)) {
            $this->redirect('/purchase-orders?error=' . urlencode('Please select a supplier and warehouse of this store.'));
            return;
        }

        // Har line check: product isi store ka, bina variant; quantity 1+; cost minus nahi
        $allowedProducts = array_map(fn($p) => $p['id'], $this->simpleProducts($storeId));
        $lines = [];
        foreach ($productIds as $i => $productId) {
            $productId = (int) $productId;
            $quantity = (int) ($quantities[$i] ?? 0);
            $unitCost = (float) ($costs[$i] ?? 0);
            if ($productId <= 0 && $quantity <= 0) {
                continue;   // khaali row
            }
            if (!in_array($productId, $allowedProducts, true)) {
                $this->redirect('/purchase-orders?error=' . urlencode('One of the products is not valid. Products with variants cannot be added to a PO — use a Stock adjustment for them.'));
                return;
            }
            if ($quantity <= 0) {
                $this->redirect('/purchase-orders?error=' . urlencode('Every product line needs a quantity of at least 1.'));
                return;
            }
            if ($unitCost < 0) {
                $this->redirect('/purchase-orders?error=' . urlencode('Unit cost cannot be negative.'));
                return;
            }
            $lines[] = [$productId, $quantity, $unitCost];
        }
        if (!$lines) {
            $this->redirect('/purchase-orders?error=' . urlencode('Please add at least one product with a quantity.'));
            return;
        }

        $poId = $this->poModel->create($storeId, $supplierId, $warehouseId, $notes ?: null);
        foreach ($lines as [$productId, $quantity, $unitCost]) {
            $this->poModel->addItem($poId, $productId, $quantity, $unitCost);
        }

        $this->redirect('/purchase-orders?created=1');
    }

    public function updateStatus(): void
    {
        $store = $this->getCurrentStore();
        $id = (int) ($_POST['id'] ?? 0);
        $status = trim($_POST['status'] ?? '');
        $actor = $_SESSION['user']['name'] ?? 'system';

        if ($id <= 0 || !in_array($status, PurchaseOrder::$statuses, true)) {
            $this->redirect('/purchase-orders?error=' . urlencode('Invalid status.'));
            return;
        }

        $db = Database::getConnection();
        $db->beginTransaction();
        try {
            // Row lock: do log ek saath "Received" dabayein to bhi maal sirf EK dafa jude
            $stmt = $db->prepare("SELECT * FROM purchase_orders WHERE id = ? AND store_id = ? FOR UPDATE");
            $stmt->execute([$id, (int) $store['id']]);
            $po = $stmt->fetch();

            if (!$po) {
                $db->rollBack();
                $this->redirect('/purchase-orders?error=' . urlencode('Purchase order not found.'));
                return;
            }
            if (!PurchaseOrder::canMove((string) $po['status'], $status)) {
                $db->rollBack();
                $this->redirect('/purchase-orders?error=' . urlencode("A {$po['status']} purchase order cannot be changed to {$status}."));
                return;
            }

            if ($status === 'received' && $po['status'] !== 'received') {
                $inventory = new InventoryService();
                foreach ($this->poModel->items($id) as $item) {
                    // Purane PO mein variant wala product ho to rok do (stock variant pe hota hai)
                    if ($this->productModel->hasVariants((int) $item['product_id'])) {
                        $db->rollBack();
                        $this->redirect('/purchase-orders?error=' . urlencode("\"{$item['product_name']}\" has variants — receive it from the Stock page (Adjustment) by choosing the variant."));
                        return;
                    }
                    // InventoryService: row lock + ledger entry ("po_received", "po:12")
                    $inventory->release((int) $item['product_id'], null, (int) $po['warehouse_id'], (int) $item['quantity'], 'po_received', "po:{$id}", $actor);
                }
            }

            $this->poModel->updateStatus($id, $status);
            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log('PO status update failed: ' . $e->getMessage());
            $this->redirect('/purchase-orders?error=' . urlencode('Purchase order could not be updated. Please try again.'));
            return;
        }

        $this->redirect('/purchase-orders?updated=1');
    }
}