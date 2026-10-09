<?php
// app/Models/Customer.php
require_once __DIR__ . '/../../core/Model.php';

/**
 * Customer = platform (Shopify, WooCommerce, eBay...) ya manual order pe kharidne wala.
 * Login wale "users" se alag — customer kabhi Finovo mein login nahi karta.
 * Data order ke saath aata hai: naam, email, phone, address.
 */
class Customer extends Model
{
    /** Ek store ke saare customers + har ek ke orders ka hisaab */
    public function allWithStats(int $storeId): array
    {
        return $this->allWithStatsForStores([$storeId]);
    }

    /**
     * Kai stores ke customers + har ek ke orders ka hisaab (orders = alag order groups, rows nahi).
     * Saath mein store ka naam bhi (Customers page pe "Store" column ke liye).
     * $storeIds khali ho to koi customer nahi (jaise abhi koi store hi na bana ho).
     */
    public function allWithStatsForStores(array $storeIds): array
    {
        $storeIds = array_values(array_filter(array_map('intval', $storeIds), fn($id) => $id > 0));
        if (empty($storeIds)) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($storeIds), '?'));

        $stmt = $this->query(
            "SELECT c.*, s.name AS store_name,
                    COUNT(DISTINCT COALESCE(o.order_group, CONCAT('row-', o.id))) AS order_count,
                    MAX(o.created_at) AS last_order
             FROM customers c
             INNER JOIN stores s ON s.id = c.store_id
             LEFT JOIN orders o ON o.customer_id = c.id
             WHERE c.store_id IN ({$placeholders})
             GROUP BY c.id
             ORDER BY (MAX(o.created_at) IS NULL), MAX(o.created_at) DESC, c.id DESC",
            $storeIds
        );
        return $stmt->fetchAll();
    }

    /**
     * Customer ki details haath se badlo (Customers page ka "Edit").
     * Khali field = NULL (matlab "Not provided"). Naam zaroori hai.
     */
    public function updateDetails(int $id, array $data): bool
    {
        $clean = fn($key, $max) => ($v = mb_substr(trim((string) ($data[$key] ?? '')), 0, $max)) === '' ? null : $v;

        $this->query(
            "UPDATE customers SET name = ?, email = ?, phone = ?, address = ?, city = ?, country = ? WHERE id = ?",
            [
                mb_substr(trim((string) $data['name']), 0, 150),
                self::cleanEmail($data['email'] ?? null),
                $clean('phone', 50),
                $clean('address', 500),
                $clean('city', 100),
                $clean('country', 100),
                $id,
            ]
        );
        return true;
    }

    /** Is customer ke orders (alag order groups) */
    public function orderCount(int $customerId): int
    {
        return (int) $this->query(
            "SELECT COUNT(DISTINCT COALESCE(order_group, CONCAT('row-', id))) FROM orders WHERE customer_id = ?",
            [$customerId]
        )->fetchColumn();
    }

    /**
     * Customer delete — sirf agar koi order na ho (WHERE NOT EXISTS: beech mein order aa jaye to bhi safe).
     * true = delete hua.
     */
    public function deleteWithoutOrders(int $customerId): bool
    {
        $stmt = $this->query(
            "DELETE FROM customers WHERE id = ? AND NOT EXISTS (SELECT 1 FROM orders WHERE customer_id = ?)",
            [$customerId, $customerId]
        );
        return $stmt->rowCount() > 0;
    }

    public function all(int $storeId): array
    {
        return $this->allWithStats($storeId);
    }

    public function find(int $id): ?array
    {
        $row = $this->query("SELECT * FROM customers WHERE id = ?", [$id])->fetch();
        return $row ?: null;
    }

    public function findByName(int $storeId, string $name): ?array
    {
        $row = $this->query("SELECT * FROM customers WHERE store_id = ? AND name = ? ORDER BY id ASC LIMIT 1", [$storeId, $name])->fetch();
        return $row ?: null;
    }

    public function ordersFor(int $customerId): array
    {
        return $this->query("SELECT * FROM orders WHERE customer_id = ? ORDER BY id DESC", [$customerId])->fetchAll();
    }

    /** Email saaf karo (chhote huruf); galat email ho to null */
    public static function cleanEmail(?string $email): ?string
    {
        $email = strtolower(trim((string) $email));
        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    /**
     * Order ke customer ko dhoondo ya banao, aur jo nayi details aayi hain woh bhar do.
     *
     * Pehchaan ka order:
     *   1) platform ka customer ID (Shopify customer #123 — sab se pakka)
     *   2) email (ek hi insaan do orders mein)
     *   3) naam — sirf tab jab email na ho (guest checkout / manual order)
     *
     * $details: email, phone, address, city, country, external_customer_id (sab optional)
     */
    public function upsertFromOrder(int $storeId, string $name, array $details = [], ?string $source = null): int
    {
        $name = mb_substr(trim($name) !== '' ? trim($name) : 'Unknown', 0, 150);
        $email = self::cleanEmail($details['email'] ?? null);
        $externalId = trim((string) ($details['external_customer_id'] ?? ''));
        $externalId = ($externalId === '' || $externalId === '0') ? null : mb_substr($externalId, 0, 100);

        $existing = null;
        if ($externalId !== null) {
            $existing = $this->query(
                "SELECT * FROM customers WHERE store_id = ? AND external_customer_id = ? LIMIT 1",
                [$storeId, $externalId]
            )->fetch() ?: null;
        }
        if ($existing === null && $email !== null) {
            $existing = $this->query(
                "SELECT * FROM customers WHERE store_id = ? AND email = ? LIMIT 1",
                [$storeId, $email]
            )->fetch() ?: null;
        }
        if ($existing === null && $email === null && $externalId === null) {
            $existing = $this->query(
                "SELECT * FROM customers WHERE store_id = ? AND name = ? AND (email IS NULL OR email = '') LIMIT 1",
                [$storeId, $name]
            )->fetch() ?: null;
        }

        $phone = mb_substr(trim((string) ($details['phone'] ?? '')), 0, 50);
        $address = mb_substr(trim((string) ($details['address'] ?? '')), 0, 500);
        $city = mb_substr(trim((string) ($details['city'] ?? '')), 0, 100);
        $country = mb_substr(trim((string) ($details['country'] ?? '')), 0, 100);

        if ($existing === null) {
            $this->query(
                "INSERT INTO customers (store_id, name, email, phone, address, city, country, source, external_customer_id)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [$storeId, $name, $email, $phone ?: null, $address ?: null, $city ?: null, $country ?: null, $source, $externalId]
            );
            return (int) $this->db->lastInsertId();
        }

        // Pehle se hai: naya order = taaza address / phone. Khali value purani value ko nahi mitati.
        $this->query(
            "UPDATE customers SET
                 email = COALESCE(email, ?),
                 phone = COALESCE(?, phone),
                 address = COALESCE(?, address),
                 city = COALESCE(?, city),
                 country = COALESCE(?, country),
                 external_customer_id = COALESCE(external_customer_id, ?),
                 source = COALESCE(source, ?)
             WHERE id = ?",
            [$email, $phone ?: null, $address ?: null, $city ?: null, $country ?: null, $externalId, $source, (int) $existing['id']]
        );
        return (int) $existing['id'];
    }

    /** Purana function (CSV import waghera) — ab upsertFromOrder() se */
    public function findOrCreate(int $storeId, string $name, ?string $email = null, ?string $externalId = null): int
    {
        return $this->upsertFromOrder($storeId, $name, ['email' => $email, 'external_customer_id' => $externalId]);
    }
}