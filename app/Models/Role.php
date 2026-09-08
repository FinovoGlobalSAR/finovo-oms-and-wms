<?php

require_once __DIR__ . '/../../core/Model.php';

class Role extends Model
{
    public function allByCompany(int $companyId): array
    {
        return $this->query(
            "SELECT id, company_id, name, created_at
             FROM roles
             WHERE company_id = :company_id
             ORDER BY name ASC",
            ['company_id' => $companyId]
        )->fetchAll();
    }

    public function belongsToCompany(int $roleId, int $companyId): bool
    {
        return (bool) $this->query(
            "SELECT id FROM roles
             WHERE id = :id AND company_id = :company_id
             LIMIT 1",
            [
                'id' => $roleId,
                'company_id' => $companyId,
            ]
        )->fetch();
    }

    public function ensureDefaultSetup(int $companyId): void
    {
        $company = $this->query(
            "SELECT id FROM companies WHERE id = :id LIMIT 1",
            ['id' => $companyId]
        )->fetch();

        if (!$company) {
            $this->query(
                "INSERT INTO companies (id, name, status)
                 VALUES (:id, :name, 'active')",
                [
                    'id' => $companyId,
                    'name' => 'Finovo',
                ]
            );
        }

        $defaultRoles = ['Admin', 'Manager', 'Sales', 'Warehouse'];

        foreach ($defaultRoles as $roleName) {
            $existing = $this->query(
                "SELECT id FROM roles
                 WHERE company_id = :company_id AND name = :name
                 LIMIT 1",
                [
                    'company_id' => $companyId,
                    'name' => $roleName,
                ]
            )->fetch();

            if (!$existing) {
                $this->query(
                    "INSERT INTO roles (company_id, name)
                     VALUES (:company_id, :name)",
                    [
                        'company_id' => $companyId,
                        'name' => $roleName,
                    ]
                );
            }
        }
    }
}
