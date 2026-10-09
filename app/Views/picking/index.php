<?php
$pickColors = [
    'not_started' => ['bg' => '#f1f5f9', 'text' => '#334155'],
    'picking'     => ['bg' => '#fef3c7', 'text' => '#92400e'],
    'picked'      => ['bg' => '#dbeafe', 'text' => '#1d4ed8'],
    'packed'      => ['bg' => '#dcfce7', 'text' => '#166534'],
];

$search = $search ?? '';
$statusFilter = $statusFilter ?? '';
$allOrders = $allOrders ?? $orders;

// ---------- Summary numbers: POORI list se (sirf is page se nahi) ----------
$totalItemsToPick = 0;
$inProgressCount  = 0;
$pickedCount      = 0;
foreach ($allOrders as $o) {
    $totalItemsToPick += (int) $o['quantity'];
    $pk = $o['picking_status'] ?? 'not_started';
    if ($pk === 'picking') { $inProgressCount++; }
    if ($pk === 'picked')  { $pickedCount++; }
}
?>

<div class="ui-head" style="align-items:center;">
    <div class="ui-head-left">
        <div class="breadcrumb">Finovo <i class="bi bi-chevron-right"></i> <span class="current">Picking</span></div>
        <h1>Picking &amp; Packing <span class="count-badge"><?= count($allOrders) ?></span></h1>
        <p>Orders waiting to be picked and packed before dispatch.</p>
    </div>
    <div class="ui-head-right">
        <a href="/shipments" class="ui-btn"><i class="bi bi-truck"></i> Go to Shipments</a>
    </div>
</div>

<?php if (!empty($updated)): ?><div class="banner banner-success"><i class="bi bi-check-circle"></i> Picking status updated.</div><?php endif; ?>
<?php if (!empty($error)): ?><div class="banner banner-error"><i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>

<div class="ui-stats">
    <div class="ui-stat">
        <div class="ui-stat-icon"><i class="bi bi-box-seam"></i></div>
        <div>
            <div class="ui-stat-label">Pending Orders</div>
            <div class="ui-stat-value"><b><?= count($allOrders) ?></b><span class="ui-pill ui-pill-blue">All</span></div>
        </div>
    </div>
    <div class="ui-stat">
        <div class="ui-stat-icon"><i class="bi bi-boxes"></i></div>
        <div>
            <div class="ui-stat-label">Items to Pick</div>
            <div class="ui-stat-value"><b><?= $totalItemsToPick ?></b><span class="ui-pill ui-pill-blue">To pick</span></div>
        </div>
    </div>
    <div class="ui-stat">
        <div class="ui-stat-icon"><i class="bi bi-arrow-repeat"></i></div>
        <div>
            <div class="ui-stat-label">In Progress</div>
            <div class="ui-stat-value"><b><?= $inProgressCount ?></b><span class="ui-pill ui-pill-amber">Picking</span></div>
        </div>
    </div>
    <div class="ui-stat">
        <div class="ui-stat-icon"><i class="bi bi-truck"></i></div>
        <div>
            <div class="ui-stat-label">Picked, Ready to Pack</div>
            <div class="ui-stat-value"><b><?= $pickedCount ?></b><span class="ui-pill ui-pill-green">Ready</span></div>
        </div>
    </div>
</div>

