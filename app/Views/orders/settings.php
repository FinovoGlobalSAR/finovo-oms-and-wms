<?php $currentPage = 'orders'; ?>

<div class="breadcrumb"><a href="/dashboard" class="breadcrumb-link">Finovo</a> <i class="bi bi-chevron-right"></i> <a href="/orders" class="breadcrumb-link">Orders</a> <i class="bi bi-chevron-right"></i> Settings</div>

<div class="page-header-row">
    <h1>Store Settings</h1>
</div>
<?php $platform = $settings['platform'] ?? 'manual'; ?>
<p class="page-subtitle">This store's platform is <strong><?= htmlspecialchars(ucfirst($platform)) ?></strong>. Configure its connection below.</p>

<?php if (!empty($saved)): ?>
    <div class="banner banner-success">Settings saved.</div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="banner banner-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="card" style="padding: 24px; max-width: 520px;">

    <form method="POST" action="/orders/settings">

        <?php if ($platform === 'shopify'): ?>

            <h2 style="font-size: 16px; font-weight: 600; margin: 0 0 14px;">Shopify Settings</h2>

            <div class="form-group">
                <label>Shopify Store URL</label>
                <input type="text" name="shopify_store_url" placeholder="yourstore.myshopify.com"
                       value="<?= htmlspecialchars($settings['store_url'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Admin API Access Token</label>
                <input type="text" name="shopify_access_token"
                       value="<?= htmlspecialchars($settings['access_token'] ?? '') ?>">
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

            <div style="background:#f9fafb; border-radius:8px; padding:12px; margin-top:14px; font-size:12px; color:var(--text-muted); line-height:1.6;">
                <strong>Webhook URL:</strong><br>
                <code>https://YOUR-DOMAIN/webhooks/shopify?store_id=<?= (int) $settings['id'] ?></code>
            </div>

        <?php elseif ($platform === 'woocommerce'): ?>

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
                <strong>Webhook URL:</strong><br>
                <code>https://YOUR-DOMAIN/webhooks/woocommerce?store_id=<?= (int) $settings['id'] ?></code>
            </div>

        <?php elseif ($platform === 'bigcommerce'): ?>

            <h2 style="font-size: 16px; font-weight: 600; margin: 0 0 14px;">BigCommerce Settings</h2>

            <div class="form-group">
                <label>BigCommerce Store Hash</label>
                <input type="text" name="bigcommerce_store_hash" placeholder="e.g. zej9n5vxfp"
                       value="<?= htmlspecialchars($settings['bigcommerce_store_hash'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>BigCommerce Access Token</label>
                <input type="text" name="bigcommerce_access_token"
                       value="<?= htmlspecialchars($settings['bigcommerce_access_token'] ?? '') ?>">
            </div>

            <div style="background:#f9fafb; border-radius:8px; padding:12px; margin-top:14px; font-size:12px; color:var(--text-muted); line-height:1.6;">
                <strong>Webhook URL:</strong><br>
                <code>https://YOUR-DOMAIN/webhooks/bigcommerce?store_id=<?= (int) $settings['id'] ?></code>
            </div>

        <?php elseif ($platform === 'prestashop'): ?>

            <h2 style="font-size: 16px; font-weight: 600; margin: 0 0 14px;">PrestaShop Settings</h2>

            <div class="form-group">
                <label>PrestaShop Store URL</label>
                <input type="text" name="prestashop_store_url" placeholder="http://localhost/prestashop"
                       value="<?= htmlspecialchars($settings['prestashop_store_url'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>PrestaShop API Key</label>
                <input type="text" name="prestashop_api_key"
                       value="<?= htmlspecialchars($settings['prestashop_api_key'] ?? '') ?>">
            </div>

            <p class="modal-help" style="margin-top:10px;">This key is usually generated automatically by the Finovo module installed on the PrestaShop store.</p>

        <?php elseif ($platform === 'opencart'): ?>

            <h2 style="font-size: 16px; font-weight: 600; margin: 0 0 14px;">OpenCart Settings</h2>

            <div class="form-group">
                <label>OpenCart Database Name</label>
                <input type="text" name="opencart_store_url" placeholder="e.g. opencart_test"
                       value="<?= htmlspecialchars($settings['opencart_store_url'] ?? '') ?>">
            </div>

            <p class="modal-help" style="margin-top:10px;">Finovo reads OpenCart's own MySQL database directly — no API keys needed here. Database credentials (host/user/password) are configured once in Finovo's <code>.env</code> file.</p>

        <?php elseif ($platform === 'oscommerce'): ?>

            <h2 style="font-size: 16px; font-weight: 600; margin: 0 0 14px;">osCommerce Settings</h2>

            <div class="form-group">
                <label>osCommerce Database Name</label>
                <input type="text" name="oscommerce_store_url" placeholder="e.g. oscommerce_test"
                       value="<?= htmlspecialchars($settings['oscommerce_store_url'] ?? '') ?>">
            </div>

            <p class="modal-help" style="margin-top:10px;">Finovo reads osCommerce's own MySQL database directly — no API keys needed here. Database credentials (host/user/password) are configured once in Finovo's <code>.env</code> file.</p>

        <?php elseif ($platform === 'wix'): ?>

            <h2 style="font-size: 16px; font-weight: 600; margin: 0 0 14px;">Wix Settings</h2>

            <div class="form-group">
                <label>Wix Site ID</label>
                <input type="text" name="wix_site_id" placeholder="e.g. 31713e68-9b53-4000-a708-6953a2b16712"
                       value="<?= htmlspecialchars($settings['wix_site_id'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Wix API Key</label>
                <input type="text" name="wix_api_key"
                       value="<?= htmlspecialchars($settings['wix_api_key'] ?? '') ?>">
            </div>

            <div style="background:#f9fafb; border-radius:8px; padding:12px; margin-top:14px; font-size:12px; color:var(--text-muted); line-height:1.6;">
                <strong>Webhook URL:</strong><br>
                <code>https://YOUR-DOMAIN/webhooks/wix?store_id=<?= (int) $settings['id'] ?></code>
            </div>

        <?php elseif ($platform === 'custom'): ?>

            <h2 style="font-size: 16px; font-weight: 600; margin: 0 0 14px;">Custom Bridge Settings</h2>

            <div class="form-group">
                <label>Custom Bridge URL</label>
                <input type="text" name="bridge_url" placeholder="https://customerstore.com/oms-bridge"
                       value="<?= htmlspecialchars($settings['bridge_url'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Bridge API Key</label>
                <input type="text" name="bridge_api_key"
                       value="<?= htmlspecialchars($settings['bridge_api_key'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Bridge Shared Secret</label>
                <input type="text" name="bridge_shared_secret"
                       value="<?= htmlspecialchars($settings['bridge_shared_secret'] ?? '') ?>">
            </div>

            <div style="background:#f9fafb; border-radius:8px; padding:12px; margin-top:14px; font-size:12px; color:var(--text-muted); line-height:1.6;">
                <strong>Webhook URL:</strong><br>
                <code>https://YOUR-DOMAIN/webhooks/custom-bridge?store_id=<?= (int) $settings['id'] ?></code>
            </div>

        <?php else: ?>

            <p style="font-size:13px; color:var(--text-muted); margin:0;">This store's platform is manual — no external sync settings needed. Change the platform from the Stores page to connect an external channel.</p>

        <?php endif; ?>

        <?php if ($platform !== 'manual'): ?>
            <button type="submit" class="btn-primary" style="margin-top: 16px;">Save Settings</button>
        <?php endif; ?>

    </form>

    <?php if ($platform !== 'manual'): ?>
        <hr style="margin: 24px 0; border: none; border-top: 1px solid var(--border-color);">

        <div style="display: flex; flex-direction: column; gap: 8px;">
            <?php if ($platform === 'shopify'): ?>
                <a href="/orders/sync-shopify" class="toolbar-btn" style="justify-content: center;">
                    <i class="bi bi-arrow-repeat"></i> Sync Orders from Shopify
                </a>
            <?php elseif ($platform === 'woocommerce'): ?>
                <a href="/orders/sync-woocommerce" class="toolbar-btn" style="justify-content: center;">
                    <i class="bi bi-arrow-repeat"></i> Sync Orders from WooCommerce
                </a>
            <?php elseif ($platform === 'bigcommerce'): ?>
                <a href="/orders/sync-bigcommerce" class="toolbar-btn" style="justify-content: center;">
                    <i class="bi bi-arrow-repeat"></i> Sync Orders from BigCommerce
                </a>
            <?php elseif ($platform === 'prestashop'): ?>
                <a href="/orders/sync-prestashop" class="toolbar-btn" style="justify-content: center;">
                    <i class="bi bi-arrow-repeat"></i> Sync Orders from PrestaShop
                </a>
            <?php elseif ($platform === 'opencart'): ?>
                <a href="/orders/sync-opencart" class="toolbar-btn" style="justify-content: center;">
                    <i class="bi bi-arrow-repeat"></i> Sync Orders from OpenCart
                </a>
            <?php elseif ($platform === 'oscommerce'): ?>
                <a href="/orders/sync-oscommerce" class="toolbar-btn" style="justify-content: center;">
                    <i class="bi bi-arrow-repeat"></i> Sync Orders from osCommerce
                </a>
            <?php elseif ($platform === 'wix'): ?>
                <a href="/orders/sync-wix" class="toolbar-btn" style="justify-content: center;">
                    <i class="bi bi-arrow-repeat"></i> Sync Orders from Wix
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</div>

<a href="/orders" style="display: block; margin-top: 16px; font-size: 13px; color: var(--text-muted);">&larr; Back to Orders</a>

<script>
function testShopifyConnection() {
    const storeUrl = document.querySelector('[name="shopify_store_url"]')?.value || '';
    const accessToken = document.querySelector('[name="shopify_access_token"]')?.value || '';
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
    const storeUrl = document.querySelector('[name="woocommerce_store_url"]')?.value || '';
    const consumerKey = document.querySelector('[name="woocommerce_consumer_key"]')?.value || '';
    const consumerSecret = document.querySelector('[name="woocommerce_consumer_secret"]')?.value || '';
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