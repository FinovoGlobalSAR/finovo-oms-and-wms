<?php
// ---------- Summary numbers (sirf display ke liye, $shipments se hi) ----------
$remittedCount = 0;
$pendingAmount = 0.0;
$remittedAmount = 0.0;
foreach ($shipments as $s) {
    if ($s['remittance_status'] === 'remitted') {
        $remittedCount++;
        $remittedAmount += (float) ($s['remittance_amount'] ?? 0);
    } else {
        $pendingAmount += (float) $s['order_total'];
    }
}
$notRemittedCount = count($shipments) - $remittedCount;
?>

<div class="ui-head">
    <div class="ui-head-left">
        <div class="breadcrumb">Finovo <i class="bi bi-chevron-right"></i> <a href="/shipments" style="color:inherit; text-decoration:none;">Shipments</a> <i class="bi bi-chevron-right"></i> <span class="current">3PL Remittance</span></div>
        <h1>3PL Remittance <span class="count-badge"><?= count($shipments) ?></span></h1>
        <p>Track COD payments received back from 3PL courier companies.</p>
    </div>
</div>

<?php if (!empty($updated)): ?>
    <div class="banner banner-success"><i class="bi bi-check-circle"></i> Remittance updated.</div>
<?php endif; ?>

<div class="ui-stats">
    <div class="ui-stat">
        <div class="ui-stat-icon"><i class="bi bi-truck"></i></div>
        <div>
            <div class="ui-stat-label">Handed Over</div>
            <div class="ui-stat-value"><b><?= count($shipments) ?></b><span class="ui-pill ui-pill-blue">Shipments</span></div>
        </div>
    </div>
    <div class="ui-stat">
        <div class="ui-stat-icon"><i class="bi bi-check2-circle"></i></div>
        <div>
            <div class="ui-stat-label">Remitted</div>
            <div class="ui-stat-value"><b><?= $remittedCount ?></b><span class="ui-pill ui-pill-green">Received</span></div>
        </div>
    </div>
    <div class="ui-stat">
        <div class="ui-stat-icon"><i class="bi bi-hourglass-split"></i></div>
        <div>
            <div class="ui-stat-label">Not Remitted</div>
            <div class="ui-stat-value"><b><?= $notRemittedCount ?></b>
                <span class="ui-pill <?= $notRemittedCount > 0 ? 'ui-pill-amber' : 'ui-pill-green' ?>"><?= $notRemittedCount > 0 ? 'Follow up' : 'All clear' ?></span>
            </div>
        </div>
    </div>
    <div class="ui-stat">
        <div class="ui-stat-icon"><i class="bi bi-cash-stack"></i></div>
        <div style="min-width:0;">
            <div class="ui-stat-label">COD Still Due</div>
            <div class="ui-stat-value"><b style="font-size:20px;">Rs. <?= number_format($pendingAmount, 2) ?></b></div>
        </div>
    </div>
</div>

