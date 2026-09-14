<?php $currentPage = 'stores'; ?>

<div class="breadcrumb">Finovo <i class="bi bi-chevron-right"></i> Stores</div>

<div class="page-header-row">
    <h1>Stores <span class="count-badge"><?= count($stores) ?></span></h1>
</div>

<?php if (!empty($created)): ?><div class="banner banner-success">Store created.</div><?php endif; ?>
<?php if (!empty($updated)): ?><div class="banner banner-success">Store updated.</div><?php endif; ?>
<?php if (!empty($deleted)): ?><div class="banner banner-success">Store deleted.</div><?php endif; ?>
<?php if (!empty($error)): ?><div class="banner banner-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<div class="card">
    <div class="filter-row">
        <div class="push-right" style="margin-left:0;">
            <button type="button" class="toolbar-btn btn-dark" onclick="openModal('createStoreModal')"><i class="bi bi-plus-lg"></i> Add store</button>
        </div>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th>Store</th>
                <th>Platform</th>
                <th>Linked warehouses</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($stores)): ?>
                <tr><td colspan="4" style="text-align:center; color:#6b7280; padding:30px;">No stores yet.</td></tr>
            <?php else: ?>
                <?php foreach ($stores as $s): ?>
                    <tr>
                        <td>
                            <div class="name-cell">
                                <span class="avatar-circle" style="background:#dbeafe; color:#1d4ed8;"><i class="bi bi-shop"></i></span>
                                <?= htmlspecialchars($s['name']) ?>
                                <?php if ((int) $s['id'] === (int) $currentStoreId): ?>
                                    <span class="source-badge" style="background:#dcfce7; color:#15803d; margin-left:6px;">Currently Viewing</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td><span class="source-badge" style="background:#f3f4f6; color:#374151;"><?= htmlspecialchars(ucfirst($s['platform'])) ?></span></td>
                        <td>
                            <?php if (empty($s['warehouses'])): ?>
                                <span class="source-badge" style="background:#fee2e2; color:#991b1b;">No warehouse linked</span>
                            <?php else: ?>
                                <?php foreach ($s['warehouses'] as $w): ?>
                                    <span class="source-badge" style="background:#eef2ff; color:#4338ca; margin-right:4px;">
                                        <?= htmlspecialchars($w['name']) ?><?= (int) $w['is_default'] === 1 ? ' ★' : '' ?>
                                    </span>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ((int) $s['id'] !== (int) $currentStoreId): ?>
                                <a href="/stores/switch?store_id=<?= $s['id'] ?>&redirect=/stores" class="action-link"><i class="bi bi-arrow-left-right"></i> Switch</a>
                            <?php endif; ?>
                            <a href="#" class="action-link" onclick="openEditModal(<?= htmlspecialchars(json_encode($s), ENT_QUOTES) ?>); return false;"><i class="bi bi-pencil"></i> Edit</a>
                            <a href="#" class="action-link" style="color:var(--red);" onclick="if(confirm('Delete store &quot;<?= htmlspecialchars($s['name'], ENT_QUOTES) ?>&quot;?')){document.getElementById('deleteStoreForm<?= $s['id'] ?>').submit();} return false;"><i class="bi bi-trash"></i> Delete</a>
                            <form id="deleteStoreForm<?= $s['id'] ?>" action="/stores/delete" method="POST" style="display:none;">
                                <input type="hidden" name="id" value="<?= $s['id'] ?>">
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="modal-backdrop" id="createStoreModal">
    <div class="modal-box">
        <div class="modal-header">
            <h2>Add Store</h2>
            <button class="modal-close" onclick="closeModal('createStoreModal')">&times;</button>
        </div>
        <form action="/stores/create" method="POST">
            <div class="form-group">
                <label>Store Name</label>
                <input type="text" name="name" placeholder="e.g. Finovo Karachi Store" required>
            </div>
            <div class="form-group">
                <label>Platform</label>
                <select name="platform" style="width:100%; border:1px solid var(--border-color); border-radius:8px; padding:8px 10px; font-size:14px; font-family:'Inter',sans-serif;">
                    <option value="manual">Manual</option>
                    <option value="shopify">Shopify</option>
                    <option value="woocommerce">WooCommerce</option>
                </select>
            </div>
            <div class="form-group">
                <label>Store URL (optional, Shopify)</label>
                <input type="text" name="store_url" placeholder="yourstore.myshopify.com">
            </div>
            <div class="form-group">
                <label>WooCommerce Store URL (optional)</label>
                <input type="text" name="woocommerce_store_url" placeholder="http://localhost/wordpress">
            </div>
            <div class="form-group">
                <label>WooCommerce Consumer Key (optional)</label>
                <input type="text" name="woocommerce_consumer_key" placeholder="ck_...">
            </div>
            <div class="form-group">
                <label>WooCommerce Consumer Secret (optional)</label>
                <input type="text" name="woocommerce_consumer_secret" placeholder="cs_...">
            </div>
            <div class="form-group">
                <label>Link to warehouse(s)</label>
                <?php if (empty($allWarehouses)): ?>
                    <p class="modal-help" style="margin:0;">No warehouses yet — create one from the Warehouses page first, then link it here.</p>
                <?php else: ?>
                    <div style="max-height:140px; overflow-y:auto; border:1px solid var(--border-color); border-radius:8px; padding:8px 10px;">
                        <?php foreach ($allWarehouses as $w): ?>
                            <label style="display:flex; align-items:center; gap:8px; font-size:13px; font-weight:400; padding:4px 0;">
                                <input type="checkbox" name="warehouse_ids[]" value="<?= $w['id'] ?>">
                                <?= htmlspecialchars($w['name']) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <p class="modal-help" style="margin-top:6px;">First checked warehouse becomes this store's default.</p>
                <?php endif; ?>
            </div>
            <div class="form-actions">
                <button type="button" class="btn-secondary" onclick="closeModal('createStoreModal')">Cancel</button>
                <button type="submit" class="btn-primary">Create</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-backdrop" id="editStoreModal">
    <div class="modal-box">
        <div class="modal-header">
            <h2>Edit Store</h2>
            <button class="modal-close" onclick="closeModal('editStoreModal')">&times;</button>
        </div>
        <form action="/stores/update" method="POST" id="editStoreForm">
            <input type="hidden" name="id" id="editStoreId">
            <div class="form-group">
                <label>Store Name</label>
                <input type="text" name="name" id="editStoreName" required>
            </div>
            <div class="form-group">
                <label>Platform</label>
                <select name="platform" id="editStorePlatform" style="width:100%; border:1px solid var(--border-color); border-radius:8px; padding:8px 10px; font-size:14px; font-family:'Inter',sans-serif;">
                    <option value="manual">Manual</option>
                    <option value="shopify">Shopify</option>
                    <option value="woocommerce">WooCommerce</option>
                </select>
            </div>
            <div class="form-group">
                <label>Store URL</label>
                <input type="text" name="store_url" id="editStoreUrl">
            </div>
            <div class="form-group">
                <label>WooCommerce Store URL</label>
                <input type="text" name="woocommerce_store_url" id="editStoreWcUrl">
            </div>
            <div class="form-group">
                <label>WooCommerce Consumer Key</label>
                <input type="text" name="woocommerce_consumer_key" id="editStoreWcKey">
            </div>
            <div class="form-group">
                <label>WooCommerce Consumer Secret</label>
                <input type="text" name="woocommerce_consumer_secret" id="editStoreWcSecret">
            </div>
            <div class="form-group">
                <label>Linked warehouse(s)</label>
                <?php if (!empty($allWarehouses)): ?>
                    <div id="editWarehouseCheckboxes" style="max-height:140px; overflow-y:auto; border:1px solid var(--border-color); border-radius:8px; padding:8px 10px;">
                        <?php foreach ($allWarehouses as $w): ?>
                            <label style="display:flex; align-items:center; gap:8px; font-size:13px; font-weight:400; padding:4px 0;">
                                <input type="checkbox" name="warehouse_ids[]" value="<?= $w['id'] ?>" class="edit-warehouse-checkbox" data-warehouse-id="<?= $w['id'] ?>">
                                <?= htmlspecialchars($w['name']) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="form-actions">
                <button type="button" class="btn-secondary" onclick="closeModal('editStoreModal')">Cancel</button>
                <button type="submit" class="btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(id) { document.getElementById(id).classList.add('show'); }
function closeModal(id) { document.getElementById(id).classList.remove('show'); }

function openEditModal(store) {
    document.getElementById('editStoreId').value = store.id;
    document.getElementById('editStoreName').value = store.name;
    document.getElementById('editStorePlatform').value = store.platform;
    document.getElementById('editStoreUrl').value = store.store_url || '';
    document.getElementById('editStoreWcUrl').value = store.woocommerce_store_url || '';
    document.getElementById('editStoreWcKey').value = store.woocommerce_consumer_key || '';
    document.getElementById('editStoreWcSecret').value = store.woocommerce_consumer_secret || '';

    var linkedIds = (store.warehouses || []).map(function (w) { return String(w.id); });
    document.querySelectorAll('.edit-warehouse-checkbox').forEach(function (cb) {
        cb.checked = linkedIds.indexOf(cb.dataset.warehouseId) !== -1;
    });

    openModal('editStoreModal');
}
</script>