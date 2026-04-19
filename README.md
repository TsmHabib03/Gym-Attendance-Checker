# Gym Attendance Checker

Production-ready standard-scope startup web app for QR attendance and membership monitoring.

## Stack
- Frontend: HTML, Tailwind CSS, JavaScript
- Backend: PHP 8+
- Database: MySQL 8+
- QR scanning: ZXing
- Email alerts: PHPMailer

## Core Features
- QR-based check-in with automatic attendance logging
- Member details shown immediately after scan:
  - Name
  - Picture
  - Membership end date
- Membership status indicator (Active or Expired)
- Expired membership denial with alert and logs
- Duplicate scan protection window
- Optional photo capture on check-in
- Dashboard for attendance and membership overview
- Member management (add/edit)

## Security and Reliability
- .env-based secret management
- CSRF protection
- Input validation/sanitization
- Output escaping
- PDO prepared statements
- Session hardening
- Admin route guard
- Rate limiting (login and check-in)
- Reverse proxy/load balancer IP awareness
- Centralized file logging + audit trail + email logs

## Folder Structure
- public: Front controller, uploads, assets
- src: Core, controllers, services, repositories
- views: Tailwind page templates
- database: schema and seed files
- scripts: automation helpers
- docs: API and operations documentation
- storage/logs: runtime logs

## Quick Start
1. Install dependencies: composer install
2. Copy .env.example to .env and update values
3. Import database/schema.sql then database/seed.sql
4. Set web root to public directory
5. Login with seeded admin account and start onboarding members

## Docker Compose Quick Start
1. Copy .env.docker.example to .env.docker and update values
2. Run: docker compose --env-file .env.docker up -d --build
3. Open: http://localhost:8080

## Default Seed Admin
- Username: admin
- Password: Admin@123

Change credentials immediately after initial setup.

## Additional Documentation
- docs/api.md
- docs/docker-compose.md
- docs/setup.md
- docs/security-checklist.md
- docs/testing-checklist.md
- docs/admin-guide.md
