<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
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

    public function getAttendanceCount(int $id): int
    {
        return $this->members->countAttendanceLogs($id);
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

        $memberCode = $this->generateNextMemberCode();
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
            throw new InvalidArgumentException(
                'Cannot delete member with attendance history (' . $attendanceCount . ' record(s)). '
                . 'Use Force Delete to permanently remove the member and all their attendance records.'
            );
        }

        $this->members->deleteById($id);
        $this->deleteMemberPhoto((string) ($existing['photo_path'] ?? ''));

        Logger::audit('member_deleted', null, [
            'member_id' => $id,
            'member_code' => (string) ($existing['member_code'] ?? ''),
        ]);
    }

    /**
     * Force-delete a member and ALL their attendance history.
     * This is irreversible — caller must show a strong confirmation prompt.
     */
    public function forceDelete(int $id): array
    {
        $existing = $this->members->findById($id);
        if (!$existing) {
            throw new InvalidArgumentException('Member not found.');
        }

        $logsDeleted = $this->members->deleteAttendanceLogsByMemberId($id);
        $this->members->deleteById($id);
        $this->deleteMemberPhoto((string) ($existing['photo_path'] ?? ''));

        Logger::audit('member_force_deleted', null, [
            'member_id' => $id,
            'member_code' => (string) ($existing['member_code'] ?? ''),
            'attendance_logs_deleted' => $logsDeleted,
        ]);

        return [
            'member_code' => (string) ($existing['member_code'] ?? ''),
            'full_name'   => (string) ($existing['full_name'] ?? ''),
            'logs_deleted' => $logsDeleted,
        ];
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

        // SECURITY: Validate the actual content is an image (not just trusting
        // the client-declared MIME type). getimagesize parses image headers and
        // returns false for anything that isn't a real image.
        $imageInfo = @getimagesize($tmpName);
        if ($imageInfo === false || !isset($imageInfo[2])) {
            throw new InvalidArgumentException('Uploaded file is not a valid image.');
        }

        $allowedTypes = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP];
        if (!in_array($imageInfo[2], $allowedTypes, true)) {
            throw new InvalidArgumentException('Member photo must be JPG, PNG, or WEBP.');
        }

        // Reject absurd dimensions (decompression bomb mitigation).
        $width = (int) ($imageInfo[0] ?? 0);
        $height = (int) ($imageInfo[1] ?? 0);
        if ($width <= 0 || $height <= 0 || $width > 8000 || $height > 8000 || ($width * $height) > 40_000_000) {
            throw new InvalidArgumentException('Image dimensions are out of range.');
        }

        $mimeType = strtolower($this->detectMimeType($tmpName));
        $allowedMime = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($mimeType, $allowedMime, true)) {
            throw new InvalidArgumentException('Member photo must be JPG, PNG, or WEBP.');
        }

        $extension = match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'jpg',
        };

        // Filename has only random hex bytes — the user never supplies the
        // stored name, so path traversal / null-byte / shell-meta injection
        // via filename is impossible.
        $filename = 'member_' . bin2hex(random_bytes(10)) . '.' . $extension;
        $targetDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'member_photos';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0775, true);
        }

        $targetPath = $targetDir . DIRECTORY_SEPARATOR . $filename;
        if (!move_uploaded_file($tmpName, $targetPath)) {
            throw new InvalidArgumentException('Unable to save uploaded photo.');
        }

        // SECURITY: Re-encode the image through GD when available. This strips
        // any embedded scripts, EXIF blobs, or polyglot payloads — what gets
        // saved is a clean, server-generated copy.
        $this->reencodeImage($targetPath, $imageInfo[2]);

        // Force restrictive permissions on the saved file.
        @chmod($targetPath, 0644);

        return '/uploads/member_photos/' . $filename;
    }

    /**
     * Re-encode an uploaded image to strip metadata and any non-image bytes.
     * Best effort — silently no-ops if GD is unavailable.
     */
    private function reencodeImage(string $path, int $imageType): void
    {
        if (!extension_loaded('gd')) {
            return;
        }

        try {
            $img = match ($imageType) {
                IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
                IMAGETYPE_PNG => @imagecreatefrompng($path),
                IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
                default => false,
            };
            if (!$img) {
                return;
            }

            // Preserve transparency for PNG.
            if ($imageType === IMAGETYPE_PNG) {
                imagealphablending($img, false);
                imagesavealpha($img, true);
            }

            $tempPath = $path . '.tmp';
            $written = match ($imageType) {
                IMAGETYPE_JPEG => @imagejpeg($img, $tempPath, 88),
                IMAGETYPE_PNG => @imagepng($img, $tempPath, 6),
                IMAGETYPE_WEBP => function_exists('imagewebp') ? @imagewebp($img, $tempPath, 88) : false,
                default => false,
            };
            imagedestroy($img);

            if ($written && is_file($tempPath)) {
                @rename($tempPath, $path);
            } else {
                @unlink($tempPath);
            }
        } catch (\Throwable $t) {
            Logger::error('Image re-encode failed', ['path' => $path, 'error' => $t->getMessage()]);
        }
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

    /**
     * Generate the next sequential member code (REP-000001, REP-000002, etc.)
     * Uses database-level atomic increment for thread-safe sequence generation.
     */
    private function generateNextMemberCode(): string
    {
        $pdo = Database::connection();

        // Atomically increment and get the next sequence number
        // This is thread-safe because of the ON DUPLICATE KEY UPDATE clause
        $pdo->query('
            INSERT INTO member_sequence (id, next_member_number)
            VALUES (1, 1)
            ON DUPLICATE KEY UPDATE next_member_number = next_member_number + 1
        ');

        $stmt = $pdo->query('SELECT next_member_number FROM member_sequence WHERE id = 1');
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$row || !isset($row['next_member_number'])) {
            throw new InvalidArgumentException('Unable to generate member code. Sequence initialization failed.');
        }

        $nextNumber = (int) $row['next_member_number'];
        return 'REP-' . str_pad((string) $nextNumber, 6, '0', STR_PAD_LEFT);
    }
}
