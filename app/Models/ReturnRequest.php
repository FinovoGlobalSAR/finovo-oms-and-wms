<?php
require_once __DIR__ . '/../../core/Model.php';

class ReturnRequest extends Model
{
    public static array $statuses = ['requested', 'approved', 'rejected', 'completed'];
    public static array $conditions = ['resellable', 'damaged'];

    public function all(int $storeId): array
    {
        $stmt = $this->query(
            "SELECT r.*, o.customer_name, o.product_name, o.source AS order_source
             FROM returns r
             INNER JOIN orders o ON o.id = r.order_id
             WHERE r.store_id = ?
             ORDER BY r.id DESC",
            [$storeId]
        );
        return $stmt->fetchAll();
    }

    /** $storeId do to sirf usi store ka return milta hai (doosre store ka id badal kar nahi chhed sakte) */
    public function find(int $id, ?int $storeId = null): ?array
    {
        $stmt = $storeId === null
            ? $this->query("SELECT * FROM returns WHERE id = ?", [$id])
            : $this->query("SELECT * FROM returns WHERE id = ? AND store_id = ?", [$id, $storeId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Is order ki kitni quantity ka return pehle se bana hua hai (rejected chhod kar) */
    public function returnedQuantityForOrder(int $orderId): int
    {
        $stmt = $this->query("SELECT COALESCE(SUM(quantity), 0) FROM returns WHERE order_id = ? AND status != 'rejected'", [$orderId]);
        return (int) $stmt->fetchColumn();
    }

    /** Status kahan se kahan ja sakta hai — completed / rejected aakhri hain (stock dobara na jude) */
    public static function canMove(string $from, string $to): bool
    {
        $allowed = [
            'requested' => ['requested', 'approved', 'rejected', 'completed'],
            'approved'  => ['approved', 'rejected', 'completed'],
            'rejected'  => [],
            'completed' => [],
        ];
        return in_array($to, $allowed[$from] ?? [], true);
    }

    public function returnedOrderIds(int $storeId): array
    {
        $stmt = $this->query(
            "SELECT DISTINCT order_id FROM returns WHERE store_id = ? AND status != 'rejected'",
            [$storeId]
        );
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    public function create(int $storeId, int $orderId, ?int $productId, ?int $variantId, int $warehouseId, int $quantity, ?string $reason, float $refundAmount): int
    {
        $this->query(
            "INSERT INTO returns (store_id, order_id, product_id, variant_id, warehouse_id, quantity, reason, refund_amount) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [$storeId, $orderId, $productId, $variantId, $warehouseId, $quantity, $reason, $refundAmount]
        );
        return (int) $this->db->lastInsertId();
    }

    public function updateStatus(int $id, string $status, string $condition): void
    {
        $status = in_array($status, self::$statuses, true) ? $status : 'requested';
        $condition = in_array($condition, self::$conditions, true) ? $condition : 'resellable';

        $this->query("UPDATE returns SET status = ?, condition_status = ? WHERE id = ?", [$status, $condition, $id]);
    }
}