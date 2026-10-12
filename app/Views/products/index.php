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

<div class="ui-head">
    <div class="ui-head-left">
        <div class="breadcrumb">Finovo <i class="bi bi-chevron-right"></i> <span class="current">Products</span></div>
        <h1>Products <span class="count-badge"><?= (int) ($totalProducts ?? count($products)) ?></span></h1>
        <p>Manage your product catalog and inventory here.</p>
    </div>
</div>

<?php if (!empty($lowStockCount)): ?>
    <div class="banner banner-error"><i class="bi bi-exclamation-triangle"></i> <?= (int)$lowStockCount ?> product(s) are low on stock or out of stock.</div>
<?php endif; ?>
<?php if (!empty($_GET['queued'])): ?>
    <div class="banner banner-success">Sync queued (Job #<?= (int) $_GET['queued'] ?>). Run <code>php worker.php</code> in a terminal to process it.</div>
<?php endif; ?>
<?php if ($synced !== null && $synced !== ''): ?>
    <div class="banner banner-success">
        <i class="bi bi-check-circle"></i>
        Sync done: <?= (int) $synced ?> new product(s) added<?php if ((int) $stockSynced > 0): ?>, stock updated for <?= (int) $stockSynced ?> existing product(s)<?php endif; ?>.
    </div>
<?php endif; ?>
<?php if ((int) $untracked > 0): ?>
    <div class="banner banner-error">
        <i class="bi bi-info-circle"></i>
        <?= (int) $untracked ?> product(s) / variant(s) do not track stock quantity on the store's platform, so their Finovo stock was not changed.
        To sync their stock, turn on stock tracking on the platform (WooCommerce: Product → Inventory → "Manage stock";
        Shopify: Product → "Track quantity"), then sync again.
    </div>
<?php endif; ?>
<?php if (!empty($exported)): ?>
    <div class="banner banner-success">Product pushed to <?= htmlspecialchars($exported === '1' ? 'the store' : $exported) ?>.</div>
<?php endif; ?>
<?php if (!empty($pushNote)): ?>
    <div class="banner banner-error"><i class="bi bi-info-circle"></i> <?= htmlspecialchars($pushNote) ?></div>
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

<div class="ui-card tbl-card">
    <!-- Search + stock filter: server pe chalta hai, har page pe sahi result -->
    <form method="GET" action="/products">
        <div class="tbl-head">
            <h2>All Products</h2>
            <div class="tbl-tools">
                <?php if ($platform === 'shopify'): ?>
                    <a href="/products/sync-shopify" class="tbl-btn tbl-btn-soft"><i class="bi bi-arrow-repeat"></i> Pull from Shopify</a>
                    <a href="/products/sync-shopify-async" class="tbl-btn"><i class="bi bi-lightning"></i> Sync (Async)</a>
                <?php elseif ($platform === 'woocommerce'): ?>
                    <a href="/products/sync-woocommerce" class="tbl-btn tbl-btn-soft"><i class="bi bi-arrow-repeat"></i> Pull from WooCommerce</a>
                    <a href="/products/sync-woocommerce-async" class="tbl-btn"><i class="bi bi-lightning"></i> Sync (Async)</a>
                <?php elseif ($platform === 'bigcommerce'): ?>
                    <a href="/products/sync-bigcommerce" class="tbl-btn tbl-btn-soft"><i class="bi bi-arrow-repeat"></i> Sync BigCommerce</a>
                    <a href="/products/sync-bigcommerce-async" class="tbl-btn"><i class="bi bi-lightning"></i> Sync (Async)</a>
                <?php elseif ($platform === 'prestashop'): ?>
                    <a href="/products/sync-prestashop" class="tbl-btn tbl-btn-soft"><i class="bi bi-arrow-repeat"></i> Sync PrestaShop</a>
                    <a href="/products/sync-prestashop-async" class="tbl-btn"><i class="bi bi-lightning"></i> Sync (Async)</a>
                <?php elseif ($platform === 'opencart'): ?>
                    <a href="/products/sync-opencart" class="tbl-btn tbl-btn-soft"><i class="bi bi-arrow-repeat"></i> Sync OpenCart</a>
                    <a href="/products/sync-opencart-async" class="tbl-btn"><i class="bi bi-lightning"></i> Sync (Async)</a>
                <?php elseif ($platform === 'oscommerce'): ?>
                    <a href="/products/sync-oscommerce" class="tbl-btn tbl-btn-soft"><i class="bi bi-arrow-repeat"></i> Sync osCommerce</a>
                    <a href="/products/sync-oscommerce-async" class="tbl-btn"><i class="bi bi-lightning"></i> Sync (Async)</a>
                <?php elseif ($platform === 'wix'): ?>
                    <a href="/products/sync-wix" class="tbl-btn tbl-btn-soft"><i class="bi bi-arrow-repeat"></i> Sync Wix</a>
                <?php elseif ($platform === 'ebay'): ?>
                    <a href="/products/sync-ebay" class="tbl-btn tbl-btn-soft"><i class="bi bi-arrow-repeat"></i> Sync eBay</a>
                <?php elseif ($platform === 'cj'): ?>
                    <a href="/products/sync-cj" class="tbl-btn tbl-btn-soft"><i class="bi bi-arrow-repeat"></i> Sync CJ</a>
                <?php elseif ($platform === 'magento'): ?>
                    <a href="/products/sync-magento" class="tbl-btn tbl-btn-soft"><i class="bi bi-arrow-repeat"></i> Sync Magento</a>
                <?php elseif ($platform === 'custom'): ?>
                    <a href="/products/sync-custom-bridge" class="tbl-btn tbl-btn-soft"><i class="bi bi-arrow-repeat"></i> Sync Custom Store</a>
                <?php endif; ?>

                <button type="button" class="tbl-btn" id="autoRefreshBtn" onclick="toggleAutoRefresh()">
                    <i class="bi bi-clock-history"></i> <span id="autoRefreshLabel">Auto-sync: Off</span>
                </button>
                <div class="ui-menu">
                    <button type="button" class="tbl-btn" data-menu-toggle><i class="bi bi-sliders"></i> Filters<?php if (($stockFilter ?? '') !== ''): ?> <span class="tbl-count">1</span><?php endif; ?></button>
                    <div class="ui-menu-list tbl-filter-panel">
                        <label for="prodStock">Stock</label>
                        <select name="stock" id="prodStock" onchange="this.form.submit()">
                            <option value="" <?= ($stockFilter ?? '') === '' ? 'selected' : '' ?>>All stock</option>
                            <option value="low" <?= ($stockFilter ?? '') === 'low' ? 'selected' : '' ?>>Low stock</option>
                            <option value="out" <?= ($stockFilter ?? '') === 'out' ? 'selected' : '' ?>>Out of stock</option>
                        </select>
                        <div class="tbl-filter-actions">
                            <a href="/products" class="tbl-btn">Clear</a>
                            <button type="submit" class="tbl-btn tbl-btn-soft">Apply</button>
                        </div>
                    </div>
                </div>
                <button type="button" class="tbl-btn" onclick="openModal('importProductModal')"><i class="bi bi-upload"></i> Import</button>
                <a href="/products/export-csv" class="tbl-btn"><i class="bi bi-download"></i> Export</a>
            </div>
        </div>
        <div class="tbl-search">
            <label class="ui-search">
                <i class="bi bi-search"></i>
                <input type="text" name="q" value="<?= htmlspecialchars($search ?? '') ?>" placeholder="Search name or SKU..." aria-label="Search products">
            </label>
        </div>
    </form>

    <table class="data-table">
        <thead>
            <tr>
                <th>Product</th>
                <th>Price</th>
                <th>Stock</th>
                <th style="width:60px;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($products)): ?>
                <tr><td colspan="4" class="ui-empty"><i class="bi bi-box-seam"></i>No products found.</td></tr>
            <?php else: ?>
                <?php foreach ($products as $i => $product):
                    $avColor = $avatarColors[$i % count($avatarColors)];

                    $isExternal = !empty($product['external_product_id'])
                        || !empty($product['external_wc_product_id'])
                        || !empty($product['external_bc_product_id'])
                        || !empty($product['external_ps_product_id'])
                        || !empty($product['external_ocart_product_id'])
                        || !empty($product['external_osc_product_id'])
                        || !empty($product['external_wix_product_id'])
                        || !empty($product['external_ebay_product_id'])
                        || !empty($product['external_cj_product_id'])
                        || !empty($product['external_magento_product_id']);
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
                        <td>
                            <div class="name-cell" style="display:flex; align-items:center; gap:10px;">
                                <!-- Image ab alag column nahi, naam ke saath chhoti si -->
                                <?php if (!empty($product['image_url'])): ?>
                                    <img src="<?= htmlspecialchars($product['image_url']) ?>" alt="" style="width:36px; height:36px; border-radius:8px; object-fit:cover; border:1px solid var(--border-color); flex-shrink:0;">
                                <?php else: ?>
                                    <span class="avatar-circle" style="background:<?= $avColor['bg'] ?>; color:<?= $avColor['text'] ?>; flex-shrink:0;"><i class="bi bi-image"></i></span>
                                <?php endif; ?>
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
                                <?php if ($pushTarget): ?>
                                    <div class="row-menu-divider"></div>
                                    <?php if (!empty($product[$pushTarget['column']])): ?>
                                        <span class="row-menu-note"><i class="bi bi-check2-circle"></i> Already on <?= htmlspecialchars($pushTarget['label']) ?></span>
                                    <?php else: ?>
                                        <a href="/products/push?id=<?= $product['id'] ?>"><i class="bi bi-box-arrow-up-right"></i> Push to <?= htmlspecialchars($pushTarget['label']) ?></a>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <?php if (in_array(strtolower(trim($_SESSION['user']['role'] ?? '')), ['admin', 'manager'], true)): ?>
                                    <!-- Product delete sirf Admin / Manager -->
                                    <div class="row-menu-divider"></div>
                                    <a href="#" class="row-menu-danger" onclick="if(confirm('Delete this product? This cannot be undone.')){document.getElementById('deleteProductForm<?= $product['id'] ?>').submit();} return false;"><i class="bi bi-trash"></i> Delete</a>
                                <?php endif; ?>
                            </div>
                            <form id="deleteProductForm<?= $product['id'] ?>" action="/products/delete" method="POST" style="display:none;">
                                <input type="hidden" name="id" value="<?= $product['id'] ?>">
                            </form>
                        </td>
                    </tr>
                    <?php if ($variantCount > 0): ?>
                        <tr id="<?= $rowId ?>" style="display:none;">
                            <td colspan="4" style="padding:0 0 14px 0;">
                                <table style="width:100%; background:#f9fafb; border-radius:8px; overflow:hidden;">
                                    <thead>
                                        <tr style="font-size:12px; color:var(--text-muted);">
                                            <th style="text-align:left; padding:8px 16px;">Variant</th>
                                            <th style="text-align:left; padding:8px 16px;">Price</th>
                                            <th style="text-align:left; padding:8px 16px;">Stock</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($product['variants'] as $v): ?>
                                            <tr style="font-size:13px;">
                                                <td style="padding:8px 16px;"><?= htmlspecialchars($v['label']) ?></td>
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

    <?php if (!empty($pager)) { $pagerLabel = 'products'; require __DIR__ . '/../partials/pagination.php'; } ?>
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
.row-menu-note {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 10px;
    font-size: 13px;
    color: var(--text-muted);
    white-space: nowrap;
}
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