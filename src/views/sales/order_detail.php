<?php
/**
 * Detail satu pesanan. Variables: $order, $items, $total, $canManage, $statuses.
 */

use App\Core\Csrf;

$order = (array) ($order ?? []);
$items = (array) ($items ?? []);
$statuses = (array) ($statuses ?? ['NEW', 'PAID', 'PROCESSING', 'COMPLETED', 'CANCELLED']);
$total = (float) ($total ?? 0);
$canManage = (bool) ($canManage ?? false);
$canDelete = (bool) ($canDelete ?? false);
?>

<div class="mb-4">
  <a href="/sales" class="text-xs font-bold text-slate-500 hover:text-brand-600 transition"><i class="fa-solid fa-arrow-left mr-1"></i>Kembali ke Pesanan</a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
  <div class="lg:col-span-2 space-y-4">
    <div class="glass-card rounded-2xl p-5 shadow-sm">
      <div class="flex items-start justify-between gap-3">
        <div>
          <p class="text-[10px] font-bold text-slate-400 uppercase">No. Order</p>
          <h3 class="font-mono font-extrabold text-brand-600 text-sm"><?= e((string) $order['order_no']) ?></h3>
        </div>
        <div class="text-right">
          <p class="text-[10px] font-bold text-slate-400 uppercase">Status</p>
          <p class="font-bold text-slate-700 dark:text-slate-200 text-sm"><?= e((string) $order['status']) ?></p>
        </div>
      </div>
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-5 text-xs">
        <div><p class="text-[10px] text-slate-400 uppercase font-bold">Channel</p><p class="font-bold text-slate-700 dark:text-slate-200"><?= e((string) $order['channel']) ?></p></div>
        <div><p class="text-[10px] text-slate-400 uppercase font-bold">Pembeli</p><p class="font-bold text-slate-700 dark:text-slate-200"><?= e((string) $order['buyer_name']) ?></p></div>
        <div><p class="text-[10px] text-slate-400 uppercase font-bold">No. HP</p><p class="font-bold text-slate-700 dark:text-slate-200"><?= e((string) ($order['buyer_phone'] ?? '—')) ?></p></div>
        <div><p class="text-[10px] text-slate-400 uppercase font-bold">Metode Bayar</p><p class="font-bold text-slate-700 dark:text-slate-200"><?= e((string) $order['payment_method']) ?></p></div>
        <div><p class="text-[10px] text-slate-400 uppercase font-bold">Kasir</p><p class="font-bold text-slate-700 dark:text-slate-200"><?= e((string) ($order['cashier_name'] ?? '—')) ?></p></div>
        <div><p class="text-[10px] text-slate-400 uppercase font-bold">Dibuat</p><p class="font-bold text-slate-700 dark:text-slate-200"><?= e(date('d/m/Y H:i', strtotime((string) $order['created_at']))) ?></p></div>
        <?php if (!empty($order['paid_at'])): ?>
          <div><p class="text-[10px] text-slate-400 uppercase font-bold">Dibayar</p><p class="font-bold text-slate-700 dark:text-slate-200"><?= e(date('d/m/Y H:i', strtotime((string) $order['paid_at']))) ?></p></div>
        <?php endif; ?>
        <?php if (!empty($order['completed_at'])): ?>
          <div><p class="text-[10px] text-slate-400 uppercase font-bold">Selesai</p><p class="font-bold text-slate-700 dark:text-slate-200"><?= e(date('d/m/Y H:i', strtotime((string) $order['completed_at']))) ?></p></div>
        <?php endif; ?>
      </div>
      <?php if (!empty($order['buyer_note'])): ?>
        <div class="mt-4 p-3 rounded-xl bg-amber-50 border border-amber-200 text-xs text-amber-800"><i class="fa-solid fa-note-sticky mr-1"></i><strong>Catatan pembeli:</strong> <?= e((string) $order['buyer_note']) ?></div>
      <?php endif; ?>
      <div class="mt-4 flex gap-2">
        <a href="/sales/receipt/<?= (int) $order['id'] ?>" target="_blank" rel="noopener"
          class="flex-1 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold shadow flex items-center justify-center gap-2">
          <i class="fa-solid fa-receipt"></i>Cetak Struk Thermal 80mm
        </a>
      </div>
    </div>

    <div class="glass-card rounded-2xl shadow-sm overflow-hidden">
      <div class="p-5 border-b border-slate-100 dark:border-slate-800">
        <h3 class="font-display text-sm font-bold text-slate-800 dark:text-slate-100"><i class="fa-solid fa-boxes-stacked mr-1 text-brand-600"></i>Item Pesanan</h3>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-xs">
          <thead>
            <tr class="text-left text-slate-500 dark:text-slate-400 border-b border-slate-100 dark:border-slate-800">
              <th class="px-5 py-3 font-semibold">Produk</th>
              <th class="px-5 py-3 font-semibold text-center">Qty</th>
              <th class="px-5 py-3 font-semibold text-right">Harga</th>
              <th class="px-5 py-3 font-semibold text-right">Subtotal</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($items as $it): $it = (array) $it; ?>
              <tr class="border-b border-slate-50 dark:border-slate-800/60">
                <td class="px-5 py-3 font-bold text-slate-700 dark:text-slate-200"><?= e((string) $it['product_name']) ?></td>
                <td class="px-5 py-3 text-center"><?= (int) $it['quantity'] ?></td>
                <td class="px-5 py-3 text-right"><?= rupiah((float) $it['unit_price']) ?></td>
                <td class="px-5 py-3 text-right font-bold"><?= rupiah((float) $it['total_price']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr class="bg-slate-50 dark:bg-slate-800/60">
              <td colspan="3" class="px-5 py-3 text-right font-bold text-slate-600 dark:text-slate-300">TOTAL</td>
              <td class="px-5 py-3 text-right font-extrabold text-brand-600 text-sm"><?= rupiah($total) ?></td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>

  <div class="space-y-4">
    <?php if ($canManage): ?>
      <div class="glass-card rounded-2xl p-5 shadow-sm">
        <h3 class="font-display text-sm font-bold text-slate-800 dark:text-slate-100 mb-3"><i class="fa-solid fa-arrows-rotate mr-1 text-brand-600"></i>Ubah Status</h3>
        <form method="post" action="/sales/<?= (int) $order['id'] ?>/status" class="space-y-3">
          <?= Csrf::field() ?>
          <select name="status" class="w-full px-3 py-2.5 text-xs border rounded-xl bg-white dark:bg-slate-800">
            <?php foreach ($statuses as $s): ?>
              <option value="<?= e((string) $s) ?>" <?= (string) $order['status'] === $s ? 'selected' : '' ?>><?= e((string) $s) ?></option>
            <?php endforeach; ?>
          </select>
          <button class="w-full py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow">Simpan Status</button>
        </form>
        <p class="text-[10px] text-slate-400 mt-2">Membatalkan order akan otomatis mengembalikan stok produk.</p>
      </div>
    <?php endif; ?>

    <?php if ($canDelete): ?>
      <div class="glass-card rounded-2xl p-5 shadow-sm border border-red-100">
        <h3 class="font-display text-sm font-bold text-red-600 mb-1"><i class="fa-solid fa-trash mr-1"></i>Hapus Transaksi</h3>
        <?php if ((string) $order['status'] === 'CANCELLED'): ?>
          <p class="text-[10px] text-slate-400 mb-3">Transaksi sudah batal — stok & saldo sudah dikembalikan saat pembatalan. Hapus permanen hanya membersihkan data dari daftar (khusus Super Admin).</p>
          <form method="post" action="/sales/<?= (int) $order['id'] ?>/delete"
            onsubmit="return confirm('HAPUS PERMANEN transaksi batal <?= e((string) $order['order_no']) ?>?\n\nData hanya dihapus dari daftar; stok & saldo sudah dipulihkan saat pembatalan.\nTindakan ini tidak dapat dibatalkan.')">
            <?= Csrf::field() ?>
            <button class="w-full py-2.5 rounded-xl bg-red-500 hover:bg-red-600 text-white text-xs font-bold shadow flex items-center justify-center gap-2">
              <i class="fa-solid fa-trash"></i>Hapus Transaksi Batal
            </button>
          </form>
        <?php else: ?>
          <p class="text-[10px] text-slate-400 mb-3">Khusus Super Admin. Stok dikembalikan & saldo anggota/tabungan (bila dipakai) direfund otomatis, lalu transaksi dihapus permanen.</p>
          <form method="post" action="/sales/<?= (int) $order['id'] ?>/delete"
            onsubmit="return confirm('HAPUS PERMANEN transaksi <?= e((string) $order['order_no']) ?>?\n\nStok & saldo anggota/tabungan akan disesuaikan otomatis.\nTindakan ini tidak dapat dibatalkan.')">
            <?= Csrf::field() ?>
            <button class="w-full py-2.5 rounded-xl bg-red-500 hover:bg-red-600 text-white text-xs font-bold shadow flex items-center justify-center gap-2">
              <i class="fa-solid fa-trash"></i>Hapus Permanen
            </button>
          </form>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <div class="glass-card rounded-2xl p-5 shadow-sm">
      <h3 class="font-display text-sm font-bold text-slate-800 dark:text-slate-100 mb-3"><i class="fa-solid fa-whatsapp mr-1 text-emerald-500"></i>Hubungi Pembeli</h3>
      <?php $phone = preg_replace('/[^0-9]/', '', (string) ($order['buyer_phone'] ?? '')); ?>
      <?php if ($phone !== ''): ?>
        <?php if (str_starts_with($phone, '0')) { $phone = '62' . substr($phone, 1); } ?>
        <a href="https://wa.me/<?= e($phone) ?>?text=<?= rawurlencode('Halo ' . $order['buyer_name'] . ', terkait pesanan ' . $order['order_no'] . ' di KUTT Suka Makmur...') ?>" target="_blank" rel="noopener"
          class="w-full py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-bold shadow flex items-center justify-center gap-2">
          <i class="fa-brands fa-whatsapp"></i>Chat WhatsApp
        </a>
      <?php else: ?>
        <p class="text-xs text-slate-400">Nomor WhatsApp pembeli tidak tersedia.</p>
      <?php endif; ?>
    </div>
  </div>
</div>
