<?php

declare(strict_types=1);

namespace App\Core;

use DateTimeImmutable;
use Throwable;

final class Logger
{
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
                ':event_type' => $eventType,
                ':event_context' => json_encode($context, JSON_UNESCAPED_SLASHES),
                ':created_at' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
            ]);
        } catch (Throwable) {
            // Keep runtime resilient even if audit table is not available yet.
        }
    }

    private static function write(string $level, string $message, array $context): void
    {
        $date = (new DateTimeImmutable())->format('Y-m-d H:i:s');
        $line = sprintf("[%s] %s: %s %s%s", $date, strtoupper($level), $message, json_encode($context, JSON_UNESCAPED_SLASHES), PHP_EOL);
        $logDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0775, true);
        }

        file_put_contents($logDir . DIRECTORY_SEPARATOR . 'app.log', $line, FILE_APPEND);
    }
}
