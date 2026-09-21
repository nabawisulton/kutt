<?php
/**
 * Pesanan penjualan (multi channel). Variables: $result, $filters, $cashiers,
 * $newCount, $statuses, $canManage.
 */

use App\Core\Csrf;

$result = (array) ($result ?? ['rows' => [], 'total' => 0, 'page' => 1, 'pages' => 1]);
$filters = (array) ($filters ?? []);
$statuses = (array) ($statuses ?? []);
$rows = (array) ($result['rows'] ?? []);

$statusStyle = [
    'NEW'        => 'bg-blue-50 text-blue-700 border border-blue-200',
    'PAID'       => 'bg-emerald-50 text-emerald-700 border border-emerald-200',
    'PROCESSING' => 'bg-amber-50 text-amber-700 border border-amber-200',
    'COMPLETED'  => 'bg-slate-100 text-slate-700 border border-slate-200',
    'CANCELLED'  => 'bg-red-50 text-red-600 border border-red-200',
];
$channelIcon = ['MARKETPLACE' => 'fa-solid fa-globe', 'POS' => 'fa-solid fa-cash-register'];
?>

<div class="glass-card rounded-2xl shadow-sm overflow-hidden">
  <div class="p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800">
    <form method="get" action="/sales" class="flex flex-wrap items-center gap-2">
      <input type="text" name="q" value="<?= e((string) ($filters['q'] ?? '')) ?>" placeholder="No order / pembeli / HP..." class="px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800 w-44">
      <select name="status" class="px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800">
        <option value="">Semua Status</option>
        <?php foreach ($statuses as $s): ?>
          <option value="<?= e((string) $s) ?>" <?= ($filters['status'] ?? '') === $s ? 'selected' : '' ?>><?= e((string) $s) ?></option>
        <?php endforeach; ?>
      </select>
      <select name="channel" class="px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800">
        <option value="">Semua Channel</option>
        <option value="MARKETPLACE" <?= ($filters['channel'] ?? '') === 'MARKETPLACE' ? 'selected' : '' ?>>Marketplace</option>
        <option value="POS" <?= ($filters['channel'] ?? '') === 'POS' ? 'selected' : '' ?>>Kasir (POS)</option>
      </select>
      <input type="date" name="from" value="<?= e((string) ($filters['from'] ?? '')) ?>" class="px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800">
      <input type="date" name="to" value="<?= e((string) ($filters['to'] ?? '')) ?>" class="px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800">
      <button class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold"><i class="fa-solid fa-filter"></i></button>
    </form>
    <div class="flex items-center gap-2">
      <a href="/sales/export/orders?from=<?= e((string) ($filters['from'] ?? date('Y-m-01'))) ?>&to=<?= e((string) ($filters['to'] ?? date('Y-m-d'))) ?>" class="px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition"><i class="fa-solid fa-file-excel mr-1 text-emerald-600"></i>Excel</a>
      <a href="/sales/pos" class="px-4 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow"><i class="fa-solid fa-cash-register mr-1"></i>Buka Kasir</a>
    </div>
  </div>

  <div class="overflow-x-auto">
    <table class="w-full text-xs">
      <thead>
        <tr class="text-left text-slate-500 dark:text-slate-400 border-b border-slate-100 dark:border-slate-800">
          <th class="px-5 py-3 font-semibold">No. Order</th>
          <th class="px-5 py-3 font-semibold">Channel</th>
          <th class="px-5 py-3 font-semibold">Pembeli</th>
          <th class="px-5 py-3 font-semibold">Kasir</th>
          <th class="px-5 py-3 font-semibold">Tanggal</th>
          <th class="px-5 py-3 font-semibold text-center">Item</th>
          <th class="px-5 py-3 font-semibold text-right">Total</th>
          <th class="px-5 py-3 font-semibold text-center">Status</th>
          <th class="px-5 py-3 font-semibold text-center">Hapus</th>
          <th class="px-5 py-3 font-semibold text-right">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): $r = (array) $r; ?>
          <tr class="border-b border-slate-50 dark:border-slate-800/60 hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
            <td class="px-5 py-3 font-mono text-[11px] font-bold text-brand-600"><?= e((string) $r['order_no']) ?></td>
            <td class="px-5 py-3">
              <span class="inline-flex items-center gap-1 text-[10px] font-bold text-slate-600 dark:text-slate-300">
                <i class="<?= $channelIcon[$r['channel']] ?? 'fa-solid fa-tag' ?> text-slate-400"></i><?= e((string) $r['channel']) ?>
              </span>
            </td>
            <td class="px-5 py-3">
              <p class="font-bold text-slate-700 dark:text-slate-200"><?= e((string) $r['buyer_name']) ?></p>
              <p class="text-[10px] text-slate-400"><?= e((string) ($r['buyer_phone'] ?? '')) ?></p>
            </td>
            <td class="px-5 py-3 text-slate-500"><?= e((string) ($r['cashier_name'] ?? '—')) ?></td>
            <td class="px-5 py-3 text-slate-500 whitespace-nowrap"><?= e(date('d/m/Y H:i', strtotime((string) $r['created_at']))) ?></td>
            <td class="px-5 py-3 text-center text-slate-600 dark:text-slate-300"><?= (int) $r['item_count'] ?></td>
            <td class="px-5 py-3 text-right font-bold text-slate-700 dark:text-slate-200"><?= rupiah((float) $r['total_amount']) ?></td>
            <td class="px-5 py-3 text-center">
              <span class="px-2 py-1 rounded-lg text-[10px] font-bold <?= $statusStyle[$r['status']] ?? 'bg-slate-100 text-slate-600' ?>"><?= e((string) $r['status']) ?></span>
            </td>
            <td class="px-5 py-3 text-center">
              <?php if (!empty($canDelete) && (string) $r['status'] === 'CANCELLED'): ?>
                <form method="post" action="/sales/<?= (int) $r['id'] ?>/delete"
                  onsubmit="return confirm('HAPUS PERMANEN transaksi batal <?= e((string) $r['order_no']) ?>?\n\nStok & saldo anggota sudah dikembalikan saat pembatalan, jadi data hanya dihapus dari daftar.\nTindakan ini khusus Super Admin dan tidak dapat dibatalkan.')">
                  <?= Csrf::field() ?>
                  <button class="px-2 py-1.5 rounded-lg bg-red-50 text-red-600 hover:bg-red-100" title="Hapus permanen transaksi batal (Super Admin)"><i class="fa-solid fa-trash"></i></button>
                </form>
              <?php else: ?>
                <span class="text-slate-300 dark:text-slate-600">&mdash;</span>
              <?php endif; ?>
            </td>
            <td class="px-5 py-3 text-right">
              <div class="flex items-center justify-end gap-1">
                <a href="/sales/<?= (int) $r['id'] ?>" class="px-2 py-1.5 rounded-lg bg-slate-100 text-slate-600 hover:bg-slate-200" title="Detail"><i class="fa-solid fa-eye"></i></a>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if ($rows === []): ?>
          <tr><td colspan="10" class="px-5 py-10 text-center text-slate-400">Belum ada pesanan.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php $pages = (int) $result['pages']; if ($pages > 1): ?>
    <div class="p-4 flex items-center justify-between border-t border-slate-100 dark:border-slate-800">
      <p class="text-[10px] text-slate-400">Halaman <?= (int) $result['page'] ?> dari <?= $pages ?> · total <?= (int) $result['total'] ?> pesanan</p>
      <div class="flex gap-1">
        <?php for ($i = 1; $i <= $pages && $i <= 10; $i++): ?>
          <a href="/sales?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"
            class="px-3 py-1.5 rounded-lg text-[11px] font-bold <?= $i === (int) $result['page'] ? 'bg-brand-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>"><?= $i ?></a>
        <?php endfor; ?>
      </div>
    </div>
  <?php endif; ?>
</div>
