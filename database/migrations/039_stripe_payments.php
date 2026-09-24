<?php
return [
    'up' => function (PDO $db) {
        $columns = $db->query("SHOW COLUMNS FROM orders")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('stripe_session_id', $columns)) {
            $db->exec("ALTER TABLE orders ADD COLUMN stripe_session_id VARCHAR(255) NULL");
        }
    },
    'down' => function (PDO $db) {
        $columns = $db->query("SHOW COLUMNS FROM orders")->fetchAll(PDO::FETCH_COLUMN);
        if (in_array('stripe_session_id', $columns)) {
            $db->exec("ALTER TABLE orders DROP COLUMN stripe_session_id");
        }
    },
];