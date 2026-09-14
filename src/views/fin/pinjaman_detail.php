<?php
/**
 * Detail pinjaman + jadwal angsuran + pembayaran.
 * Variables: $loan, $schedules, $payments, $canPay.
 */

use App\Core\Csrf;

$loan = $loan ?? [];
$schedules = $schedules ?? [];
$payments = $payments ?? [];
$canPay = (bool) ($canPay ?? false);

$pokok = (float) $loan['pokok_pinjaman'];
$totalPokokDue = array_sum(array_map(static fn ($s) => (float) $s['pokok_due'], $schedules));
$pokokPaid = array_sum(array_map(static fn ($s) => $s['status'] === 'PAID' ? (float) $s['pokok_due'] : 0.0, $schedules));
$bungaPaid = array_sum(array_map(static fn ($p) => (float) $p['bayar_bunga'], $payments));
$nextUnpaid = null;
foreach ($schedules as $s) {
    if ($s['status'] === 'UNPAID') { $nextUnpaid = $s; break; }
}
$progress = $totalPokokDue > 0 ? (int) round($pokokPaid / $totalPokokDue * 100) : 0;
?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-5">
  <div class="glass-card rounded-2xl p-5 shadow-sm">
    <p class="text-[10px] font-bold text-slate-400 uppercase">Plafon Pinjaman</p>
    <h4 class="text-xl font-extrabold font-poppins text-slate-800 dark:text-slate-100 mt-1"><?= rupiah($pokok) ?></h4>
    <p class="text-[11px] text-slate-500 mt-1"><?= (int) $loan['tenor_bulan'] ?> bulan · <?= e((string) $loan['sistem_bunga']) ?> <?= number_format((float) $loan['bunga_pertahun'], 1) ?>%/tahun</p>
  </div>
  <div class="glass-card rounded-2xl p-5 shadow-sm">
    <p class="text-[10px] font-bold text-slate-400 uppercase">Sisa Pokok</p>
    <h4 class="text-xl font-extrabold font-poppins text-amber-500 mt-1"><?= rupiah($pokok - $pokokPaid) ?></h4>
    <div class="mt-2 h-1.5 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
      <div class="h-full bg-brand-600 rounded-full" style="width: <?= $progress ?>%"></div>
    </div>
    <p class="text-[10px] text-slate-400 mt-1"><?= $progress ?>% terbayar · bunga diterima <?= rupiah($bungaPaid) ?></p>
  </div>
  <div class="glass-card rounded-2xl p-5 shadow-sm">
    <p class="text-[10px] font-bold text-slate-400 uppercase">Status</p>
    <h4 class="text-xl font-extrabold font-poppins text-brand-600 mt-1"><?= e((string) $loan['status']) ?></h4>
    <?php if ($canPay && $loan['status'] === 'DISBURSED' && $nextUnpaid !== null): ?>
      <button type="button" onclick="openModal('modal-bayar')" class="mt-2 px-4 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow">
        <i class="fa-solid fa-money-bill-wave mr-1"></i>Bayar Angsuran ke-<?= (int) $nextUnpaid['angsuran_ke'] ?>
      </button>
    <?php endif; ?>
  </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-2 gap-5">
  <!-- Jadwal angsuran -->
  <div class="glass-card rounded-2xl shadow-sm overflow-hidden">
    <div class="p-5 border-b border-slate-100 dark:border-slate-800">
      <h3 class="font-display text-sm font-bold text-slate-800 dark:text-slate-100"><i class="fa-solid fa-calendar-days mr-1 text-brand-600"></i>Jadwal Angsuran</h3>
    </div>
    <div class="overflow-x-auto max-h-96 overflow-y-auto custom-scrollbar">
      <table class="w-full text-xs">
        <thead class="sticky top-0 bg-white dark:bg-slate-900">
          <tr class="text-left text-slate-500 dark:text-slate-400 border-b border-slate-100 dark:border-slate-800">
            <th class="px-5 py-3 font-semibold">Ke-</th>
            <th class="px-5 py-3 font-semibold">Jatuh Tempo</th>
            <th class="px-5 py-3 font-semibold text-right">Pokok</th>
            <th class="px-5 py-3 font-semibold text-right">Bunga</th>
            <th class="px-5 py-3 font-semibold">Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($schedules as $s): ?>
            <tr class="border-b border-slate-50 dark:border-slate-800/60 <?= $s['status'] === 'UNPAID' && $nextUnpaid !== null && (int) $s['id'] === (int) $nextUnpaid['id'] ? 'bg-amber-50/60 dark:bg-amber-900/10' : '' ?>">
              <td class="px-5 py-2.5 font-bold text-slate-700 dark:text-slate-200"><?= (int) $s['angsuran_ke'] ?></td>
              <td class="px-5 py-2.5 text-slate-500"><?= e(tanggal((string) $s['jatuh_tempo'])) ?></td>
              <td class="px-5 py-2.5 text-right"><?= rupiah($s['pokok_due']) ?></td>
              <td class="px-5 py-2.5 text-right"><?= rupiah($s['bunga_due']) ?></td>
              <td class="px-5 py-2.5">
                <?php if ($s['status'] === 'PAID'): ?>
                  <span class="text-emerald-600 font-bold text-[10px]"><i class="fa-solid fa-circle-check mr-0.5"></i>PAID</span>
                <?php else: ?>
                  <span class="text-amber-600 font-bold text-[10px]"><i class="fa-solid fa-clock mr-0.5"></i>UNPAID</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if ($schedules === []): ?>
            <tr><td colspan="5" class="px-5 py-8 text-center text-slate-400">Jadwal dibuat saat pinjaman dicairkan.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Riwayat pembayaran -->
  <div class="glass-card rounded-2xl shadow-sm overflow-hidden">
    <div class="p-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
      <h3 class="font-display text-sm font-bold text-slate-800 dark:text-slate-100"><i class="fa-solid fa-receipt mr-1 text-brand-600"></i>Riwayat Pembayaran</h3>
      <button type="button" onclick="window.print()" class="px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 text-[11px] font-bold text-slate-600 dark:text-slate-300"><i class="fa-solid fa-print mr-1"></i>Cetak</button>
    </div>
    <div class="overflow-x-auto max-h-96 overflow-y-auto custom-scrollbar">
      <table class="w-full text-xs">
        <thead class="sticky top-0 bg-white dark:bg-slate-900">
          <tr class="text-left text-slate-500 dark:text-slate-400 border-b border-slate-100 dark:border-slate-800">
            <th class="px-5 py-3 font-semibold">No. Bukti</th>
            <th class="px-5 py-3 font-semibold">Tanggal</th>
            <th class="px-5 py-3 font-semibold text-right">Total</th>
            <th class="px-5 py-3 font-semibold">Operator</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($payments as $p): ?>
            <tr class="border-b border-slate-50 dark:border-slate-800/60">
              <td class="px-5 py-2.5 font-mono text-[11px] text-brand-600 font-bold"><?= e((string) $p['payment_no']) ?> <span class="text-slate-400">(ke-<?= (int) $p['angsuran_ke'] ?>)</span></td>
              <td class="px-5 py-2.5 text-slate-500"><?= e(tanggal((string) $p['tanggal_bayar'])) ?></td>
              <td class="px-5 py-2.5 text-right font-bold"><?= rupiah($p['total_bayar']) ?><?= (float) $p['denda'] > 0 ? ' <span class="text-red-400 text-[10px]">+denda</span>' : '' ?></td>
              <td class="px-5 py-2.5 text-slate-500"><?= e((string) ($p['operator'] ?? '-')) ?></td>
            </tr>
          <?php endforeach; ?>
          <?php if ($payments === []): ?>
            <tr><td colspan="4" class="px-5 py-8 text-center text-slate-400">Belum ada pembayaran.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="mt-5">
  <a href="/pinjaman" class="text-xs font-bold text-slate-500 hover:text-slate-700 dark:hover:text-slate-300"><i class="fa-solid fa-arrow-left mr-1"></i>Kembali ke daftar pinjaman</a>
