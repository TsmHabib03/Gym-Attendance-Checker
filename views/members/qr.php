<?php

declare(strict_types=1);

$title = 'Member QR';
$dashboardShell = true;

$photoSrc = !empty($member['photo_path'])
    ? url((string) $member['photo_path'])
    : 'https://placehold.co/96x96?text=GYM';

$membershipActive = (new DateTimeImmutable((string) $member['membership_end_date'])) >= new DateTimeImmutable('today');
$membershipStatus = $membershipActive ? 'Active' : 'Expired';
$membershipClass = $membershipActive
    ? 'bg-emerald-400/20 text-emerald-300 ring-emerald-400/40'
    : 'bg-rose-400/20 text-rose-300 ring-rose-400/40';

$rawPayload = trim((string) $qrPayloadJson);
$prettyPayload = $rawPayload;
$decodedPayload = json_decode($rawPayload, true);
if (is_array($decodedPayload)) {
    $formatted = json_encode($decodedPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if (is_string($formatted)) {
        $prettyPayload = $formatted;
    }
}

$qrTokenForRender = '';
if (is_array($decodedPayload) && isset($decodedPayload['qr_token']) && is_string($decodedPayload['qr_token'])) {
    $qrTokenForRender = trim($decodedPayload['qr_token']);
}

if ($qrTokenForRender === '' && isset($member['qr_token']) && is_string($member['qr_token'])) {
    $qrTokenForRender = trim($member['qr_token']);
}

if ($qrTokenForRender === '') {
    $qrTokenForRender = $rawPayload;
}

require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/nav.php';
?>
<main class="mx-auto w-full max-w-7xl px-3 pb-8 sm:px-4 md:px-6 lg:px-8 lg:pb-10">
  <section class="fade-up mt-4 rounded-[30px] border border-slate-800 bg-[#070b12]/90 p-3 shadow-2xl shadow-black/50 sm:mt-6 sm:p-4 lg:p-6 print-surface">
    <div class="grid gap-4 lg:grid-cols-[300px_minmax(0,1fr)] lg:gap-5">
      <aside class="order-2 rounded-2xl border border-slate-800 bg-[#0f131b] p-4 sm:p-5 lg:order-1 print-surface">
        <p class="text-xs uppercase tracking-[0.2em] text-slate-400 print-ink">QR Export</p>
        <h1 class="mt-2 font-display text-3xl font-bold leading-tight text-white print-ink sm:text-4xl">Member QR card</h1>
        <p class="mt-3 text-sm text-slate-400 print-ink">Print, download, or regenerate a scanner-ready QR for this member.</p>

        <div class="mt-5 rounded-xl border border-slate-700 bg-slate-900/70 p-4 print-surface">
          <div class="flex items-center gap-3">
            <img src="<?= e($photoSrc) ?>" alt="Member photo" class="h-16 w-16 rounded-xl object-cover ring-1 ring-slate-700">
            <div>
              <p class="text-sm font-semibold text-slate-100 print-ink"><?= e((string) $member['full_name']) ?></p>
              <p class="text-xs text-slate-400 print-ink"><?= e((string) $member['member_code']) ?></p>
            </div>
          </div>

          <div class="mt-3 flex items-center justify-between text-xs text-slate-400 print-ink">
            <span>Membership</span>
            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 <?= e($membershipClass) ?> print-ink"><?= e($membershipStatus) ?></span>
          </div>
          <div class="mt-2 flex items-center justify-between text-xs text-slate-400 print-ink">
            <span>End date</span>
            <span class="font-semibold text-slate-200 print-ink"><?= e((string) $member['membership_end_date']) ?></span>
          </div>
          <div class="mt-2 flex items-center justify-between text-xs text-slate-400 print-ink">
            <span>Email</span>
            <span class="font-semibold text-slate-200 print-ink"><?= e((string) ($member['email'] ?? '-')) ?></span>
          </div>
          <div class="mt-2 flex items-center justify-between text-xs text-slate-400 print-ink">
            <span>Gender</span>
            <span class="font-semibold text-slate-200 print-ink"><?= e((string) ($member['gender'] ?? '-')) ?></span>
          </div>
        </div>

        <div class="no-print mt-5 space-y-2">
          <a href="<?= e(url('/members')) ?>" class="flex h-11 items-center justify-center rounded-xl border border-slate-600 px-4 text-center text-sm font-semibold text-slate-200 transition hover:border-slate-400 hover:bg-slate-800">Back to Members</a>
          <button type="button" onclick="window.print()" class="block h-11 w-full rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-slate-900 transition hover:bg-slate-100">Print QR Card</button>
          <a id="downloadQrPng" href="#" class="pointer-events-none flex h-11 items-center justify-center rounded-xl border border-slate-600 bg-slate-900 px-4 py-2.5 text-center text-sm font-semibold text-slate-200 opacity-60 transition hover:border-slate-400 hover:bg-slate-800">Download QR PNG</a>
          <button id="regenerateQrBtn" type="button" class="block h-11 w-full rounded-xl border border-cyan-500/50 bg-cyan-500/10 px-4 py-2.5 text-sm font-semibold text-cyan-200 transition hover:border-cyan-400 hover:bg-cyan-500/20">Regenerate QR</button>
        </div>
      </aside>

      <section class="order-1 rounded-2xl border border-slate-800 bg-[#0f141d] p-4 sm:p-5 lg:order-2 print-surface">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
          <div>
            <h2 class="font-display text-2xl font-bold text-white print-ink">Scanner Payload</h2>
            <p class="text-sm text-slate-400 print-ink">QR encodes a scanner token for maximum scan reliability. Full payload remains available below.</p>
          </div>
          <p id="qrStatus" class="text-sm font-semibold text-cyan-300">Rendering QR code...</p>
        </div>

        <div class="mt-5 grid gap-4 lg:grid-cols-[minmax(240px,320px)_minmax(0,1fr)]">
          <div class="rounded-2xl border border-slate-700 bg-white p-3 sm:p-4">
            <div id="memberQrCanvas" class="mx-auto flex min-h-[240px] w-full max-w-[320px] items-center justify-center sm:min-h-[280px]"></div>
          </div>

          <div class="rounded-2xl border border-slate-700 bg-slate-900/60 p-4">
            <div class="flex items-center justify-between">
              <p class="text-sm font-semibold text-slate-200">Raw QR Payload</p>
              <button id="copyPayloadBtn" type="button" class="no-print rounded-lg border border-slate-600 px-3 py-1 text-xs font-semibold text-slate-200 transition hover:border-slate-400 hover:bg-slate-800">Copy</button>
            </div>
            <textarea id="payloadOutput" class="mt-3 h-56 w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-xs text-slate-200 outline-none sm:h-64" readonly><?= e($prettyPayload) ?></textarea>
          </div>
        </div>
      </section>
    </div>
  </section>
</main>

<script src="<?= e(asset('lib/qrcode.min.js')) ?>"></script>
<script>
(() => {
  const memberId = <?= json_encode((int) ($member['id'] ?? 0), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
  let qrText = <?= json_encode($qrTokenForRender, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
  let payload = <?= json_encode($rawPayload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
  const memberCode = <?= json_encode((string) ($member['member_code'] ?? ''), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
  const csrfToken = <?= json_encode((string) ($csrfToken ?? ''), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
  const regenerateEndpoint = <?= json_encode(url('/api/members/regenerate-qr'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

  const canvasWrap = document.getElementById('memberQrCanvas');
  const status = document.getElementById('qrStatus');
  const downloadLink = document.getElementById('downloadQrPng');
  const copyPayloadBtn = document.getElementById('copyPayloadBtn');
  const regenerateQrBtn = document.getElementById('regenerateQrBtn');
  const payloadOutput = document.getElementById('payloadOutput');
  let regenerateLoading = false;

  const getQrSize = () => {
    const width = Math.floor(canvasWrap.clientWidth || 320);
    return Math.max(220, Math.min(320, width));
  };

  const setStatus = (message, className) => {
    status.textContent = message;
    status.className = 'text-sm font-semibold ' + className;
  };

  const disableDownload = () => {
    downloadLink.href = '#';
    downloadLink.classList.add('pointer-events-none', 'opacity-60');
  };

  const clearCanvas = () => {
    while (canvasWrap.firstChild) {
      canvasWrap.removeChild(canvasWrap.firstChild);
    }
  };

  const formatPayloadForTextarea = (rawPayload) => {
    try {
      const parsed = JSON.parse(rawPayload);
      return JSON.stringify(parsed, null, 2);
    } catch (_error) {
      return rawPayload;
    }
  };

  const enableDownload = () => {
    try {
      const renderedCanvas = canvasWrap.querySelector('canvas');
      const renderedImage = canvasWrap.querySelector('img');
      const dataUrl = renderedCanvas
        ? renderedCanvas.toDataURL('image/png')
        : (renderedImage && renderedImage.src.startsWith('data:image/') ? renderedImage.src : '');

      if (!dataUrl) {
        disableDownload();
        setStatus('QR rendered, but PNG download is unavailable in this browser.', 'text-amber-300');
        return;
      }

      const safeCode = (memberCode || 'member').toLowerCase().replace(/[^a-z0-9\-_]+/g, '-');
      downloadLink.href = dataUrl;
      downloadLink.download = safeCode + '-qr.png';
      downloadLink.classList.remove('pointer-events-none', 'opacity-60');
    } catch (_error) {
      disableDownload();
      setStatus('QR rendered but PNG download is not supported here.', 'text-amber-300');
    }
  };

  const renderQr = (content) => {
    const normalizedContent = String(content || '').trim();
    if (normalizedContent === '') {
      disableDownload();
      setStatus('QR token is empty for this member.', 'text-rose-300');
      return;
    }

    if (!window.QRCode) {
      disableDownload();
      setStatus('QR renderer is unavailable. Reload this page.', 'text-rose-300');
      return;
    }

    clearCanvas();

    try {
      const qrSize = getQrSize();
      new window.QRCode(canvasWrap, {
        text: normalizedContent,
        width: qrSize,
        height: qrSize,
        colorDark: '#0f172a',
        colorLight: '#ffffff',
        correctLevel: window.QRCode.CorrectLevel ? window.QRCode.CorrectLevel.M : 0,
      });
    } catch (_error) {
      disableDownload();
      setStatus('Unable to render QR code payload.', 'text-rose-300');
      return;
    }

    setStatus('QR ready. Print, download, or regenerate.', 'text-emerald-300');
    window.setTimeout(enableDownload, 30);
  };

  const setRegenerateLoading = (loading) => {
    regenerateLoading = loading;
    regenerateQrBtn.disabled = loading;
    regenerateQrBtn.classList.toggle('opacity-70', loading);
    regenerateQrBtn.classList.toggle('cursor-not-allowed', loading);
    regenerateQrBtn.textContent = loading ? 'Regenerating...' : 'Regenerate QR';
  };

  const regenerateQr = async () => {
    if (regenerateLoading) {
      return;
    }

    const accepted = window.confirm('Regenerate this member QR now? Old printed QR codes will stop working.');
    if (!accepted) {
      return;
    }

    setRegenerateLoading(true);
    setStatus('Regenerating QR token...', 'text-cyan-300');

    try {
      const body = new URLSearchParams({
        id: String(memberId),
        _csrf: csrfToken,
      });

      const response = await fetch(regenerateEndpoint, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: body.toString(),
      });

      let data = null;
      try {
        data = await response.json();
      } catch (_error) {
        throw new Error('Regenerate failed due to invalid server response.');
      }

      if (!response.ok || !data || data.ok !== true) {
        throw new Error((data && data.message) ? data.message : 'Failed to regenerate QR.');
      }

      payload = String(data.member?.qr_payload || '');
      qrText = String(data.member?.qr_token || '');
      if (!qrText && payload) {
        try {
          const parsed = JSON.parse(payload);
          qrText = String(parsed?.qr_token || '').trim();
        } catch (_error) {
          qrText = payload;
        }
      }

      payloadOutput.value = formatPayloadForTextarea(payload);
      renderQr(qrText);
      setStatus('QR regenerated. Old QR codes are now invalid.', 'text-emerald-300');
    } catch (error) {
      const message = error instanceof Error ? error.message : 'Failed to regenerate QR.';
      setStatus(message, 'text-rose-300');
    } finally {
      setRegenerateLoading(false);
    }
  };

  disableDownload();
  renderQr(qrText);

  if (copyPayloadBtn) {
    copyPayloadBtn.addEventListener('click', async () => {
      if (!navigator.clipboard || !payloadOutput) {
        setStatus('Clipboard is not available in this browser.', 'text-amber-300');
        return;
      }

      try {
        await navigator.clipboard.writeText(payloadOutput.value);
        setStatus('Payload copied to clipboard.', 'text-cyan-300');
      } catch (_error) {
        setStatus('Copy failed. Select and copy manually.', 'text-amber-300');
      }
    });
  }

  if (regenerateQrBtn) {
    regenerateQrBtn.addEventListener('click', regenerateQr);
  }

  window.addEventListener('resize', () => {
    renderQr(qrText);
  });
})();
</script>
<style>
@media print {
  header,
  .no-print {
    display: none !important;
  }

  body {
    background: #ffffff !important;
    color: #111827 !important;
  }

  .print-surface {
    border-color: #d1d5db !important;
    background: #ffffff !important;
    box-shadow: none !important;
  }

  .print-ink {
    color: #111827 !important;
  }
}
</style>
<?php require __DIR__ . '/../partials/foot.php'; ?>
