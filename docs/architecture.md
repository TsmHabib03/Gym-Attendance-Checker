# Architecture Summary

Gym Attendance Checker follows a lightweight modular PHP architecture designed for startup-friendly deployment on shared hosting while preserving production security controls.

## High-Level Flow
1. Browser loads pages via public/index.php front controller.
2. Controllers enforce admin auth and CSRF for state changes.
3. Services execute business rules (membership checks, duplicate guard, email alerts).
4. Repositories run all database operations using PDO prepared statements.
5. Logs and audit events are recorded for traceability.

## Main Layers
- Core: Environment loading, session hardening, request parsing, validation, rate limiting, CSRF, logging.
- Controllers: Route-specific request handling.
- Services: Business workflows for members, attendance, dashboard, and alerts.
- Repositories: Data access with prepared statements.
- Views: Tailwind-based server-rendered pages.

## Security and Infrastructure Controls
- Secret management through .env.
- CSRF checks on form and API updates.
- Input validation and sanitization in service/controller boundary.
- Output escaping helper for templates.
- Login and check-in rate limits.
- Duplicate scan cooldown guard.
- Reverse proxy aware client IP extraction via trusted proxy list.
- Session cookie hardening (httponly, samesite, secure option).
- Audit logs and file logs for operations visibility.

## Scale and Load Balancer Readiness
- Stateless request handling except session state.
- Compatible with sticky sessions for early scale.
- Migration path to shared session store (for example Redis) for horizontal scaling.
- Trusted proxy handling protects accurate rate-limit keys and logs when behind load balancer.

## Runtime Integrations
- ZXing in scanner page for QR decoding.
- PHPMailer SMTP integration for expired scan and reminder notifications.
- Daily reminder script for near-expiry emails.
