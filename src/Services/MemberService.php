<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Logger;
use App\Core\Validator;
use App\Repositories\MemberRepository;
use DateTimeImmutable;
use InvalidArgumentException;

final class MemberService
{
    private MemberRepository $members;

    public function __construct(?MemberRepository $members = null)
    {
        $this->members = $members ?? new MemberRepository();
    }

    public function list(?string $search = null): array
    {
        $search = $search !== null ? Validator::string($search, 100) : null;
        return $this->members->findAll($search);
    }

    public function get(int $id): ?array
    {
        return $this->members->findById($id);
    }

    public function create(array $payload, array $files): int
    {
        $fullName = Validator::requiredString($payload['full_name'] ?? null, 'Full name', 120);
        $email = Validator::string($payload['email'] ?? '', 160);
        $gender = $this->normalizeGender($payload['gender'] ?? null);
        $endDate = Validator::date($payload['membership_end_date'] ?? null, 'Membership end date');

        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Email address is invalid.');
        }

        $photoPath = $this->storeMemberPhoto($files['photo'] ?? null);

        $memberCode = 'MBR-' . strtoupper(bin2hex(random_bytes(3)));
        $qrToken = bin2hex(random_bytes(24));
        $qrPayload = $this->buildQrPayload([
            'qr_token' => $qrToken,
            'member_code' => $memberCode,
            'full_name' => $fullName,
            'email' => $email === '' ? null : $email,
            'gender' => $gender,
            'photo_path' => $photoPath,
            'membership_end_date' => $endDate,
        ]);

        $memberId = $this->members->create([
            'member_code' => $memberCode,
            'qr_token' => $qrToken,
            'full_name' => $fullName,
            'email' => $email === '' ? null : $email,
            'gender' => $gender,
            'photo_path' => $photoPath,
            'qr_payload' => $qrPayload,
            'membership_end_date' => $endDate,
        ]);

        Logger::audit('member_created', null, ['member_id' => $memberId, 'member_code' => $memberCode]);

