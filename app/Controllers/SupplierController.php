<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Paginator.php';
require_once __DIR__ . '/../Models/Supplier.php';
require_once __DIR__ . '/../Services/AuditLogService.php';

class SupplierController extends Controller
{
    private Supplier $supplierModel;

    public function __construct()
    {
        parent::__construct();
        $this->requireRole(['admin', 'manager', 'warehouse staff']);
        $this->requireStoreContext();
        $this->supplierModel = new Supplier();
    }

    public function index(): void
    {
        $store = $this->getCurrentStore();
        $allSuppliers = $this->supplierModel->all((int) $store['id']);

        // Search (?q=) server pe, phir sirf latest 5 records (Paginator::latest)
        $search = trim((string) ($_GET['q'] ?? ''));
        $needle = mb_strtolower($search);
        $filtered = array_filter($allSuppliers, fn($s) => $needle === ''
            || str_contains(mb_strtolower($s['name'] . ' ' . ($s['contact_person'] ?? '') . ' ' . ($s['email'] ?? '') . ' ' . ($s['phone'] ?? '')), $needle));
        $pager = Paginator::latest($filtered, $allSuppliers);

        $this->view('suppliers/index', [
            'suppliers' => $pager['items'],
            'totalSuppliers' => count($allSuppliers),
            'pager' => $pager,
            'search' => $search,
            'canDelete' => $this->isAdminOrManager(),
            'created' => $_GET['created'] ?? null,
            'deleted' => $_GET['deleted'] ?? null,
            'error' => $_GET['error'] ?? null,
        ]);
    }

    public function create(): void
    {
        $store = $this->getCurrentStore();
        $name = trim($_POST['name'] ?? '');
        $contactPerson = trim($_POST['contact_person'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');

        if ($name === '') {
            $this->redirect('/suppliers?error=' . urlencode('Supplier name is required.'));
            return;
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->redirect('/suppliers?error=' . urlencode('Please enter a valid email address.'));
            return;
        }

        $this->supplierModel->create((int) $store['id'], $name, $contactPerson ?: null, $email ?: null, $phone ?: null);
        $this->redirect('/suppliers?created=1');
    }

    public function delete(): void
    {
        // Supplier delete sirf Admin / Manager
        if (!$this->isAdminOrManager()) {
            $this->redirect('/suppliers?error=' . urlencode('Only Admin or Manager can delete suppliers.'));
            return;
        }

        $store = $this->getCurrentStore();
        $id = (int) ($_POST['id'] ?? 0);
        $supplier = $this->supplierModel->find($id);

        // Sirf isi store ka supplier (pehle kisi bhi store ka id bhej kar delete ho jata tha)
        if (!$supplier || (int) $supplier['store_id'] !== (int) $store['id']) {
            $this->redirect('/suppliers?error=' . urlencode('Supplier not found.'));
            return;
        }

        // Database mein supplier ke saath us ke saare Purchase Orders bhi mit jate (CASCADE) — is liye rok do
        $poCount = $this->supplierModel->purchaseOrderCount($id);
        if ($poCount > 0) {
            $this->redirect('/suppliers?error=' . urlencode("This supplier has {$poCount} purchase order(s). A supplier with purchase orders cannot be deleted."));
            return;
        }

        $this->supplierModel->delete($id, (int) $store['id']);
        (new AuditLogService())->log((int) $store['id'], 'supplier_deleted', 'supplier', (string) $id, "Supplier \"{$supplier['name']}\" deleted");

        $this->redirect('/suppliers?deleted=1');
    }
}