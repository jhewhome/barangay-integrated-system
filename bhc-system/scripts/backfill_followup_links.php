<?php
/**
 * Link existing consultations to same-day scheduled appointments (follow-up visits).
 *
 * Usage:
 *   php scripts/backfill_followup_links.php [patient_id]
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Run this script from the command line only.\n");
    exit(1);
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/ConsultationRecord.php';
require_once __DIR__ . '/../models/PatientAppointment.php';

$patientId = null;
if (isset($argv[1]) && is_numeric($argv[1])) {
    $patientId = (int) $argv[1];
}

$db = (new Database())->getConnection();
if (!$db) {
    fwrite(STDERR, "Database connection failed.\n");
    exit(1);
}

$result = ConsultationRecord::backfillFollowUpLinks($db, $patientId);
echo "Follow-up link backfill complete.\n";
echo "  Linked: {$result['linked']}\n";
echo "  Skipped: {$result['skipped']}\n";
