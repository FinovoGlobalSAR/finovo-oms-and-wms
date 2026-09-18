<?php
$stateColors = [
    'mapped'   => ['bg' => '#dcfce7', 'text' => '#15803d'],
    'unmapped' => ['bg' => '#fef3c7', 'text' => '#b45309'],
    'conflict' => ['bg' => '#fee2e2', 'text' => '#991b1b'],
    'disabled' => ['bg' => '#f3f4f6', 'text' => '#374151'],
];
?>

<div class="breadcrumb"><a href="/dashboard" class="breadcrumb-link">Finovo</a> <i class="bi bi-chevron-right"></i> SKU Mappings</div>

<div class="page-header-row">
    <h1>SKU Mappings <span class="count-badge"><?= count($mappings) ?></span></h1>
</div>
<p class="page-subtitle">Map your internal products to external store product IDs, SKUs, and barcodes.</p>

<?php if (!empty($created)): ?><div class="banner banner-success">Mapping created.</div><?php endif; ?>
<?php if (!empty($error)): ?><div class="banner banner-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<div class="card" style="overflow: visible;">
    <div class="filter-row">
        <div class="push-right" style="margin-left:0;">
            <button type="button" class="toolbar-btn btn-dark" onclick="openModal('createMappingModal')"><i class="bi bi-plus-lg"></i> Add Mapping</button>
        </div>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th>Product</th>
                <th>External Product ID</th>
                <th>External SKU</th>
                <th>Barcode</th>
                <th>State</th>
                <th>Mapped By</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($mappings)): ?>
                <tr><td colspan="6" style="text-align:center; color:#6b7280; padding:30px;">No mappings yet.</td></tr>
            <?php else: ?>
                <?php foreach ($mappings as $m):
                    $color = $stateColors[$m['mapping_state']] ?? ['bg' => '#f3f4f6', 'text' => '#374151'];
                ?>
                    <tr>
                        <td><?= htmlspecialchars($m['product_name']) ?></td>
                        <td><?= htmlspecialchars($m['external_product_id'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($m['external_sku'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($m['barcode'] ?? '-') ?></td>
                        <td>
                            <span class="source-badge" style="background:<?= $color['bg'] ?>; color:<?= $color['text'] ?>;">
                                <?= ucfirst($m['mapping_state']) ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($m['mapped_by'] ?? '-') ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="modal-backdrop" id="createMappingModal">
    <div class="modal-box">
        <div class="modal-header">
            <h2>Add SKU Mapping</h2>
            <button class="modal-close" onclick="closeModal('createMappingModal')">&times;</button>
        </div>
        <form action="/sku-mappings/create" method="POST">
            <div class="form-group">
                <label>Internal Product</label>
                <select name="product_id" required style="width:100%; border:1px solid var(--border-color); border-radius:8px; padding:8px 10px; font-size:14px; font-family:'Inter',sans-serif;">
                    <option value="">Select product...</option>
                    <?php foreach ($products as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>External Product ID</label>
                <input type="text" name="external_product_id" required placeholder="e.g. Shopify/WooCommerce product ID">
            </div>
            <div class="form-group">
                <label>External SKU (optional)</label>
                <input type="text" name="external_sku">
            </div>
            <div class="form-group">
                <label>Barcode (optional)</label>
                <input type="text" name="barcode">
            </div>
            <div class="form-actions">
                <button type="button" class="btn-secondary" onclick="closeModal('createMappingModal')">Cancel</button>
                <button type="submit" class="btn-primary">Create Mapping</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(id) { document.getElementById(id).classList.add('show'); }
function closeModal(id) { document.getElementById(id).classList.remove('show'); }
</script>