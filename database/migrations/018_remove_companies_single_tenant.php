<?php
return [
    'up' => function (PDO $db) {

        $tablesToClean = ['users', 'roles'];

        foreach ($tablesToClean as $table) {

            // Foreign keys jo companies ko reference karti hain
            $fks = $db->query("
                SELECT CONSTRAINT_NAME
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = '{$table}'
                AND REFERENCED_TABLE_NAME = 'companies'
            ")->fetchAll(PDO::FETCH_COLUMN);

            foreach ($fks as $fkName) {
                $db->exec("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$fkName}`");
            }

            // company_id involve karne wale koi bhi index/unique key (PRIMARY chhod ke)
            $indexes = $db->query("
                SELECT DISTINCT INDEX_NAME
                FROM information_schema.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = '{$table}'
                AND COLUMN_NAME = 'company_id'
                AND INDEX_NAME != 'PRIMARY'
            ")->fetchAll(PDO::FETCH_COLUMN);

            foreach ($indexes as $indexName) {
                $db->exec("ALTER TABLE `{$table}` DROP INDEX `{$indexName}`");
            }

            $columns = $db->query("SHOW COLUMNS FROM `{$table}`")->fetchAll(PDO::FETCH_COLUMN);
            if (in_array('company_id', $columns)) {
                $db->exec("ALTER TABLE `{$table}` DROP COLUMN `company_id`");
            }
        }

        $db->exec("DROP TABLE IF EXISTS companies");
    },
    'down' => function (PDO $db) {
        $db->exec("CREATE TABLE IF NOT EXISTS companies (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(150) NOT NULL,
            status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB");
    },
];