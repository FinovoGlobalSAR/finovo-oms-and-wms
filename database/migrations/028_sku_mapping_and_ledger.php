<?php
return [
    'up' => function (PDO $db) {
        $db->exec("CREATE TABLE IF NOT EXISTS sku_mappings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            store_id INT NOT NULL,
            product_id INT NOT NULL,
            variant_id INT NULL,
            external_product_id VARCHAR(150) NULL,
            external_variant_id VARCHAR(150) NULL,
            external_sku VARCHAR(150) NULL,
            barcode VARCHAR(150) NULL,
            mapping_state VARCHAR(30) NOT NULL DEFAULT 'mapped',
            mapped_by VARCHAR(150) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
        ) ENGINE=InnoDB");

        $db->exec("CREATE TABLE IF NOT EXISTS inventory_ledger (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT NOT NULL,
            variant_id INT NULL,
            warehouse_id INT NOT NULL,
            quantity_before INT NOT NULL,
            quantity_change INT NOT NULL,
            quantity_after INT NOT NULL,
            source VARCHAR(50) NOT NULL,
            reference VARCHAR(150) NULL,
            actor VARCHAR(150) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
            FOREIGN KEY (warehouse_id) REFERENCES warehouses(id) ON DELETE CASCADE
        ) ENGINE=InnoDB");

        $db->exec("CREATE TABLE IF NOT EXISTS order_status_history (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            old_status VARCHAR(50) NULL,
            new_status VARCHAR(50) NOT NULL,
            actor VARCHAR(150) NULL,
            source VARCHAR(50) NOT NULL DEFAULT 'user',
            reason VARCHAR(255) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
        ) ENGINE=InnoDB");
    },
    'down' => function (PDO $db) {
        $db->exec("DROP TABLE IF EXISTS order_status_history");
        $db->exec("DROP TABLE IF EXISTS inventory_ledger");
        $db->exec("DROP TABLE IF EXISTS sku_mappings");
    },
];