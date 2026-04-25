<?php

declare(strict_types=1);

$appName = (string) \App\Core\Config::get('APP_NAME', 'Gym Attendance Checker');
$titleText = isset($title) ? $title . ' — ' . $appName : $appName;
$isDashboard = !empty($dashboardShell) || (isset($title) && $title === 'Dashboard');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($titleText) ?></title>
  <meta name="csp-nonce" content="<?= e(csp_nonce()) ?>">
  <script nonce="<?= e(csp_nonce()) ?>" src="https://cdn.tailwindcss.com"></script>
  <script nonce="<?= e(csp_nonce()) ?>">
    tailwind.config = {
      theme: {
        screens: {
          xs: '360px', sm: '640px', md: '768px',
          lg: '1024px', xl: '1280px', '2xl': '1536px'
        },
        extend: {
          fontFamily: {
            display: ['"Bebas Neue"', 'sans-serif'],
            body: ['"DM Sans"', 'sans-serif']
          },
          colors: {
            gym: {
              bg:       '#080808',
              surface:  '#111111',
              raised:   '#1a1a1a',
              border:   '#2a2a2a',
              line:     '#383838',
              muted:    '#555555',
              subtle:   '#888888',
              dim:      '#aaaaaa',
              light:    '#cccccc',
              near:     '#eeeeee',
              white:    '#ffffff',
            }
          },
          letterSpacing: {
            widest2: '0.25em',
          }
        }
      }
    };
  </script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; }

    :root {
      --bg:       #080808;
      --surface:  #111111;
      --raised:   #1a1a1a;
      --border:   #2a2a2a;
      --line:     #383838;
      --muted:    #555555;
      --subtle:   #888888;
      --dim:      #aaaaaa;
      --light:    #cccccc;
      --near:     #eeeeee;
      --white:    #ffffff;
    }

    html, body {
      background-color: var(--bg);
      color: var(--white);
      font-family: 'DM Sans', sans-serif;
      font-size: 15px;
      line-height: 1.6;
      min-height: 100vh;
      -webkit-font-smoothing: antialiased;
    }

    /* Noise texture overlay for depth */
    body::before {
      content: '';
      position: fixed;
      inset: 0;
      background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.85' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.025'/%3E%3C/svg%3E");
      pointer-events: none;
      z-index: 0;
    }

    body > * { position: relative; z-index: 1; }

    /* Scrollbar */
    ::-webkit-scrollbar { width: 4px; }
    ::-webkit-scrollbar-track { background: var(--bg); }
    ::-webkit-scrollbar-thumb { background: var(--muted); border-radius: 2px; }

    /* Focus */
    *:focus-visible {
      outline: 1px solid var(--white);
      outline-offset: 2px;
    }

    /* Card system */
    .card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 2px;
    }
    .card-raised {
      background: var(--raised);
      border: 1px solid var(--line);
      border-radius: 2px;
    }

    /* Button system */
    .btn-primary {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      height: 44px;
      padding: 0 20px;
      background: var(--white);
      color: var(--bg);
      font-family: 'DM Sans', sans-serif;
      font-size: 13px;
      font-weight: 600;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      border: none;
      border-radius: 2px;
      cursor: pointer;
      transition: background 0.15s, transform 0.1s;
      text-decoration: none;
    }
    .btn-primary:hover { background: var(--near); }
    .btn-primary:active { transform: scale(0.98); }

    .btn-ghost {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      height: 44px;
      padding: 0 20px;
      background: transparent;
      color: var(--light);
      font-family: 'DM Sans', sans-serif;
      font-size: 13px;
      font-weight: 500;
      letter-spacing: 0.06em;
      text-transform: uppercase;
      border: 1px solid var(--line);
      border-radius: 2px;
      cursor: pointer;
      transition: border-color 0.15s, color 0.15s, background 0.15s;
      text-decoration: none;
    }
    .btn-ghost:hover {
      border-color: var(--subtle);
      color: var(--white);
      background: rgba(255,255,255,0.04);
    }

    .btn-danger {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      height: 44px;
      padding: 0 20px;
      background: transparent;
      color: #f87171;
      font-family: 'DM Sans', sans-serif;
      font-size: 13px;
      font-weight: 600;
      letter-spacing: 0.06em;
      text-transform: uppercase;
      border: 1px solid rgba(248,113,113,0.3);
      border-radius: 2px;
      cursor: pointer;
      transition: background 0.15s, border-color 0.15s;
      text-decoration: none;
    }
    .btn-danger:hover {
      background: rgba(248,113,113,0.08);
      border-color: rgba(248,113,113,0.5);
    }

    /* Form inputs */
    .input {
      width: 100%;
      height: 44px;
      padding: 0 14px;
      background: var(--bg);
      color: var(--white);
      border: 1px solid var(--border);
      border-radius: 2px;
      font-family: 'DM Sans', sans-serif;
      font-size: 14px;
      transition: border-color 0.15s;
      outline: none;
    }
    .input:hover { border-color: var(--muted); }
    .input:focus { border-color: var(--subtle); }
    .input::placeholder { color: var(--muted); }
    select.input { cursor: pointer; }
    select.input option { background: var(--surface); }

    textarea.input {
      height: auto;
      padding: 12px 14px;
      resize: vertical;
    }

    /* Label */
    .label {
      display: block;
      font-size: 11px;
      font-weight: 600;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      color: var(--subtle);
      margin-bottom: 6px;
    }

    /* Section header rule */
    .section-rule {
      display: flex;
      align-items: center;
      gap: 16px;
      margin-bottom: 24px;
    }
    .section-rule::after {
      content: '';
      flex: 1;
      height: 1px;
      background: var(--border);
    }

    /* Stat badge */
    .stat-badge {
      display: inline-block;
      padding: 3px 10px;
      font-size: 11px;
      font-weight: 600;
      letter-spacing: 0.1em;
      text-transform: uppercase;
      border-radius: 1px;
    }
    .stat-badge-ok {
      background: rgba(255,255,255,0.08);
      color: var(--near);
      border: 1px solid rgba(255,255,255,0.15);
    }
    .stat-badge-warn {
      background: rgba(255,255,255,0.04);
      color: var(--dim);
      border: 1px solid var(--border);
    }
    .stat-badge-danger {
      background: rgba(248,113,113,0.06);
      color: #f87171;
      border: 1px solid rgba(248,113,113,0.2);
    }

    /* Status dot */
    .status-dot {
      display: inline-block;
      width: 6px;
      height: 6px;
      border-radius: 50%;
      vertical-align: middle;
      margin-right: 6px;
    }

    /* Table */
    .data-table { width: 100%; border-collapse: collapse; }
    .data-table th {
      padding: 10px 16px;
      text-align: left;
      font-size: 10px;
      font-weight: 600;
      letter-spacing: 0.14em;
      text-transform: uppercase;
      color: var(--muted);
      border-bottom: 1px solid var(--border);
    }
    .data-table td {
      padding: 14px 16px;
      font-size: 13px;
      color: var(--light);
      border-bottom: 1px solid var(--border);
    }
    .data-table tr:hover td { background: rgba(255,255,255,0.02); }
    .data-table tr:last-child td { border-bottom: none; }

    /* Flash messages */
    .flash-success {
      margin-bottom: 16px;
      padding: 12px 16px;
      background: rgba(255,255,255,0.05);
      border: 1px solid rgba(255,255,255,0.12);
      border-left: 3px solid var(--white);
      font-size: 13px;
      color: var(--near);
      border-radius: 2px;
    }
    .flash-error {
      margin-bottom: 16px;
      padding: 12px 16px;
      background: rgba(248,113,113,0.05);
      border: 1px solid rgba(248,113,113,0.2);
      border-left: 3px solid #f87171;
      font-size: 13px;
      color: #fca5a5;
      border-radius: 2px;
    }

    /* Page enter animation */
    .page-enter {
      animation: pageEnter 0.3s ease both;
    }
    @keyframes pageEnter {
      from { opacity: 0; transform: translateY(6px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    /* Mobile touch */
    button, [role="button"], input, select, textarea, a {
      touch-action: manipulation;
    }
  </style>
</head>
<body class="font-body">
