<?php

return [
    'up' => function (PDO $db) {
        $newColumns = [
            'stores' => [
                'magento_store_url'    => 'VARCHAR(255) NULL',
                'magento_access_token' => 'TEXT NULL',
            ],
            'products' => [
                'external_magento_product_id' => 'VARCHAR(100) NULL',
            ],
            'orders' => [
                'external_magento_order_id' => 'VARCHAR(100) NULL',
            ],
        ];

        foreach ($newColumns as $table => $columns) {
            $existing = $db->query("SHOW COLUMNS FROM {$table}")->fetchAll(PDO::FETCH_COLUMN);
            foreach ($columns as $name => $definition) {
                if (!in_array($name, $existing, true)) {
                    $db->exec("ALTER TABLE {$table} ADD COLUMN {$name} {$definition}");
                }
            }
        }
    },

    'down' => function (PDO $db) {
        $columns = [
            'stores'   => ['magento_store_url', 'magento_access_token'],
            'products' => ['external_magento_product_id'],
            'orders'   => ['external_magento_order_id'],
        ];

        foreach ($columns as $table => $names) {
            $existing = $db->query("SHOW COLUMNS FROM {$table}")->fetchAll(PDO::FETCH_COLUMN);
            foreach ($names as $name) {
                if (in_array($name, $existing, true)) {
                    $db->exec("ALTER TABLE {$table} DROP COLUMN {$name}");
                }
            }
        }
    },
];