<?php
$avatarColors = [
    ['bg' => '#dbeafe', 'text' => '#1d4ed8'],
    ['bg' => '#dcfce7', 'text' => '#15803d'],
    ['bg' => '#fce7f3', 'text' => '#be185d'],
    ['bg' => '#fef3c7', 'text' => '#b45309'],
    ['bg' => '#e0e7ff', 'text' => '#4338ca'],
    ['bg' => '#cffafe', 'text' => '#0e7490'],
];
$platform = $store['platform'] ?? 'manual';
?>

<div class="breadcrumb"><a href="/dashboard" class="breadcrumb-link">Finovo</a> <i class="bi bi-chevron-right"></i> Products</div>

<div class="page-header-row">
    <h1>Products <span class="count-badge"><?= count($products) ?></span></h1>
</div>
<p class="page-subtitle">Manage your product catalog and inventory here.</p>

<?php if (!empty($lowStockCount)): ?>
    <div class="banner banner-error"><i class="bi bi-exclamation-triangle"></i> <?= (int)$lowStockCount ?> product(s) are low on stock or out of stock.</div>
<?php endif; ?>
<?php if (!empty($_GET['queued'])): ?>
    <div class="banner banner-success">Sync queued (Job #<?= (int) $_GET['queued'] ?>). Run <code>php worker.php</code> in a terminal to process it.</div>
<?php endif; ?>
<?php if (!empty($synced)): ?>
    <div class="banner banner-success"><?= (int)$synced ?> product(s) synced.</div>
<?php endif; ?>
<?php if (!empty($exported)): ?>
    <div class="banner banner-success">Product exported.</div>
<?php endif; ?>
<?php if (!empty($imported)): ?>
    <div class="banner banner-success"><?= (int)$imported ?> product(s) imported from CSV.</div>
<?php endif; ?>
<?php if (!empty($stockUpdated)): ?>
    <div class="banner banner-success">Saved.</div>
<?php endif; ?>
<?php if (!empty($_GET['deleted'])): ?>
    <div class="banner banner-success">Product deleted.</div>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <div class="banner banner-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="card" style="overflow: visible;">
    <div class="filter-row">
        <div class="push-right" style="margin-left:0;">
            <a href="/warehouses" class="toolbar-btn"><i class="bi bi-building"></i> Warehouses</a>

            <?php if ($platform === 'shopify'): ?>
                <a href="/products/sync-shopify" class="toolbar-btn"><i class="bi bi-arrow-repeat"></i> Pull from Shopify</a>
                <a href="/products/sync-shopify-async" class="toolbar-btn"><i class="bi bi-lightning"></i> Sync (Async/Queue)</a>
            <?php elseif ($platform === 'woocommerce'): ?>
                <a href="/products/sync-woocommerce" class="toolbar-btn"><i class="bi bi-arrow-repeat"></i> Pull from WooCommerce</a>
            <?php elseif ($platform === 'bigcommerce'): ?>
                <a href="/products/sync-bigcommerce" class="toolbar-btn"><i class="bi bi-arrow-repeat"></i> Sync BigCommerce</a>
            <?php elseif ($platform === 'prestashop'): ?>
                <a href="/products/sync-prestashop" class="toolbar-btn"><i class="bi bi-arrow-repeat"></i> Sync PrestaShop</a>
            <?php elseif ($platform === 'opencart'): ?>
                <a href="/products/sync-opencart" class="toolbar-btn"><i class="bi bi-arrow-repeat"></i> Sync OpenCart</a>
            <?php elseif ($platform === 'oscommerce'): ?>
                <a href="/products/sync-oscommerce" class="toolbar-btn"><i class="bi bi-arrow-repeat"></i> Sync osCommerce</a>
            <?php elseif ($platform === 'custom'): ?>
                <a href="/products/sync-custom-bridge" class="toolbar-btn"><i class="bi bi-arrow-repeat"></i> Sync Custom Store</a>
            <?php endif; ?>

            <button type="button" class="toolbar-btn" id="autoRefreshBtn" onclick="toggleAutoRefresh()">
                <i class="bi bi-clock-history"></i> <span id="autoRefreshLabel">Auto-sync: Off</span>
            </button>
            <button type="button" class="toolbar-btn" onclick="openModal('importProductModal')"><i class="bi bi-upload"></i> Import CSV</button>
            <a href="/products/export-csv" class="toolbar-btn"><i class="bi bi-download"></i> Export CSV</a>
        </div>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th class="checkbox-col"><input type="checkbox"></th>
                <th>Image</th>
                <th>Product</th>
                <th>SKU</th>
                <th>Price</th>
                <th>Stock</th>
                <th style="width:60px;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($products)): ?>
                <tr><td colspan="7" style="text-align:center; color:#6b7280; padding:30px;">No products found.</td></tr>
            <?php else: ?>
                <?php foreach ($products as $i => $product):
                    $avColor = $avatarColors[$i % count($avatarColors)];

                    $isExternal = !empty($product['external_product_id'])
                        || !empty($product['external_wc_product_id'])
                        || !empty($product['external_bc_product_id'])
                        || !empty($product['external_ps_product_id'])
                        || !empty($product['external_ocart_product_id'])
                        || !empty($product['external_osc_product_id']);
                    $currencySymbol = $isExternal ? '$' : 'Rs.';

                    $stock = (int) ($product['stock_quantity'] ?? 0);
                    $threshold = (int) ($product['low_stock_threshold'] ?? 5);
                    $variantCount = (int) ($product['variant_count'] ?? 0);
                    $rowId = 'product-row-' . $product['id'];

                    if ($stock <= 0) {
                        $stockColor = ['bg' => '#fee2e2', 'text' => '#991b1b'];
                        $stockLabel = 'Out of stock';
                    } elseif ($stock <= $threshold) {
                        $stockColor = ['bg' => '#fef3c7', 'text' => '#b45309'];
                        $stockLabel = 'Low stock';
                    } else {
                        $stockColor = ['bg' => '#dcfce7', 'text' => '#15803d'];
                        $stockLabel = 'In stock';
                    }
                ?>
                    <tr>
                        <td class="checkbox-col"><input type="checkbox"></td>
                        <td>
                            <?php if (!empty($product['image_url'])): ?>
                                <img src="<?= htmlspecialchars($product['image_url']) ?>" alt="" style="width:40px; height:40px; border-radius:8px; object-fit:cover; border:1px solid var(--border-color);">
                            <?php else: ?>
                                <span class="avatar-circle" style="background:<?= $avColor['bg'] ?>; color:<?= $avColor['text'] ?>;"><i class="bi bi-image"></i></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="name-cell">
                                <?php if ($variantCount > 0): ?>
                                    <a href="#" onclick="toggleVariants('<?= $rowId ?>'); return false;" style="text-decoration:none; color:inherit; display:flex; align-items:center; gap:6px;">
                                        <i class="bi bi-chevron-right" id="chevron-<?= $product['id'] ?>" style="font-size:11px; color:var(--text-muted); transition: transform 0.15s;"></i>
                                        <?= htmlspecialchars($product['name'] ?? '-') ?>
                                        <span class="source-badge" style="background:#eef2ff; color:#4338ca;"><?= $variantCount ?> variants</span>
                                    </a>
                                <?php else: ?>
                                    <?= htmlspecialchars($product['name'] ?? '-') ?>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td><?= htmlspecialchars($product['sku'] ?? '-') ?></td>
                        <td>
                            <?= $variantCount > 0 ? 'From ' : '' ?><?= $currencySymbol ?> <?= htmlspecialchars(number_format((float)($product['display_price'] ?? $product['price'] ?? 0), 2)) ?>
                        </td>
                        <td>
                            <a href="/warehouses/product-stock?product_id=<?= $product['id'] ?>" class="source-badge" style="background:<?= $stockColor['bg'] ?>; color:<?= $stockColor['text'] ?>; text-decoration:none;"><?= $stockLabel ?> (<?= $stock ?>)</a>
                        </td>
                        <td style="position:relative;">
                            <button type="button" class="row-menu-btn" onclick="toggleRowMenu(event, 'product-menu-<?= $product['id'] ?>')">
                                <i class="bi bi-three-dots-vertical"></i>
                            </button>
                            <div class="row-menu" id="product-menu-<?= $product['id'] ?>">
                                <a href="/products/edit?id=<?= $product['id'] ?>"><i class="bi bi-pencil"></i> Edit</a>
                                <div class="row-menu-divider"></div>
                                <a href="/products/export-shopify?id=<?= $product['id'] ?>"><i class="bi bi-box-arrow-up-right"></i> Push to Shopify</a>
                                <div class="row-menu-divider"></div>
                                <a href="#" class="row-menu-danger" onclick="if(confirm('Delete this product? This cannot be undone.')){document.getElementById('deleteProductForm<?= $product['id'] ?>').submit();} return false;"><i class="bi bi-trash"></i> Delete</a>
                            </div>
                            <form id="deleteProductForm<?= $product['id'] ?>" action="/products/delete" method="POST" style="display:none;">
                                <input type="hidden" name="id" value="<?= $product['id'] ?>">
                            </form>
                        </td>
                    </tr>
                    <?php if ($variantCount > 0): ?>
                        <tr id="<?= $rowId ?>" style="display:none;">
                            <td></td>
                            <td colspan="6" style="padding:0 0 14px 0;">
                                <table style="width:100%; background:#f9fafb; border-radius:8px; overflow:hidden;">
                                    <thead>
                                        <tr style="font-size:12px; color:var(--text-muted);">
                                            <th style="text-align:left; padding:8px 16px;">Variant</th>
                                            <th style="text-align:left; padding:8px 16px;">SKU</th>
                                            <th style="text-align:left; padding:8px 16px;">Price</th>
                                            <th style="text-align:left; padding:8px 16px;">Stock</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($product['variants'] as $v): ?>
                                            <tr style="font-size:13px;">
                                                <td style="padding:8px 16px;"><?= htmlspecialchars($v['label']) ?></td>
                                                <td style="padding:8px 16px; color:var(--text-muted);"><?= htmlspecialchars($v['sku'] ?: '-') ?></td>
                                                <td style="padding:8px 16px;"><?= $currencySymbol ?> <?= number_format((float) $v['price'], 2) ?></td>
                                                <td style="padding:8px 16px;">
                                                    <?php $vStock = (int) $v['stock_quantity']; ?>
                                                    <span class="source-badge" style="background:<?= $vStock <= 0 ? '#fee2e2' : '#dcfce7' ?>; color:<?= $vStock <= 0 ? '#991b1b' : '#15803d' ?>;">
                                                        <?= $vStock <= 0 ? 'Out of stock' : $vStock . ' units' ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                    <?php endif; ?>
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
        <p class="modal-help">CSV columns must be in this order: <strong>name, sku, price, stock</strong> (stock is optional). Variants and images do not come from CSV, only from Shopify pull.</p>
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
    position: absolute;
    right: 8px;
    top: calc(100% - 4px);
    background: var(--bg-white, #fff);
    border: 1px solid var(--border-color);
    border-radius: 10px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.12);
    min-width: 200px;
    z-index: 50;
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

function toggleVariants(rowId) {
    const row = document.getElementById(rowId);
    const productId = rowId.replace('product-row-', '');
    const chevron = document.getElementById('chevron-' + productId);
    if (row.style.display === 'none') {
        row.style.display = '';
        chevron.style.transform = 'rotate(90deg)';
    } else {
        row.style.display = 'none';
        chevron.style.transform = 'rotate(0deg)';
    }
}

function toggleRowMenu(event, menuId) {
    event.stopPropagation();
    document.querySelectorAll('.row-menu.show').forEach(m => {
        if (m.id !== menuId) m.classList.remove('show');
    });
    document.getElementById(menuId).classList.toggle('show');
}

document.addEventListener('click', function() {
    document.querySelectorAll('.row-menu.show').forEach(m => m.classList.remove('show'));
});

// ---------- Auto-Refresh (Client-Side Scheduled Reconciliation) ----------
let autoRefreshInterval = null;

function toggleAutoRefresh() {
    const label = document.getElementById('autoRefreshLabel');
    if (autoRefreshInterval) {
        clearInterval(autoRefreshInterval);
        autoRefreshInterval = null;
        label.textContent = 'Auto-sync: Off';
        localStorage.removeItem('autoRefreshProducts');
    } else {
        autoRefreshInterval = setInterval(() => {
            window.location.reload();
        }, 5 * 60 * 1000);
        label.textContent = 'Auto-sync: On (5 min)';
        localStorage.setItem('autoRefreshProducts', '1');
    }
}

if (localStorage.getItem('autoRefreshProducts') === '1') {
    toggleAutoRefresh();
}
</script>