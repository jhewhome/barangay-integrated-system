<?php

class ConsultationRecord
{
    public static function create(PDO $db, array $data): int
    {
        $stmt = $db->prepare(
            "INSERT INTO consultation_records
             (patient_id, queue_ticket_id, doctor_id, appointment_id, diagnosis, clinical_notes, consultation_date, created_by)
             VALUES
             (:patient_id, :queue_ticket_id, :doctor_id, :appointment_id, :diagnosis, :clinical_notes, :consultation_date, :created_by)"
        );
        $stmt->execute([
            ':patient_id' => (int) $data['patient_id'],
            ':queue_ticket_id' => !empty($data['queue_ticket_id']) ? (int) $data['queue_ticket_id'] : null,
            ':doctor_id' => !empty($data['doctor_id']) ? (int) $data['doctor_id'] : null,
            ':appointment_id' => !empty($data['appointment_id']) ? (int) $data['appointment_id'] : null,
            ':diagnosis' => $data['diagnosis'],
            ':clinical_notes' => $data['clinical_notes'] ?: null,
            ':consultation_date' => $data['consultation_date'] ?? date('Y-m-d'),
            ':created_by' => !empty($data['created_by']) ? (int) $data['created_by'] : null,
        ]);
        return (int) $db->lastInsertId();
    }

