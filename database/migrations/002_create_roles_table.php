<?php
return [
    'up' => function (PDO $db) {
        $db->exec("CREATE TABLE IF NOT EXISTS roles (
            id INT AUTO_INCREMENT PRIMARY KEY,
            company_id INT NOT NULL,
            name VARCHAR(50) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
        ) ENGINE=InnoDB");
    },
    'down' => function (PDO $db) {
        $db->exec("DROP TABLE IF EXISTS roles");
    },
];