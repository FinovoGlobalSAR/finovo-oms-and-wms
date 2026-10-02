<?php
$statusColors = [
    'pending'     => ['bg' => '#f1f5f9', 'text' => '#334155'],
    'packed'      => ['bg' => '#fef3c7', 'text' => '#92400e'],
    'dispatched'  => ['bg' => '#dbeafe', 'text' => '#1d4ed8'],
    'in_transit'  => ['bg' => '#e0e7ff', 'text' => '#4338ca'],
    'delivered'   => ['bg' => '#dcfce7', 'text' => '#166534'],
    'returned'    => ['bg' => '#fee2e2', 'text' => '#991b1b'],
];

// ---------- Summary numbers (sirf display ke liye, $shipments se hi) ----------
$onTheWayCount = 0;
$deliveredCount = 0;
$returnedCount = 0;
foreach ($shipments as $s) {
    if (in_array($s['status'], ['dispatched', 'in_transit'], true)) { $onTheWayCount++; }
    if ($s['status'] === 'delivered') { $deliveredCount++; }
    if ($s['status'] === 'returned') { $returnedCount++; }
}
?>

<div class="ui-head">
    <div class="ui-head-left">
        <div class="breadcrumb">Finovo <i class="bi bi-chevron-right"></i> <span class="current">Shipments</span></div>
        <h1>Shipments <span class="count-badge"><?= count($shipments) ?></span></h1>
        <p>Dispatch orders with a courier and track them until delivery.</p>
    </div>
</div>

<?php if (!empty($created)): ?><div class="banner banner-success"><i class="bi bi-check-circle"></i> Shipment created.</div><?php endif; ?>
<?php if (!empty($updated)): ?><div class="banner banner-success"><i class="bi bi-check-circle"></i> Shipment updated.</div><?php endif; ?>
<?php if (!empty($_GET['tracked'])): ?><div class="banner banner-success"><i class="bi bi-broadcast"></i> Live status: <?= htmlspecialchars($_GET['tracked']) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="banner banner-error"><i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>

<div class="ui-stats">
    <div class="ui-stat">
        <div class="ui-stat-icon"><i class="bi bi-box-seam"></i></div>
        <div>
            <div class="ui-stat-label">Total Shipments</div>
            <div class="ui-stat-value"><b><?= count($shipments) ?></b><span class="ui-pill ui-pill-blue">All</span></div>
        </div>
    </div>
    <div class="ui-stat">
        <div class="ui-stat-icon"><i class="bi bi-truck"></i></div>
        <div>
            <div class="ui-stat-label">On the Way</div>
            <div class="ui-stat-value"><b><?= $onTheWayCount ?></b><span class="ui-pill ui-pill-blue">In transit</span></div>
        </div>
    </div>
    <div class="ui-stat">
        <div class="ui-stat-icon"><i class="bi bi-check2-circle"></i></div>
        <div>
            <div class="ui-stat-label">Delivered</div>
            <div class="ui-stat-value"><b><?= $deliveredCount ?></b><span class="ui-pill ui-pill-green">Done</span></div>
        </div>
    </div>
    <div class="ui-stat">
        <div class="ui-stat-icon"><i class="bi bi-arrow-return-left"></i></div>
        <div>
            <div class="ui-stat-label">Returned</div>
            <div class="ui-stat-value"><b><?= $returnedCount ?></b>
                <span class="ui-pill <?= $returnedCount > 0 ? 'ui-pill-red' : 'ui-pill-green' ?>"><?= $returnedCount > 0 ? 'Check returns' : 'None' ?></span>
            </div>
        </div>
    </div>
</div>

