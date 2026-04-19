<?php

declare(strict_types=1);

$title = 'Edit Member';
$dashboardShell = true;

$currentPhotoSrc = !empty($member['photo_path']) ? url((string) $member['photo_path']) : 'https://placehold.co/80x80?text=GYM';
$membershipActive = (new DateTimeImmutable((string) $member['membership_end_date'])) >= new DateTimeImmutable('today');
$membershipBadgeClass = $membershipActive
    ? 'bg-emerald-400/20 text-emerald-300 ring-emerald-400/40'
    : 'bg-rose-400/20 text-rose-300 ring-rose-400/40';
$membershipStatus = $membershipActive ? 'Active' : 'Expired';

require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/nav.php';
?>
<main class="mx-auto w-full max-w-7xl px-3 pb-8 sm:px-4 md:px-6 lg:px-8 lg:pb-10">
  <section class="fade-up mt-4 rounded-[30px] border border-slate-800 bg-[#070b12]/90 p-3 shadow-2xl shadow-black/50 sm:mt-6 sm:p-4 lg:p-6">
    <?php require __DIR__ . '/../partials/flash.php'; ?>

    <div class="grid gap-4 xl:grid-cols-[300px_minmax(0,1fr)] xl:gap-5">
      <aside class="order-2 rounded-2xl border border-slate-800 bg-[#0f131b] p-4 sm:p-5 xl:order-1">
        <div class="rounded-2xl border border-slate-700 bg-slate-900/60 p-4">
          <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Member Update</p>
          <h1 class="mt-2 font-display text-3xl font-bold leading-tight text-white sm:text-4xl">Edit profile details</h1>
          <p class="mt-3 text-sm text-slate-400">Update member information, adjust expiry date, and replace profile photo when needed.</p>

          <div class="mt-5 rounded-xl border border-slate-700 bg-slate-900/70 p-3">
            <p class="text-sm font-semibold text-slate-200"><?= e((string) $member['full_name']) ?></p>
            <p class="mt-1 text-xs text-slate-500">Member code: <?= e((string) $member['member_code']) ?></p>
            <div class="mt-2">
              <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 <?= e($membershipBadgeClass) ?>"><?= e($membershipStatus) ?></span>
            </div>
          </div>

          <div class="mt-5 space-y-2">
            <a href="<?= e(url('/members')) ?>" class="flex h-11 items-center justify-center rounded-xl border border-slate-600 px-4 text-center text-sm font-semibold text-slate-200 transition hover:border-slate-400 hover:bg-slate-800">Back to Members</a>
            <a href="<?= e(url('/attendance/scan')) ?>" class="flex h-11 items-center justify-center rounded-xl bg-white px-4 text-center text-sm font-semibold text-slate-900 transition hover:bg-slate-100">Open Scanner</a>
          </div>
        </div>

        <div class="mt-5 rounded-2xl border border-slate-700 bg-slate-900/60 p-4">
          <p class="text-sm font-semibold text-slate-200">Current Membership</p>
          <div class="mt-3 space-y-2 text-xs text-slate-400">
            <div class="flex items-center justify-between rounded-lg border border-slate-700 bg-slate-900/60 px-3 py-2">
              <span>End date</span>
              <span class="font-semibold text-slate-200"><?= e((string) $member['membership_end_date']) ?></span>
            </div>
            <div class="flex items-center justify-between rounded-lg border border-slate-700 bg-slate-900/60 px-3 py-2">
              <span>Status</span>
              <span class="font-semibold <?= $membershipActive ? 'text-emerald-300' : 'text-rose-300' ?>"><?= e($membershipStatus) ?></span>
            </div>
          </div>
        </div>
      </aside>

      <section class="order-1 rounded-2xl border border-slate-800 bg-[#0f141d] p-4 sm:p-5 xl:order-2">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
          <div>
            <h2 class="font-display text-2xl font-bold text-white">Edit Member Information</h2>
            <p class="text-sm text-slate-400">Update profile and membership details.</p>
          </div>
          <p class="text-xs uppercase tracking-wide text-slate-500">Member ID #<?= e((string) $member['id']) ?></p>
        </div>

        <form action="<?= e(url('/members/edit')) ?>" method="post" enctype="multipart/form-data" class="mt-5 grid gap-4">
          <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
          <input type="hidden" name="id" value="<?= e((string) $member['id']) ?>">

          <label class="block">
            <span class="mb-1 block text-sm font-semibold text-slate-300">Full name</span>
            <input type="text" name="full_name" value="<?= e((string) $member['full_name']) ?>" class="h-11 w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2.5 text-slate-100 outline-none ring-cyan-300 transition focus:ring sm:px-4" required>
          </label>

          <label class="block">
            <span class="mb-1 block text-sm font-semibold text-slate-300">Email (optional)</span>
            <input type="email" name="email" value="<?= e((string) ($member['email'] ?? '')) ?>" class="h-11 w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2.5 text-slate-100 outline-none ring-cyan-300 transition focus:ring sm:px-4">
          </label>

          <label class="block">
            <span class="mb-1 block text-sm font-semibold text-slate-300">Gender</span>
            <?php $selectedGender = (string) ($member['gender'] ?? 'prefer_not_say'); ?>
            <select name="gender" class="h-11 w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2.5 text-slate-100 outline-none ring-cyan-300 transition focus:ring sm:px-4" required>
              <option value="male" <?= $selectedGender === 'male' ? 'selected' : '' ?>>Male</option>
              <option value="female" <?= $selectedGender === 'female' ? 'selected' : '' ?>>Female</option>
              <option value="other" <?= $selectedGender === 'other' ? 'selected' : '' ?>>Other</option>
              <option value="prefer_not_say" <?= $selectedGender === 'prefer_not_say' ? 'selected' : '' ?>>Prefer not to say</option>
            </select>
          </label>

          <label class="block">
            <span class="mb-1 block text-sm font-semibold text-slate-300">Membership end date</span>
            <input type="date" name="membership_end_date" value="<?= e((string) $member['membership_end_date']) ?>" class="h-11 w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2.5 text-slate-100 outline-none ring-cyan-300 transition focus:ring sm:px-4" required>
          </label>

          <div class="rounded-xl border border-slate-700 bg-slate-900/60 p-3">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Current photo</p>
            <div class="mt-3 flex flex-col gap-4 sm:flex-row sm:items-center">
              <img src="<?= e($currentPhotoSrc) ?>" alt="Current photo" class="h-24 w-24 rounded-xl object-cover ring-1 ring-slate-700">
              <label class="block w-full">
                <span class="mb-1 block text-sm font-semibold text-slate-300">Replace photo</span>
                <input type="file" name="photo" accept="image/png,image/jpeg,image/webp" class="h-11 w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-slate-100 outline-none ring-cyan-300 transition focus:ring sm:px-4">
              </label>
            </div>
          </div>

          <div class="mt-2 flex flex-wrap items-center gap-3">
            <button type="submit" class="inline-flex h-11 items-center rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-slate-900 transition hover:bg-slate-100">Update Member</button>
            <a href="<?= e(url('/members')) ?>" class="inline-flex h-11 items-center rounded-xl border border-slate-600 px-4 py-2.5 text-sm font-semibold text-slate-200 transition hover:border-slate-400 hover:bg-slate-800">Cancel</a>
            <button
              type="submit"
              formaction="<?= e(url('/members/delete')) ?>"
              formmethod="post"
              formnovalidate
              class="inline-flex h-11 items-center rounded-xl bg-rose-500/90 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-rose-500"
              onclick="return confirm('Delete this member? This action cannot be undone.');"
            >Delete Member</button>
          </div>
        </form>
      </section>
    </div>
  </section>
</main>
<?php require __DIR__ . '/../partials/foot.php'; ?>
