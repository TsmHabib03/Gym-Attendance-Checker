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
    if (text === '') {
      return null;
    }

    if (TOKEN_PATTERN.test(text)) {
      return text.toLowerCase();
    }

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
        if (TOKEN_PATTERN.test(normalized)) {
          return normalized.toLowerCase();
        }
      }
    } catch (_error) {
      return null;
    }

    return null;
  };

  const setToggleState = (active) => {
    toggleButton.textContent = active ? 'Stop Scan' : 'Start Scan';
    toggleButton.className = active
      ? 'h-11 w-full rounded-xl bg-rose-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-rose-400 sm:w-auto'
      : 'h-11 w-full rounded-xl bg-white px-4 py-2 text-sm font-semibold text-slate-900 transition hover:bg-slate-100 sm:w-auto';
    toggleButton.setAttribute('aria-pressed', active ? 'true' : 'false');
  };

  const resolvePhotoPath = (path) => {
    if (!path || typeof path !== 'string') {
      return 'https://placehold.co/96x96?text=GYM';
    }

    if (/^(https?:)?\/\//i.test(path) || path.startsWith('data:') || path.startsWith('blob:')) {
      return path;
    }

    const appBasePath = String(config.appBasePath || '').replace(/\/+$/, '');
    const normalizedPath = path.startsWith('/') ? path : '/' + path;
    return appBasePath ? appBasePath + normalizedPath : normalizedPath;
  };

  const setStatus = (message, tone = 'text-slate-300') => {
    statusText.textContent = message;
    statusText.className = `mt-3 text-sm font-semibold ${tone}`;
  };

  const setResult = (data) => {
    resultEmpty.classList.add('hidden');
    resultCard.classList.remove('hidden');

    memberPhoto.src = resolvePhotoPath(data.member.photo_path || '');
    memberName.textContent = data.member.full_name;
    memberCode.textContent = data.member.member_code;
    membershipEnd.textContent = data.member.membership_end_date;

    const activeMembership = data.membership_status === 'Active';
    membershipStatus.className = activeMembership
      ? 'inline-flex rounded-full bg-emerald-400/20 px-3 py-1 text-sm font-semibold text-emerald-300 ring-1 ring-emerald-400/40'
      : 'inline-flex rounded-full bg-rose-400/20 px-3 py-1 text-sm font-semibold text-rose-300 ring-1 ring-rose-400/40';
    membershipStatus.textContent = data.membership_status;

    let scanClass = 'inline-flex rounded-full bg-slate-400/20 px-3 py-1 text-sm font-semibold text-slate-200 ring-1 ring-slate-400/40';
    let messageClass = 'rounded-xl border border-slate-700 bg-slate-900/70 px-4 py-3 text-sm font-semibold text-slate-200';

    if (data.scan_status === 'accepted') {
      scanClass = 'inline-flex rounded-full bg-emerald-400/20 px-3 py-1 text-sm font-semibold text-emerald-300 ring-1 ring-emerald-400/40';
      messageClass = 'rounded-xl border border-emerald-400/40 bg-emerald-400/10 px-4 py-3 text-sm font-semibold text-emerald-300';
    } else if (data.scan_status === 'expired_denied') {
      scanClass = 'inline-flex rounded-full bg-rose-400/20 px-3 py-1 text-sm font-semibold text-rose-300 ring-1 ring-rose-400/40';
      messageClass = 'rounded-xl border border-rose-400/40 bg-rose-400/10 px-4 py-3 text-sm font-semibold text-rose-300';
    } else if (data.scan_status === 'duplicate_denied') {
      scanClass = 'inline-flex rounded-full bg-amber-300/20 px-3 py-1 text-sm font-semibold text-amber-200 ring-1 ring-amber-300/40';
      messageClass = 'rounded-xl border border-amber-300/40 bg-amber-300/10 px-4 py-3 text-sm font-semibold text-amber-200';
    }

    scanStatus.className = scanClass;
    scanStatus.textContent = data.scan_status;
    scanMessage.className = messageClass;
    scanMessage.textContent = data.message;
  };

  const capturePhotoFrame = () => {
    if (!config.photoCaptureEnabled || !captureToggle || !captureToggle.checked) {
      return null;
    }

    const canvas = document.createElement('canvas');
    canvas.width = video.videoWidth || 640;
    canvas.height = video.videoHeight || 480;

    const context = canvas.getContext('2d');
    if (!context) {
      return null;
    }

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
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify({
        qr_token: token,
        photo_data: capturePhotoFrame()
      })
    });

    const json = await response.json();
    if (!json.ok) {
      throw new Error(json.message || 'Check-in failed.');
    }

    return json.data;
  };

  const handleScan = async (token) => {
    if (processing || scanCooldown) {
      return;
    }

    processing = true;
    setStatus('Processing check-in...', 'text-cyan-300');

    try {
      const data = await sendCheckin(token);
      setResult(data);

      if (data.scan_status === 'accepted') {
        setStatus('Check-in accepted.', 'text-emerald-300');
      } else if (data.scan_status === 'expired_denied') {
        setStatus('Expired membership. Check-in denied.', 'text-rose-300');
      } else {
        setStatus('Duplicate scan denied.', 'text-amber-300');
      }

      scanCooldown = true;
      window.setTimeout(() => {
        scanCooldown = false;
      }, 2500);
    } catch (error) {
      setStatus(error.message || 'Unable to process check-in.', 'text-rose-300');
    } finally {
      processing = false;
    }
  };

  const startScan = async () => {
    if (isScanning) {
      return;
    }

    isScanning = true;
    setToggleState(true);
    setStatus('Starting camera...', 'text-cyan-300');

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
            setStatus('Unsupported QR payload. Expected member QR token.', 'text-amber-300');
          }
        }

        if (error && !(error instanceof ZXing.NotFoundException)) {
          setStatus('Scanner error: ' + error.message, 'text-rose-300');
        }
      };

      const isMobileViewport = window.matchMedia('(max-width: 767px)').matches;
      const constraints = {
        audio: false,
        video: {
          facingMode: { ideal: 'environment' },
          width: { ideal: isMobileViewport ? 640 : 1280 },
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

      setStatus('Scanner active. Point camera at member QR.', 'text-emerald-300');
    } catch (error) {
      setStatus('Unable to access camera. Check browser permissions.', 'text-rose-300');
      isScanning = false;
      setToggleState(false);
    }
  };

  const stopScan = () => {
    codeReader.reset();
    isScanning = false;
    setToggleState(false);
    setStatus('Scanner stopped.', 'text-slate-300');
  };

  setToggleState(false);
  setStatus('Scanner idle', 'text-slate-300');

  toggleButton.addEventListener('click', () => {
    if (isScanning) {
      stopScan();
      return;
    }

    startScan();
  });
})();
