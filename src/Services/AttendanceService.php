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
            $duplicate = $this->attendance->findRecentAccepted((int) $member['id'], $duplicateWindow);
            if ($duplicate) {
                $scanStatus = 'duplicate_denied';
                $note = 'Duplicate scan detected inside cool-down window.';
            }
        }

        if ($this->photoCaptureEnabled() && $photoData !== null && $photoData !== '') {
            $photoPath = $this->storeCheckinPhoto($photoData);
        }

        $logId = $this->attendance->logScan([
            'member_id' => (int) $member['id'],
            'status' => $scanStatus,
            'note' => $note,
            'ip_address' => $ipAddress,
            'checkin_photo_path' => $photoPath,
        ]);

        Logger::audit('checkin_scanned', null, [
            'attendance_log_id' => $logId,
            'member_id' => (int) $member['id'],
            'status' => $scanStatus,
        ]);

        if ($scanStatus === 'expired_denied') {
            $this->alerts->sendExpiredScanAlert($member);
        }

        return [
            'member' => [
                'id' => (int) $member['id'],
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
        if (!str_starts_with($photoData, 'data:image/')) {
            return null;
        }

        if (!preg_match('/^data:image\/(png|jpeg|jpg|webp);base64,/', $photoData, $matches)) {
            return null;
        }

        $base64 = substr($photoData, strpos($photoData, ',') + 1);
        $binary = base64_decode($base64, true);
        if ($binary === false || strlen($binary) > 2 * 1024 * 1024) {
            return null;
        }

        $ext = $matches[1] === 'jpeg' ? 'jpg' : $matches[1];
        $filename = 'checkin_' . bin2hex(random_bytes(10)) . '.' . $ext;
        $targetDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'checkin_photos';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0775, true);
        }

        $targetFile = $targetDir . DIRECTORY_SEPARATOR . $filename;
        file_put_contents($targetFile, $binary);

        return '/uploads/checkin_photos/' . $filename;
    }
}
