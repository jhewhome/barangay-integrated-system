<?php



class ReportController extends Controller

{

    private PDO $db;



    public function __construct()

    {

        $this->db = (new Database())->getConnection();

    }



    /** @return array<string,mixed> */

    private function resolveFilter(): array

    {

        return ReportPeriod::resolve($_GET);

    }



    /** @param array<string,mixed> $filter */

    private function viewMeta(array $filter): array

    {

        return [

            'filter' => $filter,

            'periodLabel' => (string) $filter['label'],

        ];

    }



    public function index(): void

    {

        $this->requireRole('admin');

        $this->view('reports/index');

    }



    public function monthly(): void

    {

        $this->requireRole('admin');

        $filter = $this->resolveFilter();



        $this->view('reports/monthly', array_merge($this->viewMeta($filter), [

            'totals' => QueueTicket::totalsForRange($this->db, $filter['start'], $filter['end']),

            'byStation' => QueueTicket::stationSummaryForRange($this->db, $filter['start'], $filter['end']),

        ]));

    }



    public function monthlyExport(): void

    {

        $this->requireRole('admin');

        $filter = $this->resolveFilter();

        $totals = QueueTicket::totalsForRange($this->db, $filter['start'], $filter['end']);

        $byStation = QueueTicket::stationSummaryForRange($this->db, $filter['start'], $filter['end']);

        $slug = ReportPeriod::fileSlug($filter);



        $this->sendCsv('bhc-queue-report-' . $slug . '.csv', function ($out) use ($filter, $totals, $byStation) {

            fputcsv($out, ['Barangay Health Center — Queue Report']);

            fputcsv($out, ['Period', (string) $filter['label']]);

            fputcsv($out, []);

            fputcsv($out, ['Summary']);

            fputcsv($out, ['Total tickets', (int) $totals['total']]);

            fputcsv($out, ['Completed (done)', (int) $totals['done']]);

            fputcsv($out, ['Skipped', (int) $totals['skipped']]);

            fputcsv($out, ['Waiting', (int) $totals['waiting']]);

            fputcsv($out, ['Serving', (int) $totals['serving']]);

            fputcsv($out, ['Avg wait (seconds)', (int) $totals['avg_wait_seconds']]);

            fputcsv($out, ['Avg service (seconds)', (int) $totals['avg_service_seconds']]);

            fputcsv($out, []);

            fputcsv($out, ['Station', 'Total', 'Done', 'Skipped', 'Waiting', 'Serving', 'Avg wait (sec)', 'Avg service (sec)']);

            foreach ($byStation as $r) {

                fputcsv($out, [

                    (string) $r['station_name'],

                    (int) $r['total'],

                    (int) $r['done'],

                    (int) $r['skipped'],

                    (int) $r['waiting'],

                    (int) $r['serving'],

                    (int) $r['avg_wait_seconds'],

                    (int) $r['avg_service_seconds'],

                ]);

            }

        });

    }



    public function clinical(): void

    {

        $this->requireRole('admin');

        $filter = $this->resolveFilter();

        $start = (string) $filter['start'];

        $end = (string) $filter['end'];



        $this->view('reports/clinical', array_merge($this->viewMeta($filter), [

            'consultTotals' => ConsultationRecord::totalsForRange($this->db, $start, $end),

            'medTotals' => MedicineDispensing::totalsForRange($this->db, $start, $end),

            'topDiagnoses' => ConsultationRecord::topDiagnosesForRange($this->db, $start, $end, 15),

            'topMedicines' => MedicineDispensing::topMedicinesForRange($this->db, $start, $end, 15),

            'consultations' => ConsultationRecord::listForRange($this->db, $start, $end, 200),

            'medicines' => MedicineDispensing::listForRange($this->db, $start, $end, 300),

        ]));

    }



    public function clinicalExport(): void

