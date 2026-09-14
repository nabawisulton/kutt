<?php

/**
 * CMS Landing Page editor. Variables: $cms (Portal::content()).
 * Editor kartu (unit/galeri/video/produk) menyimpan payload JSON ke form
 * tersembunyi lalu submit ke endpoint masing-masing.
 */

use App\Core\Csrf;

$cms = $cms ?? [];
$js  = static fn (mixed $v): string => (string) json_encode($v, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT);
?>

<div class="flex flex-wrap items-center gap-2 mb-4" role="tablist">
  <button type="button" onclick="showCmsTab('teks')" id="cmstab-teks" class="cms-tab-btn px-4 py-2 rounded-xl text-xs font-bold bg-brand-600 text-white shadow transition"><i class="fa-solid fa-font mr-1"></i>Teks &amp; Hero</button>
  <button type="button" onclick="showCmsTab('unit')" id="cmstab-unit" class="cms-tab-btn px-4 py-2 rounded-xl text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition"><i class="fa-solid fa-store mr-1"></i>Unit Usaha</button>
  <button type="button" onclick="showCmsTab('galeri')" id="cmstab-galeri" class="cms-tab-btn px-4 py-2 rounded-xl text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition"><i class="fa-solid fa-images mr-1"></i>Galeri</button>
  <button type="button" onclick="showCmsTab('video')" id="cmstab-video" class="cms-tab-btn px-4 py-2 rounded-xl text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition"><i class="fa-solid fa-film mr-1"></i>Video</button>
  <button type="button" onclick="showCmsTab('produk')" id="cmstab-produk" class="cms-tab-btn px-4 py-2 rounded-xl text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition"><i class="fa-solid fa-box-open mr-1"></i>Produk</button>
  <a href="/" target="_blank" class="ml-auto px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition"><i class="fa-solid fa-arrow-up-right-from-square mr-1"></i>Lihat Portal</a>
</div>

