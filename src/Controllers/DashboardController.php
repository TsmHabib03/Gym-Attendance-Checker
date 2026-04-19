<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Validator;
use App\Core\View;
use App\Repositories\SettingRepository;
use App\Services\DashboardService;

final class DashboardController
{
    private DashboardService $dashboard;
    private SettingRepository $settings;

    public function __construct(?DashboardService $dashboard = null, ?SettingRepository $settings = null)
    {
        $this->dashboard = $dashboard ?? new DashboardService();
        $this->settings = $settings ?? new SettingRepository();
    }

    public function index(): void
    {
        Auth::requireAdmin();

        $overview = $this->dashboard->overview();

        if (Request::isAjax() && Request::input('live') === '1') {
            header('Content-Type: application/json');
            echo json_encode([
                'ok' => true,
                'data' => [
                    'overview' => $overview,
                    'generated_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                ],
            ]);
            return;
        }

        $settings = $this->settings->all();

        View::render('dashboard/index', [
            'overview' => $overview,
            'settings' => $settings,
            'csrfToken' => Csrf::token(),
        ]);
    }

    public function saveSettings(): void
    {
        Auth::requireAdmin();
        Csrf::assertValid((string) Request::input('_csrf'));

        $photoCapture = Request::input('photo_capture_enabled') === '1' ? 'true' : 'false';
        $expiryDays = (string) max(1, Validator::int(Request::input('expiry_reminder_days', 7), 'Expiry reminder days'));

        $this->settings->set('photo_capture_enabled', $photoCapture);
        $this->settings->set('expiry_reminder_days', $expiryDays);

        flash('success', 'Settings updated.');
        redirect('/dashboard');
    }
}
