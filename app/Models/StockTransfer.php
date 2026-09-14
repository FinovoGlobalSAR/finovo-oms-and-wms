<?php
require_once __DIR__ . '/../../core/Model.php';

class StockTransfer extends Model
{
    public function all(int $storeId): array
    {
        $stmt = $this->query(
            "SELECT st.*, p.name as product_name, wf.name as from_name, wt.name as to_name
             FROM stock_transfers st
             INNER JOIN products p ON p.id = st.product_id
             INNER JOIN warehouses wf ON wf.id = st.from_warehouse_id
             INNER JOIN warehouses wt ON wt.id = st.to_warehouse_id
             INNER JOIN store_warehouses sw ON sw.warehouse_id = st.to_warehouse_id AND sw.store_id = ?
             ORDER BY st.id DESC LIMIT 50",
            [$storeId]
        );
        return $stmt->fetchAll();
    }

    public function create(int $productId, ?int $variantId, int $fromWarehouseId, int $toWarehouseId, int $quantity, ?string $notes): int
    {
        $this->query(
            "INSERT INTO stock_transfers (product_id, variant_id, from_warehouse_id, to_warehouse_id, quantity, notes) VALUES (?, ?, ?, ?, ?, ?)",
            [$productId, $variantId, $fromWarehouseId, $toWarehouseId, $quantity, $notes]
        );
        return (int) $this->db->lastInsertId();
    }
}