<?php

declare(strict_types=1);

require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/nav.php';
?>
<div class="page-enter" style="max-width: 1280px; margin: 0 auto; padding: 32px 16px 64px;">
  <!-- Page header -->
  <div style="margin-bottom: 32px; display: flex; align-items: flex-end; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
    <div>
      <p style="font-size: 11px; letter-spacing: 0.14em; color: var(--muted); text-transform: uppercase; margin: 0 0 6px;">Member Onboarding</p>
      <h1 style="
        font-family: 'Bebas Neue', sans-serif;
        font-size: clamp(32px, 5vw, 48px);
        letter-spacing: 0.10em;
        color: var(--white);
        margin: 0; line-height: 1;
      ">Add Member</h1>
    </div>
    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
      <a href="<?= e(url('/members')) ?>" class="btn-ghost" style="height: 38px; font-size: 11px;">← Back</a>
    </div>
  </div>
  <!-- Flash -->
  <?php require __DIR__ . '/../partials/flash.php'; ?>
  <!-- Two-column layout -->
  <div style="display: grid; gap: 16px;" class="lg:grid-cols-[280px_1fr]">
    <!-- ── SIDEBAR ── -->
    <aside class="order-2 lg:order-1" style="display: flex; flex-direction: column; gap: 16px;">
      <!-- Info card -->
      <div class="card" style="padding: 20px;">
        <div class="section-rule" style="margin-bottom: 16px;">
          <span style="font-family: 'Bebas Neue', sans-serif; font-size: 16px; letter-spacing: 0.12em; color: var(--white);">Onboarding</span>
        </div>
        <p style="font-size: 13px; color: var(--muted); line-height: 1.7; margin: 0 0 16px;">
          Register member identity, membership validity, and profile photo in one guided flow.
        </p>
        <div style="display: flex; flex-direction: column; gap: 8px;">
          <a href="<?= e(url('/members')) ?>"           class="btn-ghost"   style="width: 100%; font-size: 12px;">Back to Members</a>
          <a href="<?= e(url('/attendance/scan')) ?>"   class="btn-primary" style="width: 100%; font-size: 12px;">Open Scanner</a>
        </div>
      </div>
      <!-- Checklist card -->
      <div class="card" style="padding: 20px;">
        <p style="font-size: 11px; font-weight: 600; letter-spacing: 0.12em; text-transform: uppercase; color: var(--muted); margin: 0 0 12px;">Quick Checklist</p>
        <div style="display: flex; flex-direction: column; gap: 8px;">
          <?php
          $checks = [
            'Enter full member name clearly.',
            'Set correct membership end date.',
            'Attach photo via upload or camera.',
          ];
          foreach ($checks as $check): ?>
            <div style="
              display: flex; align-items: flex-start; gap: 10px;
              background: var(--raised); border: 1px solid var(--border);
              border-radius: 2px; padding: 10px 12px;
            ">
              <span style="color: var(--white); font-size: 11px; flex-shrink: 0; margin-top: 1px;">✓</span>
              <span style="font-size: 12px; color: var(--dim);"><?= e($check) ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </aside>
    <!-- ── MAIN CONTENT ── -->
    <div class="order-1 lg:order-2" style="display: flex; flex-direction: column; gap: 16px;">
      <!-- Member Info Form -->
      <div class="card" style="padding: 20px 24px;">
        <div style="margin-bottom: 20px;">
          <h2 style="font-family: 'Bebas Neue', sans-serif; font-size: 20px; letter-spacing: 0.12em; color: var(--white); margin: 0 0 4px;">Member Information</h2>
          <p style="font-size: 13px; color: var(--muted); margin: 0;">Create member profile for QR attendance tracking.</p>
        </div>
        <form action="<?= e(url('/members/create')) ?>" method="post" enctype="multipart/form-data" style="display: flex; flex-direction: column; gap: 18px;">
          <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
          <div>
            <label for="create-full-name" class="label">Full Name</label>
            <input
              id="create-full-name"
              type="text"
              name="full_name"
              value="<?= e(old('full_name')) ?>"
              class="input"
              required
              placeholder="e.g. Juan dela Cruz"
            >
          </div>
          <div>
            <label for="create-email" class="label">Email (optional)</label>
            <input
              id="create-email"
              type="email"
              name="email"
              value="<?= e(old('email')) ?>"
              class="input"
              placeholder="member@example.com"
            >
          </div>
          <div>
            <label for="create-gender" class="label">Gender</label>
            <select id="create-gender" name="gender" class="input" required>
              <?php $selectedGender = (string) old('gender', 'prefer_not_say'); ?>
              <option value="male"           <?= $selectedGender === 'male'           ? 'selected' : '' ?>>Male</option>
              <option value="female"         <?= $selectedGender === 'female'         ? 'selected' : '' ?>>Female</option>
              <option value="other"          <?= $selectedGender === 'other'          ? 'selected' : '' ?>>Other</option>
              <option value="prefer_not_say" <?= $selectedGender === 'prefer_not_say' ? 'selected' : '' ?>>Prefer not to say</option>
            </select>
          </div>
          <div>
            <label for="create-end-date" class="label">Membership End Date</label>
            <input
              id="create-end-date"
              type="date"
              name="membership_end_date"
              value="<?= e(old('membership_end_date')) ?>"
              class="input"
              required
            >
          </div>
          <div>
            <label for="photoInput" class="label">Member Photo</label>
            <input
              id="photoInput"
              type="file"
              name="photo"
              accept="image/png,image/jpeg,image/webp"
              class="input"
              style="padding-top: 10px; height: auto; line-height: 1.4;"
            >
          </div>
          <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: 4px;">
            <button type="submit" class="btn-primary">Save Member</button>
            <a href="<?= e(url('/members')) ?>" class="btn-ghost">Cancel</a>
          </div>
        </form>
      </div>
      <!-- Camera Capture Section -->
      <div class="card" style="padding: 20px 24px;">
        <div style="
          display: flex; align-items: flex-start; justify-content: space-between;
          gap: 12px; flex-wrap: wrap; margin-bottom: 16px;
        ">
          <div>
            <h2 style="font-family: 'Bebas Neue', sans-serif; font-size: 20px; letter-spacing: 0.12em; color: var(--white); margin: 0 0 4px;">Camera Capture</h2>
            <p style="font-size: 13px; color: var(--muted); margin: 0;">Use camera or file upload. Captured image is auto-attached to the form.</p>
          </div>
          <span id="cameraStatus" style="font-size: 12px; font-weight: 600; color: var(--dim);" aria-live="polite">Camera optional</span>
        </div>
        <div id="cameraPrompt" style="
          margin-bottom: 16px;
          padding: 10px 14px;
          background: var(--raised);
          border: 1px solid var(--border);
          border-radius: 2px;
          font-size: 12px; color: var(--light);
        " aria-live="polite">
          Prompt: Open camera, capture photo, then click Save Member.
        </div>
        <!-- Video + Preview grid -->
        <div style="display: grid; gap: 16px;" class="sm:grid-cols-2">
          <!-- Live feed -->
          <div class="card-raised" style="padding: 12px;">
            <p style="font-size: 10px; font-weight: 600; letter-spacing: 0.12em; text-transform: uppercase; color: var(--muted); margin: 0 0 8px;">Live Camera</p>
            <div style="overflow: hidden; border-radius: 2px; border: 1px solid var(--border); background: #000;">
              <video
                id="cameraVideo"
                style="display: block; width: 100%; aspect-ratio: 4/3; object-fit: cover;"
                autoplay playsinline muted
              ></video>
            </div>
          </div>
          <!-- Preview -->
          <div class="card-raised" style="padding: 12px;">
            <p style="font-size: 10px; font-weight: 600; letter-spacing: 0.12em; text-transform: uppercase; color: var(--muted); margin: 0 0 8px;">Attached Photo Preview</p>
            <div style="overflow: hidden; border-radius: 2px; border: 1px solid var(--border); background: var(--bg);">
              <img
                id="cameraPreview"
                style="display: none; width: 100%; aspect-ratio: 4/3; object-fit: cover;"
                alt="Captured preview"
              >
              <div
                id="cameraPlaceholder"
                style="
                  aspect-ratio: 4/3; display: flex;
                  align-items: center; justify-content: center;
                  padding: 16px; text-align: center;
                  font-size: 12px; color: var(--muted);
                "
              >
                No photo attached yet.
              </div>
            </div>
          </div>
        </div>
        <!-- Camera controls -->
        <div style="
          display: grid; gap: 8px; margin-top: 16px;
          grid-template-columns: 1fr;
        " class="sm:grid-cols-[1fr_auto_auto_auto]">
          <div>
            <label for="cameraDevice" class="label" style="margin-bottom: 4px;">Camera Device</label>
            <select
              id="cameraDevice"
              class="input"
              disabled
            >
              <option value="">Default camera</option>
            </select>
          </div>
          <div style="display: flex; align-items: flex-end;">
            <button type="button" id="cameraOpen"    class="btn-ghost"    style="height: 44px; font-size: 12px; white-space: nowrap;">Open</button>
          </div>
          <div style="display: flex; align-items: flex-end;">
            <button type="button" id="cameraCapture" class="btn-primary"  style="height: 44px; font-size: 12px; white-space: nowrap;" disabled>Capture</button>
          </div>
          <div style="display: flex; align-items: flex-end;">
            <button type="button" id="cameraClose"   class="btn-ghost"    style="height: 44px; font-size: 12px; white-space: nowrap;" disabled>Close</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../partials/foot.php'; ?>
