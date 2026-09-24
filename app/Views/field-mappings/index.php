<?php $currentPage = 'field-mappings'; ?>

<div class="breadcrumb"><a href="/dashboard" class="breadcrumb-link">Finovo</a> <i class="bi bi-chevron-right"></i> Field Mapping</div>

<div class="page-header-row">
    <h1>Field Mapping</h1>
</div>
<p class="page-subtitle">
    Choose which <strong><?= htmlspecialchars(ucfirst($platform)) ?></strong> field maps to each Finovo product field. Leave blank to use the default.
</p>

<?php if (!empty($saved)): ?>
    <div class="banner banner-success">Field mapping saved. This will apply on the next sync.</div>
<?php endif; ?>

<?php if ($platform === 'manual' || $platform === 'custom'): ?>
    <div class="banner banner-error">This store's platform doesn't pull external data, so field mapping doesn't apply here.</div>
<?php else: ?>

<div class="card" style="padding: 24px; max-width: 560px;">
    <form method="POST" action="/field-mappings/save">
        <?php foreach ($availableFields as $field): ?>
            <div class="form-group">
                <label><?= ucfirst(str_replace('_', ' ', $field)) ?> (Finovo)</label>
                <input type="text" name="field_<?= $field ?>"
                       value="<?= htmlspecialchars($productMappings[$field] ?? '') ?>"
                       placeholder="e.g. <?= htmlspecialchars($productMappings[$field] ?? '') ?>">
                <p class="modal-help" style="margin-top:4px;">
                    <?= htmlspecialchars($platform) ?>'s field name for this. For nested fields, use dots — e.g. <code>variants.0.price</code>.
                </p>
            </div>
        <?php endforeach; ?>

        <button type="submit" class="btn-primary" style="margin-top: 12px;">Save Mapping</button>
    </form>
</div>

<?php endif; ?>