<?php
$stateColors = [
    'mapped'   => ['bg' => '#dcfce7', 'text' => '#166534'],
    'unmapped' => ['bg' => '#fef3c7', 'text' => '#92400e'],
    'conflict' => ['bg' => '#fee2e2', 'text' => '#991b1b'],
    'disabled' => ['bg' => '#f1f5f9', 'text' => '#334155'],
];

$allMappings = $allMappings ?? $mappings;
$search = $search ?? '';
$stateFilter = $stateFilter ?? '';

// ---------- Summary numbers: POORI list se (sirf is page se nahi) ----------
$stateCounts = ['mapped' => 0, 'unmapped' => 0, 'conflict' => 0, 'disabled' => 0];
foreach ($allMappings as $m) {
    if (isset($stateCounts[$m['mapping_state']])) { $stateCounts[$m['mapping_state']]++; }
}
?>

<div class="ui-head">
    <div class="ui-head-left">
        <div class="breadcrumb">Finovo <i class="bi bi-chevron-right"></i> Integration Settings <i class="bi bi-chevron-right"></i> <span class="current">SKU Mappings</span></div>
        <h1>SKU Mappings <span class="count-badge"><?= count($allMappings) ?></span></h1>
        <p>Map your internal products to external store product IDs, SKUs, and barcodes.</p>
    </div>
    <div class="ui-head-right">
        <button type="button" class="ui-btn ui-btn-primary" onclick="openModal('createMappingModal')"><i class="bi bi-plus-lg"></i> Add Mapping</button>
    </div>
</div>

<?php if (!empty($created)): ?><div class="banner banner-success"><i class="bi bi-check-circle"></i> Mapping created.</div><?php endif; ?>
<?php if (!empty($error)): ?><div class="banner banner-error"><i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>

<?php if ($stateCounts['conflict'] > 0): ?>
    <div class="ui-alert">
        <div class="ui-tip-icon"><i class="bi bi-exclamation-triangle"></i></div>
        <div class="ui-alert-body">
            <strong>Some mappings have conflicts</strong>
            <span><?= $stateCounts['conflict'] ?> mapping(s) are marked as conflict. Review them below so synced orders match the right product.</span>
        </div>
    </div>
<?php endif; ?>

<div class="ui-stats">
    <div class="ui-stat">
        <div class="ui-stat-icon"><i class="bi bi-link-45deg"></i></div>
        <div>
            <div class="ui-stat-label">Total Mappings</div>
            <div class="ui-stat-value"><b><?= count($allMappings) ?></b><span class="ui-pill ui-pill-blue">All</span></div>
        </div>
    </div>
    <div class="ui-stat">
        <div class="ui-stat-icon"><i class="bi bi-check2-circle"></i></div>
        <div>
            <div class="ui-stat-label">Mapped</div>
            <div class="ui-stat-value"><b><?= $stateCounts['mapped'] ?></b><span class="ui-pill ui-pill-green">Working</span></div>
        </div>
    </div>
    <div class="ui-stat">
        <div class="ui-stat-icon"><i class="bi bi-question-circle"></i></div>
        <div>
            <div class="ui-stat-label">Unmapped</div>
            <div class="ui-stat-value"><b><?= $stateCounts['unmapped'] ?></b>
                <span class="ui-pill <?= $stateCounts['unmapped'] > 0 ? 'ui-pill-amber' : 'ui-pill-green' ?>"><?= $stateCounts['unmapped'] > 0 ? 'Needs linking' : 'None' ?></span>
            </div>
        </div>
    </div>
    <div class="ui-stat">
        <div class="ui-stat-icon"><i class="bi bi-exclamation-octagon"></i></div>
        <div>
            <div class="ui-stat-label">Conflicts</div>
            <div class="ui-stat-value"><b><?= $stateCounts['conflict'] ?></b>
                <span class="ui-pill <?= $stateCounts['conflict'] > 0 ? 'ui-pill-red' : 'ui-pill-green' ?>"><?= $stateCounts['conflict'] > 0 ? 'Fix needed' : 'None' ?></span>
            </div>
        </div>
    </div>
