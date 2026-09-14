<?php
/**
 * Modul kas: buku kas masuk/keluar. Variables: $rows, $saldo, $masuk, $keluar,
 * $accounts, $jenis, $canCreate.
 */

use App\Core\Csrf;

$rows = $rows ?? [];
$accounts = $accounts ?? [];
$saldo = (float) ($saldo ?? 0);
$masuk = (float) ($masuk ?? 0);
$keluar = (float) ($keluar ?? 0);
$jenis = (string) ($jenis ?? '');
$canCreate = (bool) ($canCreate ?? false);
?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-5">
  <div class="glass-card rounded-2xl p-5 shadow-sm">
    <p class="text-[10px] font-bold text-slate-400 uppercase">Saldo Kas Saat Ini</p>
    <h4 class="text-xl font-extrabold font-poppins text-brand-600 mt-1"><?= rupiah($saldo) ?></h4>
  </div>
  <div class="glass-card rounded-2xl p-5 shadow-sm">
    <p class="text-[10px] font-bold text-slate-400 uppercase">Total Kas Masuk</p>
    <h4 class="text-xl font-extrabold font-poppins text-emerald-600 mt-1"><?= rupiah($masuk) ?></h4>
  </div>
  <div class="glass-card rounded-2xl p-5 shadow-sm">
    <p class="text-[10px] font-bold text-slate-400 uppercase">Total Kas Keluar</p>
    <h4 class="text-xl font-extrabold font-poppins text-red-500 mt-1"><?= rupiah($keluar) ?></h4>
  </div>
</div>

<div class="glass-card rounded-2xl shadow-sm overflow-hidden">
  <div class="p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800">
    <form method="get" action="/kas" class="flex items-center gap-2">
      <select name="jenis" class="px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800">
        <option value="">Semua Transaksi</option>
        <option value="MASUK" <?= $jenis === 'MASUK' ? 'selected' : '' ?>>Kas Masuk</option>
        <option value="KELUAR" <?= $jenis === 'KELUAR' ? 'selected' : '' ?>>Kas Keluar</option>
      </select>
      <button class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold"><i class="fa-solid fa-filter"></i></button>
    </form>
    <div class="flex items-center gap-2">
      <a href="/kas/export/excel" class="px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition"><i class="fa-solid fa-file-excel mr-1 text-emerald-600"></i>Excel</a>
      <button type="button" onclick="window.print()" class="px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition"><i class="fa-solid fa-print mr-1"></i>Cetak</button>
      <?php if ($canCreate): ?>
        <button type="button" onclick="openModal('modal-kas')" class="px-4 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow"><i class="fa-solid fa-plus mr-1"></i>Transaksi Kas</button>
      <?php endif; ?>
    </div>
  </div>

  <div class="overflow-x-auto">
    <table class="w-full text-xs">
      <thead>
        <tr class="text-left text-slate-500 dark:text-slate-400 border-b border-slate-100 dark:border-slate-800">
          <th class="px-5 py-3 font-semibold">No. Voucher</th>
          <th class="px-5 py-3 font-semibold">Kategori</th>
          <th class="px-5 py-3 font-semibold">Keterangan</th>
          <th class="px-5 py-3 font-semibold">Tanggal</th>
          <th class="px-5 py-3 font-semibold text-right">Nominal</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr class="border-b border-slate-50 dark:border-slate-800/60 hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
            <td class="px-5 py-3 font-mono text-[11px] text-brand-600 font-bold"><?= e((string) $r['voucher_no']) ?></td>
            <td class="px-5 py-3">
              <p class="font-bold text-slate-700 dark:text-slate-200"><?= e((string) ($r['account_name'] ?? $r['account_code'])) ?></p>
              <p class="text-[10px] text-slate-400 font-mono"><?= e((string) $r['account_code']) ?></p>
            </td>
            <td class="px-5 py-3 text-slate-600 dark:text-slate-300"><?= e((string) $r['keterangan']) ?></td>
            <td class="px-5 py-3 text-slate-500"><?= e(tanggal((string) $r['tanggal_trans'])) ?></td>
            <td class="px-5 py-3 text-right">
              <?php if ($r['jenis_kas'] === 'MASUK'): ?>
                <span class="text-emerald-600 font-bold">+ <?= rupiah($r['nominal']) ?></span>
              <?php else: ?>
                <span class="text-red-500 font-bold">&minus; <?= rupiah($r['nominal']) ?></span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if ($rows === []): ?>
          <tr><td colspan="5" class="px-5 py-10 text-center text-slate-400">Belum ada transaksi kas.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if ($canCreate): ?>
<div id="modal-kas" class="hidden fixed inset-0 z-[70] bg-slate-950/60 backdrop-blur-sm flex items-center justify-center p-4">
  <div class="glass-card rounded-2xl shadow-2xl w-full max-w-sm p-5 max-h-[90vh] overflow-y-auto custom-scrollbar">
    <h3 class="font-display text-sm font-bold text-slate-800 dark:text-slate-100 mb-4"><i class="fa-solid fa-money-bill-transfer mr-1 text-brand-600"></i>Transaksi Kas</h3>
    <form method="post" action="/kas" class="space-y-3">
      <?= Csrf::field() ?>
      <div class="grid grid-cols-2 gap-2">
        <div>
          <label class="text-[10px] font-bold text-slate-500 uppercase">Jenis *</label>
          <select name="jenis_kas" required class="w-full mt-1 px-3 py-2.5 text-xs border rounded-xl bg-white dark:bg-slate-800">
            <option value="MASUK">Kas Masuk</option>
            <option value="KELUAR">Kas Keluar</option>
          </select>
        </div>
        <div>
          <label class="text-[10px] font-bold text-slate-500 uppercase">Tanggal *</label>
          <input type="date" name="tanggal_trans" required value="<?= date('Y-m-d') ?>" class="w-full mt-1 px-3 py-2.5 text-xs border rounded-xl bg-white dark:bg-slate-800">
        </div>
      </div>
      <div>
        <label class="text-[10px] font-bold text-slate-500 uppercase">Kategori Akun *</label>
        <select name="account_code" required class="w-full mt-1 px-3 py-2.5 text-xs border rounded-xl bg-white dark:bg-slate-800">
          <?php foreach ($accounts as $a): ?>
            <option value="<?= e((string) $a['account_code']) ?>"><?= e($a['account_code'] . ' - ' . $a['account_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="text-[10px] font-bold text-slate-500 uppercase">Nominal (Rp) *</label>
        <input type="number" name="nominal" required min="1000" step="1000" class="w-full mt-1 px-3 py-2.5 text-xs border rounded-xl bg-white dark:bg-slate-800">
      </div>
      <div>
        <label class="text-[10px] font-bold text-slate-500 uppercase">Keterangan</label>
        <input type="text" name="keterangan" maxlength="255" class="w-full mt-1 px-3 py-2.5 text-xs border rounded-xl bg-white dark:bg-slate-800">
      </div>
      <div class="flex justify-end gap-2 pt-2">
        <button type="button" onclick="closeModal('modal-kas')" class="px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-600 dark:text-slate-300">Batal</button>
        <button type="submit" class="px-5 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow"><i class="fa-solid fa-floppy-disk mr-1"></i>Simpan</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>
