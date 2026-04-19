<?php

declare(strict_types=1);

namespace App\Core;

use DateTimeImmutable;

final class RateLimiter
{
    public static function hit(string $action, string $key, int $maxAttempts, int $windowSeconds): array
    {
        $pdo = Database::connection();
        $hashedKey = hash('sha256', $action . '|' . $key);
        $now = new DateTimeImmutable();

        $select = $pdo->prepare('SELECT id, attempts, window_started_at, blocked_until FROM rate_limits WHERE action = :action AND key_hash = :key_hash LIMIT 1');
        $select->execute([
            ':action' => $action,
            ':key_hash' => $hashedKey,
        ]);

        $row = $select->fetch();
        if (!$row) {
            $insert = $pdo->prepare('INSERT INTO rate_limits (action, key_hash, attempts, window_started_at, blocked_until, updated_at) VALUES (:action, :key_hash, :attempts, :window_started_at, :blocked_until, :updated_at)');
            $insert->execute([
                ':action' => $action,
                ':key_hash' => $hashedKey,
                ':attempts' => 1,
                ':window_started_at' => $now->format('Y-m-d H:i:s'),
                ':blocked_until' => null,
                ':updated_at' => $now->format('Y-m-d H:i:s'),
            ]);

            return ['allowed' => true, 'retry_after' => 0];
        }

        if (!empty($row['blocked_until'])) {
            $blockedUntil = new DateTimeImmutable((string) $row['blocked_until']);
            if ($blockedUntil > $now) {
                return ['allowed' => false, 'retry_after' => $blockedUntil->getTimestamp() - $now->getTimestamp()];
            }
        }

        $windowStartedAt = new DateTimeImmutable((string) $row['window_started_at']);
        $isWindowExpired = ($now->getTimestamp() - $windowStartedAt->getTimestamp()) >= $windowSeconds;

        $attempts = $isWindowExpired ? 1 : ((int) $row['attempts'] + 1);
        $blockedUntil = null;
        $allowed = true;
        $retryAfter = 0;

        if ($attempts > $maxAttempts) {
            $allowed = false;
            $blockedUntil = $now->modify('+' . $windowSeconds . ' seconds');
            $retryAfter = $windowSeconds;
        }

        $update = $pdo->prepare('UPDATE rate_limits SET attempts = :attempts, window_started_at = :window_started_at, blocked_until = :blocked_until, updated_at = :updated_at WHERE id = :id');
        $update->execute([
            ':attempts' => $attempts,
            ':window_started_at' => ($isWindowExpired ? $now : $windowStartedAt)->format('Y-m-d H:i:s'),
            ':blocked_until' => $blockedUntil?->format('Y-m-d H:i:s'),
            ':updated_at' => $now->format('Y-m-d H:i:s'),
            ':id' => $row['id'],
        ]);

        return ['allowed' => $allowed, 'retry_after' => $retryAfter];
    }

    public static function purgeStale(int $retentionDays = 7): int
    {
        $retentionDays = max(1, $retentionDays);
        $cutoff = (new DateTimeImmutable())->modify('-' . $retentionDays . ' days')->format('Y-m-d H:i:s');

        $pdo = Database::connection();
        $delete = $pdo->prepare('DELETE FROM rate_limits WHERE updated_at < :cutoff');
        $delete->execute([':cutoff' => $cutoff]);

        return $delete->rowCount();
    }
}
