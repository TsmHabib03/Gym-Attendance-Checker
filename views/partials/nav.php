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
  NAVIGATION HEADER  — mobile-first
  ============================================================ -->
<style nonce="<?= e(csp_nonce()) ?>">
  #site-header {
    position: sticky; top: 0; z-index: 50;
    background: rgba(8,8,8,0.97);
    border-bottom: 1px solid var(--border);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    /* iOS notch: push header below status bar */
    padding-top: env(safe-area-inset-top, 0px);
  }
  #site-header-inner {
    max-width: 1280px;
    margin: 0 auto;
    padding: 0 14px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
  }
  @media (min-width: 640px) {
    #site-header-inner { height: 72px; padding: 0 20px; gap: 24px; }
  }
  /* Logo link */
  #site-logo {
    display: flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
    flex-shrink: 1;
    min-width: 0;
    overflow: hidden;
  }
  #site-logo img {
    height: 40px;
    width: auto;
    display: block;
    flex-shrink: 0;
  }
  @media (min-width: 640px) {
    #site-logo img { height: 52px; }
  }
  #site-logo-name {
    font-family: 'Bebas Neue', sans-serif;
    font-size: 16px;
    letter-spacing: 0.12em;
    color: var(--white);
    line-height: 1;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    /* Hide on very small screens to prevent squeezing hamburger */
    display: none;
  }
  @media (min-width: 400px) {
    #site-logo-name { display: block; font-size: 17px; }
  }
  @media (min-width: 640px) {
    #site-logo-name { font-size: 20px; letter-spacing: 0.14em; }
  }
  /* Desktop nav spacer */
  #site-desktop-nav {
    display: none;
    align-items: center;
    gap: 2px;
    flex: 1;
    justify-content: center;
  }
  @media (min-width: 640px) {
    #site-desktop-nav { display: flex; }
  }
  /* Desktop right (username + signout) */
  #site-desktop-user {
    display: none;
    align-items: center;
    gap: 12px;
    flex-shrink: 0;
  }
  @media (min-width: 640px) {
    #site-desktop-user { display: flex; }
  }
  /* Hamburger — mobile only */
  #mobileNavToggle {
    width: 44px; height: 44px;
    background: transparent;
    border: 1px solid var(--border);
    border-radius: 4px;
    cursor: pointer;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 5px;
    padding: 0;
    flex-shrink: 0;
    -webkit-tap-highlight-color: transparent;
  }
  @media (min-width: 640px) {
    #mobileNavToggle { display: none; }
  }
  /* Mobile panel */
  #mobileNavPanel {
    position: fixed;
    top: 0; right: 0; bottom: 0; z-index: 200;
    width: min(300px, 88vw);
    background: var(--surface);
    border-left: 1px solid var(--border);
    transform: translateX(100%);
    transition: transform 0.24s cubic-bezier(.4,0,.2,1);
    display: flex;
    flex-direction: column;
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
    /* iOS notch */
    padding-top: env(safe-area-inset-top, 0px);
  }
  #mobileNavPanel[aria-hidden="false"] { transform: translateX(0); }
  #mobileNavOverlay {
    display: none;
    position: fixed; inset: 0; z-index: 190;
    background: rgba(0,0,0,0.7);
  }
  #mobileNavOverlay.open { display: block; }
  /* Sign out safe-area bottom */
  #mobileSignOutWrap {
    padding: 12px 12px;
    padding-bottom: calc(20px + env(safe-area-inset-bottom, 12px));
    border-top: 1px solid var(--border);
  }
</style>

