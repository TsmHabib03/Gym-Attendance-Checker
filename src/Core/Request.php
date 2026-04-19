<?php

declare(strict_types=1);

namespace App\Core;

final class Request
{
    public static function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public static function path(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH);

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
        $raw = file_get_contents('php://input');
        if (!$raw) {
            return [];
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    public static function ip(): string
    {
        $remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $trustedProxies = array_filter(array_map('trim', explode(',', (string) Config::get('TRUSTED_PROXIES', '127.0.0.1'))));

        if (in_array($remoteAddr, $trustedProxies, true)) {
            $forwardedFor = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
            if ($forwardedFor !== '') {
                $ips = array_map('trim', explode(',', $forwardedFor));
                if (!empty($ips)) {
                    return (string) $ips[0];
                }
            }
        }

        return $remoteAddr;
    }

    public static function isAjax(): bool
    {
        $header = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
        return strtolower($header) === 'xmlhttprequest';
    }
}
