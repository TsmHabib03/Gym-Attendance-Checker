# Production Audit: Part D — Minimalist Maintainability Plan

**Target:** Hostinger Shared Hosting | **Effort:** ~4-6 hours | **Ongoing:** ~1 hour/week

---

## Overview

This plan focuses on **minimal, sustainable maintenance** for a shared-hosting deployment without overengineering. Priorities:

1. **Dependency management** (Composer, security updates)
2. **Configuration centralization** (environment-based, secrets safe)
3. **Logging & observability** (disk usage, errors)
4. **Backup & disaster recovery** (weekly automated backups)
5. **Documentation** (runbooks for common tasks)

---

## Phase 1: Pre-Deployment (One-Time, ~2 hours)

### 1.1 Composer Cleanup

**Action:**
```bash
# Locally before deploying:
composer install --no-dev --optimize-autoloader
```

This:
- ✅ Removes ~15 MB of dev dependencies (phpstan, test frameworks)
- ✅ Generates optimized autoloader (faster class loading)
- ✅ Produces `composer.lock` with exact pinned versions

**Time:** 2 minutes

---

### 1.2 Create Production `.env`

**Action:** Create `.env` on Hostinger (never in version control):

```bash
# Create via SFTP/cPanel File Manager
# /home/username/public_html/.env

APP_ENV=production
APP_NAME="Gym Attendance Checker"
APP_URL=https://your-domain.com
APP_TIMEZONE=Asia/Manila
APP_DEBUG=false
APP_SECRET=[64 random hex chars from: php -r "echo bin2hex(random_bytes(32));"]

SESSION_COOKIE_NAME=gym_attendance_session
SESSION_SECURE=true
SESSION_SAMESITE=Lax
SESSION_LIFETIME=7200

TRUSTED_PROXIES=127.0.0.1,your-proxy-ip

DUPLICATE_SCAN_WINDOW_SECONDS=45
CHECKIN_RATE_LIMIT_WINDOW_SECONDS=60
CHECKIN_RATE_LIMIT_MAX_ATTEMPTS=25
LOGIN_RATE_LIMIT_WINDOW_SECONDS=300
LOGIN_RATE_LIMIT_MAX_ATTEMPTS=5
RATE_LIMIT_RETENTION_DAYS=7
PHOTO_CAPTURE_ENABLED=true
EXPIRY_REMINDER_DAYS=7

DB_HOST=localhost
DB_PORT=3306
DB_NAME=gym_attendance
DB_USER=gym_app_user
DB_PASS=[strong password, 20+ chars]

SMTP_HOST=smtp.yourdomain.com
SMTP_PORT=587
SMTP_USERNAME=noreply@yourdomain.com
SMTP_PASSWORD=[smtp password]
SMTP_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="Gym Attendance Checker"
ADMIN_ALERT_EMAIL=owner@yourdomain.com
```

**File permissions:** `chmod 600 .env` (read-only by owner)

**Time:** 15 minutes

---

### 1.3 Create `.user.ini` for PHP Settings

**Action:** Create `.user.ini` in project root:

```ini
; .user.ini
; Hostinger applies these settings automatically

display_errors = Off
display_startup_errors = Off
log_errors = On
error_log = /home/username/public_html/storage/logs/php_errors.log

max_input_vars = 2000
post_max_size = 10M
upload_max_filesize = 10M
max_execution_time = 300
default_socket_timeout = 30
memory_limit = 256M
```

**Time:** 5 minutes

---

### 1.4 Verify Database Least-Privilege User

**Action:** Via Hostinger cPanel:

1. Go to **MySQL Databases**
2. Create user `gym_app_user` with strong password
3. Assign to database `gym_attendance`
4. Click "Manage User Privileges"
5. Grant **only**: `SELECT`, `INSERT`, `UPDATE`, `DELETE`
6. Deny: `CREATE`, `DROP`, `ALTER`, `GRANT`, etc.

**Verification:**
```bash
# From local machine (or Hostinger terminal):
mysql -u gym_app_user -p gym_attendance -e "SHOW GRANTS FOR CURRENT_USER;"
```

Should show:
```sql
GRANT SELECT, INSERT, UPDATE, DELETE ON `gym_attendance`.* TO `gym_app_user`@`localhost`
```

