<?php

return [
    'up' => function (PDO $db) {
        $databaseName = $db->query('SELECT DATABASE()')->fetchColumn();

        $columnExists = function (string $column) use ($db, $databaseName): bool {
            $stmt = $db->prepare(
                'SELECT COUNT(*) FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?'
            );
            $stmt->execute([$databaseName, 'orders', $column]);
            return (int) $stmt->fetchColumn() > 0;
        };

        $columns = [
            'invoice_number'   => "VARCHAR(50) NULL",
            'customer_email'   => "VARCHAR(150) NULL",
            'customer_phone'   => "VARCHAR(50) NULL",
            'billing_address'  => "TEXT NULL",
            'shipping_address' => "TEXT NULL",
            'variant'          => "VARCHAR(150) NULL",
            'sku'              => "VARCHAR(100) NULL",
            'discount'         => "DECIMAL(10,2) NOT NULL DEFAULT 0",
            'shipping_cost'    => "DECIMAL(10,2) NOT NULL DEFAULT 0",
            'tax'              => "DECIMAL(10,2) NOT NULL DEFAULT 0",
            'payment_method'   => "VARCHAR(100) NULL",
            'payment_status'   => "VARCHAR(50) NOT NULL DEFAULT 'Pending'",
        ];

        foreach ($columns as $name => $definition) {
            if (!$columnExists($name)) {
                $db->exec("ALTER TABLE orders ADD COLUMN {$name} {$definition}");
            }
        }

        $db->exec(
            "UPDATE orders
             SET invoice_number = CONCAT('INV-', LPAD(id, 5, '0'))
             WHERE invoice_number IS NULL OR invoice_number = ''"
        );
    },

    'down' => function (PDO $db) {
        $databaseName = $db->query('SELECT DATABASE()')->fetchColumn();
        $columns = [
            'invoice_number',
            'customer_email',
            'customer_phone',
            'billing_address',
            'shipping_address',
            'variant',
            'sku',
            'discount',
            'shipping_cost',
            'tax',
            'payment_method',
            'payment_status',
        ];

        foreach ($columns as $column) {
            $stmt = $db->prepare(
                'SELECT COUNT(*) FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?'
            );
            $stmt->execute([$databaseName, 'orders', $column]);
            if ((int) $stmt->fetchColumn() > 0) {
                $db->exec("ALTER TABLE orders DROP COLUMN {$column}");
            }
        }
    },
];
