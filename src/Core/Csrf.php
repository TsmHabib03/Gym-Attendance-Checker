<?php

declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    private const SESSION_KEY = '_csrf_token';
    private const TOKEN_BYTES = 32;

    public static function token(): string
    {
        if (!isset($_SESSION[self::SESSION_KEY])
            || !is_string($_SESSION[self::SESSION_KEY])
            || strlen($_SESSION[self::SESSION_KEY]) !== self::TOKEN_BYTES * 2) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(self::TOKEN_BYTES));
        }

        return $_SESSION[self::SESSION_KEY];
    }

    public static function validate(?string $token): bool
    {
        if (!is_string($token) || $token === '' || strlen($token) > 128) {
            return false;
        }

        if (!isset($_SESSION[self::SESSION_KEY]) || !is_string($_SESSION[self::SESSION_KEY])) {
            return false;
        }

        return hash_equals($_SESSION[self::SESSION_KEY], $token);
    }

    public static function assertValid(?string $token): void
    {
        if (!self::validate($token)) {
            Logger::audit('csrf_failure', null, [
                'ip' => Request::ip(),
                'method' => $_SERVER['REQUEST_METHOD'] ?? '',
                'path' => $_SERVER['REQUEST_URI'] ?? '',
            ]);

            if (!headers_sent()) {
                http_response_code(419);
                header('Content-Type: text/plain; charset=utf-8');
                header('Cache-Control: no-store');
            }
            echo 'Invalid CSRF token.';
            exit;
        }
    }

    /**
     * Roll the CSRF token. Should be called on logout / privilege change.
     */
    public static function rotate(): void
    {
        unset($_SESSION[self::SESSION_KEY]);
    }
}
