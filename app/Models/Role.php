<?php
require_once __DIR__ . '/../../core/Model.php';

class Role extends Model
{
    // Employee form ke dropdown mein SIRF ye 4 role dikhenge — kitni bhi junk roles
    // database mein pehle se hon, unhe ye method ignore kar deta hai.
    public function all(): array
    {
        $stmt = $this->query(
            "SELECT id, name, created_at FROM roles
             WHERE name IN ('Admin', 'Manager', 'Sales Staff', 'Warehouse Staff')
             ORDER BY FIELD(name, 'Admin', 'Manager', 'Sales Staff', 'Warehouse Staff')"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function exists(int $roleId): bool
    {
        $role = $this->query(
            "SELECT id FROM roles WHERE id = :id LIMIT 1",
            ['id' => $roleId]
        )->fetch(PDO::FETCH_ASSOC);

        return (bool) $role;
    }

    public function ensureDefaultSetup(): void
    {
        $defaultRoles = ['Admin', 'Manager', 'Sales Staff', 'Warehouse Staff'];

        foreach ($defaultRoles as $roleName) {
            $existingRole = $this->query(
                "SELECT id FROM roles WHERE name = :name LIMIT 1",
                ['name' => $roleName]
            )->fetch(PDO::FETCH_ASSOC);

            if (!$existingRole) {
                $this->query("INSERT INTO roles (name) VALUES (:name)", ['name' => $roleName]);
            }
        }
    }
}