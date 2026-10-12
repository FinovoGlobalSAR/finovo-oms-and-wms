<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Paginator.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../Models/Product.php';
require_once __DIR__ . '/../Models/Customer.php';
require_once __DIR__ . '/../Models/Store.php';

/**
 * Customers page — woh log jo store pe kharidte hain (Shopify, WooCommerce, eBay... ya manual order).
 * Customer platform ke order ke saath khud ban jata hai (naam, email, phone, address platform deta hai).
 * Yeh Finovo ke "users" (jo login karte hain) se alag hain.
 *
 * Sidebar mein Employees ki tarah hamesha dikhta hai (store "Manage" karna zaroori nahi):
 * saare stores ke customers ek jagah, "Store" filter ke saath.
 * Customer ki details "Edit" se badli ja sakti hain; jis customer ka koi order na ho use Admin/Manager delete kar sakta hai.
 * Table mein sirf latest 5 customers aate hain (baaki sirf database mein); search / store / type filter server pe chalte hain.
 */
class CustomerController extends Controller
{
    private Product $productModel;
    private Customer $customerModel;

    public function __construct()
    {
        parent::__construct();
        $this->requireRole(['admin', 'manager', 'sales staff']);
        $this->productModel = new Product();
        $this->customerModel = new Customer();
    }

    private function currencyForOrder(array $order): string
    {
        $isExternalSource = in_array($order['source'] ?? 'manual', externalOrderSources(), true);
        $isExternalProduct = !empty($order['product_id']) && $this->productModel->isExternal((int) $order['product_id']);
        return ($isExternalSource || $isExternalProduct) ? '$' : 'Rs.';
    }

    /** Saare stores: [id => naam] */
    private function allStores(): array
    {
        $stores = [];
        foreach ((new Store())->all() as $row) {
            $stores[(int) $row['id']] = $row['name'];
        }
        return $stores;
    }

