<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../Models/Order.php';
require_once __DIR__ . '/../Services/InvoiceService.php';

class InvoiceController extends Controller
{
    private Order $orderModel;
    private InvoiceService $invoiceService;

    public function __construct()
    {
        parent::__construct();
        $this->requireRole(['admin', 'manager', 'sales staff']);
        $this->requireStoreContext();
        $this->orderModel = new Order();
        $this->invoiceService = new InvoiceService();
    }

    public function download(): void
    {
        $orderId = filter_input(INPUT_GET, 'order_id', FILTER_VALIDATE_INT);

        if (!$orderId || $orderId <= 0) {
            http_response_code(400);
            echo 'Invalid order ID.';
            return;
        }

        // Sirf isi store ka order
        $order = $this->orderModel->findWithItems((int) $orderId, (int) $this->getCurrentStore()['id']);

        if (!$order) {
            http_response_code(404);
            echo 'Order not found.';
            return;
        }
        if (!empty($order['cancelled'])) {
            http_response_code(409);
            echo 'This order is cancelled, so it has no invoice.';
            return;
        }

        $this->invoiceService->generate($order);
    }
}