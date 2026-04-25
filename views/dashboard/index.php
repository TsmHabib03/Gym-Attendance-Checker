<?php

declare(strict_types=1);

$title = 'Dashboard';
$memberStats     = $overview['members'];
$attendanceStats = $overview['attendance_today'];
$recentLogs      = $overview['recent_logs'];

$photoCaptureOn = in_array(strtolower((string) ($settings['photo_capture_enabled'] ?? 'true')), ['1', 'true', 'yes', 'on'], true);
$expiryDays     = (string) ($settings['expiry_reminder_days'] ?? \App\Core\Config::get('EXPIRY_REMINDER_DAYS', '7'));

$todayAccepted = (int) $attendanceStats['accepted'];
$todayDenied   = (int) ($attendanceStats['expired_denied'] + $attendanceStats['duplicate_denied']);
$todayTotal    = $todayAccepted + $todayDenied;
$liveUpdatedAt = (new DateTimeImmutable())->format('H:i:s');

require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/nav.php';
?>

<!-- ============================================================
     DASHBOARD PAGE
     ============================================================ -->
<div class="page-enter" style="max-width: 1280px; margin: 0 auto; padding: 32px 16px 64px;">

  <!-- Page header -->
  <div style="margin-bottom: 32px; display: flex; align-items: flex-end; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
    <div>
      <!-- LOGO PLACEHOLDER: optional secondary logo here -->
      <p style="font-size: 11px; letter-spacing: 0.14em; color: var(--muted); text-transform: uppercase; margin: 0 0 6px;">
        Control Room
      </p>
      <h1 style="
        font-family: 'Bebas Neue', sans-serif;
        font-size: clamp(32px, 5vw, 48px);
        letter-spacing: 0.10em;
        color: var(--white);
        margin: 0;
        line-height: 1;
      ">Dashboard</h1>
    </div>
    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
      <a href="<?= e(url('/members/create')) ?>" class="btn-primary" style="height: 38px; font-size: 11px;">
        + Add Member
      </a>
      <a href="<?= e(url('/attendance/scan')) ?>" class="btn-ghost" style="height: 38px; font-size: 11px;">
        Scan QR
      </a>
    </div>
  </div>

  <!-- Flash -->
  <?php
  $success = flash('success');
  $error   = flash('error');
  if ($success): ?>
    <div class="flash-success"><?= e($success) ?></div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="flash-error"><?= e($error) ?></div>
  <?php endif; ?>

  <!-- ── STAT GRID ───────────────────────────────────────── -->
  <div style="
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 1px;
    background: var(--border);
    border: 1px solid var(--border);
    border-radius: 2px;
    overflow: hidden;
    margin-bottom: 24px;
  ">
    <?php
    $stats = [
      ['label' => 'Total Members',   'value' => $memberStats['total'],   'id' => 'totalMembersValue',   'sub' => 'Registered'],
      ['label' => 'Active Members',  'value' => $memberStats['active'],  'id' => 'activeMembersValue',  'sub' => 'Ready to scan'],
      ['label' => 'Expired',         'value' => $memberStats['expired'], 'id' => 'expiredMembersValue', 'sub' => 'Needs renewal'],
      ['label' => 'Accepted Today',  'value' => $todayAccepted,           'id' => 'acceptedTodayValue',  'sub' => 'Successful'],
      ['label' => 'Denied Today',    'value' => $todayDenied,             'id' => 'deniedTodayValue',    'sub' => 'Blocked'],
    ];
    foreach ($stats as $s): ?>
      <div style="
        background: var(--surface);
        padding: 20px 18px;
        display: flex;
        flex-direction: column;
        gap: 4px;
      ">
        <span style="font-size: 10px; font-weight: 600; letter-spacing: 0.14em; text-transform: uppercase; color: var(--muted);">
          <?= e($s['label']) ?>
        </span>
        <span id="<?= e($s['id']) ?>" style="
          font-family: 'Bebas Neue', sans-serif;
          font-size: 40px;
          color: var(--white);
          line-height: 1;
          letter-spacing: 0.04em;
        "><?= e((string) $s['value']) ?></span>
        <span style="font-size: 11px; color: var(--muted);"><?= e($s['sub']) ?></span>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- ── MAIN GRID: Activity chart + Settings ────────────── -->
  <div style="
    display: grid;
    grid-template-columns: 1fr;
    gap: 16px;
    margin-bottom: 24px;
  " class="lg:grid-cols-[1fr_320px]">

    <!-- Activity panel -->
    <div style="background: var(--surface); border: 1px solid var(--border); border-radius: 2px; overflow: hidden;">
      <div style="
        padding: 20px 24px;
        border-bottom: 1px solid var(--border);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
      ">
        <div>
          <h2 style="
            font-family: 'Bebas Neue', sans-serif;
            font-size: 20px; letter-spacing: 0.12em;
            color: var(--white); margin: 0 0 2px;
          ">Today's Activity</h2>
          <p style="font-size: 12px; color: var(--muted); margin: 0;">
            Total scan attempts: <strong id="totalScanAttemptsValue" style="color: var(--light);"><?= e((string) $todayTotal) ?></strong>
          </p>
        </div>
        <span style="font-size: 11px; color: var(--muted); letter-spacing: 0.06em;">
          Live · <span id="dashboardLiveUpdated"><?= e($liveUpdatedAt) ?></span>
        </span>
      </div>

      <!-- Bar chart -->
      <div style="padding: 24px; overflow-x: auto;">
        <?php
        $bars = [
          ['key' => 'accepted', 'label' => 'Accepted', 'value' => $todayAccepted],
          ['key' => 'denied',   'label' => 'Denied',   'value' => $todayDenied],
          ['key' => 'active',   'label' => 'Active',   'value' => $memberStats['active']],
          ['key' => 'expired',  'label' => 'Expired',  'value' => $memberStats['expired']],
          ['key' => 'total',    'label' => 'Total',    'value' => $memberStats['total']],
        ];
        $maxVal = max(array_column($bars, 'value') ?: [1]);
        $maxVal = max($maxVal, 1);
        ?>
        <div style="display: flex; align-items: flex-end; gap: 12px; height: 120px;">
          <?php foreach ($bars as $bar): ?>
            <?php $heightPx = max(4, (int) round(($bar['value'] / $maxVal) * 100)); ?>
            <div style="flex: 1; display: flex; flex-direction: column; align-items: center; gap: 8px; justify-content: flex-end; height: 100%;">
              <span id="barValue-<?= e($bar['key']) ?>" style="font-size: 12px; font-weight: 600; color: var(--light);">
                <?= e((string) $bar['value']) ?>
              </span>
              <div
                id="barFill-<?= e($bar['key']) ?>"
                style="
                  width: 100%;
                  height: <?= e((string) $heightPx) ?>px;
                  background: <?php
                    echo match($bar['key']) {
                      'accepted' => 'var(--white)',
                      'denied'   => 'var(--muted)',
                      'active'   => '#aaaaaa',
                      'expired'  => 'var(--line)',
                      default    => 'var(--raised)',
                    };
                  ?>;
                  border-radius: 1px;
                  transition: height 0.4s ease;
                "
              ></div>
              <span style="font-size: 9px; font-weight: 600; letter-spacing: 0.10em; text-transform: uppercase; color: var(--muted); text-align: center; line-height: 1.2;">
                <?= e($bar['label']) ?>
              </span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Member capacity strip -->
      <div style="
        padding: 16px 24px;
        border-top: 1px solid var(--border);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
      ">
        <div>
          <span style="font-size: 11px; color: var(--muted); letter-spacing: 0.08em; text-transform: uppercase;">
            Active ratio
          </span>
        </div>
        <?php
        $activeRatio = $memberStats['total'] > 0
          ? (int) round(($memberStats['active'] / $memberStats['total']) * 100)
          : 0;
        $activeRatio = max(0, min(100, $activeRatio));
        ?>
        <div style="flex: 1; display: flex; align-items: center; gap: 12px; min-width: 120px;">
          <div style="flex: 1; height: 3px; background: var(--border); border-radius: 2px; overflow: hidden;">
            <div
              id="capacityBar"
              data-initial-ratio="<?= e((string) $activeRatio) ?>"
              style="
                height: 100%;
                width: 0%;
                background: var(--white);
                transition: width 0.6s ease;
              "
            ></div>
          </div>
          <span id="memberCapacityRatio" style="font-size: 12px; color: var(--dim); white-space: nowrap;">
            <?= e((string) $activeRatio) ?>% active
          </span>
          <span id="memberCapacityCount" style="font-size: 12px; color: var(--muted); white-space: nowrap;">
            <?= e((string) $memberStats['active']) ?>/<?= e((string) $memberStats['total']) ?>
          </span>
        </div>
      </div>
    </div>

    <!-- Settings panel -->
    <div style="background: var(--surface); border: 1px solid var(--border); border-radius: 2px; overflow: hidden;">
      <div style="padding: 20px 24px; border-bottom: 1px solid var(--border);">
        <h2 style="
          font-family: 'Bebas Neue', sans-serif;
          font-size: 20px; letter-spacing: 0.12em;
          color: var(--white); margin: 0 0 2px;
        ">Settings</h2>
        <p style="font-size: 12px; color: var(--muted); margin: 0;">Attendance configuration</p>
      </div>

      <form action="<?= e(url('/settings')) ?>" method="post" style="padding: 24px; display: flex; flex-direction: column; gap: 20px;">
        <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">

        <!-- Photo capture toggle -->
        <div>
          <label class="label">Photo Capture</label>
          <label style="
            display: flex; align-items: center;
            gap: 12px; cursor: pointer;
            padding: 12px 14px;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 2px;
          ">
            <span style="
              position: relative; display: inline-block;
              width: 36px; height: 20px;
              flex-shrink: 0;
            ">
              <input
                type="checkbox"
                name="photo_capture_enabled"
                value="1"
                <?= $photoCaptureOn ? 'checked' : '' ?>
                id="photoToggle"
                style="opacity: 0; width: 0; height: 0; position: absolute;"
              >
              <span id="toggleTrack" style="
                display: block; width: 36px; height: 20px;
                background: <?= $photoCaptureOn ? 'var(--white)' : 'var(--border)' ?>;
                border-radius: 10px; cursor: pointer;
                transition: background 0.2s;
                position: relative;
              ">
                <span id="toggleThumb" style="
                  display: block; width: 14px; height: 14px;
                  border-radius: 50%;
                  background: <?= $photoCaptureOn ? 'var(--bg)' : 'var(--muted)' ?>;
                  position: absolute; top: 3px;
                  left: <?= $photoCaptureOn ? '19px' : '3px' ?>;
                  transition: left 0.2s, background 0.2s;
                "></span>
              </span>
            </span>
            <div>
              <span style="font-size: 13px; font-weight: 500; color: var(--light); display: block;">
                Capture photo on scan
              </span>
              <span style="font-size: 11px; color: var(--muted);">
                <?= $photoCaptureOn ? 'Enabled' : 'Disabled' ?>
              </span>
            </div>
          </label>
        </div>

        <!-- Expiry reminder days -->
        <div>
          <label for="expiryDays" class="label">Expiry Reminder Days</label>
          <input
            type="number"
            id="expiryDays"
            name="expiry_reminder_days"
            min="1" max="30"
            value="<?= e($expiryDays) ?>"
            class="input"
            required
          >
          <p style="font-size: 11px; color: var(--muted); margin: 6px 0 0;">
            Days before expiry to send reminder email
          </p>
        </div>

        <button type="submit" class="btn-primary" style="width: 100%;">Save Settings</button>
      </form>

      <!-- Quick actions -->
      <div style="padding: 0 24px 24px; border-top: 1px solid var(--border); padding-top: 20px; display: flex; flex-direction: column; gap: 8px;">
        <a href="<?= e(url('/members')) ?>" class="btn-ghost" style="width: 100%; font-size: 11px;">
          View All Members
        </a>
        <a href="<?= e(url('/members/create')) ?>" class="btn-ghost" style="width: 100%; font-size: 11px;">
          + New Member
        </a>
      </div>
    </div>
  </div>

  <!-- ── RECENT SCAN ACTIVITY ─────────────────────────────── -->
  <div style="background: var(--surface); border: 1px solid var(--border); border-radius: 2px; overflow: hidden;">
    <div style="
      padding: 20px 24px;
      border-bottom: 1px solid var(--border);
      display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;
    ">
      <div>
        <h2 style="
          font-family: 'Bebas Neue', sans-serif;
          font-size: 20px; letter-spacing: 0.12em;
          color: var(--white); margin: 0 0 2px;
        ">Recent Scan Activity</h2>
        <p style="font-size: 12px; color: var(--muted); margin: 0;">Last 30 attendance events</p>
      </div>
      <a href="<?= e(url('/attendance/scan')) ?>" class="btn-primary" style="height: 36px; font-size: 11px;">
        Open Scanner
      </a>
    </div>

    <!-- Mobile cards -->
    <div id="recentLogsCards" style="display: flex; flex-direction: column;" class="md:hidden">
      <?php if (count($recentLogs) === 0): ?>
        <div style="padding: 40px 24px; text-align: center; font-size: 13px; color: var(--muted);">
          No scan activity yet.
        </div>
      <?php else: ?>
        <?php foreach ($recentLogs as $log):
          $st = $log['status'];
          $dotColor = $st === 'accepted' ? 'var(--white)'
                    : ($st === 'expired_denied' ? '#f87171' : 'var(--muted)');
          $labelColor = $st === 'accepted' ? 'var(--light)'
                      : ($st === 'expired_denied' ? '#fca5a5' : 'var(--dim)');
        ?>
          <div style="
            padding: 14px 24px;
            border-bottom: 1px solid var(--border);
            display: flex; align-items: center; gap: 14px; justify-content: space-between;
          ">
            <div style="min-width: 0; flex: 1;">
              <p style="font-size: 13px; font-weight: 500; color: var(--light); margin: 0 0 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                <?= e($log['full_name']) ?>
              </p>
              <p style="font-size: 11px; color: var(--muted); margin: 0;"><?= e($log['member_code']) ?></p>
            </div>
            <div style="text-align: right; flex-shrink: 0;">
              <p style="font-size: 11px; color: <?= e($labelColor) ?>; margin: 0 0 2px; font-weight: 500;">
                <span style="display: inline-block; width: 6px; height: 6px; border-radius: 50%; background: <?= e($dotColor) ?>; vertical-align: middle; margin-right: 4px;"></span><?= e($st) ?>
              </p>
              <p style="font-size: 11px; color: var(--muted); margin: 0;"><?= e(substr($log['scanned_at'], 11, 5)) ?></p>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <!-- Desktop table -->
    <div style="overflow-x: auto;" class="hidden md:block">
      <table class="data-table" id="recentLogsBody" style="min-width: 600px;">
        <thead>
          <tr>
            <th>Member</th>
            <th>Status</th>
            <th>Time</th>
            <th>Note</th>
          </tr>
        </thead>
        <tbody>
          <?php if (count($recentLogs) === 0): ?>
            <tr><td colspan="4" style="text-align: center; padding: 40px; color: var(--muted);">No recent scan activity yet.</td></tr>
          <?php else: ?>
            <?php foreach ($recentLogs as $log):
              $st = $log['status'];
              $dotColor = $st === 'accepted' ? 'var(--white)'
                        : ($st === 'expired_denied' ? '#f87171' : 'var(--muted)');
              $statusColor = $st === 'accepted' ? 'var(--near)'
                           : ($st === 'expired_denied' ? '#fca5a5' : 'var(--dim)');
            ?>
              <tr>
                <td>
                  <span style="font-size: 13px; font-weight: 500; color: var(--light); display: block;"><?= e($log['full_name']) ?></span>
                  <span style="font-size: 11px; color: var(--muted);"><?= e($log['member_code']) ?></span>
                </td>
                <td>
                  <span style="
                    display: inline-flex; align-items: center; gap: 6px;
                    font-size: 11px; font-weight: 600; letter-spacing: 0.08em;
                    text-transform: uppercase;
                    color: <?= e($statusColor) ?>;
                  ">
                    <span style="width: 6px; height: 6px; border-radius: 50%; background: <?= e($dotColor) ?>; flex-shrink: 0;"></span>
                    <?= e($st) ?>
                  </span>
                </td>
                <td style="color: var(--dim); font-size: 12px;"><?= e($log['scanned_at']) ?></td>
                <td style="color: var(--muted); font-size: 12px; max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                  <?= e($log['note'] ?? '') ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<!-- ── LIVE UPDATE SCRIPT ────────────────────────────────── -->
