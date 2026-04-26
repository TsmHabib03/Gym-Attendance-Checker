<?php

declare(strict_types=1);

$title = 'Members';
$dashboardShell = true;
$memberCount  = count($members);
$activeCount  = 0;
$expiredCount = 0;
foreach ($members as $member) {
    $isActive = (new DateTimeImmutable((string) $member['membership_end_date'])) >= new DateTimeImmutable('today');
    if ($isActive) {
        $activeCount++;
        continue;
    }
    $expiredCount++;
}
require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/nav.php';
?>

<!-- ── PHOTO LIGHTBOX MODAL ── -->
<div id="photoLightbox" style="
  display: none;
  position: fixed; inset: 0; z-index: 200;
  background: rgba(0,0,0,0.88);
  align-items: center; justify-content: center;
  backdrop-filter: blur(6px);
  -webkit-backdrop-filter: blur(6px);
" role="dialog" aria-modal="true" aria-label="Member Photo">
  <div style="position: relative; max-width: 480px; width: 90%;">
    <button id="lightboxClose" style="
      position: absolute; top: -44px; right: 0;
      width: 36px; height: 36px;
      background: var(--raised); border: 1px solid var(--border);
      border-radius: 2px; cursor: pointer;
      color: var(--dim); font-size: 18px; font-weight: 300;
      display: flex; align-items: center; justify-content: center;
    " aria-label="Close">✕</button>
    <img id="lightboxImg" src="" alt="Member Photo" style="
      width: 100%; max-height: 70vh; object-fit: contain;
      border-radius: 2px; border: 1px solid var(--border);
      display: block;
    ">
    <p id="lightboxName" style="
      text-align: center; margin-top: 12px;
      font-family: 'Bebas Neue', sans-serif; font-size: 18px;
      letter-spacing: 0.12em; color: var(--white);
    "></p>
  </div>
</div>

