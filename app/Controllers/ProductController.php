<?php
// app/Controllers/ProductController.php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../Models/Product.php';
require_once __DIR__ . '/../Models/Store.php';
require_once __DIR__ . '/../Models/Warehouse.php';
require_once __DIR__ . '/../Models/ProductVariant.php';
require_once __DIR__ . '/../Models/FieldMapping.php';
require_once __DIR__ . '/../Models/JobQueue.php';
require_once __DIR__ . '/../Services/IntegrationErrorService.php';

class ProductController extends Controller
{
    private Product $productModel;
    private Store $storeModel;
    private Warehouse $warehouseModel;
    private ProductVariant $variantModel;
    private IntegrationErrorService $errorService;
    private FieldMapping $fieldMappingModel;

    public function __construct()
    {
        parent::__construct();
        $this->requireRole(['admin', 'manager', 'warehouse staff']);
        $this->requireStoreContext();
        $this->productModel = new Product();
        $this->storeModel = new Store();
        $this->warehouseModel = new Warehouse();
        $this->variantModel = new ProductVariant();
        $this->errorService = new IntegrationErrorService();
        $this->fieldMappingModel = new FieldMapping();
    }

    // OpenCart/osCommerce ke external MySQL databases se connect karne ke
    // liye — .env se credentials leta hai, taaki live server pe sirf .env
    // badalna pade, code kahin change na karna pade.
    private function externalDbConnection(string $dbName): PDO
    {
        $host = $_ENV['DB_EXTERNAL_HOST'] ?? 'localhost';
        $user = $_ENV['DB_EXTERNAL_USER'] ?? 'root';
        $password = $_ENV['DB_EXTERNAL_PASSWORD'] ?? '';

        $pdo = new PDO("mysql:host={$host};dbname={$dbName};charset=utf8mb4", $user, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    }

    public function index(): void
    {
        $store = $this->getCurrentStore();
        $products = $this->productModel->all($store['id']);

        $productIds = array_map(fn($p) => (int) $p['id'], $products);
        $variantCounts = $this->productModel->variantCountsForProducts($productIds);

        foreach ($products as &$p) {
            $variantCount = $variantCounts[(int) $p['id']] ?? 0;
            $p['variant_count'] = $variantCount;

            if ($variantCount > 0) {
                $p['variants'] = $this->variantModel->allByProduct((int) $p['id']);
                $prices = array_column($p['variants'], 'price');
                $p['display_price'] = !empty($prices) ? min($prices) : $p['price'];
            } else {
                $p['variants'] = [];
                $p['display_price'] = $p['price'];
            }
        }
        unset($p);

        $lowStockCount = 0;
        foreach ($products as $p) {
            $threshold = (int) ($p['low_stock_threshold'] ?? 5);
            if ((int) ($p['stock_quantity'] ?? 0) <= $threshold) {
                $lowStockCount++;
            }
        }

        $this->view('products/index', [
            'products' => $products,
            'store' => $store,
            'lowStockCount' => $lowStockCount,
            'synced' => $_GET['synced'] ?? null,
            'exported' => $_GET['exported'] ?? null,
            'imported' => $_GET['imported'] ?? null,
            'stockUpdated' => $_GET['stock_updated'] ?? null,
            'error' => $_GET['error'] ?? null,
        ]);
    }

    public function showImportForm(): void
    {
        $this->redirect('/products');
    }

    public function handleImport(): void
    {
        $store = $this->getCurrentStore();
        $defaultWarehouse = $this->warehouseModel->getDefault($store['id']);

        if (empty($_FILES['csv_file']['tmp_name'])) {
            $this->redirect('/products?error=' . urlencode('Please choose a CSV file.'));
            return;
        }

        $handle = fopen($_FILES['csv_file']['tmp_name'], 'r');
        if (!$handle) {
            $this->redirect('/products?error=' . urlencode('Could not read the file.'));
            return;
        }

        fgetcsv($handle);
        $importedCount = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 3) {
                continue;
            }

            $name = trim($row[0]);
            $sku = trim($row[1]);
            $price = (float) trim($row[2]);
            $stock = isset($row[3]) ? (int) trim($row[3]) : 0;

            if ($name === '' || $price <= 0) {
                continue;
            }

            $productId = $this->productModel->create($store['id'], $name, $price, $sku ?: null, 0);
            if ($stock > 0) {
                $this->productModel->setWarehouseStock($productId, $defaultWarehouse['id'], $stock);
            }
            $importedCount++;
        }

