<?php
/**
 * Jurnal & buku besar. Variables: $rows, $ledger, $accounts, $from, $to,
 * $totalDebit, $totalKredit, $balanced, $canCreate.
 */

use App\Core\Csrf;

$rows = $rows ?? [];
$ledger = $ledger ?? [];
$accounts = $accounts ?? [];
$from = (string) ($from ?? date('Y-m-01'));
$to = (string) ($to ?? date('Y-m-d'));
$totalDebit = (float) ($totalDebit ?? 0);
$totalKredit = (float) ($totalKredit ?? 0);
$balanced = (bool) ($balanced ?? true);
$canCreate = (bool) ($canCreate ?? false);
?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-5">
  <div class="glass-card rounded-2xl p-5 shadow-sm">
    <p class="text-[10px] font-bold text-slate-400 uppercase">Total Debit</p>
    <h4 class="text-xl font-extrabold font-poppins text-slate-800 dark:text-slate-100 mt-1"><?= rupiah($totalDebit) ?></h4>
  </div>
  <div class="glass-card rounded-2xl p-5 shadow-sm">
    <p class="text-[10px] font-bold text-slate-400 uppercase">Total Kredit</p>
    <h4 class="text-xl font-extrabold font-poppins text-slate-800 dark:text-slate-100 mt-1"><?= rupiah($totalKredit) ?></h4>
  </div>
  <div class="glass-card rounded-2xl p-5 shadow-sm flex items-center justify-between">
    <div>
      <p class="text-[10px] font-bold text-slate-400 uppercase">Status Pembukuan</p>
      <h4 class="text-base font-extrabold font-poppins mt-1 <?= $balanced ? 'text-emerald-600' : 'text-red-500' ?>">
        <?= $balanced ? 'SEIMBANG' : 'TIDAK SEIMBANG' ?>
      </h4>
    </div>
    <i class="fa-solid <?= $balanced ? 'fa-scale-balanced text-emerald-500' : 'fa-triangle-exclamation text-red-400' ?> text-3xl"></i>
  </div>
</div>

<div class="glass-card rounded-2xl shadow-sm overflow-hidden mb-5">
  <div class="p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800">
    <form method="get" action="/akuntansi" class="flex flex-wrap items-center gap-2">
      <input type="date" name="from" value="<?= e($from) ?>" class="px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800">
      <span class="text-xs text-slate-400">s/d</span>
      <input type="date" name="to" value="<?= e($to) ?>" class="px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800">
      <button class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold"><i class="fa-solid fa-magnifying-glass"></i></button>
    </form>
    <div class="flex items-center gap-2">
      <a href="/akuntansi/export/excel?from=<?= e($from) ?>&to=<?= e($to) ?>" class="px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition"><i class="fa-solid fa-file-excel mr-1 text-emerald-600"></i>Excel</a>
      <button type="button" onclick="window.print()" class="px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition"><i class="fa-solid fa-print mr-1"></i>Cetak</button>
      <?php if ($canCreate): ?>
        <button type="button" onclick="openModal('modal-jurnal')" class="px-4 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow"><i class="fa-solid fa-plus mr-1"></i>Jurnal Manual</button>
      <?php endif; ?>
    </div>
  </div>

  <div class="overflow-x-auto">
    <table class="w-full text-xs">
      <thead>
        <tr class="text-left text-slate-500 dark:text-slate-400 border-b border-slate-100 dark:border-slate-800">
          <th class="px-5 py-3 font-semibold">Journal ID</th>
          <th class="px-5 py-3 font-semibold">Tanggal</th>
          <th class="px-5 py-3 font-semibold">Akun</th>
          <th class="px-5 py-3 font-semibold">Keterangan</th>
          <th class="px-5 py-3 font-semibold text-right">Debit</th>
          <th class="px-5 py-3 font-semibold text-right">Kredit</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr class="border-b border-slate-50 dark:border-slate-800/60 hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
            <td class="px-5 py-2.5 font-mono text-[11px] text-brand-600 font-bold"><?= e((string) $r['journal_id']) ?></td>
            <td class="px-5 py-2.5 text-slate-500 whitespace-nowrap"><?= e(tanggal((string) $r['tanggal'])) ?></td>
            <td class="px-5 py-2.5">
              <p class="font-bold text-slate-700 dark:text-slate-200"><?= e((string) ($r['account_name'] ?? $r['account_code'])) ?></p>
              <p class="text-[10px] text-slate-400 font-mono"><?= e((string) $r['account_code']) ?></p>
            </td>
            <td class="px-5 py-2.5 text-slate-500"><?= e((string) $r['keterangan']) ?></td>
            <td class="px-5 py-2.5 text-right font-bold"><?= (float) $r['debet'] > 0 ? rupiah($r['debet']) : '<span class="text-slate-300 dark:text-slate-600">-</span>' ?></td>
            <td class="px-5 py-2.5 text-right font-bold"><?= (float) $r['kredit'] > 0 ? rupiah($r['kredit']) : '<span class="text-slate-300 dark:text-slate-600">-</span>' ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if ($rows === []): ?>
          <tr><td colspan="6" class="px-5 py-10 text-center text-slate-400">Belum ada jurnal pada periode ini.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Buku besar -->
