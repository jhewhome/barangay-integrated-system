<?php
/**
 * Create saved medicine receipt documents for past consultations that already have
 * dispensed medicines or receipt_issued flags but no clinical_documents row yet.
 *
 * Usage:
 *   php scripts/backfill_medicine_receipt_documents.php [patient_id]
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Run this script from the command line only.\n");
    exit(1);
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/ConsultationRecord.php';
require_once __DIR__ . '/../models/MedicineDispensing.php';
require_once __DIR__ . '/../models/ClinicalDocument.php';

$patientId = null;
if (isset($argv[1]) && is_numeric($argv[1])) {
    $patientId = (int) $argv[1];
}

$db = (new Database())->getConnection();
if (!$db) {
    fwrite(STDERR, "Database connection failed.\n");
    exit(1);
}

$result = ClinicalDocument::backfillMedicineReceipts($db, $patientId);
echo "Backfill complete.\n";
echo "  Created: {$result['created']}\n";
echo "  Skipped (already saved): {$result['skipped']}\n";
