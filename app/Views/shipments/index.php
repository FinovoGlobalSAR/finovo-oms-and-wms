<?php
// Upar ke numbers POORI list se ($allShipments); table mein sirf latest 5 rows ($shipments)
$allShipments = $allShipments ?? $shipments;
$search = $search ?? '';
$statusFilter = $statusFilter ?? '';
$statusColors = [
    'pending'     => ['bg' => '#f1f5f9', 'text' => '#334155'],
    'packed'      => ['bg' => '#fef3c7', 'text' => '#92400e'],
    'dispatched'  => ['bg' => '#dbeafe', 'text' => '#1d4ed8'],
    'in_transit'  => ['bg' => '#e0e7ff', 'text' => '#4338ca'],
    'delivered'   => ['bg' => '#dcfce7', 'text' => '#166534'],
    'returned'    => ['bg' => '#fee2e2', 'text' => '#991b1b'],
];

// ---------- Summary numbers (POORI list se — $allShipments) ----------
$onTheWayCount = 0;
$deliveredCount = 0;
$returnedCount = 0;
foreach ($allShipments as $s) {
    if (in_array($s['status'], ['dispatched', 'in_transit'], true)) { $onTheWayCount++; }
    if ($s['status'] === 'delivered') { $deliveredCount++; }
    if ($s['status'] === 'returned') { $returnedCount++; }
}
?>

<div class="ui-head">
    <div class="ui-head-left">
        <div class="breadcrumb">Finovo <i class="bi bi-chevron-right"></i> <span class="current">Shipments</span></div>
        <h1>Shipments <span class="count-badge"><?= count($allShipments) ?></span></h1>
        <p>Dispatch orders with a courier and track them until delivery.</p>
    </div>
    <div class="ui-head-right">
        <button type="button" class="ui-btn ui-btn-primary" onclick="openModal('createShipmentModal')" <?= empty($unassignedUnits) ? 'disabled title="No orders waiting for dispatch"' : '' ?>><i class="bi bi-truck"></i> Dispatch Order</button>
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
            <div class="ui-stat-value"><b><?= count($allShipments) ?></b><span class="ui-pill ui-pill-blue">All</span></div>
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

<div class="ui-card tbl-card">
    <!-- Search / status filter: server pe chalte hain, har page ka result sahi -->
    <form method="GET" action="/shipments">
        <div class="tbl-head">
            <h2>All Shipments</h2>
            <div class="tbl-tools">
                <a href="/3pl/scan" class="tbl-btn"><i class="bi bi-upc-scan"></i> LM Scan</a>
                <a href="/3pl/remittance" class="tbl-btn"><i class="bi bi-cash-coin"></i> Remittance</a>
                <div class="ui-menu">
                    <button type="button" class="tbl-btn" data-menu-toggle><i class="bi bi-sliders"></i> Filters<?php if ($statusFilter !== ''): ?> <span class="tbl-count">1</span><?php endif; ?></button>
                    <div class="ui-menu-list tbl-filter-panel">
                        <label for="shipStatus">Status</label>
                        <select name="status" id="shipStatus" onchange="this.form.submit()">
                            <option value="">All statuses</option>
                            <?php foreach ($statuses as $st): ?>
                                <option value="<?= $st ?>" <?= $statusFilter === $st ? 'selected' : '' ?>><?= ucwords(str_replace('_', ' ', $st)) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="tbl-filter-actions">
                            <a href="/shipments" class="tbl-btn">Clear</a>
                            <button type="submit" class="tbl-btn tbl-btn-soft">Apply</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="tbl-search">
            <label class="ui-search">
                <i class="bi bi-search"></i>
                <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search customer, courier or tracking number..." aria-label="Search shipments">
            </label>
        </div>
    </form>

    <div style="overflow-x:auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Shipment</th>
                    <th>Customer</th>
                    <th>Courier</th>
                    <th>Tracking Number</th>
                    <th>Status</th>
                    <th style="width:230px;">Update Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($shipments)): ?>
                    <tr><td colspan="6" class="ui-empty"><i class="bi bi-truck"></i>No shipments yet. Click "Dispatch Order" to send your first one.</td></tr>
                <?php else: ?>
                    <?php foreach ($shipments as $s):
                        $statusColor = $statusColors[$s['status']] ?? ['bg' => '#f1f5f9', 'text' => '#334155'];
                    ?>
                        <tr class="ship-row">
                            <td style="font-weight:700;">#<?= (int) $s['id'] ?></td>
                            <td style="color:#334155; font-weight:500;"><?= htmlspecialchars($s['customer_name'] ?? '-') ?></td>
                            <td style="color:#334155;"><?= htmlspecialchars($s['courier_name']) ?></td>
                            <td>
                                <?php if (!empty($s['tracking_number'])): ?>
                                    <!-- Copy karke LM Scan mein paste kar sakte hain -->
                                    <span class="trk">
                                        <code class="trk-no"><?= htmlspecialchars($s['tracking_number']) ?></code>
                                        <button type="button" class="trk-copy" data-copy="<?= htmlspecialchars($s['tracking_number']) ?>" title="Copy tracking number" aria-label="Copy tracking number <?= htmlspecialchars($s['tracking_number']) ?>"><i class="bi bi-copy"></i></button>
                                    </span>
                                <?php else: ?>
                                    <span style="color:#94a3b8;">—</span>
                                <?php endif; ?>
                            </td>
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
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if (!empty($pager)) { $pagerLabel = 'shipments'; require __DIR__ . '/../partials/pagination.php'; } ?>
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
.trk { display: inline-flex; align-items: center; gap: 6px; white-space: nowrap; }
.trk-no { font-family: ui-monospace, SFMono-Regular, Consolas, monospace; font-size: 12.5px; color: var(--text-dark); background: #f1f5f9; border: 1px solid var(--border-color); border-radius: 6px; padding: 3px 8px; user-select: all; }
.trk-copy { width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center; border: 1px solid var(--border-color); border-radius: 6px; background: #fff; color: var(--primary); cursor: pointer; font-size: 13px; }
.trk-copy:hover { background: var(--primary-light); }
.trk-copy.copied { background: #dcfce7; border-color: #bbf7d0; color: #166534; }
</style>

<script>
function openModal(id) { document.getElementById(id).classList.add('show'); }
function closeModal(id) { document.getElementById(id).classList.remove('show'); }

// Tracking number copy (LM Scan mein paste karne ke liye)
document.querySelectorAll('.trk-copy').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var text = btn.getAttribute('data-copy');
        function done() {
            btn.classList.add('copied');
            btn.innerHTML = '<i class="bi bi-check-lg"></i>';
            btn.title = 'Copied';
            setTimeout(function () {
                btn.classList.remove('copied');
                btn.innerHTML = '<i class="bi bi-copy"></i>';
                btn.title = 'Copy tracking number';
            }, 1500);
        }
        // https / localhost pe naya tareeka; warna purana tareeka (http pe bhi chalta hai)
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(done);
        } else {
            var box = document.createElement('textarea');
            box.value = text;
            box.style.position = 'fixed';
            box.style.opacity = '0';
            document.body.appendChild(box);
            box.select();
            document.execCommand('copy');
            document.body.removeChild(box);
            done();
        }
    });
});
</script>