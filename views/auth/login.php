<?php

declare(strict_types=1);

$title = 'Admin Login';
$dashboardShell = true;
require __DIR__ . '/../partials/head.php';
?>
<main class="mx-auto flex min-h-screen w-full max-w-7xl items-center px-3 py-6 sm:px-4 sm:py-8 md:px-6 lg:px-8">
  <section class="fade-up mx-auto w-full rounded-[30px] border border-slate-800 bg-[#070b12]/90 p-3 shadow-2xl shadow-black/50 sm:p-4 lg:p-6">
    <div class="grid gap-4 lg:grid-cols-[300px_minmax(0,1fr)] lg:gap-5">
      <aside class="order-2 rounded-2xl border border-slate-800 bg-[#0f131b] p-4 sm:p-5 lg:order-1">
        <div class="rounded-2xl border border-slate-700 bg-slate-900/60 p-4">
          <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Secure Access</p>
          <h1 class="mt-2 font-display text-3xl font-bold leading-tight text-white sm:text-4xl">Welcome back, Admin</h1>
          <p class="mt-3 text-sm text-slate-400">Sign in to manage check-ins, membership status, and operational settings from the control room.</p>

          <div class="mt-5 space-y-2 text-xs text-slate-400">
            <p class="rounded-lg border border-slate-700 bg-slate-900/60 px-3 py-2">QR attendance tracking and status validation</p>
            <p class="rounded-lg border border-slate-700 bg-slate-900/60 px-3 py-2">Member records, expiry checks, and photo updates</p>
            <p class="rounded-lg border border-slate-700 bg-slate-900/60 px-3 py-2">Audit logging and admin-only protected routes</p>
          </div>
        </div>
      </aside>

      <article class="order-1 rounded-2xl border border-slate-800 bg-[#0f141d] p-4 sm:p-5 lg:order-2 lg:p-6">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
          <div>
            <h2 class="font-display text-3xl font-bold text-white">Admin Login</h2>
            <p class="text-sm text-slate-400">Use your credentials to continue.</p>
          </div>
          <p class="text-xs uppercase tracking-wide text-slate-500">Session is protected</p>
        </div>

        <div class="mt-4">
          <?php require __DIR__ . '/../partials/flash.php'; ?>
        </div>

        <form action="<?= e(url('/login')) ?>" method="post" class="mt-5 space-y-4" autocomplete="off">
          <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">

          <label class="block">
            <span class="mb-1 block text-sm font-semibold text-slate-300">Username</span>
            <input
              type="text"
              name="username"
              value="<?= e(old('username')) ?>"
              class="h-11 w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2.5 text-slate-100 outline-none ring-cyan-300 transition focus:ring sm:px-4"
              required
            >
          </label>

          <label class="block">
            <span class="mb-1 block text-sm font-semibold text-slate-300">Password</span>
            <input
              type="password"
              name="password"
              class="h-11 w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2.5 text-slate-100 outline-none ring-cyan-300 transition focus:ring sm:px-4"
              required
            >
          </label>

          <button
            type="submit"
            class="h-11 w-full rounded-xl bg-white px-4 py-2.5 font-semibold text-slate-900 transition hover:-translate-y-0.5 hover:bg-slate-100"
          >
            Sign in
          </button>
        </form>
      </article>
    </div>
  </section>
</main>
<?php require __DIR__ . '/../partials/foot.php'; ?>
