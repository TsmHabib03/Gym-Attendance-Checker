<?php

declare(strict_types=1);

$auth = \App\Core\Auth::user();
$isDashboard = !empty($dashboardShell) || (isset($title) && $title === 'Dashboard');
$currentPath = \App\Core\Request::path();

$navLinks = [
    '/dashboard'    => 'Dashboard',
    '/members'      => 'Members',
    '/attendance/scan' => 'Scan QR',
];
?>

<!-- ============================================================
  NAVIGATION HEADER
  ============================================================ -->
<header id="site-header" style="
  position: sticky; top: 0; z-index: 50;
  background: rgba(8,8,8,0.95);
  border-bottom: 1px solid var(--border);
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
">
  <div style="
    max-width: 1280px;
    margin: 0 auto;
    padding: 0 16px;
    height: 72px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 24px;
  ">

    <a href="<?= e(url('/dashboard')) ?>" style="
      display: flex;
      align-items: center;
      gap: 10px;
      text-decoration: none;
      flex-shrink: 0;
    ">
      <img
        src="<?= e(asset('img/repcorelogo1-removebg-preview.png')) ?>"
        alt="Rep Core Fitness"
        style="height: 56px; width: auto; display: block; flex-shrink: 0;"
      >
      <span style="
        font-family: 'Bebas Neue', sans-serif;
        font-size: 20px;
        letter-spacing: 0.14em;
        color: var(--white);
        line-height: 1;
      "><?= e((string) \App\Core\Config::get('APP_NAME', 'REP CORE FITNESS')) ?></span>
    </a>

    <?php if ($auth): ?>

      <!-- Desktop nav -->
      <nav style="
        display: flex;
        align-items: center;
        gap: 2px;
        flex: 1;
        justify-content: center;
      " class="hidden sm:flex">
        <?php foreach ($navLinks as $href => $label): ?>
          <?php
          $isActive = ($currentPath === $href);
          $isScan   = ($href === '/attendance/scan');
          ?>
          <?php if ($isScan): ?>
            <a href="<?= e(url($href)) ?>" style="
              display: inline-flex; align-items: center;
              height: 32px; padding: 0 14px;
              background: var(--white);
              color: var(--bg);
              font-size: 11px; font-weight: 700;
              letter-spacing: 0.12em; text-transform: uppercase;
              border-radius: 2px; text-decoration: none;
              transition: background 0.15s;
              margin-left: 8px;
            " onmouseover="this.style.background='#eee'" onmouseout="this.style.background='var(--white)'">
              <?= e($label) ?>
            </a>
          <?php else: ?>
            <a href="<?= e(url($href)) ?>" style="
              display: inline-flex; align-items: center;
              height: 32px; padding: 0 14px;
              background: <?= $isActive ? 'rgba(255,255,255,0.08)' : 'transparent' ?>;
              color: <?= $isActive ? 'var(--white)' : 'var(--dim)' ?>;
              font-size: 11px; font-weight: <?= $isActive ? '600' : '500' ?>;
              letter-spacing: 0.10em; text-transform: uppercase;
              border-radius: 2px; text-decoration: none;
              border: 1px solid <?= $isActive ? 'var(--line)' : 'transparent' ?>;
              transition: color 0.15s, background 0.15s;
            " onmouseover="this.style.color='var(--white)'; this.style.background='rgba(255,255,255,0.05)'" onmouseout="this.style.color='<?= $isActive ? 'var(--white)' : 'var(--dim)' ?>'; this.style.background='<?= $isActive ? 'rgba(255,255,255,0.08)' : 'transparent' ?>'">
              <?= e($label) ?>
            </a>
          <?php endif; ?>
        <?php endforeach; ?>
      </nav>

      <!-- Right side: username + sign out (desktop) -->
      <div style="display: flex; align-items: center; gap: 12px; flex-shrink: 0;" class="hidden sm:flex">
        <span style="font-size: 12px; color: var(--muted); letter-spacing: 0.04em;">
          <?= e((string) ($auth['username'] ?? '')) ?>
        </span>
        <form action="<?= e(url('/logout')) ?>" method="post" style="margin: 0;">
          <input type="hidden" name="_csrf" value="<?= e(\App\Core\Csrf::token()) ?>">
          <button type="submit" style="
            height: 32px; padding: 0 14px;
            background: transparent;
            color: #f87171;
            font-size: 11px; font-weight: 600;
            letter-spacing: 0.10em; text-transform: uppercase;
            border: 1px solid rgba(248,113,113,0.25);
            border-radius: 2px; cursor: pointer;
            transition: background 0.15s, border-color 0.15s;
          " onmouseover="this.style.background='rgba(248,113,113,0.08)'; this.style.borderColor='rgba(248,113,113,0.45)'" onmouseout="this.style.background='transparent'; this.style.borderColor='rgba(248,113,113,0.25)'">
            Sign Out
          </button>
        </form>
      </div>

      <!-- Mobile: hamburger -->
      <button
        type="button"
        id="mobileNavToggle"
        aria-expanded="false"
        aria-controls="mobileNavPanel"
        style="
          display: none;
          width: 40px; height: 40px;
          background: transparent;
          border: 1px solid var(--border);
          border-radius: 2px;
          cursor: pointer;
          flex-direction: column;
          align-items: center;
          justify-content: center;
          gap: 5px;
          padding: 0;
          flex-shrink: 0;
        "
        class="sm:hidden"
      >
        <span id="burgerTop"    style="display: block; width: 18px; height: 1px; background: var(--white); transition: transform 0.2s;"></span>
        <span id="burgerMid"    style="display: block; width: 18px; height: 1px; background: var(--white); transition: opacity 0.2s;"></span>
        <span id="burgerBottom" style="display: block; width: 18px; height: 1px; background: var(--white); transition: transform 0.2s;"></span>
      </button>

    <?php endif; ?>
  </div>
