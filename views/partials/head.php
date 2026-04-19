<?php

declare(strict_types=1);

$appName = (string) \App\Core\Config::get('APP_NAME', 'Gym Attendance Checker');
$titleText = isset($title) ? $title . ' | ' . $appName : $appName;
$isDashboard = !empty($dashboardShell) || (isset($title) && $title === 'Dashboard');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($titleText) ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        screens: {
          xs: '360px',
          sm: '640px',
          md: '768px',
          lg: '1024px',
          xl: '1280px',
          '2xl': '1536px'
        },
        extend: {
          fontFamily: {
            display: ['\"Space Grotesk\"', 'sans-serif'],
            body: ['\"Manrope\"', 'sans-serif']
          },
          colors: {
            brand: {
              50: '#f0fdf4',
              100: '#dcfce7',
              500: '#16a34a',
              600: '#15803d',
              800: '#166534'
            },
            accent: {
              500: '#f59e0b',
              600: '#d97706'
            }
          },
          boxShadow: {
            glow: '0 10px 35px rgba(22, 163, 74, 0.25)'
          }
        }
      }
    };
  </script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
  <style>
    body {
      background:
        radial-gradient(circle at 12% 8%, rgba(245, 158, 11, 0.15), transparent 28%),
        radial-gradient(circle at 85% 78%, rgba(22, 163, 74, 0.14), transparent 34%),
        linear-gradient(160deg, #f8fafc 0%, #ecfeff 45%, #f0fdf4 100%);
      min-height: 100vh;
    }
    body.dashboard-theme {
      background:
        radial-gradient(circle at 18% 12%, rgba(59, 130, 246, 0.18), transparent 32%),
        radial-gradient(circle at 82% 16%, rgba(236, 72, 153, 0.14), transparent 28%),
        radial-gradient(circle at 60% 78%, rgba(16, 185, 129, 0.12), transparent 32%),
        linear-gradient(165deg, #05070c 0%, #090c13 40%, #0a0f1c 100%);
    }
    .card {
      backdrop-filter: blur(5px);
      background: rgba(255, 255, 255, 0.9);
      border: 1px solid rgba(255, 255, 255, 0.6);
    }
    .fade-up {
      animation: fadeUp 0.5s ease both;
    }
    button,
    [role="button"],
    input,
    select,
    textarea,
    a {
      touch-action: manipulation;
    }
    button:focus-visible,
    a:focus-visible,
    input:focus-visible,
    select:focus-visible,
    textarea:focus-visible {
      outline: 2px solid rgba(34, 211, 238, 0.9);
      outline-offset: 2px;
    }
    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(8px); }
      to { opacity: 1; transform: translateY(0); }
    }
  </style>
</head>
<body class="font-body text-[15px] leading-relaxed <?= $isDashboard ? 'dashboard-theme text-slate-100' : 'text-slate-900' ?>">
