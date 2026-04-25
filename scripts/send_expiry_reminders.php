<?php

declare(strict_types=1);

// Block web access: these scripts must only run from the command line.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}

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
