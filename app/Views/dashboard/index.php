<?php
$roleLabel = ucwords($role);
$currencySymbol = 'Rs.';

$statusColors = [
    'pending'    => ['bg' => '#fef3c7', 'text' => '#b45309'],
    'processing' => ['bg' => '#dbeafe', 'text' => '#1d4ed8'],
    'completed'  => ['bg' => '#dcfce7', 'text' => '#15803d'],
    'delivered'  => ['bg' => '#dcfce7', 'text' => '#15803d'],
    'cancelled'  => ['bg' => '#fee2e2', 'text' => '#991b1b'],
];

function statCard(string $label, string $value, string $icon, string $color): string
{
    return "
    <div class=\"card\" style=\"padding:20px; flex:1; min-width:200px; display:flex; align-items:center; gap:14px;\">
        <div style=\"width:44px; height:44px; border-radius:12px; background:{$color}20; color:{$color}; display:flex; align-items:center; justify-content:center; font-size:20px; flex-shrink:0;\">
            <i class=\"bi {$icon}\"></i>
        </div>
        <div>
            <div style=\"font-size:12px; color:var(--text-muted); margin-bottom:2px;\">{$label}</div>
            <div style=\"font-size:22px; font-weight:700; color:var(--text-dark);\">{$value}</div>
        </div>
    </div>";
}
?>

<div class="breadcrumb">Finovo <i class="bi bi-chevron-right"></i> Dashboard</div>

<div class="page-header-row">
    <h1>Welcome back, <?= htmlspecialchars($userName) ?> <span class="count-badge"><?= htmlspecialchars($roleLabel) ?></span></h1>
</div>
<p class="page-subtitle">Here's what's happening with <?= htmlspecialchars($storeName) ?> today.</p>

<!-- ============ STAT CARDS (all roles) ============ -->
<div style="display:flex; gap:16px; flex-wrap:wrap; margin-bottom:20px;">
    <?php echo statCard('Total Orders', (string) $totalOrders, 'bi-bag-check', '#4338ca'); ?>
    <?php echo statCard('Total Revenue', $currencySymbol . ' ' . number_format($totalRevenue, 2), 'bi-cash-stack', '#15803d'); ?>

    <?php if (in_array($role, ['admin', 'manager'], true)): ?>
        <?php echo statCard('Low Stock Items', (string) $lowStockCount, 'bi-exclamation-triangle', '#b45309'); ?>
        <?php echo statCard('Employees', (string) $employeeCount, 'bi-people', '#0e7490'); ?>
        <?php echo statCard('Stores', (string) $storeCount, 'bi-shop', '#be185d'); ?>
    <?php endif; ?>

    <?php if ($role === 'warehouse staff'): ?>
        <?php echo statCard('Total Products', (string) $productCount, 'bi-box-seam', '#4338ca'); ?>
        <?php echo statCard('Low Stock', (string) $lowStockCount, 'bi-exclamation-triangle', '#b45309'); ?>
        <?php echo statCard('Out of Stock', (string) $outOfStockCount, 'bi-x-circle', '#991b1b'); ?>
    <?php endif; ?>

    <?php if ($role === 'sales staff'): ?>
        <?php echo statCard('Revenue This Month', $currencySymbol . ' ' . number_format($monthRevenue, 2), 'bi-graph-up', '#15803d'); ?>
    <?php endif; ?>
</div>

<!-- ============ CHARTS ROW ============ -->
<div style="display:flex; gap:16px; flex-wrap:wrap; margin-bottom:20px;">

    <!-- Orders trend (all roles) -->
    <div class="card" style="padding:20px; flex:2; min-width:340px;">
        <h3 style="font-size:14px; font-weight:600; margin-bottom:14px; color:var(--text-dark);">Orders — Last 7 Days</h3>
        <canvas id="trendChart" height="110"></canvas>
    </div>

    <!-- Orders by status (all roles) -->
    <div class="card" style="padding:20px; flex:1; min-width:260px;">
        <h3 style="font-size:14px; font-weight:600; margin-bottom:14px; color:var(--text-dark);">Orders by Status</h3>
        <canvas id="statusChart" height="200"></canvas>
    </div>

</div>

<?php if (in_array($role, ['admin', 'manager'], true)): ?>
<div style="display:flex; gap:16px; flex-wrap:wrap; margin-bottom:20px;">

    <div class="card" style="padding:20px; flex:1; min-width:280px;">
        <h3 style="font-size:14px; font-weight:600; margin-bottom:14px; color:var(--text-dark);">Orders by Source</h3>
        <canvas id="sourceChart" height="200"></canvas>
    </div>

    <div class="card" style="padding:20px; flex:1; min-width:280px;">
        <h3 style="font-size:14px; font-weight:600; margin-bottom:14px; color:var(--text-dark);">Stock by Warehouse</h3>
        <canvas id="warehouseChart" height="200"></canvas>
    </div>

    <div class="card" style="padding:20px; flex:1; min-width:280px;">
        <h3 style="font-size:14px; font-weight:600; margin-bottom:14px; color:var(--text-dark);">Top 5 Products</h3>
        <?php if (empty($topProducts)): ?>
            <p style="color:var(--text-muted); font-size:13px;">No order data yet.</p>
        <?php else: ?>
            <?php $maxQty = max(array_column($topProducts, 'qty')); ?>
            <?php foreach ($topProducts as $p): ?>
                <div style="margin-bottom:12px;">
                    <div style="display:flex; justify-content:space-between; font-size:13px; margin-bottom:4px;">
                        <span><?= htmlspecialchars($p['product_name']) ?></span>
                        <span style="color:var(--text-muted);"><?= (int) $p['qty'] ?> sold</span>
                    </div>
                    <div style="background:#f3f4f6; border-radius:6px; height:8px; overflow:hidden;">
                        <div style="background:#4338ca; height:100%; width:<?= $maxQty > 0 ? round(($p['qty'] / $maxQty) * 100) : 0 ?>%;"></div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>
