<?php

return [
    'up' => function (PDO $db) {
        $newColumns = [
            'products' => [
                'external_cj_product_id' => 'VARCHAR(100) NULL',
                'external_cj_variant_id' => 'VARCHAR(100) NULL',
            ],
            'orders' => [
                'cj_order_id'        => 'VARCHAR(100) NULL',
                'cj_order_status'    => 'VARCHAR(50) NULL',
                'cj_tracking_number' => 'VARCHAR(100) NULL',
                'cj_logistic_name'   => 'VARCHAR(100) NULL',
                'cj_pushed_at'       => 'DATETIME NULL',
            ],
            'stores' => [
                'cj_access_token'         => 'TEXT NULL',
                'cj_token_expires_at'     => 'DATETIME NULL',
                'ebay_refresh_token'      => 'TEXT NULL',
                'ebay_token_expires_at'   => 'DATETIME NULL',
                'ebay_refresh_expires_at' => 'DATETIME NULL',
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
            'products' => ['external_cj_variant_id'],
            'orders'   => ['cj_order_id', 'cj_order_status', 'cj_tracking_number', 'cj_logistic_name', 'cj_pushed_at'],
            'stores'   => ['cj_access_token', 'cj_token_expires_at', 'ebay_refresh_token', 'ebay_token_expires_at', 'ebay_refresh_expires_at'],
        ];
        // external_cj_product_id jaan boojh ke nahi hataya — products sync us pe chalta hai

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