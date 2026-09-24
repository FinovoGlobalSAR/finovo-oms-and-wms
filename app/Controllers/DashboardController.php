<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../Models/Product.php';

class DashboardController extends Controller
{
    // In sources se aane wale orders ($) — baki sab manual/Rs.
    private array $externalSources = ['shopify_pull', 'woocommerce_pull', 'bigcommerce_pull', 'prestashop_pull', 'opencart_pull', 'oscommerce_pull'];

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
        $productModel = new Product();

        $data = [
            'role' => $role,
            'userName' => $_SESSION['user']['name'] ?? 'User',
            'storeName' => $store['name'] ?? 'Finovo',
        ];

        // ---------- Shared stats (used by more than one role) ----------

        $totalOrdersStmt = $db->prepare("SELECT COUNT(*) as c FROM orders WHERE store_id = ?");
        $totalOrdersStmt->execute([$storeId]);
        $data['totalOrders'] = (int) $totalOrdersStmt->fetch()['c'];

        // Revenue ko currency ke hisab se alag-alag jodte hain — order khud
        // external source se aaya ho, YA uska product khud kisi external
        // platform se sync hua ho, dono cases mein wo "$" revenue mein aata hai.
        [$data['totalRevenueUsd'], $data['totalRevenuePkr']] = $this->splitRevenueByCurrency($db, $storeId, $productModel);

        $recentOrdersStmt = $db->prepare("SELECT * FROM orders WHERE store_id = ? ORDER BY id DESC LIMIT 8");
        $recentOrdersStmt->execute([$storeId]);
        $recentOrders = $recentOrdersStmt->fetchAll();

        foreach ($recentOrders as &$ro) {
            $isExternalSource = in_array($ro['source'] ?? 'manual', $this->externalSources, true);
            $isExternalProduct = !empty($ro['product_id']) && $productModel->isExternal((int) $ro['product_id']);
            $ro['currency_symbol'] = ($isExternalSource || $isExternalProduct) ? '$' : 'Rs.';
        }
        unset($ro);

        $data['recentOrders'] = $recentOrders;

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

    /**
     * Store ke orders ko unke currency ke hisab se do totals mein baantta
     * hai: [$ wala total, Rs. wala total]. Order khud external source se
     * aaya ho, ya uska product external ho — dono "$" ban jaate hain.
     */
    private function splitRevenueByCurrency(PDO $db, int $storeId, Product $productModel): array
    {
        $sourcesList = "'" . implode("','", $this->externalSources) . "'";

        $stmt = $db->prepare(
            "SELECT o.source, o.price, o.quantity,
                    p.external_product_id, p.external_wc_product_id, p.external_bc_product_id,
                    p.external_ps_product_id, p.external_ocart_product_id, p.external_osc_product_id
             FROM orders o
             LEFT JOIN products p ON p.id = o.product_id
             WHERE o.store_id = ?"
        );
        $stmt->execute([$storeId]);
        $rows = $stmt->fetchAll();

        $usdTotal = 0.0;
        $pkrTotal = 0.0;

        foreach ($rows as $row) {
            $isExternalSource = in_array($row['source'] ?? 'manual', $this->externalSources, true);
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