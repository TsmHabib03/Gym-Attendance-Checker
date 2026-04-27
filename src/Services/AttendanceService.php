<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Core\Logger;
use App\Repositories\AttendanceRepository;
use App\Repositories\MemberRepository;
use App\Repositories\SettingRepository;
use DateTimeImmutable;
use InvalidArgumentException;

final class AttendanceService
{
    private MemberRepository $members;
    private AttendanceRepository $attendance;
    private SettingRepository $settings;
    private AlertService $alerts;

    public function __construct(
        ?MemberRepository $members = null,
        ?AttendanceRepository $attendance = null,
        ?SettingRepository $settings = null,
        ?AlertService $alerts = null,
    ) {
        $this->members = $members ?? new MemberRepository();
        $this->attendance = $attendance ?? new AttendanceRepository();
        $this->settings = $settings ?? new SettingRepository();
        $this->alerts = $alerts ?? new AlertService();
    }

    public function checkIn(string $token, string $ipAddress, ?string $photoData = null): array
    {
        $token = strtolower(trim($token));
        if (!preg_match('/^(?:[a-f0-9]{48}|[a-f0-9]{64})$/', $token)) {
            throw new InvalidArgumentException('Invalid QR token.');
        }

        $member = $this->members->findByQrToken($token);
        if (!$member) {
            throw new InvalidArgumentException('Member not found for this QR token.');
        }

        $memberId = (int) $member['id'];

        // ── Race-condition guard ────────────────────────────────────────────
        // Without a lock, two near-simultaneous scans of the same QR can both
        // pass findRecentAccepted() before either writes to attendance_logs —
        // the classic TOCTOU bug that lets a single QR admit twice.
        //
        // GET_LOCK() acquires a named advisory lock in MySQL for this member.
        // Any second request for the same lock blocks here (up to 5 s) until
        // the first releases it, serialising the check + insert pair.
        $pdo = \App\Core\Database::connection();
        $lockName = 'gym_checkin_' . $memberId;
        $locked = (bool) $pdo->query(
            "SELECT GET_LOCK(" . $pdo->quote($lockName) . ", 5)"
        )->fetchColumn();

        if (!$locked) {
            throw new \RuntimeException('Scanner is busy for this member. Please scan again.');
        }

        try {
            return $this->performCheckIn($member, $memberId, $ipAddress, $photoData);
        } finally {
            $pdo->query("SELECT RELEASE_LOCK(" . $pdo->quote($lockName) . ")");
        }
    }

    private function performCheckIn(array $member, int $memberId, string $ipAddress, ?string $photoData): array
    {
        $status = $this->membershipStatus($member);
        $scanStatus = 'accepted';
        $note = 'Check-in accepted.';
        $photoPath = null;

        if ($status === 'Expired') {
            $scanStatus = 'expired_denied';
            $note = 'Membership expired. Check-in denied.';
        }

        $duplicateWindow = Config::int('DUPLICATE_SCAN_WINDOW_SECONDS', 45);
        if ($scanStatus === 'accepted') {
            $duplicate = $this->attendance->findRecentAccepted($memberId, $duplicateWindow);
            if ($duplicate) {
                $scanStatus = 'duplicate_denied';
                $note = 'Duplicate scan detected inside cool-down window.';
            }
        }

        if ($this->photoCaptureEnabled() && $photoData !== null && $photoData !== '') {
            $photoPath = $this->storeCheckinPhoto($photoData);
        }

        $logId = $this->attendance->logScan([
            'member_id' => $memberId,
            'status' => $scanStatus,
            'note' => $note,
            'ip_address' => $ipAddress,
            'checkin_photo_path' => $photoPath,
        ]);

        Logger::audit('checkin_scanned', null, [
            'attendance_log_id' => $logId,
            'member_id' => $memberId,
            'status' => $scanStatus,
        ]);

        if ($scanStatus === 'expired_denied') {
            try {
                $this->alerts->sendExpiredScanAlert($member);
            } catch (\Throwable $e) {
                Logger::error('sendExpiredScanAlert threw unexpectedly', [
                    'member_id' => $memberId,
                    'error'     => $e->getMessage(),
                ]);
            }
        }

        if ($scanStatus === 'accepted') {
            try {
                $this->alerts->sendCheckInAlert($member);
            } catch (\Throwable $e) {
                Logger::error('sendCheckInAlert threw unexpectedly', [
                    'member_id' => $memberId,
                    'error'     => $e->getMessage(),
                ]);
            }
        }

        return [
            'member' => [
                'id' => $memberId,
                'full_name' => $member['full_name'],
                'photo_path' => $member['photo_path'],
                'email' => $member['email'],
                'gender' => $member['gender'] ?? null,
                'membership_end_date' => $member['membership_end_date'],
                'member_code' => $member['member_code'],
            ],
            'membership_status' => $status,
            'scan_status' => $scanStatus,
            'message' => $note,
            'scanned_at' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
        ];
    }

    public function photoCaptureEnabled(): bool
    {
        $override = $this->settings->get('photo_capture_enabled');
        if ($override !== null) {
            return in_array(strtolower($override), ['1', 'true', 'yes', 'on'], true);
        }

        return Config::bool('PHOTO_CAPTURE_ENABLED', true);
    }

    private function membershipStatus(array $member): string
    {
        $today = new DateTimeImmutable('today');
        $membershipEnd = new DateTimeImmutable((string) $member['membership_end_date']);

        return $membershipEnd >= $today ? 'Active' : 'Expired';
    }

    private function storeCheckinPhoto(string $photoData): ?string
    {
        // Hard cap on the data-URI length we will even consider parsing.
        if (strlen($photoData) > 3 * 1024 * 1024) {
            return null;
        }

        if (!str_starts_with($photoData, 'data:image/')) {
            return null;
        }

        if (!preg_match('/^data:image\/(png|jpeg|jpg|webp);base64,/', $photoData, $matches)) {
            return null;
        }

        $commaPos = strpos($photoData, ',');
        if ($commaPos === false) {
            return null;
        }

        $base64 = substr($photoData, $commaPos + 1);
        // strict mode — base64_decode returns false on any non-base64 char.
        $binary = base64_decode($base64, true);
        if ($binary === false || strlen($binary) > 2 * 1024 * 1024 || strlen($binary) < 64) {
            return null;
        }

        // Verify the decoded bytes really are an image of the declared type.
        $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : false;
        if ($finfo !== false) {
            $detected = strtolower((string) finfo_buffer($finfo, $binary));
            finfo_close($finfo);
            $expectedMime = 'image/' . ($matches[1] === 'jpg' ? 'jpeg' : $matches[1]);
            if ($detected !== $expectedMime) {
                return null;
            }
        }

        $ext = $matches[1] === 'jpeg' ? 'jpg' : $matches[1];
        $filename = 'checkin_' . bin2hex(random_bytes(10)) . '.' . $ext;
        $targetDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'checkin_photos';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0775, true);
        }

        $targetFile = $targetDir . DIRECTORY_SEPARATOR . $filename;
        if (file_put_contents($targetFile, $binary) === false) {
            return null;
        }
        @chmod($targetFile, 0644);

        // Confirm the saved file is a real image — getimagesize returns false
        // for non-images. If it fails, drop the file.
        $info = @getimagesize($targetFile);
        if ($info === false) {
            @unlink($targetFile);
            return null;
        }

        return '/uploads/checkin_photos/' . $filename;
    }
}
