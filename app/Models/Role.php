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

    /**
     * Sirf yehi 4 roles valid hain (database mein koi purana / junk role ho to woh assign nahi ho sakta).
     * "Company Admin" = Admin ka purana naam (kuch databases mein abhi bhi yehi hai).
     */
    public function exists(int $roleId): bool
    {
        $role = $this->query(
            "SELECT id FROM roles WHERE id = :id AND name IN ('Admin', 'Company Admin', 'Manager', 'Sales Staff', 'Warehouse Staff') LIMIT 1",
            ['id' => $roleId]
        )->fetch(PDO::FETCH_ASSOC);

        return (bool) $role;
    }

    /** Role ka naam chhote huruf mein ('admin', 'manager'...). Purana "Company Admin" bhi 'admin'. */
    public function slugOf(int $roleId): string
    {
        $name = $this->query("SELECT name FROM roles WHERE id = :id LIMIT 1", ['id' => $roleId])->fetchColumn();
        $slug = strtolower(trim((string) $name));
        return $slug === 'company admin' ? 'admin' : $slug;
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