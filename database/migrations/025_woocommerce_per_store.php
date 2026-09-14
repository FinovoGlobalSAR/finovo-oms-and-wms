<?php
return [
    'up' => function (PDO $db) {
        $columns = $db->query("SHOW COLUMNS FROM stores")->fetchAll(PDO::FETCH_COLUMN);

        if (!in_array('woocommerce_store_url', $columns)) {
            $db->exec("ALTER TABLE stores ADD COLUMN woocommerce_store_url VARCHAR(255) NULL");
        }
        if (!in_array('woocommerce_consumer_key', $columns)) {
            $db->exec("ALTER TABLE stores ADD COLUMN woocommerce_consumer_key VARCHAR(255) NULL");
        }
        if (!in_array('woocommerce_consumer_secret', $columns)) {
            $db->exec("ALTER TABLE stores ADD COLUMN woocommerce_consumer_secret VARCHAR(255) NULL");
        }

        // Purani global settings jo already save ki gayi thi, unhe current store(s) mein copy kar do
        $settings = $db->query("SELECT * FROM settings LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if ($settings && !empty($settings['woocommerce_store_url'])) {
            $db->exec("UPDATE stores SET
                woocommerce_store_url = " . $db->quote($settings['woocommerce_store_url']) . ",
                woocommerce_consumer_key = " . $db->quote($settings['woocommerce_consumer_key'] ?? '') . ",
                woocommerce_consumer_secret = " . $db->quote($settings['woocommerce_consumer_secret'] ?? '') . "
                WHERE woocommerce_store_url IS NULL"
            );
        }
    },
    'down' => function (PDO $db) {
        $columns = $db->query("SHOW COLUMNS FROM stores")->fetchAll(PDO::FETCH_COLUMN);
        foreach (['woocommerce_store_url', 'woocommerce_consumer_key', 'woocommerce_consumer_secret'] as $col) {
            if (in_array($col, $columns)) {
                $db->exec("ALTER TABLE stores DROP COLUMN {$col}");
            }
        }
    },
];