<!-- ============================ TAB: TEKS ============================ -->
<div id="cms-panel-teks" class="cms-panel space-y-5">
  <div class="glass-card rounded-2xl p-5 shadow-sm">
    <h3 class="font-display text-sm font-bold text-slate-800 dark:text-slate-100 mb-4"><i class="fa-solid fa-flag mr-1 text-brand-600"></i>Hero Section</h3>
    <form method="post" action="/cms/text" class="space-y-3">
      <?= Csrf::field() ?>
      <div>
        <label class="text-[10px] font-bold text-slate-500 uppercase">Badge Hero</label>
        <input type="text" name="heroBadge" value="<?= e((string) $cms['heroBadge']) ?>" class="w-full mt-1 px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800">
      </div>
      <div>
        <label class="text-[10px] font-bold text-slate-500 uppercase">Judul Hero</label>
        <input type="text" name="heroTitle" value="<?= e((string) $cms['heroTitle']) ?>" class="w-full mt-1 px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800">
      </div>
      <div>
        <label class="text-[10px] font-bold text-slate-500 uppercase">Sub Judul Hero</label>
        <textarea name="heroSubtitle" rows="3" class="w-full mt-1 px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800"><?= e((string) $cms['heroSubtitle']) ?></textarea>
      </div>
      <div>
        <label class="text-[10px] font-bold text-slate-500 uppercase">Gambar Carousel Hero (URL gambar, satu per baris, maks 7)</label>
        <textarea name="heroImages" rows="4" class="w-full mt-1 px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800 font-mono"><?= e(implode("\n", (array) $cms['heroImages'])) ?></textarea>
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-2 border-t border-slate-100 dark:border-slate-800">
        <div>
          <label class="text-[10px] font-bold text-slate-500 uppercase">Statistik Anggota</label>
          <input type="text" name="statAnggota" value="<?= e((string) $cms['statAnggota']) ?>" class="w-full mt-1 px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800">
        </div>
        <div>
          <label class="text-[10px] font-bold text-slate-500 uppercase">Statistik Susu / Hari</label>
          <input type="text" name="statSusu" value="<?= e((string) $cms['statSusu']) ?>" class="w-full mt-1 px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800">
        </div>
        <div>
          <label class="text-[10px] font-bold text-slate-500 uppercase">Statistik Pengalaman</label>
          <input type="text" name="statPengalaman" value="<?= e((string) $cms['statPengalaman']) ?>" class="w-full mt-1 px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800">
        </div>
      </div>
      <div class="flex justify-end pt-2">
        <button class="px-5 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow"><i class="fa-solid fa-floppy-disk mr-1"></i>Simpan Hero</button>
      </div>
    </form>
  </div>

  <div class="glass-card rounded-2xl p-5 shadow-sm">
    <h3 class="font-display text-sm font-bold text-slate-800 dark:text-slate-100 mb-4"><i class="fa-solid fa-building-columns mr-1 text-brand-600"></i>Profil, Visi &amp; Misi</h3>
    <form method="post" action="/cms/text" class="space-y-3">
      <?= Csrf::field() ?>
      <div>
        <label class="text-[10px] font-bold text-slate-500 uppercase">Deskripsi Profil</label>
        <textarea name="profileDesc" rows="3" class="w-full mt-1 px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800"><?= e((string) $cms['profileDesc']) ?></textarea>
      </div>
      <div>
        <label class="text-[10px] font-bold text-slate-500 uppercase">Visi</label>
        <textarea name="visiText" rows="3" class="w-full mt-1 px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800"><?= e((string) $cms['visiText']) ?></textarea>
      </div>
      <div>
        <label class="text-[10px] font-bold text-slate-500 uppercase">Misi</label>
        <textarea name="misiText" rows="3" class="w-full mt-1 px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800"><?= e((string) $cms['misiText']) ?></textarea>
      </div>
      <div>
        <label class="text-[10px] font-bold text-slate-500 uppercase">Nilai Inti (HTML sederhana diizinkan: strong, br)</label>
        <textarea name="nilaiText" rows="3" class="w-full mt-1 px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800 font-mono"><?= e((string) $cms['nilaiText']) ?></textarea>
      </div>
      <div class="flex justify-end pt-2">
        <button class="px-5 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow"><i class="fa-solid fa-floppy-disk mr-1"></i>Simpan Profil</button>
      </div>
    </form>
  </div>

  <div class="glass-card rounded-2xl p-5 shadow-sm">
    <h3 class="font-display text-sm font-bold text-slate-800 dark:text-slate-100 mb-4"><i class="fa-solid fa-map-location-dot mr-1 text-brand-600"></i>Kontak &amp; Lokasi</h3>
    <form method="post" action="/cms/text" class="grid grid-cols-1 md:grid-cols-2 gap-3">
      <?= Csrf::field() ?>
      <div>
        <label class="text-[10px] font-bold text-slate-500 uppercase">Jam Senin - Jumat</label>
        <input type="text" name="footerHourWeekday" value="<?= e((string) $cms['footerHourWeekday']) ?>" class="w-full mt-1 px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800">
      </div>
      <div>
        <label class="text-[10px] font-bold text-slate-500 uppercase">Jam Sabtu</label>
        <input type="text" name="footerHourSaturday" value="<?= e((string) $cms['footerHourSaturday']) ?>" class="w-full mt-1 px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800">
      </div>
      <div>
        <label class="text-[10px] font-bold text-slate-500 uppercase">Latitude Peta</label>
        <input type="text" name="mapLat" value="<?= e((string) $cms['mapLat']) ?>" class="w-full mt-1 px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800 font-mono">
      </div>
      <div>
        <label class="text-[10px] font-bold text-slate-500 uppercase">Longitude Peta</label>
        <input type="text" name="mapLng" value="<?= e((string) $cms['mapLng']) ?>" class="w-full mt-1 px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800 font-mono">
      </div>
      <div class="md:col-span-2 flex justify-end pt-2 border-t border-slate-100 dark:border-slate-800">
        <button class="px-5 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow"><i class="fa-solid fa-floppy-disk mr-1"></i>Simpan Kontak</button>
      </div>
    </form>
    <p class="text-[10px] text-slate-400 mt-3"><i class="fa-solid fa-circle-info mr-1"></i>Alamat, telepon, copyright, logo, dan warna diatur di menu <a href="/settings" class="text-brand-600 font-bold hover:underline">Pengaturan</a>.</p>
  </div>
</div>

