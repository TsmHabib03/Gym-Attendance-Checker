<?php

declare(strict_types=1);

$title = 'Members';
$dashboardShell = true;

$memberCount = count($members);
$activeCount = 0;
$expiredCount = 0;

foreach ($members as $member) {
    $isActive = (new DateTimeImmutable((string) $member['membership_end_date'])) >= new DateTimeImmutable('today');
    if ($isActive) {
        $activeCount++;
        continue;
    }

    $expiredCount++;
}

require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/nav.php';
?>
<main class="mx-auto w-full max-w-7xl px-3 pb-8 sm:px-4 md:px-6 lg:px-8 lg:pb-10">
  <section class="fade-up mt-4 rounded-[30px] border border-slate-800 bg-[#070b12]/90 p-3 shadow-2xl shadow-black/50 sm:mt-6 sm:p-4 lg:p-6">
    <?php require __DIR__ . '/../partials/flash.php'; ?>

    <div class="grid gap-4 xl:grid-cols-[300px_minmax(0,1fr)] xl:gap-5">
      <aside class="order-2 rounded-2xl border border-slate-800 bg-[#0f131b] p-4 sm:p-5 xl:order-1">
        <div class="rounded-2xl border border-slate-700 bg-slate-900/60 p-4">
          <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Members Hub</p>
          <h1 class="mt-2 font-display text-3xl font-bold leading-tight text-white sm:text-4xl">Member directory and status</h1>
          <p class="mt-3 text-sm text-slate-400">Manage member records, renewal health, and profile updates in one focused view.</p>

          <div class="mt-5 space-y-2">
            <a href="<?= e(url('/members/create')) ?>" class="flex h-11 items-center justify-center rounded-xl bg-white px-4 text-center text-sm font-semibold text-slate-900 transition hover:bg-slate-100">Add New Member</a>
            <a href="<?= e(url('/attendance/scan')) ?>" class="flex h-11 items-center justify-center rounded-xl border border-slate-600 px-4 text-center text-sm font-semibold text-slate-200 transition hover:border-slate-400 hover:bg-slate-800">Open Scanner</a>
          </div>
        </div>

        <div class="mt-5 rounded-2xl border border-slate-700 bg-slate-900/60 p-4">
          <div class="flex items-center justify-between">
            <p class="text-sm font-semibold text-slate-200">Current View</p>
            <p class="text-xs text-slate-400"><?= e((string) $memberCount) ?> members</p>
          </div>
          <div class="mt-3 space-y-2 text-sm">
            <div class="flex items-center justify-between rounded-lg bg-slate-800/70 px-3 py-2">
              <span class="text-slate-300">Active</span>
              <span class="font-semibold text-emerald-300"><?= e((string) $activeCount) ?></span>
            </div>
            <div class="flex items-center justify-between rounded-lg bg-slate-800/70 px-3 py-2">
              <span class="text-slate-300">Expired</span>
              <span class="font-semibold text-rose-300"><?= e((string) $expiredCount) ?></span>
            </div>
          </div>
        </div>
      </aside>

      <div class="order-1 space-y-4 xl:order-2 xl:space-y-5">
        <div class="rounded-2xl border border-slate-800 bg-[#0f141d] p-4 sm:p-5">
          <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
            <div>
              <h2 class="font-display text-2xl font-bold text-white">Member Management</h2>
              <p class="text-sm text-slate-400">Search by name or member code and update profile records quickly.</p>
            </div>
            <p class="text-xs uppercase tracking-wide text-slate-500">Live list view</p>
          </div>

          <form action="<?= e(url('/members')) ?>" method="get" class="mt-4">
            <div class="flex flex-col gap-2 sm:flex-row">
              <input
                type="text"
                name="search"
                value="<?= e($search) ?>"
                placeholder="Search by name or member code"
                class="h-11 w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2.5 text-slate-100 outline-none ring-cyan-300 transition focus:ring sm:px-4"
              >
              <button type="submit" class="h-11 rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-slate-900 transition hover:bg-slate-100">Search</button>
            </div>
          </form>
        </div>

        <section class="rounded-2xl border border-slate-800 bg-[#0f141d] p-4 sm:p-5">
          <div class="flex items-center justify-between">
            <h2 class="font-display text-2xl font-bold text-white">Members List</h2>
            <p class="text-xs text-slate-500">Status updates daily</p>
          </div>

          <div class="mt-4 space-y-3 md:hidden">
            <?php foreach ($members as $member): ?>
              <?php
              $status = (new DateTimeImmutable((string) $member['membership_end_date'])) >= new DateTimeImmutable('today') ? 'Active' : 'Expired';
              $badgeClass = $status === 'Active'
                  ? 'bg-emerald-400/20 text-emerald-300 ring-emerald-400/40'
                  : 'bg-rose-400/20 text-rose-300 ring-rose-400/40';
              $photoSrc = !empty($member['photo_path']) ? url((string) $member['photo_path']) : 'https://placehold.co/48x48?text=GYM';
              ?>
              <article class="rounded-xl border border-slate-700 bg-slate-900/60 p-3">
                <div class="flex items-start gap-3">
                  <img src="<?= e($photoSrc) ?>" alt="Member" class="h-12 w-12 rounded-xl object-cover ring-1 ring-slate-700">
                  <div class="min-w-0 flex-1">
                    <p class="truncate font-semibold text-slate-100"><?= e($member['full_name']) ?></p>
                    <p class="text-xs text-slate-500"><?= e($member['member_code']) ?></p>
                    <p class="mt-1 text-xs text-slate-400">Ends: <?= e($member['membership_end_date']) ?></p>
                  </div>
                </div>
                <div class="mt-3 flex items-center justify-between gap-2">
                  <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 <?= e($badgeClass) ?>"><?= e($status) ?></span>
                </div>
                <div class="mt-3 grid grid-cols-2 gap-2">
                  <a href="<?= e(url('/members/edit') . '?id=' . (string) $member['id']) ?>" class="flex h-11 items-center justify-center rounded-lg bg-white/90 px-3 text-xs font-semibold text-slate-900 transition hover:bg-white">Edit</a>
                  <a href="<?= e(url('/members/qr') . '?id=' . (string) $member['id']) ?>" class="flex h-11 items-center justify-center rounded-lg border border-slate-600 bg-slate-900 px-3 text-xs font-semibold text-slate-200 transition hover:border-slate-400 hover:bg-slate-800">QR</a>
                </div>
                <form action="<?= e(url('/members/delete')) ?>" method="post" class="mt-2">
                  <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                  <input type="hidden" name="id" value="<?= e((string) $member['id']) ?>">
                  <button
                    type="submit"
                    class="flex h-11 w-full items-center justify-center rounded-lg bg-rose-500/90 px-3 text-xs font-semibold text-white transition hover:bg-rose-500"
                    onclick="return confirm('Delete this member? This action cannot be undone.');"
                  >Delete</button>
                </form>
              </article>
            <?php endforeach; ?>
            <?php if (count($members) === 0): ?>
              <p class="rounded-xl border border-slate-700 bg-slate-900/60 px-3 py-6 text-center text-sm text-slate-500">No members found for the current filter.</p>
            <?php endif; ?>
          </div>

          <div class="mt-4 hidden overflow-x-auto md:block">
            <table class="min-w-full text-sm">
              <thead>
                <tr class="border-b border-slate-800 text-left text-xs uppercase tracking-wide text-slate-500">
                  <th class="pb-2 pr-4 font-semibold">Member</th>
                  <th class="pb-2 pr-4 font-semibold">Membership End</th>
                  <th class="pb-2 pr-4 font-semibold">Status</th>
                  <th class="pb-2 pr-4 font-semibold">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($members as $member): ?>
                  <?php
                  $status = (new DateTimeImmutable((string) $member['membership_end_date'])) >= new DateTimeImmutable('today') ? 'Active' : 'Expired';
                  $badgeClass = $status === 'Active'
                      ? 'bg-emerald-400/20 text-emerald-300 ring-emerald-400/40'
                      : 'bg-rose-400/20 text-rose-300 ring-rose-400/40';
                  $photoSrc = !empty($member['photo_path']) ? url((string) $member['photo_path']) : 'https://placehold.co/48x48?text=GYM';
                  ?>
                  <tr class="border-b border-slate-900/80">
                    <td class="py-3 pr-4">
                      <div class="flex items-center gap-3">
                        <img
                          src="<?= e($photoSrc) ?>"
                          alt="Member"
                          class="h-12 w-12 rounded-xl object-cover ring-1 ring-slate-700"
                        >
                        <div>
                          <p class="font-semibold text-slate-100"><?= e($member['full_name']) ?></p>
                          <p class="text-xs text-slate-500"><?= e($member['member_code']) ?></p>
                        </div>
                      </div>
                    </td>
                    <td class="py-3 pr-4 text-slate-300"><?= e($member['membership_end_date']) ?></td>
                    <td class="py-3 pr-4">
                      <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 <?= e($badgeClass) ?>"><?= e($status) ?></span>
                    </td>
                    <td class="py-3 pr-4">
                      <div class="flex flex-wrap items-center gap-2">
                        <a href="<?= e(url('/members/edit') . '?id=' . (string) $member['id']) ?>" class="inline-flex h-10 items-center rounded-lg bg-white/90 px-3 py-1.5 text-xs font-semibold text-slate-900 transition hover:bg-white">Edit</a>
                        <a href="<?= e(url('/members/qr') . '?id=' . (string) $member['id']) ?>" class="inline-flex h-10 items-center rounded-lg border border-slate-600 bg-slate-900 px-3 py-1.5 text-xs font-semibold text-slate-200 transition hover:border-slate-400 hover:bg-slate-800">QR</a>
                        <form action="<?= e(url('/members/delete')) ?>" method="post" class="inline">
                          <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                          <input type="hidden" name="id" value="<?= e((string) $member['id']) ?>">
                          <button
                            type="submit"
                            class="inline-flex h-10 items-center rounded-lg bg-rose-500/90 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-rose-500"
                            onclick="return confirm('Delete this member? This action cannot be undone.');"
                          >Delete</button>
                        </form>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
                <?php if (count($members) === 0): ?>
                  <tr>
                    <td colspan="4" class="py-8 text-center text-sm text-slate-500">No members found for the current filter.</td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </section>
      </div>
    </div>
  </section>
</main>
<?php require __DIR__ . '/../partials/foot.php'; ?>
