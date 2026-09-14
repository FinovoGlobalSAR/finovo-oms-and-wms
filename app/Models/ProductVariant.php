<?php
require_once __DIR__ . '/../../core/Model.php';

class ProductVariant extends Model
{
    public function allByProduct(int $productId): array
    {
        $stmt = $this->query("SELECT * FROM product_variants WHERE product_id = ? ORDER BY label ASC", [$productId]);
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->query("SELECT * FROM product_variants WHERE id = ?", [$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function hasAny(int $productId): bool
    {
        $stmt = $this->query("SELECT id FROM product_variants WHERE product_id = ? LIMIT 1", [$productId]);
        return (bool) $stmt->fetch();
    }

    public function create(int $productId, string $label, ?string $attributes, ?string $sku, ?float $price, ?string $imageUrl, ?string $externalId = null, ?string $externalWcId = null): int
    {
        $this->query(
            "INSERT INTO product_variants (product_id, label, attributes, sku, price, image_url, external_variant_id, external_wc_variant_id)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [$productId, $label, $attributes, $sku, $price, $imageUrl, $externalId, $externalWcId]
        );
        return (int) $this->db->lastInsertId();
    }

    public function findOrCreate(int $productId, string $label, ?string $attributes, ?string $sku, ?float $price, ?string $imageUrl, ?string $externalId = null, ?string $externalWcId = null): int
    {
        if ($externalId) {
            $stmt = $this->query("SELECT id FROM product_variants WHERE product_id = ? AND external_variant_id = ?", [$productId, $externalId]);
            $existing = $stmt->fetch();
            if ($existing) return (int) $existing['id'];
        }
        if ($externalWcId) {
            $stmt = $this->query("SELECT id FROM product_variants WHERE product_id = ? AND external_wc_variant_id = ?", [$productId, $externalWcId]);
            $existing = $stmt->fetch();
            if ($existing) return (int) $existing['id'];
        }

        $stmt = $this->query("SELECT id FROM product_variants WHERE product_id = ? AND label = ?", [$productId, $label]);
        $existing = $stmt->fetch();
        if ($existing) return (int) $existing['id'];

        return $this->create($productId, $label, $attributes, $sku, $price, $imageUrl, $externalId, $externalWcId);
    }

    public function getWarehouseStock(int $variantId, int $warehouseId): int
    {
        $stmt = $this->query(
            "SELECT stock_quantity FROM variant_warehouse_stock WHERE variant_id = ? AND warehouse_id = ?",
            [$variantId, $warehouseId]
        );
        $row = $stmt->fetch();
        return (int) ($row['stock_quantity'] ?? 0);
    }

    public function warehouseStockMatrix(int $productId): array
    {
        $stmt = $this->query(
            "SELECT pv.id as variant_id, pv.label, pv.sku,
                    w.id as warehouse_id, w.name as warehouse_name,
                    COALESCE(vws.stock_quantity, 0) as stock_quantity
             FROM product_variants pv
             CROSS JOIN warehouses w
             LEFT JOIN variant_warehouse_stock vws ON vws.variant_id = pv.id AND vws.warehouse_id = w.id
             INNER JOIN store_warehouses sw ON sw.warehouse_id = w.id AND sw.store_id = (SELECT store_id FROM products WHERE id = ?)
             WHERE pv.product_id = ?
             ORDER BY pv.label ASC, w.name ASC",
            [$productId, $productId]
        );
        return $stmt->fetchAll();
    }

    public function receiveStock(int $variantId, int $warehouseId, int $quantity): void
    {
        $quantity = max(0, $quantity);
        $this->query(
            "INSERT INTO variant_warehouse_stock (variant_id, warehouse_id, stock_quantity)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE stock_quantity = stock_quantity + VALUES(stock_quantity)",
            [$variantId, $warehouseId, $quantity]
        );
        $this->recomputeVariantStock($variantId);
    }

    public function setWarehouseStock(int $variantId, int $warehouseId, int $quantity): void
    {
        $quantity = max(0, $quantity);
        $this->query(
            "INSERT INTO variant_warehouse_stock (variant_id, warehouse_id, stock_quantity)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE stock_quantity = VALUES(stock_quantity)",
            [$variantId, $warehouseId, $quantity]
        );
        $this->recomputeVariantStock($variantId);
    }

    public function decreaseWarehouseStock(int $variantId, int $warehouseId, int $quantity): void
    {
        $this->query(
            "INSERT INTO variant_warehouse_stock (variant_id, warehouse_id, stock_quantity)
             VALUES (?, ?, 0)
             ON DUPLICATE KEY UPDATE stock_quantity = stock_quantity",
            [$variantId, $warehouseId]
        );
        $this->query(
            "UPDATE variant_warehouse_stock SET stock_quantity = GREATEST(stock_quantity - ?, 0)
             WHERE variant_id = ? AND warehouse_id = ?",
            [$quantity, $variantId, $warehouseId]
        );
        $this->recomputeVariantStock($variantId);
    }

    public function increaseWarehouseStock(int $variantId, int $warehouseId, int $quantity): void
    {
        $quantity = max(0, $quantity);
        $this->query(
            "INSERT INTO variant_warehouse_stock (variant_id, warehouse_id, stock_quantity)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE stock_quantity = stock_quantity + VALUES(stock_quantity)",
            [$variantId, $warehouseId, $quantity]
        );
        $this->recomputeVariantStock($variantId);
    }

    public function recomputeVariantStock(int $variantId): void
    {
        $stmt = $this->query(
            "SELECT COALESCE(SUM(stock_quantity), 0) as total FROM variant_warehouse_stock WHERE variant_id = ?",
            [$variantId]
        );
        $total = (int) ($stmt->fetch()['total'] ?? 0);
        $this->query("UPDATE product_variants SET stock_quantity = ? WHERE id = ?", [$total, $variantId]);

        $productRow = $this->query("SELECT product_id FROM product_variants WHERE id = ?", [$variantId])->fetch();
        if ($productRow) {
            require_once __DIR__ . '/Product.php';
            (new Product())->recomputeStock((int) $productRow['product_id']);
        }
    }

    // Order form ke dropdown ke liye — jis bhi warehouse mein stock hai, wo variant orderable hai,
    // chahe wo product kisi bhi store ka "apna" ho. Warehouse hi sach ka source hai.
    public function availableForOrder(int $storeId, int $warehouseId): array
    {
        $stmt = $this->query(
            "SELECT pv.id as variant_id, pv.product_id, pv.label, pv.sku,
                    COALESCE(pv.price, p.price) as price,
                    vws.stock_quantity as warehouse_stock,
                    p.name as product_name, p.external_product_id, p.external_wc_product_id
             FROM product_variants pv
             INNER JOIN products p ON p.id = pv.product_id
             INNER JOIN variant_warehouse_stock vws ON vws.variant_id = pv.id AND vws.warehouse_id = ?
             WHERE vws.stock_quantity > 0
             ORDER BY p.name ASC, pv.label ASC",
            [$warehouseId]
        );
        return $stmt->fetchAll();
    }
}