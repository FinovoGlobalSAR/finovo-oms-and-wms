<?php

require_once __DIR__ . '/../../core/Model.php';

class Order extends Model
{
    public function all(): array
    {
        $stmt = $this->query("SELECT * FROM orders ORDER BY id DESC");
        return $stmt->fetchAll();
    }

    public function create(string $customerName, string $productName, int $quantity, float $price, string $source = 'manual'): int
    {
        $this->query(
            "INSERT INTO orders (customer_name, product_name, quantity, price, source) VALUES (?, ?, ?, ?, ?)",
            [$customerName, $productName, $quantity, $price, $source]
        );
        return (int) $this->db->lastInsertId();
    }
}