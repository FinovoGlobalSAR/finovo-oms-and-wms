<?php
$currentPage = 'warehouses';

// ---------- Summary numbers (sirf display ke liye, $products se hi) ----------
$unitsHere = 0;
$lowCount = 0;
$outCount = 0;
foreach ($products as $p) {
    if (!empty($p['has_variants'])) { continue; }
    $st = (int) $p['warehouse_stock'];
    $unitsHere += $st;
    if ($st <= 0) { $outCount++; } elseif ($st <= 5) { $lowCount++; }
}
?>

<div class="ui-head">
    <div class="ui-head-left">
        <div class="breadcrumb">Finovo <i class="bi bi-chevron-right"></i> <a href="/warehouses" style="color:inherit; text-decoration:none;">Warehouses</a> <i class="bi bi-chevron-right"></i> <span class="current"><?= htmlspecialchars($warehouse['name']) ?></span></div>
        <h1><?= htmlspecialchars($warehouse['name']) ?></h1>
        <p><i class="bi bi-geo-alt" style="color:#94a3b8;"></i> <?= htmlspecialchars($warehouse['location'] ?? 'No location set') ?></p>
        <?php if (!empty($linkedStores)): ?>
            <div style="display:flex; gap:6px; flex-wrap:wrap; margin-top:4px;">
                <?php foreach ($linkedStores as $s): ?>
                    <span class="ui-tag" style="background:var(--primary-light); color:var(--primary-dark); border:1px solid var(--primary-border); font-weight:500;">
                        <i class="bi bi-shop"></i> <?= htmlspecialchars($s['name']) ?>
                        <?php if ((int) $s['is_default'] === 1): ?><i class="bi bi-star-fill" style="color:#b45309; font-size:10px;" title="Default"></i><?php endif; ?>
                    </span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <div class="ui-head-right">
        <div style="display:flex; gap:8px;">
            <a href="/warehouses" class="ui-btn"><i class="bi bi-arrow-left"></i> Back</a>
            <button type="button" class="ui-btn ui-btn-primary" onclick="openModal('receiveStockModal')"><i class="bi bi-box-arrow-in-down"></i> Receive Stock</button>
        </div>
    </div>
</div>

<?php if (empty($linkedStores)): ?>
    <div class="ui-alert">
        <div class="ui-tip-icon"><i class="bi bi-exclamation-triangle"></i></div>
        <div class="ui-alert-body">
            <strong>Not linked to any store</strong>
            <span>This warehouse is not linked to any store yet, so orders can't use its stock.</span>
        </div>
        <a href="/stores" class="ui-btn ui-btn-sm" style="border-color:#fde68a; background:#fff;">Go to Stores <i class="bi bi-arrow-right"></i></a>
    </div>
<?php endif; ?>

<?php if (!empty($received)): ?>
    <div class="banner banner-success"><i class="bi bi-check-circle"></i> Stock received into this warehouse.</div>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <div class="banner banner-error"><i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="ui-stats">
    <div class="ui-stat">
        <div class="ui-stat-icon"><i class="bi bi-box-seam"></i></div>
        <div>
            <div class="ui-stat-label">Products</div>
            <div class="ui-stat-value"><b><?= count($products) ?></b><span class="ui-pill ui-pill-blue">In linked stores</span></div>
        </div>
    </div>
    <div class="ui-stat">
        <div class="ui-stat-icon"><i class="bi bi-boxes"></i></div>
        <div>
            <div class="ui-stat-label">Units in Stock</div>
            <div class="ui-stat-value"><b><?= number_format($unitsHere) ?></b><span class="ui-pill ui-pill-blue">Simple products</span></div>
        </div>
    </div>
    <div class="ui-stat">
        <div class="ui-stat-icon"><i class="bi bi-exclamation-triangle"></i></div>
        <div>
            <div class="ui-stat-label">Low Stock (5 or less)</div>
            <div class="ui-stat-value"><b><?= $lowCount ?></b>
                <span class="ui-pill <?= $lowCount > 0 ? 'ui-pill-amber' : 'ui-pill-green' ?>"><?= $lowCount > 0 ? 'Restock soon' : 'Healthy' ?></span>
            </div>
        </div>
    </div>
    <div class="ui-stat">
        <div class="ui-stat-icon"><i class="bi bi-x-circle"></i></div>
        <div>
            <div class="ui-stat-label">Out of Stock</div>
            <div class="ui-stat-value"><b><?= $outCount ?></b>
                <span class="ui-pill <?= $outCount > 0 ? 'ui-pill-red' : 'ui-pill-green' ?>"><?= $outCount > 0 ? 'Action needed' : 'None' ?></span>
            </div>
        </div>
    </div>