<div class="ui-card">
    <div class="rem-toolbar">
        <label class="ui-search rem-search">
            <i class="bi bi-search"></i>
            <input type="text" id="remSearch" placeholder="Search AWB, courier or customer..." aria-label="Search remittance">
        </label>
        <select id="remFilter" class="ui-btn" aria-label="Filter by remittance status" style="padding-right:10px;">
            <option value="">All</option>
            <option value="not_remitted">Not Remitted</option>
            <option value="remitted">Remitted</option>
        </select>
        <div style="margin-left:auto; flex-shrink:0; font-size:12.5px; color:#475569; white-space:nowrap;">
            Received so far: <b style="color:#166534;">Rs. <?= number_format($remittedAmount, 2) ?></b>
        </div>
    </div>

    <div style="overflow-x:auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>AWB No.</th>
                    <th>Courier</th>
                    <th>Customer</th>
                    <th>Order Total</th>
                    <th>Status</th>
                    <th style="width:300px;">Update Remittance</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($shipments)): ?>
                    <tr><td colspan="6" class="ui-empty"><i class="bi bi-cash-coin"></i>No handed-over shipments yet. Use LM Inventory Scan to hand parcels to the courier.</td></tr>
                <?php else: ?>
                    <?php foreach ($shipments as $s): ?>
                        <tr class="rem-row" data-status="<?= htmlspecialchars($s['remittance_status']) ?>" data-search="<?= htmlspecialchars(strtolower($s['tracking_number'] . ' ' . $s['courier_name'] . ' ' . ($s['customer_name'] ?? ''))) ?>">
                            <td style="font-weight:700; font-family:ui-monospace, 'Cascadia Code', Consolas, monospace; font-size:12.5px;"><?= htmlspecialchars($s['tracking_number']) ?></td>
                            <td style="color:#334155;"><?= htmlspecialchars($s['courier_name']) ?></td>
                            <td style="color:#334155; font-weight:500;"><?= htmlspecialchars($s['customer_name'] ?? '-') ?></td>
                            <td style="white-space:nowrap; font-weight:600;">Rs. <?= number_format((float) $s['order_total'], 2) ?></td>
                            <td>
                                <?php if ($s['remittance_status'] === 'remitted'): ?>
                                    <span class="ui-dot" style="color:#166534;">Remitted</span>
                                <?php else: ?>
                                    <span class="ui-dot" style="color:#b91c1c;">Not Remitted</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="POST" action="/3pl/remittance/update" style="display:flex; gap:6px; margin:0;">
                                    <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                    <select name="remittance_status" class="ui-select" aria-label="Remittance status">
                                        <option value="not_remitted" <?= $s['remittance_status'] === 'not_remitted' ? 'selected' : '' ?>>Not Remitted</option>
                                        <option value="remitted" <?= $s['remittance_status'] === 'remitted' ? 'selected' : '' ?>>Remitted</option>
                                    </select>
                                    <input type="number" name="remittance_amount" step="0.01" placeholder="Amount" aria-label="Remitted amount"
                                           value="<?= $s['remittance_amount'] !== null ? htmlspecialchars($s['remittance_amount']) : '' ?>"
                                           style="width:100px; height:32px; border:1px solid var(--border-color); border-radius:7px; padding:0 10px; font-size:12.5px; font-family:inherit; background:#f8fafc;">
                                    <button type="submit" class="ui-btn ui-btn-soft ui-btn-icon" title="Save" aria-label="Save remittance"><i class="bi bi-check-lg"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <tr id="remNoMatch" style="display:none;"><td colspan="6" class="ui-empty"><i class="bi bi-search"></i>No shipments match your search.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="pagination-bar">
        <span id="remShowing">Showing <?= count($shipments) ?> of <?= count($shipments) ?> shipments</span>
    </div>
</div>

<style>
.rem-toolbar { display: flex; align-items: center; gap: 8px; flex-wrap: nowrap; padding: 14px 20px; border-bottom: 1px solid var(--border-color); overflow-x: auto; }
.rem-search { flex: 0 1 360px; min-width: 180px; height: 34px; }
.rem-toolbar .ui-btn { height: 34px; padding: 0 10px; font-size: 12.5px; white-space: nowrap; }
</style>

<script>
(function () {
    var input = document.getElementById('remSearch');
    var filter = document.getElementById('remFilter');
    var rows = Array.prototype.slice.call(document.querySelectorAll('.rem-row'));
    function apply() {
        var q = input.value.trim().toLowerCase();
        var st = filter.value;
        var shown = 0;
        rows.forEach(function (r) {
            var ok = (q === '' || r.dataset.search.indexOf(q) !== -1) && (st === '' || r.dataset.status === st);
            r.style.display = ok ? '' : 'none';
            if (ok) { shown++; }
        });
        var nm = document.getElementById('remNoMatch');
        if (nm) { nm.style.display = shown === 0 ? '' : 'none'; }
        document.getElementById('remShowing').textContent = 'Showing ' + shown + ' of ' + rows.length + ' shipments';
    }
    input.addEventListener('input', apply);
    filter.addEventListener('change', apply);
})();
</script>