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
                users.role_id,
                roles.name AS role
            FROM users
            INNER JOIN roles
                ON roles.id = users.role_id
                AND roles.company_id = users.company_id
            WHERE users.company_id = ?
            ORDER BY users.id DESC",
            [$companyId]
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find(
        int $id,
        int $companyId
    ): ?array {
        $stmt = $this->query(
            "SELECT
                users.id,
                users.name,
                users.email,
                users.status,
                users.role_id,
                users.created_at,
                roles.name AS role
            FROM users
            INNER JOIN roles
                ON roles.id = users.role_id
                AND roles.company_id = users.company_id
            WHERE users.id = ?
            AND users.company_id = ?
            LIMIT 1",
            [
                $id,
                $companyId
            ]
        );

        $employee = $stmt->fetch(PDO::FETCH_ASSOC);

        return $employee ?: null;
    }

    public function create(
        int $companyId,
        string $name,
        string $email,
        string $password,
        int $roleId,
        string $status
    ): bool {
        $stmt = $this->query(
            "INSERT INTO users
                (
                    company_id,
                    role_id,
                    name,
                    email,
                    password_hash,
                    status
                )
             VALUES
                (?, ?, ?, ?, ?, ?)",
            [
                $companyId,
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

    public function update(
        int $id,
        int $companyId,
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
             WHERE id = ?
             AND company_id = ?",
            [
                $name,
                $email,
                $roleId,
                $status,
                $id,
                $companyId
            ]
        );

        return $stmt->execute();
    }

    public function delete(
        int $id,
        int $companyId
    ): bool {
        $stmt = $this->query(
            "DELETE FROM users
             WHERE id = ?
             AND company_id = ?",
            [
                $id,
                $companyId
            ]
        );

        return $stmt->rowCount() > 0;
    }

    public function getRoleId(
        string $roleName,
        int $companyId
    ): ?int {
        $stmt = $this->query(
            "SELECT id
             FROM roles
             WHERE name = ?
             AND company_id = ?
             LIMIT 1",
            [
                $roleName,
                $companyId
            ]
        );

        $role = $stmt->fetch(PDO::FETCH_ASSOC);

        return $role
            ? (int) $role['id']
            : null;
    }

    public function emailExists(
        string $email,
        int $companyId,
        ?int $excludeId = null
    ): bool {
        if ($excludeId !== null) {
            $stmt = $this->query(
                "SELECT id
                 FROM users
                 WHERE email = ?
                 AND company_id = ?
                 AND id != ?
                 LIMIT 1",
                [
                    $email,
                    $companyId,
                    $excludeId
                ]
            );
        } else {
            $stmt = $this->query(
                "SELECT id
                 FROM users
                 WHERE email = ?
                 AND company_id = ?
                 LIMIT 1",
                [
                    $email,
                    $companyId
                ]
            );
        }

        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updatePassword(
        int $id,
        int $companyId,
        string $password
    ): bool {
        $stmt = $this->query(
            "UPDATE users
             SET password_hash = ?
             WHERE id = ?
             AND company_id = ?",
            [
                password_hash(
                    $password,
                    PASSWORD_DEFAULT
                ),
                $id,
                $companyId
            ]
        );

        return $stmt->execute();
    }
}