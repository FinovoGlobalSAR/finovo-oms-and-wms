<?php
require_once __DIR__ . '/../../core/Database.php';

/**
 * Har external API call (Shopify/WooCommerce push) fail ho jaye to yahan
 * log hota hai. Retry karne pe attempts badhta hai; 5 baar fail hone pe
 * "dead_letter" status ban jata hai — manual review ke liye.
 */
class IntegrationErrorService
{
    private PDO $db;
    private const MAX_ATTEMPTS = 5;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function logFailure(int $storeId, string $connector, string $operation, ?string $externalId, ?string $internalId, ?int $httpStatus, string $errorMessage): int
    {
        // Same operation ke liye pehle se open error hai to attempts badhao, warna naya banao
        $existing = $this->findOpen($storeId, $connector, $operation, $internalId);

        if ($existing) {
            $attempts = (int) $existing['attempts'] + 1;
            $status = $attempts >= self::MAX_ATTEMPTS ? 'dead_letter' : 'pending';
            $nextRetry = $status === 'pending' ? date('Y-m-d H:i:s', time() + (60 * pow(2, $attempts))) : null;

            $this->db->prepare(
                "UPDATE integration_errors SET attempts = ?, status = ?, next_retry_at = ?, http_status = ?, error_message = ?, updated_at = NOW() WHERE id = ?"
            )->execute([$attempts, $status, $nextRetry, $httpStatus, $errorMessage, $existing['id']]);

            return (int) $existing['id'];
        }

        $nextRetry = date('Y-m-d H:i:s', time() + 60);
        $stmt = $this->db->prepare(
            "INSERT INTO integration_errors (store_id, connector, operation, external_id, internal_id, http_status, error_message, attempts, next_retry_at, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?, 'pending')"
        );
        $stmt->execute([$storeId, $connector, $operation, $externalId, $internalId, $httpStatus, $errorMessage, $nextRetry]);
        return (int) $this->db->lastInsertId();
    }

    public function markResolved(int $storeId, string $connector, string $operation, ?string $internalId): void
    {
        $existing = $this->findOpen($storeId, $connector, $operation, $internalId);
        if ($existing) {
            $this->db->prepare("UPDATE integration_errors SET status = 'resolved', updated_at = NOW() WHERE id = ?")
                ->execute([$existing['id']]);
        }
    }

    private function findOpen(int $storeId, string $connector, string $operation, ?string $internalId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM integration_errors WHERE store_id = ? AND connector = ? AND operation = ? AND internal_id = ? AND status IN ('pending', 'dead_letter') LIMIT 1"
        );
        $stmt->execute([$storeId, $connector, $operation, $internalId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function all(int $storeId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM integration_errors WHERE store_id = ? ORDER BY id DESC LIMIT 100");
        $stmt->execute([$storeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function retryNow(int $id): array
    {
        $stmt = $this->db->prepare("SELECT * FROM integration_errors WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: [];
    }

    public function markRetried(int $id, bool $success): void
    {
        if ($success) {
            $this->db->prepare("UPDATE integration_errors SET status = 'resolved', updated_at = NOW() WHERE id = ?")->execute([$id]);
        } else {
            $this->db->prepare("UPDATE integration_errors SET attempts = attempts + 1, updated_at = NOW() WHERE id = ?")->execute([$id]);
        }
    }
}
