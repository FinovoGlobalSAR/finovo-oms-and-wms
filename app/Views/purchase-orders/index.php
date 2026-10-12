<?php
// Upar ke numbers POORI list se ($allPurchaseOrders); table mein sirf latest 5 rows ($purchaseOrders)
$allPurchaseOrders = $allPurchaseOrders ?? $purchaseOrders;
$search = $search ?? '';
$statusFilter = $statusFilter ?? '';
$statusColors = [
    'draft'    => ['bg' => '#f1f5f9', 'text' => '#334155'],
    'ordered'  => ['bg' => '#dbeafe', 'text' => '#1d4ed8'],
    'received' => ['bg' => '#dcfce7', 'text' => '#166534'],
];

// ---------- Summary numbers (POORI list se — $allPurchaseOrders) ----------
$poCounts = ['draft' => 0, 'ordered' => 0, 'received' => 0];
foreach ($allPurchaseOrders as $po) {
    if (isset($poCounts[$po['status']])) { $poCounts[$po['status']]++; }
}
?>

<div class="ui-head">
    <div class="ui-head-left">
        <div class="breadcrumb">Finovo <i class="bi bi-chevron-right"></i> <span class="current">Purchase Orders</span></div>
        <h1>Purchase Orders <span class="count-badge"><?= count($allPurchaseOrders) ?></span></h1>
        <p>Order stock from suppliers and receive it into a warehouse.</p>
    </div>
    <div class="ui-head-right">
        <button type="button" class="ui-btn ui-btn-primary" onclick="openModal('createPoModal')" <?= empty($suppliers) ? 'disabled title="Add a supplier first"' : '' ?>><i class="bi bi-plus-lg"></i> New Purchase Order</button>
    </div>
</div>

<?php if (!empty($created)): ?><div class="banner banner-success"><i class="bi bi-check-circle"></i> Purchase order created.</div><?php endif; ?>
<?php if (!empty($updated)): ?><div class="banner banner-success"><i class="bi bi-check-circle"></i> Purchase order updated.</div><?php endif; ?>
<?php if (!empty($error)): ?><div class="banner banner-error"><i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>

<?php if (empty($suppliers)): ?>
    <div class="ui-alert">
        <div class="ui-tip-icon"><i class="bi bi-lightbulb"></i></div>
        <div class="ui-alert-body">
            <strong>No suppliers yet</strong>
            <span>Add a supplier first, then you can create purchase orders.</span>
        </div>
        <a href="/suppliers" class="ui-btn ui-btn-sm" style="border-color:#fde68a; background:#fff;">Add Supplier <i class="bi bi-arrow-right"></i></a>
    </div>
<?php endif; ?>

<div class="ui-stats">
    <div class="ui-stat">
        <div class="ui-stat-icon"><i class="bi bi-clipboard-check"></i></div>
        <div>
            <div class="ui-stat-label">Total POs</div>
            <div class="ui-stat-value"><b><?= count($allPurchaseOrders) ?></b><span class="ui-pill ui-pill-blue">All</span></div>
        </div>
    </div>
    <div class="ui-stat">
        <div class="ui-stat-icon"><i class="bi bi-pencil-square"></i></div>
        <div>
            <div class="ui-stat-label">Draft</div>
            <div class="ui-stat-value"><b><?= $poCounts['draft'] ?></b><span class="ui-pill ui-pill-gray">Not sent</span></div>
        </div>
    </div>
    <div class="ui-stat">
        <div class="ui-stat-icon"><i class="bi bi-send"></i></div>
        <div>
            <div class="ui-stat-label">Ordered</div>
            <div class="ui-stat-value"><b><?= $poCounts['ordered'] ?></b><span class="ui-pill ui-pill-amber">Awaiting stock</span></div>
        </div>
    </div>
    <div class="ui-stat">
        <div class="ui-stat-icon"><i class="bi bi-box-arrow-in-down"></i></div>
        <div>
            <div class="ui-stat-label">Received</div>
            <div class="ui-stat-value"><b><?= $poCounts['received'] ?></b><span class="ui-pill ui-pill-green">In warehouse</span></div>
        </div>
    </div>