<script>
(function () {
  const fileInput     = document.getElementById('photoInput');
  const deviceSelect  = document.getElementById('cameraDevice');
  const openBtn       = document.getElementById('cameraOpen');
  const captureBtn    = document.getElementById('cameraCapture');
  const closeBtn      = document.getElementById('cameraClose');
  const statusEl      = document.getElementById('cameraStatus');
  const promptEl      = document.getElementById('cameraPrompt');
  const videoEl       = document.getElementById('cameraVideo');
  const previewEl     = document.getElementById('cameraPreview');
  const placeholderEl = document.getElementById('cameraPlaceholder');
  if (!fileInput || !deviceSelect || !openBtn || !captureBtn || !closeBtn || !statusEl || !promptEl || !videoEl || !previewEl || !placeholderEl) {
    return;
  }
  const hasCameraApi = !!(navigator.mediaDevices && typeof navigator.mediaDevices.getUserMedia === 'function');
  let stream = null;
  let previewUrl = null;
  let selectedDeviceId = '';
  function setStatus(message, tone) {
    statusEl.textContent = message;
    if (tone === 'ok')  { statusEl.style.color = 'var(--near)'; return; }
    if (tone === 'err') { statusEl.style.color = '#f87171';     return; }
    statusEl.style.color = 'var(--dim)';
  }
  function setPrompt(message, tone) {
    promptEl.textContent = message;
    if (tone === 'ok') {
      promptEl.style.borderColor = 'rgba(255,255,255,0.2)';
      promptEl.style.background  = 'rgba(255,255,255,0.04)';
      promptEl.style.color       = 'var(--near)';
      return;
    }
    if (tone === 'err') {
      promptEl.style.borderColor = 'rgba(248,113,113,0.3)';
      promptEl.style.background  = 'rgba(248,113,113,0.06)';
      promptEl.style.color       = '#fca5a5';
      return;
    }
    promptEl.style.borderColor = 'var(--border)';
    promptEl.style.background  = 'var(--raised)';
    promptEl.style.color       = 'var(--light)';
  }
  function clearPreviewUrl() {
    if (previewUrl) { URL.revokeObjectURL(previewUrl); previewUrl = null; }
  }
  function stopCamera() {
    if (stream) { stream.getTracks().forEach((track) => track.stop()); stream = null; }
    videoEl.pause();
    videoEl.srcObject = null;
    captureBtn.disabled = true;
    closeBtn.disabled   = true;
    openBtn.textContent = 'Open';
  }
  function showPreviewFromFile(file) {
    clearPreviewUrl();
    previewUrl = URL.createObjectURL(file);
    previewEl.src = previewUrl;
    previewEl.style.display       = 'block';
    placeholderEl.style.display   = 'none';
  }
  async function refreshDevices() {
    if (!hasCameraApi) { deviceSelect.disabled = true; openBtn.disabled = true; return; }
    try {
      const devices = await navigator.mediaDevices.enumerateDevices();
      const cameras = devices.filter((d) => d.kind === 'videoinput');
      deviceSelect.innerHTML = '';
      const defaultOption = document.createElement('option');
      defaultOption.value = '';
      defaultOption.textContent = cameras.length > 0 ? 'Default camera' : 'No camera found';
      deviceSelect.appendChild(defaultOption);
      cameras.forEach((camera, index) => {
        const opt = document.createElement('option');
        opt.value = camera.deviceId;
        opt.textContent = camera.label || ('Camera ' + String(index + 1));
        deviceSelect.appendChild(opt);
      });
      deviceSelect.disabled = cameras.length === 0;
      openBtn.disabled      = cameras.length === 0;
      if (selectedDeviceId && cameras.some((c) => c.deviceId === selectedDeviceId)) {
        deviceSelect.value = selectedDeviceId;
      }
      if (cameras.length === 0) {
        setStatus('No camera device detected. Use file upload.', 'err');
        setPrompt('No camera detected. Please use file upload for member photo.', 'err');
      }
    } catch (_error) {
      deviceSelect.disabled = true;
      openBtn.disabled      = true;
      setStatus('Unable to list cameras. Use file upload.', 'err');
      setPrompt('Camera list unavailable. Please continue with file upload.', 'err');
    }
  }
  function handleCameraError(error) {
    stopCamera();
    if (error && (error.name === 'NotAllowedError' || error.name === 'PermissionDeniedError')) {
      setStatus('Camera permission denied. Use file upload.', 'err');
      setPrompt('Camera permission denied. Continue using file upload.', 'err');
      return;
    }
    if (error && (error.name === 'NotFoundError' || error.name === 'DevicesNotFoundError' || error.name === 'OverconstrainedError')) {
      setStatus('No usable camera found. Use file upload.', 'err');
      setPrompt('No usable camera found. Continue using file upload.', 'err');
      return;
    }
    setStatus('Camera unavailable. Use file upload.', 'err');
    setPrompt('Camera unavailable right now. Continue using file upload.', 'err');
  }
  async function openCamera() {
    if (!hasCameraApi) return;
    stopCamera();
    const constraints = selectedDeviceId
      ? { video: { deviceId: { exact: selectedDeviceId } }, audio: false }
      : { video: true, audio: false };
    try {
      stream = await navigator.mediaDevices.getUserMedia(constraints);
      videoEl.srcObject = stream;
      await videoEl.play();
      captureBtn.disabled = false;
      closeBtn.disabled   = false;
      openBtn.textContent = 'Restart';
      setStatus('Camera ready. Click Capture.', 'ok');
      setPrompt('Camera ready. Center the member and click Capture.', 'idle');
      const track = stream.getVideoTracks()[0];
      if (track && typeof track.getSettings === 'function') {
        const settings = track.getSettings();
        if (settings.deviceId) selectedDeviceId = settings.deviceId;
      }
      await refreshDevices();
    } catch (error) {
      handleCameraError(error);
      setPrompt('Open camera first, then capture photo.', 'err');
      return;
    }
  }
  function capturePhoto() {
    if (!stream) {
      setStatus('Open camera first.', 'err');
      setPrompt('Open camera first, then capture photo.', 'err');
      return;
    }
    if (typeof DataTransfer !== 'function' || typeof File !== 'function') {
      setStatus('Browser cannot attach captured image automatically. Use file upload.', 'err');
      setPrompt('Automatic attach is not supported in this browser. Use file upload.', 'err');
      return;
    }
    const srcW    = videoEl.videoWidth  || 640;
    const srcH    = videoEl.videoHeight || 480;
    const maxDim  = 1024;
    const scale   = Math.min(1, maxDim / Math.max(srcW, srcH));
    const targetW = Math.max(320, Math.round(srcW * scale));
    const targetH = Math.max(240, Math.round(srcH * scale));
    const canvas  = document.createElement('canvas');
    canvas.width  = targetW;
    canvas.height = targetH;
    const ctx = canvas.getContext('2d');
    if (!ctx) {
      setStatus('Capture failed. Use file upload.', 'err');
      setPrompt('Unable to capture from camera. Use file upload instead.', 'err');
      return;
    }
    ctx.drawImage(videoEl, 0, 0, targetW, targetH);
    canvas.toBlob((blob) => {
      if (!blob) {
        setStatus('Could not create photo file. Try again.', 'err');
        setPrompt('Capture did not produce a valid image. Try again.', 'err');
        return;
      }
      const stamp     = new Date().toISOString().replace(/[^0-9]/g, '');
      const photoFile = new File([blob], 'member-camera-' + stamp + '.jpg', {
        type: 'image/jpeg', lastModified: Date.now()
      });
      const dt = new DataTransfer();
      dt.items.add(photoFile);
      fileInput.files = dt.files;
      showPreviewFromFile(photoFile);
      setStatus('Captured photo attached. Ready to save.', 'ok');
      setPrompt('Photo captured and attached. Click Save Member to continue.', 'ok');
    }, 'image/jpeg', 0.88);
  }
  openBtn.addEventListener('click',    () => openCamera());
  captureBtn.addEventListener('click', () => capturePhoto());
  closeBtn.addEventListener('click',   () => {
    stopCamera();
    setStatus('Camera closed.', 'idle');
    setPrompt('Camera closed. You can reopen it or use file upload.', 'idle');
  });
  deviceSelect.addEventListener('change', () => {
    selectedDeviceId = deviceSelect.value;
    if (stream) openCamera();
  });
  fileInput.addEventListener('change', () => {
    if (!fileInput.files || fileInput.files.length === 0) return;
    const selected = fileInput.files[0];
    if (!selected.type || !selected.type.startsWith('image/')) return;
    showPreviewFromFile(selected);
    setStatus('Photo file selected.', 'ok');
    setPrompt('Photo file selected and attached. Click Save Member to continue.', 'ok');
  });
  if (navigator.mediaDevices && typeof navigator.mediaDevices.addEventListener === 'function') {
    navigator.mediaDevices.addEventListener('devicechange', () => refreshDevices());
  }
  window.addEventListener('beforeunload', () => { stopCamera(); clearPreviewUrl(); });
  if (!hasCameraApi) {
    deviceSelect.disabled = true;
    openBtn.disabled      = true;
    captureBtn.disabled   = true;
    closeBtn.disabled     = true;
    setStatus('Camera not supported in this browser. Use file upload.', 'err');
    setPrompt('Camera capture is not supported in this browser. Use file upload.', 'err');
    return;
  }
  setPrompt('Prompt: Open camera, capture photo, then click Save Member.', 'idle');
  setStatus('Use Open to start camera, or choose file upload.', 'idle');
  refreshDevices();
})();
</script>
