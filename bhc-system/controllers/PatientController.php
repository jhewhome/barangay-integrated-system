<?php

class PatientController extends Controller
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    private function rejectDoctorPortalOnly(): void
    {
        if ((Auth::user()['role'] ?? '') === 'doctor') {
            $this->redirectWithFlash('/doctor', 'info', 'Use My patients to open records assigned to you.');
        }
    }

    private function isAdminUser(): bool
    {
        return (Auth::user()['role'] ?? '') === 'admin';
    }

    private function isEmergencyWalkInRequest(bool $fromPost = false): bool
    {
        if ($this->isAdminUser()) {
            return false;
        }

        if ($fromPost) {
            return (string) ($_POST['emergency_walk_in'] ?? '') === '1';
        }

        return (string) ($_GET['emergency'] ?? '') === '1';
    }

    /** @return array<string,mixed> */
    private function createFormContext(array $extra = []): array
    {
        $fromPost = !empty($extra['_fromPost']);
        unset($extra['_fromPost']);

        return array_merge([
            'isAdmin' => $this->isAdminUser(),
            'emergencyWalkIn' => $this->isEmergencyWalkInRequest($fromPost),
            'gawadResidentsUrl' => GawadIntegration::residentsIndexUrl(),
        ], $extra);
    }

    private function enforceStaffRegistrationAccess(string $gawadResidentId, bool $emergencyWalkIn): bool
    {
        if ($this->isAdminUser() || $gawadResidentId !== '' || $emergencyWalkIn) {
            return true;
        }

        $this->redirectWithFlash(
            '/patients',
            'info',
            'Register new patients from Gawad BIS (Residents → Register at Health Center). '
            . 'For emergency walk-ins only, use Emergency walk-in on the Patient Registry page.'
        );

        return false;
    }

    /** @param array<string,mixed> $validatedData */
    private function applyEmergencyWalkInNotes(array $validatedData, string $reason): array
    {
        $reason = trim($reason);
        $prefix = '[Emergency walk-in] ';
        $existing = trim((string) ($validatedData['notes'] ?? ''));
        $validatedData['notes'] = $existing === ''
            ? $prefix . $reason
            : $existing . "\n\n" . $prefix . $reason;

        return $validatedData;
    }

    public function index(): void
    {
        $this->requireAuth();
        $this->rejectDoctorPortalOnly();

        $data = $this->registryIndexData();

        if (!empty($_GET['partial'])) {
            header('Content-Type: text/html; charset=utf-8');
            $this->partial('patients/partials/registry_results', $data);
            return;
        }

        $this->view('patients/index', $data);
    }

    /** @return array<string,mixed> */
    private function registryIndexData(): array
    {
        $perPage = 20;
        $page = (int) ($_GET['page'] ?? 1);
        if ($page < 1) {
            $page = 1;
        }

        $q = trim((string) ($_GET['q'] ?? ''));
        $residency = trim((string) ($_GET['residency'] ?? ''));
        $registry = strtolower(trim((string) ($_GET['registry'] ?? 'active')));
        $isAdmin = (Auth::user()['role'] ?? '') === 'admin';
        if (!$isAdmin) {
            $registry = 'active';
        } elseif (!in_array($registry, ['active', 'archived', 'all'], true)) {
            $registry = 'active';
        }

        $total = Patient::countAll($this->db, $q, $residency, $registry);
        $totalPages = max(1, (int) ceil($total / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
        }

        $offset = ($page - 1) * $perPage;
        $patients = Patient::paginate($this->db, $perPage, $offset, $q, $residency, $registry);
        $start = $total === 0 ? 0 : (($page - 1) * $perPage + 1);
        $end = min($total, $page * $perPage);
        $patientIds = array_map(static fn (array $p): int => (int) $p['id'], $patients);

        return [
            'patients' => $patients,
            'page' => $page,
            'perPage' => $perPage,
            'total' => $total,
            'totalActivePatients' => Patient::countAll($this->db, '', '', 'active'),
            'totalPages' => $totalPages,
            'q' => $q,
            'residency' => $residency,
            'registry' => $registry,
            'isAdmin' => $isAdmin,
            'start' => $start,
            'end' => $end,
            'activeQueueByPatient' => QueueTicket::activeTicketsMapForPatientsToday($this->db, $patientIds),
        ];
    }

    public function create(): void
    {
        $this->requireAuth();
        $this->rejectDoctorPortalOnly();

        $gawadResidentId = trim((string) ($_GET['gawad_resident_id'] ?? ''));
        $emergencyWalkIn = $this->isEmergencyWalkInRequest(false);
        if (!$this->enforceStaffRegistrationAccess($gawadResidentId, $emergencyWalkIn)) {
            return;
        }

        $prefill = [];
        $gawadImport = null;
        $gawadError = null;

        if ($gawadResidentId !== '') {
            if (!GawadIntegration::isEnabled()) {
                $gawadError = 'Gawad integration is not configured on BHC.';
            } elseif (!GawadIntegration::isValidResidentId($gawadResidentId)) {
                $gawadError = 'Invalid Gawad resident reference.';
            } else {
                $existing = Patient::findByGawadResidentId($this->db, $gawadResidentId);
                if ($existing) {
                    $this->redirectWithFlash(
                        '/patients/' . (int) $existing['id'] . '/history',
                        'info',
                        'This resident is already registered at the Health Center (BHC ID ' . ($existing['bhc_id'] ?? '') . ').'
                    );
                    return;
                }

                $resident = GawadIntegration::fetchResident($gawadResidentId);
                if ($resident === null) {
                    $gawadError = 'Could not load resident data from Gawad BIS. Check that Gawad is running and integration keys match.';
                } else {
                    $prefill = GawadIntegration::mapToPatientPrefill($resident);
                    $gawadImport = [
                        'id' => $gawadResidentId,
                        'name' => GawadIntegration::displayName($resident),
                    ];
                }
            }
        }

        $identityMatch = $this->resolveIdentityMatch($prefill);

        if ($identityMatch !== null && $gawadImport !== null) {
            $linkedGawad = trim((string) ($identityMatch['gawad_resident_id'] ?? ''));
            if ($linkedGawad !== '' && $linkedGawad !== $gawadImport['id']) {
                $gawadError = 'An existing BHC patient matches this resident but is linked to a different Gawad record ('
                    . ($identityMatch['bhc_id'] ?? '')
                    . '). Review the existing record before proceeding.';
            }
        }

        $this->view('patients/create', $this->createFormContext([
            'bhcId' => Patient::nextBhcId($this->db),
            'old' => $prefill,
            'gawadImport' => $gawadImport,
            'gawadError' => $gawadError,
            'identityMatch' => $identityMatch,
            'emergencyWalkIn' => $emergencyWalkIn,
        ]));
    }

    public function linkGawad(int $id): void
    {
        $this->requireAuth();
        $this->rejectDoctorPortalOnly();
        $this->requirePost();

        $gawadResidentId = trim((string) ($_POST['gawad_resident_id'] ?? ''));
        $result = Patient::linkGawadResident($this->db, $id, $gawadResidentId);
        if (!$result['ok']) {
            $this->redirectWithFlash(
                '/patients/create?gawad_resident_id=' . rawurlencode($gawadResidentId),
                'error',
                $result['error'] ?? 'Could not link Gawad resident.'
            );
            return;
        }

        AuditLog::log($this->db, (int) (Auth::user()['id'] ?? 0), 'patient_link_gawad', 'patient', $id, [
            'gawad_resident_id' => $gawadResidentId,
        ]);

        $this->redirectWithFlash(
            '/patients/' . $id . '/history',
            'ok',
            'Gawad BIS resident linked to this patient record. Route them at Registration if they are visiting today.'
        );
    }

    public function edit(int $id): void
    {
        $this->requireAuth();
        $this->rejectDoctorPortalOnly();

        $patient = Patient::find($this->db, $id);
        if (!$patient) {
            http_response_code(404);
            echo 'Patient not found';
            return;
        }

        $this->view('patients/edit', [
            'patient' => $patient,
            'errors' => [],
            'old' => [],
        ]);
    }

    public function history(int $id): void
    {
        $this->requireAuth();
        $this->rejectDoctorPortalOnly();

        $patient = Patient::find($this->db, $id);
        if (!$patient) {
            http_response_code(404);
            echo 'Patient not found';
            return;
        }

        $consultations = ConsultationRecord::forPatient($this->db, $id, 50);
        $medicines = MedicineDispensing::forPatientConsolidated($this->db, $id, 100);
        $doctorComments = DoctorComment::forPatient($this->db, $id, 100);
        $clinicalDocuments = ClinicalDocument::forPatient($this->db, $id, 50);
        $receiptMap = ClinicalDocument::medicineReceiptMapForPatient($this->db, $id);

        $consultationHasMeds = [];
        $pendingReceiptConsultations = [];
        foreach ($medicines as $m) {
            $cid = (int) ($m['consultation_id'] ?? 0);
            if ($cid <= 0) {
                continue;
            }
            $consultationHasMeds[$cid] = true;
            if (isset($receiptMap[$cid])) {
                continue;
            }
            $source = MedicineDispensing::normalizeProcurementSource((string) ($m['procurement_source'] ?? MedicineDispensing::SOURCE_CLINIC));
            $needsReceipt = in_array($source, [MedicineDispensing::SOURCE_LGU, MedicineDispensing::SOURCE_EXTERNAL], true)
                || !empty($m['receipt_issued'])
                || ($m['dispense_status'] ?? '') === 'dispensed';
            if ($needsReceipt) {
                $pendingReceiptConsultations[$cid] = $cid;
            }
        }

        $visits = PatientVisit::forPatient($this->db, $id, 30);
        $visitDetails = [];
        foreach ($visits as $visit) {
            $vid = (int) $visit['id'];
            $visitDetails[$vid] = [
                'tickets' => PatientVisit::ticketsForVisit($this->db, $vid),
                'triage' => TriageRecord::forVisit($this->db, $vid),
            ];
        }

        $this->view('patients/history', [
            'patient' => $patient,
            'visits' => $visits,
            'visitDetails' => $visitDetails,
            'todayConsultation' => ConsultationRecord::forPatientOnDate($this->db, $id, date('Y-m-d')),
            'activeQueueTickets' => QueueTicket::activeTicketsForPatientToday($this->db, $id),
            'tickets' => QueueTicket::recentForPatient($this->db, $id, 50),
            'appointments' => PatientAppointment::forPatient($this->db, $id, 50),
            'nextAppointment' => PatientAppointment::nextForPatient($this->db, $id),
            'consultations' => $consultations,
            'medicines' => $medicines,
            'doctorComments' => $doctorComments,
            'clinicalDocuments' => $clinicalDocuments,
            'medicineCatalog' => MedicineCatalog::pickerList($this->db),
            'receiptMap' => $receiptMap,
            'pendingReceiptConsultations' => array_values($pendingReceiptConsultations),
            'consultationHasMeds' => $consultationHasMeds,
            'stations' => Station::allActive($this->db),
            'errors' => [],
            'apptOld' => [],
        ]);
    }

    public function update(int $id): void
    {
        $this->requireAuth();
        $this->rejectDoctorPortalOnly();
        $this->requirePost();

        $patient = Patient::find($this->db, $id);
        if (!$patient) {
            http_response_code(404);
            echo 'Patient not found';
            return;
        }

        $validated = Patient::validateForm($_POST, false);
        if (!empty($validated['errors'])) {
            $this->view('patients/edit', [
                'patient' => $patient,
                'errors' => $validated['errors'],
                'old' => $validated['data'],
            ]);
            return;
        }

        $userId = (int) (Auth::user()['id'] ?? 0);
        $data = Patient::applyResidencyVerificationMeta($validated['data'], $userId, $patient);
        Patient::update($this->db, $id, $data);

        AuditLog::log($this->db, $userId, 'patient_update', 'patient', $id, [
            'bhc_id' => $patient['bhc_id'],
        ]);

        $this->redirectWithFlash('/patients/' . $id . '/edit', 'ok', 'Patient record updated.');
    }

    public function appointmentToday(int $id): void
    {
        $this->requireAuth();

        $appointmentId = (int) ($_GET['appointment_id'] ?? 0);
        $appt = PatientAppointment::resolveForRouting(
            $this->db,
            $id,
            $appointmentId > 0 ? $appointmentId : null
        );

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($appt ?: null);
    }

    public function queueStatus(int $id): void
    {
        $this->requireAuth();

        if (!Patient::find($this->db, $id)) {
            http_response_code(404);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['tickets' => []]);
            return;
        }

        $tickets = QueueTicket::activeTicketsForPatientToday($this->db, $id);
        $consultationTicketToday = QueueTicket::consultationTicketForPatientToday($this->db, $id);
        $incompleteConsultation = QueueTicket::incompleteConsultationTicketForPatientToday($this->db, $id);
        $consultationToday = ConsultationRecord::forPatientOnDate($this->db, $id, date('Y-m-d'));

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'tickets' => $tickets,
            'consultation_ticket_today' => $consultationTicketToday,
            'incomplete_consultation' => $incompleteConsultation,
            'consultation_today' => $consultationToday,
        ]);
    }

    public function search(): void
    {
        $this->requireAuth();

        $q = (string) ($_GET['q'] ?? '');
        $birthdate = isset($_GET['birthdate']) ? (string) $_GET['birthdate'] : null;
        $contact = isset($_GET['contact']) ? (string) $_GET['contact'] : null;

        $results = Patient::search($this->db, $q, $birthdate, $contact, 8);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($results);
    }

    public function store(): void
    {
        $this->requireAuth();
        $this->rejectDoctorPortalOnly();
        $this->requirePost();

        $validated = Patient::validateForm($_POST, true);
        $gawadResidentId = trim((string) ($_POST['gawad_resident_id'] ?? ''));
        $emergencyWalkIn = $this->isEmergencyWalkInRequest(true);
        $emergencyReason = trim((string) ($_POST['emergency_reason'] ?? ''));

        if (!empty($validated['errors'])) {
            $this->view('patients/create', $this->createFormContext([
                '_fromPost' => true,
                'bhcId' => Patient::nextBhcId($this->db),
                'error' => implode(' ', $validated['errors']),
                'old' => $validated['data'],
                'gawadImport' => $this->gawadImportFromPost(),
                'identityMatch' => $this->resolveIdentityMatch($validated['data']),
                'emergencyWalkIn' => $emergencyWalkIn,
                'emergencyReason' => $emergencyReason,
            ]));
            return;
        }

        if (!$this->isAdminUser()) {
            if ($gawadResidentId === '' && !$emergencyWalkIn) {
                $this->view('patients/create', $this->createFormContext([
                    '_fromPost' => true,
                    'bhcId' => Patient::nextBhcId($this->db),
                    'error' => 'Staff registrations require a Gawad BIS resident link or an approved emergency walk-in.',
                    'old' => $validated['data'],
                    'gawadImport' => $this->gawadImportFromPost(),
                    'identityMatch' => $this->resolveIdentityMatch($validated['data']),
                ]));
                return;
            }
            if ($emergencyWalkIn && strlen($emergencyReason) < 10) {
                $this->view('patients/create', $this->createFormContext([
                    '_fromPost' => true,
                    'bhcId' => Patient::nextBhcId($this->db),
                    'error' => 'Describe the emergency situation (at least 10 characters) before saving a walk-in registration.',
                    'old' => $validated['data'],
                    'identityMatch' => $this->resolveIdentityMatch($validated['data']),
                    'emergencyWalkIn' => true,
                    'emergencyReason' => $emergencyReason,
                ]));
                return;
            }
        }

        $duplicate = Patient::findIdentityDuplicate(
            $this->db,
            (string) $validated['data']['first_name'],
            (string) $validated['data']['last_name'],
            (string) $validated['data']['birthdate']
        );
        if ($duplicate) {
            $gawadImport = $this->gawadImportFromPost();
            $error = Patient::identityDuplicateMessage($duplicate);
            if ($gawadImport !== null) {
                $error .= ' Use “Link to existing patient” instead of saving a new record.';
            }

            $this->view('patients/create', $this->createFormContext([
                '_fromPost' => true,
                'bhcId' => Patient::nextBhcId($this->db),
                'error' => $error,
                'old' => $validated['data'],
                'gawadImport' => $gawadImport,
                'identityMatch' => $duplicate,
                'emergencyWalkIn' => $emergencyWalkIn,
                'emergencyReason' => $emergencyReason,
            ]));
            return;
        }

        if ($gawadResidentId !== '') {
            if (!GawadIntegration::isValidResidentId($gawadResidentId)) {
                $this->view('patients/create', $this->createFormContext([
                    '_fromPost' => true,
                    'bhcId' => Patient::nextBhcId($this->db),
                    'error' => 'Invalid Gawad resident link.',
                    'old' => $validated['data'],
                    'gawadImport' => $this->gawadImportFromPost(),
                    'emergencyWalkIn' => $emergencyWalkIn,
                ]));
                return;
            }
            if (Patient::findByGawadResidentId($this->db, $gawadResidentId)) {
                $this->view('patients/create', $this->createFormContext([
                    '_fromPost' => true,
                    'bhcId' => Patient::nextBhcId($this->db),
                    'error' => 'This Gawad resident is already linked to a BHC patient.',
                    'old' => $validated['data'],
                    'gawadImport' => $this->gawadImportFromPost(),
                    'emergencyWalkIn' => $emergencyWalkIn,
                ]));
                return;
            }
        }

        if ($emergencyWalkIn) {
            $validated['data'] = $this->applyEmergencyWalkInNotes($validated['data'], $emergencyReason);
        }

        $bhcId = Patient::nextBhcId($this->db);
        $userId = (int) (Auth::user()['id'] ?? 0);
        $data = Patient::applyResidencyVerificationMeta($validated['data'], $userId);
        $data = Patient::prepareNewPatientData($data);
        $data['bhc_id'] = $bhcId;
        if ($gawadResidentId !== '') {
            $data['gawad_resident_id'] = $gawadResidentId;
        }

        $id = Patient::create($this->db, $data);

        AuditLog::log($this->db, (int) (Auth::user()['id'] ?? 0), 'patient_create', 'patient', (int) $id, [
            'bhc_id' => $bhcId,
            'residency_status' => $data['residency_status'] ?? Patient::RESIDENCY_PENDING,
            'gawad_resident_id' => $gawadResidentId !== '' ? $gawadResidentId : null,
            'registration_source' => $gawadResidentId !== ''
                ? 'gawad_bis'
                : ($emergencyWalkIn ? 'emergency_walk_in' : 'admin_direct'),
            'emergency_walk_in' => $emergencyWalkIn,
        ]);

        $message = Patient::canReceiveServices($data)
            ? 'Patient registered and Balong Bato residency verified.'
            : 'Patient saved as not verified. Update residency when proof is presented before routing.';
        if ($gawadResidentId !== '') {
            $message = 'Patient registered from Gawad BIS. ' . $message;
        } elseif ($emergencyWalkIn) {
            $message = 'Emergency walk-in registered. Add the patient to Gawad BIS when possible and link the record later.';
        }
        $this->redirectWithFlash('/patients', 'ok', $message);
    }

    /** @param array<string,mixed> $data */
    private function resolveIdentityMatch(array $data): ?array
    {
        $first = trim((string) ($data['first_name'] ?? ''));
        $last = trim((string) ($data['last_name'] ?? ''));
        $birthdate = trim((string) ($data['birthdate'] ?? ''));
        if ($first === '' || $last === '' || $birthdate === '') {
            return null;
        }

        return Patient::findIdentityDuplicate($this->db, $first, $last, $birthdate);
    }

    /** @return array{id: string, name: string}|null */
    private function gawadImportFromPost(): ?array
    {
        $id = trim((string) ($_POST['gawad_resident_id'] ?? ''));
        if ($id === '') {
            return null;
        }

        return [
            'id' => $id,
            'name' => trim((string) ($_POST['gawad_resident_name'] ?? 'Gawad resident')),
        ];
    }

    public function archive(int $id): void
    {
        $this->requireRole('admin');
        $this->requirePost();

        $patient = Patient::find($this->db, $id);
        if (!$patient) {
            http_response_code(404);
            echo 'Patient not found';
            return;
        }

        if (Patient::isArchived($patient)) {
            $this->redirectWithFlash('/patients', 'info', 'Patient is already archived.');
            return;
        }

        $userId = (int) (Auth::user()['id'] ?? 0);
        if (!Patient::archive($this->db, $id, $userId)) {
            $this->redirectWithFlash('/patients', 'error', 'Could not archive patient.');
            return;
        }

        AuditLog::log($this->db, $userId, 'patient_archive', 'patient', $id, [
            'bhc_id' => $patient['bhc_id'],
        ]);

        $this->redirectWithFlash('/patients', 'ok', 'Patient archived. Visit history is retained and can be restored by an admin.');
    }

    public function restore(int $id): void
    {
        $this->requireRole('admin');
        $this->requirePost();

        $patient = Patient::find($this->db, $id);
        if (!$patient) {
            http_response_code(404);
            echo 'Patient not found';
            return;
        }

        if (!Patient::isArchived($patient)) {
            $this->redirectWithFlash('/patients', 'info', 'Patient is not archived.');
            return;
        }

        $userId = (int) (Auth::user()['id'] ?? 0);
        if (!Patient::restore($this->db, $id)) {
            $this->redirectWithFlash('/patients/' . $id . '/history', 'error', 'Could not restore patient.');
            return;
        }

        AuditLog::log($this->db, $userId, 'patient_restore', 'patient', $id, [
            'bhc_id' => $patient['bhc_id'],
        ]);

        $returnTo = trim((string) ($_POST['return_to'] ?? '/patients'));
        if ($returnTo === '' || $returnTo[0] !== '/') {
            $returnTo = '/patients';
        }

        $this->redirectWithFlash($returnTo, 'ok', 'Patient restored to the active registry.');
    }
}
