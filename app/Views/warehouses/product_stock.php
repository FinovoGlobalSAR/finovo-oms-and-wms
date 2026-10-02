<?php
$currentPage = 'products';

$inputStyle = 'width:84px; height:32px; border:1px solid var(--border-color); border-radius:7px; padding:0 10px; font-size:12.5px; font-family:inherit; background:#f8fafc;';

// ---------- Total stock (sirf display ke liye) ----------
if (!$hasVariants) {
    $grandTotal = array_sum(array_map(fn($r) => (int) $r['stock_quantity'], $stockList));
    $warehouseCount = count($stockList);
} else {
    $grouped = [];
    foreach ($variantMatrix as $row) {
        $grouped[$row['variant_id']]['label'] = $row['label'];
        $grouped[$row['variant_id']]['sku'] = $row['sku'];
        $grouped[$row['variant_id']]['warehouses'][] = $row;
    }
    $grandTotal = array_sum(array_map(fn($r) => (int) $r['stock_quantity'], $variantMatrix));
    $warehouseIds = [];
    foreach ($variantMatrix as $row) { $warehouseIds[$row['warehouse_id']] = true; }
    $warehouseCount = count($warehouseIds);
}
?>

<div class="ui-head">
    <div class="ui-head-left">
        <div class="breadcrumb">Finovo <i class="bi bi-chevron-right"></i> <a href="/products" style="color:inherit; text-decoration:none;">Products</a> <i class="bi bi-chevron-right"></i> <span class="current">Warehouse stock</span></div>
        <h1><?= htmlspecialchars($product['name']) ?></h1>
        <p><?= $hasVariants ? 'Stock for this product\'s variants, broken down by warehouse.' : 'Stock for this product, broken down by warehouse.' ?></p>
    </div>
    <div class="ui-head-right">
        <a href="/products" class="ui-btn"><i class="bi bi-arrow-left"></i> Back to Products</a>
    </div>
</div>

<?php if (!empty($updated)): ?>
    <div class="banner banner-success"><i class="bi bi-check-circle"></i> Stock updated.</div>
<?php endif; ?>

<div class="ui-stats">
    <div class="ui-stat">
        <div class="ui-stat-icon"><i class="bi bi-boxes"></i></div>
        <div>
            <div class="ui-stat-label">Total Stock</div>
            <div class="ui-stat-value"><b><?= number_format($grandTotal) ?></b>
                <span class="ui-pill <?= $grandTotal > 0 ? 'ui-pill-green' : 'ui-pill-red' ?>"><?= $grandTotal > 0 ? 'Units' : 'Out of stock' ?></span>
            </div>
        </div>
    </div>
    <div class="ui-stat">
        <div class="ui-stat-icon"><i class="bi bi-house-door"></i></div>
        <div>
            <div class="ui-stat-label">Warehouses</div>
            <div class="ui-stat-value"><b><?= $warehouseCount ?></b><span class="ui-pill ui-pill-blue">Holding stock</span></div>
        </div>
    </div>
    <?php if ($hasVariants): ?>
        <div class="ui-stat">
            <div class="ui-stat-icon"><i class="bi bi-diagram-3"></i></div>
            <div>
                <div class="ui-stat-label">Variants</div>
                <div class="ui-stat-value"><b><?= count($grouped) ?></b><span class="ui-pill ui-pill-blue">Options</span></div>
            </div>
        </div>
    <?php endif; ?>
    <div class="ui-tip">
        <div class="ui-tip-icon"><i class="bi bi-lightbulb"></i></div>
        <div>
            <strong>Tip</strong>
            <span>Type the new total for a warehouse and press the tick button to save it.</span>
        </div>
    </div>
</div>

