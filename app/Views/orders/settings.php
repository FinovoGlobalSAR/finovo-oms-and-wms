<?php $currentPage = 'orders'; ?>

<div class="breadcrumb">Finovo <i class="bi bi-chevron-right"></i> Orders <i class="bi bi-chevron-right"></i> Settings</div>

<div class="page-header-row">
    <h1>Store Settings</h1>
</div>
<p class="page-subtitle">Connect Shopify and WooCommerce to pull and push orders/products.</p>

<?php if (!empty($saved)): ?>
    <div class="banner banner-success">Settings saved.</div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="banner banner-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="card" style="padding: 24px; max-width: 520px;">

    <form method="POST" action="/orders/settings">

        <h2 style="font-size: 16px; font-weight: 600; margin: 0 0 14px;">Shopify Settings</h2>

        <div class="form-group">
            <label>Shopify Store URL</label>
            <input type="text" name="shopify_store_url" placeholder="yourstore.myshopify.com"
                   value="<?= htmlspecialchars($settings['shopify_store_url'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label>Admin API Access Token</label>
            <input type="text" name="shopify_access_token"
                   value="<?= htmlspecialchars($settings['shopify_access_token'] ?? '') ?>">
        </div>

        <hr style="margin: 24px 0; border: none; border-top: 1px solid var(--border-color);">

        <h2 style="font-size: 16px; font-weight: 600; margin: 0 0 14px;">WooCommerce Settings</h2>

        <div class="form-group">
            <label>WooCommerce Store URL</label>
            <input type="text" name="woocommerce_store_url" placeholder="https://yourstore.com"
                   value="<?= htmlspecialchars($settings['woocommerce_store_url'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label>Consumer Key</label>
            <input type="text" name="woocommerce_consumer_key" placeholder="ck_..."
                   value="<?= htmlspecialchars($settings['woocommerce_consumer_key'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label>Consumer Secret</label>
            <input type="text" name="woocommerce_consumer_secret" placeholder="cs_..."
                   value="<?= htmlspecialchars($settings['woocommerce_consumer_secret'] ?? '') ?>">
        </div>

        <button type="submit" class="btn-primary" style="margin-top: 10px;">Save Settings</button>

    </form>

    <hr style="margin: 24px 0; border: none; border-top: 1px solid var(--border-color);">

    <div style="display: flex; flex-direction: column; gap: 8px;">
        <a href="/orders/sync-shopify" class="toolbar-btn" style="justify-content: center;">
            <i class="bi bi-arrow-repeat"></i> Sync Orders from Shopify
        </a>
        <a href="/orders/sync-woocommerce" class="toolbar-btn" style="justify-content: center;">
            <i class="bi bi-arrow-repeat"></i> Sync Orders from WooCommerce
        </a>
    </div>

</div>

<a href="/orders" style="display: block; margin-top: 16px; font-size: 13px; color: var(--text-muted);">&larr; Back to Orders</a>