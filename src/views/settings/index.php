<?php

/**
 * Pengaturan aplikasi: identitas/CMS + background kartu anggota (dengan preview).
 * Variables: settings (map), cardBg (type/color/image/opacity).
 */

use App\Core\Csrf;

$settings = $settings ?? [];
$cardBg   = $cardBg ?? ['type' => 'color', 'color' => '#0b7a3e', 'image' => null, 'opacity' => 0.25];
$val = fn (string $key, string $default = ''): string => (string) ($settings[$key] ?? $default);
?>
<div class="grid grid-cols-1 xl:grid-cols-2 gap-5">

  <!-- Identitas / CMS -->
  <div class="glass-card rounded-2xl p-5 shadow-sm">
    <h3 class="font-display text-sm font-bold text-slate-800 dark:text-slate-100 mb-4"><i class="fa-solid fa-globe mr-1 text-brand-600"></i>Identitas Koperasi</h3>
    <form method="post" action="/settings/general" class="grid grid-cols-1 md:grid-cols-2 gap-3">
      <?= Csrf::field() ?>
      <div>
        <label class="text-[10px] font-bold text-slate-500 uppercase">Nama Koperasi</label>
        <input type="text" name="brandName" value="<?= e($val('brandName', 'KUTT SUKA MAKMUR')) ?>" class="w-full mt-1 px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800">
      </div>
      <div>
        <label class="text-[10px] font-bold text-slate-500 uppercase">Sub Brand / Wilayah</label>
        <input type="text" name="subBrand" value="<?= e($val('subBrand', 'Grati - Pasuruan')) ?>" class="w-full mt-1 px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800">
      </div>
      <div>
        <label class="text-[10px] font-bold text-slate-500 uppercase">Warna Primer (hex)</label>
        <div class="flex items-center gap-2 mt-1">
          <input type="color" oninput="this.previousElementSibling.value=this.value" value="<?= e($val('colorPrimary', '#0b7a3e') ?: '#0b7a3e') ?>" class="w-10 h-9 rounded-lg border-0 cursor-pointer">
          <input type="text" name="colorPrimary" value="<?= e($val('colorPrimary', '#0b7a3e')) ?>" pattern="#[0-9a-fA-F]{6}" class="flex-1 px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800 font-mono">
        </div>
      </div>
      <div>
        <label class="text-[10px] font-bold text-slate-500 uppercase">Ikon Logo (class FontAwesome)</label>
        <input type="text" name="logoIcon" value="<?= e($val('logoIcon', 'fa-solid fa-cow')) ?>" class="w-full mt-1 px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800 font-mono">
      </div>
      <div class="md:col-span-2">
        <label class="text-[10px] font-bold text-slate-500 uppercase">Alamat Footer</label>
        <textarea name="footerAddress" rows="2" class="w-full mt-1 px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800"><?= e($val('footerAddress')) ?></textarea>
      </div>
      <div>
        <label class="text-[10px] font-bold text-slate-500 uppercase">Telepon / Kontak</label>
        <input type="text" name="footerPhone" value="<?= e($val('footerPhone')) ?>" class="w-full mt-1 px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800">
      </div>
      <div>
        <label class="text-[10px] font-bold text-slate-500 uppercase">Copyright Footer</label>
        <input type="text" name="footerCopyright" value="<?= e($val('footerCopyright')) ?>" class="w-full mt-1 px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800">
      </div>
      <div class="md:col-span-2 flex justify-end pt-2 border-t border-slate-100 dark:border-slate-800">
        <button class="px-5 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow"><i class="fa-solid fa-floppy-disk mr-1"></i>Simpan Identitas</button>
      </div>
    </form>
  </div>

