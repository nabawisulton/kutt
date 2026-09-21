<?php
/**
 * Login screen - KUTT SUKA MAKMUR (legacy design preserved).
 * Variables: $title, $error, $csrfToken
 */

use App\Core\Csrf;

$brandName = \App\Models\Setting::get('brandName', config('app.name'));
$logoIcon = \App\Models\Setting::get('logoIcon', config('app.logo_icon'));
$logoImage = (string) \App\Models\Setting::get('logoImage', '');
?>
<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50 scroll-smooth">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($title ?? 'Login') ?> - <?= e($brandName) ?></title>
  <?php if (is_file(BASE_PATH . '/public/favicon.ico')): ?>
    <link rel="icon" href="/favicon.ico" sizes="any">
  <?php else: ?>
    <link rel="icon" href="data:,">
  <?php endif; ?>
  <?php if (is_file(BASE_PATH . '/public/apple-touch-icon.png')): ?>
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
  <?php endif; ?>

  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <script>
    if (typeof tailwind !== 'undefined') {
      tailwind.config = {
        darkMode: 'class',
        theme: {
          extend: {
            colors: {
              brand: { 50: '#f0fdf4', 100: '#dcfce7', 500: '#2fae60', 600: '#0b7a3e', 700: '#085c2e', gold: '#ffc107' },
              'kutt-primary': 'var(--kutt-primary)',
              'kutt-dark': 'var(--kutt-dark)',
              'kutt-gold': 'var(--kutt-gold)'
            },
            fontFamily: { sans: ['Inter', 'sans-serif'], display: ['Poppins', 'sans-serif'] }
          }
        }
      };
    }
  </script>

  <style id="cms-color-vars">
    :root {
      --kutt-primary: <?= e(\App\Models\Setting::get('colorPrimary', '#0b7a3e')) ?>;
      --kutt-dark: #052e1a;
      --kutt-gold: #ffc107;
    }
  </style>
</head>

<body class="h-full font-sans text-slate-800 dark:text-slate-100 antialiased">

  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-brand-700 via-brand-600 to-emerald-800 px-4">
    <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl w-full max-w-sm p-6 space-y-5">
      <div class="text-center space-y-1">
        <div class="w-12 h-12 mx-auto rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-center shadow-md overflow-hidden">
          <?php if ($logoImage !== ''): ?>
            <img src="<?= e($logoImage) ?>" alt="Logo" class="w-full h-full object-contain">
          <?php else: ?>
            <i class="<?= e($logoIcon) ?> text-xl text-brand-600 dark:text-brand-500"></i>
          <?php endif; ?>
        </div>
        <h2 class="font-display text-base font-bold text-slate-800 dark:text-slate-100">Login Portal KUTT</h2>
        <p class="text-[11px] text-slate-500">Masuk sesuai akun Anda: Admin, Ketua, Bendahara, atau Staff</p>
      </div>
      <form method="post" action="/login" class="space-y-3">
        <?= Csrf::field() ?>
        <div>
          <label class="text-[11px] font-semibold text-slate-600">Username / Email</label>
          <input type="text" name="username" required placeholder="contoh: bendahara"
            class="w-full mt-1 px-3 py-2 text-xs border rounded-xl focus:ring-2 focus:ring-brand-600">
        </div>
        <div>
          <label class="text-[11px] font-semibold text-slate-600">Password</label>
          <input type="password" name="password" required placeholder="••••••••"
            class="w-full mt-1 px-3 py-2 text-xs border rounded-xl focus:ring-2 focus:ring-brand-600">
        </div>
        <?php if (!empty($error)): ?>
          <p class="text-[11px] text-red-600 font-semibold"><?= e($error) ?></p>
        <?php endif; ?>
        <button type="submit"
          class="w-full py-2.5 bg-brand-600 hover:bg-brand-700 text-white rounded-xl font-bold text-xs shadow transition">Masuk
          Dashboard</button>
      </form>
      <div class="border-t pt-3 text-[10px] text-slate-400 text-center leading-relaxed">
        Hubungi Admin KUTT
      </div>
    </div>
  </div>

</body>

</html>
