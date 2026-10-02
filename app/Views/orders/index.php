<?php
$avatarColors = [
    ['bg' => '#dbeafe', 'text' => '#1d4ed8'],
    ['bg' => '#dcfce7', 'text' => '#15803d'],
    ['bg' => '#fce7f3', 'text' => '#be185d'],
    ['bg' => '#fef3c7', 'text' => '#b45309'],
    ['bg' => '#e0e7ff', 'text' => '#4338ca'],
    ['bg' => '#cffafe', 'text' => '#0e7490'],
];

$sourceColors = [
    'shopify_pull'     => ['bg' => '#ede9fe', 'text' => '#6d28d9'],
    'woocommerce_pull' => ['bg' => '#dcfce7', 'text' => '#15803d'],
    'bigcommerce_pull' => ['bg' => '#dbeafe', 'text' => '#1d4ed8'],
    'prestashop_pull'  => ['bg' => '#fce7f3', 'text' => '#be185d'],
    'opencart_pull'    => ['bg' => '#fef9c3', 'text' => '#854d0e'],
    'oscommerce_pull'  => ['bg' => '#e0f2fe', 'text' => '#0369a1'],
    'wix_pull'         => ['bg' => '#ffe4e6', 'text' => '#be123c'],
    'ebay_pull'        => ['bg' => '#e0e7ff', 'text' => '#4338ca'],
    'magento_pull'     => ['bg' => '#ffe4e6', 'text' => '#be123c'],
    'manual'           => ['bg' => '#ffedd5', 'text' => '#c2410c'],
    'api_push'         => ['bg' => '#cffafe', 'text' => '#0e7490'],
    'csv_import'       => ['bg' => '#fce7f3', 'text' => '#be185d'],
];

$statusColors = [
    'pending'    => ['bg' => '#fef3c7', 'text' => '#92400e'],
    'processing' => ['bg' => '#dbeafe', 'text' => '#1d4ed8'],
    'completed'  => ['bg' => '#dcfce7', 'text' => '#166534'],
    'delivered'  => ['bg' => '#dcfce7', 'text' => '#166534'],
    'cancelled'  => ['bg' => '#fee2e2', 'text' => '#991b1b'],
];

$shipmentColors = [
    'pending'     => ['bg' => '#f1f5f9', 'text' => '#334155'],
    'packed'      => ['bg' => '#fef3c7', 'text' => '#b45309'],
    'dispatched'  => ['bg' => '#dbeafe', 'text' => '#1d4ed8'],
    'in_transit'  => ['bg' => '#e0e7ff', 'text' => '#4338ca'],
    'delivered'   => ['bg' => '#dcfce7', 'text' => '#15803d'],
    'returned'    => ['bg' => '#fee2e2', 'text' => '#991b1b'],
];

$platform = $store['platform'] ?? 'manual';

// ---------- Summary numbers (sirf display ke liye, $orders se hi) ----------
$pendingCount = 0;
$unpaidCount = 0;
$notDispatchedCount = 0;
foreach ($orders as $o) {
    if (($o['status'] ?? 'pending') === 'pending') { $pendingCount++; }
    if (($o['payment_status'] ?? 'unpaid') !== 'paid') { $unpaidCount++; }
    if (empty($shipmentMap[(int) $o['id']])) { $notDispatchedCount++; }
}

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
        <h1>Orders <span class="count-badge"><?= count($orders) ?></span></h1>
        <p>Manage your orders and track fulfillment across all sources.</p>
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
            <div class="ui-stat-value"><b><?= count($orders) ?></b><span class="ui-pill ui-pill-blue"><?= $selectedPlatform === 'all' ? 'All' : 'Filtered' ?></span></div>
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

