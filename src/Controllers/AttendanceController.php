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
use InvalidArgumentException;
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

        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

        $csrfHeader = (string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        if (!Csrf::validate($csrfHeader)) {
            http_response_code(419);
            echo json_encode(['ok' => false, 'message' => 'Invalid CSRF token.']);
            return;
        }

        $ip = Request::ip();

        // Per-IP rate limit (the hot path).
        $rate = RateLimiter::hit(
            'checkin',
            $ip,
            Config::int('CHECKIN_RATE_LIMIT_MAX_ATTEMPTS', 25),
            Config::int('CHECKIN_RATE_LIMIT_WINDOW_SECONDS', 60)
        );

        if (!$rate['allowed']) {
            http_response_code(429);
            header('Retry-After: ' . (int) ($rate['retry_after'] ?? 30));
            echo json_encode([
                'ok' => false,
                'message' => 'Rate limit exceeded. Retry in '
                    . (int) ($rate['retry_after'] ?? 0) . ' seconds.',
            ]);
            return;
        }

        // Per-admin rate limit (defends against a single admin's session being
        // automated regardless of which IP it routes through).
        $userKey = (string) (Auth::id() ?? $ip);
        $userRate = RateLimiter::hit(
            'checkin_user',
            $userKey,
            Config::int('CHECKIN_RATE_LIMIT_MAX_ATTEMPTS', 25) * 2,
            Config::int('CHECKIN_RATE_LIMIT_WINDOW_SECONDS', 60)
        );
        if (!$userRate['allowed']) {
            http_response_code(429);
            header('Retry-After: ' . (int) ($userRate['retry_after'] ?? 30));
            echo json_encode([
                'ok' => false,
                'message' => 'Per-user rate limit exceeded.',
            ]);
            return;
        }

        $payload = Request::json();

        // Strict shape validation up front.
        $token = isset($payload['qr_token']) && is_string($payload['qr_token'])
            ? trim($payload['qr_token'])
            : '';
        $photoData = isset($payload['photo_data']) && is_string($payload['photo_data'])
            ? $payload['photo_data']
            : null;

        if ($token === '' || strlen($token) > 200) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'message' => 'Missing or invalid QR token.']);
            return;
        }

        try {
            $result = $this->attendance->checkIn($token, $ip, $photoData);
            echo json_encode(['ok' => true, 'data' => $result]);
        } catch (InvalidArgumentException $throwable) {
            // Validation errors — safe to show to the client.
            http_response_code(422);
            echo json_encode(['ok' => false, 'message' => $throwable->getMessage()]);
        } catch (Throwable $throwable) {
            Logger::error('Check-in API failed', [
                'error' => $throwable->getMessage(),
                'ip' => $ip,
            ]);
            http_response_code(500);
            echo json_encode(['ok' => false, 'message' => 'Server error. Please try again.']);
        }
    }
}
