<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../Models/StockTransfer.php';
require_once __DIR__ . '/../Models/StockAdjustment.php';
require_once __DIR__ . '/../Models/Warehouse.php';
require_once __DIR__ . '/../Models/Product.php';
require_once __DIR__ . '/../Services/InventoryService.php';

/**
 * Stock page — Transfer (ek warehouse se doosre) aur Adjustment (toota / gum / mila hua maal).
 *
 * Pehle:
 *   - Variant wale product (jaise T-shirt: Small / Large) ka stock bhi product level pe badalta tha —
 *     variants ka stock waisa hi rehta, aur kul stock ulta-seedha ho jata.
 *   - Kisi bhi store ka product / warehouse id bhej kar us ka stock badal sakte the.
 *   - Inventory ledger (history) mein kuch nahi likha jata tha.
 * Ab sab kuch InventoryService se: row lock, ledger entry, variant ka apna stock.
 *
 * Form mein "item" ki value: "productId" (simple product) ya "productId:variantId" (variant).
 */
class StockController extends Controller
{
    private StockTransfer $transferModel;
    private StockAdjustment $adjustmentModel;
    private Warehouse $warehouseModel;
    private Product $productModel;
    private InventoryService $inventory;

    public function __construct()
    {
        parent::__construct();
        $this->requireRole(['admin', 'manager', 'warehouse staff']);
        $this->requireStoreContext();
        $this->transferModel = new StockTransfer();
        $this->adjustmentModel = new StockAdjustment();
        $this->warehouseModel = new Warehouse();
        $this->productModel = new Product();
        $this->inventory = new InventoryService();
    }

    public function index(): void
    {
        $store = $this->getCurrentStore();
        $products = $this->productModel->all((int) $store['id']);

        $this->view('stock/index', [
            'transfers' => $this->transferModel->all((int) $store['id']),
            'adjustments' => $this->adjustmentModel->all((int) $store['id']),
            'warehouses' => $this->warehouseModel->allByStore((int) $store['id']),
            'products' => $products,
            'stockItems' => self::stockItems($products),
            'error' => $_GET['error'] ?? null,
            'transferred' => $_GET['transferred'] ?? null,
            'adjusted' => $_GET['adjusted'] ?? null,
        ]);
    }

    /**
     * Dropdown ki list: simple product ek dafa, variant wala product har variant ke saath
     *   [['value' => '12', 'label' => 'Mug'], ['value' => '15:40', 'label' => 'T-Shirt — Large'], ...]
     */
    public static function stockItems(array $products): array
    {
        $ids = array_map(fn($p) => (int) $p['id'], $products);
        $variantsByProduct = [];
        if ($ids) {
            $marks = implode(',', array_fill(0, count($ids), '?'));
            $stmt = Database::getConnection()->prepare("SELECT id, product_id, label FROM product_variants WHERE product_id IN ({$marks}) ORDER BY id");
            $stmt->execute($ids);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $v) {
                $variantsByProduct[(int) $v['product_id']][] = $v;
            }
        }

