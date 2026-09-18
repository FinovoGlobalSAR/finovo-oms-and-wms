<?php
return [
    'up' => function (PDO $db) {
        $columns = $db->query("SHOW COLUMNS FROM stores")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('bridge_url', $columns)) {
            $db->exec("ALTER TABLE stores ADD COLUMN bridge_url VARCHAR(255) NULL");
        }
        if (!in_array('bridge_api_key', $columns)) {
            $db->exec("ALTER TABLE stores ADD COLUMN bridge_api_key VARCHAR(255) NULL");
        }
        if (!in_array('bridge_shared_secret', $columns)) {
            $db->exec("ALTER TABLE stores ADD COLUMN bridge_shared_secret VARCHAR(255) NULL");
        }
    },
    'down' => function (PDO $db) {
        $columns = $db->query("SHOW COLUMNS FROM stores")->fetchAll(PDO::FETCH_COLUMN);
        foreach (['bridge_url', 'bridge_api_key', 'bridge_shared_secret'] as $col) {
            if (in_array($col, $columns)) {
                $db->exec("ALTER TABLE stores DROP COLUMN {$col}");
            }
        }
    },
];