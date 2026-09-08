<?php

require_once __DIR__ . '/../../core/Database.php';

class User
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare("
            SELECT
                u.id,
                u.company_id,
                u.name,
                u.email,
               u.password_hash AS password,
                u.status,
                r.name AS role,
                c.name AS company_name
            FROM users u

            INNER JOIN roles r
                ON r.id = u.role_id

            LEFT JOIN companies c
                ON c.id = u.company_id

            WHERE u.email = ?
            AND u.status = 'active'

            LIMIT 1
        ");

        $stmt->execute([$email]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    public function updateLastLogin(int $userId): void
    {
        $stmt = $this->db->prepare("
            UPDATE users
            SET last_login_at = NOW()
            WHERE id = ?
        ");

        $stmt->execute([$userId]);
    }
}