<?php
return [
    'up' => function (PDO $db) {
        $db->exec("CREATE TABLE IF NOT EXISTS suppliers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            store_id INT NOT NULL,
            name VARCHAR(150) NOT NULL,
            contact_person VARCHAR(150) NULL,
            email VARCHAR(150) NULL,
            phone VARCHAR(50) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE
        ) ENGINE=InnoDB");

        $db->exec("CREATE TABLE IF NOT EXISTS purchase_orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            store_id INT NOT NULL,
            supplier_id INT NOT NULL,
            warehouse_id INT NOT NULL,
            status VARCHAR(50) NOT NULL DEFAULT 'draft',
            notes TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            received_at DATETIME NULL,
            FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
            FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE CASCADE,
            FOREIGN KEY (warehouse_id) REFERENCES warehouses(id) ON DELETE CASCADE
        ) ENGINE=InnoDB");

        $db->exec("CREATE TABLE IF NOT EXISTS purchase_order_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            purchase_order_id INT NOT NULL,
            product_id INT NOT NULL,
            quantity INT NOT NULL DEFAULT 0,
            unit_cost DECIMAL(10,2) NOT NULL DEFAULT 0,
            FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
        ) ENGINE=InnoDB");

        $db->exec("CREATE TABLE IF NOT EXISTS stock_transfers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT NOT NULL,
            variant_id INT NULL,
            from_warehouse_id INT NOT NULL,
            to_warehouse_id INT NOT NULL,
            quantity INT NOT NULL,
            notes VARCHAR(255) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
            FOREIGN KEY (from_warehouse_id) REFERENCES warehouses(id) ON DELETE CASCADE,
            FOREIGN KEY (to_warehouse_id) REFERENCES warehouses(id) ON DELETE CASCADE
        ) ENGINE=InnoDB");

        $db->exec("CREATE TABLE IF NOT EXISTS stock_adjustments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT NOT NULL,
            variant_id INT NULL,
            warehouse_id INT NOT NULL,
            quantity_change INT NOT NULL,
            reason VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
            FOREIGN KEY (warehouse_id) REFERENCES warehouses(id) ON DELETE CASCADE
        ) ENGINE=InnoDB");

        $db->exec("CREATE TABLE IF NOT EXISTS returns (
            id INT AUTO_INCREMENT PRIMARY KEY,
            store_id INT NOT NULL,
            order_id INT NOT NULL,
            product_id INT NULL,
            variant_id INT NULL,
            warehouse_id INT NOT NULL,
            quantity INT NOT NULL DEFAULT 1,
            reason VARCHAR(255) NULL,
            condition_status VARCHAR(50) NOT NULL DEFAULT 'resellable',
            status VARCHAR(50) NOT NULL DEFAULT 'requested',
            refund_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
            FOREIGN KEY (warehouse_id) REFERENCES warehouses(id) ON DELETE CASCADE
        ) ENGINE=InnoDB");

        $orderColumns = $db->query("SHOW COLUMNS FROM orders")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('picking_status', $orderColumns)) {
            $db->exec("ALTER TABLE orders ADD COLUMN picking_status VARCHAR(50) NOT NULL DEFAULT 'not_started'");
        }
    },
    'down' => function (PDO $db) {
        $orderColumns = $db->query("SHOW COLUMNS FROM orders")->fetchAll(PDO::FETCH_COLUMN);
        if (in_array('picking_status', $orderColumns)) {
            $db->exec("ALTER TABLE orders DROP COLUMN picking_status");
        }
        $db->exec("DROP TABLE IF EXISTS returns");
        $db->exec("DROP TABLE IF EXISTS stock_adjustments");
        $db->exec("DROP TABLE IF EXISTS stock_transfers");
        $db->exec("DROP TABLE IF EXISTS purchase_order_items");
        $db->exec("DROP TABLE IF EXISTS purchase_orders");
        $db->exec("DROP TABLE IF EXISTS suppliers");
    },
];