<?php

class DoctorController extends Controller
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    public function index(): void
    {
        $this->requireRole('doctor');
        $doctorId = (int) (Auth::user()['id'] ?? 0);
        $state = $this->queueStateForDoctor($doctorId);

        $this->view('home/doctor', [
            'serving' => $state['serving'],
            'waiting' => $state['waiting'],
            'completed' => $state['completed'],
            'doctor' => User::findById($this->db, $doctorId),
            'consultationBusy' => $state['consultationBusy'],
            'stationServing' => $state['stationServing'],
            'canCallNext' => $state['canCallNext'],
            'queueSignature' => $state['signature'],
        ]);
    }

    public function queueSnapshot(): void
    {
        $this->requireRole('doctor');
        $doctorId = (int) (Auth::user()['id'] ?? 0);
        $state = $this->queueStateForDoctor($doctorId);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'signature' => $state['signature'],
            'assigned_count' => $state['assignedCount'],
            'waiting_count' => $state['waitingCount'],
            'serving_count' => $state['servingCount'],
            'can_call_next' => $state['canCallNext'],
            'consultation_busy' => $state['consultationBusy'],
            'waiting' => array_map(static fn (array $t): array => [
                'ticket_no' => (string) ($t['ticket_no'] ?? ''),
                'full_name' => (string) ($t['full_name'] ?? ''),
                'status' => (string) ($t['status'] ?? ''),
            ], $state['waiting']),
            'updated_at' => date('c'),
        ]);
    }

    /** @return array<string,mixed> */
    private function queueStateForDoctor(int $doctorId): array
    {
        $stationId = QueueTicket::CONSULTATION_STATION_ID;
        $assigned = QueueTicket::assignedToDoctor($this->db, $doctorId, true);
        $serving = array_values(array_filter($assigned, fn ($t) => ($t['status'] ?? '') === 'serving'));
        $waiting = array_values(array_filter($assigned, fn ($t) => ($t['status'] ?? '') === 'waiting'));
        $completed = array_values(array_filter($assigned, fn ($t) => in_array($t['status'] ?? '', ['done', 'skipped'], true)));
        $stationServing = QueueTicket::nowServing($this->db, $stationId);
        $consultationBusy = $stationServing
            && (int) ($stationServing['assigned_doctor_id'] ?? 0) !== $doctorId;
        $active = array_merge($serving, $waiting);

        $signatureParts = array_map(
            static fn (array $t): string => (int) ($t['id'] ?? 0) . ':' . (string) ($t['status'] ?? ''),
            $active
        );
        sort($signatureParts);

        return [
            'serving' => $serving,
            'waiting' => $waiting,
            'completed' => $completed,
            'assignedActive' => $active,
            'assignedCount' => count($active),
            'waitingCount' => count($waiting),
            'servingCount' => count($serving),
            'consultationBusy' => $consultationBusy,
            'stationServing' => $stationServing,
            'canCallNext' => empty($serving) && !$consultationBusy && !empty($waiting),
            'signature' => implode('|', $signatureParts),
        ];
    }

    public function callNext(): void
    {
        $this->requireRole('doctor');
        $this->requirePost();

        $doctorId = (int) (Auth::user()['id'] ?? 0);
        $called = QueueTicket::callNextForDoctor($this->db, $doctorId);
        if (!$called) {
            $stationServing = QueueTicket::nowServing($this->db, QueueTicket::CONSULTATION_STATION_ID);
            if ($stationServing && (int) ($stationServing['assigned_doctor_id'] ?? 0) !== $doctorId) {
                $this->redirectWithFlash('/doctor', 'error', 'Consultation is currently serving another doctor\'s patient. Please wait.');
                return;
            }
            $this->redirectWithFlash('/doctor', 'error', 'No patients are waiting for you at Consultation.');
            return;
        }

        AuditLog::log($this->db, $doctorId, 'ticket_call_next', 'queue_ticket', (int) $called['id'], [
            'station_id' => QueueTicket::CONSULTATION_STATION_ID,
            'source' => 'doctor_portal',
        ]);

        $ticketNo = (string) ($called['ticket_no'] ?? '');
        $patientId = (int) ($called['patient_id'] ?? 0);
        $this->redirectWithFlash(
            "/doctor/patients/{$patientId}",
            'ok',
            "Now serving {$ticketNo}. Open the patient record to consult."
        );
    }

    public function callTicket(int $ticketId): void
    {
        $this->requireRole('doctor');
        $this->requirePost();

        $doctorId = (int) (Auth::user()['id'] ?? 0);
        $called = QueueTicket::callTicketForDoctor(
            $this->db,
            $doctorId,
            QueueTicket::CONSULTATION_STATION_ID,
            $ticketId
        );
        if (!$called) {
            $stationServing = QueueTicket::nowServing($this->db, QueueTicket::CONSULTATION_STATION_ID);
            if ($stationServing && (int) ($stationServing['assigned_doctor_id'] ?? 0) !== $doctorId) {
                $this->redirectWithFlash('/doctor', 'error', 'Consultation is currently serving another doctor\'s patient.');
                return;
            }
            $this->redirectWithFlash('/doctor', 'error', 'Could not call that patient. They may no longer be waiting for you.');
            return;
        }

        AuditLog::log($this->db, $doctorId, 'ticket_call', 'queue_ticket', $ticketId, [
            'station_id' => QueueTicket::CONSULTATION_STATION_ID,
            'source' => 'doctor_portal',
        ]);

        $patientId = (int) ($called['patient_id'] ?? 0);
        $this->redirectWithFlash(
            "/doctor/patients/{$patientId}",
            'ok',
            'Patient called. You are now serving this ticket.'
        );
    }

    public function completeTicket(int $ticketId): void
    {
        $this->requireRole('doctor');
        $this->requirePost();

        $doctorId = (int) (Auth::user()['id'] ?? 0);
        $ticket = QueueTicket::findAssignedToDoctor($this->db, $ticketId, $doctorId);
        if (!$ticket) {
            $this->redirectWithFlash('/doctor', 'error', 'Ticket not found or not assigned to you.');
            return;
        }
        if (($ticket['status'] ?? '') !== 'serving') {
            $this->redirectWithFlash('/doctor', 'error', 'Only a patient you are currently serving can be completed.');
            return;
        }

        QueueTicket::complete($this->db, $ticketId);
        AuditLog::log($this->db, $doctorId, 'ticket_complete', 'queue_ticket', $ticketId, [
            'station_id' => (int) ($ticket['station_id'] ?? QueueTicket::CONSULTATION_STATION_ID),
            'source' => 'doctor_portal',
        ]);

        $ticketNo = (string) ($ticket['ticket_no'] ?? '');
        $next = QueueTicket::callNextForDoctor($this->db, $doctorId);
        if ($next) {
            AuditLog::log($this->db, $doctorId, 'ticket_call_next_auto', 'queue_ticket', (int) $next['id'], [
                'station_id' => QueueTicket::CONSULTATION_STATION_ID,
                'source' => 'doctor_portal',
                'auto' => true,
            ]);
            $nextNo = (string) ($next['ticket_no'] ?? '');
            $this->redirectWithFlash(
                '/doctor/patients/' . (int) $next['patient_id'],
                'ok',
                "Consultation complete for {$ticketNo}. Now serving {$nextNo}."
            );
            return;
        }

        $this->redirectWithFlash('/doctor', 'ok', "Consultation complete for {$ticketNo}.");
    }

    public function skipTicket(int $ticketId): void
    {
        $this->requireRole('doctor');
        $this->requirePost();

        $doctorId = (int) (Auth::user()['id'] ?? 0);
        $ticket = QueueTicket::findAssignedToDoctor($this->db, $ticketId, $doctorId);
        if (!$ticket) {
            $this->redirectWithFlash('/doctor', 'error', 'Ticket not found or not assigned to you.');
            return;
        }
        if (($ticket['status'] ?? '') !== 'serving') {
            $this->redirectWithFlash('/doctor', 'error', 'Only a patient you are currently serving can be skipped.');
            return;
        }

        QueueTicket::skip($this->db, $ticketId);
        AuditLog::log($this->db, $doctorId, 'ticket_skip', 'queue_ticket', $ticketId, [
            'station_id' => (int) ($ticket['station_id'] ?? QueueTicket::CONSULTATION_STATION_ID),
            'source' => 'doctor_portal',
        ]);

        $ticketNo = (string) ($ticket['ticket_no'] ?? '');
        $next = QueueTicket::callNextForDoctor($this->db, $doctorId);
        if ($next) {
            AuditLog::log($this->db, $doctorId, 'ticket_call_next_auto', 'queue_ticket', (int) $next['id'], [
                'station_id' => QueueTicket::CONSULTATION_STATION_ID,
                'source' => 'doctor_portal',
                'auto' => true,
            ]);
            $this->redirectWithFlash(
                '/doctor/patients/' . (int) $next['patient_id'],
                'ok',
                "Skipped {$ticketNo}. Now serving " . (string) ($next['ticket_no'] ?? '') . '.'
            );
            return;
        }

        $this->redirectWithFlash('/doctor', 'ok', "Skipped {$ticketNo}.");
    }

    public function patient(int $patientId): void
    {
        $this->requireRole('doctor');
        $doctorId = (int) (Auth::user()['id'] ?? 0);

        if (!PatientAccess::doctorCanView($this->db, $doctorId, $patientId)) {
            $this->redirectWithFlash('/doctor', 'error', 'This patient is not assigned to you.');
            return;
        }

        $patient = Patient::find($this->db, $patientId);
        if (!$patient) {
            http_response_code(404);
            echo 'Patient not found';
            return;
        }

        $activeTicket = QueueTicket::activeTicketForDoctorPatient($this->db, $doctorId, $patientId);
        $todayAppointment = PatientAppointment::scheduledTodayForPatient($this->db, $patientId);
        $todayConsultation = ConsultationRecord::forPatientOnDate($this->db, $patientId, date('Y-m-d'));

        $todayConsultationId = $todayConsultation ? (int) $todayConsultation['id'] : 0;
        $todayCertificate = $todayConsultationId > 0
            ? ClinicalDocument::findByConsultation($this->db, $todayConsultationId, 'medical_certificate')
            : null;
        $todayReferral = $todayConsultationId > 0
            ? ClinicalDocument::findByConsultation($this->db, $todayConsultationId, 'referral')
            : null;
        $todayRecommendation = $todayConsultationId > 0
            ? ClinicalDocument::findByConsultation($this->db, $todayConsultationId, 'recommendation')
            : null;

        $this->view('doctor/patient', [
            'patient' => $patient,
            'activeTicket' => $activeTicket,
            'todayAppointment' => $todayAppointment,
            'todayConsultation' => $todayConsultation,
            'todayCertificate' => $todayCertificate,
            'todayReferral' => $todayReferral,
            'todayRecommendation' => $todayRecommendation,
            'medicineCatalog' => MedicineCatalog::pickerList($this->db),
            'consultations' => ConsultationRecord::forPatient($this->db, $patientId, 50),
            'medicines' => MedicineDispensing::forPatientConsolidated($this->db, $patientId, 100),
            'clinicalDocuments' => ClinicalDocument::forPatient($this->db, $patientId, 50),
            'comments' => DoctorComment::forPatient($this->db, $patientId, 100),
            'tickets' => QueueTicket::recentForPatient($this->db, $patientId, 30),
            'appointments' => PatientAppointment::forPatient($this->db, $patientId, 20),
            'nextAppointment' => PatientAppointment::nextForPatient($this->db, $patientId),
            'errors' => [],
        ]);
    }

    public function storeComment(int $patientId): void
    {
        $this->requireRole('doctor');
        $this->requirePost();

        $doctorId = (int) (Auth::user()['id'] ?? 0);
        if (!PatientAccess::doctorCanView($this->db, $doctorId, $patientId)) {
            $this->redirectWithFlash('/doctor', 'error', 'This patient is not assigned to you.');
            return;
        }

        $comment = trim((string) ($_POST['comment'] ?? ''));
        if ($comment === '') {
            $this->redirectWithFlash("/doctor/patients/{$patientId}", 'error', 'Comment cannot be empty.');
            return;
        }

        $ticketId = (int) ($_POST['queue_ticket_id'] ?? 0);
        $commentId = DoctorComment::create($this->db, [
            'patient_id' => $patientId,
            'doctor_id' => $doctorId,
            'queue_ticket_id' => $ticketId > 0 ? $ticketId : null,
            'comment' => $comment,
        ]);

        AuditLog::log($this->db, $doctorId, 'doctor_comment', 'doctor_comment', $commentId, [
            'patient_id' => $patientId,
        ]);

        $this->redirectWithFlash("/doctor/patients/{$patientId}", 'ok', 'Comment saved.');
    }

    public function storeConsultation(int $patientId): void
    {
        $this->requireRole('doctor');
        $this->requirePost();

        $doctorId = (int) (Auth::user()['id'] ?? 0);
        if (!PatientAccess::doctorCanView($this->db, $doctorId, $patientId)) {
            $this->redirectWithFlash('/doctor', 'error', 'This patient is not assigned to you.');
            return;
        }

        $diagnosis = trim((string) ($_POST['diagnosis'] ?? ''));
        $notes = trim((string) ($_POST['clinical_notes'] ?? ''));
        if ($diagnosis === '') {
            $this->redirectWithFlash("/doctor/patients/{$patientId}", 'error', 'Diagnosis is required.');
            return;
        }

        $ticket = QueueTicket::activeTicketForDoctorPatient($this->db, $doctorId, $patientId);
        $ticketId = $ticket ? (int) $ticket['id'] : null;
        $consultationDate = date('Y-m-d');
        $existing = ConsultationRecord::resolveForPatientSave($this->db, $patientId, $ticketId, $consultationDate);
        $isUpdate = $existing !== null;

        if ($existing) {
            ConsultationRecord::updateFields($this->db, (int) $existing['id'], [
                'diagnosis' => $diagnosis,
                'clinical_notes' => $notes,
                'doctor_id' => $doctorId,
                'queue_ticket_id' => $ticketId,
            ]);
            $consultationId = (int) $existing['id'];
            $consultationDate = (string) ($existing['consultation_date'] ?? $consultationDate);
        } else {
            $consultationId = ConsultationRecord::create($this->db, [
                'patient_id' => $patientId,
                'queue_ticket_id' => $ticketId,
                'doctor_id' => $doctorId,
                'diagnosis' => $diagnosis,
                'clinical_notes' => $notes,
                'consultation_date' => $consultationDate,
                'created_by' => $doctorId,
            ]);
        }

        $lines = MedicineDispensing::parseLinesFromPost($_POST);
        if (!empty($lines)) {
            MedicineDispensing::createBatch($this->db, $patientId, $lines, [
                'consultation_id' => $consultationId,
                'queue_ticket_id' => $ticketId,
                'created_by' => $doctorId,
                'dispense_status' => 'prescribed',
                'replace' => 'prescribed',
            ]);
        }

        AuditLog::log($this->db, $doctorId, 'consultation_record', 'consultation_record', $consultationId, [
            'patient_id' => $patientId,
            'source' => 'doctor_portal',
        ]);

        $followUp = ConsultationRecord::linkFollowUpFromRequest($this->db, $consultationId, $patientId, $consultationDate);
        if ($followUp['linked'] && !empty($followUp['appointment_id'])) {
            AuditLog::log($this->db, $doctorId, 'appointment_completed', 'patient_appointment', (int) $followUp['appointment_id'], [
                'patient_id' => $patientId,
                'consultation_id' => $consultationId,
                'source' => 'doctor_portal',
            ]);
        }

        if (!empty($lines)) {
            $documentId = ClinicalDocument::issueMedicineReceipt($this->db, $consultationId, $doctorId);
            if ($documentId) {
                AuditLog::log($this->db, $doctorId, 'document_issued', 'clinical_document', $documentId, [
                    'patient_id' => $patientId,
                    'consultation_id' => $consultationId,
                    'type' => 'medicine_receipt',
                    'source' => 'doctor_portal',
                ]);

                $message = $isUpdate
                    ? 'Consultation record updated and prescription receipt issued.'
                    : 'Consultation record saved and prescription receipt issued.';
                if ($followUp['linked']) {
                    $message = $isUpdate
                        ? 'Follow-up consultation updated. Appointment completed and prescription receipt issued.'
                        : 'Follow-up consultation saved. Appointment completed and prescription receipt issued.';
                }

                $this->redirectWithFlashAndOpen("/doctor/patients/{$patientId}", "/clinical/documents/{$documentId}", 'ok', $message);
                return;
            }
        }

        $message = $isUpdate ? 'Consultation record updated.' : 'Consultation record saved.';
        if ($followUp['linked']) {
            $message = $isUpdate
                ? 'Follow-up consultation updated. Linked appointment marked completed.'
                : 'Follow-up consultation saved. Linked appointment marked completed.';
        }
        $this->redirectWithFlash("/doctor/patients/{$patientId}", 'ok', $message);
    }

    public function issueMedicalCertificate(int $patientId): void
    {
        $this->requireRole('doctor');
        $this->requirePost();

        $doctorId = (int) (Auth::user()['id'] ?? 0);
        if (!PatientAccess::doctorCanView($this->db, $doctorId, $patientId)) {
            $this->redirectWithFlash('/doctor', 'error', 'This patient is not assigned to you.');
            return;
        }

        $consultation = ConsultationRecord::forPatientOnDate($this->db, $patientId, date('Y-m-d'));
        if (!$consultation) {
            $this->redirectWithFlash("/doctor/patients/{$patientId}", 'error', 'Save today\'s consultation record before issuing a medical certificate.');
            return;
        }

        $consultationId = (int) $consultation['id'];
        $documentId = ClinicalDocument::issueMedicalCertificate($this->db, $consultationId, $doctorId, [
            'purpose' => $_POST['purpose'] ?? '',
            'rest_days' => $_POST['rest_days'] ?? '',
            'remarks' => $_POST['remarks'] ?? '',
        ]);

        if (!$documentId) {
            $this->redirectWithFlash("/doctor/patients/{$patientId}", 'error', 'Could not issue medical certificate. Enter a purpose or check if one was already issued today.');
            return;
        }

        AuditLog::log($this->db, $doctorId, 'document_issued', 'clinical_document', $documentId, [
            'patient_id' => $patientId,
            'consultation_id' => $consultationId,
            'type' => 'medical_certificate',
            'source' => 'doctor_portal',
        ]);

        $this->redirectWithFlashAndOpen("/doctor/patients/{$patientId}", "/clinical/documents/{$documentId}", 'ok', 'Medical certificate opened in a new tab.');
    }

    public function issueReferral(int $patientId): void
    {
        $this->requireRole('doctor');
        $this->requirePost();

        $doctorId = (int) (Auth::user()['id'] ?? 0);
        if (!PatientAccess::doctorCanView($this->db, $doctorId, $patientId)) {
            $this->redirectWithFlash('/doctor', 'error', 'This patient is not assigned to you.');
            return;
        }

        $consultation = ConsultationRecord::forPatientOnDate($this->db, $patientId, date('Y-m-d'));
        if (!$consultation) {
            $this->redirectWithFlash("/doctor/patients/{$patientId}", 'error', 'Save today\'s consultation record before issuing a referral.');
            return;
        }

        $consultationId = (int) $consultation['id'];
        $documentId = ClinicalDocument::issueReferral($this->db, $consultationId, $doctorId, [
            'referred_to' => $_POST['referred_to'] ?? '',
            'reason' => $_POST['reason'] ?? '',
            'clinical_summary' => $_POST['clinical_summary'] ?? '',
        ]);

        if (!$documentId) {
            $this->redirectWithFlash("/doctor/patients/{$patientId}", 'error', 'Could not issue referral. Enter facility and reason, or check if one was already issued today.');
            return;
        }

        AuditLog::log($this->db, $doctorId, 'document_issued', 'clinical_document', $documentId, [
            'patient_id' => $patientId,
            'consultation_id' => $consultationId,
            'type' => 'referral',
            'source' => 'doctor_portal',
        ]);

        $this->redirectWithFlashAndOpen("/doctor/patients/{$patientId}", "/clinical/documents/{$documentId}", 'ok', 'Referral letter opened in a new tab.');
    }

    public function issueRecommendation(int $patientId): void
    {
        $this->requireRole('doctor');
        $this->requirePost();

        $doctorId = (int) (Auth::user()['id'] ?? 0);
        if (!PatientAccess::doctorCanView($this->db, $doctorId, $patientId)) {
            $this->redirectWithFlash('/doctor', 'error', 'This patient is not assigned to you.');
            return;
        }

        $consultation = ConsultationRecord::forPatientOnDate($this->db, $patientId, date('Y-m-d'));
        if (!$consultation) {
            $this->redirectWithFlash("/doctor/patients/{$patientId}", 'error', 'Save today\'s consultation record before issuing a recommendation.');
            return;
        }

        $consultationId = (int) $consultation['id'];
        $documentId = ClinicalDocument::issueRecommendation($this->db, $consultationId, $doctorId, [
            'recommendation_title' => $_POST['recommendation_title'] ?? '',
            'recommendation_text' => $_POST['recommendation_text'] ?? '',
            'follow_up_notes' => $_POST['follow_up_notes'] ?? '',
        ]);

        if (!$documentId) {
            $this->redirectWithFlash("/doctor/patients/{$patientId}", 'error', 'Could not issue recommendation. Enter recommendation details or check if one was already issued today.');
            return;
        }

        AuditLog::log($this->db, $doctorId, 'document_issued', 'clinical_document', $documentId, [
            'patient_id' => $patientId,
            'consultation_id' => $consultationId,
            'type' => 'recommendation',
            'source' => 'doctor_portal',
        ]);

        $this->redirectWithFlashAndOpen("/doctor/patients/{$patientId}", "/clinical/documents/{$documentId}", 'ok', 'Clinical recommendation opened in a new tab.');
    }
}
