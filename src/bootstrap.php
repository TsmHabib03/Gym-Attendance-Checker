<?php

declare(strict_types=1);

use App\Core\Env;
use App\Core\Session;
use App\Core\Config;
use App\Core\Logger;

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

date_default_timezone_set((string) Config::get('APP_TIMEZONE', 'Asia/Manila'));
Session::start();

set_exception_handler(static function (Throwable $throwable): void {
    Logger::error('Unhandled exception', [
        'message' => $throwable->getMessage(),
        'file' => $throwable->getFile(),
        'line' => $throwable->getLine(),
    ]);

    http_response_code(500);
    $debug = \App\Core\Config::bool('APP_DEBUG', false);
    echo $debug ? 'Unhandled error: ' . e($throwable->getMessage()) : 'Something went wrong.';
});
