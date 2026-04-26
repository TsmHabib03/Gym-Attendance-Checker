<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use DateTimeImmutable;

final class MemberRepository
{
    /** Hard ceiling on rows returned in a single page — prevents OOM on large gyms. */
    private const MAX_PAGE_SIZE = 200;

    /**
     * Return a paginated member list.
     *
     * @param int $limit  Rows per page (capped at MAX_PAGE_SIZE).
     * @param int $offset Zero-based row offset.
     */
    public function findAll(?string $search = null, int $limit = 100, int $offset = 0): array
    {
        $limit  = max(1, min($limit, self::MAX_PAGE_SIZE));
        $offset = max(0, $offset);

        $pdo = Database::connection();

        $baseSelect = "SELECT m.*, COALESCE(al.cnt, 0) AS attendance_count
            FROM members m
            LEFT JOIN (
                SELECT member_id, COUNT(*) AS cnt
                FROM attendance_logs
                GROUP BY member_id
            ) al ON al.member_id = m.id";

        if ($search !== null && $search !== '') {
            $escapedSearch = $this->escapeLike($search);
            $stmt = $pdo->prepare(
                $baseSelect
                . " WHERE m.full_name LIKE :search ESCAPE '!'"
                . " OR m.member_code LIKE :search ESCAPE '!'"
                . " ORDER BY m.created_at DESC"
                . " LIMIT :limit OFFSET :offset"
            );
            $stmt->bindValue(':search', '%' . $escapedSearch . '%');
            $stmt->bindValue(':limit',  $limit,  \PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        }

        $stmt = $pdo->prepare($baseSelect . ' ORDER BY m.created_at DESC LIMIT :limit OFFSET :offset');
        $stmt->bindValue(':limit',  $limit,  \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /** Total member count (used for pagination UI). */
    public function countAll(?string $search = null): int
    {
        $pdo = Database::connection();

        if ($search !== null && $search !== '') {
            $escapedSearch = $this->escapeLike($search);
            $stmt = $pdo->prepare(
                "SELECT COUNT(*) FROM members m
                  WHERE m.full_name LIKE :search ESCAPE '!'"
                . " OR m.member_code LIKE :search ESCAPE '!'"
            );
            $stmt->execute([':search' => '%' . $escapedSearch . '%']);
            return (int) $stmt->fetchColumn();
        }

        return (int) $pdo->query('SELECT COUNT(*) FROM members')->fetchColumn();
    }

    public function findById(int $id): ?array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM members WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $member = $stmt->fetch();

        return $member ?: null;
    }

    public function findByQrToken(string $token): ?array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM members WHERE qr_token = :qr_token LIMIT 1');
        $stmt->execute([':qr_token' => $token]);
        $member = $stmt->fetch();

        return $member ?: null;
    }

    public function countAttendanceLogs(int $memberId): int
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM attendance_logs WHERE member_id = :member_id');
        $stmt->execute([':member_id' => $memberId]);
        $row = $stmt->fetch();

        return (int) ($row['total'] ?? 0);
    }

    public function create(array $data): int
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('INSERT INTO members (member_code, qr_token, full_name, email, gender, photo_path, qr_payload, membership_end_date, created_at, updated_at) VALUES (:member_code, :qr_token, :full_name, :email, :gender, :photo_path, :qr_payload, :membership_end_date, :created_at, :updated_at)');
        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');
        $stmt->execute([
            ':member_code' => $data['member_code'],
            ':qr_token' => $data['qr_token'],
            ':full_name' => $data['full_name'],
            ':email' => $data['email'],
            ':gender' => $data['gender'],
            ':photo_path' => $data['photo_path'],
            ':qr_payload' => $data['qr_payload'],
            ':membership_end_date' => $data['membership_end_date'],
            ':created_at' => $now,
            ':updated_at' => $now,
        ]);

        return (int) $pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('UPDATE members SET full_name = :full_name, email = :email, gender = :gender, photo_path = :photo_path, qr_payload = :qr_payload, membership_end_date = :membership_end_date, updated_at = :updated_at WHERE id = :id');
        $stmt->execute([
            ':full_name' => $data['full_name'],
            ':email' => $data['email'],
            ':gender' => $data['gender'],
            ':photo_path' => $data['photo_path'],
            ':qr_payload' => $data['qr_payload'],
            ':membership_end_date' => $data['membership_end_date'],
            ':updated_at' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
            ':id' => $id,
        ]);
    }

    public function updateQr(int $id, string $qrToken, string $qrPayload): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('UPDATE members SET qr_token = :qr_token, qr_payload = :qr_payload, updated_at = :updated_at WHERE id = :id');
        $stmt->execute([
            ':qr_token' => $qrToken,
            ':qr_payload' => $qrPayload,
            ':updated_at' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
            ':id' => $id,
        ]);
    }

    public function deleteById(int $id): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('DELETE FROM members WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    /**
     * Delete all attendance log rows for a member (used before force-delete).
     * Returns the number of rows deleted.
     */
    public function deleteAttendanceLogsByMemberId(int $memberId): int
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('DELETE FROM attendance_logs WHERE member_id = :member_id');
        $stmt->execute([':member_id' => $memberId]);
        return (int) $stmt->rowCount();
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $value);
    }
}
