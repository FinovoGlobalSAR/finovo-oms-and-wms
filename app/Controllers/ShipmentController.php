<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Paginator.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../Models/Shipment.php';

class ShipmentController extends Controller
{
    private Shipment $shipmentModel;

    public function __construct()
    {
        parent::__construct();
        $this->requireRole(['admin', 'manager', 'warehouse staff']);
        $this->requireStoreContext();
        $this->shipmentModel = new Shipment();
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

        $allShipments = $this->shipmentModel->all((int) $store['id']);

        // Search + status filter server pe, phir 25-25 ke pages
        $filtered = self::filterList($allShipments, fn($s) => ($s['customer_name'] ?? '') . ' ' . $s['courier_name'] . ' ' . ($s['tracking_number'] ?? '') . ' #' . $s['id']);
        $pager = Paginator::make($filtered);

        // Orders sirf is page ki shipments ke (pehle har shipment ke liye query chalti thi)
        $shipments = $pager['items'];
        foreach ($shipments as &$s) {
            $s['orders'] = $this->shipmentModel->ordersForShipment((int) $s['id']);
        }
        unset($s);

        $this->view('shipments/index', [
            'shipments' => $shipments,
            'allShipments' => $allShipments,
            'pager' => $pager,
            'search' => trim((string) ($_GET['q'] ?? '')),
            'statusFilter' => trim((string) ($_GET['status'] ?? '')),
            'unassignedUnits' => $this->shipmentModel->unassignedOrderUnits((int) $store['id']),
            'couriers' => Shipment::$couriers,
            'statuses' => Shipment::$statuses,
            'created' => $_GET['created'] ?? null,
            'updated' => $_GET['updated'] ?? null,
            'error' => $_GET['error'] ?? null,
        ]);
    }

    public function create(): void
    {
        $store = $this->getCurrentStore();

        $unitKey = trim($_POST['order_unit'] ?? '');
        $courierName = trim($_POST['courier_name'] ?? '');
        $trackingNumber = trim($_POST['tracking_number'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        if ($unitKey === '' || $courierName === '') {
            $this->redirect('/shipments?error=' . urlencode('Please select an order and a courier.'));
            return;
        }

        $shipmentId = $this->shipmentModel->create((int) $store['id'], $courierName, $trackingNumber, $notes);
        $this->shipmentModel->assignOrderUnit($shipmentId, $unitKey);
        $this->shipmentModel->updateStatus($shipmentId, 'packed');

        $this->redirect('/shipments?created=1');
    }

    public function updateStatus(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        $status = trim($_POST['status'] ?? '');

        if ($id > 0 && in_array($status, Shipment::$statuses, true)) {
            $this->shipmentModel->updateStatus($id, $status);
        }

        $this->redirect('/shipments?updated=1');
    }
}
