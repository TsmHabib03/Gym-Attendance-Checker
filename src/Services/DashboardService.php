<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Repositories\AttendanceRepository;

final class DashboardService
{
    private AttendanceRepository $attendance;

    public function __construct(?AttendanceRepository $attendance = null)
    {
        $this->attendance = $attendance ?? new AttendanceRepository();
    }

    public function overview(): array
    {
        $pdo = Database::connection();

        $memberStats = $pdo->query('SELECT
            COUNT(*) AS total_members,
            SUM(CASE WHEN membership_end_date >= CURDATE() THEN 1 ELSE 0 END) AS active_members,
            SUM(CASE WHEN membership_end_date < CURDATE() THEN 1 ELSE 0 END) AS expired_members
            FROM members')->fetch();

        return [
            'members' => [
                'total' => (int) ($memberStats['total_members'] ?? 0),
                'active' => (int) ($memberStats['active_members'] ?? 0),
                'expired' => (int) ($memberStats['expired_members'] ?? 0),
            ],
            'attendance_today' => $this->attendance->statsForToday(),
            'recent_logs' => $this->attendance->recentLogs(30),
        ];
    }
}
