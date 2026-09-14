<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../Models/Supplier.php';

class SupplierController extends Controller
{
    private Supplier $supplierModel;

    public function __construct()
    {
        parent::__construct();
        $this->requireRole(['admin', 'manager', 'warehouse staff']);
        $this->supplierModel = new Supplier();
    }

    public function index(): void
    {
        $store = $this->getCurrentStore();
        $this->view('suppliers/index', [
            'suppliers' => $this->supplierModel->all((int) $store['id']),
            'created' => $_GET['created'] ?? null,
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

        $this->supplierModel->create((int) $store['id'], $name, $contactPerson ?: null, $email ?: null, $phone ?: null);
        $this->redirect('/suppliers?created=1');
    }

    public function delete(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            $this->supplierModel->delete($id);
        }
        $this->redirect('/suppliers?deleted=1');
    }
}