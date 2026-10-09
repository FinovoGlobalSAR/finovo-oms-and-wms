<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Paginator.php';

/**
 * Picking & Packing — warehouse staff order uthate (pick) aur pack karte hain.
 *
 * Pehle: cancelled orders bhi list mein aate the (cancel maal pack ho kar parcel mein chala jata),
 * kisi bhi store ka order id bhej kar status badal sakte the, aur saare orders ek saath page pe aate the.
 * Ab: cancelled bahar, sirf isi store ke orders, aur server pe search + 25-25 ke pages.
 */
class PickingController extends Controller
{
    public const STATUSES = ['not_started', 'picking', 'picked', 'packed'];

    public function __construct()
    {
        parent::__construct();
        $this->requireRole(['admin', 'manager', 'warehouse staff']);
        $this->requireStoreContext();
    }

    public function index(): void
    {
        $store = $this->getCurrentStore();
        $db = Database::getConnection();

        $stmt = $db->prepare(
            "SELECT * FROM orders
             WHERE store_id = ? AND shipment_id IS NULL AND picking_status != 'packed' AND status != 'cancelled'
             ORDER BY id ASC"
        );
        $stmt->execute([(int) $store['id']]);
        $allOrders = $stmt->fetchAll();

        // Search (?q=) + status (?status=) server pe
        $search = trim((string) ($_GET['q'] ?? ''));
        $statusFilter = in_array($_GET['status'] ?? '', self::STATUSES, true) ? $_GET['status'] : '';
        $needle = mb_strtolower($search);
        $filtered = array_values(array_filter($allOrders, function ($o) use ($needle, $statusFilter) {
            if ($statusFilter !== '' && ($o['picking_status'] ?? 'not_started') !== $statusFilter) {
                return false;
            }
            $text = '#' . $o['id'] . ' ' . $o['customer_name'] . ' ' . $o['product_name'] . ' ' . ($o['variant_label'] ?? '');
            return $needle === '' || str_contains(mb_strtolower($text), $needle);
        }));

        // Export: filter ke mutabiq SAARE orders (sirf is page ke nahi)
        if (($_GET['export'] ?? '') === 'csv') {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="picking-orders.csv"');
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Order', 'Customer', 'Product', 'Qty', 'Status']);
            foreach ($filtered as $o) {
                $product = $o['product_name'] . (!empty($o['variant_label']) ? ' (' . $o['variant_label'] . ')' : '');
                fputcsv($out, ['#' . $o['id'], $o['customer_name'], $product, (int) $o['quantity'], str_replace('_', ' ', $o['picking_status'] ?? 'not_started')]);
            }
            fclose($out);
            exit;
        }

        $pager = Paginator::make($filtered);

        $this->view('picking/index', [
            'orders' => $pager['items'],
            'allOrders' => $allOrders,
            'pager' => $pager,
            'search' => $search,
            'statusFilter' => $statusFilter,
            'updated' => $_GET['updated'] ?? null,
            'error' => $_GET['error'] ?? null,
        ]);
    }

    public function updateStatus(): void
    {
        $store = $this->getCurrentStore();
        $db = Database::getConnection();
        $id = (int) ($_POST['id'] ?? 0);
        $status = trim($_POST['status'] ?? '');

        if ($id > 0 && in_array($status, self::STATUSES, true)) {
            // Sirf isi store ka, cancel na hua, aur abhi shipment mein na gaya order
            $stmt = $db->prepare(
                "UPDATE orders SET picking_status = ?
                 WHERE id = ? AND store_id = ? AND status != 'cancelled' AND shipment_id IS NULL"
            );
            $stmt->execute([$status, $id, (int) $store['id']]);
            if ($stmt->rowCount() === 0) {
                $check = $db->prepare("SELECT picking_status FROM orders WHERE id = ? AND store_id = ? AND status != 'cancelled' AND shipment_id IS NULL");
                $check->execute([$id, (int) $store['id']]);
                if ($check->fetchColumn() === false) {
                    $this->redirect('/picking?error=' . urlencode('This order cannot be picked (it is cancelled, already shipped, or not in this store).'));
                    return;
                }
            }
        }

        $this->redirect('/picking?updated=1');
    }
}