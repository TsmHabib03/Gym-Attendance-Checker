# Security Checklist

## Secret and Configuration Management
- [ ] .env is not committed to source control
- [ ] .env.example contains placeholders only
- [ ] APP_SECRET is set to high-entropy value
- [ ] SMTP and DB credentials are unique per environment

## Authentication and Sessions
- [ ] Passwords stored as bcrypt hashes
- [ ] Login endpoint has rate limiting
- [ ] Session cookie uses httponly
- [ ] Session cookie uses secure=true in HTTPS
- [ ] Session cookie uses SameSite policy
- [ ] Session ID regenerates on login

## Request Protection
- [ ] CSRF validation on all state-changing routes
- [ ] Input validation and sanitization server-side
- [ ] Output encoding on all user-driven content
- [ ] File uploads type and size restricted

## Data and Query Safety
- [ ] All SQL uses PDO prepared statements
- [ ] DB account has least privilege
- [ ] Audit logs enabled for key events
- [ ] Attendance logs persist denied/duplicate/expired attempts

## Edge and Infrastructure Controls
- [ ] Rate limiting on login and check-in API
- [ ] Duplicate scan guard active
- [ ] TRUSTED_PROXIES configured for reverse proxy/load balancer
- [ ] Security headers enabled (X-Frame-Options, nosniff, referrer policy)

## Alerts and Monitoring
- [ ] Expired scan email alerts configured
- [ ] Near-expiry reminders scheduled
- [ ] Failed email attempts recorded
- [ ] storage/logs monitored and rotated
