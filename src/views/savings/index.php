<?php
/**
 * Tabungan Uang — dashboard, buku besar, dan form setor/tarik/penyesuaian.
 * Variables: $accounts, $totalBalance, $summary, $ledger, $from, $to,
 *            $accountFilter, $canManage.
 */

use App\Core\Csrf;

$accounts = array_map(static fn ($a) => (array) $a, (array) ($accounts ?? []));
$ledger = array_map(static fn ($t) => (array) $t, (array) ($ledger ?? []));
$summary = (array) ($summary ?? ['in' => 0, 'out' => 0]);
$totalBalance = (float) ($totalBalance ?? 0);
$from = (string) ($from ?? date('Y-m-01'));
$to = (string) ($to ?? date('Y-m-d'));
$accountFilter = (int) ($accountFilter ?? 0);
$canManage = (bool) ($canManage ?? false);

$typeBadge = static function (string $type): string {
    return match ($type) {
        'SETOR'       => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300',
        'REFUND'      => 'bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300',
        'TARIK'       => 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300',
        'PEMBAYARAN'  => 'bg-rose-100 text-rose-700 dark:bg-rose-500/15 dark:text-rose-300',
        'PENYESUAIAN' => 'bg-slate-200 text-slate-700 dark:bg-slate-500/15 dark:text-slate-300',
        default       => 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300',
    };
};
?>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-5">
  <div class="glass-card rounded-2xl p-5 shadow-sm">
    <p class="text-[10px] font-bold text-slate-400 uppercase">Total Saldo Tabungan</p>
    <h4 class="text-xl font-extrabold font-poppins text-brand-600 mt-1"><?= rupiah($totalBalance) ?></h4>
    <p class="text-[10px] text-slate-400"><?= count($accounts) ?> akun tabungan</p>
  </div>
  <div class="glass-card rounded-2xl p-5 shadow-sm">
    <p class="text-[10px] font-bold text-slate-400 uppercase">Uang Masuk (periode)</p>
    <h4 class="text-xl font-extrabold font-poppins text-emerald-600 mt-1"><?= rupiah((float) ($summary['in'] ?? 0)) ?></h4>
    <p class="text-[10px] text-slate-400">setoran + refund</p>
  </div>
  <div class="glass-card rounded-2xl p-5 shadow-sm">
    <p class="text-[10px] font-bold text-slate-400 uppercase">Uang Keluar (periode)</p>
    <h4 class="text-xl font-extrabold font-poppins text-rose-500 mt-1"><?= rupiah((float) ($summary['out'] ?? 0)) ?></h4>
    <p class="text-[10px] text-slate-400">penarikan + pembayaran</p>
  </div>
  <div class="glass-card rounded-2xl p-5 shadow-sm">
    <p class="text-[10px] font-bold text-slate-400 uppercase">Arus Bersih</p>
    <h4 class="text-xl font-extrabold font-poppins text-slate-800 dark:text-slate-100 mt-1"><?= rupiah((float) ($summary['in'] ?? 0) - (float) ($summary['out'] ?? 0)) ?></h4>
    <p class="text-[10px] text-slate-400">masuk &minus; keluar</p>
  </div>
</div>

