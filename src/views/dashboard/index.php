<?php
/**
 * Executive Dashboard - KUTT SUKA MAKMUR (legacy design preserved).
 * Variables: metrics, trend, portfolio, recent, notifications, unreadCount,
 *            role, can (map of granular permissions for the current user).
 */

use App\Core\Roles;

$metrics = $metrics ?? [];
$trend = $trend ?? ['labels' => [], 'simpanan' => [], 'pinjaman' => []];
$portfolio = $portfolio ?? ['labels' => [], 'values' => []];
$recent = $recent ?? [];
$notifications = $notifications ?? [];
$role = (string) ($role ?? 'STAFF');
$can = static fn (string $p): bool => Roles::can($role, $p);

// Aksi cepat sesuai permission user (bukan hardcoded per role).
$quickActions = [];
if ($can('finance.create')) {
    $quickActions[] = ['/simpanan', 'fa-solid fa-piggy-bank', 'Catat Simpanan', 'bg-emerald-100 text-emerald-600'];
    $quickActions[] = ['/pinjaman', 'fa-solid fa-hand-holding-dollar', 'Pengajuan Pinjaman', 'bg-amber-100 text-amber-600'];
    $quickActions[] = ['/kas', 'fa-solid fa-money-bill-transfer', 'Transaksi Kas', 'bg-blue-100 text-blue-600'];
}
if ($can('member.create')) {
    $quickActions[] = ['/members/create', 'fa-solid fa-user-plus', 'Anggota Baru', 'bg-purple-100 text-purple-600'];
}
if ($can('news.create')) {
    $quickActions[] = ['/news/create', 'fa-solid fa-newspaper', 'Tulis Berita', 'bg-rose-100 text-rose-600'];
}
if ($can('settings.manage')) {
    $quickActions[] = ['/settings', 'fa-solid fa-gears', 'Pengaturan', 'bg-slate-200 text-slate-600'];
}
?>

<!-- Quick actions (role-aware) -->
<?php if ($quickActions !== []): ?>
<div class="flex flex-wrap items-center gap-2">
  <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mr-1"><i class="fa-solid fa-bolt mr-1"></i>Aksi Cepat:</span>
  <?php foreach ($quickActions as [$href, $icon, $label, $color]): ?>
    <a href="<?= e($href) ?>" class="glass-card rounded-xl px-3.5 py-2 text-xs font-bold text-slate-700 dark:text-slate-200 hover:shadow-md transition flex items-center gap-2">
      <span class="w-6 h-6 rounded-lg <?= $color ?> flex items-center justify-center text-[11px]"><i class="<?= e($icon) ?>"></i></span><?= e($label) ?>
    </a>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Stat cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
  <div class="glass-card rounded-2xl p-4 flex flex-col justify-between shadow-sm">
    <div class="flex items-center justify-between">
      <span class="text-xs font-semibold text-slate-500">Total Anggota Aktif</span>
      <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center"><i class="fa-solid fa-users"></i></div>
    </div>
    <div class="mt-3">
      <h3 class="font-display text-2xl font-bold text-slate-800 dark:text-slate-100"><?= number_format((float) ($metrics['totalMembers'] ?? 0), 0, ',', '.') ?></h3>
      <p class="text-[11px] text-emerald-600 font-semibold mt-1 flex items-center space-x-1">
        <i class="fa-solid fa-users"></i><span>Keanggotaan aktif</span>
      </p>
    </div>
  </div>
  <div class="glass-card rounded-2xl p-4 flex flex-col justify-between shadow-sm">
    <div class="flex items-center justify-between">
      <span class="text-xs font-semibold text-slate-500">Total Saldo Simpanan</span>
      <div class="w-8 h-8 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center"><i class="fa-solid fa-vault"></i></div>
    </div>
    <div class="mt-3">
      <h3 class="font-display text-xl font-bold text-slate-800 dark:text-slate-100"><?= rupiah($metrics['totalSimpanan'] ?? 0) ?></h3>
      <p class="text-[11px] text-emerald-600 font-semibold mt-1 flex items-center space-x-1">
        <i class="fa-solid fa-arrow-trend-up"></i><span>Pokok, wajib & sukarela</span>
      </p>
    </div>
  </div>
  <div class="glass-card rounded-2xl p-4 flex flex-col justify-between shadow-sm">
    <div class="flex items-center justify-between">
      <span class="text-xs font-semibold text-slate-500">Outstanding Pinjaman</span>
      <div class="w-8 h-8 rounded-lg bg-amber-100 text-amber-600 flex items-center justify-center"><i class="fa-solid fa-hand-holding-dollar"></i></div>
    </div>
    <div class="mt-3">
      <h3 class="font-display text-xl font-bold text-slate-800 dark:text-slate-100"><?= rupiah($metrics['outstandingPinjaman'] ?? 0) ?></h3>
      <p class="text-[11px] text-amber-600 font-semibold mt-1 flex items-center space-x-1">
        <i class="fa-solid fa-clock-rotate-left"></i><span><?= (int) ($metrics['pendingLoans'] ?? 0) ?> Pengajuan Pending · <?= rupiah($metrics['activePinjaman'] ?? 0) ?> aktif</span>
      </p>
    </div>
  </div>
  <div class="glass-card rounded-2xl p-4 flex flex-col justify-between shadow-sm">
    <div class="flex items-center justify-between">
      <span class="text-xs font-semibold text-slate-500">Saldo Kas & Bank</span>
      <div class="w-8 h-8 rounded-lg bg-purple-100 text-purple-600 flex items-center justify-center"><i class="fa-solid fa-building-columns"></i></div>
    </div>
    <div class="mt-3">
      <h3 class="font-display text-xl font-bold text-slate-800 dark:text-slate-100"><?= rupiah($metrics['saldoKas'] ?? 0) ?></h3>
      <p class="text-[11px] text-emerald-600 font-semibold mt-1 flex items-center space-x-1">
        <i class="fa-solid fa-shield-halved"></i><span>Pendapatan: <?= rupiah($metrics['totalRevenue'] ?? 0) ?></span>
      </p>
    </div>
  </div>
