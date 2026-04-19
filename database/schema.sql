CREATE DATABASE IF NOT EXISTS gym_attendance CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE gym_attendance;

CREATE TABLE IF NOT EXISTS admins (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(60) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS members (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  member_code VARCHAR(30) NOT NULL UNIQUE,
  qr_token VARCHAR(64) NOT NULL UNIQUE,
  full_name VARCHAR(120) NOT NULL,
  email VARCHAR(160) NULL,
  gender ENUM('male', 'female', 'other', 'prefer_not_say') NOT NULL DEFAULT 'prefer_not_say',
  photo_path VARCHAR(255) NULL,
  qr_payload JSON NULL,
  membership_end_date DATE NOT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  INDEX idx_membership_end_date (membership_end_date),
  INDEX idx_full_name (full_name)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS attendance_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  member_id INT UNSIGNED NOT NULL,
  status ENUM('accepted', 'expired_denied', 'duplicate_denied', 'invalid_denied') NOT NULL,
  note VARCHAR(255) NULL,
  ip_address VARCHAR(45) NOT NULL,
  checkin_photo_path VARCHAR(255) NULL,
  scanned_at DATETIME NOT NULL,
  INDEX idx_member_scanned_at (member_id, scanned_at),
  INDEX idx_status_scanned_at (status, scanned_at),
  CONSTRAINT fk_attendance_member FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS rate_limits (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  action VARCHAR(60) NOT NULL,
  key_hash CHAR(64) NOT NULL,
  attempts INT UNSIGNED NOT NULL,
  window_started_at DATETIME NOT NULL,
  blocked_until DATETIME NULL,
  updated_at DATETIME NOT NULL,
  UNIQUE KEY uq_action_key_hash (action, key_hash),
  INDEX idx_blocked_until (blocked_until)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS app_settings (
  setting_key VARCHAR(100) PRIMARY KEY,
  setting_value TEXT NOT NULL,
  updated_at DATETIME NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS audit_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  admin_id INT UNSIGNED NULL,
  event_type VARCHAR(100) NOT NULL,
  event_context JSON NULL,
  created_at DATETIME NOT NULL,
  INDEX idx_event_type_created_at (event_type, created_at),
  CONSTRAINT fk_audit_admin FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS email_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  recipient_email VARCHAR(160) NOT NULL,
  subject_line VARCHAR(255) NOT NULL,
  body_text MEDIUMTEXT NOT NULL,
  was_sent TINYINT(1) NOT NULL,
  error_message TEXT NULL,
  context_json JSON NULL,
  created_at DATETIME NOT NULL,
  INDEX idx_email_logs_created_at (created_at)
) ENGINE=InnoDB;
