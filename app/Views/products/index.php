<?php
$currentPage = 'products';
include __DIR__ . '/../partials/layout_start.php';

$avatarColors = [
    ['bg' => '#dbeafe', 'text' => '#1d4ed8'],
    ['bg' => '#dcfce7', 'text' => '#15803d'],
    ['bg' => '#fce7f3', 'text' => '#be185d'],
    ['bg' => '#fef3c7', 'text' => '#b45309'],
    ['bg' => '#e0e7ff', 'text' => '#4338ca'],
    ['bg' => '#cffafe', 'text' => '#0e7490'],
];
?>

<div class="breadcrumb">Finovo <i class="bi bi-chevron-right"></i> Products</div>

<div class="page-header-row">
    <h1>Products <span class="count-badge"><?= count($products) ?></span></h1>
</div>
<p class="page-subtitle">Manage your product catalog here.</p>

<?php if (!empty($synced)): ?>
    <div class="banner banner-success"><?= (int)$synced ?> product(s) synced from Shopify.</div>
<?php endif; ?>
<?php if (!empty($exported)): ?>
    <div class="banner banner-success">Product exported to Shopify.</div>
<?php endif; ?>
<?php if (!empty($imported)): ?>
    <div class="banner banner-success"><?= (int)$imported ?> product(s) imported from CSV.</div>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <div class="banner banner-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="card">
    <div class="filter-row">
        <div class="push-right" style="margin-left:0;">
            <a href="/products/sync-shopify" class="toolbar-btn"><i class="bi bi-arrow-repeat"></i> Pull from Shopify</a>
            <button type="button" class="toolbar-btn" onclick="openModal('importProductModal')"><i class="bi bi-upload"></i> Import CSV</button>
            <a href="/products/export-csv" class="toolbar-btn"><i class="bi bi-download"></i> Export CSV</a>
        </div>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th class="checkbox-col"><input type="checkbox"></th>
                <th>Product</th>
                <th>SKU</th>
                <th>Price</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($products)): ?>
                <tr><td colspan="5" style="text-align:center; color:#6b7280; padding:30px;">No products found.</td></tr>
            <?php else: ?>
                <?php foreach ($products as $i => $product):
                    $avColor = $avatarColors[$i % count($avatarColors)];
                    $currencySymbol = !empty($product['external_product_id']) ? '$' : 'Rs.';
                ?>
                    <tr>
                        <td class="checkbox-col"><input type="checkbox"></td>
                        <td>
                            <div class="name-cell">
                                <span class="avatar-circle" style="background:<?= $avColor['bg'] ?>; color:<?= $avColor['text'] ?>;"><?= strtoupper(substr($product['name'] ?? 'P', 0, 1)) ?></span>
                                <?= htmlspecialchars($product['name'] ?? '-') ?>
                            </div>
                        </td>
                        <td><?= htmlspecialchars($product['sku'] ?? '-') ?></td>
                        <td><?= $currencySymbol ?> <?= htmlspecialchars(number_format((float)($product['price'] ?? 0), 2)) ?></td>
                        <td>
                            <a href="/products/export-shopify?id=<?= $product['id'] ?>" class="action-link"><i class="bi bi-box-arrow-up-right"></i> Push</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="pagination-bar">
        <span>Rows per page 15 &nbsp;&nbsp; 1-<?= count($products) ?> of <?= count($products) ?> rows</span>
        <div class="pagination-controls">
            <button><i class="bi bi-chevron-left"></i></button>
            <span class="page-num active">1</span>
            <button><i class="bi bi-chevron-right"></i></button>
        </div>
    </div>
</div>

<div class="modal-backdrop" id="importProductModal">
    <div class="modal-box">
        <div class="modal-header">
            <h2>Import Products (CSV)</h2>
            <button class="modal-close" onclick="closeModal('importProductModal')">&times;</button>
        </div>
        <p class="modal-help">CSV columns must be in this order: <strong>name, sku, price</strong> (first row is treated as the header and skipped).</p>
        <form action="/products/import" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>CSV File</label>
                <input type="file" name="csv_file" accept=".csv" required>
            </div>
            <div class="form-actions">
                <button type="button" class="btn-secondary" onclick="closeModal('importProductModal')">Cancel</button>
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