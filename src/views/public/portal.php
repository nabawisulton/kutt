<?php
/**
 * PORTAL / LANDING PAGE KUTT SUKA MAKMUR — markup asli dari aplikasi GAS lama
 * (FIX, DON'T REDESIGN). Konten kini dari MySQL via Portal::content().
 * Variables: $cms (portal content), $brand (settings map), $seoData.
 */

use App\Support\HtmlSanitizer;

$cms   = $cms ?? [];
$brand = $brand ?? [];
$seo   = $seoData ?? [];

$brandName    = (string) ($brand['brandName'] ?? 'KUTT SUKA MAKMUR');
$subBrand     = (string) ($brand['subBrand'] ?? 'Grati - Pasuruan');
$logoIcon     = (string) ($brand['logoIcon'] ?? 'fa-solid fa-cow');
$logoImage    = (string) ($brand['logoImage'] ?? '');
$colorPrimary = (string) ($brand['colorPrimary'] ?? '#0b7a3e');
$footerAddr   = (string) ($brand['footerAddress'] ?? '');
$footerPhone  = (string) ($brand['footerPhone'] ?? '');
$footerCopy   = (string) ($brand['footerCopyright'] ?? '© ' . date('Y') . ' KUTT Suka Makmur Grati. All Rights Reserved.');

$heroImages = array_values(array_filter(array_map(
    static fn ($img) => is_string($img) ? $img : (string) ($img['url'] ?? ''),
    (array) ($cms['heroImages'] ?? [])
)));

