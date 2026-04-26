<?php

declare(strict_types=1);

require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/nav.php';
?>
<div class="page-enter page-container">
  <!-- Page header -->
  <div class="page-header">
    <div>
      <p style="font-size: 11px; letter-spacing: 0.14em; color: var(--muted); text-transform: uppercase; margin: 0 0 6px;">Scanner Room</p>
      <h1 style="
        font-family: 'Bebas Neue', sans-serif;
        font-size: clamp(28px, 5vw, 48px);
        letter-spacing: 0.10em;
        color: var(--white);
        margin: 0; line-height: 1;
      ">QR Scanner</h1>
    </div>
    <div class="page-header-actions">
      <a href="<?= e(url('/dashboard')) ?>" class="btn-ghost"   style="height: 38px; font-size: 11px;">← Dashboard</a>
      <a href="<?= e(url('/members')) ?>"   class="btn-primary" style="height: 38px; font-size: 11px;">Members</a>
    </div>
  </div>
  <!-- Flash -->
  <?php require __DIR__ . '/../partials/flash.php'; ?>
  <!-- Two-column layout -->
  <div class="sidebar-layout">
    <!-- ── SIDEBAR ── -->
    <aside class="order-2 lg:order-1" style="display: flex; flex-direction: column; gap: 16px; min-width: 0;">
      <!-- Info card -->
      <div class="card" style="padding: 20px;">
        <div class="section-rule" style="margin-bottom: 16px;">
          <span style="font-family: 'Bebas Neue', sans-serif; font-size: 16px; letter-spacing: 0.12em; color: var(--white);">Scanner</span>
        </div>
        <p style="font-size: 13px; color: var(--muted); line-height: 1.7; margin: 0 0 16px;">
          Scan a member QR to verify status and record attendance in real time.
        </p>
        <div style="display: flex; flex-direction: column; gap: 8px;">
          <a href="<?= e(url('/members')) ?>"   class="btn-ghost"   style="width: 100%; font-size: 12px;">Open Members</a>
          <a href="<?= e(url('/dashboard')) ?>" class="btn-primary" style="width: 100%; font-size: 12px;">Back to Dashboard</a>
        </div>
      </div>
      <!-- Workflow steps card -->
      <div class="card" style="padding: 20px;">
        <p style="font-size: 11px; font-weight: 600; letter-spacing: 0.12em; text-transform: uppercase; color: var(--muted); margin: 0 0 12px;">Scan Workflow</p>
        <div style="display: flex; flex-direction: column; gap: 8px;">
          <?php
          $steps = [
            'Start scanner and point camera at member QR.',
            'Wait for status response on the result panel.',
            'Handle accepted, expired, or duplicate outcomes.',
          ];
          foreach ($steps as $i => $step): ?>
            <div style="
              display: flex; align-items: flex-start; gap: 10px;
              background: var(--raised); border: 1px solid var(--border);
              border-radius: 2px; padding: 10px 12px;
            ">
              <span style="
                font-family: 'Bebas Neue', sans-serif;
                font-size: 13px; color: var(--muted);
                flex-shrink: 0; min-width: 16px; margin-top: 1px;
              "><?= e((string) ($i + 1)) ?></span>
              <span style="font-size: 12px; color: var(--dim); line-height: 1.5;"><?= e($step) ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </aside>
    <!-- ── MAIN: Scanner + Result ── -->
    <div class="order-1 lg:order-2 scanner-grid">
      <!-- Live Scanner panel -->
      <div class="card" style="padding: 20px 24px;">
        <div style="
          display: flex; align-items: center; justify-content: space-between;
          gap: 12px; flex-wrap: wrap; margin-bottom: 12px;
        ">
          <div>
            <h2 style="font-family: 'Bebas Neue', sans-serif; font-size: 20px; letter-spacing: 0.12em; color: var(--white); margin: 0 0 2px;">Live Scanner</h2>
            <p style="font-size: 13px; color: var(--muted); margin: 0;">Point camera at member QR to record check-in.</p>
          </div>
          <button
            id="toggleScan"
            class="btn-primary"
            aria-pressed="false"
            style="flex-shrink: 0; height: 40px; padding: 0 20px; font-size: 12px;"
          >Start Scan</button>
        </div>
        <div style="
          overflow: hidden; border-radius: 2px;
          border: 1px solid var(--border); background: #000;
          margin-bottom: 12px;
        ">
          <video
            id="qrVideo"
            style="display: block; width: 100%; aspect-ratio: 4/3; object-fit: cover;"
            autoplay playsinline muted
          ></video>
        </div>
        <?php if ($photoCaptureEnabled): ?>
          <label style="
            display: flex; align-items: center; gap: 12px;
            padding: 12px 14px;
            background: var(--raised); border: 1px solid var(--border);
            border-radius: 2px; cursor: pointer; margin-bottom: 12px;
          ">
            <input type="checkbox" id="capturePhoto" style="width: 16px; height: 16px; flex-shrink: 0; accent-color: var(--white);" checked>
            <span style="font-size: 13px; font-weight: 500; color: var(--light);">Capture photo on successful scan</span>
          </label>
        <?php endif; ?>
        <p id="scannerStatus" style="font-size: 13px; font-weight: 600; color: var(--dim); margin: 0;">Scanner idle</p>
      </div>
      <!-- Check-in Result panel -->
      <div class="card" style="padding: 20px 24px;">
        <div style="margin-bottom: 16px;">
          <h2 style="font-family: 'Bebas Neue', sans-serif; font-size: 20px; letter-spacing: 0.12em; color: var(--white); margin: 0 0 2px;">Check-in Result</h2>
          <p style="font-size: 13px; color: var(--muted); margin: 0;">Member details appear right after scanning.</p>
        </div>
        <!-- Empty state -->
        <div id="resultEmpty" style="
          padding: 40px 24px; text-align: center;
          border: 1px dashed var(--line); border-radius: 2px;
          font-size: 13px; color: var(--muted);
        ">
          Waiting for scan...
        </div>
        <!-- Result card (hidden until scan) -->
        <div id="resultCard" class="hidden" style="display: flex; flex-direction: column; gap: 16px;">
          <!-- Member info row -->
          <div style="display: flex; align-items: flex-start; gap: 14px; flex-wrap: wrap;">
            <img
              id="memberPhoto"
              src=""
              alt="Member"
              style="width: 80px; height: 80px; border-radius: 2px; object-fit: cover; border: 1px solid var(--border); flex-shrink: 0;"
            >
            <div style="min-width: 0; flex: 1;">
              <p id="memberName"     style="font-family: 'Bebas Neue', sans-serif; font-size: 22px; letter-spacing: 0.08em; color: var(--white); margin: 0 0 2px;"></p>
              <p id="memberCode"     style="font-size: 12px; color: var(--muted); margin: 0 0 4px;"></p>
              <p style="font-size: 12px; color: var(--dim); margin: 0;">
                Membership end: <span id="membershipEnd" style="font-weight: 600; color: var(--light);"></span>
              </p>
            </div>
          </div>
          <!-- Status badges (scan.js sets className completely) -->
          <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
            <span id="membershipStatus"></span>
            <span id="scanStatus"></span>
          </div>
          <!-- Scan message (scan.js sets className + content) -->
          <div id="scanMessage" style="
            padding: 12px 16px;
            background: var(--raised);
            border: 1px solid var(--border);
            border-radius: 2px;
            font-size: 13px; font-weight: 600; color: var(--light);
          "></div>
        </div>
      </div>
    </div>
  </div>
</div>
<script nonce="<?= e(csp_nonce()) ?>">
  window.GYM_SCAN_CONFIG = {
    csrfToken:           <?= json_encode($csrfToken, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
    checkinEndpoint:     <?= json_encode(url('/api/checkin'), JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
    appBasePath:         <?= json_encode(url(''), JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
    photoCaptureEnabled: <?= $photoCaptureEnabled ? 'true' : 'false' ?>,
  };
</script>
<!-- ZXing browser+library bundle — served locally -->
<script nonce="<?= e(csp_nonce()) ?>" src="<?= e(asset('js/zxing.min.js')) ?>"></script>
<script nonce="<?= e(csp_nonce()) ?>" src="<?= e(asset('js/scan.js')) ?>"></script>
<?php require __DIR__ . '/../partials/foot.php'; ?>
