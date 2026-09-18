<?php
require_once __DIR__ . '/../../core/Model.php';

class SkuMapping extends Model
{
    public static array $states = ['mapped', 'unmapped', 'conflict', 'disabled'];

    public function all(int $storeId): array
    {
        $stmt = $this->query(
            "SELECT sm.*, p.name as product_name
             FROM sku_mappings sm
             INNER JOIN products p ON p.id = sm.product_id
             WHERE sm.store_id = ?
             ORDER BY sm.id DESC",
            [$storeId]
        );
        return $stmt->fetchAll();
    }

    public function findByExternal(int $storeId, string $externalProductId): ?array
    {
        $stmt = $this->query(
            "SELECT * FROM sku_mappings WHERE store_id = ? AND external_product_id = ? LIMIT 1",
            [$storeId, $externalProductId]
        );
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(int $storeId, int $productId, ?int $variantId, ?string $externalProductId, ?string $externalVariantId, ?string $externalSku, ?string $barcode, string $mappedBy): int
    {
        $this->query(
            "INSERT INTO sku_mappings (store_id, product_id, variant_id, external_product_id, external_variant_id, external_sku, barcode, mapping_state, mapped_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, 'mapped', ?)",
            [$storeId, $productId, $variantId, $externalProductId, $externalVariantId, $externalSku, $barcode, $mappedBy]
        );
        return (int) $this->db->lastInsertId();
    }

    public function markUnmapped(int $storeId, string $externalProductId): void
    {
        $this->query(
            "UPDATE sku_mappings SET mapping_state = 'unmapped' WHERE store_id = ? AND external_product_id = ?",
            [$storeId, $externalProductId]
        );
    }

    public function delete(int $id): void
    {
        $this->query("DELETE FROM sku_mappings WHERE id = ?", [$id]);
    }
}