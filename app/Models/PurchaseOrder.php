<?php
require_once __DIR__ . '/../../core/Model.php';

class PurchaseOrder extends Model
{
    public static array $statuses = ['draft', 'ordered', 'received'];

    public function all(int $storeId): array
    {
        $stmt = $this->query(
            "SELECT po.*, s.name as supplier_name, w.name as warehouse_name
             FROM purchase_orders po
             INNER JOIN suppliers s ON s.id = po.supplier_id
             INNER JOIN warehouses w ON w.id = po.warehouse_id
             WHERE po.store_id = ?
             ORDER BY po.id DESC",
            [$storeId]
        );
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->query("SELECT * FROM purchase_orders WHERE id = ?", [$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function items(int $poId): array
    {
        $stmt = $this->query(
            "SELECT poi.*, p.name as product_name, p.sku
             FROM purchase_order_items poi
             INNER JOIN products p ON p.id = poi.product_id
             WHERE poi.purchase_order_id = ?",
            [$poId]
        );
        return $stmt->fetchAll();
    }

    public function create(int $storeId, int $supplierId, int $warehouseId, ?string $notes): int
    {
        $this->query(
            "INSERT INTO purchase_orders (store_id, supplier_id, warehouse_id, notes) VALUES (?, ?, ?, ?)",
            [$storeId, $supplierId, $warehouseId, $notes]
        );
        return (int) $this->db->lastInsertId();
    }

    public function addItem(int $poId, int $productId, int $quantity, float $unitCost): void
    {
        $this->query(
            "INSERT INTO purchase_order_items (purchase_order_id, product_id, quantity, unit_cost) VALUES (?, ?, ?, ?)",
            [$poId, $productId, $quantity, $unitCost]
        );
    }

    public function updateStatus(int $id, string $status): void
    {
        $status = in_array($status, self::$statuses, true) ? $status : 'draft';
        $receivedAt = $status === 'received' ? date('Y-m-d H:i:s') : null;
        $this->query(
            "UPDATE purchase_orders SET status = ?, received_at = COALESCE(?, received_at) WHERE id = ?",
            [$status, $receivedAt, $id]
        );
    }
}