<?php
class Database {
    private $host = "sqlXXX.infinityfree.com";
    private $db_name = "if0_XXXXXX_bhc";
    private $username = "if0_XXXXXX";
    private $password = "YOUR_MYSQL_PASSWORD";
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
              role ENUM('admin','staff') NOT NULL DEFAULT 'staff',
              is_active TINYINT(1) NOT NULL DEFAULT 1,
              created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
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