<div class="ui-card">
    <div class="ui-card-head">
        <h2>Orders to Pick &amp; Pack</h2>
    </div>

    <!-- Ek hi line: search + status filter ... Bulk Update + Export (search server pe — har page ka result sahi) -->
    <form method="GET" action="/picking" class="pick-toolbar">
        <label class="ui-search pick-search">
            <i class="bi bi-search"></i>
            <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search orders, customers or products..." aria-label="Search orders, customers or products">
        </label>
        <select name="status" class="pick-select" aria-label="Filter by status" onchange="this.form.submit()">
            <option value="">All statuses</option>
            <?php foreach (['not_started' => 'Not Started', 'picking' => 'Picking', 'picked' => 'Picked'] as $value => $label): ?>
                <option value="<?= $value ?>" <?= $statusFilter === $value ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="ui-btn">Search</button>
        <?php if ($search !== '' || $statusFilter !== ''): ?>
            <a href="/picking" class="ui-btn ui-btn-soft">Clear</a>
        <?php endif; ?>
        <div class="pick-toolbar-right">
            <button type="button" class="ui-btn ui-btn-soft" onclick="openBulkModal()"><i class="bi bi-arrow-repeat"></i> Bulk Update</button>
            <a class="ui-btn" href="/picking?<?= htmlspecialchars(http_build_query(['q' => $search, 'status' => $statusFilter, 'export' => 'csv'])) ?>"><i class="bi bi-download"></i> Export</a>
        </div>
    </form>

    <div style="overflow-x:auto;">
        <table class="data-table" id="pickTable">
            <thead>
                <tr>
                    <th class="checkbox-col"><input type="checkbox" id="pickSelectAll" aria-label="Select all orders"></th>
                    <th>Order</th>
                    <th>Customer</th>
                    <th>Product</th>
                    <th>Qty</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                    <tr><td colspan="7" class="ui-empty">
                        <?php if ($search !== '' || $statusFilter !== ''): ?>
                            <i class="bi bi-search"></i>No orders match your search.
                        <?php else: ?>
                            <i class="bi bi-check2-all"></i>Nothing to pick right now.
                        <?php endif; ?>
                    </td></tr>
                <?php else: ?>
                    <?php foreach ($orders as $o):
                        $pk = $o['picking_status'] ?? 'not_started';
                        $color = $pickColors[$pk] ?? $pickColors['not_started'];
                        $productLabel = $o['product_name'] . (!empty($o['variant_label']) ? ' (' . $o['variant_label'] . ')' : '');
                    ?>
                        <tr class="pick-row"
                            data-id="<?= (int) $o['id'] ?>"
                            data-status="<?= htmlspecialchars($pk) ?>"
                            data-search="<?= htmlspecialchars(strtolower('#' . $o['id'] . ' ' . $o['customer_name'] . ' ' . $productLabel)) ?>"
                            data-customer="<?= htmlspecialchars($o['customer_name']) ?>"
                            data-product="<?= htmlspecialchars($productLabel) ?>"
                            data-qty="<?= (int) $o['quantity'] ?>">
                            <td class="checkbox-col"><input type="checkbox" class="pick-check" value="<?= (int) $o['id'] ?>" aria-label="Select order #<?= (int) $o['id'] ?>"></td>
                            <td style="font-weight:700;">#<?= (int) $o['id'] ?></td>
                            <td style="color:#334155;"><?= htmlspecialchars($o['customer_name']) ?></td>
                            <td style="color:#334155;"><?= htmlspecialchars($productLabel) ?></td>
                            <td style="font-weight:600;"><?= (int) $o['quantity'] ?></td>
                            <td>
                                <select name="status" form="pickForm<?= (int) $o['id'] ?>" class="ui-select" aria-label="Picking status for order #<?= (int) $o['id'] ?>"
                                        style="background:<?= $color['bg'] ?>; color:<?= $color['text'] ?>; border-color:transparent; font-weight:600;">
                                    <?php foreach (['not_started', 'picking', 'picked', 'packed'] as $st): ?>
                                        <option value="<?= $st ?>" <?= $pk === $st ? 'selected' : '' ?>><?= ucwords(str_replace('_', ' ', $st)) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <form method="POST" action="/picking/update-status" id="pickForm<?= (int) $o['id'] ?>" style="margin:0;">
                                    <input type="hidden" name="id" value="<?= $o['id'] ?>">
                                    <button type="submit" class="ui-btn ui-btn-soft ui-btn-icon" title="Save status" aria-label="Save status for order #<?= (int) $o['id'] ?>"><i class="bi bi-check-lg"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php $pagerLabel = 'orders'; require __DIR__ . '/../partials/pagination.php'; ?>
