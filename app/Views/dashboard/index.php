<?php
$roleLabel = ucwords($role);
$currencySymbol = 'Rs.';

$statusColors = [
    'pending'    => ['bg' => '#fef3c7', 'text' => '#92400e'],
    'processing' => ['bg' => '#dbeafe', 'text' => '#1d4ed8'],
    'completed'  => ['bg' => '#dcfce7', 'text' => '#166534'],
    'delivered'  => ['bg' => '#dcfce7', 'text' => '#166534'],
    'cancelled'  => ['bg' => '#fee2e2', 'text' => '#991b1b'],
];

if (!function_exists('statCard')) {
    function statCard(string $label, string $value, string $icon, string $pill = '', string $pillClass = 'ui-pill-blue'): string
    {
        $pillHtml = $pill !== '' ? "<span class=\"ui-pill {$pillClass}\">{$pill}</span>" : '';
        // Lambi value (jaise "$ 2,736.70") thodi chhoti font mein, taake ek hi line mein rahe
        $longClass = mb_strlen($value) > 9 ? ' is-long' : '';
        return "
        <div class=\"ui-stat\">
            <div class=\"ui-stat-icon\"><i class=\"bi {$icon}\"></i></div>
            <div style=\"min-width:0;\">
                <div class=\"ui-stat-label\">{$label}</div>
                <div class=\"ui-stat-value\"><b class=\"dash-value{$longClass}\">{$value}</b>{$pillHtml}</div>
            </div>
        </div>";
    }
}

$weekTotal = array_sum($trendCounts);
?>

