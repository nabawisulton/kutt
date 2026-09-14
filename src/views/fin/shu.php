<?php
/**
 * SHU: simulasi distribusi + simpan hasil. Variables: $tahun, $laba,
 * $shuTotal, $shuAnggota, $porsiAnggota, $distribusi, $saved, $canEdit.
 */

use App\Core\Csrf;

$tahun = (int) ($tahun ?? date('Y'));
$laba = (float) ($laba ?? 0);
$shuTotal = (float) ($shuTotal ?? 0);
$shuAnggota = (float) ($shuAnggota ?? 0);
$porsiAnggota = (float) ($porsiAnggota ?? 50);
$distribusi = $distribusi ?? [];
$saved = $saved ?? [];
$canEdit = (bool) ($canEdit ?? false);
?>

<div class="grid grid-cols-1 lg:grid-cols-4 gap-4 mb-5">
  <div class="glass-card rounded-2xl p-5 shadow-sm">
    <p class="text-[10px] font-bold text-slate-400 uppercase">Laba Tahun <?= $tahun ?></p>
    <h4 class="text-lg font-extrabold font-poppins text-slate-800 dark:text-slate-100 mt-1"><?= rupiah($laba) ?></h4>
    <p class="text-[10px] text-slate-400 mt-1">Pendapatan &minus; beban (jurnal)</p>
  </div>
  <div class="glass-card rounded-2xl p-5 shadow-sm">
    <p class="text-[10px] font-bold text-slate-400 uppercase">Total SHU</p>
    <h4 class="text-lg font-extrabold font-poppins text-brand-600 mt-1"><?= rupiah($shuTotal) ?></h4>
  </div>
  <div class="glass-card rounded-2xl p-5 shadow-sm">
    <p class="text-[10px] font-bold text-slate-400 uppercase">Bagian Anggota (<?= number_format($porsiAnggota, 0) ?>%)</p>
    <h4 class="text-lg font-extrabold font-poppins text-amber-500 mt-1"><?= rupiah($shuAnggota) ?></h4>
  </div>
  <div class="glass-card rounded-2xl p-5 shadow-sm">
    <form method="get" action="/shu" class="flex items-end gap-2">
      <div class="flex-1">
        <label class="text-[10px] font-bold text-slate-500 uppercase">Tahun Buku</label>
        <input type="number" name="tahun" value="<?= $tahun ?>" min="2000" max="<?= (int) date('Y') + 1 ?>" class="w-full mt-1 px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800">
      </div>
      <button class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold"><i class="fa-solid fa-arrow-right"></i></button>
    </form>
  </div>
</div>

<?php if ($saved !== []): ?>
  <div class="rounded-2xl border border-brand-200 bg-brand-50 px-5 py-3 text-xs text-brand-700 mb-5">
    <i class="fa-solid fa-circle-check mr-1"></i>Distribusi SHU <?= $tahun ?> telah <strong>disimpan</strong> (<?= count($saved) ?> anggota). Tabel di bawah adalah hasil tersimpan.
  </div>
<?php endif; ?>

<div class="glass-card rounded-2xl shadow-sm overflow-hidden">
  <div class="p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800">
    <h3 class="font-display text-sm font-bold text-slate-800 dark:text-slate-100"><i class="fa-solid fa-calculator mr-1 text-brand-600"></i><?= $saved !== [] ? 'Distribusi Tersimpan' : 'Simulasi Distribusi' ?> — Komponen: Jasa Modal 50% · Jasa Anggota 50%</h3>
    <div class="flex items-center gap-2">
      <a href="/shu/export/excel?tahun=<?= $tahun ?>" class="px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition"><i class="fa-solid fa-file-excel mr-1 text-emerald-600"></i>Excel</a>
      <button type="button" onclick="window.print()" class="px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition"><i class="fa-solid fa-print mr-1"></i>Cetak</button>
      <?php if ($canEdit && $saved === [] && $distribusi !== [] && $shuAnggota > 0): ?>
        <button type="button" onclick="saveShu()" class="px-4 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow"><i class="fa-solid fa-floppy-disk mr-1"></i>Simpan Distribusi</button>
      <?php endif; ?>
    </div>
  </div>

  <div class="overflow-x-auto">
    <table class="w-full text-xs">
      <thead>
        <tr class="text-left text-slate-500 dark:text-slate-400 border-b border-slate-100 dark:border-slate-800">
          <th class="px-5 py-3 font-semibold">No. Anggota</th>
          <th class="px-5 py-3 font-semibold">Nama</th>
          <th class="px-5 py-3 font-semibold text-right">Saldo Simpanan</th>
          <th class="px-5 py-3 font-semibold text-right">Jasa Modal</th>
          <th class="px-5 py-3 font-semibold text-right">Jasa Anggota</th>
          <th class="px-5 py-3 font-semibold text-right">Total SHU</th>
        </tr>
      </thead>
      <tbody>
        <?php $list = $saved !== [] ? array_map(static fn ($s) => [
            'member_no' => $s['member_no'], 'full_name' => $s['full_name'],
            'simpanan' => null, 'jasa_modal' => (float) $s['jasa_modal'],
            'jasa_anggota' => (float) $s['jasa_anggota'], 'total' => (float) $s['total_shu'],
        ], $saved) : $distribusi; ?>
        <?php foreach ($list as $d): ?>
          <tr class="border-b border-slate-50 dark:border-slate-800/60 hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
            <td class="px-5 py-3 font-mono text-[11px] text-slate-500"><?= e((string) $d['member_no']) ?></td>
            <td class="px-5 py-3 font-bold text-slate-800 dark:text-slate-100"><?= e((string) $d['full_name']) ?></td>
            <td class="px-5 py-3 text-right text-slate-500"><?= $d['simpanan'] !== null ? rupiah($d['simpanan']) : '-' ?></td>
            <td class="px-5 py-3 text-right"><?= rupiah($d['jasa_modal']) ?></td>
            <td class="px-5 py-3 text-right"><?= rupiah($d['jasa_anggota']) ?></td>
            <td class="px-5 py-3 text-right font-bold text-brand-600"><?= rupiah($d['total']) ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if ($list === []): ?>
          <tr><td colspan="6" class="px-5 py-10 text-center text-slate-400">Tidak ada anggota aktif untuk didistribusi.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if ($canEdit && $saved === [] && $distribusi !== [] && $shuAnggota > 0): ?>
<form method="post" action="/shu" id="shu-form" class="hidden">
  <?= Csrf::field() ?>
  <input type="hidden" name="tahun" value="<?= $tahun ?>">
  <input type="hidden" name="payload" id="shu-payload">
</form>
<script>
  function saveShu() {
    if (!confirmAction(null, 'Simpan hasil distribusi SHU tahun <?= $tahun ?>?')) return;
    var rows = [];
    document.querySelectorAll('table tbody tr').forEach(function () {}); // no-op guard
    var data = <?= json_encode($distribusi, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    document.getElementById('shu-payload').value = JSON.stringify(data);
    document.getElementById('shu-form').submit();
  }
</script>
<?php endif; ?>