    public static function find(PDO $db, int $id): ?array
    {
        $stmt = $db->prepare(
            "SELECT cr.*, p.bhc_id, p.full_name, u.username AS recorded_by_name,
                    du.username AS doctor_username, du.display_name AS doctor_display_name
             FROM consultation_records cr
             JOIN patients p ON p.id = cr.patient_id
             LEFT JOIN users u ON u.id = cr.created_by
             LEFT JOIN users du ON du.id = cr.doctor_id
             WHERE cr.id = :id
             LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function byTicket(PDO $db, int $ticketId): ?array
    {
        $stmt = $db->prepare(
            "SELECT * FROM consultation_records WHERE queue_ticket_id = :ticket_id ORDER BY id DESC LIMIT 1"
        );
        $stmt->execute([':ticket_id' => $ticketId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function forPatientOnDate(PDO $db, int $patientId, string $date): ?array
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return null;
        }

        $stmt = $db->prepare(
            "SELECT * FROM consultation_records
             WHERE patient_id = :patient_id AND consultation_date = :date
             ORDER BY id DESC
             LIMIT 1"
        );
        $stmt->execute([
            ':patient_id' => $patientId,
            ':date' => $date,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function resolveForPatientSave(PDO $db, int $patientId, ?int $ticketId, string $date): ?array
    {
        if ($ticketId) {
            $byTicket = self::byTicket($db, $ticketId);
            if ($byTicket) {
                return $byTicket;
            }
        }

        return self::forPatientOnDate($db, $patientId, $date);
    }

    public static function updateFields(PDO $db, int $id, array $data): void
    {
        $sets = [
            'diagnosis = :diagnosis',
            'clinical_notes = :notes',
        ];
        $params = [
            ':diagnosis' => $data['diagnosis'],
            ':notes' => ($data['clinical_notes'] ?? '') !== '' ? $data['clinical_notes'] : null,
            ':id' => $id,
        ];

        if (array_key_exists('doctor_id', $data)) {
            $sets[] = 'doctor_id = COALESCE(:doctor_id, doctor_id)';
            $params[':doctor_id'] = $data['doctor_id'];
        }
        if (!empty($data['queue_ticket_id'])) {
            $sets[] = 'queue_ticket_id = COALESCE(queue_ticket_id, :queue_ticket_id)';
            $params[':queue_ticket_id'] = (int) $data['queue_ticket_id'];
        }

        $stmt = $db->prepare(
            'UPDATE consultation_records SET ' . implode(', ', $sets) . ' WHERE id = :id'
        );
        $stmt->execute($params);
    }

    public static function forPatient(PDO $db, int $patientId, int $limit = 50): array
    {
        $stmt = $db->prepare(
            "SELECT cr.*, u.username AS recorded_by_name,
                    du.username AS doctor_username, du.display_name AS doctor_display_name,
                    qt.ticket_no, s.name AS station_name,
                    pa.appointment_date AS linked_appointment_date,
                    pa.appointment_time AS linked_appointment_time,
                    pa.purpose AS linked_appointment_purpose,
                    pa.status AS linked_appointment_status
             FROM consultation_records cr
             LEFT JOIN users u ON u.id = cr.created_by
             LEFT JOIN users du ON du.id = cr.doctor_id
             LEFT JOIN queue_tickets qt ON qt.id = cr.queue_ticket_id
             LEFT JOIN stations s ON s.id = qt.station_id
             LEFT JOIN patient_appointments pa ON pa.id = cr.appointment_id
             WHERE cr.patient_id = :patient_id
             ORDER BY cr.consultation_date DESC, cr.id DESC
             LIMIT :limit"
        );
        $stmt->bindValue(':patient_id', $patientId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function findByAppointment(PDO $db, int $appointmentId): ?array
    {
        $stmt = $db->prepare(
            "SELECT * FROM consultation_records WHERE appointment_id = :appointment_id ORDER BY id DESC LIMIT 1"
        );
        $stmt->execute([':appointment_id' => $appointmentId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function attachFollowUpAppointment(PDO $db, int $consultationId, int $appointmentId): bool
    {
        $consultation = self::find($db, $consultationId);
        $appointment = PatientAppointment::find($db, $appointmentId);
        if (!$consultation || !$appointment) {
            return false;
        }
        if ((int) $consultation['patient_id'] !== (int) $appointment['patient_id']) {
            return false;
        }
        if (($appointment['status'] ?? '') !== 'scheduled') {
            return false;
        }
        if (!empty($consultation['appointment_id'])) {
            return (int) $consultation['appointment_id'] === $appointmentId;
        }
        if (($consultation['consultation_date'] ?? '') !== ($appointment['appointment_date'] ?? '')) {
            return false;
        }

        $stmt = $db->prepare(
            "UPDATE consultation_records
             SET appointment_id = :appointment_id
             WHERE id = :id AND appointment_id IS NULL"
        );
        $stmt->execute([
            ':appointment_id' => $appointmentId,
            ':id' => $consultationId,
        ]);
        if ($stmt->rowCount() <= 0) {
            return false;
        }

        PatientAppointment::updateStatus($db, $appointmentId, 'completed');
        return true;
    }

    /** @return array{linked:int, skipped:int} */
    public static function backfillFollowUpLinks(PDO $db, ?int $patientId = null): array
    {
        $sql =
            "SELECT cr.id AS consultation_id, pa.id AS appointment_id
             FROM consultation_records cr
             INNER JOIN patient_appointments pa
               ON pa.patient_id = cr.patient_id
              AND pa.appointment_date = cr.consultation_date
              AND pa.status = 'scheduled'
             WHERE cr.appointment_id IS NULL";
        if ($patientId !== null) {
            $sql .= ' AND cr.patient_id = ' . (int) $patientId;
        }
        $sql .= ' ORDER BY cr.id ASC, pa.id ASC';

        $linked = 0;
        $skipped = 0;
        foreach ($db->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $consultationId = (int) ($row['consultation_id'] ?? 0);
            $appointmentId = (int) ($row['appointment_id'] ?? 0);
            if ($consultationId <= 0 || $appointmentId <= 0) {
                continue;
            }
            if (self::attachFollowUpAppointment($db, $consultationId, $appointmentId)) {
                $linked++;
            } else {
                $skipped++;
            }
        }
        return ['linked' => $linked, 'skipped' => $skipped];
    }

    /** @return array{linked:bool, appointment_id:?int} */
    public static function linkFollowUpFromRequest(
        PDO $db,
        int $consultationId,
        int $patientId,
        string $consultationDate
    ): array {
        $explicitId = (int) ($_POST['appointment_id'] ?? 0);
        $appointmentId = PatientAppointment::resolveScheduledForConsultation(
            $db,
            $patientId,
            $consultationDate,
            $explicitId > 0 ? $explicitId : null
        );
        if (!$appointmentId) {
            return ['linked' => false, 'appointment_id' => null];
        }
        $linked = self::attachFollowUpAppointment($db, $consultationId, $appointmentId);
        return ['linked' => $linked, 'appointment_id' => $appointmentId];
    }

    /** @return array{total:int, unique_patients:int} */
    public static function monthlyTotals(PDO $db, string $month): array
    {
        $b = ReportMonth::bounds($month);
        return self::totalsForRange($db, $b['start'], $b['end']);
    }

    public static function totalsForRange(PDO $db, string $start, string $end): array
    {
        $stmt = $db->prepare(
            "SELECT COUNT(*) AS total, COUNT(DISTINCT patient_id) AS unique_patients
             FROM consultation_records
             WHERE consultation_date >= :start AND consultation_date < :end"
        );
        $stmt->execute([':start' => $start, ':end' => $end]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        return [
            'total' => (int) ($r['total'] ?? 0),
            'unique_patients' => (int) ($r['unique_patients'] ?? 0),
        ];
    }

    public static function monthlyTopDiagnoses(PDO $db, string $month, int $limit = 15): array
    {
        $b = ReportMonth::bounds($month);
        return self::topDiagnosesForRange($db, $b['start'], $b['end'], $limit);
    }

    public static function topDiagnosesForRange(PDO $db, string $start, string $end, int $limit = 15): array
    {
        $stmt = $db->prepare(
            "SELECT diagnosis, COUNT(*) AS case_count
             FROM consultation_records
             WHERE consultation_date >= :start AND consultation_date < :end
             GROUP BY diagnosis
             ORDER BY case_count DESC, diagnosis ASC
             LIMIT :limit"
        );
        $stmt->bindValue(':start', $start);
        $stmt->bindValue(':end', $end);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function monthlyList(PDO $db, string $month, int $limit = 300): array
    {
        $b = ReportMonth::bounds($month);
        return self::listForRange($db, $b['start'], $b['end'], $limit);
    }

    public static function listForRange(PDO $db, string $start, string $end, int $limit = 300): array
    {
        $stmt = $db->prepare(
            "SELECT cr.id, cr.consultation_date, cr.diagnosis, cr.clinical_notes,
                    p.bhc_id, p.full_name,
                    du.display_name AS doctor_display_name, du.username AS doctor_username,
                    u.username AS recorded_by_name
             FROM consultation_records cr
             JOIN patients p ON p.id = cr.patient_id
             LEFT JOIN users du ON du.id = cr.doctor_id
             LEFT JOIN users u ON u.id = cr.created_by
             WHERE cr.consultation_date >= :start AND cr.consultation_date < :end
             ORDER BY cr.consultation_date DESC, cr.id DESC
             LIMIT :limit"
        );
        $stmt->bindValue(':start', $start);
        $stmt->bindValue(':end', $end);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
