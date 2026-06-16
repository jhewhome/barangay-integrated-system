<?php
/**
 * Seed sample clinical / operational data for existing patients.
 *
 * Usage:
 *   php scripts/seed_clinical_data.php [limit] [--force]
 *
 * Options:
 *   limit   Max patients to process (default: all eligible)
 *   --force Also seed patients who already have consultation records
 *
 * Creates (per patient, when eligible):
 *   - Past consultation visits with diagnoses
 *   - Medicine lines (dispensed / prescribed)
 *   - Historical queue tickets (consultation + pharmacy)
 *   - Appointments (past completed, future scheduled, some today)
 *   - Doctor comments (when a doctor account exists)
 *
 * Also ensures a demo doctor login exists: dr.santos / doctor123
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Run this script from the command line only.\n");
    exit(1);
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/ConsultationRecord.php';
require_once __DIR__ . '/../models/MedicineDispensing.php';
require_once __DIR__ . '/../models/PatientAppointment.php';
require_once __DIR__ . '/../models/DoctorComment.php';

$limit = null;
$force = false;
foreach (array_slice($argv, 1) as $arg) {
    if ($arg === '--force') {
        $force = true;
        continue;
    }
    if (is_numeric($arg)) {
        $limit = max(1, (int) $arg);
    }
}

function pick(array $arr): mixed
{
    return $arr[random_int(0, count($arr) - 1)];
}

function randomPastDate(int $maxDaysBack = 90): string
{
    return date('Y-m-d', strtotime('-' . random_int(3, $maxDaysBack) . ' days'));
}

function randomFutureDate(int $maxDaysAhead = 21): string
{
    return date('Y-m-d', strtotime('+' . random_int(1, $maxDaysAhead) . ' days'));
}

function randomClinicTime(): string
{
    return sprintf('%02d:%02d:00', random_int(8, 16), pick([0, 15, 30, 45]));
}

function visitTimestamps(string $date): array
{
    $created = $date . ' ' . sprintf('%02d:%02d:00', random_int(8, 11), pick([0, 15, 30, 45]));
    $calledTs = strtotime($created) + random_int(300, 1500);
    $doneTs = $calledTs + random_int(600, 2400);
    return [
        'created_at' => $created,
        'called_at' => date('Y-m-d H:i:s', $calledTs),
        'completed_at' => date('Y-m-d H:i:s', $doneTs),
    ];
}

/** @return array<string,array<int,list<array{medicine_name:string,quantity:float,unit:string,receipt_issued:bool}>>> */
function diagnosisMedicineMap(): array
{
    return [
        'Upper respiratory tract infection (URTI)' => [
            ['medicine_name' => 'Paracetamol 500mg', 'quantity' => 20, 'unit' => 'tablet(s)', 'receipt_issued' => true],
            ['medicine_name' => 'Ambroxol 30mg', 'quantity' => 10, 'unit' => 'tablet(s)', 'receipt_issued' => true],
        ],
        'Hypertension — follow-up' => [
            ['medicine_name' => 'Losartan 50mg', 'quantity' => 30, 'unit' => 'tablet(s)', 'receipt_issued' => true],
            ['medicine_name' => 'Amlodipine 5mg', 'quantity' => 30, 'unit' => 'tablet(s)', 'receipt_issued' => false],
        ],
        'Type 2 diabetes mellitus' => [
            ['medicine_name' => 'Metformin 500mg', 'quantity' => 60, 'unit' => 'tablet(s)', 'receipt_issued' => true],
        ],
        'Acute gastroenteritis' => [
            ['medicine_name' => 'Oral rehydration salts', 'quantity' => 5, 'unit' => 'sachet(s)', 'receipt_issued' => true],
            ['medicine_name' => 'Loperamide 2mg', 'quantity' => 6, 'unit' => 'capsule(s)', 'receipt_issued' => true],
        ],
        'Allergic rhinitis' => [
            ['medicine_name' => 'Cetirizine 10mg', 'quantity' => 10, 'unit' => 'tablet(s)', 'receipt_issued' => true],
        ],
        'Urinary tract infection' => [
            ['medicine_name' => 'Ciprofloxacin 500mg', 'quantity' => 14, 'unit' => 'tablet(s)', 'receipt_issued' => true],
            ['medicine_name' => 'Paracetamol 500mg', 'quantity' => 10, 'unit' => 'tablet(s)', 'receipt_issued' => true],
        ],
        'Dermatitis / skin rash' => [
            ['medicine_name' => 'Hydrocortisone 1% cream', 'quantity' => 1, 'unit' => 'tube', 'receipt_issued' => true],
            ['medicine_name' => 'Cetirizine 10mg', 'quantity' => 5, 'unit' => 'tablet(s)', 'receipt_issued' => false],
        ],
        'Mild anemia' => [
            ['medicine_name' => 'Ferrous sulfate 325mg', 'quantity' => 30, 'unit' => 'tablet(s)', 'receipt_issued' => true],
            ['medicine_name' => 'Multivitamins', 'quantity' => 30, 'unit' => 'tablet(s)', 'receipt_issued' => true],
        ],
        'Prenatal check-up' => [
            ['medicine_name' => 'Folic acid 5mg', 'quantity' => 30, 'unit' => 'tablet(s)', 'receipt_issued' => true],
            ['medicine_name' => 'Iron + folic acid', 'quantity' => 30, 'unit' => 'tablet(s)', 'receipt_issued' => true],
        ],
        'Fever, unspecified' => [
            ['medicine_name' => 'Paracetamol 500mg', 'quantity' => 15, 'unit' => 'tablet(s)', 'receipt_issued' => true],
            ['medicine_name' => 'Ibuprofen 400mg', 'quantity' => 10, 'unit' => 'tablet(s)', 'receipt_issued' => false],
        ],
    ];
}