</header>

<?php if ($auth): ?>

<!-- Mobile nav overlay -->
<div id="mobileNavOverlay" style="
  display: none;
  position: fixed; inset: 0; z-index: 40;
  background: rgba(0,0,0,0.75);
" aria-hidden="true"></div>

<!-- Mobile nav panel -->
<aside id="mobileNavPanel" style="
  position: fixed;
  top: 0; right: 0; bottom: 0; z-index: 45;
  width: min(300px, 85vw);
  background: var(--surface);
  border-left: 1px solid var(--border);
  transform: translateX(100%);
  transition: transform 0.22s ease-out;
  display: flex;
  flex-direction: column;
  overflow-y: auto;
" aria-hidden="true">

  <!-- Panel header -->
  <div style="
    padding: 20px 20px 16px;
    border-bottom: 1px solid var(--border);
    display: flex; align-items: center; justify-content: space-between;
  ">
    <div style="display: flex; align-items: center; gap: 10px;">
      <img
        src="<?= e(asset('img/repcorelogo1-removebg-preview.png')) ?>"
        alt="Gym Rep Core"
        style="height: 28px; width: auto; display: block;"
      >
      <span style="font-family: 'Bebas Neue', sans-serif; font-size: 16px; letter-spacing: 0.1em; color: var(--white);">
        <?= e((string) \App\Core\Config::get('APP_NAME', 'Gym')) ?>
      </span>
    </div>
    <button type="button" id="mobileNavClose" style="
      width: 36px; height: 36px;
      background: var(--raised);
      border: 1px solid var(--border);
      border-radius: 2px;
      cursor: pointer;
      display: flex; align-items: center; justify-content: center;
      color: var(--dim); font-size: 16px; font-weight: 300;
    ">✕</button>
  </div>

  <!-- Nav links -->
  <nav style="padding: 12px 12px; flex: 1;">
    <?php foreach ($navLinks as $href => $label): ?>
      <?php
      $isActive = ($currentPath === $href);
      $isScan   = ($href === '/attendance/scan');
      ?>
      <a href="<?= e(url($href)) ?>" style="
        display: flex; align-items: center;
        height: 48px; padding: 0 14px;
        margin-bottom: 4px;
        background: <?= $isScan ? 'var(--white)' : ($isActive ? 'rgba(255,255,255,0.06)' : 'transparent') ?>;
        color: <?= $isScan ? 'var(--bg)' : 'var(--light)' ?>;
        font-size: 12px; font-weight: 600;
        letter-spacing: 0.10em; text-transform: uppercase;
        border: 1px solid <?= $isActive && !$isScan ? 'var(--line)' : ($isScan ? 'transparent' : 'transparent') ?>;
        border-radius: 2px; text-decoration: none;
      ">
        <?= e($label) ?>
      </a>
    <?php endforeach; ?>
  </nav>

  <!-- Sign out (mobile) -->
  <div style="padding: 12px 12px 24px; border-top: 1px solid var(--border);">
    <div style="font-size: 11px; color: var(--muted); letter-spacing: 0.06em; margin-bottom: 10px; padding: 0 2px;">
      Signed in as <?= e((string) ($auth['username'] ?? '')) ?>
    </div>
    <form action="<?= e(url('/logout')) ?>" method="post">
      <input type="hidden" name="_csrf" value="<?= e(\App\Core\Csrf::token()) ?>">
      <button type="submit" style="
        width: 100%; height: 44px;
        background: transparent;
        color: #f87171;
        font-size: 12px; font-weight: 600;
        letter-spacing: 0.10em; text-transform: uppercase;
        border: 1px solid rgba(248,113,113,0.25);
        border-radius: 2px; cursor: pointer;
      ">Sign Out</button>
    </form>
  </div>
</aside>

<script>
(function () {
  var toggle = document.getElementById('mobileNavToggle');
  var close  = document.getElementById('mobileNavClose');
  var panel  = document.getElementById('mobileNavPanel');
  var overlay = document.getElementById('mobileNavOverlay');
  var top    = document.getElementById('burgerTop');
  var mid    = document.getElementById('burgerMid');
  var bot    = document.getElementById('burgerBottom');
  if (!toggle || !panel) return;

  function open() {
    panel.style.transform = 'translateX(0)';
    overlay.style.display = 'block';
    panel.setAttribute('aria-hidden', 'false');
    toggle.setAttribute('aria-expanded', 'true');
    document.body.style.overflow = 'hidden';
    if (top && mid && bot) {
      top.style.transform = 'translateY(6px) rotate(45deg)';
      mid.style.opacity   = '0';
      bot.style.transform = 'translateY(-6px) rotate(-45deg)';
    }
  }

  function closePanel() {
    panel.style.transform = 'translateX(100%)';
    overlay.style.display = 'none';
    panel.setAttribute('aria-hidden', 'true');
    toggle.setAttribute('aria-expanded', 'false');
    document.body.style.overflow = '';
    if (top && mid && bot) {
      top.style.transform = '';
      mid.style.opacity   = '1';
      bot.style.transform = '';
    }
  }

  toggle.addEventListener('click', function () {
    panel.getAttribute('aria-hidden') === 'false' ? closePanel() : open();
  });
  if (close)   close.addEventListener('click', closePanel);
  if (overlay) overlay.addEventListener('click', closePanel);
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closePanel(); });
  window.addEventListener('resize', function () {
    if (window.innerWidth >= 640) closePanel();
  });
})();
</script>
<?php endif; ?>
