<?php
/**
 * Produk & Stok (dashboard). Variables: $products, $categories, $category, $q,
 * $summary, $canManage.
 */

use App\Core\Csrf;

$products = (array) ($products ?? []);
$categories = (array) ($categories ?? []);
$summary = (array) ($summary ?? []);
$lowStock = (array) ($summary['lowStock'] ?? []);
?>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-5">
  <div class="glass-card rounded-2xl p-5 shadow-sm">
    <p class="text-[10px] font-bold text-slate-400 uppercase">Total Produk</p>
    <h4 class="text-xl font-extrabold font-poppins text-slate-800 dark:text-slate-100 mt-1"><?= (int) ($summary['totalProducts'] ?? 0) ?></h4>
  </div>
  <div class="glass-card rounded-2xl p-5 shadow-sm">
    <p class="text-[10px] font-bold text-slate-400 uppercase">Produk Aktif</p>
    <h4 class="text-xl font-extrabold font-poppins text-brand-600 mt-1"><?= (int) ($summary['activeProducts'] ?? 0) ?></h4>
  </div>
  <div class="glass-card rounded-2xl p-5 shadow-sm">
    <p class="text-[10px] font-bold text-slate-400 uppercase">Nilai Persediaan</p>
    <h4 class="text-xl font-extrabold font-poppins text-emerald-600 mt-1"><?= rupiah((float) ($summary['stockValue'] ?? 0)) ?></h4>
  </div>
  <div class="glass-card rounded-2xl p-5 shadow-sm">
    <p class="text-[10px] font-bold text-slate-400 uppercase">Stok Kritis</p>
    <h4 class="text-xl font-extrabold font-poppins text-amber-500 mt-1"><?= count($lowStock) ?></h4>
  </div>
</div>

