<?php
return [
    'up' => function (PDO $db) {
        $db->exec("CREATE TABLE IF NOT EXISTS role_permissions (
            role_id INT NOT NULL,
            permission_id INT NOT NULL,
            PRIMARY KEY (role_id, permission_id),
            FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
            FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
        ) ENGINE=InnoDB");
    },
    'down' => function (PDO $db) {
        $db->exec("DROP TABLE IF EXISTS role_permissions");
    },
];