<?php

declare(strict_types=1);

// Block web access: these scripts must only run from the command line.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}

use App\Core\Config;
use App\Core\RateLimiter;

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'bootstrap.php';

$retentionDays = Config::int('RATE_LIMIT_RETENTION_DAYS', 7);
$deletedRows = RateLimiter::purgeStale($retentionDays);

echo 'Stale rate-limit rows deleted: ' . $deletedRows . PHP_EOL;
