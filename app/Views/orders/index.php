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
    'manual'           => ['bg' => '#ffedd5', 'text' => '#c2410c'],
    'api_push'         => ['bg' => '#cffafe', 'text' => '#0e7490'],
    'csv_import'       => ['bg' => '#fce7f3', 'text' => '#be185d'],
];

$statusColors = [
    'pending'    => ['bg' => '#fef3c7', 'text' => '#b45309'],
    'processing' => ['bg' => '#dbeafe', 'text' => '#1d4ed8'],
    'completed'  => ['bg' => '#dcfce7', 'text' => '#15803d'],
    'delivered'  => ['bg' => '#dcfce7', 'text' => '#15803d'],
    'cancelled'  => ['bg' => '#fee2e2', 'text' => '#991b1b'],
];

$shipmentColors = [
    'pending'     => ['bg' => '#f3f4f6', 'text' => '#374151'],
    'packed'      => ['bg' => '#fef3c7', 'text' => '#b45309'],
    'dispatched'  => ['bg' => '#dbeafe', 'text' => '#1d4ed8'],
    'in_transit'  => ['bg' => '#e0e7ff', 'text' => '#4338ca'],
    'delivered'   => ['bg' => '#dcfce7', 'text' => '#15803d'],
    'returned'    => ['bg' => '#fee2e2', 'text' => '#991b1b'],
];
?>

<div class="breadcrumb">Finovo <i class="bi bi-chevron-right"></i> Orders</div>

<div class="page-header-row">
    <h1>Orders <span class="count-badge"><?= count($orders) ?></span></h1>
</div>
<p class="page-subtitle">Manage your orders and track fulfillment across all sources.</p>

<?php if (!empty($synced)): ?>
    <div class="banner banner-success"><?= (int)$synced ?> new order(s) imported.</div>
<?php endif; ?>
<?php if (!empty($stockWarningCount)): ?>
    <div class="banner banner-error"><i class="bi bi-exclamation-triangle"></i> <?= (int)$stockWarningCount ?> synced order(s) had insufficient stock at the time of import.</div>
<?php endif; ?>
<?php if (!empty($exported)): ?>
    <div class="banner banner-success">Order exported.</div>
<?php endif; ?>
<?php if (!empty($imported)): ?>
    <div class="banner banner-success"><?= (int)$imported ?> order(s) imported from CSV.</div>
<?php endif; ?>
<?php if (!empty($_GET['stock_updated'])): ?>
    <div class="banner banner-success">Order updated.</div>
