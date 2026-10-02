<?php

require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/app/Models/JobQueue.php';
require_once __DIR__ . '/app/Models/Store.php';
require_once __DIR__ . '/app/Models/Product.php';
require_once __DIR__ . '/app/Models/Warehouse.php';
require_once __DIR__ . '/app/Models/FieldMapping.php';
require_once __DIR__ . '/app/Services/StoreSyncRunner.php';

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

function externalDbConnection(string $dbName): PDO
{
    $host = $_ENV['DB_EXTERNAL_HOST'] ?? 'localhost';
    $user = $_ENV['DB_EXTERNAL_USER'] ?? 'root';
    $password = $_ENV['DB_EXTERNAL_PASSWORD'] ?? '';

    $pdo = new PDO("mysql:host={$host};dbname={$dbName};charset=utf8mb4", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $pdo;
}

function runSyncJob(string $type, array $store): string
{
    // Naye jobs (orders sync, Magento, eBay, Wix, CJ wagera) — wahi controller function chalao jo "Sync" button chalata hai
    if (StoreSyncRunner::handles($type)) {
        return StoreSyncRunner::run($type, $store);
    }

    $productModel = new Product();
    $warehouseModel = new Warehouse();
    $fieldMappingModel = new FieldMapping();
    $defaultWarehouse = $warehouseModel->getDefault((int) $store['id']);
    $db = Database::getConnection();

    // ---------- Shopify ----------
    if ($type === 'shopify_products_sync') {
        if (empty($store['store_url']) || empty($store['access_token'])) {
            throw new Exception('Shopify credentials not set.');
        }

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
        $importedCount = 0;

        foreach (($data['products'] ?? []) as $sp) {
            $externalId = (string) $sp['id'];
            $check = $db->prepare("SELECT id FROM products WHERE store_id = ? AND external_product_id = ?");
            $check->execute([$store['id'], $externalId]);
            if ($check->fetch()) continue;

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

    // ---------- WooCommerce ----------
    if ($type === 'woocommerce_products_sync') {
        if (empty($store['woocommerce_store_url']) || empty($store['woocommerce_consumer_key']) || empty($store['woocommerce_consumer_secret'])) {
            throw new Exception('WooCommerce credentials not set.');
        }

        $baseUrl = rtrim($store['woocommerce_store_url'], '/');
        $auth = $store['woocommerce_consumer_key'] . ':' . $store['woocommerce_consumer_secret'];
        $apiUrl = $baseUrl . '/wp-json/wc/v3/products?per_page=50';

        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $auth);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, curlVerifySsl());
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new Exception("WooCommerce API error (HTTP {$httpCode}).");
        }

        $wcProducts = json_decode($response, true) ?? [];
        $importedCount = 0;

        foreach ($wcProducts as $wp) {
            $externalId = (string) $wp['id'];
            $check = $db->prepare("SELECT id FROM products WHERE store_id = ? AND external_wc_product_id = ?");
            $check->execute([$store['id'], $externalId]);
            if ($check->fetch()) continue;

            $name = $wp['name'] ?? 'Unknown product';
            $price = (float) ($wp['price'] ?? 0);
            $sku = $wp['sku'] ?? null;

            $stmt = $db->prepare("INSERT INTO products (store_id, name, sku, price, external_wc_product_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$store['id'], $name, $sku, $price, $externalId]);
            $productId = (int) $db->lastInsertId();

            $stock = (int) ($wp['stock_quantity'] ?? 0);
            $productModel->setWarehouseStock($productId, $defaultWarehouse['id'], $stock);
            $importedCount++;
        }

        return "{$importedCount} product(s) synced.";
    }

    // ---------- BigCommerce ----------
    if ($type === 'bigcommerce_products_sync') {
        if (empty($store['bigcommerce_store_hash']) || empty($store['bigcommerce_access_token'])) {
            throw new Exception('BigCommerce credentials not set.');
        }

        $apiUrl = 'https://api.bigcommerce.com/stores/' . $store['bigcommerce_store_hash'] . '/v3/catalog/products?limit=50';

        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-Auth-Token: ' . $store['bigcommerce_access_token'], 'Accept: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new Exception("BigCommerce API error (HTTP {$httpCode}).");
        }

        $data = json_decode($response, true);
        $importedCount = 0;

        foreach (($data['data'] ?? []) as $bp) {
            $externalId = (string) ($bp['id'] ?? '');
            if ($externalId === '') continue;

            $check = $db->prepare("SELECT id FROM products WHERE store_id = ? AND external_bc_product_id = ?");
            $check->execute([$store['id'], $externalId]);
            if ($check->fetch()) continue;

            $name = $bp['name'] ?? 'Unknown product';
            $price = (float) ($bp['price'] ?? 0);
            $sku = $bp['sku'] ?? null;

            $stmt = $db->prepare("INSERT INTO products (store_id, name, sku, price, external_bc_product_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$store['id'], $name, $sku, $price, $externalId]);
            $productId = (int) $db->lastInsertId();

            $stock = (int) ($bp['inventory_level'] ?? 0);
            $productModel->setWarehouseStock($productId, $defaultWarehouse['id'], $stock);
            $importedCount++;
        }

        return "{$importedCount} product(s) synced.";
    }

    // ---------- PrestaShop ----------
    if ($type === 'prestashop_products_sync') {
        if (empty($store['prestashop_store_url']) || empty($store['prestashop_api_key'])) {
            throw new Exception('PrestaShop credentials not set.');
        }

        $baseUrl = rtrim($store['prestashop_store_url'], '/');
        $apiUrl = $baseUrl . '/api/products?ws_key=' . urlencode($store['prestashop_api_key']) . '&output_format=JSON&display=[id,name,price,reference,active]&filter[active]=1';

        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, curlVerifySsl());
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new Exception("PrestaShop API error (HTTP {$httpCode}).");
        }

        $data = json_decode($response, true);
        $importedCount = 0;

        foreach (($data['products'] ?? []) as $pp) {
            $externalId = (string) ($pp['id'] ?? '');
            if ($externalId === '') continue;

            $check = $db->prepare("SELECT id FROM products WHERE store_id = ? AND external_ps_product_id = ?");
            $check->execute([$store['id'], $externalId]);
            if ($check->fetch()) continue;

            $nameRaw = $pp['name'] ?? 'Unknown product';
            $name = is_array($nameRaw) ? ($nameRaw[0]['value'] ?? 'Unknown product') : $nameRaw;
            $price = (float) ($pp['price'] ?? 0);
            $sku = $pp['reference'] ?? null;

            $stmt = $db->prepare("INSERT INTO products (store_id, name, sku, price, external_ps_product_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$store['id'], $name, $sku, $price, $externalId]);
            $productId = (int) $db->lastInsertId();

            $stockUrl = $baseUrl . '/api/stock_availables?ws_key=' . urlencode($store['prestashop_api_key']) . '&output_format=JSON&filter[id_product]=' . $externalId . '&display=[quantity]';
            $sch = curl_init($stockUrl);
            curl_setopt($sch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($sch, CURLOPT_TIMEOUT, 10);
            curl_setopt($sch, CURLOPT_SSL_VERIFYPEER, curlVerifySsl());
            $stockResponse = curl_exec($sch);
            curl_close($sch);

            $stockData = json_decode($stockResponse, true);
            $stock = (int) ($stockData['stock_availables'][0]['quantity'] ?? 0);
            $productModel->setWarehouseStock($productId, $defaultWarehouse['id'], $stock);
            $importedCount++;
        }

        return "{$importedCount} product(s) synced.";
    }

    // ---------- OpenCart ----------
    if ($type === 'opencart_products_sync') {
        $ocDbName = $store['opencart_store_url'] ?? '';
        if ($ocDbName === '') {
            throw new Exception('OpenCart database name not set.');
        }

        $ocDb = externalDbConnection($ocDbName);
        $stmt = $ocDb->query(
            "SELECT p.product_id, p.model, p.price, p.quantity, pd.name
             FROM oc_product p
             INNER JOIN oc_product_description pd ON pd.product_id = p.product_id
             WHERE p.status = 1"
        );
        $ocProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $importedCount = 0;

        foreach ($ocProducts as $op) {
            $externalId = (string) $op['product_id'];
            $check = $db->prepare("SELECT id FROM products WHERE store_id = ? AND external_ocart_product_id = ?");
            $check->execute([$store['id'], $externalId]);
            if ($check->fetch()) continue;

            $name = $op['name'] ?? 'Unknown product';
            $price = (float) ($op['price'] ?? 0);
            $sku = $op['model'] ?? null;

            $insert = $db->prepare("INSERT INTO products (store_id, name, sku, price, external_ocart_product_id) VALUES (?, ?, ?, ?, ?)");
            $insert->execute([$store['id'], $name, $sku, $price, $externalId]);
            $productId = (int) $db->lastInsertId();

            $stock = (int) ($op['quantity'] ?? 0);
            $productModel->setWarehouseStock($productId, $defaultWarehouse['id'], $stock);
            $importedCount++;
        }

        return "{$importedCount} product(s) synced.";
    }

    // ---------- osCommerce ----------
    if ($type === 'oscommerce_products_sync') {
        $oscDbName = $store['oscommerce_store_url'] ?? '';
        if ($oscDbName === '') {
            throw new Exception('osCommerce database name not set.');
        }

        $oscDb = externalDbConnection($oscDbName);
        $stmt = $oscDb->query(
            "SELECT p.products_id, p.products_model, p.products_price, p.products_quantity, pd.products_name
             FROM products p
             INNER JOIN products_description pd ON pd.products_id = p.products_id AND pd.language_id = 1
             WHERE p.products_status = 1"
        );
        $oscProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $importedCount = 0;

        foreach ($oscProducts as $op) {
            $externalId = (string) $op['products_id'];
            $check = $db->prepare("SELECT id FROM products WHERE store_id = ? AND external_osc_product_id = ?");
            $check->execute([$store['id'], $externalId]);
            if ($check->fetch()) continue;

            $name = $op['products_name'] ?? 'Unknown product';
            $price = (float) ($op['products_price'] ?? 0);
            $sku = $op['products_model'] ?? null;

            $insert = $db->prepare("INSERT INTO products (store_id, name, sku, price, external_osc_product_id) VALUES (?, ?, ?, ?, ?)");
            $insert->execute([$store['id'], $name, $sku, $price, $externalId]);
            $productId = (int) $db->lastInsertId();

            $stock = (int) ($op['products_quantity'] ?? 0);
            $productModel->setWarehouseStock($productId, $defaultWarehouse['id'], $stock);
            $importedCount++;
        }

        return "{$importedCount} product(s) synced.";
    }

    throw new Exception("Unknown job type: {$type}");
}