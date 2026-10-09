<?php

return [
    'up' => function (PDO $db) {
        $existing = $db->query("SHOW COLUMNS FROM customers")->fetchAll(PDO::FETCH_COLUMN);
        $columns = [
            'phone'      => "VARCHAR(50) NULL AFTER email",
            'address'    => "VARCHAR(500) NULL AFTER phone",
            'city'       => "VARCHAR(100) NULL AFTER address",
            'country'    => "VARCHAR(100) NULL AFTER city",
            'source'     => "VARCHAR(30) NULL AFTER country",
            'updated_at' => "TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
        ];
        foreach ($columns as $name => $definition) {
            if (!in_array($name, $existing, true)) {
                $db->exec("ALTER TABLE customers ADD COLUMN {$name} {$definition}");
            }
        }

        $indexes = $db->query("SHOW INDEX FROM customers")->fetchAll(PDO::FETCH_COLUMN, 2);
        if (!in_array('customers_store_email', $indexes, true)) {
            $db->exec("CREATE INDEX customers_store_email ON customers (store_id, email)");
        }
        if (!in_array('customers_store_external', $indexes, true)) {
            $db->exec("CREATE INDEX customers_store_external ON customers (store_id, external_customer_id)");
        }

        // ---- Purane orders → customers (email ho to email se, warna naam se pehchaan) ----
        $orders = $db->query(
            "SELECT id, store_id, customer_name, customer_email, customer_phone, shipping_address, billing_address, source
             FROM orders
             WHERE customer_id IS NULL AND customer_name IS NOT NULL AND customer_name <> ''
             ORDER BY id ASC"
        )->fetchAll(PDO::FETCH_ASSOC);

        $findByEmail = $db->prepare("SELECT id FROM customers WHERE store_id = ? AND email = ? LIMIT 1");
        $findByName = $db->prepare("SELECT id FROM customers WHERE store_id = ? AND name = ? AND (email IS NULL OR email = '') LIMIT 1");
        $insert = $db->prepare(
            "INSERT INTO customers (store_id, name, email, phone, address, source) VALUES (?, ?, ?, ?, ?, ?)"
        );
        $link = $db->prepare("UPDATE orders SET customer_id = ? WHERE id = ?");

        foreach ($orders as $o) {
            $storeId = (int) $o['store_id'];
            $email = strtolower(trim((string) ($o['customer_email'] ?? '')));
            $name = mb_substr(trim($o['customer_name']), 0, 150);

            $customerId = false;
            if ($email !== '') {
                $findByEmail->execute([$storeId, $email]);
                $customerId = $findByEmail->fetchColumn();
            } else {
                $findByName->execute([$storeId, $name]);
                $customerId = $findByName->fetchColumn();
            }

            if ($customerId === false) {
                $address = trim((string) ($o['shipping_address'] ?: $o['billing_address']));
                $insert->execute([
                    $storeId,
                    $name,
                    $email !== '' ? $email : null,
                    trim((string) $o['customer_phone']) ?: null,
                    $address !== '' ? mb_substr($address, 0, 500) : null,
                    str_replace('_pull', '', (string) $o['source']) ?: 'manual',
                ]);
                $customerId = $db->lastInsertId();
            }

            $link->execute([(int) $customerId, (int) $o['id']]);
        }
    },

    'down' => function (PDO $db) {
        $indexes = $db->query("SHOW INDEX FROM customers")->fetchAll(PDO::FETCH_COLUMN, 2);
        foreach (['customers_store_email', 'customers_store_external'] as $index) {
            if (in_array($index, $indexes, true)) {
                $db->exec("DROP INDEX {$index} ON customers");
            }
        }
        $existing = $db->query("SHOW COLUMNS FROM customers")->fetchAll(PDO::FETCH_COLUMN);
        foreach (['phone', 'address', 'city', 'country', 'source', 'updated_at'] as $column) {
            if (in_array($column, $existing, true)) {
                $db->exec("ALTER TABLE customers DROP COLUMN {$column}");
            }
        }
    },
];