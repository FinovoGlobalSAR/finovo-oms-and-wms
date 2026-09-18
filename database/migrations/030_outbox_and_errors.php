<?php
return [
    'up' => function (PDO $db) {
        $db->exec("CREATE TABLE IF NOT EXISTS integration_errors (
            id INT AUTO_INCREMENT PRIMARY KEY,
            store_id INT NOT NULL,
            connector VARCHAR(30) NOT NULL,
            operation VARCHAR(100) NOT NULL,
            external_id VARCHAR(150) NULL,
            internal_id VARCHAR(150) NULL,
            http_status INT NULL,
            error_message TEXT NULL,
            attempts INT NOT NULL DEFAULT 1,
            next_retry_at DATETIME NULL,
            status VARCHAR(30) NOT NULL DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE
        ) ENGINE=InnoDB");
    },
    'down' => function (PDO $db) {
        $db->exec("DROP TABLE IF EXISTS integration_errors");
    },
];