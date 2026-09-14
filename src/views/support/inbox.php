<?php

/**
 * Inbox pesan anggota (sisi admin). Variables: inbox.
 */

$inbox = $inbox ?? [];
?>
<div class="glass-card rounded-2xl p-5 shadow-sm">
  <div class="flex items-center justify-between mb-4">
    <h3 class="font-display text-sm font-bold text-slate-800 dark:text-slate-100"><i class="fa-solid fa-inbox mr-1 text-brand-600"></i>Percakapan Anggota</h3>
    <span class="text-[11px] text-slate-400"><?= count($inbox) ?> percakapan</span>
  </div>

  <?php if ($inbox === []): ?>
    <div class="text-center text-xs text-slate-400 py-12">
      <i class="fa-regular fa-envelope-open text-4xl block mb-2 text-slate-200"></i>
      Belum ada pesan dari anggota.
    </div>
  <?php else: ?>
    <div class="divide-y divide-slate-100 dark:divide-slate-800">
      <?php foreach ($inbox as $c): ?>
        <a href="/support/<?= (int) $c['member_id'] ?>" class="flex items-center gap-3 py-3 group hover:bg-slate-50 dark:hover:bg-slate-800/60 rounded-xl px-2 -mx-2 transition">
          <div class="w-10 h-10 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center font-bold text-sm shrink-0 overflow-hidden">
            <?php if (!empty($c['photo_path'])): ?>
              <img src="/<?= e((string) $c['photo_path']) ?>" class="w-full h-full object-cover" alt="">
            <?php else: ?>
              <?= e(mb_strtoupper(mb_substr((string) $c['full_name'], 0, 1))) ?>
            <?php endif; ?>
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-xs font-bold text-slate-700 dark:text-slate-200 truncate"><?= e((string) $c['full_name']) ?> <span class="font-mono text-[10px] text-slate-400"><?= e((string) $c['member_no']) ?></span></p>
            <p class="text-[11px] text-slate-400"><?= tanggal((string) $c['last_at']) ?> · <?= substr((string) $c['last_at'], 11, 5) ?></p>
          </div>
          <?php if ((int) $c['unread'] > 0): ?>
            <span class="px-2 py-0.5 rounded-full bg-red-500 text-white text-[10px] font-bold"><?= (int) $c['unread'] ?></span>
          <?php endif; ?>
          <i class="fa-solid fa-chevron-right text-slate-300 text-xs group-hover:text-brand-600 transition"></i>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
