<?php

declare(strict_types=1);

$title = 'Member QR';
$dashboardShell = true;

$photoSrc = !empty($member['photo_path'])
    ? url((string) $member['photo_path'])
    : 'https://placehold.co/96x96/111111/555555?text=RCF';

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

$displayGender = ucwords(str_replace('_', ' ', (string) ($member['gender'] ?? 'N/A')));
$displayEmail  = (string) ($member['email'] ?? 'N/A');
$displayExpiry = (string) ($member['membership_end_date'] ?? 'N/A');

$logoUrl = url('/assets/img/repcore-removebg-preview.png');

require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/nav.php';
?>

<div class="page-enter" style="max-width: 1280px; margin: 0 auto; padding: 32px 16px 64px;">

  <!-- Page header -->
  <div style="margin-bottom: 32px; display: flex; align-items: flex-end; justify-content: space-between; gap: 16px; flex-wrap: wrap;" class="no-print">
    <div>
      <p style="font-size: 11px; letter-spacing: 0.14em; color: var(--muted); text-transform: uppercase; margin: 0 0 6px;">QR Export</p>
      <h1 style="font-family: 'Bebas Neue', sans-serif; font-size: clamp(32px, 5vw, 48px); letter-spacing: 0.10em; color: var(--white); margin: 0; line-height: 1;">Member QR Card</h1>
    </div>
    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
      <a href="<?= e(url('/members')) ?>" class="btn-ghost" style="height: 38px; font-size: 11px;">← Members</a>
      <button type="button" onclick="window.print()" class="btn-primary" style="height: 38px; font-size: 11px;">🖨 Print Card</button>
    </div>
  </div>

  <!-- Two-column layout -->
  <div style="display: grid; gap: 16px;" class="lg:grid-cols-[280px_1fr]">

    <!-- Sidebar -->
    <aside class="order-2 lg:order-1 no-print" style="display: flex; flex-direction: column; gap: 16px;">

      <!-- Member info -->
      <div class="card" style="padding: 20px;">
        <div class="section-rule" style="margin-bottom: 16px;">
          <span style="font-family: 'Bebas Neue', sans-serif; font-size: 16px; letter-spacing: 0.12em; color: var(--white);">Member Info</span>
        </div>
        <div style="display: flex; align-items: center; gap: 14px; margin-bottom: 16px;">
          <img src="<?= e($photoSrc) ?>" alt="Member photo" style="width: 64px; height: 64px; border-radius: 2px; object-fit: cover; border: 1px solid var(--border); flex-shrink: 0;">
          <div>
            <p style="font-size: 14px; font-weight: 600; color: var(--near); margin: 0 0 2px;"><?= e((string) $member['full_name']) ?></p>
            <p style="font-size: 11px; color: var(--muted); margin: 0 0 8px;"><?= e((string) $member['member_code']) ?></p>
            <span class="<?= e($statusBadgeClass) ?>"><?= e($membershipStatus) ?></span>
          </div>
        </div>
        <div style="display: flex; flex-direction: column; gap: 2px; background: var(--border); border: 1px solid var(--border); border-radius: 2px; overflow: hidden;">
          <?php
          $infoRows = [
            ['End date', $displayExpiry],
            ['Email',    $displayEmail],
            ['Gender',   $displayGender],
          ];
          foreach ($infoRows as [$rowLabel, $rowVal]): ?>
            <div style="background: var(--raised); padding: 10px 14px; display: flex; justify-content: space-between; align-items: center; gap: 8px;">
              <span style="font-size: 11px; color: var(--muted);"><?= e($rowLabel) ?></span>
              <span style="font-size: 11px; font-weight: 600; color: var(--near);"><?= e($rowVal) ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Actions -->
      <div class="card" style="padding: 20px;">
        <p class="label" style="margin-bottom: 12px;">Actions</p>
        <div style="display: flex; flex-direction: column; gap: 8px;">
          <a href="<?= e(url('/members')) ?>" class="btn-ghost" style="width: 100%; font-size: 12px;">← Back to Members</a>
          <button type="button" onclick="window.print()" class="btn-primary" style="width: 100%; font-size: 12px;">🖨 Print QR Card</button>
          <a id="downloadQrPng" href="#" class="btn-ghost" style="width: 100%; font-size: 12px; opacity: 0.5; pointer-events: none;">Download QR PNG</a>
          <button id="regenerateQrBtn" type="button" class="btn-ghost" style="width: 100%; font-size: 12px;">Regenerate QR</button>
        </div>
      </div>

    </aside>

    <!-- Main: Business Card Preview + Admin QR -->
    <section class="order-1 lg:order-2" style="display: flex; flex-direction: column; gap: 16px;">

      <!-- BUSINESS CARD PREVIEW -->
      <div class="card" style="padding: 20px 24px;">
        <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-bottom: 20px;" class="no-print">
          <div>
            <h2 style="font-family: 'Bebas Neue', sans-serif; font-size: 20px; letter-spacing: 0.12em; color: var(--white); margin: 0 0 4px;">Print Preview</h2>
            <p style="font-size: 13px; color: var(--muted); margin: 0;">Business card size: 3.5 × 2 inches — QR left · Details right</p>
          </div>
        </div>

        <div style="display: flex; justify-content: center; padding: 8px 0;">
          <!-- BUSINESS CARD -->
          <div class="bcard" id="printCard" data-qr="<?= e($qrTokenForRender) ?>">

            <!-- Header: Logo + Brand -->
            <div class="bcard-header">
              <img src="<?= e($logoUrl) ?>" alt="REP CORE" class="bcard-logo">
              <div class="bcard-brand">
                <p class="bcard-brand-name">REP CORE FITNESS</p>
                <p class="bcard-brand-sub">Member ID Card</p>
              </div>
            </div>

            <!-- Body: QR left · Details right -->
            <div class="bcard-body">
              <div class="bcard-qr-wrap">
                <div class="bcard-qr" id="cardQrWrap"></div>
              </div>
              <div class="bcard-details">
                <p class="bcard-name"><?= e((string) $member['full_name']) ?></p>
                <div class="bcard-divider"></div>
                <div class="bcard-info-row">
                  <span class="bcard-info-label">Code</span>
                  <span class="bcard-info-val"><?= e((string) $member['member_code']) ?></span>
                </div>
                <div class="bcard-info-row">
                  <span class="bcard-info-label">Gender</span>
                  <span class="bcard-info-val"><?= e($displayGender) ?></span>
                </div>
                <div class="bcard-info-row">
                  <span class="bcard-info-label">Email</span>
                  <span class="bcard-info-val bcard-email"><?= e($displayEmail) ?></span>
                </div>
              </div>
            </div>

          </div><!-- /.bcard -->
        </div>
      </div>

      <!-- Admin QR canvas + payload -->
      <div class="card no-print" style="padding: 20px 24px;">
        <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-bottom: 20px;">
          <div>
            <h2 style="font-family: 'Bebas Neue', sans-serif; font-size: 20px; letter-spacing: 0.12em; color: var(--white); margin: 0 0 4px;">Scanner Payload</h2>
            <p style="font-size: 13px; color: var(--muted); margin: 0;">QR encodes a scanner token. Full payload available below.</p>
          </div>
          <span id="qrStatus" style="font-size: 13px; font-weight: 600; color: var(--dim);">Rendering QR code...</span>
        </div>

        <div style="display: grid; gap: 16px;" class="lg:grid-cols-[minmax(240px,300px)_1fr]">
          <div style="background: #ffffff; border: 1px solid var(--border); border-radius: 2px; padding: 16px; display: flex; align-items: center; justify-content: center;">
            <div id="memberQrCanvas" style="width: 100%; max-width: 280px; min-height: 240px; display: flex; align-items: center; justify-content: center;"></div>
          </div>
          <div class="card-raised" style="padding: 16px;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
              <p style="font-size: 13px; font-weight: 600; color: var(--light); margin: 0;">Raw QR Payload</p>
              <button id="copyPayloadBtn" type="button" class="btn-ghost" style="height: 28px; padding: 0 10px; font-size: 11px;">Copy</button>
            </div>
            <textarea id="payloadOutput" class="input" style="height: 200px; resize: vertical; font-family: monospace; font-size: 11px; line-height: 1.6;" readonly><?= e($prettyPayload) ?></textarea>
          </div>
        </div>
      </div>

    </section>
  </div><!-- /.grid -->
