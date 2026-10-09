<?php
// app/Controllers/ProductController.php
require_once __DIR__ . '/../../core/Paginator.php';
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../Models/Product.php';
require_once __DIR__ . '/../Models/Store.php';
require_once __DIR__ . '/../Models/Warehouse.php';
require_once __DIR__ . '/../Models/ProductVariant.php';
require_once __DIR__ . '/../Models/FieldMapping.php';
require_once __DIR__ . '/../Models/JobQueue.php';
require_once __DIR__ . '/../Connectors/WixConnector.php';
require_once __DIR__ . '/../Connectors/EbayConnector.php';
require_once __DIR__ . '/../Connectors/CjDropshippingConnector.php';
require_once __DIR__ . '/../Services/IntegrationErrorService.php';
require_once __DIR__ . '/../Services/WooCommerceAuth.php';
require_once __DIR__ . '/../Services/ProductPushService.php';
require_once __DIR__ . '/../Services/PlatformFetcher.php';
require_once __DIR__ . '/../Services/PlatformStock.php';
require_once __DIR__ . '/../Services/StockPushService.php';

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
        $allProducts = $this->productModel->all($store['id']);

        // Low stock ginti POORI list se (sirf is page se nahi)
        $lowStockCount = 0;
        foreach ($allProducts as $p) {
            if ((int) ($p['stock_quantity'] ?? 0) <= (int) ($p['low_stock_threshold'] ?? 5)) {
                $lowStockCount++;
            }
        }

        // Search (naam / SKU) aur stock filter — server pe, taake har page sahi ho
        $search = trim((string) ($_GET['q'] ?? ''));
        $stockFilter = (string) ($_GET['stock'] ?? '');
        $products = array_filter($allProducts, function ($p) use ($search, $stockFilter) {
            if ($search !== '') {
                $hay = strtolower(($p['name'] ?? '') . ' ' . ($p['sku'] ?? ''));
                if (!str_contains($hay, strtolower($search))) {
                    return false;
                }
            }
            $qty = (int) ($p['stock_quantity'] ?? 0);
            if ($stockFilter === 'low' && $qty > (int) ($p['low_stock_threshold'] ?? 5)) {
                return false;
            }
            if ($stockFilter === 'out' && $qty > 0) {
                return false;
            }
            return true;
        });

        $pager = Paginator::make($products);
        $products = $pager['items'];

        // Variants sirf is page ke products ke (pehle har product ke liye query chalti thi)
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

        $this->view('products/index', [
            'products' => $products,
            'pager' => $pager,
            'totalProducts' => count($allProducts),
            'search' => $search,
            'stockFilter' => $stockFilter,
            'store' => $store,
            'lowStockCount' => $lowStockCount,
            'synced' => $_GET['synced'] ?? null,
            'stockSynced' => $_GET['stock_synced'] ?? 0,
            'untracked' => $_GET['untracked'] ?? 0,
            'exported' => $_GET['exported'] ?? null,
            'pushNote' => $_GET['push_note'] ?? null,
            // "Push to ..." button store ke platform ke hisaab se (null = is platform pe push nahi hota)
            'pushTarget' => ProductPushService::PLATFORMS[$store['platform'] ?? 'manual'] ?? null,
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

    // =====================================================================
    //  Product sync — saare pages, aur har sync pe STOCK bhi platform se
    // =====================================================================

    /** Is sync mein kitne products ka stock platform pe gina hi nahi jata (WooCommerce "Manage stock" off waghera) */
    private int $untrackedCount = 0;

    /**
     * Platform ka stock Finovo mein lagao — naye AUR purane dono products pe.
     * (Pehle stock sirf pehli baar import pe aata tha; dobara sync pe nahi badalta tha,
     *  is liye pehli baar 0 aaya to product hamesha "out of stock" rehta tha.)
     * $qty = null → platform is product ka stock ginta hi nahi: Finovo ka stock nahi chhedte.
     */
    private function applyStock(int $productId, int $warehouseId, ?int $qty): void
    {
        if ($qty === null) {
            $this->untrackedCount++;
            return;
        }
        // Yeh stock platform se hi aaya hai — wapas platform pe bhejne ki zaroorat nahi
        StockPushService::pause();
        try {
            $this->productModel->applyPlatformStock($productId, $warehouseId, $qty);
        } finally {
            StockPushService::resume();
        }
    }

    private function applyVariantStock(int $variantId, int $warehouseId, ?int $qty): void
    {
        if ($qty === null) {
            $this->untrackedCount++;
            return;
        }
        StockPushService::pause();
        try {
            $this->variantModel->applyPlatformStock($variantId, $warehouseId, $qty);
        } finally {
            StockPushService::resume();
        }
    }

    /** /products?synced=5 — aur jin ka stock platform pe track nahi hota un ki ginti bhi */
    private function syncedRedirect(int $importedCount, int $updatedCount = 0): void
    {
        $url = '/products?synced=' . $importedCount . '&stock_synced=' . $updatedCount;
        if ($this->untrackedCount > 0) {
            $url .= '&untracked=' . $this->untrackedCount;
        }
        $this->redirect($url);
    }

    /** Store ka product jis ka platform ID yeh ho (na ho to null) */
    private function findByExternalId(int $storeId, string $column, string $externalId): ?int
    {
        $stmt = Database::getConnection()->prepare("SELECT id FROM products WHERE store_id = ? AND {$column} = ? LIMIT 1");
        $stmt->execute([$storeId, $externalId]);
        $id = $stmt->fetchColumn();
        return $id === false ? null : (int) $id;
    }

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

        // Saare products, 250-250 kar ke (pehle sirf pehle 50)
        $fetch = PlatformFetcher::shopify($store['store_url'], $store['access_token'], 'products');

        if (!$fetch['ok']) {
            $httpCode = $fetch['code'];
            $this->errorService->logFailure((int) $store['id'], 'shopify', 'sync_products', null, null, $httpCode, $fetch['body'] ?: 'No response');
            $this->storeModel->markUnhealthy((int) $store['id'], "Shopify API error (HTTP {$httpCode})");
            $this->redirect('/products?error=' . urlencode("Shopify API error (HTTP {$httpCode})."));
            return;
        }

        $importedCount = 0;
        $updatedCount = 0;

        foreach ($fetch['items'] as $sp) {
            $externalId = (string) $sp['id'];
            $imageUrl = $sp['image']['src'] ?? null;
            $variants = $sp['variants'] ?? [];

            $productId = $this->findByExternalId((int) $store['id'], 'external_product_id', $externalId);
            $isRealVariantProduct = count($variants) > 1 || (isset($variants[0]['title']) && $variants[0]['title'] !== 'Default Title');

            if ($productId !== null) {
                $updatedCount++;
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

                    $this->applyVariantStock($variantId, (int) $defaultWarehouse['id'], PlatformStock::shopifyVariant($variant));
                }
            } else {
                $this->applyStock($productId, (int) $defaultWarehouse['id'], PlatformStock::shopifyVariant($variants[0] ?? []));
            }
        }

        $this->storeModel->markHealthy((int) $store['id']);
        $this->syncedRedirect($importedCount, $updatedCount);
    }

    // ---------- Async Sync (Queue-Based) — all platforms ----------

    public function syncShopifyAsync(): void
    {
        $store = $this->getCurrentStore();
        $jobQueue = new JobQueue();
        $jobId = $jobQueue->push('shopify_products_sync', (int) $store['id']);
        $this->redirect('/products?queued=' . $jobId);
    }

    public function syncWooCommerceAsync(): void
    {
        $store = $this->getCurrentStore();
        $jobQueue = new JobQueue();
        $jobId = $jobQueue->push('woocommerce_products_sync', (int) $store['id']);
        $this->redirect('/products?queued=' . $jobId);
    }

    public function syncBigCommerceAsync(): void
    {
        $store = $this->getCurrentStore();
        $jobQueue = new JobQueue();
        $jobId = $jobQueue->push('bigcommerce_products_sync', (int) $store['id']);
        $this->redirect('/products?queued=' . $jobId);
    }

    public function syncPrestaShopAsync(): void
    {
        $store = $this->getCurrentStore();
        $jobQueue = new JobQueue();
        $jobId = $jobQueue->push('prestashop_products_sync', (int) $store['id']);
        $this->redirect('/products?queued=' . $jobId);
    }

    public function syncOpenCartAsync(): void
    {
        $store = $this->getCurrentStore();
        $jobQueue = new JobQueue();
        $jobId = $jobQueue->push('opencart_products_sync', (int) $store['id']);
        $this->redirect('/products?queued=' . $jobId);
    }

    public function syncOsCommerceAsync(): void
    {
        $store = $this->getCurrentStore();
        $jobQueue = new JobQueue();
        $jobId = $jobQueue->push('oscommerce_products_sync', (int) $store['id']);
        $this->redirect('/products?queued=' . $jobId);
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
        $key = $store['woocommerce_consumer_key'];
        $secret = $store['woocommerce_consumer_secret'];

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

        // Saare products, 100-100 kar ke (pehle sirf pehle 50)
        $fetch = PlatformFetcher::woo($baseUrl, $key, $secret, '/wp-json/wc/v3/products');

        if (!$fetch['ok']) {
            $httpCode = $fetch['code'];
            $this->errorService->logFailure((int) $store['id'], 'woocommerce', 'sync_products', null, null, $httpCode, $fetch['body'] ?: 'No response');
            $this->storeModel->markUnhealthy((int) $store['id'], "WooCommerce API error (HTTP {$httpCode})");
            $this->redirect('/products?error=' . urlencode("WooCommerce API error (HTTP {$httpCode}). Please check the store URL and keys."));
            return;
        }

        $importedCount = 0;
        $updatedCount = 0;

        foreach ($fetch['items'] as $wp) {
            $externalId = (string) $wp['id'];
            $imageUrl = $fixImageProtocol($wp['images'][0]['src'] ?? null);

            $productId = $this->findByExternalId((int) $store['id'], 'external_wc_product_id', $externalId);

            if ($productId !== null) {
                $updatedCount++;
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
                $variations = PlatformFetcher::woo($baseUrl, $key, $secret, "/wp-json/wc/v3/products/{$wp['id']}/variations")['items'];

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

                    $this->applyVariantStock($variantId, (int) $defaultWarehouse['id'], PlatformStock::wooCommerce($variation));
                }
            } else {
                $this->applyStock($productId, (int) $defaultWarehouse['id'], PlatformStock::wooCommerce($wp));
            }
        }

        $this->storeModel->markHealthy((int) $store['id']);
        $this->syncedRedirect($importedCount, $updatedCount);
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
        $updatedCount = 0;

        foreach ($products as $p) {
            $externalId = (string) ($p['id'] ?? '');
            if ($externalId === '') continue;

            $productId = $this->findByExternalId((int) $store['id'], 'external_product_id', $externalId);

            if ($productId === null) {
                $name = $p['name'] ?? 'Unknown product';
                $price = (float) ($p['price'] ?? 0);
                $sku = $p['sku'] ?? null;

                $stmt = $db->prepare("INSERT INTO products (store_id, name, sku, price, external_product_id) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$store['id'], $name, $sku, $price, $externalId]);
                $productId = (int) $db->lastInsertId();
                $importedCount++;
            } else {
                $updatedCount++;
            }

            $qty = isset($p['stock_quantity']) && is_numeric($p['stock_quantity']) ? (int) $p['stock_quantity'] : null;
            $this->applyStock($productId, (int) $defaultWarehouse['id'], $qty);
        }

        $this->errorService->markResolved((int) $store['id'], 'custom_bridge', 'sync_products', null);
        $this->storeModel->markHealthy((int) $store['id']);
        $this->syncedRedirect($importedCount, $updatedCount);
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

        // Saare products, 250-250 kar ke (pehle sirf pehle 50)
        $fetch = PlatformFetcher::bigCommerceV3($store['bigcommerce_store_hash'], $store['bigcommerce_access_token'], '/v3/catalog/products');

        if (!$fetch['ok']) {
            $httpCode = $fetch['code'];
            $this->errorService->logFailure((int) $store['id'], 'bigcommerce', 'sync_products', null, null, $httpCode, $fetch['body'] ?: 'No response');
            $this->storeModel->markUnhealthy((int) $store['id'], "BigCommerce API error (HTTP {$httpCode})");
            $this->redirect('/products?error=' . urlencode("BigCommerce API error (HTTP {$httpCode})."));
            return;
        }

        $db = Database::getConnection();
        $importedCount = 0;
        $updatedCount = 0;

        foreach ($fetch['items'] as $bp) {
            $externalId = (string) ($bp['id'] ?? '');
            if ($externalId === '') continue;

            $productId = $this->findByExternalId((int) $store['id'], 'external_bc_product_id', $externalId);

            if ($productId === null) {
                $name = $bp['name'] ?? 'Unknown product';
                $price = (float) ($bp['price'] ?? 0);
                $sku = $bp['sku'] ?? null;

                $stmt = $db->prepare("INSERT INTO products (store_id, name, sku, price, external_bc_product_id) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$store['id'], $name, $sku, $price, $externalId]);
                $productId = (int) $db->lastInsertId();
                $importedCount++;
            } else {
                $updatedCount++;
            }

            $this->applyStock($productId, (int) $defaultWarehouse['id'], PlatformStock::bigCommerce($bp));
        }

        $this->errorService->markResolved((int) $store['id'], 'bigcommerce', 'sync_products', null);
        $this->storeModel->markHealthy((int) $store['id']);
        $this->syncedRedirect($importedCount, $updatedCount);
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
        $wsKey = $store['prestashop_api_key'];

        // Saare active products, 100-100 kar ke
        $fetch = PlatformFetcher::prestaShop($baseUrl, 'products', [
            'ws_key' => $wsKey,
            'display' => '[id,name,price,reference,active]',
            'filter[active]' => '1',
        ]);

        if (!$fetch['ok']) {
            $httpCode = $fetch['code'];
            $this->errorService->logFailure((int) $store['id'], 'prestashop', 'sync_products', null, null, $httpCode, $fetch['body'] ?: 'No response');
            $this->storeModel->markUnhealthy((int) $store['id'], "PrestaShop API error (HTTP {$httpCode})");
            $this->redirect('/products?error=' . urlencode("PrestaShop API error (HTTP {$httpCode})."));
            return;
        }

        // Saare products ka stock EK hi baar mein (pehle har product ke liye alag request jaati thi)
        $stockFetch = PlatformFetcher::prestaShop($baseUrl, 'stock_availables', [
            'ws_key' => $wsKey,
            'display' => '[id_product,id_product_attribute,quantity]',
            'filter[id_product_attribute]' => '0',
        ]);
        $stockByProduct = [];
        foreach ($stockFetch['items'] as $sa) {
            $stockByProduct[(string) ($sa['id_product'] ?? '')] = (int) ($sa['quantity'] ?? 0);
        }

        $db = Database::getConnection();
        $importedCount = 0;
        $updatedCount = 0;

        foreach ($fetch['items'] as $pp) {
            $externalId = (string) ($pp['id'] ?? '');
            if ($externalId === '') continue;

            $nameRaw = $pp['name'] ?? 'Unknown product';
            $name = is_array($nameRaw) ? ($nameRaw[0]['value'] ?? 'Unknown product') : $nameRaw;

            $productId = $this->findByExternalId((int) $store['id'], 'external_ps_product_id', $externalId);

            if ($productId === null) {
                $price = (float) ($pp['price'] ?? 0);
                $sku = $pp['reference'] ?? null;

                $stmt = $db->prepare("INSERT INTO products (store_id, name, sku, price, external_ps_product_id) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$store['id'], $name, $sku, $price, $externalId]);
                $productId = (int) $db->lastInsertId();
                $importedCount++;
            } else {
                $updatedCount++;
            }

            // Stock list na mil saki (key ko stock_availables ki GET permission nahi) → Finovo ka stock mat chhedo
            $this->applyStock($productId, (int) $defaultWarehouse['id'], $stockFetch['ok'] ? ($stockByProduct[$externalId] ?? 0) : null);
        }

        $this->errorService->markResolved((int) $store['id'], 'prestashop', 'sync_products', null);
        $this->storeModel->markHealthy((int) $store['id']);
        $this->syncedRedirect($importedCount, $updatedCount);
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
            // Database ki andar ki detail (SQLSTATE...) user ko nahi dikhate — woh Integration Errors mein save hoti hai
            $this->errorService->logFailure((int) $store['id'], 'opencart', 'sync_products', null, null, 0, $e->getMessage());
            $this->storeModel->markUnhealthy((int) $store['id'], 'Could not connect to OpenCart database.');
            $this->redirect('/products?error=' . urlencode('Could not connect to the OpenCart database. Please check the database name in Stores → Edit (details are in Integration Errors).'));
            return;
        }

        // Ek product ki kai languages ho sakti hain — sirf ek naam lo (warna product dobara aata)
        $ocProducts = $ocDb->query(
            "SELECT p.product_id, p.model, p.price, p.quantity, MIN(pd.name) AS name
             FROM oc_product p
             INNER JOIN oc_product_description pd ON pd.product_id = p.product_id
             WHERE p.status = 1
             GROUP BY p.product_id, p.model, p.price, p.quantity"
        )->fetchAll(PDO::FETCH_ASSOC);

        $db = Database::getConnection();
        $importedCount = 0;
        $updatedCount = 0;

        foreach ($ocProducts as $op) {
            $externalId = (string) $op['product_id'];

            $productId = $this->findByExternalId((int) $store['id'], 'external_ocart_product_id', $externalId);

            if ($productId === null) {
                $name = $op['name'] ?? 'Unknown product';
                $price = (float) ($op['price'] ?? 0);
                $sku = $op['model'] ?? null;

                $insert = $db->prepare("INSERT INTO products (store_id, name, sku, price, external_ocart_product_id) VALUES (?, ?, ?, ?, ?)");
                $insert->execute([$store['id'], $name, $sku, $price, $externalId]);
                $productId = (int) $db->lastInsertId();
                $importedCount++;
            } else {
                $updatedCount++;
            }

            $this->applyStock($productId, (int) $defaultWarehouse['id'], (int) ($op['quantity'] ?? 0));
        }

        $this->errorService->markResolved((int) $store['id'], 'opencart', 'sync_products', null);
        $this->storeModel->markHealthy((int) $store['id']);
        $this->syncedRedirect($importedCount, $updatedCount);
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
            $this->redirect('/products?error=' . urlencode('Could not connect to the osCommerce database. Please check the database name in Stores → Edit (details are in Integration Errors).'));
            return;
        }

        $oscProducts = $oscDb->query(
            "SELECT p.products_id, p.products_model, p.products_price, p.products_quantity, pd.products_name
             FROM products p
             INNER JOIN products_description pd ON pd.products_id = p.products_id AND pd.language_id = 1
             WHERE p.products_status = 1"
        )->fetchAll(PDO::FETCH_ASSOC);

        $db = Database::getConnection();
        $importedCount = 0;
        $updatedCount = 0;

        foreach ($oscProducts as $op) {
            $externalId = (string) $op['products_id'];

            $productId = $this->findByExternalId((int) $store['id'], 'external_osc_product_id', $externalId);

            if ($productId === null) {
                $name = $op['products_name'] ?? 'Unknown product';
                $price = (float) ($op['products_price'] ?? 0);
                $sku = $op['products_model'] ?? null;

                $insert = $db->prepare("INSERT INTO products (store_id, name, sku, price, external_osc_product_id) VALUES (?, ?, ?, ?, ?)");
                $insert->execute([$store['id'], $name, $sku, $price, $externalId]);
                $productId = (int) $db->lastInsertId();
                $importedCount++;
            } else {
                $updatedCount++;
            }

            $this->applyStock($productId, (int) $defaultWarehouse['id'], (int) ($op['products_quantity'] ?? 0));
        }

        $this->errorService->markResolved((int) $store['id'], 'oscommerce', 'sync_products', null);
        $this->storeModel->markHealthy((int) $store['id']);
        $this->syncedRedirect($importedCount, $updatedCount);
    }

    // ---------- Wix: Products Pull ----------

    public function syncWix(): void
    {
        $store = $this->getCurrentStore();
        $defaultWarehouse = $this->warehouseModel->getDefault($store['id']);

        if (empty($store['wix_site_id']) || empty($store['wix_api_key'])) {
            $this->redirect('/products?error=' . urlencode('Please save this store\'s Wix Site ID and API Key first.'));
            return;
        }

        $connector = new WixConnector($store['wix_api_key'], $store['wix_site_id']);
        $result = $connector->fetchProducts();

        if (!$result['ok']) {
            $this->errorService->logFailure((int) $store['id'], 'wix', 'sync_products', null, null, 0, $result['message']);
            $this->storeModel->markUnhealthy((int) $store['id'], $result['message']);
            $this->redirect('/products?error=' . urlencode($result['message']));
            return;
        }

        $db = Database::getConnection();
        $importedCount = 0;
        $updatedCount = 0;

        foreach ($result['products'] as $wp) {
            $externalId = (string) ($wp['id'] ?? '');
            if ($externalId === '') continue;

            $productId = $this->findByExternalId((int) $store['id'], 'external_wix_product_id', $externalId);

            if ($productId === null) {
                $name = $wp['name'] ?? 'Unknown product';
                $price = (float) ($wp['actualPriceRange']['minValue']['amount'] ?? $wp['basePrice']['price']['amount'] ?? 0);
                $sku = $wp['physicalProperties']['sku'] ?? null;

                $stmt = $db->prepare("INSERT INTO products (store_id, name, sku, price, external_wix_product_id) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$store['id'], $name, $sku, $price, $externalId]);
                $productId = (int) $db->lastInsertId();
                $importedCount++;
            } else {
                $updatedCount++;
            }

            $this->applyStock($productId, (int) $defaultWarehouse['id'], PlatformStock::wix($wp));
        }

        $this->errorService->markResolved((int) $store['id'], 'wix', 'sync_products', null);
        $this->storeModel->markHealthy((int) $store['id']);
        $this->syncedRedirect($importedCount, $updatedCount);
    }

    // ---------- eBay: Products Pull ----------

    public function syncEbay(): void
    {
        $store = $this->getCurrentStore();
        $defaultWarehouse = $this->warehouseModel->getDefault($store['id']);

        require_once __DIR__ . '/../Services/EbayAuthService.php';

        // Connect eBay (OAuth) wala token khud refresh hota hai; purana manual token bhi chalta hai
        $token = EbayAuthService::getValidAccessToken($store);

        if ($token === null) {
            $this->redirect('/products?error=' . urlencode('This store is not connected to eBay. Use "Connect eBay" on the Stores page (or save an eBay User Token).'));
            return;
        }

        $connector = new EbayConnector($token);
        $result = $connector->fetchProducts();

        if (!$result['ok']) {
            $this->errorService->logFailure((int) $store['id'], 'ebay', 'sync_products', null, null, 0, $result['message']);
            $this->storeModel->markUnhealthy((int) $store['id'], $result['message']);
            $this->redirect('/products?error=' . urlencode($result['message']));
            return;
        }

        $db = Database::getConnection();
        $importedCount = 0;
        $updatedCount = 0;

        foreach ($result['products'] as $ep) {
            $externalId = (string) ($ep['sku'] ?? '');
            if ($externalId === '') continue;

            $stock = (int) ($ep['availability']['shipToLocationAvailability']['quantity'] ?? 0);
            $productId = $this->findByExternalId((int) $store['id'], 'external_ebay_product_id', $externalId);

            if ($productId === null) {
                $name = $ep['product']['title'] ?? 'Unknown product';
                $price = (float) ($ep['product']['aspects']['price'][0] ?? 0);

                $stmt = $db->prepare("INSERT INTO products (store_id, name, sku, price, external_ebay_product_id) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$store['id'], $name, $externalId, $price, $externalId]);
                $productId = (int) $db->lastInsertId();
                $importedCount++;
            } else {
                $updatedCount++;
            }

            $this->applyStock($productId, (int) $defaultWarehouse['id'], $stock);
        }

        $this->errorService->markResolved((int) $store['id'], 'ebay', 'sync_products', null);
        $this->storeModel->markHealthy((int) $store['id']);
        $this->syncedRedirect($importedCount, $updatedCount);
    }

    // ---------- CJdropshipping: Products Pull ----------

    public function syncCj(): void
    {
        $store = $this->getCurrentStore();
        $defaultWarehouse = $this->warehouseModel->getDefault($store['id']);

        if (empty($store['cj_email']) || empty($store['cj_api_key'])) {
            $this->redirect('/products?error=' . urlencode('Please save this store\'s CJdropshipping Email and API Key first.'));
            return;
        }

        $connector = new CjDropshippingConnector($store['cj_email'], $store['cj_api_key'], (int) $store['id']);
        $result = $connector->fetchProducts();

        if (!$result['ok']) {
            $this->errorService->logFailure((int) $store['id'], 'cj', 'sync_products', null, null, 0, $result['message']);
            $this->storeModel->markUnhealthy((int) $store['id'], $result['message']);
            $this->redirect('/products?error=' . urlencode($result['message']));
            return;
        }

        $db = Database::getConnection();
        $importedCount = 0;

        foreach ($result['products'] as $cp) {
            $externalId = (string) ($cp['pid'] ?? '');
            if ($externalId === '') continue;

            $check = $db->prepare("SELECT id FROM products WHERE store_id = ? AND external_cj_product_id = ?");
            $check->execute([$store['id'], $externalId]);
            if ($check->fetch()) continue;

            $name = $cp['productNameEn'] ?? $cp['productName'] ?? 'Unknown product';
            $price = (float) ($cp['sellPrice'] ?? 0);

            $stmt = $db->prepare("INSERT INTO products (store_id, name, price, external_cj_product_id) VALUES (?, ?, ?, ?)");
            $stmt->execute([$store['id'], $name, $price, $externalId]);
            $productId = (int) $db->lastInsertId();
            $importedCount++;

            $this->productModel->setWarehouseStock($productId, $defaultWarehouse['id'], 0);
        }

        $this->errorService->markResolved((int) $store['id'], 'cj', 'sync_products', null);
        $this->storeModel->markHealthy((int) $store['id']);
        $this->redirect('/products?synced=' . $importedCount);
    }

    // ---------- Product Push (store ke apne platform pe) ----------

    /**
     * Products page ka "Push to <platform>" button — product isi store ke platform pe banta hai
     * (Shopify store → Shopify, WooCommerce store → WooCommerce, ...). Pehle har store pe sirf
     * "Push to Shopify" tha.
     */
    public function push(): void
    {
        $db = Database::getConnection();
        $store = $this->getCurrentStore();
        $id = (int) ($_GET['id'] ?? 0);
        $platform = $store['platform'] ?? 'manual';

        $target = ProductPushService::PLATFORMS[$platform] ?? null;
        if ($target === null) {
            $reason = ProductPushService::NOT_SUPPORTED[$platform] ?? 'This store is not connected to any platform, so there is nowhere to push the product.';
            $this->redirect('/products?error=' . urlencode($reason));
            return;
        }

        $stmt = $db->prepare("SELECT * FROM products WHERE id = ? AND store_id = ?");
        $stmt->execute([$id, $store['id']]);
        $product = $stmt->fetch();

        if (!$product) {
            $this->redirect('/products?error=' . urlencode('Product not found.'));
            return;
        }

        // Pehle se platform pe hai → dobara push nahi (warna wahan duplicate product ban jata)
        $column = $target['column'];
        if (!empty($product[$column])) {
            $this->redirect('/products?error=' . urlencode("This product is already on {$target['label']}."));
            return;
        }

        // Finovo ka kul stock (saare warehouses / variants jor ke). Push khud stock bhejta hai — alag stock push nahi chahiye.
        StockPushService::pause();
        try {
            $this->productModel->recomputeStock($id);
        } finally {
            StockPushService::resume();
        }
        $stock = max(0, (int) ($this->productModel->find($id)['stock_quantity'] ?? 0));

        // Variants (size / color...) — Shopify aur WooCommerce pe har variant alag banta hai
        $variants = $this->variantModel->allByProduct($id);

        $service = new ProductPushService();
        switch ($platform) {
            case 'shopify':     $result = $service->pushShopify($store, $product, $stock, $variants); break;
            case 'woocommerce': $result = $service->pushWooCommerce($store, $product, $stock, $variants); break;
            case 'bigcommerce': $result = $service->pushBigCommerce($store, $product, $stock); break;
            case 'wix':         $result = $service->pushWix($store, $product, $stock); break;
            case 'magento':     $result = $service->pushMagento($store, $product, $stock); break;
            case 'prestashop':  $result = $service->pushPrestaShop($store, $product, $stock); break;
            case 'ebay':
                require_once __DIR__ . '/../Services/EbayAuthService.php';
                $token = EbayAuthService::getValidAccessToken($store);
                $result = $token === null
                    ? ['ok' => false, 'message' => 'This store is not connected to eBay. Use "Connect eBay" on the Stores page.']
                    : $service->pushEbay($token, $product, $stock);
                break;
        }

        if (!$result['ok']) {
            $this->errorService->logFailure((int) $store['id'], $platform, 'export_product', null, (string) $id, 0, $result['message']);
            $this->redirect('/products?error=' . urlencode($result['message']));
            return;
        }

        // Platform wala ID save karo (agli sync mein yahi product pehchana jayega, duplicate nahi banega)
        $db->prepare("UPDATE products SET {$column} = ? WHERE id = ?")->execute([$result['external_id'], $id]);

        // Variants ke platform IDs bhi (stock push har variant ka alag kar sake)
        $variantColumn = $platform === 'woocommerce' ? 'external_wc_variant_id' : 'external_variant_id';
        foreach ($result['variant_ids'] ?? [] as $variantId => $externalVariantId) {
            $db->prepare("UPDATE product_variants SET {$variantColumn} = ? WHERE id = ? AND product_id = ?")
                ->execute([$externalVariantId, (int) $variantId, $id]);
        }
        if (!empty($variants) && !in_array($platform, ['shopify', 'woocommerce'], true)) {
            $result['warning'] = trim(($result['warning'] ?? '') . ' This product has ' . count($variants)
                . " variant(s); {$target['label']} received it as one product with the total stock. Add the variants in {$target['label']} admin.");
        }

        // Magento/eBay pe SKU hi pehchaan hai — product ka SKU khali tha to jo SKU bheja woh Finovo mein bhi likh do
        if (in_array($platform, ['magento', 'ebay'], true) && trim((string) ($product['sku'] ?? '')) === '') {
            $db->prepare("UPDATE products SET sku = ? WHERE id = ?")->execute([ProductPushService::skuFor($product), $id]);
        }

        $this->errorService->markResolved((int) $store['id'], $platform, 'export_product', (string) $id);

        $notes = [];
        if (!empty($result['warning'])) {
            $notes[] = $result['warning'];
        }

        $query = 'exported=' . urlencode($target['label']);
        if ($notes) {
            $query .= '&push_note=' . urlencode(implode(' ', $notes));
        }
        $this->redirect('/products?' . $query);
    }
}