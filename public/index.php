<?php

declare(strict_types=1);

use App\Controllers\AttendanceController;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\MemberController;
use App\Core\Auth;
use App\Core\Config;
use App\Core\Logger;
use App\Core\Request;

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'bootstrap.php';

// -----------------------------------------------------------------------------
// Security headers — defense in depth
// -----------------------------------------------------------------------------
$cspNonce = base64_encode(random_bytes(16));
$GLOBALS['__CSP_NONCE'] = $cspNonce;

$isHttps = (
    (($_SERVER['HTTPS'] ?? '') !== '' && strtolower((string) $_SERVER['HTTPS']) !== 'off')
    || ((int) ($_SERVER['SERVER_PORT'] ?? 0) === 443)
    || (strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https')
);

header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('X-Permitted-Cross-Domain-Policies: none');
header('Cross-Origin-Opener-Policy: same-origin');
header('Cross-Origin-Resource-Policy: same-origin');
header('Permissions-Policy: camera=(self), geolocation=(), microphone=(), payment=(), usb=()');

if ($isHttps) {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

// CSP — inline scripts must use the per-request nonce. We keep 'unsafe-inline'
// only for style-src because Tailwind CDN injects styles at runtime.
$csp = "default-src 'self'; "
    . "base-uri 'self'; "
    . "form-action 'self'; "
    . "frame-ancestors 'self'; "
    . "object-src 'none'; "
    . "script-src 'self' 'nonce-" . $cspNonce . "' https://cdn.tailwindcss.com https://unpkg.com; "
    . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; "
    . "img-src 'self' data: blob:; "
    . "font-src 'self' https://fonts.gstatic.com data:; "
    . "connect-src 'self'; "
    . "media-src 'self' blob:; "
    . "worker-src 'self' blob:";
header('Content-Security-Policy: ' . $csp);

// -----------------------------------------------------------------------------
// Routing
// -----------------------------------------------------------------------------
$method = Request::method();
$path = Request::path();

$authController = new AuthController();
$dashboardController = new DashboardController();
$memberController = new MemberController();
$attendanceController = new AttendanceController();

try {
    if ($method === 'GET' && $path === '/') {
        redirect(Auth::check() ? '/dashboard' : '/login');
    }

    if ($method === 'GET' && $path === '/login') {
        $authController->showLogin();
        return;
    }

    if ($method === 'POST' && $path === '/login') {
        $authController->login();
        return;
    }

    if ($method === 'POST' && $path === '/logout') {
        $authController->logout();
        return;
    }

    if ($method === 'GET' && $path === '/dashboard') {
        $dashboardController->index();
        return;
    }

    if ($method === 'POST' && $path === '/settings') {
        $dashboardController->saveSettings();
        return;
    }

    if ($method === 'GET' && $path === '/members') {
        $memberController->index();
        return;
    }

    if ($method === 'GET' && $path === '/members/create') {
        $memberController->createForm();
        return;
    }

    if ($method === 'POST' && $path === '/members/create') {
        $memberController->create();
        return;
    }

    if ($method === 'GET' && $path === '/members/edit') {
        $memberController->editForm();
        return;
    }

    if ($method === 'GET' && $path === '/members/qr') {
        $memberController->qrCard();
        return;
    }

    if ($method === 'GET' && $path === '/members/qr-bulk') {
        $memberController->qrBulk();
        return;
    }

    if ($method === 'POST' && $path === '/members/edit') {
        $memberController->update();
        return;
    }

    if ($method === 'POST' && $path === '/members/delete') {
        $memberController->delete();
        return;
    }

    if ($method === 'POST' && $path === '/api/members/regenerate-qr') {
        $memberController->regenerateQr();
        return;
    }

    if ($method === 'GET' && $path === '/attendance/scan') {
        $attendanceController->scanner();
        return;
    }

    if ($method === 'POST' && $path === '/api/checkin') {
        $attendanceController->checkinApi();
        return;
    }

    http_response_code(404);
    echo 'Not found';
} catch (Throwable $throwable) {
    Logger::error('Unhandled application exception', [
        'message' => $throwable->getMessage(),
        'path' => $path,
        'method' => $method,
    ]);

    if (!headers_sent()) {
        http_response_code(500);
    }

    // SECURITY: Never leak exception text to clients, even in debug mode.
    // Real diagnostics live in storage/logs/app.log.
    echo Config::bool('APP_DEBUG', false)
        ? 'Internal error (debug). Check application logs.'
        : 'Unexpected application error. Please try again.';
}
