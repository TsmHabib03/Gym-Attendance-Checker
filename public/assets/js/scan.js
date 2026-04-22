(() => {
  const config = window.GYM_SCAN_CONFIG || {};
  const video = document.getElementById('qrVideo');
  const toggleButton = document.getElementById('toggleScan');
  const statusText = document.getElementById('scannerStatus');
  const captureToggle = document.getElementById('capturePhoto');

  const resultEmpty = document.getElementById('resultEmpty');
  const resultCard = document.getElementById('resultCard');
  const memberPhoto = document.getElementById('memberPhoto');
  const memberName = document.getElementById('memberName');
  const memberCode = document.getElementById('memberCode');
  const membershipEnd = document.getElementById('membershipEnd');
  const membershipStatus = document.getElementById('membershipStatus');
  const scanStatus = document.getElementById('scanStatus');
  const scanMessage = document.getElementById('scanMessage');

  if (!video || !toggleButton || !statusText || !resultEmpty || !resultCard || !memberPhoto || !memberName || !memberCode || !membershipEnd || !membershipStatus || !scanStatus || !scanMessage) {
    return;
  }

  const codeReader = new ZXing.BrowserMultiFormatReader();
  let isScanning = false;
  let processing = false;
  let scanCooldown = false;
  const TOKEN_PATTERN = /^[a-f0-9]{48}$|^[a-f0-9]{64}$/i;

  const extractTokenFromScan = (rawText) => {
    const text = String(rawText || '').trim();
    if (text === '') return null;

    if (TOKEN_PATTERN.test(text)) return text.toLowerCase();

    try {
      const payload = JSON.parse(text);
      const tokenCandidates = [
        payload?.qr_token,
        payload?.token,
        payload?.member?.qr_token,
        payload?.member?.token,
      ];
      for (const candidate of tokenCandidates) {
        const normalized = String(candidate || '').trim();
        if (TOKEN_PATTERN.test(normalized)) return normalized.toLowerCase();
      }
    } catch (_error) {
      return null;
    }
    return null;
  };

  // Use system CSS classes (btn-primary / btn-danger) for consistent alignment
  const setToggleState = (active) => {
    toggleButton.textContent = active ? 'Stop Scan' : 'Start Scan';
    toggleButton.className = active ? 'btn-danger' : 'btn-primary';
    toggleButton.setAttribute('aria-pressed', active ? 'true' : 'false');
  };

  const resolvePhotoPath = (path) => {
    if (!path || typeof path !== 'string') {
      return 'https://placehold.co/96x96?text=RCF';
    }
    if (/^(https?:)?\/\//i.test(path) || path.startsWith('data:') || path.startsWith('blob:')) {
      return path;
    }
    const appBasePath = String(config.appBasePath || '').replace(/\/+$/, '');
    const normalizedPath = path.startsWith('/') ? path : '/' + path;
    return appBasePath ? appBasePath + normalizedPath : normalizedPath;
  };

  // Use inline styles matching the system's CSS variables
  const setStatus = (message, tone = 'neutral') => {
    statusText.textContent = message;
    const colors = {
      neutral:  'var(--dim)',
      success:  '#4ade80',
      error:    '#f87171',
      warning:  '#fbbf24',
      info:     '#67e8f9',
    };
    statusText.style.color = colors[tone] || colors.neutral;
    statusText.style.fontWeight = '600';
    statusText.style.fontSize = '13px';
  };

  const setResult = (data) => {
    resultEmpty.style.display = 'none';
    resultCard.style.display = 'flex';

    memberPhoto.src = resolvePhotoPath(data.member.photo_path || '');
    memberName.textContent = data.member.full_name;
    memberCode.textContent = data.member.member_code;
    membershipEnd.textContent = data.member.membership_end_date;

    // Use system stat-badge classes
    const activeMembership = data.membership_status === 'Active';
    membershipStatus.className = activeMembership ? 'stat-badge stat-badge-ok' : 'stat-badge stat-badge-danger';
    membershipStatus.textContent = data.membership_status;

    let scanBadgeClass = 'stat-badge';
    let scanBadgeStyle = 'background:rgba(170,170,170,0.08);color:var(--dim);border:1px solid var(--border)';
    let msgBg = 'background:var(--raised);border:1px solid var(--border);color:var(--light)';

    if (data.scan_status === 'accepted') {
      scanBadgeStyle = 'background:rgba(74,222,128,0.10);color:#4ade80;border:1px solid rgba(74,222,128,0.25)';
      msgBg = 'background:rgba(74,222,128,0.06);border:1px solid rgba(74,222,128,0.25);color:#4ade80';
    } else if (data.scan_status === 'expired_denied') {
      scanBadgeStyle = 'background:rgba(248,113,113,0.10);color:#f87171;border:1px solid rgba(248,113,113,0.25)';
      msgBg = 'background:rgba(248,113,113,0.06);border:1px solid rgba(248,113,113,0.25);color:#f87171';
    } else if (data.scan_status === 'duplicate_denied') {
      scanBadgeStyle = 'background:rgba(251,191,36,0.10);color:#fbbf24;border:1px solid rgba(251,191,36,0.25)';
      msgBg = 'background:rgba(251,191,36,0.06);border:1px solid rgba(251,191,36,0.25);color:#fbbf24';
    }

    scanStatus.className = scanBadgeClass;
    scanStatus.style.cssText = scanBadgeStyle;
    scanStatus.textContent = data.scan_status.replace(/_/g, ' ').toUpperCase();

    scanMessage.style.cssText = `padding:12px 16px;border-radius:2px;font-size:13px;font-weight:600;${msgBg}`;
    scanMessage.textContent = data.message;
  };

  const capturePhotoFrame = () => {
    if (!config.photoCaptureEnabled || !captureToggle || !captureToggle.checked) return null;

    const canvas = document.createElement('canvas');
    canvas.width = video.videoWidth || 640;
    canvas.height = video.videoHeight || 480;
    const context = canvas.getContext('2d');
    if (!context) return null;

    context.drawImage(video, 0, 0, canvas.width, canvas.height);
    return canvas.toDataURL('image/jpeg', 0.8);
  };

  const sendCheckin = async (token) => {
    const endpoint = config.checkinEndpoint || '/api/checkin';
    const response = await fetch(endpoint, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': config.csrfToken,
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: JSON.stringify({ qr_token: token, photo_data: capturePhotoFrame() }),
    });

    const json = await response.json();
    if (!json.ok) throw new Error(json.message || 'Check-in failed.');
    return json.data;
  };

  const handleScan = async (token) => {
    if (processing || scanCooldown) return;

    processing = true;
    setStatus('Processing check-in...', 'info');

    try {
      const data = await sendCheckin(token);
      setResult(data);

      if (data.scan_status === 'accepted') {
        setStatus('Check-in accepted.', 'success');
      } else if (data.scan_status === 'expired_denied') {
        setStatus('Expired membership — check-in denied.', 'error');
      } else {
        setStatus('Duplicate scan denied.', 'warning');
      }

      scanCooldown = true;
      window.setTimeout(() => { scanCooldown = false; }, 2500);
    } catch (error) {
      setStatus(error.message || 'Unable to process check-in.', 'error');
    } finally {
      processing = false;
    }
  };

  const startScan = async () => {
    if (isScanning) return;

    isScanning = true;
    setToggleState(true);
    setStatus('Starting camera...', 'info');

    video.style.filter = 'none';
    video.style.opacity = '1';
    video.style.mixBlendMode = 'normal';
    video.setAttribute('autoplay', 'autoplay');
    video.setAttribute('playsinline', 'playsinline');
    video.setAttribute('muted', 'muted');

    try {
      const onDecode = (result, error) => {
        if (result) {
          const token = extractTokenFromScan(result.getText());
          if (token) {
            handleScan(token);
          } else if (!processing && !scanCooldown) {
            setStatus('Unsupported QR payload. Expected member QR token.', 'warning');
          }
        }
        if (error && !(error instanceof ZXing.NotFoundException)) {
          setStatus('Scanner error: ' + error.message, 'error');
        }
      };

      const isMobileViewport = window.matchMedia('(max-width: 767px)').matches;
      const constraints = {
        audio: false,
        video: {
          facingMode: { ideal: 'environment' },
          width:  { ideal: isMobileViewport ? 640 : 1280 },
          height: { ideal: isMobileViewport ? 640 : 720 },
          aspectRatio: { ideal: isMobileViewport ? 1 : 16 / 9 },
        },
      };

      if (typeof codeReader.decodeFromConstraints === 'function') {
        try {
          await codeReader.decodeFromConstraints(constraints, video, onDecode);
        } catch (_constraintsError) {
          await codeReader.decodeFromVideoDevice(null, video, onDecode);
        }
      } else {
        await codeReader.decodeFromVideoDevice(null, video, onDecode);
      }

      setStatus('Scanner active. Point camera at member QR.', 'success');
    } catch (error) {
      setStatus('Unable to access camera. Check browser permissions.', 'error');
      isScanning = false;
      setToggleState(false);
    }
  };

  const stopScan = () => {
    codeReader.reset();
    isScanning = false;
    setToggleState(false);
    setStatus('Scanner stopped.', 'neutral');
  };

  // Initial state
  setToggleState(false);
  setStatus('Scanner idle', 'neutral');

  toggleButton.addEventListener('click', () => {
    if (isScanning) { stopScan(); return; }
    startScan();
  });
})();
