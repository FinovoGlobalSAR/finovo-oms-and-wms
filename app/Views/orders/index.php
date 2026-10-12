<?php
$avatarColors = [
    ['bg' => '#dbeafe', 'text' => '#1d4ed8'],
    ['bg' => '#dcfce7', 'text' => '#15803d'],
    ['bg' => '#fce7f3', 'text' => '#be185d'],
    ['bg' => '#fef3c7', 'text' => '#b45309'],
    ['bg' => '#e0e7ff', 'text' => '#4338ca'],
    ['bg' => '#cffafe', 'text' => '#0e7490'],
];

$statusColors = [
    'pending'    => ['bg' => '#fef3c7', 'text' => '#92400e'],
    'processing' => ['bg' => '#dbeafe', 'text' => '#1d4ed8'],
    'completed'  => ['bg' => '#dcfce7', 'text' => '#166534'],
    'delivered'  => ['bg' => '#dcfce7', 'text' => '#166534'],
    'cancelled'  => ['bg' => '#fee2e2', 'text' => '#991b1b'],
];

$platform = $store['platform'] ?? 'manual';

// ---------- Summary numbers — controller POORI list se bhejta hai ($orders sirf latest 5 rows hain) ----------
$totalCount = (int) ($stats['total'] ?? count($orders));
$pendingCount = (int) ($stats['pending'] ?? 0);
$unpaidCount = (int) ($stats['unpaid'] ?? 0);
$notDispatchedCount = (int) ($stats['notDispatched'] ?? 0);
$selectedStatus = $selectedStatus ?? '';

$syncLinks = [
    'shopify'     => ['/orders/sync-shopify', 'Shopify'],
    'woocommerce' => ['/orders/sync-woocommerce', 'WooCommerce'],
    'bigcommerce' => ['/orders/sync-bigcommerce', 'BigCommerce'],
    'prestashop'  => ['/orders/sync-prestashop', 'PrestaShop'],
    'opencart'    => ['/orders/sync-opencart', 'OpenCart'],
    'oscommerce'  => ['/orders/sync-oscommerce', 'osCommerce'],
    'wix'         => ['/orders/sync-wix', 'Wix'],
    'ebay'        => ['/orders/sync-ebay', 'eBay'],
    'magento'     => ['/orders/sync-magento', 'Magento'],
];

// CJdropshipping: "Sync CJ" button (CJ ko bheje gaye orders ka status + tracking)
$showCjSync = ($platform === 'cj') || !empty($store['cj_api_key']);
?>

<div class="ui-head">
    <div class="ui-head-left">
        <div class="breadcrumb">Finovo <i class="bi bi-chevron-right"></i> <span class="current">Orders</span></div>
        <h1>Orders <span class="count-badge"><?= $totalCount ?></span></h1>
        <p>Manage your orders and track fulfillment across all sources.</p>
    </div>
    <div class="ui-head-right">
        <a href="/orders/create" class="ui-btn ui-btn-primary"><i class="bi bi-plus-lg"></i> Add Order</a>
    </div>
</div>

<?php if (!empty($synced)): ?>
    <div class="banner banner-success"><i class="bi bi-check-circle"></i> <?= (int)$synced ?> new order(s) imported.</div>
<?php endif; ?>
<?php if (!empty($stockWarningCount)): ?>
    <div class="banner banner-error"><i class="bi bi-exclamation-triangle"></i> <?= (int)$stockWarningCount ?> synced order(s) had insufficient stock at the time of import.</div>
<?php endif; ?>
<?php if (!empty($exported)): ?>
    <div class="banner banner-success"><i class="bi bi-check-circle"></i> Order exported.</div>
<?php endif; ?>
<?php if (!empty($_GET['pushed'])): ?>
    <div class="banner banner-success"><i class="bi bi-check-circle"></i> Order sent to <?= htmlspecialchars($_GET['pushed']) ?> successfully.</div>
<?php endif; ?>
<?php if (!empty($imported)): ?>
    <div class="banner banner-success"><i class="bi bi-check-circle"></i> <?= (int)$imported ?> order(s) imported from CSV.</div>
<?php endif; ?>
<?php if (!empty($_GET['stock_updated'])): ?>
    <div class="banner banner-success"><i class="bi bi-check-circle"></i> Order updated.</div>
<?php endif; ?>
<?php if (isset($_GET['cj_synced'])): ?>
    <div class="banner banner-success"><i class="bi bi-check-circle"></i> CJ sync done — <?= (int) $_GET['cj_synced'] ?> order(s) updated from CJdropshipping.</div>
