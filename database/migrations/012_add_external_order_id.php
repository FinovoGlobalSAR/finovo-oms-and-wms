<?php
return [
    'up' => function (PDO $db) {
        $db->exec("ALTER TABLE orders ADD COLUMN external_order_id VARCHAR(100) NULL");
    },
    'down' => function (PDO $db) {
        $db->exec("ALTER TABLE orders DROP COLUMN external_order_id");
    },
];