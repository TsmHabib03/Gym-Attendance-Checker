<?php

declare(strict_types=1);

$currentPhotoSrc = !empty($member['photo_path']) ? url((string) $member['photo_path']) : 'https://placehold.co/80x80?text=GYM';
$membershipActive = (new DateTimeImmutable((string) $member['membership_end_date'])) >= new DateTimeImmutable('today');
$membershipStatus  = $membershipActive ? 'Active' : 'Expired';
$statusBadgeClass  = $membershipActive ? 'stat-badge stat-badge-ok' : 'stat-badge stat-badge-danger';
require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/nav.php';
?>
<div class="page-enter" style="max-width: 1280px; margin: 0 auto; padding: 32px 16px 64px;">
  <!-- Page header -->
  <div style="margin-bottom: 32px; display: flex; align-items: flex-end; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
    <div>
      <p style="font-size: 11px; letter-spacing: 0.14em; color: var(--muted); text-transform: uppercase; margin: 0 0 6px;">Member Update</p>
      <h1 style="
        font-family: 'Bebas Neue', sans-serif;
        font-size: clamp(32px, 5vw, 48px);
        letter-spacing: 0.10em;
        color: var(--white);
        margin: 0; line-height: 1;
      ">Edit Member</h1>
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
      <!-- Member summary card -->
      <div class="card" style="padding: 20px;">
        <div class="section-rule" style="margin-bottom: 16px;">
          <span style="font-family: 'Bebas Neue', sans-serif; font-size: 16px; letter-spacing: 0.12em; color: var(--white);">Member</span>
        </div>
        <div style="
          background: var(--raised); border: 1px solid var(--border);
          border-radius: 2px; padding: 14px; margin-bottom: 16px;
        ">
          <p style="font-size: 14px; font-weight: 600; color: var(--near); margin: 0 0 4px;"><?= e((string) $member['full_name']) ?></p>
          <p style="font-size: 11px; color: var(--muted); margin: 0 0 10px;">Code: <?= e((string) $member['member_code']) ?></p>
          <span class="<?= e($statusBadgeClass) ?>"><?= e($membershipStatus) ?></span>
        </div>
        <div style="display: flex; flex-direction: column; gap: 8px;">
          <a href="<?= e(url('/members')) ?>"           class="btn-ghost"   style="width: 100%; font-size: 12px;">Back to Members</a>
          <a href="<?= e(url('/attendance/scan')) ?>"   class="btn-primary" style="width: 100%; font-size: 12px;">Open Scanner</a>
        </div>
      </div>
      <!-- Membership details card -->
      <div class="card" style="padding: 20px;">
        <p style="font-size: 11px; font-weight: 600; letter-spacing: 0.12em; text-transform: uppercase; color: var(--muted); margin: 0 0 12px;">Current Membership</p>
        <div style="display: flex; flex-direction: column; gap: 2px; background: var(--border); border: 1px solid var(--border); border-radius: 2px; overflow: hidden;">
          <div style="background: var(--raised); padding: 10px 14px; display: flex; justify-content: space-between; align-items: center;">
            <span style="font-size: 12px; color: var(--muted);">End date</span>
            <span style="font-size: 12px; font-weight: 600; color: var(--near);"><?= e((string) $member['membership_end_date']) ?></span>
          </div>
          <div style="background: var(--raised); padding: 10px 14px; display: flex; justify-content: space-between; align-items: center;">
            <span style="font-size: 12px; color: var(--muted);">Status</span>
            <span style="font-size: 12px; font-weight: 600; color: <?= $membershipActive ? 'var(--near)' : '#f87171' ?>;"><?= e($membershipStatus) ?></span>
          </div>
        </div>
        <p style="font-size: 11px; color: var(--muted); margin: 10px 0 0; letter-spacing: 0.02em;">
          Member ID #<?= e((string) $member['id']) ?>
        </p>
      </div>
    </aside>
    <!-- ── MAIN FORM ── -->
    <section class="order-1 lg:order-2 card" style="padding: 20px 24px;">
      <div style="margin-bottom: 20px;">
        <h2 style="font-family: 'Bebas Neue', sans-serif; font-size: 20px; letter-spacing: 0.12em; color: var(--white); margin: 0 0 4px;">Edit Member Information</h2>
        <p style="font-size: 13px; color: var(--muted); margin: 0;">Update profile and membership details.</p>
      </div>
      <form action="<?= e(url('/members/edit')) ?>" method="post" enctype="multipart/form-data" style="display: flex; flex-direction: column; gap: 18px;">
        <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
        <input type="hidden" name="id"   value="<?= e((string) $member['id']) ?>">
        <div>
          <label for="edit-full-name" class="label">Full Name</label>
          <input
            id="edit-full-name"
            type="text"
            name="full_name"
            value="<?= e((string) $member['full_name']) ?>"
            class="input"
            required
          >
        </div>
        <div>
          <label for="edit-email" class="label">Email (optional)</label>
          <input
            id="edit-email"
            type="email"
            name="email"
            value="<?= e((string) ($member['email'] ?? '')) ?>"
            class="input"
          >
        </div>
        <div>
          <label for="edit-gender" class="label">Gender</label>
          <?php $selectedGender = (string) ($member['gender'] ?? 'prefer_not_say'); ?>
          <select id="edit-gender" name="gender" class="input" required>
            <option value="male"           <?= $selectedGender === 'male'           ? 'selected' : '' ?>>Male</option>
            <option value="female"         <?= $selectedGender === 'female'         ? 'selected' : '' ?>>Female</option>
            <option value="other"          <?= $selectedGender === 'other'          ? 'selected' : '' ?>>Other</option>
            <option value="prefer_not_say" <?= $selectedGender === 'prefer_not_say' ? 'selected' : '' ?>>Prefer not to say</option>
          </select>
        </div>
        <div>
          <label for="edit-end-date" class="label">Membership End Date</label>
          <input
            id="edit-end-date"
            type="date"
            name="membership_end_date"
            value="<?= e((string) $member['membership_end_date']) ?>"
            class="input"
            required
          >
        </div>
        <!-- Current photo + replace -->
        <div style="background: var(--raised); border: 1px solid var(--border); border-radius: 2px; padding: 16px;">
          <p class="label" style="margin-bottom: 12px;">Profile Photo</p>
          <div style="display: flex; align-items: flex-start; gap: 16px; flex-wrap: wrap;">
            <!-- Clickable current photo -->
            <div style="position: relative; flex-shrink: 0;">
              <img
                id="editPhotoPreview"
                src="<?= e($currentPhotoSrc) ?>"
                alt="Current photo"
                data-fullsrc="<?= e($currentPhotoSrc) ?>"
                data-name="<?= e((string) $member['full_name']) ?>"
                style="
                  width: 100px; height: 100px; border-radius: 2px; object-fit: cover;
                  border: 1px solid var(--border); cursor: pointer; display: block;
                  transition: border-color 0.15s;
                "
                title="Click to view full photo"
                onclick="openEditLightbox(this.dataset.fullsrc, this.dataset.name)"
              >
              <div style="
                margin-top: 6px; text-align: center;
                font-size: 10px; letter-spacing: 0.08em; color: var(--muted); text-transform: uppercase;
              ">Click to view</div>
            </div>
            <div style="flex: 1; min-width: 160px; display: flex; flex-direction: column; gap: 12px;">
              <div>
                <label for="edit-photo" class="label">Replace Photo (optional)</label>
                <input
                  id="edit-photo"
                  type="file"
                  name="photo"
                  accept="image/png,image/jpeg,image/webp"
                  class="input"
                  style="padding-top: 10px; height: auto; line-height: 1.4;"
                >
              </div>
              <p style="font-size: 11px; color: var(--muted); margin: 0; line-height: 1.6;">
                Upload a new photo to replace the current one. Accepted: JPG, PNG, WebP.
              </p>
            </div>
          </div>
        </div>
        <!-- Action buttons -->
        <div style="display: flex; gap: 8px; flex-wrap: wrap; padding-top: 4px; border-top: 1px solid var(--border);">
          <button type="submit" class="btn-primary">Update Member</button>
          <a href="<?= e(url('/members')) ?>" class="btn-ghost">Cancel</a>
          <button
            type="submit"
            formaction="<?= e(url('/members/delete')) ?>"
            formmethod="post"
            formnovalidate
            class="btn-danger"
            style="margin-left: auto;"
            onclick="return confirm('Delete this member? This action cannot be undone.');"
          >Delete Member</button>
        </div>
      </form>
    </section>
  </div>
