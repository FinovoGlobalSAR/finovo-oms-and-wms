<?php
/**
 * Sabhi Shopify/WooCommerce/BigCommerce stores ke liye webhooks register
 * karta hai — ek baar chalane se, wo platforms khud Finovo ko naye orders
 * turant bhejna shuru kar dete hain (real-time, polling ki zaroorat nahi).
 * Chalao: C:\xampp\php\php.exe register-webhooks.php
 */
require_once __DIR__ . '/core/Database.php';

$db = Database::getConnection();
$baseUrl = $_ENV['APP_URL'] ?? '';

if ($baseUrl === '') {
    die("Error: APP_URL is not set in .env file. Please set it (e.g. your live domain) before running this script.\n");
}

$stores = $db->query("SELECT * FROM stores")->fetchAll();

foreach ($stores as $store) {
    $storeId = (int) $store['id'];
    $platform = $store['platform'];

    // ---------- Shopify ----------
    if ($platform === 'shopify' && !empty($store['store_url']) && !empty($store['access_token'])) {
        $webhookUrl = $baseUrl . '/webhooks/shopify?store_id=' . $storeId;

        $payload = json_encode([
            'webhook' => [
                'topic' => 'orders/create',
                'address' => $webhookUrl,
                'format' => 'json',
            ],
        ]);

        $ch = curl_init('https://' . rtrim($store['store_url'], '/') . '/admin/api/2026-07/webhooks.json');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'X-Shopify-Access-Token: ' . $store['access_token'],
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        echo "Shopify [{$store['name']}]: HTTP {$httpCode}\n";

        // Shopify webhook secret humare app-level secret se verify hota hai (Configuration
        // tab mein "webhook secret" set hota hai) — humara stored secret wahi hona chahiye
        if (empty($store['shopify_webhook_secret']) && !empty($_ENV['SHOPIFY_CLIENT_SECRET'])) {
            $db->prepare("UPDATE stores SET shopify_webhook_secret = ? WHERE id = ?")
               ->execute([$_ENV['SHOPIFY_CLIENT_SECRET'], $storeId]);
        }
    }

    // ---------- WooCommerce ----------
    if ($platform === 'woocommerce' && !empty($store['woocommerce_store_url']) && !empty($store['woocommerce_consumer_key'])) {
        $webhookUrl = $baseUrl . '/webhooks/woocommerce?store_id=' . $storeId;
        $secret = $store['woocommerce_webhook_secret'] ?: bin2hex(random_bytes(16));

        $payload = json_encode([
            'name' => 'Finovo Order Created',
            'topic' => 'order.created',
            'delivery_url' => $webhookUrl,
            'secret' => $secret,
        ]);

        $auth = $store['woocommerce_consumer_key'] . ':' . $store['woocommerce_consumer_secret'];

        $ch = curl_init(rtrim($store['woocommerce_store_url'], '/') . '/wp-json/wc/v3/webhooks');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_USERPWD, $auth);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, curlVerifySsl());
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        echo "WooCommerce [{$store['name']}]: HTTP {$httpCode}\n";

        if (in_array($httpCode, [200, 201], true)) {
            $db->prepare("UPDATE stores SET woocommerce_webhook_secret = ? WHERE id = ?")
               ->execute([$secret, $storeId]);
        }
    }

    // ---------- BigCommerce ----------
    if ($platform === 'bigcommerce' && !empty($store['bigcommerce_store_hash']) && !empty($store['bigcommerce_access_token'])) {
        $webhookUrl = $baseUrl . '/webhooks/bigcommerce?store_id=' . $storeId;

        $payload = json_encode([
            'scope' => 'store/order/created',
            'destination' => $webhookUrl,
            'is_active' => true,
        ]);

        $ch = curl_init('https://api.bigcommerce.com/stores/' . $store['bigcommerce_store_hash'] . '/v3/hooks');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'X-Auth-Token: ' . $store['bigcommerce_access_token'],
            'Content-Type: application/json',
            'Accept: application/json',
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        echo "BigCommerce [{$store['name']}]: HTTP {$httpCode}\n";
    }

    // ---------- Wix ----------
    if ($platform === 'wix' && !empty($store['wix_site_id'])) {
        echo "Wix [{$store['name']}]: webhook must be registered manually via Wix Dashboard -> Webhooks (see README).\n";
    }
}

echo "\nDone. PrestaShop uses its own module trigger (no separate registration needed).\n";
echo "OpenCart/osCommerce don't support webhooks — they still rely on the sync buttons/queue.\n";