<?php

declare(strict_types=1);

$title = 'Dashboard';
$memberStats = $overview['members'];
$attendanceStats = $overview['attendance_today'];
$recentLogs = $overview['recent_logs'];

$photoCaptureOn = in_array(strtolower((string) ($settings['photo_capture_enabled'] ?? 'true')), ['1', 'true', 'yes', 'on'], true);
$expiryDays = (string) ($settings['expiry_reminder_days'] ?? \App\Core\Config::get('EXPIRY_REMINDER_DAYS', '7'));

$todayAccepted = (int) $attendanceStats['accepted'];
$todayDenied = (int) ($attendanceStats['expired_denied'] + $attendanceStats['duplicate_denied']);
$todayTotal = $todayAccepted + $todayDenied;
$activeRatio = (int) round($memberStats['total'] > 0 ? ($memberStats['active'] / $memberStats['total']) * 100 : 0);
$activeRatio = max(0, min(100, $activeRatio));
$liveUpdatedAt = (new DateTimeImmutable())->format('H:i:s');

$bars = [
    max(0, $todayAccepted),
    max(0, $todayDenied),
    max(0, (int) $memberStats['active']),
    max(0, (int) $memberStats['expired']),
    max(0, (int) $memberStats['total']),
];

$maxBar = max($bars);
$maxBar = $maxBar > 0 ? $maxBar : 1;
$labels = ['Accepted', 'Denied', 'Active', 'Expired', 'Total'];
$tones = ['bg-cyan-300', 'bg-amber-300', 'bg-emerald-400', 'bg-rose-400', 'bg-white'];
$barKeys = ['accepted', 'denied', 'active', 'expired', 'total'];

