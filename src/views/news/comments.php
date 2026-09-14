<?php
/**
 * Moderasi komentar berita. Variables: post, comments.
 */
$post = $post ?? [];
$comments = $comments ?? [];
use App\Core\Csrf;
?>
<div class="glass-card rounded-2xl p-5 shadow-sm space-y-4">
  <div class="flex items-center justify-between">
    <div class="flex items-center space-x-3">
      <a href="/news" class="px-3 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-xs font-bold"><i class="fa-solid fa-arrow-left"></i></a>
      <h3 class="font-display text-sm font-bold text-slate-800 dark:text-slate-100">Komentar: <?= e($post['title'] ?? '') ?></h3>
    </div>
    <a href="/berita/<?= e($post['slug'] ?? '') ?>" target="_blank" class="text-[11px] text-brand-600 font-semibold hover:underline">Lihat berita <i class="fa-solid fa-arrow-up-right-from-square"></i></a>
  </div>

  <div class="space-y-2">
    <?php if ($comments === []): ?>
      <p class="text-xs text-slate-400">Belum ada komentar.</p>
    <?php else: ?>
      <?php foreach ($comments as $c): ?>
        <div class="p-3 rounded-xl border <?= $c['status'] === 'APPROVED' ? 'border-emerald-200 bg-emerald-50/50 dark:bg-slate-800' : ($c['status'] === 'HIDDEN' ? 'border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 opacity-60' : 'border-amber-200 bg-amber-50/50 dark:bg-slate-800') ?>">
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
              <p class="text-xs font-bold text-slate-700 dark:text-slate-200"><?= e($c['name']) ?>
                <span class="ml-1 px-1.5 py-0.5 rounded text-[9px] font-bold <?= $c['status'] === 'APPROVED' ? 'bg-emerald-100 text-emerald-700' : ($c['status'] === 'HIDDEN' ? 'bg-slate-200 text-slate-600' : 'bg-amber-100 text-amber-700') ?>"><?= e($c['status']) ?></span>
              </p>
              <p class="text-xs text-slate-600 dark:text-slate-300 mt-1"><?= e($c['body']) ?></p>
              <p class="text-[10px] text-slate-400 mt-1"><?= tanggal((string) $c['created_at'], true) ?></p>
            </div>
            <div class="flex items-center space-x-1 shrink-0">
              <?php if ($c['status'] !== 'APPROVED'): ?>
                <form method="post" action="/news/comments/<?= (int) $c['id'] ?>/status" class="inline"><?= Csrf::field() ?>
                  <input type="hidden" name="status" value="APPROVED">
                  <button class="px-2 py-1 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-100" title="Setujui"><i class="fa-solid fa-check"></i></button>
                </form>
              <?php endif; ?>
              <?php if ($c['status'] !== 'HIDDEN'): ?>
                <form method="post" action="/news/comments/<?= (int) $c['id'] ?>/status" class="inline"><?= Csrf::field() ?>
                  <input type="hidden" name="status" value="HIDDEN">
                  <button class="px-2 py-1 rounded-lg bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-200" title="Sembunyikan"><i class="fa-solid fa-eye-slash"></i></button>
                </form>
              <?php endif; ?>
              <form method="post" action="/news/comments/<?= (int) $c['id'] ?>/delete" onsubmit="return confirmAction(this, 'Apakah Anda yakin ingin menghapus data ini?')" class="inline"><?= Csrf::field() ?>
                <button class="px-2 py-1 rounded-lg bg-red-50 text-red-600 hover:bg-red-100" title="Hapus"><i class="fa-solid fa-trash"></i></button>
              </form>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>
