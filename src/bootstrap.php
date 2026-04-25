<?php

declare(strict_types=1);

use App\Core\Config;
use App\Core\Env;
use App\Core\Logger;
use App\Core\Session;

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

Env::load(dirname(__DIR__));

$requiredKeys = [
    'APP_ENV',
    'APP_NAME',
    'APP_URL',
    'APP_SECRET',
    'DB_HOST',
    'DB_PORT',
    'DB_NAME',
    'DB_USER',
    'SMTP_HOST',
    'SMTP_PORT',
    'SMTP_USERNAME',
    'SMTP_PASSWORD',
    'MAIL_FROM_ADDRESS',
    'MAIL_FROM_NAME',
    'ADMIN_ALERT_EMAIL',
];

foreach ($requiredKeys as $key) {
    if (Env::get($key) === null) {
        throw new RuntimeException('Missing required environment variable: ' . $key);
    }
}

// SECURITY: Refuse to boot on a placeholder APP_SECRET — it backs CSRF tokens
// and the session fingerprint HMAC.
$appSecret = (string) Env::get('APP_SECRET', '');
$forbiddenSecrets = ['change_this_secret', 'change_this_to_a_long_random_secret', '', 'secret'];
if (strtolower(Config::get('APP_ENV', 'production')) === 'production'
    && (in_array(strtolower($appSecret), $forbiddenSecrets, true) || strlen($appSecret) < 32)) {
    error_log('[SECURITY] Refusing to boot in production with weak APP_SECRET.');
    http_response_code(500);
    echo 'Server misconfiguration. See logs.';
    exit;
}

date_default_timezone_set((string) Config::get('APP_TIMEZONE', 'Asia/Manila'));

// SECURITY: never display PHP warnings/errors to the browser; always log.
$debug = Config::bool('APP_DEBUG', false);
ini_set('display_errors', $debug ? '1' : '0');
ini_set('display_startup_errors', $debug ? '1' : '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

Session::start();

set_exception_handler(static function (Throwable $throwable): void {
    Logger::error('Unhandled exception', [
        'message' => $throwable->getMessage(),
        'file' => $throwable->getFile(),
        'line' => $throwable->getLine(),
    ]);

    if (!headers_sent()) {
        http_response_code(500);
    }

    // Never leak exception messages to clients — even in debug we only show a
    // short, safe label. Real diagnostics live in storage/logs/app.log.
    echo \App\Core\Config::bool('APP_DEBUG', false)
        ? 'Internal error (debug). Check application logs.'
        : 'Something went wrong.';
});

set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    Logger::error('PHP error', [
        'severity' => $severity,
        'message' => $message,
        'file' => $file,
        'line' => $line,
    ]);
    // Convert to ErrorException so set_exception_handler covers it uniformly.
    throw new ErrorException($message, 0, $severity, $file, $line);
});
