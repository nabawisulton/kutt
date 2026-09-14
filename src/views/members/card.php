<?php

/**
 * Kartu Anggota 2 sisi + QR + cetak NPWP-style.
 * Variables: member, card, cardBg, cardBackBg.
 * Foto memakai rasio 4x6 (object-cover), QR berisi URL verifikasi bertoken.
 */

use App\Core\Csrf;

$member     = $member ?? [];
$card       = $card ?? [];
$cardBg     = $cardBg ?? ['type' => 'color', 'color' => '#0b7a3e', 'image' => null, 'opacity' => 0.25];
$cardBackBg = $cardBackBg ?? null; // null = sisi belakang memakai background depan

/** Style CSS background untuk satu sisi kartu. */
$bgStyleFor = function (array $bg): string {
    if (($bg['type'] ?? 'color') === 'image' && !empty($bg['image'])) {
        return "background-image:url('/" . e((string) $bg['image']) . "');background-size:cover;background-position:center;";
    }

    return 'background-color:' . e((string) ($bg['color'] ?? '#0b7a3e')) . ';';
};

/** Overlay gelap agar teks/QR tetap terbaca. */
$overlayFor = function (array $bg): string {
    if (($bg['type'] ?? 'color') === 'image') {
        return 'background:rgba(5,46,26,' . e((string) ($bg['opacity'] ?? 0.25)) . ');';
    }

    return '';
};

