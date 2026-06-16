<?php

class PatientAppointment
{
    public static function create(PDO $db, array $data): int
    {
        $stmt = $db->prepare(
            "INSERT INTO patient_appointments
             (patient_id, appointment_date, appointment_time, purpose, station_id, status, notes, created_by)
             VALUES
             (:patient_id, :appointment_date, :appointment_time, :purpose, :station_id, 'scheduled', :notes, :created_by)"
        );
        $stmt->execute([
            ':patient_id' => (int) $data['patient_id'],
            ':appointment_date' => $data['appointment_date'],
            ':appointment_time' => $data['appointment_time'] ?: null,
            ':purpose' => $data['purpose'] ?: null,
            ':station_id' => !empty($data['station_id']) ? (int) $data['station_id'] : null,
            ':notes' => $data['notes'] ?: null,
            ':created_by' => !empty($data['created_by']) ? (int) $data['created_by'] : null,
        ]);
        return (int) $db->lastInsertId();
    }

    public static function find(PDO $db, int $id): ?array
    {
        $stmt = $db->prepare(
            "SELECT pa.*, p.bhc_id, p.full_name, p.first_name, p.last_name, s.name AS station_name
             FROM patient_appointments pa
             JOIN patients p ON p.id = pa.patient_id
             LEFT JOIN stations s ON s.id = pa.station_id
             WHERE pa.id = :id
             LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function forPatient(PDO $db, int $patientId, int $limit = 50): array
    {
        $stmt = $db->prepare(
            "SELECT pa.*, s.name AS station_name,
                    cr.id AS linked_consultation_id,
                    cr.consultation_date AS linked_consultation_date,
                    cr.diagnosis AS linked_consultation_diagnosis
             FROM patient_appointments pa
             LEFT JOIN stations s ON s.id = pa.station_id
             LEFT JOIN consultation_records cr ON cr.appointment_id = pa.id
             WHERE pa.patient_id = :patient_id
             ORDER BY pa.appointment_date DESC, pa.id DESC
             LIMIT :limit"
        );
        $stmt->bindValue(':patient_id', $patientId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function nextForPatient(PDO $db, int $patientId): ?array
    {
        $stmt = $db->prepare(
            "SELECT pa.*, s.name AS station_name
             FROM patient_appointments pa
             LEFT JOIN stations s ON s.id = pa.station_id
             WHERE pa.patient_id = :patient_id
               AND pa.status = 'scheduled'
               AND pa.appointment_date >= CURDATE()
             ORDER BY pa.appointment_date ASC, pa.appointment_time ASC, pa.id ASC
             LIMIT 1"
        );
        $stmt->execute([':patient_id' => $patientId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

  /** Scheduled appointments on a single calendar day (default: today). */
    public static function scheduledForDate(PDO $db, ?string $date = null, int $limit = 50): array
    {
        $date = $date && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? $date : date('Y-m-d');
        return self::upcoming($db, $date, $date, $limit);
    }

    public static function scheduledOnDateForPatient(PDO $db, int $patientId, string $date): ?array
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return null;
        }
        $stmt = $db->prepare(
            "SELECT pa.*, s.name AS station_name,
                    p.bhc_id, p.full_name, p.contact_number
             FROM patient_appointments pa
             JOIN patients p ON p.id = pa.patient_id
             LEFT JOIN stations s ON s.id = pa.station_id
             WHERE pa.patient_id = :patient_id
               AND pa.status = 'scheduled'
               AND pa.appointment_date = :appointment_date
             ORDER BY pa.appointment_time ASC, pa.id ASC
             LIMIT 1"
        );
        $stmt->execute([
            ':patient_id' => $patientId,
            ':appointment_date' => $date,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function resolveScheduledForConsultation(
        PDO $db,
        int $patientId,
        string $consultationDate,
        ?int $explicitAppointmentId = null
    ): ?int {
        if ($explicitAppointmentId !== null && $explicitAppointmentId > 0) {
            $appt = self::find($db, $explicitAppointmentId);
            if (
                $appt
                && (int) $appt['patient_id'] === $patientId
                && ($appt['status'] ?? '') === 'scheduled'
                && ($appt['appointment_date'] ?? '') === $consultationDate
            ) {
                return $explicitAppointmentId;
            }
            return null;
        }
        $appt = self::scheduledOnDateForPatient($db, $patientId, $consultationDate);
        return $appt ? (int) $appt['id'] : null;
    }

    public static function scheduledTodayForPatient(PDO $db, int $patientId): ?array
    {
        return self::scheduledOnDateForPatient($db, $patientId, date('Y-m-d'));
    }

    /** Appointment context when routing a patient to Registration (station 1). */
    public static function resolveForRouting(PDO $db, int $patientId, ?int $appointmentId = null): ?array
    {
        if ($appointmentId !== null && $appointmentId > 0) {
            $appt = self::find($db, $appointmentId);
            if (
                !$appt
                || ($appt['status'] ?? '') !== 'scheduled'
                || ($patientId > 0 && (int) $appt['patient_id'] !== $patientId)
            ) {
                return null;
            }

            return self::withRoutingPatientFields($db, $appt);
        }

        if ($patientId <= 0) {
            return null;
        }

        return self::scheduledTodayForPatient($db, $patientId);
    }

    /** @param array<string,mixed> $appt */
    private static function withRoutingPatientFields(PDO $db, array $appt): array
    {
        if (!empty($appt['contact_number'])) {
            return $appt;
        }

        $patient = Patient::find($db, (int) ($appt['patient_id'] ?? 0));
        if ($patient) {
            $appt['contact_number'] = $patient['contact_number'] ?? null;
        }

        return $appt;
    }

    /** @return array<int,int> patient_id => patient_id */
    public static function patientIdsScheduledToday(PDO $db): array
    {
        $stmt = $db->query(
            "SELECT DISTINCT patient_id
             FROM patient_appointments
             WHERE status = 'scheduled' AND appointment_date = CURDATE()"
        );
        $ids = [];
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $id) {
            $ids[(int) $id] = (int) $id;
        }
        return $ids;
    }

    public static function upcoming(PDO $db, ?string $fromDate = null, ?string $toDate = null, int $limit = 100): array
    {
        $from = $fromDate && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fromDate) ? $fromDate : date('Y-m-d');
        $to = $toDate && preg_match('/^\d{4}-\d{2}-\d{2}$/', $toDate) ? $toDate : date('Y-m-d', strtotime('+30 days'));

        $stmt = $db->prepare(
            "SELECT pa.*, p.bhc_id, p.full_name, p.contact_number, s.name AS station_name
             FROM patient_appointments pa
             JOIN patients p ON p.id = pa.patient_id
             LEFT JOIN stations s ON s.id = pa.station_id
             WHERE pa.status = 'scheduled'
               AND pa.appointment_date BETWEEN :from_date AND :to_date
             ORDER BY pa.appointment_date ASC, pa.appointment_time ASC, pa.id ASC
             LIMIT :limit"
        );
        $stmt->bindValue(':from_date', $from);
        $stmt->bindValue(':to_date', $to);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return array{total:int, scheduled:int, completed:int, cancelled:int, no_show:int, unique_patients:int} */
    public static function monthlyTotals(PDO $db, string $month): array
    {
        $b = ReportMonth::bounds($month);
        return self::totalsForRange($db, $b['start'], $b['end']);
    }

    public static function totalsForRange(PDO $db, string $start, string $end): array
    {
        $stmt = $db->prepare(
            "SELECT
                COUNT(*) AS total,
                SUM(status = 'scheduled') AS scheduled,
                SUM(status = 'completed') AS completed,
                SUM(status = 'cancelled') AS cancelled,
                SUM(status = 'no_show') AS no_show,
                COUNT(DISTINCT patient_id) AS unique_patients
             FROM patient_appointments
             WHERE appointment_date >= :start AND appointment_date < :end"
        );
        $stmt->execute([':start' => $b['start'], ':end' => $b['end']]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        return [
            'total' => (int) ($r['total'] ?? 0),
            'scheduled' => (int) ($r['scheduled'] ?? 0),
            'completed' => (int) ($r['completed'] ?? 0),
            'cancelled' => (int) ($r['cancelled'] ?? 0),
            'no_show' => (int) ($r['no_show'] ?? 0),
            'unique_patients' => (int) ($r['unique_patients'] ?? 0),
        ];
    }

    public static function monthlyList(PDO $db, string $month, int $limit = 500): array
    {
        $b = ReportMonth::bounds($month);
        $stmt = $db->prepare(
            "SELECT pa.*, p.bhc_id, p.full_name, p.contact_number, s.name AS station_name
             FROM patient_appointments pa
             JOIN patients p ON p.id = pa.patient_id
             LEFT JOIN stations s ON s.id = pa.station_id
             WHERE pa.appointment_date >= :start AND pa.appointment_date < :end
             ORDER BY pa.appointment_date ASC, pa.appointment_time ASC, pa.id ASC
             LIMIT :limit"
        );
        $stmt->bindValue(':start', $start);
        $stmt->bindValue(':end', $end);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function updateStatus(PDO $db, int $id, string $status): bool
    {
        if (!in_array($status, ['scheduled', 'completed', 'cancelled', 'no_show'], true)) {
            return false;
        }
        if ($status === 'scheduled') {
            $stmt = $db->prepare("UPDATE patient_appointments SET status = :status, completed_at = NULL WHERE id = :id");
        } else {
            $stmt = $db->prepare("UPDATE patient_appointments SET status = :status, completed_at = NOW() WHERE id = :id");
        }
        $stmt->execute([':status' => $status, ':id' => $id]);
        return $stmt->rowCount() > 0;
    }
}
