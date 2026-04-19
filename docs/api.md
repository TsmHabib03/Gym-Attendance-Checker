# API Endpoints

Base URL depends on deployment (for example: http://localhost/gym-attendance-checker/public).

## Authentication

### POST /login
- Content type: application/x-www-form-urlencoded
- Fields:
  - _csrf: string
  - username: string
  - password: string
- Behavior:
  - Applies login rate limiting
  - Starts admin session on success

### POST /logout
- Content type: application/x-www-form-urlencoded
- Fields:
  - _csrf: string
- Behavior:
  - Destroys session and redirects to /login

## Member Management

### GET /members
- Query:
  - search (optional): filter by full name or member code
- Behavior:
  - Admin-only list page

### GET /members/create
- Behavior:
  - Admin-only create form

### POST /members/create
- Content type: multipart/form-data
- Fields:
  - _csrf: string
  - full_name: string (required)
  - email: string (optional)
  - membership_end_date: Y-m-d (required)
  - photo: file (optional, jpg/png/webp)
- Behavior:
  - Creates member with QR token and member code

### GET /members/edit?id={id}
- Behavior:
  - Admin-only edit form

### POST /members/edit
- Content type: multipart/form-data
- Fields:
  - _csrf: string
  - id: integer
  - full_name: string
  - email: string (optional)
  - membership_end_date: Y-m-d
  - photo: file (optional)
- Behavior:
  - Updates member profile

## Dashboard and Settings

### GET /dashboard
- Behavior:
  - Admin-only dashboard data and settings panel

### POST /settings
- Content type: application/x-www-form-urlencoded
- Fields:
  - _csrf: string
  - photo_capture_enabled: 1 or omitted
  - expiry_reminder_days: integer
- Behavior:
  - Updates app settings in database

## QR Check-in API

### POST /api/checkin
- Content type: application/json
- Headers:
  - X-CSRF-TOKEN: CSRF token from scanner page
  - X-Requested-With: XMLHttpRequest
- Body:
  - qr_token: lowercase hex string, 48 characters (required)
  - photo_data: data URL image (optional)
- Behavior:
  - Applies check-in rate limiting
  - Loads member by QR token
  - Returns member details and membership state
  - Logs attendance attempt with status
  - Enforces duplicate scan cool-down

### Success response
{
  "ok": true,
  "data": {
    "member": {
      "id": 1,
      "full_name": "Juan Dela Cruz",
      "photo_path": "/uploads/member_photos/member_xxx.jpg",
      "membership_end_date": "2026-05-01",
      "member_code": "MBR-A1B2C3"
    },
    "membership_status": "Active",
    "scan_status": "accepted",
    "message": "Check-in accepted.",
    "scanned_at": "2026-04-19 08:31:00"
  }
}

### Error response
{
  "ok": false,
  "message": "Membership expired. Check-in denied."
}

### Status codes
- 200: Check-in request handled. Result can be accepted or denied in payload.
- 419: Missing or invalid CSRF token.
- 422: Validation or business-rule failure (invalid token, member not found, expired, duplicate).
- 429: Rate limit exceeded.
