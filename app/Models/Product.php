<?php
// app/Models/Product.php
require_once __DIR__ . '/../../core/Model.php';

class Product extends Model
{
    // Ab store ka apna product + shared warehouse ke through stock wala product, dono dikhte hain
    public function all(int $storeId): array
    {
        $stmt = $this->query(
            "SELECT DISTINCT p.* FROM products p
             WHERE p.store_id = ?
             OR p.id IN (
                 SELECT pws.product_id FROM product_warehouse_stock pws
                 INNER JOIN store_warehouses sw ON sw.warehouse_id = pws.warehouse_id
                 WHERE sw.store_id = ?
             )
             ORDER BY p.id DESC",
            [$storeId, $storeId]
        );
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->query("SELECT * FROM products WHERE id = ?", [$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByName(int $storeId, string $name): ?array
    {
        $stmt = $this->query("SELECT * FROM products WHERE store_id = ? AND name = ?", [$storeId, $name]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
    public function create(int $storeId, string $name, float $price, ?string $sku = null, int $stock = 0, ?string $imageUrl = null): int
    {
        $this->query(
            "INSERT INTO products (store_id, name, sku, price, stock_quantity, image_url) VALUES (?, ?, ?, ?, ?, ?)",
            [$storeId, $name, $sku, $price, $stock, $imageUrl]
        );
        return (int) $this->db->lastInsertId();
    }

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

    public function updateImage(int $productId, string $imageUrl): void
    {
        $this->query("UPDATE products SET image_url = ? WHERE id = ?", [$imageUrl, $productId]);
    }

    // ---------- Edit / Delete (CRUD) ----------

    public function update(int $id, string $name, ?string $sku, float $price, int $lowStockThreshold): void
    {
        $this->query(
            "UPDATE products SET name = ?, sku = ?, price = ?, low_stock_threshold = ? WHERE id = ?",
            [$name, $sku, $price, $lowStockThreshold, $id]
        );
    }
    public function delete(int $id): void
    {
        $this->query("DELETE FROM products WHERE id = ?", [$id]);
    }

    public function hasVariants(int $productId): bool
    {
        $stmt = $this->query("SELECT id FROM product_variants WHERE product_id = ? LIMIT 1", [$productId]);
        return (bool) $stmt->fetch();
    }

    public function variantCount(int $productId): int
    {
        $stmt = $this->query("SELECT COUNT(*) as c FROM product_variants WHERE product_id = ?", [$productId]);
        return (int) ($stmt->fetch()['c'] ?? 0);
    }

    // N+1 query se bachne ke liye — sabhi products ke variant counts ek hi
    // query mein le aata hai, loop mein alag-alag query chalane ki jagah.
    public function variantCountsForProducts(array $productIds): array
    {
        if (empty($productIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        $stmt = $this->query(
            "SELECT product_id, COUNT(*) as c FROM product_variants WHERE product_id IN ({$placeholders}) GROUP BY product_id",
            $productIds
        );

        $counts = [];
        foreach ($stmt->fetchAll() as $row) {
            $counts[(int) $row['product_id']] = (int) $row['c'];
        }
        return $counts;
    }

    // Order list page ke currency ke liye — batata hai product Shopify/WooCommerce se aaya hai ya nahi
    public function isExternal(int $productId): bool
    {
        $stmt = $this->query(
            "SELECT external_product_id, external_wc_product_id FROM products WHERE id = ?",
            [$productId]
        );
        $row = $stmt->fetch();

        if (!$row) {
            return false;
        }

        return !empty($row['external_product_id']) || !empty($row['external_wc_product_id']);
    }
    // ---------- Warehouse-aware stock methods (simple/no-variant products) ----------

    public function getWarehouseStock(int $productId, int $warehouseId): int
    {
        $stmt = $this->query(
            "SELECT stock_quantity FROM product_warehouse_stock WHERE product_id = ? AND warehouse_id = ?",
            [$productId, $warehouseId]
        );
        $row = $stmt->fetch();
        return (int) ($row['stock_quantity'] ?? 0);
    }

    public function warehouseStockList(int $productId): array
    {
        $stmt = $this->query(
            "SELECT w.id as warehouse_id, w.name as warehouse_name, w.location,
                    COALESCE(pws.stock_quantity, 0) as stock_quantity
             FROM warehouses w
             LEFT JOIN product_warehouse_stock pws ON pws.warehouse_id = w.id AND pws.product_id = ?
             WHERE w.store_id = (SELECT store_id FROM products WHERE id = ?)
             ORDER BY w.name ASC",
            [$productId, $productId]
        );
        return $stmt->fetchAll();
    }

    public function productsInWarehouse(int $warehouseId): array
    {
        $stmt = $this->query(
            "SELECT DISTINCT p.id, p.name, p.sku, p.price, p.image_url, p.external_product_id, p.external_wc_product_id,
                    COALESCE(pws.stock_quantity, 0) as warehouse_stock
             FROM products p
             INNER JOIN store_warehouses sw ON sw.store_id = p.store_id AND sw.warehouse_id = ?
             LEFT JOIN product_warehouse_stock pws ON pws.product_id = p.id AND pws.warehouse_id = ?
             ORDER BY p.name ASC",
            [$warehouseId, $warehouseId]
        );
        return $stmt->fetchAll();
    }
    public function availableForOrder(int $storeId, int $warehouseId): array
    {
        $stmt = $this->query(
            "SELECT p.id, p.name, p.sku, p.price, p.external_product_id, p.external_wc_product_id,
                    pws.stock_quantity as warehouse_stock
             FROM products p
             INNER JOIN product_warehouse_stock pws ON pws.product_id = p.id AND pws.warehouse_id = ?
             WHERE pws.stock_quantity > 0
             AND p.id NOT IN (SELECT DISTINCT product_id FROM product_variants)
             ORDER BY p.name ASC",
            [$warehouseId]
        );
        return $stmt->fetchAll();
    }

    public function receiveStock(int $productId, int $warehouseId, int $quantity): void
    {
        $quantity = max(0, $quantity);
        $this->query(
            "INSERT INTO product_warehouse_stock (product_id, warehouse_id, stock_quantity)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE stock_quantity = stock_quantity + VALUES(stock_quantity)",
            [$productId, $warehouseId, $quantity]
        );
        $this->recomputeStock($productId);
    }

    public function setWarehouseStock(int $productId, int $warehouseId, int $quantity): void
    {
        $quantity = max(0, $quantity);
        $this->query(
            "INSERT INTO product_warehouse_stock (product_id, warehouse_id, stock_quantity)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE stock_quantity = VALUES(stock_quantity)",
            [$productId, $warehouseId, $quantity]
        );
        $this->recomputeStock($productId);
    }
    public function decreaseWarehouseStock(int $productId, int $warehouseId, int $quantity): void
    {
        $this->query(
            "INSERT INTO product_warehouse_stock (product_id, warehouse_id, stock_quantity)
             VALUES (?, ?, 0)
             ON DUPLICATE KEY UPDATE stock_quantity = stock_quantity",
            [$productId, $warehouseId]
        );
        $this->query(
            "UPDATE product_warehouse_stock SET stock_quantity = GREATEST(stock_quantity - ?, 0)
             WHERE product_id = ? AND warehouse_id = ?",
            [$quantity, $productId, $warehouseId]
        );
        $this->recomputeStock($productId);
    }

    public function increaseWarehouseStock(int $productId, int $warehouseId, int $quantity): void
    {
        $quantity = max(0, $quantity);
        $this->query(
            "INSERT INTO product_warehouse_stock (product_id, warehouse_id, stock_quantity)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE stock_quantity = stock_quantity + VALUES(stock_quantity)",
            [$productId, $warehouseId, $quantity]
        );
        $this->recomputeStock($productId);
    }

    public function recomputeStock(int $productId): void
    {
        if ($this->hasVariants($productId)) {
            $stmt = $this->query(
                "SELECT COALESCE(SUM(pv.stock_quantity), 0) as total FROM product_variants pv WHERE pv.product_id = ?",
                [$productId]
            );
        } else {
            $stmt = $this->query(
                "SELECT COALESCE(SUM(stock_quantity), 0) as total FROM product_warehouse_stock WHERE product_id = ?",
                [$productId]
            );
        }
        $total = (int) ($stmt->fetch()['total'] ?? 0);
        $this->query("UPDATE products SET stock_quantity = ? WHERE id = ?", [$total, $productId]);
    }
    public function lowStockList(int $storeId): array
    {
        $stmt = $this->query(
            "SELECT id, name, sku, stock_quantity, low_stock_threshold
             FROM products
             WHERE store_id = ? AND stock_quantity <= low_stock_threshold
             ORDER BY stock_quantity ASC",
            [$storeId]
        );
        return $stmt->fetchAll();
    }
}