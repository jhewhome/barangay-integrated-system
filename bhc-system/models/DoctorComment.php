<?php

class DoctorComment
{
    public static function create(PDO $db, array $data): int
    {
        $stmt = $db->prepare(
            "INSERT INTO doctor_comments
             (patient_id, doctor_id, consultation_id, queue_ticket_id, comment)
             VALUES
             (:patient_id, :doctor_id, :consultation_id, :queue_ticket_id, :comment)"
        );
        $stmt->execute([
            ':patient_id' => (int) $data['patient_id'],
            ':doctor_id' => (int) $data['doctor_id'],
            ':consultation_id' => !empty($data['consultation_id']) ? (int) $data['consultation_id'] : null,
            ':queue_ticket_id' => !empty($data['queue_ticket_id']) ? (int) $data['queue_ticket_id'] : null,
            ':comment' => $data['comment'],
        ]);
        return (int) $db->lastInsertId();
    }

    public static function forPatient(PDO $db, int $patientId, int $limit = 100): array
    {
        $stmt = $db->prepare(
            "SELECT dc.*, u.username AS doctor_username, u.display_name AS doctor_display_name
             FROM doctor_comments dc
             JOIN users u ON u.id = dc.doctor_id
             WHERE dc.patient_id = :patient_id
             ORDER BY dc.created_at DESC, dc.id DESC
             LIMIT :limit"
        );
        $stmt->bindValue(':patient_id', $patientId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function doctorLabel(array $row): string
    {
        $name = trim((string) ($row['doctor_display_name'] ?? ''));
        if ($name !== '') {
            return str_starts_with(strtolower($name), 'dr') ? $name : 'Dr. ' . $name;
        }
        return 'Dr. ' . ($row['doctor_username'] ?? 'Staff');
    }
}
