<?php
/**
 * Background worker — jobs table se pending jobs uthake process karta hai.
 * Chalane ka tarika: "php worker.php" — ek baar chalke, sab pending jobs
 * process karke khatam ho jata hai (real production mein Task Scheduler se
 * har minute chalana hota, jaisa cron job).
 */
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/app/Models/JobQueue.php';
require_once __DIR__ . '/app/Models/Store.php';

$jobQueue = new JobQueue();
$storeModel = new Store();

echo "Worker started...\n";

$processedCount = 0;

while (true) {
    $job = $jobQueue->nextPending();

    if (!$job) {
        break;
    }

    $jobId = (int) $job['id'];
    $storeId = (int) $job['store_id'];
    $type = $job['type'];

    echo "Processing job #{$jobId} ({$type}) for store #{$storeId}...\n";
    $jobQueue->markProcessing($jobId);

    try {
        $store = $storeModel->find($storeId);
        if (!$store) {
            throw new Exception('Store not found.');
        }

        $result = runSyncJob($type, $store);
        $jobQueue->markCompleted($jobId, $result);
        echo "  -> Completed: {$result}\n";
    } catch (Throwable $e) {
        $jobQueue->markFailed($jobId, $e->getMessage());
        echo "  -> Failed: " . $e->getMessage() . "\n";
    }

    $processedCount++;
}

echo "Worker finished. {$processedCount} job(s) processed.\n";

/**
 * Job type ke hisab se sahi sync function chalata hai. Yahan Shopify
 * products sync wire kiya hai (pattern demo ke liye) — baaki sync types
 * isi tarah add ho sakte hain.
 */
function runSyncJob(string $type, array $store): string
{
    require_once __DIR__ . '/app/Models/Product.php';
    require_once __DIR__ . '/app/Models/Warehouse.php';
    require_once __DIR__ . '/app/Models/FieldMapping.php';

    if ($type === 'shopify_products_sync') {
        if (empty($store['store_url']) || empty($store['access_token'])) {
            throw new Exception('Shopify credentials not set.');
        }

        $productModel = new Product();
        $warehouseModel = new Warehouse();
        $fieldMappingModel = new FieldMapping();
        $defaultWarehouse = $warehouseModel->getDefault((int) $store['id']);
        $mapping = $fieldMappingModel->allForStore((int) $store['id'], 'shopify', 'product');

        $apiUrl = 'https://' . rtrim($store['store_url'], '/') . '/admin/api/2026-07/products.json?limit=50';

        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-Shopify-Access-Token: ' . $store['access_token']]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new Exception("Shopify API error (HTTP {$httpCode}).");
        }

        $data = json_decode($response, true);
        $shopifyProducts = $data['products'] ?? [];
        $db = Database::getConnection();
        $importedCount = 0;

        foreach ($shopifyProducts as $sp) {
            $externalId = (string) $sp['id'];

            $check = $db->prepare("SELECT id FROM products WHERE store_id = ? AND external_product_id = ?");
            $check->execute([$store['id'], $externalId]);
            if ($check->fetch()) {
                continue;
            }

            $name = FieldMapping::extract($sp, $mapping['name']) ?? 'Unknown product';
            $variants = $sp['variants'] ?? [];
            $price = (float) (FieldMapping::extract($sp, $mapping['price']) ?? 0);
            $sku = FieldMapping::extract($sp, $mapping['sku']);

            $stmt = $db->prepare("INSERT INTO products (store_id, name, sku, price, external_product_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$store['id'], $name, $sku, $price, $externalId]);
            $productId = (int) $db->lastInsertId();

            $stock = (int) ($variants[0]['inventory_quantity'] ?? 0);
            $productModel->setWarehouseStock($productId, $defaultWarehouse['id'], $stock);

            $importedCount++;
        }

        return "{$importedCount} product(s) synced.";
    }

    throw new Exception("Unknown job type: {$type}");
}