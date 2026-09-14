<?php
/**
 * Berita form (create/edit). Variables: post (nullable), postTags, categories.
 */
$post = $post ?? null;
$postTags = $postTags ?? [];
$categories = $categories ?? [];
$isEdit = $post !== null;
$action = $isEdit ? '/news/update/' . (int) $post['id'] : '/news/save';
?>
<div class="glass-card rounded-2xl p-5 shadow-sm">
  <form method="post" action="<?= e($action) ?>" enctype="multipart/form-data" class="grid grid-cols-1 lg:grid-cols-3 gap-5" onsubmit="return validateNewsForm(this)">
    <?php use App\Core\Csrf; ?>
    <?= Csrf::field() ?>

    <!-- Main column -->
    <div class="lg:col-span-2 space-y-4">
      <div>
        <label class="text-[11px] font-bold text-slate-600 dark:text-slate-300">Judul Berita <span class="text-red-500">*</span></label>
        <input type="text" name="title" required maxlength="200" value="<?= e($post['title'] ?? '') ?>"
          placeholder="Contoh: KUTT Suka Makmur Raih Penghargaan Koperasi Terbaik"
          class="w-full mt-1 px-3 py-2.5 text-sm border rounded-xl bg-white dark:bg-slate-800 focus:ring-2 focus:ring-brand-600">
      </div>
      <div>
        <label class="text-[11px] font-bold text-slate-600 dark:text-slate-300">Ringkasan (tampil di daftar & metadata share)</label>
        <textarea name="excerpt" rows="2" maxlength="500"
          placeholder="Ringkasan singkat maksimal 500 karakter..."
          class="w-full mt-1 px-3 py-2.5 text-xs border rounded-xl bg-white dark:bg-slate-800 focus:ring-2 focus:ring-brand-600"><?= e($post['excerpt'] ?? '') ?></textarea>
      </div>
      <div>
        <label class="text-[11px] font-bold text-slate-600 dark:text-slate-300">Isi Berita</label>
        <textarea name="body" id="news-body" rows="12"
          placeholder="Tulis isi berita di sini... (mendukung HTML sederhana: <p>, <strong>, <em>, <ul>, <li>, <img>)"
          class="w-full mt-1 px-3 py-2.5 text-xs border rounded-xl bg-white dark:bg-slate-800 focus:ring-2 focus:ring-brand-600 font-mono"><?= e($post['body'] ?? '') ?></textarea>
        <p class="text-[10px] text-slate-400 mt-1">Isi akan disaring otomatis dari tag berbahaya (script/iframe/on* event) sebelum tampil di portal publik.</p>
      </div>
    </div>

    <!-- Side column -->
    <div class="space-y-4">
      <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800 space-y-3">
        <p class="text-[11px] font-bold text-slate-600 dark:text-slate-300 uppercase">Publikasi</p>
        <div>
          <label class="text-[10px] font-bold text-slate-500 uppercase">Status</label>
          <select name="status" class="w-full mt-1 px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800">
            <option value="DRAFT" <?= ($post['status'] ?? '') === 'DRAFT' ? 'selected' : '' ?>>Draft</option>
            <option value="PUBLISHED" <?= ($post['status'] ?? '') === 'PUBLISHED' ? 'selected' : '' ?>>Published</option>
          </select>
        </div>
        <div>
          <label class="text-[10px] font-bold text-slate-500 uppercase">Kategori</label>
          <select name="category_id" class="w-full mt-1 px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800">
            <option value="">- Tanpa Kategori -</option>
            <?php foreach ($categories as $c): ?>
              <option value="<?= (int) $c['id'] ?>" <?= ($post['category_id'] ?? '') == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="text-[10px] font-bold text-slate-500 uppercase">Tag (pisahkan koma)</label>
          <input type="text" name="tags" value="<?= e(implode(', ', $postTags)) ?>" placeholder="susu, koperasi, rtc"
            class="w-full mt-1 px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800">
        </div>
      </div>

      <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800 space-y-3">
        <p class="text-[11px] font-bold text-slate-600 dark:text-slate-300 uppercase">Media</p>
        <div>
          <label class="text-[10px] font-bold text-slate-500 uppercase">Gambar Utama / Thumbnail OG <?= $isEdit ? '' : '(opsional)' ?></label>
          <input type="file" name="image" accept="image/jpeg,image/png,image/webp"
            class="w-full mt-1 text-[11px] border rounded-xl px-3 py-2 bg-white dark:bg-slate-800 file:mr-2 file:px-2 file:py-1 file:rounded-lg file:border-0 file:bg-brand-50 file:text-brand-600 file:text-[10px] file:font-bold">
          <p class="text-[10px] text-slate-400 mt-1">JPG/PNG/WEBP, maksimal 3 MB. Dipakai sebagai thumbnail saat dibagikan ke WhatsApp/Facebook.</p>
          <?php if (!empty($post['image_path'])): ?>
            <div class="mt-2 flex items-center space-x-2">
              <img src="<?= e(str_starts_with((string) $post['image_path'], 'uploads/') ? '/' . $post['image_path'] : $post['image_path']) ?>" class="h-16 w-24 rounded-lg object-cover" alt="Gambar saat ini">
              <span class="text-[10px] text-slate-400">Gambar saat ini</span>
            </div>
          <?php endif; ?>
        </div>
        <div>
          <label class="text-[10px] font-bold text-slate-500 uppercase">atau Link Gambar (URL)</label>
          <input type="url" name="image_url" value="<?= e(!empty($post['image_path']) && !str_starts_with((string) $post['image_path'], 'uploads/') ? $post['image_path'] : '') ?>" placeholder="https://contoh.com/foto-kegiatan.jpg"
            class="w-full mt-1 px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800">
          <p class="text-[10px] text-slate-400 mt-1">Kosongkan jika memakai upload. Jika diisi, dipakai sebagai gambar berita.</p>
        </div>
        <div class="border-t border-slate-200 dark:border-slate-700 pt-3">
          <label class="text-[10px] font-bold text-slate-500 uppercase">Upload Video (MP4/WebM, maks 25MB)</label>
          <input type="file" name="video" accept="video/mp4,video/webm,video/quicktime"
            class="w-full mt-1 text-[11px] border rounded-xl px-3 py-2 bg-white dark:bg-slate-800 file:mr-2 file:px-2 file:py-1 file:rounded-lg file:border-0 file:bg-brand-50 file:text-brand-600 file:text-[10px] file:font-bold">
        </div>
        <div>
          <label class="text-[10px] font-bold text-slate-500 uppercase">atau Link Video (YouTube dll)</label>
          <input type="url" name="video_url" value="<?= e($post['video_url'] ?? '') ?>" placeholder="https://www.youtube.com/watch?v=XXXXXXXX"
            class="w-full mt-1 px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800">
        </div>
      </div>

      <div class="flex space-x-2">
        <button type="submit" class="flex-1 px-4 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow">
          <i class="fa-solid fa-floppy-disk"></i> <?= $isEdit ? 'Perbarui' : 'Simpan' ?> Berita
        </button>
        <a href="/news" class="px-4 py-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-xs font-bold">Batal</a>
      </div>
    </div>
  </form>
</div>

<script>
function validateNewsForm(form) {
  if (!form.title.value.trim()) { showToast('Data belum lengkap: judul wajib diisi.', 'error'); return false; }
  var img = form.querySelector('input[name="image"]');
  var imgUrl = form.querySelector('input[name="image_url"]');
  if (img && img.files.length > 0 && imgUrl && imgUrl.value.trim() !== '') {
    showToast('Pilih salah satu saja: upload gambar ATAU link gambar.', 'error');
    return false;
  }
  return true;
}
</script>
