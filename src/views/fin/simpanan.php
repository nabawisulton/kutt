<?php
/**
 * Modul simpanan: daftar transaksi + form input + saldo per jenis.
 * Variables: $rows, $balances, $members, $filters, $page, $totalPages, $total, $canCreate.
 */

use App\Core\Csrf;

$rows = $rows ?? [];
$balances = $balances ?? [];
$members = $members ?? [];
$filters = $filters ?? ['q' => '', 'jenis' => '', 'member' => 0];
$page = (int) ($page ?? 1);
$totalPages = (int) ($totalPages ?? 1);
$total = (int) ($total ?? 0);
$canCreate = (bool) ($canCreate ?? false);
$jenisLabels = ['POKOK' => 'Simpanan Pokok', 'WAJIB' => 'Simpanan Wajib', 'SUKARELA' => 'Simpanan Sukarela'];
$qs = static fn (array $over = []): string => '/simpanan?' . http_build_query(array_filter(array_merge([
    'q' => $filters['q'], 'jenis' => $filters['jenis'], 'member' => $filters['member'], 'page' => $page,
], $over), static fn ($v) => $v !== '' && $v !== 0));
?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-5">
  <?php foreach (['POKOK', 'WAJIB', 'SUKARELA'] as $jenis): ?>
    <div class="glass-card rounded-2xl p-5 shadow-sm">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider"><?= e($jenisLabels[$jenis]) ?></p>
          <h4 class="text-xl font-extrabold font-poppins text-slate-800 dark:text-slate-100 mt-1"><?= rupiah($balances[$jenis] ?? 0) ?></h4>
        </div>
        <div class="w-11 h-11 rounded-xl flex items-center justify-center text-lg <?= $jenis === 'POKOK' ? 'bg-emerald-100 text-emerald-600' : ($jenis === 'WAJIB' ? 'bg-amber-100 text-amber-600' : 'bg-blue-100 text-blue-600') ?>">
          <i class="fa-solid fa-piggy-bank"></i>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<div class="glass-card rounded-2xl shadow-sm overflow-hidden">
  <div class="p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800">
    <form method="get" action="/simpanan" class="flex flex-wrap items-center gap-2">
      <input type="text" name="q" value="<?= e($filters['q']) ?>" placeholder="Cari anggota / no. transaksi..."
        class="px-4 py-2 text-xs bg-slate-100 dark:bg-slate-800 border-0 rounded-xl focus:ring-2 focus:ring-brand-600 w-48 sm:w-56 max-w-full">
      <select name="jenis" class="px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800">
        <option value="">Semua Jenis</option>
        <?php foreach ($jenisLabels as $j => $label): ?>
          <option value="<?= e($j) ?>" <?= $filters['jenis'] === $j ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
      <button class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold"><i class="fa-solid fa-magnifying-glass"></i></button>
    </form>
    <div class="flex items-center gap-2">
      <a href="/simpanan/export/excel" class="px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition"><i class="fa-solid fa-file-excel mr-1 text-emerald-600"></i>Excel</a>
      <button type="button" onclick="window.print()" class="px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition"><i class="fa-solid fa-print mr-1"></i>Cetak</button>
      <?php if ($canCreate): ?>
        <button type="button" onclick="openModal('modal-simpanan')" class="px-4 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow"><i class="fa-solid fa-plus mr-1"></i>Transaksi Baru</button>
      <?php endif; ?>
    </div>
  </div>

  <div class="overflow-x-auto">
    <table class="w-full text-xs">
      <thead>
        <tr class="text-left text-slate-500 dark:text-slate-400 border-b border-slate-100 dark:border-slate-800">
          <th class="px-5 py-3 font-semibold">No. Transaksi</th>
          <th class="px-5 py-3 font-semibold">Anggota</th>
          <th class="px-5 py-3 font-semibold">Jenis</th>
          <th class="px-5 py-3 font-semibold">Tipe</th>
          <th class="px-5 py-3 font-semibold text-right">Nominal</th>
          <th class="px-5 py-3 font-semibold">Tanggal</th>
          <th class="px-5 py-3 font-semibold">Operator</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr class="border-b border-slate-50 dark:border-slate-800/60 hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
            <td class="px-5 py-3 font-mono text-[11px] text-brand-600 font-bold"><?= e((string) $r['trans_no']) ?></td>
            <td class="px-5 py-3">
              <p class="font-bold text-slate-800 dark:text-slate-100"><?= e((string) $r['full_name']) ?></p>
              <p class="text-[11px] text-slate-500"><?= e((string) $r['member_no']) ?></p>
            </td>
            <td class="px-5 py-3"><span class="px-2 py-1 rounded-lg text-[10px] font-bold <?= $r['jenis'] === 'POKOK' ? 'bg-emerald-100 text-emerald-700' : ($r['jenis'] === 'WAJIB' ? 'bg-amber-100 text-amber-700' : 'bg-blue-100 text-blue-700') ?>"><?= e($jenisLabels[$r['jenis']] ?? $r['jenis']) ?></span></td>
            <td class="px-5 py-3">
              <?php if ($r['tipe_transaksi'] === 'SETOR'): ?>
                <span class="text-emerald-600 font-bold"><i class="fa-solid fa-arrow-down mr-1"></i>Setor</span>
              <?php else: ?>
                <span class="text-red-500 font-bold"><i class="fa-solid fa-arrow-up mr-1"></i>Tarik</span>
              <?php endif; ?>
            </td>
            <td class="px-5 py-3 text-right font-bold <?= $r['tipe_transaksi'] === 'SETOR' ? 'text-slate-800 dark:text-slate-100' : 'text-red-500' ?>"><?= rupiah($r['nominal']) ?></td>
            <td class="px-5 py-3 text-slate-500"><?= e(tanggal((string) $r['tanggal_trans'])) ?></td>
            <td class="px-5 py-3 text-slate-500"><?= e((string) $r['operator_name']) ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if ($rows === []): ?>
          <tr><td colspan="7" class="px-5 py-10 text-center text-slate-400">Belum ada transaksi simpanan.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php if ($totalPages > 1): ?>
    <div class="p-4 flex items-center justify-between border-t border-slate-100 dark:border-slate-800">
      <p class="text-[11px] text-slate-400">Halaman <?= $page ?> dari <?= $totalPages ?> (<?= $total ?> transaksi)</p>
      <div class="flex gap-1.5">
        <?php if ($page > 1): ?><a href="<?= e($qs(['page' => $page - 1])) ?>" class="px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-600 dark:text-slate-300">&laquo;</a><?php endif; ?>
        <?php if ($page < $totalPages): ?><a href="<?= e($qs(['page' => $page + 1])) ?>" class="px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-600 dark:text-slate-300">&raquo;</a><?php endif; ?>
      </div>
    </div>
  <?php endif; ?>
