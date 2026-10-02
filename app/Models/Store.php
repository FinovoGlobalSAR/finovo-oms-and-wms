<?php
require_once __DIR__ . '/../../core/Model.php';

class Store extends Model
{
    public function all(): array
    {
        $stmt = $this->query("SELECT * FROM stores ORDER BY id ASC");
        return $stmt->fetchAll();
    }

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

    public function updateBridgeCredentials(int $id, string $bridgeUrl, string $apiKey, string $sharedSecret): void
    {
        $this->query(
            "UPDATE stores SET bridge_url = ?, bridge_api_key = ?, bridge_shared_secret = ? WHERE id = ?",
            [$bridgeUrl, $apiKey, $sharedSecret, $id]
        );
    }

    public function updateBigCommerceCredentials(int $id, string $storeHash, string $accessToken): void
    {
        $this->query(
            "UPDATE stores SET bigcommerce_store_hash = ?, bigcommerce_access_token = ? WHERE id = ?",
            [$storeHash, $accessToken, $id]
        );
    }

    public function updatePrestaShopCredentials(int $id, string $storeUrl, string $apiKey): void
    {
        $this->query(
            "UPDATE stores SET prestashop_store_url = ?, prestashop_api_key = ? WHERE id = ?",
            [$storeUrl, $apiKey, $id]
        );
    }

    public function updateOpenCartCredentials(int $id, string $storeUrl, string $apiUsername, string $apiKey): void
    {
        $this->query(
            "UPDATE stores SET opencart_store_url = ?, opencart_api_username = ?, opencart_api_key = ? WHERE id = ?",
            [$storeUrl, $apiUsername, $apiKey, $id]
        );
    }

    public function updateOsCommerceCredentials(int $id, string $storeUrl, string $apiUsername, string $apiKey): void
    {
        $this->query(
            "UPDATE stores SET oscommerce_store_url = ?, oscommerce_api_username = ?, oscommerce_api_key = ? WHERE id = ?",
            [$storeUrl, $apiUsername, $apiKey, $id]
        );
    }

    public function updateWixCredentials(int $id, string $siteId, string $apiKey): void
    {
        $this->query(
            "UPDATE stores SET wix_site_id = ?, wix_api_key = ? WHERE id = ?",
            [$siteId, $apiKey, $id]
        );
    }

    public function updateEbayCredentials(int $id, string $userToken): void
    {
        $this->query(
            "UPDATE stores SET ebay_user_token = ?, ebay_refresh_token = NULL, ebay_token_expires_at = NULL, ebay_refresh_expires_at = NULL WHERE id = ?",
            [$userToken, $id]
        );
    }

    /**
     * "Connect eBay" (OAuth) ke baad tokens save karo.
     */
    public function updateEbayOAuthTokens(int $id, string $accessToken, int $expiresIn, string $refreshToken, int $refreshExpiresIn): void
    {
        $this->query(
            "UPDATE stores SET ebay_user_token = ?, ebay_token_expires_at = ?, ebay_refresh_token = ?, ebay_refresh_expires_at = ? WHERE id = ?",
            [
                $accessToken,
                date('Y-m-d H:i:s', time() + $expiresIn),
                $refreshToken,
                $refreshExpiresIn > 0 ? date('Y-m-d H:i:s', time() + $refreshExpiresIn) : null,
                $id,
            ]
        );
    }

    /**
     * Woh stores jo eBay se connected hain (webhook ke liye).
     */
    public function allEbayConnected(): array
    {
        return $this->query(
            "SELECT * FROM stores WHERE (ebay_refresh_token IS NOT NULL AND ebay_refresh_token <> '') OR (ebay_user_token IS NOT NULL AND ebay_user_token <> '')"
        )->fetchAll();
    }

    public function updateCjCredentials(int $id, string $email, string $apiKey): void
    {
        $this->query(
            "UPDATE stores SET cj_email = ?, cj_api_key = ?, cj_access_token = NULL, cj_token_expires_at = NULL WHERE id = ?",
            [$email, $apiKey, $id]
        );
    }

    public function updateMagentoCredentials(int $id, string $storeUrl, string $accessToken): void
    {
        $this->query(
            "UPDATE stores SET magento_store_url = ?, magento_access_token = ? WHERE id = ?",
            [$storeUrl !== '' ? rtrim($storeUrl, '/') : null, $accessToken !== '' ? $accessToken : null, $id]
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
}