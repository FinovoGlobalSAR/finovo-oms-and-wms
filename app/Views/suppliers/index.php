<div class="breadcrumb">Finovo <i class="bi bi-chevron-right"></i> Suppliers</div>

<div class="page-header-row">
    <h1>Suppliers <span class="count-badge"><?= count($suppliers) ?></span></h1>
</div>
<p class="page-subtitle">Manage the suppliers you order stock from.</p>

<?php if (!empty($created)): ?><div class="banner banner-success">Supplier added.</div><?php endif; ?>
<?php if (!empty($error)): ?><div class="banner banner-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<div class="card">
    <div class="filter-row">
        <div class="push-right" style="margin-left:0;">
            <a href="/purchase-orders" class="toolbar-btn"><i class="bi bi-clipboard-check"></i> Purchase Orders</a>
            <button type="button" class="toolbar-btn btn-dark" onclick="openModal('createSupplierModal')"><i class="bi bi-plus-lg"></i> Add supplier</button>
        </div>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Contact Person</th>
                <th>Email</th>
                <th>Phone</th>
                <th style="width:60px;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($suppliers)): ?>
                <tr><td colspan="5" style="text-align:center; color:#6b7280; padding:30px;">No suppliers yet.</td></tr>
            <?php else: ?>
                <?php foreach ($suppliers as $s): ?>
                    <tr>
                        <td><?= htmlspecialchars($s['name']) ?></td>
                        <td><?= htmlspecialchars($s['contact_person'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($s['email'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($s['phone'] ?? '-') ?></td>
                        <td>
                            <a href="#" class="action-link" style="color:var(--red);" onclick="if(confirm('Delete this supplier?')){document.getElementById('delSup<?= $s['id'] ?>').submit();} return false;"><i class="bi bi-trash"></i></a>
                            <form id="delSup<?= $s['id'] ?>" action="/suppliers/delete" method="POST" style="display:none;">
                                <input type="hidden" name="id" value="<?= $s['id'] ?>">
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="modal-backdrop" id="createSupplierModal">
    <div class="modal-box">
        <div class="modal-header">
            <h2>Add Supplier</h2>
            <button class="modal-close" onclick="closeModal('createSupplierModal')">&times;</button>
        </div>
        <form action="/suppliers/create" method="POST">
            <div class="form-group">
                <label>Supplier Name</label>
                <input type="text" name="name" required placeholder="e.g. ABC Traders">
            </div>
            <div class="form-group">
                <label>Contact Person</label>
                <input type="text" name="contact_person" placeholder="e.g. Ahmed Khan">
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" placeholder="supplier@example.com">
            </div>
            <div class="form-group">
                <label>Phone</label>
                <input type="text" name="phone" placeholder="03xx-xxxxxxx">
            </div>
            <div class="form-actions">
                <button type="button" class="btn-secondary" onclick="closeModal('createSupplierModal')">Cancel</button>
                <button type="submit" class="btn-primary">Add Supplier</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(id) { document.getElementById(id).classList.add('show'); }
function closeModal(id) { document.getElementById(id).classList.remove('show'); }
</script>