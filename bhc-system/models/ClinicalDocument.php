<?php

class ClinicalDocument
{
    public static function nextDocumentNo(PDO $db, string $code = 'RX'): string
    {
        $year = date('Y');
        $prefix = 'BHC-' . strtoupper($code) . '-' . $year . '-';
        $stmt = $db->prepare(
            "SELECT document_no FROM clinical_documents
             WHERE document_no LIKE :prefix
             ORDER BY id DESC LIMIT 1"
        );
        $stmt->execute([':prefix' => $prefix . '%']);
        $last = (string) ($stmt->fetchColumn() ?: '');
        $seq = 1;
        if ($last !== '' && preg_match('/-(\d+)$/', $last, $m)) {
            $seq = (int) $m[1] + 1;
        }
        return $prefix . str_pad((string) $seq, 5, '0', STR_PAD_LEFT);
    }

    public static function find(PDO $db, int $id): ?array
    {
        $stmt = $db->prepare(
            "SELECT cd.*, u.username AS issued_by_username, u.display_name AS issued_by_display_name,
                    du.display_name AS doctor_display_name, du.username AS doctor_username
             FROM clinical_documents cd
             LEFT JOIN users u ON u.id = cd.created_by
             LEFT JOIN users du ON du.id = cd.doctor_id
             WHERE cd.id = :id
             LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function findByConsultation(PDO $db, int $consultationId, string $type = 'medicine_receipt'): ?array
    {
        $stmt = $db->prepare(
            "SELECT * FROM clinical_documents
             WHERE consultation_id = :consultation_id
               AND document_type = :document_type
               AND status = 'issued'
             ORDER BY id DESC
             LIMIT 1"
        );
        $stmt->execute([
            ':consultation_id' => $consultationId,
            ':document_type' => $type,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function forPatient(PDO $db, int $patientId, int $limit = 50): array
    {
        $stmt = $db->prepare(
            "SELECT cd.*, u.username AS issued_by_username, u.display_name AS issued_by_display_name
             FROM clinical_documents cd
             LEFT JOIN users u ON u.id = cd.created_by
             WHERE cd.patient_id = :patient_id
               AND cd.status = 'issued'
             ORDER BY cd.issued_at DESC, cd.id DESC
             LIMIT :fetch_limit"
        );
        $fetchLimit = max($limit * 3, $limit);
        $stmt->bindValue(':patient_id', $patientId, PDO::PARAM_INT);
        $stmt->bindValue(':fetch_limit', $fetchLimit, PDO::PARAM_INT);
        $stmt->execute();

        $documents = [];
        $receiptConsultations = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if (($row['document_type'] ?? '') === 'medicine_receipt') {
                $cid = (int) ($row['consultation_id'] ?? 0);
                if ($cid > 0) {
                    if (isset($receiptConsultations[$cid])) {
                        continue;
                    }
                    $receiptConsultations[$cid] = true;
                }
            }
            $documents[] = $row;
            if (count($documents) >= $limit) {
                break;
            }
        }

        return $documents;
    }

    /** @return array<string,mixed> */
    public static function decodeContent(array $document): array
    {
        $raw = $document['content_json'] ?? '{}';
        $decoded = json_decode((string) $raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    /** @return array<int,int> consultation_id => document_id */
    public static function medicineReceiptMapForPatient(PDO $db, int $patientId): array
    {
        $stmt = $db->prepare(
            "SELECT id, consultation_id FROM clinical_documents
             WHERE patient_id = :patient_id
               AND document_type = 'medicine_receipt'
               AND status = 'issued'
               AND consultation_id IS NOT NULL
             ORDER BY id DESC"
        );
        $stmt->execute([':patient_id' => $patientId]);
        $map = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $cid = (int) ($row['consultation_id'] ?? 0);
            if ($cid > 0 && !isset($map[$cid])) {
                $map[$cid] = (int) $row['id'];
            }
        }
        return $map;
    }

    /** @param array<int,array<string,mixed>> $medicines */
    private static function consolidateMedicineLines(array $medicines): array
    {
        return MedicineDispensing::consolidateLines($medicines);
    }

    /**
     * Build printable snapshot from a consultation and its medicine lines.
     * @return array<string,mixed>|null
     */
    public static function buildMedicineReceiptSnapshot(PDO $db, int $consultationId): ?array
    {
        $consultation = ConsultationRecord::find($db, $consultationId);
        if (!$consultation) {
            return null;
        }

        $allMedicines = MedicineDispensing::forConsultation($db, $consultationId);
        if (empty($allMedicines)) {
            return null;
        }

        $allMedicines = self::consolidateMedicineLines($allMedicines);

        $dispensed = array_values(array_filter(
            $allMedicines,
            fn ($m) => ($m['dispense_status'] ?? '') === 'dispensed'
        ));
        $medicines = $allMedicines;

        $doctorName = User::documentNameFromParts(
            $consultation['doctor_display_name'] ?? null,
            $consultation['doctor_username'] ?? null
        );

        $recordedBy = (string) ($consultation['recorded_by_name'] ?? 'Staff');

        return [
            'patient_id' => (int) $consultation['patient_id'],
            'bhc_id' => (string) ($consultation['bhc_id'] ?? ''),
            'full_name' => (string) ($consultation['full_name'] ?? ''),
            'consultation_date' => (string) ($consultation['consultation_date'] ?? date('Y-m-d')),
            'diagnosis' => (string) ($consultation['diagnosis'] ?? ''),
            'clinical_notes' => (string) ($consultation['clinical_notes'] ?? ''),
            'doctor_name' => $doctorName,
            'recorded_by_name' => $recordedBy,
            'medicines' => array_map(static function (array $m): array {
                $source = MedicineDispensing::normalizeProcurementSource((string) ($m['procurement_source'] ?? MedicineDispensing::SOURCE_CLINIC));
                return [
                    'medicine_name' => (string) ($m['medicine_name'] ?? ''),
                    'quantity' => (string) ($m['quantity'] ?? ''),
                    'unit' => (string) ($m['unit'] ?? ''),
                    'dispense_status' => (string) ($m['dispense_status'] ?? ''),
                    'procurement_source' => $source,
                    'procurement_label' => MedicineDispensing::procurementLabel($source),
                    'receipt_issued' => !empty($m['receipt_issued']),
                ];
            }, $medicines),
            'medicine_count' => count($medicines),
            'has_dispensed' => !empty($dispensed),
            'has_external_procurement' => !empty(array_filter(
                $medicines,
                static fn (array $m): bool => in_array(
                    MedicineDispensing::normalizeProcurementSource((string) ($m['procurement_source'] ?? MedicineDispensing::SOURCE_CLINIC)),
                    [MedicineDispensing::SOURCE_LGU, MedicineDispensing::SOURCE_EXTERNAL],
                    true
                )
            )),
        ];
    }

    /** @return array<string,mixed>|null */
    public static function buildConsultationSnapshot(PDO $db, int $consultationId): ?array
    {
        $consultation = ConsultationRecord::find($db, $consultationId);
        if (!$consultation) {
            return null;
        }

        $doctorName = User::documentNameFromParts(
            $consultation['doctor_display_name'] ?? null,
            $consultation['doctor_username'] ?? null
        );

        return [
            'patient_id' => (int) $consultation['patient_id'],
            'bhc_id' => (string) ($consultation['bhc_id'] ?? ''),
            'full_name' => (string) ($consultation['full_name'] ?? ''),
            'consultation_date' => (string) ($consultation['consultation_date'] ?? date('Y-m-d')),
            'diagnosis' => (string) ($consultation['diagnosis'] ?? ''),
            'clinical_notes' => (string) ($consultation['clinical_notes'] ?? ''),
            'doctor_name' => $doctorName,
            'recorded_by_name' => (string) ($consultation['recorded_by_name'] ?? 'Staff'),
        ];
    }

    private static function attachIssuerName(PDO $db, array $snapshot, int $createdBy): array
    {
        if ($createdBy > 0) {
            $issuer = User::findById($db, $createdBy);
            if (!$issuer) {
                return $snapshot;
            }

            $issuerName = ($issuer['role'] ?? '') === 'doctor'
                ? User::documentName($issuer)
                : trim((string) ($issuer['display_name'] ?? $issuer['username'] ?? ''));
            if ($issuerName !== '') {
                $snapshot['issued_by_name'] = $issuerName;
            }
        }
        return $snapshot;
    }

    private static function insertDocument(
        PDO $db,
        string $documentType,
        string $numberCode,
        string $title,
        array $consultation,
        array $snapshot,
        int $createdBy
    ): int {
        $doctorId = !empty($consultation['doctor_id']) ? (int) $consultation['doctor_id'] : null;
        $stmt = $db->prepare(
            "INSERT INTO clinical_documents
             (document_no, document_type, patient_id, consultation_id, doctor_id,
              issued_at, status, title, content_json, created_by)
             VALUES
             (:document_no, :document_type, :patient_id, :consultation_id, :doctor_id,
              NOW(), 'issued', :title, :content_json, :created_by)"
        );
        $stmt->execute([
            ':document_no' => self::nextDocumentNo($db, $numberCode),
            ':document_type' => $documentType,
            ':patient_id' => (int) $consultation['patient_id'],
            ':consultation_id' => (int) $consultation['id'],
            ':doctor_id' => $doctorId,
            ':title' => $title,
            ':content_json' => json_encode($snapshot, JSON_UNESCAPED_UNICODE),
            ':created_by' => $createdBy > 0 ? $createdBy : null,
        ]);

        return (int) $db->lastInsertId();
    }

    public static function issueMedicineReceipt(PDO $db, int $consultationId, int $createdBy): ?int
    {
        $snapshot = self::buildMedicineReceiptSnapshot($db, $consultationId);
        if (!$snapshot) {
            return null;
        }

        $consultation = ConsultationRecord::find($db, $consultationId);
        if (!$consultation) {
            return null;
        }

        $hasDispensed = !empty($snapshot['has_dispensed']);
        $hasExternal = !empty($snapshot['has_external_procurement']);
        $title = self::medicineReceiptTitle($snapshot);
        $snapshot = self::attachIssuerName($db, $snapshot, $createdBy);

        $existing = self::findByConsultation($db, $consultationId);
        if ($existing) {
            self::updateMedicineReceipt($db, (int) $existing['id'], $title, $snapshot, $createdBy);
            self::purgeDuplicateMedicineReceipts($db, $consultationId, (int) $existing['id']);
            return (int) $existing['id'];
        }

        $documentId = self::insertDocument(
            $db,
            'medicine_receipt',
            'RX',
            $title,
            $consultation,
            $snapshot,
            $createdBy
        );
        self::purgeDuplicateMedicineReceipts($db, $consultationId, $documentId);
        return $documentId;
    }

    /** @param array<string,mixed> $snapshot */
    public static function medicineReceiptTitle(array $snapshot): string
    {
        $hasDispensed = !empty($snapshot['has_dispensed']);
        $hasExternal = !empty($snapshot['has_external_procurement']);

        return match (true) {
            $hasDispensed && $hasExternal => 'Medicine prescription and dispensing receipt',
            $hasDispensed => 'Medicine dispensing receipt',
            $hasExternal => 'Medicine prescription (obtain from LGU or pharmacy)',
            default => 'Medicine prescription receipt',
        };
    }

    private static function purgeDuplicateMedicineReceipts(PDO $db, int $consultationId, int $keepId): void
    {
        if ($consultationId <= 0 || $keepId <= 0) {
            return;
        }
        $stmt = $db->prepare(
            "DELETE FROM clinical_documents
             WHERE consultation_id = :consultation_id
               AND document_type = 'medicine_receipt'
               AND id <> :keep_id"
        );
        $stmt->execute([
            ':consultation_id' => $consultationId,
            ':keep_id' => $keepId,
        ]);
    }

    private static function updateMedicineReceipt(
        PDO $db,
        int $documentId,
        string $title,
        array $snapshot,
        int $createdBy
    ): void {
        $stmt = $db->prepare(
            "UPDATE clinical_documents
             SET title = :title,
                 content_json = :content_json,
                 issued_at = NOW(),
                 created_by = COALESCE(:created_by, created_by)
             WHERE id = :id
               AND document_type = 'medicine_receipt'
               AND status = 'issued'"
        );
        $stmt->execute([
            ':title' => $title,
            ':content_json' => json_encode($snapshot, JSON_UNESCAPED_UNICODE),
            ':created_by' => $createdBy > 0 ? $createdBy : null,
            ':id' => $documentId,
        ]);
    }

    /** @param array<string,mixed> $fields */
    public static function issueMedicalCertificate(PDO $db, int $consultationId, int $createdBy, array $fields): ?int
    {
        $existing = self::findByConsultation($db, $consultationId, 'medical_certificate');
        if ($existing) {
            return (int) $existing['id'];
        }

        $consultation = ConsultationRecord::find($db, $consultationId);
        if (!$consultation) {
            return null;
        }

        $purpose = trim((string) ($fields['purpose'] ?? ''));
        if ($purpose === '') {
            return null;
        }

        $snapshot = self::buildConsultationSnapshot($db, $consultationId);
        if (!$snapshot) {
            return null;
        }

        $restDays = trim((string) ($fields['rest_days'] ?? ''));
        $remarks = trim((string) ($fields['remarks'] ?? ''));
        $snapshot['purpose'] = $purpose;
        $snapshot['rest_days'] = $restDays !== '' ? $restDays : null;
        $snapshot['remarks'] = $remarks;
        $snapshot = self::attachIssuerName($db, $snapshot, $createdBy);

        return self::insertDocument(
            $db,
            'medical_certificate',
            'MC',
            'Medical certificate',
            $consultation,
            $snapshot,
            $createdBy
        );
    }

    /** @param array<string,mixed> $fields */
    public static function issueRecommendation(PDO $db, int $consultationId, int $createdBy, array $fields): ?int
    {
        $existing = self::findByConsultation($db, $consultationId, 'recommendation');
        if ($existing) {
            return (int) $existing['id'];
        }

        $consultation = ConsultationRecord::find($db, $consultationId);
        if (!$consultation) {
            return null;
        }

        $recommendationText = trim((string) ($fields['recommendation_text'] ?? ''));
        if ($recommendationText === '') {
            return null;
        }

        $snapshot = self::buildConsultationSnapshot($db, $consultationId);
        if (!$snapshot) {
            return null;
        }

        $snapshot['recommendation_title'] = trim((string) ($fields['recommendation_title'] ?? 'Clinical recommendation')) ?: 'Clinical recommendation';
        $snapshot['recommendation_text'] = $recommendationText;
        $snapshot['follow_up_notes'] = trim((string) ($fields['follow_up_notes'] ?? ''));
        $snapshot = self::attachIssuerName($db, $snapshot, $createdBy);

        return self::insertDocument(
            $db,
            'recommendation',
            'REC',
            'Clinical recommendation',
            $consultation,
            $snapshot,
            $createdBy
        );
    }

    /** @param array<string,mixed> $fields */
    public static function issueReferral(PDO $db, int $consultationId, int $createdBy, array $fields): ?int
    {
        $existing = self::findByConsultation($db, $consultationId, 'referral');
        if ($existing) {
            return (int) $existing['id'];
        }

        $consultation = ConsultationRecord::find($db, $consultationId);
        if (!$consultation) {
            return null;
        }

        $referredTo = trim((string) ($fields['referred_to'] ?? ''));
        $reason = trim((string) ($fields['reason'] ?? ''));
        if ($referredTo === '' || $reason === '') {
            return null;
        }

        $snapshot = self::buildConsultationSnapshot($db, $consultationId);
        if (!$snapshot) {
            return null;
        }

        $clinicalSummary = trim((string) ($fields['clinical_summary'] ?? ''));
        if ($clinicalSummary === '') {
            $clinicalSummary = trim((string) ($snapshot['diagnosis'] ?? ''));
            if (($snapshot['clinical_notes'] ?? '') !== '') {
                $clinicalSummary .= ($clinicalSummary !== '' ? ' — ' : '')
                    . trim((string) $snapshot['clinical_notes']);
            }
        }

        $snapshot['referred_to'] = $referredTo;
        $snapshot['reason'] = $reason;
        $snapshot['clinical_summary'] = $clinicalSummary;
        $snapshot = self::attachIssuerName($db, $snapshot, $createdBy);

        return self::insertDocument(
            $db,
            'referral',
            'REF',
            'Referral letter',
            $consultation,
            $snapshot,
            $createdBy
        );
    }

    /** @return array{created:int, skipped:int} */
    public static function backfillMedicineReceipts(PDO $db, ?int $patientId = null): array
    {
        $sql =
            "SELECT DISTINCT md.consultation_id
             FROM medicine_dispensings md
             WHERE md.consultation_id IS NOT NULL
               AND (md.dispense_status = 'dispensed'
                    OR md.receipt_issued = 1
                    OR md.procurement_source IN ('lgu','external'))";
        if ($patientId !== null) {
            $sql .= ' AND md.patient_id = ' . (int) $patientId;
        }
        $sql .= ' ORDER BY md.consultation_id ASC';

        $created = 0;
        $skipped = 0;
        foreach ($db->query($sql)->fetchAll(PDO::FETCH_COLUMN) as $consultationId) {
            $cid = (int) $consultationId;
            if ($cid <= 0) {
                continue;
            }
            if (self::findByConsultation($db, $cid)) {
                $skipped++;
                continue;
            }
            $id = self::issueMedicineReceipt($db, $cid, 0);
            if ($id) {
                $created++;
            }
        }
        return ['created' => $created, 'skipped' => $skipped];
    }

    public static function typeLabel(string $type): string
    {
        return match ($type) {
            'medicine_receipt' => 'Medicine receipt',
            'medical_certificate' => 'Medical certificate',
            'referral' => 'Referral',
            'recommendation' => 'Recommendation',
            default => ucfirst(str_replace('_', ' ', $type)),
        };
    }
}
