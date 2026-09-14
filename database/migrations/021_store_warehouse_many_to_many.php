<?php
return [
    'up' => function (PDO $db) {
        $db->exec("CREATE TABLE IF NOT EXISTS store_warehouses (
            id INT AUTO_INCREMENT PRIMARY KEY,
            store_id INT NOT NULL,
            warehouse_id INT NOT NULL,
            is_default TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY store_warehouse_unique (store_id, warehouse_id),
            FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
            FOREIGN KEY (warehouse_id) REFERENCES warehouses(id) ON DELETE CASCADE
        ) ENGINE=InnoDB");

        $warehouseColumns = $db->query("SHOW COLUMNS FROM warehouses")->fetchAll(PDO::FETCH_COLUMN);

        if (in_array('store_id', $warehouseColumns)) {
            $existingWarehouses = $db->query("SELECT id, store_id, is_default FROM warehouses WHERE store_id IS NOT NULL")->fetchAll(PDO::FETCH_ASSOC);

            $insert = $db->prepare(
                "INSERT INTO store_warehouses (store_id, warehouse_id, is_default) VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE is_default = VALUES(is_default)"
            );
            foreach ($existingWarehouses as $w) {
                $insert->execute([$w['store_id'], $w['id'], (int) $w['is_default']]);
            }

            $fks = $db->query("
                SELECT CONSTRAINT_NAME
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'warehouses'
                AND REFERENCED_TABLE_NAME = 'stores'
            ")->fetchAll(PDO::FETCH_COLUMN);

            foreach ($fks as $fkName) {
                $db->exec("ALTER TABLE warehouses DROP FOREIGN KEY `{$fkName}`");
            }

            $db->exec("ALTER TABLE warehouses DROP COLUMN store_id");
        }

        $warehouseColumns2 = $db->query("SHOW COLUMNS FROM warehouses")->fetchAll(PDO::FETCH_COLUMN);
        if (in_array('is_default', $warehouseColumns2)) {
            $db->exec("ALTER TABLE warehouses DROP COLUMN is_default");
        }
    },
    'down' => function (PDO $db) {
        $warehouseColumns = $db->query("SHOW COLUMNS FROM warehouses")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('store_id', $warehouseColumns)) {
            $db->exec("ALTER TABLE warehouses ADD COLUMN store_id INT NULL");
        }
        if (!in_array('is_default', $warehouseColumns)) {
            $db->exec("ALTER TABLE warehouses ADD COLUMN is_default TINYINT(1) NOT NULL DEFAULT 0");
        }
        $rows = $db->query("SELECT store_id, warehouse_id, is_default FROM store_warehouses ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
        $update = $db->prepare("UPDATE warehouses SET store_id = ?, is_default = ? WHERE id = ? AND store_id IS NULL");
        foreach ($rows as $r) {
            $update->execute([$r['store_id'], $r['is_default'], $r['warehouse_id']]);
        }
        $db->exec("DROP TABLE IF EXISTS store_warehouses");
    },
];