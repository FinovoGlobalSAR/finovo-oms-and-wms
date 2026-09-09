<?php

require_once __DIR__ . '/../../core/Database.php';

class User
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /*
    |--------------------------------------------------------------------------
    | Get all employees of a company
    |--------------------------------------------------------------------------
    */
    public function allByCompany(int $companyId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                u.id,
                u.company_id,
                u.role_id,
                u.name,
                u.email,
                u.status,
                u.created_at,
                r.name AS role_name
            FROM users u
            LEFT JOIN roles r
                ON r.id = u.role_id
                AND r.company_id = u.company_id
            WHERE u.company_id = ?
            ORDER BY u.id DESC
        ");

        $stmt->execute([$companyId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /*
    |--------------------------------------------------------------------------
    | Find employee by ID and company
    |--------------------------------------------------------------------------
    */
    public function findByIdAndCompany(int $id, int $companyId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT
                u.id,
                u.company_id,
                u.role_id,
                u.name,
                u.email,
                u.status,
                u.created_at,
                r.name AS role_name
            FROM users u
            LEFT JOIN roles r
                ON r.id = u.role_id
                AND r.company_id = u.company_id
            WHERE u.id = ?
            AND u.company_id = ?
            LIMIT 1
        ");

        $stmt->execute([
            $id,
            $companyId
        ]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    /*
    |--------------------------------------------------------------------------
    | Find user by email - used for login
    |--------------------------------------------------------------------------
    */
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare("
            SELECT
                u.id,
                u.company_id,
                u.role_id,
                u.name,
                u.email,
                u.password_hash AS password,
                u.status,
                r.name AS role,
                c.name AS company_name
            FROM users u

            INNER JOIN roles r
                ON r.id = u.role_id
                AND r.company_id = u.company_id

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

    /*
    |--------------------------------------------------------------------------
    | Check duplicate employee email in same company
    |--------------------------------------------------------------------------
    */
    public function emailExists(
        string $email,
        int $companyId,
        ?int $excludeId = null
    ): bool {

        if ($excludeId !== null) {

            $stmt = $this->db->prepare("
                SELECT id
                FROM users
                WHERE email = ?
                AND company_id = ?
                AND id != ?
                LIMIT 1
            ");

            $stmt->execute([
                $email,
                $companyId,
                $excludeId
            ]);

        } else {

            $stmt = $this->db->prepare("
                SELECT id
                FROM users
                WHERE email = ?
                AND company_id = ?
                LIMIT 1
            ");

            $stmt->execute([
                $email,
                $companyId
            ]);
        }

        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /*
    |--------------------------------------------------------------------------
    | Create employee
    |--------------------------------------------------------------------------
    */
    public function create(array $data): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO users (
                company_id,
                role_id,
                name,
                email,
                password_hash,
                status
            )
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        return $stmt->execute([
            $data['company_id'],
            $data['role_id'],
            $data['name'],
            $data['email'],
            $data['password_hash'],
            $data['status']
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Update employee
    |--------------------------------------------------------------------------
    */
    public function updateEmployee(
        int $id,
        int $companyId,
        array $data
    ): bool {

        if (!empty($data['password_hash'])) {

            $stmt = $this->db->prepare("
                UPDATE users
                SET
                    role_id = ?,
                    name = ?,
                    email = ?,
                    password_hash = ?,
                    status = ?
                WHERE id = ?
                AND company_id = ?
            ");

            return $stmt->execute([
                $data['role_id'],
                $data['name'],
                $data['email'],
                $data['password_hash'],
                $data['status'],
                $id,
                $companyId
            ]);
        }

        $stmt = $this->db->prepare("
            UPDATE users
            SET
                role_id = ?,
                name = ?,
                email = ?,
                status = ?
            WHERE id = ?
            AND company_id = ?
        ");

        return $stmt->execute([
            $data['role_id'],
            $data['name'],
            $data['email'],
            $data['status'],
            $id,
            $companyId
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Delete employee
    |--------------------------------------------------------------------------
    */
    public function deleteEmployee(int $id, int $companyId): bool
    {
        $stmt = $this->db->prepare("
            DELETE FROM users
            WHERE id = ?
            AND company_id = ?
        ");

        $stmt->execute([
            $id,
            $companyId
        ]);

        return $stmt->rowCount() > 0;
    }
}