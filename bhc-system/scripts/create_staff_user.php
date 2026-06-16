<?php
/**
 * Create a staff or admin login (command line).
 *
 * Usage:
 *   php scripts/create_staff_user.php <username> <password> [role]
 *
 * role: staff (default) or admin
 *
 * Examples:
 *   php scripts/create_staff_user.php nurse1 MySecurePass staff
 *   php scripts/create_staff_user.php supervisor MySecurePass admin
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Run this script from the command line only.\n");
    exit(1);
}

$username = $argv[1] ?? '';
$password = $argv[2] ?? '';
$role = strtolower($argv[3] ?? 'staff');

if ($username === '' || $password === '') {
    fwrite(STDERR, "Usage: php scripts/create_staff_user.php <username> <password> [staff|admin]\n");
    exit(1);
}

if (!in_array($role, ['staff', 'admin'], true)) {
    fwrite(STDERR, "Role must be staff or admin.\n");
    exit(1);
}

if (!preg_match('/^[a-zA-Z0-9._-]{3,50}$/', $username)) {
    fwrite(STDERR, "Username: 3-50 characters, letters, numbers, dot, underscore, hyphen only.\n");
    exit(1);
}

require_once __DIR__ . '/../config/database.php';

$db = (new Database())->getConnection();
if (!$db) {
    fwrite(STDERR, "Database connection failed.\n");
    exit(1);
}

$exists = $db->prepare('SELECT id FROM users WHERE username = :u LIMIT 1');
$exists->execute([':u' => $username]);
if ($exists->fetchColumn()) {
    fwrite(STDERR, "Username already exists: {$username}\n");
    exit(1);
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $db->prepare(
    'INSERT INTO users (username, password_hash, role, is_active) VALUES (:u, :h, :r, 1)'
);
$stmt->execute([':u' => $username, ':h' => $hash, ':r' => $role]);

echo "Created user: {$username} (role: {$role})\n";
echo "They can sign in at the Login page.\n";
