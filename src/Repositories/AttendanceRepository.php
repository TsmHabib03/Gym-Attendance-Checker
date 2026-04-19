<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use DateTimeImmutable;

final class AttendanceRepository
{
    public function findRecentAccepted(int $memberId, int $seconds): ?array
    {
        $pdo = Database::connection();
        $threshold = (new DateTimeImmutable())->modify('-' . $seconds . ' seconds')->format('Y-m-d H:i:s');

        $stmt = $pdo->prepare('SELECT * FROM attendance_logs WHERE member_id = :member_id AND status = :status AND scanned_at >= :threshold ORDER BY scanned_at DESC LIMIT 1');
        $stmt->execute([
            ':member_id' => $memberId,
            ':status' => 'accepted',
            ':threshold' => $threshold,
        ]);

        $log = $stmt->fetch();
        return $log ?: null;
    }

    public function logScan(array $data): int
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('INSERT INTO attendance_logs (member_id, status, note, ip_address, checkin_photo_path, scanned_at) VALUES (:member_id, :status, :note, :ip_address, :checkin_photo_path, :scanned_at)');
        $stmt->execute([
            ':member_id' => $data['member_id'],
            ':status' => $data['status'],
            ':note' => $data['note'],
            ':ip_address' => $data['ip_address'],
            ':checkin_photo_path' => $data['checkin_photo_path'],
            ':scanned_at' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
        ]);

        return (int) $pdo->lastInsertId();
    }

    public function statsForToday(): array
    {
        $pdo = Database::connection();
        $today = (new DateTimeImmutable())->format('Y-m-d');

        $stmt = $pdo->prepare('SELECT 
            SUM(CASE WHEN status = "accepted" THEN 1 ELSE 0 END) AS accepted,
            SUM(CASE WHEN status = "expired_denied" THEN 1 ELSE 0 END) AS expired_denied,
            SUM(CASE WHEN status = "duplicate_denied" THEN 1 ELSE 0 END) AS duplicate_denied
            FROM attendance_logs
            WHERE DATE(scanned_at) = :today');
        $stmt->execute([':today' => $today]);
        $row = $stmt->fetch();

        return [
            'accepted' => (int) ($row['accepted'] ?? 0),
            'expired_denied' => (int) ($row['expired_denied'] ?? 0),
            'duplicate_denied' => (int) ($row['duplicate_denied'] ?? 0),
        ];
    }

    public function recentLogs(int $limit = 25): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT al.*, m.full_name, m.member_code, m.membership_end_date, m.photo_path
            FROM attendance_logs al
            INNER JOIN members m ON m.id = al.member_id
            ORDER BY al.scanned_at DESC
            LIMIT :limit');
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
