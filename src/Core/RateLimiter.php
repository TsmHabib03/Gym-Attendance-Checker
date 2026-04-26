<?php

declare(strict_types=1);

namespace App\Core;

use DateTimeImmutable;

final class RateLimiter
{
    /**
     * Record one attempt and return whether it is allowed.
     *
     * Race-condition fix: the original SELECT → UPDATE sequence allowed two
     * concurrent requests to both read the same row, both decide "not exceeded",
     * and both increment — effectively letting 2× the allowed attempts through.
     *
     * The fix wraps everything in a transaction and uses SELECT … FOR UPDATE to
     * acquire a row-level exclusive lock before reading. Any second request for
     * the same (action, key_hash) row blocks at the SELECT until the first
     * commits, serialising all concurrent attempts for the same key.
     *
     * For a brand-new key (no row yet) we use INSERT IGNORE so that two
     * simultaneous first-hits do not both succeed — only one INSERT lands, the
     * other sees 0 affected rows and falls through to the locked SELECT.
     */
    public static function hit(string $action, string $key, int $maxAttempts, int $windowSeconds): array
    {
        $pdo       = Database::connection();
        $hashedKey = hash('sha256', $action . '|' . $key);
        $now       = new DateTimeImmutable();
        $nowStr    = $now->format('Y-m-d H:i:s');

        // Seed the row so the FOR UPDATE below always finds it, even on first hit.
        // INSERT IGNORE is a no-op when the UNIQUE key (action, key_hash) already exists.
        $pdo->prepare(
            'INSERT IGNORE INTO rate_limits
                (action, key_hash, attempts, window_started_at, blocked_until, updated_at)
             VALUES
                (:action, :key_hash, 0, :window_started_at, NULL, :updated_at)'
        )->execute([
            ':action'            => $action,
            ':key_hash'          => $hashedKey,
            ':window_started_at' => $nowStr,
            ':updated_at'        => $nowStr,
        ]);

        $pdo->beginTransaction();

        try {
            // FOR UPDATE locks the row for the duration of this transaction,
            // blocking any concurrent request on the same key until we COMMIT.
            $select = $pdo->prepare(
                'SELECT id, attempts, window_started_at, blocked_until
                   FROM rate_limits
                  WHERE action = :action AND key_hash = :key_hash
                  LIMIT 1
                  FOR UPDATE'
            );
            $select->execute([':action' => $action, ':key_hash' => $hashedKey]);
            $row = $select->fetch();

            if (!$row) {
                // Should never happen after the INSERT IGNORE above, but guard defensively.
                $pdo->rollBack();
                return ['allowed' => true, 'retry_after' => 0];
            }

            // If still inside an active block window, reject without counting.
            if (!empty($row['blocked_until'])) {
                $blockedUntil = new DateTimeImmutable((string) $row['blocked_until']);
                if ($blockedUntil > $now) {
                    $pdo->rollBack();
                    return [
                        'allowed'     => false,
                        'retry_after' => $blockedUntil->getTimestamp() - $now->getTimestamp(),
                    ];
                }
            }

            $windowStartedAt = new DateTimeImmutable((string) $row['window_started_at']);
            $isWindowExpired = ($now->getTimestamp() - $windowStartedAt->getTimestamp()) >= $windowSeconds;

            $attempts       = $isWindowExpired ? 1 : ((int) $row['attempts'] + 1);
            $newWindowStart = $isWindowExpired ? $now : $windowStartedAt;
            $blockedUntil   = null;
            $allowed        = true;
            $retryAfter     = 0;

            if ($attempts > $maxAttempts) {
                $allowed      = false;
                $blockedUntil = $now->modify('+' . $windowSeconds . ' seconds');
                $retryAfter   = $windowSeconds;
            }

            $pdo->prepare(
                'UPDATE rate_limits
                    SET attempts          = :attempts,
                        window_started_at = :window_started_at,
                        blocked_until     = :blocked_until,
                        updated_at        = :updated_at
                  WHERE id = :id'
            )->execute([
                ':attempts'           => $attempts,
                ':window_started_at'  => $newWindowStart->format('Y-m-d H:i:s'),
                ':blocked_until'      => $blockedUntil?->format('Y-m-d H:i:s'),
                ':updated_at'         => $nowStr,
                ':id'                 => $row['id'],
            ]);

            $pdo->commit();

            return ['allowed' => $allowed, 'retry_after' => $retryAfter];

        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public static function purgeStale(int $retentionDays = 7): int
    {
        $retentionDays = max(1, $retentionDays);
        $cutoff        = (new DateTimeImmutable())
            ->modify('-' . $retentionDays . ' days')
            ->format('Y-m-d H:i:s');

        $pdo    = Database::connection();
        $delete = $pdo->prepare('DELETE FROM rate_limits WHERE updated_at < :cutoff');
        $delete->execute([':cutoff' => $cutoff]);

        return $delete->rowCount();
    }
}
