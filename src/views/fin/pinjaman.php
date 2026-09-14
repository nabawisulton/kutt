<?php
/**
 * Modul pinjaman: daftar pinjaman + filter status + form pengajuan + aksi approval.
 * Variables: $rows, $members, $status, $q, $pending, $canApprove, $canCreate.
 */

use App\Core\Csrf;

$rows = $rows ?? [];
$members = $members ?? [];
$status = (string) ($status ?? '');
$q = (string) ($q ?? '');
$pending = (int) ($pending ?? 0);
$canApprove = (bool) ($canApprove ?? false);
$canCreate = (bool) ($canCreate ?? false);
$statusBadge = [
    'PENDING'   => 'bg-amber-100 text-amber-700',
    'APPROVED'  => 'bg-blue-100 text-blue-700',
    'REJECTED'  => 'bg-red-100 text-red-600',
    'DISBURSED' => 'bg-brand-100 text-brand-700',
    'LUNAS'     => 'bg-slate-200 text-slate-600',
];
?>

<?php if ($pending > 0 && $canApprove): ?>
  <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-3 text-xs text-amber-800 mb-5">
    <i class="fa-solid fa-triangle-exclamation mr-1"></i><strong><?= $pending ?> pengajuan</strong> menunggu persetujuan Anda.
  </div>
<?php endif; ?>

<div class="glass-card rounded-2xl shadow-sm overflow-hidden">
  <div class="p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800">
    <form method="get" action="/pinjaman" class="flex flex-wrap items-center gap-2">
      <input type="text" name="q" value="<?= e($q) ?>" placeholder="Cari anggota / no. pinjaman..."
        class="px-4 py-2 text-xs bg-slate-100 dark:bg-slate-800 border-0 rounded-xl focus:ring-2 focus:ring-brand-600 w-48 sm:w-56 max-w-full">
      <select name="status" class="px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800">
        <option value="">Semua Status</option>
        <?php foreach (array_keys($statusBadge) as $s): ?>
          <option value="<?= e($s) ?>" <?= $status === $s ? 'selected' : '' ?>><?= e($s) ?></option>
        <?php endforeach; ?>
      </select>
      <button class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold"><i class="fa-solid fa-magnifying-glass"></i></button>
    </form>
    <div class="flex items-center gap-2">
      <a href="/pinjaman/export/excel" class="px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition"><i class="fa-solid fa-file-excel mr-1 text-emerald-600"></i>Excel</a>
      <button type="button" onclick="window.print()" class="px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition"><i class="fa-solid fa-print mr-1"></i>Cetak</button>
      <?php if ($canCreate): ?>
        <button type="button" onclick="openModal('modal-pinjaman')" class="px-4 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow"><i class="fa-solid fa-plus mr-1"></i>Pengajuan Baru</button>
      <?php endif; ?>
    </div>
  </div>

  <div class="overflow-x-auto">
    <table class="w-full text-xs">
      <thead>
        <tr class="text-left text-slate-500 dark:text-slate-400 border-b border-slate-100 dark:border-slate-800">
          <th class="px-5 py-3 font-semibold">No. Pinjaman</th>
          <th class="px-5 py-3 font-semibold">Anggota</th>
          <th class="px-5 py-3 font-semibold text-right">Plafon</th>
          <th class="px-5 py-3 font-semibold">Tenor / Bunga</th>
          <th class="px-5 py-3 font-semibold text-right">Pokok Terbayar</th>
          <th class="px-5 py-3 font-semibold">Status</th>
          <th class="px-5 py-3 font-semibold text-right">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr class="border-b border-slate-50 dark:border-slate-800/60 hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
            <td class="px-5 py-3 font-mono text-[11px] text-brand-600 font-bold"><?= e((string) $r['loan_no']) ?></td>
            <td class="px-5 py-3">
              <p class="font-bold text-slate-800 dark:text-slate-100"><?= e((string) $r['full_name']) ?></p>
              <p class="text-[11px] text-slate-500"><?= e((string) $r['member_no']) ?> · <?= e(tanggal((string) $r['tanggal_pengajuan'])) ?></p>
            </td>
            <td class="px-5 py-3 text-right font-bold text-slate-800 dark:text-slate-100"><?= rupiah($r['pokok_pinjaman']) ?></td>
            <td class="px-5 py-3 text-slate-600 dark:text-slate-300"><?= (int) $r['tenor_bulan'] ?> bln · <?= e((string) $r['sistem_bunga']) ?> <?= number_format((float) $r['bunga_pertahun'], 1) ?>%</td>
            <td class="px-5 py-3 text-right text-slate-600 dark:text-slate-300"><?= rupiah($r['pokok_dibayar']) ?></td>
            <td class="px-5 py-3"><span class="px-2 py-1 rounded-lg text-[10px] font-bold <?= $statusBadge[$r['status']] ?? 'bg-slate-100 text-slate-600' ?>"><?= e((string) $r['status']) ?></span></td>
            <td class="px-5 py-3">
              <div class="flex items-center justify-end gap-1.5">
                <?php if ($canApprove && $r['status'] === 'PENDING'): ?>
                  <form method="post" action="/pinjaman/approve/<?= (int) $r['id'] ?>" onsubmit="return confirmAction(this, 'Setujui pinjaman ini?')"><?= Csrf::field() ?>
                    <button title="Setujui" class="p-2 rounded-lg text-brand-600 hover:bg-brand-50 dark:hover:bg-brand-900/20 transition"><i class="fa-solid fa-check text-xs"></i></button>
                  </form>
                  <form method="post" action="/pinjaman/reject/<?= (int) $r['id'] ?>" onsubmit="return confirmAction(this, 'Tolak pinjaman ini?')"><?= Csrf::field() ?>
                    <button title="Tolak" class="p-2 rounded-lg text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition"><i class="fa-solid fa-xmark text-xs"></i></button>
                  </form>
                <?php endif; ?>
                <a href="/pinjaman/detail/<?= (int) $r['id'] ?>" title="Detail" class="p-2 rounded-lg text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition"><i class="fa-solid fa-eye text-xs"></i></a>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if ($rows === []): ?>
          <tr><td colspan="7" class="px-5 py-10 text-center text-slate-400">Belum ada data pinjaman.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if ($canCreate): ?>
