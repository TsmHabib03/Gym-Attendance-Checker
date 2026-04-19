<?php

declare(strict_types=1);

use App\Core\Config;
use App\Core\RateLimiter;

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'bootstrap.php';

$retentionDays = Config::int('RATE_LIMIT_RETENTION_DAYS', 7);
$deletedRows = RateLimiter::purgeStale($retentionDays);

echo 'Stale rate-limit rows deleted: ' . $deletedRows . PHP_EOL;
