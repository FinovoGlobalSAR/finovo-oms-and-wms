<?php

require_once __DIR__ . '/../../core/Model.php';

class Role extends Model
{
    /*
    |--------------------------------------------------------------------------
    | Get company roles
    |--------------------------------------------------------------------------
    */
    public function allByCompany(int $companyId): array
    {
        return $this->query(
            "
            SELECT
                id,
                company_id,
                name,
                created_at
            FROM roles
            WHERE company_id = :company_id
            ORDER BY name ASC
            ",
            [
                'company_id' => $companyId
            ]
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    /*
    |--------------------------------------------------------------------------
    | Check role belongs to current company
    |--------------------------------------------------------------------------
    */
    public function belongsToCompany(
        int $roleId,
        int $companyId
    ): bool {

        $role = $this->query(
            "
            SELECT id
            FROM roles
            WHERE id = :id
            AND company_id = :company_id
            LIMIT 1
            ",
            [
                'id' => $roleId,
                'company_id' => $companyId
            ]
        )->fetch(PDO::FETCH_ASSOC);

        return (bool) $role;
    }

    /*
    |--------------------------------------------------------------------------
    | Temporary default company + roles
    |--------------------------------------------------------------------------
    */
    public function ensureDefaultSetup(int $companyId): void
    {
        /*
         * Check company exists.
         */
        $company = $this->query(
            "
            SELECT id
            FROM companies
            WHERE id = :id
            LIMIT 1
            ",
            [
                'id' => $companyId
            ]
        )->fetch(PDO::FETCH_ASSOC);

        /*
         * Temporary company until proper auth/company creation exists.
         */
        if (!$company) {

            $this->query(
                "
                INSERT INTO companies (
                    id,
                    name,
                    status
                )
                VALUES (
                    :id,
                    :name,
                    'active'
                )
                ",
                [
                    'id' => $companyId,
                    'name' => 'Finovo'
                ]
            );
        }

        /*
         * Default roles.
         */
        $defaultRoles = [
            'Admin',
            'Manager',
            'Sales',
            'Warehouse'
        ];

        foreach ($defaultRoles as $roleName) {

            $existingRole = $this->query(
                "
                SELECT id
                FROM roles
                WHERE company_id = :company_id
                AND name = :name
                LIMIT 1
                ",
                [
                    'company_id' => $companyId,
                    'name' => $roleName
                ]
            )->fetch(PDO::FETCH_ASSOC);

            if (!$existingRole) {

                $this->query(
                    "
                    INSERT INTO roles (
                        company_id,
                        name
                    )
                    VALUES (
                        :company_id,
                        :name
                    )
                    ",
                    [
                        'company_id' => $companyId,
                        'name' => $roleName
                    ]
                );
            }
        }
    }
}