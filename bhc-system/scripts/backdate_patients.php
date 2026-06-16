<?php

require_once __DIR__ . '/../config/database.php';

// Usage:
// php scripts/backdate_patients.php [count] [startDate] [endDate]
// Examples:
// php scripts/backdate_patients.php 30 2026-03-15 2026-03-31

$count = 30;
$startDate = '2026-03-15';
$endDate = '2026-03-31';

if (isset($argv[1]) && is_numeric($argv[1])) {
    $count = (int) $argv[1];
}
if (isset($argv[2]) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $argv[2])) {
    $startDate = $argv[2];
}
if (isset($argv[3]) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $argv[3])) {
    $endDate = $argv[3];
}

if ($count < 1) {
    fwrite(STDERR, "Count must be >= 1\n");
    exit(1);
}

$startTs = strtotime($startDate . ' 09:00:00');
$endTs = strtotime($endDate . ' 17:30:00');
if ($startTs === false || $endTs === false || $endTs <= $startTs) {
    fwrite(STDERR, "Invalid date range.\n");
    exit(1);
}

$db = (new Database())->getConnection();
if (!$db) {
    fwrite(STDERR, "Database connection failed.\n");
    exit(1);
}

// Backdate most recent N patients (safe for your recent dummy inserts)
$stmt = $db->prepare("SELECT id FROM patients ORDER BY id DESC LIMIT :limit");
$stmt->bindValue(':limit', $count, PDO::PARAM_INT);
$stmt->execute();
$ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

if (empty($ids)) {
    echo "No patients found.\n";
    exit(0);
}

$db->beginTransaction();
try {
    $upd = $db->prepare("UPDATE patients SET created_at = :created_at WHERE id = :id");
    foreach ($ids as $id) {
        $ts = random_int($startTs, $endTs);
        $dt = date('Y-m-d H:i:s', $ts);
        $upd->execute([
            ':created_at' => $dt,
            ':id' => (int) $id,
        ]);
    }
    $db->commit();
} catch (Throwable $e) {
    $db->rollBack();
    fwrite(STDERR, "Backdate failed: " . $e->getMessage() . "\n");
    exit(1);
}

echo "Backdated " . count($ids) . " patient records to {$startDate} .. {$endDate}.\n";

