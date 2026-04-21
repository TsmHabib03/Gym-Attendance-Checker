<?php

declare(strict_types=1);

$title = 'Member QR';
$dashboardShell = true;

$photoSrc = !empty($member['photo_path'])
    ? url((string) $member['photo_path'])
    : 'https://placehold.co/96x96?text=GYM';

$membershipActive = (new DateTimeImmutable((string) $member['membership_end_date'])) >= new DateTimeImmutable('today');
$membershipStatus = $membershipActive ? 'Active' : 'Expired';
$statusBadgeClass = $membershipActive ? 'stat-badge stat-badge-ok' : 'stat-badge stat-badge-danger';

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
<div class="page-enter" style="max-width: 1280px; margin: 0 auto; padding: 32px 16px 64px;">
  <!-- Page header -->
  <div style="margin-bottom: 32px; display: flex; align-items: flex-end; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
    <div>
      <p style="font-size: 11px; letter-spacing: 0.14em; color: var(--muted); text-transform: uppercase; margin: 0 0 6px;">QR Export</p>
      <h1 style="
        font-family: 'Bebas Neue', sans-serif;
        font-size: clamp(32px, 5vw, 48px);
        letter-spacing: 0.10em;
        color: var(--white);
        margin: 0; line-height: 1;
      " class="print-ink">Member QR Card</h1>
    </div>
    <div style="display: flex; gap: 8px; flex-wrap: wrap;" class="no-print">
      <a href="<?= e(url('/members')) ?>" class="btn-ghost" style="height: 38px; font-size: 11px;">← Members</a>
    </div>
  </div>

  <!-- Two-column layout -->
  <div style="display: grid; gap: 16px;" class="lg:grid-cols-[280px_1fr]">
    <!-- Sidebar -->
    <aside class="order-2 lg:order-1 print-surface" style="display: flex; flex-direction: column; gap: 16px;">
      <!-- Member info -->
      <div class="card print-surface" style="padding: 20px;">
        <div class="section-rule" style="margin-bottom: 16px;">
          <span style="font-family: 'Bebas Neue', sans-serif; font-size: 16px; letter-spacing: 0.12em; color: var(--white);" class="print-ink">Member Info</span>
        </div>

        <div style="display: flex; align-items: center; gap: 14px; margin-bottom: 16px;">
          <img
            src="<?= e($photoSrc) ?>"
            alt="Member photo"
            style="width: 64px; height: 64px; border-radius: 2px; object-fit: cover; border: 1px solid var(--border); flex-shrink: 0;"
          >
          <div>
            <p style="font-size: 14px; font-weight: 600; color: var(--near); margin: 0 0 2px;" class="print-ink"><?= e((string) $member['full_name']) ?></p>
            <p style="font-size: 11px; color: var(--muted); margin: 0 0 8px;" class="print-ink"><?= e((string) $member['member_code']) ?></p>
            <span class="<?= e($statusBadgeClass) ?> print-ink"><?= e($membershipStatus) ?></span>
          </div>
        </div>

        <div style="display: flex; flex-direction: column; gap: 2px; background: var(--border); border: 1px solid var(--border); border-radius: 2px; overflow: hidden;">
          <?php
          $infoRows = [
            ['End date', (string) $member['membership_end_date']],
            ['Email', (string) ($member['email'] ?? '-')],
            ['Gender', (string) ($member['gender'] ?? '-')],
          ];
          foreach ($infoRows as [$rowLabel, $rowVal]): ?>
            <div style="background: var(--raised); padding: 10px 14px; display: flex; justify-content: space-between; align-items: center; gap: 8px;" class="print-surface">
              <span style="font-size: 11px; color: var(--muted);" class="print-ink"><?= e($rowLabel) ?></span>
              <span style="font-size: 11px; font-weight: 600; color: var(--near);" class="print-ink"><?= e($rowVal) ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Actions -->
      <div class="card no-print" style="padding: 20px;">
        <p class="label" style="margin-bottom: 12px;">Actions</p>
        <div style="display: flex; flex-direction: column; gap: 8px;">
          <a href="<?= e(url('/members')) ?>" class="btn-ghost" style="width: 100%; font-size: 12px;">← Back to Members</a>
          <button type="button" onclick="window.print()" class="btn-primary" style="width: 100%; font-size: 12px;">Print QR Card</button>
          <a id="downloadQrPng" href="#" class="btn-ghost" style="width: 100%; font-size: 12px; opacity: 0.5; pointer-events: none;">Download QR PNG</a>
          <button id="regenerateQrBtn" type="button" class="btn-ghost" style="width: 100%; font-size: 12px;">Regenerate QR</button>
        </div>
      </div>
    </aside>

    <!-- Main: QR canvas + payload -->
    <section class="order-1 lg:order-2 card print-surface" style="padding: 20px 24px;">
      <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-bottom: 20px;">
        <div>
          <h2 style="font-family: 'Bebas Neue', sans-serif; font-size: 20px; letter-spacing: 0.12em; color: var(--white); margin: 0 0 4px;" class="print-ink">Scanner Payload</h2>
          <p style="font-size: 13px; color: var(--muted); margin: 0;" class="print-ink">QR encodes a scanner token. Full payload available below.</p>
        </div>
        <span id="qrStatus" style="font-size: 13px; font-weight: 600; color: var(--dim);" class="print-ink">Rendering QR code...</span>
      </div>

      <div style="display: grid; gap: 16px;" class="lg:grid-cols-[minmax(240px,300px)_1fr]">
        <!-- QR canvas area -->
        <div style="background: #ffffff; border: 1px solid var(--border); border-radius: 2px; padding: 16px; display: flex; align-items: center; justify-content: center;" class="print-surface">
          <div id="memberQrCanvas" style="width: 100%; max-width: 280px; min-height: 240px; display: flex; align-items: center; justify-content: center;"></div>
        </div>

        <!-- Raw payload -->
        <div class="card-raised" style="padding: 16px;">
          <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
            <p style="font-size: 13px; font-weight: 600; color: var(--light); margin: 0;" class="print-ink">Raw QR Payload</p>
            <button id="copyPayloadBtn" type="button" class="btn-ghost no-print" style="height: 28px; padding: 0 10px; font-size: 11px;">Copy</button>
          </div>
          <textarea
            id="payloadOutput"
            class="input"
            style="height: 200px; resize: vertical; font-family: monospace; font-size: 11px; line-height: 1.6;"
            readonly
          ><?= e($prettyPayload) ?></textarea>
        </div>
      </div>
    </section>
  </div>