<div class="glass-card rounded-2xl p-5 shadow-sm">
    <h3 class="font-display text-sm font-bold text-slate-800 dark:text-slate-100 mb-4"><i class="fa-solid fa-flag mr-1 text-brand-600"></i>Hero &amp; Statistik Portal</h3>
    <form method="post" action="/cms/text" class="grid grid-cols-1 md:grid-cols-2 gap-3">
      <?= Csrf::field() ?>
      <div class="md:col-span-2">
        <label class="text-[10px] font-bold text-slate-500 uppercase">Badge Hero</label>
        <input type="text" name="heroBadge" value="<?= e(\App\Models\Portal::content()['heroBadge']) ?>" class="w-full mt-1 px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800">
      </div>
      <div>
        <label class="text-[10px] font-bold text-slate-500 uppercase">Statistik Anggota</label>
        <input type="text" name="statAnggota" value="<?= e(\App\Models\Portal::content()['statAnggota']) ?>" class="w-full mt-1 px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800">
      </div>
      <div>
        <label class="text-[10px] font-bold text-slate-500 uppercase">Statistik Susu / Hari</label>
        <input type="text" name="statSusu" value="<?= e(\App\Models\Portal::content()['statSusu']) ?>" class="w-full mt-1 px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800">
      </div>
      <div class="md:col-span-2 flex items-center justify-between pt-2 border-t border-slate-100 dark:border-slate-800">
        <a href="/cms" class="text-[11px] font-bold text-brand-600 hover:underline"><i class="fa-solid fa-up-right-from-square mr-1"></i>Semua konten portal di menu CMS</a>
        <button class="px-5 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow"><i class="fa-solid fa-floppy-disk mr-1"></i>Simpan Hero</button>
      </div>
    </form>
  </div>

  <!-- Kartu anggota -->
  <div class="glass-card rounded-2xl p-5 shadow-sm">
    <h3 class="font-display text-sm font-bold text-slate-800 dark:text-slate-100 mb-4"><i class="fa-solid fa-id-card mr-1 text-brand-600"></i>Background Kartu Anggota</h3>
    <form method="post" action="/settings/card-background" enctype="multipart/form-data" class="space-y-3">
      <?= Csrf::field() ?>

      <div class="grid grid-cols-2 gap-2">
        <label class="flex items-center gap-2 p-2.5 rounded-xl border cursor-pointer text-xs <?= $cardBg['type'] === 'color' ? 'border-brand-600 bg-brand-50 text-brand-600 font-bold' : 'border-slate-200 dark:border-slate-700' ?>">
          <input type="radio" name="card_bg_type" value="color" <?= $cardBg['type'] === 'color' ? 'checked' : '' ?> onchange="cardBgPreview()"> Warna
        </label>
        <label class="flex items-center gap-2 p-2.5 rounded-xl border cursor-pointer text-xs <?= $cardBg['type'] === 'image' ? 'border-brand-600 bg-brand-50 text-brand-600 font-bold' : 'border-slate-200 dark:border-slate-700' ?>">
          <input type="radio" name="card_bg_type" value="image" <?= $cardBg['type'] === 'image' ? 'checked' : '' ?> onchange="cardBgPreview()"> Gambar
        </label>
      </div>

      <div id="bg-color-row" class="<?= $cardBg['type'] === 'color' ? '' : 'hidden' ?>">
        <label class="text-[10px] font-bold text-slate-500 uppercase">Warna Background</label>
        <div class="flex items-center gap-2 mt-1">
          <input type="color" id="card-bg-color-picker" value="<?= e((string) ($cardBg['color'] ?: '#0b7a3e')) ?>" oninput="document.getElementById('card-bg-color').value=this.value; cardBgPreview();" class="w-12 h-9 rounded-lg border-0 cursor-pointer">
          <input type="text" id="card-bg-color" name="card_bg_color" value="<?= e((string) $cardBg['color']) ?>" pattern="#[0-9a-fA-F]{6}" class="flex-1 px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800 font-mono">
        </div>
      </div>

      <div id="bg-image-row" class="<?= $cardBg['type'] === 'image' ? '' : 'hidden' ?> space-y-2">
        <label class="text-[10px] font-bold text-slate-500 uppercase">Gambar Background (JPG/PNG/WEBP, maks 3MB)</label>
        <input type="file" name="card_bg_image" accept="image/jpeg,image/png,image/webp" onchange="cardBgPreview(this)"
          class="w-full px-3 py-1.5 text-xs border rounded-xl bg-white dark:bg-slate-800 file:mr-2 file:px-2 file:py-1 file:rounded-lg file:border-0 file:bg-brand-50 file:text-brand-600 file:text-[10px] file:font-bold">
        <?php if (!empty($cardBg['image'])): ?>
          <label class="flex items-center gap-2 text-[11px] text-slate-500"><input type="checkbox" name="keep_existing_image" value="1" checked> Pertahankan gambar saat ini (<?= e(basename((string) $cardBg['image'])) ?>)</label>
        <?php endif; ?>
        <div>
          <label class="text-[10px] font-bold text-slate-500 uppercase">Opacity Overlay: <span id="opacity-label"><?= e((string) $cardBg['opacity']) ?></span></label>
          <input type="range" name="card_bg_opacity" id="card-bg-opacity" min="0" max="0.9" step="0.05" value="<?= e((string) $cardBg['opacity']) ?>" oninput="document.getElementById('opacity-label').innerText=this.value; cardBgPreview();" class="w-full">
        </div>
      </div>

      <!-- Sisi belakang kartu -->
      <div class="border-t border-slate-100 dark:border-slate-800 pt-3 space-y-2">
        <label class="flex items-center gap-2 text-xs font-bold text-slate-600 dark:text-slate-300">
          <input type="checkbox" name="card_back_enabled" value="1" <?= \App\Models\Setting::get('card_back_enabled', '0') === '1' ? 'checked' : '' ?> onchange="backRowToggle()">
          Background khusus sisi BELAKANG (jika tidak dicentang, belakang mengikuti depan)
        </label>
        <div id="back-bg-row" class="<?= \App\Models\Setting::get('card_back_enabled', '0') === '1' ? '' : 'hidden' ?> space-y-2">
          <div class="grid grid-cols-2 gap-2">
            <label class="flex items-center gap-2 p-2 rounded-xl border cursor-pointer text-[11px] <?= (\App\Models\Setting::get('card_back_bg_type', 'color') === 'color') ? 'border-brand-600 bg-brand-50 text-brand-600 font-bold' : 'border-slate-200 dark:border-slate-700' ?>">
              <input type="radio" name="card_back_bg_type" value="color" <?= \App\Models\Setting::get('card_back_bg_type', 'color') === 'color' ? 'checked' : '' ?> onchange="backBgPreview()"> Warna
            </label>
            <label class="flex items-center gap-2 p-2 rounded-xl border cursor-pointer text-[11px] <?= \App\Models\Setting::get('card_back_bg_type', 'color') === 'image' ? 'border-brand-600 bg-brand-50 text-brand-600 font-bold' : 'border-slate-200 dark:border-slate-700' ?>">
              <input type="radio" name="card_back_bg_type" value="image" <?= \App\Models\Setting::get('card_back_bg_type', 'color') === 'image' ? 'checked' : '' ?> onchange="backBgPreview()"> Gambar
            </label>
          </div>
          <div id="back-color-row" class="<?= \App\Models\Setting::get('card_back_bg_type', 'color') === 'color' ? '' : 'hidden' ?>">
            <div class="flex items-center gap-2">
              <input type="color" value="<?= e((string) (\App\Models\Setting::get('card_back_bg_color', '#052e1a') ?: '#052e1a')) ?>" oninput="document.getElementById('back-bg-color').value=this.value; backBgPreview();" class="w-10 h-8 rounded-lg border-0 cursor-pointer">
              <input type="text" id="back-bg-color" name="card_back_bg_color" value="<?= e((string) \App\Models\Setting::get('card_back_bg_color', '#052e1a')) ?>" pattern="#[0-9a-fA-F]{6}" class="flex-1 px-3 py-1.5 text-xs border rounded-xl bg-white dark:bg-slate-800 font-mono">
            </div>
          </div>
          <div id="back-image-row" class="<?= \App\Models\Setting::get('card_back_bg_type', 'color') === 'image' ? '' : 'hidden' ?>">
            <input type="file" name="card_back_bg_image" accept="image/jpeg,image/png,image/webp" onchange="backBgPreview(this)"
              class="w-full px-3 py-1.5 text-xs border rounded-xl bg-white dark:bg-slate-800 file:mr-2 file:px-2 file:py-1 file:rounded-lg file:border-0 file:bg-brand-50 file:text-brand-600 file:text-[10px] file:font-bold">
            <?php if ((string) \App\Models\Setting::get('card_back_bg_image', '') !== ''): ?>
              <p class="text-[10px] text-slate-400 mt-1">Gambar saat ini: <?= e(basename((string) \App\Models\Setting::get('card_back_bg_image'))) ?> (kosongkan upload = tetap dipakai)</p>
            <?php endif; ?>
            <label class="block mt-1.5">
              <span class="text-[10px] font-bold text-slate-500 uppercase">Opacity Overlay: <span id="back-opacity-label"><?= e((string) \App\Models\Setting::get('card_back_bg_opacity', '0.35')) ?></span></span>
              <input type="range" name="card_back_bg_opacity" id="back-opacity" min="0" max="0.9" step="0.05" value="<?= e((string) \App\Models\Setting::get('card_back_bg_opacity', '0.35')) ?>" oninput="document.getElementById('back-opacity-label').innerText=this.value; backBgPreview();" class="w-full">
            </label>
          </div>
        </div>
      </div>

      <!-- Preview kartu -->
      <div>
        <p class="text-[10px] font-bold text-slate-500 uppercase mb-1">Preview Kartu (depan &amp; belakang)</p>
        <div class="flex gap-3 flex-wrap">
          <div class="relative w-full max-w-[300px] aspect-[85.6/53.98] rounded-xl overflow-hidden shadow-lg border border-white/20">
            <div id="card-preview-bg" class="absolute inset-0" style="<?= $cardBg['type'] === 'image' && !empty($cardBg['image']) ? 'background-image:url(\'/' . e($cardBg['image']) . '\');background-size:cover;background-position:center' : 'background-color:' . e((string) $cardBg['color']) ?>;"></div>
            <div id="card-preview-overlay" class="absolute inset-0" style="background:rgba(5,46,26,<?= e((string) $cardBg['opacity']) ?>);"></div>
            <div class="relative h-full flex items-center justify-between p-4 text-white" style="background:linear-gradient(135deg, rgba(5,46,26,0.55), rgba(11,122,62,0.35));">
              <div class="flex items-center gap-2">
                <div class="w-8 h-[52px] rounded bg-white/25 border border-white/60 flex items-center justify-center text-[7px]">4x6</div>
                <div>
                  <p class="font-bold text-[10px]">DEPAN</p>
                  <p class="text-[8px] opacity-75">Nama Anggota</p>
                  <p class="text-[8px] font-mono opacity-75">AGT-0000-0000</p>
                </div>
              </div>
              <div class="w-10 h-10 rounded bg-white p-0.5"><div class="w-full h-full" style="background:conic-gradient(#000 0 25%, #fff 0 50%, #000 0 75%, #fff 0);"></div></div>
            </div>
          </div>
          <div class="relative w-full max-w-[300px] aspect-[85.6/53.98] rounded-xl overflow-hidden shadow-lg border border-white/20">
            <div id="card-preview-back" class="absolute inset-0" style="background-color:<?= e((string) \App\Models\Setting::get('card_back_bg_color', '#052e1a')) ?>;"></div>
            <div id="card-preview-back-overlay" class="absolute inset-0" style="background:rgba(5,46,26,<?= e((string) \App\Models\Setting::get('card_back_bg_opacity', '0.35')) ?>);"></div>
            <div class="relative h-full flex flex-col justify-center p-4 text-white" style="background:linear-gradient(160deg, rgba(5,46,26,0.55), rgba(11,122,62,0.35));">
              <p class="font-bold text-[10px]">BELAKANG</p>
              <p class="text-[8px] opacity-80 mt-1">Syarat &amp; ketentuan kartu +</p>
              <p class="text-[8px] opacity-80">info hubungan eksternal anggota</p>
              <p class="text-[8px] opacity-80">+ QR verifikasi keaslian</p>
            </div>
          </div>
        </div>
        <p class="text-[10px] text-slate-400 mt-1">Overlay gelap menjaga keterbacaan nama, foto, dan QR.</p>
      </div>

      <div class="flex justify-end pt-2 border-t border-slate-100 dark:border-slate-800">
        <button class="px-5 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow"><i class="fa-solid fa-floppy-disk mr-1"></i>Simpan Background</button>
      </div>
    </form>
  </div>