function ensureDoctorUser(PDO $db): int
{
    $stmt = $db->prepare("SELECT id FROM users WHERE username = 'dr.santos' LIMIT 1");
    $stmt->execute();
    $id = $stmt->fetchColumn();
    if ($id) {
        return (int) $id;
    }

    $hash = password_hash('doctor123', PASSWORD_DEFAULT);
    $ins = $db->prepare(
        "INSERT INTO users (username, password_hash, role, is_active, display_name)
         VALUES ('dr.santos', :hash, 'doctor', 1, 'Maria Santos')"
    );
    $ins->execute([':hash' => $hash]);
    return (int) $db->lastInsertId();
}

/** @return array<string,int> */
function stationIds(PDO $db): array
{
    $out = [];
    foreach ($db->query("SELECT id, code FROM stations")->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $out[(string) $row['code']] = (int) $row['id'];
    }
    return $out;
}

function nextHistoricalTicketNo(string $code, int &$counter): string
{
    $counter++;
    return $code . '-' . str_pad((string) $counter, 3, '0', STR_PAD_LEFT);
}

function backdateRow(PDO $db, string $table, int $id, string $createdAt): void
{
    $stmt = $db->prepare("UPDATE {$table} SET created_at = :created_at WHERE id = :id");
    $stmt->execute([':created_at' => $createdAt, ':id' => $id]);
}

$db = (new Database())->getConnection();
if (!$db) {
    fwrite(STDERR, "Database connection failed.\n");
    exit(1);
}

$staffId = (int) ($db->query("SELECT id FROM users WHERE role IN ('admin','staff') ORDER BY id ASC LIMIT 1")->fetchColumn() ?: 1);
$doctorId = ensureDoctorUser($db);
$stations = stationIds($db);
$cnStation = $stations['CN'] ?? 3;
$phStation = $stations['PH'] ?? 4;

$diagMap = diagnosisMedicineMap();
$diagnoses = array_keys($diagMap);
$reasons = ['Fever', 'Follow-up consultation', 'Medication refill', 'General check-up', 'Cough and colds', 'Prenatal visit', 'Blood pressure check'];
$apptPurposes = [
    'Follow-up consultation',
    'Blood pressure monitoring',
    'Wound dressing',
    'Lab results review',
    'Medication refill',
    'Post-treatment check',
    'Prenatal follow-up',
];
$doctorNotes = [
    'Advise rest and increased fluid intake. Return if symptoms worsen.',
    'Blood pressure slightly elevated; lifestyle modification discussed.',
    'Continue current regimen. Follow up in 2 weeks.',
    'Patient tolerating medications well. No adverse effects reported.',
    'Counselled on medication compliance and diet.',
    'Improvement noted since last visit.',
];

