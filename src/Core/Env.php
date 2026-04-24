<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;
use Dotenv\Dotenv;

final class Env
{
    private static bool $loaded = false;

    public static function load(string $rootPath): void
    {
        if (self::$loaded) {
            return;
        }

        $envPath = $rootPath . DIRECTORY_SEPARATOR . '.env';

        // SECURITY: Never silently copy .env.example → .env. Missing configuration
        // must fail loudly so production deployments cannot run with insecure
        // placeholder secrets. Operators must explicitly provision a .env file.
        if (!file_exists($envPath)) {
            throw new RuntimeException(
                'Missing .env file at ' . $envPath . '. Copy .env.example to .env and populate '
                . 'real secrets before starting the application.'
            );
        }

        // Reject world-readable .env in non-Windows environments (basic hygiene).
        if (DIRECTORY_SEPARATOR === '/' && function_exists('fileperms')) {
            $perms = @fileperms($envPath);
            if ($perms !== false && ($perms & 0o004) === 0o004) {
                // Log only; do not abort — deployments may rely on root-owned files.
                error_log('[SECURITY] .env file is world-readable: ' . $envPath);
            }
        }

        if (class_exists(Dotenv::class)) {
            Dotenv::createImmutable($rootPath)->safeLoad();
        } else {
            self::loadManually($envPath);
        }

        self::$loaded = true;
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        if ($value === false || $value === null || $value === '') {
            return $default;
        }

        return (string) $value;
    }

    public static function require(string $key): string
    {
        $value = self::get($key);
        if ($value === null) {
            throw new RuntimeException('Missing required environment key: ' . $key);
        }

        return $value;
    }

    private static function loadManually(string $envPath): void
    {
        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value, " \t\n\r\0\x0B\"");

            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
            putenv($key . '=' . $value);
        }
    }
}
