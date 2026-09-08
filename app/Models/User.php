<?php

require_once __DIR__ . '/../../core/Model.php';

class User extends Model
{
    public function allByCompany(int $companyId): array
    {
        $sql = "SELECT
                    users.id,
                    users.company_id,
                    users.role_id,
                    users.name,
                    users.email,
                    users.status,
                    users.created_at,
                    roles.name AS role_name
                FROM users
                INNER JOIN roles ON roles.id = users.role_id
                WHERE users.company_id = :company_id
                ORDER BY users.id DESC";

        return $this->query($sql, ['company_id' => $companyId])->fetchAll();
    }

    public function findByIdAndCompany(int $id, int $companyId): ?array
    {
        $sql = "SELECT id, company_id, role_id, name, email, status, created_at
                FROM users
                WHERE id = :id AND company_id = :company_id
                LIMIT 1";

        $user = $this->query($sql, [
            'id' => $id,
            'company_id' => $companyId,
        ])->fetch();

        return $user ?: null;
    }

    public function emailExists(string $email, int $companyId, ?int $ignoreId = null): bool
    {
        $sql = "SELECT id FROM users
                WHERE company_id = :company_id AND email = :email";

        $params = [
            'company_id' => $companyId,
            'email' => $email,
        ];

        if ($ignoreId !== null) {
            $sql .= " AND id != :ignore_id";
            $params['ignore_id'] = $ignoreId;
        }

        $sql .= " LIMIT 1";

        return (bool) $this->query($sql, $params)->fetch();
    }

    public function create(array $data): int
    {
        $sql = "INSERT INTO users
                    (company_id, role_id, name, email, password_hash, status)
                VALUES
                    (:company_id, :role_id, :name, :email, :password_hash, :status)";

        $this->query($sql, [
            'company_id' => $data['company_id'],
            'role_id' => $data['role_id'],
            'name' => $data['name'],
            'email' => $data['email'],
            'password_hash' => $data['password_hash'],
            'status' => $data['status'],
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function updateEmployee(int $id, int $companyId, array $data): bool
    {
        $params = [
            'id' => $id,
            'company_id' => $companyId,
            'role_id' => $data['role_id'],
            'name' => $data['name'],
            'email' => $data['email'],
            'status' => $data['status'],
        ];

        $passwordSql = '';

        if (!empty($data['password_hash'])) {
            $passwordSql = ', password_hash = :password_hash';
            $params['password_hash'] = $data['password_hash'];
        }

        $sql = "UPDATE users
                SET role_id = :role_id,
                    name = :name,
                    email = :email,
                    status = :status
                    {$passwordSql}
                WHERE id = :id AND company_id = :company_id";

        return $this->query($sql, $params)->rowCount() >= 0;
    }

    public function deleteEmployee(int $id, int $companyId): bool
    {
        $stmt = $this->query(
            "DELETE FROM users WHERE id = :id AND company_id = :company_id",
            [
                'id' => $id,
                'company_id' => $companyId,
            ]
        );

        return $stmt->rowCount() > 0;
    }
}
