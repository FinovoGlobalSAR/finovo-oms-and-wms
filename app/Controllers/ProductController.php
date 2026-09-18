<?php
// app/Controllers/ProductController.php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../Models/Product.php';
require_once __DIR__ . '/../Models/Store.php';
require_once __DIR__ . '/../Models/Warehouse.php';
require_once __DIR__ . '/../Models/ProductVariant.php';
require_once __DIR__ . '/../Services/IntegrationErrorService.php';

class ProductController extends Controller
{
    private Product $productModel;
    private Store $storeModel;
    private Warehouse $warehouseModel;
    private ProductVariant $variantModel;
    private IntegrationErrorService $errorService;

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
    }

    public function index(): void
    {
        $store = $this->getCurrentStore();
        $products = $this->productModel->all($store['id']);

        foreach ($products as &$p) {
            $variantCount = $this->productModel->variantCount((int) $p['id']);
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
                $name = $sp['title'] ?? 'Unknown product';
                $firstVariant = $variants[0] ?? [];
                $price = (float) ($firstVariant['price'] ?? 0);
                $sku = $firstVariant['sku'] ?? null;

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

        // Pehle connection test karo — agar bridge hi reachable nahi hai, to error log karke rok do
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