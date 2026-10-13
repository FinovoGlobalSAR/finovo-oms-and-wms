<?php
$roleLabel = ucwords($role);
$currencySymbol = 'Rs.';

// Platform ka naam + icon + rang (Stores page jaisa)
$platformInfo = [
    'shopify'     => ['label' => 'Shopify',        'icon' => 'bi-bag',               'bg' => '#e6f4ea', 'fg' => '#16a34a'],
    'woocommerce' => ['label' => 'WooCommerce',    'icon' => 'bi-cart3',             'bg' => '#f5f3ff', 'fg' => '#7c3aed'],
    'bigcommerce' => ['label' => 'BigCommerce',    'icon' => 'bi-shop-window',       'bg' => '#e0e7ff', 'fg' => '#3730a3'],
    'prestashop'  => ['label' => 'PrestaShop',     'icon' => 'bi-bag-heart',         'bg' => '#fce7f3', 'fg' => '#9d174d'],
    'opencart'    => ['label' => 'OpenCart',       'icon' => 'bi-cart',              'bg' => '#e0f2fe', 'fg' => '#075985'],
    'oscommerce'  => ['label' => 'osCommerce',     'icon' => 'bi-basket',            'bg' => '#ecfccb', 'fg' => '#3f6212'],
    'wix'         => ['label' => 'Wix',            'icon' => 'bi-window',            'bg' => '#f1f5f9', 'fg' => '#0f172a'],
    'ebay'        => ['label' => 'eBay',           'icon' => 'bi-tag',               'bg' => '#fef3c7', 'fg' => '#92400e'],
    'cj'          => ['label' => 'CJdropshipping', 'icon' => 'bi-truck',             'bg' => '#ffedd5', 'fg' => '#9a3412'],
    'magento'     => ['label' => 'Magento',        'icon' => 'bi-box2',              'bg' => '#ffe4e6', 'fg' => '#be123c'],
    'custom'      => ['label' => 'Custom Store',   'icon' => 'bi-code-slash',        'bg' => '#f1f5f9', 'fg' => '#334155'],
    'manual'      => ['label' => 'Manual',         'icon' => 'bi-file-earmark-text', 'bg' => '#f1f5f9', 'fg' => '#334155'],
];
$statusInfo = [
    'active'     => ['label' => 'Active',           'color' => '#047857'],
    'incomplete' => ['label' => 'Setup Incomplete', 'color' => '#e48508'],
    'sync_error' => ['label' => 'Sync Error',       'color' => '#dc2626'],
];

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
?>

