<?php
/**
 * Ye columns local database mein pehle se the (seedha SQL se add kiye gaye the),
 * lekin kisi migration mein nahi the. Is liye live server pe `php migrate.php`
 * chalane ke baad ye columns bante hi nahi the, aur Dashboard, Store "Save Changes"
 * aur BigCommerce / PrestaShop / OpenCart / osCommerce / Wix / eBay / CJ sync
 * "Unknown column" error de rahe the.
 *
 * Har column sirf tab add hota hai jab wo pehle se na ho — is liye ye migration
 * local database (jahan columns pehle se hain) pe bhi safe chalti hai.
 */
return [
    'up' => function (PDO $db) {
        $newColumns = [
            'stores' => [
                'prestashop_store_url'    => 'VARCHAR(255) NULL',
                'prestashop_api_key'      => 'VARCHAR(100) NULL',
                'opencart_store_url'      => 'VARCHAR(255) NULL',
                'opencart_api_username'   => 'VARCHAR(100) NULL',
                'opencart_api_key'        => 'VARCHAR(255) NULL',
                'oscommerce_store_url'    => 'VARCHAR(255) NULL',
                'oscommerce_api_username' => 'VARCHAR(100) NULL',
                'oscommerce_api_key'      => 'VARCHAR(255) NULL',
                'wix_site_id'             => 'VARCHAR(100) NULL',
                'wix_api_key'             => 'TEXT NULL',
                'ebay_user_token'         => 'TEXT NULL',
                'cj_email'                => 'VARCHAR(150) NULL',
                'cj_api_key'              => 'VARCHAR(255) NULL',
            ],
            'products' => [
                'external_bc_product_id'    => 'VARCHAR(100) NULL',
                'external_ps_product_id'    => 'VARCHAR(100) NULL',
                'external_ocart_product_id' => 'VARCHAR(100) NULL',
                'external_osc_product_id'   => 'VARCHAR(100) NULL',
                'external_wix_product_id'   => 'VARCHAR(100) NULL',
                'external_ebay_product_id'  => 'VARCHAR(100) NULL',
            ],
            'orders' => [
                'external_bc_order_id'    => 'VARCHAR(100) NULL',
                'external_ps_order_id'    => 'VARCHAR(100) NULL',
                'external_ocart_order_id' => 'VARCHAR(100) NULL',
                'external_osc_order_id'   => 'VARCHAR(100) NULL',
                'external_wix_order_id'   => 'VARCHAR(100) NULL',
                'external_ebay_order_id'  => 'VARCHAR(100) NULL',
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
            'stores' => [
                'prestashop_store_url', 'prestashop_api_key',
                'opencart_store_url', 'opencart_api_username', 'opencart_api_key',
                'oscommerce_store_url', 'oscommerce_api_username', 'oscommerce_api_key',
                'wix_site_id', 'wix_api_key', 'ebay_user_token', 'cj_email', 'cj_api_key',
            ],
            'products' => [
                'external_bc_product_id', 'external_ps_product_id', 'external_ocart_product_id',
                'external_osc_product_id', 'external_wix_product_id', 'external_ebay_product_id',
            ],
            'orders' => [
                'external_bc_order_id', 'external_ps_order_id', 'external_ocart_order_id',
                'external_osc_order_id', 'external_wix_order_id', 'external_ebay_order_id',
            ],
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