<?php

/**
 * Halaman saat token verifikasi kartu tidak valid / kartu dinonaktifkan.
 */
?>
<main class="min-h-screen bg-[#F7F9F8] flex items-center justify-center py-10 px-4">
  <div class="max-w-sm w-full">
    <div class="glass-card rounded-2xl p-8 shadow-sm text-center">
      <div class="w-16 h-16 mx-auto rounded-full bg-red-100 text-red-500 flex items-center justify-center text-2xl">
        <i class="fa-solid fa-id-card-clip"></i>
      </div>
      <h1 class="font-display text-lg font-bold text-slate-800 mt-4">Kartu Tidak Valid</h1>
      <p class="text-xs text-slate-500 mt-2 leading-relaxed">
        Kode verifikasi tidak dikenali atau kartu telah dinonaktifkan/diganti.<br>
        Silakan hubungi pengurus KUTT SUKA MAKMUR untuk penerbitan kartu baru.
      </p>
      <div class="flex flex-col gap-2 mt-5">
        <a href="/berita" class="px-4 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow">Portal Berita</a>
        <a href="/" class="text-[11px] text-slate-400 hover:text-brand-600">Kembali ke halaman utama</a>
      </div>
    </div>
    <p class="text-center text-[10px] text-slate-400 mt-4">&copy; <?= date('Y') ?> KUTT SUKA MAKMUR Grati - Pasuruan</p>
  </div>
</main>