</div>

<div class="ui-card tbl-card">
    <!-- Search / status filter: server pe chalte hain, har page ka result sahi -->
    <form method="GET" action="/purchase-orders">
        <div class="tbl-head">
            <h2>All Purchase Orders</h2>
            <div class="tbl-tools">
                <a href="/suppliers" class="tbl-btn"><i class="bi bi-truck"></i> Suppliers</a>
                <div class="ui-menu">
                    <button type="button" class="tbl-btn" data-menu-toggle><i class="bi bi-sliders"></i> Filters<?php if ($statusFilter !== ''): ?> <span class="tbl-count">1</span><?php endif; ?></button>
                    <div class="ui-menu-list tbl-filter-panel">
                        <label for="poStatus">Status</label>
                        <select name="status" id="poStatus" onchange="this.form.submit()">
                            <option value="">All statuses</option>
                            <?php foreach ($statuses as $st): ?>
                                <option value="<?= $st ?>" <?= $statusFilter === $st ? 'selected' : '' ?>><?= ucfirst($st) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="tbl-filter-actions">
                            <a href="/purchase-orders" class="tbl-btn">Clear</a>
                            <button type="submit" class="tbl-btn tbl-btn-soft">Apply</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="tbl-search">
            <label class="ui-search">
                <i class="bi bi-search"></i>
                <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search supplier, warehouse or product..." aria-label="Search purchase orders">
            </label>
        </div>
    </form>

    <div style="overflow-x:auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>PO</th>
                    <th>Supplier</th>
                    <th>Status</th>
                    <th style="width:210px;">Update Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($purchaseOrders)): ?>
                    <tr><td colspan="4" class="ui-empty"><i class="bi bi-clipboard"></i>No purchase orders yet. Click "New Purchase Order" to order stock from a supplier.</td></tr>
                <?php else: ?>
                    <?php foreach ($purchaseOrders as $po):
                        $statusColor = $statusColors[$po['status']] ?? ['bg' => '#f1f5f9', 'text' => '#334155'];
                    ?>
                        <tr class="po-row">
                            <td style="font-weight:700;">#<?= (int) $po['id'] ?></td>
                            <td>
                                <div style="display:flex; align-items:center; gap:10px;">
                                    <span style="width:28px; height:28px; border-radius:8px; background:var(--primary-light); color:var(--primary); display:flex; align-items:center; justify-content:center; flex-shrink:0;"><i class="bi bi-truck"></i></span>
                                    <span style="font-weight:600;"><?= htmlspecialchars($po['supplier_name']) ?></span>
                                </div>
                            </td>
                            <td>
                                <span class="ui-tag" style="background:<?= $statusColor['bg'] ?>; color:<?= $statusColor['text'] ?>;">
                                    <?= ucfirst($po['status']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($po['status'] === 'received'): ?>
                                    <!-- Received aakhri hai — maal stock mein aa chuka, status ab nahi badalta -->
                                    <span style="color:var(--text-muted); font-size:12.5px;"><i class="bi bi-lock"></i> Stock added</span>
                                <?php else: ?>
                                    <form method="POST" action="/purchase-orders/update-status" style="display:flex; gap:6px; margin:0;">
                                        <input type="hidden" name="id" value="<?= $po['id'] ?>">
                                        <select name="status" class="ui-select" style="flex:1;" aria-label="New status for PO #<?= (int) $po['id'] ?>">
                                            <?php foreach ($statuses as $st): ?>
                                                <?php if (PurchaseOrder::canMove($po['status'], $st)): ?>
                                                    <option value="<?= $st ?>" <?= $po['status'] === $st ? 'selected' : '' ?>><?= ucfirst($st) ?></option>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" class="ui-btn ui-btn-soft ui-btn-icon" title="Save status" aria-label="Save status" onclick="return this.form.status.value !== 'received' || confirm('Mark as received? The stock will be added to the warehouse and this cannot be undone.');"><i class="bi bi-check-lg"></i></button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if (!empty($pager)) { $pagerLabel = 'purchase orders'; require __DIR__ . '/../partials/pagination.php'; } ?>
</div>

<div class="modal-backdrop" id="createPoModal">
    <div class="modal-box" style="max-width:560px;">
        <div class="modal-header">
            <h2>New Purchase Order</h2>
            <button type="button" class="modal-close" onclick="closeModal('createPoModal')" aria-label="Close">&times;</button>
        </div>
        <form action="/purchase-orders/create" method="POST">
            <div class="form-group">
                <label>Supplier</label>
                <select name="supplier_id" required class="po-modal-select">
                    <option value="">Select supplier...</option>
                    <?php foreach ($suppliers as $s): ?>
                        <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Receive Into Warehouse</label>
                <select name="warehouse_id" required class="po-modal-select">
                    <option value="">Select warehouse...</option>
                    <?php foreach ($warehouses as $w): ?>
                        <option value="<?= $w['id'] ?>"><?= htmlspecialchars($w['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                <span style="font-size:12.5px; font-weight:600; color:var(--text-body);">Products</span>
                <span style="font-size:12px; color:var(--text-muted);">Product · Qty · Unit cost</span>
            </div>
            <div id="poItemsContainer" style="display:flex; flex-direction:column; gap:8px; margin-bottom:10px;"></div>
            <button type="button" class="ui-btn ui-btn-soft ui-btn-sm" onclick="addPoRow()" style="margin-bottom:16px;"><i class="bi bi-plus-lg"></i> Add product</button>

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

<style>
.po-modal-select { width: 100%; border: 1px solid var(--border-color); border-radius: 9px; padding: 9px 12px; font-size: 13.5px; font-family: inherit; background: #f8fafc; }
.po-item-row { display: grid; grid-template-columns: 2fr 80px 100px 36px; gap: 8px; padding: 8px; border: 1px solid var(--border-color); border-radius: 10px; background: #f8fafc; }
.po-item-row select, .po-item-row input { height: 36px; border: 1px solid var(--border-color); border-radius: 8px; padding: 0 8px; font-size: 13px; font-family: inherit; background: #fff; min-width: 0; }
.po-item-row select:focus, .po-item-row input:focus { outline: none; border-color: #93b4f5; box-shadow: 0 0 0 3px rgba(29,78,216,0.12); }
.po-item-row button { height: 36px; border: 1px solid #fecaca; border-radius: 8px; background: #fff; color: var(--red); cursor: pointer; }
.po-item-row button:hover { background: #fee2e2; }
</style>

<script>
const poProducts = <?= json_encode($products, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

// Product ke naam platform / CSV se aate hain — HTML mein daalne se pehle escape (warna naam mein chhupa code chal jata)
function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, ch => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[ch]));
}

function openModal(id) { document.getElementById(id).classList.add('show'); }
function closeModal(id) { document.getElementById(id).classList.remove('show'); }

function poProductOptions() {
    let opts = '<option value="">Select product...</option>';
    poProducts.forEach(p => {
        opts += `<option value="${escapeHtml(p.id)}">${escapeHtml(p.name)}</option>`;
    });
    return opts;
}

function addPoRow() {
    const container = document.getElementById('poItemsContainer');
    const row = document.createElement('div');
    row.className = 'po-item-row';
    row.innerHTML = `
        <select name="product_id[]" required aria-label="Product">${poProductOptions()}</select>
        <input type="number" name="quantity[]" min="1" placeholder="Qty" required aria-label="Quantity">
        <input type="number" name="unit_cost[]" min="0" step="0.01" placeholder="Cost" aria-label="Unit cost">
        <button type="button" onclick="this.parentElement.remove()" aria-label="Remove product"><i class="bi bi-trash"></i></button>
    `;
    container.appendChild(row);
}

addPoRow();

</script>