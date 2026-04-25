<?php

declare(strict_types=1);

namespace App\Core;

use DateTimeImmutable;
use Throwable;

final class Logger
{
    /** Hard cap on the size a single log line can reach. */
    private const MAX_LINE_BYTES = 16 * 1024;

    /** Truncate any log file at this many bytes (rotate by truncation). */
    private const MAX_FILE_BYTES = 25 * 1024 * 1024;

    public static function error(string $message, array $context = []): void
    {
        self::write('error', $message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::write('info', $message, $context);
    }

    public static function audit(string $eventType, ?int $adminId, array $context = []): void
    {
        self::write('audit', $eventType, $context);

        try {
            $pdo = Database::connection();
            $stmt = $pdo->prepare('INSERT INTO audit_logs (admin_id, event_type, event_context, created_at) VALUES (:admin_id, :event_type, :event_context, :created_at)');
            $stmt->execute([
                ':admin_id' => $adminId,
                ':event_type' => self::sanitizeForLog($eventType),
                ':event_context' => (string) json_encode(self::scrubContext($context), JSON_UNESCAPED_SLASHES),
                ':created_at' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
            ]);
        } catch (Throwable) {
            // Audit table may not exist yet at first boot — never let a logging
            // failure abort the request.
        }
    }

    private static function write(string $level, string $message, array $context): void
    {
        $date = (new DateTimeImmutable())->format('Y-m-d H:i:s');
        $cleanMessage = self::sanitizeForLog($message);
        $cleanContext = json_encode(self::scrubContext($context), JSON_UNESCAPED_SLASHES) ?: '{}';
        $line = sprintf(
            "[%s] %s: %s %s%s",
            $date,
            strtoupper($level),
            $cleanMessage,
            $cleanContext,
            PHP_EOL
        );

        if (strlen($line) > self::MAX_LINE_BYTES) {
            $line = substr($line, 0, self::MAX_LINE_BYTES - 16) . "...truncated\n";
        }

        $logDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0750, true);
        }

        $logFile = $logDir . DIRECTORY_SEPARATOR . 'app.log';

        // Naive rotation by truncation — production should ship logs to a
        // durable sink, but this prevents a runaway log from filling disk.
        if (is_file($logFile) && @filesize($logFile) > self::MAX_FILE_BYTES) {
            @rename($logFile, $logFile . '.' . date('YmdHis'));
        }

        @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
        @chmod($logFile, 0640);
    }

    /**
     * Strip CR/LF and other control characters so an attacker cannot inject
     * fake log lines through user-controlled input.
     */
    private static function sanitizeForLog(string $value): string
    {
        $value = (string) preg_replace('/[\x00-\x08\x0A-\x1F\x7F]/u', ' ', $value);
        if (strlen($value) > 4000) {
            $value = substr($value, 0, 4000) . '…';
        }
        return $value;
    }

    /**
     * Walk the context array, sanitize every scalar, and redact sensitive keys.
     */
    private static function scrubContext(array $context): array
    {
        $redactedKeys = ['password', 'pass', 'pwd', 'secret', 'token', 'authorization', 'cookie', '_csrf', 'csrf'];
        $out = [];
        foreach ($context as $k => $v) {
            $key = is_string($k) ? self::sanitizeForLog($k) : (string) $k;

            $lcKey = strtolower($key);
            $isRedacted = false;
            foreach ($redactedKeys as $needle) {
                if (str_contains($lcKey, $needle)) {
                    $isRedacted = true;
                    break;
                }
            }

            if ($isRedacted) {
                $out[$key] = '[REDACTED]';
                continue;
            }

            if (is_array($v)) {
                $out[$key] = self::scrubContext($v);
            } elseif (is_scalar($v) || $v === null) {
                $out[$key] = is_string($v) ? self::sanitizeForLog($v) : $v;
            } else {
                $out[$key] = '[unserializable]';
            }
        }
        return $out;
    }
}
