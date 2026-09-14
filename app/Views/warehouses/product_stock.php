<?php $currentPage = 'products'; ?>

<div class="breadcrumb">Finovo <i class="bi bi-chevron-right"></i> Products <i class="bi bi-chevron-right"></i> Warehouse stock</div>

<div class="page-header-row">
    <h1><?= htmlspecialchars($product['name']) ?></h1>
</div>
<p class="page-subtitle">
    <?= $hasVariants ? 'Stock for this product\'s variants, broken down by warehouse.' : 'Stock for this product, broken down by warehouse.' ?>
</p>

<?php if (!empty($updated)): ?>
    <div class="banner banner-success">Stock updated.</div>
<?php endif; ?>

<?php if (!$hasVariants): ?>

    <div class="card">
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
                <?php foreach ($stockList as $row): ?>
                    <tr>
                        <td>
                            <?= htmlspecialchars($row['warehouse_name']) ?>
                            <?php if ((int) $row['is_default'] === 1): ?>
                                <span class="source-badge" style="background:#dcfce7; color:#15803d; margin-left:6px;">Default</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($row['location'] ?? '-') ?></td>
                        <td><?= (int) $row['stock_quantity'] ?> units</td>
                        <td>
                            <form method="POST" action="/warehouses/update-stock" style="display:flex; gap:6px; align-items:center;">
                                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                <input type="hidden" name="warehouse_id" value="<?= $row['warehouse_id'] ?>">
                                <input type="number" name="stock_quantity" value="<?= (int) $row['stock_quantity'] ?>" min="0" style="width:80px; border:1px solid var(--border-color); border-radius:6px; padding:5px 8px; font-size:13px; font-family:'Inter',sans-serif;">
                                <button type="submit" class="filter-btn"><i class="bi bi-check-lg"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

<?php else: ?>

    <?php
    $grouped = [];
    foreach ($variantMatrix as $row) {
        $grouped[$row['variant_id']]['label'] = $row['label'];
        $grouped[$row['variant_id']]['sku'] = $row['sku'];
        $grouped[$row['variant_id']]['warehouses'][] = $row;
    }
    ?>

    <?php foreach ($grouped as $variantId => $variant):
        $totalStock = array_sum(array_column($variant['warehouses'], 'stock_quantity'));
        $isOut = $totalStock <= 0;
    ?>
        <div class="card" style="margin-bottom: 16px;">
            <div style="padding: 14px 20px; border-bottom: 1px solid var(--border-color); display:flex; align-items:center; justify-content:space-between;">
                <div style="font-size:14px; font-weight:600; color:var(--text-dark);">
                    <?= htmlspecialchars($variant['label']) ?>
                    <?php if (!empty($variant['sku'])): ?>
                        <span style="color:var(--text-muted); font-weight:400; font-size:12px;">(<?= htmlspecialchars($variant['sku']) ?>)</span>
                    <?php endif; ?>
                </div>
                <?php if ($isOut): ?>
                    <span class="source-badge" style="background:#fee2e2; color:#991b1b;"><?= htmlspecialchars($variant['label']) ?> is out of stock</span>
                <?php else: ?>
                    <span class="source-badge" style="background:#dcfce7; color:#15803d;">Available — <?= $totalStock ?> units</span>
                <?php endif; ?>
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>Warehouse</th>
                        <th>Stock</th>
                        <th>Update</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($variant['warehouses'] as $w): ?>
                        <tr>
                            <td>
                                <?= htmlspecialchars($w['warehouse_name']) ?>
                                <?php if ((int) $w['is_default'] === 1): ?>
                                    <span class="source-badge" style="background:#dcfce7; color:#15803d; margin-left:6px;">Default</span>
                                <?php endif; ?>
                            </td>
                            <td><?= (int) $w['stock_quantity'] ?> units</td>
                            <td>
                                <form method="POST" action="/warehouses/update-variant-stock" style="display:flex; gap:6px; align-items:center;">
                                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                    <input type="hidden" name="variant_id" value="<?= $variantId ?>">
                                    <input type="hidden" name="warehouse_id" value="<?= $w['warehouse_id'] ?>">
                                    <input type="number" name="stock_quantity" value="<?= (int) $w['stock_quantity'] ?>" min="0" style="width:80px; border:1px solid var(--border-color); border-radius:6px; padding:5px 8px; font-size:13px; font-family:'Inter',sans-serif;">
                                    <button type="submit" class="filter-btn"><i class="bi bi-check-lg"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endforeach; ?>

<?php endif; ?>

<a href="/products" style="display: block; margin-top: 16px; font-size: 13px; color: var(--text-muted);">&larr; Back to Products</a>