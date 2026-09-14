<?php
/**
 * Portal berita publik — halaman penuh gaya news portal (hero slider geser +
 * daftar terkini + sidebar terpopuler). Variables: posts, featured, popular,
 * categories, activeCategory, q, page, pages, total, seoTitle, seoDesc,
 * seoImage, brand.
 */

use App\Support\PortalLayout;

$posts = $posts ?? [];
$featured = $featured ?? [];
$popular = $popular ?? [];
$categories = $categories ?? [];
$activeCategory = $activeCategory ?? null;
$q = $q ?? '';
$page = (int) ($page ?? 1);
$pages = (int) ($pages ?? 1);
$total = (int) ($total ?? 0);
$brand = $brand ?? \App\Models\Setting::all();

$navItems = [
    ['path' => '/', 'label' => 'Beranda'],
    ['path' => '/berita', 'label' => 'Berita & Kegiatan'],
    ['path' => '/login', 'label' => 'Portal Anggota'],
];

/** Kartu berita vertikal dipakai berulang di grid & sidebar. */
function news_card(array $post): void
{
    ?>
    <a href="/berita/<?= e((string) $post['slug']) ?>" class="group glass-card rounded-2xl overflow-hidden shadow-sm hover:shadow-xl transition flex flex-col">
      <div class="h-44 bg-slate-100 overflow-hidden shrink-0 relative">
        <?php if (!empty($post['image_path'])): ?>
          <img src="<?= e(news_image_src($post['image_path'])) ?>" alt="<?= e((string) $post['title']) ?>" class="w-full h-full object-cover group-hover:scale-105 transition duration-500" loading="lazy">
        <?php else: ?>
          <div class="w-full h-full flex items-center justify-center text-3xl text-slate-300"><i class="fa-solid fa-image"></i></div>
        <?php endif; ?>
        <span class="absolute top-3 left-3 px-2.5 py-1 rounded-lg bg-white/90 backdrop-blur text-[10px] font-bold text-kutt-primary uppercase"><?= e((string) ($post['category_name'] ?? 'Umum')) ?></span>
      </div>
      <div class="p-5 space-y-2 flex-1 flex flex-col">
        <p class="text-[10px] font-semibold text-slate-400 uppercase"><?= e(tanggal((string) ($post['published_at'] ?? $post['created_at']))) ?></p>
        <h3 class="font-poppins font-bold text-sm text-slate-800 leading-snug group-hover:text-kutt-primary transition line-clamp-2"><?= e((string) $post['title']) ?></h3>
        <p class="text-[11px] text-slate-500 leading-relaxed line-clamp-2 flex-1"><?= e(mb_substr(strip_tags((string) ($post['excerpt'] ?: $post['body'])), 0, 120)) ?>...</p>
        <div class="flex items-center gap-3.5 pt-2 border-t border-slate-100 text-[10px] text-slate-400 font-semibold">
          <span><i class="fa-solid fa-eye mr-1 text-kutt-primary"></i><?= number_format((int) ($post['views'] ?? 0)) ?></span>
          <span><i class="fa-solid fa-thumbs-up mr-1 text-kutt-primary"></i><?= number_format((int) ($post['likes'] ?? 0)) ?></span>
          <span><i class="fa-solid fa-comment mr-1 text-kutt-primary"></i><?= number_format((int) ($post['comments_count'] ?? 0)) ?></span>
        </div>
      </div>
    </a>
    <?php
}

header('Content-Type: text/html; charset=UTF-8');
PortalLayout::header($brand, $navItems, '/berita');
?>

