<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Config;
use App\Core\Csrf;
use App\Core\Logger;
use App\Core\RateLimiter;
use App\Core\Request;
use App\Core\View;
use App\Services\AttendanceService;
use Throwable;

final class AttendanceController
{
    private AttendanceService $attendance;

    public function __construct(?AttendanceService $attendance = null)
    {
        $this->attendance = $attendance ?? new AttendanceService();
    }

    public function scanner(): void
    {
        Auth::requireAdmin();

        View::render('attendance/scan', [
            'csrfToken' => Csrf::token(),
            'photoCaptureEnabled' => $this->attendance->photoCaptureEnabled(),
        ]);
    }

    public function checkinApi(): void
    {
        Auth::requireAdmin();

        header('Content-Type: application/json');

        $csrfHeader = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!Csrf::validate($csrfHeader)) {
            http_response_code(419);
            echo json_encode(['ok' => false, 'message' => 'Invalid CSRF token']);
            return;
        }

        $ip = Request::ip();
        $rate = RateLimiter::hit(
            'checkin',
            $ip,
            Config::int('CHECKIN_RATE_LIMIT_MAX_ATTEMPTS', 25),
            Config::int('CHECKIN_RATE_LIMIT_WINDOW_SECONDS', 60)
        );

        if (!$rate['allowed']) {
            http_response_code(429);
            echo json_encode([
                'ok' => false,
                'message' => 'Rate limit exceeded. Retry in ' . (int) $rate['retry_after'] . ' seconds.',
            ]);
            return;
        }

        $payload = Request::json();
        $token = (string) ($payload['qr_token'] ?? '');
        $photoData = isset($payload['photo_data']) ? (string) $payload['photo_data'] : null;

        try {
            $result = $this->attendance->checkIn($token, $ip, $photoData);
            echo json_encode(['ok' => true, 'data' => $result]);
        } catch (Throwable $throwable) {
            Logger::error('Check-in API failed', ['error' => $throwable->getMessage()]);
            http_response_code(422);
            echo json_encode(['ok' => false, 'message' => $throwable->getMessage()]);
        }
    }
}