    public function index(): void
    {
        $stores = $this->allStores();
        $allCustomers = $this->customerModel->allWithStatsForStores(array_keys($stores));

        // Upar ke 4 numbers POORI list se (sirf is page se nahi)
        $stats = ['total' => count($allCustomers), 'orders' => 0, 'repeat' => 0, 'contact' => 0];
        foreach ($allCustomers as $c) {
            $stats['orders'] += (int) $c['order_count'];
            if ((int) $c['order_count'] > 1) { $stats['repeat']++; }
            if (!empty($c['email']) || !empty($c['phone'])) { $stats['contact']++; }
        }

        // Store filter: URL ka ?store= ; na ho to jis store pe "Manage" chal raha hai
        if (isset($_GET['store'])) {
            $selectedStoreId = isset($stores[(int) $_GET['store']]) ? (int) $_GET['store'] : 0;
        } elseif (!empty($_SESSION['store_context_active']) && isset($stores[(int) ($_SESSION['current_store_id'] ?? 0)])) {
            $selectedStoreId = (int) $_SESSION['current_store_id'];
        } else {
            $selectedStoreId = 0;
        }
        $search = trim((string) ($_GET['q'] ?? ''));
        $type = in_array($_GET['type'] ?? '', ['repeat', 'once'], true) ? $_GET['type'] : '';

        // Filters server pe — har page ka result sahi (pehle sirf screen pe chhupate the)
        $needle = mb_strtolower($search);
        $customers = array_filter($allCustomers, function ($c) use ($selectedStoreId, $needle, $type) {
            if ($selectedStoreId > 0 && (int) $c['store_id'] !== $selectedStoreId) {
                return false;
            }
            if ($type === 'repeat' && (int) $c['order_count'] < 2) {
                return false;
            }
            if ($type === 'once' && (int) $c['order_count'] > 1) {
                return false;
            }
            if ($needle !== '') {
                $hay = mb_strtolower(implode(' ', [$c['name'], $c['email'] ?? '', $c['phone'] ?? '', $c['city'] ?? '', $c['country'] ?? '']));
                if (!str_contains($hay, $needle)) {
                    return false;
                }
            }
            return true;
        });

        // Table mein sirf latest 5 customers (jis ka order sab se naya). "Latest" chunne ke liye
        // chuna hua store gina jata hai, search / type filter nahi — warna doosre store ke customers 5 ki jagah le lete.
        $storeCustomers = array_filter($allCustomers, fn($c) => $selectedStoreId === 0 || (int) $c['store_id'] === $selectedStoreId);
        $pager = Paginator::latest($customers, $storeCustomers, Paginator::LATEST, false);
        $customers = $pager['items'];

        // Total kharcha sirf in 5 customers ka (currency ke hisaab se alag — $ aur Rs. jorte nahi)
        $ids = array_map(fn($c) => (int) $c['id'], $customers);
        $totals = [];
        if (!empty($ids)) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = Database::getConnection()->prepare(
                "SELECT customer_id, source, product_id, price, quantity FROM orders WHERE customer_id IN ({$placeholders})"
            );
            $stmt->execute($ids);

            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $order) {
                $currency = $this->currencyForOrder($order);
                $id = (int) $order['customer_id'];
                $totals[$id][$currency] = ($totals[$id][$currency] ?? 0) + (float) $order['price'] * (int) $order['quantity'];
            }
        }

        foreach ($customers as &$c) {
            $c['totals'] = $totals[(int) $c['id']] ?? [];
        }
        unset($c);

        [$success, $error] = $this->takeFlash();

        $this->view('customers/index', [
            'customers' => $customers,
            'pager' => $pager,
            'stats' => $stats,
            'stores' => $stores,
            'selectedStoreId' => $selectedStoreId,
            'search' => $search,
            'type' => $type,
            'canDelete' => $this->isAdminOrManager(),
            'success' => $success,
            'error' => $error,
        ]);
    }

    public function show(): void
    {
        $id = (int) ($_GET['id'] ?? 0);

        // Purane links (?name=...) bhi chalein — managed store mein naam se dhoondo
        if ($id <= 0 && trim($_GET['name'] ?? '') !== '' && !empty($_SESSION['current_store_id'])) {
            $byName = $this->customerModel->findByName((int) $_SESSION['current_store_id'], trim($_GET['name']));
            $id = (int) ($byName['id'] ?? 0);
        }

        $customer = $id > 0 ? $this->customerModel->find($id) : null;

        if (!$customer) {
            $this->redirect('/customers');
            return;
        }

        $orders = $this->customerModel->ordersFor($id);

        $totals = [];
        $orderGroups = [];
        foreach ($orders as &$o) {
            $currency = $this->currencyForOrder($o);
            $o['currency_symbol'] = $currency;
            $totals[$currency] = ($totals[$currency] ?? 0) + (float) $o['price'] * (int) $o['quantity'];
            $orderGroups[$o['order_group'] ?: 'row-' . $o['id']] = true;
        }
        unset($o);

        $store = (new Store())->find((int) $customer['store_id']);
        [$success, $error] = $this->takeFlash();

        $this->view('customers/view', [
            'customer' => $customer,
            'customerName' => $customer['name'],
            'storeName' => $store['name'] ?? '',
            'orders' => $orders,
            'orderCount' => count($orderGroups),
            'totals' => $totals,
            'canDelete' => $this->isAdminOrManager(),
            'success' => $success,
            'error' => $error,
        ]);
    }

    /** Customer ki details badlo (Edit form se) */
    public function update(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        $back = ($_POST['back'] ?? '') === 'view' ? '/customers/view?id=' . $id : '/customers';

        $customer = $id > 0 ? $this->customerModel->find($id) : null;
        if (!$customer) {
            $this->flashAndGo('error', 'Customer not found.', '/customers');
        }

        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');

        if ($name === '') {
            $this->flashAndGo('error', 'Customer name is required.', $back);
        }
        if ($email !== '' && Customer::cleanEmail($email) === null) {
            $this->flashAndGo('error', 'Please enter a valid email address (or leave it empty).', $back);
        }

        try {
            $this->customerModel->updateDetails($id, [
                'name' => $name,
                'email' => $email,
                'phone' => $_POST['phone'] ?? '',
                'address' => $_POST['address'] ?? '',
                'city' => $_POST['city'] ?? '',
                'country' => $_POST['country'] ?? '',
            ]);
            $this->flashAndGo('success', 'Customer updated successfully.', $back);
        } catch (PDOException $e) {
            error_log('Customer update failed: ' . $e->getMessage());
            $this->flashAndGo('error', 'Customer could not be updated. Please try again.', $back);
        }
    }

    /**
     * Customer delete — sirf Admin / Manager, aur sirf tab jab us ke koi order na hon.
     * Orders wale customer ko delete karne se orders ka hisaab (kis ne kharida) toot jata,
     * is liye us case mein mana kar dete hain.
     */
    public function delete(): void
    {
        if (!$this->isAdminOrManager()) {
            $this->flashAndGo('error', 'Only Admin or Manager can delete customers.', '/customers');
        }

        $id = (int) ($_POST['id'] ?? 0);
        $customer = $id > 0 ? $this->customerModel->find($id) : null;
        if (!$customer) {
            $this->flashAndGo('error', 'Customer not found.', '/customers');
        }

        $orderCount = $this->customerModel->orderCount($id);
        if ($orderCount > 0) {
            $this->flashAndGo('error', 'This customer has ' . $orderCount . ' order(s), so they cannot be deleted. You can edit their details instead.', '/customers/view?id=' . $id);
        }

        try {
            $this->customerModel->deleteWithoutOrders($id);
            $this->flashAndGo('success', 'Customer "' . $customer['name'] . '" deleted.', '/customers');
        } catch (PDOException $e) {
            error_log('Customer delete failed: ' . $e->getMessage());
            $this->flashAndGo('error', 'Customer could not be deleted. Please try again.', '/customers');
        }
    }

    private function takeFlash(): array
    {
        $success = $_SESSION['customer_success'] ?? null;
        $error = $_SESSION['customer_error'] ?? null;
        unset($_SESSION['customer_success'], $_SESSION['customer_error']);
        return [$success, $error];
    }

    private function flashAndGo(string $type, string $message, string $url): void
    {
        $_SESSION['customer_' . $type] = $message;
        $this->redirect($url);
    }
}