<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../Models/SkuMapping.php';
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

        $this->view('sku-mappings/index', [
            'mappings' => $this->mappingModel->all((int) $store['id']),
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