<?php

/**
 * Daftar user sistem. Variables: $users, $roles, $allowedViews, $activeView.
 */

use App\Core\Csrf;
use App\Core\Roles;

$users = $users ?? [];
$me    = $_SESSION['_auth_user'] ?? [];
?>

<div class="glass-card rounded-2xl shadow-sm overflow-hidden">
  <div class="p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800">
    <div class="flex items-center gap-2">
      <div class="relative">
        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400"><i class="fa-solid fa-magnifying-glass text-xs"></i></span>
        <input type="text" id="user-search" oninput="filterUsers()" placeholder="Cari username / nama / email..."
          class="pl-9 pr-4 py-2 text-xs bg-slate-100 dark:bg-slate-800 border-0 rounded-xl focus:ring-2 focus:ring-brand-600 w-48 sm:w-64 max-w-full">
      </div>
    </div>
    <div class="flex items-center gap-2">
      <a href="/users/export/excel" class="px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
        <i class="fa-solid fa-file-excel mr-1 text-emerald-600"></i>Excel
      </a>
      <?php if (Roles::can((string) ($me['role'] ?? ''), 'users')): ?>
        <a href="/users/create" class="px-4 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow">
          <i class="fa-solid fa-user-plus mr-1"></i>Tambah User
        </a>
      <?php endif; ?>
    </div>
  </div>

  <div class="overflow-x-auto">
    <table class="w-full text-xs">
      <thead>
        <tr class="text-left text-slate-500 dark:text-slate-400 border-b border-slate-100 dark:border-slate-800">
          <th class="px-5 py-3 font-semibold">ID</th>
          <th class="px-5 py-3 font-semibold">Nama / Username</th>
          <th class="px-5 py-3 font-semibold">Email</th>
          <th class="px-5 py-3 font-semibold">Role</th>
          <th class="px-5 py-3 font-semibold">Status</th>
          <th class="px-5 py-3 font-semibold text-right">Aksi</th>
        </tr>
      </thead>
      <tbody id="user-rows">
        <?php foreach ($users as $u): ?>
          <tr class="user-row border-b border-slate-50 dark:border-slate-800/60 hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition" data-search="<?= e(mb_strtolower($u['username'] . ' ' . $u['full_name'] . ' ' . $u['email'])) ?>">
            <td class="px-5 py-3 font-mono text-[11px] text-slate-500"><?= e((string) $u['user_id']) ?></td>
            <td class="px-5 py-3">
              <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-full bg-brand-600 text-white font-bold flex items-center justify-center text-[10px] shadow shrink-0"><?= e(initials((string) $u['full_name'])) ?></div>
                <div>
                  <p class="font-bold text-slate-800 dark:text-slate-100"><?= e((string) $u['full_name']) ?></p>
                  <p class="text-[11px] text-slate-500">@<?= e((string) $u['username']) ?></p>
                </div>
              </div>
            </td>
            <td class="px-5 py-3 text-slate-600 dark:text-slate-300"><?= e((string) $u['email']) ?></td>
            <td class="px-5 py-3">
              <span class="px-2 py-1 rounded-lg text-[10px] font-bold <?= $u['role'] === 'SUPER_ADMIN' ? 'bg-purple-100 text-purple-700' : ($u['role'] === 'ADMIN' ? 'bg-brand-100 text-brand-700' : ($u['role'] === 'BENDAHARA' ? 'bg-amber-100 text-amber-700' : ($u['role'] === 'KETUA' ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-600'))) ?>">
                <?= e(role_label((string) $u['role'])) ?>
              </span>
            </td>
            <td class="px-5 py-3">
              <?php if ((int) $u['is_active'] === 1): ?>
                <span class="px-2 py-1 rounded-lg text-[10px] font-bold bg-emerald-100 text-emerald-700"><i class="fa-solid fa-circle-check mr-0.5"></i>Aktif</span>
              <?php else: ?>
                <span class="px-2 py-1 rounded-lg text-[10px] font-bold bg-red-100 text-red-600"><i class="fa-solid fa-ban mr-0.5"></i>Nonaktif</span>
              <?php endif; ?>
            </td>
            <td class="px-5 py-3">
              <div class="flex items-center justify-end gap-1.5">
                <?php if ((int) $u['id'] !== (int) ($me['id'] ?? 0)): ?>
                  <form method="post" action="/users/toggle/<?= (int) $u['id'] ?>" onsubmit="return confirmAction(this, '<?= (int) $u['is_active'] === 1 ? 'Nonaktifkan' : 'Aktifkan' ?> user ini?')">
                    <?= Csrf::field() ?>
                    <button type="submit" title="<?= (int) $u['is_active'] === 1 ? 'Nonaktifkan' : 'Aktifkan' ?>" class="p-2 rounded-lg text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-900/20 transition">
                      <i class="fa-solid fa-power-off text-xs"></i>
                    </button>
                  </form>
                <?php endif; ?>
                <a href="/users/edit/<?= (int) $u['id'] ?>" title="Edit" class="p-2 rounded-lg text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition">
                  <i class="fa-solid fa-pen text-xs"></i>
                </a>
                <?php if ((int) $u['id'] !== (int) ($me['id'] ?? 0)): ?>
                  <button type="button" onclick="openResetModal(<?= (int) $u['id'] ?>, '<?= e((string) $u['username']) ?>')" title="Reset Password" class="p-2 rounded-lg text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                    <i class="fa-solid fa-key text-xs"></i>
                  </button>
                  <form method="post" action="/users/delete/<?= (int) $u['id'] ?>" onsubmit="return confirmAction(this, 'Apakah Anda yakin ingin menghapus data ini?')">
                    <?= Csrf::field() ?>
                    <button type="submit" title="Hapus" class="p-2 rounded-lg text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition">
                      <i class="fa-solid fa-trash text-xs"></i>
                    </button>
                  </form>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if ($users === []): ?>
          <tr><td colspan="6" class="px-5 py-10 text-center text-slate-400">Belum ada user terdaftar.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal reset password -->
