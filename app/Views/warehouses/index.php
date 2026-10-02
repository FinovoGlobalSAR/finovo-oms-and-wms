<?php
$currentPage = 'warehouses';

// ---------- Summary numbers (sirf display ke liye, $warehouses se hi) ----------
$totalUnits = 0;
$notLinkedCount = 0;
$linkedCount = 0;
$totalProducts = 0;
foreach ($warehouses as $w) {
    $totalUnits += (int) $w['total_stock'];
    $totalProducts += (int) $w['product_count'];
    if (empty($w['stores'])) { $notLinkedCount++; } else { $linkedCount++; }
}
?>

<div class="ui-head" style="align-items:center;">
    <div class="ui-head-left">
        <div class="breadcrumb">Finovo <i class="bi bi-chevron-right"></i> <span class="current">Warehouses</span></div>
        <h1>Warehouses <span class="count-badge"><?= count($warehouses) ?></span></h1>
        <p>Manage warehouse locations. A warehouse can serve one or more stores.</p>
    </div>
    <div class="ui-head-right">
        <button type="button" class="ui-btn ui-btn-primary" onclick="openModal('createWarehouseModal')"><i class="bi bi-plus-lg"></i> Add Warehouse</button>
    </div>
</div>

<?php if (!empty($created)): ?>
    <div class="banner banner-success"><i class="bi bi-check-circle"></i> Warehouse created.</div>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <div class="banner banner-error"><i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="ui-stats">
    <div class="ui-stat">
        <div class="ui-stat-icon"><i class="bi bi-house-door"></i></div>
        <div>
            <div class="ui-stat-label">Total Warehouses</div>
            <div class="ui-stat-value"><b><?= count($warehouses) ?></b><span class="ui-pill ui-pill-blue">Locations</span></div>
        </div>
    </div>
    <div class="ui-stat">
        <div class="ui-stat-icon"><i class="bi bi-link-45deg"></i></div>
        <div>
            <div class="ui-stat-label">Linked to a Store</div>
            <div class="ui-stat-value"><b><?= $linkedCount ?></b>
                <span class="ui-pill <?= $notLinkedCount > 0 ? 'ui-pill-amber' : 'ui-pill-green' ?>"><?= $notLinkedCount > 0 ? $notLinkedCount . ' not linked' : 'All linked' ?></span>
            </div>
        </div>
    </div>
    <div class="ui-stat">
        <div class="ui-stat-icon"><i class="bi bi-boxes"></i></div>
        <div>
            <div class="ui-stat-label">Total Stock</div>
            <div class="ui-stat-value"><b><?= number_format($totalUnits) ?></b><span class="ui-pill ui-pill-blue">Units</span></div>
        </div>
    </div>
    <div class="ui-stat">
        <div class="ui-stat-icon"><i class="bi bi-box-seam"></i></div>
        <div>
            <div class="ui-stat-label">Products Stored</div>
            <div class="ui-stat-value"><b><?= number_format($totalProducts) ?></b><span class="ui-pill ui-pill-blue">Items</span></div>
        </div>
    </div>
</div>

