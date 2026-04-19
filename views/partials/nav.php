<?php

declare(strict_types=1);

$auth = \App\Core\Auth::user();
$isDashboard = !empty($dashboardShell) || (isset($title) && $title === 'Dashboard');

$shellClass = $isDashboard
    ? 'border border-slate-800 bg-[#090d14]/90 shadow-xl shadow-black/40'
    : 'card shadow-glow';
$titleClass = $isDashboard ? 'text-slate-100' : 'text-brand-800';
$subtitleClass = $isDashboard ? 'text-slate-400' : 'text-slate-600';
$linkClass = $isDashboard
    ? 'rounded-lg bg-slate-900/70 px-3 py-2 font-semibold text-slate-200 ring-1 ring-slate-700 transition hover:-translate-y-0.5 hover:bg-slate-800 hover:text-white'
    : 'rounded-lg bg-white px-3 py-2 font-semibold text-slate-700 ring-1 ring-slate-200 transition hover:-translate-y-0.5 hover:text-brand-700';
$scanClass = $isDashboard
    ? 'rounded-lg bg-white px-3 py-2 font-semibold text-slate-900 transition hover:-translate-y-0.5 hover:bg-slate-100'
    : 'rounded-lg bg-brand-500 px-3 py-2 font-semibold text-white transition hover:-translate-y-0.5 hover:bg-brand-600';
$logoutClass = $isDashboard
    ? 'rounded-lg bg-rose-500/90 px-3 py-2 font-semibold text-white transition hover:-translate-y-0.5 hover:bg-rose-500'
    : 'rounded-lg bg-rose-500 px-3 py-2 font-semibold text-white transition hover:-translate-y-0.5 hover:bg-rose-600';
$mobilePanelClass = $isDashboard
    ? 'border-l border-slate-800 bg-[#0a0f17]/95 text-slate-100'
    : 'border-l border-slate-200 bg-white/95 text-slate-900';
?>
<header class="relative z-10">
  <div class="mx-auto w-full max-w-7xl px-3 py-3 sm:px-4 sm:py-4 md:px-6 lg:px-8">
    <div class="flex flex-col gap-3 rounded-2xl p-3 sm:flex-row sm:items-center sm:justify-between sm:gap-4 sm:p-4 <?= e($shellClass) ?>">
      <div>
        <p class="font-display text-xl font-bold xs:text-2xl <?= e($titleClass) ?>">Gym Attendance Checker</p>
        <p class="text-xs xs:text-sm <?= e($subtitleClass) ?>">QR-based check-in and membership monitoring</p>
      </div>
      <?php if ($auth): ?>
        <button
          type="button"
          id="mobileNavToggle"
          class="inline-flex h-11 items-center justify-center rounded-xl border border-slate-600 bg-slate-900/70 px-4 text-sm font-semibold text-slate-100 transition hover:border-slate-400 hover:bg-slate-800 sm:hidden"
          aria-expanded="false"
          aria-controls="mobileNavPanel"
        >
          Menu
        </button>

        <div class="hidden flex-wrap items-center gap-2 text-sm sm:flex">
          <a href="<?= e(url('/dashboard')) ?>" class="<?= e($linkClass) ?>">Dashboard</a>
          <a href="<?= e(url('/members')) ?>" class="<?= e($linkClass) ?>">Members</a>
          <a href="<?= e(url('/attendance/scan')) ?>" class="<?= e($scanClass) ?>">Scan QR</a>
          <form action="<?= e(url('/logout')) ?>" method="post" class="inline">
            <input type="hidden" name="_csrf" value="<?= e(\App\Core\Csrf::token()) ?>">
            <button type="submit" class="<?= e($logoutClass) ?>">Sign out</button>
          </form>
        </div>

        <div id="mobileNavOverlay" class="fixed inset-0 z-40 hidden bg-black/60 sm:hidden" aria-hidden="true"></div>
        <aside id="mobileNavPanel" class="fixed inset-y-0 right-0 z-50 flex w-[85vw] max-w-xs translate-x-full flex-col px-4 py-5 shadow-2xl transition-transform duration-200 ease-out sm:hidden <?= e($mobilePanelClass) ?>" aria-hidden="true">
          <div class="mb-4 flex items-center justify-between">
            <p class="font-display text-lg font-bold">Navigation</p>
            <button type="button" id="mobileNavClose" class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-slate-600 bg-slate-900/50 text-sm font-semibold text-slate-100">X</button>
          </div>
          <div class="space-y-2">
            <a href="<?= e(url('/dashboard')) ?>" class="flex h-11 items-center rounded-xl border border-slate-700 bg-slate-900/50 px-4 text-sm font-semibold text-slate-100">Dashboard</a>
            <a href="<?= e(url('/members')) ?>" class="flex h-11 items-center rounded-xl border border-slate-700 bg-slate-900/50 px-4 text-sm font-semibold text-slate-100">Members</a>
            <a href="<?= e(url('/attendance/scan')) ?>" class="flex h-11 items-center rounded-xl bg-white px-4 text-sm font-semibold text-slate-900">Scan QR</a>
            <form action="<?= e(url('/logout')) ?>" method="post" class="pt-2">
              <input type="hidden" name="_csrf" value="<?= e(\App\Core\Csrf::token()) ?>">
              <button type="submit" class="flex h-11 w-full items-center justify-center rounded-xl bg-rose-500/90 px-4 text-sm font-semibold text-white">Sign out</button>
            </form>
          </div>
        </aside>
      <?php endif; ?>
    </div>
  </div>
</header>
<?php if ($auth): ?>
<script>
(() => {
  const toggle = document.getElementById('mobileNavToggle');
  const closeBtn = document.getElementById('mobileNavClose');
  const panel = document.getElementById('mobileNavPanel');
  const overlay = document.getElementById('mobileNavOverlay');

  if (!toggle || !closeBtn || !panel || !overlay) {
    return;
  }

  const openPanel = () => {
    panel.classList.remove('translate-x-full');
    overlay.classList.remove('hidden');
    panel.setAttribute('aria-hidden', 'false');
    toggle.setAttribute('aria-expanded', 'true');
    document.body.classList.add('overflow-hidden');
  };

  const closePanel = () => {
    panel.classList.add('translate-x-full');
    overlay.classList.add('hidden');
    panel.setAttribute('aria-hidden', 'true');
    toggle.setAttribute('aria-expanded', 'false');
    document.body.classList.remove('overflow-hidden');
  };

  toggle.addEventListener('click', openPanel);
  closeBtn.addEventListener('click', closePanel);
  overlay.addEventListener('click', closePanel);

  panel.querySelectorAll('a, button, form').forEach((el) => {
    el.addEventListener('click', () => {
      if (el !== toggle) {
        closePanel();
      }
    });
  });

  window.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      closePanel();
    }
  });

  window.addEventListener('resize', () => {
    if (window.matchMedia('(min-width: 640px)').matches) {
      closePanel();
    }
  });
})();
</script>
<?php endif; ?>