</div>

<!-- Charts -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
  <div class="lg:col-span-2 glass-card rounded-2xl p-5 shadow-sm">
    <div class="flex items-center justify-between mb-4">
      <div>
        <h3 class="font-display text-sm font-bold text-slate-800 dark:text-slate-100">Tren Financial Simpanan vs Pinjaman</h3>
        <p class="text-xs text-slate-500">Akumulasi tren 6 bulan terakhir</p>
      </div>
      <span class="text-[11px] bg-brand-50 text-brand-600 px-2.5 py-1 rounded-lg font-semibold"><?= date('Y') ?></span>
    </div>
    <div class="h-64 relative"><canvas id="chart-financial-trends"></canvas></div>
  </div>
  <div class="glass-card rounded-2xl p-5 shadow-sm">
    <div class="flex items-center justify-between mb-4">
      <h3 class="font-display text-sm font-bold text-slate-800 dark:text-slate-100">Portofolio Simpanan</h3>
      <i class="fa-solid fa-chart-pie text-slate-400 text-xs"></i>
    </div>
    <div class="h-64 relative flex items-center justify-center"><canvas id="chart-portfolio"></canvas></div>
  </div>
</div>

<!-- Recent transactions + notifications -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
  <div class="lg:col-span-2 glass-card rounded-2xl p-5 shadow-sm">
    <h3 class="font-display text-sm font-bold text-slate-800 dark:text-slate-100 mb-3">Transaksi Operasional Terakhir</h3>
    <div class="overflow-x-auto">
      <table class="w-full text-xs text-left">
        <thead class="bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-semibold uppercase text-[10px]">
          <tr>
            <th class="p-2.5 rounded-l-lg">ID Ref</th>
            <th class="p-2.5">Keterangan</th>
            <th class="p-2.5">Tipe</th>
            <th class="p-2.5">Nominal</th>
            <th class="p-2.5 rounded-r-lg text-center">Status</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
          <?php if ($recent === []): ?>
            <tr>
              <td colspan="5" class="p-4 text-center text-slate-400">Belum ada transaksi tercatat</td>
            </tr>
          <?php else: ?>
            <?php foreach ($recent as $trx): ?>
              <tr class="hover:bg-slate-50 dark:hover:bg-slate-800">
                <td class="p-2.5 font-mono font-semibold text-brand-600"><?= e($trx['id_ref']) ?></td>
                <td class="p-2.5 max-w-[220px] truncate" title="<?= e($trx['anggota']) ?>"><?= e($trx['anggota']) ?></td>
                <td class="p-2.5"><span class="px-2 py-0.5 rounded bg-slate-50 dark:bg-slate-700 text-slate-600 dark:text-slate-300 font-medium"><?= e($trx['tipe']) ?></span></td>
                <td class="p-2.5 font-semibold"><?= rupiah($trx['nominal']) ?></td>
                <td class="p-2.5 text-center"><span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 text-[10px] font-bold"><?= e($trx['status']) ?></span></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="glass-card rounded-2xl p-5 shadow-sm space-y-3">
    <div class="flex items-center justify-between">
      <h3 class="font-display text-sm font-bold text-slate-800 dark:text-slate-100">Notifikasi</h3>
      <span class="text-[10px] px-2 py-0.5 rounded-full bg-brand-50 text-brand-600 font-bold"><?= (int) ($unreadCount ?? 0) ?> belum dibaca</span>
    </div>
    <div class="space-y-2 max-h-64 overflow-y-auto custom-scrollbar">
      <?php if ($notifications === []): ?>
        <p class="text-xs text-slate-400">Tidak ada notifikasi.</p>
      <?php else: ?>
        <?php foreach ($notifications as $notif): ?>
          <div class="p-3 rounded-xl border <?= ((int) $notif['is_read'] === 1) ? 'border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800' : 'border-brand-200 bg-brand-50/60 dark:bg-slate-800' ?>">
            <div class="flex items-start justify-between gap-2">
              <p class="text-xs font-bold text-slate-700 dark:text-slate-200"><?= e($notif['title']) ?></p>
              <?php if ((int) $notif['is_read'] === 0): ?>
                <span class="w-2 h-2 mt-1 rounded-full bg-brand-500 shrink-0"></span>
              <?php endif; ?>
            </div>
            <p class="text-[11px] text-slate-500 mt-1"><?= e($notif['message']) ?></p>
            <p class="text-[10px] text-slate-400 mt-1"><?= tanggal((string) $notif['created_at'], true) ?></p>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    // Financial trend chart
    var trendCtx = document.getElementById('chart-financial-trends')?.getContext('2d');
    if (trendCtx && typeof Chart !== 'undefined') {
      new Chart(trendCtx, {
        type: 'line',
        data: {
          labels: <?= json_encode($trend['labels'] ?? []) ?>,
          datasets: [
            { label: 'Simpanan (Rp)', data: <?= json_encode(array_map('floatval', $trend['simpanan'] ?? [])) ?>, borderColor: '#0b7a3e', backgroundColor: 'rgba(11, 122, 62, 0.1)', fill: true, tension: 0.4 },
            { label: 'Pinjaman (Rp)', data: <?= json_encode(array_map('floatval', $trend['pinjaman'] ?? [])) ?>, borderColor: '#ffc107', backgroundColor: 'transparent', tension: 0.4 }
          ]
        },
        options: { responsive: true, maintainAspectRatio: false }
      });
    }

    // Portfolio doughnut chart
    var portCtx = document.getElementById('chart-portfolio')?.getContext('2d');
    if (portCtx && typeof Chart !== 'undefined') {
      new Chart(portCtx, {
        type: 'doughnut',
        data: {
          labels: <?= json_encode($portfolio['labels'] ?? []) ?>,
          datasets: [{ data: <?= json_encode(array_map('floatval', $portfolio['values'] ?? [])) ?>, backgroundColor: ['#0b7a3e', '#2fae60', '#ffc107'] }]
        },
        options: { responsive: true, maintainAspectRatio: false }
      });
    }
  });
</script>