$sql = "SELECT p.id, p.bhc_id, p.full_name FROM patients p";
if (!$force) {
    $sql .= " WHERE NOT EXISTS (
                SELECT 1 FROM consultation_records cr WHERE cr.patient_id = p.id
              )";
}
$sql .= " ORDER BY p.id ASC";
if ($limit !== null) {
    $sql .= " LIMIT " . (int) $limit;
}

$patients = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
if (empty($patients)) {
    echo "No eligible patients to seed. Use --force to add data for patients who already have records.\n";
    exit(0);
}

$ticketIns = $db->prepare(
    "INSERT INTO queue_tickets
     (station_id, patient_id, ticket_no, reason, status, assigned_doctor_id, created_at, called_at, completed_at)
     VALUES
     (:station_id, :patient_id, :ticket_no, :reason, 'done', :doctor_id, :created_at, :called_at, :completed_at)"
);

$apptIns = $db->prepare(
    "INSERT INTO patient_appointments
     (patient_id, appointment_date, appointment_time, purpose, station_id, status, notes, created_by, created_at, completed_at)
     VALUES
     (:patient_id, :appointment_date, :appointment_time, :purpose, :station_id, :status, :notes, :created_by, :created_at, :completed_at)"
);

$ticketCounter = 100;
$stats = [
    'patients' => 0,
    'consultations' => 0,
    'medicines' => 0,
    'tickets' => 0,
    'appointments' => 0,
    'comments' => 0,
];