</div>

<div class="ui-card">
    <div class="ui-card-head">
        <div class="ui-card-title">
            <div class="ui-card-title-icon"><i class="bi bi-box-seam"></i></div>
            <div>
                <h2>Products in this Warehouse</h2>
                <div class="sub">Add received quantity directly from a row, or use "Receive Stock".</div>
            </div>
        </div>
        <div class="ui-card-tools">
            <select id="whvFilter" class="ui-btn" aria-label="Filter by stock level" style="padding-right:10px;">
                <option value="">All stock levels</option>
                <option value="ok">In stock</option>
                <option value="low">Low stock</option>
                <option value="out">Out of stock</option>
                <option value="variants">Has variants</option>
            </select>
        </div>
    </div>
    <div class="ui-card-search">
        <label class="ui-search">
            <i class="bi bi-search"></i>
            <input type="text" id="whvSearch" placeholder="Search by product name or SKU..." aria-label="Search products">
        </label>
    </div>

    <div style="overflow-x:auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Product</th>
                    <th>SKU</th>
                    <th>Price</th>
                    <th>Stock Here</th>
                    <th>Receive More</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($products)): ?>
                    <tr><td colspan="6" class="ui-empty"><i class="bi bi-box-seam"></i>No products in linked store(s) yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($products as $p):
                        $hasVariants = !empty($p['has_variants']);
                        $stock = (int) $p['warehouse_stock'];
                        if ($hasVariants) {
                            $level = 'variants';
                        } elseif ($stock <= 0) {
                            $level = 'out';
                        } elseif ($stock <= 5) {
                            $level = 'low';
                        } else {
                            $level = 'ok';
                        }
                        $levelPill = ['out' => 'ui-pill-red', 'low' => 'ui-pill-amber', 'ok' => 'ui-pill-green', 'variants' => 'ui-pill-blue'][$level];
                        $currencySymbol = !empty($p['external_product_id']) ? '$' : 'Rs.';
                    ?>
                        <tr class="whv-row" data-level="<?= $level ?>" data-search="<?= htmlspecialchars(strtolower($p['name'] . ' ' . ($p['sku'] ?? ''))) ?>">
                            <td>
                                <?php if (!empty($p['image_url'])): ?>
                                    <img src="<?= htmlspecialchars($p['image_url']) ?>" alt="" style="width:38px; height:38px; border-radius:8px; object-fit:cover; border:1px solid var(--border-color);">
                                <?php else: ?>
                                    <span style="width:38px; height:38px; border-radius:8px; background:#f1f5f9; color:#94a3b8; display:flex; align-items:center; justify-content:center;"><i class="bi bi-image"></i></span>
                                <?php endif; ?>
                            </td>
                            <td style="font-weight:600;"><?= htmlspecialchars($p['name']) ?></td>
                            <td style="color:#475569;"><?= htmlspecialchars($p['sku'] ?? '-') ?></td>
                            <td style="white-space:nowrap;"><?= $currencySymbol ?> <?= number_format((float) $p['price'], 2) ?></td>
                            <td>
                                <?php if ($hasVariants): ?>
                                    <span class="ui-tag <?= $levelPill ?>"><i class="bi bi-diagram-3"></i> Multiple variants</span>
                                <?php else: ?>
                                    <span class="ui-tag <?= $levelPill ?>"><?= $stock ?> units</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($hasVariants): ?>
                                    <a href="/warehouses/product-stock?product_id=<?= $p['id'] ?>" class="ui-btn ui-btn-soft ui-btn-sm">
                                        <i class="bi bi-diagram-3"></i> Manage variants
                                    </a>
                                <?php else: ?>
                                    <form method="POST" action="/warehouses/receive-stock" style="display:flex; gap:6px; align-items:center; margin:0;">
                                        <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                        <input type="hidden" name="warehouse_id" value="<?= $warehouse['id'] ?>">
                                        <input type="number" name="quantity" min="1" placeholder="Qty" aria-label="Quantity to receive for <?= htmlspecialchars($p['name']) ?>" style="width:76px; height:32px; border:1px solid var(--border-color); border-radius:7px; padding:0 10px; font-size:12.5px; font-family:inherit; background:#f8fafc;">
                                        <button type="submit" class="ui-btn ui-btn-soft ui-btn-sm"><i class="bi bi-plus-lg"></i> Add</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <tr id="whvNoMatch" style="display:none;"><td colspan="6" class="ui-empty"><i class="bi bi-search"></i>No products match your search.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="pagination-bar">
        <span id="whvShowing">Showing <?= count($products) ?> of <?= count($products) ?> products</span>
    </div>
