<?php

declare(strict_types=1);

namespace App\Core;

use DateTime;
use InvalidArgumentException;

final class Validator
{
    /**
     * Sanitize a string: enforce UTF-8, strip control characters (except
     * tab/newline), normalize whitespace, and apply a length cap.
     */
    public static function string(mixed $value, int $max = 255): string
    {
        if (!is_string($value) && !is_numeric($value)) {
            return '';
        }

        $value = (string) $value;

        // Strip BOM.
        if (str_starts_with($value, "\xEF\xBB\xBF")) {
            $value = substr($value, 3);
        }

        // Force valid UTF-8 — anything invalid is replaced with U+FFFD.
        if (function_exists('mb_convert_encoding')) {
            $converted = @mb_convert_encoding($value, 'UTF-8', 'UTF-8');
            if (is_string($converted)) {
                $value = $converted;
            }
        }

        // Drop NUL and most control bytes; keep tab (\t) and newline (\n).
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value) ?? '';

        $value = trim($value);

        if ($max > 0 && mb_strlen($value, 'UTF-8') > $max) {
            $value = mb_substr($value, 0, $max, 'UTF-8');
        }

        return $value;
    }

    public static function requiredString(mixed $value, string $fieldName, int $max = 255): string
    {
        $clean = self::string($value, $max + 1);

        if ($clean === '') {
            throw new InvalidArgumentException($fieldName . ' is required.');
        }

        if (mb_strlen($clean, 'UTF-8') > $max) {
            throw new InvalidArgumentException($fieldName . ' must not exceed ' . $max . ' characters.');
        }

        return $clean;
    }

    public static function date(mixed $value, string $fieldName): string
    {
        $value = self::requiredString($value, $fieldName, 20);
        $date = DateTime::createFromFormat('Y-m-d', $value);
        if (!$date || $date->format('Y-m-d') !== $value) {
            throw new InvalidArgumentException($fieldName . ' must be a valid date in Y-m-d format.');
        }

        return $value;
    }

    public static function int(mixed $value, string $fieldName): int
    {
        if (filter_var($value, FILTER_VALIDATE_INT) === false) {
            throw new InvalidArgumentException($fieldName . ' must be a valid integer.');
        }

        return (int) $value;
    }

    public static function email(mixed $value, string $fieldName, int $max = 160): string
    {
        $clean = self::string($value, $max);
        if ($clean === '') {
            return '';
        }

        if (!filter_var($clean, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException($fieldName . ' must be a valid email address.');
        }

        return $clean;
    }

    /**
     * Validate that a value is one of an allowlist (case-insensitive).
     */
    public static function inEnum(mixed $value, array $allowed, string $fieldName): string
    {
        $clean = strtolower(self::requiredString($value, $fieldName, 60));
        $allowedLower = array_map('strtolower', $allowed);

        if (!in_array($clean, $allowedLower, true)) {
            throw new InvalidArgumentException($fieldName . ' must be one of: ' . implode(', ', $allowed) . '.');
        }

        return $clean;
    }

    /**
     * Validate a hex token of expected lengths (e.g., 48 or 64 chars).
     */
    public static function hexToken(mixed $value, array $allowedLengths, string $fieldName): string
    {
        $clean = strtolower(self::requiredString($value, $fieldName, 200));
        if (!preg_match('/^[a-f0-9]+$/', $clean)) {
            throw new InvalidArgumentException($fieldName . ' must be a hex token.');
        }
        if (!in_array(strlen($clean), $allowedLengths, true)) {
            throw new InvalidArgumentException($fieldName . ' has an unexpected length.');
        }

        return $clean;
    }
}