$db->beginTransaction();
try {
    $todayAssigned = 0;
    foreach ($patients as $i => $patient) {
        $patientId = (int) $patient['id'];
        $visitCount = random_int(1, 2);

        for ($v = 0; $v < $visitCount; $v++) {
            $visitDate = randomPastDate(120);
            $ts = visitTimestamps($visitDate);
            $diagnosis = pick($diagnoses);
            $reason = pick($reasons);

            $ticketIns->execute([
                ':station_id' => $cnStation,
                ':patient_id' => $patientId,
                ':ticket_no' => nextHistoricalTicketNo('CN', $ticketCounter),
                ':reason' => $reason,
                ':doctor_id' => $doctorId,
                ':created_at' => $ts['created_at'],
                ':called_at' => $ts['called_at'],
                ':completed_at' => $ts['completed_at'],
            ]);
            $ticketId = (int) $db->lastInsertId();
            $stats['tickets']++;

            $consultId = ConsultationRecord::create($db, [
                'patient_id' => $patientId,
                'queue_ticket_id' => $ticketId,
                'doctor_id' => $doctorId,
                'diagnosis' => $diagnosis,
                'clinical_notes' => pick([
                    'Vital signs stable. No acute distress.',
                    'Mild symptoms on examination.',
                    'Patient alert and cooperative.',
                    'Advised follow-up as needed.',
                    null,
                ]),
                'consultation_date' => $visitDate,
                'created_by' => $staffId,
            ]);
            backdateRow($db, 'consultation_records', $consultId, $ts['completed_at']);
            $stats['consultations']++;

            $medLines = $diagMap[$diagnosis] ?? [
                ['medicine_name' => 'Paracetamol 500mg', 'quantity' => 10, 'unit' => 'tablet(s)', 'receipt_issued' => true],
            ];
            if (random_int(1, 100) <= 20) {
                $medLines[0]['receipt_issued'] = false;
                $dispenseStatus = 'prescribed';
            } else {
                $dispenseStatus = 'dispensed';
            }

            $medCount = MedicineDispensing::createBatch($db, $patientId, $medLines, [
                'consultation_id' => $consultId,
                'queue_ticket_id' => $ticketId,
                'created_by' => $staffId,
                'dispense_status' => $dispenseStatus,
            ]);
            $stats['medicines'] += $medCount;

            $medRows = $db->prepare(
                "SELECT id FROM medicine_dispensings WHERE consultation_id = :cid ORDER BY id ASC"
            );
            $medRows->execute([':cid' => $consultId]);
            $phTs = visitTimestamps($visitDate);
            $phTs['created_at'] = date('Y-m-d H:i:s', strtotime($ts['completed_at']) + random_int(300, 900));
            $phTs['called_at'] = date('Y-m-d H:i:s', strtotime($phTs['created_at']) + random_int(120, 600));
            $phTs['completed_at'] = date('Y-m-d H:i:s', strtotime($phTs['called_at']) + random_int(300, 1200));

            foreach ($medRows->fetchAll(PDO::FETCH_COLUMN) as $medId) {
                backdateRow($db, 'medicine_dispensings', (int) $medId, $phTs['completed_at']);
            }

            if ($dispenseStatus === 'dispensed') {
                $ticketIns->execute([
                    ':station_id' => $phStation,
                    ':patient_id' => $patientId,
                    ':ticket_no' => nextHistoricalTicketNo('PH', $ticketCounter),
                    ':reason' => 'Medicine dispensing',
                    ':doctor_id' => null,
                    ':created_at' => $phTs['created_at'],
                    ':called_at' => $phTs['called_at'],
                    ':completed_at' => $phTs['completed_at'],
                ]);
                $stats['tickets']++;
            }

            if (random_int(1, 100) <= 55) {
                $commentId = DoctorComment::create($db, [
                    'patient_id' => $patientId,
                    'doctor_id' => $doctorId,
                    'consultation_id' => $consultId,
                    'queue_ticket_id' => $ticketId,
                    'comment' => pick($doctorNotes),
                ]);
                backdateRow($db, 'doctor_comments', $commentId, $ts['completed_at']);
                $stats['comments']++;
            }
        }

        // Past resolved appointments
        if (random_int(1, 100) <= 45) {
            $pastDate = randomPastDate(60);
            $pastStatus = pick(['completed', 'completed', 'cancelled', 'no_show']);
            $completedAt = $pastStatus === 'completed'
                ? $pastDate . ' ' . randomClinicTime()
                : null;
            $apptIns->execute([
                ':patient_id' => $patientId,
                ':appointment_date' => $pastDate,
                ':appointment_time' => randomClinicTime(),
                ':purpose' => pick($apptPurposes),
                ':station_id' => $cnStation,
                ':status' => $pastStatus,
                ':notes' => pick([null, 'Sample seeded appointment', 'Follow-up from prior visit']),
                ':created_by' => $staffId,
                ':created_at' => $pastDate . ' 07:30:00',
                ':completed_at' => $completedAt,
            ]);
            $stats['appointments']++;
        }

        // Future scheduled — first 5 patients also get one today (for dashboard / routing banner)
        $scheduleFuture = random_int(1, 100) <= 50 || $todayAssigned < 5;
        if ($scheduleFuture) {
            $isToday = $todayAssigned < 5 && random_int(1, 100) <= 70;
            $apptDate = $isToday ? date('Y-m-d') : randomFutureDate(28);
            if ($isToday) {
                $todayAssigned++;
            }
            $apptIns->execute([
                ':patient_id' => $patientId,
                ':appointment_date' => $apptDate,
                ':appointment_time' => randomClinicTime(),
                ':purpose' => pick($apptPurposes),
                ':station_id' => pick([$cnStation, $phStation, null]),
                ':status' => 'scheduled',
                ':notes' => $isToday ? 'Appointment today (sample data)' : 'Upcoming follow-up (sample data)',
                ':created_by' => $staffId,
                ':created_at' => date('Y-m-d H:i:s', strtotime('-' . random_int(1, 10) . ' days')),
                ':completed_at' => null,
            ]);
            $stats['appointments']++;
        }

        $stats['patients']++;
    }

    $db->commit();
} catch (Throwable $e) {
    $db->rollBack();
    fwrite(STDERR, "Seeding failed: " . $e->getMessage() . "\n");
    exit(1);
}

echo "Seeded sample data for {$stats['patients']} patient(s).\n";
echo "  Consultations: {$stats['consultations']}\n";
echo "  Medicine lines:  {$stats['medicines']}\n";
echo "  Queue tickets:   {$stats['tickets']}\n";
echo "  Appointments:    {$stats['appointments']}\n";
echo "  Doctor comments: {$stats['comments']}\n";
echo "\nDemo doctor login: dr.santos / doctor123\n";