<div class="ui-card" style="overflow: visible;">
    <div class="ui-card-head">
        <h2>All Orders</h2>
    </div>

    <!-- Ek hi line: search + filters + Search ... Import / Export / Shipments / Sync / Settings / Add Order -->
    <form method="GET" action="/orders" class="orders-toolbar">
        <label class="ui-search orders-search">
            <i class="bi bi-search"></i>
            <input type="text" name="search" placeholder="Search orders..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" aria-label="Search customer or product">
        </label>
        <select name="platform" class="orders-select" aria-label="Filter by platform" onchange="this.form.submit()">
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
            <option value="csv" <?= $selectedPlatform === 'csv' ? 'selected' : '' ?>>CSV import</option>
        </select>
        <select id="orderStatusFilter" class="orders-select" aria-label="Filter by status">
            <option value="">All statuses</option>
            <option value="pending">Pending</option>
            <option value="processing">Processing</option>
            <option value="delivered">Delivered</option>
            <option value="completed">Completed</option>
            <option value="cancelled">Cancelled</option>
        </select>
        <button type="submit" class="ui-btn ui-btn-primary"><i class="bi bi-search"></i> Search</button>

        <div class="orders-toolbar-right">
            <button type="button" class="ui-btn" onclick="openModal('importOrderModal')"><i class="bi bi-upload"></i> Import CSV</button>
            <a href="/orders/export-csv" class="ui-btn"><i class="bi bi-download"></i> Export CSV</a>
            <a href="/shipments" class="ui-btn"><i class="bi bi-truck"></i> Shipments</a>
            <span class="orders-toolbar-divider"></span>
            <?php if (isset($syncLinks[$platform])): ?>
                <a href="<?= $syncLinks[$platform][0] ?>" class="ui-btn ui-btn-soft"><i class="bi bi-arrow-repeat"></i> Sync <?= $syncLinks[$platform][1] ?></a>
            <?php endif; ?>
            <?php if ($showCjSync): ?>
                <a href="/orders/sync-cj" class="ui-btn ui-btn-soft"><i class="bi bi-arrow-repeat"></i> Sync CJ</a>
            <?php endif; ?>
            <a href="/orders/settings" class="ui-btn"><i class="bi bi-gear"></i> Settings</a>
            <a href="/orders/create" class="ui-btn ui-btn-primary"><i class="bi bi-plus-lg"></i> Add Order</a>
        </div>
    </form>

    <div style="overflow-x:auto;">
    <table class="data-table">
        <thead>
            <tr>
                <th class="checkbox-col"><input type="checkbox" id="orderSelectAll" aria-label="Select all orders"></th>
                <th>Order</th>
                <th>Customer</th>
                <th>Product</th>
                <th>Qty</th>
                <th>Price</th>
                <th>Source</th>
                <th>Status</th>
                <th>Payment</th>
                <th>Shipment</th>
                <th style="width:60px;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($orders)): ?>
                <tr><td colspan="11" class="ui-empty"><i class="bi bi-receipt"></i>No orders found. Try a different search, or click "Add Order".</td></tr>
            <?php else: ?>
                <?php foreach ($orders as $i => $order):
                    $avColor = $avatarColors[$i % count($avatarColors)];
                    $srcKey = $order['source'] ?? 'manual';
                    $srcColor = $sourceColors[$srcKey] ?? ['bg' => '#f1f5f9', 'text' => '#334155'];
                    $currencySymbol = $order['currency_symbol'] ?? 'Rs.';
                    $statusKey = $order['status'] ?? 'pending';
                    $statusColor = $statusColors[$statusKey] ?? ['bg' => '#f1f5f9', 'text' => '#334155'];
                    $payKey = $order['payment_status'] ?? 'unpaid';
                    $shipment = $shipmentMap[(int) $order['id']] ?? null;
                ?>
                    <tr class="order-row" data-status="<?= htmlspecialchars($statusKey) ?>">
                        <td class="checkbox-col"><input type="checkbox" class="order-check" aria-label="Select order #<?= (int) $order['id'] ?>"></td>
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
                        <td style="font-weight:600;"><?= htmlspecialchars($order['quantity'] ?? 1) ?></td>
                        <td style="white-space:nowrap;"><?= $currencySymbol ?> <?= htmlspecialchars(number_format((float)($order['price'] ?? 0), 2)) ?></td>
                        <td>
                            <span class="ui-tag" style="background:<?= $srcColor['bg'] ?>; color:<?= $srcColor['text'] ?>; white-space:nowrap;">
                                <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $srcKey))) ?>
                            </span>
                        </td>
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
                            <span class="ui-dot" style="color:<?= $payKey === 'paid' ? '#166534' : '#b91c1c' ?>;">
                                <?= htmlspecialchars(ucfirst($payKey)) ?>
                            </span>
                            <?php if (!empty($order['payment_method'])): ?>
                                <div style="font-size:11px; color:var(--text-muted); margin-top:3px;"><?= htmlspecialchars($order['payment_method']) ?></div>
                            <?php endif; ?>
                            <?php if ($payKey !== 'paid' && in_array($order['payment_method'] ?? '', ['Card'], true)): ?>
                                <a href="/payment/initiate?order_id=<?= $order['id'] ?>" style="display:inline-flex; align-items:center; gap:4px; margin-top:4px; font-size:11.5px; font-weight:600; color:var(--primary); text-decoration:none;">
                                    <i class="bi bi-credit-card"></i> Pay Now
                                </a>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($shipment): ?>
                                <?php $shColor = $shipmentColors[$shipment['status']] ?? ['bg' => '#f1f5f9', 'text' => '#334155']; ?>
                                <span class="ui-tag" style="background:<?= $shColor['bg'] ?>; color:<?= $shColor['text'] ?>; white-space:nowrap;" title="<?= htmlspecialchars($shipment['courier_name']) ?><?= $shipment['tracking_number'] ? ' — ' . htmlspecialchars($shipment['tracking_number']) : '' ?>">
                                    <?= htmlspecialchars(ucwords(str_replace('_', ' ', $shipment['status']))) ?>
                                </span>
                            <?php else: ?>
                                <span class="ui-tag" style="background:#f1f5f9; color:#64748b; white-space:nowrap;">Not dispatched</span>
                            <?php endif; ?>
                            <?php if (!empty($order['cj_order_id'])): ?>
                                <div style="font-size:11px; color:var(--text-muted); margin-top:4px; white-space:nowrap;" title="CJ order <?= htmlspecialchars($order['cj_order_id']) ?>">
                                    <i class="bi bi-truck"></i> CJ: <?= htmlspecialchars($order['cj_order_status'] ?? 'CREATED') ?>
                                    <?php if (!empty($order['cj_tracking_number'])): ?>
                                        <br><span style="font-family:monospace;"><?= htmlspecialchars($order['cj_tracking_number']) ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button type="button" class="row-menu-btn" aria-label="Actions for order #<?= (int) $order['id'] ?>" onclick="toggleRowMenu(event, 'order-menu-<?= $order['id'] ?>')">
                                <i class="bi bi-three-dots-vertical"></i>
                            </button>
                            <div class="row-menu" id="order-menu-<?= $order['id'] ?>">
                                <a href="/orders/edit?id=<?= $order['id'] ?>"><i class="bi bi-pencil"></i> Edit</a>
                                <a href="/orders/invoice?order_id=<?= $order['id'] ?>"><i class="bi bi-file-earmark-pdf"></i> Download Invoice</a>
                                <a href="/orders/cj-fulfill?id=<?= $order['id'] ?>"><i class="bi bi-truck"></i> Send to CJdropshipping</a>
                                <div class="row-menu-divider"></div>
                                <a href="/orders/export-shopify?id=<?= $order['id'] ?>"><i class="bi bi-box-arrow-up-right"></i> Push to Shopify</a>
                                <a href="/orders/export-woocommerce?id=<?= $order['id'] ?>"><i class="bi bi-box-arrow-up-right"></i> Push to WooCommerce</a>
                                <div class="row-menu-divider"></div>
                                <a href="#" class="row-menu-danger" onclick="if(confirm('Delete this order? Stock will be returned to the warehouse.')){document.getElementById('deleteOrderForm<?= $order['id'] ?>').submit();} return false;"><i class="bi bi-trash"></i> Delete</a>
                            </div>
                            <form id="deleteOrderForm<?= $order['id'] ?>" action="/orders/delete" method="POST" style="display:none;">
                                <input type="hidden" name="id" value="<?= $order['id'] ?>">
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <tr id="orderNoMatch" style="display:none;"><td colspan="11" class="ui-empty"><i class="bi bi-funnel"></i>No orders with this status.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    </div>

    <div class="pagination-bar">
        <span id="orderShowing">Showing <?= count($orders) ?> of <?= count($orders) ?> orders</span>
        <div class="pagination-controls" id="orderPager"></div>
    </div>
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
/* ---------- Orders page: saare buttons ek hi line mein ---------- */
.orders-toolbar {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
    row-gap: 10px;
    padding: 14px 20px 16px;
}
.orders-toolbar .ui-btn,
.orders-toolbar .ui-search,
.orders-toolbar .orders-select {
    height: 36px;
    box-sizing: border-box;
    flex-shrink: 0;
}
.orders-toolbar .ui-btn { padding: 0 10px; font-size: 12.5px; gap: 5px; }
.orders-toolbar .orders-search {
    flex: 1 1 130px;
    max-width: 260px;
    min-width: 130px;
    flex-shrink: 1;
}
.orders-select {
    padding: 0 26px 0 10px;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 16 16'%3E%3Cpath fill='%2364748b' d='M8 11L3 6h10z'/%3E%3C/svg%3E") no-repeat right 8px center;
    -webkit-appearance: none;
    appearance: none;
    font: inherit;
    font-size: 12.5px;
    font-weight: 600;
    color: var(--text-body);
    cursor: pointer;
}
.orders-select:hover { background-color: var(--bg-hover); }
.orders-toolbar-right {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-left: auto;
    flex-wrap: nowrap;
}
.orders-toolbar-divider {
    width: 1px;
    height: 24px;
    background: var(--border-color);
    margin: 0 1px;
    flex-shrink: 0;
}
/* Jagah kam ho to poora button-group agli line mein (page na toote) */
@media (max-width: 700px) {
    .orders-toolbar .orders-search { flex: 1 1 100%; max-width: none; }
    .orders-toolbar-right { margin-left: 0; flex-wrap: wrap; }
    .orders-toolbar-divider { display: none; }
}

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

