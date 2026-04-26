/**
 * Gym QR Scanner — ZXing RAF canvas-decode loop
 *
 * Decode pipeline (confirmed against this ZXing build's export list):
 *   canvas → HTMLCanvasElementLuminanceSource
 *          → HybridBinarizer
 *          → BinaryBitmap
 *          → MultiFormatReader.decode()   ← synchronous, throws NotFoundException on miss
 *
 * Speed knobs:
 *   POSSIBLE_FORMATS → [QR_CODE]  — skips all non-QR decoders
 *   TRY_HARDER       → false      — no exhaustive fallback pass
 *   Decode canvas    → 480 px cap — 2× faster than 720p, enough for any QR
 *   RAF loop         → every frame (~16 ms)
 *   Normal + inverted luminance tried each frame — catches light-on-dark QRs
 *
 * CSP-safe: no ZXing camera management, no internal beep, no inline handlers.
 * We own getUserMedia() and requestAnimationFrame() entirely.
 */
(() => {
  'use strict';

  // ── DOM refs ──────────────────────────────────────────────────────────────
  const config           = window.GYM_SCAN_CONFIG || {};
  const video            = document.getElementById('qrVideo');
  const toggleButton     = document.getElementById('toggleScan');
  const statusText       = document.getElementById('scannerStatus');
  const captureToggle    = document.getElementById('capturePhoto');
  const resultEmpty      = document.getElementById('resultEmpty');
  const resultCard       = document.getElementById('resultCard');
  const memberPhoto      = document.getElementById('memberPhoto');
  const memberName       = document.getElementById('memberName');
  const memberCode       = document.getElementById('memberCode');
  const membershipEnd    = document.getElementById('membershipEnd');
  const membershipStatus = document.getElementById('membershipStatus');
  const scanStatus       = document.getElementById('scanStatus');
  const scanMessage      = document.getElementById('scanMessage');

  if (!video || !toggleButton || !statusText || !resultEmpty || !resultCard ||
      !memberPhoto || !memberName || !memberCode || !membershipEnd ||
      !membershipStatus || !scanStatus || !scanMessage) {
    return;
  }

  // ── ZXing availability guard ──────────────────────────────────────────────
  if (
    typeof ZXing === 'undefined' ||
    typeof ZXing.MultiFormatReader === 'undefined' ||
    typeof ZXing.HTMLCanvasElementLuminanceSource === 'undefined' ||
    typeof ZXing.HybridBinarizer === 'undefined' ||
    typeof ZXing.BinaryBitmap === 'undefined' ||
    typeof ZXing.NotFoundException === 'undefined'
  ) {
    statusText.textContent = 'Scanner library failed to load. Please reload.';
    return;
  }

  // ── ZXing decoder setup ───────────────────────────────────────────────────
  // MultiFormatReader is the raw synchronous decoder — no camera, no audio, no DOM.
  // Hints lock it to QR-only with TRY_HARDER off for maximum decode speed.
  const hints = new Map();
  hints.set(ZXing.DecodeHintType.POSSIBLE_FORMATS, [ZXing.BarcodeFormat.QR_CODE]);
  hints.set(ZXing.DecodeHintType.TRY_HARDER, false);

  const zxingReader = new ZXing.MultiFormatReader();
  zxingReader.setHints(hints);

  // ── Decode canvas ─────────────────────────────────────────────────────────
  // One canvas element reused every frame — avoids per-frame GC pressure.
  const canvas = document.createElement('canvas');
  const ctx    = canvas.getContext('2d', { willReadFrequently: true });

  // 480 px: enough detail for any QR code printed on a gym membership card.
  // Decoding at full 720p/1080p doubles CPU time with no detection benefit.
  const MAX_DECODE_PX = 480;

  // ── State ─────────────────────────────────────────────────────────────────
  let stream        = null;
  let rafHandle     = null;
  let isScanning    = false;
  let processing    = false;
  let scanCooldown  = false;
  let lastToken     = '';
  let lastTokenTime = 0;
  const TOKEN_REPEAT_MS  = 1500; // same token silenced for 1.5 s client-side
  const TOKEN_PATTERN    = /^[a-f0-9]{48}$|^[a-f0-9]{64}$/i;

  // ── UI helpers ────────────────────────────────────────────────────────────
  const setStatus = (msg, tone = 'neutral') => {
    statusText.textContent = msg;
    const palette = {
      neutral: 'var(--dim)',
      success: '#4ade80',
      error:   '#f87171',
      warning: '#fbbf24',
      info:    '#67e8f9',
    };
    statusText.style.color      = palette[tone] ?? palette.neutral;
    statusText.style.fontWeight = '600';
    statusText.style.fontSize   = '13px';
  };

  const setToggleState = (active) => {
    toggleButton.textContent = active ? 'Stop Scan' : 'Start Scan';
    toggleButton.className   = active ? 'btn-danger' : 'btn-primary';
    toggleButton.setAttribute('aria-pressed', String(active));
  };

  const resolvePhotoPath = (path) => {
    if (!path || typeof path !== 'string') return 'https://placehold.co/96x96?text=GYM';
    if (/^(https?:)?\/\//i.test(path) || path.startsWith('data:') || path.startsWith('blob:')) return path;
    const base = String(config.appBasePath || '').replace(/\/+$/, '');
    const p    = path.startsWith('/') ? path : '/' + path;
    return base ? base + p : p;
  };

  const vibrate = (pattern) => {
    try { if (navigator.vibrate) navigator.vibrate(pattern); } catch (_) {}
  };

  // ── Token extraction ──────────────────────────────────────────────────────
  const extractToken = (raw) => {
    const text = String(raw || '').trim();
    if (!text) return null;
    if (TOKEN_PATTERN.test(text)) return text.toLowerCase();
    if (text.startsWith('{') || text.startsWith('[')) {
      try {
        const p = JSON.parse(text);
        for (const c of [p?.qr_token, p?.token, p?.member?.qr_token, p?.member?.token]) {
          const v = String(c ?? '').trim();
          if (TOKEN_PATTERN.test(v)) return v.toLowerCase();
        }
      } catch (_) {}
    }
    return null;
  };

  // ── Result display ────────────────────────────────────────────────────────
  const setResult = (data) => {
    // resultEmpty / resultCard both start with class="hidden" (display:none !important).
    // classList.remove() must come before any style assignment or the !important wins.
    resultEmpty.classList.add('hidden');
    resultEmpty.style.display = 'none';
    resultCard.classList.remove('hidden');
    resultCard.style.display  = 'flex';

    memberPhoto.src              = resolvePhotoPath(data.member?.photo_path ?? '');
    memberName.textContent       = data.member?.full_name ?? '';
    memberCode.textContent       = data.member?.member_code ?? '';
    membershipEnd.textContent    = data.member?.membership_end_date ?? '';

    const active                 = data.membership_status === 'Active';
    membershipStatus.className   = active ? 'stat-badge stat-badge-ok' : 'stat-badge stat-badge-danger';
    membershipStatus.textContent = data.membership_status ?? '';

    let scanStyle = 'background:rgba(170,170,170,0.08);color:var(--dim);border:1px solid var(--border)';
    let msgStyle  = 'background:var(--raised);border:1px solid var(--border);color:var(--light)';

    if (data.scan_status === 'accepted') {
      scanStyle = 'background:rgba(74,222,128,0.10);color:#4ade80;border:1px solid rgba(74,222,128,0.25)';
      msgStyle  = 'background:rgba(74,222,128,0.06);border:1px solid rgba(74,222,128,0.25);color:#4ade80';
    } else if (data.scan_status === 'expired_denied') {
      scanStyle = 'background:rgba(248,113,113,0.10);color:#f87171;border:1px solid rgba(248,113,113,0.25)';
      msgStyle  = 'background:rgba(248,113,113,0.06);border:1px solid rgba(248,113,113,0.25);color:#f87171';
    } else if (data.scan_status === 'duplicate_denied') {
      scanStyle = 'background:rgba(251,191,36,0.10);color:#fbbf24;border:1px solid rgba(251,191,36,0.25)';
      msgStyle  = 'background:rgba(251,191,36,0.06);border:1px solid rgba(251,191,36,0.25);color:#fbbf24';
    }

    scanStatus.className     = 'stat-badge';
    scanStatus.style.cssText = scanStyle;
    scanStatus.textContent   = (data.scan_status ?? '').replace(/_/g, ' ').toUpperCase();

    scanMessage.style.cssText = `padding:12px 16px;border-radius:2px;font-size:13px;font-weight:600;${msgStyle}`;
    scanMessage.textContent   = data.message ?? '';
  };

  // ── Photo capture ─────────────────────────────────────────────────────────
  const capturePhotoFrame = () => {
    if (!config.photoCaptureEnabled || !captureToggle?.checked) return null;
    const c = document.createElement('canvas');
    c.width  = video.videoWidth  || 640;
    c.height = video.videoHeight || 480;
    c.getContext('2d')?.drawImage(video, 0, 0, c.width, c.height);
    return c.toDataURL('image/jpeg', 0.75);
  };

  // ── API ───────────────────────────────────────────────────────────────────
  const sendCheckin = async (token) => {
    const r = await fetch(config.checkinEndpoint || '/api/checkin', {
      method:  'POST',
      headers: {
        'Content-Type':     'application/json',
        'X-CSRF-TOKEN':     config.csrfToken ?? '',
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: JSON.stringify({ qr_token: token, photo_data: capturePhotoFrame() }),
    });
    const json = await r.json();
    if (!json.ok) throw new Error(json.message || 'Check-in failed.');
    return json.data;
  };

  const handleScan = async (raw) => {
    const token = extractToken(raw);
    if (!token) {
      if (!processing && !scanCooldown) setStatus('Unrecognised QR — expected member token.', 'warning');
      return;
    }

    const now = Date.now();
    if (token === lastToken && now - lastTokenTime < TOKEN_REPEAT_MS) return;
    if (processing || scanCooldown) return;

    lastToken     = token;
    lastTokenTime = now;
    processing    = true;
    setStatus('Processing check-in…', 'info');

    try {
      const data = await sendCheckin(token);
      setResult(data);
      if (data.scan_status === 'accepted') {
        setStatus('✓ Check-in accepted.', 'success');
        vibrate([60, 30, 60]);
      } else if (data.scan_status === 'expired_denied') {
        setStatus('✗ Expired membership — denied.', 'error');
        vibrate([200]);
      } else {
        setStatus('⚠ Duplicate scan denied.', 'warning');
        vibrate([100, 50, 100]);
      }
      scanCooldown = true;
      window.setTimeout(() => { scanCooldown = false; }, 600);
    } catch (err) {
      setStatus(err.message || 'Unable to process check-in.', 'error');
      vibrate([200]);
      scanCooldown = true;
      window.setTimeout(() => { scanCooldown = false; }, 400);
    } finally {
      processing = false;
    }
  };

  // ── Core decode: attempt one frame ────────────────────────────────────────
  // Returns the decoded text string, or null if no QR found.
  // Tries normal luminance first, then inverted — covers white-on-dark QRs.
  const tryDecode = () => {
    // Pass 1: normal (dark QR on light background — most common)
    try {
      const lum    = new ZXing.HTMLCanvasElementLuminanceSource(canvas);
      const bitmap = new ZXing.BinaryBitmap(new ZXing.HybridBinarizer(lum));
      return zxingReader.decode(bitmap).getText();
    } catch (_) {}

    // Pass 2: inverted (light QR on dark background)
    try {
      const lum    = new ZXing.HTMLCanvasElementLuminanceSource(canvas);
      const inv    = new ZXing.InvertedLuminanceSource(lum);
      const bitmap = new ZXing.BinaryBitmap(new ZXing.HybridBinarizer(inv));
      return zxingReader.decode(bitmap).getText();
    } catch (_) {}

    return null;
  };

  // ── RAF decode loop ───────────────────────────────────────────────────────
  const decodeFrame = () => {
    if (!isScanning) return;

    if (video.readyState === video.HAVE_ENOUGH_DATA && video.videoWidth > 0) {
      // Scale video down to MAX_DECODE_PX (preserves aspect ratio)
      const vw    = video.videoWidth;
      const vh    = video.videoHeight;
      const scale = Math.min(1, MAX_DECODE_PX / Math.max(vw, vh));
      const dw    = Math.max(1, Math.round(vw * scale));
      const dh    = Math.max(1, Math.round(vh * scale));

      if (canvas.width !== dw || canvas.height !== dh) {
        canvas.width  = dw;
        canvas.height = dh;
      }

      ctx.drawImage(video, 0, 0, dw, dh);

      const text = tryDecode();
      if (text) handleScan(text);
    }

    rafHandle = requestAnimationFrame(decodeFrame);
  };

  // ── Camera start / stop ───────────────────────────────────────────────────
  const startScan = async () => {
    if (isScanning) return;
    setToggleState(true);
    setStatus('Starting camera…', 'info');

    try {
      const isMobile = window.matchMedia('(max-width: 767px)').matches;

      stream = await navigator.mediaDevices.getUserMedia({
        audio: false,
        video: {
          facingMode:  { ideal: 'environment' },
          width:       { ideal: isMobile ? 720 : 1280 },
          height:      { ideal: 720 },
          aspectRatio: { ideal: 1 },
          frameRate:   { ideal: 30, max: 30 },
        },
      });

      video.srcObject = stream;
      video.setAttribute('playsinline', 'playsinline');
      await video.play();

      // Wait for the first decodable frame before starting the loop
      await new Promise((resolve) => {
        if (video.readyState >= video.HAVE_ENOUGH_DATA) { resolve(); return; }
        video.addEventListener('canplay', resolve, { once: true });
      });

      isScanning = true;
      setStatus('Scanner active — point camera at member QR.', 'success');
      rafHandle = requestAnimationFrame(decodeFrame);

    } catch (err) {
      isScanning = false;
      setToggleState(false);
      killStream();
      const msg = err?.message || String(err);
      if (/permission|denied|notallowed/i.test(msg))   setStatus('Camera permission denied — please allow camera access.', 'error');
      else if (/notfound|notreadable|overconstrained/i.test(msg)) setStatus('No camera found or camera is busy.', 'error');
      else setStatus('Cannot start camera: ' + msg, 'error');
    }
  };

  const killStream = () => {
    if (stream) { stream.getTracks().forEach((t) => t.stop()); stream = null; }
    video.srcObject = null;
  };

  const stopScan = () => {
    isScanning = false;
    if (rafHandle !== null) { cancelAnimationFrame(rafHandle); rafHandle = null; }
    killStream();
    setToggleState(false);
    setStatus('Scanner stopped.', 'neutral');
  };

  // ── Init ──────────────────────────────────────────────────────────────────
  setToggleState(false);
  setStatus('Scanner idle', 'neutral');

  toggleButton.addEventListener('click', () => {
    if (isScanning) stopScan(); else startScan();
  });

  window.addEventListener('beforeunload', stopScan);
  document.addEventListener('visibilitychange', () => {
    if (document.hidden && isScanning) stopScan();
  });
})();
