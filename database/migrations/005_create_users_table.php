<?php
return [
    'up' => function (PDO $db) {
        $db->exec("CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            company_id INT NOT NULL,
            role_id INT NOT NULL,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(150) NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_email_per_company (company_id, email),
            FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
            FOREIGN KEY (role_id) REFERENCES roles(id)
        ) ENGINE=InnoDB");
    },
    'down' => function (PDO $db) {
        $db->exec("DROP TABLE IF EXISTS users");
    },
];