</div>

<?php if ($canCreate): ?>
<!-- Modal transaksi simpanan -->
<div id="modal-simpanan" class="hidden fixed inset-0 z-[70] bg-slate-950/60 backdrop-blur-sm flex items-center justify-center p-4">
  <div class="glass-card rounded-2xl shadow-2xl w-full max-w-md p-5 max-h-[90vh] overflow-y-auto custom-scrollbar">
    <h3 class="font-display text-sm font-bold text-slate-800 dark:text-slate-100 mb-4"><i class="fa-solid fa-piggy-bank mr-1 text-brand-600"></i>Transaksi Simpanan</h3>
    <form method="post" action="/simpanan" class="space-y-3">
      <?= Csrf::field() ?>
      <div>
        <label class="text-[10px] font-bold text-slate-500 uppercase">Anggota *</label>
        <select name="member_id" required class="w-full mt-1 px-3 py-2.5 text-xs border rounded-xl bg-white dark:bg-slate-800">
          <option value="">-- Pilih Anggota --</option>
          <?php foreach ($members as $m): ?>
            <option value="<?= (int) $m['id'] ?>"><?= e($m['member_no'] . ' - ' . $m['full_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="grid grid-cols-2 gap-2">
        <div>
          <label class="text-[10px] font-bold text-slate-500 uppercase">Jenis *</label>
          <select name="jenis" required class="w-full mt-1 px-3 py-2.5 text-xs border rounded-xl bg-white dark:bg-slate-800">
            <?php foreach ($jenisLabels as $j => $label): ?>
              <option value="<?= e($j) ?>"><?= e($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="text-[10px] font-bold text-slate-500 uppercase">Tipe *</label>
          <select name="tipe_transaksi" required class="w-full mt-1 px-3 py-2.5 text-xs border rounded-xl bg-white dark:bg-slate-800">
            <option value="SETOR">Setoran</option>
            <option value="TARIK">Penarikan</option>
          </select>
        </div>
      </div>
      <div class="grid grid-cols-2 gap-2">
        <div>
          <label class="text-[10px] font-bold text-slate-500 uppercase">Nominal (Rp) *</label>
          <input type="number" name="nominal" required min="1000" step="1000" class="w-full mt-1 px-3 py-2.5 text-xs border rounded-xl bg-white dark:bg-slate-800">
        </div>
        <div>
          <label class="text-[10px] font-bold text-slate-500 uppercase">Tanggal *</label>
          <input type="date" name="tanggal_trans" required value="<?= date('Y-m-d') ?>" class="w-full mt-1 px-3 py-2.5 text-xs border rounded-xl bg-white dark:bg-slate-800">
        </div>
      </div>
      <div>
        <label class="text-[10px] font-bold text-slate-500 uppercase">Keterangan</label>
        <input type="text" name="keterangan" maxlength="255" class="w-full mt-1 px-3 py-2.5 text-xs border rounded-xl bg-white dark:bg-slate-800">
      </div>
      <div class="flex justify-end gap-2 pt-2">
        <button type="button" onclick="closeModal('modal-simpanan')" class="px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-600 dark:text-slate-300">Batal</button>
        <button type="submit" class="px-5 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow"><i class="fa-solid fa-floppy-disk mr-1"></i>Simpan</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>
