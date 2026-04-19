# Setup and Deployment Guide

## 1. Requirements
- PHP 8.0+
- MySQL 8+
- Composer 2+
- Web server: Apache or Nginx
- SSL certificate for production

Docker-based deployment is available in docs/docker-compose.md.

## 2. Install dependencies
1. Copy project files into your hosting directory.
2. Run:
   - composer install

## 3. Configure environment
1. Copy .env.example to .env
2. Update all values:
   - app URLs and secret
   - DB credentials
   - SMTP credentials
   - alert email target
3. Set APP_DEBUG=false on production.
4. Set SESSION_SECURE=true when HTTPS is enabled.

## 4. Database setup
1. Create database and schema:
   - import database/schema.sql
2. Seed initial records:
   - import database/seed.sql
3. Default admin account:
   - username: admin
   - password: Admin@123
   - Change this immediately after first login by updating DB hash workflow in your security process.

## 5. Web root
- Point document root to public directory.
- Ensure mod_rewrite is enabled if using Apache.

## 6. Upload directories permissions
- public/uploads/member_photos
- public/uploads/checkin_photos
- storage/logs

These directories must be writable by the web server user.

## 7. Scheduled reminders
- Schedule script scripts/send_expiry_reminders.php daily.
- Also schedule scripts/cleanup_rate_limits.php daily to purge stale throttling rows.
- Example (Windows Task Scheduler):
   - Program: C:/laragon/bin/php/php-8.3.30-Win32-vs16-x64/php.exe
   - Arguments: C:/laragon/www/gym-attendance-checker/scripts/send_expiry_reminders.php
- Cleanup task example (Windows Task Scheduler):
   - Program: C:/laragon/bin/php/php-8.3.30-Win32-vs16-x64/php.exe
   - Arguments: C:/laragon/www/gym-attendance-checker/scripts/cleanup_rate_limits.php

## 8. Production checklist
- HTTPS enabled
- SESSION_SECURE=true
- APP_DEBUG=false
- Strong APP_SECRET and DB credentials
- Regular DB backups
- Log rotation for storage/logs/app.log
- Restrict DB user permissions to least privilege

## 9. Load balancer readiness
- Keep application instances stateless except server-side session.
- Use sticky sessions initially on shared hosting/VPS.
- For horizontal scale, move sessions to shared store (for example Redis).
- Configure TRUSTED_PROXIES so client IP is resolved correctly from X-Forwarded-For.
