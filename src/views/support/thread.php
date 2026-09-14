<?php

/**
 * Thread pesan satu anggota (sisi admin). Variables: member, thread, finance.
 */

use App\Core\Csrf;

$member  = $member ?? [];
$thread  = $thread ?? [];
$finance = $finance ?? [];
?>
<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
  <!-- Thread -->
  <div class="lg:col-span-2 glass-card rounded-2xl p-5 shadow-sm flex flex-col h-[70vh]">
    <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
      <div class="flex items-center gap-2.5">
        <div class="w-9 h-9 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center font-bold overflow-hidden">
          <?php if (!empty($member['photo_path'])): ?>
            <img src="/<?= e((string) $member['photo_path']) ?>" class="w-full h-full object-cover" alt="">
          <?php else: ?>
            <?= e(mb_strtoupper(mb_substr((string) $member['full_name'], 0, 1))) ?>
          <?php endif; ?>
        </div>
        <div>
          <p class="font-display text-sm font-bold text-slate-800 dark:text-slate-100"><?= e((string) $member['full_name']) ?></p>
          <p class="text-[10px] text-slate-400 font-mono"><?= e((string) $member['member_no']) ?> · <?= e((string) ($member['phone'] ?? '-')) ?></p>
        </div>
      </div>
      <a href="/support" class="text-[11px] font-bold text-slate-400 hover:text-brand-600"><i class="fa-solid fa-arrow-left mr-1"></i>Inbox</a>
    </div>

    <div id="chat-scroll" class="flex-1 overflow-y-auto custom-scrollbar py-4 space-y-2.5">
      <?php if ($thread === []): ?>
        <p class="text-center text-xs text-slate-400 py-10">Belum ada pesan.</p>
      <?php endif; ?>
      <?php foreach ($thread as $msg): ?>
        <?php $isAdmin = ($msg['sender_role'] ?? '') === 'ADMIN'; ?>
        <div class="flex <?= $isAdmin ? 'justify-end' : 'justify-start' ?>">
          <div class="max-w-[80%] px-3.5 py-2.5 rounded-2xl text-xs leading-relaxed <?= $isAdmin
              ? 'bg-brand-600 text-white rounded-br-sm'
              : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 rounded-bl-sm' ?>">
            <p class="whitespace-pre-wrap break-words"><?= e((string) $msg['body']) ?></p>
            <p class="text-[9px] mt-1 opacity-70"><?= e((string) $msg['sender_name']) ?> · <?= tanggal((string) $msg['created_at']) ?> <?= substr((string) $msg['created_at'], 11, 5) ?></p>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <form method="post" action="/support/<?= (int) $member['id'] ?>/reply" class="pt-3 border-t border-slate-100 dark:border-slate-800 flex items-end gap-2">
      <?= Csrf::field() ?>
      <textarea name="body" rows="2" required maxlength="2000" placeholder="Tulis balasan untuk anggota..."
        class="flex-1 px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800 resize-none"></textarea>
      <button class="px-4 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow"><i class="fa-solid fa-paper-plane"></i></button>
    </form>
  </div>

  <!-- Ringkasan keuangan anggota (konteks admin) -->
  <div class="space-y-4">
    <div class="glass-card rounded-2xl p-5 shadow-sm">
      <h3 class="font-display text-sm font-bold text-slate-800 dark:text-slate-100 mb-3">Ringkasan Anggota</h3>
      <div class="space-y-1.5 text-xs">
        <div class="flex justify-between"><span class="text-slate-500">Status</span><span class="font-bold"><?= e((string) $member['status']) ?></span></div>
        <div class="flex justify-between"><span class="text-slate-500">Jabatan</span><span class="font-bold"><?= e(trim((string) ($member['jabatan_internal'] ?? '')) !== '' ? $member['jabatan_internal'] : 'Anggota') ?></span></div>
        <div class="flex justify-between"><span class="text-slate-500">Total Simpanan</span><span class="font-bold text-brand-600"><?= rupiah($finance['total_simpanan'] ?? 0) ?></span></div>
        <div class="flex justify-between"><span class="text-slate-500">Sisa Pinjaman</span><span class="font-bold"><?= rupiah($finance['sisa_pinjaman'] ?? 0) ?></span></div>
        <div class="flex justify-between"><span class="text-slate-500">SHU</span><span class="font-bold"><?= rupiah($finance['shu_total'] ?? 0) ?></span></div>
      </div>
      <div class="mt-3 pt-3 border-t border-slate-100 dark:border-slate-800 flex flex-col gap-1.5">
        <a href="/members/<?= (int) $member['id'] ?>/card" class="text-[11px] font-bold text-brand-600 hover:underline"><i class="fa-solid fa-id-card mr-1"></i>Kartu &amp; QR Anggota</a>
        <a href="/pinjaman" class="text-[11px] font-bold text-slate-500 hover:text-brand-600"><i class="fa-solid fa-hand-holding-dollar mr-1"></i>Kelola Pinjaman</a>
      </div>
    </div>
  </div>
</div>

<script>
  var sc = document.getElementById('chat-scroll');
  if (sc) { sc.scrollTop = sc.scrollHeight; }
</script>
