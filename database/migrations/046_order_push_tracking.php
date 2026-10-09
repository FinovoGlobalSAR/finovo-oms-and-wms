<?php
// eBay pe tracking number bhejne ke baad yahan time likh dete hain,
// taake wahi tracking dobara na bheji jaye (eBay pe duplicate shipment ban jati).

return [
    'up' => function (PDO $db) {
        $existing = $db->query("SHOW COLUMNS FROM orders")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('platform_tracking_pushed_at', $existing, true)) {
            $db->exec("ALTER TABLE orders ADD COLUMN platform_tracking_pushed_at DATETIME NULL");
        }
    },

    'down' => function (PDO $db) {
        $existing = $db->query("SHOW COLUMNS FROM orders")->fetchAll(PDO::FETCH_COLUMN);
        if (in_array('platform_tracking_pushed_at', $existing, true)) {
            $db->exec("ALTER TABLE orders DROP COLUMN platform_tracking_pushed_at");
        }
    },
];