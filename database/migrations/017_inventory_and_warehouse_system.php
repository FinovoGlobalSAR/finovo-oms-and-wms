<?php
return [
    'up' => function (PDO $db) {

        $settingsColumns = $db->query("SHOW COLUMNS FROM settings")->fetchAll(PDO::FETCH_COLUMN);

        if (!in_array('woocommerce_store_url', $settingsColumns)) {
            $db->exec("ALTER TABLE settings ADD COLUMN woocommerce_store_url VARCHAR(255) NULL");
        }
        if (!in_array('woocommerce_consumer_key', $settingsColumns)) {
            $db->exec("ALTER TABLE settings ADD COLUMN woocommerce_consumer_key VARCHAR(255) NULL");
        }
        if (!in_array('woocommerce_consumer_secret', $settingsColumns)) {
            $db->exec("ALTER TABLE settings ADD COLUMN woocommerce_consumer_secret VARCHAR(255) NULL");
        }

        // ---------- Orders: WooCommerce ID, Status, Payment Status, Order Group ----------
        $orderColumns = $db->query("SHOW COLUMNS FROM orders")->fetchAll(PDO::FETCH_COLUMN);

        if (!in_array('external_wc_order_id', $orderColumns)) {
            $db->exec("ALTER TABLE orders ADD COLUMN external_wc_order_id VARCHAR(100) NULL");
        }
        if (!in_array('status', $orderColumns)) {
            $db->exec("ALTER TABLE orders ADD COLUMN status VARCHAR(50) NOT NULL DEFAULT 'pending'");
        }
        if (!in_array('payment_status', $orderColumns)) {
            $db->exec("ALTER TABLE orders ADD COLUMN payment_status VARCHAR(50) NOT NULL DEFAULT 'unpaid'");
        }
        if (!in_array('order_group', $orderColumns)) {
            $db->exec("ALTER TABLE orders ADD COLUMN order_group VARCHAR(64) NULL");
        }

        // ---------- Products: WooCommerce ID, Stock ----------
        $productColumns = $db->query("SHOW COLUMNS FROM products")->fetchAll(PDO::FETCH_COLUMN);

        if (!in_array('external_wc_product_id', $productColumns)) {
            $db->exec("ALTER TABLE products ADD COLUMN external_wc_product_id VARCHAR(100) NULL");
        }
        if (!in_array('stock_quantity', $productColumns)) {
            $db->exec("ALTER TABLE products ADD COLUMN stock_quantity INT NOT NULL DEFAULT 0");
        }
        if (!in_array('low_stock_threshold', $productColumns)) {
            $db->exec("ALTER TABLE products ADD COLUMN low_stock_threshold INT NOT NULL DEFAULT 5");
        }

        // ---------- Warehouses ----------
        $db->exec("CREATE TABLE IF NOT EXISTS warehouses (
            id INT AUTO_INCREMENT PRIMARY KEY,
            store_id INT NOT NULL,
            name VARCHAR(150) NOT NULL,
            location VARCHAR(255) NULL,
            is_default TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE
        ) ENGINE=InnoDB");

        $db->exec("CREATE TABLE IF NOT EXISTS product_warehouse_stock (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT NOT NULL,
            warehouse_id INT NOT NULL,
            stock_quantity INT NOT NULL DEFAULT 0,
            UNIQUE KEY product_warehouse_unique (product_id, warehouse_id),
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
            FOREIGN KEY (warehouse_id) REFERENCES warehouses(id) ON DELETE CASCADE
        ) ENGINE=InnoDB");

        $orderColumns2 = $db->query("SHOW COLUMNS FROM orders")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('warehouse_id', $orderColumns2)) {
            $db->exec("ALTER TABLE orders ADD COLUMN warehouse_id INT NULL");
        }

        // Har store ke liye default "Main Warehouse" banao aur existing stock usme migrate karo
        $stores = $db->query("SELECT id FROM stores")->fetchAll(PDO::FETCH_COLUMN);

        foreach ($stores as $storeId) {
            $check = $db->prepare("SELECT id FROM warehouses WHERE store_id = ? AND is_default = 1 LIMIT 1");
            $check->execute([$storeId]);
            $existing = $check->fetch();

            if ($existing) {
                $warehouseId = $existing['id'];
            } else {
                $insert = $db->prepare("INSERT INTO warehouses (store_id, name, location, is_default) VALUES (?, 'Main Warehouse', 'Head Office', 1)");
                $insert->execute([$storeId]);
                $warehouseId = (int) $db->lastInsertId();
            }

            $products = $db->prepare("SELECT id, stock_quantity FROM products WHERE store_id = ?");
            $products->execute([$storeId]);

            foreach ($products->fetchAll(PDO::FETCH_ASSOC) as $product) {
                $seed = $db->prepare(
                    "INSERT INTO product_warehouse_stock (product_id, warehouse_id, stock_quantity)
                     VALUES (?, ?, ?)
                     ON DUPLICATE KEY UPDATE stock_quantity = VALUES(stock_quantity)"
                );
                $seed->execute([$product['id'], $warehouseId, (int) ($product['stock_quantity'] ?? 0)]);
            }
        }
    },
    'down' => function (PDO $db) {
        $orderColumns = $db->query("SHOW COLUMNS FROM orders")->fetchAll(PDO::FETCH_COLUMN);
        if (in_array('warehouse_id', $orderColumns)) {
            $db->exec("ALTER TABLE orders DROP COLUMN warehouse_id");
        }
        $db->exec("DROP TABLE IF EXISTS product_warehouse_stock");
        $db->exec("DROP TABLE IF EXISTS warehouses");
    },
];