<header id="site-header">
  <div id="site-header-inner">

    <!-- Logo -->
    <a id="site-logo" href="<?= e(url('/dashboard')) ?>">
      <img
        src="<?= e(asset('img/repcorelogo1-removebg-preview.png')) ?>"
        alt="Rep Core Fitness"
      >
      <span id="site-logo-name"><?= e((string) \App\Core\Config::get('APP_NAME', 'REP CORE FITNESS')) ?></span>
    </a>

    <?php if ($auth): ?>

      <!-- Desktop nav links -->
      <nav id="site-desktop-nav">
        <?php foreach ($navLinks as $href => $label): ?>
          <?php
          $isActive = ($currentPath === $href);
          $isScan   = ($href === '/attendance/scan');
          ?>
          <?php if ($isScan): ?>
            <a href="<?= e(url($href)) ?>" style="
              display: inline-flex; align-items: center;
              height: 32px; padding: 0 14px;
              background: var(--white); color: var(--bg);
              font-size: 11px; font-weight: 700;
              letter-spacing: 0.12em; text-transform: uppercase;
              border-radius: 2px; text-decoration: none;
              transition: background 0.15s; margin-left: 8px;
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
            " onmouseover="this.style.color='var(--white)'; this.style.background='rgba(255,255,255,0.05)'"
               onmouseout="this.style.color='<?= $isActive ? 'var(--white)' : 'var(--dim)' ?>'; this.style.background='<?= $isActive ? 'rgba(255,255,255,0.08)' : 'transparent' ?>'">
              <?= e($label) ?>
            </a>
          <?php endif; ?>
        <?php endforeach; ?>
      </nav>

      <!-- Desktop: username + sign out -->
      <div id="site-desktop-user">
        <span style="font-size: 12px; color: var(--muted); letter-spacing: 0.04em; white-space: nowrap;">
          <?= e((string) ($auth['username'] ?? '')) ?>
        </span>
        <form action="<?= e(url('/logout')) ?>" method="post" style="margin: 0;">
          <input type="hidden" name="_csrf" value="<?= e(\App\Core\Csrf::token()) ?>">
          <button type="submit" style="
            height: 32px; padding: 0 14px;
            background: transparent; color: #f87171;
            font-size: 11px; font-weight: 600;
            letter-spacing: 0.10em; text-transform: uppercase;
            border: 1px solid rgba(248,113,113,0.25);
            border-radius: 2px; cursor: pointer;
            transition: background 0.15s, border-color 0.15s; white-space: nowrap;
          " onmouseover="this.style.background='rgba(248,113,113,0.08)'; this.style.borderColor='rgba(248,113,113,0.45)'"
             onmouseout="this.style.background='transparent'; this.style.borderColor='rgba(248,113,113,0.25)'">
            Sign Out
          </button>
        </form>
      </div>

      <!-- Mobile hamburger -->
      <button
        type="button"
        id="mobileNavToggle"
        aria-expanded="false"
        aria-controls="mobileNavPanel"
        aria-label="Open navigation"
      >
        <span id="burgerTop"    style="display: block; width: 20px; height: 1.5px; background: var(--white); border-radius: 1px; transition: transform 0.22s;"></span>
        <span id="burgerMid"    style="display: block; width: 20px; height: 1.5px; background: var(--white); border-radius: 1px; transition: opacity 0.22s;"></span>
        <span id="burgerBottom" style="display: block; width: 20px; height: 1.5px; background: var(--white); border-radius: 1px; transition: transform 0.22s;"></span>
      </button>

    <?php endif; ?>
  </div>
</header>

<?php if ($auth): ?>

<!-- Mobile overlay -->
<div id="mobileNavOverlay" aria-hidden="true"></div>