<script nonce="<?= e(csp_nonce()) ?>">
window.DASHBOARD_LIVE_CONFIG = {
  endpoint: <?= json_encode(url('/dashboard?live=1'), JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
  refreshMs: 20000,
};
</script>
<script nonce="<?= e(csp_nonce()) ?>">
(function () {
  var config = window.DASHBOARD_LIVE_CONFIG || {};
  if (!config.endpoint) return;

  var refs = {
    totalMembers:    document.getElementById('totalMembersValue'),
    activeMembers:   document.getElementById('activeMembersValue'),
    expiredMembers:  document.getElementById('expiredMembersValue'),
    acceptedToday:   document.getElementById('acceptedTodayValue'),
    deniedToday:     document.getElementById('deniedTodayValue'),
    totalAttempts:   document.getElementById('totalScanAttemptsValue'),
    capacityRatio:   document.getElementById('memberCapacityRatio'),
    capacityCount:   document.getElementById('memberCapacityCount'),
    capacityBar:     document.getElementById('capacityBar'),
    liveUpdated:     document.getElementById('dashboardLiveUpdated'),
    logsBody:        document.getElementById('recentLogsBody'),
    logsCards:       document.getElementById('recentLogsCards'),
  };

  var barKeys = ['accepted','denied','active','expired','total'];

  function esc(s) {
    return String(s||'').replace(/[&<>"']/g, function(c){
      return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];
    });
  }
  function toInt(v) { var n=parseInt(String(v||0),10); return isNaN(n)?0:n; }

  function statusColor(st) {
    if (st==='accepted')       return 'var(--near)';
    if (st==='expired_denied') return '#fca5a5';
    return 'var(--dim)';
  }
  function dotColor(st) {
    if (st==='accepted')       return 'var(--white)';
    if (st==='expired_denied') return '#f87171';
    return 'var(--muted)';
  }

  function updateBars(vals) {
    var mx = Math.max(1, Math.max.apply(null, vals));
    barKeys.forEach(function(k, i) {
      var fill = document.getElementById('barFill-'+k);
      var val  = document.getElementById('barValue-'+k);
      if (!fill||!val) return;
      var h = Math.max(4, Math.round((vals[i]/mx)*100));
      fill.style.height = h+'px';
      val.textContent   = String(vals[i]);
    });
  }

  function renderLogs(logs) {
    if (!Array.isArray(logs)||!logs.length) return;
    if (refs.logsBody) {
      refs.logsBody.querySelector('tbody').innerHTML = logs.map(function(l) {
        var st = String(l.status||'');
        return '<tr>'
          +'<td><span style="font-size:13px;font-weight:500;color:var(--light);display:block">'+esc(l.full_name||'')+'</span>'
          +'<span style="font-size:11px;color:var(--muted)">'+esc(l.member_code||'')+'</span></td>'
          +'<td><span style="display:inline-flex;align-items:center;gap:6px;font-size:11px;font-weight:600;letter-spacing:.08em;text-transform:uppercase;color:'+statusColor(st)+'">'
          +'<span style="width:6px;height:6px;border-radius:50%;background:'+dotColor(st)+';flex-shrink:0"></span>'
          +esc(st)+'</span></td>'
          +'<td style="color:var(--dim);font-size:12px">'+esc(l.scanned_at||'')+'</td>'
          +'<td style="color:var(--muted);font-size:12px;max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">'+esc(l.note||'')+'</td>'
          +'</tr>';
      }).join('');
    }
  }

  function apply(overview, generatedAt) {
    var m  = overview.members||{};
    var a  = overview.attendance_today||{};
    var total    = toInt(m.total);
    var active   = toInt(m.active);
    var expired  = toInt(m.expired);
    var accepted = toInt(a.accepted);
    var denied   = toInt(a.expired_denied)+toInt(a.duplicate_denied);
    var ratio    = total>0 ? Math.round((active/total)*100) : 0;

    function set(el,v) { if(el) el.textContent=String(v); }
    set(refs.totalMembers,  total);
    set(refs.activeMembers, active);
    set(refs.expiredMembers,expired);
    set(refs.acceptedToday, accepted);
    set(refs.deniedToday,   denied);
    set(refs.totalAttempts, accepted+denied);
    set(refs.capacityRatio, ratio+'% active');
    set(refs.capacityCount, active+'/'+total);
    if (refs.capacityBar) refs.capacityBar.style.width = ratio+'%';
    if (refs.liveUpdated) {
      var d = new Date(String(generatedAt||'').replace(' ','T'));
      refs.liveUpdated.textContent = isNaN(d)?generatedAt:d.toLocaleTimeString([],{hour:'2-digit',minute:'2-digit',second:'2-digit'});
    }
    updateBars([accepted, denied, active, expired, total]);
    renderLogs(overview.recent_logs||[]);
  }

  async function fetchLive() {
    try {
      var r = await fetch(config.endpoint, {
        headers: {'Accept':'application/json','X-Requested-With':'XMLHttpRequest'},
        cache: 'no-store'
      });
      if (!r.ok) return;
      var p = await r.json();
      if (p&&p.ok&&p.data&&p.data.overview) apply(p.data.overview, p.data.generated_at||'');
    } catch(e) {}
  }

  var ms = Math.max(5000, parseInt(String(config.refreshMs||20000),10)||20000);

  // Apply initial ratio before the first live refresh.
  if (refs.capacityBar) {
    var initialRatio = parseInt(String(refs.capacityBar.getAttribute('data-initial-ratio') || '0'), 10);
    if (isNaN(initialRatio)) initialRatio = 0;
    initialRatio = Math.max(0, Math.min(100, initialRatio));
    refs.capacityBar.style.width = initialRatio + '%';
  }

  window.setInterval(fetchLive, ms);

  // Toggle animation for settings checkbox
  var cb = document.getElementById('photoToggle');
  var track = document.getElementById('toggleTrack');
  var thumb = document.getElementById('toggleThumb');
  if (cb && track && thumb) {
    cb.addEventListener('change', function() {
      track.style.background = cb.checked ? 'var(--white)' : 'var(--border)';
      thumb.style.background = cb.checked ? 'var(--bg)'    : 'var(--muted)';
      thumb.style.left       = cb.checked ? '19px'         : '3px';
    });
  }
})();
</script>

<?php require __DIR__ . '/../partials/foot.php'; ?>