**Time:** 10 minutes

---

### 1.5 Initialize Database Schema

**Action:**
```bash
# On Hostinger terminal or via phpMyAdmin:
mysql -u gym_app_user -p gym_attendance < database/schema.sql
```

**Time:** 2 minutes

---

## Phase 2: Initial Deployment (One-Time, ~1 hour)

### 2.1 Deploy Code via FTP/SFTP

**Action:** Upload code to `/home/username/public_html/`:

```
.
├── public/                  # Web root
│   ├── index.php
│   ├── assets/
│   ├── uploads/
│   └── .htaccess
├── src/                     # Application code
├── views/                   # Templates
├── storage/                 # Logs, temp
├── vendor/                  # Composer deps (compiled --no-dev)
├── .env                     # Secrets (created on server)
├── .env.example             # Template
├── .user.ini                # PHP settings
├── .htaccess                # Root security (blocks non-public/ access)
├── composer.json            # Dependencies
└── composer.lock            # Version pins
```

**Note:** Do NOT upload `.git/`, `docker/`, `k8s/`, deployment scripts, or demo files (use Part B cleanup script).

**Time:** 20 minutes (via SFTP)

---

### 2.2 Set File Permissions

**Action:** Via Hostinger cPanel → Terminal or SSH:

```bash
cd /home/username/public_html

# Code is readable by web server (644)
find . -type f -name '*.php' -exec chmod 644 {} \;
find . -type f -name '*.phtml' -exec chmod 644 {} \;

# Directories are traversable by web server (755)
find . -type d -exec chmod 755 {} \;

# Writable directories for logs and uploads (775)
chmod -R 775 storage/
chmod -R 775 public/uploads/

# Secrets readable only by owner
chmod 600 .env
chmod 600 .user.ini
```

**Time:** 5 minutes

---

### 2.3 Install Dependencies

**Action:** On Hostinger (via cPanel Terminal or SSH):

```bash
cd /home/username/public_html

# Download and install PHP Composer first if not present
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install production dependencies
composer install --no-dev --optimize-autoloader --no-interaction
```

**Time:** 5-10 minutes

---

### 2.4 Verify Application Boots

**Action:** Check for errors:

```bash
# Test PHP syntax
php -l public/index.php

# Check for bootstrap errors
php -r "require_once 'src/bootstrap.php'; echo 'Bootstrap OK';"
```

**Time:** 2 minutes

---

## Phase 3: Ongoing Maintenance (Recurring)

### 3.1 Weekly: Log Rotation & Cleanup

**Action:** Create Cron Job via cPanel:

```bash
# Run every Sunday at 3 AM
0 3 * * 0 /usr/bin/php /home/username/public_html/scripts/maintenance.php
```

**Create `scripts/maintenance.php`:**

```php
<?php
// scripts/maintenance.php
// Rotate logs, clean up old rate limits, prune old backups

chdir(dirname(__DIR__));
require 'src/bootstrap.php';

use App\Core\Logger;
use App\Core\Config;

// 1. Rotate logs if > 50 MB
$logFile = 'storage/logs/app.log';
if (file_exists($logFile) && filesize($logFile) > 50 * 1024 * 1024) {
    $timestamp = date('Y-m-d_H-i-s');
    rename($logFile, "{$logFile}.{$timestamp}");
    exec("gzip {$logFile}.{$timestamp}");
    touch($logFile);
    Logger::info('Log rotated');
}

// 2. Delete logs older than 30 days
exec("find storage/logs -name 'app.log.*.gz' -mtime +30 -delete");
Logger::info('Old logs cleaned');

// 3. Clean up old rate limit entries
$pdo = \App\Core\Database::connection();
$daysOld = (int) Config::int('RATE_LIMIT_RETENTION_DAYS', 7);
$pdo->exec("DELETE FROM rate_limits WHERE updated_at < DATE_SUB(NOW(), INTERVAL $daysOld DAY)");
Logger::info('Rate limits cleaned');

echo "Maintenance completed at " . date('Y-m-d H:i:s') . "\n";
```

**Verify Cron:** Check cPanel → Cron Jobs to confirm it's scheduled.

**Time:** 5 minutes setup, 30 seconds runtime

