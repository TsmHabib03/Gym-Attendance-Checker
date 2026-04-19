# Testing Checklist

## Authentication
- [ ] Valid admin login succeeds
- [ ] Invalid login fails with flash message
- [ ] Repeated invalid login attempts trigger rate limit
- [ ] Logout invalidates session

## Member Management
- [ ] Create member with valid fields succeeds
- [ ] Invalid email or date is rejected
- [ ] Photo upload accepts jpg/png/webp only
- [ ] Edit member updates fields properly

## QR Check-in
- [ ] Active member scan records accepted check-in
- [ ] Expired member scan returns denied status with alert
- [ ] Duplicate scan within cool-down is denied
- [ ] Scan outside cool-down is accepted
- [ ] Optional photo capture saves check-in photo path

## Dashboard and Settings
- [ ] Dashboard counters match attendance table
- [ ] Recent logs table shows latest events
- [ ] Toggling photo capture updates check-in behavior
- [ ] Expiry reminder days setting persists

## Email and Scheduler
- [ ] Expired scan sends alert email
- [ ] Reminder script sends near-expiry emails
- [ ] Email logs record sent and failed attempts

## Security
- [ ] CSRF token missing or invalid is rejected
- [ ] SQL injection payloads do not alter query behavior
- [ ] Rendered member fields are escaped in UI
- [ ] Client IP is correctly resolved behind proxy
