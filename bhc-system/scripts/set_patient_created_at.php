<?php

require_once __DIR__ . '/../config/database.php';

// Usage:
// php scripts/set_patient_created_at.php <patientId> <YYYY-MM-DD> [HH:MM:SS]
// Example:
// php scripts/set_patient_created_at.php 1 2026-03-15 09:30:00

if (!isset($argv[1], $argv[2])) {
    fwrite(STDERR, "Usage: php scripts/set_patient_created_at.php <patientId> <YYYY-MM-DD> [HH:MM:SS]\n");
    exit(1);
}

$patientId = (int) $argv[1];
$date = (string) $argv[2];
$time = isset($argv[3]) ? (string) $argv[3] : '09:00:00';

if ($patientId <= 0) {
    fwrite(STDERR, "patientId must be >= 1\n");
    exit(1);
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    fwrite(STDERR, "Invalid date. Use YYYY-MM-DD\n");
    exit(1);
}
if (!preg_match('/^\d{2}:\d{2}:\d{2}$/', $time)) {
    fwrite(STDERR, "Invalid time. Use HH:MM:SS\n");
    exit(1);
}

$createdAt = $date . ' ' . $time;

$db = (new Database())->getConnection();
if (!$db) {
    fwrite(STDERR, "Database connection failed.\n");
    exit(1);
}

$stmt = $db->prepare("UPDATE patients SET created_at = :created_at WHERE id = :id");
$stmt->execute([
    ':created_at' => $createdAt,
    ':id' => $patientId,
]);

if ($stmt->rowCount() === 0) {
    echo "No rows updated (patient id may not exist).\n";
    exit(0);
}

$check = $db->prepare("SELECT id, bhc_id, full_name, created_at FROM patients WHERE id = :id LIMIT 1");
$check->execute([':id' => $patientId]);
$row = $check->fetch(PDO::FETCH_ASSOC);

echo "Updated patient:\n";
echo "ID: {$row['id']}\n";
echo "BHC ID: {$row['bhc_id']}\n";
echo "Name: {$row['full_name']}\n";
echo "Created At: {$row['created_at']}\n";