        return $memberId;
    }

    public function update(int $id, array $payload, array $files): void
    {
        $existing = $this->members->findById($id);
        if (!$existing) {
            throw new InvalidArgumentException('Member not found.');
        }

        $fullName = Validator::requiredString($payload['full_name'] ?? null, 'Full name', 120);
        $email = Validator::string($payload['email'] ?? '', 160);
        $gender = $this->normalizeGender($payload['gender'] ?? null);
        $endDate = Validator::date($payload['membership_end_date'] ?? null, 'Membership end date');

        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Email address is invalid.');
        }

        $photoPath = $existing['photo_path'];
        $uploaded = $this->storeMemberPhoto($files['photo'] ?? null);
        if ($uploaded !== null) {
            $photoPath = $uploaded;
        }

        $qrPayload = $this->buildQrPayload([
            'qr_token' => (string) $existing['qr_token'],
            'member_code' => (string) $existing['member_code'],
            'full_name' => $fullName,
            'email' => $email === '' ? null : $email,
            'gender' => $gender,
            'photo_path' => $photoPath,
            'membership_end_date' => $endDate,
        ]);

        $this->members->update($id, [
            'full_name' => $fullName,
            'email' => $email === '' ? null : $email,
            'gender' => $gender,
            'photo_path' => $photoPath,
            'qr_payload' => $qrPayload,
            'membership_end_date' => $endDate,
        ]);

        Logger::audit('member_updated', null, ['member_id' => $id]);
    }

    public function delete(int $id): void
    {
        $existing = $this->members->findById($id);
        if (!$existing) {
            throw new InvalidArgumentException('Member not found.');
        }

        $attendanceCount = $this->members->countAttendanceLogs($id);
        if ($attendanceCount > 0) {
            throw new InvalidArgumentException('Cannot delete member with attendance history.');
        }

        $this->members->deleteById($id);
        $this->deleteMemberPhoto((string) ($existing['photo_path'] ?? ''));

        Logger::audit('member_deleted', null, [
            'member_id' => $id,
            'member_code' => (string) ($existing['member_code'] ?? ''),
        ]);
    }

    public function regenerateQr(int $id): array
    {
        $existing = $this->members->findById($id);
        if (!$existing) {
            throw new InvalidArgumentException('Member not found.');
        }

        $qrToken = $this->generateUniqueQrToken();
        $qrPayload = $this->buildQrPayload([
            'qr_token' => $qrToken,
            'member_code' => (string) ($existing['member_code'] ?? ''),
            'full_name' => (string) ($existing['full_name'] ?? ''),
            'email' => $existing['email'] ?? null,
            'gender' => (string) ($existing['gender'] ?? ''),
            'photo_path' => $existing['photo_path'] ?? null,
            'membership_end_date' => (string) ($existing['membership_end_date'] ?? ''),
        ]);

        $this->members->updateQr($id, $qrToken, $qrPayload);

        $updated = $this->members->findById($id);
        if (!$updated) {
            throw new InvalidArgumentException('Member not found after QR regeneration.');
        }

        return $updated;
    }

    public function membershipStatus(array $member): string
    {
        $today = new DateTimeImmutable('today');
        $end = new DateTimeImmutable((string) $member['membership_end_date']);

        return $end >= $today ? 'Active' : 'Expired';
    }

    private function storeMemberPhoto(?array $file): ?string
    {
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException('Failed to upload member photo.');
        }

        if (($file['size'] ?? 0) > 3 * 1024 * 1024) {
            throw new InvalidArgumentException('Member photo must be 3MB or smaller.');
        }

        $tmpName = (string) ($file['tmp_name'] ?? '');
        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            throw new InvalidArgumentException('Invalid member photo upload payload.');
        }

        $mimeType = strtolower($this->detectMimeType($tmpName));
        $allowed = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($mimeType, $allowed, true)) {
            throw new InvalidArgumentException('Member photo must be JPG, PNG, or WEBP.');
        }

        $extension = match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'jpg',
        };

        $filename = 'member_' . bin2hex(random_bytes(10)) . '.' . $extension;
        $targetDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'member_photos';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0775, true);
        }

        $targetPath = $targetDir . DIRECTORY_SEPARATOR . $filename;
        if (!move_uploaded_file($tmpName, $targetPath)) {
            throw new InvalidArgumentException('Unable to save uploaded photo.');
        }

        return '/uploads/member_photos/' . $filename;
    }

    private function detectMimeType(string $filePath): string
    {
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo !== false) {
                $detected = finfo_file($finfo, $filePath);
                finfo_close($finfo);

                if (is_string($detected)) {
                    return $detected;
                }
            }
        }

        $imageInfo = @getimagesize($filePath);
        if (is_array($imageInfo) && isset($imageInfo['mime']) && is_string($imageInfo['mime'])) {
            return $imageInfo['mime'];
        }

        return '';
    }

    private function normalizeGender(mixed $value): string
    {
        $gender = strtolower(Validator::requiredString($value, 'Gender', 30));
        $allowed = ['male', 'female', 'other', 'prefer_not_say'];

        if (!in_array($gender, $allowed, true)) {
            throw new InvalidArgumentException('Gender must be Male, Female, Other, or Prefer not to say.');
        }

        return $gender;
    }

    private function buildQrPayload(array $member): string
    {
        $payload = [
            'v' => 1,
            'type' => 'gym_member',
            'qr_token' => (string) ($member['qr_token'] ?? ''),
            'member_code' => (string) ($member['member_code'] ?? ''),
            'full_name' => (string) ($member['full_name'] ?? ''),
            'email' => $member['email'] ?? null,
            'gender' => (string) ($member['gender'] ?? ''),
            'photo_path' => $member['photo_path'] ?? null,
            'membership_end_date' => (string) ($member['membership_end_date'] ?? ''),
            'generated_at' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
        ];

        $json = json_encode($payload, JSON_UNESCAPED_SLASHES);
        if (!is_string($json)) {
            throw new InvalidArgumentException('Unable to generate QR payload.');
        }

        return $json;
    }

    private function generateUniqueQrToken(): string
    {
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $token = bin2hex(random_bytes(24));
            if ($this->members->findByQrToken($token) === null) {
                return $token;
            }
        }

        throw new InvalidArgumentException('Unable to generate unique QR token. Please try again.');
    }

    private function deleteMemberPhoto(string $photoPath): void
    {
        $photoPath = trim($photoPath);
        if ($photoPath === '') {
            return;
        }

        if (!str_starts_with($photoPath, '/uploads/member_photos/')) {
            return;
        }

        $relativePath = ltrim($photoPath, '/');
        $absolutePath = dirname(__DIR__, 2)
            . DIRECTORY_SEPARATOR
            . 'public'
            . DIRECTORY_SEPARATOR
            . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);

        if (is_file($absolutePath)) {
            @unlink($absolutePath);
        }
    }
}