<?php if ($canManage): ?>
<div class="grid lg:grid-cols-2 gap-4 mb-5">
  <div class="glass-card rounded-2xl p-5 shadow-sm">
    <h4 class="font-bold text-slate-800 dark:text-slate-100 text-sm mb-3"><i class="fa-solid fa-arrow-down-a-z text-emerald-500 mr-1"></i>Setor / Tarik Tabungan</h4>
    <form method="post" action="/tabungan/move" class="grid grid-cols-2 gap-3">
      <?= Csrf::field() ?>
      <div class="col-span-2">
        <label class="text-[10px] font-bold text-slate-400 uppercase block mb-1">Akun Tabungan</label>
        <select name="account_id" required class="w-full px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800">
          <?php foreach ($accounts as $a): ?>
            <?php if ((int) $a['is_active'] !== 1) { continue; } ?>
            <option value="<?= (int) $a['id'] ?>"><?= e($a['account_no']) ?> — <?= e($a['name']) ?> (<?= rupiah((float) $a['balance']) ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="text-[10px] font-bold text-slate-400 uppercase block mb-1">Jenis</label>
        <select name="type" class="w-full px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800">
          <option value="SETOR">Setor (uang masuk)</option>
          <option value="TARIK">Tarik (uang keluar)</option>
        </select>
      </div>
      <div>
        <label class="text-[10px] font-bold text-slate-400 uppercase block mb-1">Metode</label>
        <select name="method" class="w-full px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800">
          <option value="KAS">Kas</option>
          <option value="TRANSFER">Transfer</option>
          <option value="LAINNYA">Lainnya</option>
        </select>
      </div>
      <div>
        <label class="text-[10px] font-bold text-slate-400 uppercase block mb-1">Nominal (Rp)</label>
        <input type="number" name="amount" min="1" step="1" required class="w-full px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800" placeholder="0">
      </div>
      <div>
        <label class="text-[10px] font-bold text-slate-400 uppercase block mb-1">Keterangan</label>
        <input type="text" name="description" maxlength="255" class="w-full px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800" placeholder="opsional">
      </div>
      <div class="col-span-2">
        <button class="w-full py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold"><i class="fa-solid fa-piggy-bank mr-1"></i>Simpan Transaksi</button>
      </div>
    </form>
  </div>
  <div class="glass-card rounded-2xl p-5 shadow-sm">
    <h4 class="font-bold text-slate-800 dark:text-slate-100 text-sm mb-3"><i class="fa-solid fa-scale-balanced text-amber-500 mr-1"></i>Penyesuaian Saldo (koreksi)</h4>
    <form method="post" action="/tabungan/adjust" class="grid grid-cols-2 gap-3">
      <?= Csrf::field() ?>
      <div class="col-span-2">
        <label class="text-[10px] font-bold text-slate-400 uppercase block mb-1">Akun Tabungan</label>
        <select name="account_id" required class="w-full px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800">
          <?php foreach ($accounts as $a): ?>
            <?php if ((int) $a['is_active'] !== 1) { continue; } ?>
            <option value="<?= (int) $a['id'] ?>"><?= e($a['account_no']) ?> — <?= e($a['name']) ?> (saldo: <?= rupiah((float) $a['balance']) ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="text-[10px] font-bold text-slate-400 uppercase block mb-1">Penyesuaian (Rp, boleh &minus;)</label>
        <input type="number" name="delta" step="1" required class="w-full px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800" placeholder="+50000 / -25000">
      </div>
      <div>
        <label class="text-[10px] font-bold text-slate-400 uppercase block mb-1">Alasan <span class="text-rose-500">*</span></label>
        <input type="text" name="description" maxlength="255" required class="w-full px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800" placeholder="wajib diisi">
      </div>
      <div class="col-span-2">
        <button class="w-full py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold"><i class="fa-solid fa-check mr-1"></i>Simpan Penyesuaian</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<div class="glass-card rounded-2xl shadow-sm p-4 mb-5">
  <form method="get" action="/tabungan" class="flex flex-wrap items-end gap-2">
    <div>
      <label class="text-[10px] font-bold text-slate-400 uppercase block mb-1">Dari</label>
      <input type="date" name="from" value="<?= e($from) ?>" class="px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800">
    </div>
    <div>
      <label class="text-[10px] font-bold text-slate-400 uppercase block mb-1">Sampai</label>
      <input type="date" name="to" value="<?= e($to) ?>" class="px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800">
    </div>
    <div>
      <label class="text-[10px] font-bold text-slate-400 uppercase block mb-1">Akun</label>
      <select name="account" class="px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800 min-w-[180px]">
        <option value="0">Semua Akun</option>
        <?php foreach ($accounts as $a): ?>
          <option value="<?= (int) $a['id'] ?>" <?= $accountFilter === (int) $a['id'] ? 'selected' : '' ?>><?= e($a['account_no']) ?> — <?= e($a['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <button class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold"><i class="fa-solid fa-filter mr-1"></i>Terapkan</button>
    <a href="/tabungan/export?from=<?= e($from) ?>&to=<?= e($to) ?>" class="px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition"><i class="fa-solid fa-file-excel mr-1 text-emerald-600"></i>Export Excel</a>
  </form>
</div>

<div class="glass-card rounded-2xl shadow-sm overflow-hidden">
  <div class="overflow-x-auto">
    <table class="w-full text-xs">
      <thead>
        <tr class="text-left text-[10px] uppercase text-slate-400 border-b border-slate-100 dark:border-slate-800">
          <th class="px-4 py-3">Waktu</th>
          <th class="px-4 py-3">No. Transaksi</th>
          <th class="px-4 py-3">Akun</th>
          <th class="px-4 py-3">Jenis</th>
          <th class="px-4 py-3">Keterangan</th>
          <th class="px-4 py-3 text-right">Masuk</th>
          <th class="px-4 py-3 text-right">Keluar</th>
          <th class="px-4 py-3 text-right">Saldo</th>
          <th class="px-4 py-3">Oleh</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-50 dark:divide-slate-800/60">
        <?php if ($ledger === []): ?>
          <tr><td colspan="9" class="px-4 py-10 text-center text-slate-400">Belum ada transaksi tabungan.</td></tr>
        <?php else: foreach ($ledger as $t): ?>
          <?php
            $inflow = in_array((string) $t['type'], ['SETOR', 'REFUND'], true);
          ?>
          <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40">
            <td class="px-4 py-3 text-slate-500 whitespace-nowrap"><?= e(date('d/m/Y H:i', strtotime((string) $t['created_at']))) ?></td>
            <td class="px-4 py-3 font-mono text-[11px]"><?= e((string) $t['transaction_no']) ?></td>
            <td class="px-4 py-3"><?= e((string) $t['account_name']) ?></td>
            <td class="px-4 py-3"><span class="px-2 py-0.5 rounded-full font-bold text-[10px] <?= $typeBadge((string) $t['type']) ?>"><?= e((string) $t['type']) ?></span></td>
            <td class="px-4 py-3 text-slate-500 max-w-[240px] truncate" title="<?= e((string) ($t['description'] ?? '')) ?>"><?= e((string) ($t['description'] ?? '-')) ?></td>
            <td class="px-4 py-3 text-right font-bold text-emerald-600"><?= $inflow ? rupiah((float) $t['amount']) : '-' ?></td>
            <td class="px-4 py-3 text-right font-bold text-rose-500"><?= !$inflow ? rupiah((float) $t['amount']) : '-' ?></td>
            <td class="px-4 py-3 text-right font-bold text-slate-700 dark:text-slate-200"><?= rupiah((float) $t['balance_after']) ?></td>
            <td class="px-4 py-3 text-slate-500"><?= e((string) ($t['created_by_name'] ?? '-')) ?></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