</div>

<style>
.pick-toolbar { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; padding: 14px 20px 16px; }
.pick-toolbar .ui-btn, .pick-toolbar .ui-search, .pick-toolbar .pick-select { height: 36px; box-sizing: border-box; }
.pick-toolbar .pick-search { flex: 1 1 260px; max-width: 420px; }
.pick-select {
    padding: 0 26px 0 10px;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 16 16'%3E%3Cpath fill='%2364748b' d='M8 11L3 6h10z'/%3E%3C/svg%3E") no-repeat right 8px center;
    -webkit-appearance: none;
    appearance: none;
    font: inherit;
    font-size: 13px;
    font-weight: 600;
    color: var(--text-body);
    cursor: pointer;
}
.pick-toolbar-right { display: flex; align-items: center; gap: 8px; margin-left: auto; }
@media (max-width: 700px) {
    .pick-toolbar .pick-search { flex: 1 1 100%; max-width: none; }
    .pick-toolbar-right { margin-left: 0; }
}
</style>

<!-- Bulk update modal (uses the same /picking/update-status endpoint for each selected order) -->
<div class="modal-backdrop" id="bulkPickModal">
    <div class="modal-box">
        <div class="modal-header">
            <h2>Bulk Update Status</h2>
            <button type="button" class="modal-close" onclick="closeBulkModal()" aria-label="Close">&times;</button>
        </div>
        <p class="modal-help" id="bulkPickHelp">Select orders in the table first.</p>
        <div class="form-group">
            <label for="bulkPickStatus">New status</label>
            <select id="bulkPickStatus" style="width:100%; border:1px solid var(--border-color); border-radius:9px; padding:9px 12px; font-size:13.5px; font-family:'Inter',sans-serif; background:#f8fafc;">
                <option value="not_started">Not Started</option>
                <option value="picking">Picking</option>
                <option value="picked">Picked</option>
                <option value="packed">Packed</option>
            </select>
        </div>
        <div class="form-actions">
            <button type="button" class="btn-secondary" onclick="closeBulkModal()">Cancel</button>
            <button type="button" class="btn-primary" id="bulkPickApply" onclick="applyBulkPick()">Update orders</button>
        </div>
    </div>
</div>

<script>
(function () {
    // "Select all" sirf is page ke orders chunta hai
    var selectAll = document.getElementById('pickSelectAll');
    if (selectAll) {
        selectAll.addEventListener('change', function () {
            document.querySelectorAll('.pick-check').forEach(function (c) { c.checked = selectAll.checked; });
        });
    }

    window.pickSelectedIds = function () {
        return Array.prototype.map.call(document.querySelectorAll('.pick-check:checked'), function (c) { return c.value; });
    };
})();

function openBulkModal() {
    var ids = window.pickSelectedIds();
    document.getElementById('bulkPickHelp').textContent = ids.length
        ? ids.length + ' order(s) selected.'
        : 'Select orders in the table first.';
    document.getElementById('bulkPickApply').disabled = ids.length === 0;
    document.getElementById('bulkPickModal').classList.add('show');
}
function closeBulkModal() { document.getElementById('bulkPickModal').classList.remove('show'); }

async function applyBulkPick() {
    var ids = window.pickSelectedIds();
    var status = document.getElementById('bulkPickStatus').value;
    var btn = document.getElementById('bulkPickApply');
    btn.disabled = true;
    btn.textContent = 'Updating...';
    for (var i = 0; i < ids.length; i++) {
        var body = new URLSearchParams();
        body.append('id', ids[i]);
        body.append('status', status);
        await fetch('/picking/update-status', { method: 'POST', body: body });
    }
    window.location.href = '/picking?updated=1';
}
</script>