<?php
return [
    'up' => function (PDO $db) {
        $db->exec("CREATE TABLE IF NOT EXISTS companies (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(150) NOT NULL,
            status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB");
    },
    'down' => function (PDO $db) {
        $db->exec("DROP TABLE IF EXISTS companies");
        
    },
];