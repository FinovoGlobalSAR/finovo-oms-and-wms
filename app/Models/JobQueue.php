<?php
require_once __DIR__ . '/../../core/Model.php';

class JobQueue extends Model
{
    public function push(string $type, int $storeId): int
    {
        $this->query(
            "INSERT INTO jobs (type, store_id, status) VALUES (?, ?, 'pending')",
            [$type, $storeId]
        );
        return (int) $this->db->lastInsertId();
    }

    public function nextPending(): ?array
    {
        $stmt = $this->query("SELECT * FROM jobs WHERE status = 'pending' ORDER BY id ASC LIMIT 1");
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function markProcessing(int $id): void
    {
        $this->query("UPDATE jobs SET status = 'processing', attempts = attempts + 1 WHERE id = ?", [$id]);
    }

    public function markCompleted(int $id, string $message): void
    {
        $this->query("UPDATE jobs SET status = 'completed', result_message = ? WHERE id = ?", [$message, $id]);
    }

    public function markFailed(int $id, string $message): void
    {
        $this->query("UPDATE jobs SET status = 'failed', result_message = ? WHERE id = ?", [$message, $id]);
    }

    public function find(int $id): ?array
    {
        $stmt = $this->query("SELECT * FROM jobs WHERE id = ?", [$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function recentForStore(int $storeId, int $limit = 10): array
    {
        $stmt = $this->query(
            "SELECT * FROM jobs WHERE store_id = ? ORDER BY id DESC LIMIT ?",
            [$storeId, $limit]
        );
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute([$storeId]);
        return $stmt->fetchAll();
    }
}