</div>

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

  if (!canvasWrap || !status || !downloadLink || !payloadOutput) {
    return;
  }

  let regenerateLoading = false;

  const getQrSize = () => {
    const width = Math.floor(canvasWrap.clientWidth || 280);
    return Math.max(200, Math.min(280, width));
  };

  const setStatus = (message, tone = 'info') => {
    const colors = {
      info: 'var(--dim)',
      success: 'var(--near)',
      warning: '#f59e0b',
      error: '#f87171',
      accent: '#22d3ee',
    };
    status.textContent = message;
    status.style.color = colors[tone] || colors.info;
  };

  const disableDownload = () => {
    downloadLink.href = '#';
    downloadLink.style.opacity = '0.5';
    downloadLink.style.pointerEvents = 'none';
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
        setStatus('QR rendered, but PNG download is unavailable in this browser.', 'warning');
        return;
      }

      const safeCode = (memberCode || 'member').toLowerCase().replace(/[^a-z0-9\-_]+/g, '-');
      downloadLink.href = dataUrl;
      downloadLink.download = safeCode + '-qr.png';
      downloadLink.style.opacity = '1';
      downloadLink.style.pointerEvents = 'auto';
    } catch (_error) {
      disableDownload();
      setStatus('QR rendered but PNG download is not supported here.', 'warning');
    }
  };

  const renderQr = (content) => {
    const normalizedContent = String(content || '').trim();
    if (normalizedContent === '') {
      disableDownload();
      setStatus('QR token is empty for this member.', 'error');
      return;
    }

    if (!window.QRCode) {
      disableDownload();
      setStatus('QR renderer is unavailable. Reload this page.', 'error');
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
      setStatus('Unable to render QR code payload.', 'error');
      return;
    }

    setStatus('QR ready. Print, download, or regenerate.', 'success');
    window.setTimeout(enableDownload, 30);
  };

  const setRegenerateLoading = (loading) => {
    regenerateLoading = loading;
    if (!regenerateQrBtn) {
      return;
    }

    regenerateQrBtn.disabled = loading;
    regenerateQrBtn.style.opacity = loading ? '0.7' : '1';
    regenerateQrBtn.style.pointerEvents = loading ? 'none' : 'auto';
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
    setStatus('Regenerating QR token...', 'accent');

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
      setStatus('QR regenerated. Old QR codes are now invalid.', 'success');
    } catch (error) {
      const message = error instanceof Error ? error.message : 'Failed to regenerate QR.';
      setStatus(message, 'error');
    } finally {
      setRegenerateLoading(false);
    }
  };

  disableDownload();
  renderQr(qrText);

  if (copyPayloadBtn) {
    copyPayloadBtn.addEventListener('click', async () => {
      if (!navigator.clipboard || !payloadOutput) {
        setStatus('Clipboard is not available in this browser.', 'warning');
        return;
      }

      try {
        await navigator.clipboard.writeText(payloadOutput.value);
        setStatus('Payload copied to clipboard.', 'accent');
      } catch (_error) {
        setStatus('Copy failed. Select and copy manually.', 'warning');
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
