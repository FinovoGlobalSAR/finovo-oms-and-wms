<?php
return [
    'up' => function (PDO $db) {
        $tables = $db->query("SHOW TABLES LIKE 'field_mappings'")->fetchAll(PDO::FETCH_COLUMN);
        if (empty($tables)) {
            $db->exec(
                "CREATE TABLE field_mappings (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    store_id INT NOT NULL,
                    platform VARCHAR(50) NOT NULL,
                    entity_type ENUM('product', 'order') NOT NULL,
                    finovo_field VARCHAR(50) NOT NULL,
                    external_field VARCHAR(255) NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_mapping (store_id, platform, entity_type, finovo_field)
                )"
            );
        }
    },
    'down' => function (PDO $db) {
        $db->exec("DROP TABLE IF EXISTS field_mappings");
    },
];