$navItems = [
    '#beranda'  => 'Beranda',
    '/berita'   => 'Berita',
    '#profil'   => 'Profil & Visi',
    '#layanan'  => 'Unit Usaha',
    '/marketplace' => 'Belanja',
    '#galeri'   => 'Galeri',
    '#video'    => 'Video',
    '#produk'   => 'Katalog Produk',
    '#kalkulator' => 'Simulasi SHU',
    '#kontak'   => 'Kontak',
];
?>
<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50 scroll-smooth">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e((string) $seo['title']) ?></title>
  <meta name="description" content="<?= e((string) $seo['description']) ?>">
  <?php
    // Prioritas gambar share: unggahan admin (OG 1200x630) -> hero pertama -> berita terbaru.
    $ogAsset = (string) ($brand['ogImagePath'] ?? '');
    $ogImage = $ogAsset !== ''
        ? base_url('/' . $ogAsset)
        : ($heroImages[0] ?? (function (): ?string {
            $latest = App\Core\Database::first(
                "SELECT image_path FROM news_posts WHERE status = 'PUBLISHED' AND image_path IS NOT NULL AND image_path != '' ORDER BY published_at DESC, id DESC LIMIT 1"
            );
            return $latest === null ? null : base_url(news_image_src($latest['image_path']));
        })());
    $ogUrl = base_url('/');
  ?>
  <meta property="og:title" content="<?= e((string) $seo['title']) ?>">
  <meta property="og:description" content="<?= e((string) $seo['description']) ?>">
  <?php if ($ogImage !== null): ?><meta property="og:image" content="<?= e($ogImage) ?>"><?php endif; ?>
  <meta property="og:url" content="<?= e($ogUrl) ?>">
  <meta property="og:type" content="website">
  <meta property="og:site_name" content="<?= e((string) ($brand['brandName'] ?? 'KUTT SUKA MAKMUR')) ?>">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?= e((string) $seo['title']) ?>">
  <meta name="twitter:description" content="<?= e((string) $seo['description']) ?>">
  <?php if ($ogImage !== null): ?><meta name="twitter:image" content="<?= e($ogImage) ?>"><?php endif; ?>
  <?php if (is_file(BASE_PATH . '/public/favicon.ico')): ?>
    <link rel="icon" href="/favicon.ico" sizes="any">
  <?php else: ?>
    <link rel="icon" href="data:,">
  <?php endif; ?>
  <?php if (is_file(BASE_PATH . '/public/apple-touch-icon.png')): ?>
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
  <?php endif; ?>
  <?php if (is_file(BASE_PATH . '/public/icon-192.png') && is_file(BASE_PATH . '/public/icon-512.png')): ?>
    <link rel="icon" type="image/png" sizes="192x192" href="/icon-192.png">
    <link rel="icon" type="image/png" sizes="512x512" href="/icon-512.png">
    <link rel="manifest" href="/site.webmanifest">
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
      --kutt-primary: <?= e($colorPrimary) ?>;
      --kutt-dark: <?= e((string) ($brand['colorDark'] ?? '#052e1a')) ?>;
      --kutt-gold: <?= e((string) ($brand['colorGold'] ?? '#ffc107')) ?>;
    }
  </style>

  <style>
    .glass-card { background: rgba(255, 255, 255, 0.88); backdrop-filter: blur(12px); border: 1px solid rgba(11, 122, 62, 0.12); }
    /* Anti-blur mobile: hentikan auto-zoom teks & paksa render tajam */
    html { -webkit-text-size-adjust: 100%; text-size-adjust: 100%; }
    body * { -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; }
    .hero-slide img, .gallery-card img, .product-slide img { image-rendering: auto; backface-visibility: hidden; transform: translateZ(0); }
    .hero-bg { background: linear-gradient(180deg, #F5FBF7 0%, #FFFFFF 45%); }
    .glass-nav { background: rgba(255, 255, 255, 0.78); backdrop-filter: blur(14px); border-bottom: 1px solid rgba(11, 122, 62, 0.08); }
    /* Di layar kecil, blur full-width nav bikin teks buram & berat -> solid saja */
    @media (max-width: 640px) {
      .glass-nav { backdrop-filter: none; background: rgba(255, 255, 255, 0.96); }
      .glass-card { backdrop-filter: none; background: #ffffff; }
    }
    .blob { position: absolute; border-radius: 9999px; filter: blur(70px); opacity: 0.28; z-index: 0; pointer-events: none; }
    .blob-1 { width: 320px; height: 320px; background: var(--kutt-primary); top: -80px; left: -100px; }
    .blob-2 { width: 260px; height: 260px; background: var(--kutt-gold); bottom: -60px; right: -60px; }
    .blob-3 { width: 220px; height: 220px; background: #3b82f6; top: 120px; right: -90px; }
    .custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #CBD5E1; border-radius: 9999px; }
    /* Drawer & backdrop mobile: kelola TAMPIL/SEMBUNYI lewat kelas .hidden
       (class-level specificity) supaya JS toggle tetap bekerja. visibility
       via CSS hanya menyembunyikan di desktop >=1024px. */
    @media (min-width: 1024px) {
      #mobileNavDrawer, #mobileNavBackdrop { display: none !important; }
    }

    #heroCarouselViewport { position: relative; overflow: hidden; width: 100%; height: 20rem; border-radius: 1.5rem; }
    @media (min-width: 640px) { #heroCarouselViewport { height: 24rem; } }
    #heroCarouselTrack { display: flex; height: 100%; transition: transform 1.1s cubic-bezier(0.65, 0, 0.35, 1); will-change: transform; }
    .hero-slide { position: relative; flex: 0 0 100%; height: 100%; }
    .hero-slide img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .hero-carousel-dots { position: absolute; bottom: 14px; left: 0; right: 0; display: flex; align-items: center; justify-content: center; gap: 6px; z-index: 10; }
    .hero-carousel-dot { width: 8px; height: 8px; border-radius: 9999px; background: rgba(255, 255, 255, 0.55); transition: all 0.3s ease; cursor: pointer; }
    .hero-carousel-dot.active { width: 22px; background: #ffffff; }

    @keyframes heroFadeSlideUp { from { opacity: 0; transform: translateY(22px); } to { opacity: 1; transform: translateY(0); } }
    .hero-anim { opacity: 0; animation: heroFadeSlideUp 0.9s cubic-bezier(0.22, 1, 0.36, 1) forwards; }
    .hero-anim-1 { animation-delay: 0.05s; } .hero-anim-2 { animation-delay: 0.2s; }
    .hero-anim-3 { animation-delay: 0.35s; } .hero-anim-4 { animation-delay: 0.5s; } .hero-anim-5 { animation-delay: 0.65s; }

    #productsViewport { overflow: hidden; width: 100%; }
    #landingProductsGrid { display: flex; gap: 1.5rem; transition: transform 1.2s cubic-bezier(0.65, 0, 0.35, 1); will-change: transform; }
    .product-slide { flex: 0 0 100%; }
    @media (min-width: 640px) { .product-slide { flex: 0 0 calc(50% - 0.75rem); } }
    @media (min-width: 1024px) { .product-slide { flex: 0 0 calc(33.333% - 1rem); } }

    #public-landing section[id] { scroll-margin-top: 5.5rem; }
    .glass-nav.nav-scrolled { background: rgba(255, 255, 255, 0.95); box-shadow: 0 4px 24px -8px rgba(5, 46, 26, 0.15); border-bottom: 1px solid rgba(11, 122, 62, 0.14); }
    #public-landing nav a { position: relative; padding-bottom: 2px; }
    #public-landing nav a::after { content: ''; position: absolute; left: 0; bottom: -4px; width: 0; height: 2px; border-radius: 9999px; background: var(--kutt-primary); transition: width 0.25s ease; }
    #public-landing nav a:hover::after { width: 100%; }
    .section-eyebrow { letter-spacing: 0.14em; }

    .gallery-card { position: relative; display: block; border-radius: 1.25rem; overflow: hidden; aspect-ratio: 1 / 1; background: #e2e8f0; box-shadow: 0 1px 2px rgba(15, 23, 42, 0.06); border: 1px solid rgba(15, 23, 42, 0.06); }
    .gallery-card img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.6s cubic-bezier(0.22, 1, 0.36, 1); }
    .gallery-card:hover img { transform: scale(1.08); }
    .gallery-card-caption { position: absolute; inset: 0; display: flex; align-items: flex-end; padding: 0.9rem; background: linear-gradient(180deg, rgba(0, 0, 0, 0) 45%, rgba(0, 0, 0, 0.75) 100%); }
    .gallery-title-clamp { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }

    .lightbox.hidden { display: none !important; }
  </style>
</head>

<body class="h-full font-sans text-slate-800 bg-[#F7F9F8] antialiased">
  <div id="public-landing" class="min-h-screen flex flex-col relative overflow-hidden hero-bg">
    <header class="fixed top-0 left-0 right-0 z-40 glass-nav transition-all duration-300">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
        <a href="#beranda" class="flex items-center gap-3 group">
          <div class="w-12 h-12 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200/80 flex items-center justify-center font-bold text-2xl shadow-lg shadow-emerald-900/10 group-hover:scale-105 transition overflow-hidden">
            <?php if ($logoImage !== ''): ?>
              <img src="<?= e($logoImage) ?>" alt="Logo" class="w-full h-full object-contain">
            <?php else: ?>
              <i class="<?= e($logoIcon) ?>"></i>
            <?php endif; ?>
          </div>
          <div>
            <span class="font-poppins font-extrabold text-lg text-slate-900 tracking-tight block leading-tight"><?= e($brandName) ?></span>
            <span class="text-xs font-bold text-kutt-primary uppercase tracking-widest block"><?= e($subBrand) ?></span>
          </div>
        </a>
        <nav class="hidden lg:flex items-center gap-6 xl:gap-8 text-sm font-medium text-slate-600">
          <?php foreach ($navItems as $href => $label): ?>
            <a href="<?= e($href) ?>" class="hover:text-kutt-primary transition"><?= e($label) ?></a>
          <?php endforeach; ?>
        </nav>
        <div class="flex items-center gap-3">
          <a href="/login"
            class="px-5 py-2.5 bg-kutt-primary hover:opacity-90 text-white font-semibold rounded-xl text-xs sm:text-sm shadow-md transition flex items-center gap-2">
            <i class="fa-solid fa-right-to-bracket"></i>
            <span>Login</span>
          </a>
          <button type="button" onclick="toggleMobileNav()"
            class="lg:hidden p-2.5 rounded-xl bg-slate-100 text-slate-800 text-xl hover:bg-slate-200 transition">
            <i id="mobileMenuIcon" class="fa-solid fa-bars"></i>
          </button>
        </div>
      </div>
    </header>

    <!-- Mobile Nav Drawer -->
    <div id="mobileNavBackdrop" onclick="toggleMobileNav()"
      class="lg:hidden hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-40 transition-opacity duration-300"></div>
    <aside id="mobileNavDrawer"
      class="lg:hidden fixed top-0 right-0 h-full w-72 max-w-[85vw] bg-white z-50 shadow-2xl transform translate-x-full transition-transform duration-300 flex-col">
      <div class="flex items-center justify-between p-5 border-b border-slate-100">
        <span class="font-poppins font-extrabold text-sm text-slate-900">Menu Navigasi</span>
        <button type="button" onclick="toggleMobileNav()" class="p-2 rounded-lg bg-slate-100 text-slate-600">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>
      <nav class="flex-1 overflow-y-auto custom-scrollbar p-5 space-y-1 text-sm font-semibold text-slate-700">
        <?php foreach ($navItems as $href => $label): ?>
          <a href="<?= e($href) ?>" onclick="toggleMobileNav()" class="block px-3 py-3 rounded-xl hover:bg-slate-50 transition"><?= e($label) ?></a>
        <?php endforeach; ?>
      </nav>
      <div class="p-5 border-t border-slate-100">
        <a href="/login" onclick="toggleMobileNav()"
          class="w-full px-5 py-3 bg-kutt-primary hover:opacity-90 text-white font-semibold rounded-xl text-sm shadow-md transition flex items-center justify-center gap-2">
          <i class="fa-solid fa-right-to-bracket"></i> Login
        </a>
      </div>
    </aside>

    <!-- HERO -->
    <section id="beranda"
      class="pt-32 pb-20 md:pt-40 md:pb-28 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto flex flex-col md:flex-row items-center justify-between gap-12 relative">
      <div class="blob blob-1"></div>
      <div class="blob blob-3"></div>
      <div class="md:w-1/2 space-y-6 text-center md:text-left z-10 order-2 md:order-none">
        <div class="hero-anim hero-anim-1 inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-emerald-100/80 border border-emerald-200 text-kutt-primary text-xs font-bold uppercase tracking-wider">
          <i class="fa-solid fa-circle-check text-emerald-600"></i> <?= e((string) ($cms['heroBadge'] ?? '')) ?>
        </div>
        <h1 class="hero-anim hero-anim-2 text-3xl sm:text-4xl lg:text-5xl font-extrabold font-poppins text-slate-900 leading-tight">
          <?= e((string) ($cms['heroTitle'] ?? '')) ?>
        </h1>
        <p class="hero-anim hero-anim-3 text-sm sm:text-base text-slate-600 leading-relaxed max-w-xl">
          <?= e((string) ($cms['heroSubtitle'] ?? '')) ?>
        </p>
        <div class="hero-anim hero-anim-4 pt-2 flex flex-col sm:flex-row items-center gap-4 justify-center md:justify-start">
          <a href="#layanan"
            class="w-full sm:w-auto px-7 py-3.5 bg-kutt-primary hover:opacity-90 text-white font-bold rounded-2xl shadow-xl shadow-emerald-700/25 transition duration-200 flex items-center justify-center gap-2">
            <span>Jelajahi Unit Usaha</span><i class="fa-solid fa-arrow-down text-xs"></i>
          </a>
        </div>
        <div class="hero-anim hero-anim-5 pt-8 grid grid-cols-3 gap-2 sm:gap-4 border-t border-slate-200">
          <div>
            <h4 class="text-base sm:text-2xl font-extrabold font-poppins text-slate-900"><?= e((string) ($cms['statAnggota'] ?? '')) ?></h4>
            <p class="text-xs text-slate-500 font-medium">Anggota Peternak</p>
          </div>
          <div>
            <h4 class="text-base sm:text-2xl font-extrabold font-poppins text-kutt-primary"><?= e((string) ($cms['statSusu'] ?? '')) ?></h4>
            <p class="text-xs text-slate-500 font-medium">Susu Murni / Hari</p>
          </div>
          <div>
            <h4 class="text-base sm:text-2xl font-extrabold font-poppins text-amber-500"><?= e((string) ($cms['statPengalaman'] ?? '')) ?></h4>
            <p class="text-xs text-slate-500 font-medium">Pengalaman Pengabdian</p>
          </div>
        </div>
      </div>
      <div class="md:w-1/2 relative z-10 w-full max-w-lg order-1 md:order-none">
        <div class="relative mx-auto rounded-3xl shadow-2xl border-4 border-white" id="heroCarouselViewport"
          onmouseenter="pauseHeroCarousel()" onmouseleave="resumeHeroCarousel()">
          <div id="heroCarouselTrack"></div>
          <div id="heroCarouselDots" class="hero-carousel-dots"></div>
          <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-transparent flex flex-col justify-end p-6 text-white pointer-events-none">
            <span class="text-xs font-bold text-amber-400 uppercase tracking-widest mb-1">Standard ISO 22000 Certified</span>
            <h3 class="font-poppins font-bold text-lg">Pusat Penampungan &amp; Industri Susu Murni Grati</h3>
            <p class="text-xs text-slate-200">Kapasitas Tangki Pendingin Cold Chain Modern</p>
          </div>
        </div>
      </div>
    </section>

    <!-- PROFIL -->
    <section id="profil" class="py-16 bg-white border-y border-slate-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
        <div class="text-center max-w-2xl mx-auto space-y-3">
          <h2 class="text-xs font-bold text-kutt-primary uppercase tracking-widest">PROFIL KOPERASI</h2>
          <h3 class="text-2xl sm:text-3xl font-extrabold font-poppins text-slate-900">Membangun Ekosistem Peternakan Berkelanjutan</h3>
          <p class="text-xs sm:text-sm text-slate-500"><?= e((string) ($cms['profileDesc'] ?? '')) ?></p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
          <div class="p-6 rounded-2xl bg-slate-50 border border-slate-200 hover:shadow-md transition space-y-3">
            <div class="w-12 h-12 rounded-xl bg-emerald-100 text-kutt-primary flex items-center justify-center text-xl">
              <i class="fa-solid fa-bullseye"></i>
            </div>
            <h4 class="font-bold font-poppins text-slate-900">Visi Utama</h4>
            <p class="text-xs text-slate-600 leading-relaxed"><?= e((string) ($cms['visiText'] ?? '')) ?></p>
          </div>
          <div class="p-6 rounded-2xl bg-slate-50 border border-slate-200 hover:shadow-md transition space-y-3">
            <div class="w-12 h-12 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center text-xl"><i class="fa-solid fa-hands-holding-child"></i></div>
            <h4 class="font-bold font-poppins text-slate-900">Misi Koperasi</h4>
            <p class="text-xs text-slate-600 leading-relaxed"><?= e((string) ($cms['misiText'] ?? '')) ?></p>
          </div>
          <div class="p-6 rounded-2xl bg-slate-50 border border-slate-200 hover:shadow-md transition space-y-3">
            <div class="w-12 h-12 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center text-xl"><i class="fa-solid fa-shield-heart"></i></div>
            <h4 class="font-bold font-poppins text-slate-900">Nilai Inti</h4>
            <p class="text-xs text-slate-600 leading-relaxed"><?= HtmlSanitizer::clean((string) ($cms['nilaiText'] ?? '')) ?></p>
          </div>
        </div>
      </div>
    </section>

    <!-- UNIT USAHA -->
    <section id="layanan" class="py-16 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
      <div class="text-center max-w-2xl mx-auto space-y-3">
        <h2 class="text-xs font-bold text-kutt-primary uppercase tracking-widest">UNIT BISNIS STRATEGIS</h2>
        <h3 class="text-2xl sm:text-3xl font-extrabold font-poppins text-slate-900">Layanan Lengkap Untuk Kebutuhan Peternak</h3>
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <?php
        $unitColors = ['emerald' => 'bg-emerald-100 text-emerald-600', 'blue' => 'bg-blue-100 text-blue-600', 'amber' => 'bg-amber-100 text-amber-600', 'rose' => 'bg-rose-100 text-rose-600', 'purple' => 'bg-purple-100 text-purple-600', 'slate' => 'bg-slate-200 text-slate-700'];
        foreach ((array) ($cms['units'] ?? []) as $u):
          $u = (array) $u;
          $colorClass = $unitColors[(string) ($u['color'] ?? 'emerald')] ?? $unitColors['emerald'];
        ?>
          <div class="glass-card rounded-2xl p-5 shadow-sm space-y-2 hover:shadow-md transition">
            <div class="w-11 h-11 rounded-xl <?= e($colorClass) ?> flex items-center justify-center text-lg">
              <i class="<?= e((string) ($u['icon'] ?? 'fa-solid fa-circle-check')) ?>"></i>
            </div>
            <h4 class="font-poppins text-sm font-bold text-slate-800"><?= e((string) ($u['title'] ?? '')) ?></h4>
            <p class="text-[11px] text-slate-500"><?= e((string) ($u['desc'] ?? '')) ?></p>
          </div>
        <?php endforeach; ?>
      </div>
    </section>

    <!-- GALERI -->
    <section id="galeri" class="py-16 bg-slate-50 border-y border-slate-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-10">
        <div class="text-center max-w-2xl mx-auto space-y-3">
          <h2 class="text-xs font-bold text-kutt-primary uppercase tracking-widest section-eyebrow">DOKUMENTASI KEGIATAN</h2>
          <h3 class="text-2xl sm:text-3xl font-extrabold font-poppins text-slate-900">Galeri Kegiatan &amp; Fasilitas KUTT</h3>
          <p class="text-xs sm:text-sm text-slate-500">Potret kegiatan operasional, fasilitas, dan program pemberdayaan anggota peternak KUTT Suka Makmur Grati.</p>
        </div>
        <?php $galleryItems = array_values((array) ($cms['gallery'] ?? [])); ?>
        <div id="landingGalleryGrid" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-5">
          <?php foreach ($galleryItems as $i => $g): $g = (array) $g; ?>
            <button type="button" onclick="openGalleryLightbox(<?= $i ?>)" class="gallery-card group focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-kutt-primary">
              <img src="<?= e((string) ($g['gambar'] ?? '')) ?>" alt="<?= e((string) ($g['title'] ?? 'Galeri KUTT')) ?>" loading="lazy">
              <span class="gallery-card-caption"><span class="text-white text-xs font-bold font-poppins leading-snug gallery-title-clamp"><?= e((string) ($g['title'] ?? '')) ?></span></span>
            </button>
          <?php endforeach; ?>
        </div>
        <?php if ($galleryItems === []): ?>
          <p class="text-center text-xs text-slate-400 py-6">
            <i class="fa-solid fa-images text-2xl block mb-2 text-slate-300"></i>
            Galeri belum diisi oleh admin. Silakan kembali lagi nanti.
          </p>
        <?php endif; ?>
      </div>
    </section>

    <!-- VIDEO -->
    <section id="video" class="py-16">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-10">
        <div class="text-center max-w-2xl mx-auto space-y-3">
          <h2 class="text-xs font-bold text-kutt-primary uppercase tracking-widest section-eyebrow">DOKUMENTASI VIDEO</h2>
          <h3 class="text-2xl sm:text-3xl font-extrabold font-poppins text-slate-900">Video Kegiatan &amp; Profil KUTT</h3>
          <p class="text-xs sm:text-sm text-slate-500">Tonton dokumentasi video kegiatan, profil, dan program pemberdayaan anggota peternak KUTT Suka Makmur Grati.</p>
        </div>
        <?php
        $videoItems = array_values(array_filter((array) ($cms['videos'] ?? []), static fn ($v) => (string) ($v['videoId'] ?? '') !== ''));
        ?>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-5">
          <?php foreach ($videoItems as $v): $v = (array) $v; ?>
            <button type="button" onclick="openVideoLightbox('<?= e((string) $v['videoId']) ?>', <?= e(json_encode((string) ($v['title'] ?? ''), JSON_HEX_APOS | JSON_HEX_QUOT)) ?>, <?= e(json_encode((string) ($v['desc'] ?? ''), JSON_HEX_APOS | JSON_HEX_QUOT)) ?>)"
              class="gallery-card group focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-kutt-primary">
              <img src="https://img.youtube.com/vi/<?= e((string) $v['videoId']) ?>/hqdefault.jpg" alt="<?= e((string) ($v['title'] ?? 'Video KUTT')) ?>" loading="lazy">
              <span class="absolute inset-0 flex items-center justify-center pointer-events-none">
                <span class="w-11 h-11 rounded-full bg-white/90 text-kutt-primary flex items-center justify-center text-lg shadow-lg group-hover:scale-110 transition"><i class="fa-solid fa-play"></i></span>
              </span>
              <span class="gallery-card-caption"><span class="text-white text-xs font-bold font-poppins leading-snug gallery-title-clamp"><?= e((string) ($v['title'] ?? '')) ?></span></span>
            </button>
          <?php endforeach; ?>
        </div>
        <?php if ($videoItems === []): ?>
          <p class="text-center text-xs text-slate-400 py-6">
            <i class="fa-solid fa-film text-2xl block mb-2 text-slate-300"></i>
            Video belum diisi oleh admin. Silakan kembali lagi nanti.
          </p>
        <?php endif; ?>
      </div>
    </section>

    <!-- PRODUK -->
    <section id="produk" class="py-16 bg-slate-900 text-white">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-10">
        <div class="flex flex-col md:flex-row items-end justify-between gap-4">
          <div>
            <span class="text-xs font-bold text-amber-400 uppercase tracking-widest">KATALOG UNGGULAN</span>
            <h3 class="text-2xl sm:text-3xl font-extrabold font-poppins text-white mt-1">Produk Susu &amp; Olahan KUTT Grati</h3>
          </div>
          <div class="flex items-center gap-3">
            <p class="text-xs text-slate-400 max-w-md hidden md:block">Diolah dari susu segar murni pilihan peternak Grati, menjamin kualitas gizi tinggi alami tanpa bahan pengawet.</p>
            <a href="/marketplace" class="shrink-0 px-5 py-3 rounded-xl bg-amber-400 hover:bg-amber-300 text-slate-900 text-xs font-bold shadow-lg transition flex items-center gap-2">
              <i class="fa-solid fa-store"></i>Beli via Marketplace
            </a>
          </div>
        </div>
        <div id="productsViewport" onmouseenter="pauseProductCarousel()" onmouseleave="resumeProductCarousel()">
          <div id="landingProductsGrid">
            <?php foreach ((array) ($cms['products'] ?? []) as $p): $p = (array) $p; ?>
              <div class="product-slide">
                <div class="bg-slate-800 rounded-2xl overflow-hidden shadow-lg border border-slate-700 hover:border-kutt-gold transition h-full">
                  <img src="<?= e((string) ($p['gambar'] ?? '')) ?>" alt="<?= e((string) ($p['nama'] ?? '')) ?>" class="w-full h-40 object-cover">
                  <div class="p-4 space-y-1.5">
                    <h4 class="font-poppins font-bold text-sm text-white"><?= e((string) ($p['nama'] ?? '')) ?></h4>
                    <p class="text-[11px] text-slate-400"><?= e((string) ($p['desc'] ?? '')) ?></p>
                    <p class="text-xs font-bold text-amber-400 pt-1"><?= e((string) ($p['harga'] ?? '')) ?></p>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </section>

    <!-- SIMULASI SHU -->
    <section id="kalkulator" class="py-16 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="bg-gradient-to-br from-emerald-900 to-kutt-dark rounded-3xl p-8 sm:p-12 text-white shadow-2xl relative overflow-hidden">
        <div class="blob blob-2 opacity-30"></div>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 items-center relative z-10">
          <div class="space-y-4">
            <span class="text-xs font-bold text-amber-300 uppercase tracking-widest"><i class="fa-solid fa-calculator"></i> TRANSPARANSI ANGGOTA</span>
            <h3 class="text-2xl sm:text-3xl font-extrabold font-poppins">Simulasi Estimasi Pembagian SHU Anggota</h3>
            <p class="text-xs sm:text-sm text-emerald-100 leading-relaxed">Hitung perkiraan Sisa Hasil Usaha (SHU) tahunan Anda berdasarkan total setoran susu harian serta simpanan modal koperasi.</p>
          </div>
          <div class="bg-white/10 backdrop-blur-md p-6 rounded-2xl border border-white/20 space-y-4">
            <div><label class="block text-xs font-medium text-emerald-200 mb-1">Setoran Susu Rata-rata per Hari (Liter)</label><input type="number" id="calcLiter" value="30" oninput="calculateSHU()" class="w-full px-3.5 py-2.5 bg-black/30 border border-white/20 rounded-xl text-white text-sm focus:outline-none focus:ring-2 focus:ring-amber-400"></div>
            <div><label class="block text-xs font-medium text-emerald-200 mb-1">Total Simpanan Pokok &amp; Wajib (Rp)</label><input type="number" id="calcSimpanan" value="5000000" oninput="calculateSHU()" class="w-full px-3.5 py-2.5 bg-black/30 border border-white/20 rounded-xl text-white text-sm focus:outline-none focus:ring-2 focus:ring-amber-400"></div>
            <div class="p-4 bg-emerald-950/80 rounded-xl border border-emerald-500/30 flex items-center justify-between">
              <div>
                <p class="text-[11px] text-emerald-300 font-semibold">Estimasi SHU Diterima / Tahun:</p>
                <h4 class="text-xl font-bold text-amber-400 font-poppins" id="calcResultSHU">Rp 2.850.000</h4>
              </div>
              <i class="fa-solid fa-coins text-3xl text-amber-400"></i>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- KONTAK -->
    <section id="kontak" class="py-16 bg-white border-t border-slate-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-8">
        <div class="space-y-3">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-white border border-slate-200/80 flex items-center justify-center font-bold overflow-hidden">
              <?php if ($logoImage !== ''): ?>
                <img src="<?= e($logoImage) ?>" alt="Logo" class="w-full h-full object-contain">
              <?php else: ?>
                <i class="<?= e($logoIcon) ?>"></i>
              <?php endif; ?>
            </div>
            <h4 class="font-extrabold font-poppins text-slate-900 text-sm"><?= e($brandName) ?></h4>
          </div>
          <p class="text-xs text-slate-500 leading-relaxed"><?= nl2br(e($footerAddr)) ?></p>
          <p class="text-xs text-slate-600"><strong>Call Center:</strong> <?= e($footerPhone) ?></p>
        </div>
        <div class="space-y-2 text-xs text-slate-600">
          <h5 class="font-bold font-poppins text-slate-900 text-sm mb-3">Jam Operasional Kantor</h5>
          <p class="space-y-1">
            <span class="flex justify-between border-b pb-1"><span>Senin - Jumat:</span> <strong><?= e((string) ($cms['footerHourWeekday'] ?? '')) ?></strong></span>
            <span class="flex justify-between border-b pb-1"><span>Sabtu:</span> <strong><?= e((string) ($cms['footerHourSaturday'] ?? '')) ?></strong></span>
            <span class="flex justify-between"><span>Penampungan Susu:</span> <strong class="text-emerald-700">24 Jam Setiap Hari</strong></span>
          </p>
        </div>
        <div class="space-y-2">
          <h5 class="font-bold font-poppins text-slate-900 text-sm flex items-center gap-1.5"><i class="fa-solid fa-satellite text-kutt-primary text-xs"></i> Lokasi Kantor</h5>
          <?php
          $mapLat = (string) ($cms['mapLat'] ?? '-7.68');
          $mapLng = (string) ($cms['mapLng'] ?? '113.005');
          ?>
          <div class="rounded-xl overflow-hidden border border-slate-200 shadow-sm h-28 bg-slate-100">
            <iframe src="https://maps.google.com/maps?q=<?= e(urlencode($mapLat . ',' . $mapLng)) ?>&z=15&output=embed" title="Peta Lokasi Kantor (Satelit)" class="w-full h-full border-0" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
          </div>
          <a href="https://www.google.com/maps/search/?api=1&query=<?= e(urlencode($mapLat . ',' . $mapLng)) ?>" target="_blank" rel="noopener" class="text-[11px] font-bold text-kutt-primary hover:underline flex items-center gap-1">
            <span>Buka di Google Maps</span><i class="fa-solid fa-arrow-up-right-from-square text-[9px]"></i>
          </a>
        </div>
        <div class="space-y-3">
          <h5 class="font-bold font-poppins text-slate-900 text-sm">Akses Cepat System</h5>
          <a href="/login"
            class="w-full py-3 bg-kutt-primary hover:opacity-90 text-white text-xs font-bold rounded-xl shadow transition flex items-center justify-center gap-2">
            <i class="fa-solid fa-lock"></i><span>Login Pengurus &amp; Anggota</span>
          </a>
          <p class="text-[11px] text-center text-slate-400">Sistem Informasi Koperasi Digital SaaS Enterprise</p>
        </div>
      </div>
      <div class="mt-12 pt-6 border-t border-slate-200 text-center text-xs text-slate-500">
        <p><?= e($footerCopy) ?></p>
      </div>
    </section>
  </div>

  <!-- Lightbox Galeri -->
  <div id="galleryLightbox" onclick="closeGalleryLightbox(event)" class="lightbox hidden fixed inset-0 z-[60] bg-slate-950/90 backdrop-blur-sm flex items-center justify-center p-4">
    <div onclick="event.stopPropagation()" class="relative max-w-2xl w-full bg-white rounded-2xl overflow-hidden shadow-2xl max-h-[90vh] flex flex-col">
      <button type="button" onclick="closeGalleryLightbox()" aria-label="Tutup" class="absolute top-3 right-3 z-10 w-9 h-9 rounded-full bg-white/95 text-slate-700 flex items-center justify-center shadow hover:bg-white transition">
        <i class="fa-solid fa-xmark"></i>
      </button>
      <button type="button" onclick="showGalleryLightboxNav(-1)" aria-label="Sebelumnya" class="hidden sm:flex absolute left-3 top-1/2 -translate-y-1/2 z-10 w-9 h-9 rounded-full bg-white/90 text-slate-700 items-center justify-center shadow hover:bg-white transition">
        <i class="fa-solid fa-chevron-left"></i>
      </button>
      <button type="button" onclick="showGalleryLightboxNav(1)" aria-label="Berikutnya" class="hidden sm:flex absolute right-3 top-1/2 -translate-y-1/2 z-10 w-9 h-9 rounded-full bg-white/90 text-slate-700 items-center justify-center shadow hover:bg-white transition">
        <i class="fa-solid fa-chevron-right"></i>
      </button>
      <img id="galleryLightboxImg" src="" alt="" class="w-full max-h-[55vh] object-cover bg-slate-100 shrink-0">
      <div class="p-5 space-y-1.5 overflow-y-auto custom-scrollbar">
        <h4 id="galleryLightboxTitle" class="font-poppins font-bold text-slate-900 text-sm"></h4>
        <p id="galleryLightboxDesc" class="text-xs text-slate-600 leading-relaxed"></p>
      </div>
    </div>
  </div>

  <!-- Lightbox Video -->
  <div id="videoLightbox" onclick="closeVideoLightbox(event)" class="lightbox hidden fixed inset-0 z-[60] bg-slate-950/90 backdrop-blur-sm flex items-center justify-center p-4">
    <div onclick="event.stopPropagation()" class="relative max-w-2xl w-full bg-slate-900 rounded-2xl overflow-hidden shadow-2xl max-h-[90vh] flex flex-col">
      <button type="button" onclick="closeVideoLightbox()" aria-label="Tutup" class="absolute top-3 right-3 z-10 w-9 h-9 rounded-full bg-white/95 text-slate-700 flex items-center justify-center shadow hover:bg-white transition">
        <i class="fa-solid fa-xmark"></i>
      </button>
      <div class="w-full aspect-video bg-black">
        <iframe id="videoLightboxFrame" src="" title="Video KUTT" class="w-full h-full" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe>
      </div>
      <div class="p-5 space-y-1.5 bg-slate-900 text-left">
        <h4 id="videoLightboxTitle" class="font-poppins font-bold text-slate-100 text-sm"></h4>
        <p id="videoLightboxDesc" class="text-xs text-slate-300 leading-relaxed"></p>
      </div>
    </div>
  </div>

  <script>
    // Portal data dari PHP (sudah di-escape saat dirender di server)
    var heroImages = <?= json_encode($heroImages, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?>;

    var heroCarouselTimer = null, heroCarouselIndex = 0, heroCarouselCount = 0, heroCarouselPaused = false;

    function renderHeroCarousel() {
      var track = document.getElementById('heroCarouselTrack');
      var dotsWrap = document.getElementById('heroCarouselDots');
      if (!track || !dotsWrap) return;
      if (!heroImages.length) return;
      heroCarouselCount = heroImages.length;
      heroCarouselIndex = 0;
      var fallback = 'https://placehold.co/1200x800/0B7A3E/FFFFFF?text=KUTT+Suka+Makmur+Grati';
      var slidesHtml = heroImages.map(function (url, i) {
        return '<div class="hero-slide"><img src="' + url + '" alt="Galeri KUTT Suka Makmur Grati ' + (i + 1) + '" onerror="this.src=\'' + fallback + '\'"></div>';
      }).join('');
      if (heroImages.length > 1) {
        slidesHtml += '<div class="hero-slide"><img src="' + heroImages[0] + '" alt="Galeri KUTT Suka Makmur Grati (ulang)" onerror="this.src=\'' + fallback + '\'"></div>';
      }
      track.innerHTML = slidesHtml;
      track.style.transition = 'none';
      track.style.transform = 'translateX(0%)';
      void track.offsetWidth;
      track.style.transition = '';
      dotsWrap.innerHTML = heroImages.map(function (img, i) {
        return '<span class="hero-carousel-dot' + (i === 0 ? ' active' : '') + '" onclick="goToHeroSlide(' + i + ')"></span>';
      }).join('');
      if (heroCarouselTimer) clearInterval(heroCarouselTimer);
      if (heroCarouselCount > 1) heroCarouselTimer = setInterval(advanceHeroCarousel, 4500);
    }

    function advanceHeroCarousel() {
      if (heroCarouselPaused || heroCarouselCount <= 1) return;
      heroCarouselIndex++;
      moveHeroCarouselTo(heroCarouselIndex);
      if (heroCarouselIndex >= heroCarouselCount) {
        var track = document.getElementById('heroCarouselTrack');
        var onDone = function () {
          track.removeEventListener('transitionend', onDone);
          track.style.transition = 'none';
          heroCarouselIndex = 0;
          track.style.transform = 'translateX(0%)';
          void track.offsetWidth;
          track.style.transition = '';
          updateHeroDots(0);
        };
        track.addEventListener('transitionend', onDone);
      }
    }

    function moveHeroCarouselTo(idx) {
      var track = document.getElementById('heroCarouselTrack');
      track.style.transform = 'translateX(-' + (idx * 100) + '%)';
      updateHeroDots(idx % heroCarouselCount);
    }

    function goToHeroSlide(idx) { heroCarouselIndex = idx; moveHeroCarouselTo(idx); }
    function updateHeroDots(activeIdx) {
      var dots = document.querySelectorAll('#heroCarouselDots .hero-carousel-dot');
      dots.forEach(function (d, i) { d.classList.toggle('active', i === activeIdx); });
    }
    function pauseHeroCarousel() { heroCarouselPaused = true; }
    function resumeHeroCarousel() { heroCarouselPaused = false; }

    function toggleMobileNav() {
      var drawer = document.getElementById('mobileNavDrawer');
      var backdrop = document.getElementById('mobileNavBackdrop');
      var icon = document.getElementById('mobileMenuIcon');
      if (!drawer || !backdrop) return;
      var isOpen = drawer.classList.contains('translate-x-0');
      if (isOpen) {
        drawer.classList.remove('translate-x-0'); drawer.classList.add('translate-x-full');
        backdrop.classList.add('hidden'); document.body.classList.remove('overflow-hidden');
        if (icon) icon.className = 'fa-solid fa-bars';
      } else {
        drawer.classList.remove('translate-x-full'); drawer.classList.add('translate-x-0');
        backdrop.classList.remove('hidden'); document.body.classList.add('overflow-hidden');
        if (icon) icon.className = 'fa-solid fa-xmark';
      }
    }

    function calculateSHU() {
      var liter = Number(document.getElementById('calcLiter').value) || 0;
      var simpanan = Number(document.getElementById('calcSimpanan').value) || 0;
      var estimasi = Math.round((liter * 500 * 365 * 0.02) + (simpanan * 0.05));
      document.getElementById('calcResultSHU').innerText = 'Rp ' + estimasi.toLocaleString('id-ID');
    }

    // Galeri lightbox
    var galleryItems = <?= json_encode(array_map(static function ($g) {
        return ['gambar' => (string) ($g['gambar'] ?? ''), 'title' => (string) ($g['title'] ?? ''), 'desc' => (string) ($g['desc'] ?? '')];
    }, $galleryItems), JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    var galleryLightboxIndex = 0;

    function openGalleryLightbox(idx) {
      var g = galleryItems[idx];
      if (!g) return;
      galleryLightboxIndex = idx;
      var img = document.getElementById('galleryLightboxImg');
      img.src = g.gambar; img.alt = g.title || 'Galeri KUTT';
      document.getElementById('galleryLightboxTitle').innerText = g.title || '';
      document.getElementById('galleryLightboxDesc').innerText = g.desc || '';
      document.getElementById('galleryLightbox').classList.remove('hidden');
      document.body.classList.add('overflow-hidden');
    }

    function closeGalleryLightbox(evt) {
      if (evt && evt.target && evt.currentTarget && evt.target !== evt.currentTarget) return;
      document.getElementById('galleryLightbox').classList.add('hidden');
      document.body.classList.remove('overflow-hidden');
    }

    function showGalleryLightboxNav(direction) {
      if (!galleryItems.length) return;
      galleryLightboxIndex = (galleryLightboxIndex + direction + galleryItems.length) % galleryItems.length;
      openGalleryLightbox(galleryLightboxIndex);
    }

    // Video lightbox
    function openVideoLightbox(videoId, title, desc) {
      document.getElementById('videoLightboxFrame').src = 'https://www.youtube.com/embed/' + encodeURIComponent(videoId) + '?autoplay=1&rel=0';
      document.getElementById('videoLightboxTitle').innerText = title || '';
      document.getElementById('videoLightboxDesc').innerText = desc || '';
      document.getElementById('videoLightbox').classList.remove('hidden');
      document.body.classList.add('overflow-hidden');
    }

    function closeVideoLightbox(evt) {
      if (evt && evt.target && evt.currentTarget && evt.target !== evt.currentTarget) return;
      var frame = document.getElementById('videoLightboxFrame');
      if (frame) frame.src = '';
      document.getElementById('videoLightbox').classList.add('hidden');
      document.body.classList.remove('overflow-hidden');
    }

    document.addEventListener('keydown', function (e) {
      ['galleryLightbox', 'videoLightbox'].forEach(function (id) {
        var lb = document.getElementById(id);
        if (!lb || lb.classList.contains('hidden')) return;
        if (e.key === 'Escape') (id === 'galleryLightbox' ? closeGalleryLightbox : closeVideoLightbox)();
        if (id === 'galleryLightbox') {
          if (e.key === 'ArrowRight') showGalleryLightboxNav(1);
          if (e.key === 'ArrowLeft') showGalleryLightboxNav(-1);
        }
      });
    });

    // Nav scroll effect (legacy)
    window.addEventListener('scroll', function () {
      var header = document.querySelector('.glass-nav');
      if (header) header.classList.toggle('nav-scrolled', window.scrollY > 24);
    });

    renderHeroCarousel();
    calculateSHU();
  </script>
</body>

</html>
