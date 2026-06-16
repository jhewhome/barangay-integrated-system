<?php
/**
 * Create patient_visits rows and link existing queue_tickets by patient + calendar day.
 *
 * Usage:
 *   php scripts/backfill_patient_visits.php
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Run this script from the command line only.\n");
    exit(1);
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/PatientVisit.php';

$db = (new Database())->getConnection();
if (!$db) {
    fwrite(STDERR, "Database connection failed.\n");
    exit(1);
}

$rows = $db->query(
    "SELECT id, patient_id, reason, DATE(created_at) AS visit_day
     FROM queue_tickets
     WHERE visit_id IS NULL
     ORDER BY created_at ASC, id ASC"
)->fetchAll(PDO::FETCH_ASSOC);

$visitsCreated = 0;
$linked = 0;

$db->beginTransaction();
try {
    $link = $db->prepare('UPDATE queue_tickets SET visit_id = :visit_id WHERE id = :id AND visit_id IS NULL');
    foreach ($rows as $row) {
        $patientId = (int) $row['patient_id'];
        $visitDay = (string) $row['visit_day'];
        $before = (int) $db->query(
            "SELECT COUNT(*) FROM patient_visits WHERE patient_id = {$patientId} AND visit_date = " . $db->quote($visitDay)
        )->fetchColumn();
        $visitId = PatientVisit::findOrCreateForDate($db, $patientId, $visitDay, $row['reason'] ?? null);
        if ($before === 0) {
            $visitsCreated++;
        }
        $link->execute([':visit_id' => $visitId, ':id' => (int) $row['id']]);
        if ($link->rowCount() > 0) {
            $linked++;
        }
        PatientVisit::refreshStatus($db, $visitId);
    }
    $db->commit();
} catch (Throwable $e) {
    $db->rollBack();
    fwrite(STDERR, 'Backfill failed: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

echo "Patient visit backfill complete.\n";
echo "  Visits created: {$visitsCreated}\n";
echo "  Tickets linked: {$linked}\n";