</div>

<script>
function backRowToggle() {
  var on = document.querySelector('input[name="card_back_enabled"]')?.checked;
  document.getElementById('back-bg-row').classList.toggle('hidden', !on);
  backBgPreview();
}

function backBgPreview(fileInput) {
  var on = document.querySelector('input[name="card_back_enabled"]')?.checked;
  var back = document.getElementById('card-preview-back');
  var ov = document.getElementById('card-preview-back-overlay');
  if (!back) return;
  if (!on) { // mengikuti depan
    var fwd = document.getElementById('card-preview-bg');
    back.style.backgroundImage = fwd.style.backgroundImage;
    back.style.backgroundColor = fwd.style.backgroundColor;
    ov.style.background = 'transparent';
    return;
  }
  var type = document.querySelector('input[name="card_back_bg_type"]:checked')?.value || 'color';
  document.getElementById('back-color-row').classList.toggle('hidden', type !== 'color');
  document.getElementById('back-image-row').classList.toggle('hidden', type !== 'image');
  if (type === 'color') {
    back.style.backgroundImage = 'none';
    back.style.backgroundColor = document.getElementById('back-bg-color').value || '#052e1a';
    ov.style.background = 'transparent';
  } else {
    if (fileInput && fileInput.files && fileInput.files[0]) {
      back.style.backgroundImage = 'url(' + URL.createObjectURL(fileInput.files[0]) + ')';
    } else {
      var cur = '<?= \App\Models\Setting::get('card_back_bg_image', '') !== '' ? "url('/" . e((string) \App\Models\Setting::get('card_back_bg_image')) . "')" : "none" ?>';
      back.style.backgroundImage = cur;
    }
    back.style.backgroundSize = 'cover';
    back.style.backgroundPosition = 'center';
    ov.style.background = 'rgba(5,46,26,' + (document.getElementById('back-opacity')?.value || 0.35) + ')';
  }
}

