<?php
return [
    'up' => function (PDO $db) {
        $columns = $db->query("SHOW COLUMNS FROM stores")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('bigcommerce_store_hash', $columns)) {
            $db->exec("ALTER TABLE stores ADD COLUMN bigcommerce_store_hash VARCHAR(50) NULL");
        }
        if (!in_array('bigcommerce_access_token', $columns)) {
            $db->exec("ALTER TABLE stores ADD COLUMN bigcommerce_access_token VARCHAR(255) NULL");
        }
    },
    'down' => function (PDO $db) {
        $columns = $db->query("SHOW COLUMNS FROM stores")->fetchAll(PDO::FETCH_COLUMN);
        foreach (['bigcommerce_store_hash', 'bigcommerce_access_token'] as $col) {
            if (in_array($col, $columns)) {
                $db->exec("ALTER TABLE stores DROP COLUMN {$col}");
            }
        }
    },
];