<?php

return [
    'up' => function (PDO $db) {
        $db->exec("UPDATE settings SET api_key = 'finovo-test-key-12345'");
    },
    'down' => function (PDO $db) {
        $db->exec("UPDATE settings SET api_key = REPLACE(UUID(), '-', '')");
    },
];