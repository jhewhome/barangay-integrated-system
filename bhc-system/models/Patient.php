<?php

class Patient
{
    public const RESIDENCY_PENDING = 'pending';
    public const RESIDENCY_VERIFIED = 'verified';
    public const RESIDENCY_NON_RESIDENT = 'non_resident';

    /** @return array<string,string> */
    public static function residencyProofTypes(): array
    {
        return [
            'barangay_id' => 'Barangay ID / certificate of residency',
            'voters_id' => "Voter's ID / COMELEC certificate",
            'valid_id_address' => 'Valid ID with Balong Bato address',
            'utility_bill' => 'Utility bill (Balong Bato address)',
            'other' => 'Other proof of residence',
        ];
    }

    public static function normalizeResidencyStatus(string $status): string
    {
        return match ($status) {
            self::RESIDENCY_VERIFIED, self::RESIDENCY_NON_RESIDENT => $status,
            default => self::RESIDENCY_PENDING,
        };
    }

    public static function residencyLabel(string $status): string
    {
        return match (self::normalizeResidencyStatus($status)) {
            self::RESIDENCY_VERIFIED => 'Verified resident',
            self::RESIDENCY_NON_RESIDENT => 'Not verified',
            default => 'Pending verification',
        };
    }

    public static function residencyProofLabel(?string $type): string
    {
        if ($type === null || $type === '') {
            return '—';
        }
        return self::residencyProofTypes()[$type] ?? ucfirst(str_replace('_', ' ', $type));
    }

    /** @param array<string,mixed> $patient */
    public static function isArchived(array $patient): bool
    {
        return !empty($patient['archived_at']);
    }

    /** @param array<string,mixed> $patient */
    public static function requiresResidencyVerification(array $patient): bool
    {
        return (int) ($patient['residency_verification_required'] ?? 1) === 1;
    }

    /** @param array<string,mixed> $patient */
    public static function residencyDisplayLabel(array $patient): string
    {
        $status = self::normalizeResidencyStatus((string) ($patient['residency_status'] ?? ''));
        if ($status === self::RESIDENCY_PENDING && !self::requiresResidencyVerification($patient)) {
            return 'Existing record';
        }

        return self::residencyLabel($status);
    }

    /** @param array<string,mixed> $patient */
    public static function canReceiveServices(array $patient): bool
    {
        if (self::isArchived($patient)) {
            return false;
        }

        $status = self::normalizeResidencyStatus((string) ($patient['residency_status'] ?? ''));
        if ($status === self::RESIDENCY_NON_RESIDENT) {
            return false;
        }
        if ($status === self::RESIDENCY_VERIFIED) {
            return true;
        }

        return !self::requiresResidencyVerification($patient);
    }

    public static function buildFullName(string $first, ?string $middle, string $last, ?string $suffix = null): string
    {
        $parts = [trim($first)];
        $middle = trim((string) $middle);
        if ($middle !== '') {
            $parts[] = $middle;
        }
        $parts[] = trim($last);
        $name = implode(' ', array_filter($parts, fn ($p) => $p !== ''));
        $suffix = trim((string) $suffix);
        if ($suffix !== '') {
            $name .= ($suffix[0] === ',' ? ' ' : ' ') . $suffix;
        }
        return trim($name);
    }

    /** @return array{errors: array<int,string>, data: array<string,mixed>} */
    public static function validateForm(array $input, bool $requireResidency = false): array
    {
        $first = trim((string) ($input['first_name'] ?? ''));
        $middle = trim((string) ($input['middle_name'] ?? ''));
        $last = trim((string) ($input['last_name'] ?? ''));
        $suffix = trim((string) ($input['suffix'] ?? ''));
        $sex = trim((string) ($input['sex'] ?? ''));
        $birthdate = trim((string) ($input['birthdate'] ?? ''));
        $contact = trim((string) ($input['contact_number'] ?? ''));
        $address = trim((string) ($input['address'] ?? ''));
        $barangay = trim((string) ($input['barangay'] ?? ''));
        $civil = trim((string) ($input['civil_status'] ?? ''));
        $philhealth = trim((string) ($input['philhealth_no'] ?? ''));
        $ecName = trim((string) ($input['emergency_contact_name'] ?? ''));
        $ecPhone = trim((string) ($input['emergency_contact_phone'] ?? ''));
        $notes = trim((string) ($input['notes'] ?? ''));

        $errors = [];
        if ($first === '') {
            $errors[] = 'First name is required.';
        }
        if ($last === '') {
            $errors[] = 'Last name is required.';
        }
        if (!in_array($sex, ['M', 'F'], true)) {
            $errors[] = 'Sex is required.';
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $birthdate)) {
            $errors[] = 'Date of birth is required.';
        }
        if ($civil !== '' && !in_array($civil, ['single', 'married', 'widowed', 'separated'], true)) {
            $errors[] = 'Invalid civil status.';
        }

