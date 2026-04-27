-- Migration: Convert member codes from MBR-XXXXXX to REP-000001 format
-- This script converts all existing members to the new sequential format
-- IMPORTANT: Always backup your database before running migrations!

USE gym_attendance;

-- Step 1: Create the member_sequence table if it doesn't exist
CREATE TABLE IF NOT EXISTS member_sequence (
  id TINYINT UNSIGNED PRIMARY KEY DEFAULT 1,
  next_member_number INT UNSIGNED NOT NULL DEFAULT 1,
  last_updated DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Step 2: Get the count of existing members to set the next sequence number correctly
SET @existing_member_count := (SELECT COUNT(*) FROM members);

-- Step 3: Initialize or update the sequence counter
INSERT INTO member_sequence (id, next_member_number)
VALUES (1, @existing_member_count + 1)
ON DUPLICATE KEY UPDATE
  next_member_number = @existing_member_count + 1;

-- Step 4: Create a temporary table to hold the new member codes
CREATE TEMPORARY TABLE temp_member_codes (
  old_id INT UNSIGNED PRIMARY KEY,
  old_code VARCHAR(30) NOT NULL,
  new_code VARCHAR(30) NOT NULL UNIQUE
);

-- Step 5: Generate sequential codes for all existing members
INSERT INTO temp_member_codes (old_id, old_code, new_code)
SELECT
  m.id,
  m.member_code,
  CONCAT('REP-', LPAD(ROW_NUMBER() OVER (ORDER BY m.id), 6, '0'))
FROM members m
ORDER BY m.id;

-- Step 6: Update member codes to new sequential format
UPDATE members m
INNER JOIN temp_member_codes t ON m.id = t.old_id
SET
  m.member_code = t.new_code,
  m.qr_payload = JSON_SET(
    m.qr_payload,
    '$.member_code',
    t.new_code
  ),
  m.updated_at = NOW()
WHERE m.member_code LIKE 'MBR-%' OR m.member_code LIKE 'RC-%';

-- Step 7: Log the migration (optional - verify the changes)
SELECT
  COUNT(*) as members_converted,
  NOW() as migration_timestamp
FROM members
WHERE member_code LIKE 'REP-%';

-- Clean up is automatic for TEMPORARY tables

-- Summary:
-- This migration:
-- 1. Creates the member_sequence tracking table
-- 2. Initializes the sequence counter to start after existing members
-- 3. Converts all member codes to REP-000001 format (ordered by creation date)
-- 4. Updates QR payloads to reflect new member codes
-- 5. Logs the conversion statistics
--
-- Note: The conversion maintains the original order of members (by creation date).
-- The sequence counter is set to ensure new members start from the next available number.