<div class="page-enter page-container">
  <!-- Page header -->
  <div class="page-header">
    <div>
      <p style="font-size: 11px; letter-spacing: 0.14em; color: var(--muted); text-transform: uppercase; margin: 0 0 6px;">Member Hub</p>
      <h1 style="
        font-family: 'Bebas Neue', sans-serif;
        font-size: clamp(28px, 5vw, 48px);
        letter-spacing: 0.10em;
        color: var(--white);
        margin: 0; line-height: 1;
      ">Members</h1>
    </div>
    <div class="page-header-actions">
      <a href="<?= e(url('/members/create')) ?>" class="btn-primary" style="height: 38px; font-size: 11px;">+ Add Member</a>
      <a href="<?= e(url('/attendance/scan')) ?>" class="btn-ghost"   style="height: 38px; font-size: 11px;">Scan QR</a>
    </div>
  </div>
  <!-- Flash -->
  <?php require __DIR__ . '/../partials/flash.php'; ?>
  <!-- Two-column layout -->
  <div class="sidebar-layout">
    <!-- ── SIDEBAR ── -->
    <aside class="order-2 lg:order-1" style="display: flex; flex-direction: column; gap: 16px;">
      <!-- Stats -->
      <div class="card" style="padding: 20px;">
        <div class="section-rule" style="margin-bottom: 16px;">
          <span style="font-family: 'Bebas Neue', sans-serif; font-size: 16px; letter-spacing: 0.12em; color: var(--white);">Directory</span>
        </div>
        <div style="display: grid; gap: 1px; background: var(--border); border: 1px solid var(--border); border-radius: 2px; overflow: hidden; margin-bottom: 16px;">
          <?php
          $sideStats = [
            ['label' => 'Total',   'value' => $memberCount,  'color' => 'var(--white)'],
            ['label' => 'Active',  'value' => $activeCount,  'color' => 'var(--near)'],
            ['label' => 'Expired', 'value' => $expiredCount, 'color' => '#f87171'],
          ];
          foreach ($sideStats as $ss): ?>
            <div style="background: var(--raised); padding: 12px 14px; display: flex; justify-content: space-between; align-items: center;">
              <span style="font-size: 11px; font-weight: 600; letter-spacing: 0.1em; text-transform: uppercase; color: var(--muted);"><?= e($ss['label']) ?></span>
              <span style="font-family: 'Bebas Neue', sans-serif; font-size: 26px; color: <?= e($ss['color']) ?>; line-height: 1;">
                <?= e((string) $ss['value']) ?>
              </span>
            </div>
          <?php endforeach; ?>
        </div>
        <div style="display: flex; flex-direction: column; gap: 8px;">
          <a href="<?= e(url('/members/create')) ?>" class="btn-primary"  style="width: 100%;">+ Add New Member</a>
          <a href="<?= e(url('/attendance/scan')) ?>"  class="btn-ghost"   style="width: 100%;">Open Scanner</a>
          <a href="<?= e(url('/members/qr-bulk')) ?>" class="btn-ghost"   style="width: 100%;">Print All QR Cards</a>
        </div>
      </div>
      <!-- Info note -->
      <div class="card" style="padding: 16px 20px;">
        <p style="font-size: 12px; color: var(--muted); line-height: 1.7; margin: 0;">
          Manage member records, renewal health, and profile updates in one focused view.
          Click a member photo to view it full-size.
        </p>
      </div>
    </aside>
    <!-- ── MAIN CONTENT ── -->
    <div class="order-1 lg:order-2" style="display: flex; flex-direction: column; gap: 16px;">
      <!-- Search -->
      <div class="card" style="padding: 20px 24px;">
        <div style="margin-bottom: 16px;">
          <h2 style="font-family: 'Bebas Neue', sans-serif; font-size: 20px; letter-spacing: 0.12em; color: var(--white); margin: 0 0 4px;">Search Members</h2>
          <p style="font-size: 13px; color: var(--muted); margin: 0;">Find by name or member code.</p>
        </div>
        <form action="<?= e(url('/members')) ?>" method="get">
          <div style="display: flex; gap: 8px; flex-wrap: wrap;">
            <input
              type="text"
              name="search"
              value="<?= e($search) ?>"
              placeholder="Search by name or member code"
              class="input"
              style="flex: 1; min-width: 0;"
            >
            <button type="submit" class="btn-primary" style="height: 44px; font-size: 12px; flex-shrink: 0; white-space: nowrap;">Search</button>
          </div>
        </form>
      </div>
      <!-- Members list -->
      <div class="card" style="overflow: hidden;">
        <div style="
          padding: 16px 24px;
          border-bottom: 1px solid var(--border);
          display: flex; align-items: center; justify-content: space-between; gap: 12px;
        ">
          <h2 style="font-family: 'Bebas Neue', sans-serif; font-size: 20px; letter-spacing: 0.12em; color: var(--white); margin: 0;">Members List</h2>
          <span style="font-size: 11px; color: var(--muted); letter-spacing: 0.06em;">
            <?= e((string) $memberCount) ?> total
          </span>
        </div>
        <!-- Mobile cards (hidden on md+) -->
        <div class="md:hidden">
          <?php if (count($members) === 0): ?>
            <div style="padding: 40px 24px; text-align: center; font-size: 13px; color: var(--muted);">No members found.</div>
          <?php else: ?>
            <?php foreach ($members as $member): ?>
              <?php
              $isActive      = (new DateTimeImmutable((string) $member['membership_end_date'])) >= new DateTimeImmutable('today');
              $status        = $isActive ? 'Active' : 'Expired';
              $statusClass   = $isActive ? 'stat-badge stat-badge-ok' : 'stat-badge stat-badge-danger';
              $photoSrc      = !empty($member['photo_path']) ? url((string) $member['photo_path']) : url('/assets/img/placeholder-member.svg');
              $attendanceCount = (int) ($member['attendance_count'] ?? 0);
              $hasAttendance = $attendanceCount > 0;
              ?>
              <div style="padding: 16px 24px; border-bottom: 1px solid var(--border);">
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                  <img
                    src="<?= e($photoSrc) ?>"
                    alt="Member photo"
                    data-fullsrc="<?= e($photoSrc) ?>"
                    data-name="<?= e($member['full_name']) ?>"
                    class="member-photo-thumb"
                    style="width: 56px; height: 56px; border-radius: 2px; object-fit: cover; border: 1px solid var(--border); flex-shrink: 0; cursor: pointer; transition: border-color 0.15s, opacity 0.15s;"
                    title="Click to view full photo"
                  >
                  <div style="min-width: 0; flex: 1;">
                    <p style="font-size: 14px; font-weight: 600; color: var(--near); margin: 0 0 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                      <?= e($member['full_name']) ?>
                    </p>
                    <p style="font-size: 11px; color: var(--muted); margin: 0 0 2px;">
                      <?= e($member['member_code']) ?>
                    </p>
                    <p style="font-size: 11px; color: var(--muted); margin: 0;">
                      Ends: <?= e($member['membership_end_date']) ?>
                      <?php if ($hasAttendance): ?>
                        · <span style="color: var(--subtle);"><?= e((string) $attendanceCount) ?> check-in(s)</span>
                      <?php endif; ?>
                    </p>
                  </div>
                  <span class="<?= e($statusClass) ?>"><?= e($status) ?></span>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                  <a href="<?= e(url('/members/edit') . '?id=' . (string) $member['id']) ?>" class="btn-primary" style="height: 38px; font-size: 11px;">Edit</a>
                  <a href="<?= e(url('/members/qr')   . '?id=' . (string) $member['id']) ?>" class="btn-ghost"   style="height: 38px; font-size: 11px;">QR Code</a>
                </div>
                <?php if ($hasAttendance): ?>
                  <form action="<?= e(url('/members/force-delete')) ?>" method="post" style="margin-top: 8px;">
                    <input type="hidden" name="_csrf"    value="<?= e($csrfToken) ?>">
                    <input type="hidden" name="id"       value="<?= e((string) $member['id']) ?>">
                    <input type="hidden" name="confirm"  value="FORCE_DELETE">
                    <button
                      type="submit"
                      class="btn-danger"
                      style="width: 100%; height: 38px; font-size: 11px;"
                      onclick="return confirm('⚠️ FORCE DELETE\n\nThis will permanently delete &quot;<?= e(addslashes((string) $member['full_name'])) ?>&quot; AND all <?= e((string) $attendanceCount) ?> attendance record(s).\n\nThis cannot be undone. Continue?');"
                    >Force Delete (<?= e((string) $attendanceCount) ?> records)</button>
                  </form>
                <?php else: ?>
                  <form action="<?= e(url('/members/delete')) ?>" method="post" style="margin-top: 8px;">
                    <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                    <input type="hidden" name="id"   value="<?= e((string) $member['id']) ?>">
                    <button
                      type="submit"
                      class="btn-danger"
                      style="width: 100%; height: 38px; font-size: 11px;"
                      onclick="return confirm('Delete this member? This action cannot be undone.');"
                    >Delete</button>
                  </form>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
        <!-- Desktop table (hidden on mobile) -->
        <div style="overflow-x: auto;" class="hidden md:block">
          <table class="data-table" style="min-width: 620px;">
            <thead>
              <tr>
                <th>Photo</th>
                <th>Member</th>
                <th>Membership End</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (count($members) === 0): ?>
                <tr><td colspan="5" style="text-align: center; padding: 40px; color: var(--muted);">No members found.</td></tr>
              <?php else: ?>
                <?php foreach ($members as $member): ?>
                  <?php
                  $isActive        = (new DateTimeImmutable((string) $member['membership_end_date'])) >= new DateTimeImmutable('today');
                  $status          = $isActive ? 'Active' : 'Expired';
                  $statusClass     = $isActive ? 'stat-badge stat-badge-ok' : 'stat-badge stat-badge-danger';
                  $photoSrc        = !empty($member['photo_path']) ? url((string) $member['photo_path']) : url('/assets/img/placeholder-member.svg');
                  $attendanceCount = (int) ($member['attendance_count'] ?? 0);
                  $hasAttendance   = $attendanceCount > 0;
                  ?>
                  <tr>
                    <td style="width: 64px; padding: 10px 12px 10px 16px;">
                      <div style="position: relative; width: 52px; height: 52px;">
                        <img
                          src="<?= e($photoSrc) ?>"
                          alt="<?= e($member['full_name']) ?>"
                          data-fullsrc="<?= e($photoSrc) ?>"
                          data-name="<?= e($member['full_name']) ?>"
                          class="member-photo-thumb"
                          style="width: 52px; height: 52px; border-radius: 2px; object-fit: cover; border: 1px solid var(--border); cursor: pointer; display: block; transition: border-color 0.15s, opacity 0.15s;"
                          title="Click to view full photo"
                        >
                        <div style="
                          position: absolute; inset: 0;
                          background: rgba(0,0,0,0.45); border-radius: 2px;
                          display: flex; align-items: center; justify-content: center;
                          opacity: 0; transition: opacity 0.15s; pointer-events: none;
                        " class="photo-overlay">
                          <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                            <circle cx="8" cy="8" r="4" stroke="white" stroke-width="1.5"/>
                            <circle cx="8" cy="8" r="1.5" fill="white"/>
                          </svg>
                        </div>
                      </div>
                    </td>
                    <td>
                      <span style="font-size: 13px; font-weight: 500; color: var(--light); display: block;">
                        <?= e($member['full_name']) ?>
                      </span>
                      <span style="font-size: 11px; color: var(--muted);"><?= e($member['member_code']) ?></span>
                      <?php if ($hasAttendance): ?>
                        <span style="display: block; font-size: 10px; color: var(--muted); margin-top: 2px;">
                          <?= e((string) $attendanceCount) ?> check-in(s)
                        </span>
                      <?php endif; ?>
                    </td>
                    <td style="color: var(--dim); font-size: 13px;"><?= e($member['membership_end_date']) ?></td>
                    <td><span class="<?= e($statusClass) ?>"><?= e($status) ?></span></td>
                    <td>
                      <div style="display: flex; gap: 6px; flex-wrap: wrap; align-items: center;">
                        <a href="<?= e(url('/members/edit') . '?id=' . (string) $member['id']) ?>" class="btn-primary" style="height: 32px; padding: 0 12px; font-size: 11px;">Edit</a>
                        <a href="<?= e(url('/members/qr')   . '?id=' . (string) $member['id']) ?>" class="btn-ghost"   style="height: 32px; padding: 0 12px; font-size: 11px;">QR</a>
                        <?php if ($hasAttendance): ?>
                          <form action="<?= e(url('/members/force-delete')) ?>" method="post" style="margin: 0;">
                            <input type="hidden" name="_csrf"   value="<?= e($csrfToken) ?>">
                            <input type="hidden" name="id"      value="<?= e((string) $member['id']) ?>">
                            <input type="hidden" name="confirm" value="FORCE_DELETE">
                            <button
                              type="submit"
                              class="btn-danger"
                              style="height: 32px; padding: 0 12px; font-size: 11px;"
                              title="Deletes member + <?= e((string) $attendanceCount) ?> attendance record(s)"
                              onclick="return confirm('⚠️ FORCE DELETE\n\nThis will permanently delete &quot;<?= e(addslashes((string) $member['full_name'])) ?>&quot; AND all <?= e((string) $attendanceCount) ?> attendance record(s).\n\nThis cannot be undone. Continue?');"
                            >Force Delete</button>
                          </form>
                        <?php else: ?>
                          <form action="<?= e(url('/members/delete')) ?>" method="post" style="margin: 0;">
                            <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                            <input type="hidden" name="id"   value="<?= e((string) $member['id']) ?>">
                            <button
                              type="submit"
                              class="btn-danger"
                              style="height: 32px; padding: 0 12px; font-size: 11px;"
                              onclick="return confirm('Delete this member? This action cannot be undone.');"
                            >Delete</button>
                          </form>
                        <?php endif; ?>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Lightbox script -->
