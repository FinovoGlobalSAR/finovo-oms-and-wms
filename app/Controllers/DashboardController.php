<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../Models/Product.php';

class DashboardController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
    }

    public function index(): void
    {
        $db = Database::getConnection();
        $role = $this->currentRole();

        // Admin / Manager ne abhi koi store "Manage" nahi kiya → neutral dashboard
        // (kisi bhi store ka revenue / orders nahi dikhana, warna pata nahi chalta kis store ka hai)
        if ($this->isAdminOrManager() && empty($_SESSION['store_context_active'])) {
            $this->view('dashboard/index', $this->neutralData($db, $role));
            return;
        }

        $store = $this->getCurrentStore();
        $storeId = (int) $store['id'];
        $productModel = new Product();

        $data = [
            'neutral' => false,
            'role' => $role,
            'userName' => $_SESSION['user']['name'] ?? 'User',
            'storeName' => $store['name'] ?? 'Finovo',
            'storePlatform' => strtolower($store['platform'] ?? 'manual'),
            'storeHealth' => $store['health_status'] ?? 'disconnected',
            'storeLastSync' => $store['last_successful_sync'] ?? null,
        ];

        // ---------- Shared stats (used by more than one role) ----------

        $totalOrdersStmt = $db->prepare("SELECT COUNT(*) as c FROM orders WHERE store_id = ?");
        $totalOrdersStmt->execute([$storeId]);
        $data['totalOrders'] = (int) $totalOrdersStmt->fetch()['c'];

        [$data['totalRevenueUsd'], $data['totalRevenuePkr']] = $this->splitRevenueByCurrency($db, $storeId, $productModel);

        $recentOrdersStmt = $db->prepare("SELECT * FROM orders WHERE store_id = ? ORDER BY id DESC LIMIT 5");
        $recentOrdersStmt->execute([$storeId]);
        $recentOrders = $recentOrdersStmt->fetchAll();

        foreach ($recentOrders as &$ro) {
            $isExternalSource = in_array($ro['source'] ?? 'manual', externalOrderSources(), true);
            $isExternalProduct = !empty($ro['product_id']) && $productModel->isExternal((int) $ro['product_id']);
            $ro['currency_symbol'] = ($isExternalSource || $isExternalProduct) ? '$' : 'Rs.';
        }
        unset($ro);

        $data['recentOrders'] = $recentOrders;

        // Orders per day, last 7 days (for line chart)
        $trendStmt = $db->prepare(
            "SELECT DATE(created_at) as d, COUNT(*) as c, COALESCE(SUM(price * quantity), 0) as revenue
             FROM orders
             WHERE store_id = ? AND status != 'cancelled' AND created_at >= DATE_SUB(NOW(), INTERVAL 6 DAY)
             GROUP BY DATE(created_at)
             ORDER BY d ASC"
        );
        $trendStmt->execute([$storeId]);
        $trendRows = $trendStmt->fetchAll();

        $trendLabels = [];
        $trendCounts = [];
        $trendRevenue = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $label = date('D', strtotime($date));
            $trendLabels[] = $label;
            $match = null;
            foreach ($trendRows as $row) {
                if ($row['d'] === $date) {
                    $match = $row;
                    break;
                }
            }
            $trendCounts[] = $match ? (int) $match['c'] : 0;
            $trendRevenue[] = $match ? (float) $match['revenue'] : 0;
        }
        $data['trendLabels'] = $trendLabels;
        $data['trendCounts'] = $trendCounts;
        $data['trendRevenue'] = $trendRevenue;

        // Orders by status
        $statusStmt = $db->prepare("SELECT status, COUNT(*) as c FROM orders WHERE store_id = ? GROUP BY status");
        $statusStmt->execute([$storeId]);
        $data['ordersByStatus'] = $statusStmt->fetchAll();

        // Pending orders (jin pe abhi kaam baaki hai)
        $data['pendingOrders'] = 0;
        foreach ($data['ordersByStatus'] as $s) {
            if (in_array($s['status'], ['pending', 'processing'], true)) {
                $data['pendingOrders'] += (int) $s['c'];
            }
        }

        // ---------- Role-specific stats ----------

        if (in_array($role, ['admin', 'manager'], true)) {
            $sourceStmt = $db->prepare("SELECT source, COUNT(*) as c FROM orders WHERE store_id = ? GROUP BY source");
            $sourceStmt->execute([$storeId]);
            $data['ordersBySource'] = $sourceStmt->fetchAll();

            $topProductsStmt = $db->prepare(
                "SELECT product_name, SUM(quantity) as qty FROM orders WHERE store_id = ? AND status != 'cancelled' GROUP BY product_name ORDER BY qty DESC LIMIT 5"
            );
            $topProductsStmt->execute([$storeId]);
            $data['topProducts'] = $topProductsStmt->fetchAll();

            $warehouseStmt = $db->prepare(
                "SELECT w.name, COALESCE(SUM(pws.stock_quantity), 0) as total
                 FROM warehouses w
                 INNER JOIN store_warehouses sw ON sw.warehouse_id = w.id
                 LEFT JOIN products p ON p.store_id = sw.store_id
                 LEFT JOIN product_warehouse_stock pws ON pws.warehouse_id = w.id AND pws.product_id = p.id
                 WHERE sw.store_id = ?
                 GROUP BY w.id, w.name"
            );
            $warehouseStmt->execute([$storeId]);
            $data['warehouseStock'] = $warehouseStmt->fetchAll();

            $lowStockStmt = $db->prepare("SELECT COUNT(*) as c FROM products WHERE store_id = ? AND stock_quantity <= low_stock_threshold");
            $lowStockStmt->execute([$storeId]);
            $data['lowStockCount'] = (int) $lowStockStmt->fetch()['c'];
            // Employees / Stores ki ginti ab neutral dashboard pe hai (woh kisi ek store ki nahi)
        }

        if ($role === 'warehouse staff') {
            $productCountStmt = $db->prepare("SELECT COUNT(*) as c FROM products WHERE store_id = ?");
            $productCountStmt->execute([$storeId]);
            $data['productCount'] = (int) $productCountStmt->fetch()['c'];

            $lowStockStmt = $db->prepare("SELECT COUNT(*) as c FROM products WHERE store_id = ? AND stock_quantity <= low_stock_threshold AND stock_quantity > 0");
            $lowStockStmt->execute([$storeId]);
            $data['lowStockCount'] = (int) $lowStockStmt->fetch()['c'];

            $outOfStockStmt = $db->prepare("SELECT COUNT(*) as c FROM products WHERE store_id = ? AND stock_quantity <= 0");
            $outOfStockStmt->execute([$storeId]);
            $data['outOfStockCount'] = (int) $outOfStockStmt->fetch()['c'];

            $warehouseStmt = $db->prepare(
                "SELECT w.name, COALESCE(SUM(pws.stock_quantity), 0) as total
                 FROM warehouses w
                 INNER JOIN store_warehouses sw ON sw.warehouse_id = w.id
                 LEFT JOIN products p ON p.store_id = sw.store_id
                 LEFT JOIN product_warehouse_stock pws ON pws.warehouse_id = w.id AND pws.product_id = p.id
                 WHERE sw.store_id = ?
                 GROUP BY w.id, w.name"
            );
            $warehouseStmt->execute([$storeId]);
            $data['warehouseStock'] = $warehouseStmt->fetchAll();

            $lowStockListStmt = $db->prepare(
                "SELECT name, sku, stock_quantity, low_stock_threshold FROM products
                 WHERE store_id = ? AND stock_quantity <= low_stock_threshold
                 ORDER BY stock_quantity ASC LIMIT 5"
            );
            $lowStockListStmt->execute([$storeId]);
            $data['lowStockList'] = $lowStockListStmt->fetchAll();
        }

        if ($role === 'sales staff') {
            $monthRevenueStmt = $db->prepare(
                "SELECT COALESCE(SUM(price * quantity), 0) as total FROM orders
                WHERE store_id = ? AND status != 'cancelled' AND MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())"
            );
            $monthRevenueStmt->execute([$storeId]);
            $data['monthRevenue'] = (float) $monthRevenueStmt->fetch()['total'];

            $paymentStmt = $db->prepare("SELECT payment_status, COUNT(*) as c FROM orders WHERE store_id = ? GROUP BY payment_status");
            $paymentStmt->execute([$storeId]);
            $data['ordersByPayment'] = $paymentStmt->fetchAll();
        }

        $this->view('dashboard/index', $data);
    }

    private function neutralData(PDO $db, string $role): array
    {
        $stores = $db->query(
            "SELECT s.id, s.name, s.platform, s.health_status, s.last_successful_sync,
                    (SELECT COUNT(*) FROM store_warehouses sw WHERE sw.store_id = s.id) AS warehouse_count,
                    (SELECT COUNT(*) FROM products p WHERE p.store_id = s.id) AS product_count
             FROM stores s
             ORDER BY s.id DESC"
        )->fetchAll();

        $connected = 0;
        $needsAttention = 0;
        foreach ($stores as &$s) {
            $isManual = in_array(strtolower($s['platform'] ?? 'manual'), ['manual', ''], true);
            $health = $s['health_status'] ?? 'disconnected';

            // Stores page jaisa status: Active / Setup Incomplete / Sync Error
            if ($health === 'sync_error') {
                $s['ui_status'] = 'sync_error';
            } elseif ((int) $s['warehouse_count'] === 0 || (!$isManual && $health !== 'connected')) {
                $s['ui_status'] = 'incomplete';
            } else {
                $s['ui_status'] = 'active';
            }

            if (!$isManual && $health === 'connected') {
                $connected++;
            }
            if ($s['ui_status'] !== 'active') {
                $needsAttention++;
            }
        }
        unset($s);

        return [
            'neutral' => true,
            'role' => $role,
            'userName' => $_SESSION['user']['name'] ?? 'User',
            'stores' => $stores,
            'storeCount' => count($stores),
            'connectedCount' => $connected,
            'attentionCount' => $needsAttention,
            'warehouseCount' => (int) $db->query("SELECT COUNT(*) AS c FROM warehouses")->fetch()['c'],
            'employeeCount' => (int) $db->query("SELECT COUNT(*) AS c FROM users")->fetch()['c'],
        ];
    }

    private function splitRevenueByCurrency(PDO $db, int $storeId, Product $productModel): array
    {
        $sourcesList = "'" . implode("','", externalOrderSources()) . "'";

        $stmt = $db->prepare(
            "SELECT o.source, o.price, o.quantity,
                    p.external_product_id, p.external_wc_product_id, p.external_bc_product_id,
                    p.external_ps_product_id, p.external_ocart_product_id, p.external_osc_product_id
             FROM orders o
             LEFT JOIN products p ON p.id = o.product_id
             WHERE o.store_id = ? AND o.status != 'cancelled'"
        );
        $stmt->execute([$storeId]);
        $rows = $stmt->fetchAll();

        $usdTotal = 0.0;
        $pkrTotal = 0.0;

        foreach ($rows as $row) {
            $isExternalSource = in_array($row['source'] ?? 'manual', externalOrderSources(), true);
            $isExternalProduct = !empty($row['external_product_id'])
                || !empty($row['external_wc_product_id'])
                || !empty($row['external_bc_product_id'])
                || !empty($row['external_ps_product_id'])
                || !empty($row['external_ocart_product_id'])
                || !empty($row['external_osc_product_id']);

            $lineTotal = (float) $row['price'] * (int) $row['quantity'];

            if ($isExternalSource || $isExternalProduct) {
                $usdTotal += $lineTotal;
            } else {
                $pkrTotal += $lineTotal;
            }
        }

        return [$usdTotal, $pkrTotal];
    }
}