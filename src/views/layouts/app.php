<?php

/**
 * Dashboard layout shell - KUTT SUKA MAKMUR.
 * Tahap 2: + notification bell, dark mode, menu berita/log/pengaturan, favicon,
 * dialog konfirmasi UI. Desain legacy tetap dipertahankan.
 *
 * Variables: $content, $currentUser, $csrfToken, $flash, $allowedViews,
 *            $activeView, $pageTitle, $pageSubtitle
 */

use App\Core\Csrf;

$currentUser  = $currentUser ?? null;
$allowedViews = $allowedViews ?? [];
$activeView   = $activeView ?? 'dashboard';
$flash        = $flash ?? null;

// Sidebar definition: view => [icon, label, section]
$sidebarSections = [
    'NAVIGASI UTAMA' => [
        'dashboard' => ['fa-solid fa-chart-pie', 'Dashboard Executive'],
        'members'   => ['fa-solid fa-users', 'Master Anggota'],
    ],
    'OPERASIONAL SIMPAN PINJAM' => [
        'simpanan'  => ['fa-solid fa-wallet', 'Simpanan & Tabungan'],
        'pinjaman'  => ['fa-solid fa-hand-holding-dollar', 'Pinjaman & Approval'],
        'kas'       => ['fa-solid fa-money-bill-transfer', 'Kas Masuk / Keluar'],
    ],
    'AKUNTANSI & SHU' => [
        'akuntansi' => ['fa-solid fa-book-journal-whills', 'Jurnal & Laporan'],
        'shu'       => ['fa-solid fa-calculator', 'Kalkulasi Bagi SHU'],
    ],
    'PORTAL & SISTEM' => [
        'news'      => ['fa-solid fa-newspaper', 'Berita / News'],
        'support'   => ['fa-solid fa-headset', 'Pesan Anggota'],
        'cms'       => ['fa-solid fa-globe', 'CMS Landing Page'],
        'settings'  => ['fa-solid fa-gears', 'Pengaturan'],
        'activity'  => ['fa-solid fa-clipboard-list', 'Log Aktivitas'],
        'users'     => ['fa-solid fa-user-shield', 'Kelola User'],
    ],
];

// Role ANGGOTA: menu portal mandiri (data miliknya + komunikasi).
$isAnggota = ($currentUser['role'] ?? '') === 'ANGGOTA';
if ($isAnggota) {
    $sidebarSections = [
        'PORTAL ANGGOTA' => [
            'portal'      => ['fa-solid fa-chart-pie', 'Saldo & SHU Saya'],
            'portal_chat' => ['fa-solid fa-comments', 'Pesan ke Admin'],
        ],
    ];
}

$brandName = \App\Models\Setting::get('brandName', config('app.name'));
$logoIcon = \App\Models\Setting::get('logoIcon', config('app.logo_icon'));
$colorPrimary = \App\Models\Setting::get('colorPrimary', '#0b7a3e');
$hasFavicon = is_file(BASE_PATH . '/public/favicon.ico');
?>
<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50 scroll-smooth">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle ?? 'Dashboard') ?> - <?= e($brandName) ?></title>
  <?php if ($hasFavicon): ?>
    <link rel="icon" href="/favicon.ico" sizes="any">
  <?php else: ?>
    <link rel="icon" href="data:,">
  <?php endif; ?>

  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

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
      --kutt-primary: <?= e($colorPrimary) ?>;
      --kutt-dark: #052e1a;
      --kutt-gold: #ffc107;
    }
  </style>

  <style>
    /* Anti-blur & anti-overlap mobile */
    html { -webkit-text-size-adjust: 100%; text-size-adjust: 100%; }
    body * { -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; }

    .glass-card {
      background: rgba(255, 255, 255, 0.88);
      backdrop-filter: blur(12px);
      border: 1px solid rgba(11, 122, 62, 0.12);
    }
    /* Blur besar bikin teks buram & berat di ponsel -> solid di layar kecil */
    @media (max-width: 640px) {
      .glass-card { backdrop-filter: none; background: #ffffff; }
      .dark .glass-card { background: #0f172a; }
    }

    .dark .glass-card {
      background: rgba(15, 23, 42, 0.88);
      border-color: rgba(148, 163, 184, 0.15);
    }

    .custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #CBD5E1; border-radius: 9999px; }

    #sidebar {
      transform: translateX(-100%);
      z-index: 45 !important;
      background-color: #ffffff !important;
      box-shadow: 8px 0 24px rgba(15, 23, 42, 0.18);
    }
    html.dark #sidebar, .dark #sidebar { background-color: #0f172a !important; }
    #sidebar.sidebar-open { transform: translateX(0); }
    #sidebar nav { min-height: 0; }
    #sidebarBackdrop { z-index: 44 !important; }

    @media (min-width: 1024px) {
      #sidebar {
        transform: translateX(0) !important;
        position: relative !important;
        z-index: auto !important;
        box-shadow: none;
      }
    }
  </style>

  <script>
    // Dark mode: preferensi tersimpan di localStorage (hanya UI preference).
    (function () {
      try {
        var theme = localStorage.getItem('kutt-theme');
        var dark = theme === 'dark' || (theme === null && false);
        if (dark) document.documentElement.classList.add('dark');
      } catch (e) { /* localStorage tidak tersedia */ }
    })();
  </script>
