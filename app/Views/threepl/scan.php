<div class="ui-head" style="align-items:center;">
    <div class="ui-head-left">
        <div class="breadcrumb">Finovo <i class="bi bi-chevron-right"></i> <a href="/shipments" style="color:inherit; text-decoration:none;">Shipments</a> <i class="bi bi-chevron-right"></i> <span class="current">LM Inventory Scan</span></div>
        <h1>LM Inventory Scan</h1>
        <p>Scan AWB numbers when handing shipments over to the 3PL courier's rider.</p>
    </div>
    <div class="ui-head-right">
        <a href="/3pl/remittance" class="ui-btn"><i class="bi bi-cash-coin"></i> Go to 3PL Remittance</a>
    </div>
</div>

<?php if (!empty($scanned)): ?>
    <div class="banner banner-success"><i class="bi bi-check-circle"></i> <?= (int) $scanned ?> shipment(s) marked as handed over.</div>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <div class="banner banner-error"><i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="ui-card" style="padding:20px 22px 22px;">
    <div style="display:flex; justify-content:space-between; align-items:center; padding-bottom:16px; margin-bottom:18px; border-bottom:1px solid var(--border-soft);">
        <div class="ui-card-title">
            <div class="ui-card-title-icon"><i class="bi bi-upc-scan"></i></div>
            <div>
                <h2 style="margin:0; font-size:15px; font-weight:700;">Scan Using Scanner</h2>
                <div style="font-size:12.5px; color:#475569;">Scanner input works directly in the box below.</div>
            </div>
        </div>
        <span class="ui-tag ui-pill-blue" id="awbCount">0 AWB</span>
    </div>

    <form method="POST" action="/3pl/scan/submit" style="display:flex; flex-direction:column; gap:16px;">
        <div class="ui-field">
            <label for="awbNumbers" style="font-size:12.5px; font-weight:600; color:var(--text-body);">AWB Numbers (one per line)</label>
            <textarea id="awbNumbers" name="awb_numbers" rows="10" placeholder="Scan or paste AWB numbers here, one per line..." autofocus
                      style="width:100%; box-sizing:border-box; border:1px solid var(--border-color); border-radius:10px; padding:12px 14px; font-size:13.5px; line-height:1.7; font-family:ui-monospace, 'Cascadia Code', Consolas, monospace; background:#f8fafc; resize:vertical;"></textarea>
        </div>

        <div style="display:flex; justify-content:flex-end;">
            <button type="submit" class="ui-btn ui-btn-primary" style="height:42px; padding:0 22px; font-size:14px;">
                <i class="bi bi-upc-scan"></i> Confirm Handover
            </button>
        </div>
    </form>
</div>

<style>
#awbNumbers:focus { outline: none; border-color: #93b4f5; background: #fff; box-shadow: 0 0 0 3px rgba(29,78,216,0.12); }
</style>

<script>
(function () {
    var box = document.getElementById('awbNumbers');
    var badge = document.getElementById('awbCount');
    function count() {
        var n = box.value.split('\n').filter(function (l) { return l.trim() !== ''; }).length;
        badge.textContent = n + ' AWB';
    }
    box.addEventListener('input', count);
    count();
})();
</script>