<?php endif; ?>

<?php if ($role === 'warehouse staff'): ?>
<div style="display:flex; gap:16px; flex-wrap:wrap; margin-bottom:20px;">

    <div class="card" style="padding:20px; flex:1; min-width:280px;">
        <h3 style="font-size:14px; font-weight:600; margin-bottom:14px; color:var(--text-dark);">Stock by Warehouse</h3>
        <canvas id="warehouseChart" height="200"></canvas>
    </div>

    <div class="card" style="padding:0; flex:1.5; min-width:320px; overflow:hidden;">
        <h3 style="font-size:14px; font-weight:600; padding:20px 20px 0; color:var(--text-dark);">Low Stock Products</h3>
        <table class="data-table" style="margin-top:10px;">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>SKU</th>
                    <th>Stock</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($lowStockList)): ?>
                    <tr><td colspan="3" style="text-align:center; color:#6b7280; padding:20px;">No low stock items.</td></tr>
                <?php else: ?>
                    <?php foreach ($lowStockList as $p): ?>
                        <tr>
                            <td><?= htmlspecialchars($p['name']) ?></td>
                            <td><?= htmlspecialchars($p['sku'] ?? '-') ?></td>
                            <td>
                                <span class="source-badge" style="background:<?= $p['stock_quantity'] <= 0 ? '#fee2e2' : '#fef3c7' ?>; color:<?= $p['stock_quantity'] <= 0 ? '#991b1b' : '#b45309' ?>;">
                                    <?= (int) $p['stock_quantity'] ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>
<?php endif; ?>

<!-- ============ RECENT ORDERS (all roles) ============ -->
<div class="card" style="padding:0; overflow:hidden;">
    <h3 style="font-size:14px; font-weight:600; padding:20px 20px 0; color:var(--text-dark);">Recent Orders</h3>
    <table class="data-table" style="margin-top:10px;">
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
                <tr><td colspan="6" style="text-align:center; color:#6b7280; padding:24px;">No orders yet.</td></tr>
            <?php else: ?>
                <?php foreach ($recentOrders as $o):
                    $statusKey = $o['status'] ?? 'pending';
                    $statusColor = $statusColors[$statusKey] ?? ['bg' => '#f3f4f6', 'text' => '#374151'];
                ?>
                    <tr>
                        <td>#<?= (int) $o['id'] ?></td>
                        <td><?= htmlspecialchars($o['customer_name'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($o['product_name'] ?? '-') ?></td>
                        <td><?= (int) $o['quantity'] ?></td>
                        <td>Rs. <?= number_format((float) $o['price'], 2) ?></td>
                        <td>
                            <span class="source-badge" style="background:<?= $statusColor['bg'] ?>; color:<?= $statusColor['text'] ?>;">
                                <?= htmlspecialchars(ucfirst($statusKey)) ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const trendCtx = document.getElementById('trendChart');
new Chart(trendCtx, {
    type: 'line',
    data: {
        labels: <?= json_encode($trendLabels) ?>,
        datasets: [{
            label: 'Orders',
            data: <?= json_encode($trendCounts) ?>,
            borderColor: '#4338ca',
            backgroundColor: 'rgba(67,56,202,0.08)',
            tension: 0.35,
            fill: true,
            pointRadius: 3,
        }]
    },
    options: {
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
    }
});

const statusCtx = document.getElementById('statusChart');
new Chart(statusCtx, {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_map(fn($s) => ucfirst($s['status']), $ordersByStatus)) ?>,
        datasets: [{
            data: <?= json_encode(array_map(fn($s) => (int) $s['c'], $ordersByStatus)) ?>,
            backgroundColor: ['#fef3c7', '#dbeafe', '#dcfce7', '#fee2e2', '#e0e7ff'].slice(0, <?= count($ordersByStatus) ?>),
            borderWidth: 0,
        }]
    },
    options: {
        plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } } }
    }
});

<?php if (in_array($role, ['admin', 'manager'], true)): ?>
const sourceCtx = document.getElementById('sourceChart');
new Chart(sourceCtx, {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_map(fn($s) => ucwords(str_replace('_', ' ', $s['source'])), $ordersBySource)) ?>,
        datasets: [{
            label: 'Orders',
            data: <?= json_encode(array_map(fn($s) => (int) $s['c'], $ordersBySource)) ?>,
            backgroundColor: '#6d28d9',
            borderRadius: 6,
        }]
    },
    options: {
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
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
            backgroundColor: '#0e7490',
            borderRadius: 6,
        }]
    },
    options: {
        indexAxis: 'y',
        plugins: { legend: { display: false } },
        scales: { x: { beginAtZero: true, ticks: { precision: 0 } } }
    }
});
<?php endif; ?>
</script>