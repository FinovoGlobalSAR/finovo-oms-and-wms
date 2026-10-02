<?php
$statusColors = [
    'requested' => ['bg' => '#fef3c7', 'text' => '#92400e'],
    'approved'  => ['bg' => '#dbeafe', 'text' => '#1d4ed8'],
    'rejected'  => ['bg' => '#fee2e2', 'text' => '#991b1b'],
    'completed' => ['bg' => '#dcfce7', 'text' => '#166534'],
];

// ---------- Summary numbers (sirf display ke liye, $returns se hi) ----------
$returnCounts = ['requested' => 0, 'approved' => 0, 'rejected' => 0, 'completed' => 0];
foreach ($returns as $r) {
    if (isset($returnCounts[$r['status']])) { $returnCounts[$r['status']]++; }
}
?>

<div class="ui-head">
    <div class="ui-head-left">
        <div class="breadcrumb">Finovo <i class="bi bi-chevron-right"></i> <span class="current">Returns</span></div>
        <h1>Returns <span class="count-badge"><?= count($returns) ?></span></h1>
        <p>Track returned items and refunds. Completing a resellable return restores warehouse stock.</p>
    </div>
</div>

<?php if (!empty($created)): ?><div class="banner banner-success"><i class="bi bi-check-circle"></i> Return recorded.</div><?php endif; ?>
<?php if (!empty($updated)): ?><div class="banner banner-success"><i class="bi bi-check-circle"></i> Return updated.</div><?php endif; ?>
<?php if (!empty($error)): ?><div class="banner banner-error"><i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>

<div class="ui-stats">
    <div class="ui-stat">
        <div class="ui-stat-icon"><i class="bi bi-hourglass-split"></i></div>
        <div>
            <div class="ui-stat-label">Requested</div>
            <div class="ui-stat-value"><b><?= $returnCounts['requested'] ?></b>
                <span class="ui-pill <?= $returnCounts['requested'] > 0 ? 'ui-pill-amber' : 'ui-pill-green' ?>"><?= $returnCounts['requested'] > 0 ? 'Needs review' : 'None waiting' ?></span>
            </div>
        </div>
    </div>
    <div class="ui-stat">
        <div class="ui-stat-icon"><i class="bi bi-hand-thumbs-up"></i></div>
        <div>
            <div class="ui-stat-label">Approved</div>
            <div class="ui-stat-value"><b><?= $returnCounts['approved'] ?></b><span class="ui-pill ui-pill-blue">In progress</span></div>
        </div>
    </div>
    <div class="ui-stat">
        <div class="ui-stat-icon"><i class="bi bi-check2-circle"></i></div>
        <div>
            <div class="ui-stat-label">Completed</div>
            <div class="ui-stat-value"><b><?= $returnCounts['completed'] ?></b><span class="ui-pill ui-pill-green">Closed</span></div>
        </div>
    </div>
    <div class="ui-stat">
        <div class="ui-stat-icon"><i class="bi bi-x-circle"></i></div>
        <div>
            <div class="ui-stat-label">Rejected</div>
            <div class="ui-stat-value"><b><?= $returnCounts['rejected'] ?></b><span class="ui-pill ui-pill-red">Closed</span></div>
        </div>
    </div>
</div>

