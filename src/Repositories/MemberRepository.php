<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use DateTimeImmutable;

final class MemberRepository
{
    public function findAll(?string $search = null): array
    {
        $pdo = Database::connection();

        if ($search !== null && $search !== '') {
            $escapedSearch = $this->escapeLike($search);
            $stmt = $pdo->prepare("SELECT * FROM members WHERE full_name LIKE :search ESCAPE '!' OR member_code LIKE :search ESCAPE '!' ORDER BY created_at DESC");
            $stmt->execute([':search' => '%' . $escapedSearch . '%']);
            return $stmt->fetchAll();
        }

        $stmt = $pdo->query('SELECT * FROM members ORDER BY created_at DESC');
        return $stmt->fetchAll();
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

    private function escapeLike(string $value): string
    {
        return str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $value);
    }
}
