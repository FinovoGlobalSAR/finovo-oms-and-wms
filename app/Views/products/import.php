<?php $pageTitle = 'Import Products (CSV) — Finovo OMS/WMS'; require __DIR__ . '/../partials/layout_start.php'; ?>

<div class="max-w-lg mx-auto p-8">
    <a href="/products" class="text-sm text-ink/60 hover:underline">&larr; Back to Products</a>
    <h1 class="text-2xl font-bold mt-2 mb-2">Import Products (CSV)</h1>
    <p class="text-sm text-ink/60 mb-6">
        CSV columns must be in this order: <strong>name, sku, price</strong>
        (first row is treated as the header and skipped).
    </p>

    <?php if ($imported !== null): ?>
        <div class="border border-ink px-4 py-2 rounded mb-4 text-sm">
            <?= (int) $imported ?> product(s) imported successfully.
        </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="border border-ink px-4 py-2 rounded mb-4 text-sm font-medium"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="/products/import" enctype="multipart/form-data" class="space-y-4">
        <div>
            <label class="block text-sm font-medium mb-1">CSV File</label>
            <input type="file" name="csv_file" accept=".csv" required
                   class="w-full border border-ink/40 rounded px-3 py-2">
        </div>
        <button type="submit" class="w-full bg-ink text-canvas py-2 rounded hover:opacity-80">
            Import
        </button>
    </form>
</div>

<?php require __DIR__ . '/../partials/layout_end.php'; ?>