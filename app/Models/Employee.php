
<?php

require_once __DIR__ . '/../../core/Model.php';

class Employee extends Model
{
    public function all(int $companyId): array
    {
        $stmt = $this->query(
            "SELECT
                users.id,
                users.name,
                users.email,
                users.status,
                users.created_at,
                roles.name AS role
            FROM users
            INNER JOIN roles ON roles.id = users.role_id
            WHERE users.company_id = ?
            ORDER BY users.id DESC",
            [$companyId]
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

