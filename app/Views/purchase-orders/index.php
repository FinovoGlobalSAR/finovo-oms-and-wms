<?php
$statusColors = [
    'draft'    => ['bg' => '#f3f4f6', 'text' => '#374151'],
    'ordered'  => ['bg' => '#dbeafe', 'text' => '#1d4ed8'],
    'received' => ['bg' => '#dcfce7', 'text' => '#15803d'],
];
?>

<div class="breadcrumb">Finovo <i class="bi bi-chevron-right"></i> Purchase Orders</div>

<div class="page-header-row">
    <h1>Purchase Orders <span class="count-badge"><?= count($purchaseOrders) ?></span></h1>
</div>
<p class="page-subtitle">Order stock from suppliers and receive it into a warehouse.</p>

<?php if (!empty($created)): ?><div class="banner banner-success">Purchase order created.</div><?php endif; ?>
<?php if (!empty($updated)): ?><div class="banner banner-success">Purchase order updated.</div><?php endif; ?>
<?php if (!empty($error)): ?><div class="banner banner-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<div class="card" style="overflow: visible;">
    <div class="filter-row">
        <div class="push-right" style="margin-left:0;">
            <a href="/suppliers" class="toolbar-btn"><i class="bi bi-truck"></i> Suppliers</a>
            <button type="button" class="toolbar-btn btn-dark" onclick="openModal('createPoModal')" <?= empty($suppliers) ? 'disabled' : '' ?>>
                <i class="bi bi-plus-lg"></i> New Purchase Order
            </button>
        </div>
    </div>

    <?php if (empty($suppliers)): ?>
        <p style="padding:20px; color:var(--text-muted); font-size:13px;">No suppliers yet — <a href="/suppliers" class="link-blue">add a supplier</a> first.</p>
    <?php endif; ?>

    <table class="data-table">
        <thead>
            <tr>
                <th>PO</th>
                <th>Supplier</th>
                <th>Warehouse</th>
                <th>Items</th>
                <th>Status</th>
                <th style="width:180px;">Update Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($purchaseOrders)): ?>
                <tr><td colspan="6" style="text-align:center; color:#6b7280; padding:30px;">No purchase orders yet.</td></tr>
            <?php else: ?>
                <?php foreach ($purchaseOrders as $po):
                    $statusColor = $statusColors[$po['status']] ?? ['bg' => '#f3f4f6', 'text' => '#374151'];
                ?>
                    <tr>
                        <td>#<?= (int) $po['id'] ?></td>
                        <td><?= htmlspecialchars($po['supplier_name']) ?></td>
                        <td><?= htmlspecialchars($po['warehouse_name']) ?></td>
                        <td title="<?= htmlspecialchars(implode(', ', array_map(fn($i) => $i['product_name'] . ' x' . $i['quantity'], $po['items']))) ?>">
                            <?= count($po['items']) ?> product(s)
                        </td>
                        <td>
                            <span class="source-badge" style="background:<?= $statusColor['bg'] ?>; color:<?= $statusColor['text'] ?>;">
                                <?= ucfirst($po['status']) ?>
                            </span>
                        </td>
                        <td>
                            <form method="POST" action="/purchase-orders/update-status" style="display:flex; gap:6px;">
                                <input type="hidden" name="id" value="<?= $po['id'] ?>">
                                <select name="status" style="flex:1; border:1px solid var(--border-color); border-radius:6px; padding:5px 8px; font-size:12px; font-family:'Inter',sans-serif;">
                                    <?php foreach ($statuses as $st): ?>
                                        <option value="<?= $st ?>" <?= $po['status'] === $st ? 'selected' : '' ?>><?= ucfirst($st) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="filter-btn" style="padding:5px 10px;"><i class="bi bi-check-lg"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="modal-backdrop" id="createPoModal">
    <div class="modal-box">
        <div class="modal-header">
            <h2>New Purchase Order</h2>
            <button class="modal-close" onclick="closeModal('createPoModal')">&times;</button>
        </div>
        <form action="/purchase-orders/create" method="POST">
            <div class="form-group">
                <label>Supplier</label>
                <select name="supplier_id" required style="width:100%; border:1px solid var(--border-color); border-radius:8px; padding:8px 10px; font-size:14px; font-family:'Inter',sans-serif;">
                    <option value="">Select supplier...</option>
                    <?php foreach ($suppliers as $s): ?>
                        <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Receive Into Warehouse</label>
                <select name="warehouse_id" required style="width:100%; border:1px solid var(--border-color); border-radius:8px; padding:8px 10px; font-size:14px; font-family:'Inter',sans-serif;">
                    <option value="">Select warehouse...</option>
                    <?php foreach ($warehouses as $w): ?>
                        <option value="<?= $w['id'] ?>"><?= htmlspecialchars($w['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <label style="display:block; font-size:13px; font-weight:600; margin-bottom:8px;">Products</label>
            <div id="poItemsContainer" style="display:flex; flex-direction:column; gap:8px; margin-bottom:10px;"></div>
            <button type="button" class="toolbar-btn" onclick="addPoRow()" style="margin-bottom:16px;"><i class="bi bi-plus-lg"></i> Add product</button>

            <div class="form-group">
                <label>Notes (optional)</label>
                <input type="text" name="notes" placeholder="e.g. Urgent restock">
            </div>

            <div class="form-actions">
                <button type="button" class="btn-secondary" onclick="closeModal('createPoModal')">Cancel</button>
                <button type="submit" class="btn-primary">Create Purchase Order</button>
            </div>
        </form>
    </div>
</div>

<script>
const poProducts = <?= json_encode($products) ?>;

function openModal(id) { document.getElementById(id).classList.add('show'); }
function closeModal(id) { document.getElementById(id).classList.remove('show'); }

function poProductOptions() {
    let opts = '<option value="">Select product...</option>';
    poProducts.forEach(p => {
        opts += `<option value="${p.id}">${p.name}</option>`;
    });
    return opts;
}

function addPoRow() {
    const container = document.getElementById('poItemsContainer');
    const row = document.createElement('div');
    row.style.cssText = 'display:grid; grid-template-columns: 2fr 80px 100px 32px; gap:8px;';
    row.innerHTML = `
        <select name="product_id[]" required style="border:1px solid var(--border-color); border-radius:8px; padding:7px 8px; font-size:13px; font-family:'Inter',sans-serif;">${poProductOptions()}</select>
        <input type="number" name="quantity[]" min="1" placeholder="Qty" required style="border:1px solid var(--border-color); border-radius:8px; padding:7px 8px; font-size:13px;">
        <input type="number" name="unit_cost[]" min="0" step="0.01" placeholder="Cost" style="border:1px solid var(--border-color); border-radius:8px; padding:7px 8px; font-size:13px;">
        <button type="button" onclick="this.parentElement.remove()" style="border:1px solid var(--border-color); border-radius:8px; background:#fff; color:var(--red); cursor:pointer;"><i class="bi bi-trash"></i></button>
    `;
    container.appendChild(row);
}

addPoRow();
</script>