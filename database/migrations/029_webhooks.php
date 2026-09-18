<?php
return [
    'up' => function (PDO $db) {
        $db->exec("CREATE TABLE IF NOT EXISTS webhook_events (
            id INT AUTO_INCREMENT PRIMARY KEY,
            store_id INT NOT NULL,
            source VARCHAR(30) NOT NULL,
            external_id VARCHAR(150) NOT NULL,
            event_type VARCHAR(100) NULL,
            received_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_event (store_id, source, external_id, event_type),
            FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE
        ) ENGINE=InnoDB");

        $columns = $db->query("SHOW COLUMNS FROM stores")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('shopify_webhook_secret', $columns)) {
            $db->exec("ALTER TABLE stores ADD COLUMN shopify_webhook_secret VARCHAR(255) NULL");
        }
        if (!in_array('woocommerce_webhook_secret', $columns)) {
            $db->exec("ALTER TABLE stores ADD COLUMN woocommerce_webhook_secret VARCHAR(255) NULL");
        }
    },
    'down' => function (PDO $db) {
        $columns = $db->query("SHOW COLUMNS FROM stores")->fetchAll(PDO::FETCH_COLUMN);
        if (in_array('shopify_webhook_secret', $columns)) {
            $db->exec("ALTER TABLE stores DROP COLUMN shopify_webhook_secret");
        }
        if (in_array('woocommerce_webhook_secret', $columns)) {
            $db->exec("ALTER TABLE stores DROP COLUMN woocommerce_webhook_secret");
        }
        $db->exec("DROP TABLE IF EXISTS webhook_events");
    },
];