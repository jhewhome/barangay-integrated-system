<?php

class PatientAccess
{
    public static function doctorCanView(PDO $db, int $doctorId, int $patientId): bool
    {
        $stmt = $db->prepare(
            "SELECT 1 FROM queue_tickets
             WHERE patient_id = :patient_id AND assigned_doctor_id = :doctor_id
             LIMIT 1"
        );
        $stmt->execute([':patient_id' => $patientId, ':doctor_id' => $doctorId]);
        if ($stmt->fetchColumn()) {
            return true;
        }

        $stmt = $db->prepare(
            "SELECT 1 FROM doctor_comments
             WHERE patient_id = :patient_id AND doctor_id = :doctor_id
             LIMIT 1"
        );
        $stmt->execute([':patient_id' => $patientId, ':doctor_id' => $doctorId]);
        if ($stmt->fetchColumn()) {
            return true;
        }

        $stmt = $db->prepare(
            "SELECT 1 FROM consultation_records
             WHERE patient_id = :patient_id AND doctor_id = :doctor_id
             LIMIT 1"
        );
        $stmt->execute([':patient_id' => $patientId, ':doctor_id' => $doctorId]);
        return (bool) $stmt->fetchColumn();
    }
}