<div id="reset-modal" class="hidden fixed inset-0 z-[70] bg-slate-950/60 backdrop-blur-sm flex items-center justify-center p-4">
  <div class="glass-card rounded-2xl shadow-2xl w-full max-w-sm p-5 max-h-[90vh] overflow-y-auto custom-scrollbar">
    <h3 class="font-display text-sm font-bold text-slate-800 dark:text-slate-100 mb-1"><i class="fa-solid fa-key mr-1 text-brand-600"></i>Reset Password</h3>
    <p class="text-[11px] text-slate-500 mb-4">Akun: <strong id="reset-username" class="text-slate-700 dark:text-slate-200"></strong></p>
    <form method="post" id="reset-form" class="space-y-3">
      <?= Csrf::field() ?>
      <input type="password" name="password" required minlength="6" placeholder="Password baru (min. 6 karakter)"
        class="w-full px-3 py-2.5 text-xs border rounded-xl bg-white dark:bg-slate-800 focus:ring-2 focus:ring-brand-600">
      <div class="flex justify-end gap-2 pt-1">
        <button type="button" onclick="closeResetModal()" class="px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-600 dark:text-slate-300">Batal</button>
        <button type="submit" class="px-4 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow"><i class="fa-solid fa-floppy-disk mr-1"></i>Simpan</button>
      </div>
    </form>
  </div>
</div>

<script>
  function filterUsers() {
    var q = document.getElementById('user-search').value.toLowerCase().trim();
    document.querySelectorAll('.user-row').forEach(function (row) {
      var hay = row.getAttribute('data-search') || '';
      row.style.display = hay.indexOf(q) !== -1 ? '' : 'none';
    });
  }

  function openResetModal(id, username) {
    document.getElementById('reset-username').textContent = username;
    var form = document.getElementById('reset-form');
    form.action = '/users/password/' + id;
    document.getElementById('reset-modal').classList.remove('hidden');
    form.querySelector('input[name=password]').focus();
  }

  function closeResetModal() {
    document.getElementById('reset-modal').classList.add('hidden');
  }
</script>
