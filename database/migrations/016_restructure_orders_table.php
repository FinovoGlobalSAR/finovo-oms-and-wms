<?php
return [
    'up' => function (PDO $db) {
        $db->exec("ALTER TABLE orders ADD COLUMN store_id INT NULL");
        $db->exec("ALTER TABLE orders ADD COLUMN customer_id INT NULL");
        $db->exec("ALTER TABLE orders ADD COLUMN product_id INT NULL");

        $store = $db->query("SELECT id FROM stores ORDER BY id ASC LIMIT 1")->fetch();
        $storeId = $store['id'];
        $db->exec("UPDATE orders SET store_id = {$storeId} WHERE store_id IS NULL");

        $db->exec("ALTER TABLE orders ADD CONSTRAINT orders_store_fk FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE");
        $db->exec("ALTER TABLE orders ADD CONSTRAINT orders_customer_fk FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL");
        $db->exec("ALTER TABLE orders ADD CONSTRAINT orders_product_fk FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL");

        $db->exec("ALTER TABLE orders MODIFY store_id INT NOT NULL");
    },
    'down' => function (PDO $db) {
        $db->exec("ALTER TABLE orders DROP FOREIGN KEY orders_store_fk");
        $db->exec("ALTER TABLE orders DROP FOREIGN KEY orders_customer_fk");
        $db->exec("ALTER TABLE orders DROP FOREIGN KEY orders_product_fk");
        $db->exec("ALTER TABLE orders DROP COLUMN store_id");
        $db->exec("ALTER TABLE orders DROP COLUMN customer_id");
        $db->exec("ALTER TABLE orders DROP COLUMN product_id");
    },
];