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

header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header("Content-Security-Policy: default-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'; object-src 'none'; script-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://unpkg.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; img-src 'self' data: blob:; font-src 'self' https://fonts.gstatic.com data:; connect-src 'self';");
header('Permissions-Policy: camera=(self), geolocation=(), microphone=()');

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

    http_response_code(500);
    if (Config::bool('APP_DEBUG', false)) {
        echo 'Unexpected application error: ' . e($throwable->getMessage());
        return;
    }

    echo 'Unexpected application error. Please try again.';
}
