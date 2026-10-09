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
    exit("Error: email is not correct.\n");
}

if (strlen($password) < 8) {
    exit("Error: minimum 8 characters.\n");
}

$db = Database::getConnection();

$roleStmt = $db->prepare("SELECT id FROM roles WHERE LOWER(name) = 'admin' ORDER BY id ASC LIMIT 1");
$roleStmt->execute();
$roleId = $roleStmt->fetchColumn();

if (!$roleId) {
    exit("Error: Not Found, Run migrations\n");
}

$existsStmt = $db->prepare("SELECT id FROM users WHERE LOWER(email) = ?");
$existsStmt->execute([$email]);
if ($existsStmt->fetchColumn()) {
    exit("Error: Already exists.\n");
}

$insert = $db->prepare(
    "INSERT INTO users (role_id, name, email, password_hash, status) VALUES (?, ?, ?, ?, 'active')"
);
$insert->execute([(int) $roleId, trim($name), $email, password_hash($password, PASSWORD_DEFAULT)]);

echo "Admin user created: {$email}\n";