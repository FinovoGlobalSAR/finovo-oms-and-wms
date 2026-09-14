<?php
return [
    'up' => function (PDO $db) {

        $productColumns = $db->query("SHOW COLUMNS FROM products")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('image_url', $productColumns)) {
            $db->exec("ALTER TABLE products ADD COLUMN image_url VARCHAR(500) NULL");
        }

        $db->exec("CREATE TABLE IF NOT EXISTS product_variants (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT NOT NULL,
            label VARCHAR(150) NOT NULL,
            attributes VARCHAR(255) NULL,
            sku VARCHAR(100) NULL,
            price DECIMAL(10,2) NULL,
            image_url VARCHAR(500) NULL,
            stock_quantity INT NOT NULL DEFAULT 0,
            external_variant_id VARCHAR(100) NULL,
            external_wc_variant_id VARCHAR(100) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
        ) ENGINE=InnoDB");

        $db->exec("CREATE TABLE IF NOT EXISTS variant_warehouse_stock (
            id INT AUTO_INCREMENT PRIMARY KEY,
            variant_id INT NOT NULL,
            warehouse_id INT NOT NULL,
            stock_quantity INT NOT NULL DEFAULT 0,
            UNIQUE KEY variant_warehouse_unique (variant_id, warehouse_id),
            FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE CASCADE,
            FOREIGN KEY (warehouse_id) REFERENCES warehouses(id) ON DELETE CASCADE
        ) ENGINE=InnoDB");

        $orderColumns = $db->query("SHOW COLUMNS FROM orders")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('variant_id', $orderColumns)) {
            $db->exec("ALTER TABLE orders ADD COLUMN variant_id INT NULL");
        }
        if (!in_array('variant_label', $orderColumns)) {
            $db->exec("ALTER TABLE orders ADD COLUMN variant_label VARCHAR(150) NULL");
        }
    },
    'down' => function (PDO $db) {
        $orderColumns = $db->query("SHOW COLUMNS FROM orders")->fetchAll(PDO::FETCH_COLUMN);
        if (in_array('variant_id', $orderColumns)) {
            $db->exec("ALTER TABLE orders DROP COLUMN variant_id");
        }
        if (in_array('variant_label', $orderColumns)) {
            $db->exec("ALTER TABLE orders DROP COLUMN variant_label");
        }
        $db->exec("DROP TABLE IF EXISTS variant_warehouse_stock");
        $db->exec("DROP TABLE IF EXISTS product_variants");

        $productColumns = $db->query("SHOW COLUMNS FROM products")->fetchAll(PDO::FETCH_COLUMN);
        if (in_array('image_url', $productColumns)) {
            $db->exec("ALTER TABLE products DROP COLUMN image_url");
        }
    },
];