        $residencyStatus = self::normalizeResidencyStatus((string) ($input['residency_status'] ?? ''));
        $residencyProofType = trim((string) ($input['residency_proof_type'] ?? ''));
        $residencyProofNotes = trim((string) ($input['residency_proof_notes'] ?? ''));

        if ($requireResidency) {
            $decision = (string) ($input['residency_status'] ?? '');
            if (!in_array($decision, [self::RESIDENCY_VERIFIED, self::RESIDENCY_NON_RESIDENT], true)) {
                $errors[] = 'Confirm whether Balong Bato residency was verified from a supporting document.';
            }
        }

        if ($residencyStatus === self::RESIDENCY_VERIFIED) {
            if ($residencyProofType === '' || !isset(self::residencyProofTypes()[$residencyProofType])) {
                $errors[] = 'Select the residency document presented.';
            }
            $barangay = 'Balong Bato';
        }

        if ($residencyStatus === self::RESIDENCY_NON_RESIDENT && $residencyProofNotes === '' && trim($notes) === '') {
            $errors[] = 'Add a short note explaining why residency was not verified.';
        }

        $data = [
            'first_name' => $first,
            'middle_name' => $middle,
            'last_name' => $last,
            'suffix' => $suffix,
            'full_name' => self::buildFullName($first, $middle, $last, $suffix),
            'sex' => $sex,
            'birthdate' => $birthdate,
            'contact_number' => $contact,
            'address' => $address,
            'barangay' => $barangay !== '' ? $barangay : 'Balong Bato',
            'civil_status' => $civil !== '' ? $civil : null,
            'philhealth_no' => $philhealth,
            'emergency_contact_name' => $ecName,
            'emergency_contact_phone' => $ecPhone,
            'notes' => $notes,
            'residency_status' => $residencyStatus,
            'residency_proof_type' => $residencyProofType !== '' ? $residencyProofType : null,
            'residency_proof_notes' => $residencyProofNotes !== '' ? $residencyProofNotes : null,
        ];