<div class="ui-card" style="overflow: visible;">
    <div class="ship-toolbar">
        <label class="ui-search ship-search">
            <i class="bi bi-search"></i>
            <input type="text" id="shipSearch" placeholder="Search customer, courier or tracking number..." aria-label="Search shipments">
        </label>
        <select id="shipStatusFilter" class="ui-btn" aria-label="Filter by status" style="padding-right:10px;">
            <option value="">All statuses</option>
            <?php foreach ($statuses as $st): ?>
                <option value="<?= $st ?>"><?= ucwords(str_replace('_', ' ', $st)) ?></option>
            <?php endforeach; ?>
        </select>
        <div class="ship-toolbar-actions">
            <a href="/3pl/scan" class="ui-btn"><i class="bi bi-upc-scan"></i> LM Scan</a>
            <a href="/3pl/remittance" class="ui-btn"><i class="bi bi-cash-coin"></i> Remittance</a>
            <button type="button" class="ui-btn ui-btn-primary" onclick="openModal('createShipmentModal')" <?= empty($unassignedUnits) ? 'disabled title="No orders waiting for dispatch"' : '' ?>>
                <i class="bi bi-truck"></i> Dispatch Order
            </button>
        </div>
    </div>

    <div style="overflow-x:auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Shipment</th>
                    <th>Customer</th>
                    <th>Items</th>
                    <th>Courier</th>
                    <th>Tracking #</th>
                    <th>Status</th>
                    <th style="width:230px;">Update Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($shipments)): ?>
                    <tr><td colspan="7" class="ui-empty"><i class="bi bi-truck"></i>No shipments yet. Click "Dispatch Order" to send your first one.</td></tr>
                <?php else: ?>
                    <?php foreach ($shipments as $s):
                        $statusColor = $statusColors[$s['status']] ?? ['bg' => '#f1f5f9', 'text' => '#334155'];
                    ?>
                        <tr class="ship-row" data-status="<?= htmlspecialchars($s['status']) ?>" data-search="<?= htmlspecialchars(strtolower(($s['customer_name'] ?? '') . ' ' . $s['courier_name'] . ' ' . ($s['tracking_number'] ?? '') . ' #' . $s['id'])) ?>">
                            <td style="font-weight:700;">#<?= (int) $s['id'] ?></td>
                            <td style="color:#334155; font-weight:500;"><?= htmlspecialchars($s['customer_name'] ?? '-') ?></td>
                            <td>
                                <span class="ui-tag ui-pill-gray" title="<?= htmlspecialchars(implode(', ', array_map(fn($o) => $o['product_name'], $s['orders']))) ?>">
                                    <i class="bi bi-box"></i> <?= (int) $s['item_count'] ?> item(s)
                                </span>
                            </td>
                            <td style="color:#334155;"><?= htmlspecialchars($s['courier_name']) ?></td>
                            <td style="color:#334155; font-family:ui-monospace, 'Cascadia Code', Consolas, monospace; font-size:12.5px;"><?= htmlspecialchars($s['tracking_number'] ?: '-') ?></td>
                            <td>
                                <span class="ui-tag" style="background:<?= $statusColor['bg'] ?>; color:<?= $statusColor['text'] ?>; white-space:nowrap;">
                                    <?= htmlspecialchars(ucwords(str_replace('_', ' ', $s['status']))) ?>
                                </span>
                            </td>
                            <td>
                                <form method="POST" action="/shipments/update-status" style="display:flex; gap:6px; margin:0;">
                                    <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                    <select name="status" class="ui-select" style="flex:1;" aria-label="New status for shipment #<?= (int) $s['id'] ?>">
                                        <?php foreach ($statuses as $st): ?>
                                            <option value="<?= $st ?>" <?= $s['status'] === $st ? 'selected' : '' ?>><?= ucwords(str_replace('_', ' ', $st)) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="ui-btn ui-btn-soft ui-btn-icon" title="Save status" aria-label="Save status"><i class="bi bi-check-lg"></i></button>
                                </form>
                                <a href="/3pl/track?id=<?= $s['id'] ?>" style="display:inline-flex; align-items:center; gap:4px; margin-top:6px; font-size:11.5px; font-weight:600; color:var(--primary); text-decoration:none;">
                                    <i class="bi bi-broadcast"></i> Track Live (DHL only for now)
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <tr id="shipNoMatch" style="display:none;"><td colspan="7" class="ui-empty"><i class="bi bi-search"></i>No shipments match your search.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="pagination-bar">
        <span id="shipShowing">Showing <?= count($shipments) ?> of <?= count($shipments) ?> shipments</span>
    </div>
