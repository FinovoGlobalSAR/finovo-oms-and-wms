<?php
require_once __DIR__ . '/../../core/Model.php';

class FieldMapping extends Model
{
    // Har platform ke liye default field names — agar customer ne khud
    // configure nahi kiya, to yehi defaults use honge (backward-compatible).
    private static array $defaults = [
        'shopify' => [
            'product' => ['name' => 'title', 'sku' => 'variants.0.sku', 'price' => 'variants.0.price'],
        ],
        'woocommerce' => [
            'product' => ['name' => 'name', 'sku' => 'sku', 'price' => 'price'],
        ],
        'bigcommerce' => [
            'product' => ['name' => 'name', 'sku' => 'sku', 'price' => 'price'],
        ],
        'prestashop' => [
            'product' => ['name' => 'name', 'sku' => 'reference', 'price' => 'price'],
        ],
        'opencart' => [
            'product' => ['name' => 'name', 'sku' => 'model', 'price' => 'price'],
        ],
        'oscommerce' => [
            'product' => ['name' => 'products_name', 'sku' => 'products_model', 'price' => 'products_price'],
        ],
    ];

    public function allForStore(int $storeId, string $platform, string $entityType): array
    {
        $stmt = $this->query(
            "SELECT finovo_field, external_field FROM field_mappings WHERE store_id = ? AND platform = ? AND entity_type = ?",
            [$storeId, $platform, $entityType]
        );
        $rows = $stmt->fetchAll();

        $mapping = [];
        foreach ($rows as $row) {
            $mapping[$row['finovo_field']] = $row['external_field'];
        }

        // Jo fields customer ne configure nahi kiye, unke liye default bhar do
        $defaults = self::$defaults[$platform][$entityType] ?? [];
        foreach ($defaults as $field => $defaultValue) {
            if (!isset($mapping[$field])) {
                $mapping[$field] = $defaultValue;
            }
        }

        return $mapping;
    }

    public function save(int $storeId, string $platform, string $entityType, array $fieldValues): void
    {
        foreach ($fieldValues as $finovoField => $externalField) {
            $externalField = trim($externalField);
            if ($externalField === '') continue;

            $this->query(
                "INSERT INTO field_mappings (store_id, platform, entity_type, finovo_field, external_field)
                 VALUES (?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE external_field = VALUES(external_field)",
                [$storeId, $platform, $entityType, $finovoField, $externalField]
            );
        }
    }

    public static function availableFields(string $entityType): array
    {
        return $entityType === 'product'
            ? ['name', 'sku', 'price']
            : ['customer_name', 'product_name', 'quantity', 'price'];
    }

    /**
     * Dotted path (jaisa "variants.0.sku") se nested array/object se value
     * nikalta hai. Isse mapping mein "variants.0.price" jaisi paths likhi ja
     * sakti hain, jo Shopify/WooCommerce ke nested JSON response ke liye zaroori hai.
     */
    public static function extract(array $data, string $path)
    {
        $segments = explode('.', $path);
        $current = $data;

        foreach ($segments as $segment) {
            if (is_array($current) && isset($current[$segment])) {
                $current = $current[$segment];
            } else {
                return null;
            }
        }

        return $current;
    }
}