<style>
    .dash-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 22px; }
    .dash-grid { display: grid; gap: 16px; margin-bottom: 22px; }
    .dash-grid-2 { grid-template-columns: minmax(0, 2fr) minmax(0, 1fr); }
    .dash-grid-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .dash-grid-wh { grid-template-columns: minmax(0, 1fr) minmax(0, 1.5fr); }
    .dash-grid-neutral { grid-template-columns: minmax(0, 1.7fr) minmax(0, 1fr); }
    @media (max-width: 1100px) { .dash-grid-2, .dash-grid-3, .dash-grid-wh, .dash-grid-neutral { grid-template-columns: minmax(0, 1fr); } }
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

    /* ----- Neutral dashboard (koi store select nahi) ----- */
    .dash-hero {
        position: relative; overflow: hidden; display: flex; align-items: center; gap: 22px; flex-wrap: wrap;
        padding: 26px 28px; margin-bottom: 22px; border-radius: 20px; border: 1px solid var(--primary-border);
        background: linear-gradient(120deg, #eff6ff 0%, #f8fbff 55%, #ffffff 100%);
    }
    .dash-hero::after {
        content: ''; position: absolute; right: -60px; top: -80px; width: 260px; height: 260px; border-radius: 50%;
        background: radial-gradient(circle, rgba(37, 99, 235, 0.12) 0%, rgba(37, 99, 235, 0) 70%); pointer-events: none;
    }
    .dash-hero-icon { width: 56px; height: 56px; flex-shrink: 0; border-radius: 16px; background: var(--primary); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 26px; box-shadow: 0 12px 24px -12px rgba(37, 99, 235, 0.8); }
    .dash-hero-body { flex: 1; min-width: 240px; position: relative; z-index: 1; }
    .dash-hero-body h2 { margin: 0 0 6px; font-size: 19px; font-weight: 700; color: var(--text-dark); }
    .dash-hero-body p { margin: 0; font-size: 14px; line-height: 1.55; color: var(--text-body); max-width: 620px; }
    .dash-hero-actions { display: flex; gap: 10px; flex-wrap: wrap; position: relative; z-index: 1; }

    .store-pick { display: flex; align-items: center; gap: 14px; padding: 14px 20px; border-top: 1px solid var(--border-color); }
    .store-pick:first-child { border-top: none; }
    .store-pick-icon { width: 40px; height: 40px; flex-shrink: 0; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px; }
    .store-pick-body { flex: 1; min-width: 0; }
    .store-pick-name { font-size: 14px; font-weight: 600; color: var(--text-dark); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .store-pick-meta { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; margin-top: 3px; font-size: 12px; color: var(--text-body); }
    .store-pick-meta .ui-dot { font-size: 12px; }
    .store-pick .ui-btn { flex-shrink: 0; }
    @media (max-width: 600px) {
        .store-pick { flex-wrap: wrap; }
        .store-pick .ui-btn { width: 100%; }
    }

    .steps { list-style: none; margin: 0; padding: 0 20px 20px; display: flex; flex-direction: column; gap: 14px; }
    .steps li { display: flex; gap: 12px; align-items: flex-start; }
    .step-num { width: 26px; height: 26px; flex-shrink: 0; border-radius: 50%; border: 1.5px solid var(--primary-border); background: #fff; color: var(--primary); font-size: 12px; font-weight: 700; display: flex; align-items: center; justify-content: center; }
    .steps li.done .step-num { background: var(--primary); border-color: var(--primary); color: #fff; }
    .steps strong { display: block; font-size: 13.5px; font-weight: 600; color: var(--text-dark); }
    .steps span { display: block; font-size: 12px; line-height: 1.45; color: var(--text-body); margin-top: 2px; }
    .steps li.done strong { color: var(--text-body); }

    /* ----- Store dashboard: "kis store ka data hai" wali patti ----- */
    .store-strip { display: flex; align-items: center; gap: 14px; flex-wrap: wrap; padding: 14px 18px; margin-bottom: 22px; background: #fff; border: 1px solid var(--border-color); border-radius: 16px; }
    .store-strip-body { flex: 1; min-width: 200px; }
    .store-strip-label { font-size: 11px; font-weight: 600; letter-spacing: .04em; text-transform: uppercase; color: var(--text-light); }
    .store-strip-name { font-size: 16px; font-weight: 700; color: var(--text-dark); margin-top: 2px; }
    .store-strip-meta { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; font-size: 12px; color: var(--text-body); }
    .store-strip-actions { display: flex; gap: 8px; flex-wrap: wrap; }
</style>

<?php if (!empty($neutral)): ?>
<?php
    // ================= NEUTRAL DASHBOARD =================
    // Koi store "Manage" nahi kiya → kisi store ka revenue / orders nahi dikhate
    $anyWarehouseLinked = false;
    foreach ($stores as $st) {
        if ((int) $st['warehouse_count'] > 0) { $anyWarehouseLinked = true; break; }
    }
    $pickStores = array_slice($stores, 0, 6); // sab se naye 6 stores
    $steps = [
        ['done' => $storeCount > 0,      'title' => 'Add a store',             'text' => 'Create a store for each sales channel on the Stores page.'],
        ['done' => $connectedCount > 0,  'title' => 'Connect a platform',      'text' => 'Link Shopify, WooCommerce or another platform so orders sync in.'],
        ['done' => $anyWarehouseLinked,  'title' => 'Link a warehouse',        'text' => 'Attach at least one warehouse so stock can be tracked.'],
        ['done' => false,                'title' => 'Open a store dashboard',  'text' => 'Pick a store to see its revenue, orders and stock.'],
    ];
?>

<div class="ui-head">
    <div class="ui-head-left">
        <div class="breadcrumb">Finovo <i class="bi bi-chevron-right"></i> <span class="current">Dashboard</span></div>
        <h1>Welcome back, <?= htmlspecialchars($userName) ?> <span class="count-badge"><?= htmlspecialchars($roleLabel) ?></span></h1>
        <p>This is your workspace overview. Choose a store to see its revenue, orders and stock.</p>
    </div>
</div>

<div class="dash-hero">
    <div class="dash-hero-icon"><i class="bi bi-shop"></i></div>
    <div class="dash-hero-body">
        <h2>No store selected</h2>
        <p>Revenue, orders and stock are shown per store, so nothing is mixed up. Open a store below and its numbers will appear here.</p>
    </div>
    <div class="dash-hero-actions">
        <?php if ($storeCount > 0): ?>
            <a href="#storePicker" class="ui-btn ui-btn-primary"><i class="bi bi-grid"></i> Choose a store</a>
        <?php endif; ?>
        <a href="/stores" class="ui-btn ui-btn-sm" style="height:40px; border-radius:10px;"><i class="bi bi-plus-lg"></i> Add or connect a store</a>
    </div>
</div>

<div class="dash-stats">
    <?php echo statCard('Stores', (string) $storeCount, 'bi-shop', 'In workspace'); ?>
    <?php echo statCard('Connected Platforms', (string) $connectedCount, 'bi-plug', $connectedCount > 0 ? 'Syncing' : 'None yet', $connectedCount > 0 ? 'ui-pill-green' : 'ui-pill-gray'); ?>
    <?php echo statCard('Warehouses', (string) $warehouseCount, 'bi-house-door', 'Locations'); ?>
    <?php echo statCard('Employees', (string) $employeeCount, 'bi-people', 'Team'); ?>
</div>

<?php if ($attentionCount > 0): ?>
    <div class="ui-alert" style="margin-bottom:22px;">
        <div class="ui-tip-icon"><i class="bi bi-exclamation-triangle"></i></div>
        <div class="ui-alert-body">
            <strong><?= $attentionCount ?> store<?= $attentionCount > 1 ? 's need' : ' needs' ?> attention</strong>
            <span>Some stores are not fully set up yet (platform not connected, no warehouse linked, or a sync error).</span>
        </div>
        <a href="/stores" class="ui-btn ui-btn-sm">Review stores</a>
    </div>
<?php endif; ?>

<div class="dash-grid dash-grid-neutral">

    <!-- Store chuno -->
    <div class="ui-card" id="storePicker">
        <div class="ui-card-head" style="padding-bottom:14px;">
            <div class="ui-card-title">
                <div class="ui-card-title-icon"><i class="bi bi-grid"></i></div>
                <div>
                    <h2>Choose a store</h2>
                    <div class="sub">Open a store to see its own dashboard</div>
                </div>
            </div>
            <?php if ($storeCount > count($pickStores)): ?>
                <a href="/stores" class="ui-btn ui-btn-sm">View all <?= $storeCount ?> <i class="bi bi-arrow-right"></i></a>
            <?php endif; ?>
        </div>

        <?php if (empty($pickStores)): ?>
            <div class="ui-empty" style="padding:30px 20px; text-align:center;">
                <i class="bi bi-shop"></i> No stores yet. <a href="/stores">Add your first store</a>.
            </div>
        <?php else: ?>
            <div>
                <?php foreach ($pickStores as $st):
                    $pKey = strtolower($st['platform'] ?? 'manual');
                    $p = $platformInfo[$pKey] ?? ['label' => ucfirst($pKey), 'icon' => 'bi-shop', 'bg' => '#f1f5f9', 'fg' => '#334155'];
                    $stStatus = $statusInfo[$st['ui_status']];
                ?>
                    <div class="store-pick">
                        <div class="store-pick-icon" style="background:<?= $p['bg'] ?>; color:<?= $p['fg'] ?>;"><i class="bi <?= $p['icon'] ?>"></i></div>
                        <div class="store-pick-body">
                            <div class="store-pick-name"><?= htmlspecialchars($st['name']) ?></div>
                            <div class="store-pick-meta">
                                <span><?= htmlspecialchars($p['label']) ?></span>
                                <span class="ui-dot" style="color:<?= $stStatus['color'] ?>;"><?= $stStatus['label'] ?></span>
                                <span><?= (int) $st['warehouse_count'] ?> warehouse<?= (int) $st['warehouse_count'] === 1 ? '' : 's' ?></span>
                                <span><?= (int) $st['product_count'] ?> product<?= (int) $st['product_count'] === 1 ? '' : 's' ?></span>
                            </div>
                        </div>
                        <a href="/stores/manage?store_id=<?= (int) $st['id'] ?>" class="ui-btn ui-btn-soft">Open dashboard <i class="bi bi-arrow-right"></i></a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Shuru kaise karein -->
    <div class="ui-card">
        <div class="ui-card-head" style="padding-bottom:16px;">
            <div class="ui-card-title">
                <div class="ui-card-title-icon"><i class="bi bi-list-check"></i></div>
                <div>
                    <h2>Getting started</h2>
                    <div class="sub">Set up a store to see live numbers</div>
                </div>
            </div>
        </div>
        <ol class="steps">
            <?php foreach ($steps as $i => $step): ?>
                <li class="<?= $step['done'] ? 'done' : '' ?>">
                    <div class="step-num"><?= $step['done'] ? '<i class="bi bi-check-lg"></i>' : $i + 1 ?></div>
                    <div>
                        <strong><?= $step['title'] ?></strong>
                        <span><?= $step['text'] ?></span>
                    </div>
                </li>
            <?php endforeach; ?>
        </ol>
    </div>

</div>
<?php return; ?>
<?php endif; ?>

<?php
// ================= STORE DASHBOARD (store select ho chuka) =================
$weekTotal = array_sum($trendCounts);
$storeP = $platformInfo[$storePlatform] ?? ['label' => ucfirst($storePlatform), 'icon' => 'bi-shop', 'bg' => '#f1f5f9', 'fg' => '#334155'];
$isManualStore = in_array($storePlatform, ['manual', ''], true);
if ($storeHealth === 'sync_error') {
    $storeStatus = $statusInfo['sync_error'];
} elseif (!$isManualStore && $storeHealth !== 'connected') {
    $storeStatus = ['label' => 'Not connected', 'color' => '#e48508'];
} else {
    $storeStatus = $statusInfo['active'];
}
?>

<div class="ui-head">
    <div class="ui-head-left">
        <div class="breadcrumb">Finovo <i class="bi bi-chevron-right"></i> <span class="current">Dashboard</span></div>
        <h1>Welcome back, <?= htmlspecialchars($userName) ?> <span class="count-badge"><?= htmlspecialchars($roleLabel) ?></span></h1>
        <p>Here's what's happening with <?= htmlspecialchars($storeName) ?> today.</p>
    </div>
</div>

<!-- Kis store ka data dikh raha hai -->
<div class="store-strip">
    <div class="store-pick-icon" style="background:<?= $storeP['bg'] ?>; color:<?= $storeP['fg'] ?>;"><i class="bi <?= $storeP['icon'] ?>"></i></div>
    <div class="store-strip-body">
        <div class="store-strip-label">Showing data for</div>
        <div class="store-strip-name"><?= htmlspecialchars($storeName) ?></div>
        <div class="store-strip-meta">
            <span><?= htmlspecialchars($storeP['label']) ?></span>
            <span class="ui-dot" style="color:<?= $storeStatus['color'] ?>;"><?= $storeStatus['label'] ?></span>
            <?php if (!empty($storeLastSync)): ?>
                <span>Last sync <?= htmlspecialchars(date('d M, H:i', strtotime($storeLastSync))) ?></span>
            <?php endif; ?>
        </div>
    </div>
    <?php if (in_array($role, ['admin', 'manager'], true)): ?>
        <div class="store-strip-actions">
            <a href="/stores/exit-management" class="ui-btn ui-btn-sm"><i class="bi bi-arrow-left-right"></i> Switch store</a>
            <a href="/stores" class="ui-btn ui-btn-sm"><i class="bi bi-gear"></i> Store settings</a>
        </div>
    <?php endif; ?>
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

    <?php echo statCard('Open Orders', (string) $pendingOrders, 'bi-hourglass-split', $pendingOrders > 0 ? 'To process' : 'All clear', $pendingOrders > 0 ? 'ui-pill-amber' : 'ui-pill-green'); ?>

    <?php if (in_array($role, ['admin', 'manager'], true)): ?>
        <?php echo statCard('Low Stock Items', (string) $lowStockCount, 'bi-exclamation-triangle', $lowStockCount > 0 ? 'Needs restock' : 'Healthy', $lowStockCount > 0 ? 'ui-pill-amber' : 'ui-pill-green'); ?>
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
            <?php if (in_array(strtolower(trim($_SESSION['user']['role'] ?? '')), ['admin', 'manager', 'warehouse staff'], true)): ?>
                <a href="/warehouses" class="ui-btn ui-btn-sm">View</a>
            <?php endif; ?>
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
            <a href="/products" class="ui-btn ui-btn-sm">Go to Products</a>
        </div>
        <div style="overflow-x:auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Stock</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($lowStockList)): ?>
                        <tr><td colspan="2" class="ui-empty"><i class="bi bi-check2-circle"></i>No low stock items.</td></tr>
                    <?php else: ?>
                        <?php foreach ($lowStockList as $p): ?>
                            <tr>
                                <td style="font-weight:600;"><?= htmlspecialchars($p['name']) ?></td>
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
                <div class="sub">Latest 5 orders in this store</div>
            </div>
        </div>
        <?php if (in_array($role, ['admin', 'manager', 'sales staff'], true)): ?>
            <a href="/orders" class="ui-btn ui-btn-sm">Go to Orders <i class="bi bi-arrow-right"></i></a>
        <?php endif; ?>
    </div>
    <div style="overflow-x:auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Customer</th>
                    <th>Product</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentOrders)): ?>
                    <tr><td colspan="4" class="ui-empty"><i class="bi bi-receipt"></i>No orders yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($recentOrders as $o):
                        $statusKey = $o['status'] ?? 'pending';
                        $statusColor = $statusColors[$statusKey] ?? ['bg' => '#f1f5f9', 'text' => '#334155'];
                    ?>
                        <tr>
                            <td style="font-weight:700;">#<?= (int) $o['id'] ?></td>
                            <td style="color:#334155;"><?= htmlspecialchars($o['customer_name'] ?? '-') ?></td>
                            <td style="color:#334155;"><?= htmlspecialchars($o['product_name'] ?? '-') ?></td>
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