        fclose($handle);
        $this->redirect('/products?imported=' . $importedCount);
    }

    public function updateStock(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        $stock = (int) ($_POST['stock_quantity'] ?? 0);

        if ($id > 0) {
            $product = $this->productModel->find($id);
            if ($product && !$this->productModel->hasVariants($id)) {
                $defaultWarehouse = $this->warehouseModel->getDefault((int) $product['store_id']);
                $this->productModel->setWarehouseStock($id, $defaultWarehouse['id'], $stock);
            }
        }

        $this->redirect('/products?stock_updated=1');
    }

    public function editForm(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        $product = $this->productModel->find($id);

        if (!$product) {
            echo "Product not found.";
            exit;
        }

        $this->view('products/edit', [
            'product' => $product,
            'error' => $_GET['error'] ?? null,
        ]);
    }

    public function update(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $sku = trim($_POST['sku'] ?? '');
        $price = (float) ($_POST['price'] ?? 0);
        $threshold = (int) ($_POST['low_stock_threshold'] ?? 5);

        if ($id <= 0 || $name === '' || $price <= 0) {
            $this->redirect('/products/edit?id=' . $id . '&error=' . urlencode('Please fill in all fields correctly.'));
            return;
        }

        $this->productModel->update($id, $name, $sku ?: null, $price, $threshold);
        $this->redirect('/products?stock_updated=1');
    }

    public function delete(): void
    {
        $id = (int) ($_POST['id'] ?? 0);

        if ($id > 0) {
            $this->productModel->delete($id);
        }

        $this->redirect('/products?deleted=1');
    }

    public function exportCsv(): void
    {
        $store = $this->getCurrentStore();
        $products = $this->productModel->all($store['id']);

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="products.csv"');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['name', 'sku', 'price', 'stock']);
        foreach ($products as $p) {
            fputcsv($out, [$p['name'], $p['sku'], $p['price'], $p['stock_quantity'] ?? 0]);
        }
        fclose($out);
        exit;
    }

    // ---------- Shopify: Products Pull ----------

    public function syncShopify(): void
    {
        $db = Database::getConnection();
        $store = $this->getCurrentStore();
        $defaultWarehouse = $this->warehouseModel->getDefault($store['id']);

        if (empty($store['store_url']) || empty($store['access_token'])) {
            $this->redirect('/products?error=' . urlencode('Please save this store\'s Shopify Settings (Store URL and Access Token) first.'));
            return;
        }

        $mapping = $this->fieldMappingModel->allForStore((int) $store['id'], 'shopify', 'product');

        $apiUrl = 'https://' . rtrim($store['store_url'], '/') . '/admin/api/2026-07/products.json?limit=50';

        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-Shopify-Access-Token: ' . $store['access_token']]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            $this->errorService->logFailure((int) $store['id'], 'shopify', 'sync_products', null, null, $httpCode, $response ?: 'No response');
            $this->storeModel->markUnhealthy((int) $store['id'], "Shopify API error (HTTP {$httpCode})");
            $this->redirect('/products?error=' . urlencode("Shopify API error (HTTP {$httpCode})."));
            return;
        }

        $data = json_decode($response, true);
        $shopifyProducts = $data['products'] ?? [];
        $importedCount = 0;

        foreach ($shopifyProducts as $sp) {
            $externalId = (string) $sp['id'];
            $imageUrl = $sp['image']['src'] ?? null;
            $variants = $sp['variants'] ?? [];

            $check = $db->prepare("SELECT id FROM products WHERE store_id = ? AND external_product_id = ?");
            $check->execute([$store['id'], $externalId]);
            $existingProduct = $check->fetch();

            $isRealVariantProduct = count($variants) > 1 || (isset($variants[0]['title']) && $variants[0]['title'] !== 'Default Title');

            if ($existingProduct) {
                $productId = (int) $existingProduct['id'];
                if ($imageUrl) {
                    $this->productModel->updateImage($productId, $imageUrl);
                }
            } else {
                $name = FieldMapping::extract($sp, $mapping['name']) ?? 'Unknown product';
                $price = (float) (FieldMapping::extract($sp, $mapping['price']) ?? 0);
                $sku = FieldMapping::extract($sp, $mapping['sku']);

                $stmt = $db->prepare(
                    "INSERT INTO products (store_id, name, sku, price, external_product_id, image_url) VALUES (?, ?, ?, ?, ?, ?)"
                );
                $stmt->execute([$store['id'], $name, $sku, $price, $externalId, $imageUrl]);
                $productId = (int) $db->lastInsertId();
                $importedCount++;
            }

            if ($isRealVariantProduct) {
                $optionNames = array_column($sp['options'] ?? [], 'name');

                foreach ($variants as $variant) {
                    $labelParts = array_filter([$variant['option1'] ?? null, $variant['option2'] ?? null, $variant['option3'] ?? null]);
                    $label = implode(' / ', $labelParts) ?: ($variant['title'] ?? 'Variant');
                    $attributes = !empty($optionNames) ? implode(', ', $optionNames) : null;

                    $variantId = $this->variantModel->findOrCreate(
                        $productId,
                        $label,
                        $attributes,
                        $variant['sku'] ?? null,
                        (float) ($variant['price'] ?? 0),
                        $imageUrl,
                        (string) $variant['id']
                    );

                    $stock = (int) ($variant['inventory_quantity'] ?? 0);
                    $this->variantModel->setWarehouseStock($variantId, $defaultWarehouse['id'], $stock);
                }
            } else {
                $stock = (int) ($variants[0]['inventory_quantity'] ?? 0);
                if (!$existingProduct) {
                    $this->productModel->setWarehouseStock($productId, $defaultWarehouse['id'], $stock);
                }
            }
        }

        $this->storeModel->markHealthy((int) $store['id']);
        $this->redirect('/products?synced=' . $importedCount);
    }

    // ---------- Shopify: Async Sync (Queue-Based) ----------

    public function syncShopifyAsync(): void
    {
        $store = $this->getCurrentStore();

        $jobQueue = new JobQueue();
        $jobId = $jobQueue->push('shopify_products_sync', (int) $store['id']);

        $this->redirect('/products?queued=' . $jobId);
    }

    // ---------- Shopify: Product Push ----------

    public function exportToShopify(): void
    {
        $db = Database::getConnection();
        $store = $this->getCurrentStore();
        $id = (int) ($_GET['id'] ?? 0);

        if (empty($store['store_url']) || empty($store['access_token'])) {
            $this->redirect('/products?error=' . urlencode('Please save this store\'s Shopify Settings first.'));
            return;
        }

        $stmt = $db->prepare("SELECT * FROM products WHERE id = ? AND store_id = ?");
        $stmt->execute([$id, $store['id']]);
        $product = $stmt->fetch();

        if (!$product) {
            $this->redirect('/products?error=' . urlencode('Product not found.'));
            return;
        }

        $apiUrl = 'https://' . rtrim($store['store_url'], '/') . '/admin/api/2026-07/products.json';

        $payload = json_encode([
            'product' => [
                'title' => $product['name'],
                'variants' => [
                    ['price' => (string) $product['price'], 'sku' => $product['sku']],
                ],
            ],
        ]);

        $ch = curl_init($apiUrl);
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

        if ($httpCode !== 201) {
            $this->errorService->logFailure((int) $store['id'], 'shopify', 'export_product', null, (string) $id, $httpCode, $response ?: 'No response');
            $this->storeModel->markUnhealthy((int) $store['id'], "Shopify export failed (HTTP {$httpCode})");
            $this->redirect('/products?error=' . urlencode("Shopify export failed (HTTP {$httpCode})."));
            return;
        }

        $this->errorService->markResolved((int) $store['id'], 'shopify', 'export_product', (string) $id);
        $this->storeModel->markHealthy((int) $store['id']);

        $data = json_decode($response, true);
        $externalId = (string) ($data['product']['id'] ?? '');

        if ($externalId) {
            $update = $db->prepare("UPDATE products SET external_product_id = ? WHERE id = ?");
            $update->execute([$externalId, $id]);
        }

        $this->redirect('/products?exported=1');
    }

    // ---------- WooCommerce: Products Pull ----------

    public function syncWooCommerce(): void
    {
        $db = Database::getConnection();
        $store = $this->getCurrentStore();
        $defaultWarehouse = $this->warehouseModel->getDefault($store['id']);

        if (empty($store['woocommerce_store_url']) || empty($store['woocommerce_consumer_key']) || empty($store['woocommerce_consumer_secret'])) {
            $this->redirect('/products?error=' . urlencode('Please save this store\'s WooCommerce Settings first (Orders -> Settings).'));
            return;
        }

        $baseUrl = rtrim($store['woocommerce_store_url'], '/');
        $auth = $store['woocommerce_consumer_key'] . ':' . $store['woocommerce_consumer_secret'];

        $fixImageProtocol = function (?string $url) use ($baseUrl): ?string {
            if (!$url) {
                return null;
            }
            $baseScheme = parse_url($baseUrl, PHP_URL_SCHEME) ?: 'http';
            $urlScheme = parse_url($url, PHP_URL_SCHEME);
            if ($urlScheme && $urlScheme !== $baseScheme) {
                $url = preg_replace('#^https?://#', $baseScheme . '://', $url);
            }
            return $url;
        };

        $apiUrl = $baseUrl . '/wp-json/wc/v3/products?per_page=50';

        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $auth);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            $this->errorService->logFailure((int) $store['id'], 'woocommerce', 'sync_products', null, null, $httpCode, $response ?: 'No response');
            $this->storeModel->markUnhealthy((int) $store['id'], "WooCommerce API error (HTTP {$httpCode})");
            $this->redirect('/products?error=' . urlencode("WooCommerce API error (HTTP {$httpCode}): " . $response));
            return;
        }

        $wcProducts = json_decode($response, true) ?? [];
        $importedCount = 0;

        foreach ($wcProducts as $wp) {
            $externalId = (string) $wp['id'];
            $imageUrl = $fixImageProtocol($wp['images'][0]['src'] ?? null);

            $check = $db->prepare("SELECT id FROM products WHERE store_id = ? AND external_wc_product_id = ?");
            $check->execute([$store['id'], $externalId]);
            $existingProduct = $check->fetch();

            if ($existingProduct) {
                $productId = (int) $existingProduct['id'];
                if ($imageUrl) {
                    $this->productModel->updateImage($productId, $imageUrl);
                }
            } else {
                $name = $wp['name'] ?? 'Unknown product';
                $price = (float) ($wp['price'] ?? 0);
                $sku = $wp['sku'] ?? null;

                $stmt = $db->prepare(
                    "INSERT INTO products (store_id, name, sku, price, external_wc_product_id, image_url) VALUES (?, ?, ?, ?, ?, ?)"
                );
                $stmt->execute([$store['id'], $name, $sku, $price, $externalId, $imageUrl]);
                $productId = (int) $db->lastInsertId();
                $importedCount++;
            }

            if (($wp['type'] ?? 'simple') === 'variable') {
                $variationsUrl = $baseUrl . "/wp-json/wc/v3/products/{$wp['id']}/variations?per_page=50";
                $vch = curl_init($variationsUrl);
                curl_setopt($vch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($vch, CURLOPT_USERPWD, $auth);
                curl_setopt($vch, CURLOPT_TIMEOUT, 15);
                curl_setopt($vch, CURLOPT_SSL_VERIFYPEER, false);
                $vResponse = curl_exec($vch);
                curl_close($vch);

                $variations = json_decode($vResponse, true) ?? [];

                foreach ($variations as $variation) {
                    $attrs = $variation['attributes'] ?? [];
                    $labelParts = array_column($attrs, 'option');
                    $label = implode(' / ', $labelParts) ?: 'Variant';
                    $attributeNames = implode(', ', array_column($attrs, 'name'));
                    $variantImage = $fixImageProtocol($variation['image']['src'] ?? null) ?? $imageUrl;

                    $variantId = $this->variantModel->findOrCreate(
                        $productId,
                        $label,
                        $attributeNames ?: null,
                        $variation['sku'] ?? null,
                        (float) ($variation['price'] ?? 0),
                        $variantImage,
                        null,
                        (string) $variation['id']
                    );

                    $stock = (int) ($variation['stock_quantity'] ?? 0);
                    $this->variantModel->setWarehouseStock($variantId, $defaultWarehouse['id'], $stock);
                }
            } elseif (!$existingProduct) {
                $stock = (int) ($wp['stock_quantity'] ?? 0);
                $this->productModel->setWarehouseStock($productId, $defaultWarehouse['id'], $stock);
            }
        }

        $this->storeModel->markHealthy((int) $store['id']);
        $this->redirect('/products?synced=' . $importedCount);
    }

    // ---------- Custom Bridge: Products Pull ----------

    public function syncCustomBridge(): void
    {
        $store = $this->getCurrentStore();
        $defaultWarehouse = $this->warehouseModel->getDefault($store['id']);

        if (empty($store['bridge_url']) || empty($store['bridge_api_key']) || empty($store['bridge_shared_secret'])) {
            $this->redirect('/products?error=' . urlencode('Please save this store\'s Custom Bridge credentials first.'));
            return;
        }

        require_once __DIR__ . '/../../app/Connectors/BridgeConnector.php';
        $connector = new BridgeConnector($store['bridge_url'], $store['bridge_api_key'], $store['bridge_shared_secret']);

        $testResult = $connector->testConnection();

        if (!$testResult['ok']) {
            $this->errorService->logFailure((int) $store['id'], 'custom_bridge', 'sync_products', null, null, 0, $testResult['message']);
            $this->storeModel->markUnhealthy((int) $store['id'], $testResult['message']);
            $this->redirect('/products?error=' . urlencode($testResult['message']));
            return;
        }

        $products = $connector->fetchProducts();
        $db = Database::getConnection();
        $importedCount = 0;

        foreach ($products as $p) {
            $externalId = (string) ($p['id'] ?? '');
            if ($externalId === '') continue;

            $check = $db->prepare("SELECT id FROM products WHERE store_id = ? AND external_product_id = ?");
            $check->execute([$store['id'], $externalId]);
            $existing = $check->fetch();

            if (!$existing) {
                $name = $p['name'] ?? 'Unknown product';
                $price = (float) ($p['price'] ?? 0);
                $sku = $p['sku'] ?? null;

                $stmt = $db->prepare("INSERT INTO products (store_id, name, sku, price, external_product_id) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$store['id'], $name, $sku, $price, $externalId]);
                $productId = (int) $db->lastInsertId();
                $importedCount++;

                $stock = (int) ($p['stock_quantity'] ?? 0);
                $this->productModel->setWarehouseStock($productId, $defaultWarehouse['id'], $stock);
            }
        }

        $this->errorService->markResolved((int) $store['id'], 'custom_bridge', 'sync_products', null);
        $this->storeModel->markHealthy((int) $store['id']);
        $this->redirect('/products?synced=' . $importedCount);
    }

    // ---------- BigCommerce: Products Pull ----------

    public function syncBigCommerce(): void
    {
        $store = $this->getCurrentStore();
        $defaultWarehouse = $this->warehouseModel->getDefault($store['id']);

        if (empty($store['bigcommerce_store_hash']) || empty($store['bigcommerce_access_token'])) {
            $this->redirect('/products?error=' . urlencode('Please save this store\'s BigCommerce Store Hash and Access Token first.'));
            return;
        }

        $apiUrl = 'https://api.bigcommerce.com/stores/' . $store['bigcommerce_store_hash'] . '/v3/catalog/products?limit=50';

        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'X-Auth-Token: ' . $store['bigcommerce_access_token'],
            'Accept: application/json',
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            $this->errorService->logFailure((int) $store['id'], 'bigcommerce', 'sync_products', null, null, $httpCode, $response ?: 'No response');
            $this->storeModel->markUnhealthy((int) $store['id'], "BigCommerce API error (HTTP {$httpCode})");
            $this->redirect('/products?error=' . urlencode("BigCommerce API error (HTTP {$httpCode})."));
            return;
        }

        $data = json_decode($response, true);
        $bcProducts = $data['data'] ?? [];
        $db = Database::getConnection();
        $importedCount = 0;

        foreach ($bcProducts as $bp) {
            $externalId = (string) ($bp['id'] ?? '');
            if ($externalId === '') continue;

            $check = $db->prepare("SELECT id FROM products WHERE store_id = ? AND external_bc_product_id = ?");
            $check->execute([$store['id'], $externalId]);
            $existing = $check->fetch();

            if (!$existing) {
                $name = $bp['name'] ?? 'Unknown product';
                $price = (float) ($bp['price'] ?? 0);
                $sku = $bp['sku'] ?? null;

                $stmt = $db->prepare("INSERT INTO products (store_id, name, sku, price, external_bc_product_id) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$store['id'], $name, $sku, $price, $externalId]);
                $productId = (int) $db->lastInsertId();
                $importedCount++;

                $stock = (int) ($bp['inventory_level'] ?? 0);
                $this->productModel->setWarehouseStock($productId, $defaultWarehouse['id'], $stock);
            }
        }

        $this->errorService->markResolved((int) $store['id'], 'bigcommerce', 'sync_products', null);
        $this->storeModel->markHealthy((int) $store['id']);
        $this->redirect('/products?synced=' . $importedCount);
    }

    // ---------- PrestaShop: Products Pull ----------

    public function syncPrestaShop(): void
    {
        $store = $this->getCurrentStore();
        $defaultWarehouse = $this->warehouseModel->getDefault($store['id']);

        if (empty($store['prestashop_store_url']) || empty($store['prestashop_api_key'])) {
            $this->redirect('/products?error=' . urlencode('Please save this store\'s PrestaShop Store URL and API Key first.'));
            return;
        }

        $baseUrl = rtrim($store['prestashop_store_url'], '/');
        $apiUrl = $baseUrl . '/api/products?ws_key=' . urlencode($store['prestashop_api_key']) . '&output_format=JSON&display=[id,name,price,reference,active]&filter[active]=1';

        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            $this->errorService->logFailure((int) $store['id'], 'prestashop', 'sync_products', null, null, $httpCode, $response ?: 'No response');
            $this->storeModel->markUnhealthy((int) $store['id'], "PrestaShop API error (HTTP {$httpCode})");
            $this->redirect('/products?error=' . urlencode("PrestaShop API error (HTTP {$httpCode})."));
            return;
        }

        $data = json_decode($response, true);
        $psProducts = $data['products'] ?? [];
        $db = Database::getConnection();
        $importedCount = 0;

        foreach ($psProducts as $pp) {
            $externalId = (string) ($pp['id'] ?? '');
            if ($externalId === '') continue;

            $nameRaw = $pp['name'] ?? 'Unknown product';
            $name = is_array($nameRaw) ? ($nameRaw[0]['value'] ?? 'Unknown product') : $nameRaw;

            $check = $db->prepare("SELECT id FROM products WHERE store_id = ? AND external_ps_product_id = ?");
            $check->execute([$store['id'], $externalId]);
            $existing = $check->fetch();

            if (!$existing) {
                $price = (float) ($pp['price'] ?? 0);
                $sku = $pp['reference'] ?? null;

                $stmt = $db->prepare("INSERT INTO products (store_id, name, sku, price, external_ps_product_id) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$store['id'], $name, $sku, $price, $externalId]);
                $productId = (int) $db->lastInsertId();
                $importedCount++;

                $stockUrl = $baseUrl . '/api/stock_availables?ws_key=' . urlencode($store['prestashop_api_key']) . '&output_format=JSON&filter[id_product]=' . $externalId . '&display=[quantity]';
                $sch = curl_init($stockUrl);
                curl_setopt($sch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($sch, CURLOPT_TIMEOUT, 10);
                curl_setopt($sch, CURLOPT_SSL_VERIFYPEER, false);
                $stockResponse = curl_exec($sch);
                curl_close($sch);

                $stockData = json_decode($stockResponse, true);
                $stockList = $stockData['stock_availables'] ?? [];
                $stock = (int) ($stockList[0]['quantity'] ?? 0);

                $this->productModel->setWarehouseStock($productId, $defaultWarehouse['id'], $stock);
            }
        }

        $this->errorService->markResolved((int) $store['id'], 'prestashop', 'sync_products', null);
        $this->storeModel->markHealthy((int) $store['id']);
        $this->redirect('/products?synced=' . $importedCount);
    }

    // ---------- OpenCart: Products Pull (direct DB read) ----------

    public function syncOpenCart(): void
    {
        $store = $this->getCurrentStore();
        $defaultWarehouse = $this->warehouseModel->getDefault($store['id']);

        $ocDbName = $store['opencart_store_url'] ?? '';
        if ($ocDbName === '') {
            $this->redirect('/products?error=' . urlencode('Please save this store\'s OpenCart database name first.'));
            return;
        }

        try {
            $ocDb = $this->externalDbConnection($ocDbName);
        } catch (PDOException $e) {
            $this->errorService->logFailure((int) $store['id'], 'opencart', 'sync_products', null, null, 0, $e->getMessage());
            $this->storeModel->markUnhealthy((int) $store['id'], 'Could not connect to OpenCart database.');
            $this->redirect('/products?error=' . urlencode('Could not connect to OpenCart database: ' . $e->getMessage()));
            return;
        }

        $stmt = $ocDb->query(
            "SELECT p.product_id, p.model, p.price, p.quantity, pd.name
             FROM oc_product p
             INNER JOIN oc_product_description pd ON pd.product_id = p.product_id
             WHERE p.status = 1"
        );
        $ocProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $db = Database::getConnection();
        $importedCount = 0;

        foreach ($ocProducts as $op) {
            $externalId = (string) $op['product_id'];

            $check = $db->prepare("SELECT id FROM products WHERE store_id = ? AND external_ocart_product_id = ?");
            $check->execute([$store['id'], $externalId]);
            $existing = $check->fetch();

            if (!$existing) {
                $name = $op['name'] ?? 'Unknown product';
                $price = (float) ($op['price'] ?? 0);
                $sku = $op['model'] ?? null;

                $insert = $db->prepare("INSERT INTO products (store_id, name, sku, price, external_ocart_product_id) VALUES (?, ?, ?, ?, ?)");
                $insert->execute([$store['id'], $name, $sku, $price, $externalId]);
                $productId = (int) $db->lastInsertId();
                $importedCount++;

                $stock = (int) ($op['quantity'] ?? 0);
                $this->productModel->setWarehouseStock($productId, $defaultWarehouse['id'], $stock);
            }
        }

        $this->errorService->markResolved((int) $store['id'], 'opencart', 'sync_products', null);
        $this->storeModel->markHealthy((int) $store['id']);
        $this->redirect('/products?synced=' . $importedCount);
    }

    // ---------- osCommerce: Products Pull (direct DB read) ----------

    public function syncOsCommerce(): void
    {
        $store = $this->getCurrentStore();
        $defaultWarehouse = $this->warehouseModel->getDefault($store['id']);

        $oscDbName = $store['oscommerce_store_url'] ?? '';
        if ($oscDbName === '') {
            $this->redirect('/products?error=' . urlencode('Please save this store\'s osCommerce database name first.'));
            return;
        }

        try {
            $oscDb = $this->externalDbConnection($oscDbName);
        } catch (PDOException $e) {
            $this->errorService->logFailure((int) $store['id'], 'oscommerce', 'sync_products', null, null, 0, $e->getMessage());
            $this->storeModel->markUnhealthy((int) $store['id'], 'Could not connect to osCommerce database.');
            $this->redirect('/products?error=' . urlencode('Could not connect to osCommerce database: ' . $e->getMessage()));
            return;
        }

        $stmt = $oscDb->query(
            "SELECT p.products_id, p.products_model, p.products_price, p.products_quantity, pd.products_name
             FROM products p
             INNER JOIN products_description pd ON pd.products_id = p.products_id AND pd.language_id = 1
             WHERE p.products_status = 1"
        );
        $oscProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $db = Database::getConnection();
        $importedCount = 0;

        foreach ($oscProducts as $op) {
            $externalId = (string) $op['products_id'];

            $check = $db->prepare("SELECT id FROM products WHERE store_id = ? AND external_osc_product_id = ?");
            $check->execute([$store['id'], $externalId]);
            $existing = $check->fetch();

            if (!$existing) {
                $name = $op['products_name'] ?? 'Unknown product';
                $price = (float) ($op['products_price'] ?? 0);
                $sku = $op['products_model'] ?? null;

                $insert = $db->prepare("INSERT INTO products (store_id, name, sku, price, external_osc_product_id) VALUES (?, ?, ?, ?, ?)");
                $insert->execute([$store['id'], $name, $sku, $price, $externalId]);
                $productId = (int) $db->lastInsertId();
                $importedCount++;

                $stock = (int) ($op['products_quantity'] ?? 0);
                $this->productModel->setWarehouseStock($productId, $defaultWarehouse['id'], $stock);
            }
        }

        $this->errorService->markResolved((int) $store['id'], 'oscommerce', 'sync_products', null);
        $this->storeModel->markHealthy((int) $store['id']);
        $this->redirect('/products?synced=' . $importedCount);
    }

    // ---------- WooCommerce: Product Push ----------

    public function exportToWooCommerce(): void
    {
        $db = Database::getConnection();
        $store = $this->getCurrentStore();
        $id = (int) ($_GET['id'] ?? 0);

        if (empty($store['woocommerce_store_url']) || empty($store['woocommerce_consumer_key']) || empty($store['woocommerce_consumer_secret'])) {
            $this->redirect('/products?error=' . urlencode('Please save this store\'s WooCommerce Settings first.'));
            return;
        }

        $stmt = $db->prepare("SELECT * FROM products WHERE id = ? AND store_id = ?");
        $stmt->execute([$id, $store['id']]);
        $product = $stmt->fetch();

        if (!$product) {
            $this->redirect('/products?error=' . urlencode('Product not found.'));
            return;
        }

        $auth = $store['woocommerce_consumer_key'] . ':' . $store['woocommerce_consumer_secret'];
        $apiUrl = rtrim($store['woocommerce_store_url'], '/') . '/wp-json/wc/v3/products';

        $payload = json_encode([
            'name' => $product['name'],
            'type' => 'simple',
            'regular_price' => (string) $product['price'],
            'sku' => $product['sku'] ?: '',
            'manage_stock' => true,
            'stock_quantity' => (int) ($product['stock_quantity'] ?? 0),
        ]);

        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_USERPWD, $auth);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 201) {
            $this->errorService->logFailure((int) $store['id'], 'woocommerce', 'export_product', null, (string) $id, $httpCode, $response ?: 'No response');
            $this->storeModel->markUnhealthy((int) $store['id'], "WooCommerce export failed (HTTP {$httpCode})");
            $this->redirect('/products?error=' . urlencode("WooCommerce export failed (HTTP {$httpCode}): " . $response));
            return;
        }

        $this->errorService->markResolved((int) $store['id'], 'woocommerce', 'export_product', (string) $id);
        $this->storeModel->markHealthy((int) $store['id']);

        $data = json_decode($response, true);
        $externalId = (string) ($data['id'] ?? '');

        if ($externalId) {
            $update = $db->prepare("UPDATE products SET external_wc_product_id = ? WHERE id = ?");
            $update->execute([$externalId, $id]);
        }

        $this->redirect('/products?exported=1');
    }
}