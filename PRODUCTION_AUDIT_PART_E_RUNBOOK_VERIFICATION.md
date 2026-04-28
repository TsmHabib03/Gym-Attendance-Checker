# Production Audit: Part E — Final Runbook & Verification Steps

**Hostinger Deployment Runbook** | **Target:** Live Production | **Risk:** Medium

---

## Pre-Deployment Checklist (24 Hours Before Go-Live)

### A. Code Preparation (Local Dev Machine)

```bash
# 1. Clean up dev files
bash production-cleanup.sh

# 2. Update dependencies
composer install --no-dev --optimize-autoloader

# 3. Verify syntax
find src views -name '*.php' -exec php -l {} \;

# 4. Run static analysis (if available)
composer require --dev phpstan/phpstan
./vendor/bin/phpstan analyse src/ --level=5
```

**Checklist:**
- [ ] All PHP files syntax-valid
- [ ] No dev dependencies in vendor/
- [ ] composer.lock is clean (no conflicts)
- [ ] All .phtml files exist and are readable

---

### B. Security Pre-Flight Checks

```bash
# 1. Verify .env is NOT in Git
grep -l "^APP_SECRET=" .env
git check-ignore .env && echo "✓ .env in .gitignore" || echo "✗ .env exposed!"

# 2. Check for hardcoded secrets in code
grep -r "password\|secret\|key\|token" src/ views/ --include="*.php" | \
  grep -v "password_hash\|password_verify\|CONFIG" || echo "✓ No hardcoded secrets"

# 3. Verify security headers in code
grep -c "Strict-Transport-Security\|Content-Security-Policy" public/index.php || \
  echo "✗ Security headers missing!"

# 4. Check database queries for injection risks (spot check)
grep -r "SELECT\|INSERT\|UPDATE\|DELETE" src/ --include="*.php" | \
  grep -v "prepare\|?" && echo "✗ Found non-prepared queries!" || echo "✓ All prepared"
```

**Checklist:**
- [ ] `.env` NOT in Git
- [ ] No hardcoded secrets in code
- [ ] Security headers present
- [ ] All SQL uses prepared statements

---

## Step 1: Hostinger Account Setup (1 hour)

### 1.1 Enable SSL/TLS Certificate

**Action:**
1. Log into Hostinger cPanel
2. Navigate to **SSL/TLS Status** (or AutoSSL)
3. Click **Manage** or **Install** for your domain
4. Select **Let's Encrypt** (free, auto-renew)
5. Verify installation successful

**Verification:**
```bash
# From your machine:
curl -I https://your-domain.com
# Should NOT show certificate error
```

---

### 1.2 Create MySQL User & Database

**Action:**
1. Go to cPanel → **MySQL Databases**
2. Click **Create New Database**
   - Name: `gym_attendance` (or `yourname_gymdb`)
   - Note the full name: `cpanelusername_gymdb`

3. Click **MySQL Users** → Create new user
   - User: `gym_app_user`
   - Password: Generate strong password (20+ chars, mixed case, numbers, symbols)
   - Store securely in a password manager

4. Click **Add User to Database**
   - Select database and user
   - Grant only: `SELECT`, `INSERT`, `UPDATE`, `DELETE`
   - Deny: `CREATE`, `DROP`, `ALTER`, `GRANT`

**Verification:**
```bash
# Via Hostinger terminal (if available) or phpMyAdmin:
mysql -u gym_app_user -p yourname_gymdb -e "SELECT 1;"
# Should return: 1
```

---

### 1.3 Create FTP User (If Deploying via FTP)

**Action:**
1. Go to cPanel → **FTP Accounts**
2. Create account:
   - Name: `deploy` (or similar)
   - Password: Strong, unique
   - Directory: `/home/cpanelusername/public_html`
   - Quota: Unlimited or 1000 MB

**Verification:**
```bash
# From your machine, test connection:
ftp -u ftp://deploy%40your-domain.com@ftp.your-domain.com
# Should connect successfully
```

---

## Step 2: Deploy Code to Hostinger (30 minutes)

### 2.1 Via SFTP (Recommended)

**Tools:** Cyberduck (Mac), WinSCP (Windows), FileZilla (All)

**Action:**
1. Open SFTP client
2. Connect:
   - Host: `your-domain.com` or `ftp.your-domain.com`
   - User: `deploy` (or FTP user created above)
   - Password: FTP password
   - Port: 22 (SFTP) or 21 (FTP)

