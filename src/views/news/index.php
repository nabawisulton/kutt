<?php
/**
 * Portal Berita - admin list. Variables: result, filters, categories.
 */

use App\Core\Roles;

$result = $result ?? ['rows' => [], 'total' => 0, 'page' => 1, 'pages' => 1];
$filters = $filters ?? [];
$categories = $categories ?? [];
$role      = (string) ($currentUser['role'] ?? '');
$canCreate = Roles::can($role, 'news.create');
$canExport = Roles::can($role, 'report.export');
?>
<div class="glass-card rounded-2xl p-5 shadow-sm space-y-4">

  <!-- Toolbar -->
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
    <div class="flex items-center gap-2 text-xs text-slate-500">
      <span class="bg-brand-50 text-brand-600 px-2.5 py-1 rounded-lg font-semibold"><?= (int) $result['total'] ?> berita</span>
      <?php if ($canExport): ?>
        <a href="/news/export/excel" class="px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold shadow"><i class="fa-solid fa-file-excel mr-1"></i>Excel</a>
      <?php endif; ?>
    </div>
    <?php if ($canCreate): ?>
      <a href="/news/create" class="px-4 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow-md shadow-brand-600/20 whitespace-nowrap">
        <i class="fa-solid fa-pen-nib mr-1"></i>Tulis Berita
      </a>
    <?php endif; ?>
  </div>

  <!-- Filters -->
  <form method="get" action="/news" class="grid grid-cols-2 md:grid-cols-5 gap-2 items-end">
    <div class="col-span-2 md:col-span-2">
      <label class="text-[10px] font-bold text-slate-500 uppercase">Cari</label>
      <input type="text" name="q" value="<?= e($filters['q'] ?? '') ?>" placeholder="Judul / ringkasan..."
        class="w-full mt-1 px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800 focus:ring-2 focus:ring-brand-600">
    </div>
    <div>
      <label class="text-[10px] font-bold text-slate-500 uppercase">Status</label>
      <select name="status" class="w-full mt-1 px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800">
        <option value="">Semua</option>
        <?php foreach (['PUBLISHED' => 'Published', 'DRAFT' => 'Draft'] as $v => $l): ?>
          <option value="<?= $v ?>" <?= ($filters['status'] ?? '') === $v ? 'selected' : '' ?>><?= $l ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="text-[10px] font-bold text-slate-500 uppercase">Kategori</label>
      <select name="category" class="w-full mt-1 px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800">
        <option value="">Semua</option>
        <?php foreach ($categories as $c): ?>
          <option value="<?= (int) $c['id'] ?>" <?= ($filters['category'] ?? '') == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="flex space-x-2">
      <button class="flex-1 px-3 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow"><i class="fa-solid fa-filter"></i> Filter</button>
      <a href="/news" class="px-3 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-xs font-bold">Reset</a>
    </div>
  </form>

  <!-- Table -->
  <div class="overflow-x-auto">
    <table class="w-full text-xs text-left">
      <thead class="bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-semibold uppercase text-[10px]">
        <tr>
          <th class="p-2.5 rounded-l-lg">Judul</th>
          <th class="p-2.5">Kategori</th>
          <th class="p-2.5">Status</th>
          <th class="p-2.5 text-center">Statistik</th>
          <th class="p-2.5 rounded-r-lg text-center">Aksi</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
        <?php if ($result['rows'] === []): ?>
          <tr><td colspan="5" class="p-4 text-center text-slate-400">Belum ada berita.</td></tr>
        <?php else: ?>
          <?php foreach ($result['rows'] as $post): ?>
            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800">
              <td class="p-2.5">
                <div class="flex items-center space-x-2">
                  <?php if (!empty($post['image_path'])): ?>
                    <img src="<?= e(news_image_src($post['image_path'])) ?>" class="w-10 h-10 rounded-lg object-cover" alt="">
                  <?php else: ?>
                    <div class="w-10 h-10 rounded-lg bg-slate-100 dark:bg-slate-700 flex items-center justify-center text-slate-400"><i class="fa-regular fa-newspaper"></i></div>
                  <?php endif; ?>
                  <div class="min-w-0">
                    <p class="font-bold text-slate-700 dark:text-slate-200 truncate max-w-[220px]"><?= e($post['title']) ?></p>
                    <p class="text-[10px] text-slate-400"><?= e($post['author_name'] ?? '') ?> · <?= tanggal((string) $post['created_at']) ?></p>
                  </div>
                </div>
              </td>
              <td class="p-2.5"><?= e($post['category_name'] ?? '-') ?></td>
              <td class="p-2.5">
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold <?= $post['status'] === 'PUBLISHED' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' ?>"><?= $post['status'] === 'PUBLISHED' ? 'PUBLISHED' : 'DRAFT' ?></span>
              </td>
              <td class="p-2.5">
                <div class="flex items-center justify-center space-x-2 text-[10px] text-slate-500">
                  <span title="Dilihat"><i class="fa-solid fa-eye"></i> <?= (int) $post['views'] ?></span>
                  <span title="Suka"><i class="fa-solid fa-thumbs-up"></i> <?= (int) $post['likes'] ?></span>
                  <span title="Dibagikan"><i class="fa-solid fa-share-nodes"></i> <?= (int) $post['shares'] ?></span>
                  <span title="Komentar"><i class="fa-solid fa-comments"></i> <?= (int) $post['comments_count'] ?></span>
                </div>
              </td>
              <td class="p-2.5">
                <div class="flex items-center justify-center space-x-1 whitespace-nowrap">
                  <a href="/berita/<?= e($post['slug']) ?>" target="_blank" title="Lihat" class="px-2 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200"><i class="fa-solid fa-eye"></i></a>
                  <a href="/news/edit/<?= (int) $post['id'] ?>" title="Edit" class="px-2 py-1 rounded-lg bg-brand-50 text-brand-600 hover:bg-brand-100"><i class="fa-solid fa-pen"></i></a>
                  <a href="/news/comments/<?= (int) $post['id'] ?>" title="Komentar" class="px-2 py-1 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100"><i class="fa-solid fa-comments"></i></a>
                  <?php if ($post['status'] === 'DRAFT'): ?>
                    <form method="post" action="/news/publish/<?= (int) $post['id'] ?>" onsubmit="return confirmAction(this, 'Publikasikan berita ini?')" class="inline">
                      <?php echo \App\Core\Csrf::field(); ?>
                      <button class="px-2 py-1 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-100" title="Publikasikan"><i class="fa-solid fa-paper-plane"></i></button>
                    </form>
                  <?php else: ?>
                    <form method="post" action="/news/unpublish/<?= (int) $post['id'] ?>" onsubmit="return confirmAction(this, 'Jadikan draft berita ini?')" class="inline">
                      <?php echo \App\Core\Csrf::field(); ?>
                      <button class="px-2 py-1 rounded-lg bg-amber-50 text-amber-600 hover:bg-amber-100" title="Jadikan draft"><i class="fa-solid fa-box-archive"></i></button>
                    </form>
                  <?php endif; ?>
                  <form method="post" action="/news/delete/<?= (int) $post['id'] ?>" onsubmit="return confirmAction(this, 'Apakah Anda yakin ingin menghapus data ini?')" class="inline">
                    <?php echo \App\Core\Csrf::field(); ?>
                    <button class="px-2 py-1 rounded-lg bg-red-50 text-red-600 hover:bg-red-100" title="Hapus"><i class="fa-solid fa-trash"></i></button>
                  </form>
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
        <?php if ($result['page'] > 1): ?><a href="/news?<?= $qs ?>" class="px-3 py-1.5 rounded-lg bg-slate-100 dark:bg-slate-800 font-bold">&laquo;</a><?php endif; ?>
        <?php if ($result['page'] < $result['pages']): ?><a href="/news?<?= $qsNext ?>" class="px-3 py-1.5 rounded-lg bg-slate-100 dark:bg-slate-800 font-bold">&raquo;</a><?php endif; ?>
      </div>
    </div>
  <?php endif; ?>
</div>