</head>

<body class="h-full font-sans text-slate-800 bg-[#F7F9F8] dark:bg-slate-900 dark:text-slate-100 antialiased">

  <!-- DASHBOARD APP -->
  <div id="app" class="min-h-screen flex flex-col">
    <!-- HEADER -->
    <header
      class="sticky top-0 z-30 h-16 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md border-b border-brand-600/10 dark:border-slate-800 px-3 sm:px-4 lg:px-6 flex items-center justify-between gap-2 shadow-sm">
      <div class="flex items-center space-x-2 sm:space-x-3 min-w-0">
        <button id="btn-toggle-sidebar" onclick="toggleSidebar()"
          class="lg:hidden p-2 rounded-lg text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 shrink-0">
          <i class="fa-solid fa-bars text-lg"></i>
        </button>
        <div class="flex items-center space-x-2.5 min-w-0">
          <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-brand-600 to-brand-500 flex items-center justify-center text-white shadow-md shadow-brand-600/20 overflow-hidden shrink-0">
            <?php $logoImage = \App\Models\Setting::get('logoImage', ''); ?>
            <?php if ($logoImage !== ''): ?>
              <img src="<?= e($logoImage) ?>" alt="Logo" class="w-full h-full object-contain">
            <?php else: ?>
              <i class="<?= e($logoIcon) ?> text-xl"></i>
            <?php endif; ?>
          </div>
          <div class="min-w-0">
            <h1 class="font-display font-bold text-sm sm:text-base leading-tight text-brand-600 dark:text-brand-500 truncate"><?= e($brandName) ?></h1>
            <p class="text-[10px] tracking-wider font-semibold text-slate-500 uppercase truncate">Koperasi Digital Enterprise</p>
          </div>
        </div>
      </div>
      <div class="hidden md:flex items-center flex-1 max-w-md mx-8">
        <?php if (in_array('members', $allowedViews, true)): ?>
        <div class="relative w-full">
          <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400"><i class="fa-solid fa-magnifying-glass text-xs"></i></span>
          <input type="text" id="global-search" placeholder="Cari NIK, Anggota, ID Transaksi, Voucher..."
            onkeydown="if (event.key === 'Enter') globalSearch()"
            class="w-full pl-9 pr-4 py-2 text-xs bg-slate-100 dark:bg-slate-800 border-0 rounded-full focus:ring-2 focus:ring-brand-600 transition-all">
        </div>
        <?php endif; ?>
      </div>
      <div class="flex items-center space-x-1 sm:space-x-3 shrink-0">
        <!-- Dark mode -->
        <button onclick="toggleDarkMode()" id="btn-darkmode" title="Mode Malam"
          class="p-2 rounded-lg text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 dark:text-slate-300 transition">
          <i class="fa-solid fa-moon text-base"></i>
        </button>
        <!-- Notifications -->
        <div class="relative shrink-0">
          <button onclick="toggleNotif()" class="relative p-2 rounded-lg text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 dark:text-slate-300 transition">
            <i class="fa-regular fa-bell text-base"></i>
            <span id="notif-dot" class="absolute top-1.5 right-1.5 w-2 h-2 rounded-full bg-red-500 animate-pulse hidden"></span>
          </button>
          <div id="notif-panel" class="hidden absolute right-0 mt-2 w-80 max-w-[90vw] glass-card rounded-2xl shadow-2xl z-50 p-3">
            <div class="flex items-center justify-between mb-2">
              <p class="text-xs font-bold text-slate-700 dark:text-slate-200">Notifikasi</p>
              <div class="flex items-center gap-2">
                <button onclick="markAllRead()" class="text-[10px] text-brand-600 hover:underline font-semibold">Tandai dibaca</button>
                <form method="post" action="/notifications/clear" onsubmit="return confirmAction(this, 'Bersihkan semua notifikasi?')">
                  <?= Csrf::field() ?>
                  <button type="submit" class="text-[10px] text-red-500 hover:underline font-semibold">Bersihkan</button>
                </form>
              </div>
            </div>
            <div id="notif-items" class="space-y-1.5 max-h-72 overflow-y-auto custom-scrollbar">
              <p class="text-[11px] text-slate-400">Memuat...</p>
            </div>
          </div>
        </div>
        <!-- Logout -->
        <form method="post" action="/logout" class="inline" onsubmit="return confirmAction(this, 'Yakin ingin keluar?')">
          <?= Csrf::field() ?>
          <button type="submit" title="Logout"
            class="p-2 rounded-lg text-slate-500 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-900/20 transition">
            <i class="fa-solid fa-right-from-bracket text-base"></i>
          </button>
        </form>
        <div class="flex items-center pl-2 border-l border-slate-200 dark:border-slate-800 space-x-2 shrink-0">
          <div class="w-8 h-8 rounded-full bg-brand-600 text-white font-bold flex items-center justify-center text-xs shadow shrink-0">
            <span><?= e(initials((string) ($currentUser['full_name'] ?? 'U'))) ?></span>
          </div>
          <div class="hidden lg:block text-left">
            <p class="text-xs font-bold text-slate-800 dark:text-slate-100 leading-tight"><?= e($currentUser['full_name'] ?? '') ?></p>
            <p class="text-[10px] text-slate-500"><?= e(role_label((string) ($currentUser['role'] ?? ''))) ?></p>
          </div>
        </div>
      </div>
    </header>

    <!-- MAIN -->
    <div class="flex-1 flex overflow-hidden relative">
      <div id="sidebarBackdrop" onclick="toggleSidebar()"
        class="hidden fixed inset-0 bg-slate-950/50 backdrop-blur-sm z-10 lg:hidden transition-opacity"></div>

      <!-- SIDEBAR -->
      <aside id="sidebar"
        class="w-64 max-w-[85vw] bg-white dark:bg-slate-900 border-r border-slate-200 dark:border-slate-800 flex flex-col shrink-0 transition-transform duration-300 z-20 fixed lg:relative top-16 lg:top-0 left-0 h-[calc(100vh-4rem)] lg:h-auto">
        <nav class="flex-1 p-4 space-y-1.5 overflow-y-auto custom-scrollbar">
          <?php foreach ($sidebarSections as $sectionLabel => $items): ?>
            <?php
              $sectionHasAny = false;
              foreach ($items as $viewId => [$icon, $label]) {
                  if (in_array($viewId, $allowedViews, true)) { $sectionHasAny = true; break; }
              }
            ?>
            <?php if ($sectionHasAny): ?>
            <div class="px-3 pb-2 text-[10px] font-bold text-slate-400 uppercase tracking-wider"><?= e($sectionLabel) ?></div>
            <?php foreach ($items as $viewId => [$icon, $label]): ?>
              <?php
                $allowed = in_array($viewId, $allowedViews, true);
                if (!$allowed) continue;
                $isActive = ($viewId === $activeView);
                $classes = 'nav-item w-full flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs '
                  . ($isActive
                    ? 'bg-brand-50 text-brand-600 dark:bg-slate-800 dark:text-brand-500 font-semibold'
                    : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 font-medium')
                  . ' transition';
              ?>
              <a href="<?= $viewId === 'portal_chat' ? '/portal/chat' : '/' . e($viewId) ?>" id="nav-<?= e($viewId) ?>" class="<?= $classes ?>">
                <i class="<?= e($icon) ?> text-sm"></i><span><?= e($label) ?></span>
              </a>
            <?php endforeach; ?>
            <?php endif; ?>
          <?php endforeach; ?>
        </nav>
        <div class="p-4 border-t border-slate-200 dark:border-slate-800 text-[11px] text-slate-400">
          <p class="font-semibold text-slate-600 dark:text-slate-300">MySQL DB Active</p>
          <p class="text-[10px] flex items-center space-x-1 mt-0.5">
            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
            <span>Sync Live (v<?= e(APP_VERSION) ?>)</span>
          </p>
        </div>
      </aside>

      <!-- CONTENT VIEWPORT -->
      <main class="flex-1 overflow-y-auto custom-scrollbar p-4 lg:p-6 space-y-6">
        <!-- Breadcrumb -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div>
            <h2 class="font-display text-xl font-bold text-slate-800 dark:text-slate-100"><?= e($pageTitle ?? '') ?></h2>
            <p class="text-xs text-slate-500 mt-0.5"><?= e($pageSubtitle ?? '') ?></p>
          </div>
          <div class="flex items-center space-x-2 flex-wrap gap-2">
            <?= $viewActions ?? '' ?>
          </div>
        </div>

        <?= $content ?>
      </main>
    </div>
  </div>

  <!-- Confirm dialog (pengganti confirm() browser) -->
  <div id="confirm-dialog" class="hidden fixed inset-0 z-[90] flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-sm">
    <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl w-full max-w-sm p-5 space-y-4">
      <div class="flex items-start space-x-3">
        <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center shrink-0">
          <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
        <div>
          <h3 class="font-display text-sm font-bold text-slate-800 dark:text-slate-100">Konfirmasi</h3>
          <p id="confirm-message" class="text-xs text-slate-500 mt-1"></p>
        </div>
      </div>
      <div class="flex justify-end space-x-2">
        <button onclick="closeConfirm(false)" class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-xs font-bold hover:bg-slate-200">Batal</button>
        <button id="confirm-ok" onclick="closeConfirm(true)" class="px-4 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow">Ya, Lanjutkan</button>
      </div>
    </div>
  </div>

  <!-- Toast container -->
  <div id="toast-container" class="fixed bottom-4 right-4 z-[100] flex flex-col space-y-2 items-end"></div>

  <script>
    var _confirmSubmit = null;
    function openModal(id) { var el = document.getElementById(id); if (el) el.classList.remove('hidden'); }
    function closeModal(id) { var el = document.getElementById(id); if (el) el.classList.add('hidden'); }

    // Pencarian global: arahkan ke modul Master Anggota (pencarian server-side).
    function globalSearch() {
      var q = (document.getElementById('global-search') || {}).value || '';
      q = q.trim();
      if (q) window.location.href = '/members?q=' + encodeURIComponent(q);
    }

    function confirmAction(formOrBtn, message) {
      var dlg = document.getElementById('confirm-dialog');
      document.getElementById('confirm-message').innerText = message || 'Apakah Anda yakin ingin menghapus data ini?';
      _confirmSubmit = formOrBtn;
      dlg.classList.remove('hidden');
      return false; // selalu cegah submit langsung; dialog yang memutuskan
    }
    function closeConfirm(ok) {
      document.getElementById('confirm-dialog').classList.add('hidden');
      if (ok && _confirmSubmit) {
        if (_confirmSubmit instanceof HTMLFormElement) _confirmSubmit.submit();
        else if (typeof _confirmSubmit === 'function') _confirmSubmit();
      }
      _confirmSubmit = null;
    }

    function toggleSidebar(forceClose) {
      var sidebar = document.getElementById('sidebar');
      var backdrop = document.getElementById('sidebarBackdrop');
      if (!sidebar || !backdrop) return;
      var isOpen = sidebar.classList.contains('sidebar-open');
      if (forceClose === true || isOpen) {
        sidebar.classList.remove('sidebar-open');
        backdrop.classList.add('hidden');
        if (window.innerWidth < 1024) document.body.classList.remove('overflow-hidden');
      } else {
        sidebar.classList.add('sidebar-open');
        backdrop.classList.remove('hidden');
        if (window.innerWidth < 1024) document.body.classList.add('overflow-hidden');
      }
    }

    function toggleNotif() {
      var panel = document.getElementById('notif-panel');
      if (!panel) return;
      panel.classList.toggle('hidden');
      if (!panel.classList.contains('hidden')) loadNotifications();
    }

    function toggleDarkMode() {
      var isDark = document.documentElement.classList.toggle('dark');
      try { localStorage.setItem('kutt-theme', isDark ? 'dark' : 'light'); } catch (e) {}
      var icon = document.querySelector('#btn-darkmode i');
      if (icon) icon.className = isDark ? 'fa-solid fa-sun text-base' : 'fa-solid fa-moon text-base';
    }

    function markAllRead() {
      var xhr = new XMLHttpRequest();
      xhr.open('POST', '/notifications/read');
      xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
      xhr.setRequestHeader('X-CSRF-Token', <?= json_encode(Csrf::token()) ?>);
      xhr.onload = function () { loadNotifications(); };
      xhr.send();
    }

    function loadNotifications() {
      var box = document.getElementById('notif-items');
      var dot = document.getElementById('notif-dot');
      var xhr = new XMLHttpRequest();
      xhr.open('GET', '/notifications');
      xhr.onload = function () {
        if (xhr.status !== 200) { box.innerHTML = '<p class="text-[11px] text-red-400">Gagal memuat notifikasi.</p>'; return; }
        var data = JSON.parse(xhr.responseText);
        dot.classList.toggle('hidden', data.unread === 0);
        if (!data.items.length) {
          box.innerHTML = '<p class="text-[11px] text-slate-400">Tidak ada notifikasi.</p>';
          return;
        }
        box.innerHTML = data.items.map(function (n) {
          var icons = { INFO: 'fa-circle-info text-blue-500', SUCCESS: 'fa-circle-check text-emerald-500', WARNING: 'fa-triangle-exclamation text-amber-500', DANGER: 'fa-circle-exclamation text-red-500' };
          var icon = icons[n.type] || icons.INFO;
          // Escape: judul/pesan bisa memuat teks bebas (nama anggota dsb.) —
          // hindari XSS via innerHTML.
          var esc = function (s) { var d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; };
          return '<a href="' + esc(n.link || '#') + '" class="flex items-start space-x-2 p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 ' + (n.is_read == 0 ? 'bg-brand-50/60 dark:bg-slate-800' : '') + '">' +
            '<i class="fa-solid ' + icon + ' mt-0.5"></i>' +
            '<div class="min-w-0"><p class="text-[11px] font-bold text-slate-700 dark:text-slate-200 truncate">' + esc(n.title) + '</p>' +
            '<p class="text-[10px] text-slate-500 line-clamp-2">' + esc(n.message || '') + '</p></div></a>';
        }).join('');
      };
      xhr.send();
    }

    function showToast(message, type) {
      type = type || 'info';
      var container = document.getElementById('toast-container');
      var toast = document.createElement('div');
      toast.className = 'pointer-events-auto px-4 py-3 rounded-xl text-white text-xs font-semibold shadow-2xl flex items-center space-x-2 transition-all duration-300 ' + (type === 'success' ? 'bg-emerald-600' : type === 'error' ? 'bg-red-600' : 'bg-slate-800');
      toast.innerHTML = '<i class="fa-solid ' + (type === 'success' ? 'fa-circle-check' : type === 'error' ? 'fa-circle-exclamation' : 'fa-circle-info') + '"></i><span>' + message + '</span>';
      container.appendChild(toast);
      setTimeout(function () { toast.remove(); }, 3500);
    }

    window.addEventListener('resize', function () {
      if (window.innerWidth >= 1024) {
        var sidebar = document.getElementById('sidebar');
        var backdrop = document.getElementById('sidebarBackdrop');
        if (sidebar) sidebar.classList.remove('sidebar-open');
        if (backdrop) backdrop.classList.add('hidden');
      }
    });

    // Set dark icon sesuai state awal
    (function () {
      var isDark = document.documentElement.classList.contains('dark');
      var icon = document.querySelector('#btn-darkmode i');
      if (icon) icon.className = isDark ? 'fa-solid fa-sun text-base' : 'fa-solid fa-moon text-base';
    })();

    <?php if ($flash !== null): ?>
      showToast(<?= json_encode($flash['message']) ?>, <?= json_encode($flash['type'] === 'success' ? 'success' : ($flash['type'] === 'error' ? 'error' : 'info')) ?>);
    <?php endif; ?>
  </script>
</body>

</html>
