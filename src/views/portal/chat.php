<?php

/**
 * Chat anggota <-> admin. Variables: member, thread, unread.
 */

use App\Core\Csrf;

$member = $member ?? [];
$thread = $thread ?? [];
$unread = (int) ($unread ?? 0);
?>
<div class="glass-card rounded-2xl p-5 shadow-sm max-w-3xl mx-auto flex flex-col h-[70vh]">
  <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
    <div class="flex items-center gap-2.5">
      <div class="w-9 h-9 rounded-xl bg-brand-600 text-white flex items-center justify-center"><i class="fa-solid fa-headset"></i></div>
      <div>
        <p class="font-display text-sm font-bold text-slate-800 dark:text-slate-100">Pengurus KUTT SUKA MAKMUR</p>
        <p class="text-[10px] text-slate-400">Biasanya membalas pada hari kerja</p>
      </div>
    </div>
    <a href="/portal" class="text-[11px] font-bold text-slate-400 hover:text-brand-600"><i class="fa-solid fa-arrow-left mr-1"></i>Portal</a>
  </div>

  <!-- Thread -->
  <div id="chat-scroll" class="flex-1 overflow-y-auto custom-scrollbar py-4 space-y-2.5">
    <?php if ($thread === []): ?>
      <div class="text-center text-xs text-slate-400 py-10">
        <i class="fa-regular fa-comment-dots text-4xl block mb-2 text-slate-200"></i>
        Belum ada percakapan. Kirim pesan pertama Anda di bawah.
      </div>
    <?php endif; ?>
    <?php foreach ($thread as $msg): ?>
      <?php $isAdmin = ($msg['sender_role'] ?? '') === 'ADMIN'; ?>
      <div class="flex <?= $isAdmin ? 'justify-start' : 'justify-end' ?>">
        <div class="max-w-[80%] px-3.5 py-2.5 rounded-2xl text-xs leading-relaxed <?= $isAdmin
            ? 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 rounded-bl-sm'
            : 'bg-brand-600 text-white rounded-br-sm' ?>">
          <p class="whitespace-pre-wrap break-words"><?= e((string) $msg['body']) ?></p>
          <p class="text-[9px] mt-1 opacity-70"><?= e((string) $msg['sender_name']) ?> · <?= tanggal((string) $msg['created_at']) ?> <?= substr((string) $msg['created_at'], 11, 5) ?></p>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Composer -->
  <form method="post" action="/portal/chat" class="pt-3 border-t border-slate-100 dark:border-slate-800 flex items-end gap-2">
    <?= Csrf::field() ?>
    <textarea name="body" rows="2" required maxlength="2000" placeholder="Tulis pesan untuk admin..."
      class="flex-1 px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800 resize-none"></textarea>
    <button class="px-4 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow"><i class="fa-solid fa-paper-plane"></i></button>
  </form>
</div>

<script>
  // Tandai dibaca saat halaman dibuka: fetch POST kecil? Cukup reload-free:
  // badge dihitung server saat render, jadi setelah buka halaman ini notif dianggap terbaca
  // di request berikutnya (markReadByMember dipanggil controller chat()).
  var sc = document.getElementById('chat-scroll');
  if (sc) { sc.scrollTop = sc.scrollHeight; }
</script>
