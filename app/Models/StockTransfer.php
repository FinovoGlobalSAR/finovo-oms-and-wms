<?php
require_once __DIR__ . '/../../core/Model.php';

class StockTransfer extends Model
{
    public function all(int $storeId): array
    {
        $stmt = $this->query(
            "SELECT st.*, CONCAT(p.name, COALESCE(CONCAT(' — ', pv.label), '')) as product_name, wf.name as from_name, wt.name as to_name,
                    il.quantity_before, il.quantity_after, il.actor
             FROM stock_transfers st
             INNER JOIN products p ON p.id = st.product_id
             LEFT JOIN product_variants pv ON pv.id = st.variant_id
             INNER JOIN warehouses wf ON wf.id = st.from_warehouse_id
             INNER JOIN warehouses wt ON wt.id = st.to_warehouse_id
             INNER JOIN store_warehouses sw ON sw.warehouse_id = st.to_warehouse_id AND sw.store_id = ?
             -- Ledger ki 'nikalne' wali line: From warehouse ka pehle / baad ka stock aur kis ne kiya
             LEFT JOIN inventory_ledger il ON il.product_id = st.product_id AND il.warehouse_id = st.from_warehouse_id
                                          AND il.source = 'stock_transfer_out' AND il.reference = CONCAT('transfer:', st.id)
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