<div class="ui-card" style="overflow: visible;">
    <div class="ret-toolbar">
        <label class="ui-search ret-search">
            <i class="bi bi-search"></i>
            <input type="text" id="retSearch" placeholder="Search order, customer or product..." aria-label="Search returns">
        </label>
        <select id="retStatusFilter" class="ui-btn" aria-label="Filter by status" style="padding-right:10px;">
            <option value="">All statuses</option>
            <?php foreach ($statuses as $st): ?>
                <option value="<?= $st ?>"><?= ucfirst($st) ?></option>
            <?php endforeach; ?>
        </select>
        <div class="ret-toolbar-actions">
            <button type="button" class="ui-btn ui-btn-primary" onclick="openModal('createReturnModal')"><i class="bi bi-arrow-return-left"></i> New Return</button>
        </div>
    </div>

    <div style="overflow-x:auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Return</th>
                    <th>Order</th>
                    <th>Product</th>
                    <th>Qty</th>
                    <th>Refund</th>
                    <th>Status</th>
                    <th style="width:280px;">Update</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($returns)): ?>
                    <tr><td colspan="7" class="ui-empty"><i class="bi bi-arrow-return-left"></i>No returns yet. Click "New Return" when a customer sends something back.</td></tr>
                <?php else: ?>
                    <?php foreach ($returns as $r):
                        $statusColor = $statusColors[$r['status']] ?? ['bg' => '#f1f5f9', 'text' => '#334155'];
                        $currency = $r['currency_symbol'] ?? 'Rs.';
                        $isFinal = $r['status'] === 'completed' || $r['status'] === 'rejected';
                    ?>
                        <tr class="ret-row" data-status="<?= htmlspecialchars($r['status']) ?>" data-search="<?= htmlspecialchars(strtolower('#' . $r['id'] . ' #' . $r['order_id'] . ' ' . $r['customer_name'] . ' ' . $r['product_name'])) ?>">
                            <td style="font-weight:700;">#<?= (int) $r['id'] ?></td>
                            <td>
                                <div style="display:flex; flex-direction:column; gap:2px;">
                                    <span style="font-weight:600;">#<?= (int) $r['order_id'] ?></span>
                                    <span style="font-size:12px; color:#64748b;"><?= htmlspecialchars($r['customer_name']) ?></span>
                                </div>
                            </td>
                            <td style="color:#334155;"><?= htmlspecialchars($r['product_name']) ?></td>
                            <td style="font-weight:600;"><?= (int) $r['quantity'] ?></td>
                            <td style="white-space:nowrap; font-weight:600;"><?= $currency ?> <?= number_format((float) $r['refund_amount'], 2) ?></td>
                            <td>
                                <span class="ui-tag" style="background:<?= $statusColor['bg'] ?>; color:<?= $statusColor['text'] ?>;">
                                    <?= ucfirst($r['status']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if (!$isFinal): ?>
                                    <form method="POST" action="/returns/update-status" style="display:flex; gap:6px; margin:0;">
                                        <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                        <select name="status" class="ui-select" aria-label="New status for return #<?= (int) $r['id'] ?>">
                                            <?php foreach ($statuses as $st): ?>
                                                <option value="<?= $st ?>" <?= $r['status'] === $st ? 'selected' : '' ?>><?= ucfirst($st) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <select name="condition_status" class="ui-select" aria-label="Item condition for return #<?= (int) $r['id'] ?>">
                                            <?php foreach ($conditions as $c): ?>
                                                <option value="<?= $c ?>" <?= $r['condition_status'] === $c ? 'selected' : '' ?>><?= ucfirst($c) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" class="ui-btn ui-btn-soft ui-btn-icon" title="Save" aria-label="Save return"><i class="bi bi-check-lg"></i></button>
                                    </form>
                                <?php else: ?>
                                    <span class="ui-tag ui-pill-gray"><i class="bi bi-lock"></i> Final</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <tr id="retNoMatch" style="display:none;"><td colspan="7" class="ui-empty"><i class="bi bi-search"></i>No returns match your search.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="pagination-bar">
        <span id="retShowing">Showing <?= count($returns) ?> of <?= count($returns) ?> returns</span>
    </div>
</div>

<div class="modal-backdrop" id="createReturnModal">
    <div class="modal-box">
        <div class="modal-header">
            <h2>New Return</h2>
            <button type="button" class="modal-close" onclick="closeModal('createReturnModal')" aria-label="Close">&times;</button>
        </div>
        <form action="/returns/create" method="POST">
            <div class="form-group">
                <label>Order</label>
                <select name="order_id" required style="width:100%; border:1px solid var(--border-color); border-radius:9px; padding:9px 12px; font-size:13.5px; font-family:inherit; background:#f8fafc;">
                    <option value="">Select order...</option>
                    <?php if (empty($recentOrders)): ?>
                        <option value="" disabled>No eligible orders — all recent orders already have a return.</option>
                    <?php endif; ?>
                    <?php foreach ($recentOrders as $o): ?>
                        <option value="<?= $o['id'] ?>">#<?= $o['id'] ?> — <?= htmlspecialchars($o['customer_name']) ?> — <?= htmlspecialchars($o['product_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Quantity</label>
                <input type="number" name="quantity" min="1" value="1" required>
            </div>
            <div class="form-group">
                <label>Reason</label>
                <input type="text" name="reason" placeholder="e.g. Wrong size, defective">
            </div>
            <div class="form-group">
                <label>Refund Amount</label>
                <input type="number" name="refund_amount" min="0" step="0.01" placeholder="0.00">
            </div>
            <div class="form-actions">
                <button type="button" class="btn-secondary" onclick="closeModal('createReturnModal')">Cancel</button>
                <button type="submit" class="btn-primary">Record Return</button>
            </div>
        </form>
    </div>
</div>

<style>
.ret-toolbar { display: flex; align-items: center; gap: 8px; flex-wrap: nowrap; padding: 14px 20px; border-bottom: 1px solid var(--border-color); overflow-x: auto; }
.ret-search { flex: 1 1 auto; min-width: 180px; height: 34px; }
.ret-toolbar-actions { display: flex; align-items: center; gap: 6px; flex-wrap: nowrap; flex-shrink: 0; margin-left: auto; }
.ret-toolbar .ui-btn { height: 34px; padding: 0 10px; font-size: 12.5px; white-space: nowrap; }
</style>

<script>
function openModal(id) { document.getElementById(id).classList.add('show'); }
function closeModal(id) { document.getElementById(id).classList.remove('show'); }

(function () {
    var input = document.getElementById('retSearch');
    var filter = document.getElementById('retStatusFilter');
    var rows = Array.prototype.slice.call(document.querySelectorAll('.ret-row'));
    function apply() {
        var q = input.value.trim().toLowerCase();
        var st = filter.value;
        var shown = 0;
        rows.forEach(function (r) {
            var ok = (q === '' || r.dataset.search.indexOf(q) !== -1) && (st === '' || r.dataset.status === st);
            r.style.display = ok ? '' : 'none';
            if (ok) { shown++; }
        });
        var nm = document.getElementById('retNoMatch');
        if (nm) { nm.style.display = shown === 0 ? '' : 'none'; }
        document.getElementById('retShowing').textContent = 'Showing ' + shown + ' of ' + rows.length + ' returns';
    }
    input.addEventListener('input', apply);
    filter.addEventListener('change', apply);
})();
</script>