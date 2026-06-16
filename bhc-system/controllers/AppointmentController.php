<?php

class AppointmentController extends Controller
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    public function index(): void
    {
        $this->requireAuth();

        $from = trim((string) ($_GET['from'] ?? date('Y-m-d')));
        $to = trim((string) ($_GET['to'] ?? date('Y-m-d', strtotime('+30 days'))));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
            $from = date('Y-m-d');
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
            $to = date('Y-m-d', strtotime('+30 days'));
        }

        $appointments = PatientAppointment::upcoming($this->db, $from, $to, 200);
        $patientIds = array_map(static fn (array $a): int => (int) $a['patient_id'], $appointments);

        $this->view('appointments/index', [
            'appointments' => $appointments,
            'from' => $from,
            'to' => $to,
            'activeQueueByPatient' => QueueTicket::activeTicketsMapForPatientsToday($this->db, $patientIds),
        ]);
    }

    public function storeForPatient(int $patientId): void
    {
        $this->requireAuth();
        $this->requirePost();

        $patient = Patient::find($this->db, $patientId);
        if (!$patient) {
            http_response_code(404);
            echo 'Patient not found';
            return;
        }

        $date = trim((string) ($_POST['appointment_date'] ?? ''));
        $time = trim((string) ($_POST['appointment_time'] ?? ''));
        $purpose = trim((string) ($_POST['purpose'] ?? ''));
        $stationId = (int) ($_POST['station_id'] ?? 0);
        $notes = trim((string) ($_POST['appointment_notes'] ?? ''));

        $errors = [];
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $errors[] = 'Appointment date is required.';
        } elseif ($date < date('Y-m-d')) {
            $errors[] = 'Appointment date cannot be in the past.';
        }
        if ($time !== '' && !preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $time)) {
            $errors[] = 'Invalid appointment time.';
        }

        if (!empty($errors)) {
            $this->view('patients/history', $this->historyViewData($patientId, $errors, $_POST));
            return;
        }

        $apptId = PatientAppointment::create($this->db, [
            'patient_id' => $patientId,
            'appointment_date' => $date,
            'appointment_time' => $time !== '' ? substr($time, 0, 5) . ':00' : null,
            'purpose' => $purpose,
            'station_id' => $stationId > 0 ? $stationId : null,
            'notes' => $notes,
            'created_by' => (int) (Auth::user()['id'] ?? 0),
        ]);

        AuditLog::log($this->db, (int) (Auth::user()['id'] ?? 0), 'appointment_create', 'patient_appointment', $apptId, [
            'patient_id' => $patientId,
            'appointment_date' => $date,
        ]);

        $this->redirectWithFlash('/patients/' . $patientId . '/history', 'ok', 'Next appointment scheduled.');
    }

    public function updateStatus(int $id): void
    {
        $this->requireAuth();
        $this->requirePost();

        $appt = PatientAppointment::find($this->db, $id);
        if (!$appt) {
            http_response_code(404);
            echo 'Appointment not found';
            return;
        }

        $status = trim((string) ($_POST['status'] ?? ''));
        if (!PatientAppointment::updateStatus($this->db, $id, $status)) {
            $this->redirectWithFlash('/patients/' . (int) $appt['patient_id'] . '/history', 'error', 'Could not update appointment status.');
            return;
        }

        AuditLog::log($this->db, (int) (Auth::user()['id'] ?? 0), 'appointment_' . $status, 'patient_appointment', $id, [
            'patient_id' => (int) $appt['patient_id'],
        ]);

        $returnTo = trim((string) ($_POST['return_to'] ?? ''));
        if ($returnTo === 'appointments') {
            $this->redirectWithFlash('/appointments', 'ok', 'Appointment updated.');
            return;
        }

        $this->redirectWithFlash('/patients/' . (int) $appt['patient_id'] . '/history', 'ok', 'Appointment updated.');
    }

    /** @return array<string,mixed> */
    private function historyViewData(int $patientId, array $errors = [], array $apptOld = []): array
    {
        $patient = Patient::find($this->db, $patientId);
        return [
            'patient' => $patient,
            'tickets' => QueueTicket::recentForPatient($this->db, $patientId, 50),
            'appointments' => PatientAppointment::forPatient($this->db, $patientId, 50),
            'nextAppointment' => PatientAppointment::nextForPatient($this->db, $patientId),
            'consultations' => ConsultationRecord::forPatient($this->db, $patientId, 50),
            'medicines' => MedicineDispensing::forPatient($this->db, $patientId, 100),
            'doctorComments' => DoctorComment::forPatient($this->db, $patientId, 100),
            'stations' => Station::allActive($this->db),
            'errors' => $errors,
            'apptOld' => $apptOld,
        ];
    }
}
