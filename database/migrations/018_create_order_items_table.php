<?php

return [
    'up' => function (PDO $db) {
        $db->exec(
            "CREATE TABLE IF NOT EXISTS order_items (
                id INT AUTO_INCREMENT PRIMARY KEY,
                order_id INT NOT NULL,
                product_id INT NULL,
                product_name VARCHAR(150) NOT NULL,
                sku VARCHAR(100) NULL,
                variant VARCHAR(150) NULL,
                quantity INT NOT NULL DEFAULT 1,
                unit_price DECIMAL(10,2) NOT NULL DEFAULT 0,
                line_total DECIMAL(10,2) NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT order_items_order_fk
                    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
                CONSTRAINT order_items_product_fk
                    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
            ) ENGINE=InnoDB"
        );

        $db->exec(
            "INSERT INTO order_items
                (order_id, product_id, product_name, sku, variant, quantity, unit_price, line_total)
             SELECT
                o.id,
                o.product_id,
                o.product_name,
                COALESCE(o.sku, p.sku),
                o.variant,
                o.quantity,
                CASE
                    WHEN o.quantity > 0 THEN o.price / o.quantity
                    ELSE o.price
                END,
                o.price
             FROM orders o
             LEFT JOIN products p ON p.id = o.product_id
             WHERE NOT EXISTS (
                SELECT 1 FROM order_items oi WHERE oi.order_id = o.id
             )"
        );
    },

    'down' => function (PDO $db) {
        $db->exec('DROP TABLE IF EXISTS order_items');
    },
];
