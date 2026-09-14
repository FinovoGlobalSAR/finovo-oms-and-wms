<?php
return [
    'up' => function (PDO $db) {
        $columns = $db->query("SHOW COLUMNS FROM orders")->fetchAll(PDO::FETCH_COLUMN);

        $newColumns = [
            'customer_email'   => "VARCHAR(150) NULL",
            'customer_phone'   => "VARCHAR(50) NULL",
            'billing_address'  => "TEXT NULL",
            'shipping_address' => "TEXT NULL",
            'discount'         => "DECIMAL(10,2) NOT NULL DEFAULT 0",
            'shipping_cost'    => "DECIMAL(10,2) NOT NULL DEFAULT 0",
            'tax'              => "DECIMAL(10,2) NOT NULL DEFAULT 0",
            'payment_method'   => "VARCHAR(100) NULL",
        ];

        foreach ($newColumns as $name => $definition) {
            if (!in_array($name, $columns)) {
                $db->exec("ALTER TABLE orders ADD COLUMN {$name} {$definition}");
            }
        }
    },
    'down' => function (PDO $db) {
        $columns = $db->query("SHOW COLUMNS FROM orders")->fetchAll(PDO::FETCH_COLUMN);
        $newColumns = ['customer_email', 'customer_phone', 'billing_address', 'shipping_address', 'discount', 'shipping_cost', 'tax', 'payment_method'];
        foreach ($newColumns as $name) {
            if (in_array($name, $columns)) {
                $db->exec("ALTER TABLE orders DROP COLUMN {$name}");
            }
        }
    },
];