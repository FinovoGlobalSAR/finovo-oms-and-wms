<?php $currentPage = 'orders'; ?>

<div class="breadcrumb"><a href="/dashboard" class="breadcrumb-link">Finovo</a> <i class="bi bi-chevron-right"></i> <a href="/orders" class="breadcrumb-link">Orders</a> <i class="bi bi-chevron-right"></i> Settings</div>

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
        <div class="form-group">
            <label>Shopify Webhook Secret</label>
            <input type="text" name="shopify_webhook_secret" placeholder="From Shopify Admin → Notifications → Webhooks"
                   value="<?= htmlspecialchars($settings['shopify_webhook_secret'] ?? '') ?>">
        </div>

        <button type="button" onclick="testShopifyConnection()" class="toolbar-btn" style="margin-top:4px;">
            <i class="bi bi-plug"></i> Test Shopify Connection
        </button>
        <div id="shopifyTestResult" style="margin-top:8px; font-size:13px;"></div>

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
        <div class="form-group">
            <label>WooCommerce Webhook Secret</label>
            <input type="text" name="woocommerce_webhook_secret" placeholder="From WooCommerce → Settings → Advanced → Webhooks"
                   value="<?= htmlspecialchars($settings['woocommerce_webhook_secret'] ?? '') ?>">
        </div>

        <button type="button" onclick="testWooCommerceConnection()" class="toolbar-btn" style="margin-top:4px;">
            <i class="bi bi-plug"></i> Test WooCommerce Connection
        </button>
        <div id="woocommerceTestResult" style="margin-top:8px; font-size:13px;"></div>

        <div style="background:#f9fafb; border-radius:8px; padding:12px; margin-top:14px; font-size:12px; color:var(--text-muted); line-height:1.6;">
            <strong>Webhook URLs (this store's ID: <?= (int) $settings['id'] ?>):</strong><br>
            Shopify: <code>https://YOUR-DOMAIN/webhooks/shopify?store_id=<?= (int) $settings['id'] ?></code><br>
            WooCommerce: <code>https://YOUR-DOMAIN/webhooks/woocommerce?store_id=<?= (int) $settings['id'] ?></code>
        </div>

        <button type="submit" class="btn-primary" style="margin-top: 16px;">Save Settings</button>

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

<script>
function testShopifyConnection() {
    const storeUrl = document.querySelector('[name="shopify_store_url"]').value;
    const accessToken = document.querySelector('[name="shopify_access_token"]').value;
    const resultDiv = document.getElementById('shopifyTestResult');

    resultDiv.innerHTML = '<span style="color:#6b7280;">Testing...</span>';

    fetch('/orders/test-shopify-connection', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'store_url=' + encodeURIComponent(storeUrl) + '&access_token=' + encodeURIComponent(accessToken)
    })
    .then(res => res.json())
    .then(data => {
        resultDiv.innerHTML = data.ok
            ? '<span style="color:#15803d;"><i class="bi bi-check-circle"></i> ' + data.message + '</span>'
            : '<span style="color:#991b1b;"><i class="bi bi-x-circle"></i> ' + data.message + '</span>';
    })
    .catch(() => {
        resultDiv.innerHTML = '<span style="color:#991b1b;">Test failed — network error.</span>';
    });
}

function testWooCommerceConnection() {
    const storeUrl = document.querySelector('[name="woocommerce_store_url"]').value;
    const consumerKey = document.querySelector('[name="woocommerce_consumer_key"]').value;
    const consumerSecret = document.querySelector('[name="woocommerce_consumer_secret"]').value;
    const resultDiv = document.getElementById('woocommerceTestResult');

    resultDiv.innerHTML = '<span style="color:#6b7280;">Testing...</span>';

    fetch('/orders/test-woocommerce-connection', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'store_url=' + encodeURIComponent(storeUrl) + '&consumer_key=' + encodeURIComponent(consumerKey) + '&consumer_secret=' + encodeURIComponent(consumerSecret)
    })
    .then(res => res.json())
    .then(data => {
        resultDiv.innerHTML = data.ok
            ? '<span style="color:#15803d;"><i class="bi bi-check-circle"></i> ' + data.message + '</span>'
            : '<span style="color:#991b1b;"><i class="bi bi-x-circle"></i> ' + data.message + '</span>';
    })
    .catch(() => {
        resultDiv.innerHTML = '<span style="color:#991b1b;">Test failed — network error.</span>';
    });
}
</script>