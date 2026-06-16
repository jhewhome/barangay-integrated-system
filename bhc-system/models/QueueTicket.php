<?php

class QueueTicket
{
    public const CONSULTATION_STATION_ID = 3;

    /**
     * Returns per-station counts for today's queue.
     * @return array<int, array{waiting:int, serving:int}>
     */
    public static function stationCountsToday(PDO $db): array
    {
        $stmt = $db->query(
            "SELECT station_id,
                    SUM(status = 'waiting') AS waiting,
                    SUM(status = 'serving') AS serving
             FROM queue_tickets
             WHERE DATE(created_at) = CURDATE()
             GROUP BY station_id"
        );
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $out = [];
        foreach ($rows as $r) {
            $sid = (int) ($r['station_id'] ?? 0);
            if ($sid <= 0) continue;
            $out[$sid] = [
                'waiting' => (int) ($r['waiting'] ?? 0),
                'serving' => (int) ($r['serving'] ?? 0),
            ];
        }
        return $out;
    }

    public static function totalsToday(PDO $db): array
    {
        $stmt = $db->query(
            "SELECT
                SUM(status = 'waiting') AS waiting,
                SUM(status = 'serving') AS serving,
                SUM(status = 'done') AS done,
                SUM(status = 'skipped') AS skipped
             FROM queue_tickets
             WHERE DATE(created_at) = CURDATE()"
        );
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        return [
            'waiting' => (int) ($row['waiting'] ?? 0),
            'serving' => (int) ($row['serving'] ?? 0),
            'done' => (int) ($row['done'] ?? 0),
            'skipped' => (int) ($row['skipped'] ?? 0),
        ];
    }

    /**
     * Monthly summary per station for reports.
     * @return array<int, array{
     *   station_id:int,
     *   station_name:string,
     *   waiting:int,
     *   serving:int,
     *   done:int,
     *   skipped:int,
     *   total:int,
     *   avg_wait_seconds:int,
     *   avg_service_seconds:int
     * }>
     */
    public static function monthlyStationSummary(PDO $db, string $month): array
    {
        $b = ReportMonth::bounds($month);
        return self::stationSummaryForRange($db, $b['start'], $b['end']);
    }