<div class="ui-card">
    <div class="ui-card-head" style="padding-bottom:18px;">
        <div class="ui-card-title">
            <div class="ui-card-title-icon"><i class="bi bi-house-door"></i></div>
            <div>
                <h2>All Warehouses</h2>
                <div class="sub">Locations, linked stores and stock levels.</div>
            </div>
        </div>
        <label class="ui-search" style="width:260px;">
            <i class="bi bi-search"></i>
            <input type="text" id="whSearch" placeholder="Search warehouses..." aria-label="Search warehouses">
        </label>
    </div>

    <div style="overflow-x:auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Warehouse</th>
                    <th>Location</th>
                    <th>Linked Stores</th>
                    <th>Products</th>
                    <th>Total Stock</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($warehouses)): ?>
                    <tr><td colspan="6" class="ui-empty"><i class="bi bi-house-door"></i>No warehouses yet. Click "Add Warehouse" to create your first one.</td></tr>
                <?php else: ?>
                    <?php foreach ($warehouses as $w): ?>
                        <tr class="wh-row" data-search="<?= htmlspecialchars(strtolower($w['name'] . ' ' . ($w['location'] ?? ''))) ?>">
                            <td>
                                <a href="/warehouses/view?id=<?= $w['id'] ?>" style="display:flex; align-items:center; gap:10px; text-decoration:none; color:inherit;">
                                    <span style="width:30px; height:30px; border-radius:8px; background:var(--primary-light); color:var(--primary); display:flex; align-items:center; justify-content:center; flex-shrink:0;"><i class="bi bi-building"></i></span>
                                    <span style="font-weight:700; color:var(--text-dark);"><?= htmlspecialchars($w['name']) ?></span>
                                </a>
                            </td>
                            <td style="color:#334155;">
                                <?php if (!empty($w['location'])): ?>
                                    <span style="display:inline-flex; align-items:center; gap:6px;"><i class="bi bi-geo-alt" style="color:#94a3b8;"></i><?= htmlspecialchars($w['location']) ?></span>
                                <?php else: ?>
                                    <span style="color:#94a3b8;">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (empty($w['stores'])): ?>
                                    <span class="ui-tag ui-pill-red"><i class="bi bi-exclamation-circle"></i> Not linked</span>
                                <?php else: ?>
                                    <div style="display:flex; gap:6px; flex-wrap:wrap;">
                                        <?php foreach ($w['stores'] as $s): ?>
                                            <span class="ui-tag" style="background:var(--primary-light); color:var(--primary-dark); border:1px solid var(--primary-border); font-weight:500;" <?= (int) $s['is_default'] === 1 ? 'title="Default warehouse for this store"' : '' ?>>
                                                <?= htmlspecialchars($s['name']) ?>
                                                <?php if ((int) $s['is_default'] === 1): ?><i class="bi bi-star-fill" style="color:#b45309; font-size:10px;"></i><?php endif; ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td style="font-weight:600;"><?= (int) $w['product_count'] ?></td>
                            <td style="white-space:nowrap;"><b><?= number_format((int) $w['total_stock']) ?></b> <span style="color:#64748b;">units</span></td>
                            <td style="text-align:right;">
                                <a href="/warehouses/view?id=<?= $w['id'] ?>" class="ui-btn ui-btn-soft ui-btn-sm"><i class="bi bi-eye"></i> View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <tr id="whNoMatch" style="display:none;"><td colspan="6" class="ui-empty"><i class="bi bi-search"></i>No warehouses match your search.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="pagination-bar">
        <span id="whShowing">Showing <?= count($warehouses) ?> of <?= count($warehouses) ?> warehouses</span>
    </div>
</div>

<div class="modal-backdrop" id="createWarehouseModal">
    <div class="modal-box">
        <div class="modal-header">
            <h2>Add Warehouse</h2>
            <button type="button" class="modal-close" onclick="closeModal('createWarehouseModal')" aria-label="Close">&times;</button>
        </div>
        <form action="/warehouses/create" method="POST">
            <div class="form-group">
                <label>Warehouse Name</label>
                <input type="text" name="name" placeholder="e.g. Lahore Warehouse" required>
            </div>
            <div class="form-group">
                <label>Location</label>
                <input type="text" name="location" placeholder="e.g. Gulberg, Lahore">
            </div>
            <div class="form-group">
                <label>Link to store(s)</label>
                <?php if (empty($allStores)): ?>
                    <p class="modal-help" style="margin:0;">No stores yet — create one from the Stores page first.</p>
                <?php else: ?>
                    <div style="max-height:140px; overflow-y:auto; border:1px solid var(--border-color); border-radius:9px; padding:8px 12px; background:#f8fafc;">
                        <?php foreach ($allStores as $s): ?>
                            <label style="display:flex; align-items:center; gap:8px; font-size:13px; font-weight:400; padding:4px 0;">
                                <input type="checkbox" name="store_ids[]" value="<?= $s['id'] ?>" style="accent-color:var(--primary);">
                                <?= htmlspecialchars($s['name']) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <p class="modal-help" style="margin-top:6px;">First checked store becomes the default store for this warehouse.</p>
                <?php endif; ?>
            </div>
            <div class="form-actions">
                <button type="button" class="btn-secondary" onclick="closeModal('createWarehouseModal')">Cancel</button>
                <button type="submit" class="btn-primary">Create Warehouse</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(id) { document.getElementById(id).classList.add('show'); }
function closeModal(id) { document.getElementById(id).classList.remove('show'); }

(function () {
    var input = document.getElementById('whSearch');
    if (!input) { return; }
    var rows = Array.prototype.slice.call(document.querySelectorAll('.wh-row'));
    input.addEventListener('input', function () {
        var q = input.value.trim().toLowerCase();
        var shown = 0;
        rows.forEach(function (r) {
            var ok = q === '' || r.dataset.search.indexOf(q) !== -1;
            r.style.display = ok ? '' : 'none';
            if (ok) { shown++; }
        });
        var nm = document.getElementById('whNoMatch');
        if (nm) { nm.style.display = shown === 0 ? '' : 'none'; }
        document.getElementById('whShowing').textContent = 'Showing ' + shown + ' of ' + rows.length + ' warehouses';
    });
})();
</script>