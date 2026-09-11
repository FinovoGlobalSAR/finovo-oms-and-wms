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
        $order = $this->orderModel->findWithItems(
            (int) $orderId,
            $this->companyId
        );

        if (!$order) {
            http_response_code(404);
            echo 'Order not found.';
            return;
        }

        $this->invoiceService->generate($order);
    }
}
