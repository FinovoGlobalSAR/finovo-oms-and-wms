<?php
// Status dropdown ka text rang (Figma: neutral box, halka rang text)
$pickColors = [
    'not_started' => '#64748b',
    'picking'     => '#b45309',
    'picked'      => '#1d4ed8',
    'packed'      => '#047857',
];
$pickLabels = ['not_started' => 'Not Started', 'picking' => 'Picking', 'picked' => 'Picked', 'packed' => 'Packed'];

$search = $search ?? '';
$statusFilter = $statusFilter ?? '';
$allOrders = $allOrders ?? $orders;
$readyToDispatch = $readyToDispatch ?? 0;

// ---------- Summary numbers: POORI list se (sirf is page se nahi) ----------
$totalItemsToPick = 0;
$inProgressCount  = 0;
foreach ($allOrders as $o) {
    $totalItemsToPick += (int) $o['quantity'];
    if (($o['picking_status'] ?? 'not_started') === 'picking') { $inProgressCount++; }
}
?>

<div class="pk-page">

<div class="ui-head">
    <div class="ui-head-left">
        <div class="breadcrumb">Finovo <i class="bi bi-chevron-right"></i> <span class="current">Picking</span></div>
        <h1>Picking &amp; Packing <span class="pk-count"><?= count($allOrders) ?></span></h1>
        <p>Orders waiting to be picked and packed before dispatch.</p>
    </div>
    <div class="ui-info pk-info">
        <div class="ui-info-icon round"><i class="bi bi-info-lg"></i></div>
        <div>
            <strong>Fulfill orders efficiently</strong>
            <span>Keep picking and packing status up to date to ensure faster dispatch and happier customers.</span>
            <a href="#" onclick="document.getElementById('pkHelp').classList.toggle('show'); return false;">Learn more <i class="bi bi-arrow-right"></i></a>
            <span class="pk-help" id="pkHelp">Not Started → Picking → Picked → Packed. Packed orders leave this list and are ready to dispatch from Shipments.</span>
        </div>
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
        <div class="ui-stat-icon"><i class="bi bi-box"></i></div>
        <div>
            <div class="ui-stat-label">Items to Pick</div>
            <div class="ui-stat-value"><b><?= $totalItemsToPick ?></b><span class="ui-pill ui-pill-blue">To pick</span></div>
        </div>
    </div>
    <div class="ui-stat">
        <div class="ui-stat-icon"><i class="bi bi-arrow-repeat"></i></div>
        <div>
            <div class="ui-stat-label">In Progress</div>
            <div class="ui-stat-value"><b><?= $inProgressCount ?></b><span class="ui-pill ui-pill-blue">On track</span></div>
        </div>
    </div>
    <div class="ui-stat">
        <div class="ui-stat-icon"><i class="bi bi-truck"></i></div>
        <div>
            <div class="ui-stat-label">Ready to Dispatch</div>
            <div class="ui-stat-value"><b><?= $readyToDispatch ?></b><span class="ui-pill ui-pill-blue">Ready</span></div>
        </div>
    </div>
</div>

<div class="ui-card pk-card" style="overflow:visible;">
    <div class="pk-card-head">
        <h2>Orders to Pick &amp; Pack</h2>
        <div class="pk-tools">
            <button type="button" class="pk-btn pk-btn-soft" onclick="openBulkModal()"><i class="bi bi-arrow-repeat"></i> Bulk Update</button>
            <div class="ui-menu">
                <button type="button" class="pk-btn" data-menu-toggle><i class="bi bi-sliders"></i> Filters<?= $statusFilter !== '' ? ': ' . htmlspecialchars($pickLabels[$statusFilter] ?? '') : '' ?></button>
                <div class="ui-menu-list">
                    <a href="/picking?<?= htmlspecialchars(http_build_query(['q' => $search])) ?>">All statuses</a>
                    <?php foreach (['not_started', 'picking', 'picked'] as $value): ?>
                        <a href="/picking?<?= htmlspecialchars(http_build_query(['q' => $search, 'status' => $value])) ?>"><?= $pickLabels[$value] ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
            <a class="pk-btn" href="/picking?<?= htmlspecialchars(http_build_query(['q' => $search, 'status' => $statusFilter, 'export' => 'csv'])) ?>"><i class="bi bi-download"></i> Export</a>
        </div>
    </div>

    <!-- Search server pe (Enter dabao) — har page ka result sahi -->
    <form method="GET" action="/picking" class="pk-search-row">
        <?php if ($statusFilter !== ''): ?><input type="hidden" name="status" value="<?= htmlspecialchars($statusFilter) ?>"><?php endif; ?>
        <label class="ui-search">
            <i class="bi bi-search"></i>
            <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search orders, customers or products..." aria-label="Search orders, customers or products">
        </label>
    </form>

    <div style="overflow-x:auto;">
        <table class="data-table pk-table" id="pickTable">
            <thead>
                <tr>
                    <th class="checkbox-col"><input type="checkbox" id="pickSelectAll" aria-label="Select all orders"></th>
                    <th style="width:80px;">Order</th>
                    <th>Customer</th>
                    <th>Product</th>
                    <th style="width:100px;">Qty</th>
                    <th style="width:150px;">Status</th>
                    <th style="width:70px;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                    <tr><td colspan="7" class="ui-empty">
                        <?php if ($search !== '' || $statusFilter !== ''): ?>
                            <i class="bi bi-search"></i>No orders match your search. <a href="/picking" class="link-blue">Clear</a>
                        <?php else: ?>
                            <i class="bi bi-check2-all"></i>Nothing to pick right now.
                        <?php endif; ?>
                    </td></tr>
                <?php else: ?>
                    <?php foreach ($orders as $o):
                        $pk = $o['picking_status'] ?? 'not_started';
                        $productLabel = $o['product_name'] . (!empty($o['variant_label']) ? ' (' . $o['variant_label'] . ')' : '');
                    ?>
                        <tr class="pick-row" data-id="<?= (int) $o['id'] ?>">
                            <td class="checkbox-col"><input type="checkbox" class="pick-check" value="<?= (int) $o['id'] ?>" aria-label="Select order #<?= (int) $o['id'] ?>"></td>
                            <td class="pk-order">#<?= (int) $o['id'] ?></td>
                            <td class="pk-muted"><?= htmlspecialchars($o['customer_name']) ?></td>
                            <td class="pk-muted"><?= htmlspecialchars($productLabel) ?></td>
                            <td class="pk-qty"><?= (int) $o['quantity'] ?></td>
                            <td>
                                <select name="status" form="pickForm<?= (int) $o['id'] ?>" class="pk-status" style="color:<?= $pickColors[$pk] ?? $pickColors['not_started'] ?>;" aria-label="Picking status for order #<?= (int) $o['id'] ?>">
                                    <?php foreach ($pickLabels as $st => $label): ?>
                                        <option value="<?= $st ?>" <?= $pk === $st ? 'selected' : '' ?>><?= $label ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <form method="POST" action="/picking/update-status" id="pickForm<?= (int) $o['id'] ?>" style="margin:0;">
                                    <input type="hidden" name="id" value="<?= $o['id'] ?>">
                                    <button type="submit" class="pk-save" title="Save status" aria-label="Save status for order #<?= (int) $o['id'] ?>"><i class="bi bi-check-lg"></i></button>
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

