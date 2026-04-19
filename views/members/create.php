<?php

declare(strict_types=1);

$title = 'Add Member';
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
          <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Member Onboarding</p>
          <h1 class="mt-2 font-display text-3xl font-bold leading-tight text-white sm:text-4xl">Create a new member profile</h1>
          <p class="mt-3 text-sm text-slate-400">Register member identity, membership validity, and profile photo in one guided flow.</p>

          <div class="mt-5 space-y-2">
            <a href="<?= e(url('/members')) ?>" class="flex h-11 items-center justify-center rounded-xl border border-slate-600 px-4 text-center text-sm font-semibold text-slate-200 transition hover:border-slate-400 hover:bg-slate-800">Back to Members</a>
            <a href="<?= e(url('/attendance/scan')) ?>" class="flex h-11 items-center justify-center rounded-xl bg-white px-4 text-center text-sm font-semibold text-slate-900 transition hover:bg-slate-100">Open Scanner</a>
          </div>
        </div>

        <div class="mt-5 rounded-2xl border border-slate-700 bg-slate-900/60 p-4">
          <p class="text-sm font-semibold text-slate-200">Quick Checklist</p>
          <ul class="mt-3 space-y-2 text-xs text-slate-400">
            <li class="rounded-lg border border-slate-700 bg-slate-900/60 px-3 py-2">Enter full member name clearly.</li>
            <li class="rounded-lg border border-slate-700 bg-slate-900/60 px-3 py-2">Set correct membership end date.</li>
            <li class="rounded-lg border border-slate-700 bg-slate-900/60 px-3 py-2">Attach photo via upload or camera capture.</li>
          </ul>
        </div>
      </aside>

      <div class="order-1 space-y-4 xl:order-2 xl:space-y-5">
        <section class="rounded-2xl border border-slate-800 bg-[#0f141d] p-4 sm:p-5">
          <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
              <h2 class="font-display text-2xl font-bold text-white">Member Information</h2>
              <p class="text-sm text-slate-400">Create member profile for QR attendance tracking.</p>
            </div>
            <p class="text-xs uppercase tracking-wide text-slate-500">Required fields marked by browser validation</p>
          </div>

          <form action="<?= e(url('/members/create')) ?>" method="post" enctype="multipart/form-data" class="mt-5 grid gap-4">
            <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">

            <label class="block">
              <span class="mb-1 block text-sm font-semibold text-slate-300">Full name</span>
              <input type="text" name="full_name" value="<?= e(old('full_name')) ?>" class="h-11 w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2.5 text-slate-100 outline-none ring-cyan-300 transition focus:ring sm:px-4" required>
            </label>

            <label class="block">
              <span class="mb-1 block text-sm font-semibold text-slate-300">Email (optional)</span>
              <input type="email" name="email" value="<?= e(old('email')) ?>" class="h-11 w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2.5 text-slate-100 outline-none ring-cyan-300 transition focus:ring sm:px-4">
            </label>

            <label class="block">
              <span class="mb-1 block text-sm font-semibold text-slate-300">Gender</span>
              <select name="gender" class="h-11 w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2.5 text-slate-100 outline-none ring-cyan-300 transition focus:ring sm:px-4" required>
                <?php $selectedGender = (string) old('gender', 'prefer_not_say'); ?>
                <option value="male" <?= $selectedGender === 'male' ? 'selected' : '' ?>>Male</option>
                <option value="female" <?= $selectedGender === 'female' ? 'selected' : '' ?>>Female</option>
                <option value="other" <?= $selectedGender === 'other' ? 'selected' : '' ?>>Other</option>
                <option value="prefer_not_say" <?= $selectedGender === 'prefer_not_say' ? 'selected' : '' ?>>Prefer not to say</option>
              </select>
            </label>

            <label class="block">
              <span class="mb-1 block text-sm font-semibold text-slate-300">Membership end date</span>
              <input type="date" name="membership_end_date" value="<?= e(old('membership_end_date')) ?>" class="h-11 w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2.5 text-slate-100 outline-none ring-cyan-300 transition focus:ring sm:px-4" required>
            </label>

            <label class="block">
              <span class="mb-1 block text-sm font-semibold text-slate-300">Member photo</span>
              <input id="photoInput" type="file" name="photo" accept="image/png,image/jpeg,image/webp" class="h-11 w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-slate-100 outline-none ring-cyan-300 transition focus:ring sm:px-4">
            </label>

            <div class="mt-2 flex flex-wrap items-center gap-3">
              <button type="submit" class="inline-flex h-11 items-center rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-slate-900 transition hover:bg-slate-100">Save Member</button>
              <a href="<?= e(url('/members')) ?>" class="inline-flex h-11 items-center rounded-xl border border-slate-600 px-4 py-2.5 text-sm font-semibold text-slate-200 transition hover:border-slate-400 hover:bg-slate-800">Cancel</a>
            </div>
          </form>
        </section>

        <section class="rounded-2xl border border-slate-800 bg-[#0f141d] p-4 sm:p-5">
          <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
            <div>
              <p class="text-sm font-semibold text-slate-200">Capture member photo</p>
              <p class="text-xs text-slate-400">Use camera or file upload. Captured image is auto-attached to the form.</p>
            </div>
            <p id="cameraStatus" class="text-xs font-medium text-slate-300" aria-live="polite">Camera optional</p>
          </div>

          <p id="cameraPrompt" class="mt-3 rounded-xl border border-slate-700 bg-slate-900/70 px-3 py-2 text-xs font-semibold text-slate-200" aria-live="polite">
            Prompt: Open camera, capture photo, then click Save Member.
          </p>

          <div class="mt-4 grid gap-3 sm:gap-4 lg:grid-cols-2">
            <article class="rounded-2xl border border-slate-700 bg-slate-950/95 p-3 shadow-inner">
              <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-300">Live camera</p>
              <div class="overflow-hidden rounded-xl border border-slate-700 bg-black">
                <video id="cameraVideo" class="aspect-video w-full object-cover sm:aspect-[4/3]" autoplay playsinline muted></video>
              </div>
            </article>

            <article class="rounded-2xl border border-slate-700 bg-slate-900/70 p-3 shadow-sm">
              <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">Attached photo preview</p>
              <div class="overflow-hidden rounded-xl border border-slate-700 bg-slate-950">
                <img id="cameraPreview" class="hidden aspect-video w-full object-cover sm:aspect-[4/3]" alt="Captured preview">
                <div id="cameraPlaceholder" class="flex aspect-video items-center justify-center px-4 text-center text-xs text-slate-500 sm:aspect-[4/3]">
                  No photo attached yet. Capture from camera or choose a file above.
                </div>
              </div>
            </article>
          </div>

          <div class="mt-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-[minmax(0,1fr)_auto_auto_auto] lg:items-end">
            <label class="block">
              <span class="mb-1 block text-xs font-semibold text-slate-300">Camera device</span>
              <select id="cameraDevice" class="h-11 w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-sm text-slate-100 outline-none ring-cyan-300 transition focus:ring" disabled>
                <option value="">Default camera</option>
              </select>
            </label>

            <button type="button" id="cameraOpen" class="h-11 rounded-xl border border-slate-600 px-4 py-2 text-sm font-semibold text-slate-200 transition hover:border-slate-400 hover:bg-slate-800">Open</button>
            <button type="button" id="cameraCapture" class="h-11 rounded-xl bg-white px-4 py-2 text-sm font-semibold text-slate-900 transition hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-50">Capture</button>
            <button type="button" id="cameraClose" class="h-11 rounded-xl border border-slate-600 px-4 py-2 text-sm font-semibold text-slate-200 transition hover:border-slate-400 hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-50">Close</button>
          </div>
        </section>
      </div>
    </div>
  </section>
