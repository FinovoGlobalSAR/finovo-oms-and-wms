<?php
require_once __DIR__ . '/../../core/Database.php';

class OrderStatusService
{
    private PDO $db;

    private array $transitions = [
        'pending'    => ['processing', 'cancelled'],
        'processing' => ['dispatched', 'cancelled', 'delivered'],
        'dispatched' => ['delivered', 'cancelled'],
        'delivered'  => ['returned'],
        'cancelled'  => [],
        'returned'   => [],
    ];

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function canTransition(string $from, string $to): bool
    {
        if ($from === $to) {
            return true;
        }
        return in_array($to, $this->transitions[$from] ?? [], true);
    }

    /**
     * @return array{ok: bool, message?: string}
     */
    public function transition(int $orderId, string $newStatus, string $source = 'user', ?string $actor = null, ?string $reason = null): array
    {
        $stmt = $this->db->prepare("SELECT status FROM orders WHERE id = ?");
        $stmt->execute([$orderId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return ['ok' => false, 'message' => 'Order not found.'];
        }

        $oldStatus = $row['status'];

        if (!$this->canTransition($oldStatus, $newStatus)) {
            return ['ok' => false, 'message' => "Cannot move order from '{$oldStatus}' to '{$newStatus}'."];
        }

        $this->db->prepare("UPDATE orders SET status = ? WHERE id = ?")->execute([$newStatus, $orderId]);

        $this->db->prepare(
            "INSERT INTO order_status_history (order_id, old_status, new_status, actor, source, reason) VALUES (?, ?, ?, ?, ?, ?)"
        )->execute([$orderId, $oldStatus, $newStatus, $actor, $source, $reason]);

        return ['ok' => true];
    }

    public function history(int $orderId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM order_status_history WHERE order_id = ? ORDER BY id ASC");
        $stmt->execute([$orderId]);
        return $stmt->fetchAll();
    }
}