<div class="glass-card rounded-2xl shadow-sm overflow-hidden">
  <div class="p-5 border-b border-slate-100 dark:border-slate-800">
    <h3 class="font-display text-sm font-bold text-slate-800 dark:text-slate-100"><i class="fa-solid fa-book mr-1 text-brand-600"></i>Buku Besar (Rekap per Akun)</h3>
  </div>
  <div class="overflow-x-auto">
    <table class="w-full text-xs">
      <thead>
        <tr class="text-left text-slate-500 dark:text-slate-400 border-b border-slate-100 dark:border-slate-800">
          <th class="px-5 py-3 font-semibold">Kode</th>
          <th class="px-5 py-3 font-semibold">Nama Akun</th>
          <th class="px-5 py-3 font-semibold text-right">Mutasi Debit</th>
          <th class="px-5 py-3 font-semibold text-right">Mutasi Kredit</th>
          <th class="px-5 py-3 font-semibold text-right">Saldo Normal</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($ledger as $code => $l): ?>
          <?php $net = (float) $l['debet'] - (float) $l['kredit']; ?>
          <tr class="border-b border-slate-50 dark:border-slate-800/60">
            <td class="px-5 py-2.5 font-mono text-[11px] text-slate-500"><?= e((string) $code) ?></td>
            <td class="px-5 py-2.5 font-bold text-slate-700 dark:text-slate-200"><?= e((string) $l['name']) ?></td>
            <td class="px-5 py-2.5 text-right"><?= rupiah($l['debet']) ?></td>
            <td class="px-5 py-2.5 text-right"><?= rupiah($l['kredit']) ?></td>
            <td class="px-5 py-2.5 text-right font-bold text-brand-600"><?= rupiah(abs($net)) ?> <?= $net >= 0 ? 'D' : 'K' ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if ($ledger === []): ?>
          <tr><td colspan="5" class="px-5 py-10 text-center text-slate-400">Belum ada mutasi.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if ($canCreate): ?>