</main>
<?php require __DIR__ . '/../partials/foot.php'; ?>

<script>
(function () {
  const fileInput = document.getElementById('photoInput');
  const deviceSelect = document.getElementById('cameraDevice');
  const openBtn = document.getElementById('cameraOpen');
  const captureBtn = document.getElementById('cameraCapture');
  const closeBtn = document.getElementById('cameraClose');
  const statusEl = document.getElementById('cameraStatus');
  const promptEl = document.getElementById('cameraPrompt');
  const videoEl = document.getElementById('cameraVideo');
  const previewEl = document.getElementById('cameraPreview');
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
    statusEl.classList.remove('text-slate-300', 'text-emerald-300', 'text-rose-300');
    if (tone === 'ok') {
      statusEl.classList.add('text-emerald-300');
      return;
    }
    if (tone === 'err') {
      statusEl.classList.add('text-rose-300');
      return;
    }
    statusEl.classList.add('text-slate-300');
  }

  function setPrompt(message, tone) {
    promptEl.textContent = message;
    promptEl.classList.remove(
      'border-slate-700', 'bg-slate-900/70', 'text-slate-200',
      'border-emerald-400/40', 'bg-emerald-400/10', 'text-emerald-300',
      'border-rose-400/40', 'bg-rose-400/10', 'text-rose-300'
    );

    if (tone === 'ok') {
      promptEl.classList.add('border-emerald-400/40', 'bg-emerald-400/10', 'text-emerald-300');
      return;
    }

    if (tone === 'err') {
      promptEl.classList.add('border-rose-400/40', 'bg-rose-400/10', 'text-rose-300');
      return;
    }

    promptEl.classList.add('border-slate-700', 'bg-slate-900/70', 'text-slate-200');
  }

  function clearPreviewUrl() {
    if (previewUrl) {
      URL.revokeObjectURL(previewUrl);
      previewUrl = null;
    }
  }

  function stopCamera() {
    if (stream) {
      stream.getTracks().forEach((track) => track.stop());
      stream = null;
    }
    videoEl.pause();
    videoEl.srcObject = null;
    captureBtn.disabled = true;
    closeBtn.disabled = true;
    openBtn.textContent = 'Open';
  }

  function showPreviewFromFile(file) {
    clearPreviewUrl();
    previewUrl = URL.createObjectURL(file);
    previewEl.src = previewUrl;
    previewEl.classList.remove('hidden');
    placeholderEl.classList.add('hidden');
  }

  async function refreshDevices() {
    if (!hasCameraApi) {
      deviceSelect.disabled = true;
      openBtn.disabled = true;
      return;
    }

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
      openBtn.disabled = cameras.length === 0;

      if (selectedDeviceId && cameras.some((c) => c.deviceId === selectedDeviceId)) {
        deviceSelect.value = selectedDeviceId;
      }

      if (cameras.length === 0) {
        setStatus('No camera device detected. Use file upload.', 'err');
        setPrompt('No camera detected. Please use file upload for member photo.', 'err');
      }
    } catch (_error) {
      deviceSelect.disabled = true;
      openBtn.disabled = true;
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
    if (!hasCameraApi) {
      return;
    }

    stopCamera();

    const constraints = selectedDeviceId
      ? { video: { deviceId: { exact: selectedDeviceId } }, audio: false }
      : { video: true, audio: false };

    try {
      stream = await navigator.mediaDevices.getUserMedia(constraints);
      videoEl.srcObject = stream;
      await videoEl.play();

      captureBtn.disabled = false;
      closeBtn.disabled = false;
      openBtn.textContent = 'Restart';
      setStatus('Camera ready. Click Capture.', 'ok');
      setPrompt('Camera ready. Center the member and click Capture.', 'idle');

      const track = stream.getVideoTracks()[0];
      if (track && typeof track.getSettings === 'function') {
        const settings = track.getSettings();
        if (settings.deviceId) {
          selectedDeviceId = settings.deviceId;
        }
      }

      await refreshDevices();
    } catch (error) {
      handleCameraError(error);
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

    const srcW = videoEl.videoWidth || 640;
    const srcH = videoEl.videoHeight || 480;
    const maxDim = 1024;
    const scale = Math.min(1, maxDim / Math.max(srcW, srcH));
    const targetW = Math.max(320, Math.round(srcW * scale));
    const targetH = Math.max(240, Math.round(srcH * scale));

    const canvas = document.createElement('canvas');
    canvas.width = targetW;
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

      const stamp = new Date().toISOString().replace(/[^0-9]/g, '');
      const photoFile = new File([blob], 'member-camera-' + stamp + '.jpg', {
        type: 'image/jpeg',
        lastModified: Date.now()
      });

      const dt = new DataTransfer();
      dt.items.add(photoFile);
      fileInput.files = dt.files;

      showPreviewFromFile(photoFile);
      setStatus('Captured photo attached. Ready to save.', 'ok');
      setPrompt('Photo captured and attached. Click Save Member to continue.', 'ok');
    }, 'image/jpeg', 0.88);
  }

  openBtn.addEventListener('click', () => openCamera());
  captureBtn.addEventListener('click', () => capturePhoto());

  closeBtn.addEventListener('click', () => {
    stopCamera();
    setStatus('Camera closed.', 'idle');
    setPrompt('Camera closed. You can reopen it or use file upload.', 'idle');
  });

  deviceSelect.addEventListener('change', () => {
    selectedDeviceId = deviceSelect.value;
    if (stream) {
      openCamera();
    }
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

  window.addEventListener('beforeunload', () => {
    stopCamera();
    clearPreviewUrl();
  });

  if (!hasCameraApi) {
    deviceSelect.disabled = true;
    openBtn.disabled = true;
    captureBtn.disabled = true;
    closeBtn.disabled = true;
    setStatus('Camera not supported in this browser. Use file upload.', 'err');
    setPrompt('Camera capture is not supported in this browser. Use file upload.', 'err');
    return;
  }

  setPrompt('Prompt: Open camera, capture photo, then click Save Member.', 'idle');
  setStatus('Use Open to start camera, or choose file upload.', 'idle');
  refreshDevices();
})();
</script>
