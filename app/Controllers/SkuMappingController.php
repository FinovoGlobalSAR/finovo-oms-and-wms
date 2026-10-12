<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../Models/SkuMapping.php';
require_once __DIR__ . '/../../core/Paginator.php';
require_once __DIR__ . '/../Models/Product.php';
require_once __DIR__ . '/../Services/AuditLogService.php';

class SkuMappingController extends Controller
{
    private SkuMapping $mappingModel;
    private Product $productModel;
    private AuditLogService $auditService;

    public function __construct()
    {
        parent::__construct();
        $this->requireRole(['admin', 'manager']);
        $this->requireStoreContext();
        $this->mappingModel = new SkuMapping();
        $this->productModel = new Product();
        $this->auditService = new AuditLogService();
    }

    public function index(): void
    {
        $store = $this->getCurrentStore();
        $allMappings = $this->mappingModel->all((int) $store['id']);

        // Search (?q=) + state (?state=) server pe, phir sirf latest 5 records (Paginator::latest)
        $search = trim((string) ($_GET['q'] ?? ''));
        $stateFilter = in_array($_GET['state'] ?? '', ['mapped', 'unmapped', 'conflict', 'disabled'], true) ? $_GET['state'] : '';
        $needle = mb_strtolower($search);
        $filtered = array_filter($allMappings, function ($m) use ($needle, $stateFilter) {
            if ($stateFilter !== '' && ($m['mapping_state'] ?? '') !== $stateFilter) {
                return false;
            }
            $text = $m['product_name'] . ' ' . ($m['external_product_id'] ?? '') . ' ' . ($m['external_sku'] ?? '') . ' ' . ($m['barcode'] ?? '');
            return $needle === '' || str_contains(mb_strtolower($text), $needle);
        });
        $pager = Paginator::latest($filtered, $allMappings);

        $this->view('sku-mappings/index', [
            'mappings' => $pager['items'],
            'allMappings' => $allMappings,
            'pager' => $pager,
            'search' => $search,
            'stateFilter' => $stateFilter,
            'products' => $this->productModel->all((int) $store['id']),
            'created' => $_GET['created'] ?? null,
            'error' => $_GET['error'] ?? null,
        ]);
    }

    public function create(): void
    {
        $store = $this->getCurrentStore();

        $productId = (int) ($_POST['product_id'] ?? 0);
        $externalProductId = trim($_POST['external_product_id'] ?? '');
        $externalSku = trim($_POST['external_sku'] ?? '');
        $barcode = trim($_POST['barcode'] ?? '');

        if ($productId <= 0 || $externalProductId === '') {
            $this->redirect('/sku-mappings?error=' . urlencode('Please select a product and enter the external product ID.'));
            return;
        }
        // Product isi store ka (ya store ke warehouse wala) ho
        $allowed = array_map(fn($p) => (int) $p['id'], $this->productModel->all((int) $store['id']));
        if (!in_array($productId, $allowed, true)) {
            $this->redirect('/sku-mappings?error=' . urlencode('Please select a valid product.'));
            return;
        }

        // Conflict check: same external ID already mapped to a DIFFERENT internal product
        $existing = $this->mappingModel->findByExternal((int) $store['id'], $externalProductId);
        if ($existing && (int) $existing['product_id'] !== $productId) {
            $this->redirect('/sku-mappings?error=' . urlencode("Conflict: external ID {$externalProductId} is already mapped to a different product."));
            return;
        }

        $this->mappingModel->create(
            (int) $store['id'],
            $productId,
            null,
            $externalProductId,
            null,
            $externalSku ?: null,
            $barcode ?: null,
            $_SESSION['user']['name'] ?? 'system'
        );

        $this->auditService->log((int) $store['id'], 'mapping_create', 'product', (string) $productId, "Mapped to external ID {$externalProductId}");

        $this->redirect('/sku-mappings?created=1');
    }
}