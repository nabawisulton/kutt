<?php
/**
 * Detail berita publik + Open Graph + share + like + komentar.
 * Variables: post, tags, comments, related, seoTitle, seoDesc, seoImage, seoUrl.
 */
use App\Core\Csrf;

$post = $post ?? [];
$tags = $tags ?? [];
$comments = $comments ?? [];
$related = $related ?? [];
$shareUrl = $seoUrl ?? base_url('/berita/' . ($post['slug'] ?? ''));
$shareText = rawurlencode((string) ($post['title'] ?? ''));
?>
<!DOCTYPE html>
<html lang="id" class="h-full scroll-smooth">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?= e(\App\Core\Csrf::token()) ?>">
  <title><?= e($seoTitle ?? 'Berita') ?></title>
  <meta name="description" content="<?= e($seoDesc ?? '') ?>">

  <!-- Open Graph (WhatsApp, Facebook, Telegram, dsb.) -->
  <meta property="og:type" content="article">
  <meta property="og:site_name" content="KUTT SUKA MAKMUR">
  <meta property="og:title" content="<?= e($post['title'] ?? '') ?>">
  <meta property="og:description" content="<?= e($seoDesc ?? '') ?>">
  <meta property="og:url" content="<?= e($shareUrl) ?>">
  <meta property="og:image" content="<?= e($seoImage ?? base_url('public/img/og-default.jpg')) ?>">
  <meta property="og:image:width" content="1200">
  <meta property="og:image:height" content="630">
  <meta property="og:locale" content="id_ID">
  <meta property="article:published_time" content="<?= e((string) ($post['published_at'] ?? '')) ?>">

  <!-- Twitter Card -->
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?= e($post['title'] ?? '') ?>">
  <meta name="twitter:description" content="<?= e($seoDesc ?? '') ?>">
  <meta name="twitter:image" content="<?= e($seoImage ?? base_url('public/img/og-default.jpg')) ?>">

  <?php if (is_file(BASE_PATH . '/public/favicon.ico')): ?><link rel="icon" href="/favicon.ico"><?php endif; ?>

  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <script>
    tailwind.config = { theme: { extend: { colors: {
      brand: { 50: '#f0fdf4', 100: '#dcfce7', 500: '#2fae60', 600: '#0b7a3e', 700: '#085c2e', gold: '#ffc107' },
      'kutt-primary': 'var(--kutt-primary)'
    }, fontFamily: { sans: ['Inter', 'sans-serif'], display: ['Poppins', 'sans-serif'] } } } };
  </script>
  <style>:root { --kutt-primary: #0b7a3e; }</style>
</head>
<body class="min-h-screen bg-[#F7F9F8] font-sans text-slate-800 antialiased">

  <header class="bg-white/90 backdrop-blur-md sticky top-0 z-40 border-b border-brand-600/10">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between">
      <a href="/berita" class="flex items-center gap-2.5">
        <div class="w-9 h-9 rounded-xl bg-brand-600 text-white flex items-center justify-center shadow"><i class="fa-solid fa-cow"></i></div>
        <span class="font-display font-extrabold text-sm text-slate-900">KUTT SUKA MAKMUR</span>
      </a>
      <a href="/berita" class="text-xs font-bold text-brand-600 hover:underline"><i class="fa-solid fa-arrow-left mr-1"></i> Semua Berita</a>
    </div>
  </header>

  <main class="max-w-4xl mx-auto px-4 sm:px-6 py-8">
    <article class="glass-card rounded-2xl shadow-sm overflow-hidden">
      <?php if (!empty($post['image_path'])): ?>
        <img src="<?= e(news_image_src($post['image_path'])) ?>" class="w-full h-64 sm:h-80 object-cover" alt="<?= e($post['title']) ?>">
      <?php endif; ?>

      <div class="p-6 sm:p-8">
        <div class="flex flex-wrap items-center gap-2 text-[11px]">
          <span class="px-2.5 py-1 rounded-lg bg-brand-50 text-brand-600 font-bold uppercase"><?= e($post['category_name'] ?? 'Umum') ?></span>
          <span class="text-slate-400"><i class="fa-regular fa-calendar mr-1"></i><?= tanggal((string) ($post['published_at'] ?? $post['created_at']), true) ?></span>
          <span class="text-slate-400"><i class="fa-solid fa-user-pen mr-1"></i><?= e($post['author_name'] ?? 'Admin') ?></span>
        </div>

        <h1 class="font-display text-2xl sm:text-3xl font-extrabold text-slate-900 mt-3 leading-tight"><?= e($post['title']) ?></h1>

        <?php if ($tags !== []): ?>
          <div class="flex flex-wrap gap-1.5 mt-3">
            <?php foreach ($tags as $t): ?>
              <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-500 text-[10px] font-semibold">#<?= e($t['name']) ?></span>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <!-- Engagement bar -->
        <div class="flex flex-wrap items-center justify-between mt-5 pb-4 border-b border-slate-100">
          <div class="flex items-center space-x-4 text-xs text-slate-500">
            <span><i class="fa-solid fa-eye mr-1"></i><?= (int) ($post['views'] ?? 0) ?> dilihat</span>
            <span><i class="fa-solid fa-thumbs-up mr-1"></i><span id="like-count"><?= (int) $post['likes'] ?></span> suka</span>
            <span><i class="fa-solid fa-share-nodes mr-1"></i><span id="share-count"><?= (int) $post['shares'] ?></span> dibagikan</span>
            <span><i class="fa-solid fa-comments mr-1"></i><span id="comment-count"><?= (int) $post['comments_count'] ?></span> komentar</span>
          </div>
          <div class="flex items-center space-x-1.5 mt-3 sm:mt-0">
            <button id="btn-like" onclick="toggleLike()" class="px-3 py-2 rounded-xl bg-brand-50 text-brand-600 hover:bg-brand-100 text-xs font-bold transition">
              <i class="fa-regular fa-thumbs-up mr-1"></i>Suka
            </button>
            <a href="https://wa.me/?text=<?= $shareText ?>%20<?= rawurlencode($shareUrl) ?>" target="_blank" onclick="trackShare('whatsapp')" class="px-3 py-2 rounded-xl bg-emerald-500 text-white text-xs font-bold hover:opacity-90"><i class="fa-brands fa-whatsapp"></i></a>
            <a href="https://www.facebook.com/sharer/sharer.php?u=<?= rawurlencode($shareUrl) ?>" target="_blank" onclick="trackShare('facebook')" class="px-3 py-2 rounded-xl bg-blue-600 text-white text-xs font-bold hover:opacity-90"><i class="fa-brands fa-facebook-f"></i></a>
            <a href="https://t.me/share/url?url=<?= rawurlencode($shareUrl) ?>&text=<?= $shareText ?>" target="_blank" onclick="trackShare('telegram')" class="px-3 py-2 rounded-xl bg-sky-500 text-white text-xs font-bold hover:opacity-90"><i class="fa-brands fa-telegram"></i></a>
            <a href="https://twitter.com/intent/tweet?url=<?= rawurlencode($shareUrl) ?>&text=<?= $shareText ?>" target="_blank" onclick="trackShare('twitter')" class="px-3 py-2 rounded-xl bg-slate-800 text-white text-xs font-bold hover:opacity-90"><i class="fa-brands fa-x-twitter"></i></a>
            <button onclick="copyLink()" class="px-3 py-2 rounded-xl bg-slate-100 text-slate-600 text-xs font-bold hover:bg-slate-200" title="Salin tautan"><i class="fa-solid fa-link"></i></button>
          </div>
        </div>

        <!-- Body -->
        <div class="prose prose-sm max-w-none mt-6 text-sm leading-7 text-slate-700 news-body">
          <?php if (!empty($post['video_url'])): ?>
            <?php if (str_contains((string) $post['video_url'], 'uploads/')): ?>
              <video controls class="w-full rounded-xl mb-4 max-h-96"><source src="/<?= e($post['video_url']) ?>" type="video/mp4"></video>
            <?php else: ?>
              <?= \App\Support\HtmlSanitizer::embedVideo((string) $post['video_url']) ?>
            <?php endif; ?>
          <?php endif; ?>
          <?= \App\Support\HtmlSanitizer::clean((string) ($post['body'] ?? '')) ?>
        </div>
      </div>
    </article>

    <!-- Comments -->
    <section class="glass-card rounded-2xl shadow-sm p-6 mt-6">
      <h3 class="font-display text-sm font-bold text-slate-800"><i class="fa-solid fa-comments mr-2 text-brand-600"></i>Komentar (<?= count($comments) ?>)</h3>

      <form method="post" action="/berita/<?= (int) $post['id'] ?>/comment" class="mt-4 space-y-2">
        <?= Csrf::field() ?>
        <input type="hidden" name="slug" value="<?= e($post['slug']) ?>">
        <input type="text" name="name" required maxlength="120" placeholder="Nama Anda *"
          class="w-full px-3 py-2.5 text-xs border rounded-xl bg-white focus:ring-2 focus:ring-brand-600">
        <textarea name="body" required maxlength="1000" rows="3" placeholder="Tulis komentar yang santun dan membangun... *"
          class="w-full px-3 py-2.5 text-xs border rounded-xl bg-white focus:ring-2 focus:ring-brand-600"></textarea>
        <button class="px-5 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow">Kirim Komentar</button>
      </form>

      <div class="mt-6 space-y-3">
        <?php if ($comments === []): ?>
          <p class="text-xs text-slate-400">Belum ada komentar. Jadilah yang pertama!</p>
        <?php else: ?>
          <?php foreach ($comments as $c): ?>
            <div class="flex items-start space-x-3 p-3 rounded-xl bg-slate-50">
              <div class="w-8 h-8 rounded-full bg-brand-600 text-white flex items-center justify-center text-[10px] font-bold shrink-0"><?= e(strtoupper(mb_substr($c['name'], 0, 1))) ?></div>
              <div class="min-w-0">
                <p class="text-xs font-bold text-slate-700"><?= e($c['name']) ?> <span class="ml-1 text-[10px] font-normal text-slate-400"><?= tanggal((string) $c['created_at'], true) ?></span></p>
                <p class="text-xs text-slate-600 mt-0.5 break-words"><?= e($c['body']) ?></p>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </section>

    <!-- Related -->
    <?php if ($related !== []): ?>
      <section class="mt-8">
        <h3 class="font-display text-sm font-bold text-slate-800 mb-3">Berita Lainnya</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <?php foreach (array_slice($related, 0, 4) as $r): ?>
            <?php if ((int) $r['id'] === (int) $post['id']) continue; ?>
            <a href="/berita/<?= e($r['slug']) ?>" class="glass-card rounded-xl p-3 flex items-center space-x-3 hover:shadow-md transition">
              <?php if (!empty($r['image_path'])): ?>
                <img src="<?= e(news_image_src($r['image_path'])) ?>" class="w-16 h-16 rounded-lg object-cover" alt="">
              <?php else: ?>
                <div class="w-16 h-16 rounded-lg bg-brand-50 flex items-center justify-center text-brand-400"><i class="fa-regular fa-newspaper"></i></div>
              <?php endif; ?>
              <div class="min-w-0">
                <p class="text-xs font-bold text-slate-700 line-clamp-2"><?= e($r['title']) ?></p>
                <p class="text-[10px] text-slate-400 mt-1"><i class="fa-solid fa-eye mr-1"></i><?= (int) $r['views'] ?> · <i class="fa-solid fa-thumbs-up mr-1"></i><?= (int) $r['likes'] ?></p>
              </div>
            </a>
          <?php endforeach; ?>
        </div>
      </section>
    <?php endif; ?>
  </main>

  <footer class="bg-brand-700 text-white/80 text-center text-xs py-6">© <?= date('Y') ?> KUTT Suka Makmur Grati. All Rights Reserved.</footer>

  <script>
    // Token CSRF untuk semua XHR engagement (meta di header layout publik).
    var CSRF_TOKEN = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

    function toggleLike() {
      var xhr = new XMLHttpRequest();
      xhr.open('POST', '/berita/<?= (int) $post['id'] ?>/like');
      xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
      xhr.setRequestHeader('X-CSRF-Token', CSRF_TOKEN);
      xhr.onload = function () {
        if (xhr.status !== 200) { alertLikeFail(); return; }
        var res = JSON.parse(xhr.responseText);
        document.getElementById('like-count').innerText = res.likes;
        var btn = document.getElementById('btn-like');
        var icon = btn.querySelector('i');
        icon.className = res.liked ? 'fa-solid fa-thumbs-up mr-1' : 'fa-regular fa-thumbs-up mr-1';
        btn.classList.toggle('bg-brand-600', res.liked);
        btn.classList.toggle('text-white', res.liked);
        btn.classList.toggle('bg-brand-50', !res.liked);
        btn.classList.toggle('text-brand-600', !res.liked);
      };
      xhr.send();
    }
    function alertLikeFail() { showToast('Terjadi kesalahan. Silakan coba kembali.', 'error'); }

    function trackShare(platform) {
      var xhr = new XMLHttpRequest();
      xhr.open('POST', '/berita/<?= (int) $post['id'] ?>/share');
      xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
      xhr.setRequestHeader('X-CSRF-Token', CSRF_TOKEN);
      xhr.onload = function () {
        if (xhr.status === 204) {
          var el = document.getElementById('share-count');
          el.innerText = parseInt(el.innerText) + 1;
        }
      };
      xhr.send('platform=' + encodeURIComponent(platform));
    }

    function copyLink() {
      navigator.clipboard.writeText(<?= json_encode($shareUrl) ?>).then(function () {
        showToast('Tautan berhasil disalin.', 'success');
      });
    }
  </script>
</body>
</html>