3. Navigate to `/home/cpanelusername/public_html/`

4. Upload all files **EXCEPT**:
   - ❌ `vendor/` (will regenerate on server)
   - ❌ `.git/` (not needed)
   - ❌ `docker/`, `k8s/`, `*.sh`, `*.ps1`
   - ❌ `.env` (create on server)
   - ❌ Demo/seed files (part A cleanup)

5. Verify upload:
   - All PHP files present
   - All directories present (src, views, public, storage, etc.)
   - `composer.json` and `composer.lock` present

---

### 2.2 Via Git (If Hostinger Supports SSH Access)

**Action:**
```bash
# On Hostinger terminal (via cPanel → Terminal):
cd /home/cpanelusername/public_html

# Clone repo (if public) or pull (if private with SSH key)
git clone https://your-repo.git .

# Or, if already cloned:
git pull origin main

# Clean up git artifacts
rm -rf .git docker k8s *.sh *.ps1 .dockerignore .env.docker*
```

---

### 2.3 Create `.env` on Server

**Action:** Via cPanel File Manager or SSH:

```bash
# SSH preferred (more secure):
ssh cpanelusername@your-domain.com
cd public_html

# Create .env with production secrets:
cat > .env << 'EOF'
APP_ENV=production
APP_NAME="Gym Attendance Checker"
APP_URL=https://your-domain.com
APP_TIMEZONE=Asia/Manila
APP_DEBUG=false
APP_SECRET=[GENERATE: php -r "echo bin2hex(random_bytes(32));"]

SESSION_COOKIE_NAME=gym_attendance_session
SESSION_SECURE=true
SESSION_SAMESITE=Lax
SESSION_LIFETIME=7200

TRUSTED_PROXIES=127.0.0.1

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
DB_NAME=cpanelusername_gymdb
DB_USER=gym_app_user
DB_PASS=[Strong password from MySQL setup]

SMTP_HOST=smtp.your-email-provider.com
SMTP_PORT=587
SMTP_USERNAME=noreply@your-domain.com
SMTP_PASSWORD=[SMTP password]
SMTP_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@your-domain.com
MAIL_FROM_NAME="Gym Attendance Checker"
ADMIN_ALERT_EMAIL=your-email@gmail.com
EOF

# Secure permissions
chmod 600 .env

# Verify
cat .env | head -5
```

---

### 2.4 Set File Permissions

**Action:**
```bash
# On Hostinger terminal:
cd /home/cpanelusername/public_html

# PHP files readable by web server
find . -type f -name '*.php' -exec chmod 644 {} \;
find . -type f -name '*.phtml' -exec chmod 644 {} \;

# Directories traversable
find . -type d -exec chmod 755 {} \;

# Writable directories (for logs, uploads)
chmod -R 775 storage/
chmod -R 775 public/uploads/

# Secrets readable only by owner
chmod 600 .env .user.ini

# Verify
ls -la .env storage/ public/uploads/
```

---

### 2.5 Install Dependencies

**Action:**
```bash
# On Hostinger terminal:
cd /home/cpanelusername/public_html

# Check if composer is available
composer --version

# If not found, install:
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install production dependencies
composer install --no-dev --optimize-autoloader --no-interaction

# Verify
ls -la vendor/
```

---

### 2.6 Initialize Database

**Action:**
```bash
# Via Hostinger phpMyAdmin or terminal:

# Option A: Terminal
mysql -u gym_app_user -p cpanelusername_gymdb < database/schema.sql
mysql -u gym_app_user -p cpanelusername_gymdb < database/migrations/002_convert_member_codes_to_sequential.sql

# Option B: phpMyAdmin (cPanel → phpMyAdmin)
# 1. Select database `cpanelusername_gymdb`
# 2. Click "Import"
# 3. Upload `database/schema.sql`
# 4. Repeat for migrations
```

**Verification:**
```bash
mysql -u gym_app_user -p cpanelusername_gymdb -e "SHOW TABLES;"
# Should show: admins, members, attendance_logs, rate_limits
```

---

## Step 3: Verification & Testing (45 minutes)

### 3.1 Application Bootstrap Test

