<?php

declare(strict_types=1);

$success = flash('success');
$error = flash('error');
$dashboard = !empty($dashboardShell) || (isset($isDashboard) && $isDashboard === true);

$successClass = $dashboard
    ? 'mb-4 rounded-xl border border-emerald-400/40 bg-emerald-400/10 px-3 py-3 text-sm text-emerald-200 sm:px-4'
    : 'mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-3 text-sm text-emerald-800 sm:px-4';

$errorClass = $dashboard
    ? 'mb-4 rounded-xl border border-rose-400/40 bg-rose-400/10 px-3 py-3 text-sm text-rose-200 sm:px-4'
    : 'mb-4 rounded-xl border border-rose-200 bg-rose-50 px-3 py-3 text-sm text-rose-800 sm:px-4';
?>
<?php if ($success): ?>
  <div class="<?= e($successClass) ?>">
    <?= e($success) ?>
  </div>
<?php endif; ?>
<?php if ($error): ?>
  <div class="<?= e($errorClass) ?>">
    <?= e($error) ?>
  </div>
<?php endif; ?>