</div>

<div class="modal-backdrop" id="receiveStockModal">
    <div class="modal-box">
        <div class="modal-header">
            <h2>Receive Stock — <?= htmlspecialchars($warehouse['name']) ?></h2>
            <button type="button" class="modal-close" onclick="closeModal('receiveStockModal')" aria-label="Close">&times;</button>
        </div>
        <p class="modal-help">Only simple products (without variants) can be received here. For products with variants, use the "Manage variants" button instead.</p>
        <form action="/warehouses/receive-stock" method="POST">
            <input type="hidden" name="warehouse_id" value="<?= $warehouse['id'] ?>">
            <div class="form-group">
                <label>Product</label>
                <select name="product_id" required style="width:100%; border:1px solid var(--border-color); border-radius:9px; padding:9px 12px; font-size:13.5px; font-family:inherit; background:#f8fafc;">
                    <option value="">Select a product...</option>
                    <?php foreach ($products as $p): ?>
                        <?php if (empty($p['has_variants'])): ?>
                            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?> (currently <?= (int) $p['warehouse_stock'] ?>)</option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Quantity received</label>
                <input type="number" name="quantity" min="1" placeholder="e.g. 50" required>
            </div>
            <div class="form-actions">
                <button type="button" class="btn-secondary" onclick="closeModal('receiveStockModal')">Cancel</button>
                <button type="submit" class="btn-primary">Receive Stock</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(id) { document.getElementById(id).classList.add('show'); }
function closeModal(id) { document.getElementById(id).classList.remove('show'); }

(function () {
    var input = document.getElementById('whvSearch');
    var filter = document.getElementById('whvFilter');
    var rows = Array.prototype.slice.call(document.querySelectorAll('.whv-row'));
    function apply() {
        var q = input.value.trim().toLowerCase();
        var lv = filter.value;
        var shown = 0;
        rows.forEach(function (r) {
            var ok = (q === '' || r.dataset.search.indexOf(q) !== -1) && (lv === '' || r.dataset.level === lv);
            r.style.display = ok ? '' : 'none';
            if (ok) { shown++; }
        });
        var nm = document.getElementById('whvNoMatch');
        if (nm) { nm.style.display = shown === 0 ? '' : 'none'; }
        document.getElementById('whvShowing').textContent = 'Showing ' + shown + ' of ' + rows.length + ' products';
    }
    if (input) { input.addEventListener('input', apply); }
    if (filter) { filter.addEventListener('change', apply); }
})();
</script>