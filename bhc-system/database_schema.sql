-- =============================================================================
-- Barangay Health Center (BHC) System — Database Schema
-- =============================================================================
-- Database: brgy_health_db
--
-- This file is the canonical SQL reference for a FRESH install.
-- Runtime migrations also run automatically via config/database.php (ensureSchema)
-- when the app connects — useful for older databases missing new columns.
--
-- Workflow tables (summary):
--   patients        → Patient Registry (BHC ID, demographics)
--   stations          → Registration, Triage, Consultation, Pharmacy
--   queue_tickets     → Per-station daily queue (waiting → serving → done/skipped)
--   users             → Staff / admin login
--   audit_logs        → Activity Log (ticket_create, ticket_complete, login, etc.)
-- =============================================================================

CREATE DATABASE IF NOT EXISTS brgy_health_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE brgy_health_db;

-- -----------------------------------------------------------------------------
-- Patient Registry
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS patients (
  id INT AUTO_INCREMENT PRIMARY KEY,
  bhc_id VARCHAR(20) NOT NULL,
  first_name VARCHAR(100) NULL,
  middle_name VARCHAR(100) NULL,
  last_name VARCHAR(100) NULL,
  suffix VARCHAR(20) NULL,
  full_name VARCHAR(255) NOT NULL,
  sex ENUM('M','F') NULL,
  birthdate DATE NULL,
  contact_number VARCHAR(30) NULL,
  address VARCHAR(500) NULL,
  barangay VARCHAR(100) NULL,
  civil_status ENUM('single','married','widowed','separated') NULL,
  philhealth_no VARCHAR(30) NULL,
  emergency_contact_name VARCHAR(150) NULL,
  emergency_contact_phone VARCHAR(30) NULL,
  notes TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_patients_bhc_id (bhc_id),
  INDEX idx_patients_full_name (full_name),
  INDEX idx_patients_bhc_id (bhc_id),
  INDEX idx_patients_last_first (last_name, first_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Service stations (multi-queue)
-- Station 1 = Registration (routing desk only; tickets created for other stations)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS stations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(10) NOT NULL,
  name VARCHAR(100) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  UNIQUE KEY uq_stations_code (code),
  INDEX idx_stations_active_sort (is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Queue tickets (per station, per calendar day)
-- status: waiting | serving | done | skipped
-- reason: captured at Patient Routing (Registration desk)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS queue_tickets (
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
  INDEX idx_queue_station_status_day (station_id, status, created_at),
  CONSTRAINT fk_queue_station FOREIGN KEY (station_id) REFERENCES stations(id),
  CONSTRAINT fk_queue_patient FOREIGN KEY (patient_id) REFERENCES patients(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Staff authentication
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','staff','doctor') NOT NULL DEFAULT 'staff',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  display_name VARCHAR(100) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_users_username (username),
  INDEX idx_users_role_active (role, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Patient appointments (follow-up / next visit scheduling)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS patient_appointments (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Consultation / diagnosis records
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS consultation_records (
  id INT AUTO_INCREMENT PRIMARY KEY,
  patient_id INT NOT NULL,
  queue_ticket_id INT NULL,
  doctor_id INT NULL,
  diagnosis TEXT NOT NULL,
  clinical_notes TEXT NULL,
  consultation_date DATE NOT NULL,
  created_by INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_consult_patient_date (patient_id, consultation_date),
  CONSTRAINT fk_consult_patient FOREIGN KEY (patient_id) REFERENCES patients(id),
  CONSTRAINT fk_consult_ticket FOREIGN KEY (queue_ticket_id) REFERENCES queue_tickets(id),
  CONSTRAINT fk_consult_doctor FOREIGN KEY (doctor_id) REFERENCES users(id),
  CONSTRAINT fk_consult_user FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Medicine prescribed or dispensed
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS medicine_dispensings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  patient_id INT NOT NULL,
  consultation_id INT NULL,
  queue_ticket_id INT NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Doctor comments (clinical notes from doctor accounts)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS doctor_comments (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Activity Log (audit trail for reports and accountability)
-- Common actions: login, logout, patient_create, ticket_create,
--   ticket_call_next, ticket_call_next_auto, ticket_call,
--   ticket_complete, ticket_skip
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS audit_logs (
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
  INDEX idx_audit_entity (entity_type, entity_id),
  CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Seed: default stations
-- -----------------------------------------------------------------------------
INSERT INTO stations (code, name, sort_order, is_active) VALUES
  ('RG', 'Registration', 1, 1),
  ('TR', 'Triage / Vitals', 2, 1),
  ('CN', 'Consultation', 3, 1),
  ('PH', 'Pharmacy', 4, 1)
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  sort_order = VALUES(sort_order),
  is_active = VALUES(is_active);

-- -----------------------------------------------------------------------------
-- Seed: demo admin (only if no users exist — change password before production)
-- Password: admin123  (generate a new hash with PHP password_hash())
-- -----------------------------------------------------------------------------
-- INSERT INTO users (username, password_hash, role, is_active)
-- SELECT 'admin', '$2y$10$REPLACE_WITH_PASSWORD_HASH', 'admin', 1
-- FROM DUAL
-- WHERE NOT EXISTS (SELECT 1 FROM users LIMIT 1);

-- =============================================================================
-- Upgrade notes (existing databases from older schema.sql)
-- Run only if your database was created from an earlier version:
--
-- ALTER TABLE patients
--   ADD COLUMN sex ENUM('M','F') NULL AFTER full_name,
--   ADD COLUMN birthdate DATE NULL AFTER sex;
--
-- ALTER TABLE queue_tickets
--   ADD COLUMN reason VARCHAR(255) NULL AFTER ticket_no;
--
-- Then create users and audit_logs using the CREATE TABLE statements above,
-- or let the application run ensureSchema() on first connection.
-- =============================================================================
