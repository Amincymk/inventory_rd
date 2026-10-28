<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Run this script from the command line.');
}

require_once __DIR__ . '/../config/database.php';

function ask(string $prompt): string
{
    echo $prompt;
    return trim((string)fgets(STDIN));
}

$name = ask('Administrator name: ');
$email = ask('Administrator email: ');
$password = ask('Administrator password: ');
$confirm = ask('Confirm password: ');

if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    exit("Enter a name and a valid email address.\n");
}

if (strlen($password) < 12) {
    exit("Use a password with at least 12 characters.\n");
}

if (!hash_equals($password, $confirm)) {
    exit("The passwords do not match.\n");
}

$stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
$stmt->execute(['email' => $email]);

if ($stmt->fetch()) {
    exit("An account with that email already exists.\n");
}

$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare(
    'INSERT INTO users (name, email, password, role, is_active)
     VALUES (:name, :email, :password, :role, 1)'
);

$stmt->execute([
    'name' => $name,
    'email' => $email,
    'password' => $hash,
    'role' => 'Admin',
]);

echo "Administrator created successfully.\n";
