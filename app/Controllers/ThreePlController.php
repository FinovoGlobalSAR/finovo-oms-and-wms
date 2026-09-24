<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../Models/Shipment.php';
require_once __DIR__ . '/../Connectors/DhlConnector.php';

class ThreePlController extends Controller
{
    private Shipment $shipmentModel;

    public function __construct()
    {
        parent::__construct();
        $this->requireRole(['admin', 'manager', 'warehouse staff']);
        $this->requireStoreContext();
        $this->shipmentModel = new Shipment();
    }

    public function scanForm(): void
    {
        $this->view('threepl/scan', [
            'scanned' => $_GET['scanned'] ?? null,
            'error' => $_GET['error'] ?? null,
        ]);
    }

    public function scanSubmit(): void
    {
        $store = $this->getCurrentStore();
        $awbInput = trim($_POST['awb_numbers'] ?? '');
        $scannedBy = $_SESSION['user']['name'] ?? 'system';

        if ($awbInput === '') {
            $this->redirect('/3pl/scan?error=' . urlencode('Please enter or scan at least one AWB number.'));
            return;
        }

        $awbLines = array_filter(array_map('trim', explode("\n", str_replace("\r", "", $awbInput))));

        $successCount = 0;
        $failedAwbs = [];

        foreach ($awbLines as $awb) {
            $shipment = $this->shipmentModel->findByTrackingNumber($awb, (int) $store['id']);

            if (!$shipment) {
                $failedAwbs[] = $awb . ' (not found)';
                continue;
            }

            $this->shipmentModel->markHandedOver((int) $shipment['id'], $scannedBy);
            $successCount++;
        }

        $redirectUrl = '/3pl/scan?scanned=' . $successCount;
        if (!empty($failedAwbs)) {
            $redirectUrl .= '&error=' . urlencode('Not found: ' . implode(', ', $failedAwbs));
        }

        $this->redirect($redirectUrl);
    }

    public function remittanceIndex(): void
    {
        $store = $this->getCurrentStore();
        $shipments = $this->shipmentModel->allForRemittance((int) $store['id']);

        $this->view('threepl/remittance', [
            'shipments' => $shipments,
            'updated' => $_GET['updated'] ?? null,
        ]);
    }

    public function remittanceUpdate(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        $status = trim($_POST['remittance_status'] ?? '');
        $amount = isset($_POST['remittance_amount']) && $_POST['remittance_amount'] !== ''
            ? (float) $_POST['remittance_amount']
            : null;

        if ($id > 0) {
            $this->shipmentModel->updateRemittance($id, $status, $amount);
        }

        $this->redirect('/3pl/remittance?updated=1');
    }

    public function trackShipment(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        $shipment = $this->shipmentModel->find($id);

        if (!$shipment) {
            $this->redirect('/shipments?error=' . urlencode('Shipment not found.'));
            return;
        }

        $courier = strtolower($shipment['courier_name'] ?? '');

        if (str_contains($courier, 'dhl')) {
            $apiKey = $_ENV['DHL_API_KEY'] ?? '';
            if ($apiKey === '') {
                $this->redirect('/shipments?error=' . urlencode('DHL API key not configured.'));
                return;
            }

            $connector = new DhlConnector($apiKey);
            $result = $connector->trackShipment($shipment['tracking_number']);

            if (!$result['ok']) {
                $this->redirect('/shipments?error=' . urlencode($result['message']));
                return;
            }

            $finovoStatus = DhlConnector::mapToFinovoStatus($result['status']);
            $this->shipmentModel->updateStatus($id, $finovoStatus);

            $this->redirect('/shipments?updated=1&tracked=' . urlencode($result['description']));
            return;
        }

        $this->redirect('/shipments?error=' . urlencode("Live tracking isn't connected for {$shipment['courier_name']} yet."));
    }
}