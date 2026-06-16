<?php

class QueueController extends Controller
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    public function stations(): void
    {
        $this->requireClinicStaff();
        $stations = Station::allActive($this->db);
        $counts = QueueTicket::stationCountsToday($this->db);
        $this->view('stations/index', [
            'stations' => $stations,
            'counts' => $counts,
            'isAdmin' => (Auth::user()['role'] ?? '') === 'admin',
        ]);
    }

    public function index(int $stationId): void
    {
        $this->requireClinicStaff();
        $station = Station::find($this->db, $stationId);
        if (!$station) {
            http_response_code(404);
            echo "Station not found";
            return;
        }

        $stations = Station::allActive($this->db);
        $nowServing = QueueTicket::nowServing($this->db, $stationId);
        $tickets = QueueTicket::todayTicketsForStation($this->db, $stationId);
        $enqueuedTicket = null;
        if (isset($_GET['enqueued'])) {
            $enqueuedTicket = QueueTicket::find($this->db, (int) $_GET['enqueued']);
            if ($enqueuedTicket && (int) $enqueuedTicket['station_id'] !== $stationId) {
                $enqueuedTicket = null;
            }
        }

        $consultationRecord = null;
        $todayConsultation = null;
        $ticketMedicines = [];
        $triageRecord = null;
        $servingTodayAppointment = null;
        if ($nowServing && !empty($nowServing['id'])) {
            $ticketId = (int) $nowServing['id'];
            $consultationRecord = ConsultationRecord::byTicket($this->db, $ticketId);
            if ((int) $stationId === QueueTicket::consultationStationId()) {
                $todayConsultation = ConsultationRecord::forPatientOnDate(
                    $this->db,
                    (int) $nowServing['patient_id'],
                    date('Y-m-d')
                );
            }
            $ticketMedicines = MedicineDispensing::forTicket($this->db, $ticketId);
            if ((int) $stationId === 2) {
                $triageRecord = TriageRecord::byTicket($this->db, $ticketId);
            }
            if ((int) $stationId === 3) {
                $servingTodayAppointment = PatientAppointment::scheduledTodayForPatient(
                    $this->db,
                    (int) $nowServing['patient_id']
                );
            }
        }

        $doctors = ((int) $stationId === 3) ? User::allDoctors($this->db) : [];

        $prefillPatientId = ((int) $stationId === 1) ? (int) ($_GET['patient_id'] ?? 0) : 0;
        $prefillAppointmentId = ((int) $stationId === 1) ? (int) ($_GET['appointment_id'] ?? 0) : 0;
        $prefillAppointment = null;
        $prefillActiveTickets = [];
        $prefillConsultationTicket = null;
        $todayAppointmentPatientIds = [];
        if ((int) $stationId === 1) {
            $todayAppointmentPatientIds = PatientAppointment::patientIdsScheduledToday($this->db);
            $prefillAppointment = PatientAppointment::resolveForRouting(
                $this->db,
                $prefillPatientId,
                $prefillAppointmentId > 0 ? $prefillAppointmentId : null
            );
            if ($prefillAppointment && $prefillPatientId <= 0) {
                $prefillPatientId = (int) $prefillAppointment['patient_id'];
            }
            if ($prefillPatientId > 0) {
                $prefillActiveTickets = QueueTicket::activeTicketsForPatientToday($this->db, $prefillPatientId);
                $prefillConsultationTicket = QueueTicket::consultationTicketForPatientToday($this->db, $prefillPatientId);
            }
        }

        $this->view('queue/index', [
            'station' => $station,
            'stations' => $stations,
            'nowServing' => $nowServing,
            'tickets' => $tickets,
            'enqueuedTicket' => $enqueuedTicket,
            'consultationRecord' => $consultationRecord,
            'todayConsultation' => $todayConsultation,
            'ticketMedicines' => $ticketMedicines,
            'doctors' => $doctors,
            'prefillPatientId' => $prefillPatientId,
            'prefillAppointmentId' => $prefillAppointmentId,
            'prefillAppointment' => $prefillAppointment,
            'prefillActiveTickets' => $prefillActiveTickets,
            'prefillConsultationTicket' => $prefillConsultationTicket,
            'todayAppointmentPatientIds' => $todayAppointmentPatientIds,
            'servingTodayAppointment' => $servingTodayAppointment,
            'triageRecord' => $triageRecord,
            'medicineCatalog' => MedicineCatalog::pickerList($this->db),
        ]);
    }

    public function storeTriage(int $stationId, int $ticketId): void
    {
        $this->requireClinicStaff();
        $this->requirePost();

        if ($stationId !== 2) {
            $this->redirect("/queue/{$stationId}");
            return;
        }

        $ticket = QueueTicket::find($this->db, $ticketId);
        if (!$ticket || (int) $ticket['station_id'] !== $stationId) {
            $this->redirectWithFlash("/queue/{$stationId}", 'error', 'Ticket not found.');
            return;
        }

        $visitId = (int) ($ticket['visit_id'] ?? 0);
        if ($visitId <= 0) {
            $visitId = PatientVisit::findOrCreateForDate(
                $this->db,
                (int) $ticket['patient_id'],
                date('Y-m-d', strtotime((string) $ticket['created_at'])),
                $ticket['reason'] ?? null
            );
            $this->db->prepare('UPDATE queue_tickets SET visit_id = :visit_id WHERE id = :id AND visit_id IS NULL')
                ->execute([':visit_id' => $visitId, ':id' => $ticketId]);
        }

        $triageId = TriageRecord::saveForTicket($this->db, [
            'visit_id' => $visitId,
            'queue_ticket_id' => $ticketId,
            'patient_id' => (int) $ticket['patient_id'],
            'blood_pressure_systolic' => $_POST['blood_pressure_systolic'] ?? null,
            'blood_pressure_diastolic' => $_POST['blood_pressure_diastolic'] ?? null,
            'temperature' => $_POST['temperature'] ?? null,
            'pulse_rate' => $_POST['pulse_rate'] ?? null,
            'weight_kg' => $_POST['weight_kg'] ?? null,
            'height_cm' => $_POST['height_cm'] ?? null,
            'notes' => $_POST['triage_notes'] ?? null,
            'recorded_by' => (int) (Auth::user()['id'] ?? 0),
        ]);

        AuditLog::log($this->db, (int) (Auth::user()['id'] ?? 0), 'triage_record', 'triage_record', $triageId, [
            'patient_id' => (int) $ticket['patient_id'],
            'visit_id' => $visitId,
            'ticket_id' => $ticketId,
        ]);

        $this->redirectWithFlash("/queue/{$stationId}", 'ok', 'Triage vitals saved.');
    }

    public function enqueueRedirect(int $stationId): void
    {
        $this->requireClinicStaff();
        $this->redirect("/queue/{$stationId}");
    }

    public function enqueue(int $stationId): void
    {
        $this->requireClinicStaff();
        $this->requirePost();

        $patientId = (int) ($_POST['patient_id'] ?? 0);
        if ($patientId <= 0) {
            $this->redirect("/queue/{$stationId}");
        }

        $patient = Patient::find($this->db, $patientId);
        if (!$patient) {
            $this->redirect("/queue/{$stationId}");
        }

        if (!Patient::canReceiveServices($patient)) {
            $returnPath = '/queue/' . $stationId;
            if ($stationId === 1) {
                $returnPath .= '?patient_id=' . $patientId;
            }
            if (Patient::isArchived($patient)) {
                $message = 'This patient is archived. Restore the record in the patient registry before routing.';
            } elseif (Patient::normalizeResidencyStatus((string) ($patient['residency_status'] ?? '')) === Patient::RESIDENCY_NON_RESIDENT) {
                $message = 'This patient is marked as not verified for Balong Bato residency and cannot be routed for regular BHC services.';
            } else {
                $message = 'This patient requires Balong Bato residency verification before routing. Complete verification when registering or editing the patient record.';
            }
            $this->redirectWithFlash($returnPath, 'error', $message);
            return;
        }

        $reason = trim((string) ($_POST['reason'] ?? ''));
        $targetStationId = (int) ($_POST['target_station_id'] ?? 0);

        // Registration can route directly to another station
        $effectiveStationId = $stationId;
        if ($stationId === 1 && $targetStationId > 0) {
            $target = Station::find($this->db, $targetStationId);
            if ($target && (int) $target['is_active'] === 1) {
                $effectiveStationId = $targetStationId;
            }
        }

        if ($effectiveStationId === QueueTicket::consultationStationId()) {
            $consultTicket = QueueTicket::consultationTicketForPatientToday($this->db, $patientId);
            if ($consultTicket) {
                $returnPath = '/queue/' . $stationId;
                if ($stationId === 1) {
                    $returnPath .= '?patient_id=' . $patientId;
                }
                $this->redirectWithFlash(
                    $returnPath,
                    'error',
                    'This patient already has a consultation ticket today: '
                    . ($consultTicket['ticket_no'] ?? 'Ticket')
                    . ' ('
                    . strtoupper((string) ($consultTicket['status'] ?? 'active'))
                    . '). Only one consultation ticket per patient per day is allowed.'
                );
                return;
            }
        }

        $activeTickets = QueueTicket::activeTicketsForPatientToday($this->db, $patientId);
        if (!empty($activeTickets)) {
            $labels = array_map(
                static fn (array $t): string => ($t['ticket_no'] ?? 'Ticket')
                    . ' at '
                    . ($t['station_name'] ?? 'station')
                    . ' ('
                    . strtoupper((string) ($t['status'] ?? 'active'))
                    . ')',
                $activeTickets
            );
            $returnPath = '/queue/' . $stationId;
            if ($stationId === 1) {
                $returnPath .= '?patient_id=' . $patientId;
            }
            $this->redirectWithFlash(
                $returnPath,
                'error',
                'This patient already has an active queue ticket: '
                . implode('; ', $labels)
                . '. Complete or skip the current ticket before routing again.'
            );
            return;
        }

        $ticketId = QueueTicket::enqueueWithReason($this->db, $effectiveStationId, $patientId, $reason);
        AuditLog::log($this->db, (int) (Auth::user()['id'] ?? 0), 'ticket_create', 'queue_ticket', (int) $ticketId, [
            'station_id' => $effectiveStationId,
            'patient_id' => $patientId,
            'reason' => $reason,
        ]);
        $this->redirect("/ticket/{$ticketId}");
    }

    public function ticket(int $ticketId): void
    {
        $ticket = QueueTicket::find($this->db, $ticketId);
        if (!$ticket) {
            http_response_code(404);
            echo "Ticket not found";
            return;
        }
        $station = Station::find($this->db, (int) $ticket['station_id']);
        $waiting = QueueTicket::waitingList($this->db, (int) $ticket['station_id'], 5);
        $nowServing = QueueTicket::nowServing($this->db, (int) $ticket['station_id']);

        $this->view('queue/ticket', [
            'ticket' => $ticket,
            'station' => $station,
            'nowServing' => $nowServing,
            'waiting' => $waiting,
        ]);
    }

    public function ticketQr(int $ticketId): void
    {
        $ticket = QueueTicket::find($this->db, $ticketId);
        if (!$ticket) {
            http_response_code(404);
            echo "Ticket not found";
            return;
        }
        $station = Station::find($this->db, (int) $ticket['station_id']);
        $this->view('queue/ticket_qr', [
            'ticket' => $ticket,
            'station' => $station,
        ]);
    }

    public function display(int $stationId): void
    {
        $station = Station::find($this->db, $stationId);
        if (!$station) {
            http_response_code(404);
            echo "Station not found";
            return;
        }
        $nowServing = QueueTicket::nowServing($this->db, $stationId);
        $waiting = QueueTicket::waitingList($this->db, $stationId, 8);
        $this->view('queue/display', [
            'station' => $station,
            'nowServing' => $nowServing,
            'waiting' => $waiting,
        ]);
    }

    public function callNext(int $stationId): void
    {
        $this->requireClinicStaff();
        $this->requirePost();
        if ($stationId === 1) {
            $this->redirect("/queue/{$stationId}");
        }
        $called = QueueTicket::callNext($this->db, $stationId);
        if ($called) {
            AuditLog::log($this->db, (int) (Auth::user()['id'] ?? 0), 'ticket_call_next', 'queue_ticket', (int) $called['id'], [
                'station_id' => $stationId,
            ]);
        }
        $this->redirect("/queue/{$stationId}");
    }

    public function callTicket(int $stationId, int $ticketId): void
    {
        $this->requireClinicStaff();
        $this->requirePost();
        if ($stationId === 1) {
            $this->redirect("/queue/{$stationId}");
        }
        $called = QueueTicket::callTicket($this->db, $stationId, $ticketId);
        if ($called) {
            AuditLog::log($this->db, (int) (Auth::user()['id'] ?? 0), 'ticket_call', 'queue_ticket', (int) $ticketId, [
                'station_id' => $stationId,
            ]);
        }
        $this->redirect("/queue/{$stationId}");
    }

    public function complete(int $stationId, int $ticketId): void
    {
        $this->requireClinicStaff();
        $this->requirePost();
        if ($stationId === 1) {
            $this->redirect("/queue/{$stationId}");
        }
        QueueTicket::complete($this->db, $ticketId);
        AuditLog::log($this->db, (int) (Auth::user()['id'] ?? 0), 'ticket_complete', 'queue_ticket', (int) $ticketId, [
            'station_id' => $stationId,
        ]);
        $this->autoCallNextIfIdle($stationId);
        $this->redirect("/queue/{$stationId}");
    }

    public function skip(int $stationId, int $ticketId): void
    {
        $this->requireClinicStaff();
        $this->requirePost();
        if ($stationId === 1) {
            $this->redirect("/queue/{$stationId}");
        }
        QueueTicket::skip($this->db, $ticketId);
        AuditLog::log($this->db, (int) (Auth::user()['id'] ?? 0), 'ticket_skip', 'queue_ticket', (int) $ticketId, [
            'station_id' => $stationId,
        ]);
        $this->autoCallNextIfIdle($stationId);
        $this->redirect("/queue/{$stationId}");
    }

    public function recall(int $stationId, int $ticketId): void
    {
        $this->requireClinicStaff();
        $this->requirePost();
        if ($stationId === 1) {
            $this->redirect("/queue/{$stationId}");
        }

        $recalled = QueueTicket::recallToQueue($this->db, $stationId, $ticketId);
        if (!$recalled) {
            $this->redirectWithFlash(
                "/queue/{$stationId}",
                'error',
                'Could not recall this ticket. Only today\'s SKIPPED tickets at this station can be returned to the queue.'
            );
        }

        $ticket = QueueTicket::find($this->db, $ticketId);
        $ticketNo = (string) ($ticket['ticket_no'] ?? (string) $ticketId);
        AuditLog::log($this->db, (int) (Auth::user()['id'] ?? 0), 'ticket_recall', 'queue_ticket', $ticketId, [
            'station_id' => $stationId,
            'ticket_no' => $ticketNo,
        ]);

        $this->redirectWithFlash(
            "/queue/{$stationId}",
            'ok',
            "Ticket {$ticketNo} was returned to the waiting queue. Call the patient when ready."
        );
    }

    public function assignDoctor(int $stationId, int $ticketId): void
    {
        $this->requireClinicStaff();
        $this->requirePost();

        if ($stationId !== 3) {
            $this->redirect("/queue/{$stationId}");
            return;
        }

        $ticket = QueueTicket::find($this->db, $ticketId);
        if (!$ticket || (int) $ticket['station_id'] !== $stationId) {
            $this->redirectWithFlash("/queue/{$stationId}", 'error', 'Ticket not found.');
            return;
        }

        $doctorId = (int) ($_POST['doctor_id'] ?? 0);
        if ($doctorId > 0) {
            $doctor = User::findById($this->db, $doctorId);
            if (!$doctor || ($doctor['role'] ?? '') !== 'doctor' || (int) ($doctor['is_active'] ?? 0) !== 1) {
                $this->redirectWithFlash("/queue/{$stationId}", 'error', 'Invalid doctor selected.');
                return;
            }
        }

        QueueTicket::assignDoctor($this->db, $ticketId, $doctorId > 0 ? $doctorId : null);

        AuditLog::log($this->db, (int) (Auth::user()['id'] ?? 0), 'ticket_assign_doctor', 'queue_ticket', $ticketId, [
            'doctor_id' => $doctorId > 0 ? $doctorId : null,
            'patient_id' => (int) $ticket['patient_id'],
        ]);

        $this->redirectWithFlash("/queue/{$stationId}", 'ok', $doctorId > 0 ? 'Doctor assigned to patient.' : 'Doctor assignment cleared.');
    }

    /**
     * After complete/skip, call the next waiting ticket if the station is idle.
     */
    private function autoCallNextIfIdle(int $stationId): void
    {
        if ($stationId === 1) {
            return;
        }
        $called = QueueTicket::callNext($this->db, $stationId);
        if ($called) {
            AuditLog::log($this->db, (int) (Auth::user()['id'] ?? 0), 'ticket_call_next_auto', 'queue_ticket', (int) $called['id'], [
                'station_id' => $stationId,
                'auto' => true,
            ]);
        }
    }
}

