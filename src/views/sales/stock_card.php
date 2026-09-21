<?php
/**
 * Kartu stok satu produk. Variables: $product, $movements, $canManage.
 */

use App\Core\Csrf;

$product = (array) ($product ?? []);
$movements = (array) ($movements ?? []);
$canManage = (bool) ($canManage ?? false);

$reasonLabels = [
    'PURCHASE'   => ['Pembelian', 'fa-solid fa-truck-ramp-box', 'bg-emerald-50 text-emerald-700'],
    'SALE'       => ['Penjualan', 'fa-solid fa-cash-register', 'bg-blue-50 text-blue-700'],
    'ADJUSTMENT' => ['Koreksi', 'fa-solid fa-sliders', 'bg-amber-50 text-amber-700'],
    'CANCEL'     => ['Pembatalan Order', 'fa-solid fa-rotate-left', 'bg-slate-100 text-slate-600'],
];
?>

<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 mb-5">
  <a href="/products" class="text-xs font-bold text-slate-500 hover:text-brand-600 transition"><i class="fa-solid fa-arrow-left mr-1"></i>Kembali ke Produk</a>
  <?php if ($canManage): ?>
    <button type="button" onclick="openModal('modal-koreksi')" class="px-4 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow"><i class="fa-solid fa-sliders mr-1"></i>Koreksi Stok</button>
  <?php endif; ?>
</div>

<div class="glass-card rounded-2xl p-5 shadow-sm mb-5 flex items-center gap-4">
  <?php if (!empty($product['image_path'])): ?>
    <img src="<?= e(news_image_src((string) $product['image_path'])) ?>" alt="" class="w-14 h-14 rounded-xl object-cover border border-slate-200">
  <?php else: ?>
    <div class="w-14 h-14 rounded-xl bg-emerald-50 text-brand-600 flex items-center justify-center text-xl border border-emerald-100"><i class="fa-solid fa-box"></i></div>
  <?php endif; ?>
  <div>
    <h3 class="font-poppins font-extrabold text-slate-800 dark:text-slate-100"><?= e((string) $product['name']) ?></h3>
    <p class="text-xs text-slate-400 font-mono"><?= e((string) $product['sku']) ?> · <?= e((string) ($product['category'] ?? '-')) ?></p>
  </div>
  <div class="ml-auto text-right">
    <p class="text-[10px] font-bold text-slate-400 uppercase">Stok Saat Ini</p>
    <h4 class="text-2xl font-extrabold font-poppins <?= (int) $product['stock'] <= (int) $product['min_stock'] ? 'text-amber-500' : 'text-brand-600' ?>">
      <?= (int) $product['stock'] ?> <span class="text-xs font-bold text-slate-400"><?= e((string) $product['unit']) ?></span>
    </h4>
  </div>
</div>

<div class="glass-card rounded-2xl shadow-sm overflow-hidden">
  <div class="p-5 border-b border-slate-100 dark:border-slate-800">
    <h3 class="font-display text-sm font-bold text-slate-800 dark:text-slate-100"><i class="fa-solid fa-clipboard-list mr-1 text-brand-600"></i>Riwayat Mutasi (kartu stok)</h3>
  </div>
  <div class="overflow-x-auto">
    <table class="w-full text-xs">
      <thead>
        <tr class="text-left text-slate-500 dark:text-slate-400 border-b border-slate-100 dark:border-slate-800">
          <th class="px-5 py-3 font-semibold">Waktu</th>
          <th class="px-5 py-3 font-semibold">Jenis</th>
          <th class="px-5 py-3 font-semibold">Referensi</th>
          <th class="px-5 py-3 font-semibold">Catatan</th>
          <th class="px-5 py-3 font-semibold">Oleh</th>
          <th class="px-5 py-3 font-semibold text-right">Perubahan</th>
          <th class="px-5 py-3 font-semibold text-right">Sisa</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($movements as $m): $m = (array) $m; $meta = $reasonLabels[$m['reason']] ?? ['Mutasi', 'fa-solid fa-tag', 'bg-slate-100 text-slate-600']; ?>
          <tr class="border-b border-slate-50 dark:border-slate-800/60 hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
            <td class="px-5 py-3 text-slate-500 whitespace-nowrap"><?= e(date('d/m/Y H:i', strtotime((string) $m['created_at']))) ?></td>
            <td class="px-5 py-3">
              <span class="px-2 py-1 rounded-lg text-[10px] font-bold <?= $meta[2] ?>">
                <i class="<?= $meta[1] ?> mr-1"></i><?= $meta[0] ?>
              </span>
            </td>
            <td class="px-5 py-3 font-mono text-[11px] text-brand-600 font-bold"><?= e((string) ($m['reference'] ?? '-')) ?></td>
            <td class="px-5 py-3 text-slate-600 dark:text-slate-300"><?= e((string) ($m['note'] ?? '')) ?></td>
            <td class="px-5 py-3 text-slate-500"><?= e((string) ($m['user_name'] ?? 'Sistem')) ?></td>
            <td class="px-5 py-3 text-right font-bold <?= (int) $m['change_qty'] >= 0 ? 'text-emerald-600' : 'text-red-500' ?>">
              <?= (int) $m['change_qty'] >= 0 ? '+' : '' ?><?= (int) $m['change_qty'] ?>
            </td>
            <td class="px-5 py-3 text-right font-bold text-slate-700 dark:text-slate-200"><?= (int) $m['stock_after'] ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if ($movements === []): ?>
          <tr><td colspan="7" class="px-5 py-10 text-center text-slate-400">Belum ada mutasi stok.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if ($canManage): ?>
<div id="modal-koreksi" class="hidden fixed inset-0 z-[70] bg-slate-950/60 backdrop-blur-sm flex items-center justify-center p-4">
  <div class="glass-card rounded-2xl shadow-2xl w-full max-w-sm p-5">
    <h3 class="font-display text-sm font-bold text-slate-800 dark:text-slate-100 mb-4"><i class="fa-solid fa-sliders mr-1 text-brand-600"></i>Koreksi Stok: <?= e((string) $product['sku']) ?></h3>
    <form method="post" action="/products/stock/<?= (int) $product['id'] ?>" class="space-y-3">
      <?= Csrf::field() ?>
      <div class="grid grid-cols-2 gap-2">
        <div>
          <label class="text-[10px] font-bold text-slate-500 uppercase">Arah *</label>
          <select name="mode" class="w-full mt-1 px-3 py-2.5 text-xs border rounded-xl bg-white dark:bg-slate-800">
            <option value="IN">Stok Masuk (+)</option>
            <option value="OUT">Stok Keluar (−)</option>
          </select>
        </div>
        <div>
          <label class="text-[10px] font-bold text-slate-500 uppercase">Jumlah *</label>
          <input type="number" name="qty" required min="1" class="w-full mt-1 px-3 py-2.5 text-xs border rounded-xl bg-white dark:bg-slate-800">
        </div>
      </div>
      <div>
        <label class="text-[10px] font-bold text-slate-500 uppercase">Catatan</label>
        <input type="text" name="note" maxlength="255" placeholder="Contoh: pembelian pakan dari supplier" class="w-full mt-1 px-3 py-2.5 text-xs border rounded-xl bg-white dark:bg-slate-800">
      </div>
      <div class="flex justify-end gap-2 pt-1">
        <button type="button" onclick="closeModal('modal-koreksi')" class="px-4 py-2.5 rounded-xl bg-slate-100 text-slate-600 text-xs font-bold">Batal</button>
        <button class="px-5 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow">Simpan</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>
