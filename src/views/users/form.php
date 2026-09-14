<?php

/**
 * Form tambah/edit user. Variables: $editUser (null saat tambah), $roles.
 */

use App\Core\Csrf;
use App\Core\Roles;

$editUser = $editUser ?? null;
$isEdit   = $editUser !== null;
$action   = $isEdit ? '/users/edit/' . (int) $editUser['id'] : '/users/create';
$val      = static fn (string $key, string $default = ''): string => (string) ($editUser[$key] ?? $default);
?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
  <div class="lg:col-span-2 glass-card rounded-2xl p-5 shadow-sm">
    <h3 class="font-display text-sm font-bold text-slate-800 dark:text-slate-100 mb-4">
      <i class="fa-solid <?= $isEdit ? 'fa-user-pen' : 'fa-user-plus' ?> mr-1 text-brand-600"></i>
      <?= $isEdit ? 'Edit User: ' . e((string) $editUser['username']) : 'Data User Baru' ?>
    </h3>
    <form method="post" action="<?= e($action) ?>" class="grid grid-cols-1 md:grid-cols-2 gap-3">
      <?= Csrf::field() ?>
      <div>
        <label class="text-[10px] font-bold text-slate-500 uppercase">Nama Lengkap *</label>
        <input type="text" name="full_name" required maxlength="120" value="<?= e($val('full_name')) ?>" class="w-full mt-1 px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800">
      </div>
      <div>
        <label class="text-[10px] font-bold text-slate-500 uppercase">Username *</label>
        <input type="text" name="username" required pattern="[a-zA-Z0-9._-]{3,40}" value="<?= e($val('username')) ?>" class="w-full mt-1 px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800 font-mono">
      </div>
      <div>
        <label class="text-[10px] font-bold text-slate-500 uppercase">Email *</label>
        <input type="email" name="email" required maxlength="120" value="<?= e($val('email')) ?>" class="w-full mt-1 px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800">
      </div>
      <div>
        <label class="text-[10px] font-bold text-slate-500 uppercase">Role *</label>
        <select name="role" required class="w-full mt-1 px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800">
          <?php foreach ($roles as $role): ?>
            <?php if ($role === Roles::ANGGOTA) { continue; } // anggota bukan akun dashboard ?>
            <option value="<?= e($role) ?>" <?= ($editUser['role'] ?? '') === $role ? 'selected' : '' ?>><?= e(role_label($role)) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="md:col-span-2">
        <label class="text-[10px] font-bold text-slate-500 uppercase"><?= $isEdit ? 'Password Baru (opsional)' : 'Password *' ?></label>
        <input type="password" name="password" <?= $isEdit ? '' : 'required' ?> minlength="6" placeholder="<?= $isEdit ? 'Kosongkan jika tidak diubah' : 'Minimal 6 karakter' ?>"
          class="w-full mt-1 px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800">
      </div>
      <div class="md:col-span-2 flex items-center justify-between pt-3 border-t border-slate-100 dark:border-slate-800">
        <a href="/users" class="text-xs font-bold text-slate-500 hover:text-slate-700 dark:hover:text-slate-300"><i class="fa-solid fa-arrow-left mr-1"></i>Kembali</a>
        <button class="px-5 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow"><i class="fa-solid fa-floppy-disk mr-1"></i><?= $isEdit ? 'Perbarui' : 'Simpan' ?></button>
      </div>
    </form>
  </div>

  <div class="glass-card rounded-2xl p-5 shadow-sm space-y-3">
    <h4 class="font-display text-sm font-bold text-slate-800 dark:text-slate-100"><i class="fa-solid fa-circle-info mr-1 text-brand-600"></i>Hak Akses Role</h4>
    <ul class="text-[11px] text-slate-600 dark:text-slate-300 space-y-2">
      <li><strong class="text-purple-700 dark:text-purple-400">Super Admin</strong> — akses penuh + kelola user &amp; pengaturan.</li>
      <li><strong class="text-brand-700 dark:text-brand-500">Admin</strong> — seluruh modul operasional &amp; CMS, tanpa kelola user.</li>
      <li><strong class="text-amber-700 dark:text-amber-400">Bendahara</strong> — keuangan: simpanan, pinjaman, kas, jurnal, SHU.</li>
      <li><strong class="text-blue-700 dark:text-blue-400">Ketua</strong> — pantau data, pinjaman, SHU, berita (tanpa input keuangan).</li>
      <li><strong>Staff / Kasir</strong> — input transaksi harian: anggota, simpanan, angsuran, kas, berita.</li>
    </ul>
  </div>
</div>
