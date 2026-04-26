<?php

declare(strict_types=1);

namespace App\Core;

final class Session
{
    /** Absolute maximum session lifetime regardless of activity (seconds). */
    private const ABSOLUTE_LIFETIME = 28800; // 8 hours

    /** Idle timeout — session ends if no requests within this window. */
    private const IDLE_TIMEOUT = 1800; // 30 minutes

    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        // Default true — sessions MUST carry the Secure flag in production (HTTPS).
        // Only set SESSION_SECURE=false in .env for local HTTP development.
        $secure = Config::bool('SESSION_SECURE', true);
        $sameSite = (string) Config::get('SESSION_SAMESITE', 'Lax');
        $lifetime = Config::int('SESSION_LIFETIME', 7200);
        $cookieName = (string) Config::get('SESSION_COOKIE_NAME', 'gym_attendance_session');

        // Force HttpOnly + Secure-as-configured + SameSite at the ini layer too.
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_secure', $secure ? '1' : '0');
        ini_set('session.cookie_samesite', $sameSite);
        ini_set('session.gc_maxlifetime', (string) $lifetime);
        ini_set('session.sid_length', '64');
        ini_set('session.sid_bits_per_character', '6');

        session_name($cookieName);
        session_set_cookie_params([
            'lifetime' => $lifetime,
            'path' => '/',
            'domain' => '',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => $sameSite,
        ]);

        session_start();

        // Enforce idle + absolute timeouts. We only enforce on authenticated
        // sessions so login pages still work for anonymous visitors.
        if (isset($_SESSION['admin_id'])) {
            $now = time();
            $loginAt = (int) ($_SESSION['login_at'] ?? $now);
            $lastActivity = (int) ($_SESSION['last_activity'] ?? $now);

            if (($now - $loginAt) > self::ABSOLUTE_LIFETIME
                || ($now - $lastActivity) > self::IDLE_TIMEOUT) {
                self::destroy();
                self::start(); // start a fresh anonymous session
                return;
            }

            $_SESSION['last_activity'] = $now;
        }
    }

    public static function regenerate(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                (bool) $params['secure'],
                (bool) $params['httponly']
            );
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    /**
     * Bind the current session to a fingerprint of the user-agent and a
     * truncated IP. We hash with APP_SECRET so the stored value is opaque.
     */
    public static function bindFingerprint(): void
    {
        $_SESSION['_fp'] = self::computeFingerprint();
        $_SESSION['last_activity'] = time();
    }

    public static function isFingerprintValid(): bool
    {
        if (!isset($_SESSION['admin_id'])) {
            return true; // nothing to bind
        }

        $stored = (string) ($_SESSION['_fp'] ?? '');
        if ($stored === '') {
            return false;
        }

        return hash_equals($stored, self::computeFingerprint());
    }

    private static function computeFingerprint(): string
    {
        $secret = (string) Config::get('APP_SECRET', 'fallback_session_secret');
        $ua = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
        // Use only the /24 (IPv4) or /64 (IPv6) prefix so legitimate carrier
        // NAT / IP rotation does not invalidate sessions, while still binding
        // them to the rough network location.
        $ip = self::truncateIp(Request::ip());

        return hash_hmac('sha256', $ua . '|' . $ip, $secret);
    }

    private static function truncateIp(string $ip): string
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $ip);
            if (count($parts) === 4) {
                return $parts[0] . '.' . $parts[1] . '.' . $parts[2] . '.0';
            }
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $packed = inet_pton($ip);
            if ($packed !== false) {
                $prefix = substr($packed, 0, 8) . str_repeat("\0", 8);
                $back = inet_ntop($prefix);
                return $back === false ? $ip : $back;
            }
        }

        return $ip;
    }
}