<?php endif; ?>
<?php if (!empty($_GET['deleted'])): ?>
    <div class="banner banner-success">Order deleted, stock restored.</div>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <div class="banner banner-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="card" style="overflow: visible;">
    <div class="filter-row">
        <form method="GET" action="/orders" style="display:flex; gap:8px; align-items:center;">
            <input type="text" name="search" placeholder="Search customer or product..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" style="border:1px solid var(--border-color); border-radius:8px; padding:6px 12px; font-size:13px; font-family:'Inter',sans-serif; width:220px;">
            <select name="platform" class="filter-btn" onchange="this.form.submit()">
                <option value="all" <?= $selectedPlatform === 'all' ? 'selected' : '' ?>>All platforms</option>
                <option value="manual" <?= $selectedPlatform === 'manual' ? 'selected' : '' ?>>Manual</option>
                <option value="custom" <?= $selectedPlatform === 'custom' ? 'selected' : '' ?>>API push</option>
                <option value="shopify" <?= $selectedPlatform === 'shopify' ? 'selected' : '' ?>>Shopify</option>
                <option value="woocommerce" <?= $selectedPlatform === 'woocommerce' ? 'selected' : '' ?>>WooCommerce</option>
                <option value="csv" <?= $selectedPlatform === 'csv' ? 'selected' : '' ?>>CSV import</option>
            </select>
            <button type="submit" class="filter-btn"><i class="bi bi-search"></i></button>
        </form>
        <div class="push-right">
            <button type="button" class="toolbar-btn" onclick="openModal('importOrderModal')"><i class="bi bi-upload"></i> Import CSV</button>
            <a href="/orders/export-csv" class="toolbar-btn"><i class="bi bi-download"></i> Export CSV</a>
            <a href="/orders/sync-shopify" class="toolbar-btn"><i class="bi bi-arrow-repeat"></i> Sync Shopify</a>
            <a href="/orders/sync-woocommerce" class="toolbar-btn"><i class="bi bi-arrow-repeat"></i> Sync WooCommerce</a>
            <a href="/shipments" class="toolbar-btn"><i class="bi bi-truck"></i> Shipments</a>
            <a href="/orders/settings" class="toolbar-btn"><i class="bi bi-gear"></i> Settings</a>
            <a href="/orders/create" class="toolbar-btn btn-dark"><i class="bi bi-plus-lg"></i> Add order</a>
        </div>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th class="checkbox-col"><input type="checkbox"></th>
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
                <tr><td colspan="11" style="text-align:center; color:#6b7280; padding:30px;">No orders found.</td></tr>
            <?php else: ?>
                <?php foreach ($orders as $i => $order):
                    $avColor = $avatarColors[$i % count($avatarColors)];
                    $srcKey = $order['source'] ?? 'manual';
                    $srcColor = $sourceColors[$srcKey] ?? ['bg' => '#f3f4f6', 'text' => '#374151'];
                    $currencySymbol = $order['currency_symbol'] ?? 'Rs.';
                    $statusKey = $order['status'] ?? 'pending';
                    $statusColor = $statusColors[$statusKey] ?? ['bg' => '#f3f4f6', 'text' => '#374151'];
                    $payKey = $order['payment_status'] ?? 'unpaid';
                    $shipment = $shipmentMap[(int) $order['id']] ?? null;
                ?>
                    <tr>
                        <td class="checkbox-col"><input type="checkbox"></td>
                        <td>
                            <div class="name-cell">
                                <span class="avatar-circle" style="background:<?= $avColor['bg'] ?>; color:<?= $avColor['text'] ?>;"><?= strtoupper(substr($order['customer_name'] ?? 'O', 0, 1)) ?></span>
                                #<?= htmlspecialchars($order['id']) ?>
                            </div>
                        </td>
                        <td><a href="#" class="link-blue"><?= htmlspecialchars($order['customer_name'] ?? '-') ?></a></td>
                        <td>
                            <?= htmlspecialchars($order['product_name'] ?? '-') ?>
                            <?php if (!empty($order['variant_label'])): ?>
                                <span style="color:var(--text-muted); font-size:12px;">(<?= htmlspecialchars($order['variant_label']) ?>)</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($order['quantity'] ?? 1) ?></td>
                        <td><?= $currencySymbol ?> <?= htmlspecialchars(number_format((float)($order['price'] ?? 0), 2)) ?></td>
                        <td>
                            <span class="source-badge" style="background:<?= $srcColor['bg'] ?>; color:<?= $srcColor['text'] ?>;">
                                <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $srcKey))) ?>
                            </span>
                        </td>
                        <td>
                            <span class="source-badge" style="background:<?= $statusColor['bg'] ?>; color:<?= $statusColor['text'] ?>;">
                                <?= htmlspecialchars(ucfirst($statusKey)) ?>
                            </span>
                            <?php if (!empty($order['stock_warning'])): ?>
                                <span class="source-badge" style="background:#fee2e2; color:#991b1b; margin-left:4px;" title="Not enough stock was available when this order was pulled in">
                                    <i class="bi bi-exclamation-triangle"></i>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="source-badge" style="background:<?= $payKey === 'paid' ? '#dcfce7' : '#fee2e2' ?>; color:<?= $payKey === 'paid' ? '#15803d' : '#991b1b' ?>;">
                                <?= htmlspecialchars(ucfirst($payKey)) ?>
                            </span>
                            <?php if (!empty($order['payment_method'])): ?>
                                <div style="font-size:11px; color:var(--text-muted); margin-top:3px;"><?= htmlspecialchars($order['payment_method']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($shipment): ?>
                                <?php $shColor = $shipmentColors[$shipment['status']] ?? ['bg' => '#f3f4f6', 'text' => '#374151']; ?>
                                <span class="source-badge" style="background:<?= $shColor['bg'] ?>; color:<?= $shColor['text'] ?>;" title="<?= htmlspecialchars($shipment['courier_name']) ?><?= $shipment['tracking_number'] ? ' — ' . htmlspecialchars($shipment['tracking_number']) : '' ?>">
                                    <?= htmlspecialchars(ucwords(str_replace('_', ' ', $shipment['status']))) ?>
                                </span>
                            <?php else: ?>
                                <span class="source-badge" style="background:#f3f4f6; color:#9ca3af;">Not dispatched</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button type="button" class="row-menu-btn" onclick="toggleRowMenu(event, 'order-menu-<?= $order['id'] ?>')">
                                <i class="bi bi-three-dots-vertical"></i>
                            </button>
                            <div class="row-menu" id="order-menu-<?= $order['id'] ?>">
                                <a href="/orders/edit?id=<?= $order['id'] ?>"><i class="bi bi-pencil"></i> Edit</a>
                                <a href="/orders/invoice?order_id=<?= $order['id'] ?>"><i class="bi bi-file-earmark-pdf"></i> Download Invoice</a>
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
            <?php endif; ?>
        </tbody>
    </table>

    <div class="pagination-bar">
        <span>Rows per page 15 &nbsp;&nbsp; 1-<?= count($orders) ?> of <?= count($orders) ?> rows</span>
        <div class="pagination-controls">
            <button><i class="bi bi-chevron-left"></i></button>
            <span class="page-num active">1</span>
            <button><i class="bi bi-chevron-right"></i></button>
        </div>
    </div>
</div>

<div class="modal-backdrop" id="importOrderModal">
    <div class="modal-box">
        <div class="modal-header">
            <h2>Import Orders (CSV)</h2>
            <button class="modal-close" onclick="closeModal('importOrderModal')">&times;</button>
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
    background: #f3f4f6;
    color: var(--text-dark);
}
.row-menu {
    display: none;
    position: fixed;
    background: var(--bg-white, #fff);
    border: 1px solid var(--border-color);
    border-radius: 10px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.15);
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
.row-menu a:hover { background: #f3f4f6; }
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