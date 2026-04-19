USE gym_attendance;

-- Handle older databases that were created before members.gender existed.
SET @gender_col_exists := (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'members'
    AND COLUMN_NAME = 'gender'
);

SET @alter_members_gender_sql := IF(
  @gender_col_exists = 0,
  "ALTER TABLE members ADD COLUMN gender ENUM('male', 'female', 'other', 'prefer_not_say') NOT NULL DEFAULT 'prefer_not_say' AFTER email",
  'SELECT 1'
);

PREPARE stmt_alter_members_gender FROM @alter_members_gender_sql;
EXECUTE stmt_alter_members_gender;
DEALLOCATE PREPARE stmt_alter_members_gender;

INSERT INTO admins (username, password_hash, created_at, updated_at)
VALUES
  ('admin', '$2y$10$BDcgaQhu57OAr8UL72xiLuKVWWt1p9sQHBaNYr1JY2OcDmBQJeYRK', NOW(), NOW())
ON DUPLICATE KEY UPDATE
  updated_at = VALUES(updated_at);

INSERT INTO app_settings (setting_key, setting_value, updated_at)
VALUES
  ('photo_capture_enabled', 'true', NOW()),
  ('expiry_reminder_days', '7', NOW())
ON DUPLICATE KEY UPDATE
  setting_value = VALUES(setting_value),
  updated_at = VALUES(updated_at);

INSERT INTO members (member_code, qr_token, full_name, email, gender, photo_path, qr_payload, membership_end_date, created_at, updated_at)
VALUES
  ('MBR-A1B2C3', '7a3f24c89e4a6341db1fce863118a00f3f96a7150b542417', 'Juan Dela Cruz', 'juan@example.com', 'male', NULL, '{"v":1,"type":"gym_member","qr_token":"7a3f24c89e4a6341db1fce863118a00f3f96a7150b542417","member_code":"MBR-A1B2C3","full_name":"Juan Dela Cruz","email":"juan@example.com","gender":"male","photo_path":null}', DATE_ADD(CURDATE(), INTERVAL 10 DAY), NOW(), NOW()),
  ('MBR-D4E5F6', '9bb8131fa76ea5afbd82417d56f3d3c7ac1e6072ca9270f6', 'Maria Santos', 'maria@example.com', 'female', NULL, '{"v":1,"type":"gym_member","qr_token":"9bb8131fa76ea5afbd82417d56f3d3c7ac1e6072ca9270f6","member_code":"MBR-D4E5F6","full_name":"Maria Santos","email":"maria@example.com","gender":"female","photo_path":null}', DATE_ADD(CURDATE(), INTERVAL 3 DAY), NOW(), NOW()),
  ('MBR-G7H8I9', 'f15ad3a7df0131d9bdb30aaf8cd9f1f8cb14859cd2e6759b', 'Pedro Reyes', 'pedro@example.com', 'male', NULL, '{"v":1,"type":"gym_member","qr_token":"f15ad3a7df0131d9bdb30aaf8cd9f1f8cb14859cd2e6759b","member_code":"MBR-G7H8I9","full_name":"Pedro Reyes","email":"pedro@example.com","gender":"male","photo_path":null}', DATE_SUB(CURDATE(), INTERVAL 5 DAY), NOW(), NOW())
ON DUPLICATE KEY UPDATE
  full_name = VALUES(full_name),
  email = VALUES(email),
  gender = VALUES(gender),
  qr_payload = VALUES(qr_payload),
  membership_end_date = VALUES(membership_end_date),
  updated_at = VALUES(updated_at);
