<?php
return [
    'up' => function (PDO $db) {
        $db->exec("CREATE TABLE IF NOT EXISTS stores (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(150) NOT NULL,
            platform VARCHAR(50) NOT NULL DEFAULT 'manual',
            store_url VARCHAR(255) NULL,
            api_key VARCHAR(255) NULL,
            access_token VARCHAR(255) NULL,
            consumer_key VARCHAR(255) NULL,
            consumer_secret VARCHAR(255) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB");

        $old = $db->query("SELECT * FROM settings LIMIT 1")->fetch();

        if ($old) {
            $stmt = $db->prepare(
                "INSERT INTO stores (name, platform, store_url, api_key, access_token)
                 VALUES (?, 'shopify', ?, ?, ?)"
            );
            $stmt->execute([
                'My Store',
                $old['shopify_store_url'] ?? null,
                $old['api_key'] ?? null,
                $old['shopify_access_token'] ?? null,
            ]);
        } else {
            $db->exec("INSERT INTO stores (name, platform, api_key) VALUES ('My Store', 'manual', REPLACE(UUID(), '-', ''))");
        }
    },
    'down' => function (PDO $db) {
        $db->exec("DROP TABLE IF EXISTS stores");
    },
];