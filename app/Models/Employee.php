<?php

require_once __DIR__ . '/../../core/Model.php';

class Employee extends Model
{
    /**
     * Get all employees
     */
    public function all(): array
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
            INNER JOIN roles
                ON roles.id = users.role_id
            ORDER BY users.id DESC"
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Find one employee
     */
    public function find(int $id): ?array
    {
        $stmt = $this->query(
            "SELECT
                users.id,
                users.name,
                users.email,
                users.status,
                users.role_id,
                roles.name AS role
            FROM users
            INNER JOIN roles
                ON roles.id = users.role_id
            WHERE users.id = ?
            LIMIT 1",
            [$id]
        );

        $employee = $stmt->fetch(PDO::FETCH_ASSOC);

        return $employee ?: null;
    }

    /**
     * Create employee
     */
    public function create(
        string $name,
        string $email,
        string $password,
        int $roleId,
        string $status
    ): bool {
        $stmt = $this->query(
            "INSERT INTO users
                (
                    role_id,
                    name,
                    email,
                    password,
                    status,
                    created_at
                )
             VALUES
                (?, ?, ?, ?, ?, NOW())",
            [
                $roleId,
                $name,
                $email,
                password_hash(
                    $password,
                    PASSWORD_DEFAULT
                ),
                $status
            ]
        );

        return $stmt->rowCount() > 0;
    }

    /**
     * Update employee
     */
    public function update(
        int $id,
        string $name,
        string $email,
        int $roleId,
        string $status
    ): bool {
        $stmt = $this->query(
            "UPDATE users
             SET
                name = ?,
                email = ?,
                role_id = ?,
                status = ?
             WHERE id = ?",
            [
                $name,
                $email,
                $roleId,
                $status,
                $id
            ]
        );

        return $stmt->rowCount() >= 0;
    }

    /**
     * Delete employee
     */
    public function delete(int $id): bool
    {
        $stmt = $this->query(
            "DELETE FROM users
             WHERE id = ?",
            [$id]
        );

        return $stmt->rowCount() > 0;
    }

    /**
     * Get role ID by role name
     */
    public function getRoleId(string $roleName): ?int
    {
        $stmt = $this->query(
            "SELECT id
             FROM roles
             WHERE name = ?
             LIMIT 1",
            [$roleName]
        );

        $role = $stmt->fetch(PDO::FETCH_ASSOC);

        return $role
            ? (int) $role['id']
            : null;
    }

    /**
     * Check whether email already exists
     */
    public function emailExists(
        string $email,
        ?int $excludeId = null
    ): bool {
        if ($excludeId !== null) {
            $stmt = $this->query(
                "SELECT id
                 FROM users
                 WHERE email = ?
                 AND id != ?
                 LIMIT 1",
                [
                    $email,
                    $excludeId
                ]
            );
        } else {
            $stmt = $this->query(
                "SELECT id
                 FROM users
                 WHERE email = ?
                 LIMIT 1",
                [$email]
            );
        }

        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Update employee password
     */
    public function updatePassword(int $id, string $password): bool
    {
        $stmt = $this->query(
            "UPDATE users
             SET password = ?
             WHERE id = ?",
            [
                password_hash(
                    $password,
                    PASSWORD_DEFAULT
                ),
                $id
            ]
        );

        return $stmt->rowCount() >= 0;
    }
}