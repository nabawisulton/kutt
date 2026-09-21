<?php
/**
 * Marketplace publik — katalog produk. Variables: $products, $categories,
 * $activeCategory, $q, $seoTitle, $seoDesc. Layout portal publik dipakai
 * agar navigasi/brand konsisten dengan landing & berita.
 */

use App\Support\PortalLayout;

$products = (array) ($products ?? []);
$categories = (array) ($categories ?? []);
$activeCategory = (string) ($activeCategory ?? '');
$q = (string) ($q ?? '');

$brand = \App\Models\Setting::all();
$navItems = [
    ['path' => '/', 'label' => 'Beranda'],
    ['path' => '/berita', 'label' => 'Berita & Kegiatan'],
    ['path' => '/marketplace', 'label' => 'Belanja Produk'],
    ['path' => '/login', 'label' => 'Portal Anggota'],
];
?>

<?php
PortalLayout::header($brand, $navItems, '/marketplace');
?>

<main class="pt-28 pb-16">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

    <!-- Hero kecil -->
    <div class="rounded-3xl bg-gradient-to-br from-emerald-900 to-kutt-dark p-8 sm:p-10 text-white shadow-2xl relative overflow-hidden">
      <div class="blob blob-2 opacity-30"></div>
      <div class="relative z-10 flex flex-col md:flex-row md:items-end justify-between gap-4">
        <div class="space-y-2">
          <span class="text-xs font-bold text-amber-400 uppercase tracking-widest"><i class="fa-solid fa-store mr-1"></i>MARKETPLACE KOPERASI</span>
          <h1 class="text-2xl sm:text-3xl font-extrabold font-poppins">Belanja Produk KUTT Suka Makmur</h1>
          <p class="text-xs sm:text-sm text-emerald-100 max-w-xl">Susu segar, olahan susu, pakan ternak, dan kebutuhan usaha tani — pesan langsung, konfirmasi cepat via WhatsApp.</p>
        </div>
        <form method="get" action="/marketplace" class="flex gap-2">
          <input type="text" name="q" value="<?= e($q) ?>" placeholder="Cari produk..." class="px-4 py-2.5 text-xs rounded-xl bg-white/10 border border-white/20 text-white placeholder-slate-300 focus:outline-none focus:ring-2 focus:ring-amber-400">
          <button class="px-4 py-2.5 rounded-xl bg-amber-400 hover:bg-amber-300 text-slate-900 text-xs font-bold shadow"><i class="fa-solid fa-magnifying-glass"></i></button>
        </form>
      </div>
    </div>

    <!-- Filter kategori -->
    <div class="flex flex-wrap gap-2">
      <a href="/marketplace<?= $q !== '' ? '?q=' . rawurlencode($q) : '' ?>"
        class="px-4 py-2 rounded-xl text-xs font-bold transition <?= $activeCategory === '' ? 'bg-kutt-primary text-white shadow' : 'glass-card text-slate-600 hover:text-kutt-primary' ?>">
        Semua
      </a>
      <?php foreach ($categories as $cat): ?>
        <a href="/marketplace?kategori=<?= rawurlencode((string) $cat) ?><?= $q !== '' ? '&q=' . rawurlencode($q) : '' ?>"
          class="px-4 py-2 rounded-xl text-xs font-bold transition <?= $activeCategory === $cat ? 'bg-kutt-primary text-white shadow' : 'glass-card text-slate-600 hover:text-kutt-primary' ?>">
          <?= e((string) $cat) ?>
        </a>
      <?php endforeach; ?>
    </div>

    <!-- Grid produk -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
      <?php foreach ($products as $p): $p = (array) $p; ?>
        <a href="/marketplace/<?= (int) $p['id'] ?>" class="group glass-card rounded-2xl overflow-hidden shadow-sm hover:shadow-xl transition flex flex-col">
          <div class="h-44 bg-slate-100 overflow-hidden shrink-0 relative">
            <?php if (!empty($p['image_path'])): ?>
              <img src="<?= e(news_image_src((string) $p['image_path'])) ?>" alt="<?= e((string) $p['name']) ?>" class="w-full h-full object-cover group-hover:scale-105 transition duration-500" loading="lazy">
            <?php else: ?>
              <div class="w-full h-full flex items-center justify-center text-4xl text-slate-300"><i class="fa-solid fa-box"></i></div>
            <?php endif; ?>
            <?php if (!empty($p['category'])): ?>
              <span class="absolute top-3 left-3 px-2.5 py-1 rounded-lg bg-white/90 backdrop-blur text-[10px] font-bold text-kutt-primary uppercase"><?= e((string) $p['category']) ?></span>
            <?php endif; ?>
          </div>
          <div class="p-4 space-y-1.5 flex-1 flex flex-col">
            <h3 class="font-poppins font-bold text-sm text-slate-800 leading-snug group-hover:text-kutt-primary transition line-clamp-2"><?= e((string) $p['name']) ?></h3>
            <p class="text-[11px] text-slate-500 line-clamp-2 flex-1"><?= e(mb_substr(strip_tags((string) ($p['description'] ?? '')), 0, 100)) ?></p>
            <div class="flex items-center justify-between pt-2 border-t border-slate-100">
              <span class="font-extrabold text-kutt-primary text-sm"><?= rupiah((float) $p['price']) ?></span>
              <span class="text-[10px] font-bold <?= (int) $p['stock'] <= 5 ? 'text-amber-500' : 'text-slate-400' ?>">Stok <?= (int) $p['stock'] ?> <?= e((string) $p['unit']) ?></span>
            </div>
          </div>
        </a>
      <?php endforeach; ?>

      <?php if ($products === []): ?>
        <div class="col-span-full text-center py-16 glass-card rounded-2xl">
          <i class="fa-solid fa-box-open text-4xl text-slate-300 mb-3"></i>
          <p class="text-sm text-slate-400 font-semibold">Produk tidak ditemukan.</p>
          <p class="text-xs text-slate-400 mt-1">Coba kata kunci atau kategori lain.</p>
        </div>
      <?php endif; ?>
    </div>
  </div>
</main>

<?php PortalLayout::footer($brand); ?>
