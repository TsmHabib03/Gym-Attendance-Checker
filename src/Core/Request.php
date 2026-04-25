<?php

declare(strict_types=1);

namespace App\Core;

final class Request
{
    /** Max raw JSON body we will accept (4 MB — large enough for a base64 photo). */
    private const MAX_JSON_BODY_BYTES = 4 * 1024 * 1024;

    public static function method(): string
    {
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        // Only allow standard verbs we actually route.
        return in_array($method, ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'HEAD'], true)
            ? $method
            : 'GET';
    }

    public static function path(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url((string) $uri, PHP_URL_PATH);

        if (!is_string($path) || $path === '') {
            return '/';
        }

        $path = '/' . ltrim($path, '/');

        $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
        $basePath = dirname($scriptName);
        if ($basePath !== '/' && $basePath !== '.' && $basePath !== '\\') {
            $basePath = rtrim($basePath, '/');
            if ($path === $basePath || str_starts_with($path, $basePath . '/')) {
                $path = substr($path, strlen($basePath));
                $path = $path === '' ? '/' : $path;
            }
        }

        // Reject path traversal sequences.
        if (str_contains($path, '..')) {
            return '/';
        }

        return rtrim($path, '/') ?: '/';
    }

    public static function input(string $key, mixed $default = null): mixed
    {
        $method = self::method();
        if ($method === 'GET') {
            return $_GET[$key] ?? $default;
        }

        return $_POST[$key] ?? $default;
    }

    public static function all(): array
    {
        return self::method() === 'GET' ? $_GET : $_POST;
    }

    public static function json(): array
    {
        // Cap body size before reading to avoid OOM via large request bodies.
        $contentLength = isset($_SERVER['CONTENT_LENGTH']) ? (int) $_SERVER['CONTENT_LENGTH'] : 0;
        if ($contentLength > self::MAX_JSON_BODY_BYTES) {
            return [];
        }

        $stream = fopen('php://input', 'rb');
        if (!is_resource($stream)) {
            return [];
        }

        $raw = stream_get_contents($stream, self::MAX_JSON_BODY_BYTES + 1);
        fclose($stream);

        if (!is_string($raw) || $raw === '' || strlen($raw) > self::MAX_JSON_BODY_BYTES) {
            return [];
        }

        $decoded = json_decode($raw, true, 32, JSON_THROW_ON_ERROR & 0); // do not throw
        return is_array($decoded) ? $decoded : [];
    }

    public static function ip(): string
    {
        $remoteAddr = (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
        $trustedProxies = array_filter(array_map('trim', explode(',', (string) Config::get('TRUSTED_PROXIES', '127.0.0.1'))));

        if ($trustedProxies && in_array($remoteAddr, $trustedProxies, true)) {
            $forwardedFor = (string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? '');
            if ($forwardedFor !== '') {
                $ips = array_map('trim', explode(',', $forwardedFor));
                foreach ($ips as $candidate) {
                    if ($candidate !== '' && filter_var($candidate, FILTER_VALIDATE_IP) !== false) {
                        return $candidate;
                    }
                }
            }
        }

        return filter_var($remoteAddr, FILTER_VALIDATE_IP) !== false ? $remoteAddr : '0.0.0.0';
    }

    public static function isAjax(): bool
    {
        $header = (string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '');
        return strtolower($header) === 'xmlhttprequest';
    }

    public static function header(string $name): string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return (string) ($_SERVER[$key] ?? '');
    }

    public static function userAgent(): string
    {
        return substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 512);
    }
}