</div>
<!-- Edit page photo lightbox modal -->
<div id="editPhotoLightbox" style="
  display: none;
  position: fixed; inset: 0; z-index: 200;
  background: rgba(0,0,0,0.88);
  align-items: center; justify-content: center;
  backdrop-filter: blur(6px);
  -webkit-backdrop-filter: blur(6px);
" role="dialog" aria-modal="true" aria-label="Member Photo">
  <div style="position: relative; max-width: 480px; width: 90%;">
    <button onclick="closeEditLightbox()" style="
      position: absolute; top: -44px; right: 0;
      width: 36px; height: 36px;
      background: var(--raised); border: 1px solid var(--border);
      border-radius: 2px; cursor: pointer;
      color: var(--dim); font-size: 18px; font-weight: 300;
      display: flex; align-items: center; justify-content: center;
    " aria-label="Close">✕</button>
    <img id="editLightboxImg" src="" alt="Member Photo" style="
      width: 100%; max-height: 70vh; object-fit: contain;
      border-radius: 2px; border: 1px solid var(--border); display: block;
    ">
    <p id="editLightboxName" style="
      text-align: center; margin-top: 12px;
      font-family: 'Bebas Neue', sans-serif; font-size: 18px;
      letter-spacing: 0.12em; color: var(--white);
    "></p>
  </div>
</div>
<script>
function openEditLightbox(src, name) {
  document.getElementById('editLightboxImg').src = src || '';
  document.getElementById('editLightboxName').textContent = name || '';
  var lb = document.getElementById('editPhotoLightbox');
  lb.style.display = 'flex';
  document.body.style.overflow = 'hidden';
}
function closeEditLightbox() {
  var lb = document.getElementById('editPhotoLightbox');
  lb.style.display = 'none';
  document.body.style.overflow = '';
  document.getElementById('editLightboxImg').src = '';
}
document.getElementById('editPhotoLightbox').addEventListener('click', function (e) {
  if (e.target === this) closeEditLightbox();
});
document.addEventListener('keydown', function (e) {
  if (e.key === 'Escape') closeEditLightbox();
});
</script>

<?php require __DIR__ . '/../partials/foot.php'; ?>