<style>
    .dash-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 22px; }
    .dash-grid { display: grid; gap: 16px; margin-bottom: 22px; }
    .dash-grid-2 { grid-template-columns: minmax(0, 2fr) minmax(0, 1fr); }
    .dash-grid-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .dash-grid-wh { grid-template-columns: minmax(0, 1fr) minmax(0, 1.5fr); }
    @media (max-width: 1100px) { .dash-grid-2, .dash-grid-3, .dash-grid-wh { grid-template-columns: minmax(0, 1fr); } }
    .dash-grid .ui-card { margin-bottom: 0; }
    .dash-panel { padding: 18px 20px 20px; }
    .dash-panel-head { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 16px; }
    .dash-panel-head h2 { margin: 0; font-size: 15px; font-weight: 700; color: var(--text-dark); }
    .dash-panel-head .sub { font-size: 12px; color: #475569; margin-top: 2px; }
    .dash-bar { background: #eef2f7; border-radius: 6px; height: 8px; overflow: hidden; }
    .dash-bar > div { background: var(--primary); height: 100%; border-radius: 6px; }
    /* Stat cards: "$" aur number hamesha ek hi line mein; jagah kam ho to sirf chhota tag neeche jaye */
    .dash-stats .ui-stat-value { flex-wrap: wrap; row-gap: 4px; }
    .dash-stats .dash-value { white-space: nowrap; }
    .dash-stats .dash-value.is-long { font-size: 20px; }
</style>

<div class="ui-head">
    <div class="ui-head-left">
        <div class="breadcrumb">Finovo <i class="bi bi-chevron-right"></i> <span class="current">Dashboard</span></div>
        <h1>Welcome back, <?= htmlspecialchars($userName) ?> <span class="count-badge"><?= htmlspecialchars($roleLabel) ?></span></h1>
        <p>Here's what's happening with <?= htmlspecialchars($storeName) ?> today.</p>
    </div>
</div>

<!-- ============ STAT CARDS (all roles) ============ -->
<div class="dash-stats">
    <?php echo statCard('Total Orders', (string) $totalOrders, 'bi-bag-check', 'All time'); ?>

    <?php if ($totalRevenueUsd > 0): ?>
        <?php echo statCard('Total Revenue ($)', '$ ' . number_format($totalRevenueUsd, 2), 'bi-currency-dollar', 'External', 'ui-pill-green'); ?>
    <?php endif; ?>
    <?php if ($totalRevenuePkr > 0 || $totalRevenueUsd == 0): ?>
        <?php echo statCard('Total Revenue (Rs.)', 'Rs. ' . number_format($totalRevenuePkr, 2), 'bi-cash-stack', 'Manual', 'ui-pill-green'); ?>
    <?php endif; ?>

    <?php if (in_array($role, ['admin', 'manager'], true)): ?>
        <?php echo statCard('Low Stock Items', (string) $lowStockCount, 'bi-exclamation-triangle', $lowStockCount > 0 ? 'Needs restock' : 'Healthy', $lowStockCount > 0 ? 'ui-pill-amber' : 'ui-pill-green'); ?>
        <?php echo statCard('Employees', (string) $employeeCount, 'bi-people', 'Team'); ?>
        <?php echo statCard('Stores', (string) $storeCount, 'bi-shop', 'Connected'); ?>
    <?php endif; ?>

    <?php if ($role === 'warehouse staff'): ?>
        <?php echo statCard('Total Products', (string) $productCount, 'bi-box-seam', 'In system'); ?>
        <?php echo statCard('Low Stock', (string) $lowStockCount, 'bi-exclamation-triangle', $lowStockCount > 0 ? 'Needs restock' : 'Healthy', $lowStockCount > 0 ? 'ui-pill-amber' : 'ui-pill-green'); ?>
        <?php echo statCard('Out of Stock', (string) $outOfStockCount, 'bi-x-circle', $outOfStockCount > 0 ? 'Action needed' : 'None', $outOfStockCount > 0 ? 'ui-pill-red' : 'ui-pill-green'); ?>
    <?php endif; ?>

    <?php if ($role === 'sales staff'): ?>
        <?php echo statCard('Revenue This Month', $currencySymbol . ' ' . number_format($monthRevenue, 2), 'bi-graph-up', date('M Y'), 'ui-pill-green'); ?>
    <?php endif; ?>
</div>

<!-- ============ CHARTS ROW ============ -->
<div class="dash-grid dash-grid-2">

    <!-- Orders trend (all roles) -->
    <div class="ui-card dash-panel">
        <div class="dash-panel-head">
            <div class="ui-card-title">
                <div class="ui-card-title-icon"><i class="bi bi-graph-up"></i></div>
                <div>
                    <h2>Orders — Last 7 Days</h2>
                    <div class="sub"><?= $weekTotal ?> order(s) this week</div>
                </div>
            </div>
        </div>
        <canvas id="trendChart" height="110"></canvas>
    </div>

    <!-- Orders by status (all roles) -->
    <div class="ui-card dash-panel">
        <div class="dash-panel-head">
            <div class="ui-card-title">
                <div class="ui-card-title-icon"><i class="bi bi-pie-chart"></i></div>
                <div>
                    <h2>Orders by Status</h2>
                    <div class="sub">All orders in this store</div>
                </div>
            </div>
        </div>
        <?php if (empty($ordersByStatus)): ?>
            <p class="ui-help" style="text-align:center; padding:30px 0;">No orders yet.</p>
        <?php else: ?>
            <canvas id="statusChart" height="200"></canvas>
        <?php endif; ?>
    </div>
</div>

<?php if (in_array($role, ['admin', 'manager'], true)): ?>
<div class="dash-grid dash-grid-3">

    <div class="ui-card dash-panel">
        <div class="dash-panel-head">
            <div class="ui-card-title">
                <div class="ui-card-title-icon"><i class="bi bi-diagram-3"></i></div>
                <div>
                    <h2>Orders by Source</h2>
                    <div class="sub">Where orders come from</div>
                </div>
            </div>
        </div>
        <canvas id="sourceChart" height="200"></canvas>
    </div>

    <div class="ui-card dash-panel">
        <div class="dash-panel-head">
            <div class="ui-card-title">
                <div class="ui-card-title-icon"><i class="bi bi-house-door"></i></div>
                <div>
                    <h2>Stock by Warehouse</h2>
                    <div class="sub">Total units per warehouse</div>
                </div>
            </div>
            <a href="/warehouses" class="ui-btn ui-btn-sm">View</a>
        </div>
        <canvas id="warehouseChart" height="200"></canvas>
    </div>

    <div class="ui-card dash-panel">
        <div class="dash-panel-head">
            <div class="ui-card-title">
                <div class="ui-card-title-icon"><i class="bi bi-trophy"></i></div>
                <div>
                    <h2>Top 5 Products</h2>
                    <div class="sub">By quantity sold</div>
                </div>
            </div>
        </div>
        <?php if (empty($topProducts)): ?>
            <p class="ui-help" style="text-align:center; padding:30px 0;">No order data yet.</p>
        <?php else: ?>
            <?php $maxQty = max(array_column($topProducts, 'qty')); ?>
            <?php foreach ($topProducts as $p): ?>
                <div style="margin-bottom:14px;">
                    <div style="display:flex; justify-content:space-between; gap:10px; font-size:13px; margin-bottom:6px;">
                        <span style="color:var(--text-dark); font-weight:500; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><?= htmlspecialchars($p['product_name']) ?></span>
                        <span style="color:#475569; white-space:nowrap;"><?= (int) $p['qty'] ?> sold</span>
                    </div>
                    <div class="dash-bar"><div style="width:<?= $maxQty > 0 ? round(($p['qty'] / $maxQty) * 100) : 0 ?>%;"></div></div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>
<?php endif; ?>

<?php if ($role === 'warehouse staff'): ?>
<div class="dash-grid dash-grid-wh">

    <div class="ui-card dash-panel">
        <div class="dash-panel-head">
            <div class="ui-card-title">
                <div class="ui-card-title-icon"><i class="bi bi-house-door"></i></div>
                <div>
                    <h2>Stock by Warehouse</h2>
                    <div class="sub">Total units per warehouse</div>
                </div>
            </div>
        </div>
        <canvas id="warehouseChart" height="200"></canvas>
    </div>

    <div class="ui-card">
        <div class="ui-card-head" style="padding-bottom:14px;">
            <div class="ui-card-title">
                <div class="ui-card-title-icon" style="background:#fef3c7; color:#b45309;"><i class="bi bi-exclamation-triangle"></i></div>
                <div>
                    <h2>Low Stock Products</h2>
                    <div class="sub">Products at or below their threshold</div>
                </div>
            </div>
            <a href="/products" class="ui-btn ui-btn-sm">View all</a>
        </div>
        <div style="overflow-x:auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>SKU</th>
                        <th>Stock</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($lowStockList)): ?>
                        <tr><td colspan="3" class="ui-empty"><i class="bi bi-check2-circle"></i>No low stock items.</td></tr>
                    <?php else: ?>
                        <?php foreach ($lowStockList as $p): ?>
                            <tr>
                                <td style="font-weight:600;"><?= htmlspecialchars($p['name']) ?></td>
                                <td style="color:#475569;"><?= htmlspecialchars($p['sku'] ?? '-') ?></td>
                                <td>
                                    <span class="ui-pill <?= $p['stock_quantity'] <= 0 ? 'ui-pill-red' : 'ui-pill-amber' ?>" style="font-size:11.5px;">
                                        <?= $p['stock_quantity'] <= 0 ? 'Out of stock' : (int) $p['stock_quantity'] . ' left' ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>
<?php endif; ?>

<!-- ============ RECENT ORDERS (all roles) ============ -->
<div class="ui-card">
    <div class="ui-card-head" style="padding-bottom:14px;">
        <div class="ui-card-title">
            <div class="ui-card-title-icon"><i class="bi bi-receipt"></i></div>
            <div>
                <h2>Recent Orders</h2>
                <div class="sub">Latest 8 orders in this store</div>
            </div>
        </div>
        <?php if (in_array($role, ['admin', 'manager', 'sales staff'], true)): ?>
            <a href="/orders" class="ui-btn ui-btn-sm">View all orders <i class="bi bi-arrow-right"></i></a>
        <?php endif; ?>
    </div>
    <div style="overflow-x:auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Customer</th>
                    <th>Product</th>
                    <th>Qty</th>
                    <th>Price</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentOrders)): ?>
                    <tr><td colspan="6" class="ui-empty"><i class="bi bi-receipt"></i>No orders yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($recentOrders as $o):
                        $statusKey = $o['status'] ?? 'pending';
                        $statusColor = $statusColors[$statusKey] ?? ['bg' => '#f1f5f9', 'text' => '#334155'];
                    ?>
                        <tr>
                            <td style="font-weight:700;">#<?= (int) $o['id'] ?></td>
                            <td style="color:#334155;"><?= htmlspecialchars($o['customer_name'] ?? '-') ?></td>
                            <td style="color:#334155;"><?= htmlspecialchars($o['product_name'] ?? '-') ?></td>
                            <td style="font-weight:600;"><?= (int) $o['quantity'] ?></td>
                            <td style="white-space:nowrap;"><?= $o['currency_symbol'] ?? 'Rs.' ?> <?= number_format((float) $o['price'], 2) ?></td>
                            <td>
                                <span class="ui-tag" style="background:<?= $statusColor['bg'] ?>; color:<?= $statusColor['text'] ?>;">
                                    <?= htmlspecialchars(ucfirst($statusKey)) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