</div>

<?php if ($canPay && $loan['status'] === 'DISBURSED' && $nextUnpaid !== null): ?>
<div id="modal-bayar" class="hidden fixed inset-0 z-[70] bg-slate-950/60 backdrop-blur-sm flex items-center justify-center p-4">
  <div class="glass-card rounded-2xl shadow-2xl w-full max-w-sm p-5 max-h-[90vh] overflow-y-auto custom-scrollbar">
    <h3 class="font-display text-sm font-bold text-slate-800 dark:text-slate-100 mb-4"><i class="fa-solid fa-money-bill-wave mr-1 text-brand-600"></i>Pembayaran Angsuran ke-<?= (int) $nextUnpaid['angsuran_ke'] ?></h3>
    <form method="post" action="/pinjaman/detail/<?= (int) $loan['id'] ?>/pay" class="space-y-3">
      <?= Csrf::field() ?>
      <div class="p-3 rounded-xl bg-brand-50 dark:bg-slate-800 text-xs space-y-1">
        <div class="flex justify-between"><span class="text-slate-500">Pokok</span><strong><?= rupiah($nextUnpaid['pokok_due']) ?></strong></div>
        <div class="flex justify-between"><span class="text-slate-500">Bunga</span><strong><?= rupiah($nextUnpaid['bunga_due']) ?></strong></div>
        <div class="flex justify-between border-t border-brand-100 dark:border-slate-700 pt-1"><span class="text-slate-500">Total</span><strong class="text-brand-600"><?= rupiah((float) $nextUnpaid['pokok_due'] + (float) $nextUnpaid['bunga_due']) ?></strong></div>
      </div>
      <div class="grid grid-cols-2 gap-2">
        <div>
          <label class="text-[10px] font-bold text-slate-500 uppercase">Denda (Rp)</label>
          <input type="number" name="denda" min="0" step="1000" value="0" class="w-full mt-1 px-3 py-2.5 text-xs border rounded-xl bg-white dark:bg-slate-800">
        </div>
        <div>
          <label class="text-[10px] font-bold text-slate-500 uppercase">Tanggal *</label>
          <input type="date" name="tanggal_bayar" required value="<?= date('Y-m-d') ?>" class="w-full mt-1 px-3 py-2.5 text-xs border rounded-xl bg-white dark:bg-slate-800">
        </div>
      </div>
      <div>
        <label class="text-[10px] font-bold text-slate-500 uppercase">Keterangan</label>
        <input type="text" name="keterangan" maxlength="255" class="w-full mt-1 px-3 py-2.5 text-xs border rounded-xl bg-white dark:bg-slate-800">
      </div>
      <div class="flex justify-end gap-2 pt-2">
        <button type="button" onclick="closeModal('modal-bayar')" class="px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-600 dark:text-slate-300">Batal</button>
        <button type="submit" class="px-5 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow"><i class="fa-solid fa-floppy-disk mr-1"></i>Simpan</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>
