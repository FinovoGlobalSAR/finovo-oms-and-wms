<?php

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}

require_once __DIR__ . '/core/Database.php';

[$script, $name, $email, $password] = array_pad($argv, 4, null);

if (!$name || !$email || !$password) {
    exit("Usage: php create-admin.php \"Full Name\" email@example.com \"password\"\n");
}

$email = strtolower(trim($email));

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    exit("Error: email sahi nahi hai.\n");
}

if (strlen($password) < 8) {
    exit("Error: password kam se kam 8 characters ka hona chahiye.\n");
}

$db = Database::getConnection();

$roleStmt = $db->prepare("SELECT id FROM roles WHERE LOWER(name) = 'admin' ORDER BY id ASC LIMIT 1");
$roleStmt->execute();
$roleId = $roleStmt->fetchColumn();

if (!$roleId) {
    exit("Error: 'Admin' role nahi mila. Pehle `php migrate.php` chalao.\n");
}

$existsStmt = $db->prepare("SELECT id FROM users WHERE LOWER(email) = ?");
$existsStmt->execute([$email]);
if ($existsStmt->fetchColumn()) {
    exit("Error: is email ka user pehle se maujood hai.\n");
}

$insert = $db->prepare(
    "INSERT INTO users (role_id, name, email, password_hash, status) VALUES (?, ?, ?, ?, 'active')"
);
$insert->execute([(int) $roleId, trim($name), $email, password_hash($password, PASSWORD_DEFAULT)]);

echo "Admin user ban gaya: {$email}\n";