---

### 3.2 Monthly: Dependency Updates

**Action:** Check for security updates:

```bash
cd /home/username/public_html

# Locally:
composer audit                          # Check for CVEs
composer update --dry-run               # Simulate updates
composer update --no-dev                # Apply safe updates
```

**When to apply:**
- ✅ Security patches (always apply immediately)
- ✅ Minor version updates (apply monthly)
- ❌ Major version updates (test extensively first, or skip)

**Hostinger deployment after updating locally:**
```bash
# Upload new composer.lock via SFTP
# Then on Hostinger terminal:
composer install --no-dev --optimize-autoloader
php -l public/index.php  # Verify syntax
```

**Time:** 15 minutes

---

### 3.3 Monthly: Backup Verification

**Action:** Test that backups are working:

```bash
# Check backup directory size
du -sh /home/username/backups/

# Verify latest backup is recent
ls -lth /home/username/backups/ | head -5
```

Setup automated backups via Hostinger cPanel if not already enabled.

**Time:** 5 minutes

---

### 3.4 Quarterly: Security Headers Audit

**Action:** Verify headers are being sent:

```bash
# From local machine:
curl -I https://your-domain.com

# Check for:
# Strict-Transport-Security
# Content-Security-Policy
# X-Frame-Options: SAMEORIGIN
# X-Content-Type-Options: nosniff
```

Use https://securityheaders.com to scan your domain (free).

**Time:** 10 minutes

---

### 3.5 Quarterly: Database Maintenance

**Action:** Optimize tables:

```bash
mysql -u gym_app_user -p gym_attendance -e "
ANALYZE TABLE members;
ANALYZE TABLE attendance_logs;
ANALYZE TABLE admins;
ANALYZE TABLE rate_limits;
"
```

Check database size:
```bash
mysql -u gym_app_user -p gym_attendance -e "
SELECT table_name, ROUND(((data_length + index_length) / 1024 / 1024), 2) as size_mb
FROM information_schema.tables
WHERE table_schema = 'gym_attendance'
ORDER BY (data_length + index_length) DESC;
"
```

**Time:** 10 minutes

---

## Configuration Management

### Secrets Management Checklist

