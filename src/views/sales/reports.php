<?php
/**
 * Laporan penjualan & stok. Variables: $report, $from, $to, $cashier,
 * $cashiers, $inventory, $canExport.
 */

$report = (array) ($report ?? []);
$inventory = (array) ($inventory ?? []);
$byDay = (array) ($report['byDay'] ?? []);
$byCashier = (array) ($report['byCashier'] ?? []);
$byProduct = (array) ($report['byProduct'] ?? []);
$byChannel = (array) ($report['byChannel'] ?? []);
$lowStock = (array) ($inventory['lowStock'] ?? []);
$canExport = (bool) ($canExport ?? false);
?>

<div class="glass-card rounded-2xl shadow-sm p-4 mb-5">
  <form method="get" action="/sales/reports" class="flex flex-wrap items-end gap-2">
    <div>
      <label class="text-[10px] font-bold text-slate-400 uppercase block mb-1">Dari</label>
      <input type="date" name="from" value="<?= e($from) ?>" class="px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800">
    </div>
    <div>
      <label class="text-[10px] font-bold text-slate-400 uppercase block mb-1">Sampai</label>
      <input type="date" name="to" value="<?= e($to) ?>" class="px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800">
    </div>
    <div>
      <label class="text-[10px] font-bold text-slate-400 uppercase block mb-1">Kasir</label>
      <select name="cashier" class="px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800 min-w-[160px]">
        <option value="0">Semua Kasir</option>
        <?php foreach ($cashiers as $c): $c = (array) $c; ?>
          <option value="<?= (int) $c['id'] ?>" <?= (int) $cashier === (int) $c['id'] ? 'selected' : '' ?>><?= e((string) $c['full_name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <button class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold"><i class="fa-solid fa-filter mr-1"></i>Terapkan</button>
    <?php if ($canExport): ?>
      <a href="/sales/export/orders?from=<?= e($from) ?>&to=<?= e($to) ?>" class="px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition"><i class="fa-solid fa-file-excel mr-1 text-emerald-600"></i>Export Excel</a>
    <?php endif; ?>
  </form>
</div>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-5">
  <div class="glass-card rounded-2xl p-5 shadow-sm">
    <p class="text-[10px] font-bold text-slate-400 uppercase">Omzet (tercatat)</p>
    <h4 class="text-xl font-extrabold font-poppins text-slate-800 dark:text-slate-100 mt-1"><?= rupiah((float) ($report['revenue'] ?? 0)) ?></h4>
    <p class="text-[10px] text-slate-400"><?= (int) ($report['orders'] ?? 0) ?> transaksi</p>
  </div>
  <div class="glass-card rounded-2xl p-5 shadow-sm">
    <p class="text-[10px] font-bold text-slate-400 uppercase">Omzet Terbayar</p>
    <h4 class="text-xl font-extrabold font-poppins text-brand-600 mt-1"><?= rupiah((float) ($report['paidRevenue'] ?? 0)) ?></h4>
    <p class="text-[10px] text-slate-400">status PAID/PROCESSING/COMPLETED</p>
  </div>
  <div class="glass-card rounded-2xl p-5 shadow-sm">
    <p class="text-[10px] font-bold text-slate-400 uppercase">Marketplace</p>
    <h4 class="text-xl font-extrabold font-poppins text-blue-600 mt-1"><?= rupiah((float) ($byChannel['MARKETPLACE'] ?? 0)) ?></h4>
  </div>
  <div class="glass-card rounded-2xl p-5 shadow-sm">
    <p class="text-[10px] font-bold text-slate-400 uppercase">Kasir (POS)</p>
    <h4 class="text-xl font-extrabold font-poppins text-amber-500 mt-1"><?= rupiah((float) ($byChannel['POS'] ?? 0)) ?></h4>
  </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-5">
  <div class="glass-card rounded-2xl shadow-sm overflow-hidden">
    <div class="p-5 border-b border-slate-100 dark:border-slate-800"><h3 class="font-display text-sm font-bold text-slate-800 dark:text-slate-100"><i class="fa-solid fa-calendar-day mr-1 text-brand-600"></i>Penjualan per Hari</h3></div>
    <table class="w-full text-xs">
      <thead><tr class="text-left text-slate-500 border-b border-slate-100 dark:border-slate-800"><th class="px-5 py-3 font-semibold">Tanggal</th><th class="px-5 py-3 font-semibold text-center">Trx</th><th class="px-5 py-3 font-semibold text-right">Total</th></tr></thead>
      <tbody>
        <?php foreach ($byDay as $d): $d = (array) $d; ?>
          <tr class="border-b border-slate-50 dark:border-slate-800/60">
            <td class="px-5 py-3 text-slate-600 dark:text-slate-300"><?= e(tanggal((string) $d['tanggal'])) ?></td>
            <td class="px-5 py-3 text-center"><?= (int) $d['orders'] ?></td>
            <td class="px-5 py-3 text-right font-bold text-slate-700 dark:text-slate-200"><?= rupiah((float) $d['total']) ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if ($byDay === []): ?><tr><td colspan="3" class="px-5 py-8 text-center text-slate-400">Tidak ada penjualan pada periode ini.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>

  <div class="glass-card rounded-2xl shadow-sm overflow-hidden">
    <div class="p-5 border-b border-slate-100 dark:border-slate-800"><h3 class="font-display text-sm font-bold text-slate-800 dark:text-slate-100"><i class="fa-solid fa-users mr-1 text-brand-600"></i>Performa per Kasir</h3></div>
    <table class="w-full text-xs">
      <thead><tr class="text-left text-slate-500 border-b border-slate-100 dark:border-slate-800"><th class="px-5 py-3 font-semibold">Kasir</th><th class="px-5 py-3 font-semibold text-center">Trx</th><th class="px-5 py-3 font-semibold text-right">Total</th></tr></thead>
      <tbody>
        <?php foreach ($byCashier as $c): $c = (array) $c; ?>
          <tr class="border-b border-slate-50 dark:border-slate-800/60">
            <td class="px-5 py-3 font-bold text-slate-700 dark:text-slate-200"><?= e((string) $c['kasir']) ?></td>
            <td class="px-5 py-3 text-center"><?= (int) $c['orders'] ?></td>
            <td class="px-5 py-3 text-right font-bold text-brand-600"><?= rupiah((float) $c['total']) ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if ($byCashier === []): ?><tr><td colspan="3" class="px-5 py-8 text-center text-slate-400">Tidak ada data.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
  <div class="glass-card rounded-2xl shadow-sm overflow-hidden">
    <div class="p-5 border-b border-slate-100 dark:border-slate-800"><h3 class="font-display text-sm font-bold text-slate-800 dark:text-slate-100"><i class="fa-solid fa-trophy mr-1 text-amber-500"></i>Produk Terlaris</h3></div>
    <table class="w-full text-xs">
      <thead><tr class="text-left text-slate-500 border-b border-slate-100 dark:border-slate-800"><th class="px-5 py-3 font-semibold">Produk</th><th class="px-5 py-3 font-semibold text-center">Qty</th><th class="px-5 py-3 font-semibold text-right">Omzet</th></tr></thead>
      <tbody>
        <?php foreach ($byProduct as $p): $p = (array) $p; ?>
          <tr class="border-b border-slate-50 dark:border-slate-800/60">
            <td class="px-5 py-3 font-bold text-slate-700 dark:text-slate-200"><?= e((string) $p['product_name']) ?></td>
            <td class="px-5 py-3 text-center"><?= (int) $p['qty'] ?></td>
            <td class="px-5 py-3 text-right font-bold text-slate-700 dark:text-slate-200"><?= rupiah((float) $p['total']) ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if ($byProduct === []): ?><tr><td colspan="3" class="px-5 py-8 text-center text-slate-400">Tidak ada data.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>

  <div class="glass-card rounded-2xl shadow-sm overflow-hidden">
    <div class="p-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
      <h3 class="font-display text-sm font-bold text-slate-800 dark:text-slate-100"><i class="fa-solid fa-triangle-exclamation mr-1 text-amber-500"></i>Stok Kritis</h3>
      <a href="/sales/export/stock" class="text-[10px] font-bold text-brand-600 hover:underline">Export stok lengkap</a>
    </div>
    <table class="w-full text-xs">
      <thead><tr class="text-left text-slate-500 border-b border-slate-100 dark:border-slate-800"><th class="px-5 py-3 font-semibold">Produk</th><th class="px-5 py-3 font-semibold text-center">Stok</th><th class="px-5 py-3 font-semibold text-center">Min</th></tr></thead>
      <tbody>
        <?php foreach ($lowStock as $p): $p = (array) $p; ?>
          <tr class="border-b border-slate-50 dark:border-slate-800/60">
            <td class="px-5 py-3">
              <p class="font-bold text-slate-700 dark:text-slate-200"><?= e((string) $p['name']) ?></p>
              <p class="text-[10px] text-slate-400 font-mono"><?= e((string) $p['sku']) ?></p>
            </td>
            <td class="px-5 py-3 text-center font-bold text-amber-600"><?= (int) $p['stock'] ?> <?= e((string) $p['unit']) ?></td>
            <td class="px-5 py-3 text-center text-slate-500"><?= (int) $p['min_stock'] ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if ($lowStock === []): ?><tr><td colspan="3" class="px-5 py-8 text-center text-slate-400">Semua stok aman.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
