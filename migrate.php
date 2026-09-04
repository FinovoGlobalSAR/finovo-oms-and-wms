<?php

require __DIR__ . '/core/Database.php';

$db = Database::getConnection();

$db->exec("CREATE TABLE IF NOT EXISTS migrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    migration VARCHAR(255) NOT NULL,
    run_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$alreadyRan = $db->query("SELECT migration FROM migrations")->fetchAll(PDO::FETCH_COLUMN);

$files = glob(__DIR__ . '/database/migrations/*.php');
sort($files);

$ranCount = 0;

foreach ($files as $file) {
    $name = basename($file);

    if (in_array($name, $alreadyRan)) {
        continue;
    }

    echo "Running migration: $name\n";

    $migration = require $file;
    $migration['up']($db);

    $stmt = $db->prepare("INSERT INTO migrations (migration) VALUES (?)");
    $stmt->execute([$name]);

    $ranCount++;
}

if ($ranCount === 0) {
    echo "Everything is already up to date — no new migrations found.\n";
} else {
    echo "\n{$ranCount} new migration(s) ran successfully. Everything is up to date.\n";
    
}