<?php

class PatientVisit
{
    public static function nextVisitNo(PDO $db, string $visitDate): string
    {
        $compact = str_replace('-', '', $visitDate);
        $prefix = 'V-' . $compact . '-';
        $stmt = $db->prepare(
            "SELECT visit_no FROM patient_visits WHERE visit_no LIKE :prefix ORDER BY id DESC LIMIT 1"
        );
        $stmt->execute([':prefix' => $prefix . '%']);
        $last = (string) ($stmt->fetchColumn() ?: '');
        $seq = 1;
        if ($last !== '' && preg_match('/-(\d+)$/', $last, $m)) {
            $seq = (int) $m[1] + 1;
        }
        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    public static function find(PDO $db, int $id): ?array
    {
        $stmt = $db->prepare(
            "SELECT pv.*, p.bhc_id, p.full_name
             FROM patient_visits pv
             JOIN patients p ON p.id = pv.patient_id
             WHERE pv.id = :id
             LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function findForPatientOnDate(PDO $db, int $patientId, string $visitDate): ?array
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $visitDate)) {
            $visitDate = date('Y-m-d');
        }
        $stmt = $db->prepare(
            "SELECT * FROM patient_visits
             WHERE patient_id = :patient_id AND visit_date = :visit_date
             LIMIT 1"
        );
        $stmt->execute([':patient_id' => $patientId, ':visit_date' => $visitDate]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function findOrCreateForDate(PDO $db, int $patientId, string $visitDate, ?string $primaryReason = null): int
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $visitDate)) {
            $visitDate = date('Y-m-d');
        }
        $existing = self::findForPatientOnDate($db, $patientId, $visitDate);
        if ($existing) {
            if ($primaryReason && trim($primaryReason) !== '' && empty($existing['primary_reason'])) {
                $upd = $db->prepare(
                    "UPDATE patient_visits SET primary_reason = :reason WHERE id = :id AND (primary_reason IS NULL OR TRIM(primary_reason) = '')"
                );
                $upd->execute([':reason' => trim($primaryReason), ':id' => (int) $existing['id']]);
            }
            return (int) $existing['id'];
        }

        $stmt = $db->prepare(
            "INSERT INTO patient_visits
             (visit_no, patient_id, visit_date, primary_reason, status, started_at)
             VALUES
             (:visit_no, :patient_id, :visit_date, :primary_reason, 'open', NOW())"
        );
        $stmt->execute([
            ':visit_no' => self::nextVisitNo($db, $visitDate),
            ':patient_id' => $patientId,
            ':visit_date' => $visitDate,
            ':primary_reason' => $primaryReason ? trim($primaryReason) : null,
        ]);
        return (int) $db->lastInsertId();
    }

    public static function refreshStatus(PDO $db, int $visitId): void
    {
        $stmt = $db->prepare(
            "SELECT
                COUNT(*) AS total,
                SUM(status IN ('done','skipped')) AS resolved
             FROM queue_tickets
             WHERE visit_id = :visit_id"
        );
        $stmt->execute([':visit_id' => $visitId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $total = (int) ($row['total'] ?? 0);
        $resolved = (int) ($row['resolved'] ?? 0);
        if ($total > 0 && $resolved >= $total) {
            $db->prepare(
                "UPDATE patient_visits SET status = 'completed', ended_at = COALESCE(ended_at, NOW()) WHERE id = :id"
            )->execute([':id' => $visitId]);
        }
    }

    public static function forPatient(PDO $db, int $patientId, int $limit = 30): array
    {
        $stmt = $db->prepare(
            "SELECT pv.*,
                    (SELECT COUNT(*) FROM queue_tickets qt WHERE qt.visit_id = pv.id) AS ticket_count,
                    (SELECT COUNT(*) FROM triage_records tr WHERE tr.visit_id = pv.id) AS triage_count
             FROM patient_visits pv
             WHERE pv.patient_id = :patient_id
             ORDER BY pv.visit_date DESC, pv.id DESC
             LIMIT :limit"
        );
        $stmt->bindValue(':patient_id', $patientId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return array<int,array<string,mixed>> */
    public static function ticketsForVisit(PDO $db, int $visitId): array
    {
        $stmt = $db->prepare(
            "SELECT qt.id, qt.ticket_no, qt.status, qt.reason, qt.created_at, qt.completed_at, s.name AS station_name
             FROM queue_tickets qt
             JOIN stations s ON s.id = qt.station_id
             WHERE qt.visit_id = :visit_id
             ORDER BY qt.created_at ASC, qt.id ASC"
        );
        $stmt->execute([':visit_id' => $visitId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return array{total:int, open:int, completed:int} */
    public static function statsForDate(PDO $db, string $date): array
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $date = date('Y-m-d');
        }
        $stmt = $db->prepare(
            "SELECT
                COUNT(*) AS total,
                SUM(status = 'open') AS open_count,
                SUM(status = 'completed') AS completed_count
             FROM patient_visits
             WHERE visit_date = :visit_date"
        );
        $stmt->execute([':visit_date' => $date]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        return [
            'total' => (int) ($r['total'] ?? 0),
            'open' => (int) ($r['open_count'] ?? 0),
            'completed' => (int) ($r['completed_count'] ?? 0),
        ];
    }
}
