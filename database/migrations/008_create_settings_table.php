<?php

return [
    'up' => function (PDO $db) {
        $db->exec("CREATE TABLE IF NOT EXISTS settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            api_key VARCHAR(64) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB");

        $db->exec("INSERT INTO settings (api_key) VALUES (REPLACE(UUID(), '-', ''))");
    },
    'down' => function (PDO $db) {
        $db->exec("DROP TABLE IF EXISTS settings");
    },
];