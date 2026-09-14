<?php

/**
 * Portal Anggota — dashboard pribadi. Variables: member, finance, myLoans,
 * schedule, unread, latestNews. SEMUA data berasal dari member milik akun ini.
 */

use App\Core\Csrf;
use App\Core\Roles;

$member   = $member ?? [];
$finance  = $finance ?? [];
$myLoans  = $myLoans ?? [];
$schedule = $schedule ?? [];
$unread   = (int) ($unread ?? 0);
$latestNews = $latestNews ?? [];
$canLoan  = Roles::can((string) ($_SESSION['auth_user']['role'] ?? ($currentUser['role'] ?? '')), 'portal.loan_request');

$statusColor = [
    'PENDING' => 'bg-amber-100 text-amber-700',
    'APPROVED' => 'bg-blue-100 text-blue-700',
    'REJECTED' => 'bg-red-100 text-red-700',
    'DISBURSED' => 'bg-emerald-100 text-emerald-700',
    'LUNAS' => 'bg-slate-100 text-slate-600',
];
?>
<div class="space-y-5">

  <!-- Kartu sambutan + saldo -->
  <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <div class="md:col-span-1 rounded-2xl p-5 bg-gradient-to-br from-brand-700 to-brand-600 text-white shadow-lg">
      <p class="text-[10px] uppercase tracking-widest opacity-80">Saldo Simpanan Saya</p>
      <p class="font-display font-extrabold text-2xl mt-1"><?= rupiah($finance['total_simpanan'] ?? 0) ?></p>
      <p class="text-[11px] opacity-85 mt-2"><?= e((string) $member['full_name']) ?> · <?= e((string) $member['member_no']) ?></p>
      <div class="mt-3 pt-3 border-t border-white/20 flex items-center justify-between text-[11px]">
        <span class="opacity-85">SHU saya</span>
        <span class="font-bold text-gold"><?= rupiah($finance['shu_total'] ?? 0) ?></span>
      </div>
    </div>

    <div class="glass-card rounded-2xl p-5 shadow-sm">
      <p class="text-[10px] font-bold text-slate-400 uppercase">Rincian Simpanan</p>
      <div class="mt-2 space-y-1.5 text-xs">
        <div class="flex justify-between"><span class="text-slate-500">Simpanan Pokok</span><span class="font-bold text-slate-700 dark:text-slate-200"><?= rupiah($finance['simpanan_pokok'] ?? 0) ?></span></div>
        <div class="flex justify-between"><span class="text-slate-500">Simpanan Wajib</span><span class="font-bold text-slate-700 dark:text-slate-200"><?= rupiah($finance['simpanan_wajib'] ?? 0) ?></span></div>
        <div class="flex justify-between"><span class="text-slate-500">Simpanan Sukarela</span><span class="font-bold text-slate-700 dark:text-slate-200"><?= rupiah($finance['simpanan_sukarela'] ?? 0) ?></span></div>
      </div>
    </div>

    <div class="glass-card rounded-2xl p-5 shadow-sm">
      <p class="text-[10px] font-bold text-slate-400 uppercase">Pinjaman Saya</p>
      <div class="mt-2 space-y-1.5 text-xs">
        <div class="flex justify-between"><span class="text-slate-500">Status</span><span class="font-bold text-slate-700 dark:text-slate-200"><?= e((string) ($finance['status_pinjaman'] ?? '-')) ?></span></div>
        <div class="flex justify-between"><span class="text-slate-500">Sisa Pinjaman</span><span class="font-bold text-slate-700 dark:text-slate-200"><?= rupiah($finance['sisa_pinjaman'] ?? 0) ?></span></div>
        <div class="flex justify-between"><span class="text-slate-500">Terbayar</span><span class="font-bold text-slate-700 dark:text-slate-200"><?= rupiah($finance['total_angsuran'] ?? 0) ?></span></div>
      </div>
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
    <!-- Pengajuan pinjaman -->
    <div class="glass-card rounded-2xl p-5 shadow-sm">
      <h3 class="font-display text-sm font-bold text-slate-800 dark:text-slate-100 mb-3"><i class="fa-solid fa-hand-holding-dollar mr-1 text-brand-600"></i>Ajukan Pinjaman</h3>
      <?php if ($canLoan): ?>
      <form method="post" action="/portal/loan-request" class="space-y-3" onsubmit="return confirmAction(this, 'Kirim pengajuan pinjaman ini ke admin?')">
        <?= Csrf::field() ?>
        <div>
          <label class="text-[10px] font-bold text-slate-500 uppercase">Plafon (Rp)</label>
          <input type="number" name="pokok_pinjaman" required min="100000" max="200000000" step="50000" placeholder="5000000"
            class="w-full mt-1 px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800">
        </div>
        <div class="grid grid-cols-2 gap-2">
          <div>
            <label class="text-[10px] font-bold text-slate-500 uppercase">Tenor (bulan)</label>
            <input type="number" name="tenor_bulan" required min="1" max="60" value="12"
              class="w-full mt-1 px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800">
          </div>
          <div>
            <label class="text-[10px] font-bold text-slate-500 uppercase">Bunga</label>
            <select name="sistem_bunga" class="w-full mt-1 px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800">
              <option value="FLAT">Flat</option>
              <option value="MENURUN">Menurun</option>
            </select>
          </div>
        </div>
        <div>
          <label class="text-[10px] font-bold text-slate-500 uppercase">Keperluan</label>
          <textarea name="keperluan" rows="2" required maxlength="255" placeholder="Modal sapi perah, renovasi kandang..."
            class="w-full mt-1 px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800"></textarea>
        </div>
        <button class="w-full px-4 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow"><i class="fa-solid fa-paper-plane mr-1"></i>Kirim Pengajuan</button>
        <p class="text-[10px] text-slate-400">Bunga 12%/tahun. Pengajuan diperiksa oleh bendahara/admin.</p>
      </form>
      <?php endif; ?>
    </div>

    <!-- Status pengajuan saya -->
    <div class="glass-card rounded-2xl p-5 shadow-sm lg:col-span-2">
      <h3 class="font-display text-sm font-bold text-slate-800 dark:text-slate-100 mb-3"><i class="fa-solid fa-list-check mr-1 text-brand-600"></i>Pengajuan &amp; Angsuran Saya</h3>
      <?php if ($myLoans === []): ?>
        <p class="text-xs text-slate-400 py-6 text-center">Belum ada pengajuan pinjaman.</p>
      <?php else: ?>
        <div class="overflow-x-auto">
          <table class="w-full text-xs text-left">
            <thead class="bg-slate-100 dark:bg-slate-800 text-slate-500 uppercase text-[10px]">
              <tr><th class="p-2 rounded-l-lg">No</th><th class="p-2">Plafon</th><th class="p-2">Tenor</th><th class="p-2">Keperluan</th><th class="p-2 rounded-r-lg">Status</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
              <?php foreach ($myLoans as $l): ?>
                <tr>
                  <td class="p-2 font-mono"><?= e((string) $l['loan_no']) ?></td>
                  <td class="p-2 font-bold"><?= rupiah($l['pokok_pinjaman']) ?></td>
                  <td class="p-2"><?= (int) $l['tenor_bulan'] ?> bln · <?= e((string) $l['sistem_bunga']) ?></td>
                  <td class="p-2 max-w-[160px] truncate" title="<?= e((string) $l['keperluan']) ?>"><?= e((string) $l['keperluan']) ?></td>
                  <td class="p-2"><span class="px-2 py-0.5 rounded-full text-[10px] font-bold <?= $statusColor[$l['status']] ?? 'bg-slate-100 text-slate-600' ?>"><?= e((string) $l['status']) ?></span></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <?php if ($schedule !== []): ?>
        <p class="text-[10px] font-bold text-slate-400 uppercase mt-4 mb-1.5">Jadwal Angsuran Terdekat</p>
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
          <?php foreach (array_slice($schedule, 0, 6) as $s): ?>
            <div class="p-2.5 rounded-xl border <?= ($s['status'] ?? '') === 'PAID' ? 'border-emerald-200 bg-emerald-50 dark:bg-emerald-900/20' : 'border-slate-200 dark:border-slate-700' ?> text-[11px]">
              <p class="font-bold text-slate-600 dark:text-slate-300">Angsuran <?= (int) $s['angsuran_ke'] ?> · <?= tanggal((string) $s['jatuh_tempo']) ?></p>
              <p class="text-slate-500"><?= rupiah($s['total_bayar'] ?? 0) ?> · <?= ($s['status'] ?? '') === 'PAID' ? 'LUNAS' : 'BELUM BAYAR' ?></p>
            </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- Berita terbaru + link chat -->
  <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <a href="/portal/chat" class="glass-card rounded-2xl p-5 shadow-sm hover:shadow-lg transition md:col-span-1 flex flex-col justify-between">
      <div>
        <h3 class="font-display text-sm font-bold text-slate-800 dark:text-slate-100"><i class="fa-solid fa-comments mr-1 text-brand-600"></i>Pesan ke Admin</h3>
        <p class="text-[11px] text-slate-500 mt-1">Tanya saldo, ajukan keluhan, atau konfirmasi transaksi langsung dengan pengurus.</p>
      </div>
      <span class="mt-3 inline-flex items-center gap-1.5 text-[11px] font-bold text-brand-600">
        Buka Percakapan
        <?php if ($unread > 0): ?><span class="px-2 py-0.5 rounded-full bg-red-500 text-white text-[10px]"><?= $unread ?> balasan baru</span><?php endif; ?>
      </span>
    </a>

    <div class="glass-card rounded-2xl p-5 shadow-sm md:col-span-2">
      <h3 class="font-display text-sm font-bold text-slate-800 dark:text-slate-100 mb-3"><i class="fa-solid fa-newspaper mr-1 text-brand-600"></i>Berita Terbaru</h3>
      <?php if ($latestNews === []): ?>
        <p class="text-xs text-slate-400">Belum ada berita.</p>
      <?php else: ?>
        <div class="space-y-2.5">
          <?php foreach ($latestNews as $n): ?>
            <a href="/berita/<?= e((string) $n['slug']) ?>" target="_blank" class="flex items-center gap-3 group">
              <div class="w-14 h-11 rounded-lg overflow-hidden bg-slate-100 shrink-0">
                <?php if (!empty($n['image_path'])): ?>
                  <img src="<?= e(news_image_src($n['image_path'])) ?>" class="w-full h-full object-cover" alt="">
                <?php else: ?>
                  <div class="w-full h-full flex items-center justify-center text-slate-300"><i class="fa-solid fa-image"></i></div>
                <?php endif; ?>
              </div>
              <div class="min-w-0">
                <p class="text-[11px] font-bold text-slate-700 dark:text-slate-200 truncate group-hover:text-brand-600 transition"><?= e((string) $n['title']) ?></p>
                <p class="text-[10px] text-slate-400"><?= e(tanggal((string) ($n['published_at'] ?? $n['created_at']))) ?></p>
              </div>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
