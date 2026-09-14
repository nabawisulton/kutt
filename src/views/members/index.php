<?php

/**
 * Master Data Anggota - list dengan CRUD actions, filter, pagination, export.
 * Variables: result (rows/total/page/pages), filters (q/status/group),
 *            currentUser, csrfToken (via layout), allowedViews.
 */

use App\Core\Roles;

$result  = $result ?? ['rows' => [], 'total' => 0, 'page' => 1, 'pages' => 1];
$filters = $filters ?? [];
$role    = (string) ($currentUser['role'] ?? '');
$canCreate = Roles::can($role, 'member.create');
$canEdit   = Roles::can($role, 'member.edit');
$canDelete = Roles::can($role, 'member.delete');
$canExport = Roles::can($role, 'report.export');
$canPrint  = Roles::can($role, 'report.print');
?>
<div class="glass-card rounded-2xl p-5 shadow-sm space-y-4">

  <!-- Toolbar -->
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
    <div class="flex items-center gap-2 text-xs text-slate-500">
      <span class="bg-brand-50 text-brand-600 px-2.5 py-1 rounded-lg font-semibold"><?= (int) $result['total'] ?> anggota</span>
      <?php if ($canExport): ?>
        <a href="/members/export/excel" class="px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold shadow"><i class="fa-solid fa-file-excel mr-1"></i>Excel</a>
      <?php endif; ?>
      <?php if ($canPrint): ?>
        <a href="/members/print" target="_blank" class="px-3 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-bold"><i class="fa-solid fa-print mr-1"></i>Cetak</a>
      <?php endif; ?>
    </div>
    <?php if ($canCreate): ?>
      <a href="/members/create" class="px-4 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow-md shadow-brand-600/20 whitespace-nowrap">
        <i class="fa-solid fa-user-plus mr-1"></i>Tambah Anggota
      </a>
    <?php endif; ?>
  </div>

  <!-- Filters -->
  <form method="get" action="/members" class="grid grid-cols-2 md:grid-cols-4 gap-2 items-end">
    <div class="col-span-2">
      <label class="text-[10px] font-bold text-slate-500 uppercase">Cari</label>
      <input type="text" name="q" value="<?= e($filters['q'] ?? '') ?>" placeholder="No. anggota / nama / NIK / No. HP..."
        class="w-full mt-1 px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800 focus:ring-2 focus:ring-brand-600">
    </div>
    <div>
      <label class="text-[10px] font-bold text-slate-500 uppercase">Status</label>
      <select name="status" class="w-full mt-1 px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800">
        <option value="">Semua</option>
        <?php foreach (['AKTIF' => 'Aktif', 'CALON' => 'Calon', 'NONAKTIF' => 'Nonaktif', 'KELUAR' => 'Keluar'] as $v => $l): ?>
          <option value="<?= $v ?>" <?= ($filters['status'] ?? '') === $v ? 'selected' : '' ?>><?= $l ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="flex space-x-2">
      <button class="flex-1 px-3 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow"><i class="fa-solid fa-filter"></i> Filter</button>
      <a href="/members" class="px-3 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-xs font-bold">Reset</a>
    </div>
  </form>

  <!-- Table -->
  <div class="overflow-x-auto">
    <table class="w-full text-xs text-left">
      <thead class="bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-semibold uppercase text-[10px]">
        <tr>
          <th class="p-2.5 rounded-l-lg">No. Anggota</th>
          <th class="p-2.5">Nama Lengkap</th>
          <th class="p-2.5">NIK</th>
          <th class="p-2.5">Kelompok</th>
          <th class="p-2.5">Status</th>
          <th class="p-2.5">Bergabung</th>
          <th class="p-2.5 rounded-r-lg text-center">Aksi</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
        <?php if ($result['rows'] === []): ?>
          <tr><td colspan="7" class="p-4 text-center text-slate-400">Belum ada data anggota<?= ($filters['q'] ?? '') !== '' ? ' yang cocok dengan pencarian' : '' ?>.</td></tr>
        <?php else: ?>
          <?php foreach ($result['rows'] as $member): ?>
            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800">
              <td class="p-2.5 font-mono font-semibold text-brand-600"><?= e($member['member_no']) ?></td>
              <td class="p-2.5">
                <div class="flex items-center space-x-2">
                  <?php if (!empty($member['photo_path'])): ?>
                    <img src="/<?= e($member['photo_path']) ?>" class="w-8 h-8 rounded-lg object-cover" alt="">
                  <?php else: ?>
                    <div class="w-8 h-8 rounded-lg bg-brand-600 text-white flex items-center justify-center text-[10px] font-bold"><?= e(initials((string) $member['full_name'])) ?></div>
                  <?php endif; ?>
                  <span class="font-semibold text-slate-700 dark:text-slate-200"><?= e($member['full_name']) ?></span>
                </div>
              </td>
              <td class="p-2.5 font-mono"><?= e($member['nik'] ?? '-') ?></td>
              <td class="p-2.5"><?= e($member['group_name'] ?? '-') ?></td>
              <td class="p-2.5">
                <?php
                  $badgeClass = match ((string) $member['status']) {
                    'AKTIF'    => 'bg-emerald-100 text-emerald-700',
                    'CALON'    => 'bg-blue-100 text-blue-700',
                    'NONAKTIF' => 'bg-slate-200 text-slate-600',
                    'KELUAR'   => 'bg-red-100 text-red-700',
                    default    => 'bg-slate-100 text-slate-600',
                  };
                ?>
                <span class="px-2 py-0.5 rounded-full <?= $badgeClass ?> text-[10px] font-bold"><?= e($member['status']) ?></span>
              </td>
              <td class="p-2.5 text-slate-500"><?= tanggal((string) ($member['joined_at'] ?? $member['created_at'])) ?></td>
              <td class="p-2.5">
                <div class="flex items-center justify-center space-x-1 whitespace-nowrap">
                  <a href="/members/<?= (int) $member['id'] ?>/card" title="Kartu & QR" class="px-2 py-1 rounded-lg bg-brand-50 text-brand-600 hover:bg-brand-100"><i class="fa-solid fa-id-card"></i></a>
                  <?php if ($canEdit): ?>
                    <a href="/members/edit/<?= (int) $member['id'] ?>" title="Edit" class="px-2 py-1 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100"><i class="fa-solid fa-pen"></i></a>
                  <?php endif; ?>
                  <?php if ($canDelete): ?>
                    <form method="post" action="/members/delete/<?= (int) $member['id'] ?>" onsubmit="return confirmAction(this, 'Apakah Anda yakin ingin menghapus data ini?')" class="inline">
                      <?php echo \App\Core\Csrf::field(); ?>
                      <button class="px-2 py-1 rounded-lg bg-red-50 text-red-600 hover:bg-red-100" title="Hapus"><i class="fa-solid fa-trash"></i></button>
                    </form>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  <?php if ($result['pages'] > 1): ?>
    <div class="flex items-center justify-between text-xs text-slate-500">
      <span>Hal <?= (int) $result['page'] ?> dari <?= (int) $result['pages'] ?> (<?= (int) $result['total'] ?> data)</span>
      <div class="space-x-1">
        <?php
          $qs = http_build_query(array_merge($_GET, ['page' => max(1, $result['page'] - 1)]));
          $qsNext = http_build_query(array_merge($_GET, ['page' => min($result['pages'], $result['page'] + 1)]));
        ?>
        <?php if ($result['page'] > 1): ?><a href="/members?<?= $qs ?>" class="px-3 py-1.5 rounded-lg bg-slate-100 dark:bg-slate-800 font-bold">&laquo;</a><?php endif; ?>
        <?php if ($result['page'] < $result['pages']): ?><a href="/members?<?= $qsNext ?>" class="px-3 py-1.5 rounded-lg bg-slate-100 dark:bg-slate-800 font-bold">&raquo;</a><?php endif; ?>
      </div>
    </div>
  <?php endif; ?>
</div>
