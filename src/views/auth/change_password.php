<?php

/**
 * Halaman wajib ganti password (login pertama / password masih bawaan seed).
 * Variables: title.
 */

use App\Core\Csrf;

$title = $title ?? 'Ganti Password';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars((string) $title) ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="min-h-screen bg-gradient-to-br from-[#052e1a] via-[#0b4d2a] to-[#0b7a3e] flex items-center justify-center p-4 font-[Inter]">
  <div class="w-full max-w-md bg-white/95 backdrop-blur rounded-3xl shadow-2xl p-8">
    <div class="flex items-center gap-3 mb-5">
      <div class="w-12 h-12 rounded-2xl bg-brand-600 text-white flex items-center justify-center text-2xl shadow"><i class="fa-solid fa-cow"></i></div>
      <div>
        <h1 class="font-[Poppins] font-bold text-slate-800">KUTT SUKA MAKMUR</h1>
        <p class="text-[11px] text-slate-500">Keamanan akun Anda</p>
      </div>
    </div>

    <?php $flash = flash_take(); ?>
    <?php if (!empty($flash['message'])): ?>
      <div class="mb-4 rounded-xl px-4 py-3 text-xs font-semibold <?= ($flash['type'] ?? '') === 'error' ? 'bg-red-50 text-red-700 border border-red-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200' ?>">
        <i class="fa-solid <?= ($flash['type'] ?? '') === 'error' ? 'fa-circle-exclamation' : 'fa-circle-check' ?> mr-1"></i>
        <?= htmlspecialchars((string) $flash['message']) ?>
      </div>
    <?php endif; ?>

    <div class="rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-xs p-3.5 mb-5">
      <strong><i class="fa-solid fa-shield-halved mr-1"></i>Wajib ganti password.</strong>
      Akun Anda masih memakai password bawaan sistem. Untuk keamanan data koperasi,
      buat password baru minimal 8 karakter sebelum melanjutkan.
    </div>

    <form method="post" action="/password/change" class="space-y-3.5">
      <?= Csrf::field() ?>
      <div>
        <label class="block text-[11px] font-bold text-slate-600 mb-1.5">Password Saat Ini</label>
        <input type="password" name="current_password" required autocomplete="current-password"
          class="w-full px-3.5 py-2.5 text-xs border border-slate-200 rounded-xl focus:ring-2 focus:ring-brand-600 focus:border-brand-600">
      </div>
      <div>
        <label class="block text-[11px] font-bold text-slate-600 mb-1.5">Password Baru (min. 8 karakter)</label>
        <input type="password" name="new_password" required minlength="8" autocomplete="new-password"
          class="w-full px-3.5 py-2.5 text-xs border border-slate-200 rounded-xl focus:ring-2 focus:ring-brand-600 focus:border-brand-600">
      </div>
      <div>
        <label class="block text-[11px] font-bold text-slate-600 mb-1.5">Ulangi Password Baru</label>
        <input type="password" name="confirm_password" required minlength="8" autocomplete="new-password"
          class="w-full px-3.5 py-2.5 text-xs border border-slate-200 rounded-xl focus:ring-2 focus:ring-brand-600 focus:border-brand-600">
      </div>
      <button type="submit" class="w-full py-3 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow-lg transition">
        <i class="fa-solid fa-key mr-1"></i>Simpan Password Baru
      </button>
    </form>

    <form method="post" action="/logout" class="mt-4 text-center">
      <?= Csrf::field() ?>
      <button type="submit" class="text-[11px] font-semibold text-slate-400 hover:text-red-500 transition">
        <i class="fa-solid fa-arrow-right-from-bracket mr-1"></i>Logout saja untuk sekarang
      </button>
    </form>
  </div>
</body>
</html>
