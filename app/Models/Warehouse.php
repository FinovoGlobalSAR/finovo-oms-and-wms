<?php
require_once __DIR__ . '/../../core/Model.php';

class Warehouse extends Model
{
    public function all(): array
    {
        $stmt = $this->query("SELECT * FROM warehouses ORDER BY name ASC");
        return $stmt->fetchAll();
    }

    public function allByStore(int $storeId): array
    {
        $stmt = $this->query(
            "SELECT w.*, sw.is_default
             FROM warehouses w
             INNER JOIN store_warehouses sw ON sw.warehouse_id = w.id
             WHERE sw.store_id = ?
             ORDER BY sw.is_default DESC, w.name ASC",
            [$storeId]
        );
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->query("SELECT * FROM warehouses WHERE id = ?", [$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(string $name, ?string $location = null): int
    {
        $this->query("INSERT INTO warehouses (name, location) VALUES (?, ?)", [$name, $location]);
        return (int) $this->db->lastInsertId();
    }

    public function linkToStore(int $warehouseId, int $storeId, bool $isDefault = false): void
    {
        if ($isDefault) {
            $this->query("UPDATE store_warehouses SET is_default = 0 WHERE store_id = ?", [$storeId]);
        }
        $this->query(
            "INSERT INTO store_warehouses (store_id, warehouse_id, is_default)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE is_default = VALUES(is_default)",
            [$storeId, $warehouseId, $isDefault ? 1 : 0]
        );
    }

    public function unlinkFromStore(int $warehouseId, int $storeId): void
    {
        $this->query("DELETE FROM store_warehouses WHERE store_id = ? AND warehouse_id = ?", [$storeId, $warehouseId]);
    }

    public function getDefault(int $storeId): array
    {
        $stmt = $this->query(
            "SELECT w.* FROM warehouses w
             INNER JOIN store_warehouses sw ON sw.warehouse_id = w.id
             WHERE sw.store_id = ? AND sw.is_default = 1 LIMIT 1",
            [$storeId]
        );
        $existing = $stmt->fetch();
        if ($existing) {
            return $existing;
        }

        $stmt2 = $this->query(
            "SELECT w.* FROM warehouses w
             INNER JOIN store_warehouses sw ON sw.warehouse_id = w.id
             WHERE sw.store_id = ? LIMIT 1",
            [$storeId]
        );
        $any = $stmt2->fetch();
        if ($any) {
            $this->linkToStore((int) $any['id'], $storeId, true);
            return $any;
        }

        $id = $this->create('Main Warehouse', 'Head Office');
        $this->linkToStore($id, $storeId, true);
        return $this->find($id);
    }

    public function storesLinkedTo(int $warehouseId): array
    {
        $stmt = $this->query(
            "SELECT s.*, sw.is_default
             FROM stores s
             INNER JOIN store_warehouses sw ON sw.store_id = s.id
             WHERE sw.warehouse_id = ?
             ORDER BY s.name ASC",
            [$warehouseId]
        );
        return $stmt->fetchAll();
    }

    /**
     * Low/out-of-stock alerts for the notification bell — per warehouse linked to this store.
     */
    public function warehouseStockAlerts(int $storeId): array
    {
        $simpleRows = $this->query(
            "SELECT w.id AS warehouse_id, w.name AS warehouse_name,
                    p.name AS item_name, p.sku AS item_sku,
                    COALESCE(pws.stock_quantity, 0) AS stock_quantity,
                    COALESCE(p.low_stock_threshold, 5) AS threshold
             FROM store_warehouses sw
             INNER JOIN warehouses w ON w.id = sw.warehouse_id
             INNER JOIN products p ON p.store_id = sw.store_id
             LEFT JOIN product_warehouse_stock pws ON pws.warehouse_id = w.id AND pws.product_id = p.id
             WHERE sw.store_id = ?
             AND p.id NOT IN (SELECT DISTINCT product_id FROM product_variants)
             AND COALESCE(pws.stock_quantity, 0) <= COALESCE(p.low_stock_threshold, 5)",
            [$storeId]
        )->fetchAll();

        $variantRows = $this->query(
            "SELECT w.id AS warehouse_id, w.name AS warehouse_name,
                    CONCAT(p.name, ' — ', pv.label) AS item_name, pv.sku AS item_sku,
                    COALESCE(vws.stock_quantity, 0) AS stock_quantity,
                    COALESCE(p.low_stock_threshold, 5) AS threshold
             FROM store_warehouses sw
             INNER JOIN warehouses w ON w.id = sw.warehouse_id
             INNER JOIN products p ON p.store_id = sw.store_id
             INNER JOIN product_variants pv ON pv.product_id = p.id
             LEFT JOIN variant_warehouse_stock vws ON vws.warehouse_id = w.id AND vws.variant_id = pv.id
             WHERE sw.store_id = ?
             AND COALESCE(vws.stock_quantity, 0) <= COALESCE(p.low_stock_threshold, 5)",
            [$storeId]
        )->fetchAll();

        $allRows = array_merge($simpleRows, $variantRows);

        $grouped = [];
        foreach ($allRows as $row) {
            $warehouseId = $row['warehouse_id'];

            if (!isset($grouped[$warehouseId])) {
                $grouped[$warehouseId] = [
                    'warehouse_id' => $warehouseId,
                    'warehouse_name' => $row['warehouse_name'],
                    'items' => [],
                ];
            }

            $grouped[$warehouseId]['items'][] = [
                'name' => $row['item_name'],
                'sku' => $row['item_sku'],
                'stock' => (int) $row['stock_quantity'],
                'is_out' => (int) $row['stock_quantity'] <= 0,
            ];
        }

        return array_values($grouped);
    }
}