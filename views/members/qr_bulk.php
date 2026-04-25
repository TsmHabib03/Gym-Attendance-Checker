<?php

declare(strict_types=1);

$title = 'Print All QR Cards';
$dashboardShell = true;

$logoUrl = url('/assets/img/repcore-removebg-preview.png');

// Pre-process each member's QR token and display values once
$processedMembers = [];
foreach ($members as $m) {
    $qrPayloadRaw = trim((string) ($m['qr_payload'] ?? ''));
    if ($qrPayloadRaw === '') {
        $fallback = [
            'v'           => 1,
            'type'        => 'gym_member',
            'qr_token'    => (string) ($m['qr_token'] ?? ''),
            'member_code' => (string) ($m['member_code'] ?? ''),
        ];
        $qrPayloadRaw = json_encode($fallback, JSON_UNESCAPED_SLASHES) ?: (string) ($m['qr_token'] ?? '');
    }

    $qrToken = '';
    $parsed  = json_decode($qrPayloadRaw, true);
    if (is_array($parsed) && isset($parsed['qr_token']) && is_string($parsed['qr_token'])) {
        $qrToken = trim($parsed['qr_token']);
    }
    if ($qrToken === '' && isset($m['qr_token']) && is_string($m['qr_token'])) {
        $qrToken = trim($m['qr_token']);
    }
    if ($qrToken === '') {
        $qrToken = $qrPayloadRaw;
    }

    $endDate = (string) ($m['membership_end_date'] ?? '');
    $active  = $endDate !== '' && (new DateTimeImmutable($endDate)) >= new DateTimeImmutable('today');

    $processedMembers[] = [
        'full_name'   => (string) ($m['full_name']   ?? ''),
        'member_code' => (string) ($m['member_code'] ?? ''),
        'email'       => (string) ($m['email']       ?? 'N/A'),
        'gender'      => ucwords(str_replace('_', ' ', (string) ($m['gender'] ?? 'N/A'))),
        'expiry'      => $endDate ?: 'N/A',
        'active'      => $active,
        'qr_token'    => $qrToken,
    ];
}

require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/nav.php';
?>

<!-- ── Screen header & controls (hidden on print) ── -->
<div class="page-enter no-print" style="max-width: 1280px; margin: 0 auto; padding: 32px 16px 24px;">
  <div style="margin-bottom: 24px; display: flex; align-items: flex-end; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
    <div>
      <p style="font-size: 11px; letter-spacing: 0.14em; color: var(--muted); text-transform: uppercase; margin: 0 0 6px;">Bulk Export</p>
      <h1 style="font-family: 'Bebas Neue', sans-serif; font-size: clamp(32px, 5vw, 48px); letter-spacing: 0.10em; color: var(--white); margin: 0; line-height: 1;">Print All QR Cards</h1>
    </div>
    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
      <a href="<?= e(url('/members')) ?>" class="btn-ghost" style="height: 38px; font-size: 11px;">← Members</a>
      <button type="button" id="printAllBtn" class="btn-primary" style="height: 38px; font-size: 11px;">🖨 Print All Cards</button>
    </div>
  </div>

  <div class="card no-print" style="padding: 14px 20px; margin-bottom: 8px; display: flex; align-items: center; gap: 16px; flex-wrap: wrap;">
    <div>
      <p style="font-size: 13px; color: var(--muted); margin: 0; line-height: 1.6;">
        Each card is <strong style="color: var(--light);">3.5 × 2 inches</strong> — dark theme, QR code on the left, member details on the right.<br>
        <strong style="color: var(--light);"><?= e((string) count($processedMembers)) ?></strong> member <?= count($processedMembers) === 1 ? 'card' : 'cards' ?> ready to print in a single job.
      </p>
    </div>
    <button type="button" id="printAllBtn2" class="btn-primary" style="margin-left: auto; height: 36px; font-size: 12px; white-space: nowrap;">🖨 Print All</button>
  </div>
</div>