<div class="glass-card rounded-2xl shadow-sm overflow-hidden">
  <div class="p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800">
    <form method="get" action="/products" class="flex flex-wrap items-center gap-2">
      <input type="text" name="q" value="<?= e($q ?? '') ?>" placeholder="Cari nama / SKU..."
        class="px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800 w-44">
      <select name="kategori" class="px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800">
        <option value="">Semua Kategori</option>
        <?php foreach ($categories as $cat): ?>
          <option value="<?= e((string) $cat) ?>" <?= ($category ?? '') === $cat ? 'selected' : '' ?>><?= e((string) $cat) ?></option>
        <?php endforeach; ?>
      </select>
      <button class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold"><i class="fa-solid fa-filter"></i></button>
    </form>
    <div class="flex items-center gap-2">
      <a href="/sales/export/stock" class="px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition"><i class="fa-solid fa-file-excel mr-1 text-emerald-600"></i>Excel Stok</a>
      <?php if ($canManage ?? false): ?>
        <button type="button" onclick="openModal('modal-produk')" class="px-4 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow"><i class="fa-solid fa-plus mr-1"></i>Tambah Produk</button>
      <?php endif; ?>
    </div>
  </div>

  <div class="overflow-x-auto">
    <table class="w-full text-xs">
      <thead>
        <tr class="text-left text-slate-500 dark:text-slate-400 border-b border-slate-100 dark:border-slate-800">
          <th class="px-5 py-3 font-semibold">Produk</th>
          <th class="px-5 py-3 font-semibold">Kategori</th>
          <th class="px-5 py-3 font-semibold text-right">Harga Jual</th>
          <th class="px-5 py-3 font-semibold text-center">Stok</th>
          <th class="px-5 py-3 font-semibold text-center">Status</th>
          <?php if ($canManage ?? false): ?><th class="px-5 py-3 font-semibold text-right">Aksi</th><?php endif; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($products as $p): $p = (array) $p; ?>
          <tr class="border-b border-slate-50 dark:border-slate-800/60 hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
            <td class="px-5 py-3">
              <div class="flex items-center gap-3">
                <?php if (!empty($p['image_path'])): ?>
                  <img src="<?= e(news_image_src((string) $p['image_path'])) ?>" alt="" class="w-9 h-9 rounded-lg object-cover border border-slate-200">
                <?php else: ?>
                  <div class="w-9 h-9 rounded-lg bg-emerald-50 text-brand-600 flex items-center justify-center border border-emerald-100"><i class="fa-solid fa-box"></i></div>
                <?php endif; ?>
                <div>
                  <p class="font-bold text-slate-700 dark:text-slate-200"><?= e((string) $p['name']) ?></p>
                  <p class="text-[10px] text-slate-400 font-mono"><?= e((string) $p['sku']) ?></p>
                </div>
              </div>
            </td>
            <td class="px-5 py-3 text-slate-600 dark:text-slate-300"><?= e((string) ($p['category'] ?? '-')) ?></td>
            <td class="px-5 py-3 text-right font-bold text-slate-700 dark:text-slate-200"><?= rupiah((float) $p['price']) ?></td>
            <td class="px-5 py-3 text-center">
              <span class="px-2 py-1 rounded-lg text-[11px] font-bold <?= (int) $p['stock'] <= (int) $p['min_stock'] ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200' ?>">
                <?= (int) $p['stock'] ?> <?= e((string) $p['unit']) ?>
              </span>
            </td>
            <td class="px-5 py-3 text-center">
              <?= (int) $p['is_active'] === 1
                ? '<span class="px-2 py-1 rounded-lg text-[10px] font-bold bg-emerald-50 text-emerald-700">AKTIF</span>'
                : '<span class="px-2 py-1 rounded-lg text-[10px] font-bold bg-slate-100 text-slate-500">NONAKTIF</span>' ?>
            </td>
            <?php if ($canManage ?? false): ?>
              <td class="px-5 py-3 text-right whitespace-nowrap">
                <a href="/products/stock/<?= (int) $p['id'] ?>" class="px-2 py-1.5 rounded-lg bg-slate-100 text-slate-600 hover:bg-slate-200 mr-1" title="Kartu Stok"><i class="fa-solid fa-warehouse"></i></a>
                <button type="button" onclick='openEdit(<?= json_encode([
                    'id' => (int) $p['id'],
                    'sku' => (string) $p['sku'],
                    'name' => (string) $p['name'],
                    'category' => (string) ($p['category'] ?? ''),
                    'description' => (string) ($p['description'] ?? ''),
                    'price' => (float) $p['price'],
                    'cost_price' => (float) ($p['cost_price'] ?? 0),
                    'min_stock' => (int) $p['min_stock'],
                    'unit' => (string) $p['unit'],
                    'is_active' => (int) $p['is_active'],
                ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)' class="px-2 py-1.5 rounded-lg bg-slate-100 text-slate-600 hover:bg-slate-200 mr-1" title="Edit"><i class="fa-solid fa-pen"></i></button>
                <form method="post" action="/products/delete/<?= (int) $p['id'] ?>" class="inline" onsubmit="return confirm('Hapus / nonaktifkan produk ini?')">
                  <?= Csrf::field() ?>
                  <button class="px-2 py-1.5 rounded-lg bg-red-50 text-red-600 hover:bg-red-100" title="Hapus"><i class="fa-solid fa-trash"></i></button>
                </form>
              </td>
            <?php endif; ?>
          </tr>
        <?php endforeach; ?>
        <?php if ($products === []): ?>
          <tr><td colspan="6" class="px-5 py-10 text-center text-slate-400">Belum ada produk. Tambah produk pertama Anda.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if ($canManage ?? false): ?>
