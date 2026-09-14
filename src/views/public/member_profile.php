<?php

/**
 * Halaman verifikasi publik setelah scan QR kartu anggota.
 * Variables: member, finance (null saat tanpa permission), transactions,
 *            canSeeFinance, cardBg. SEO vars sudah dipakai layout publik.
 */
$member   = $member ?? [];
$finance  = $finance ?? null;
$transactions = $transactions ?? [];
$canSeeFinance = (bool) ($canSeeFinance ?? false);

$baseUrl = base_url();
?>
<main class="min-h-screen bg-[#F7F9F8] py-10 px-4">
  <div class="max-w-3xl mx-auto space-y-5">

    <div class="glass-card rounded-2xl p-6 shadow-sm">
      <div class="flex items-start justify-between gap-4">
        <div class="flex items-center gap-4">
          <?php if (!empty($member['photo_path'])): ?>
            <img src="<?= e($baseUrl . '/' . $member['photo_path']) ?>" alt="Foto anggota"
              class="w-[88px] h-[132px] object-cover rounded-xl border-4 border-white shadow-lg">
          <?php else: ?>
            <div class="w-[88px] h-[132px] rounded-xl border-4 border-white shadow-lg bg-brand-50 flex flex-col items-center justify-center text-brand-600">
              <i class="fa-solid fa-user text-2xl"></i>
              <span class="text-[9px] mt-1">Foto 4x6</span>
            </div>
          <?php endif; ?>
          <div>
            <p class="text-[10px] font-bold uppercase tracking-wider text-brand-600">Kartu Anggota Terverifikasi</p>
            <h1 class="font-display text-xl font-bold text-slate-800"><?= e((string) $member['full_name']) ?></h1>
            <p class="text-xs text-slate-500 font-mono mt-0.5"><?= e((string) $member['member_no']) ?></p>
            <span class="inline-block mt-2 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700"><?= e((string) $member['status']) ?></span>
          </div>
        </div>
        <div class="text-right">
          <div class="w-10 h-10 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center ml-auto"><i class="fa-solid fa-check"></i></div>
          <p class="text-[9px] text-slate-400 mt-1"><?= e((string) ($cardBg['type'] ?? '')) ?></p>
        </div>
      </div>

      <div class="grid grid-cols-2 md:grid-cols-3 gap-3 mt-5 text-xs">
        <div><p class="text-[10px] uppercase text-slate-400 font-bold">NIK</p><p class="font-mono text-slate-700"><?= e($member['nik'] ?? '-') ?></p></div>
        <div><p class="text-[10px] uppercase text-slate-400 font-bold">Alamat</p><p class="text-slate-700"><?= e($member['address'] ?? '-') ?></p></div>
        <div><p class="text-[10px] uppercase text-slate-400 font-bold">Kelompok Tani</p><p class="text-slate-700"><?= e($member['group_name'] ?? '-') ?></p></div>
        <div><p class="text-[10px] uppercase text-slate-400 font-bold">Pekerjaan</p><p class="text-slate-700"><?= e($member['occupation'] ?? '-') ?></p></div>
        <div><p class="text-[10px] uppercase text-slate-400 font-bold">Jabatan di Koperasi</p><p class="text-slate-700"><?= e(trim((string) ($member['jabatan_internal'] ?? '')) !== '' ? $member['jabatan_internal'] : 'Anggota') ?></p></div>
        <div><p class="text-[10px] uppercase text-slate-400 font-bold">Hubungan Eksternal</p><p class="text-slate-700"><?php
            $jx = trim((string) ($member['jabatan_eksternal'] ?? ''));
            if ($jx !== '') {
                echo e($jx);
            } elseif (($member['relasi_eksternal'] ?? 'TIDAK_ADA') !== 'TIDAK_ADA') {
                echo e((string) $member['relasi_eksternal']);
            } else {
                echo '-';
            }
        ?></p></div>
        <div><p class="text-[10px] uppercase text-slate-400 font-bold">Bergabung</p><p class="text-slate-700"><?= e(tanggal((string) ($member['joined_at'] ?? ''))) ?></p></div>
        <div><p class="text-[10px] uppercase text-slate-400 font-bold">NIA</p><p class="font-mono text-slate-700"><?= e($member['nia'] ?? '-') ?></p></div>
      </div>
    </div>

    <?php if ($canSeeFinance && $finance !== null): ?>
      <div class="glass-card rounded-2xl p-6 shadow-sm">
        <h2 class="font-display text-sm font-bold text-slate-800 mb-4"><i class="fa-solid fa-wallet mr-1 text-brand-600"></i>Keuangan Anggota <span class="text-[10px] font-normal text-slate-400">(akses penuh)</span></h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-xs">
          <div class="bg-brand-50 rounded-xl p-3"><p class="text-[10px] text-slate-500 font-bold uppercase">Simpanan Pokok</p><p class="font-bold text-brand-600 mt-1"><?= rupiah($finance['simpanan_pokok'] ?? 0) ?></p></div>
          <div class="bg-brand-50 rounded-xl p-3"><p class="text-[10px] text-slate-500 font-bold uppercase">Simpanan Wajib</p><p class="font-bold text-brand-600 mt-1"><?= rupiah($finance['simpanan_wajib'] ?? 0) ?></p></div>
          <div class="bg-brand-50 rounded-xl p-3"><p class="text-[10px] text-slate-500 font-bold uppercase">Simpanan Sukarela</p><p class="font-bold text-brand-600 mt-1"><?= rupiah($finance['simpanan_sukarela'] ?? 0) ?></p></div>
          <div class="bg-brand-600 text-white rounded-xl p-3"><p class="text-[10px] font-bold uppercase opacity-80">Total Simpanan</p><p class="font-bold mt-1"><?= rupiah($finance['total_simpanan'] ?? 0) ?></p></div>
          <div class="bg-slate-100 rounded-xl p-3"><p class="text-[10px] text-slate-500 font-bold uppercase">Total Pinjaman</p><p class="font-bold text-slate-700 mt-1"><?= rupiah($finance['total_pinjaman'] ?? 0) ?></p></div>
          <div class="bg-slate-100 rounded-xl p-3"><p class="text-[10px] text-slate-500 font-bold uppercase">Sisa Pinjaman</p><p class="font-bold text-slate-700 mt-1"><?= rupiah($finance['sisa_pinjaman'] ?? 0) ?></p></div>
          <div class="bg-slate-100 rounded-xl p-3"><p class="text-[10px] text-slate-500 font-bold uppercase">Total Angsuran</p><p class="font-bold text-slate-700 mt-1"><?= rupiah($finance['total_angsuran'] ?? 0) ?></p></div>
          <div class="bg-gold/20 rounded-xl p-3"><p class="text-[10px] text-slate-500 font-bold uppercase">SHU</p><p class="font-bold text-amber-600 mt-1"><?= rupiah($finance['shu_total'] ?? 0) ?></p></div>
        </div>
        <div class="flex flex-wrap items-center gap-x-6 gap-y-1 mt-3 text-[11px] text-slate-500">
          <span>Status pinjaman: <b class="text-slate-700"><?= e((string) ($finance['status_pinjaman'] ?? '-')) ?></b></span>
          <span>Pembayaran terakhir: <b class="text-slate-700"><?= e(tanggal($finance['pembayaran_terakhir'] ?? null)) ?></b></span>
        </div>

        <?php if ($transactions !== []): ?>
          <div class="mt-4">
            <p class="text-[10px] font-bold text-slate-500 uppercase mb-1">Transaksi Terakhir</p>
            <table class="w-full text-xs text-left">
              <thead class="bg-slate-100 text-slate-500 uppercase text-[9px]">
                <tr><th class="p-2 rounded-l-lg">Tanggal</th><th class="p-2">Modul</th><th class="p-2">Ref</th><th class="p-2">Jenis</th><th class="p-2 text-right rounded-r-lg">Nominal</th></tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <?php foreach ($transactions as $t): ?>
                  <tr>
                    <td class="p-2 text-slate-500"><?= e(tanggal((string) ($t['tanggal'] ?? ''))) ?></td>
                    <td class="p-2"><span class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-600 text-[9px] font-bold"><?= e((string) ($t['modul'] ?? '')) ?></span></td>
                    <td class="p-2 font-mono text-[10px]"><?= e((string) ($t['ref'] ?? '-')) ?></td>
                    <td class="p-2"><?= e((string) ($t['jenis'] ?? '')) ?></td>
                    <td class="p-2 text-right font-semibold"><?= rupiah($t['nominal'] ?? 0) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <div class="glass-card rounded-2xl p-5 shadow-sm text-center">
        <i class="fa-solid fa-lock text-slate-300 text-2xl"></i>
        <p class="text-xs text-slate-500 mt-2">Data keuangan bersifat rahasia dan hanya tampil kepada anggota bersangkutan<br>atau pengurus dengan izin khusus setelah login.</p>
        <a href="/login" class="inline-block mt-3 px-4 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow">Login Pengurus</a>
      </div>
    <?php endif; ?>

    <p class="text-center text-[10px] text-slate-400">
      Verifikasi resmi &copy; <?= date('Y') ?> KUTT SUKA MAKMUR Grati - Pasuruan.
      <a class="text-brand-600 hover:underline" href="<?= e($baseUrl) ?>/berita">Portal Berita</a>
    </p>
  </div>
</main>
