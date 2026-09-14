<?php
return [
    'up' => function (PDO $db) {
        $db->exec("CREATE TABLE IF NOT EXISTS shipments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            store_id INT NOT NULL,
            courier_name VARCHAR(100) NOT NULL,
            tracking_number VARCHAR(150) NULL,
            status VARCHAR(50) NOT NULL DEFAULT 'pending',
            dispatched_at DATETIME NULL,
            delivered_at DATETIME NULL,
            notes TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE
        ) ENGINE=InnoDB");

        $orderColumns = $db->query("SHOW COLUMNS FROM orders")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('shipment_id', $orderColumns)) {
            $db->exec("ALTER TABLE orders ADD COLUMN shipment_id INT NULL");
        }
    },
    'down' => function (PDO $db) {
        $orderColumns = $db->query("SHOW COLUMNS FROM orders")->fetchAll(PDO::FETCH_COLUMN);
        if (in_array('shipment_id', $orderColumns)) {
            $db->exec("ALTER TABLE orders DROP COLUMN shipment_id");
        }
        $db->exec("DROP TABLE IF EXISTS shipments");
    },
];