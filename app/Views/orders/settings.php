<?php $pageTitle = 'Shopify Settings — Finovo OMS/WMS'; require __DIR__ . '/../partials/layout_start.php'; ?>

<div class="max-w-lg mx-auto p-8">
    <a href="/orders" class="text-sm text-ink/60 hover:underline">&larr; Back to Orders</a>
    <h1 class="text-2xl font-bold mt-2 mb-6">Shopify Settings</h1>

    <?php if (!empty($saved)): ?>
        <div class="border border-ink px-4 py-2 rounded mb-4 text-sm">Settings saved.</div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="border border-ink px-4 py-2 rounded mb-4 text-sm font-medium"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="/orders/settings" class="space-y-4 mb-6">
        <div>
            <label class="block text-sm font-medium mb-1">Shopify Store URL</label>
            <input type="text" name="shopify_store_url" placeholder="finovotest2026.myshopify.com"
                   value="<?= htmlspecialchars($settings['shopify_store_url'] ?? '') ?>"
                   class="w-full border border-ink/40 rounded px-3 py-2">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Admin API Access Token</label>
            <input type="text" name="shopify_access_token"
                   value="<?= htmlspecialchars($settings['shopify_access_token'] ?? '') ?>"
                   class="w-full border border-ink/40 rounded px-3 py-2">
        </div>
        <button type="submit" class="bg-ink text-canvas px-4 py-2 rounded hover:opacity-80">
            Save Settings
        </button>
    </form>

    <a href="/orders/sync-shopify" class="block text-center bg-canvas border border-ink px-4 py-2 rounded hover:opacity-80">
        Sync Orders from Shopify
    </a>
</div>

<?php require __DIR__ . '/../partials/layout_end.php'; ?>