</div><!-- /.page-enter -->

<script src="<?= e(asset('lib/qrcode.min.js')) ?>"></script>
<script>
(() => {
  const memberId          = <?= json_encode((int) ($member['id'] ?? 0), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
  let   qrText            = <?= json_encode($qrTokenForRender, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
  let   payload           = <?= json_encode($rawPayload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
  const memberCode        = <?= json_encode((string) ($member['member_code'] ?? ''), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
  const csrfToken         = <?= json_encode((string) ($csrfToken ?? ''), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
  const regenerateEndpoint = <?= json_encode(url('/api/members/regenerate-qr'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

  const canvasWrap      = document.getElementById('memberQrCanvas');
  const cardQrWrap      = document.getElementById('cardQrWrap');
  const statusEl        = document.getElementById('qrStatus');
  const downloadLink    = document.getElementById('downloadQrPng');
  const copyPayloadBtn  = document.getElementById('copyPayloadBtn');
  const regenerateQrBtn = document.getElementById('regenerateQrBtn');
  const payloadOutput   = document.getElementById('payloadOutput');

  if (!canvasWrap || !statusEl || !downloadLink || !payloadOutput) return;

  let regenerateLoading = false;

  const getQrSize = () => {
    const width = Math.floor(canvasWrap.clientWidth || 280);
    return Math.max(200, Math.min(280, width));
  };

  const setStatus = (message, tone = 'info') => {
    const colors = { info: 'var(--dim)', success: 'var(--near)', warning: '#f59e0b', error: '#f87171', accent: '#22d3ee' };
    statusEl.textContent = message;
    statusEl.style.color = colors[tone] || colors.info;
  };

  const disableDownload = () => {
    downloadLink.href = '#';
    downloadLink.style.opacity = '0.5';
    downloadLink.style.pointerEvents = 'none';
  };

  const clearEl = (el) => { while (el.firstChild) el.removeChild(el.firstChild); };

  const formatPayload = (raw) => {
    try { return JSON.stringify(JSON.parse(raw), null, 2); } catch (_e) { return raw; }
  };

  const enableDownload = () => {
    try {
      const canvas = canvasWrap.querySelector('canvas');
      const img    = canvasWrap.querySelector('img');
      const dataUrl = canvas
        ? canvas.toDataURL('image/png')
        : (img && img.src.startsWith('data:image/') ? img.src : '');
      if (!dataUrl) { disableDownload(); setStatus('QR rendered, but PNG download unavailable.', 'warning'); return; }
      const safeCode = (memberCode || 'member').toLowerCase().replace(/[^a-z0-9\-_]+/g, '-');
      downloadLink.href     = dataUrl;
      downloadLink.download = safeCode + '-qr.png';
      downloadLink.style.opacity      = '1';
      downloadLink.style.pointerEvents = 'auto';
    } catch (_e) { disableDownload(); setStatus('PNG download not supported here.', 'warning'); }
  };

  const renderQr = (content) => {
    const text = String(content || '').trim();
    if (!text)             { disableDownload(); setStatus('QR token is empty.', 'error'); return; }
    if (!window.QRCode)    { disableDownload(); setStatus('QR renderer unavailable.', 'error'); return; }

    // Admin canvas (large, dark-on-white)
    clearEl(canvasWrap);
    try {
      const qrSize = getQrSize();
      new window.QRCode(canvasWrap, { text, width: qrSize, height: qrSize,
        colorDark: '#0f172a', colorLight: '#ffffff',
        correctLevel: window.QRCode.CorrectLevel ? window.QRCode.CorrectLevel.M : 0 });
    } catch (_e) { disableDownload(); setStatus('Unable to render QR payload.', 'error'); return; }

    // Card QR (larger for business card — white-on-dark)
    if (cardQrWrap) {
      clearEl(cardQrWrap);
      try {
        new window.QRCode(cardQrWrap, { text, width: 116, height: 116,
          colorDark: '#ffffff', colorLight: '#111111',
          correctLevel: window.QRCode.CorrectLevel ? window.QRCode.CorrectLevel.M : 0 });
      } catch (_e) {}
    }

    setStatus('QR ready. Print, download, or regenerate.', 'success');
    window.setTimeout(enableDownload, 30);
  };

  const setRegenerateLoading = (loading) => {
    regenerateLoading = loading;
    if (!regenerateQrBtn) return;
    regenerateQrBtn.disabled          = loading;
    regenerateQrBtn.style.opacity     = loading ? '0.7' : '1';
    regenerateQrBtn.style.pointerEvents = loading ? 'none' : 'auto';
    regenerateQrBtn.textContent       = loading ? 'Regenerating…' : 'Regenerate QR';
  };

  const regenerateQr = async () => {
    if (regenerateLoading) return;
    if (!window.confirm('Regenerate this member\'s QR? Old printed cards will stop working.')) return;
    setRegenerateLoading(true);
    setStatus('Regenerating QR token…', 'accent');
    try {
      const body = new URLSearchParams({ id: String(memberId), _csrf: csrfToken });
      const res  = await fetch(regenerateEndpoint, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8', 'X-Requested-With': 'XMLHttpRequest' },
        body: body.toString(),
      });
      let data = null;
      try { data = await res.json(); } catch (_e) { throw new Error('Invalid server response.'); }
      if (!res.ok || !data || data.ok !== true) throw new Error(data?.message || 'Failed to regenerate QR.');
      payload  = String(data.member?.qr_payload || '');
      qrText   = String(data.member?.qr_token   || '');
      if (!qrText && payload) {
        try { const p = JSON.parse(payload); qrText = String(p?.qr_token || '').trim(); } catch (_e) { qrText = payload; }
      }
      payloadOutput.value = formatPayload(payload);
      renderQr(qrText);
      setStatus('QR regenerated. Old QR codes are now invalid.', 'success');
    } catch (err) {
      setStatus(err instanceof Error ? err.message : 'Failed to regenerate QR.', 'error');
    } finally { setRegenerateLoading(false); }
  };

  disableDownload();
  renderQr(qrText);

  if (copyPayloadBtn) {
    copyPayloadBtn.addEventListener('click', async () => {
      if (!navigator.clipboard) { setStatus('Clipboard unavailable.', 'warning'); return; }
      try { await navigator.clipboard.writeText(payloadOutput.value); setStatus('Payload copied.', 'accent'); }
      catch (_e) { setStatus('Copy failed.', 'warning'); }
    });
  }
  if (regenerateQrBtn) regenerateQrBtn.addEventListener('click', regenerateQr);
  window.addEventListener('resize', () => renderQr(qrText));
})();
</script>

<style>
/* =========================================================
   BUSINESS CARD  — 3.5 × 2 in, dark theme
   Layout: header strip | body [ QR-left | details-right ]
   ========================================================= */
.bcard {
  width: 3.5in;
  height: 2in;
  background: #111111;
  border: 1px solid #2a2a2a;
  border-radius: 3px;
  box-sizing: border-box;
  padding: 0.13in 0.15in 0.13in 0.13in;
  display: flex;
  flex-direction: column;
  gap: 0;
  page-break-inside: avoid;
  break-inside: avoid;
}

/* --- Header --- */
.bcard-header {
  display: flex;
  align-items: center;
  gap: 7px;
  border-bottom: 1px solid #2a2a2a;
  padding-bottom: 6px;
  margin-bottom: 6px;
  flex-shrink: 0;
}
.bcard-logo {
  width: auto;
  height: 52px;
  border-radius: 2px;
  display: block;
  flex-shrink: 0;
  background: transparent;
  /* Force the logo to render pure white so it's always visible
     on the dark card, regardless of the original PNG colour */
  filter: brightness(0) invert(1);
}
.bcard-brand { line-height: 1.1; }
.bcard-brand-name {
  font-size: 9pt;
  font-weight: 700;
  letter-spacing: 0.10em;
  text-transform: uppercase;
  color: #ffffff;
  margin: 0;
}
.bcard-brand-sub {
  font-size: 6pt;
  color: #666666;
  margin: 1px 0 0;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

/* --- Body (flex row) --- */
.bcard-body {
  display: flex;
  flex-direction: row;
  align-items: center;
  gap: 10px;
  flex: 1;
  min-height: 0;
}

/* --- QR side (left) --- */
.bcard-qr-wrap {
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
}
/* qrcode.js renders both a canvas (used to draw) and an img (the output).
   Hide the canvas — only the img should print. */
.bcard-qr canvas { display: none !important; }
.bcard-qr img {
  display: block !important;
  width: 116px !important;
  height: 116px !important;
  border-radius: 2px;
  border: 2px solid #333333;
}

/* --- Details side (right) --- */
.bcard-details {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 3px;
}
.bcard-name {
  font-size: 9.5pt;
  font-weight: 700;
  color: #22c55e;
  margin: 0 0 3px;
  letter-spacing: 0.02em;
  line-height: 1.2;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.bcard-divider {
  width: 100%;
  height: 1px;
  background: #2a2a2a;
  margin-bottom: 2px;
}
.bcard-info-row {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  gap: 4px;
  line-height: 1.3;
}
.bcard-info-label {
  font-size: 5.5pt;
  color: #666666;
  text-transform: uppercase;
  letter-spacing: 0.07em;
  flex-shrink: 0;
}
.bcard-info-val {
  font-size: 7pt;
  color: #cccccc;
  font-weight: 600;
  text-align: right;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 130px;
}
.bcard-email  { font-size: 6.5pt; }
.bcard-active { color: #22c55e !important; }
.bcard-expired { color: #f87171 !important; }

/* =========================================================
   PRINT
   ========================================================= */
@media print {
  @page { size: auto; margin: 0.3in; }
  body { background: #0a0a0a !important; color: #ffffff !important; }
  .no-print, header, nav { display: none !important; }
  .bcard {
    background: #111111 !important;
    border: 1px solid #2a2a2a !important;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }
  .bcard-logo { background: transparent !important; filter: brightness(0) invert(1) !important; }
  .bcard-qr img, .bcard-qr canvas { border-color: #333333 !important; }
}
</style>

<?php require __DIR__ . '/../partials/foot.php'; ?>
