<?php

declare(strict_types=1);

namespace App\Core;

final class Validator
{
    public static function string(mixed $value, int $max = 255): string
    {
        $value = is_string($value) ? trim($value) : '';
        $value = filter_var($value, FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_LOW);
        if (mb_strlen($value) > $max) {
            $value = mb_substr($value, 0, $max);
        }

        return $value;
    }

    public static function requiredString(mixed $value, string $fieldName, int $max = 255): string
    {
        $value = is_string($value) ? trim($value) : '';
        $value = filter_var($value, FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_LOW);

        if ($value === '') {
            throw new \InvalidArgumentException($fieldName . ' is required.');
        }

        if (mb_strlen($value) > $max) {
            throw new \InvalidArgumentException($fieldName . ' must not exceed ' . $max . ' characters.');
        }

        return $value;
    }

    public static function date(mixed $value, string $fieldName): string
    {
        $value = self::requiredString($value, $fieldName, 20);
        $date = \DateTime::createFromFormat('Y-m-d', $value);
        if (!$date || $date->format('Y-m-d') !== $value) {
            throw new \InvalidArgumentException($fieldName . ' must be a valid date in Y-m-d format.');
        }

        return $value;
    }

    public static function int(mixed $value, string $fieldName): int
    {
        if (filter_var($value, FILTER_VALIDATE_INT) === false) {
            throw new \InvalidArgumentException($fieldName . ' must be a valid integer.');
        }

        return (int) $value;
    }
}