<?php endif; ?>
<?php if (!empty($_GET['cj_sent'])): ?>
    <div class="banner banner-success"><i class="bi bi-check-circle"></i> Order sent to CJdropshipping (CJ order <?= htmlspecialchars($_GET['cj_sent']) ?>). Pay for it in your CJ account so CJ ships it.</div>
<?php endif; ?>
<?php if (!empty($_GET['deleted'])): ?>
    <div class="banner banner-success"><i class="bi bi-check-circle"></i> Order deleted, stock restored.</div>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <div class="banner banner-error"><i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="ui-stats">
    <div class="ui-stat">
        <div class="ui-stat-icon"><i class="bi bi-bag-check"></i></div>
        <div>
            <div class="ui-stat-label">Total Orders</div>
            <div class="ui-stat-value"><b><?= $totalCount ?></b><span class="ui-pill ui-pill-blue"><?= ($selectedPlatform === 'all' && $selectedStatus === '' && trim($_GET['search'] ?? '') === '') ? 'All' : 'Filtered' ?></span></div>
        </div>
    </div>
    <div class="ui-stat">
        <div class="ui-stat-icon"><i class="bi bi-hourglass-split"></i></div>
        <div>
            <div class="ui-stat-label">Pending</div>
            <div class="ui-stat-value"><b><?= $pendingCount ?></b><span class="ui-pill ui-pill-amber">To process</span></div>
        </div>
    </div>
    <div class="ui-stat">
        <div class="ui-stat-icon"><i class="bi bi-credit-card"></i></div>
        <div>
            <div class="ui-stat-label">Unpaid</div>
            <div class="ui-stat-value"><b><?= $unpaidCount ?></b>
                <span class="ui-pill <?= $unpaidCount > 0 ? 'ui-pill-red' : 'ui-pill-green' ?>"><?= $unpaidCount > 0 ? 'Awaiting payment' : 'All paid' ?></span>
            </div>
        </div>
    </div>
    <div class="ui-stat">
        <div class="ui-stat-icon"><i class="bi bi-truck"></i></div>
        <div>
            <div class="ui-stat-label">Not Dispatched</div>
            <div class="ui-stat-value"><b><?= $notDispatchedCount ?></b><span class="ui-pill ui-pill-blue">No shipment</span></div>
        </div>
    </div>
</div>

