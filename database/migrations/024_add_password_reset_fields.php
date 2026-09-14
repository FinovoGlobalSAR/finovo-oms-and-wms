<?php
return [
    'up' => function (PDO $db) {
        $columns = $db->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);

        if (!in_array('reset_otp', $columns)) {
            $db->exec("ALTER TABLE users ADD COLUMN reset_otp VARCHAR(10) NULL");
        }
        if (!in_array('reset_otp_expires', $columns)) {
            $db->exec("ALTER TABLE users ADD COLUMN reset_otp_expires DATETIME NULL");
        }
    },
    'down' => function (PDO $db) {
        $columns = $db->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
        if (in_array('reset_otp', $columns)) {
            $db->exec("ALTER TABLE users DROP COLUMN reset_otp");
        }
        if (in_array('reset_otp_expires', $columns)) {
            $db->exec("ALTER TABLE users DROP COLUMN reset_otp_expires");
        }
    },
];