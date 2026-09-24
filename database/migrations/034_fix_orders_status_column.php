<?php
return [
    'up' => function (PDO $db) {
        // status column ko ENUM (fixed list) se VARCHAR (koi bhi text)
        // mein badal rahe hain — taaki CanonicalMapper jo bhi naya status
        // value bheje (delivered, cancelled, waghera), wo bina error ke
        // save ho sake.
        $db->exec("ALTER TABLE orders MODIFY status VARCHAR(30) NOT NULL DEFAULT 'pending'");
    },
    'down' => function (PDO $db) {
        $db->exec("ALTER TABLE orders MODIFY status ENUM('pending','processing','delivered','cancelled') NOT NULL DEFAULT 'pending'");
    },
];