</div>

<div class="modal-backdrop" id="createShipmentModal">
    <div class="modal-box">
        <div class="modal-header">
            <h2>Dispatch Order</h2>
            <button type="button" class="modal-close" onclick="closeModal('createShipmentModal')" aria-label="Close">&times;</button>
        </div>
        <form action="/shipments/create" method="POST">
            <div class="form-group">
                <label>Order</label>
                <select name="order_unit" required style="width:100%; border:1px solid var(--border-color); border-radius:9px; padding:9px 12px; font-size:13.5px; font-family:inherit; background:#f8fafc;">
                    <option value="">Select an order...</option>
                    <?php foreach ($unassignedUnits as $u): ?>
                        <option value="<?= htmlspecialchars($u['unit_key']) ?>">
                            <?= htmlspecialchars($u['customer_name']) ?> — <?= htmlspecialchars($u['products']) ?> (<?= (int) $u['item_count'] ?> item<?= $u['item_count'] > 1 ? 's' : '' ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Courier</label>
                <select name="courier_name" required style="width:100%; border:1px solid var(--border-color); border-radius:9px; padding:9px 12px; font-size:13.5px; font-family:inherit; background:#f8fafc;">
                    <?php foreach ($couriers as $c): ?>
                        <option value="<?= htmlspecialchars($c) ?>"><?= htmlspecialchars($c) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Tracking Number (optional)</label>
                <input type="text" name="tracking_number" placeholder="e.g. TCS1234567890">
            </div>
            <div class="form-group">
                <label>Notes (optional)</label>
                <input type="text" name="notes" placeholder="e.g. Fragile, handle with care">
            </div>
            <div class="form-actions">
                <button type="button" class="btn-secondary" onclick="closeModal('createShipmentModal')">Cancel</button>
                <button type="submit" class="btn-primary"><i class="bi bi-truck"></i> Dispatch</button>
            </div>
        </form>
    </div>
</div>

<style>
.ship-toolbar { display: flex; align-items: center; gap: 8px; flex-wrap: nowrap; padding: 14px 20px; border-bottom: 1px solid var(--border-color); overflow-x: auto; }
.ship-search { flex: 1 1 auto; min-width: 180px; height: 34px; }
.ship-toolbar-actions { display: flex; align-items: center; gap: 6px; flex-wrap: nowrap; flex-shrink: 0; margin-left: auto; }
.ship-toolbar .ui-btn { height: 34px; padding: 0 10px; font-size: 12.5px; white-space: nowrap; }
.ship-toolbar .ui-btn:disabled { opacity: 0.55; cursor: not-allowed; }
</style>

<script>
function openModal(id) { document.getElementById(id).classList.add('show'); }
function closeModal(id) { document.getElementById(id).classList.remove('show'); }

(function () {
    var input = document.getElementById('shipSearch');
    var filter = document.getElementById('shipStatusFilter');
    var rows = Array.prototype.slice.call(document.querySelectorAll('.ship-row'));
    function apply() {
        var q = input.value.trim().toLowerCase();
        var st = filter.value;
        var shown = 0;
        rows.forEach(function (r) {
            var ok = (q === '' || r.dataset.search.indexOf(q) !== -1) && (st === '' || r.dataset.status === st);
            r.style.display = ok ? '' : 'none';
            if (ok) { shown++; }
        });
        var nm = document.getElementById('shipNoMatch');
        if (nm) { nm.style.display = shown === 0 ? '' : 'none'; }
        document.getElementById('shipShowing').textContent = 'Showing ' + shown + ' of ' + rows.length + ' shipments';
    }
    input.addEventListener('input', apply);
    filter.addEventListener('change', apply);
})();
</script>