<div class="ui-card tbl-card">
    <!-- Search + filters (server pe) ... Sync / Import / Export / Settings -->
    <form method="GET" action="/orders">
        <div class="tbl-head">
            <h2>All Orders</h2>
            <div class="tbl-tools">
                <?php if (isset($syncLinks[$platform])): ?>
                    <a href="<?= $syncLinks[$platform][0] ?>" class="tbl-btn tbl-btn-soft"><i class="bi bi-arrow-repeat"></i> Sync <?= $syncLinks[$platform][1] ?></a>
                <?php endif; ?>
                <?php if ($showCjSync): ?>
                    <a href="/orders/sync-cj" class="tbl-btn tbl-btn-soft"><i class="bi bi-arrow-repeat"></i> Sync CJ</a>
                <?php endif; ?>
                <div class="ui-menu">
                    <?php $nf = ($selectedPlatform !== 'all') + ($selectedStatus !== ''); ?>
                    <button type="button" class="tbl-btn" data-menu-toggle><i class="bi bi-sliders"></i> Filters<?php if ($nf): ?> <span class="tbl-count"><?= $nf ?></span><?php endif; ?></button>
                    <div class="ui-menu-list tbl-filter-panel">
                        <label for="orderPlatformFilter">Platform</label>
                        <select name="platform" id="orderPlatformFilter" onchange="this.form.submit()">
                            <option value="all" <?= $selectedPlatform === 'all' ? 'selected' : '' ?>>All platforms</option>
                            <option value="manual" <?= $selectedPlatform === 'manual' ? 'selected' : '' ?>>Manual</option>
                            <option value="custom" <?= $selectedPlatform === 'custom' ? 'selected' : '' ?>>API push</option>
                            <option value="shopify" <?= $selectedPlatform === 'shopify' ? 'selected' : '' ?>>Shopify</option>
                            <option value="woocommerce" <?= $selectedPlatform === 'woocommerce' ? 'selected' : '' ?>>WooCommerce</option>
                            <option value="bigcommerce" <?= $selectedPlatform === 'bigcommerce' ? 'selected' : '' ?>>BigCommerce</option>
                            <option value="prestashop" <?= $selectedPlatform === 'prestashop' ? 'selected' : '' ?>>PrestaShop</option>
                            <option value="opencart" <?= $selectedPlatform === 'opencart' ? 'selected' : '' ?>>OpenCart</option>
                            <option value="oscommerce" <?= $selectedPlatform === 'oscommerce' ? 'selected' : '' ?>>osCommerce</option>
                            <option value="wix" <?= $selectedPlatform === 'wix' ? 'selected' : '' ?>>Wix</option>
                            <option value="ebay" <?= $selectedPlatform === 'ebay' ? 'selected' : '' ?>>eBay</option>
                            <option value="magento" <?= $selectedPlatform === 'magento' ? 'selected' : '' ?>>Magento</option>
                            <option value="csv" <?= $selectedPlatform === 'csv' ? 'selected' : '' ?>>CSV import</option>
                        </select>
                        <label for="orderStatusFilter">Status</label>
                        <select name="status" id="orderStatusFilter" onchange="this.form.submit()">
                            <option value="">All statuses</option>
                            <?php foreach (['pending' => 'Pending', 'processing' => 'Processing', 'delivered' => 'Delivered', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $sv => $sl): ?>
                                <option value="<?= $sv ?>" <?= $selectedStatus === $sv ? 'selected' : '' ?>><?= $sl ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="tbl-filter-actions">
                            <a href="/orders" class="tbl-btn">Clear</a>
                            <button type="submit" class="tbl-btn tbl-btn-soft">Apply</button>
                        </div>
                    </div>
                </div>
                <button type="button" class="tbl-btn" onclick="openModal('importOrderModal')"><i class="bi bi-upload"></i> Import</button>
                <a href="/orders/export-csv" class="tbl-btn"><i class="bi bi-download"></i> Export</a>
                <?php if (in_array(strtolower(trim($_SESSION['user']['role'] ?? '')), ['admin', 'manager'], true)): ?>
                    <a href="/orders/settings" class="tbl-btn" title="Order settings"><i class="bi bi-gear"></i> Settings</a>
                <?php endif; ?>
            </div>
        </div>
        <div class="tbl-search">
            <label class="ui-search">
                <i class="bi bi-search"></i>
                <input type="text" name="search" placeholder="Search orders, customers or products..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" aria-label="Search customer or product">
            </label>
        </div>
    </form>

    <div style="overflow-x:auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Customer</th>
                    <th>Product</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th style="width:60px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                    <tr><td colspan="6" class="ui-empty"><i class="bi bi-receipt"></i>No orders found. Try a different search, or click "Add Order".</td></tr>
                <?php else: ?>
                    <?php foreach ($orders as $i => $order):
                        $avColor = $avatarColors[$i % count($avatarColors)];
                        $currencySymbol = $order['currency_symbol'] ?? 'Rs.';
                        $statusKey = $order['status'] ?? 'pending';
                        $statusColor = $statusColors[$statusKey] ?? ['bg' => '#f1f5f9', 'text' => '#334155'];
                        $payKey = $order['payment_status'] ?? 'unpaid';
                        $shipment = $shipmentMap[(int) $order['id']] ?? null;
                    ?>
                        <tr class="order-row" data-status="<?= htmlspecialchars($statusKey) ?>">
                            <td>
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <span style="width:28px; height:28px; border-radius:50%; background:<?= $avColor['bg'] ?>; color:<?= $avColor['text'] ?>; display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:700; flex-shrink:0;"><?= strtoupper(substr($order['customer_name'] ?? 'O', 0, 1)) ?></span>
                                    <span style="font-weight:700;">#<?= htmlspecialchars($order['id']) ?></span>
                                </div>
                            </td>
                            <td style="color:#334155; font-weight:500;"><?= htmlspecialchars($order['customer_name'] ?? '-') ?></td>
                            <td style="color:#334155;">
                                <?= htmlspecialchars($order['product_name'] ?? '-') ?>
                                <?php if (!empty($order['variant_label'])): ?>
                                    <span style="color:var(--text-muted); font-size:12px;">(<?= htmlspecialchars($order['variant_label']) ?>)</span>
                                <?php endif; ?>
                            </td>
                            <td style="white-space:nowrap;"><?= $currencySymbol ?> <?= htmlspecialchars(number_format((float)($order['price'] ?? 0), 2)) ?></td>
                            <td style="white-space:nowrap;">
                                <span class="ui-tag" style="background:<?= $statusColor['bg'] ?>; color:<?= $statusColor['text'] ?>;">
                                    <?= htmlspecialchars(ucfirst($statusKey)) ?>
                                </span>
                                <?php if (!empty($order['stock_warning'])): ?>
                                    <span class="ui-tag ui-pill-red" style="margin-left:4px;" title="Not enough stock was available when this order was pulled in">
                                        <i class="bi bi-exclamation-triangle"></i>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button type="button" class="row-menu-btn" aria-label="Actions for order #<?= (int) $order['id'] ?>" onclick="toggleRowMenu(event, 'order-menu-<?= $order['id'] ?>')">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <div class="row-menu" id="order-menu-<?= $order['id'] ?>">
                                    <a href="/orders/edit?id=<?= $order['id'] ?>"><i class="bi bi-pencil"></i> Edit</a>
                                    <a href="/orders/invoice?order_id=<?= $order['id'] ?>"><i class="bi bi-file-earmark-pdf"></i> Download Invoice</a>
                                    <?php if ($payKey !== 'paid' && in_array($order['payment_method'] ?? '', ['Card'], true)): ?>
                                        <!-- Payment column hat gaya, is liye "Pay Now" ab yahan menu mein -->
                                        <a href="/payment/initiate?order_id=<?= $order['id'] ?>"><i class="bi bi-credit-card"></i> Pay Now</a>
                                    <?php endif; ?>
                                    <a href="/orders/cj-fulfill?id=<?= $order['id'] ?>"><i class="bi bi-truck"></i> Send to CJdropshipping</a>
                                    <?php
                                    // Push sirf usi platform ka dikhao jis ka yeh store hai.
                                    //  - "Push to X": Finovo ka order X pe banao (jo pehle se X pe hai us ka nahi)
                                    //  - "Send tracking to X": X wale order ko shipment ke tracking number ke saath "Shipped" karo
                                    $storePlatform = strtolower($store['platform'] ?? '');
                                    $pushColumns = [
                                        'shopify' => 'external_order_id', 'woocommerce' => 'external_wc_order_id',
                                        'bigcommerce' => 'external_bc_order_id', 'wix' => 'external_wix_order_id',
                                        'magento' => 'external_magento_order_id', 'prestashop' => 'external_ps_order_id',
                                        'ebay' => 'external_ebay_order_id',
                                    ];
                                    $pushLabels = [
                                        'shopify' => 'Shopify', 'woocommerce' => 'WooCommerce', 'bigcommerce' => 'BigCommerce',
                                        'wix' => 'Wix', 'magento' => 'Magento', 'prestashop' => 'PrestaShop', 'ebay' => 'eBay',
                                    ];
                                    $canCreate = in_array($storePlatform, ['shopify', 'woocommerce', 'bigcommerce', 'wix', 'magento', 'prestashop'], true);
                                    $canTrack = in_array($storePlatform, ['shopify', 'woocommerce', 'bigcommerce', 'magento', 'ebay'], true);
                                    $inPlatform = isset($pushColumns[$storePlatform]) && !empty($order[$pushColumns[$storePlatform]]);
                                    $hasTracking = ($shipment && !empty($shipment['tracking_number'])) || !empty($order['cj_tracking_number']);
                                    $pushLabel = $pushLabels[$storePlatform] ?? '';
                                    ?>
                                    <?php if ($canCreate || ($canTrack && $inPlatform && $hasTracking)): ?>
                                        <div class="row-menu-divider"></div>
                                        <?php if ($canCreate && !$inPlatform): ?>
                                            <a href="/orders/push?id=<?= $order['id'] ?>"><i class="bi bi-box-arrow-up-right"></i> Push to <?= $pushLabel ?></a>
                                        <?php elseif ($canCreate): ?>
                                            <span class="row-menu-note"><i class="bi bi-check2-circle"></i> Already in <?= $pushLabel ?></span>
                                        <?php endif; ?>
                                        <?php if ($canTrack && $inPlatform && $hasTracking): ?>
                                            <?php if (!empty($order['platform_tracking_pushed_at'])): ?>
                                                <span class="row-menu-note"><i class="bi bi-check2-circle"></i> Tracking sent to <?= $pushLabel ?></span>
                                            <?php else: ?>
                                                <a href="/orders/push-tracking?id=<?= $order['id'] ?>"><i class="bi bi-truck"></i> Send tracking to <?= $pushLabel ?></a>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <?php if (in_array(strtolower(trim($_SESSION['user']['role'] ?? '')), ['admin', 'manager'], true)): ?>
                                        <!-- Order delete sirf Admin / Manager -->
                                        <div class="row-menu-divider"></div>
                                        <a href="#" class="row-menu-danger" onclick="if(confirm('Delete this order? Stock will be returned to the warehouse.')){document.getElementById('deleteOrderForm<?= $order['id'] ?>').submit();} return false;"><i class="bi bi-trash"></i> Delete</a>
                                    <?php endif; ?>
                                </div>
                                <?php if (in_array(strtolower(trim($_SESSION['user']['role'] ?? '')), ['admin', 'manager'], true)): ?>
                                    <form id="deleteOrderForm<?= $order['id'] ?>" action="/orders/delete" method="POST" style="display:none;">
                                        <input type="hidden" name="id" value="<?= $order['id'] ?>">
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php $pagerLabel = 'order lines'; require __DIR__ . '/../partials/pagination.php'; ?>
</div>

<div class="modal-backdrop" id="importOrderModal">
    <div class="modal-box">
        <div class="modal-header">
            <h2>Import Orders (CSV)</h2>
            <button type="button" class="modal-close" onclick="closeModal('importOrderModal')" aria-label="Close">&times;</button>
        </div>
        <p class="modal-help">CSV columns must be in this order: <strong>customer_name, product_name, quantity, price</strong> (first row is treated as the header and skipped).</p>
        <form action="/orders/import" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>CSV File</label>
                <input type="file" name="csv_file" accept=".csv" required>
            </div>
            <div class="form-actions">
                <button type="button" class="btn-secondary" onclick="closeModal('importOrderModal')">Cancel</button>
                <button type="submit" class="btn-primary">Import</button>
            </div>
        </form>
    </div>
</div>

<style>

.row-menu-btn {
    width: 32px;
    height: 32px;
    border: 1px solid transparent;
    background: transparent;
    border-radius: 8px;
    cursor: pointer;
    color: var(--text-muted);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
}
.row-menu-btn:hover {
    background: #eff6ff;
    color: var(--text-dark);
}
.row-menu {
    display: none;
    position: fixed;
    background: var(--bg-white, #fff);
    border: 1px solid var(--border-color);
    border-radius: 10px;
    box-shadow: 0 10px 25px rgba(15,23,42,0.12);
    min-width: 210px;
    z-index: 9999;
    padding: 6px;
}
.row-menu.show { display: block; }
.row-menu a {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 10px;
    font-size: 13px;
    color: var(--text-dark);
    text-decoration: none;
    border-radius: 6px;
    white-space: nowrap;
}
.row-menu a:hover { background: #f8fafc; }
.row-menu a.row-menu-danger { color: var(--red); }
.row-menu a.row-menu-danger:hover { background: #fee2e2; }
.row-menu-note {
    display: flex; align-items: center; gap: 8px;
    padding: 8px 12px; font-size: 13px; color: var(--text-muted); cursor: default;
}
.row-menu-note i { color: #16a34a; }
.row-menu-divider {
    height: 1px;
    background: var(--border-color);
    margin: 6px 4px;
}
</style>

<script>
function openModal(id) { document.getElementById(id).classList.add('show'); }
function closeModal(id) { document.getElementById(id).classList.remove('show'); }

function toggleRowMenu(event, menuId) {
    event.stopPropagation();
    const btn = event.currentTarget;
    const menu = document.getElementById(menuId);

    document.querySelectorAll('.row-menu.show').forEach(m => {
        if (m.id !== menuId) m.classList.remove('show');
    });

    if (menu.classList.contains('show')) {
        menu.classList.remove('show');
        return;
    }

    const rect = btn.getBoundingClientRect();
    const menuWidth = 210;

    let left = rect.right - menuWidth;
    if (left < 8) left = 8;

    let top = rect.bottom + 4;

    menu.style.left = left + 'px';
    menu.style.top = top + 'px';
    menu.classList.add('show');

    const menuRect = menu.getBoundingClientRect();
    if (menuRect.bottom > window.innerHeight) {
        menu.style.top = (rect.top - menuRect.height - 4) + 'px';
    }
}

document.addEventListener('click', function() {
    document.querySelectorAll('.row-menu.show').forEach(m => m.classList.remove('show'));
});

window.addEventListener('scroll', function() {
    document.querySelectorAll('.row-menu.show').forEach(m => m.classList.remove('show'));
}, true);
</script>