<?php
require_once __DIR__ . '/../../core/Model.php';

class JobQueue extends Model
{
    /** Itne minute se zyada "processing" mein pada job = worker beech mein ruk gaya tha (timeout / server restart) */
    public const STALE_MINUTES = 30;

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
        $this->releaseStale();

        while (true) {
            $stmt = $this->query("SELECT id FROM jobs WHERE status = 'pending' ORDER BY id ASC LIMIT 1");
            $id = $stmt->fetchColumn();
            if ($id === false) {
                return null;
            }

            $claim = $this->query(
                "UPDATE jobs SET status = 'processing', attempts = attempts + 1 WHERE id = ? AND status = 'pending'",
                [(int) $id]
            );
            if ($claim->rowCount() === 1) {
                return $this->find((int) $id);
            }
            // Kisi aur worker ne yeh job le liya — agla dekho
        }
    }

    /** nextPending() ab khud "processing" kar deta hai; yeh sirf purane code ke liye (dobara attempts nahi barhata) */
    public function markProcessing(int $id): void
    {
        $this->query("UPDATE jobs SET status = 'processing' WHERE id = ? AND status <> 'processing'", [$id]);
    }

    /**
     * Atke hue jobs: worker beech mein band ho jaye to job hamesha "processing" mein reh jata tha, aur
     * auto-sync us store ka naya job queue hi nahi karta tha (store ka auto-sync hamesha ke liye ruk jata).
     * Ab aise purane jobs "failed" ho jate hain — agle cycle mein auto-sync naya job bana dega.
     */
    public function releaseStale(int $minutes = self::STALE_MINUTES): int
    {
        $stmt = $this->query(
            "UPDATE jobs
             SET status = 'failed',
                 result_message = CONCAT('Stopped: the worker did not finish this job within ', ?, ' minutes (timeout or server restart). It will run again in the next sync cycle.')
             WHERE status = 'processing' AND updated_at < (NOW() - INTERVAL ? MINUTE)",
            [$minutes, $minutes]
        );
        return $stmt->rowCount();
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
        // LIMIT ko integer bind karna padta hai (string "10" pe MySQL error deta hai)
        $stmt = $this->db->prepare("SELECT * FROM jobs WHERE store_id = ? ORDER BY id DESC LIMIT ?");
        $stmt->bindValue(1, $storeId, PDO::PARAM_INT);
        $stmt->bindValue(2, max(1, $limit), PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}