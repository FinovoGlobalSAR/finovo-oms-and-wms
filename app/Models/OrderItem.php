<?php

require_once __DIR__ . '/../../core/Model.php';

class OrderItem extends Model
{
    public function forOrder(int $orderId): array
    {
        $stmt = $this->query(
            'SELECT * FROM order_items WHERE order_id = ? ORDER BY id ASC',
            [$orderId]
        );

        return $stmt->fetchAll();
    }

    public function create(array $data): int
    {
        $this->query(
            'INSERT INTO order_items
                (order_id, product_id, product_name, sku, variant, quantity, unit_price, line_total)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $data['order_id'],
                $data['product_id'] ?? null,
                $data['product_name'],
                $data['sku'] ?? null,
                $data['variant'] ?? null,
                $data['quantity'],
                $data['unit_price'],
                $data['line_total'],
            ]
        );

        return (int) $this->db->lastInsertId();
    }
}