<div id="modal-pinjaman" class="hidden fixed inset-0 z-[70] bg-slate-950/60 backdrop-blur-sm flex items-center justify-center p-4">
  <div class="glass-card rounded-2xl shadow-2xl w-full max-w-md p-5 max-h-[90vh] overflow-y-auto custom-scrollbar">
    <h3 class="font-display text-sm font-bold text-slate-800 dark:text-slate-100 mb-4"><i class="fa-solid fa-hand-holding-dollar mr-1 text-brand-600"></i>Pengajuan Pinjaman</h3>
    <form method="post" action="/pinjaman" class="space-y-3">
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
          <label class="text-[10px] font-bold text-slate-500 uppercase">Plafon (Rp) *</label>
          <input type="number" name="pokok_pinjaman" required min="100000" step="100000" class="w-full mt-1 px-3 py-2.5 text-xs border rounded-xl bg-white dark:bg-slate-800">
        </div>
        <div>
          <label class="text-[10px] font-bold text-slate-500 uppercase">Tenor (bulan) *</label>
          <input type="number" name="tenor_bulan" required min="1" max="60" value="12" class="w-full mt-1 px-3 py-2.5 text-xs border rounded-xl bg-white dark:bg-slate-800">
        </div>
      </div>
      <div class="grid grid-cols-2 gap-2">
        <div>
          <label class="text-[10px] font-bold text-slate-500 uppercase">Bunga / Tahun (%) *</label>
          <input type="number" name="bunga_pertahun" required min="0" max="36" step="0.5" value="12" class="w-full mt-1 px-3 py-2.5 text-xs border rounded-xl bg-white dark:bg-slate-800">
        </div>
        <div>
          <label class="text-[10px] font-bold text-slate-500 uppercase">Sistem Bunga *</label>
          <select name="sistem_bunga" required class="w-full mt-1 px-3 py-2.5 text-xs border rounded-xl bg-white dark:bg-slate-800">
            <option value="FLAT">Flat</option>
            <option value="MENURUN">Menurun (Sliding)</option>
          </select>
        </div>
      </div>
      <div>
        <label class="text-[10px] font-bold text-slate-500 uppercase">Tanggal Pengajuan *</label>
        <input type="date" name="tanggal_pengajuan" required value="<?= date('Y-m-d') ?>" class="w-full mt-1 px-3 py-2.5 text-xs border rounded-xl bg-white dark:bg-slate-800">
      </div>
      <div>
        <label class="text-[10px] font-bold text-slate-500 uppercase">Keperluan</label>
        <input type="text" name="keperluan" maxlength="255" class="w-full mt-1 px-3 py-2.5 text-xs border rounded-xl bg-white dark:bg-slate-800">
      </div>
      <div>
        <label class="text-[10px] font-bold text-slate-500 uppercase">Agunan</label>
        <input type="text" name="agunan" maxlength="255" class="w-full mt-1 px-3 py-2.5 text-xs border rounded-xl bg-white dark:bg-slate-800">
      </div>
      <div class="flex justify-end gap-2 pt-2">
        <button type="button" onclick="closeModal('modal-pinjaman')" class="px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-600 dark:text-slate-300">Batal</button>
        <button type="submit" class="px-5 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow"><i class="fa-solid fa-floppy-disk mr-1"></i>Simpan</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>
