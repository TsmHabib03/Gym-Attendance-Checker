<?php

declare(strict_types=1);

use App\Core\Config;
use App\Services\AlertService;

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'bootstrap.php';

$days = Config::int('EXPIRY_REMINDER_DAYS', 7);
$override = (new App\Repositories\SettingRepository())->get('expiry_reminder_days');
if ($override !== null && is_numeric($override)) {
    $days = max(1, (int) $override);
}

$service = new AlertService();
$count = $service->sendExpiryReminders($days);

echo 'Expiry reminders sent: ' . $count . PHP_EOL;
