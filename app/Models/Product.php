<?php
// app/Models/Product.php
require_once __DIR__ . '/../../core/Model.php';

class Product extends Model
{
    public function all(int $storeId): array
    {
        $stmt = $this->query("SELECT * FROM products WHERE store_id = ? ORDER BY id DESC", [$storeId]);
        return $stmt->fetchAll();
    }

    public function create(int $storeId, string $name, float $price, ?string $sku = null): int
    {
        $this->query(
            "INSERT INTO products (store_id, name, sku, price) VALUES (?, ?, ?, ?)",
            [$storeId, $name, $sku, $price]
        );
        return (int) $this->db->lastInsertId();
    }

    // Agar product pehle se hai (naam se match), wahi use karo — warna naya banao
    public function findOrCreate(int $storeId, string $name, float $price, ?string $externalId = null): int
    {
        if ($externalId) {
            $stmt = $this->query("SELECT id FROM products WHERE store_id = ? AND external_product_id = ?", [$storeId, $externalId]);
            $existing = $stmt->fetch();
            if ($existing) return (int) $existing['id'];
        }

        $stmt = $this->query("SELECT id FROM products WHERE store_id = ? AND name = ?", [$storeId, $name]);
        $existing = $stmt->fetch();
        if ($existing) return (int) $existing['id'];

        return $this->create($storeId, $name, $price);
    }
}