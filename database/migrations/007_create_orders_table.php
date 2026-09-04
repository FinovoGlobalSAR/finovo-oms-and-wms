<?php

return [
    'up' => function (PDO $db) {
        $db->exec("CREATE TABLE IF NOT EXISTS orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            customer_name VARCHAR(150) NOT NULL,
            product_name VARCHAR(150) NOT NULL,
            quantity INT NOT NULL DEFAULT 1,
            price DECIMAL(10,2) NOT NULL,
            status ENUM('Pending', 'Confirmed', 'Shipped', 'Delivered') NOT NULL DEFAULT 'Pending',
            source VARCHAR(20) NOT NULL DEFAULT 'manual',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB");
    },
    'down' => function (PDO $db) {
        $db->exec("DROP TABLE IF EXISTS orders");
    },
];