<?php

declare(strict_types=1);

$title = 'Admin Login';
$dashboardShell = true;
require __DIR__ . '/../partials/head.php';
// NOTE: No nav on login page — intentional
?>

<!-- ============================================================
     LOGIN PAGE — Full-screen centered layout
     ============================================================ -->
<main style="
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px 16px;
">
  <div class="page-enter" style="width: 100%; max-width: 400px;">

    <div style="text-align: center; margin-bottom: 40px;">
      <img
        src="<?= e(asset('img/repcore-removebg-preview.png')) ?>"
        alt="Gym Rep Core"
        style="height: 72px; width: auto; display: block; margin: 0 auto 20px;"
      >
      <h1 style="
        font-family: 'Bebas Neue', sans-serif;
        font-size: 32px;
        letter-spacing: 0.15em;
        color: var(--white);
        line-height: 1;
        margin: 0 0 6px;
      "><?= e((string) \App\Core\Config::get('APP_NAME', 'Gym Attendance')) ?></h1>
      <p style="font-size: 12px; color: var(--muted); letter-spacing: 0.08em; text-transform: uppercase; margin: 0;">
        Admin Control Room
      </p>
    </div>

    <!-- Flash messages -->
    <?php
    $error = flash('error');
    if ($error): ?>
      <div class="flash-error" style="margin-bottom: 24px;"><?= e($error) ?></div>
    <?php endif; ?>

    <!-- Login card -->
    <div style="
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 2px;
      padding: 32px 28px;
    ">
      <!-- Header -->
      <div style="margin-bottom: 28px;">
        <h2 style="
          font-family: 'Bebas Neue', sans-serif;
          font-size: 22px;
          letter-spacing: 0.12em;
          color: var(--white);
          margin: 0 0 4px;
        ">Sign In</h2>
        <p style="font-size: 13px; color: var(--muted); margin: 0;">
          Enter your credentials to continue.
        </p>
      </div>

      <!-- Form -->
      <form action="<?= e(url('/login')) ?>" method="post" autocomplete="off" style="display: flex; flex-direction: column; gap: 18px;">
        <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">

        <div>
          <label for="login-username" class="label">Username</label>
          <input
            type="text"
            id="login-username"
            name="username"
            value="<?= e(old('username')) ?>"
            class="input"
            autocomplete="username"
            required
            placeholder="admin"
          >
        </div>

        <div>
          <label for="login-password" class="label">Password</label>
          <input
            type="password"
            id="login-password"
            name="password"
            class="input"
            autocomplete="current-password"
            required
            placeholder="••••••••"
          >
        </div>

        <button type="submit" class="btn-primary" style="width: 100%; margin-top: 8px;">
          Sign In
        </button>
      </form>

      <!-- Subtle divider + security note -->
      <div style="
        margin-top: 24px;
        padding-top: 20px;
        border-top: 1px solid var(--border);
        display: flex;
        align-items: center;
        gap: 8px;
      ">
        <svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg" style="flex-shrink: 0; opacity: 0.4;">
          <path d="M6 1L9.5 2.5V6C9.5 8 6 11 6 11C6 11 2.5 8 2.5 6V2.5L6 1Z" stroke="#888" stroke-width="1" fill="none"/>
        </svg>
        <span style="font-size: 11px; color: var(--muted);">
          Session protected with CSRF and rate limiting
        </span>
      </div>
    </div>

    <!-- Footer -->
    <p style="text-align: center; font-size: 11px; color: var(--muted); margin-top: 24px; letter-spacing: 0.04em;">
      <?= e((string) \App\Core\Config::get('APP_NAME', 'Gym Attendance Checker')) ?>
    </p>

  </div>
</main>

<?php require __DIR__ . '/../partials/foot.php'; ?>