<!-- ── Print area ── -->
<div class="print-area">
  <div class="cards-grid">

    <?php foreach ($processedMembers as $m): ?>
    <div class="bcard" data-qr="<?= e($m['qr_token']) ?>">

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
          <div class="bcard-qr"></div>
        </div>
        <div class="bcard-details">
          <p class="bcard-name"><?= e($m['full_name']) ?></p>
          <div class="bcard-divider"></div>
          <div class="bcard-info-row">
            <span class="bcard-info-label">Code</span>
            <span class="bcard-info-val"><?= e($m['member_code']) ?></span>
          </div>
          <div class="bcard-info-row">
            <span class="bcard-info-label">Gender</span>
            <span class="bcard-info-val"><?= e($m['gender']) ?></span>
          </div>
          <div class="bcard-info-row">
            <span class="bcard-info-label">Email</span>
            <span class="bcard-info-val bcard-email"><?= e($m['email']) ?></span>
          </div>
        </div>
      </div>

    </div><!-- /.bcard -->
    <?php endforeach; ?>

  </div><!-- /.cards-grid -->
</div><!-- /.print-area -->

<script nonce="<?= e(csp_nonce()) ?>" src="<?= e(asset('lib/qrcode.min.js')) ?>"></script>
<script nonce="<?= e(csp_nonce()) ?>">
(function () {
  if (!window.QRCode) return;
  document.querySelectorAll('.bcard').forEach(function (card) {
    var qrText = (card.dataset.qr || '').trim();
    var qrWrap = card.querySelector('.bcard-qr');
    if (!qrWrap || !qrText) return;
    try {
      new window.QRCode(qrWrap, {
        text: qrText,
        width: 116,
        height: 116,
        colorDark:  '#ffffff',
        colorLight: '#111111',
        correctLevel: window.QRCode.CorrectLevel ? window.QRCode.CorrectLevel.M : 0,
      });
    } catch (_e) {}
  });
})();
</script>
<script nonce="<?= e(csp_nonce()) ?>">
(function () {
  ['printAllBtn', 'printAllBtn2'].forEach(function (id) {
    var btn = document.getElementById(id);
    if (btn) btn.addEventListener('click', function () { window.print(); });
  });
})();
</script>

<style>
/* =========================================================
   SCREEN: preview grid
   ========================================================= */
.print-area {
  background: #0a0a0a;
  padding: 16px 16px 64px;
  min-height: 60vh;
}
.cards-grid {
  max-width: 960px;
  margin: 0 auto;
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
  gap: 16px;
}

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
  page-break-inside: avoid;
  break-inside: avoid;
  margin: 0 auto;
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
  color: #00d4ff;
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
.bcard-email   { font-size: 6.5pt; }
.bcard-active  { color: #22c55e !important; }
.bcard-expired { color: #f87171 !important; }

/* =========================================================
   PRINT — 2 cards per row, dark backgrounds preserved
   ========================================================= */
@media print {
  @page {
    size: letter landscape;   /* wider page = 2 cards per row easily */
    margin: 0.35in;
  }
  body {
    background: #0a0a0a !important;
    color: #ffffff !important;
  }
  .no-print, header, nav {
    display: none !important;
  }
  .print-area {
    background: #0a0a0a !important;
    padding: 0 !important;
    min-height: auto !important;
  }
  .cards-grid {
    max-width: none !important;
    display: grid !important;
    grid-template-columns: 3.5in 3.5in !important;
    gap: 0.18in !important;
    justify-content: center !important;
  }
  .bcard {
    margin: 0 !important;
    background: #111111 !important;
    border: 1px solid #2a2a2a !important;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }
  .bcard-logo {
    background: transparent !important;
    filter: brightness(0) invert(1) !important;
  }
  .bcard-qr img,
  .bcard-qr canvas {
    border-color: #333333 !important;
  }
}

/* =========================================================
   RESPONSIVE (screen narrow)
   ========================================================= */
@media screen and (max-width: 780px) {
  .cards-grid {
    grid-template-columns: 1fr;
  }
  .bcard {
    margin: 0 auto;
  }
}
</style>

<?php require __DIR__ . '/../partials/foot.php'; ?>
        