| Secret | Location | How Often | Who Manages |
|--------|----------|-----------|-------------|
| `APP_SECRET` | `.env` (server only) | Never rotate | Admin |
| `DB_PASS` | `.env` (server only) | Annually | Admin |
| `SMTP_PASSWORD` | `.env` (server only) | When changed | Admin |
| SSL Certificate | Hostinger cPanel | Auto-renew (Let's Encrypt) | Hostinger |

**Golden rules:**
- ✅ `.env` never in Git
- ✅ `.env` readable only by owner (`chmod 600`)
- ✅ Rotate secrets annually
- ✅ Log all access to `.env` (audit trail)

---

## Documentation Requirements

**Keep in production repo:**

1. **`README.md`** — Project overview, high-level setup
2. **`PRODUCTION_DEPLOYMENT_README.md`** — Hostinger-specific deployment steps
3. **`HOSTINGER_DEPLOYMENT_GUIDE.md`** — Detailed Hostinger integration
4. **`docs/admin-guide.md`** — How to use the application
5. **`docs/api.md`** — API documentation for integrations
6. **This audit** (Part D) — Maintenance procedures

**Remove before deploying:**
- Dev/Docker docs
- Demo/client guides
- Migration guides
- Change logs

**Update frequency:**
- After adding features: update README
- After security changes: update hardening docs
- After procedure changes: update runbooks

---

## Disk Usage Monitoring

**Action:** Check monthly:

```bash
du -sh /home/username/public_html/storage/*
df -h /home/username/  # Overall disk usage
```

**Alerts:**
- ⚠️ Logs > 500 MB → rotate more frequently
- ⚠️ Disk > 80% → request larger hosting plan or clean up
- ⚠️ Uploads > 1 GB → implement cleanup policy (old photos)

**Recommended disk allocation:**
- Code + assets: ~200 MB
- Database: ~100-200 MB (grows with usage)
- Logs: ~100 MB (rotate monthly)
- Uploads: ~500 MB - 2 GB (depends on policy)
- **Total:** 5-10 GB minimum

---

## Monitoring & Alerts (Optional)

If you want to be notified of problems:

### Option A: Email Alerts (Free)

Create `scripts/health_check.php`:

```php
<?php
chdir(dirname(__DIR__));
require 'src/bootstrap.php';

$issues = [];

// Check database connection
try {
    \App\Core\Database::connection();
} catch (Throwable $e) {
    $issues[] = "Database: " . $e->getMessage();
}

// Check disk space
$diskFree = disk_free_bytes(getcwd()) / (1024 * 1024);
if ($diskFree < 500) {
    $issues[] = "Low disk space: {$diskFree} MB free";
}

// Check log file size
if (filesize('storage/logs/app.log') > 100 * 1024 * 1024) {
    $issues[] = "Log file > 100 MB";
}

// Email alert if issues found
if (!empty($issues)) {
    mail(
        (string) \App\Core\Config::get('ADMIN_ALERT_EMAIL'),
        'Gym Attendance Checker - Health Alert',
        implode("\n", $issues),
        "From: " . (string) \App\Core\Config::get('MAIL_FROM_ADDRESS')
    );
}
```

Schedule weekly:
```bash
0 9 * * 1 /usr/bin/php /home/username/public_html/scripts/health_check.php
```

### Option B: External Monitoring (Paid)

- **Uptime monitoring:** Pingdom, StatusCake, UptimeRobot (monitor 5-minute heartbeat endpoint)
- **Error tracking:** Sentry, Bugsnag (integrate with logger)
- **Database backups:** Hostinger's automated backups + external offsite copy

---

## Disaster Recovery Runbook

If something breaks in production:

### Step 1: Immediate Damage Control
```bash
# 1. Identify the problem
tail -100 storage/logs/app.log

# 2. If application broken, show maintenance page
mv public/index.php public/index.php.bak
echo "Site under maintenance. Check back soon." > public/maintenance.html
```

### Step 2: Restore from Backup
```bash
# Latest database backup
mysql -u gym_app_user -p gym_attendance < /path/to/backup.sql

# Or, if you kept .backups folder:
cd /home/username/public_html
tar -xzf .backups/gym-attendance-before-cleanup_20260428_120000.tar.gz
```

### Step 3: Verify & Bring Back Online
```bash
# Restore index.php
mv public/index.php.bak public/index.php

# Test
curl -I https://your-domain.com

# Check logs
tail -20 storage/logs/app.log
```

---

## Maintenance Checklist Template

Create a Google Sheets or local document:

```
MAINTENANCE CHECKLIST — Gym Attendance Checker

Weekly (Sunday 3 AM):
  ☐ Cron job runs maintenance.php (check logs)
  ☐ Log rotation successful

Monthly:
  ☐ Check disk usage (du -sh storage/*)
  ☐ Run composer audit
  ☐ Review error logs for patterns
  ☐ Test backup restore (if automated)

Quarterly:
  ☐ Run securityheaders.com scan
  ☐ Update dependencies (composer update)
  ☐ Database optimization (ANALYZE TABLE)
  ☐ Review access logs for suspicious activity

Annually:
  ☐ Rotate APP_SECRET
  ☐ Rotate DB_PASS (Hostinger)
  ☐ Rotate SMTP credentials
  ☐ Penetration test or security audit
  ☐ Review all .env settings
```

---

## Summary: Time Investment

| Task | Frequency | Time | Effort |
|------|-----------|------|--------|
| Pre-deployment setup | One-time | 4 hours | High |
| Cron jobs monitoring | Weekly | 1 min | Low |
| Log rotation | Automated | 0 min | None |
| Composer audit | Monthly | 15 min | Low |
| Backup verification | Monthly | 5 min | Low |
| Security audit | Quarterly | 10 min | Low |
| Database maintenance | Quarterly | 10 min | Low |
| **Total (per year)** | — | **~50 hours** | **~1 hour/week** |

**Conclusion:** After initial setup (~4 hours), maintenance requires only **1 hour per week** for a health application.

---

## Next Steps

1. Follow Phase 1 & 2 above to deploy to Hostinger
2. Set up cron jobs (Phase 3.1)
3. Create backup system (Phase 3.3)
4. Proceed to **Part E (Verification & Runbook)**