**Action:**
```bash
# On Hostinger terminal:
cd /home/cpanelusername/public_html

# Test PHP syntax
php -l public/index.php
php -l src/bootstrap.php

# Test bootstrap loads
php -r "require_once 'src/bootstrap.php'; echo 'Bootstrap OK';"

# Check for fatal errors
php -r "error_reporting(E_ALL); require_once 'src/bootstrap.php';" 2>&1 | grep -i error && \
  echo "✗ Errors found!" || echo "✓ Bootstrap OK"
```

**Expected output:**
```
Parse error: No parse errors detected
Bootstrap OK
✓ Bootstrap OK
```

---

### 3.2 Database Connectivity Test

**Action:**
```bash
# Test DB connection
php -r "
require_once 'src/bootstrap.php';
try {
    \$pdo = \App\Core\Database::connection();
    \$result = \$pdo->query('SELECT COUNT(*) FROM admins');
    echo 'Database OK, admins count: ' . \$result->fetchColumn() . PHP_EOL;
} catch (\Exception \$e) {
    echo 'Database ERROR: ' . \$e->getMessage() . PHP_EOL;
}
"
```

**Expected output:**
```
Database OK, admins count: 0
```

---

### 3.3 HTTP Request Test

**Action:**
```bash
# From your local machine:
curl -I https://your-domain.com

# Check for:
# - HTTP/2 200 or 301 (redirect to HTTPS)
# - Strict-Transport-Security header
# - Content-Security-Policy header
# - X-Frame-Options: SAMEORIGIN
```

**Expected output:**
```
HTTP/2 200 OK
Strict-Transport-Security: max-age=31536000; includeSubDomains
Content-Security-Policy: default-src 'self'; ...
X-Frame-Options: SAMEORIGIN
X-Content-Type-Options: nosniff
```

---

### 3.4 Security Headers Audit

**Action:**
```bash
# Using online tool:
# 1. Visit https://securityheaders.com
# 2. Enter your domain: https://your-domain.com
# 3. Review results (aim for A or A+ grade)

# Or, check manually:
curl -sI https://your-domain.com | grep -E "^(Strict-Transport|Content-Security|X-Frame|X-Content)"
```

---

### 3.5 Login Test

**Action:**
1. Open browser: `https://your-domain.com`
2. You should see login page (redirects from `/` to `/login`)
3. Try logging in with default admin (if you seeded one)

**If no admin exists, create one via MySQL:**
```bash
# Generate password hash (locally or on server):
php -r "echo password_hash('your-secure-password', PASSWORD_BCRYPT, ['cost' => 12]);"

# Insert into database:
mysql -u gym_app_user -p cpanelusername_gymdb -e "
INSERT INTO admins (username, password_hash, created_at, updated_at)
VALUES ('admin', '[HASH FROM ABOVE]', NOW(), NOW());
"

# Verify
mysql -u gym_app_user -p cpanelusername_gymdb -e "SELECT id, username FROM admins;"
```

4. Login and verify dashboard loads

---

### 3.6 Check-In API Test

**Action:**
```bash
# Get CSRF token from dashboard
# Then test check-in API:

curl -X POST https://your-domain.com/checkin \
  -H "Content-Type: application/json" \
  -H "X-Requested-With: XMLHttpRequest" \
  -H "X-CSRF-Token: [YOUR_CSRF_TOKEN]" \
  -d '{"qr_token":"test-token"}'

# Expected response:
# {"ok":false,"message":"..."} (member not found, but API works)
```

---

### 3.7 Email Configuration Test

**Action:**
```bash
# Test SMTP connection
php -r "
require_once 'vendor/autoload.php';
\$mail = new PHPMailer\PHPMailer\PHPMailer(true);
\$mail->isSMTP();
\$mail->Host = getenv('SMTP_HOST');
\$mail->Port = getenv('SMTP_PORT');
\$mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
\$mail->SMTPAuth = true;
\$mail->Username = getenv('SMTP_USERNAME');
\$mail->Password = getenv('SMTP_PASSWORD');

try {
    \$mail->connect();
    echo 'SMTP connection OK' . PHP_EOL;
} catch (\Exception \$e) {
    echo 'SMTP ERROR: ' . \$e->getMessage() . PHP_EOL;
}
"
```

---

### 3.8 Disk Space & Permissions