<?php if (!$hasVariants): ?>

    <div class="ui-card">
        <div class="ui-card-head" style="padding-bottom:14px;">
            <div class="ui-card-title">
                <div class="ui-card-title-icon"><i class="bi bi-house-door"></i></div>
                <div>
                    <h2>Stock by Warehouse</h2>
                    <div class="sub">Set the exact quantity held in each warehouse.</div>
                </div>
            </div>
        </div>
        <div style="overflow-x:auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Warehouse</th>
                        <th>Location</th>
                        <th>Stock</th>
                        <th>Update</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($stockList)): ?>
                        <tr><td colspan="4" class="ui-empty"><i class="bi bi-house-door"></i>No warehouses linked to this product's store yet.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($stockList as $row): $q = (int) $row['stock_quantity']; ?>
                        <tr>
                            <td>
                                <span style="font-weight:600;"><?= htmlspecialchars($row['warehouse_name']) ?></span>
                                <?php if ((int) $row['is_default'] === 1): ?>
                                    <span class="ui-pill ui-pill-green" style="margin-left:6px;">Default</span>
                                <?php endif; ?>
                            </td>
                            <td style="color:#334155;"><?= htmlspecialchars($row['location'] ?? '-') ?></td>
                            <td><span class="ui-tag <?= $q <= 0 ? 'ui-pill-red' : ($q <= 5 ? 'ui-pill-amber' : 'ui-pill-green') ?>"><?= $q ?> units</span></td>
                            <td>
                                <form method="POST" action="/warehouses/update-stock" style="display:flex; gap:6px; align-items:center; margin:0;">
                                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                    <input type="hidden" name="warehouse_id" value="<?= $row['warehouse_id'] ?>">
                                    <input type="number" name="stock_quantity" value="<?= $q ?>" min="0" aria-label="New stock for <?= htmlspecialchars($row['warehouse_name']) ?>" style="<?= $inputStyle ?>">
                                    <button type="submit" class="ui-btn ui-btn-soft ui-btn-icon" title="Save" aria-label="Save stock"><i class="bi bi-check-lg"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php else: ?>

    <?php foreach ($grouped as $variantId => $variant):
        $totalStock = array_sum(array_column($variant['warehouses'], 'stock_quantity'));
        $isOut = $totalStock <= 0;
    ?>
        <div class="ui-card">
            <div class="ui-card-head" style="padding-bottom:14px;">
                <div class="ui-card-title">
                    <div class="ui-card-title-icon"><i class="bi bi-tag"></i></div>
                    <div>
                        <h2><?= htmlspecialchars($variant['label']) ?></h2>
                        <div class="sub"><?= !empty($variant['sku']) ? 'SKU: ' . htmlspecialchars($variant['sku']) : 'No SKU' ?></div>
                    </div>
                </div>
                <?php if ($isOut): ?>
                    <span class="ui-tag ui-pill-red"><i class="bi bi-x-circle"></i> Out of stock</span>
                <?php else: ?>
                    <span class="ui-tag ui-pill-green"><i class="bi bi-check-circle"></i> Available — <?= (int) $totalStock ?> units</span>
                <?php endif; ?>
            </div>

            <div style="overflow-x:auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Warehouse</th>
                            <th>Stock</th>
                            <th>Update</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($variant['warehouses'] as $w): $q = (int) $w['stock_quantity']; ?>
                            <tr>
                                <td>
                                    <span style="font-weight:600;"><?= htmlspecialchars($w['warehouse_name']) ?></span>
                                    <?php if ((int) $w['is_default'] === 1): ?>
                                        <span class="ui-pill ui-pill-green" style="margin-left:6px;">Default</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="ui-tag <?= $q <= 0 ? 'ui-pill-red' : ($q <= 5 ? 'ui-pill-amber' : 'ui-pill-green') ?>"><?= $q ?> units</span></td>
                                <td>
                                    <form method="POST" action="/warehouses/update-variant-stock" style="display:flex; gap:6px; align-items:center; margin:0;">
                                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                        <input type="hidden" name="variant_id" value="<?= $variantId ?>">
                                        <input type="hidden" name="warehouse_id" value="<?= $w['warehouse_id'] ?>">
                                        <input type="number" name="stock_quantity" value="<?= $q ?>" min="0" aria-label="New stock for <?= htmlspecialchars($w['warehouse_name']) ?>" style="<?= $inputStyle ?>">
                                        <button type="submit" class="ui-btn ui-btn-soft ui-btn-icon" title="Save" aria-label="Save stock"><i class="bi bi-check-lg"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endforeach; ?>

<?php endif; ?>