<script>
// ---------- Status filter + 15-per-page pagination (browser only) ----------
(function () {
    var PER_PAGE = 15;
    var page = 1;
    var rows = Array.prototype.slice.call(document.querySelectorAll('.order-row'));
    var filter = document.getElementById('orderStatusFilter');
    var pager = document.getElementById('orderPager');
    var showing = document.getElementById('orderShowing');
    var noMatch = document.getElementById('orderNoMatch');
    var selectAll = document.getElementById('orderSelectAll');

    function render() {
        var st = filter ? filter.value : '';
        var list = rows.filter(function (r) { return st === '' || r.dataset.status === st; });
        var pages = Math.max(1, Math.ceil(list.length / PER_PAGE));
        if (page > pages) { page = pages; }
        var start = (page - 1) * PER_PAGE;
        rows.forEach(function (r) { r.style.display = 'none'; });
        list.slice(start, start + PER_PAGE).forEach(function (r) { r.style.display = ''; });
        if (noMatch) { noMatch.style.display = (rows.length > 0 && list.length === 0) ? '' : 'none'; }
        var shown = Math.min(PER_PAGE, Math.max(0, list.length - start));
        showing.textContent = 'Showing ' + shown + ' of ' + list.length + ' orders';

        pager.innerHTML = '';
        if (pages <= 1) { return; }
        function add(label, target, disabled, active) {
            var b = document.createElement('button');
            b.type = 'button';
            b.innerHTML = label;
            b.disabled = disabled;
            if (active) { b.className = 'page-num active'; b.setAttribute('aria-current', 'page'); }
            b.addEventListener('click', function () { page = target; render(); });
            pager.appendChild(b);
        }
        add('<i class="bi bi-arrow-left"></i> Prev', page - 1, page === 1, false);
        for (var i = 1; i <= pages; i++) { add(String(i), i, false, i === page); }
        add('Next <i class="bi bi-arrow-right"></i>', page + 1, page === pages, false);
    }

    if (filter) { filter.addEventListener('change', function () { page = 1; render(); }); }
    if (selectAll) {
        selectAll.addEventListener('change', function () {
            rows.forEach(function (r) {
                if (r.style.display !== 'none') { r.querySelector('.order-check').checked = selectAll.checked; }
            });
        });
    }
    render();
})();
</script>