<div id="modal-produk" class="hidden fixed inset-0 z-[70] bg-slate-950/60 backdrop-blur-sm flex items-center justify-center p-4">
  <div class="glass-card rounded-2xl shadow-2xl w-full max-w-lg p-5 max-h-[90vh] overflow-y-auto custom-scrollbar">
    <h3 class="font-display text-sm font-bold text-slate-800 dark:text-slate-100 mb-4"><i class="fa-solid fa-box-open mr-1 text-brand-600"></i><span id="modalTitle">Tambah Produk</span></h3>
    <form method="post" action="/products" enctype="multipart/form-data" class="space-y-3">
      <?= Csrf::field() ?>
      <input type="hidden" name="id" id="f-id" value="0">
      <div class="grid grid-cols-2 gap-2">
        <div>
          <label class="text-[10px] font-bold text-slate-500 uppercase">Nama Produk *</label>
          <input type="text" name="name" id="f-name" required maxlength="160" class="w-full mt-1 px-3 py-2.5 text-xs border rounded-xl bg-white dark:bg-slate-800">
        </div>
        <div>
          <label class="text-[10px] font-bold text-slate-500 uppercase">Kategori</label>
          <input type="text" name="category" id="f-category" list="kategoriList" maxlength="60" class="w-full mt-1 px-3 py-2.5 text-xs border rounded-xl bg-white dark:bg-slate-800">
          <datalist id="kategoriList">
            <?php foreach ($categories as $cat): ?><option value="<?= e((string) $cat) ?>"></option><?php endforeach; ?>
          </datalist>
        </div>
      </div>
      <div class="grid grid-cols-3 gap-2">
        <div>
          <label class="text-[10px] font-bold text-slate-500 uppercase">Harga Jual *</label>
          <input type="number" name="price" id="f-price" required min="0" step="1" class="w-full mt-1 px-3 py-2.5 text-xs border rounded-xl bg-white dark:bg-slate-800">
        </div>
        <div>
          <label class="text-[10px] font-bold text-slate-500 uppercase">Harga Pokok</label>
          <input type="number" name="cost_price" id="f-cost" min="0" step="1" class="w-full mt-1 px-3 py-2.5 text-xs border rounded-xl bg-white dark:bg-slate-800">
        </div>
        <div>
          <label class="text-[10px] font-bold text-slate-500 uppercase">Satuan</label>
          <input type="text" name="unit" id="f-unit" value="pcs" maxlength="20" class="w-full mt-1 px-3 py-2.5 text-xs border rounded-xl bg-white dark:bg-slate-800">
        </div>
      </div>
      <div class="grid grid-cols-3 gap-2">
        <div>
          <label class="text-[10px] font-bold text-slate-500 uppercase">SKU (otomatis)</label>
          <input type="text" name="sku" id="f-sku" maxlength="40" placeholder="PRD-001" class="w-full mt-1 px-3 py-2.5 text-xs border rounded-xl bg-white dark:bg-slate-800">
        </div>
        <div>
          <label class="text-[10px] font-bold text-slate-500 uppercase">Stok Awal</label>
          <input type="number" name="stock" id="f-stock" min="0" value="0" class="w-full mt-1 px-3 py-2.5 text-xs border rounded-xl bg-white dark:bg-slate-800">
        </div>
        <div>
          <label class="text-[10px] font-bold text-slate-500 uppercase">Stok Minimum</label>
          <input type="number" name="min_stock" id="f-min" min="0" value="5" class="w-full mt-1 px-3 py-2.5 text-xs border rounded-xl bg-white dark:bg-slate-800">
        </div>
      </div>
      <div>
        <label class="text-[10px] font-bold text-slate-500 uppercase">Deskripsi</label>
        <textarea name="description" id="f-desc" rows="2" maxlength="2000" class="w-full mt-1 px-3 py-2.5 text-xs border rounded-xl bg-white dark:bg-slate-800"></textarea>
      </div>
      <div class="grid grid-cols-2 gap-2 items-end">
        <div>
          <label class="text-[10px] font-bold text-slate-500 uppercase">Foto Produk (JPG/PNG/WEBP, maks 3MB)</label>
          <input type="file" name="image" accept="image/jpeg,image/png,image/webp" class="w-full mt-1 text-xs border rounded-xl bg-white dark:bg-slate-800 px-2 py-1.5">
        </div>
        <label class="flex items-center gap-2 text-xs text-slate-600 dark:text-slate-300 pb-2">
          <input type="checkbox" name="is_active" value="1" id="f-active" checked class="rounded"> Tampilkan di marketplace
        </label>
      </div>
      <p class="text-[10px] text-slate-400">Stok produk lama diubah melalui tombol gudang (kartu stok) agar tercatat di kartu stok.</p>
      <div class="flex justify-end gap-2 pt-2">
        <button type="button" onclick="closeModal('modal-produk')" class="px-4 py-2.5 rounded-xl bg-slate-100 text-slate-600 text-xs font-bold">Batal</button>
        <button class="px-5 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow"><i class="fa-solid fa-floppy-disk mr-1"></i>Simpan</button>
      </div>
    </form>
  </div>
</div>

<script>
function openEdit(p) {
  document.getElementById('modalTitle').textContent = 'Edit Produk: ' + p.sku;
  document.getElementById('f-id').value = p.id;
  document.getElementById('f-name').value = p.name;
  document.getElementById('f-category').value = p.category || '';
  document.getElementById('f-price').value = p.price;
  document.getElementById('f-cost').value = p.cost_price || '';
  document.getElementById('f-unit').value = p.unit || 'pcs';
  document.getElementById('f-sku').value = p.sku;
  document.getElementById('f-min').value = p.min_stock;
  document.getElementById('f-desc').value = p.description || '';
  document.getElementById('f-active').checked = p.is_active === 1;
  document.getElementById('f-stock').disabled = true;
  openModal('modal-produk');
}
document.getElementById('modal-produk').addEventListener('click', function (e) {
  if (e.target === this) {
    closeModal('modal-produk');
    document.getElementById('f-stock').disabled = false;
  }
});
</script>
<?php endif; ?>
