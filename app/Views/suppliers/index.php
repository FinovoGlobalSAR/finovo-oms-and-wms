<div class="ui-head">
    <div class="ui-head-left">
        <div class="breadcrumb">Finovo <i class="bi bi-chevron-right"></i> <span class="current">Suppliers</span></div>
        <h1>Suppliers <span class="count-badge"><?= (int) ($totalSuppliers ?? count($suppliers)) ?></span></h1>
        <p>Manage the suppliers you order stock from.</p>
    </div>
    <div class="ui-head-right">
        <button type="button" class="ui-btn ui-btn-primary" onclick="openModal('createSupplierModal')"><i class="bi bi-plus-lg"></i> Add Supplier</button>
    </div>
</div>

<?php if (!empty($created)): ?><div class="banner banner-success">Supplier added.</div><?php endif; ?>
<?php if (!empty($deleted)): ?><div class="banner banner-success">Supplier deleted.</div><?php endif; ?>
<?php if (!empty($error)): ?><div class="banner banner-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<div class="ui-card tbl-card">
    <!-- Search server pe (har page ka result sahi) -->
    <form method="GET" action="/suppliers">
        <div class="tbl-head">
            <h2>All Suppliers</h2>
            <div class="tbl-tools">
                <a href="/purchase-orders" class="tbl-btn"><i class="bi bi-clipboard-check"></i> Purchase Orders</a>
            </div>
        </div>
        <div class="tbl-search">
            <label class="ui-search">
                <i class="bi bi-search"></i>
                <input type="text" name="q" value="<?= htmlspecialchars($search ?? '') ?>" placeholder="Search name, contact, email or phone..." aria-label="Search suppliers">
            </label>
        </div>
    </form>

    <table class="data-table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Contact Person</th>
                <th>Phone</th>
                <th style="width:60px;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($suppliers)): ?>
                <tr><td colspan="4" class="ui-empty"><i class="bi bi-truck"></i><?= ($search ?? '') !== '' ? 'No suppliers match your search.' : 'No suppliers yet.' ?></td></tr>
            <?php else: ?>
                <?php foreach ($suppliers as $s): ?>
                    <tr>
                        <td><?= htmlspecialchars($s['name']) ?></td>
                        <td><?= htmlspecialchars($s['contact_person'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($s['phone'] ?? '-') ?></td>
                        <td>
                            <?php if (!empty($canDelete)): ?>
                                <!-- Supplier delete sirf Admin / Manager -->
                                <a href="#" class="action-link" style="color:var(--red);" aria-label="Delete supplier <?= htmlspecialchars($s['name']) ?>" onclick="if(confirm('Delete this supplier?')){document.getElementById('delSup<?= $s['id'] ?>').submit();} return false;"><i class="bi bi-trash"></i></a>
                                <form id="delSup<?= $s['id'] ?>" action="/suppliers/delete" method="POST" style="display:none;">
                                    <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                </form>
                            <?php else: ?>
                                <span style="color:#94a3b8;">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    <?php if (isset($pager)) { $pagerLabel = 'suppliers'; require __DIR__ . '/../partials/pagination.php'; } ?>
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

<style>
/* Email ka khana bhi baaki khanon jaisa (border, padding, font) */
#createSupplierModal .form-group input[type="email"] {
    width: 100%; border: 1px solid var(--border-input); border-radius: 8px; padding: 9px 12px;
    font-size: 13.5px; font-family: inherit; color: var(--text-dark); background: #ffffff; box-sizing: border-box;
}
</style>

<script>
function openModal(id) { document.getElementById(id).classList.add('show'); }
function closeModal(id) { document.getElementById(id).classList.remove('show'); }
</script>