<script nonce="<?= e(csp_nonce()) ?>">
(function () {
  var lightbox  = document.getElementById('photoLightbox');
  var lbImg     = document.getElementById('lightboxImg');
  var lbName    = document.getElementById('lightboxName');
  var lbClose   = document.getElementById('lightboxClose');

  function openLightbox(src, name) {
    lbImg.src = src;
    lbName.textContent = name || '';
    lightbox.style.display = 'flex';
    document.body.style.overflow = 'hidden';
  }

  function closeLightbox() {
    lightbox.style.display = 'none';
    document.body.style.overflow = '';
    lbImg.src = '';
  }

  document.querySelectorAll('.member-photo-thumb').forEach(function (img) {
    img.addEventListener('click', function () {
      openLightbox(img.dataset.fullsrc || img.src, img.dataset.name || '');
    });
    // Hover overlay effect
    var wrapper = img.parentElement;
    var overlay = wrapper ? wrapper.querySelector('.photo-overlay') : null;
    if (overlay) {
      wrapper.addEventListener('mouseenter', function () { overlay.style.opacity = '1'; });
      wrapper.addEventListener('mouseleave', function () { overlay.style.opacity = '0'; });
    }
  });

  if (lbClose)  lbClose.addEventListener('click', closeLightbox);
  lightbox.addEventListener('click', function (e) {
    if (e.target === lightbox) closeLightbox();
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeLightbox();
  });
})();
</script>

<?php require __DIR__ . '/../partials/foot.php'; ?>