<div id="modal-jurnal" class="hidden fixed inset-0 z-[70] bg-slate-950/60 backdrop-blur-sm flex items-center justify-center p-4">
  <div class="glass-card rounded-2xl shadow-2xl w-full max-w-lg p-5 max-h-[90vh] overflow-y-auto custom-scrollbar">
    <h3 class="font-display text-sm font-bold text-slate-800 dark:text-slate-100 mb-4"><i class="fa-solid fa-book-journal-whills mr-1 text-brand-600"></i>Jurnal Manual</h3>
    <form method="post" action="/akuntansi" class="space-y-3" id="journal-form">
      <?= Csrf::field() ?>
      <div class="grid grid-cols-2 gap-2">
        <div>
          <label class="text-[10px] font-bold text-slate-500 uppercase">Tanggal *</label>
          <input type="date" name="tanggal" required value="<?= date('Y-m-d') ?>" class="w-full mt-1 px-3 py-2.5 text-xs border rounded-xl bg-white dark:bg-slate-800">
        </div>
        <div>
          <label class="text-[10px] font-bold text-slate-500 uppercase">Keterangan</label>
          <input type="text" name="keterangan" maxlength="255" class="w-full mt-1 px-3 py-2.5 text-xs border rounded-xl bg-white dark:bg-slate-800">
        </div>
      </div>
      <div class="space-y-2" id="journal-lines">
        <div class="flex gap-2 items-center journal-line">
          <select name="lines[0][account]" required class="flex-1 px-3 py-2.5 text-xs border rounded-xl bg-white dark:bg-slate-800">
            <?php foreach ($accounts as $a): ?>
              <option value="<?= e((string) $a['account_code']) ?>"><?= e($a['account_code'] . ' - ' . $a['account_name']) ?></option>
            <?php endforeach; ?>
          </select>
          <input type="number" name="lines[0][debet]" placeholder="Debit" min="0" step="1000" oninput="syncLine(this)" class="w-28 px-3 py-2.5 text-xs border rounded-xl bg-white dark:bg-slate-800">
          <input type="number" name="lines[0][kredit]" placeholder="Kredit" min="0" step="1000" oninput="syncLine(this)" class="w-28 px-3 py-2.5 text-xs border rounded-xl bg-white dark:bg-slate-800">
        </div>
      </div>
      <button type="button" onclick="addJournalLine()" class="text-xs font-bold text-brand-600 hover:underline"><i class="fa-solid fa-plus mr-1"></i>Tambah Baris</button>
      <p class="text-[10px] text-slate-400">Total debit harus sama dengan total kredit (jurnal ganda).</p>
      <div class="flex justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
        <button type="button" onclick="closeModal('modal-jurnal')" class="px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-600 dark:text-slate-300">Batal</button>
        <button type="submit" class="px-5 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow"><i class="fa-solid fa-floppy-disk mr-1"></i>Simpan</button>
      </div>
    </form>
  </div>
</div>
<script>
  var journalLineCount = 1;
  var journalAccounts = <?= json_encode($accounts, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

  function addJournalLine() {
    var wrap = document.getElementById('journal-lines');
    var div = document.createElement('div');
    div.className = 'flex gap-2 items-center journal-line';
    var options = journalAccounts.map(function (a) {
      return '<option value="' + a.account_code + '">' + a.account_code + ' - ' + a.account_name + '</option>';
    }).join('');
    div.innerHTML = '<select name="lines[' + journalLineCount + '][account]" required class="flex-1 px-3 py-2.5 text-xs border rounded-xl bg-white dark:bg-slate-800">' + options + '</select>'
      + '<input type="number" name="lines[' + journalLineCount + '][debet]" placeholder="Debit" min="0" step="1000" oninput="syncLine(this)" class="w-28 px-3 py-2.5 text-xs border rounded-xl bg-white dark:bg-slate-800">'
      + '<input type="number" name="lines[' + journalLineCount + '][kredit]" placeholder="Kredit" min="0" step="1000" oninput="syncLine(this)" class="w-28 px-3 py-2.5 text-xs border rounded-xl bg-white dark:bg-slate-800">';
    wrap.appendChild(div);
    journalLineCount++;
  }

  function syncLine(input) {
    var row = input.closest('.journal-line');
    var inputs = row.querySelectorAll('input[type=number]');
    if (input.name.indexOf('debet') !== -1 && parseFloat(input.value) > 0) {
      inputs[1].value = '';
    } else if (input.name.indexOf('kredit') !== -1 && parseFloat(input.value) > 0) {
      inputs[0].value = '';
    }
  }
</script>
<?php endif; ?>