**Action:**
```bash
# Check disk usage
du -sh /home/cpanelusername/public_html/
df -h /home/cpanelusername/

# Verify permissions
ls -la /home/cpanelusername/public_html/.env
ls -la /home/cpanelusername/public_html/storage/
ls -la /home/cpanelusername/public_html/public/uploads/

# Should see:
# .env: -rw------- (600)
# storage/: drwxrwxr-x (775)
# uploads/: drwxrwxr-x (775)
```

---

## Step 4: Security Hardening Final Checks (30 minutes)

### 4.1 HTTPS Enforcement

**Test:** Visit `http://your-domain.com` (without `https://`)

**Expected:** Auto-redirects to `https://your-domain.com` with 301 status

**If not redirecting:**
- Add HTTPS redirect to `public/index.php` (see Part C)
- Or configure in Hostinger cPanel → AutoSSL redirect

---

### 4.2 .env Verification

**Test:**
```bash
# Attempt to access .env via HTTP (should be denied)
curl https://your-domain.com/.env
# Expected: 403 Forbidden
```

**If accessible:** Your `.htaccess` is not working. Check:
1. `.htaccess` is in project root AND `public/`
2. mod_rewrite is enabled (Hostinger default)

---

### 4.3 Session Fingerprinting Test

**Test:**
1. Login to admin panel
2. Note the session cookie name (from DevTools → Cookies)
3. Open a different browser (or private window)
4. Manually set the cookie (using browser extension or proxy)
5. Try to access `/dashboard`

**Expected:** Session rejected (fingerprint mismatch)

---

### 4.4 Rate Limiting Test

**Test:**
```bash
# Attempt 6 rapid login requests
for i in {1..6}; do
  curl -X POST https://your-domain.com/login \
    -d "username=admin&password=wrong" \
    -w "Status: %{http_code}\n"
done

# First 5 should return 200/302
# 6th should return 429 (Too Many Requests)
```

---

### 4.5 CSRF Protection Test

**Test:**
```bash
# Try to submit form without CSRF token
curl -X POST https://your-domain.com/settings \
  -d "key=value"

# Expected: 419 (CSRF token invalid)
```

---

## Step 5: Final Pre-Launch Checklist

Print and verify each item:

```
PRODUCTION DEPLOYMENT CHECKLIST

PRE-DEPLOYMENT:
☐ Code cleanup (vendor, .git, docker, etc.)
☐ Dependencies optimized (--no-dev)
☐ No hardcoded secrets in code
☐ Security headers verified in code
☐ All SQL uses prepared statements

HOSTINGER SETUP:
☐ SSL/TLS certificate installed (Let's Encrypt)
☐ MySQL database created (gym_attendance)
☐ MySQL user created with least privilege
☐ FTP/SFTP account created

DEPLOYMENT:
☐ All files uploaded (except vendor/)
☐ .env created on server with production secrets
☐ File permissions set correctly (600, 644, 755, 775)
☐ Dependencies installed (composer install --no-dev)
☐ Database schema loaded
☐ Admin user created (if needed)

VERIFICATION:
☐ PHP syntax valid (php -l)
☐ Bootstrap loads (php -r)
☐ Database connection works
☐ HTTP requests work (curl -I)
☐ Security headers present
☐ Login page loads
☐ Admin login works
☐ API check-in responds
☐ SMTP connection works
☐ HTTPS enforced
☐ .env not accessible via HTTP (403)
☐ Rate limiting works (429 on limit)
☐ CSRF protection works (419 without token)

FINAL:
☐ Disk usage acceptable (< 80%)
☐ Log file initialized
☐ Uploads directory writable
☐ Backup system configured
☐ Cron jobs scheduled
☐ Security audit passed
☐ Team signed off
```

---

## Go-Live Procedure

### Step 1: Announce Downtime (If Needed)

If migrating from old system:
- Notify users: "Service maintenance from X to Y"
- Provide status page: "Check back soon"

### Step 2: Final Data Sync

```bash
# Backup old system data (if applicable)
mysqldump old_database > old_database_backup.sql

# Load new schema
mysql gym_attendance < database/schema.sql

# Import any existing data (if necessary)
mysql gym_attendance < path/to/migration_data.sql
```

### Step 3: DNS Switch (If New Domain)

1. Point domain DNS to Hostinger nameservers
2. Wait for propagation (can take 24 hours)
3. Verify: `nslookup your-domain.com` returns Hostinger IPs

### Step 4: Test End-to-End