require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/nav.php';
?>
<main class="mx-auto w-full max-w-7xl px-3 pb-8 sm:px-4 md:px-6 lg:px-8 lg:pb-10">
  <section class="fade-up mt-4 rounded-[30px] border border-slate-800 bg-[#070b12]/90 p-3 shadow-2xl shadow-black/50 sm:mt-6 sm:p-4 lg:p-6">
    <?php require __DIR__ . '/../partials/flash.php'; ?>

    <div class="grid gap-4 xl:grid-cols-[280px_minmax(0,1fr)] xl:gap-5">
      <aside class="order-2 rounded-2xl border border-slate-800 bg-[#0f131b] p-4 sm:p-5 xl:order-1">
        <div class="rounded-2xl border border-slate-700 bg-slate-900/60 p-4">
          <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Control Room</p>
          <h1 class="mt-2 font-display text-3xl font-bold leading-tight text-white sm:text-4xl">Manage your gym with confidence</h1>
          <p class="mt-3 text-sm text-slate-400">Daily attendance, member activity, and quick actions in one modern dashboard.</p>

          <div class="mt-5 space-y-2">
            <a href="<?= e(url('/members/create')) ?>" class="flex h-11 items-center justify-center rounded-xl bg-white px-4 text-center text-sm font-semibold text-slate-900 transition hover:bg-slate-100">Add New Member</a>
            <a href="<?= e(url('/attendance/scan')) ?>" class="flex h-11 items-center justify-center rounded-xl border border-slate-600 px-4 text-center text-sm font-semibold text-slate-200 transition hover:border-slate-400 hover:bg-slate-800">Open Scanner</a>
          </div>
        </div>

        <div class="mt-5 rounded-2xl border border-slate-700 bg-slate-900/60 p-4">
          <div class="flex items-center justify-between">
            <p class="text-sm font-semibold text-slate-200">Member Capacity</p>
            <p id="memberCapacityRatio" class="text-xs text-slate-400"><?= e((string) $activeRatio) ?>% active</p>
          </div>
          <div id="memberCapacityMobile" class="mt-3 rounded-lg border border-slate-700 bg-slate-900/70 px-3 py-2 text-xs text-slate-300 sm:hidden">
            <p>Active members now: <span id="memberCapacityMobileCount" class="font-semibold text-slate-100"><?= e((string) $memberStats['active']) ?></span></p>
          </div>
          <div id="memberCapacityCells" class="mt-3 hidden grid-cols-10 gap-1 sm:grid">
            <?php for ($i = 1; $i <= 50; $i++): ?>
              <?php $threshold = (int) round(($activeRatio / 100) * 50); ?>
              <span data-capacity-cell="<?= e((string) $i) ?>" class="h-2.5 rounded-full <?= $i <= $threshold ? 'bg-white' : 'bg-slate-700' ?>"></span>
            <?php endfor; ?>
          </div>
          <div class="mt-4 flex items-center justify-between text-xs text-slate-400">
            <span>Active members</span>
            <span id="memberCapacityCount"><?= e((string) $memberStats['active']) ?>/<?= e((string) $memberStats['total']) ?></span>
          </div>
        </div>

        <div class="mt-5 rounded-2xl border border-slate-700 bg-gradient-to-br from-slate-900 to-slate-800 p-4">
          <p class="text-sm font-semibold text-white">Need quick setup?</p>
          <p class="mt-2 text-xs text-slate-300">Configure attendance verification and reminder policy from System Settings.</p>
        </div>
      </aside>

      <div class="order-1 space-y-4 xl:order-2 xl:space-y-5">
        <div class="grid grid-cols-1 gap-3 xs:grid-cols-2 md:gap-4 xl:grid-cols-5">
          <article class="rounded-2xl border border-slate-800 bg-[#0f141d] p-4">
            <p class="text-xs uppercase tracking-wide text-slate-400">Total Members</p>
            <p id="totalMembersValue" class="mt-2 font-display text-3xl font-bold text-white"><?= e((string) $memberStats['total']) ?></p>
            <p class="text-xs text-slate-500">Registered accounts</p>
          </article>
          <article class="rounded-2xl border border-slate-800 bg-[#0f141d] p-4">
            <p class="text-xs uppercase tracking-wide text-slate-400">Active Members</p>
            <p id="activeMembersValue" class="mt-2 font-display text-3xl font-bold text-emerald-400"><?= e((string) $memberStats['active']) ?></p>
            <p class="text-xs text-slate-500">Ready for check-in</p>
          </article>
          <article class="rounded-2xl border border-slate-800 bg-[#0f141d] p-4">
            <p class="text-xs uppercase tracking-wide text-slate-400">Expired Members</p>
            <p id="expiredMembersValue" class="mt-2 font-display text-3xl font-bold text-rose-400"><?= e((string) $memberStats['expired']) ?></p>
            <p class="text-xs text-slate-500">Needs renewal</p>
          </article>
          <article class="rounded-2xl border border-slate-800 bg-[#0f141d] p-4">
            <p class="text-xs uppercase tracking-wide text-slate-400">Accepted Today</p>
            <p id="acceptedTodayValue" class="mt-2 font-display text-3xl font-bold text-cyan-300"><?= e((string) $todayAccepted) ?></p>
            <p class="text-xs text-slate-500">Successful scans</p>
          </article>
          <article class="rounded-2xl border border-slate-800 bg-[#0f141d] p-4">
            <p class="text-xs uppercase tracking-wide text-slate-400">Denied Today</p>
            <p id="deniedTodayValue" class="mt-2 font-display text-3xl font-bold text-amber-300"><?= e((string) $todayDenied) ?></p>
            <p class="text-xs text-slate-500">Expired or duplicate</p>
          </article>
        </div>

        <div class="grid gap-4 lg:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)] lg:gap-5">
          <section class="rounded-2xl border border-slate-800 bg-[#0f141d] p-4 sm:p-5">
            <div class="flex items-center justify-between">
              <h2 class="font-display text-2xl font-bold text-white">Attendance Activity</h2>
              <p class="text-xs text-slate-400">Live at <span id="dashboardLiveUpdated"><?= e($liveUpdatedAt) ?></span></p>
            </div>

            <div class="mt-4 grid grid-cols-2 gap-2 md:hidden">
              <div class="rounded-xl border border-slate-700 bg-slate-900/70 px-3 py-2">
                <p class="text-[11px] uppercase tracking-wide text-slate-400">Accepted</p>
                <p id="quickAcceptedValue" class="font-display text-2xl font-bold text-cyan-300"><?= e((string) $todayAccepted) ?></p>
              </div>
              <div class="rounded-xl border border-slate-700 bg-slate-900/70 px-3 py-2">
                <p class="text-[11px] uppercase tracking-wide text-slate-400">Denied</p>
                <p id="quickDeniedValue" class="font-display text-2xl font-bold text-amber-300"><?= e((string) $todayDenied) ?></p>
              </div>
              <div class="rounded-xl border border-slate-700 bg-slate-900/70 px-3 py-2">
                <p class="text-[11px] uppercase tracking-wide text-slate-400">Active</p>
                <p id="quickActiveValue" class="font-display text-2xl font-bold text-emerald-400"><?= e((string) $memberStats['active']) ?></p>
              </div>
              <div class="rounded-xl border border-slate-700 bg-slate-900/70 px-3 py-2">
                <p class="text-[11px] uppercase tracking-wide text-slate-400">Expired</p>
                <p id="quickExpiredValue" class="font-display text-2xl font-bold text-rose-400"><?= e((string) $memberStats['expired']) ?></p>
              </div>
            </div>

            <div class="mt-6 hidden h-44 items-end gap-3 rounded-xl border border-slate-800 bg-slate-900/60 p-4 md:flex">
              <?php foreach ($bars as $index => $value): ?>
                <?php
                $barKey = $barKeys[$index];
                $height = (int) round(($value / $maxBar) * 120);
                $height = max(12, $height);
                ?>
                <div class="flex flex-1 flex-col items-center justify-end gap-2">
                  <div id="barFill-<?= e($barKey) ?>" class="<?= e($tones[$index]) ?> w-full rounded-t-xl" style="height: <?= e((string) $height) ?>px"></div>
                  <p class="text-[11px] uppercase tracking-wide text-slate-400"><?= e($labels[$index]) ?></p>
                  <p id="barValue-<?= e($barKey) ?>" class="text-xs font-semibold text-slate-200"><?= e((string) $value) ?></p>
                </div>
              <?php endforeach; ?>
            </div>
            <p class="mt-3 text-xs text-slate-400">Total scan attempts today: <span id="totalScanAttemptsValue" class="font-semibold text-slate-200"><?= e((string) $todayTotal) ?></span></p>
          </section>

          <section class="rounded-2xl border border-slate-800 bg-[#0f141d] p-4 sm:p-5">
            <h2 class="font-display text-2xl font-bold text-white">System Settings</h2>
            <p class="mt-1 text-sm text-slate-400">Configure photo verification and reminder thresholds.</p>

            <form action="<?= e(url('/settings')) ?>" method="post" class="mt-5 space-y-4">
              <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">

              <label class="flex items-center gap-3 rounded-xl border border-slate-700 bg-slate-900/60 p-3">
                <input type="checkbox" name="photo_capture_enabled" value="1" class="h-5 w-5" <?= $photoCaptureOn ? 'checked' : '' ?>>
                <span class="text-sm font-semibold text-slate-200">Enable optional photo capture</span>
              </label>

              <label class="block">
                <span class="mb-1 block text-sm font-semibold text-slate-300">Expiry reminder days</span>
                <input
                  type="number"
                  min="1"
                  max="30"
                  name="expiry_reminder_days"
                  value="<?= e($expiryDays) ?>"
                  class="h-11 w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2.5 text-slate-100 outline-none ring-cyan-300 transition focus:ring sm:px-4"
                  required
                >
              </label>

              <button type="submit" class="h-11 w-full rounded-xl bg-white px-4 py-2.5 font-semibold text-slate-900 transition hover:bg-slate-100">Save Settings</button>
            </form>
          </section>
        </div>

        <section class="rounded-2xl border border-slate-800 bg-[#0f141d] p-4 sm:p-5">
          <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-display text-2xl font-bold text-white">Recent Scan Activity</h2>
            <a href="<?= e(url('/attendance/scan')) ?>" class="inline-flex h-11 items-center rounded-xl bg-white px-4 py-2 text-sm font-semibold text-slate-900 transition hover:bg-slate-100">Open Scanner</a>
          </div>

          <div id="recentLogsCards" class="mt-4 space-y-3 md:hidden">
            <?php foreach ($recentLogs as $log): ?>
              <?php
              $badgeClass = match ($log['status']) {
                  'accepted' => 'bg-emerald-400/20 text-emerald-300 ring-emerald-400/40',
                  'expired_denied' => 'bg-rose-400/20 text-rose-300 ring-rose-400/40',
                  default => 'bg-amber-300/20 text-amber-200 ring-amber-300/40',
              };
              ?>
              <article class="rounded-xl border border-slate-700 bg-slate-900/60 p-3">
                <div class="flex items-start justify-between gap-3">
                  <div class="min-w-0">
                    <p class="truncate font-semibold text-slate-100"><?= e($log['full_name']) ?></p>
                    <p class="text-xs text-slate-500"><?= e($log['member_code']) ?></p>
                  </div>
                  <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 <?= e($badgeClass) ?>"><?= e($log['status']) ?></span>
                </div>
                <p class="mt-2 text-xs text-slate-300"><?= e($log['scanned_at']) ?></p>
                <p class="mt-1 text-xs text-slate-400"><?= e($log['note'] ?? '') ?></p>
              </article>
            <?php endforeach; ?>
            <?php if (count($recentLogs) === 0): ?>
              <p class="rounded-xl border border-slate-700 bg-slate-900/60 px-3 py-6 text-center text-sm text-slate-500">No recent scan activity yet.</p>
            <?php endif; ?>
          </div>

          <div class="mt-4 hidden overflow-x-auto md:block">
            <table class="min-w-full text-sm">
              <thead>
                <tr class="border-b border-slate-800 text-left text-xs uppercase tracking-wide text-slate-500">
                  <th class="pb-2 pr-4 font-semibold">Member</th>
                  <th class="pb-2 pr-4 font-semibold">Status</th>
                  <th class="pb-2 pr-4 font-semibold">Time</th>
                  <th class="pb-2 pr-4 font-semibold">Note</th>
                </tr>
              </thead>
              <tbody id="recentLogsBody">
                <?php foreach ($recentLogs as $log): ?>
                  <?php
                  $badgeClass = match ($log['status']) {
                      'accepted' => 'bg-emerald-400/20 text-emerald-300 ring-emerald-400/40',
                      'expired_denied' => 'bg-rose-400/20 text-rose-300 ring-rose-400/40',
                      default => 'bg-amber-300/20 text-amber-200 ring-amber-300/40',
                  };
                  ?>
                  <tr class="border-b border-slate-900/80">
                    <td class="py-3 pr-4">
                      <p class="font-semibold text-slate-100"><?= e($log['full_name']) ?></p>
                      <p class="text-xs text-slate-500"><?= e($log['member_code']) ?></p>
                    </td>
                    <td class="py-3 pr-4">
                      <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 <?= e($badgeClass) ?>"><?= e($log['status']) ?></span>
                    </td>
                    <td class="py-3 pr-4 text-slate-300"><?= e($log['scanned_at']) ?></td>
                    <td class="py-3 pr-4 text-slate-400"><?= e($log['note'] ?? '') ?></td>
                  </tr>
                <?php endforeach; ?>
                <?php if (count($recentLogs) === 0): ?>
                  <tr>
                    <td colspan="4" class="py-8 text-center text-sm text-slate-500">No recent scan activity yet.</td>
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
<script>
  window.DASHBOARD_LIVE_CONFIG = {
    endpoint: <?= json_encode(url('/dashboard?live=1'), JSON_UNESCAPED_SLASHES) ?>,
    refreshMs: 20000,
  };
