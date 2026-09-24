<div class="breadcrumb">Finovo <i class="bi bi-chevron-right"></i> 3PL <i class="bi bi-chevron-right"></i> LM Inventory Scan</div>

<div class="page-header-row">
    <h1>LM Inventory Scan</h1>
</div>
<p class="page-subtitle">Scan AWB numbers when handing shipments over to the 3PL courier's rider.</p>

<?php if (!empty($scanned)): ?>
    <div class="banner banner-success"><?= (int) $scanned ?> shipment(s) marked as handed over.</div>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <div class="banner banner-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="card" style="padding: 24px; max-width: 560px;">
    <h2 style="font-size: 16px; font-weight: 600; margin: 0 0 14px;">Scan Using Scanner</h2>

    <form method="POST" action="/3pl/scan/submit">
        <div class="form-group">
            <label>AWB Numbers (one per line — scanner input works directly here)</label>
            <textarea name="awb_numbers" rows="6" placeholder="Scan or paste AWB numbers here, one per line..." style="width:100%; border:1px solid var(--border-color); border-radius:8px; padding:10px; font-size:13px; font-family:'Inter',sans-serif;" autofocus></textarea>
        </div>

        <button type="submit" class="btn-primary">
            <i class="bi bi-upc-scan"></i> Confirm Handover
        </button>
    </form>

    <p class="modal-help" style="margin-top:14px;">
        Each AWB found will be marked <strong>"Handed Over"</strong> and its shipment status will move to <strong>Dispatched</strong>.
        AWBs not found in the system will be listed as errors above.
    </p>
</div>