<?php
return [
    'up' => function (PDO $db) {
        $tables = $db->query("SHOW TABLES LIKE 'jobs'")->fetchAll(PDO::FETCH_COLUMN);
        if (empty($tables)) {
            $db->exec(
                "CREATE TABLE jobs (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    type VARCHAR(50) NOT NULL,
                    store_id INT NOT NULL,
                    status ENUM('pending', 'processing', 'completed', 'failed') NOT NULL DEFAULT 'pending',
                    result_message TEXT NULL,
                    attempts INT NOT NULL DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_status (status),
                    INDEX idx_store_id (store_id)
                )"
            );
        }
    },
    'down' => function (PDO $db) {
        $db->exec("DROP TABLE IF EXISTS jobs");
    },
];