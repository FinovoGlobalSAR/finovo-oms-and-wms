<?php
$currentPage = 'field-mappings';
$isExternal = !($platform === 'manual' || $platform === 'custom');
$platformName = ucfirst($platform);
$fieldCount = count($availableFields);
$filledCount = 0;
foreach ($availableFields as $f) {
    if (($productMappings[$f] ?? '') !== '') { $filledCount++; }
}
?>

<div class="ui-head" style="align-items:center;">
    <div class="ui-head-left">
        <div class="breadcrumb">Finovo <i class="bi bi-chevron-right"></i> Integration Settings <i class="bi bi-chevron-right"></i> <span class="current">Field Mapping</span></div>
        <h1>Field Mapping</h1>
        <p>Choose which <strong><?= htmlspecialchars($platformName) ?></strong> field maps to each Finovo product field. Leave blank to use the default.</p>
    </div>
    <div class="ui-head-right">
        <?php if (!$isExternal): ?>
            <a href="/stores" class="ui-btn"><i class="bi bi-shop"></i> Go to Stores</a>
        <?php endif; ?>
        <a href="/sku-mappings" class="ui-btn"><i class="bi bi-link-45deg"></i> SKU Mappings</a>
    </div>
</div>

<?php if (!empty($saved)): ?>
    <div class="banner banner-success"><i class="bi bi-check-circle"></i> Field mapping saved. This will apply on the next sync.</div>
<?php endif; ?>

<div class="ui-stats">
    <div class="ui-stat">
        <div class="ui-stat-icon"><i class="bi bi-plug"></i></div>
        <div>
            <div class="ui-stat-label">Platform</div>
            <div class="ui-stat-value"><b><?= htmlspecialchars($platformName) ?></b>
                <span class="ui-pill <?= $isExternal ? 'ui-pill-green' : 'ui-pill-amber' ?>"><?= $isExternal ? 'External' : 'No sync' ?></span>
            </div>
        </div>
    </div>
    <div class="ui-stat">
        <div class="ui-stat-icon"><i class="bi bi-sliders"></i></div>
        <div>
            <div class="ui-stat-label">Product Fields</div>
            <div class="ui-stat-value"><b><?= $fieldCount ?></b><span class="ui-pill ui-pill-blue">Fields</span></div>
        </div>
    </div>
    <div class="ui-stat">
        <div class="ui-stat-icon"><i class="bi bi-check2-square"></i></div>
        <div>
            <div class="ui-stat-label">Mapped</div>
            <div class="ui-stat-value"><b><?= $isExternal ? $filledCount : 0 ?></b>
                <span class="ui-pill <?= ($isExternal && $filledCount === $fieldCount) ? 'ui-pill-green' : 'ui-pill-amber' ?>">of <?= $fieldCount ?></span>
            </div>
        </div>
    </div>
    <div class="ui-stat">
        <div class="ui-stat-icon"><i class="bi bi-arrow-repeat"></i></div>
        <div>
            <div class="ui-stat-label">Applies On</div>
            <div class="ui-stat-value"><b>Next Sync</b></div>
        </div>
    </div>
</div>

<?php if (!$isExternal): ?>

    <div class="ui-card">
        <div class="ui-empty" style="padding:48px 20px;">
            <i class="bi bi-sliders"></i>
            Field mapping doesn't apply to <?= htmlspecialchars($platformName) ?> stores — there's no external data to map.
        </div>
    </div>

<?php else: ?>

<div class="ui-card">
    <div class="ui-card-head" style="padding-bottom:16px; border-bottom:1px solid var(--border-soft); align-items:center;">
        <div class="ui-card-title">
            <div class="ui-card-title-icon"><i class="bi bi-sliders"></i></div>
            <div>
                <h2>Product Fields</h2>
                <div class="sub">Finovo field on the left, <?= htmlspecialchars($platformName) ?> field on the right. Use dots for nested fields, e.g. <code>variants.0.price</code>.</div>
            </div>
        </div>
        <button type="submit" form="fmForm" class="ui-btn ui-btn-primary"><i class="bi bi-check2-circle"></i> Save Mapping</button>
    </div>

    <form method="POST" action="/field-mappings/save" id="fmForm">
        <div class="fm-head">
            <span>Finovo field</span>
            <span></span>
            <span><?= htmlspecialchars($platformName) ?> field</span>
        </div>
        <?php foreach ($availableFields as $field): ?>
            <div class="fm-row">
                <label for="fm_<?= $field ?>" class="fm-label">
                    <i class="bi bi-box-seam"></i> <?= ucfirst(str_replace('_', ' ', $field)) ?>
                </label>
                <i class="bi bi-arrow-left-right fm-arrow" aria-hidden="true"></i>
                <input type="text" id="fm_<?= $field ?>" name="field_<?= $field ?>"
                       value="<?= htmlspecialchars($productMappings[$field] ?? '') ?>"
                       placeholder="e.g. <?= htmlspecialchars($productMappings[$field] ?? '') ?>"
                       class="fm-input">
            </div>
        <?php endforeach; ?>
    </form>
</div>

<style>
.fm-head { display: grid; grid-template-columns: 220px 24px minmax(0, 560px); gap: 12px; padding: 12px 20px; background: #f8fafc; border-bottom: 1px solid var(--border-color); font-size: 11.5px; font-weight: 600; letter-spacing: 0.04em; text-transform: uppercase; color: #475569; }
.fm-row { display: grid; grid-template-columns: 220px 24px minmax(0, 560px); gap: 12px; align-items: center; padding: 12px 20px; border-bottom: 1px solid var(--border-soft); }
.fm-row:last-child { border-bottom: none; }
.fm-label { display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 600; color: var(--text-dark); }
.fm-label i { color: var(--primary); }
.fm-arrow { color: #94a3b8; text-align: center; }
.fm-input { height: 38px; border: 1px solid var(--border-color); border-radius: 8px; padding: 0 12px; font-size: 13px; font-family: ui-monospace, 'Cascadia Code', Consolas, monospace; background: #f8fafc; width: 100%; box-sizing: border-box; }
.fm-input:focus { outline: none; border-color: #93b4f5; background: #fff; box-shadow: 0 0 0 3px rgba(29,78,216,0.12); }
@media (max-width: 700px) {
    .fm-head { display: none; }
    .fm-row { grid-template-columns: minmax(0, 1fr); gap: 6px; }
    .fm-arrow { display: none; }
}
</style>

<?php endif; ?>