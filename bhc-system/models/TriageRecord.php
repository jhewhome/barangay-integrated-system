<?php

class TriageRecord
{
    public static function create(PDO $db, array $data): int
    {
        $stmt = $db->prepare(
            "INSERT INTO triage_records
             (visit_id, queue_ticket_id, patient_id,
              blood_pressure_systolic, blood_pressure_diastolic,
              temperature, pulse_rate, weight_kg, height_cm, notes, recorded_by)
             VALUES
             (:visit_id, :queue_ticket_id, :patient_id,
              :bps, :bpd, :temperature, :pulse_rate, :weight_kg, :height_cm, :notes, :recorded_by)"
        );
        $stmt->execute([
            ':visit_id' => (int) $data['visit_id'],
            ':queue_ticket_id' => !empty($data['queue_ticket_id']) ? (int) $data['queue_ticket_id'] : null,
            ':patient_id' => (int) $data['patient_id'],
            ':bps' => self::nullableInt($data['blood_pressure_systolic'] ?? null),
            ':bpd' => self::nullableInt($data['blood_pressure_diastolic'] ?? null),
            ':temperature' => self::nullableFloat($data['temperature'] ?? null),
            ':pulse_rate' => self::nullableInt($data['pulse_rate'] ?? null),
            ':weight_kg' => self::nullableFloat($data['weight_kg'] ?? null),
            ':height_cm' => self::nullableFloat($data['height_cm'] ?? null),
            ':notes' => !empty($data['notes']) ? trim((string) $data['notes']) : null,
            ':recorded_by' => !empty($data['recorded_by']) ? (int) $data['recorded_by'] : null,
        ]);
        return (int) $db->lastInsertId();
    }

    public static function update(PDO $db, int $id, array $data): bool
    {
        $stmt = $db->prepare(
            "UPDATE triage_records SET
              blood_pressure_systolic = :bps,
              blood_pressure_diastolic = :bpd,
              temperature = :temperature,
              pulse_rate = :pulse_rate,
              weight_kg = :weight_kg,
              height_cm = :height_cm,
              notes = :notes
             WHERE id = :id"
        );
        $stmt->execute([
            ':bps' => self::nullableInt($data['blood_pressure_systolic'] ?? null),
            ':bpd' => self::nullableInt($data['blood_pressure_diastolic'] ?? null),
            ':temperature' => self::nullableFloat($data['temperature'] ?? null),
            ':pulse_rate' => self::nullableInt($data['pulse_rate'] ?? null),
            ':weight_kg' => self::nullableFloat($data['weight_kg'] ?? null),
            ':height_cm' => self::nullableFloat($data['height_cm'] ?? null),
            ':notes' => !empty($data['notes']) ? trim((string) $data['notes']) : null,
            ':id' => $id,
        ]);
        return $stmt->rowCount() > 0;
    }

    public static function byTicket(PDO $db, int $ticketId): ?array
    {
        $stmt = $db->prepare(
            "SELECT tr.*, u.username AS recorded_by_name
             FROM triage_records tr
             LEFT JOIN users u ON u.id = tr.recorded_by
             WHERE tr.queue_ticket_id = :ticket_id
             ORDER BY tr.id DESC
             LIMIT 1"
        );
        $stmt->execute([':ticket_id' => $ticketId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function forVisit(PDO $db, int $visitId): ?array
    {
        $stmt = $db->prepare(
            "SELECT tr.*, u.username AS recorded_by_name
             FROM triage_records tr
             LEFT JOIN users u ON u.id = tr.recorded_by
             WHERE tr.visit_id = :visit_id
             ORDER BY tr.id DESC
             LIMIT 1"
        );
        $stmt->execute([':visit_id' => $visitId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function saveForTicket(PDO $db, array $data): int
    {
        $ticketId = (int) ($data['queue_ticket_id'] ?? 0);
        $existing = $ticketId > 0 ? self::byTicket($db, $ticketId) : null;
        if ($existing) {
            self::update($db, (int) $existing['id'], $data);
            return (int) $existing['id'];
        }
        return self::create($db, $data);
    }

    /** @return array{total:int, with_vitals:int} */
    public static function statsForDate(PDO $db, string $date): array
    {
        $start = $date . ' 00:00:00';
        $end = date('Y-m-d', strtotime($date . ' +1 day')) . ' 00:00:00';
        $stmt = $db->prepare(
            "SELECT COUNT(*) AS total,
                    SUM(
                      blood_pressure_systolic IS NOT NULL
                      OR temperature IS NOT NULL
                      OR pulse_rate IS NOT NULL
                      OR weight_kg IS NOT NULL
                    ) AS with_vitals
             FROM triage_records
             WHERE created_at >= :start AND created_at < :end"
        );
        $stmt->execute([':start' => $start, ':end' => $end]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        return [
            'total' => (int) ($r['total'] ?? 0),
            'with_vitals' => (int) ($r['with_vitals'] ?? 0),
        ];
    }

    private static function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        return (int) $value;
    }

    private static function nullableFloat(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        return (float) $value;
    }

    public static function formatBloodPressure(?array $row): string
    {
        if (!$row) {
            return '—';
        }
        $sys = $row['blood_pressure_systolic'] ?? null;
        $dia = $row['blood_pressure_diastolic'] ?? null;
        if ($sys === null && $dia === null) {
            return '—';
        }
        return (string) ($sys ?? '—') . '/' . (string) ($dia ?? '—');
    }
}