    public static function stationSummaryForRange(PDO $db, string $start, string $end): array
    {
        $sql =
            "SELECT
                s.id AS station_id,
                s.name AS station_name,
                SUM(qt.status = 'waiting') AS waiting,
                SUM(qt.status = 'serving') AS serving,
                SUM(qt.status = 'done') AS done,
                SUM(qt.status = 'skipped') AS skipped,
                COUNT(qt.id) AS total,
                COALESCE(AVG(CASE
                    WHEN qt.called_at IS NOT NULL THEN TIMESTAMPDIFF(SECOND, qt.created_at, qt.called_at)
                    ELSE NULL
                END), 0) AS avg_wait_seconds,
                COALESCE(AVG(CASE
                    WHEN qt.called_at IS NOT NULL AND qt.completed_at IS NOT NULL THEN TIMESTAMPDIFF(SECOND, qt.called_at, qt.completed_at)
                    ELSE NULL
                END), 0) AS avg_service_seconds
             FROM stations s
             LEFT JOIN queue_tickets qt
               ON qt.station_id = s.id
              AND qt.created_at >= :start
              AND qt.created_at < :end
             WHERE s.is_active = 1
             GROUP BY s.id, s.name
             ORDER BY s.sort_order ASC, s.id ASC";

        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':start' => $start,
            ':end' => $end,
        ]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'station_id' => (int) ($r['station_id'] ?? 0),
                'station_name' => (string) ($r['station_name'] ?? ''),
                'waiting' => (int) ($r['waiting'] ?? 0),
                'serving' => (int) ($r['serving'] ?? 0),
                'done' => (int) ($r['done'] ?? 0),
                'skipped' => (int) ($r['skipped'] ?? 0),
                'total' => (int) ($r['total'] ?? 0),
                'avg_wait_seconds' => (int) round((float) ($r['avg_wait_seconds'] ?? 0)),
                'avg_service_seconds' => (int) round((float) ($r['avg_service_seconds'] ?? 0)),
            ];
        }
        return $out;
    }

    /**
     * @return array{waiting:int, serving:int, done:int, skipped:int, total:int, avg_wait_seconds:int, avg_service_seconds:int}
     */
    public static function monthlyTotals(PDO $db, string $month): array
    {
        $b = ReportMonth::bounds($month);
        return self::totalsForRange($db, $b['start'], $b['end']);
    }

    public static function totalsForRange(PDO $db, string $start, string $end): array
    {
        $stmt = $db->prepare(
            "SELECT
                SUM(status = 'waiting') AS waiting,
                SUM(status = 'serving') AS serving,
                SUM(status = 'done') AS done,
                SUM(status = 'skipped') AS skipped,
                COUNT(*) AS total,
                COALESCE(AVG(CASE
                    WHEN called_at IS NOT NULL THEN TIMESTAMPDIFF(SECOND, created_at, called_at)
                    ELSE NULL
                END), 0) AS avg_wait_seconds,
                COALESCE(AVG(CASE
                    WHEN called_at IS NOT NULL AND completed_at IS NOT NULL THEN TIMESTAMPDIFF(SECOND, called_at, completed_at)
                    ELSE NULL
                END), 0) AS avg_service_seconds
             FROM queue_tickets
             WHERE created_at >= :start AND created_at < :end"
        );
        $stmt->execute([':start' => $start, ':end' => $end]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        return [
            'waiting' => (int) ($r['waiting'] ?? 0),
            'serving' => (int) ($r['serving'] ?? 0),
            'done' => (int) ($r['done'] ?? 0),
            'skipped' => (int) ($r['skipped'] ?? 0),
            'total' => (int) ($r['total'] ?? 0),
            'avg_wait_seconds' => (int) round((float) ($r['avg_wait_seconds'] ?? 0)),
            'avg_service_seconds' => (int) round((float) ($r['avg_service_seconds'] ?? 0)),
        ];
    }

    public static function waitingList(PDO $db, int $stationId, int $limit = 5): array
    {
        $stmt = $db->prepare(
            "SELECT qt.id, qt.ticket_no, qt.reason, qt.status, qt.created_at, p.bhc_id, p.full_name
             FROM queue_tickets qt
             JOIN patients p ON p.id = qt.patient_id
             WHERE qt.station_id = :station_id
               AND DATE(qt.created_at) = CURDATE()
               AND qt.status = 'waiting'
             ORDER BY qt.id ASC
             LIMIT :limit"
        );
        $stmt->bindValue(':station_id', $stationId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function callTicket(PDO $db, int $stationId, int $ticketId): ?array
    {
        // If someone is already serving, don't call another.
        $serving = self::nowServing($db, $stationId);
        if ($serving) {
            return $serving;
        }

        $stmt = $db->prepare(
            "SELECT id FROM queue_tickets
             WHERE id = :id
               AND station_id = :station_id
               AND DATE(created_at) = CURDATE()
               AND status = 'waiting'
             LIMIT 1"
        );
        $stmt->execute([
            ':id' => $ticketId,
            ':station_id' => $stationId,
        ]);
        $id = $stmt->fetchColumn();
        if (!$id) {
            return null;
        }

        $upd = $db->prepare("UPDATE queue_tickets SET status = 'serving', called_at = NOW() WHERE id = :id");
        $upd->execute([':id' => (int) $id]);
        return self::find($db, (int) $id);
    }

    public static function todayTicketsForStation(PDO $db, int $stationId): array
    {
        $stmt = $db->prepare(
            "SELECT qt.id, qt.station_id, qt.patient_id, qt.ticket_no, qt.reason, qt.status, qt.created_at, qt.called_at, qt.completed_at,
                    p.bhc_id, p.full_name
             FROM queue_tickets qt
             JOIN patients p ON p.id = qt.patient_id
             WHERE qt.station_id = :station_id
               AND DATE(qt.created_at) = CURDATE()
             ORDER BY
               FIELD(qt.status, 'serving','waiting','done','skipped') ASC,
               qt.id ASC"
        );
        $stmt->execute([':station_id' => $stationId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function nowServing(PDO $db, int $stationId): ?array
    {
        $stmt = $db->prepare(
            "SELECT qt.id, qt.ticket_no, qt.status, qt.called_at, qt.assigned_doctor_id,
                    qt.patient_id, p.bhc_id, p.full_name,
                    u.username AS doctor_username, u.display_name AS doctor_display_name
             FROM queue_tickets qt
             JOIN patients p ON p.id = qt.patient_id
             LEFT JOIN users u ON u.id = qt.assigned_doctor_id
             WHERE qt.station_id = :station_id
               AND DATE(qt.created_at) = CURDATE()
               AND qt.status = 'serving'
             ORDER BY qt.called_at DESC, qt.id DESC
             LIMIT 1"
        );
        $stmt->execute([':station_id' => $stationId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function enqueue(PDO $db, int $stationId, int $patientId): int
    {
        return self::enqueueWithReason($db, $stationId, $patientId, null);
    }

    public static function enqueueWithReason(PDO $db, int $stationId, int $patientId, ?string $reason): int
    {
        $visitDate = date('Y-m-d');
        $visitId = PatientVisit::findOrCreateForDate($db, $patientId, $visitDate, $reason);
        $ticketNo = self::nextTicketNo($db, $stationId);
        $stmt = $db->prepare(
            "INSERT INTO queue_tickets (station_id, patient_id, visit_id, ticket_no, reason, status)
             VALUES (:station_id, :patient_id, :visit_id, :ticket_no, :reason, 'waiting')"
        );
        $stmt->execute([
            ':station_id' => $stationId,
            ':patient_id' => $patientId,
            ':visit_id' => $visitId,
            ':ticket_no' => $ticketNo,
            ':reason' => $reason ?: null,
        ]);
        return (int) $db->lastInsertId();
    }

    public static function callNext(PDO $db, int $stationId): ?array
    {
        // If someone is already serving, don't call another.
        $serving = self::nowServing($db, $stationId);
        if ($serving) {
            return $serving;
        }

        $stmt = $db->prepare(
            "SELECT id FROM queue_tickets
             WHERE station_id = :station_id
               AND DATE(created_at) = CURDATE()
               AND status = 'waiting'
             ORDER BY id ASC
             LIMIT 1"
        );
        $stmt->execute([':station_id' => $stationId]);
        $nextId = $stmt->fetchColumn();
        if (!$nextId) {
            return null;
        }

        $upd = $db->prepare("UPDATE queue_tickets SET status = 'serving', called_at = NOW() WHERE id = :id");
        $upd->execute([':id' => (int) $nextId]);

        return self::find($db, (int) $nextId);
    }

    public static function complete(PDO $db, int $ticketId): void
    {
        $visitId = self::visitIdForTicket($db, $ticketId);
        $stmt = $db->prepare("UPDATE queue_tickets SET status = 'done', completed_at = NOW() WHERE id = :id");
        $stmt->execute([':id' => $ticketId]);
        if ($visitId) {
            PatientVisit::refreshStatus($db, $visitId);
        }
    }

    public static function skip(PDO $db, int $ticketId): void
    {
        $visitId = self::visitIdForTicket($db, $ticketId);
        $stmt = $db->prepare("UPDATE queue_tickets SET status = 'skipped', completed_at = NOW() WHERE id = :id");
        $stmt->execute([':id' => $ticketId]);
        if ($visitId) {
            PatientVisit::refreshStatus($db, $visitId);
        }
    }

    private static function visitIdForTicket(PDO $db, int $ticketId): ?int
    {
        $stmt = $db->prepare("SELECT visit_id FROM queue_tickets WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $ticketId]);
        $id = $stmt->fetchColumn();
        return $id ? (int) $id : null;
    }

    /**
     * Return a skipped ticket to the waiting queue (same station, same day).
     */
    public static function recallToQueue(PDO $db, int $stationId, int $ticketId): bool
    {
        $stmt = $db->prepare(
            "UPDATE queue_tickets
             SET status = 'waiting', called_at = NULL, completed_at = NULL
             WHERE id = :id
               AND station_id = :station_id
               AND DATE(created_at) = CURDATE()
               AND status = 'skipped'"
        );
        $stmt->execute([
            ':id' => $ticketId,
            ':station_id' => $stationId,
        ]);

        return $stmt->rowCount() > 0;
    }

    public static function skippedList(PDO $db, int $stationId, int $limit = 8): array
    {
        $stmt = $db->prepare(
            "SELECT qt.id, qt.ticket_no, qt.reason, qt.status, qt.created_at, qt.completed_at,
                    p.bhc_id, p.full_name
             FROM queue_tickets qt
             JOIN patients p ON p.id = qt.patient_id
             WHERE qt.station_id = :station_id
               AND DATE(qt.created_at) = CURDATE()
               AND qt.status = 'skipped'
             ORDER BY qt.completed_at DESC, qt.id DESC
             LIMIT :limit"
        );
        $stmt->bindValue(':station_id', $stationId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function recentForPatient(PDO $db, int $patientId, int $limit = 25): array
    {
        $stmt = $db->prepare(
            "SELECT qt.id, qt.ticket_no, qt.status, qt.reason, qt.created_at, qt.called_at, qt.completed_at,
                    s.name AS station_name
             FROM queue_tickets qt
             JOIN stations s ON s.id = qt.station_id
             WHERE qt.patient_id = :patient_id
             ORDER BY qt.created_at DESC
             LIMIT :limit"
        );
        $stmt->bindValue(':patient_id', $patientId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function find(PDO $db, int $id): ?array
    {
        $stmt = $db->prepare(
            "SELECT qt.id, qt.station_id, qt.patient_id, qt.visit_id, qt.ticket_no, qt.reason, qt.status,
                    qt.created_at, qt.called_at, qt.completed_at, qt.assigned_doctor_id,
                    p.bhc_id, p.full_name,
                    u.username AS doctor_username, u.display_name AS doctor_display_name
             FROM queue_tickets qt
             JOIN patients p ON p.id = qt.patient_id
             LEFT JOIN users u ON u.id = qt.assigned_doctor_id
             WHERE qt.id = :id
             LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private static function nextTicketNo(PDO $db, int $stationId): string
    {
        // Ticket format: <StationCode>-<3-digit sequence>, e.g. TR-001
        $stmt = $db->prepare("SELECT code FROM stations WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $stationId]);
        $code = (string) ($stmt->fetchColumn() ?: 'ST');

        $stmt2 = $db->prepare(
            "SELECT COUNT(*) FROM queue_tickets
             WHERE station_id = :station_id
               AND DATE(created_at) = CURDATE()"
        );
        $stmt2->execute([':station_id' => $stationId]);
        $count = (int) $stmt2->fetchColumn();

        $seq = $count + 1;
        return $code . '-' . str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
    }

    public static function assignDoctor(PDO $db, int $ticketId, ?int $doctorId): bool
    {
        $stmt = $db->prepare("UPDATE queue_tickets SET assigned_doctor_id = :doctor_id WHERE id = :id");
        $stmt->execute([
            ':doctor_id' => $doctorId && $doctorId > 0 ? $doctorId : null,
            ':id' => $ticketId,
        ]);
        return $stmt->rowCount() > 0;
    }

    /** @return array<int,array<string,mixed>> */
    public static function assignedToDoctor(PDO $db, int $doctorId, bool $todayOnly = true): array
    {
        $dateClause = $todayOnly ? 'AND DATE(qt.created_at) = CURDATE()' : '';
        $stmt = $db->prepare(
            "SELECT qt.id, qt.ticket_no, qt.status, qt.reason, qt.created_at, qt.called_at,
                    qt.station_id, s.name AS station_name,
                    p.id AS patient_id, p.bhc_id, p.full_name, p.sex, p.birthdate, p.contact_number
             FROM queue_tickets qt
             JOIN patients p ON p.id = qt.patient_id
             JOIN stations s ON s.id = qt.station_id
             WHERE qt.assigned_doctor_id = :doctor_id
               {$dateClause}
             ORDER BY FIELD(qt.status, 'serving', 'waiting', 'done', 'skipped'), qt.id ASC"
        );
        $stmt->execute([':doctor_id' => $doctorId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function consultationStationId(): int
    {
        return 3;
    }

    /** Any consultation-station ticket for the patient today (one per patient per day). */
    public static function consultationTicketForPatientToday(PDO $db, int $patientId): ?array
    {
        $stmt = $db->prepare(
            "SELECT qt.id, qt.ticket_no, qt.status, qt.station_id, s.name AS station_name
             FROM queue_tickets qt
             JOIN stations s ON s.id = qt.station_id
             WHERE qt.patient_id = :patient_id
               AND qt.station_id = :station_id
               AND DATE(qt.created_at) = CURDATE()
             ORDER BY qt.id DESC
             LIMIT 1"
        );
        $stmt->execute([
            ':patient_id' => $patientId,
            ':station_id' => self::consultationStationId(),
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function incompleteConsultationTicketForPatientToday(PDO $db, int $patientId): ?array
    {
        $ticket = self::consultationTicketForPatientToday($db, $patientId);
        if (!$ticket) {
            return null;
        }
        $status = (string) ($ticket['status'] ?? '');
        return in_array($status, ['waiting', 'serving'], true) ? $ticket : null;
    }

    /**
     * @param array<int,int> $patientIds
     * @return array<int,array<int,array<string,mixed>>>
     */
    public static function activeTicketsMapForPatientsToday(PDO $db, array $patientIds): array
    {
        $patientIds = array_values(array_unique(array_filter(array_map('intval', $patientIds))));
        if ($patientIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($patientIds), '?'));
        $stmt = $db->prepare(
            "SELECT qt.id, qt.patient_id, qt.ticket_no, qt.status, qt.station_id, s.name AS station_name
             FROM queue_tickets qt
             JOIN stations s ON s.id = qt.station_id
             WHERE qt.patient_id IN ({$placeholders})
               AND DATE(qt.created_at) = CURDATE()
               AND qt.status IN ('waiting', 'serving')
             ORDER BY FIELD(qt.status, 'serving', 'waiting'), qt.id ASC"
        );
        foreach ($patientIds as $i => $id) {
            $stmt->bindValue($i + 1, $id, PDO::PARAM_INT);
        }
        $stmt->execute();

        $map = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $pid = (int) $row['patient_id'];
            $map[$pid][] = $row;
        }

        return $map;
    }

    /** @return array<int,array<string,mixed>> */
    public static function activeTicketsForPatientToday(PDO $db, int $patientId): array
    {
        $stmt = $db->prepare(
            "SELECT qt.id, qt.ticket_no, qt.status, qt.station_id, s.name AS station_name
             FROM queue_tickets qt
             JOIN stations s ON s.id = qt.station_id
             WHERE qt.patient_id = :patient_id
               AND DATE(qt.created_at) = CURDATE()
               AND qt.status IN ('waiting', 'serving')
             ORDER BY FIELD(qt.status, 'serving', 'waiting'), qt.id ASC"
        );
        $stmt->execute([':patient_id' => $patientId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function activeTicketForDoctorPatient(PDO $db, int $doctorId, int $patientId): ?array
    {
        $stmt = $db->prepare(
            "SELECT qt.*
             FROM queue_tickets qt
             WHERE qt.patient_id = :patient_id
               AND qt.assigned_doctor_id = :doctor_id
               AND qt.status IN ('waiting', 'serving')
               AND DATE(qt.created_at) = CURDATE()
             ORDER BY FIELD(qt.status, 'serving', 'waiting'), qt.id DESC
             LIMIT 1"
        );
        $stmt->execute([':patient_id' => $patientId, ':doctor_id' => $doctorId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function findAssignedToDoctor(PDO $db, int $ticketId, int $doctorId): ?array
    {
        $stmt = $db->prepare(
            "SELECT qt.*, p.bhc_id, p.full_name
             FROM queue_tickets qt
             JOIN patients p ON p.id = qt.patient_id
             WHERE qt.id = :id
               AND qt.assigned_doctor_id = :doctor_id
               AND DATE(qt.created_at) = CURDATE()
             LIMIT 1"
        );
        $stmt->execute([':id' => $ticketId, ':doctor_id' => $doctorId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Call the oldest waiting consultation ticket assigned to this doctor.
     * Returns null if the room is serving another doctor's patient.
     */
    public static function callNextForDoctor(
        PDO $db,
        int $doctorId,
        int $stationId = self::CONSULTATION_STATION_ID
    ): ?array {
        $serving = self::nowServing($db, $stationId);
        if ($serving) {
            if ((int) ($serving['assigned_doctor_id'] ?? 0) === $doctorId) {
                return self::find($db, (int) $serving['id']);
            }
            return null;
        }

        $stmt = $db->prepare(
            "SELECT id FROM queue_tickets
             WHERE station_id = :station_id
               AND DATE(created_at) = CURDATE()
               AND status = 'waiting'
               AND assigned_doctor_id = :doctor_id
             ORDER BY id ASC
             LIMIT 1"
        );
        $stmt->execute([
            ':station_id' => $stationId,
            ':doctor_id' => $doctorId,
        ]);
        $nextId = $stmt->fetchColumn();
        if (!$nextId) {
            return null;
        }

        $upd = $db->prepare("UPDATE queue_tickets SET status = 'serving', called_at = NOW() WHERE id = :id");
        $upd->execute([':id' => (int) $nextId]);

        return self::find($db, (int) $nextId);
    }

    public static function callTicketForDoctor(
        PDO $db,
        int $doctorId,
        int $stationId,
        int $ticketId
    ): ?array {
        $serving = self::nowServing($db, $stationId);
        if ($serving && (int) $serving['id'] !== $ticketId) {
            return null;
        }

        $stmt = $db->prepare(
            "SELECT id FROM queue_tickets
             WHERE id = :id
               AND station_id = :station_id
               AND assigned_doctor_id = :doctor_id
               AND DATE(created_at) = CURDATE()
               AND status = 'waiting'
             LIMIT 1"
        );
        $stmt->execute([
            ':id' => $ticketId,
            ':station_id' => $stationId,
            ':doctor_id' => $doctorId,
        ]);
        $id = $stmt->fetchColumn();
        if (!$id) {
            return null;
        }

        $upd = $db->prepare("UPDATE queue_tickets SET status = 'serving', called_at = NOW() WHERE id = :id");
        $upd->execute([':id' => (int) $id]);

        return self::find($db, (int) $id);
    }
}

