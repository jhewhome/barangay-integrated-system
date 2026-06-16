<?php
class Database {
    private $host = "localhost";
    private $db_name = "brgy_health_db";
    private $username = "root";
    private $password = "";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->ensureSchema($this->conn);
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        return $this->conn;
    }

    private function ensureSchema(PDO $db): void
    {
        // Ensure base tables exist (fresh DB support)
        $db->exec(
            "CREATE TABLE IF NOT EXISTS patients (
              id INT AUTO_INCREMENT PRIMARY KEY,
              bhc_id VARCHAR(20) NULL,
              full_name VARCHAR(255) NOT NULL,
              sex ENUM('M','F') NULL,
              birthdate DATE NULL,
              contact_number VARCHAR(30) NULL,
              created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )"
        );

        // Patients: add bhc_id if missing (older schema)
        $cols = $db->query("SHOW COLUMNS FROM patients")->fetchAll(PDO::FETCH_ASSOC);
        $hasBhcId = false;
        $hasSex = false;
        $hasBirthdate = false;
        foreach ($cols as $c) {
            if (($c['Field'] ?? '') === 'bhc_id') {
                $hasBhcId = true;
            }
            if (($c['Field'] ?? '') === 'sex') {
                $hasSex = true;
            }
            if (($c['Field'] ?? '') === 'birthdate') {
                $hasBirthdate = true;
            }
        }

        if (!$hasBhcId) {
            $db->exec("ALTER TABLE patients ADD COLUMN bhc_id VARCHAR(20) NULL AFTER id");
        }
        if (!$hasSex) {
            $db->exec("ALTER TABLE patients ADD COLUMN sex ENUM('M','F') NULL AFTER full_name");
        }
        if (!$hasBirthdate) {
            $db->exec("ALTER TABLE patients ADD COLUMN birthdate DATE NULL AFTER sex");
        }

        // Expanded patient registry fields
        $colMap = [];
        foreach ($db->query("SHOW COLUMNS FROM patients")->fetchAll(PDO::FETCH_ASSOC) as $c) {
            $colMap[$c['Field'] ?? ''] = true;
        }
        $patientAdds = [
            'first_name' => "ALTER TABLE patients ADD COLUMN first_name VARCHAR(100) NULL AFTER bhc_id",
            'middle_name' => "ALTER TABLE patients ADD COLUMN middle_name VARCHAR(100) NULL AFTER first_name",
            'last_name' => "ALTER TABLE patients ADD COLUMN last_name VARCHAR(100) NULL AFTER middle_name",
            'suffix' => "ALTER TABLE patients ADD COLUMN suffix VARCHAR(20) NULL AFTER last_name",
            'address' => "ALTER TABLE patients ADD COLUMN address VARCHAR(500) NULL AFTER contact_number",
            'barangay' => "ALTER TABLE patients ADD COLUMN barangay VARCHAR(100) NULL AFTER address",
            'civil_status' => "ALTER TABLE patients ADD COLUMN civil_status ENUM('single','married','widowed','separated') NULL AFTER barangay",
            'philhealth_no' => "ALTER TABLE patients ADD COLUMN philhealth_no VARCHAR(30) NULL AFTER civil_status",
            'emergency_contact_name' => "ALTER TABLE patients ADD COLUMN emergency_contact_name VARCHAR(150) NULL AFTER philhealth_no",
            'emergency_contact_phone' => "ALTER TABLE patients ADD COLUMN emergency_contact_phone VARCHAR(30) NULL AFTER emergency_contact_name",
            'notes' => "ALTER TABLE patients ADD COLUMN notes TEXT NULL AFTER emergency_contact_phone",
            'residency_status' => "ALTER TABLE patients ADD COLUMN residency_status ENUM('pending','verified','non_resident') NOT NULL DEFAULT 'pending' AFTER notes",
            'residency_proof_type' => "ALTER TABLE patients ADD COLUMN residency_proof_type VARCHAR(50) NULL AFTER residency_status",
            'residency_proof_notes' => "ALTER TABLE patients ADD COLUMN residency_proof_notes VARCHAR(255) NULL AFTER residency_proof_type",
            'residency_verified_at' => "ALTER TABLE patients ADD COLUMN residency_verified_at DATETIME NULL AFTER residency_proof_notes",
            'residency_verified_by' => "ALTER TABLE patients ADD COLUMN residency_verified_by INT NULL AFTER residency_verified_at",
            'residency_verification_required' => "ALTER TABLE patients ADD COLUMN residency_verification_required TINYINT(1) NOT NULL DEFAULT 1 AFTER residency_verified_by",
            'archived_at' => "ALTER TABLE patients ADD COLUMN archived_at DATETIME NULL AFTER residency_verification_required",
            'archived_by' => "ALTER TABLE patients ADD COLUMN archived_by INT NULL AFTER archived_at",
        ];
        foreach ($patientAdds as $field => $sql) {
            if (empty($colMap[$field])) {
                $db->exec($sql);
                if ($field === 'residency_verification_required') {
                    $db->exec('UPDATE patients SET residency_verification_required = 0');
                }
            }
        }

        if (empty($colMap['residency_verified_by'])) {
            try {
                $db->exec(
                    "ALTER TABLE patients
                     ADD CONSTRAINT fk_patient_residency_verifier
                     FOREIGN KEY (residency_verified_by) REFERENCES users(id)"
                );
            } catch (PDOException $e) {
                // ignore if key already exists
            }
        }

        if (empty($colMap['archived_by'])) {
            try {
                $db->exec(
                    "ALTER TABLE patients
                     ADD CONSTRAINT fk_patient_archived_by
                     FOREIGN KEY (archived_by) REFERENCES users(id)"
                );
            } catch (PDOException $e) {
                // ignore if key already exists
            }
        }

        if (empty($colMap['gawad_resident_id'])) {
            $db->exec("ALTER TABLE patients ADD COLUMN gawad_resident_id VARCHAR(24) NULL AFTER bhc_id");
            try {
                $db->exec("ALTER TABLE patients ADD UNIQUE KEY uq_patients_gawad_resident_id (gawad_resident_id)");
            } catch (PDOException $e) {
                // ignore if key already exists
            }
        }

        // Backfill name parts from legacy full_name
        $backfill = $db->query(
            "SELECT id, full_name FROM patients
             WHERE (first_name IS NULL OR first_name = '')
               AND full_name IS NOT NULL AND TRIM(full_name) <> ''"
        )->fetchAll(PDO::FETCH_ASSOC);
        if ($backfill) {
            require_once dirname(__DIR__) . '/models/Patient.php';
            $upd = $db->prepare(
                "UPDATE patients SET first_name = :first_name, middle_name = :middle_name,
                 last_name = :last_name, suffix = :suffix
                 WHERE id = :id"
            );
            foreach ($backfill as $row) {
                $parts = Patient::splitLegacyFullName((string) $row['full_name']);
                $upd->execute([
                    ':first_name' => $parts['first_name'] ?: 'Unknown',
                    ':middle_name' => $parts['middle_name'] ?: null,
                    ':last_name' => $parts['last_name'] ?: 'Unknown',
                    ':suffix' => $parts['suffix'] ?: null,
                    ':id' => (int) $row['id'],
                ]);
            }
        }
        $db->exec(
            "UPDATE patients SET barangay = 'Balong Bato' WHERE barangay IS NULL OR TRIM(barangay) = ''"
        );

        // Backfill + enforce (safe to run repeatedly)
        $db->exec("UPDATE patients SET bhc_id = CONCAT('BHC-', LPAD(id, 6, '0')) WHERE bhc_id IS NULL OR bhc_id = ''");
        $db->exec("ALTER TABLE patients MODIFY bhc_id VARCHAR(20) NOT NULL");
        try {
            $db->exec("ALTER TABLE patients ADD UNIQUE KEY uq_patients_bhc_id (bhc_id)");
        } catch (PDOException $e) {
            // ignore if key already exists
        }

        // Enforce required clinical minimums going forward (leave existing rows nullable if already stored)
        // If you want to make these NOT NULL in DB later, we can add a migration + cleanup.

        // Stations
        $db->exec(
            "CREATE TABLE IF NOT EXISTS stations (
              id INT AUTO_INCREMENT PRIMARY KEY,
              code VARCHAR(10) NOT NULL UNIQUE,
              name VARCHAR(100) NOT NULL,
              sort_order INT NOT NULL DEFAULT 0,
              is_active TINYINT(1) NOT NULL DEFAULT 1
            )"
        );

        // Queue tickets
        $db->exec(
            "CREATE TABLE IF NOT EXISTS queue_tickets (
              id INT AUTO_INCREMENT PRIMARY KEY,
              station_id INT NOT NULL,
              patient_id INT NOT NULL,
              ticket_no VARCHAR(20) NOT NULL,
              reason VARCHAR(255) NULL,
              status ENUM('waiting','serving','done','skipped') NOT NULL DEFAULT 'waiting',
              created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              called_at DATETIME NULL,
              completed_at DATETIME NULL,
              INDEX idx_queue_station_day (station_id, created_at),
              INDEX idx_queue_status (status),
              CONSTRAINT fk_queue_station FOREIGN KEY (station_id) REFERENCES stations(id),
              CONSTRAINT fk_queue_patient FOREIGN KEY (patient_id) REFERENCES patients(id)
            )"
        );

        // Add missing reason column on older installs
        try {
            $cols2 = $db->query("SHOW COLUMNS FROM queue_tickets")->fetchAll(PDO::FETCH_ASSOC);
            $hasReason = false;
            foreach ($cols2 as $c2) {
                if (($c2['Field'] ?? '') === 'reason') {
                    $hasReason = true;
                    break;
                }
            }
            if (!$hasReason) {
                $db->exec("ALTER TABLE queue_tickets ADD COLUMN reason VARCHAR(255) NULL AFTER ticket_no");
            }
        } catch (PDOException $e) {
            // ignore if table not ready yet
        }

        // Seed default stations (idempotent)
        $db->exec(
            "INSERT INTO stations (code, name, sort_order, is_active) VALUES
              ('RG', 'Registration', 1, 1),
              ('TR', 'Triage / Vitals', 2, 1),
              ('CN', 'Consultation', 3, 1),
              ('PH', 'Pharmacy', 4, 1)
             ON DUPLICATE KEY UPDATE
              name = VALUES(name),
              sort_order = VALUES(sort_order),
              is_active = VALUES(is_active)"
        );

        // Users (minimal auth)
        $db->exec(
            "CREATE TABLE IF NOT EXISTS users (
              id INT AUTO_INCREMENT PRIMARY KEY,
              username VARCHAR(50) NOT NULL UNIQUE,
              password_hash VARCHAR(255) NOT NULL,
              role ENUM('admin','staff','doctor') NOT NULL DEFAULT 'staff',
              is_active TINYINT(1) NOT NULL DEFAULT 1,
              display_name VARCHAR(100) NULL,
              created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )"
        );

        try {
            $db->exec("ALTER TABLE users MODIFY role ENUM('admin','staff','doctor') NOT NULL DEFAULT 'staff'");
        } catch (PDOException $e) {
            // ignore if already applied
        }
        $userCols = [];
        foreach ($db->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_ASSOC) as $uc) {
            $userCols[$uc['Field'] ?? ''] = true;
        }
        if (empty($userCols['display_name'])) {
            $db->exec("ALTER TABLE users ADD COLUMN display_name VARCHAR(100) NULL AFTER role");
        }

        $qtCols = [];
        foreach ($db->query("SHOW COLUMNS FROM queue_tickets")->fetchAll(PDO::FETCH_ASSOC) as $qc) {
            $qtCols[$qc['Field'] ?? ''] = true;
        }
        if (empty($qtCols['assigned_doctor_id'])) {
            $db->exec("ALTER TABLE queue_tickets ADD COLUMN assigned_doctor_id INT NULL AFTER patient_id");
            try {
                $db->exec(
                    "ALTER TABLE queue_tickets ADD CONSTRAINT fk_queue_doctor
                     FOREIGN KEY (assigned_doctor_id) REFERENCES users(id)"
                );
            } catch (PDOException $e) {
                // ignore if FK already exists
            }
        }
        // Patient visit episodes (one per patient per calendar day)
        $db->exec(
            "CREATE TABLE IF NOT EXISTS patient_visits (
              id INT AUTO_INCREMENT PRIMARY KEY,
              visit_no VARCHAR(24) NOT NULL,
              patient_id INT NOT NULL,
              visit_date DATE NOT NULL,
              primary_reason VARCHAR(255) NULL,
              status ENUM('open','completed') NOT NULL DEFAULT 'open',
              started_at DATETIME NOT NULL,
              ended_at DATETIME NULL,
              created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              UNIQUE KEY uq_patient_visits_no (visit_no),
              UNIQUE KEY uq_patient_visit_day (patient_id, visit_date),
              INDEX idx_patient_visits_date (visit_date),
              CONSTRAINT fk_patient_visits_patient FOREIGN KEY (patient_id) REFERENCES patients(id)
            )"
        );

        if (empty($qtCols['visit_id'])) {
            $db->exec("ALTER TABLE queue_tickets ADD COLUMN visit_id INT NULL AFTER patient_id");
        }
        try {
            $db->exec(
                "ALTER TABLE queue_tickets ADD CONSTRAINT fk_queue_visit
                 FOREIGN KEY (visit_id) REFERENCES patient_visits(id)"
            );
        } catch (PDOException $e) {
            // ignore if already exists
        }

        $db->exec(
            "CREATE TABLE IF NOT EXISTS triage_records (
              id INT AUTO_INCREMENT PRIMARY KEY,
              visit_id INT NOT NULL,
              queue_ticket_id INT NULL,
              patient_id INT NOT NULL,
              blood_pressure_systolic SMALLINT NULL,
              blood_pressure_diastolic SMALLINT NULL,
              temperature DECIMAL(4,1) NULL,
              pulse_rate SMALLINT NULL,
              weight_kg DECIMAL(5,2) NULL,
              height_cm DECIMAL(5,2) NULL,
              notes TEXT NULL,
              recorded_by INT NULL,
              created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              INDEX idx_triage_visit (visit_id),
              INDEX idx_triage_ticket (queue_ticket_id),
              INDEX idx_triage_patient_day (patient_id, created_at),
              CONSTRAINT fk_triage_visit FOREIGN KEY (visit_id) REFERENCES patient_visits(id),
              CONSTRAINT fk_triage_ticket FOREIGN KEY (queue_ticket_id) REFERENCES queue_tickets(id),
              CONSTRAINT fk_triage_patient FOREIGN KEY (patient_id) REFERENCES patients(id),
              CONSTRAINT fk_triage_user FOREIGN KEY (recorded_by) REFERENCES users(id)
            )"
        );

        // Next appointments / follow-up visits (after stations + users for FKs)
        $db->exec(
            "CREATE TABLE IF NOT EXISTS patient_appointments (
              id INT AUTO_INCREMENT PRIMARY KEY,
              patient_id INT NOT NULL,
              appointment_date DATE NOT NULL,
              appointment_time TIME NULL,
              purpose VARCHAR(255) NULL,
              station_id INT NULL,
              status ENUM('scheduled','completed','cancelled','no_show') NOT NULL DEFAULT 'scheduled',
              notes TEXT NULL,
              created_by INT NULL,
              created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              completed_at DATETIME NULL,
              INDEX idx_appt_patient_date (patient_id, appointment_date),
              INDEX idx_appt_status_date (status, appointment_date),
              CONSTRAINT fk_appt_patient FOREIGN KEY (patient_id) REFERENCES patients(id),
              CONSTRAINT fk_appt_station FOREIGN KEY (station_id) REFERENCES stations(id),
              CONSTRAINT fk_appt_user FOREIGN KEY (created_by) REFERENCES users(id)
            )"
        );

        // Consultation / diagnosis records
        $db->exec(
            "CREATE TABLE IF NOT EXISTS consultation_records (
              id INT AUTO_INCREMENT PRIMARY KEY,
              patient_id INT NOT NULL,
              queue_ticket_id INT NULL,
              diagnosis TEXT NOT NULL,
              clinical_notes TEXT NULL,
              consultation_date DATE NOT NULL,
              created_by INT NULL,
              created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              INDEX idx_consult_patient_date (patient_id, consultation_date),
              CONSTRAINT fk_consult_patient FOREIGN KEY (patient_id) REFERENCES patients(id),
              CONSTRAINT fk_consult_ticket FOREIGN KEY (queue_ticket_id) REFERENCES queue_tickets(id),
              CONSTRAINT fk_consult_user FOREIGN KEY (created_by) REFERENCES users(id)
            )"
        );

        $crCols = [];
        foreach ($db->query("SHOW COLUMNS FROM consultation_records")->fetchAll(PDO::FETCH_ASSOC) as $cc) {
            $crCols[$cc['Field'] ?? ''] = true;
        }
        if (empty($crCols['doctor_id'])) {
            $db->exec("ALTER TABLE consultation_records ADD COLUMN doctor_id INT NULL AFTER queue_ticket_id");
            try {
                $db->exec(
                    "ALTER TABLE consultation_records ADD CONSTRAINT fk_consult_doctor
                     FOREIGN KEY (doctor_id) REFERENCES users(id)"
                );
            } catch (PDOException $e) {
                // ignore
            }
        }
        if (empty($crCols['appointment_id'])) {
            $db->exec("ALTER TABLE consultation_records ADD COLUMN appointment_id INT NULL AFTER doctor_id");
            try {
                $db->exec(
                    "ALTER TABLE consultation_records ADD CONSTRAINT fk_consult_appointment
                     FOREIGN KEY (appointment_id) REFERENCES patient_appointments(id)"
                );
            } catch (PDOException $e) {
                // ignore
            }
        }

        $db->exec(
            "CREATE TABLE IF NOT EXISTS doctor_comments (
              id INT AUTO_INCREMENT PRIMARY KEY,
              patient_id INT NOT NULL,
              doctor_id INT NOT NULL,
              consultation_id INT NULL,
              queue_ticket_id INT NULL,
              comment TEXT NOT NULL,
              created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              INDEX idx_doc_comment_patient (patient_id, created_at),
              INDEX idx_doc_comment_doctor (doctor_id, created_at),
              CONSTRAINT fk_doc_comment_patient FOREIGN KEY (patient_id) REFERENCES patients(id),
              CONSTRAINT fk_doc_comment_doctor FOREIGN KEY (doctor_id) REFERENCES users(id),
              CONSTRAINT fk_doc_comment_consult FOREIGN KEY (consultation_id) REFERENCES consultation_records(id),
              CONSTRAINT fk_doc_comment_ticket FOREIGN KEY (queue_ticket_id) REFERENCES queue_tickets(id)
            )"
        );

        // Clinic medicine list (name picker for prescribing — stock lives in barangay BIS)
        $db->exec(
            "CREATE TABLE IF NOT EXISTS medicine_catalog (
              id INT AUTO_INCREMENT PRIMARY KEY,
              name VARCHAR(200) NOT NULL,
              default_unit VARCHAR(30) NOT NULL DEFAULT 'tablet(s)',
              stock_qty DECIMAL(10,2) NOT NULL DEFAULT 0,
              min_stock DECIMAL(10,2) NULL,
              is_active TINYINT(1) NOT NULL DEFAULT 1,
              created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              UNIQUE KEY uq_medicine_catalog_name (name)
            )"
        );

        // Medicine prescribed or dispensed
        $db->exec(
            "CREATE TABLE IF NOT EXISTS medicine_dispensings (
              id INT AUTO_INCREMENT PRIMARY KEY,
              patient_id INT NOT NULL,
              consultation_id INT NULL,
              queue_ticket_id INT NULL,
              medicine_id INT NULL,
              medicine_name VARCHAR(200) NOT NULL,
              quantity DECIMAL(10,2) NOT NULL DEFAULT 1,
              unit VARCHAR(30) NOT NULL DEFAULT 'tablet(s)',
              dispense_status ENUM('prescribed','dispensed') NOT NULL DEFAULT 'dispensed',
              receipt_issued TINYINT(1) NOT NULL DEFAULT 0,
              notes TEXT NULL,
              created_by INT NULL,
              created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              INDEX idx_med_patient (patient_id, created_at),
              INDEX idx_med_consult (consultation_id),
              CONSTRAINT fk_med_patient FOREIGN KEY (patient_id) REFERENCES patients(id),
              CONSTRAINT fk_med_consult FOREIGN KEY (consultation_id) REFERENCES consultation_records(id),
              CONSTRAINT fk_med_ticket FOREIGN KEY (queue_ticket_id) REFERENCES queue_tickets(id),
              CONSTRAINT fk_med_user FOREIGN KEY (created_by) REFERENCES users(id)
            )"
        );

        $medCols = $db->query("SHOW COLUMNS FROM medicine_dispensings")->fetchAll(PDO::FETCH_ASSOC);
        $hasMedicineId = false;
        foreach ($medCols as $c) {
            if (($c['Field'] ?? '') === 'medicine_id') {
                $hasMedicineId = true;
                break;
            }
        }
        if (!$hasMedicineId) {
            $db->exec("ALTER TABLE medicine_dispensings ADD COLUMN medicine_id INT NULL AFTER queue_ticket_id");
            try {
                $db->exec(
                    "ALTER TABLE medicine_dispensings
                     ADD CONSTRAINT fk_med_catalog FOREIGN KEY (medicine_id) REFERENCES medicine_catalog(id)"
                );
            } catch (PDOException $e) {
                // ignore if key already exists
            }
        }

        $hasProcurementSource = false;
        foreach ($db->query("SHOW COLUMNS FROM medicine_dispensings")->fetchAll(PDO::FETCH_ASSOC) as $c) {
            if (($c['Field'] ?? '') === 'procurement_source') {
                $hasProcurementSource = true;
                break;
            }
        }
        if (!$hasProcurementSource) {
            $db->exec(
                "ALTER TABLE medicine_dispensings
                 ADD COLUMN procurement_source ENUM('clinic','lgu','external') NOT NULL DEFAULT 'clinic'
                 AFTER unit"
            );
        }

        require_once dirname(__DIR__) . '/models/MedicineCatalog.php';
        MedicineCatalog::ensureSeedDefaults($db);

        // Issued clinical documents (medicine receipts, certificates, etc.)
        $db->exec(
            "CREATE TABLE IF NOT EXISTS clinical_documents (
              id INT AUTO_INCREMENT PRIMARY KEY,
              document_no VARCHAR(30) NOT NULL,
              document_type ENUM('medicine_receipt','medical_certificate','referral','recommendation') NOT NULL,
              patient_id INT NOT NULL,
              consultation_id INT NULL,
              doctor_id INT NULL,
              issued_at DATETIME NOT NULL,
              status ENUM('issued','voided') NOT NULL DEFAULT 'issued',
              title VARCHAR(255) NULL,
              content_json TEXT NOT NULL,
              created_by INT NULL,
              created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              voided_at DATETIME NULL,
              void_reason TEXT NULL,
              UNIQUE KEY uq_clinical_documents_no (document_no),
              INDEX idx_clinical_doc_patient (patient_id, issued_at),
              INDEX idx_clinical_doc_consult (consultation_id),
              CONSTRAINT fk_clinical_doc_patient FOREIGN KEY (patient_id) REFERENCES patients(id),
              CONSTRAINT fk_clinical_doc_consult FOREIGN KEY (consultation_id) REFERENCES consultation_records(id),
              CONSTRAINT fk_clinical_doc_doctor FOREIGN KEY (doctor_id) REFERENCES users(id),
              CONSTRAINT fk_clinical_doc_user FOREIGN KEY (created_by) REFERENCES users(id)
            )"
        );

        // Audit logs
        $db->exec(
            "CREATE TABLE IF NOT EXISTS audit_logs (
              id INT AUTO_INCREMENT PRIMARY KEY,
              user_id INT NULL,
              action VARCHAR(50) NOT NULL,
              entity_type VARCHAR(50) NULL,
              entity_id INT NULL,
              metadata_json TEXT NULL,
              ip_address VARCHAR(45) NULL,
              user_agent VARCHAR(255) NULL,
              created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              INDEX idx_audit_user_day (user_id, created_at),
              INDEX idx_audit_action (action),
              CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id)
            )"
        );

        // Seed a default admin if none exists (demo-friendly)
        $countUsers = (int) $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
        if ($countUsers === 0) {
            $hash = password_hash('admin123', PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (username, password_hash, role, is_active) VALUES ('admin', :hash, 'admin', 1)");
            $stmt->execute([':hash' => $hash]);
        }
    }
}
?>