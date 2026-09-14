<?php $currentPage = 'warehouses'; ?>

<div class="breadcrumb">Finovo <i class="bi bi-chevron-right"></i> Warehouses</div>

<div class="page-header-row">
    <h1>Warehouses <span class="count-badge"><?= count($warehouses) ?></span></h1>
</div>
<p class="page-subtitle">Manage warehouse locations. A warehouse can serve one or more stores.</p>

<?php if (!empty($created)): ?>
    <div class="banner banner-success">Warehouse created.</div>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <div class="banner banner-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="card">
    <div class="filter-row">
        <div class="push-right" style="margin-left:0;">
            <button type="button" class="toolbar-btn btn-dark" onclick="openModal('createWarehouseModal')"><i class="bi bi-plus-lg"></i> Add warehouse</button>
        </div>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th>Warehouse</th>
                <th>Location</th>
                <th>Linked stores</th>
                <th>Products</th>
                <th>Total stock</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($warehouses)): ?>
                <tr><td colspan="5" style="text-align:center; color:#6b7280; padding:30px;">No warehouses yet.</td></tr>
            <?php else: ?>
                <?php foreach ($warehouses as $w): ?>
                    <tr>
                        <td>
                            <a href="/warehouses/view?id=<?= $w['id'] ?>" class="name-cell" style="text-decoration:none; color:inherit;">
                                <span class="avatar-circle" style="background:#e0e7ff; color:#4338ca;"><i class="bi bi-building"></i></span>
                                <span class="link-blue"><?= htmlspecialchars($w['name']) ?></span>
                            </a>
                        </td>
                        <td><?= htmlspecialchars($w['location'] ?? '-') ?></td>
                        <td>
                            <?php if (empty($w['stores'])): ?>
                                <span class="source-badge" style="background:#fee2e2; color:#991b1b;">Not linked</span>
                            <?php else: ?>
                                <?php foreach ($w['stores'] as $s): ?>
                                    <span class="source-badge" style="background:#eef2ff; color:#4338ca; margin-right:4px;"><?= htmlspecialchars($s['name']) ?><?= (int) $s['is_default'] === 1 ? ' ★' : '' ?></span>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </td>
                        <td><?= (int) $w['product_count'] ?></td>
                        <td><?= (int) $w['total_stock'] ?> units</td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<p style="margin-top: 16px; font-size: 13px; color: var(--text-muted);">
    <i class="bi bi-info-circle"></i> ★ = default warehouse for that store. Click a warehouse to see its products and receive new stock.
</p>

<div class="modal-backdrop" id="createWarehouseModal">
    <div class="modal-box">
        <div class="modal-header">
            <h2>Add Warehouse</h2>
            <button class="modal-close" onclick="closeModal('createWarehouseModal')">&times;</button>
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
                    <div style="max-height:140px; overflow-y:auto; border:1px solid var(--border-color); border-radius:8px; padding:8px 10px;">
                        <?php foreach ($allStores as $s): ?>
                            <label style="display:flex; align-items:center; gap:8px; font-size:13px; font-weight:400; padding:4px 0;">
                                <input type="checkbox" name="store_ids[]" value="<?= $s['id'] ?>">
                                <?= htmlspecialchars($s['name']) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <p class="modal-help" style="margin-top:6px;">First checked store becomes the default store for this warehouse.</p>
                <?php endif; ?>
            </div>
            <div class="form-actions">
                <button type="button" class="btn-secondary" onclick="closeModal('createWarehouseModal')">Cancel</button>
                <button type="submit" class="btn-primary">Create</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(id) { document.getElementById(id).classList.add('show'); }
function closeModal(id) { document.getElementById(id).classList.remove('show'); }
</script>