<!-- Mobile nav drawer -->
<aside id="mobileNavPanel" aria-hidden="true" role="dialog" aria-label="Navigation">

  <!-- Drawer header -->
  <div style="
    padding: 16px 16px 14px;
    border-bottom: 1px solid var(--border);
    display: flex; align-items: center; justify-content: space-between;
    flex-shrink: 0;
  ">
    <div style="display: flex; align-items: center; gap: 10px; min-width: 0;">
      <img
        src="<?= e(asset('img/repcorelogo1-removebg-preview.png')) ?>"
        alt="Rep Core Fitness"
        style="height: 32px; width: auto; display: block; flex-shrink: 0;"
      >
      <span style="
        font-family: 'Bebas Neue', sans-serif;
        font-size: 16px; letter-spacing: 0.1em;
        color: var(--white); white-space: nowrap;
        overflow: hidden; text-overflow: ellipsis;
      ">
        <?= e((string) \App\Core\Config::get('APP_NAME', 'REP CORE')) ?>
      </span>
    </div>
    <button type="button" id="mobileNavClose" aria-label="Close navigation" style="
      width: 40px; height: 40px; flex-shrink: 0;
      background: var(--raised); border: 1px solid var(--line);
      border-radius: 4px; cursor: pointer;
      display: flex; align-items: center; justify-content: center;
      color: var(--dim); font-size: 18px; line-height: 1;
      -webkit-tap-highlight-color: transparent;
    ">✕</button>
  </div>

  <!-- Signed-in-as badge -->
  <div style="
    padding: 10px 16px 8px;
    font-size: 11px; color: var(--muted);
    letter-spacing: 0.06em;
    border-bottom: 1px solid var(--border);
    flex-shrink: 0;
  ">
    <span style="color: var(--subtle);">Signed in as</span>
    <strong style="color: var(--dim); margin-left: 4px;"><?= e((string) ($auth['username'] ?? '')) ?></strong>
  </div>

  <!-- Nav links -->
  <nav style="padding: 10px 10px; flex: 1; overflow-y: auto;">
    <?php foreach ($navLinks as $href => $label): ?>
      <?php
      $isActive = ($currentPath === $href);
      $isScan   = ($href === '/attendance/scan');
      ?>
      <a href="<?= e(url($href)) ?>" style="
        display: flex; align-items: center;
        min-height: 52px; padding: 0 14px;
        margin-bottom: 4px;
        background: <?= $isScan ? 'var(--white)' : ($isActive ? 'rgba(255,255,255,0.07)' : 'transparent') ?>;
        color: <?= $isScan ? 'var(--bg)' : ($isActive ? 'var(--white)' : 'var(--light)') ?>;
        font-size: 13px; font-weight: 600;
        letter-spacing: 0.10em; text-transform: uppercase;
        border: 1px solid <?= $isActive && !$isScan ? 'var(--line)' : 'transparent' ?>;
        border-radius: 4px; text-decoration: none;
        -webkit-tap-highlight-color: transparent;
      ">
        <?= e($label) ?>
      </a>
    <?php endforeach; ?>
  </nav>

  <!-- Sign out — always visible at bottom with safe area -->
  <div id="mobileSignOutWrap">
    <form action="<?= e(url('/logout')) ?>" method="post">
      <input type="hidden" name="_csrf" value="<?= e(\App\Core\Csrf::token()) ?>">
      <button type="submit" style="
        width: 100%; min-height: 52px;
        background: rgba(248,113,113,0.06);
        color: #f87171;
        font-size: 13px; font-weight: 700;
        letter-spacing: 0.10em; text-transform: uppercase;
        border: 1px solid rgba(248,113,113,0.3);
        border-radius: 4px; cursor: pointer;
        -webkit-tap-highlight-color: transparent;
      ">Sign Out</button>
    </form>
  </div>
</aside>

<script nonce="<?= e(csp_nonce()) ?>">
(function () {
  var toggle  = document.getElementById('mobileNavToggle');
  var close   = document.getElementById('mobileNavClose');
  var panel   = document.getElementById('mobileNavPanel');
  var overlay = document.getElementById('mobileNavOverlay');
  var top     = document.getElementById('burgerTop');
  var mid     = document.getElementById('burgerMid');
  var bot     = document.getElementById('burgerBottom');
  if (!toggle || !panel || !overlay) return;

  function openNav() {
    panel.setAttribute('aria-hidden', 'false');
    overlay.classList.add('open');
    toggle.setAttribute('aria-expanded', 'true');
    toggle.setAttribute('aria-label', 'Close navigation');
    document.body.style.overflow = 'hidden';
    if (top && mid && bot) {
      top.style.transform = 'translateY(6.5px) rotate(45deg)';
      mid.style.opacity   = '0';
      bot.style.transform = 'translateY(-6.5px) rotate(-45deg)';
    }
    /* Trap focus: move focus to close button */
    if (close) { setTimeout(function(){ close.focus(); }, 50); }
  }

  function closeNav() {
    panel.setAttribute('aria-hidden', 'true');
    overlay.classList.remove('open');
    toggle.setAttribute('aria-expanded', 'false');
    toggle.setAttribute('aria-label', 'Open navigation');
    document.body.style.overflow = '';
    if (top && mid && bot) {
      top.style.transform = '';
      mid.style.opacity   = '1';
      bot.style.transform = '';
    }
    toggle.focus();
  }

  toggle.addEventListener('click', function () {
    panel.getAttribute('aria-hidden') === 'false' ? closeNav() : openNav();
  });
  if (close)   close.addEventListener('click', closeNav);
  overlay.addEventListener('click', closeNav);
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeNav(); });
  window.addEventListener('resize', function () {
    if (window.innerWidth >= 640) closeNav();
  });
})();
</script>
<?php endif; ?>
