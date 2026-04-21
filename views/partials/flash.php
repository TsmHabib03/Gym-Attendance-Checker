<?php

declare(strict_types=1);

$success = flash('success');
$error   = flash('error');
?>
<?php if ($success): ?>
  <div class="flash-success"><?= e($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
  <div class="flash-error"><?= e($error) ?></div>
<?php endif; ?>
