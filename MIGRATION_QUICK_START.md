# Quick Start: Member Code Migration

## 🎯 What Changed?
- **Old format**: `MBR-A1B2C3` (random hex)
- **New format**: `REP-000001` (sequential)

## 📋 For Fresh Installations
No action needed! New format is already in place.

```bash
# When you run schema.sql and seed.sql:
# - member_sequence table created
# - Test members initialized with REP-000001, REP-000002, REP-000003
# - Next new member will be REP-000004
```

## 🚀 For Existing Databases (Production)

### Step 1: Backup
```bash
mysqldump -u root -p gym_attendance > gym_attendance_backup_$(date +%Y%m%d).sql
```

### Step 2: Deploy Code
Deploy the updated `src/Services/MemberService.php`

### Step 3: Run Migration
```bash
mysql -u root -p gym_attendance < database/migrations/002_convert_member_codes_to_sequential.sql
```

### Step 4: Verify
```sql
SELECT COUNT(*) FROM members WHERE member_code LIKE 'REP-%';
```
All members should have REP- format.

### Step 5: Test
1. Create a new member via admin panel
2. New code should be sequential (REP-000004 if you had 3 seed members)
3. QR codes should scan correctly
4. Member search should work

## 📊 What Gets Updated?

| Item | Before | After |
|------|--------|-------|
| Member Code | MBR-A1B2C3 | REP-000001 |
| QR Payload | Contains MBR-... | Contains REP-... |
| Database Table | members | + member_sequence |
| Code Files | MemberService.php | Updated generation logic |

## ⚠️ Important Notes

- ✅ **Zero downtime** - Can deploy while app is running
- ✅ **Backward compatible** - Old data safely converted
- ✅ **Thread-safe** - Works with concurrent requests
- ✅ **Atomic** - No partial updates possible
- ✅ **Rollback-safe** - Backup exists for recovery

## 🔍 Monitoring After Migration

Watch for these in logs:
```
✅ Member creation succeeds with new format
✅ QR codes generate with new member code
✅ No sequence generation errors
✅ No duplicate member codes
```

## 🆘 If Something Goes Wrong

**Problem**: Migration failed
```bash
# Restore backup
mysql -u root -p gym_attendance < backup.sql
# Check what went wrong in migration output
# Retry migration
```

**Problem**: New members still getting old format
```bash
# Code might not be deployed. Check:
# 1. Is old MemberService.php still running?
# 2. Was PHP/application restarted?
# 3. Restart the application
```

**Problem**: QR codes showing old member code
```sql
-- Regenerate QR codes for all members
-- Through admin panel: Edit member → Regenerate QR Code
-- For batch: Run migration script includes QR payload update
```

## 📞 Need Help?

1. Read `MEMBER_CODE_MIGRATION.md` for detailed docs
2. Check `IMPLEMENTATION_SUMMARY.md` for technical details
3. Review migration script output for specific errors
4. Verify backup exists before troubleshooting

## ✅ Post-Migration Checklist

- [ ] Code deployed
- [ ] Migration script ran successfully
- [ ] All members show REP- format
- [ ] New member creation works
- [ ] QR codes scan correctly
- [ ] Member search works with new codes
- [ ] No errors in application logs
- [ ] Database backup archived safely

---

**Expected Time**: 5-10 minutes (actual migration is ~1 second)  
**Rollback Time**: 2-3 minutes (restore from backup)  
**Downtime Required**: None (can deploy live)
