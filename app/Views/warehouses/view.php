<?php $currentPage = 'warehouses'; ?>

<div class="breadcrumb">Finovo <i class="bi bi-chevron-right"></i> Warehouses <i class="bi bi-chevron-right"></i> <?= htmlspecialchars($warehouse['name']) ?></div>

<div class="page-header-row">
    <h1><?= htmlspecialchars($warehouse['name']) ?></h1>
</div>
<p class="page-subtitle"><?= htmlspecialchars($warehouse['location'] ?? 'No location set') ?></p>

<?php if (!empty($linkedStores)): ?>
    <div style="margin-bottom: 16px; display:flex; gap:6px; flex-wrap:wrap;">
        <?php foreach ($linkedStores as $s): ?>
            <span class="source-badge" style="background:#eef2ff; color:#4338ca;">
                <?= htmlspecialchars($s['name']) ?><?= (int) $s['is_default'] === 1 ? ' ★ Default' : '' ?>
            </span>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="banner banner-error" style="margin-bottom:16px;">This warehouse is not linked to any store yet.</div>
<?php endif; ?>

<?php if (!empty($received)): ?>
    <div class="banner banner-success">Stock received into this warehouse.</div>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <div class="banner banner-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="card">
    <div class="filter-row">
        <div class="push-right" style="margin-left:0;">
            <button type="button" class="toolbar-btn btn-dark" onclick="openModal('receiveStockModal')"><i class="bi bi-box-arrow-in-down"></i> Receive stock</button>
        </div>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th>Image</th>
                <th>Product</th>
                <th>SKU</th>
                <th>Price</th>
                <th>Stock in this warehouse</th>
                <th>Receive more</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($products)): ?>
                <tr><td colspan="6" style="text-align:center; color:#6b7280; padding:30px;">No products in linked store(s) yet.</td></tr>
            <?php else: ?>
                <?php foreach ($products as $p):
                    $hasVariants = !empty($p['has_variants']);
                    $stock = (int) $p['warehouse_stock'];
                    if ($stock <= 0) {
                        $badgeColor = ['bg' => '#fee2e2', 'text' => '#991b1b'];
                    } elseif ($stock <= 5) {
                        $badgeColor = ['bg' => '#fef3c7', 'text' => '#b45309'];
                    } else {
                        $badgeColor = ['bg' => '#dcfce7', 'text' => '#15803d'];
                    }
                    $currencySymbol = !empty($p['external_product_id']) ? '$' : 'Rs.';
                ?>
                    <tr>
                        <td>
                            <?php if (!empty($p['image_url'])): ?>
                                <img src="<?= htmlspecialchars($p['image_url']) ?>" alt="" style="width:36px; height:36px; border-radius:8px; object-fit:cover; border:1px solid var(--border-color);">
                            <?php else: ?>
                                <span class="avatar-circle" style="background:#f3f4f6; color:#6b7280;"><i class="bi bi-image"></i></span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($p['name']) ?></td>
                        <td><?= htmlspecialchars($p['sku'] ?? '-') ?></td>
                        <td><?= $currencySymbol ?> <?= number_format((float) $p['price'], 2) ?></td>
                        <td>
                            <?php if ($hasVariants): ?>
                                <span class="source-badge" style="background:#eef2ff; color:#4338ca;">Multiple variants</span>
                            <?php else: ?>
                                <span class="source-badge" style="background:<?= $badgeColor['bg'] ?>; color:<?= $badgeColor['text'] ?>;"><?= $stock ?> units</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($hasVariants): ?>
                                <a href="/warehouses/product-stock?product_id=<?= $p['id'] ?>" class="action-link">
                                    <i class="bi bi-diagram-3"></i> Manage variants
                                </a>
                            <?php else: ?>
                                <form method="POST" action="/warehouses/receive-stock" style="display:flex; gap:6px; align-items:center;">
                                    <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                    <input type="hidden" name="warehouse_id" value="<?= $warehouse['id'] ?>">
                                    <input type="number" name="quantity" min="1" placeholder="qty" style="width:70px; border:1px solid var(--border-color); border-radius:6px; padding:5px 8px; font-size:13px; font-family:'Inter',sans-serif;">
                                    <button type="submit" class="filter-btn"><i class="bi bi-plus-lg"></i> Add</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<a href="/warehouses" style="display: block; margin-top: 16px; font-size: 13px; color: var(--text-muted);">&larr; Back to Warehouses</a>

<div class="modal-backdrop" id="receiveStockModal">
    <div class="modal-box">
        <div class="modal-header">
            <h2>Receive Stock — <?= htmlspecialchars($warehouse['name']) ?></h2>
            <button class="modal-close" onclick="closeModal('receiveStockModal')">&times;</button>
        </div>
        <p class="modal-help">Only simple products (without variants) can be received here. For products with variants, use the "Manage variants" link instead.</p>
        <form action="/warehouses/receive-stock" method="POST">
            <input type="hidden" name="warehouse_id" value="<?= $warehouse['id'] ?>">
            <div class="form-group">
                <label>Product</label>
                <select name="product_id" required style="width:100%; border:1px solid var(--border-color); border-radius:8px; padding:8px 10px; font-size:14px; font-family:'Inter',sans-serif;">
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
</script>