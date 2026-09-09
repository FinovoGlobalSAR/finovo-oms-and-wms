<?php
return [
    'up' => function (PDO $db) {
        $db->exec("ALTER TABLE settings ADD COLUMN shopify_store_url VARCHAR(255) NULL");
        $db->exec("ALTER TABLE settings ADD COLUMN shopify_access_token VARCHAR(255) NULL");
    },
    'down' => function (PDO $db) {
        $db->exec("ALTER TABLE settings DROP COLUMN shopify_store_url");
        $db->exec("ALTER TABLE settings DROP COLUMN shopify_access_token");
    },
];