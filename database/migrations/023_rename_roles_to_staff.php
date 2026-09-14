<?php
return [
    'up' => function (PDO $db) {
        // Purane role naam ("Sales", "Warehouse") ko naye naam se rename karo
        $db->exec("UPDATE roles SET name = 'Sales Staff' WHERE name = 'Sales'");
        $db->exec("UPDATE roles SET name = 'Warehouse Staff' WHERE name = 'Warehouse'");
    },
    'down' => function (PDO $db) {
        $db->exec("UPDATE roles SET name = 'Sales' WHERE name = 'Sales Staff'");
        $db->exec("UPDATE roles SET name = 'Warehouse' WHERE name = 'Warehouse Staff'");
    },
];