<main class="pt-28 pb-16">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

    <!-- HEADER -->
    <div class="text-center max-w-2xl mx-auto space-y-3">
      <h2 class="text-xs font-bold text-kutt-primary uppercase tracking-widest section-eyebrow">PORTAL INFORMASI</h2>
      <h1 class="text-2xl sm:text-3xl font-extrabold font-poppins text-slate-900">Berita &amp; Kegiatan KUTT Suka Makmur</h1>
      <p class="text-xs sm:text-sm text-slate-500">Kabar terbaru kegiatan, program, dan pengumuman resmi koperasi.</p>
    </div>

    <!-- HERO SLIDER (berita terbaru, bergeser otomatis + bisa digeser) -->
    <?php if ($featured !== []): ?>
    <section id="newsHero" class="relative rounded-3xl overflow-hidden shadow-xl bg-kutt-dark select-none" aria-label="Berita terbaru">
      <div id="newsTrack" class="flex transition-transform duration-700 ease-out">
        <?php foreach ($featured as $i => $post): ?>
        <a href="/berita/<?= e((string) $post['slug']) ?>" class="hero-slide relative w-full shrink-0 <?= $i === 0 ? '' : 'hidden' ?>" data-slide="<?= $i ?>">
          <div class="h-[300px] sm:h-[380px] lg:h-[440px] w-full bg-slate-800">
            <?php if (!empty($post['image_path'])): ?>
              <img src="<?= e(news_image_src($post['image_path'])) ?>" alt="<?= e((string) $post['title']) ?>" class="w-full h-full object-cover" <?= $i === 0 ? '' : 'loading="lazy"' ?>>
            <?php else: ?>
              <div class="w-full h-full flex items-center justify-center text-6xl text-white/20"><i class="fa-solid fa-newspaper"></i></div>
            <?php endif; ?>
          </div>
          <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/40 to-transparent"></div>
          <div class="absolute bottom-0 left-0 right-0 p-5 sm:p-8 space-y-2">
            <div class="flex items-center gap-2 flex-wrap">
              <span class="px-2.5 py-1 rounded-lg bg-kutt-gold text-[10px] font-extrabold text-slate-900 uppercase"><?= e((string) ($post['category_name'] ?? 'Umum')) ?></span>
              <span class="text-[11px] font-semibold text-white/80"><?= e(tanggal((string) ($post['published_at'] ?? $post['created_at']))) ?></span>
            </div>
            <h2 class="font-poppins font-extrabold text-white text-lg sm:text-2xl lg:text-3xl leading-tight line-clamp-2 max-w-3xl"><?= e((string) $post['title']) ?></h2>
            <p class="hidden sm:block text-xs sm:text-sm text-white/75 leading-relaxed line-clamp-2 max-w-2xl"><?= e(mb_substr(strip_tags((string) ($post['excerpt'] ?: $post['body'])), 0, 150)) ?>...</p>
          </div>
        </a>
        <?php endforeach; ?>
      </div>

      <!-- Panah navigasi -->
      <button type="button" data-dir="-1" class="slide-btn absolute left-3 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-white/15 hover:bg-white/30 backdrop-blur text-white flex items-center justify-center transition" aria-label="Sebelumnya"><i class="fa-solid fa-chevron-left"></i></button>
      <button type="button" data-dir="1" class="slide-btn absolute right-3 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-white/15 hover:bg-white/30 backdrop-blur text-white flex items-center justify-center transition" aria-label="Berikutnya"><i class="fa-solid fa-chevron-right"></i></button>

      <!-- Dots -->
      <div id="newsDots" class="absolute bottom-4 right-5 flex items-center gap-1.5 z-10">
        <?php foreach ($featured as $i => $post): ?>
          <button type="button" data-dot="<?= $i ?>" class="dot w-2 h-2 rounded-full bg-white/40 transition-all <?= $i === 0 ? 'w-6 bg-kutt-gold' : '' ?>" aria-label="Slide <?= $i + 1 ?>"></button>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endif; ?>

    <!-- FILTER -->
    <form method="get" action="/berita" class="flex flex-wrap items-center justify-center gap-2">
      <input type="text" name="q" value="<?= e($q) ?>" placeholder="Cari berita..."
        class="px-4 py-2.5 text-xs border rounded-xl bg-white focus:ring-2 focus:ring-kutt-primary w-48 sm:w-56 max-w-full">
      <select name="kategori" class="px-3 py-2.5 text-xs border rounded-xl bg-white">
        <option value="">Semua Kategori</option>
        <?php foreach ($categories as $c): ?>
          <option value="<?= (int) $c['id'] ?>" <?= $activeCategory == $c['id'] ? 'selected' : '' ?>><?= e((string) $c['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <button class="px-5 py-2.5 rounded-xl bg-kutt-primary hover:opacity-90 text-white text-xs font-bold shadow"><i class="fa-solid fa-magnifying-glass mr-1"></i>Cari</button>
    </form>

    <!-- KATEGORI CHIPS -->
    <div class="flex flex-wrap items-center justify-center gap-2">
      <a href="/berita" class="px-4 py-1.5 rounded-full text-[11px] font-bold transition <?= $activeCategory === null ? 'bg-kutt-primary text-white shadow' : 'bg-white text-slate-600 border hover:border-kutt-primary hover:text-kutt-primary' ?>">Semua</a>
      <?php foreach ($categories as $c): ?>
        <a href="/berita?kategori=<?= (int) $c['id'] ?>" class="px-4 py-1.5 rounded-full text-[11px] font-bold transition <?= $activeCategory == $c['id'] ? 'bg-kutt-primary text-white shadow' : 'bg-white text-slate-600 border hover:border-kutt-primary hover:text-kutt-primary' ?>"><?= e((string) $c['name']) ?></a>
      <?php endforeach; ?>
    </div>

    <?php if ($posts === []): ?>
      <div class="text-center text-xs text-slate-400 py-16">
        <i class="fa-solid fa-newspaper text-4xl block mb-3 text-slate-200"></i>
        Belum ada berita yang dipublikasikan.
      </div>
    <?php else: ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
      <!-- DAFTAR TERKINI -->
      <div class="lg:col-span-2 space-y-5">
        <div class="flex items-center gap-3">
          <h2 class="font-poppins font-extrabold text-slate-900 text-base sm:text-lg">Terbaru</h2>
          <span class="h-0.5 flex-1 bg-gradient-to-r from-kutt-primary/40 to-transparent rounded-full"></span>
          <span class="text-[11px] font-bold text-slate-400"><?= number_format($total) ?> berita</span>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
          <?php foreach ($posts as $post): news_card($post); endforeach; ?>
        </div>

        <!-- PAGINATION -->
        <?php if ($pages > 1): ?>
        <nav class="flex items-center justify-center gap-1.5 pt-2" aria-label="Navigasi halaman">
          <?php
            $mk = fn (int $p): string => '/berita?' . http_build_query(array_filter(['q' => $q, 'kategori' => $activeCategory, 'page' => $p], fn ($v) => $v !== '' && $v !== null));
          ?>
          <?php if ($page > 1): ?><a href="<?= e($mk($page - 1)) ?>" class="px-3.5 py-2 rounded-xl bg-white border text-xs font-bold text-slate-600 hover:border-kutt-primary hover:text-kutt-primary transition">&laquo;</a><?php endif; ?>
          <?php for ($p = max(1, $page - 2); $p <= min($pages, $page + 2); $p++): ?>
            <a href="<?= e($mk($p)) ?>" class="px-3.5 py-2 rounded-xl text-xs font-bold transition <?= $p === $page ? 'bg-kutt-primary text-white shadow' : 'bg-white border text-slate-600 hover:border-kutt-primary hover:text-kutt-primary' ?>"><?= $p ?></a>
          <?php endfor; ?>
          <?php if ($page < $pages): ?><a href="<?= e($mk($page + 1)) ?>" class="px-3.5 py-2 rounded-xl bg-white border text-xs font-bold text-slate-600 hover:border-kutt-primary hover:text-kutt-primary transition">&raquo;</a><?php endif; ?>
        </nav>
        <?php endif; ?>
      </div>

      <!-- SIDEBAR -->
      <aside class="space-y-5 lg:sticky lg:top-28">
        <div class="glass-card rounded-2xl p-5 shadow-sm">
          <h3 class="font-poppins font-extrabold text-sm text-slate-900 flex items-center gap-2 mb-4"><i class="fa-solid fa-fire text-amber-500"></i>Paling Dibaca</h3>
          <div class="space-y-4">
            <?php foreach ($popular as $i => $p): ?>
              <a href="/berita/<?= e((string) $p['slug']) ?>" class="flex items-start gap-3 group">
                <span class="font-poppins font-extrabold text-xl text-kutt-primary/30 leading-none w-6 shrink-0"><?= $i + 1 ?></span>
                <div class="w-16 h-14 rounded-xl overflow-hidden bg-slate-100 shrink-0">
                  <?php if (!empty($p['image_path'])): ?>
                    <img src="<?= e(news_image_src($p['image_path'])) ?>" alt="" class="w-full h-full object-cover" loading="lazy">
                  <?php else: ?>
                    <div class="w-full h-full flex items-center justify-center text-slate-300"><i class="fa-solid fa-image"></i></div>
                  <?php endif; ?>
                </div>
                <div class="min-w-0">
                  <p class="text-[11px] font-bold text-slate-700 leading-snug line-clamp-2 group-hover:text-kutt-primary transition"><?= e((string) $p['title']) ?></p>
                  <p class="text-[10px] text-slate-400 mt-1"><i class="fa-solid fa-eye mr-1"></i><?= number_format((int) $p['views']) ?></p>
                </div>
              </a>
            <?php endforeach; ?>
            <?php if ($popular === []): ?><p class="text-[11px] text-slate-400">Belum ada data.</p><?php endif; ?>
          </div>
        </div>

        <div class="rounded-2xl p-5 bg-kutt-dark text-white shadow-lg space-y-2">
          <h3 class="font-poppins font-extrabold text-sm">Ikuti Kabar Koperasi</h3>
          <p class="text-[11px] text-white/70 leading-relaxed">Daftar berita terbaru otomatis tayang di halaman ini — kunjungi rutin untuk info kegiatan &amp; pengumuman resmi.</p>
          <a href="/login" class="inline-flex items-center gap-2 mt-2 px-4 py-2 rounded-xl bg-kutt-gold text-slate-900 text-[11px] font-extrabold hover:opacity-90 transition"><i class="fa-solid fa-bell"></i>Portal Anggota</a>
        </div>
      </aside>
    </div>
    <?php endif; ?>
  </div>
</main>

<?php PortalLayout::footer($brand); ?>

<script>
(function () {
  var hero = document.getElementById("newsHero");
  var track = document.getElementById("newsTrack");
  if (!hero || !track) return;

  var slides = Array.prototype.slice.call(track.querySelectorAll(".hero-slide"));
  var dots = Array.prototype.slice.call(hero.querySelectorAll(".dot"));
  var current = 0, timer = null, DELAY = 5000;

  function show(idx) {
    current = (idx + slides.length) % slides.length;
    slides.forEach(function (s, i) {
      s.classList.toggle("hidden", i !== current);
    });
    dots.forEach(function (d, i) {
      d.classList.toggle("w-6", i === current);
      d.classList.toggle("bg-kutt-gold", i === current);
      d.classList.toggle("w-2", i !== current);
      d.classList.toggle("bg-white/40", i !== current);
    });
  }

  function next() { show(current + 1); }
  function start() { stop(); timer = setInterval(next, DELAY); }
  function stop() { if (timer) { clearInterval(timer); timer = null; } }

  hero.querySelectorAll(".slide-btn").forEach(function (b) {
    b.addEventListener("click", function () {
      show(current + parseInt(b.getAttribute("data-dir"), 10));
      start(); // reset timer saat navigasi manual
    });
  });
  dots.forEach(function (d) {
    d.addEventListener("click", function () {
      show(parseInt(d.getAttribute("data-dot"), 10));
      start();
    });
  });

  // Geser (swipe) untuk smartphone
  var x0 = null, y0 = null;
  hero.addEventListener("touchstart", function (e) {
    x0 = e.touches[0].clientX; y0 = e.touches[0].clientY; stop();
  }, { passive: true });
  hero.addEventListener("touchend", function (e) {
    if (x0 === null) return;
    var dx = e.changedTouches[0].clientX - x0;
    var dy = e.changedTouches[0].clientY - y0;
    if (Math.abs(dx) > 40 && Math.abs(dx) > Math.abs(dy)) {
      show(current + (dx < 0 ? 1 : -1));
    }
    x0 = y0 = null; start();
  }, { passive: true });

  hero.addEventListener("mouseenter", stop);
  hero.addEventListener("mouseleave", start);
  start();
})();
</script>