<!-- ============================ TAB: UNIT ============================ -->
<div id="cms-panel-unit" class="cms-panel hidden">
  <div class="glass-card rounded-2xl p-5 shadow-sm">
    <div class="flex items-center justify-between mb-4">
      <h3 class="font-display text-sm font-bold text-slate-800 dark:text-slate-100"><i class="fa-solid fa-store mr-1 text-brand-600"></i>Unit Usaha (maks 12)</h3>
      <button type="button" onclick="addUnit()" class="px-3 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow"><i class="fa-solid fa-plus mr-1"></i>Tambah</button>
    </div>
    <div id="units-editor" class="grid grid-cols-1 md:grid-cols-2 gap-3"></div>
    <form method="post" action="/cms/units" id="units-form" class="hidden">
      <?= Csrf::field() ?>
      <input type="hidden" name="units_payload" id="units_payload">
    </form>
    <div class="flex justify-end mt-4 pt-3 border-t border-slate-100 dark:border-slate-800">
      <button type="button" onclick="submitUnits()" class="px-5 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow"><i class="fa-solid fa-floppy-disk mr-1"></i>Simpan Unit Usaha</button>
    </div>
  </div>
</div>

<!-- ============================ TAB: GALERI ============================ -->
<div id="cms-panel-galeri" class="cms-panel hidden">
  <div class="glass-card rounded-2xl p-5 shadow-sm">
    <div class="flex items-center justify-between mb-4">
      <h3 class="font-display text-sm font-bold text-slate-800 dark:text-slate-100"><i class="fa-solid fa-images mr-1 text-brand-600"></i>Galeri (maks 24)</h3>
      <button type="button" onclick="addGallery()" class="px-3 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow"><i class="fa-solid fa-plus mr-1"></i>Tambah</button>
    </div>
    <div id="gallery-editor" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3"></div>
    <form method="post" action="/cms/gallery" id="gallery-form" enctype="multipart/form-data" class="hidden">
      <?= Csrf::field() ?>
      <input type="hidden" name="gallery_payload" id="gallery_payload">
    </form>
    <div class="flex justify-end mt-4 pt-3 border-t border-slate-100 dark:border-slate-800">
      <button type="button" onclick="submitGallery()" class="px-5 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow"><i class="fa-solid fa-floppy-disk mr-1"></i>Simpan Galeri</button>
    </div>
  </div>
</div>

<!-- ============================ TAB: VIDEO ============================ -->
<div id="cms-panel-video" class="cms-panel hidden">
  <div class="glass-card rounded-2xl p-5 shadow-sm">
    <div class="flex items-center justify-between mb-4">
      <h3 class="font-display text-sm font-bold text-slate-800 dark:text-slate-100"><i class="fa-solid fa-film mr-1 text-brand-600"></i>Video YouTube (maks 24)</h3>
      <button type="button" onclick="addVideo()" class="px-3 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow"><i class="fa-solid fa-plus mr-1"></i>Tambah</button>
    </div>
    <div id="videos-editor" class="grid grid-cols-1 md:grid-cols-2 gap-3"></div>
    <form method="post" action="/cms/videos" id="videos-form" class="hidden">
      <?= Csrf::field() ?>
      <input type="hidden" name="videos_payload" id="videos_payload">
    </form>
    <div class="flex justify-end mt-4 pt-3 border-t border-slate-100 dark:border-slate-800">
      <button type="button" onclick="submitVideos()" class="px-5 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow"><i class="fa-solid fa-floppy-disk mr-1"></i>Simpan Video</button>
    </div>
  </div>
</div>

<!-- ============================ TAB: PRODUK ============================ -->
<div id="cms-panel-produk" class="cms-panel hidden">
  <div class="glass-card rounded-2xl p-5 shadow-sm">
    <div class="flex items-center justify-between mb-4">
      <h3 class="font-display text-sm font-bold text-slate-800 dark:text-slate-100"><i class="fa-solid fa-box-open mr-1 text-brand-600"></i>Katalog Produk (maks 12)</h3>
      <button type="button" onclick="addProduct()" class="px-3 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow"><i class="fa-solid fa-plus mr-1"></i>Tambah</button>
    </div>
    <div id="products-editor" class="grid grid-cols-1 md:grid-cols-2 gap-3"></div>
    <form method="post" action="/cms/products" id="products-form" class="hidden">
      <?= Csrf::field() ?>
      <input type="hidden" name="products_payload" id="products_payload">
    </form>
    <div class="flex justify-end mt-4 pt-3 border-t border-slate-100 dark:border-slate-800">
      <button type="button" onclick="submitProducts()" class="px-5 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow"><i class="fa-solid fa-floppy-disk mr-1"></i>Simpan Produk</button>
    </div>
  </div>
</div>

