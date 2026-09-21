<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Header & footer portal publik yang dipakai bersama oleh halaman portal
 * utama dan halaman berita publik — memastikan navigasi/brand konsisten.
 */
final class PortalLayout
{
    /**
     * @param array<string,mixed> $brand  Setting::all()
     * @param list<array<string,string>> $navItems
     */
    public static function header(array $brand, array $navItems, string $activePath = ''): void
    {
        $brandName    = (string) ($brand['brandName'] ?? 'KUTT SUKA MAKMUR');
        $subBrand     = (string) ($brand['subBrand'] ?? 'Grati - Pasuruan');
        $logoIcon     = (string) ($brand['logoIcon'] ?? 'fa-solid fa-cow');
        $logoImage    = (string) ($brand['logoImage'] ?? '');
        $colorPrimary = (string) ($brand['colorPrimary'] ?? '#0b7a3e');
        $isLoggedIn   = \App\Core\Auth::check();

        $iconTags = is_file(BASE_PATH . '/public/favicon.ico')
            ? '<link rel="icon" href="/favicon.ico" sizes="any">'
            : '<link rel="icon" href="data:,">';
        if (is_file(BASE_PATH . '/public/apple-touch-icon.png')) {
            $iconTags .= '<link rel="apple-touch-icon" href="/apple-touch-icon.png">';
        }
        if (is_file(BASE_PATH . '/public/icon-192.png') && is_file(BASE_PATH . '/public/icon-512.png')) {
            $iconTags .= '<link rel="icon" type="image/png" sizes="192x192" href="/icon-192.png">'
                . '<link rel="icon" type="image/png" sizes="512x512" href="/icon-512.png">'
                . '<link rel="manifest" href="/site.webmanifest">';
        }
        $ogAsset = (string) ($brand['ogImagePath'] ?? '');
        $ogTags = '';
        if ($ogAsset !== '') {
            $ogUrl = base_url('/' . $ogAsset);
            $ogTags = '<meta property="og:title" content="' . e($brandName) . '">'
                . '<meta property="og:image" content="' . e($ogUrl) . '">'
                . '<meta name="twitter:card" content="summary_large_image">'
                . '<meta name="twitter:image" content="' . e($ogUrl) . '">';
        }

        echo '<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50 scroll-smooth">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>' . e($brandName) . '</title>
  <script src="https://cdn.tailwindcss.com"></script>
  ' . $iconTags . $ogTags . '
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <script>
    if (typeof tailwind !== "undefined") { tailwind.config = { theme: { extend: { colors: {
      brand: { 50: "#f0fdf4", 100: "#dcfce7", 500: "#2fae60", 600: "#0b7a3e", 700: "#085c2e", gold: "#ffc107" },
      "kutt-primary": "var(--kutt-primary)", "kutt-dark": "var(--kutt-dark)", "kutt-gold": "var(--kutt-gold)"
    }, fontFamily: { sans: ["Inter", "sans-serif"], display: ["Poppins", "sans-serif"] } } } }; }
  </script>
  <style>
    :root { --kutt-primary: ' . e($colorPrimary) . '; --kutt-dark: ' . e((string) ($brand['colorDark'] ?? '#052e1a')) . '; --kutt-gold: ' . e((string) ($brand['colorGold'] ?? '#ffc107')) . '; }
    .glass-nav { background: rgba(255,255,255,0.85); backdrop-filter: blur(14px); border-bottom: 1px solid rgba(11,122,62,0.08); }
    .glass-card { background: rgba(255,255,255,0.88); backdrop-filter: blur(12px); border: 1px solid rgba(11,122,62,0.12); }
    .custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #CBD5E1; border-radius: 9999px; }
    #mobileNavDrawer, #mobileNavBackdrop { display: none; }
    @media (max-width: 767px) { #mobileNavDrawer { display: flex; } #mobileNavBackdrop { display: block; } }
    /* Kelola tampil/sembunyi via kelas .hidden (JS toggle) — lawan spesifisitas ID */
    .hidden#mobileNavDrawer, .hidden#mobileNavBackdrop { display: none !important; }
  </style>
  <meta name="csrf-token" content="' . e(\App\Core\Csrf::token()) . '">
</head>
<body class="h-full font-sans text-slate-800 bg-[#F7F9F8] antialiased">
<header class="fixed top-0 left-0 right-0 z-40 glass-nav transition-all duration-300">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
    <a href="/" class="flex items-center gap-3 group">
      <div class="w-12 h-12 rounded-2xl bg-white border border-slate-200/80 flex items-center justify-center font-bold text-2xl shadow-lg shadow-emerald-900/10 group-hover:scale-105 transition overflow-hidden">'
            . ($logoImage !== ''
                ? '<img src="' . e($logoImage) . '" alt="Logo" class="w-full h-full object-contain">'
                : '<i class="' . e($logoIcon) . '"></i>') . '
      </div>
      <div>
        <span class="font-poppins font-extrabold text-lg text-slate-900 tracking-tight block leading-tight">' . e($brandName) . '</span>
        <span class="text-xs font-bold text-kutt-primary uppercase tracking-widest block">' . e($subBrand) . '</span>
      </div>
    </a>
    <nav class="hidden md:flex items-center gap-8 text-sm font-medium text-slate-600">';
        foreach ($navItems as $item) {
            $active = $item['path'] === $activePath ? 'text-kutt-primary font-bold' : '';
            echo '<a href="' . e($item['path']) . '" class="hover:text-kutt-primary transition ' . $active . '">' . e($item['label']) . '</a>';
        }
        echo '</nav>
    <div class="flex items-center gap-3">
      ' . ($isLoggedIn
            ? '<a href="/dashboard" class="px-5 py-2.5 bg-kutt-primary hover:opacity-90 text-white font-semibold rounded-xl text-xs sm:text-sm shadow-md transition flex items-center gap-2"><i class="fa-solid fa-gauge-high"></i><span class="hidden sm:inline">Dashboard</span></a>'
            : '<a href="/login" class="px-5 py-2.5 bg-kutt-primary hover:opacity-90 text-white font-semibold rounded-xl text-xs sm:text-sm shadow-md transition flex items-center gap-2"><i class="fa-solid fa-right-to-bracket"></i><span>Login</span></a>') . '
      <button type="button" onclick="toggleMobileNav()" class="md:hidden p-2.5 rounded-xl bg-slate-100 text-slate-800 text-xl hover:bg-slate-200 transition"><i id="mobileMenuIcon" class="fa-solid fa-bars"></i></button>
    </div>
  </div>
</header>

<div id="mobileNavBackdrop" onclick="toggleMobileNav()" class="md:hidden hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-40 transition-opacity duration-300"></div>
<aside id="mobileNavDrawer" class="md:hidden fixed top-0 right-0 h-full w-72 max-w-[85%] bg-white z-50 shadow-2xl transform translate-x-full transition-transform duration-300 flex-col">
  <div class="flex items-center justify-between p-5 border-b border-slate-100">
    <span class="font-poppins font-extrabold text-sm text-slate-900">Menu Navigasi</span>
    <button type="button" onclick="toggleMobileNav()" class="p-2 rounded-lg bg-slate-100 text-slate-600"><i class="fa-solid fa-xmark"></i></button>
  </div>
  <nav class="flex-1 overflow-y-auto custom-scrollbar p-5 space-y-1 text-sm font-semibold text-slate-700">';
        foreach ($navItems as $item) {
            echo '<a href="' . e($item['path']) . '" onclick="toggleMobileNav()" class="block px-3 py-3 rounded-xl hover:bg-slate-50 transition">' . e($item['label']) . '</a>';
        }
        echo '</nav>
  <div class="p-5 border-t border-slate-100">
    <a href="' . ($isLoggedIn ? '/dashboard' : '/login') . '" onclick="toggleMobileNav()" class="w-full px-5 py-3 bg-kutt-primary hover:opacity-90 text-white font-semibold rounded-xl text-sm shadow-md transition flex items-center justify-center gap-2">
      <i class="fa-solid fa-right-to-bracket"></i> ' . ($isLoggedIn ? 'Buka Dashboard' : 'Login') . '
    </a>
  </div>
</aside>

<script>
function toggleMobileNav() {
  var drawer = document.getElementById("mobileNavDrawer");
  var backdrop = document.getElementById("mobileNavBackdrop");
  var icon = document.getElementById("mobileMenuIcon");
  if (!drawer || !backdrop) return;
  var isOpen = drawer.classList.contains("translate-x-0");
  if (isOpen) {
    drawer.classList.remove("translate-x-0"); drawer.classList.add("translate-x-full");
    backdrop.classList.add("hidden"); document.body.classList.remove("overflow-hidden");
    if (icon) icon.className = "fa-solid fa-bars";
  } else {
    drawer.classList.remove("translate-x-full"); drawer.classList.add("translate-x-0");
    backdrop.classList.remove("hidden"); document.body.classList.add("overflow-hidden");
    if (icon) icon.className = "fa-solid fa-xmark";
  }
}
window.addEventListener("scroll", function () {
  document.querySelectorAll(".glass-nav").forEach(function (h) { h.classList.toggle("nav-scrolled", window.scrollY > 24); });
});
</script>';
    }

    /**
     * @param array<string,mixed> $brand
     */
    public static function footer(array $brand): void
    {
        $brandName  = (string) ($brand['brandName'] ?? 'KUTT SUKA MAKMUR');
        $subBrand   = (string) ($brand['subBrand'] ?? 'Grati - Pasuruan');
        $logoIcon   = (string) ($brand['logoIcon'] ?? 'fa-solid fa-cow');
        $logoImage  = (string) ($brand['logoImage'] ?? '');
        $address    = (string) ($brand['footerAddress'] ?? '');
        $phone      = (string) ($brand['footerPhone'] ?? '');
        $copyright  = (string) ($brand['footerCopyright'] ?? ('© ' . date('Y') . ' ' . $brandName));

        echo '<footer class="py-12 bg-white border-t border-slate-200 mt-auto">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-1 sm:grid-cols-3 gap-8">
    <div class="space-y-3">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-kutt-primary text-white flex items-center justify-center font-bold overflow-hidden">'
            . ($logoImage !== '' ? '<img src="' . e($logoImage) . '" alt="Logo" class="w-full h-full object-contain">' : '<i class="' . e($logoIcon) . '"></i>') . '
        </div>
        <h4 class="font-extrabold font-poppins text-slate-900 text-sm">' . e($brandName) . '</h4>
      </div>
      <p class="text-xs text-slate-500 leading-relaxed">' . nl2br(e($address)) . '</p>
      <p class="text-xs text-slate-600"><strong>Call Center:</strong> ' . e($phone) . '</p>
    </div>
    <div class="space-y-2 text-xs text-slate-600">
      <h5 class="font-bold font-poppins text-slate-900 text-sm mb-3">Navigasi</h5>
      <a href="/" class="block hover:text-kutt-primary transition">Beranda</a>
      <a href="/berita" class="block hover:text-kutt-primary transition">Berita &amp; Kegiatan</a>
      <a href="/login" class="block hover:text-kutt-primary transition">Portal Anggota &amp; Admin</a>
    </div>
    <div class="space-y-3">
      <h5 class="font-bold font-poppins text-slate-900 text-sm">' . e($subBrand) . '</h5>
      <a href="/login" class="w-full py-3 bg-kutt-primary hover:opacity-90 text-white text-xs font-bold rounded-xl shadow transition flex items-center justify-center gap-2">
        <i class="fa-solid fa-lock"></i><span>Login Pengurus &amp; Anggota</span>
      </a>
      <p class="text-[11px] text-center text-slate-400">Sistem Informasi Koperasi Digital</p>
    </div>
  </div>
  <div class="mt-10 pt-6 border-t border-slate-200 text-center text-xs text-slate-500">
    <p>' . e($copyright) . '</p>
  </div>
</footer>
</body>
</html>';
    }
}
