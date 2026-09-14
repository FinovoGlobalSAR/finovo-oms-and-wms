<?php
require_once __DIR__ . '/../../core/Model.php';

class StockAdjustment extends Model
{
    public function all(int $storeId): array
    {
        $stmt = $this->query(
            "SELECT sa.*, p.name as product_name, w.name as warehouse_name
             FROM stock_adjustments sa
             INNER JOIN products p ON p.id = sa.product_id
             INNER JOIN warehouses w ON w.id = sa.warehouse_id
             INNER JOIN store_warehouses sw ON sw.warehouse_id = sa.warehouse_id AND sw.store_id = ?
             ORDER BY sa.id DESC LIMIT 50",
            [$storeId]
        );
        return $stmt->fetchAll();
    }

    public function create(int $productId, ?int $variantId, int $warehouseId, int $quantityChange, string $reason): int
    {
        $this->query(
            "INSERT INTO stock_adjustments (product_id, variant_id, warehouse_id, quantity_change, reason) VALUES (?, ?, ?, ?, ?)",
            [$productId, $variantId, $warehouseId, $quantityChange, $reason]
        );
        return (int) $this->db->lastInsertId();
    }
}