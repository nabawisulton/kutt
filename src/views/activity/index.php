<?php

/**
 * Log Aktivitas (audit trail) dengan pagination.
 * Variables: logs, page, pages, total.
 */
$logs  = $logs ?? [];
$page  = (int) ($page ?? 1);
$pages = (int) ($pages ?? 1);
$total = (int) ($total ?? 0);

$badge = [
    'LOGIN'  => 'bg-emerald-100 text-emerald-700',
    'LOGOUT' => 'bg-slate-200 text-slate-600',
    'CREATE' => 'bg-brand-100 text-brand-700',
    'UPDATE' => 'bg-blue-100 text-blue-700',
    'DELETE' => 'bg-red-100 text-red-700',
    'EXPORT' => 'bg-amber-100 text-amber-700',
];
?>
<div class="glass-card rounded-2xl p-5 shadow-sm space-y-4">
  <div class="flex items-center justify-between">
    <span class="text-[11px] bg-brand-50 text-brand-600 px-2.5 py-1 rounded-lg font-semibold"><?= number_format($total) ?> aktivitas tercatat</span>
    <span class="text-[10px] text-slate-400">Data sensitif (password/kredensial) tidak pernah disimpan di log.</span>
  </div>

  <div class="overflow-x-auto">
    <table class="w-full text-xs text-left">
      <thead class="bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-semibold uppercase text-[10px]">
        <tr>
          <th class="p-2.5 rounded-l-lg">Waktu</th>
          <th class="p-2.5">User</th>
          <th class="p-2.5">Aksi</th>
          <th class="p-2.5">Modul</th>
          <th class="p-2.5 rounded-r-lg">Detail</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
        <?php if ($logs === []): ?>
          <tr><td colspan="5" class="p-4 text-center text-slate-400">Belum ada aktivitas tercatat.</td></tr>
        <?php else: ?>
          <?php foreach ($logs as $log): ?>
            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800">
              <td class="p-2.5 text-slate-500 whitespace-nowrap font-mono text-[10px]"><?= e(tanggal((string) ($log['timestamp'] ?? ''), true)) ?></td>
              <td class="p-2.5">
                <?php
                  $u = $log['user_id'] !== null
                    ? \App\Core\Database::first('SELECT username, full_name FROM users WHERE id = ?', [(int) $log['user_id']])
                    : null;
                ?>
                <span class="font-semibold text-slate-700 dark:text-slate-200"><?= e($u['username'] ?? 'Sistem') ?></span>
                <?php if (!empty($log['role'])): ?>
                  <span class="ml-1 text-[9px] px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-700 text-slate-500"><?= e((string) $log['role']) ?></span>
                <?php endif; ?>
              </td>
              <td class="p-2.5">
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold <?= $badge[$log['action']] ?? 'bg-slate-100 text-slate-600' ?>"><?= e((string) $log['action']) ?></span>
              </td>
              <td class="p-2.5 text-slate-500">
                <?= e((string) ($log['module'] ?? '-')) ?><?= !empty($log['data_id']) ? ' #' . e((string) $log['data_id']) : '' ?>
              </td>
              <td class="p-2.5 text-slate-600 dark:text-slate-300"><?= e((string) ($log['details'] ?? '')) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php if ($pages > 1): ?>
    <div class="flex items-center justify-between text-xs text-slate-500">
      <span>Hal <?= $page ?> dari <?= $pages ?></span>
      <div class="space-x-1">
        <?php if ($page > 1): ?><a href="/activity?page=<?= $page - 1 ?>" class="px-3 py-1.5 rounded-lg bg-slate-100 dark:bg-slate-800 font-bold">&laquo;</a><?php endif; ?>
        <?php if ($page < $pages): ?><a href="/activity?page=<?= $page + 1 ?>" class="px-3 py-1.5 rounded-lg bg-slate-100 dark:bg-slate-800 font-bold">&raquo;</a><?php endif; ?>
      </div>
    </div>
  <?php endif; ?>
</div>