    {

        $this->requireRole('admin');

        $filter = $this->resolveFilter();

        $start = (string) $filter['start'];

        $end = (string) $filter['end'];

        $consultTotals = ConsultationRecord::totalsForRange($this->db, $start, $end);

        $medTotals = MedicineDispensing::totalsForRange($this->db, $start, $end);

        $topDiagnoses = ConsultationRecord::topDiagnosesForRange($this->db, $start, $end, 50);

        $topMedicines = MedicineDispensing::topMedicinesForRange($this->db, $start, $end, 50);

        $consultations = ConsultationRecord::listForRange($this->db, $start, $end, 1000);

        $medicines = MedicineDispensing::listForRange($this->db, $start, $end, 2000);

        $slug = ReportPeriod::fileSlug($filter);



        $this->sendCsv('bhc-clinical-report-' . $slug . '.csv', function ($out) use ($filter, $consultTotals, $medTotals, $topDiagnoses, $topMedicines, $consultations, $medicines) {

            fputcsv($out, ['Barangay Health Center — Clinical Report']);

            fputcsv($out, ['Period', (string) $filter['label']]);

            fputcsv($out, []);

            fputcsv($out, ['Consultation summary']);

            fputcsv($out, ['Total consultations', (int) $consultTotals['total']]);

            fputcsv($out, ['Unique patients', (int) $consultTotals['unique_patients']]);

            fputcsv($out, []);

            fputcsv($out, ['Medicine summary']);

            fputcsv($out, ['Total medicine lines', (int) $medTotals['total_lines']]);

            fputcsv($out, ['Prescribed', (int) $medTotals['prescribed']]);

            fputcsv($out, ['Dispensed', (int) $medTotals['dispensed']]);

            fputcsv($out, ['Receipts issued', (int) $medTotals['receipts']]);

            fputcsv($out, []);

            fputcsv($out, ['Top diagnoses', 'Case count']);

            foreach ($topDiagnoses as $row) {

                fputcsv($out, [(string) $row['diagnosis'], (int) $row['case_count']]);

            }

            fputcsv($out, []);

            fputcsv($out, ['Top medicines', 'Unit', 'Total qty', 'Lines', 'Dispensed lines']);

            foreach ($topMedicines as $row) {

                fputcsv($out, [

                    (string) $row['medicine_name'],

                    (string) $row['unit'],

                    (string) $row['total_quantity'],

                    (int) $row['line_count'],

                    (int) $row['dispensed_count'],

                ]);

            }

            fputcsv($out, []);

            fputcsv($out, ['Consultations — detail']);

            fputcsv($out, ['Date', 'BHC ID', 'Patient', 'Diagnosis', 'Doctor', 'Recorded by']);

            foreach ($consultations as $c) {

                $doctor = trim((string) ($c['doctor_display_name'] ?? $c['doctor_username'] ?? ''));

                fputcsv($out, [

                    (string) $c['consultation_date'],

                    (string) $c['bhc_id'],

                    (string) $c['full_name'],

                    (string) $c['diagnosis'],

                    $doctor,

                    (string) ($c['recorded_by_name'] ?? ''),

                ]);

            }

            fputcsv($out, []);

            fputcsv($out, ['Medicines — detail']);

            fputcsv($out, ['Date', 'BHC ID', 'Patient', 'Medicine', 'Qty', 'Unit', 'Status', 'Receipt', 'Diagnosis']);

            foreach ($medicines as $m) {

                fputcsv($out, [

                    substr((string) ($m['created_at'] ?? ''), 0, 10),

                    (string) $m['bhc_id'],

                    (string) $m['full_name'],

                    (string) $m['medicine_name'],

                    (string) $m['quantity'],

                    (string) $m['unit'],

                    (string) $m['dispense_status'],

                    !empty($m['receipt_issued']) ? 'Yes' : 'No',

                    (string) ($m['diagnosis'] ?? ''),

                ]);

            }

        });

    }



    public function appointments(): void

    {

        $this->requireRole('admin');

        $filter = $this->resolveFilter();

        $start = (string) $filter['start'];

        $end = (string) $filter['end'];



        $totals = PatientAppointment::totalsForRange($this->db, $start, $end);

        $showRate = 0;

        $resolved = (int) $totals['completed'] + (int) $totals['no_show'] + (int) $totals['cancelled'];

        if ($resolved > 0) {

            $showRate = (int) round(((int) $totals['completed'] / $resolved) * 100);

        }



        $this->view('reports/appointments', array_merge($this->viewMeta($filter), [

            'totals' => $totals,

            'showRate' => $showRate,

            'appointments' => PatientAppointment::listForRange($this->db, $start, $end, 500),

        ]));

    }



    public function appointmentsExport(): void

    {

        $this->requireRole('admin');

        $filter = $this->resolveFilter();

        $start = (string) $filter['start'];

        $end = (string) $filter['end'];

        $totals = PatientAppointment::totalsForRange($this->db, $start, $end);

        $appointments = PatientAppointment::listForRange($this->db, $start, $end, 2000);

        $slug = ReportPeriod::fileSlug($filter);



        $this->sendCsv('bhc-appointments-report-' . $slug . '.csv', function ($out) use ($filter, $totals, $appointments) {

            fputcsv($out, ['Barangay Health Center — Appointments Report']);

            fputcsv($out, ['Period', (string) $filter['label']]);

            fputcsv($out, []);

            fputcsv($out, ['Summary']);

            fputcsv($out, ['Total appointments', (int) $totals['total']]);

            fputcsv($out, ['Unique patients', (int) $totals['unique_patients']]);

            fputcsv($out, ['Scheduled', (int) $totals['scheduled']]);

            fputcsv($out, ['Completed', (int) $totals['completed']]);

            fputcsv($out, ['Cancelled', (int) $totals['cancelled']]);

            fputcsv($out, ['No-show', (int) $totals['no_show']]);

            fputcsv($out, []);

            fputcsv($out, ['Date', 'Time', 'BHC ID', 'Patient', 'Contact', 'Purpose', 'Station', 'Status', 'Notes']);

            foreach ($appointments as $a) {

                fputcsv($out, [

                    (string) $a['appointment_date'],

                    $a['appointment_time'] ? substr((string) $a['appointment_time'], 0, 5) : '',

                    (string) $a['bhc_id'],

                    (string) $a['full_name'],

                    (string) ($a['contact_number'] ?? ''),

                    (string) ($a['purpose'] ?? ''),

                    (string) ($a['station_name'] ?? ''),

                    (string) $a['status'],

                    (string) ($a['notes'] ?? ''),

                ]);

            }

        });

    }



