<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Database.php';

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
        $store = $this->getCurrentStore();
        $storeId = (int) $store['id'];
        $role = $this->currentRole();

        $data = [
            'role' => $role,
            'userName' => $_SESSION['user']['name'] ?? 'User',
            'storeName' => $store['name'] ?? 'Finovo',
        ];

        // ---------- Shared stats (used by more than one role) ----------

        $totalOrdersStmt = $db->prepare("SELECT COUNT(*) as c FROM orders WHERE store_id = ?");
        $totalOrdersStmt->execute([$storeId]);
        $data['totalOrders'] = (int) $totalOrdersStmt->fetch()['c'];

        $revenueStmt = $db->prepare("SELECT COALESCE(SUM(price * quantity), 0) as total FROM orders WHERE store_id = ?");
        $revenueStmt->execute([$storeId]);
        $data['totalRevenue'] = (float) $revenueStmt->fetch()['total'];

        $recentOrdersStmt = $db->prepare("SELECT * FROM orders WHERE store_id = ? ORDER BY id DESC LIMIT 8");
        $recentOrdersStmt->execute([$storeId]);
        $data['recentOrders'] = $recentOrdersStmt->fetchAll();

        // Orders per day, last 7 days (for line chart)
        $trendStmt = $db->prepare(
            "SELECT DATE(created_at) as d, COUNT(*) as c, COALESCE(SUM(price * quantity), 0) as revenue
             FROM orders
             WHERE store_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 6 DAY)
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

        // ---------- Role-specific stats ----------

        if (in_array($role, ['admin', 'manager'], true)) {
            $sourceStmt = $db->prepare("SELECT source, COUNT(*) as c FROM orders WHERE store_id = ? GROUP BY source");
            $sourceStmt->execute([$storeId]);
            $data['ordersBySource'] = $sourceStmt->fetchAll();

            $topProductsStmt = $db->prepare(
                "SELECT product_name, SUM(quantity) as qty FROM orders WHERE store_id = ? GROUP BY product_name ORDER BY qty DESC LIMIT 5"
            );
            $topProductsStmt->execute([$storeId]);
            $data['topProducts'] = $topProductsStmt->fetchAll();

            $warehouseStmt = $db->prepare(
                "SELECT w.name, COALESCE(SUM(pws.stock_quantity), 0) as total
                 FROM warehouses w
                 INNER JOIN store_warehouses sw ON sw.warehouse_id = w.id
                 LEFT JOIN product_warehouse_stock pws ON pws.warehouse_id = w.id
                 WHERE sw.store_id = ?
                 GROUP BY w.id, w.name"
            );
            $warehouseStmt->execute([$storeId]);
            $data['warehouseStock'] = $warehouseStmt->fetchAll();

            $lowStockStmt = $db->prepare("SELECT COUNT(*) as c FROM products WHERE store_id = ? AND stock_quantity <= low_stock_threshold");
            $lowStockStmt->execute([$storeId]);
            $data['lowStockCount'] = (int) $lowStockStmt->fetch()['c'];

            $employeeCountStmt = $db->query("SELECT COUNT(*) as c FROM users");
            $data['employeeCount'] = (int) $employeeCountStmt->fetch()['c'];

            $storeCountStmt = $db->query("SELECT COUNT(*) as c FROM stores");
            $data['storeCount'] = (int) $storeCountStmt->fetch()['c'];
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
                 LEFT JOIN product_warehouse_stock pws ON pws.warehouse_id = w.id
                 WHERE sw.store_id = ?
                 GROUP BY w.id, w.name"
            );
            $warehouseStmt->execute([$storeId]);
            $data['warehouseStock'] = $warehouseStmt->fetchAll();

            $lowStockListStmt = $db->prepare(
                "SELECT name, sku, stock_quantity, low_stock_threshold FROM products
                 WHERE store_id = ? AND stock_quantity <= low_stock_threshold
                 ORDER BY stock_quantity ASC LIMIT 8"
            );
            $lowStockListStmt->execute([$storeId]);
            $data['lowStockList'] = $lowStockListStmt->fetchAll();
        }

        if ($role === 'sales staff') {
            $monthRevenueStmt = $db->prepare(
                "SELECT COALESCE(SUM(price * quantity), 0) as total FROM orders
                 WHERE store_id = ? AND MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())"
            );
            $monthRevenueStmt->execute([$storeId]);
            $data['monthRevenue'] = (float) $monthRevenueStmt->fetch()['total'];

            $paymentStmt = $db->prepare("SELECT payment_status, COUNT(*) as c FROM orders WHERE store_id = ? GROUP BY payment_status");
            $paymentStmt->execute([$storeId]);
            $data['ordersByPayment'] = $paymentStmt->fetchAll();
        }

        $this->view('dashboard/index', $data);
    }
}