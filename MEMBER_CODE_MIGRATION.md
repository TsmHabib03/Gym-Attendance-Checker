# Member Code Format Migration

## Overview
This document describes the migration from the old random member code format (`MBR-XXXXXX`) to a new sequential format (`REP-000001`, `REP-000002`, etc.).

## Changes Made

### 1. Database Schema
- **New Table**: `member_sequence`
  - Tracks the next sequential member number to generate
  - Uses atomic increments for thread-safe sequence generation
  - Stores `id=1` with `next_member_number` field
  - Ensures no race conditions during concurrent member creation

### 2. Code Changes
- **File**: `src/Services/MemberService.php`
  - **Old**: `'MBR-' . strtoupper(bin2hex(random_bytes(3)))`
  - **New**: Sequential format via `generateNextMemberCode()` method
  - Uses database-level atomic increment (`ON DUPLICATE KEY UPDATE`)
  - Guarantees unique sequential numbers even under high concurrency
  - Format: `REP-` + zero-padded 6-digit number

### 3. Database Seed Data
- **File**: `database/seed.sql`
  - Updated test members to use new format:
    - Juan Dela Cruz: `REP-000001`
    - Maria Santos: `REP-000002`
    - Pedro Reyes: `REP-000003`
  - Initializes sequence counter to `4` for next new member creation

### 4. Migration Script
- **File**: `database/migrations/002_convert_member_codes_to_sequential.sql`
- Converts all existing members from old format to new format
- Maintains member creation order
- Updates QR payloads to reflect new member codes
- Safely initializes sequence counter

## Migration Steps

### For New Installations (Fresh Database)
1. Run `database/schema.sql` - Creates the new `member_sequence` table
2. Run `database/seed.sql` - Initializes test data with new format

### For Existing Installations (Production Data)
⚠️ **BACKUP YOUR DATABASE FIRST**

1. Stop the application
2. Back up the database:
   ```bash
   mysqldump -u root -p gym_attendance > gym_attendance_backup.sql
   ```

3. Run the migration script:
   ```bash
   mysql -u root -p gym_attendance < database/migrations/002_convert_member_codes_to_sequential.sql
   ```

4. Verify the conversion:
   ```sql
   SELECT COUNT(*) as total, 
          SUM(CASE WHEN member_code LIKE 'REP-%' THEN 1 ELSE 0 END) as converted
   FROM members;
   ```
   Both counts should match if conversion was successful.

5. Restart the application
6. Test member creation to ensure new members get sequential codes

## How It Works

### Member Code Generation (Thread-Safe)
The new system uses a database-level approach for atomic increment:

```sql
INSERT INTO member_sequence (id, next_member_number)
VALUES (1, 1)
ON DUPLICATE KEY UPDATE next_member_number = next_member_number + 1;

SELECT next_member_number FROM member_sequence WHERE id = 1;
```

This ensures:
- ✅ No race conditions
- ✅ No duplicate member codes
- ✅ Guaranteed sequential numbering
- ✅ Works correctly under concurrent load
- ✅ No need for application-level locking

### Code Flow
1. `MemberService::create()` calls `generateNextMemberCode()`
2. Database atomically increments the counter
3. Method reads the updated counter value
4. Formats code as `REP-` + zero-padded number (e.g., `REP-000001`)
5. Code is guaranteed unique due to UNIQUE constraint on `member_code` column

## Technical Details

### Why Not Use AUTO_INCREMENT on members.id?
The original approach used random hex because:
- Random, non-sequential codes look more like unique tokens
- Each member could have a "secret" code

The new sequential approach provides:
- Better user experience (easy to read/remember)
- Easier to reference in support contexts
- Better for analytics and reporting
- Thread-safe without application-level locking

### QR Code Updates
QR payloads are JSON objects that include the member code:
```json
{
  "v": 1,
  "type": "gym_member",
  "member_code": "REP-000001",
  ...
}
```

- During member creation: Payload is generated with new sequential code
- During member update: Payload is regenerated with existing (unchanged) code
- During QR regeneration: Payload updated but member_code unchanged

The migration script updates all existing QR payloads to reflect new member codes.

## Rollback Plan
If you need to rollback:

1. Restore from backup:
   ```bash
   mysql -u root -p gym_attendance < gym_attendance_backup.sql
   ```

2. Remove the changes from code:
   - Revert `src/Services/MemberService.php`
   - Revert `database/seed.sql`
   - Keep the schema change (harmless if unused)

3. Restart the application

## Security Considerations
- Member codes are now sequential and predictable
- If you need secrecy in codes, add an additional `member_secret` field
- QR tokens remain random and secure (unchanged)
- UNIQUE constraint prevents duplicate codes
- Foreign key constraints protect referential integrity

## Testing Checklist
- [ ] New members receive sequential codes (REP-000001, REP-000002, etc.)
- [ ] Member list displays new codes correctly
- [ ] QR codes scan and generate payloads with correct codes
- [ ] Concurrent member creation produces unique codes
- [ ] Sequence doesn't skip numbers under normal load
- [ ] Database transaction logs show atomic operations
- [ ] Search by member code finds members by new code
- [ ] API endpoints return new format in responses
- [ ] Export/reports show new format

## Performance Notes
- Single-row table lookup is O(1)
- Atomic increment has minimal overhead
- No locking required (database handles it)
- Suitable for thousands of concurrent members
- Sequential access is cache-friendly

## FAQ

**Q: Why REP instead of MBR?**
A: REP stands for "Representative" (gym member) and avoids confusion with the old MBR format.

**Q: Can I use a different prefix?**
A: Yes, change the prefix in `MemberService::generateNextMemberCode()` and update references.

**Q: What happens if the sequence reaches 999,999?**
A: With padding to 6 digits, you can support 1 million members. For larger numbers, increase the padding.

**Q: Is the conversion reversible?**
A: Yes, restore from backup. The migration creates a temporary table, no permanent changes until final UPDATE.

**Q: Will this affect API integrations?**
A: Yes - any API expecting MBR format should be updated to handle REP format. The member_code field is the same (column name unchanged).

## Support
For issues or questions about the migration:
1. Check the migration script output for errors
2. Verify database backup exists before attempting migration
3. Review audit logs for member creation events
4. Check application logs for sequence generation errors