</script>
<script>
  (function () {
    const config = window.DASHBOARD_LIVE_CONFIG || {};
    if (!config.endpoint) {
      return;
    }

    const refs = {
      totalMembers: document.getElementById('totalMembersValue'),
      activeMembers: document.getElementById('activeMembersValue'),
      expiredMembers: document.getElementById('expiredMembersValue'),
      acceptedToday: document.getElementById('acceptedTodayValue'),
      deniedToday: document.getElementById('deniedTodayValue'),
      quickAccepted: document.getElementById('quickAcceptedValue'),
      quickDenied: document.getElementById('quickDeniedValue'),
      quickActive: document.getElementById('quickActiveValue'),
      quickExpired: document.getElementById('quickExpiredValue'),
      totalScanAttempts: document.getElementById('totalScanAttemptsValue'),
      memberCapacityRatio: document.getElementById('memberCapacityRatio'),
      memberCapacityCount: document.getElementById('memberCapacityCount'),
      memberCapacityMobileCount: document.getElementById('memberCapacityMobileCount'),
      liveUpdated: document.getElementById('dashboardLiveUpdated'),
      recentLogsBody: document.getElementById('recentLogsBody'),
      recentLogsCards: document.getElementById('recentLogsCards')
    };

    const barKeys = ['accepted', 'denied', 'active', 'expired', 'total'];
    const capacityCells = Array.from(document.querySelectorAll('[data-capacity-cell]'));

    if (!refs.totalMembers || !refs.activeMembers || !refs.expiredMembers || !refs.acceptedToday || !refs.deniedToday || !refs.totalScanAttempts || !refs.memberCapacityRatio || !refs.memberCapacityCount || !refs.liveUpdated || !refs.recentLogsBody) {
      return;
    }

    const toInt = (value) => {
      const parsed = Number.parseInt(String(value ?? '0'), 10);
      return Number.isNaN(parsed) ? 0 : parsed;
    };

    const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => {
      const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;'
      };
      return map[char] || char;
    });

    const badgeClassForStatus = (status) => {
      if (status === 'accepted') {
        return 'bg-emerald-400/20 text-emerald-300 ring-emerald-400/40';
      }
      if (status === 'expired_denied') {
        return 'bg-rose-400/20 text-rose-300 ring-rose-400/40';
      }
      return 'bg-amber-300/20 text-amber-200 ring-amber-300/40';
    };

    const formatLiveTime = (timestamp) => {
      if (!timestamp) {
        return '';
      }

      const parsed = new Date(String(timestamp).replace(' ', 'T'));
      if (Number.isNaN(parsed.getTime())) {
        return String(timestamp);
      }

      return parsed.toLocaleTimeString([], {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
      });
    };

    const renderRecentLogs = (logs) => {
      if (!Array.isArray(logs) || logs.length === 0) {
        refs.recentLogsBody.innerHTML = '<tr><td colspan="4" class="py-8 text-center text-sm text-slate-500">No recent scan activity yet.</td></tr>';
        if (refs.recentLogsCards) {
          refs.recentLogsCards.innerHTML = '<p class="rounded-xl border border-slate-700 bg-slate-900/60 px-3 py-6 text-center text-sm text-slate-500">No recent scan activity yet.</p>';
        }
        return;
      }

      refs.recentLogsBody.innerHTML = logs.map((log) => {
        const status = String(log.status || '');
        const badgeClass = badgeClassForStatus(status);

        return '<tr class="border-b border-slate-900/80">'
          + '<td class="py-3 pr-4">'
          + '<p class="font-semibold text-slate-100">' + escapeHtml(log.full_name || '') + '</p>'
          + '<p class="text-xs text-slate-500">' + escapeHtml(log.member_code || '') + '</p>'
          + '</td>'
          + '<td class="py-3 pr-4">'
          + '<span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ' + badgeClass + '">' + escapeHtml(status) + '</span>'
          + '</td>'
          + '<td class="py-3 pr-4 text-slate-300">' + escapeHtml(log.scanned_at || '') + '</td>'
          + '<td class="py-3 pr-4 text-slate-400">' + escapeHtml(log.note || '') + '</td>'
          + '</tr>';
      }).join('');

      if (refs.recentLogsCards) {
        refs.recentLogsCards.innerHTML = logs.map((log) => {
          const status = String(log.status || '');
          const badgeClass = badgeClassForStatus(status);

          return '<article class="rounded-xl border border-slate-700 bg-slate-900/60 p-3">'
            + '<div class="flex items-start justify-between gap-3">'
            + '<div class="min-w-0">'
            + '<p class="truncate font-semibold text-slate-100">' + escapeHtml(log.full_name || '') + '</p>'
            + '<p class="text-xs text-slate-500">' + escapeHtml(log.member_code || '') + '</p>'
            + '</div>'
            + '<span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ' + badgeClass + '">' + escapeHtml(status) + '</span>'
            + '</div>'
            + '<p class="mt-2 text-xs text-slate-300">' + escapeHtml(log.scanned_at || '') + '</p>'
            + '<p class="mt-1 text-xs text-slate-400">' + escapeHtml(log.note || '') + '</p>'
            + '</article>';
        }).join('');
      }
    };

    const updateMemberCapacityCells = (ratio) => {
      const threshold = Math.round((ratio / 100) * 50);
      capacityCells.forEach((cell, index) => {
        const active = index < threshold;
        cell.classList.remove('bg-white', 'bg-slate-700');
        cell.classList.add(active ? 'bg-white' : 'bg-slate-700');
      });
    };

    const updateBars = (values) => {
      const maxBar = Math.max(1, ...values);

      barKeys.forEach((key, index) => {
        const fill = document.getElementById('barFill-' + key);
        const valueEl = document.getElementById('barValue-' + key);
        if (!fill || !valueEl) {
          return;
        }

        const value = toInt(values[index] ?? 0);
        const height = Math.max(12, Math.round((value / maxBar) * 120));
        fill.style.height = String(height) + 'px';
        valueEl.textContent = String(value);
      });
    };

    const applyOverview = (overview, generatedAt) => {
      const members = overview.members || {};
      const attendance = overview.attendance_today || {};
      const logs = overview.recent_logs || [];

      const totalMembers = toInt(members.total);
      const activeMembers = toInt(members.active);
      const expiredMembers = toInt(members.expired);
      const acceptedToday = toInt(attendance.accepted);
      const deniedToday = toInt(attendance.expired_denied) + toInt(attendance.duplicate_denied);
      const totalAttempts = acceptedToday + deniedToday;

      const activeRatio = totalMembers > 0 ? Math.round((activeMembers / totalMembers) * 100) : 0;

      refs.totalMembers.textContent = String(totalMembers);
      refs.activeMembers.textContent = String(activeMembers);
      refs.expiredMembers.textContent = String(expiredMembers);
      refs.acceptedToday.textContent = String(acceptedToday);
      refs.deniedToday.textContent = String(deniedToday);
      if (refs.quickAccepted) refs.quickAccepted.textContent = String(acceptedToday);
      if (refs.quickDenied) refs.quickDenied.textContent = String(deniedToday);
      if (refs.quickActive) refs.quickActive.textContent = String(activeMembers);
      if (refs.quickExpired) refs.quickExpired.textContent = String(expiredMembers);
      refs.totalScanAttempts.textContent = String(totalAttempts);
      refs.memberCapacityRatio.textContent = String(activeRatio) + '% active';
      refs.memberCapacityCount.textContent = String(activeMembers) + '/' + String(totalMembers);
      if (refs.memberCapacityMobileCount) refs.memberCapacityMobileCount.textContent = String(activeMembers);
      refs.liveUpdated.textContent = formatLiveTime(generatedAt);

      updateMemberCapacityCells(activeRatio);
      updateBars([acceptedToday, deniedToday, activeMembers, expiredMembers, totalMembers]);
      renderRecentLogs(logs);
    };

    const fetchLive = async () => {
      try {
        const response = await fetch(config.endpoint, {
          method: 'GET',
          headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          },
          cache: 'no-store'
        });

        if (!response.ok) {
          return;
        }

        const payload = await response.json();
        if (!payload || payload.ok !== true || !payload.data || !payload.data.overview) {
          return;
        }

        applyOverview(payload.data.overview, payload.data.generated_at || '');
      } catch (_error) {
      }
    };

    const refreshMs = Math.max(5000, Number.parseInt(String(config.refreshMs || 20000), 10) || 20000);
    window.setInterval(fetchLive, refreshMs);
  })();
</script>
<?php require __DIR__ . '/../partials/foot.php'; ?>