1. Open app in browser
2. Test login
3. Test member check-in
4. Test email notifications
5. Check logs for errors: `tail -50 storage/logs/app.log`

### Step 5: Monitor First 24 Hours

- Watch logs: `tail -f storage/logs/app.log`
- Monitor disk usage: `du -sh storage/`
- Check for email delivery issues
- Verify SSL certificate is valid

---

## Rollback Procedure (If Something Breaks)

### Quick Rollback (< 5 minutes)

```bash
# 1. Identify the problem
tail -50 storage/logs/app.log

# 2. If code issue, restore from backup
cd /home/cpanelusername/public_html
git revert HEAD  # If using Git

# 3. Or restore from tarball backup
tar -xzf /path/to/backup.tar.gz

# 4. Restore database (if needed)
mysql gym_attendance < /path/to/backup.sql

# 5. Test
curl -I https://your-domain.com
```

### Extended Rollback (If Needed)

Contact Hostinger support:
- Ask for file system restore from backup
- Request database restore from daily backup
- Estimated time: 30 minutes to 2 hours

---

## Post-Launch (First Week)

### Daily
- Check logs: `tail -20 storage/logs/app.log`
- Verify no errors
- Monitor SMTP delivery

### End of Week 1
- Confirm cron jobs running (`scripts/maintenance.php`)
- Verify backups are being created
- Check disk usage trending

### End of Month 1
- Run security headers audit (securityheaders.com)
- Check composer for vulnerabilities: `composer audit`
- Review performance metrics (response times, errors)

---

## Common Issues & Troubleshooting

### Issue: "Database connection failed"
**Cause:** `.env` has wrong DB credentials  
**Fix:**
```bash
# Verify database user exists
mysql -u gym_app_user -p yourname_gymdb -e "SELECT 1;"

# Check .env
cat .env | grep "^DB_"

# Recreate user if needed (via cPanel → MySQL Users)
```

---

### Issue: ".env not found" or "Missing required env var"
**Cause:** `.env` not created on server  
**Fix:**
```bash
# SSH to server
ssh cpanelusername@your-domain.com
cd public_html

# Verify .env exists
ls -la .env

# If not, create it (see Step 2.3 above)
# Then test bootstrap
php -r "require_once 'src/bootstrap.php'; echo 'OK';"
```

---

### Issue: "Permission denied" on uploads
**Cause:** Directory permissions too restrictive  
**Fix:**
```bash
chmod -R 775 public/uploads/
chmod 755 public/uploads/.htaccess
ls -la public/uploads/  # Verify 775 (drwxrwxr-x)
```

---

### Issue: SSL certificate shows as untrusted
**Cause:** Let's Encrypt certificate not installed or expired  
**Fix:**
1. Go to cPanel → SSL/TLS Status
2. Click "Install" or "Manage" for your domain
3. Select Let's Encrypt
4. Wait 5-10 minutes for installation
5. Verify: `curl -I https://your-domain.com` (no SSL errors)

---

### Issue: SMTP emails not sending
**Cause:** Wrong SMTP credentials or host blocked  
**Fix:**
```bash
# Test SMTP connection (run PHP code from Step 3.7 above)
# If fails:
# 1. Verify credentials in .env are correct
# 2. Check SMTP_HOST and SMTP_PORT
# 3. Contact Hostinger support (some plans have SMTP restrictions)
# 4. Or use third-party SMTP: SendGrid, Mailgun, AWS SES
```

---

## Support Contacts

| Issue | Contact |
|-------|---------|
| Hosting/cPanel/SSL | Hostinger Support (chat or ticket) |
| Domain/DNS | Domain registrar (GoDaddy, Namecheap, etc.) |
| SMTP/Email | Email provider (Hostinger, SendGrid, etc.) |
| Application bugs | Development team |

---

## Success Criteria

Your deployment is **successful** when:

✅ HTTPS enforces (HTTP→HTTPS redirect works)  
✅ Dashboard login works  
✅ Member check-in API responds  
✅ Security headers present (A+ on securityheaders.com)  
✅ Logs are being written to `storage/logs/app.log`  
✅ No PHP errors in logs  
✅ Email notifications send successfully  
✅ Disk usage < 80%  
✅ Backups are being created automatically  
✅ Cron jobs running (maintenance.php)  

---

## Next: Operations & Maintenance

Refer to **Part D (Maintainability Plan)** for ongoing operations.

Good luck! 🚀