        return ['errors' => $errors, 'data' => $data];
    }

    /** @param array<string,mixed> $data */
    public static function applyResidencyVerificationMeta(array $data, int $userId, ?array $existing = null): array
    {
        $status = self::normalizeResidencyStatus((string) ($data['residency_status'] ?? self::RESIDENCY_PENDING));
        $data['residency_status'] = $status;

        if ($status === self::RESIDENCY_VERIFIED) {
            $data['barangay'] = 'Balong Bato';
            $data['residency_verified_at'] = date('Y-m-d H:i:s');
            $data['residency_verified_by'] = $userId > 0 ? $userId : null;
            return $data;
        }

        if ($status === self::RESIDENCY_PENDING && $existing) {
            $data['residency_verified_at'] = $existing['residency_verified_at'] ?? null;
            $data['residency_verified_by'] = $existing['residency_verified_by'] ?? null;
            return $data;
        }

        $data['residency_verified_at'] = null;
        $data['residency_verified_by'] = null;
        return $data;
    }

    public static function paginate(PDO $db, int $limit = 50, int $offset = 0, string $q = '', string $residency = '', string $registry = 'active'): array
    {
        $where = self::listWhereSql($q, $residency, $registry);
        $stmt = $db->prepare(
            "SELECT id, bhc_id, first_name, middle_name, last_name, suffix, full_name, sex, birthdate,
                    contact_number, barangay, civil_status, residency_status, residency_proof_type,
                    residency_verification_required, archived_at, created_at
             FROM patients
             {$where['sql']}
             ORDER BY id DESC
             LIMIT :limit OFFSET :offset"
        );
        foreach ($where['params'] as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function countAll(PDO $db, string $q = '', string $residency = '', string $registry = 'active'): int
    {
        $where = self::listWhereSql($q, $residency, $registry);
        $stmt = $db->prepare("SELECT COUNT(*) FROM patients {$where['sql']}");
        foreach ($where['params'] as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    /** @return array{sql: string, params: array<string, string>} */
    private static function listWhereSql(string $q, string $residency = '', string $registry = 'active'): array
    {
        $clauses = [];
        $params = [];

        $registryFilter = strtolower(trim($registry));
        if ($registryFilter === 'archived') {
            $clauses[] = 'archived_at IS NOT NULL';
        } elseif ($registryFilter !== 'all') {
            $clauses[] = 'archived_at IS NULL';
        }

        $q = trim($q);
        if ($q !== '') {
            $clauses[] = '(
                full_name LIKE :q OR first_name LIKE :q OR middle_name LIKE :q OR last_name LIKE :q
                OR bhc_id LIKE :q OR contact_number LIKE :q OR philhealth_no LIKE :q
            )';
            $params[':q'] = '%' . $q . '%';
        }

        $residencyFilter = trim($residency);
        if ($residencyFilter !== '') {
            $normalizedResidency = self::normalizeResidencyStatus($residencyFilter);
            if (in_array($normalizedResidency, [self::RESIDENCY_VERIFIED, self::RESIDENCY_NON_RESIDENT, self::RESIDENCY_PENDING], true)) {
                $clauses[] = 'residency_status = :residency_status';
                $params[':residency_status'] = $normalizedResidency;
            }
        }

        if (empty($clauses)) {
            return ['sql' => '', 'params' => []];
        }

        return [
            'sql' => 'WHERE ' . implode(' AND ', $clauses),
            'params' => $params,
        ];
    }

    public static function archive(PDO $db, int $id, int $userId): bool
    {
        $stmt = $db->prepare(
            "UPDATE patients
             SET archived_at = :archived_at, archived_by = :archived_by
             WHERE id = :id AND archived_at IS NULL"
        );
        $stmt->execute([
            ':archived_at' => date('Y-m-d H:i:s'),
            ':archived_by' => $userId > 0 ? $userId : null,
            ':id' => $id,
        ]);

        return $stmt->rowCount() > 0;
    }

    public static function restore(PDO $db, int $id): bool
    {
        $stmt = $db->prepare(
            "UPDATE patients
             SET archived_at = NULL, archived_by = NULL
             WHERE id = :id AND archived_at IS NOT NULL"
        );
        $stmt->execute([':id' => $id]);

        return $stmt->rowCount() > 0;
    }

    public static function create(PDO $db, array $data): int
    {
        $stmt = $db->prepare(
            "INSERT INTO patients
             (bhc_id, gawad_resident_id, first_name, middle_name, last_name, suffix, full_name, sex, birthdate, contact_number,
              address, barangay, civil_status, philhealth_no, emergency_contact_name, emergency_contact_phone, notes,
              residency_status, residency_proof_type, residency_proof_notes, residency_verified_at, residency_verified_by,
              residency_verification_required)
             VALUES
             (:bhc_id, :gawad_resident_id, :first_name, :middle_name, :last_name, :suffix, :full_name, :sex, :birthdate, :contact_number,
              :address, :barangay, :civil_status, :philhealth_no, :emergency_contact_name, :emergency_contact_phone, :notes,
              :residency_status, :residency_proof_type, :residency_proof_notes, :residency_verified_at, :residency_verified_by,
              :residency_verification_required)"
        );
        $params = self::bindPatientFields($data);
        $params[':bhc_id'] = $data['bhc_id'] ?? null;
        $params[':gawad_resident_id'] = !empty($data['gawad_resident_id']) ? (string) $data['gawad_resident_id'] : null;
        $params[':residency_verification_required'] = !empty($data['residency_verification_required']) ? 1 : 0;
        $stmt->execute($params);
        return (int) $db->lastInsertId();
    }

    public static function update(PDO $db, int $id, array $data): void
    {
        $stmt = $db->prepare(
            "UPDATE patients SET
              first_name = :first_name, middle_name = :middle_name, last_name = :last_name, suffix = :suffix,
              full_name = :full_name, sex = :sex, birthdate = :birthdate, contact_number = :contact_number,
              address = :address, barangay = :barangay, civil_status = :civil_status, philhealth_no = :philhealth_no,
              emergency_contact_name = :emergency_contact_name, emergency_contact_phone = :emergency_contact_phone,
              notes = :notes, residency_status = :residency_status, residency_proof_type = :residency_proof_type,
              residency_proof_notes = :residency_proof_notes, residency_verified_at = :residency_verified_at,
              residency_verified_by = :residency_verified_by
             WHERE id = :id"
        );
        $params = self::bindPatientFields($data);
        $params[':id'] = $id;
        $stmt->execute($params);
    }

    private static function bindPatientFields(array $data): array
    {
        return [
            ':first_name' => $data['first_name'],
            ':middle_name' => $data['middle_name'] ?: null,
            ':last_name' => $data['last_name'],
            ':suffix' => $data['suffix'] ?: null,
            ':full_name' => $data['full_name'],
            ':sex' => $data['sex'],
            ':birthdate' => $data['birthdate'],
            ':contact_number' => $data['contact_number'] ?: null,
            ':address' => $data['address'] ?: null,
            ':barangay' => $data['barangay'] ?: null,
            ':civil_status' => $data['civil_status'] ?: null,
            ':philhealth_no' => $data['philhealth_no'] ?: null,
            ':emergency_contact_name' => $data['emergency_contact_name'] ?: null,
            ':emergency_contact_phone' => $data['emergency_contact_phone'] ?: null,
            ':notes' => $data['notes'] ?: null,
            ':residency_status' => self::normalizeResidencyStatus((string) ($data['residency_status'] ?? self::RESIDENCY_PENDING)),
            ':residency_proof_type' => ($data['residency_proof_type'] ?? '') !== '' ? $data['residency_proof_type'] : null,
            ':residency_proof_notes' => ($data['residency_proof_notes'] ?? '') !== '' ? $data['residency_proof_notes'] : null,
            ':residency_verified_at' => ($data['residency_verified_at'] ?? '') !== '' ? $data['residency_verified_at'] : null,
            ':residency_verified_by' => !empty($data['residency_verified_by']) ? (int) $data['residency_verified_by'] : null,
        ];
    }

    /** @param array<string,mixed> $data */
    public static function prepareNewPatientData(array $data): array
    {
        $data['residency_verification_required'] = 1;

        return $data;
    }

    public static function find(PDO $db, int $id): ?array
    {
        $stmt = $db->prepare("SELECT * FROM patients WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function findByBhcId(PDO $db, string $bhcId): ?array
    {
        $stmt = $db->prepare("SELECT * FROM patients WHERE bhc_id = :bhc_id LIMIT 1");
        $stmt->execute([':bhc_id' => $bhcId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function findByGawadResidentId(PDO $db, string $gawadResidentId): ?array
    {
        $gawadResidentId = trim($gawadResidentId);
        if ($gawadResidentId === '') {
            return null;
        }

        $stmt = $db->prepare("SELECT * FROM patients WHERE gawad_resident_id = :gid LIMIT 1");
        $stmt->execute([':gid' => $gawadResidentId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Strong duplicate check: same first name, last name, and birthdate (active records only).
     *
     * @return array<string,mixed>|null
     */
    public static function findIdentityDuplicate(
        PDO $db,
        string $firstName,
        string $lastName,
        string $birthdate,
        ?int $excludeId = null
    ): ?array {
        $first = self::normalizeIdentityPart($firstName);
        $last = self::normalizeIdentityPart($lastName);
        $birthdate = trim($birthdate);

        if ($first === '' || $last === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $birthdate)) {
            return null;
        }

        $sql = "SELECT id, bhc_id, first_name, middle_name, last_name, suffix, full_name, sex, birthdate,
                       contact_number, gawad_resident_id, archived_at
                FROM patients
                WHERE archived_at IS NULL
                  AND birthdate = :birthdate
                  AND LOWER(TRIM(first_name)) = :first_name
                  AND LOWER(TRIM(last_name)) = :last_name";
        if ($excludeId !== null && $excludeId > 0) {
            $sql .= ' AND id != :exclude_id';
        }
        $sql .= ' ORDER BY id ASC LIMIT 1';

        $stmt = $db->prepare($sql);
        $stmt->bindValue(':birthdate', $birthdate, PDO::PARAM_STR);
        $stmt->bindValue(':first_name', $first, PDO::PARAM_STR);
        $stmt->bindValue(':last_name', $last, PDO::PARAM_STR);
        if ($excludeId !== null && $excludeId > 0) {
            $stmt->bindValue(':exclude_id', $excludeId, PDO::PARAM_INT);
        }
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /** @return array{ok: bool, error?: string} */
    public static function linkGawadResident(PDO $db, int $patientId, string $gawadResidentId): array
    {
        $gawadResidentId = trim($gawadResidentId);
        if (!GawadIntegration::isValidResidentId($gawadResidentId)) {
            return ['ok' => false, 'error' => 'Invalid Gawad resident reference.'];
        }

        $patient = self::find($db, $patientId);
        if (!$patient) {
            return ['ok' => false, 'error' => 'Patient not found.'];
        }
        if (self::isArchived($patient)) {
            return ['ok' => false, 'error' => 'Cannot link an archived patient. Restore the record first.'];
        }

        $existingGawad = trim((string) ($patient['gawad_resident_id'] ?? ''));
        if ($existingGawad !== '') {
            if ($existingGawad === $gawadResidentId) {
                return ['ok' => true];
            }

            return ['ok' => false, 'error' => 'This patient is already linked to a different Gawad resident.'];
        }

        $linkedElsewhere = self::findByGawadResidentId($db, $gawadResidentId);
        if ($linkedElsewhere && (int) $linkedElsewhere['id'] !== $patientId) {
            return [
                'ok' => false,
                'error' => 'This Gawad resident is already linked to BHC ID ' . ($linkedElsewhere['bhc_id'] ?? '') . '.',
            ];
        }

        $stmt = $db->prepare(
            'UPDATE patients SET gawad_resident_id = :gid WHERE id = :id AND (gawad_resident_id IS NULL OR gawad_resident_id = \'\')'
        );
        $stmt->execute([':gid' => $gawadResidentId, ':id' => $patientId]);
        if ($stmt->rowCount() === 0) {
            return ['ok' => false, 'error' => 'Could not link Gawad resident to this patient.'];
        }

        return ['ok' => true];
    }

    /** @param array<string,mixed> $patient */
    public static function identityDuplicateMessage(array $patient): string
    {
        $label = trim((string) ($patient['full_name'] ?? ''));
        if ($label === '') {
            $label = self::buildFullName(
                (string) ($patient['first_name'] ?? ''),
                (string) ($patient['middle_name'] ?? ''),
                (string) ($patient['last_name'] ?? ''),
                (string) ($patient['suffix'] ?? '')
            );
        }

        return 'A patient with the same name and date of birth already exists ('
            . ($patient['bhc_id'] ?? 'BHC record')
            . ' — '
            . $label
            . '). Use the existing record instead of creating a duplicate.';
    }

    private static function normalizeIdentityPart(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return $value;
    }

    public static function search(PDO $db, string $q, ?string $birthdate = null, ?string $contact = null, int $limit = 8): array
    {
        $q = trim($q);
        $contact = $contact !== null ? trim($contact) : null;
        if ($q === '' && ($contact === null || $contact === '')) {
            return [];
        }

        $like = $q === '' ? null : ('%' . $q . '%');
        $contactLike = ($contact === null || $contact === '') ? null : ('%' . $contact . '%');
        $hasDob = $birthdate && preg_match('/^\d{4}-\d{2}-\d{2}$/', $birthdate);

        $nameClause = '(
            (:q IS NOT NULL AND (
                full_name LIKE :q OR first_name LIKE :q OR middle_name LIKE :q OR last_name LIKE :q OR bhc_id LIKE :q
            ))
            OR (:contact IS NOT NULL AND contact_number LIKE :contact)
        )';

        $sql = "SELECT id, bhc_id, first_name, middle_name, last_name, suffix, full_name, sex, birthdate
                FROM patients WHERE archived_at IS NULL AND {$nameClause}";
        if ($hasDob) {
            $sql .= ' AND birthdate = :birthdate';
        }
        $sql .= ' ORDER BY id DESC LIMIT :limit';

        $stmt = $db->prepare($sql);
        $stmt->bindValue(':q', $like, $like === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':contact', $contactLike, $contactLike === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        if ($hasDob) {
            $stmt->bindValue(':birthdate', $birthdate, PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function nextBhcId(PDO $db): string
    {
        $stmt = $db->query("SELECT MAX(id) AS max_id FROM patients");
        $max = (int) ($stmt->fetch(PDO::FETCH_ASSOC)['max_id'] ?? 0);
        return 'BHC-' . str_pad((string) ($max + 1), 6, '0', STR_PAD_LEFT);
    }

    public static function splitLegacyFullName(string $fullName): array
    {
        $fullName = trim(preg_replace('/\s+/', ' ', $fullName));
        if ($fullName === '') {
            return ['first_name' => '', 'middle_name' => '', 'last_name' => '', 'suffix' => ''];
        }
        $parts = explode(' ', $fullName);
        if (count($parts) === 1) {
            return ['first_name' => $parts[0], 'middle_name' => '', 'last_name' => $parts[0], 'suffix' => ''];
        }
        $suffix = '';
        $last = array_pop($parts);
        if (preg_match('/^(Jr\.?|Sr\.?|III|IV|II)$/i', $last) && count($parts) >= 1) {
            $suffix = $last;
            $last = array_pop($parts);
        }
        $first = array_shift($parts);
        $middle = implode(' ', $parts);
        return [
            'first_name' => $first,
            'middle_name' => $middle,
            'last_name' => $last,
            'suffix' => $suffix,
        ];
    }
}