        $items = [];
        foreach ($products as $p) {
            $pid = (int) $p['id'];
            if (!empty($variantsByProduct[$pid])) {
                foreach ($variantsByProduct[$pid] as $v) {
                    $items[] = ['value' => $pid . ':' . (int) $v['id'], 'label' => $p['name'] . ' — ' . $v['label']];
                }
            } else {
                $items[] = ['value' => (string) $pid, 'label' => $p['name']];
            }
        }
        return $items;
    }

    /**
     * Form ki value ko [productId, variantId] mein badlo aur check karo ke:
     *   - product isi store ka hai (ya store ke warehouse mein us ka stock hai)
     *   - variant wale product ka variant chuna gaya hai, aur woh variant isi product ka hai
     * Ghalat ho to error message (string) wapas.
     */
    private function parseItem(string $raw, int $storeId): array|string
    {
        [$productPart, $variantPart] = array_pad(explode(':', $raw, 2), 2, '');
        $productId = (int) $productPart;
        $variantId = $variantPart !== '' ? (int) $variantPart : null;

        $allowed = array_map(fn($p) => (int) $p['id'], $this->productModel->all($storeId));
        if ($productId <= 0 || !in_array($productId, $allowed, true)) {
            return 'Please select a valid product.';
        }

        $hasVariants = $this->productModel->hasVariants($productId);
        if ($hasVariants && !$variantId) {
            return 'This product has variants (sizes / colours) — please pick the exact variant.';
        }
        if (!$hasVariants && $variantId) {
            return 'Please select a valid product.';
        }
        if ($variantId) {
            $stmt = Database::getConnection()->prepare("SELECT 1 FROM product_variants WHERE id = ? AND product_id = ?");
            $stmt->execute([$variantId, $productId]);
            if (!$stmt->fetchColumn()) {
                return 'Please select a valid variant.';
            }
        }
        return [$productId, $variantId];
    }

    /** Warehouse isi store se juda hua hai? */
    private function storeHasWarehouse(int $storeId, int $warehouseId): bool
    {
        foreach ($this->warehouseModel->allByStore($storeId) as $w) {
            if ((int) $w['id'] === $warehouseId) {
                return true;
            }
        }
        return false;
    }

    public function transfer(): void
    {
        $store = $this->getCurrentStore();
        $storeId = (int) $store['id'];
        $fromWarehouseId = (int) ($_POST['from_warehouse_id'] ?? 0);
        $toWarehouseId = (int) ($_POST['to_warehouse_id'] ?? 0);
        $quantity = (int) ($_POST['quantity'] ?? 0);
        $notes = trim($_POST['notes'] ?? '');
        $actor = $_SESSION['user']['name'] ?? 'system';

        if ($fromWarehouseId <= 0 || $toWarehouseId <= 0 || $quantity <= 0 || $fromWarehouseId === $toWarehouseId) {
            $this->redirect('/stock?error=' . urlencode('Please select a product, two different warehouses, and a valid quantity.'));
            return;
        }
        if (!$this->storeHasWarehouse($storeId, $fromWarehouseId) || !$this->storeHasWarehouse($storeId, $toWarehouseId)) {
            $this->redirect('/stock?error=' . urlencode('Both warehouses must be linked to this store.'));
            return;
        }

        $item = $this->parseItem((string) ($_POST['product_id'] ?? ''), $storeId);
        if (is_string($item)) {
            $this->redirect('/stock?error=' . urlencode($item));
            return;
        }
        [$productId, $variantId] = $item;

        // Ek hi transaction: nikalna + daalna + record — beech mein fail ho to kuch bhi aadha nahi
        $db = Database::getConnection();
        $db->beginTransaction();
        try {
            $transferId = $this->transferModel->create($productId, $variantId, $fromWarehouseId, $toWarehouseId, $quantity, $notes ?: null);
            $reference = "transfer:{$transferId}";   // ledger ki dono lines pe transfer ka number

            $out = $this->inventory->reserve($productId, $variantId, $fromWarehouseId, $quantity, 'stock_transfer_out', $reference, $actor);
            if (!$out['ok']) {
                $db->rollBack();
                $this->redirect('/stock?error=' . urlencode('Only ' . (int) ($out['available'] ?? 0) . ' units available in the source warehouse.'));
                return;
            }
            $this->inventory->release($productId, $variantId, $toWarehouseId, $quantity, 'stock_transfer_in', $reference, $actor);

            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log('Stock transfer failed: ' . $e->getMessage());
            $this->redirect('/stock?error=' . urlencode('Transfer could not be saved. Please try again.'));
            return;
        }

        $this->redirect('/stock?transferred=1');
    }

    public function adjust(): void
    {
        $store = $this->getCurrentStore();
        $storeId = (int) $store['id'];
        $warehouseId = (int) ($_POST['warehouse_id'] ?? 0);
        $quantityChange = (int) ($_POST['quantity_change'] ?? 0);
        $reason = trim($_POST['reason'] ?? '');
        $actor = $_SESSION['user']['name'] ?? 'system';

        if ($warehouseId <= 0 || $quantityChange === 0 || $reason === '') {
            $this->redirect('/stock?error=' . urlencode('Please fill in all fields correctly.'));
            return;
        }
        if (!$this->storeHasWarehouse($storeId, $warehouseId)) {
            $this->redirect('/stock?error=' . urlencode('This warehouse is not linked to this store.'));
            return;
        }

        $item = $this->parseItem((string) ($_POST['product_id'] ?? ''), $storeId);
        if (is_string($item)) {
            $this->redirect('/stock?error=' . urlencode($item));
            return;
        }
        [$productId, $variantId] = $item;

        $db = Database::getConnection();
        $db->beginTransaction();
        try {
            $adjustmentId = $this->adjustmentModel->create($productId, $variantId, $warehouseId, $quantityChange, $reason);
            $reference = "adjustment:{$adjustmentId}";

            if ($quantityChange > 0) {
                $this->inventory->release($productId, $variantId, $warehouseId, $quantityChange, 'stock_adjustment', $reference, $actor);
            } else {
                // Minus adjustment: jitna hai us se zyada nahi ghata sakte (pehle chupke se 0 kar deta tha)
                $result = $this->inventory->reserve($productId, $variantId, $warehouseId, abs($quantityChange), 'stock_adjustment', $reference, $actor);
                if (!$result['ok']) {
                    $db->rollBack();
                    $this->redirect('/stock?error=' . urlencode('Cannot remove ' . abs($quantityChange) . ' — only ' . (int) ($result['available'] ?? 0) . ' in this warehouse.'));
                    return;
                }
            }
            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log('Stock adjustment failed: ' . $e->getMessage());
            $this->redirect('/stock?error=' . urlencode('Adjustment could not be saved. Please try again.'));
            return;
        }

        $this->redirect('/stock?adjusted=1');
    }
}