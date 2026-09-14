<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Database.php';

class PickingController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->requireRole(['admin', 'manager', 'warehouse staff']);
    }

    public function index(): void
    {
        $store = $this->getCurrentStore();
        $db = Database::getConnection();

        $stmt = $db->prepare(
            "SELECT * FROM orders
             WHERE store_id = ? AND shipment_id IS NULL AND picking_status != 'packed'
             ORDER BY id ASC"
        );
        $stmt->execute([(int) $store['id']]);
        $orders = $stmt->fetchAll();

        $this->view('picking/index', [
            'orders' => $orders,
            'updated' => $_GET['updated'] ?? null,
        ]);
    }

    public function updateStatus(): void
    {
        $db = Database::getConnection();
        $id = (int) ($_POST['id'] ?? 0);
        $status = trim($_POST['status'] ?? '');

        $allowed = ['not_started', 'picking', 'picked', 'packed'];
        if ($id > 0 && in_array($status, $allowed, true)) {
            $stmt = $db->prepare("UPDATE orders SET picking_status = ? WHERE id = ?");
            $stmt->execute([$status, $id]);
        }

        $this->redirect('/picking?updated=1');
    }
}