$jabatanLabel = trim((string) ($member['jabatan_internal'] ?? ''));
$relasiMap = [
    'TIDAK_ADA' => null, 'KARYAWAN' => 'Karyawan', 'KONSUMEN' => 'Konsumen / Client',
    'PEMASOK' => 'Pemasok', 'MITRA' => 'Mitra Usaha', 'PIHAK_LAIN' => 'Pihak Lain',
];
$relasiLabel = $relasiMap[(string) ($member['relasi_eksternal'] ?? 'TIDAK_ADA')] ?? null;
$jabatanEksternal = trim((string) ($member['jabatan_eksternal'] ?? ''));
?>
<div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

  <!-- Kartu depan + belakang -->
  <div class="glass-card rounded-2xl p-5 shadow-sm">
    <div class="flex items-center justify-between mb-4">
      <h3 class="font-display text-sm font-bold text-slate-800 dark:text-slate-100">Kartu Anggota (2 Sisi)</h3>
      <div class="flex items-center gap-2">
        <a href="/members/<?= (int) $member['id'] ?>/card/print" target="_blank" class="px-3 py-1.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow"><i class="fa-solid fa-print mr-1"></i>Cetak Kartu</a>
        <form method="post" action="/members/<?= (int) $member['id'] ?>/card/rotate" onsubmit="return confirmAction(this, 'Rotate token QR? Kode lama tidak berlaku.')" class="inline">
          <?= Csrf::field() ?>
          <button class="px-3 py-1.5 rounded-xl bg-amber-50 text-amber-600 hover:bg-amber-100 text-xs font-bold"><i class="fa-solid fa-rotate mr-1"></i>Rotate QR</button>
        </form>
      </div>
    </div>

    <div class="flex flex-wrap items-start justify-center gap-4">
      <!-- SISI DEPAN -->
      <div>
        <p class="text-[10px] font-bold text-slate-400 uppercase text-center mb-1.5">Depan</p>
        <div class="relative w-full max-w-[340px] aspect-[85.6/53.98] rounded-xl overflow-hidden shadow-xl border border-white/20 mx-auto">
          <div class="absolute inset-0" style="<?= $bgStyleFor($cardBg) ?>"></div>
          <?php if ($overlayFor($cardBg) !== ''): ?><div class="absolute inset-0" style="<?= $overlayFor($cardBg) ?>"></div><?php endif; ?>
          <div class="relative h-full flex flex-col p-3.5 text-white" style="background:linear-gradient(135deg, rgba(5,46,26,0.50), rgba(11,122,62,0.30));">
            <div class="flex items-start justify-between">
              <div class="flex items-center space-x-1.5">
                <div class="w-8 h-8 rounded-lg bg-white/90 flex items-center justify-center text-brand-600 shadow text-sm">
                  <i class="fa-solid fa-cow"></i>
                </div>
                <div>
                  <p class="font-display font-bold text-[10px] leading-tight">KUTT SUKA MAKMUR</p>
                  <p class="text-[7px] uppercase tracking-wider opacity-80">Kartu Anggota Resmi</p>
                </div>
              </div>
              <span class="text-[7px] font-mono bg-white/20 rounded px-1.5 py-0.5"><?= e((string) ($card['card_serial'] ?? '-')) ?></span>
            </div>

            <div class="flex-1 flex items-center justify-between gap-2.5 mt-1.5">
              <div class="shrink-0">
                <?php if (!empty($member['photo_path'])): ?>
                  <img src="/<?= e($member['photo_path']) ?>" alt="Foto"
                    class="w-[58px] h-[87px] object-cover rounded-md border-2 border-white/70 shadow-lg">
                <?php else: ?>
                  <div class="w-[58px] h-[87px] rounded-md border-2 border-white/70 bg-white/20 flex flex-col items-center justify-center text-center text-[6px] leading-tight">
                    <i class="fa-solid fa-user text-base opacity-60"></i>
                    <span class="mt-1 opacity-80 px-1">Foto 4x6</span>
                  </div>
                <?php endif; ?>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-[7px] uppercase opacity-75">No. Anggota</p>
                <p class="font-mono font-bold text-[10px] mb-1"><?= e((string) $member['member_no']) ?></p>
                <p class="text-[7px] uppercase opacity-75">Nama</p>
                <p class="font-display font-bold text-[13px] leading-tight truncate"><?= e((string) $member['full_name']) ?></p>
                <?php if ($jabatanLabel !== ''): ?>
                  <p class="text-[8px] font-bold text-gold mt-0.5 truncate"><?= e($jabatanLabel) ?></p>
                <?php endif; ?>
                <p class="text-[7px] opacity-80 mt-0.5 line-clamp-2"><?= e((string) ($member['address'] ?? '')) ?: '&nbsp;' ?></p>
                <div class="mt-1 flex items-center gap-1">
                  <span class="text-[7px] font-bold px-1.5 py-0.5 rounded bg-gold/90 text-slate-900"><?= e((string) $member['status']) ?></span>
                  <span class="text-[7px] opacity-80"><?= e(tanggal((string) ($member['joined_at'] ?? ''))) ?></span>
                </div>
              </div>
              <div class="shrink-0 text-center">
                <img src="/qr/<?= e((string) ($card['verify_code'] ?? '')) ?>" alt="QR" class="w-[58px] h-[58px] rounded-md bg-white p-0.5 shadow">
                <p class="text-[6px] mt-0.5 opacity-80">Pindai verifikasi</p>
              </div>
            </div>

            <div class="flex items-center justify-between text-[6px] opacity-75 border-t border-white/25 pt-1">
              <span>KUTT SUKA MAKMUR Grati - Pasuruan</span>
              <span>NIA: <?= e((string) ($member['nia'] ?? '-')) ?></span>
            </div>
          </div>
        </div>
      </div>

      <!-- SISI BELAKANG -->
      <?php $bgBelakang = $cardBackBg ?? $cardBg; ?>
      <div>
        <p class="text-[10px] font-bold text-slate-400 uppercase text-center mb-1.5">Belakang</p>
        <div class="relative w-full max-w-[340px] aspect-[85.6/53.98] rounded-xl overflow-hidden shadow-xl border border-white/20 mx-auto">
          <div class="absolute inset-0" style="<?= $bgStyleFor($bgBelakang) ?>"></div>
          <?php if ($overlayFor($bgBelakang) !== ''): ?><div class="absolute inset-0" style="<?= $overlayFor($bgBelakang) ?>"></div><?php endif; ?>
          <div class="relative h-full flex flex-col p-3.5 text-white" style="background:linear-gradient(160deg, rgba(5,46,26,0.55), rgba(11,122,62,0.35));">
            <div class="flex items-center justify-between">
              <p class="font-display font-bold text-[10px]">SYARAT &amp; KETENTUAN</p>
              <span class="text-[7px] font-mono bg-white/20 rounded px-1.5 py-0.5"><?= e((string) $member['member_no']) ?></span>
            </div>
            <ol class="text-[7px] leading-relaxed opacity-90 list-decimal list-inside mt-1.5 space-y-0.5 flex-1">
              <li>Kartu ini adalah identitas resmi anggota KUTT SUKA MAKMUR dan wajib dibawa saat bertransaksi.</li>
              <li>Kartu tidak boleh dipindahtangankan; penggunaan oleh orang lain tidak sah.</li>
              <li>Apabila kartu hilang, segera laporkan ke pengurus untuk penerbitan kartu pengganti.</li>
            </ol>

            <div class="mt-1.5 rounded-lg bg-white/10 border border-white/15 p-2 space-y-0.5">
              <p class="text-[7px] uppercase tracking-wide opacity-80">Hubungan Eksternal</p>
              <?php if ($relasiLabel !== null): ?>
                <p class="text-[9px] font-bold"><i class="fa-solid fa-briefcase mr-1 text-gold"></i><?= e($relasiLabel) ?><?php if ($jabatanEksternal !== ''): ?> — <?= e($jabatanEksternal) ?><?php endif; ?></p>
              <?php else: ?>
                <p class="text-[9px] opacity-75">Tidak tercatat</p>
              <?php endif; ?>
              <?php if ($jabatanLabel !== ''): ?>
                <p class="text-[7px] opacity-85"><i class="fa-solid fa-user-tie mr-1 text-gold"></i>Jabatan internal: <?= e($jabatanLabel) ?></p>
              <?php endif; ?>
            </div>

            <div class="flex items-end justify-between mt-1.5 pt-1 border-t border-white/25">
              <div class="text-[6px] opacity-75 leading-tight">
                <p>KUTT SUKA MAKMUR</p>
                <p>Jl. Raya Grati No. 128, Pasuruan</p>
                <p>(0343) 481123</p>
              </div>
              <div class="text-center">
                <img src="/qr/<?= e((string) ($card['verify_code'] ?? '')) ?>" alt="QR" class="w-[40px] h-[40px] rounded bg-white p-0.5 shadow">
                <p class="text-[5px] mt-0.5 opacity-75">Verifikasi keaslian</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <p class="text-[10px] text-slate-400 mt-3 text-center">Cetak Kartu membuka lembar cetak ukuran kartu standar (85.6 × 53.98 mm) siap dipotong — sama seperti format kartu NPWP/KTP.</p>
  </div>

  <!-- Info verifikasi -->
  <div class="glass-card rounded-2xl p-5 shadow-sm space-y-3">
    <h3 class="font-display text-sm font-bold text-slate-800 dark:text-slate-100">Verifikasi Publik</h3>
    <div class="text-xs space-y-2">
      <div class="flex justify-between"><span class="text-slate-500">URL verifikasi</span><a class="text-brand-600 font-mono text-[10px] break-all" href="/anggota/verify/<?= e((string) ($card['verify_code'] ?? '')) ?>" target="_blank">/anggota/verify/<?= e((string) ($card['verify_code'] ?? '')) ?></a></div>
      <div class="flex justify-between"><span class="text-slate-500">Seri kartu</span><span class="font-mono"><?= e((string) ($card['card_serial'] ?? '-')) ?></span></div>
      <div class="flex justify-between"><span class="text-slate-500">Diterbitkan</span><span><?= tanggal((string) ($card['issued_at'] ?? '')) ?></span></div>
      <div class="flex justify-between"><span class="text-slate-500">Jabatan internal</span><span><?= $jabatanLabel !== '' ? e($jabatanLabel) : '-' ?></span></div>
      <div class="flex justify-between"><span class="text-slate-500">Relasi eksternal</span><span><?= $relasiLabel !== null ? e($relasiLabel . ($jabatanEksternal !== '' ? ' — ' . $jabatanEksternal : '')) : '-' ?></span></div>
      <div class="flex justify-between"><span class="text-slate-500">Background depan</span><span class="capitalize"><?= e((string) $cardBg['type']) ?><?= $cardBg['type'] === 'image' ? ' + overlay ' . e((string) $cardBg['opacity']) : ' ' . e((string) $cardBg['color']) ?></span></div>
      <div class="flex justify-between"><span class="text-slate-500">Background belakang</span><span class="capitalize"><?= $cardBackBg !== null ? e((string) $cardBackBg['type']) : 'mengikuti depan' ?></span></div>
    </div>
    <div class="bg-brand-50 dark:bg-slate-800 rounded-xl p-3 text-[11px] text-slate-600 dark:text-slate-300">
      <p class="font-bold text-brand-600 mb-1"><i class="fa-solid fa-shield-halved mr-1"></i>Keamanan</p>
      <ul class="list-disc list-inside space-y-0.5">
        <li>Halaman verifikasi bersifat publik: hanya identitas dasar yang tampil.</li>
        <li>Data keuangan hanya tampil bagi user ber-permission <code>finance.view</code>.</li>
        <li>Rotate token mengganti QR; kartu lama otomatis tidak valid.</li>
      </ul>
    </div>
    <a href="/members/scan" class="block text-center px-4 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow"><i class="fa-solid fa-qrcode mr-1"></i>Buka Halaman Scan</a>
  </div>
</div>
