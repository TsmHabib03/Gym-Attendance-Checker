<?php

declare(strict_types=1);

namespace App\Core;

final class Config
{
    public static function get(string $key, mixed $default = null): mixed
    {
        $value = Env::get($key);
        return $value ?? $default;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $value = self::get($key);
        if ($value === null) {
            return $default;
        }

        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
    }

    public static function int(string $key, int $default = 0): int
    {
        $value = self::get($key);
        if ($value === null || !is_numeric($value)) {
            return $default;
        }

        return (int) $value;
    }
}
