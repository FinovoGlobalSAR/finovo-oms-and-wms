<?php
// app/Models/Customer.php
require_once __DIR__ . '/../../core/Model.php';

class Customer extends Model
{
    public function all(int $storeId): array
    {
        $stmt = $this->query("SELECT * FROM customers WHERE store_id = ? ORDER BY id DESC", [$storeId]);
        return $stmt->fetchAll();
    }

    // Agar customer pehle se hai (naam se match), wahi use karo — warna naya banao
    public function findOrCreate(int $storeId, string $name, ?string $email = null, ?string $externalId = null): int
    {
        if ($externalId) {
            $stmt = $this->query("SELECT id FROM customers WHERE store_id = ? AND external_customer_id = ?", [$storeId, $externalId]);
            $existing = $stmt->fetch();
            if ($existing) return (int) $existing['id'];
        }

        $stmt = $this->query("SELECT id FROM customers WHERE store_id = ? AND name = ?", [$storeId, $name]);
        $existing = $stmt->fetch();
        if ($existing) return (int) $existing['id'];

        $this->query(
            "INSERT INTO customers (store_id, name, email, external_customer_id) VALUES (?, ?, ?, ?)",
            [$storeId, $name, $email, $externalId]
        );
        return (int) $this->db->lastInsertId();
    }
}