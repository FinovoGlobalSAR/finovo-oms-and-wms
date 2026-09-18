<?php
require_once __DIR__ . '/../../core/Database.php';

class AuditLogService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function log(?int $storeId, string $action, string $entityType, ?string $entityId, ?string $details = null): void
    {
        $actor = $_SESSION['user']['name'] ?? 'system';

        $stmt = $this->db->prepare(
            "INSERT INTO audit_log (store_id, actor, action, entity_type, entity_id, details) VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$storeId, $actor, $action, $entityType, $entityId, $details]);
    }

    public function all(?int $storeId = null, int $limit = 100): array
    {
        if ($storeId) {
            $stmt = $this->db->prepare("SELECT * FROM audit_log WHERE store_id = ? OR store_id IS NULL ORDER BY id DESC LIMIT ?");
            $stmt->bindValue(1, $storeId, PDO::PARAM_INT);
            $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        } else {
            $stmt = $this->db->prepare("SELECT * FROM audit_log ORDER BY id DESC LIMIT ?");
            $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}