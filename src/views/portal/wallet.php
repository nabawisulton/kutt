<?php

/**
 * Portal Anggota — Saldo & Transaksi Saya (wallet).
 * Variables: member, balance, hasPin, ledger, requests, unread.
 */

use App\Core\Csrf;

$member   = $member ?? [];
$balance  = (float) ($balance ?? 0);
$hasPin   = (bool) ($hasPin ?? false);
$ledger   = $ledger ?? [];
$requests = $requests ?? [];

$statusColor = [
    'PENDING'  => 'bg-amber-100 text-amber-700',
    'APPROVED' => 'bg-emerald-100 text-emerald-700',
    'REJECTED' => 'bg-red-100 text-red-700',
];

$typeBadge = [
    'TOPUP'       => 'bg-emerald-100 text-emerald-700',
    'PEMBAYARAN'  => 'bg-amber-100 text-amber-700',
    'REFUND'      => 'bg-sky-100 text-sky-700',
    'PENYESUAIAN' => 'bg-slate-200 text-slate-600',
];
?>
<div class="space-y-5">

  <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <div class="md:col-span-1 rounded-2xl p-5 bg-gradient-to-br from-brand-700 to-brand-600 text-white shadow-lg">
      <p class="text-[10px] uppercase tracking-widest opacity-80">Saldo Belanja Anggota</p>
      <p class="font-display font-extrabold text-2xl mt-1"><?= rupiah($balance) ?></p>
      <p class="text-[11px] opacity-85 mt-2"><?= e((string) $member['full_name']) ?> · <?= e((string) $member['member_no']) ?></p>
      <div class="mt-3 pt-3 border-t border-white/20 flex items-center justify-between text-[11px]">
        <span class="opacity-85">Status PIN transaksi</span>
        <span class="font-bold <?= $hasPin ? 'text-gold' : 'text-red-200' ?>"><?= $hasPin ? 'AKTIF' : 'BELUM DIBUAT' ?></span>
      </div>
    </div>

    <!-- Buat / Ubah PIN -->
    <div class="glass-card rounded-2xl p-5 shadow-sm md:col-span-2">
      <h3 class="font-display text-sm font-bold text-slate-800 dark:text-slate-100 mb-1"><i class="fa-solid fa-key mr-1 text-brand-600"></i><?= $hasPin ? 'Ubah' : 'Buat' ?> PIN Transaksi</h3>
      <p class="text-[10px] text-slate-400 mb-3">PIN 6 digit dipakai untuk cek saldo, top up, dan pembayaran dengan saldo (scan QR kartu / POS). PIN disimpan ter-enkripsi (hash) dan tidak pernah tampil di mana pun.</p>
      <form method="post" action="/portal/wallet/pin" class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-end" onsubmit="return confirmAction(this, 'Simpan PIN transaksi baru?')">
        <?= Csrf::field() ?>
        <?php if ($hasPin): ?>
          <div>
            <label class="text-[10px] font-bold text-slate-500 uppercase">PIN Saat Ini</label>
            <input type="password" name="current_pin" required maxlength="6" minlength="6" inputmode="numeric" pattern="\d{6}" autocomplete="off"
              class="w-full mt-1 px-3 py-2 text-center tracking-[0.4em] text-sm border rounded-xl bg-white dark:bg-slate-800">
          </div>
        <?php endif; ?>
        <div>
          <label class="text-[10px] font-bold text-slate-500 uppercase">PIN Baru (6 digit)</label>
          <input type="password" name="new_pin" required maxlength="6" minlength="6" inputmode="numeric" pattern="\d{6}" autocomplete="off"
            class="w-full mt-1 px-3 py-2 text-center tracking-[0.4em] text-sm border rounded-xl bg-white dark:bg-slate-800">
        </div>
        <div class="flex gap-2">
          <div class="flex-1">
            <label class="text-[10px] font-bold text-slate-500 uppercase">Ulangi PIN</label>
            <input type="password" name="confirm_pin" required maxlength="6" minlength="6" inputmode="numeric" pattern="\d{6}" autocomplete="off"
              class="w-full mt-1 px-3 py-2 text-center tracking-[0.4em] text-sm border rounded-xl bg-white dark:bg-slate-800">
          </div>
          <button type="submit" class="px-4 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow transition whitespace-nowrap">Simpan PIN</button>
        </div>
      </form>
      <p class="text-[9px] text-slate-400 mt-2"><i class="fa-solid fa-circle-info mr-0.5"></i>Hindari PIN berulang (mis. 111111) atau 123456.</p>
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
    <!-- Ajukan top up -->
    <div class="glass-card rounded-2xl p-5 shadow-sm">
      <h3 class="font-display text-sm font-bold text-slate-800 dark:text-slate-100 mb-1"><i class="fa-solid fa-circle-plus mr-1 text-amber-500"></i>Ajukan Top Up</h3>
      <p class="text-[10px] text-slate-400 mb-3">Saldo bertambah setelah admin/kasir memproses pengajuan (uang diterima kas).</p>
      <form method="post" action="/portal/wallet/topup" class="space-y-3" onsubmit="return confirmAction(this, 'Kirim pengajuan top up ke admin?')">
        <?= Csrf::field() ?>
        <div>
          <label class="text-[10px] font-bold text-slate-500 uppercase">Nominal (Rp)</label>
          <input type="number" name="amount" required min="1000" max="100000000" step="1000" placeholder="100000"
            class="w-full mt-1 px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800">
        </div>
        <div>
          <label class="text-[10px] font-bold text-slate-500 uppercase">Keterangan (opsional)</label>
          <input type="text" name="note" maxlength="255" placeholder="mis. top up tunai di kantor"
            class="w-full mt-1 px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800">
        </div>
        <div>
          <label class="text-[10px] font-bold text-slate-500 uppercase">PIN Transaksi</label>
          <input type="password" name="wallet_pin" required maxlength="6" minlength="6" inputmode="numeric" pattern="\d{6}" autocomplete="off"
            class="w-full mt-1 px-3 py-2 text-center tracking-[0.4em] text-sm border rounded-xl bg-white dark:bg-slate-800">
        </div>
        <button type="submit" class="w-full py-2.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold shadow transition">
          <i class="fa-solid fa-paper-plane mr-1"></i>Kirim Pengajuan
        </button>
      </form>

      <?php if ($requests !== []): ?>
        <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-700">
          <p class="text-[10px] font-bold text-slate-500 uppercase mb-2">Pengajuan Terakhir</p>
          <div class="space-y-1.5 text-[11px]">
            <?php foreach ($requests as $r): ?>
              <div class="flex items-center justify-between gap-2">
                <span class="font-mono text-[10px] text-slate-500"><?= e((string) $r['request_no']) ?></span>
                <span class="font-bold text-slate-700 dark:text-slate-200"><?= rupiah((float) $r['amount']) ?></span>
                <span class="px-1.5 py-0.5 rounded text-[9px] font-bold <?= $statusColor[(string) $r['status']] ?? 'bg-slate-100 text-slate-600' ?>"><?= e((string) $r['status']) ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>
    </div>

    <!-- Riwayat saldo -->
    <div class="glass-card rounded-2xl p-5 shadow-sm lg:col-span-2 overflow-hidden">
      <h3 class="font-display text-sm font-bold text-slate-800 dark:text-slate-100 mb-3"><i class="fa-solid fa-clock-rotate-left mr-1 text-brand-600"></i>Riwayat Mutasi Saldo</h3>
      <div class="overflow-x-auto">
        <table class="w-full text-xs text-left">
          <thead class="bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-300 uppercase text-[9px]">
            <tr>
              <th class="p-2 rounded-l-lg">Tanggal</th>
              <th class="p-2">No. Transaksi</th>
              <th class="p-2">Jenis</th>
              <th class="p-2">Keterangan</th>
              <th class="p-2 text-right">Debit</th>
              <th class="p-2 text-right">Kredit</th>
              <th class="p-2 text-right rounded-r-lg">Saldo</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
            <?php if ($ledger === []): ?>
              <tr><td colspan="7" class="p-4 text-center text-slate-400">Belum ada mutasi saldo.</td></tr>
            <?php endif; ?>
            <?php foreach ($ledger as $w): ?>
              <?php $amt = (float) $w['amount']; ?>
              <tr>
                <td class="p-2 text-slate-500 whitespace-nowrap"><?= e(tanggal((string) $w['created_at'])) ?></td>
                <td class="p-2 font-mono text-[10px] text-slate-500"><?= e((string) $w['transaction_no']) ?></td>
                <td class="p-2"><span class="px-1.5 py-0.5 rounded text-[9px] font-bold <?= $typeBadge[(string) $w['type']] ?? 'bg-slate-100 text-slate-600' ?>"><?= e((string) $w['type']) ?></span></td>
                <td class="p-2 text-slate-600 dark:text-slate-300"><?= e((string) ($w['description'] ?? '')) ?></td>
                <td class="p-2 text-right font-semibold text-red-600"><?= $amt < 0 ? rupiah(-$amt) : '-' ?></td>
                <td class="p-2 text-right font-semibold text-emerald-600"><?= $amt > 0 ? rupiah($amt) : '-' ?></td>
                <td class="p-2 text-right font-bold text-slate-700 dark:text-slate-200"><?= rupiah((float) $w['balance_after']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <p class="text-[9px] text-slate-400 mt-3"><i class="fa-solid fa-qrcode mr-0.5"></i>Tip: scan QR di kartu anggota Anda untuk cek saldo cepat di halaman verifikasi (tetap perlu PIN).</p>
    </div>
  </div>
</div>