    public function daily(): void
    {
        $this->requireRole('admin');
        $date = DailyOperations::resolveDate($_GET);
        $this->view('reports/daily', [
            'date' => $date,
            'dateLabel' => date('F j, Y', strtotime($date)),
            'totals' => DailyOperations::ticketTotals($this->db, $date),
            'visitStats' => PatientVisit::statsForDate($this->db, $date),
            'triageStats' => TriageRecord::statsForDate($this->db, $date),
            'byStation' => DailyOperations::stationBreakdown($this->db, $date),
            'topReasons' => DailyOperations::topReasons($this->db, $date, 15),
            'hourlyVolume' => DailyOperations::hourlyVolume($this->db, $date),
        ]);
    }

    public function dailyExport(): void
    {
        $this->requireRole('admin');
        $date = DailyOperations::resolveDate($_GET);
        $totals = DailyOperations::ticketTotals($this->db, $date);
        $visitStats = PatientVisit::statsForDate($this->db, $date);
        $triageStats = TriageRecord::statsForDate($this->db, $date);
        $byStation = DailyOperations::stationBreakdown($this->db, $date);
        $topReasons = DailyOperations::topReasons($this->db, $date, 50);
        $hourlyVolume = DailyOperations::hourlyVolume($this->db, $date);

        $this->sendCsv('bhc-daily-operations-' . $date . '.csv', function ($out) use ($date, $totals, $visitStats, $triageStats, $byStation, $topReasons, $hourlyVolume) {
            fputcsv($out, ['Barangay Health Center — Daily Operations Report']);
            fputcsv($out, ['Date', $date]);
            fputcsv($out, []);
            fputcsv($out, ['Queue summary']);
            fputcsv($out, ['Total tickets', (int) $totals['total']]);
            fputcsv($out, ['Completed', (int) $totals['done']]);
            fputcsv($out, ['Skipped', (int) $totals['skipped']]);
            fputcsv($out, ['Waiting', (int) $totals['waiting']]);
            fputcsv($out, ['Serving', (int) $totals['serving']]);
            fputcsv($out, []);
            fputcsv($out, ['Patient visits']);
            fputcsv($out, ['Total visits', (int) $visitStats['total']]);
            fputcsv($out, ['Open', (int) $visitStats['open']]);
            fputcsv($out, ['Completed', (int) $visitStats['completed']]);
            fputcsv($out, []);
            fputcsv($out, ['Triage records', (int) $triageStats['total']]);
            fputcsv($out, ['With vitals captured', (int) $triageStats['with_vitals']]);
            fputcsv($out, []);
            fputcsv($out, ['Station', 'Total', 'Done', 'Skipped', 'Waiting', 'Serving', 'Avg wait (sec)', 'Avg service (sec)']);
            foreach ($byStation as $r) {
                fputcsv($out, [
                    (string) $r['station_name'],
                    (int) $r['total'],
                    (int) $r['done'],
                    (int) $r['skipped'],
                    (int) $r['waiting'],
                    (int) $r['serving'],
                    (int) $r['avg_wait_seconds'],
                    (int) $r['avg_service_seconds'],
                ]);
            }
            fputcsv($out, []);
            fputcsv($out, ['Reason for visit', 'Tickets']);
            foreach ($topReasons as $row) {
                fputcsv($out, [(string) $row['reason_label'], (int) $row['ticket_count']]);
            }
            fputcsv($out, []);
            fputcsv($out, ['Hour', 'Tickets created']);
            foreach ($hourlyVolume as $row) {
                fputcsv($out, [sprintf('%02d:00', (int) $row['hour']), (int) $row['ticket_count']]);
            }
        });
    }

    /** @param callable(resource):void $writer */

    private function sendCsv(string $filename, callable $writer): void

    {

        header('Content-Type: text/csv; charset=utf-8');

        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $out = fopen('php://output', 'w');

        if ($out === false) {

            return;

        }

        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));

        $writer($out);

        fclose($out);

        exit;

    }

}


