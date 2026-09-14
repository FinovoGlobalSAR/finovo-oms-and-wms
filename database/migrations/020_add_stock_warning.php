<?php
return [
    'up' => function (PDO $db) {
        $columns = $db->query("SHOW COLUMNS FROM orders")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('stock_warning', $columns)) {
            $db->exec("ALTER TABLE orders ADD COLUMN stock_warning TINYINT(1) NOT NULL DEFAULT 0");
        }
    },
    'down' => function (PDO $db) {
        $columns = $db->query("SHOW COLUMNS FROM orders")->fetchAll(PDO::FETCH_COLUMN);
        if (in_array('stock_warning', $columns)) {
            $db->exec("ALTER TABLE orders DROP COLUMN stock_warning");
        }
    },
];