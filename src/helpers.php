<?php

declare(strict_types=1);

if (!function_exists('e')) {
    function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('app_base_path')) {
    function app_base_path(): string
    {
        $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
        $basePath = dirname($scriptName);

        if ($basePath === '/' || $basePath === '.' || $basePath === '\\') {
            return '';
        }

        return rtrim($basePath, '/');
    }
}

if (!function_exists('url')) {
    function url(string $path = '/'): string
    {
        if ($path === '') {
            $path = '/';
        }

        if (preg_match('/^https?:\/\//i', $path) === 1) {
            return $path;
        }

        $normalizedPath = '/' . ltrim($path, '/');
        $basePath = app_base_path();

        return $basePath === '' ? $normalizedPath : $basePath . $normalizedPath;
    }
}

if (!function_exists('redirect')) {
    function redirect(string $path): void
    {
        $target = preg_match('/^https?:\/\//i', $path) === 1 ? $path : url($path);
        header('Location: ' . $target);
        exit;
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        return url('/assets/' . ltrim($path, '/'));
    }
}

if (!function_exists('old')) {
    function old(string $key, string $default = ''): string
    {
        $value = $_SESSION['_old'][$key] ?? $default;
        if (isset($_SESSION['_old'][$key])) {
            unset($_SESSION['_old'][$key]);
        }

        return (string) $value;
    }
}

if (!function_exists('flash')) {
    function flash(string $key, ?string $message = null): ?string
    {
        if ($message === null) {
            $value = $_SESSION['_flash'][$key] ?? null;
            if (isset($_SESSION['_flash'][$key])) {
                unset($_SESSION['_flash'][$key]);
            }

            return $value;
        }

        $_SESSION['_flash'][$key] = $message;
        return null;
    }
}
