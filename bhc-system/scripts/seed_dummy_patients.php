<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Patient.php';

$count = 30;
if (isset($argv[1]) && is_numeric($argv[1])) {
    $count = (int) $argv[1];
}

if ($count < 1) {
    fwrite(STDERR, "Count must be >= 1\n");
    exit(1);
}

$db = (new Database())->getConnection();
if (!$db) {
    fwrite(STDERR, "Database connection failed.\n");
    exit(1);
}

$firstNames = [
    'Juan', 'Maria', 'Jose', 'Ana', 'Mark', 'Grace', 'Paolo', 'Angel', 'Joshua', 'Patricia',
    'Carlo', 'Catherine', 'Jasmine', 'Miguel', 'Bianca', 'Erika', 'Ramon', 'Luz', 'Noel', 'Ivy',
    'Gabriel', 'Sofia', 'Daniel', 'Andrea', 'Francis', 'Shaira', 'Jerome', 'Karla', 'Ryan', 'Aileen',
];
$middleInitials = range('A', 'Z');
$lastNames = [
    'Dela Cruz', 'Santos', 'Reyes', 'Garcia', 'Mendoza', 'Gonzales', 'Bautista', 'Villanueva', 'Torres', 'Flores',
    'Ramos', 'Aquino', 'Navarro', 'Castillo', 'Fernandez', 'Cruz', 'Valdez', 'Rivera', 'Domingo', 'Hernandez',
];

function pick(array $arr)
{
    return $arr[random_int(0, count($arr) - 1)];
}

function maybeContact(): ?string
{
    // ~75% have a contact number
    if (random_int(1, 100) <= 25) {
        return null;
    }
    $suffix = str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT);
    return '09' . $suffix;
}

function randomSex(): string
{
    return random_int(0, 1) === 0 ? 'M' : 'F';
}

function randomBirthdate(): string
{
    // Roughly 1..80 years old
    $years = random_int(1, 80);
    $days = random_int(0, 364);
    $ts = strtotime("-{$years} years") - ($days * 86400);
    return date('Y-m-d', $ts);
}

$inserted = 0;
$db->beginTransaction();
try {
    for ($i = 0; $i < $count; $i++) {
        $fullName = pick($firstNames) . ' ' . pick($middleInitials) . '. ' . pick($lastNames);
        $bhcId = Patient::nextBhcId($db);
        Patient::create($db, [
            'bhc_id' => $bhcId,
            'full_name' => $fullName,
            'sex' => randomSex(),
            'birthdate' => randomBirthdate(),
            'contact_number' => maybeContact(),
        ]);
        $inserted++;
    }
    $db->commit();
} catch (Throwable $e) {
    $db->rollBack();
    fwrite(STDERR, "Seeding failed: " . $e->getMessage() . "\n");
    exit(1);
}

echo "Inserted {$inserted} dummy patients.\n";

