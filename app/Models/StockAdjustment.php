<?php
require_once __DIR__ . '/../../core/Model.php';

class StockAdjustment extends Model
{
    public function all(int $storeId): array
    {
        $stmt = $this->query(
            "SELECT sa.*, CONCAT(p.name, COALESCE(CONCAT(' — ', pv.label), '')) as product_name, w.name as warehouse_name,
                    il.quantity_before, il.quantity_after, il.actor
             FROM stock_adjustments sa
             INNER JOIN products p ON p.id = sa.product_id
             LEFT JOIN product_variants pv ON pv.id = sa.variant_id
             INNER JOIN warehouses w ON w.id = sa.warehouse_id
             INNER JOIN store_warehouses sw ON sw.warehouse_id = sa.warehouse_id AND sw.store_id = ?
             -- Ledger se pehle / baad ka stock aur kis ne kiya
             LEFT JOIN inventory_ledger il ON il.product_id = sa.product_id AND il.warehouse_id = sa.warehouse_id
                                          AND il.source = 'stock_adjustment' AND il.reference = CONCAT('adjustment:', sa.id)
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