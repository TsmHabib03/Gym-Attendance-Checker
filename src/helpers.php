<?php

declare(strict_types=1);

if (!function_exists('e')) {
    function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
    }
}

if (!function_exists('e_attr')) {
    /**
     * Stricter escape for HTML attribute contexts. Use for any attribute that
     * holds user-controlled data (data-*, title, alt, value).
     */
    function e_attr(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
    }
}

if (!function_exists('csp_nonce')) {
    /**
     * Per-request CSP nonce — emit this on every <script> tag we author so the
     * strict CSP in public/index.php accepts it.
     */
    function csp_nonce(): string
    {
        return (string) ($GLOBALS['__CSP_NONCE'] ?? '');
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
        $url = url('/assets/' . ltrim($path, '/'));
        $file = dirname(__DIR__) . '/public/assets/' . ltrim($path, '/');
        if (is_file($file)) {
            $url .= '?v=' . filemtime($file);
        }
        return $url;
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