function cardBgPreview(fileInput) {
  var type = document.querySelector('input[name="card_bg_type"]:checked')?.value || 'color';
  var bg = document.getElementById('card-preview-bg');
  var overlay = document.getElementById('card-preview-overlay');
  document.getElementById('bg-color-row').classList.toggle('hidden', type !== 'color');
  document.getElementById('bg-image-row').classList.toggle('hidden', type !== 'image');

  if (type === 'color') {
    bg.style.backgroundImage = 'none';
    bg.style.backgroundColor = document.getElementById('card-bg-color').value || '#0b7a3e';
    overlay.style.background = 'transparent';
  } else {
    if (fileInput && fileInput.files && fileInput.files[0]) {
      bg.style.backgroundImage = 'url(' + URL.createObjectURL(fileInput.files[0]) + ')';
    } else {
      bg.style.backgroundImage = '<?= !empty($cardBg['image']) ? "url('/" . e($cardBg['image']) . "')" : "none" ?>';
      bg.style.backgroundColor = document.getElementById('card-bg-color').value || '#0b7a3e';
    }
    bg.style.backgroundSize = 'cover';
    bg.style.backgroundPosition = 'center';
    overlay.style.background = 'rgba(5,46,26,' + (document.getElementById('card-bg-opacity')?.value || 0.25) + ')';
  }
  backBgPreview(); // belakang mengikuti depan saat tidak diaktifkan
}
</script>
