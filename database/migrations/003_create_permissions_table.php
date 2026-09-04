<?php
return [
    'up' => function (PDO $db) {
        $db->exec("CREATE TABLE IF NOT EXISTS permissions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(50) NOT NULL UNIQUE
        ) ENGINE=InnoDB");
    },
    'down' => function (PDO $db) {
        $db->exec("DROP TABLE IF EXISTS permissions");
    },
];