<?php
require_once __DIR__ . '/../../core/Model.php';

class Supplier extends Model
{
    public function all(int $storeId): array
    {
        $stmt = $this->query("SELECT * FROM suppliers WHERE store_id = ? ORDER BY name ASC", [$storeId]);
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->query("SELECT * FROM suppliers WHERE id = ?", [$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(int $storeId, string $name, ?string $contactPerson, ?string $email, ?string $phone): int
    {
        $this->query(
            "INSERT INTO suppliers (store_id, name, contact_person, email, phone) VALUES (?, ?, ?, ?, ?)",
            [$storeId, $name, $contactPerson, $email, $phone]
        );
        return (int) $this->db->lastInsertId();
    }

    public function delete(int $id): void
    {
        $this->query("DELETE FROM suppliers WHERE id = ?", [$id]);
    }
}