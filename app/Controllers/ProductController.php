<?php
// app/Controllers/ProductController.php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../Models/Product.php';
require_once __DIR__ . '/../Models/Store.php';

class ProductController extends Controller
{
    private Product $productModel;
    private Store $storeModel;

    public function __construct()
    {
        $this->productModel = new Product();
        $this->storeModel = new Store();
    }

    public function index(): void
    {
        $store = $this->storeModel->first();
        $products = $this->productModel->all($store['id']);
        $this->view('products/index', [
            'products' => $products,
            'synced' => $_GET['synced'] ?? null,
            'exported' => $_GET['exported'] ?? null,
            'imported' => $_GET['imported'] ?? null,
            'error' => $_GET['error'] ?? null,
        ]);
    }

    public function showImportForm(): void
    {
        $this->redirect('/products');
    }

    public function handleImport(): void
    {
        $store = $this->storeModel->first();

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

            [$name, $sku, $price] = $row;
            $name = trim($name);
            $sku = trim($sku);
            $price = (float) trim($price);

            if ($name === '' || $price <= 0) {
                continue;
            }

            $this->productModel->create($store['id'], $name, $price, $sku ?: null);
            $importedCount++;
        }

        fclose($handle);
        $this->redirect('/products?imported=' . $importedCount);
    }

    // ---------- CSV Export ----------

    public function exportCsv(): void
    {
        $store = $this->storeModel->first();
        $products = $this->productModel->all($store['id']);

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="products.csv"');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['name', 'sku', 'price']);
        foreach ($products as $p) {
            fputcsv($out, [$p['name'], $p['sku'], $p['price']]);
        }
        fclose($out);
        exit;
    }

    // ---------- Shopify: Products Pull (fetch) ----------

    public function syncShopify(): void
    {
        $db = Database::getConnection();
        $store = $this->storeModel->first();

        if (empty($store['store_url']) || empty($store['access_token'])) {
            $this->redirect('/products?error=' . urlencode('Pehle Shopify Settings mein Store URL aur Access Token save karo.'));
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
            $this->redirect('/products?error=' . urlencode("Shopify API error (HTTP {$httpCode})."));
            return;
        }

        $data = json_decode($response, true);
        $shopifyProducts = $data['products'] ?? [];
        $importedCount = 0;

        foreach ($shopifyProducts as $sp) {
            $externalId = (string) $sp['id'];

            $check = $db->prepare("SELECT id FROM products WHERE store_id = ? AND external_product_id = ?");
            $check->execute([$store['id'], $externalId]);
            if ($check->fetch()) {
                continue;
            }

            $name = $sp['title'] ?? 'Unknown product';
            $variant = $sp['variants'][0] ?? [];
            $price = (float) ($variant['price'] ?? 0);
            $sku = $variant['sku'] ?? null;

            $stmt = $db->prepare(
                "INSERT INTO products (store_id, name, sku, price, external_product_id) VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->execute([$store['id'], $name, $sku, $price, $externalId]);
            $importedCount++;
        }

        $this->redirect('/products?synced=' . $importedCount);
    }

    // ---------- Shopify: Product Push (Export ek product) ----------

    public function exportToShopify(): void
    {
        $db = Database::getConnection();
        $store = $this->storeModel->first();
        $id = (int) ($_GET['id'] ?? 0);

        if (empty($store['store_url']) || empty($store['access_token'])) {
            $this->redirect('/products?error=' . urlencode('Pehle Shopify Settings save karo.'));
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
            $this->redirect('/products?error=' . urlencode("Shopify export failed (HTTP {$httpCode})."));
            return;
        }

        $data = json_decode($response, true);
        $externalId = (string) ($data['product']['id'] ?? '');

        if ($externalId) {
            $update = $db->prepare("UPDATE products SET external_product_id = ? WHERE id = ?");
            $update->execute([$externalId, $id]);
        }

        $this->redirect('/products?exported=1');
    }
}