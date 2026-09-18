<?php
require_once __DIR__ . '/../../core/Model.php';

class Store extends Model
{
    public function all(): array
    {
        $stmt = $this->query("SELECT * FROM stores ORDER BY id ASC");
        $rows = $stmt->fetchAll();
        return array_map([$this, 'decryptRow'], $rows);
    }

    public function first(): array
    {
        $stmt = $this->query("SELECT * FROM stores ORDER BY id ASC LIMIT 1");
        $row = $stmt->fetch();
        if ($row) {
            return $this->decryptRow($row);
        }
        $id = $this->create('My Store', 'manual', null);
        return $this->find($id);
    }

    public function find(int $id): ?array
    {
        $stmt = $this->query("SELECT * FROM stores WHERE id = ?", [$id]);
        $row = $stmt->fetch();
        return $row ? $this->decryptRow($row) : null;
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
        require_once __DIR__ . '/../Services/EncryptionService.php';
        $this->query(
            "UPDATE stores SET store_url = ?, access_token = ? WHERE id = ?",
            [$storeUrl, EncryptionService::encrypt($accessToken), $id]
        );
    }

    public function updateWooCommerceCredentials(int $id, string $storeUrl, string $consumerKey, string $consumerSecret): void
    {
        require_once __DIR__ . '/../Services/EncryptionService.php';
        $this->query(
            "UPDATE stores SET woocommerce_store_url = ?, woocommerce_consumer_key = ?, woocommerce_consumer_secret = ? WHERE id = ?",
            [$storeUrl, EncryptionService::encrypt($consumerKey), EncryptionService::encrypt($consumerSecret), $id]
        );
    }

    public function updateBridgeCredentials(int $id, string $bridgeUrl, string $apiKey, string $sharedSecret): void
    {
        require_once __DIR__ . '/../Services/EncryptionService.php';
        $this->query(
            "UPDATE stores SET bridge_url = ?, bridge_api_key = ?, bridge_shared_secret = ? WHERE id = ?",
            [$bridgeUrl, EncryptionService::encrypt($apiKey), EncryptionService::encrypt($sharedSecret), $id]
        );
    }

    public function markHealthy(int $id): void
    {
        $this->query(
            "UPDATE stores SET health_status = 'connected', last_successful_sync = NOW(), last_error = NULL WHERE id = ?",
            [$id]
        );
    }

    public function markUnhealthy(int $id, string $error): void
    {
        $this->query(
            "UPDATE stores SET health_status = 'sync_error', last_error = ? WHERE id = ?",
            [$error, $id]
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

    private function decryptRow(array $row): array
    {
        require_once __DIR__ . '/../Services/EncryptionService.php';

        $row['access_token'] = EncryptionService::decrypt($row['access_token'] ?? null);
        $row['woocommerce_consumer_key'] = EncryptionService::decrypt($row['woocommerce_consumer_key'] ?? null);
        $row['woocommerce_consumer_secret'] = EncryptionService::decrypt($row['woocommerce_consumer_secret'] ?? null);
        $row['bridge_api_key'] = EncryptionService::decrypt($row['bridge_api_key'] ?? null);
        $row['bridge_shared_secret'] = EncryptionService::decrypt($row['bridge_shared_secret'] ?? null);

        return $row;
    }
}