</div>

<div class="ui-card tbl-card">
    <!-- Search / state filter server pe (har page ka result sahi) -->
    <form method="GET" action="/sku-mappings">
        <div class="tbl-head">
            <h2>All Mappings</h2>
            <div class="tbl-tools">
                <a href="/field-mappings" class="tbl-btn"><i class="bi bi-sliders2"></i> Field Mapping</a>
                <div class="ui-menu">
                    <button type="button" class="tbl-btn" data-menu-toggle><i class="bi bi-sliders"></i> Filters<?php if ($stateFilter !== ''): ?> <span class="tbl-count">1</span><?php endif; ?></button>
                    <div class="ui-menu-list tbl-filter-panel">
                        <label for="skuState">State</label>
                        <select name="state" id="skuState" onchange="this.form.submit()">
                            <?php foreach (['' => 'All states', 'mapped' => 'Mapped', 'unmapped' => 'Unmapped', 'conflict' => 'Conflict', 'disabled' => 'Disabled'] as $value => $label): ?>
                                <option value="<?= $value ?>" <?= $stateFilter === $value ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="tbl-filter-actions">
                            <a href="/sku-mappings" class="tbl-btn">Clear</a>
                            <button type="submit" class="tbl-btn tbl-btn-soft">Apply</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="tbl-search">
            <label class="ui-search">
                <i class="bi bi-search"></i>
                <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search product, external ID, SKU or barcode..." aria-label="Search mappings">
            </label>
        </div>
    </form>

    <div style="overflow-x:auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>External Product ID</th>
                    <th>State</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($mappings)): ?>
                    <?php if ($search !== '' || $stateFilter !== ''): ?>
                        <tr><td colspan="3" class="ui-empty"><i class="bi bi-search"></i>No mappings match your search.</td></tr>
                    <?php else: ?>
                        <tr><td colspan="3" class="ui-empty"><i class="bi bi-link-45deg"></i>No mappings yet. Click "Add Mapping" to link a product to its external store ID.</td></tr>
                    <?php endif; ?>
                <?php else: ?>
                    <?php foreach ($mappings as $m):
                        $color = $stateColors[$m['mapping_state']] ?? ['bg' => '#f1f5f9', 'text' => '#334155'];
                    ?>
                        <tr class="sku-row">
                            <td>
                                <div style="display:flex; align-items:center; gap:10px;">
                                    <span style="width:28px; height:28px; border-radius:8px; background:var(--primary-light); color:var(--primary); display:flex; align-items:center; justify-content:center; flex-shrink:0;"><i class="bi bi-box-seam"></i></span>
                                    <span style="font-weight:600;"><?= htmlspecialchars($m['product_name']) ?></span>
                                </div>
                            </td>
                            <td class="sku-code"><?= htmlspecialchars($m['external_product_id'] ?? '-') ?></td>
                            <td>
                                <span class="ui-tag" style="background:<?= $color['bg'] ?>; color:<?= $color['text'] ?>;">
                                    <?= ucfirst($m['mapping_state']) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php $pagerLabel = 'mappings'; require __DIR__ . '/../partials/pagination.php'; ?>
</div>

<div class="modal-backdrop" id="createMappingModal">
    <div class="modal-box">
        <div class="modal-header">
            <h2>Add SKU Mapping</h2>
            <button type="button" class="modal-close" onclick="closeModal('createMappingModal')" aria-label="Close">&times;</button>
        </div>
        <form action="/sku-mappings/create" method="POST">
            <div class="form-group">
                <label>Internal Product</label>
                <select name="product_id" required style="width:100%; border:1px solid var(--border-color); border-radius:9px; padding:9px 12px; font-size:13.5px; font-family:inherit; background:#f8fafc;">
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

<style>
td.sku-code { color: #334155; font-family: ui-monospace, 'Cascadia Code', Consolas, monospace; font-size: 12.5px; }
</style>

<script>
function openModal(id) { document.getElementById(id).classList.add('show'); }
function closeModal(id) { document.getElementById(id).classList.remove('show'); }
</script>