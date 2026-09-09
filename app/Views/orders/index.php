<?php
$currentPage = 'orders';
include __DIR__ . '/../partials/layout_start.php';

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
    'manual'           => ['bg' => '#ffedd5', 'text' => '#c2410c'],
    'api_push'         => ['bg' => '#cffafe', 'text' => '#0e7490'],
    'csv_import'       => ['bg' => '#fce7f3', 'text' => '#be185d'],
    'woocommerce_pull' => ['bg' => '#dcfce7', 'text' => '#15803d'],
];
?>

<div class="breadcrumb">Finovo <i class="bi bi-chevron-right"></i> Orders</div>

<div class="page-header-row">
    <h1>Orders <span class="count-badge"><?= count($orders) ?></span></h1>
</div>
<p class="page-subtitle">Manage your orders and track fulfillment across all sources.</p>

<?php if (!empty($synced)): ?>
    <div class="banner banner-success"><?= (int)$synced ?> new order(s) imported from Shopify.</div>
<?php endif; ?>
<?php if (!empty($exported)): ?>
    <div class="banner banner-success">Order exported to Shopify.</div>
<?php endif; ?>
<?php if (!empty($imported)): ?>
    <div class="banner banner-success"><?= (int)$imported ?> order(s) imported from CSV.</div>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <div class="banner banner-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="card">
    <div class="filter-row">
        <form method="GET" action="/orders">
            <select name="platform" class="filter-btn" onchange="this.form.submit()">
                <option value="all" <?= $selectedPlatform === 'all' ? 'selected' : '' ?>>All platforms</option>
                <option value="manual" <?= $selectedPlatform === 'manual' ? 'selected' : '' ?>>Manual</option>
                <option value="custom" <?= $selectedPlatform === 'custom' ? 'selected' : '' ?>>API push</option>
                <option value="shopify" <?= $selectedPlatform === 'shopify' ? 'selected' : '' ?>>Shopify</option>
                <option value="woocommerce" <?= $selectedPlatform === 'woocommerce' ? 'selected' : '' ?>>WooCommerce</option>
                <option value="csv" <?= $selectedPlatform === 'csv' ? 'selected' : '' ?>>CSV import</option>
            </select>
        </form>
        <div class="push-right">
            <button type="button" class="toolbar-btn" onclick="openModal('importOrderModal')"><i class="bi bi-upload"></i> Import CSV</button>
            <a href="/orders/export-csv" class="toolbar-btn"><i class="bi bi-download"></i> Export CSV</a>
            <a href="/orders/sync-shopify" class="toolbar-btn"><i class="bi bi-arrow-repeat"></i> Sync Shopify</a>
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
                <th>Price</th>
                <th>Source</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($orders)): ?>
                <tr><td colspan="7" style="text-align:center; color:#6b7280; padding:30px;">No orders found.</td></tr>
            <?php else: ?>
                <?php foreach ($orders as $i => $order):
                    $avColor = $avatarColors[$i % count($avatarColors)];
                    $srcKey = $order['source'] ?? 'manual';
                    $srcColor = $sourceColors[$srcKey] ?? ['bg' => '#f3f4f6', 'text' => '#374151'];
                    $currencySymbol = ($srcKey === 'shopify_pull') ? '$' : 'Rs.';
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
                        <td><?= htmlspecialchars($order['product_name'] ?? '-') ?></td>
                        <td><?= $currencySymbol ?> <?= htmlspecialchars(number_format((float)($order['price'] ?? 0), 2)) ?></td>
                        <td>
                            <span class="source-badge" style="background:<?= $srcColor['bg'] ?>; color:<?= $srcColor['text'] ?>;">
                                <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $srcKey))) ?>
                            </span>
                        </td>
                        <td>
                            <a href="/orders/export-shopify?id=<?= $order['id'] ?>" class="action-link"><i class="bi bi-box-arrow-up-right"></i> Push</a>
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

<script>
function openModal(id) { document.getElementById(id).classList.add('show'); }
function closeModal(id) { document.getElementById(id).classList.remove('show'); }
</script>

<?php include __DIR__ . '/../partials/layout_end.php'; ?>