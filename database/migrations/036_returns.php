<?php
return [
    'up' => function (PDO $db) {
        $tables = $db->query("SHOW TABLES LIKE 'returns'")->fetchAll(PDO::FETCH_COLUMN);
        if (empty($tables)) {
            $db->exec(
                "CREATE TABLE returns (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    order_id INT NOT NULL,
                    store_id INT NOT NULL,
                    product_id INT NULL,
                    quantity INT NOT NULL DEFAULT 1,
                    reason VARCHAR(255) NULL,
                    status ENUM('requested', 'approved', 'received', 'refunded', 'rejected') NOT NULL DEFAULT 'requested',
                    condition_notes TEXT NULL,
                    requested_by VARCHAR(100) NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
                )"
            );
        }
    },
    'down' => function (PDO $db) {
        $db->exec("DROP TABLE IF EXISTS returns");
    },
];