</div>

<style>
/* Picking page — Figma "finovo-picking-packing" */
.pk-page .breadcrumb { gap: 4px; }
.pk-page .breadcrumb .current { color: var(--primary); font-weight: 600; }
.pk-page .ui-head h1 { font-size: 26px; font-weight: 800; color: #1e293b; gap: 10px; }
.pk-count { padding: 3px 9px; border-radius: 8px; background: var(--primary-light); color: var(--primary); font-size: 13px; font-weight: 700; line-height: 1.3; }
.pk-page .ui-head p { font-size: 13px; color: var(--text-muted); }
.pk-info { width: 440px; align-items: flex-start; gap: 14px; border-color: #bfdbfe; border-radius: 14px; }
.pk-info strong { color: var(--primary); margin-bottom: 4px; }
.pk-info span { font-size: 12px; color: var(--text-muted); }
.pk-help { display: none !important; margin-top: 6px; padding: 8px 10px; border-radius: 8px; background: #fff; border: 1px solid #dbeafe; }
.pk-help.show { display: block !important; }
.pk-page .ui-stats { gap: 12px; margin-bottom: 20px; }
.pk-page .ui-stat { padding: 16px; gap: 14px; border-radius: 14px; }
.pk-page .ui-stat-icon { width: 40px; height: 40px; border-radius: 10px; font-size: 18px; }
.pk-page .ui-stat-label { color: var(--text-muted); }
.pk-page .ui-stat-value { gap: 6px; margin-top: 3px; }
.pk-page .ui-stat-value b { color: #1e293b; }
.pk-page .ui-pill { font-size: 10px; padding: 2px 7px; border-radius: 5px; }

.pk-card { border-radius: 14px; padding: 22px 0 20px; }
.pk-card-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 0 22px; flex-wrap: wrap; }
.pk-card-head h2 { margin: 0; font-size: 16px; font-weight: 700; color: #1e293b; }
.pk-tools { display: flex; gap: 10px; flex-wrap: wrap; }
.pk-btn { display: inline-flex; align-items: center; gap: 7px; height: 32px; padding: 0 14px; border: 1px solid var(--border-color); border-radius: 8px; background: #fff; color: var(--text-muted); font-family: inherit; font-size: 12px; font-weight: 600; text-decoration: none; cursor: pointer; white-space: nowrap; }
.pk-btn:hover { background: #f8fafc; }
.pk-btn-soft { background: var(--primary-light); border-color: #bfdbfe; color: var(--primary); }
.pk-btn-soft:hover { background: #dbeafe; }
.pk-search-row { padding: 14px 22px; margin: 0; }
.pk-search-row .ui-search { height: 32px; }

.pk-table th { padding: 10px 22px; font-size: 12px; font-weight: 700; text-transform: uppercase; color: var(--text-muted); background: #f8fafc; }
.pk-table td { padding: 12px 22px; border-bottom: 1px solid var(--border-color); }
.pk-table th.checkbox-col, .pk-table td.checkbox-col { width: 36px; padding-right: 0; }
.pk-table input[type="checkbox"] { width: 14px; height: 14px; border-radius: 3px; }
.pk-order { font-weight: 600; color: #1e293b !important; }
.pk-muted { color: var(--text-body) !important; }
.pk-qty { font-weight: 500; color: #1e293b !important; }
.pk-status { height: 26px; padding: 0 22px 0 10px; border: 1px solid var(--border-color); border-radius: 6px; background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='10' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E") no-repeat right 8px center; -webkit-appearance: none; appearance: none; font-family: inherit; font-size: 12px; font-weight: 500; cursor: pointer; }
.pk-save { width: 28px; height: 28px; border-radius: 8px; border: 1px solid #bfdbfe; background: #ecf3fd; color: var(--brand); font-size: 14px; display: flex; align-items: center; justify-content: center; cursor: pointer; }
.pk-save:hover { background: #dbeafe; }
.pk-card .pagination-bar { border-top: none; padding: 20px 22px 0; }
@media (max-width: 1100px) { .pk-info { width: 100%; } }
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