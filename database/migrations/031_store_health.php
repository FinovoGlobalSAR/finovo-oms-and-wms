<?php
return [
    'up' => function (PDO $db) {
        $columns = $db->query("SHOW COLUMNS FROM stores")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('health_status', $columns)) {
            $db->exec("ALTER TABLE stores ADD COLUMN health_status VARCHAR(30) NOT NULL DEFAULT 'disconnected'");
        }
        if (!in_array('last_successful_sync', $columns)) {
            $db->exec("ALTER TABLE stores ADD COLUMN last_successful_sync DATETIME NULL");
        }
        if (!in_array('last_error', $columns)) {
            $db->exec("ALTER TABLE stores ADD COLUMN last_error TEXT NULL");
        }
    },
    'down' => function (PDO $db) {
        $columns = $db->query("SHOW COLUMNS FROM stores")->fetchAll(PDO::FETCH_COLUMN);
        foreach (['health_status', 'last_successful_sync', 'last_error'] as $col) {
            if (in_array($col, $columns)) {
                $db->exec("ALTER TABLE stores DROP COLUMN {$col}");
            }
        }
    },
];