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
        if (!file_exists($envPath) && file_exists($rootPath . DIRECTORY_SEPARATOR . '.env.example')) {
            copy($rootPath . DIRECTORY_SEPARATOR . '.env.example', $envPath);
        }

        if (class_exists(Dotenv::class) && file_exists($envPath)) {
            Dotenv::createImmutable($rootPath)->safeLoad();
        } elseif (file_exists($envPath)) {
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
