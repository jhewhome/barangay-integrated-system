<?php



class ClinicalController extends Controller

{

    private PDO $db;



    public function __construct()

    {

        $this->db = (new Database())->getConnection();

    }



    public function storeConsultation(int $stationId, int $ticketId): void

    {

        $this->requireClinicStaff();

        $this->requirePost();



        $ticket = QueueTicket::find($this->db, $ticketId);

        if (!$ticket || (int) $ticket['station_id'] !== $stationId) {

            http_response_code(404);

            echo 'Ticket not found';

            return;

        }



        $diagnosis = trim((string) ($_POST['diagnosis'] ?? ''));

        $notes = trim((string) ($_POST['clinical_notes'] ?? ''));

        if ($diagnosis === '') {

            $this->redirectWithFlash("/queue/{$stationId}", 'error', 'Diagnosis is required.');

            return;

        }



        $patientId = (int) $ticket['patient_id'];

        $doctorId = !empty($ticket['assigned_doctor_id']) ? (int) $ticket['assigned_doctor_id'] : null;

        $consultationDate = date('Y-m-d');

        $existing = ConsultationRecord::resolveForPatientSave($this->db, $patientId, $ticketId, $consultationDate);

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

                'created_by' => (int) (Auth::user()['id'] ?? 0),

            ]);

        }



        $lines = MedicineDispensing::parseLinesFromPost($_POST);

        if (!empty($lines)) {

            MedicineDispensing::createBatch($this->db, $patientId, $lines, [

                'consultation_id' => $consultationId,

                'queue_ticket_id' => $ticketId,

                'created_by' => (int) (Auth::user()['id'] ?? 0),

                'dispense_status' => 'prescribed',

                'replace' => 'prescribed',

            ]);

        }



        $userId = (int) (Auth::user()['id'] ?? 0);

        AuditLog::log($this->db, $userId, 'consultation_record', 'consultation_record', $consultationId, [

            'patient_id' => $patientId,

            'ticket_id' => $ticketId,

        ]);



        $followUp = $this->linkFollowUpAppointment($consultationId, $patientId, $consultationDate, $userId);

        if (MedicineDispensing::consultationShouldHaveReceipt($this->db, $consultationId, false)) {
            $documentId = ClinicalDocument::issueMedicineReceipt($this->db, $consultationId, $userId);
            if ($documentId) {
                AuditLog::log($this->db, $userId, 'document_issued', 'clinical_document', $documentId, [
                    'patient_id' => $patientId,
                    'consultation_id' => $consultationId,
                    'type' => 'medicine_receipt',
                ]);
                $msg = 'Consultation saved and prescription receipt issued.';
                if ($followUp['linked']) {
                    $msg = 'Follow-up consultation saved. Appointment completed and prescription receipt issued.';
                }
                $this->redirectWithFlashAndOpen("/queue/{$stationId}", "/clinical/documents/{$documentId}", 'ok', $msg);
                return;
            }
        }

        $message = $existing ? 'Consultation record updated.' : 'Consultation record saved.';

        if ($followUp['linked']) {

            $message = $existing
                ? 'Follow-up consultation updated. Linked appointment marked completed.'
                : 'Follow-up consultation saved. Linked appointment marked completed.';

        }



        $this->redirectWithFlash("/queue/{$stationId}", 'ok', $message);

    }



    public function storeDispensing(int $stationId, int $ticketId): void

    {

        $this->requireClinicStaff();

        $this->requirePost();



        $ticket = QueueTicket::find($this->db, $ticketId);

        if (!$ticket || (int) $ticket['station_id'] !== $stationId) {

            http_response_code(404);

            echo 'Ticket not found';

            return;

        }



        $lines = MedicineDispensing::parseLinesFromPost($_POST);

        if (empty($lines) || !array_filter($lines, fn ($l) => trim((string) ($l['medicine_name'] ?? '')) !== '')) {

            $this->redirectWithFlash("/queue/{$stationId}", 'error', 'Add at least one medicine.');

            return;

        }



        $patientId = (int) $ticket['patient_id'];

        $consultation = ConsultationRecord::byTicket($this->db, $ticketId);

        $consultationId = $consultation ? (int) $consultation['id'] : null;



        if (!$consultationId) {

            $recent = ConsultationRecord::forPatient($this->db, $patientId, 1);

            $consultationId = !empty($recent[0]['id']) ? (int) $recent[0]['id'] : null;

        }



        try {
            $count = MedicineDispensing::createBatch($this->db, $patientId, $lines, [
                'consultation_id' => $consultationId,
                'queue_ticket_id' => $ticketId,
                'created_by' => (int) (Auth::user()['id'] ?? 0),
                'dispense_status' => 'dispensed',
                'replace' => 'dispensed',
            ]);
        } catch (Throwable $e) {
            $this->redirectWithFlash("/queue/{$stationId}", 'error', 'Could not record medicines: ' . $e->getMessage());
            return;
        }



        AuditLog::log($this->db, (int) (Auth::user()['id'] ?? 0), 'medicine_dispense', 'queue_ticket', $ticketId, [

            'patient_id' => $patientId,

            'items' => $count,

        ]);



        if ($consultationId && MedicineDispensing::consultationShouldHaveReceipt($this->db, $consultationId, false)) {

            $userId = (int) (Auth::user()['id'] ?? 0);

            $documentId = ClinicalDocument::issueMedicineReceipt($this->db, $consultationId, $userId);

            if ($documentId) {

                AuditLog::log($this->db, $userId, 'document_issued', 'clinical_document', $documentId, [

                    'patient_id' => $patientId,

                    'consultation_id' => $consultationId,

                    'type' => 'medicine_receipt',

                ]);

                $this->redirectWithFlashAndOpen("/queue/{$stationId}", "/clinical/documents/{$documentId}", 'ok', 'Medicines recorded. Receipt opened in a new tab.');

                return;

            }

            $this->redirectWithFlash("/clinical/receipt/{$consultationId}", 'ok', 'Medicines recorded. Receipt ready to print.');

            return;

        }



        $this->redirectWithFlash("/queue/{$stationId}", 'ok', 'Medicines dispensed and recorded.');

    }



    public function storeConsultationForPatient(int $patientId): void

    {

        $this->requireAuth();

        $this->requirePost();



        $patient = Patient::find($this->db, $patientId);

        if (!$patient) {

            http_response_code(404);

            echo 'Patient not found';

            return;

        }



        $diagnosis = trim((string) ($_POST['diagnosis'] ?? ''));

        $notes = trim((string) ($_POST['clinical_notes'] ?? ''));

        $date = trim((string) ($_POST['consultation_date'] ?? date('Y-m-d')));



        if ($diagnosis === '') {

            $this->redirectWithFlash("/patients/{$patientId}/history#section-add-clinical", 'error', 'Diagnosis is required.');

            return;

        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {

            $date = date('Y-m-d');

        }



        $existing = ConsultationRecord::forPatientOnDate($this->db, $patientId, $date);
        $isUpdate = $existing !== null;

        if ($existing) {
            ConsultationRecord::updateFields($this->db, (int) $existing['id'], [
                'diagnosis' => $diagnosis,
                'clinical_notes' => $notes,
            ]);
            $consultationId = (int) $existing['id'];
        } else {
            $consultationId = ConsultationRecord::create($this->db, [
                'patient_id' => $patientId,
                'queue_ticket_id' => null,
                'diagnosis' => $diagnosis,
                'clinical_notes' => $notes,
                'consultation_date' => $date,
                'created_by' => (int) (Auth::user()['id'] ?? 0),
            ]);
        }



        $lines = MedicineDispensing::parseLinesFromPost($_POST);

        $isDispensed = ($_POST['record_type'] ?? '') === 'dispensed';

        if (!empty($lines)) {
            try {
                MedicineDispensing::createBatch($this->db, $patientId, $lines, [
                    'consultation_id' => $consultationId,
                    'created_by' => (int) (Auth::user()['id'] ?? 0),
                    'dispense_status' => $isDispensed ? 'dispensed' : 'prescribed',
                    'replace' => $isDispensed ? 'dispensed' : 'prescribed',
                ]);
            } catch (Throwable $e) {
                $this->redirectWithFlash("/patients/{$patientId}/history#section-add-clinical", 'error', 'Could not save medicines: ' . $e->getMessage());
                return;
            }
        }



        $userId = (int) (Auth::user()['id'] ?? 0);

        AuditLog::log($this->db, $userId, 'consultation_record', 'consultation_record', $consultationId, [

            'patient_id' => $patientId,

            'source' => 'patient_history',

        ]);



        $followUp = $this->linkFollowUpAppointment($consultationId, $patientId, $date, $userId);



        if (MedicineDispensing::consultationShouldHaveReceipt($this->db, $consultationId, $isDispensed)) {

            $documentId = ClinicalDocument::issueMedicineReceipt($this->db, $consultationId, $userId);

            if ($documentId) {

                AuditLog::log($this->db, $userId, 'document_issued', 'clinical_document', $documentId, [

                    'patient_id' => $patientId,

                    'consultation_id' => $consultationId,

                    'type' => 'medicine_receipt',

                ]);

                $msg = 'Clinical record and medicine receipt saved.';

                if ($followUp['linked']) {

                    $msg = 'Follow-up consultation saved. Appointment marked completed and receipt issued.';

                }

                $this->redirectWithFlashAndOpen("/patients/{$patientId}/history#section-add-clinical", "/clinical/documents/{$documentId}", 'ok', $msg);

                return;

            }

            $this->redirectWithFlash(
                "/patients/{$patientId}/history#section-add-clinical",
                'error',
                'Medicines saved but the prescription receipt could not be created. Open Medicine history and click Issue receipt.'
            );
            return;

        }



        $message = $isUpdate ? 'Clinical record updated.' : 'Clinical record saved.';

        if ($followUp['linked']) {

            $message = $isUpdate
                ? 'Follow-up consultation updated and linked appointment marked completed.'
                : 'Follow-up consultation saved and linked appointment marked completed.';

        }

        $this->redirectWithFlash("/patients/{$patientId}/history#section-add-clinical", 'ok', $message);

    }



    public function issueReceipt(int $consultationId): void

    {

        $this->requireAuth();

        $this->requirePost();



        $consultation = ConsultationRecord::find($this->db, $consultationId);

        if (!$consultation) {

            http_response_code(404);

            echo 'Consultation not found';

            return;

        }



        $userId = (int) (Auth::user()['id'] ?? 0);

        $documentId = ClinicalDocument::issueMedicineReceipt($this->db, $consultationId, $userId);

        if (!$documentId) {

            $this->redirectWithFlash(

                '/patients/' . (int) $consultation['patient_id'] . '/history#section-receipts',

                'error',

                'No medicines on file for this visit — add medicines before issuing a receipt.'

            );

            return;

        }



        AuditLog::log($this->db, $userId, 'document_issued', 'clinical_document', $documentId, [

            'patient_id' => (int) $consultation['patient_id'],

            'consultation_id' => $consultationId,

            'type' => 'medicine_receipt',

        ]);



        $this->redirectWithFlashAndOpen(
            '/patients/' . (int) $consultation['patient_id'] . '/history#section-receipts',
            "/clinical/documents/{$documentId}",
            'ok',
            'Medicine receipt issued and opened in a new tab.'
        );

    }



    public function document(int $documentId, bool $autoPrint = false): void

    {

        $this->requireAuth();



        $document = ClinicalDocument::find($this->db, $documentId);

        if (!$document || ($document['status'] ?? '') !== 'issued') {

            http_response_code(404);

            echo 'Document not found';

            return;

        }



        $autoPrint = $autoPrint || isset($_GET['print']);
        $documentType = (string) ($document['document_type'] ?? '');

        if ($documentType === 'medicine_receipt') {
            $liveSnapshot = null;
            $consultationId = (int) ($document['consultation_id'] ?? 0);
            if ($consultationId > 0) {
                $liveSnapshot = ClinicalDocument::buildMedicineReceiptSnapshot($this->db, $consultationId);
            }
            $this->viewReceipt('clinical/receipt', [
                'document' => $document,
                'documentPageTitle' => 'Medicine receipt',
                'autoPrint' => $autoPrint,
                'liveSnapshot' => $liveSnapshot,
            ]);
            return;
        }

        if ($documentType === 'medical_certificate') {
            $this->viewReceipt('clinical/certificate', [
                'document' => $document,
                'documentPageTitle' => 'Medical certificate',
                'autoPrint' => $autoPrint,
            ]);
            return;
        }

        if ($documentType === 'referral') {
            $this->viewReceipt('clinical/referral', [
                'document' => $document,
                'documentPageTitle' => 'Referral letter',
                'autoPrint' => $autoPrint,
            ]);
            return;
        }

        if ($documentType === 'recommendation') {
            $this->viewReceipt('clinical/recommendation', [
                'document' => $document,
                'documentPageTitle' => 'Clinical recommendation',
                'autoPrint' => $autoPrint,
            ]);
            return;
        }

        http_response_code(404);
        echo 'Document type not supported yet';

    }



    public function receipt(int $consultationId): void

    {

        $this->requireAuth();



        $consultation = ConsultationRecord::find($this->db, $consultationId);

        if (!$consultation) {

            http_response_code(404);

            echo 'Record not found';

            return;

        }



        $existing = ClinicalDocument::findByConsultation($this->db, $consultationId);

        if ($existing) {

            $this->document((int) $existing['id']);

            return;

        }



        $medicines = MedicineDispensing::forConsultation($this->db, $consultationId);

        $dispensed = array_values(array_filter($medicines, fn ($m) => ($m['dispense_status'] ?? '') === 'dispensed'));



        $this->viewReceipt('clinical/receipt', [

            'consultation' => $consultation,

            'medicines' => !empty($dispensed) ? $dispensed : $medicines,

            'autoPrint' => isset($_GET['print']),

        ]);

    }



    /** @return array{linked:bool, appointment_id:?int} */

    private function linkFollowUpAppointment(int $consultationId, int $patientId, string $consultationDate, int $userId): array

    {

        $result = ConsultationRecord::linkFollowUpFromRequest($this->db, $consultationId, $patientId, $consultationDate);

        if ($result['linked'] && !empty($result['appointment_id'])) {

            AuditLog::log($this->db, $userId, 'appointment_completed', 'patient_appointment', (int) $result['appointment_id'], [

                'patient_id' => $patientId,

                'consultation_id' => $consultationId,

                'source' => 'follow_up_consultation',

            ]);

        }

        return $result;

    }

}