Chart.defaults.font.family = "'Inter', 'Segoe UI', system-ui, sans-serif";
Chart.defaults.color = '#64748b';
Chart.defaults.borderColor = '#eef1f5';

const trendCtx = document.getElementById('trendChart');
new Chart(trendCtx, {
    type: 'line',
    data: {
        labels: <?= json_encode($trendLabels) ?>,
        datasets: [{
            label: 'Orders',
            data: <?= json_encode($trendCounts) ?>,
            borderColor: '#1d4ed8',
            backgroundColor: 'rgba(29,78,216,0.08)',
            tension: 0.35,
            fill: true,
            pointRadius: 3,
            pointBackgroundColor: '#1d4ed8',
        }]
    },
    options: {
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { precision: 0 } }, x: { grid: { display: false } } }
    }
});

const statusCtx = document.getElementById('statusChart');
if (statusCtx) {
    new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode(array_map(fn($s) => ucfirst($s['status']), $ordersByStatus)) ?>,
            datasets: [{
                data: <?= json_encode(array_map(fn($s) => (int) $s['c'], $ordersByStatus)) ?>,
                backgroundColor: ['#1d4ed8', '#60a5fa', '#16a34a', '#f59e0b', '#dc2626', '#94a3b8'].slice(0, <?= count($ordersByStatus) ?>),
                borderWidth: 2,
                borderColor: '#ffffff',
            }]
        },
        options: {
            cutout: '65%',
            plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } } }
        }
    });
}
<?php if (in_array($role, ['admin', 'manager'], true)): ?>
const sourceCtx = document.getElementById('sourceChart');
new Chart(sourceCtx, {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_map(fn($s) => ucwords(str_replace('_', ' ', $s['source'])), $ordersBySource)) ?>,
        datasets: [{
            label: 'Orders',
            data: <?= json_encode(array_map(fn($s) => (int) $s['c'], $ordersBySource)) ?>,
            backgroundColor: '#1d4ed8',
            borderRadius: 6,
            maxBarThickness: 36,
        }]
    },
    options: {
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { precision: 0 } }, x: { grid: { display: false } } }
    }
});
<?php endif; ?>

<?php if (in_array($role, ['admin', 'manager', 'warehouse staff'], true)): ?>
const warehouseCtx = document.getElementById('warehouseChart');
new Chart(warehouseCtx, {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($warehouseStock, 'name')) ?>,
        datasets: [{
            label: 'Stock',
            data: <?= json_encode(array_map(fn($w) => (int) $w['total'], $warehouseStock)) ?>,
            backgroundColor: '#60a5fa',
            borderRadius: 6,
            maxBarThickness: 28,
        }]
    },
    options: {
        indexAxis: 'y',
        plugins: { legend: { display: false } },
        scales: { x: { beginAtZero: true, ticks: { precision: 0 } }, y: { grid: { display: false } } }
    }
});
<?php endif; ?>
</script>