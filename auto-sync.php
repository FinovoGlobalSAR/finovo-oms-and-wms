<?php

require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/app/Models/JobQueue.php';

$jobQueue = new JobQueue();
$db = Database::getConnection();

// Platform naam -> job types (worker.php / StoreSyncRunner ke job names se match hona chahiye)
$platformJobs = [
    'shopify'     => ['shopify_products_sync', 'shopify_orders_sync'],
    'woocommerce' => ['woocommerce_products_sync', 'woocommerce_orders_sync'],
    'bigcommerce' => ['bigcommerce_products_sync', 'bigcommerce_orders_sync'],
    'prestashop'  => ['prestashop_products_sync', 'prestashop_orders_sync'],
    'opencart'    => ['opencart_products_sync', 'opencart_orders_sync'],
    'oscommerce'  => ['oscommerce_products_sync', 'oscommerce_orders_sync'],
    'wix'         => ['wix_products_sync', 'wix_orders_sync'],
    'ebay'        => ['ebay_products_sync', 'ebay_orders_sync'],
    'magento'     => ['magento_products_sync', 'magento_orders_sync'],
    'custom'      => ['custom_products_sync'],
];

$stores = $db->query("SELECT id, platform, cj_api_key FROM stores")->fetchAll();
$queuedCount = 0;

$existingStmt = $db->prepare(
    "SELECT id FROM jobs WHERE store_id = ? AND type = ? AND status IN ('pending', 'processing')"
);

foreach ($stores as $store) {
    $types = $platformJobs[$store['platform']] ?? [];

    // CJdropshipping: jis store se CJ ko orders bheje jate hain, un ka status/tracking bhi update karo
    if ($store['platform'] === 'cj' || !empty($store['cj_api_key'])) {
        $types[] = 'cj_orders_status_sync';
    }

    foreach ($types as $type) {
        $existingStmt->execute([(int) $store['id'], $type]);
        if ($existingStmt->fetch()) {
            continue;
        }

        $jobQueue->push($type, (int) $store['id']);
        $queuedCount++;
    }
}

echo "{$queuedCount} sync job(s) queued.\n";