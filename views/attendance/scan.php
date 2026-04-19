<?php

declare(strict_types=1);

$title = 'QR Scanner';
$dashboardShell = true;
require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/nav.php';
?>
<main class="mx-auto w-full max-w-7xl px-3 pb-8 sm:px-4 md:px-6 lg:px-8 lg:pb-10">
  <section class="fade-up mt-4 rounded-[30px] border border-slate-800 bg-[#070b12]/90 p-3 shadow-2xl shadow-black/50 sm:mt-6 sm:p-4 lg:p-6">
    <?php require __DIR__ . '/../partials/flash.php'; ?>

    <div class="grid gap-4 xl:grid-cols-[300px_minmax(0,1fr)] xl:gap-5">
      <aside class="order-2 rounded-2xl border border-slate-800 bg-[#0f131b] p-4 sm:p-5 xl:order-1">
        <div class="rounded-2xl border border-slate-700 bg-slate-900/60 p-4">
          <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Scanner Room</p>
          <h1 class="mt-2 font-display text-3xl font-bold leading-tight text-white sm:text-4xl">QR attendance check-in</h1>
          <p class="mt-3 text-sm text-slate-400">Scan a member QR to verify status and record attendance in real time.</p>

          <div class="mt-5 space-y-2">
            <a href="<?= e(url('/members')) ?>" class="flex h-11 items-center justify-center rounded-xl border border-slate-600 px-4 text-center text-sm font-semibold text-slate-200 transition hover:border-slate-400 hover:bg-slate-800">Open Members</a>
            <a href="<?= e(url('/dashboard')) ?>" class="flex h-11 items-center justify-center rounded-xl bg-white px-4 text-center text-sm font-semibold text-slate-900 transition hover:bg-slate-100">Back to Dashboard</a>
          </div>
        </div>

        <div class="mt-5 rounded-2xl border border-slate-700 bg-slate-900/60 p-4">
          <p class="text-sm font-semibold text-slate-200">Scan Workflow</p>
          <ol class="mt-3 space-y-2 text-xs text-slate-400">
            <li class="rounded-lg border border-slate-700 bg-slate-900/60 px-3 py-2">Start scanner and point camera at member QR.</li>
            <li class="rounded-lg border border-slate-700 bg-slate-900/60 px-3 py-2">Wait for status response on the result panel.</li>
            <li class="rounded-lg border border-slate-700 bg-slate-900/60 px-3 py-2">Handle accepted, expired, or duplicate outcomes.</li>
          </ol>
        </div>
      </aside>

      <div class="order-1 grid gap-4 lg:order-2 lg:grid-cols-2 lg:gap-5">
        <article class="rounded-2xl border border-slate-800 bg-[#0f141d] p-4 sm:p-5">
          <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-display text-2xl font-bold text-white">Live Scanner</h2>
            <button id="toggleScan" class="h-11 w-full rounded-xl bg-white px-4 py-2 text-sm font-semibold text-slate-900 transition hover:bg-slate-100 sm:w-auto" aria-pressed="false">Start Scan</button>
          </div>
          <p class="mt-1 text-sm text-slate-400">Scan member QR to display profile and record check-in automatically.</p>

          <div class="mt-4 overflow-hidden rounded-2xl border border-slate-700 bg-black p-2">
            <video id="qrVideo" class="aspect-[4/3] w-full rounded-xl object-cover [filter:none] sm:aspect-video" autoplay playsinline muted></video>
          </div>

          <?php if ($photoCaptureEnabled): ?>
            <label class="mt-4 flex items-center gap-3 rounded-xl border border-slate-700 bg-slate-900/70 p-3">
              <input type="checkbox" id="capturePhoto" class="h-5 w-5" checked>
              <span class="text-sm font-semibold text-slate-200">Capture photo on successful scan</span>
            </label>
          <?php endif; ?>

          <p id="scannerStatus" class="mt-3 text-sm font-semibold text-slate-300">Scanner idle</p>
        </article>

        <article class="rounded-2xl border border-slate-800 bg-[#0f141d] p-4 sm:p-5">
          <h2 class="font-display text-2xl font-bold text-white">Check-in Result</h2>
          <p class="text-sm text-slate-400">Member details will appear right after scanning.</p>

          <div id="resultEmpty" class="mt-8 rounded-xl border border-dashed border-slate-700 bg-slate-900/70 p-8 text-center text-sm text-slate-400">
            Waiting for scan...
          </div>

          <div id="resultCard" class="mt-4 hidden space-y-4">
            <div class="flex flex-col gap-3 xs:flex-row xs:items-center">
              <img id="memberPhoto" src="" alt="Member" class="h-24 w-24 rounded-2xl object-cover ring-1 ring-slate-700">
              <div class="min-w-0">
                <p id="memberName" class="font-display text-2xl font-bold text-slate-100"></p>
                <p id="memberCode" class="text-sm text-slate-400"></p>
                <p class="mt-1 text-sm text-slate-400">Membership end: <span id="membershipEnd" class="font-semibold text-slate-200"></span></p>
              </div>
            </div>
            <div>
              <span id="membershipStatus" class="inline-flex rounded-full px-3 py-1 text-sm font-semibold"></span>
              <span id="scanStatus" class="ml-2 inline-flex rounded-full px-3 py-1 text-sm font-semibold"></span>
            </div>
            <div id="scanMessage" class="rounded-xl border border-slate-700 bg-slate-900/70 px-4 py-3 text-sm font-semibold text-slate-200"></div>
          </div>
        </article>
      </div>
    </div>
  </section>
</main>

<script>
  window.GYM_SCAN_CONFIG = {
    csrfToken: <?= json_encode($csrfToken, JSON_UNESCAPED_SLASHES) ?>,
    checkinEndpoint: <?= json_encode(url('/api/checkin'), JSON_UNESCAPED_SLASHES) ?>,
    appBasePath: <?= json_encode(url(''), JSON_UNESCAPED_SLASHES) ?>,
    photoCaptureEnabled: <?= $photoCaptureEnabled ? 'true' : 'false' ?>,
  };
</script>
<script src="https://unpkg.com/@zxing/library@0.21.3/umd/index.min.js"></script>
<script src="<?= e(asset('js/scan.js')) ?>"></script>
<?php require __DIR__ . '/../partials/foot.php'; ?>
