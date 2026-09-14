<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../Models/Product.php';

class CustomerController extends Controller
{
    private Product $productModel;

    public function __construct()
    {
        parent::__construct();
        $this->requireRole(['admin', 'manager', 'sales staff']);
        $this->productModel = new Product();
    }

    private function currencyForOrder(array $order): string
    {
        $externalSources = ['shopify_pull', 'woocommerce_pull'];
        $isExternalSource = in_array($order['source'] ?? 'manual', $externalSources, true);
        $isExternalProduct = !empty($order['product_id']) && $this->productModel->isExternal((int) $order['product_id']);
        return ($isExternalSource || $isExternalProduct) ? '$' : 'Rs.';
    }

    public function index(): void
    {
        $store = $this->getCurrentStore();
        $db = Database::getConnection();

        $stmt = $db->prepare("SELECT * FROM orders WHERE store_id = ? AND customer_name IS NOT NULL AND customer_name != '' ORDER BY created_at DESC");
        $stmt->execute([(int) $store['id']]);
        $allOrders = $stmt->fetchAll();

        $customers = [];
        foreach ($allOrders as $order) {
            $name = $order['customer_name'];
            if (!isset($customers[$name])) {
                $customers[$name] = [
                    'customer_name' => $name,
                    'order_count' => 0,
                    'totals' => [],
                    'last_order' => $order['created_at'],
                ];
            }
            $customers[$name]['order_count']++;
            $currency = $this->currencyForOrder($order);
            $lineTotal = (float) $order['price'] * (int) $order['quantity'];
            $customers[$name]['totals'][$currency] = ($customers[$name]['totals'][$currency] ?? 0) + $lineTotal;
        }

        $this->view('customers/index', [
            'customers' => array_values($customers),
        ]);
    }

    public function show(): void
    {
        $store = $this->getCurrentStore();
        $name = trim($_GET['name'] ?? '');

        if ($name === '') {
            $this->redirect('/customers');
            return;
        }

        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM orders WHERE store_id = ? AND customer_name = ? ORDER BY id DESC");
        $stmt->execute([(int) $store['id'], $name]);
        $orders = $stmt->fetchAll();

        $totals = [];
        foreach ($orders as &$o) {
            $currency = $this->currencyForOrder($o);
            $o['currency_symbol'] = $currency;
            $lineTotal = (float) $o['price'] * (int) $o['quantity'];
            $totals[$currency] = ($totals[$currency] ?? 0) + $lineTotal;
        }
        unset($o);

        $this->view('customers/view', [
            'customerName' => $name,
            'orders' => $orders,
            'totals' => $totals,
        ]);
    }
}