<script>
  // ------------------------------------------------------------- tabs
  function showCmsTab(name) {
    document.querySelectorAll('.cms-panel').forEach(function (p) { p.classList.add('hidden'); });
    document.querySelectorAll('.cms-tab-btn').forEach(function (b) {
      b.classList.remove('bg-brand-600', 'text-white', 'shadow');
      b.classList.add('text-slate-600', 'dark:text-slate-300');
    });
    document.getElementById('cms-panel-' + name).classList.remove('hidden');
    var btn = document.getElementById('cmstab-' + name);
    btn.classList.add('bg-brand-600', 'text-white', 'shadow');
    btn.classList.remove('text-slate-600', 'dark:text-slate-300');
  }
  // Buka tab sesuai hash (#galeri -> galeri, #video -> video, #layanan -> unit, #produk -> produk)
  (function () {
    var h = location.hash.replace('#', '');
    var map = { galeri: 'galeri', video: 'video', layanan: 'unit', produk: 'produk' };
    if (map[h]) showCmsTab(map[h]);
  })();

  var UNIT_COLORS = ['emerald', 'blue', 'amber', 'rose', 'purple', 'slate'];
  var units = <?= $js($cms['units']) ?>;
  var gallery = <?= $js($cms['gallery']) ?>;
  var videos = <?= $js($cms['videos']) ?>;
  var products = <?= $js($cms['products']) ?>;

  function esc(s) {
    return String(s == null ? '' : s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }

  // ------------------------------------------------------------- units
  function renderUnits() {
    var wrap = document.getElementById('units-editor');
    wrap.innerHTML = units.map(function (u, i) {
      return '<div class="p-4 rounded-2xl border border-slate-200 dark:border-slate-700 space-y-2">'
        + '<div class="flex justify-between items-center"><span class="text-[10px] font-bold text-slate-400 uppercase">Unit ' + (i + 1) + '</span>'
        + '<button type="button" onclick="removeItem(units,' + i + ',renderUnits)" class="text-red-400 hover:text-red-600 text-xs"><i class="fa-solid fa-trash"></i></button></div>'
        + '<input type="text" placeholder="Judul" value="' + esc(u.title) + '" oninput="units[' + i + '].title=this.value" class="w-full px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800">'
        + '<textarea placeholder="Deskripsi" rows="2" oninput="units[' + i + '].desc=this.value" class="w-full px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800">' + esc(u.desc) + '</textarea>'
        + '<div class="flex gap-2"><input type="text" placeholder="fa-solid fa-cow" value="' + esc(u.icon) + '" oninput="units[' + i + '].icon=this.value" class="flex-1 px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800 font-mono">'
        + '<select onchange="units[' + i + '].color=this.value" class="px-2 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800">'
        + UNIT_COLORS.map(function (c) { return '<option value="' + c + '"' + (u.color === c ? ' selected' : '') + '>' + c + '</option>'; }).join('')
        + '</select></div></div>';
    }).join('');
  }
  function addUnit() { if (units.length >= 12) return; units.push({ icon: 'fa-solid fa-circle-check', color: 'emerald', title: '', desc: '' }); renderUnits(); }
  function submitUnits() {
    if (!confirmAction(null, 'Simpan perubahan unit usaha?')) return;
    document.getElementById('units_payload').value = JSON.stringify(units);
    document.getElementById('units-form').submit();
  }

  // ------------------------------------------------------------- gallery
  function renderGallery() {
    var wrap = document.getElementById('gallery-editor');
    wrap.innerHTML = gallery.map(function (g, i) {
      return '<div class="p-4 rounded-2xl border border-slate-200 dark:border-slate-700 space-y-2">'
        + '<div class="flex justify-between items-center"><span class="text-[10px] font-bold text-slate-400 uppercase">Item ' + (i + 1) + '</span>'
        + '<button type="button" onclick="removeItem(gallery,' + i + ',renderGallery)" class="text-red-400 hover:text-red-600 text-xs"><i class="fa-solid fa-trash"></i></button></div>'
        + '<img src="' + esc(g.gambar) + '" alt="" onerror="this.style.opacity=0.3" class="w-full h-28 object-cover rounded-xl bg-slate-100">'
        + '<input type="text" placeholder="Judul" value="' + esc(g.title) + '" oninput="gallery[' + i + '].title=this.value" class="w-full px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800">'
        + '<textarea placeholder="Deskripsi" rows="2" oninput="gallery[' + i + '].desc=this.value" class="w-full px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800">' + esc(g.desc) + '</textarea>'
        + '<input type="url" placeholder="URL gambar" value="' + esc(g.gambar) + '" oninput="gallery[' + i + '].gambar=this.value" class="w-full px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800">'
        + '<label class="block text-[10px] font-bold text-slate-400 uppercase">atau Upload Gambar</label>'
        + '<input type="file" name="gallery_file_' + i + '" accept="image/*" class="w-full text-[11px]">'
        + '</div>';
    }).join('');
  }
  function addGallery() { if (gallery.length >= 24) return; gallery.push({ title: '', desc: '', gambar: '' }); renderGallery(); }
  function submitGallery() {
    if (!confirmAction(null, 'Simpan perubahan galeri?')) return;
    document.getElementById('gallery_payload').value = JSON.stringify(gallery);
    document.getElementById('gallery-form').submit();
  }

  // ------------------------------------------------------------- videos
  function renderVideos() {
    var wrap = document.getElementById('videos-editor');
    wrap.innerHTML = videos.map(function (v, i) {
      return '<div class="p-4 rounded-2xl border border-slate-200 dark:border-slate-700 space-y-2">'
        + '<div class="flex justify-between items-center"><span class="text-[10px] font-bold text-slate-400 uppercase">Video ' + (i + 1) + '</span>'
        + '<button type="button" onclick="removeItem(videos,' + i + ',renderVideos)" class="text-red-400 hover:text-red-600 text-xs"><i class="fa-solid fa-trash"></i></button></div>'
        + '<input type="text" placeholder="Video ID YouTube (mis. dQw4w9WgXcQ)" value="' + esc(v.videoId) + '" oninput="videos[' + i + '].videoId=this.value" class="w-full px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800 font-mono">'
        + '<input type="text" placeholder="Judul" value="' + esc(v.title) + '" oninput="videos[' + i + '].title=this.value" class="w-full px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800">'
        + '<textarea placeholder="Deskripsi" rows="2" oninput="videos[' + i + '].desc=this.value" class="w-full px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800">' + esc(v.desc) + '</textarea>'
        + '</div>';
    }).join('');
  }
  function addVideo() { if (videos.length >= 24) return; videos.push({ videoId: '', title: '', desc: '' }); renderVideos(); }
  function submitVideos() {
    if (!confirmAction(null, 'Simpan perubahan video?')) return;
    document.getElementById('videos_payload').value = JSON.stringify(videos);
    document.getElementById('videos-form').submit();
  }

  // ------------------------------------------------------------- products
  function renderProducts() {
    var wrap = document.getElementById('products-editor');
    wrap.innerHTML = products.map(function (p, i) {
      return '<div class="p-4 rounded-2xl border border-slate-200 dark:border-slate-700 space-y-2">'
        + '<div class="flex justify-between items-center"><span class="text-[10px] font-bold text-slate-400 uppercase">Produk ' + (i + 1) + '</span>'
        + '<button type="button" onclick="removeItem(products,' + i + ',renderProducts)" class="text-red-400 hover:text-red-600 text-xs"><i class="fa-solid fa-trash"></i></button></div>'
        + '<input type="text" placeholder="Nama Produk" value="' + esc(p.nama) + '" oninput="products[' + i + '].nama=this.value" class="w-full px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800">'
        + '<input type="text" placeholder="Harga (mis. Rp 12.000 / Liter)" value="' + esc(p.harga) + '" oninput="products[' + i + '].harga=this.value" class="w-full px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800">'
        + '<textarea placeholder="Deskripsi" rows="2" oninput="products[' + i + '].desc=this.value" class="w-full px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800">' + esc(p.desc) + '</textarea>'
        + '<input type="url" placeholder="URL gambar produk" value="' + esc(p.gambar) + '" oninput="products[' + i + '].gambar=this.value" class="w-full px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800">'
        + '</div>';
    }).join('');
  }
  function addProduct() { if (products.length >= 12) return; products.push({ nama: '', harga: '', desc: '', gambar: '' }); renderProducts(); }
  function submitProducts() {
    if (!confirmAction(null, 'Simpan perubahan produk?')) return;
    document.getElementById('products_payload').value = JSON.stringify(products);
    document.getElementById('products-form').submit();
  }

  function removeItem(arr, idx, rerender) { arr.splice(idx, 1); rerender(); }

  renderUnits(); renderGallery(); renderVideos(); renderProducts();
</script>
