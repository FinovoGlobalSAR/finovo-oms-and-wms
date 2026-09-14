<?php
require_once __DIR__ . '/../../core/Model.php';

class Store extends Model
{
    public function all(): array
    {
        $stmt = $this->query("SELECT * FROM stores ORDER BY id ASC");
        return $stmt->fetchAll();
    }

    // Bootstrap ke liye — agar koi store hi nahi hai to ek bana deta hai
    public function first(): array
    {
        $stmt = $this->query("SELECT * FROM stores ORDER BY id ASC LIMIT 1");
        $row = $stmt->fetch();
        if ($row) {
            return $row;
        }
        $id = $this->create('My Store', 'manual', null);
        return $this->find($id);
    }

    public function find(int $id): ?array
    {
        $stmt = $this->query("SELECT * FROM stores WHERE id = ?", [$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(string $name, string $platform, ?string $storeUrl): int
    {
        $this->query(
            "INSERT INTO stores (name, platform, store_url) VALUES (?, ?, ?)",
            [$name, $platform, $storeUrl]
        );
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, string $name, string $platform, ?string $storeUrl): void
    {
        $this->query(
            "UPDATE stores SET name = ?, platform = ?, store_url = ? WHERE id = ?",
            [$name, $platform, $storeUrl, $id]
        );
    }

    public function updateCredentials(int $id, string $storeUrl, string $accessToken): void
    {
        $this->query(
            "UPDATE stores SET store_url = ?, access_token = ? WHERE id = ?",
            [$storeUrl, $accessToken, $id]
        );
    }

    public function updateWooCommerceCredentials(int $id, string $storeUrl, string $consumerKey, string $consumerSecret): void
    {
        $this->query(
            "UPDATE stores SET woocommerce_store_url = ?, woocommerce_consumer_key = ?, woocommerce_consumer_secret = ? WHERE id = ?",
            [$storeUrl, $consumerKey, $consumerSecret, $id]
        );
    }

    public function delete(int $id): void
    {
        $this->query("DELETE FROM stores WHERE id = ?", [$id]);
    }

    public function productCount(int $id): int
    {
        $stmt = $this->query("SELECT COUNT(*) as c FROM products WHERE store_id = ?", [$id]);
        return (int) ($stmt->fetch()['c'] ?? 0);
    }

    public function orderCount(int $id): int
    {
        $stmt = $this->query("SELECT COUNT(*) as c FROM orders WHERE store_id = ?", [$id]);
        return (int) ($stmt->fetch()['c'] ?? 0);
    }
}