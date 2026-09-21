<?php

/**
 * Halaman verifikasi publik setelah scan QR kartu anggota.
 * Variables: member, finance (null saat tanpa permission), transactions,
 *            canSeeFinance, cardBg, cardToken, hasPin,
 *            showBalance/walletBalance/walletLedger, showHistory/walletLedger.
 * QR/token HANYA identifikasi — saldo & aksi finansial wajib PIN 6 digit.
 */

use App\Support\PortalLayout;

$member   = $member ?? [];
$finance  = $finance ?? null;
$transactions = $transactions ?? [];
$canSeeFinance = (bool) ($canSeeFinance ?? false);
$cardToken = (string) ($cardToken ?? '');
$hasPin   = (bool) ($hasPin ?? false);
$showBalance = (bool) ($showBalance ?? false);
$showHistory = (bool) ($showHistory ?? false);
$walletBalance = (float) ($walletBalance ?? 0);
$walletLedger = $walletLedger ?? [];
$flash = $flash ?? null;
$brand = $brand ?? \App\Models\Setting::all();

$baseUrl = base_url();
$verifyBase = $baseUrl . '/anggota/verify/' . rawurlencode($cardToken);

$navItems = [
    ['path' => '/', 'label' => 'Beranda'],
    ['path' => '/berita', 'label' => 'Berita & Kegiatan'],
    ['path' => '/marketplace', 'label' => 'Belanja'],
    ['path' => '/login', 'label' => 'Portal Anggota'],
];

/** Badge warna untuk jenis mutasi saldo. */
function wallet_type_badge(string $type): string
{
    return match ($type) {
        'TOPUP'       => 'bg-emerald-100 text-emerald-700',
        'PEMBAYARAN'  => 'bg-amber-100 text-amber-700',
        'REFUND'      => 'bg-sky-100 text-sky-700',
        'PENYESUAIAN' => 'bg-slate-200 text-slate-600',
        default       => 'bg-slate-100 text-slate-600',
    };
}

PortalLayout::header($brand, $navItems, '/anggota/verify');
?>
<main class="pt-28 pb-16 px-4">
  <div class="max-w-3xl mx-auto space-y-5">

    <?php if ($flash !== null): ?>
      <div class="rounded-2xl p-4 text-xs font-semibold shadow-sm border <?= $flash['type'] === 'error'
          ? 'bg-red-50 border-red-200 text-red-700'
          : ($flash['type'] === 'info' ? 'bg-sky-50 border-sky-200 text-sky-700' : 'bg-emerald-50 border-emerald-200 text-emerald-700') ?>">
        <i class="fa-solid <?= $flash['type'] === 'error' ? 'fa-circle-exclamation' : 'fa-circle-check' ?> mr-1"></i>
        <?= e((string) $flash['message']) ?>
      </div>
    <?php endif; ?>

    <div class="glass-card rounded-2xl p-6 shadow-sm">
      <div class="flex items-start justify-between gap-4">
        <div class="flex items-center gap-4">
          <?php if (!empty($member['photo_path'])): ?>
            <img src="<?= e($baseUrl . '/' . $member['photo_path']) ?>" alt="Foto anggota"
              class="w-[88px] h-[132px] object-cover rounded-xl border-4 border-white shadow-lg">
          <?php else: ?>
            <div class="w-[88px] h-[132px] rounded-xl border-4 border-white shadow-lg bg-brand-50 flex flex-col items-center justify-center text-brand-600">
              <i class="fa-solid fa-user text-2xl"></i>
              <span class="text-[9px] mt-1">Foto 4x6</span>
            </div>
          <?php endif; ?>
          <div>
            <p class="text-[10px] font-bold uppercase tracking-wider text-brand-600">Kartu Anggota Terverifikasi</p>
            <h1 class="font-display text-xl font-bold text-slate-800"><?= e((string) $member['full_name']) ?></h1>
            <p class="text-xs text-slate-500 font-mono mt-0.5"><?= e((string) $member['member_no']) ?></p>
            <span class="inline-block mt-2 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700"><?= e((string) $member['status']) ?></span>
          </div>
        </div>
        <div class="text-right">
          <div class="w-10 h-10 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center ml-auto"><i class="fa-solid fa-check"></i></div>
          <p class="text-[9px] text-slate-400 mt-1"><?= e((string) ($cardBg['type'] ?? '')) ?></p>
        </div>
      </div>

      <div class="grid grid-cols-2 md:grid-cols-3 gap-3 mt-5 text-xs">
        <div><p class="text-[10px] uppercase text-slate-400 font-bold">NIK</p><p class="font-mono text-slate-700"><?= e($member['nik'] ?? '-') ?></p></div>
        <div><p class="text-[10px] uppercase text-slate-400 font-bold">Alamat</p><p class="text-slate-700"><?= e($member['address'] ?? '-') ?></p></div>
        <div><p class="text-[10px] uppercase text-slate-400 font-bold">Kelompok Tani</p><p class="text-slate-700"><?= e($member['group_name'] ?? '-') ?></p></div>
        <div><p class="text-[10px] uppercase text-slate-400 font-bold">Pekerjaan</p><p class="text-slate-700"><?= e($member['occupation'] ?? '-') ?></p></div>
        <div><p class="text-[10px] uppercase text-slate-400 font-bold">Jabatan di Koperasi</p><p class="text-slate-700"><?= e(trim((string) ($member['jabatan_internal'] ?? '')) !== '' ? $member['jabatan_internal'] : 'Anggota') ?></p></div>
        <div><p class="text-[10px] uppercase text-slate-400 font-bold">Hubungan Eksternal</p><p class="text-slate-700"><?php
            $jx = trim((string) ($member['jabatan_eksternal'] ?? ''));
            if ($jx !== '') {
                echo e($jx);
            } elseif (($member['relasi_eksternal'] ?? 'TIDAK_ADA') !== 'TIDAK_ADA') {
                echo e((string) $member['relasi_eksternal']);
            } else {
                echo '-';
            }
        ?></p></div>
        <div><p class="text-[10px] uppercase text-slate-400 font-bold">Bergabung</p><p class="text-slate-700"><?= e(tanggal((string) ($member['joined_at'] ?? ''))) ?></p></div>
        <div><p class="text-[10px] uppercase text-slate-400 font-bold">NIA</p><p class="font-mono text-slate-700"><?= e($member['nia'] ?? '-') ?></p></div>
      </div>
    </div>

    <!-- ================================================================
         SALDO & TRANSAKSI ANGGOTA — terlindungi PIN 6 digit.
         QR hanya identifikasi; saldo tidak pernah tampil hanya dengan
         memegang URL/token.
         ================================================================ -->
    <div class="glass-card rounded-2xl p-6 shadow-sm">
      <h2 class="font-display text-sm font-bold text-slate-800 mb-1"><i class="fa-solid fa-coins mr-1 text-amber-500"></i>Saldo Belanja Anggota</h2>
      <p class="text-[10px] text-slate-400 mb-4">Cek saldo, top up, dan riwayat — semua memerlukan <b>PIN transaksi 6 digit</b>.</p>

      <?php if ($showBalance): ?>
        <div class="rounded-2xl bg-gradient-to-br from-brand-600 to-brand-700 text-white p-6 mb-4 shadow-lg">
          <p class="text-[10px] font-bold uppercase tracking-widest opacity-80">Saldo Anda</p>
          <p class="font-display text-3xl font-bold mt-1"><?= rupiah($walletBalance) ?></p>
          <p class="text-[10px] opacity-75 mt-2"><i class="fa-solid fa-user-check mr-1"></i><?= e((string) $member['full_name']) ?> &middot; <?= e((string) $member['member_no']) ?></p>
        </div>
      <?php endif; ?>

      <?php if ($showBalance || $showHistory): ?>
        <div class="overflow-x-auto">
          <table class="w-full text-xs text-left">
            <thead class="bg-slate-100 text-slate-500 uppercase text-[9px]">
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
            <tbody class="divide-y divide-slate-100">
              <?php if ($walletLedger === []): ?>
                <tr><td colspan="7" class="p-4 text-center text-slate-400">Belum ada mutasi saldo.</td></tr>
              <?php endif; ?>
              <?php foreach ($walletLedger as $w): ?>
                <?php $amt = (float) $w['amount']; ?>
                <tr>
                  <td class="p-2 text-slate-500 whitespace-nowrap"><?= e(tanggal((string) $w['created_at'])) ?></td>
                  <td class="p-2 font-mono text-[10px] text-slate-500"><?= e((string) $w['transaction_no']) ?></td>
                  <td class="p-2"><span class="px-1.5 py-0.5 rounded text-[9px] font-bold <?= wallet_type_badge((string) $w['type']) ?>"><?= e((string) $w['type']) ?></span></td>
                  <td class="p-2 text-slate-600"><?= e((string) ($w['description'] ?? '')) ?></td>
                  <td class="p-2 text-right font-semibold text-red-600"><?= $amt < 0 ? rupiah(-$amt) : '-' ?></td>
                  <td class="p-2 text-right font-semibold text-emerald-600"><?= $amt > 0 ? rupiah($amt) : '-' ?></td>
                  <td class="p-2 text-right font-bold text-slate-700"><?= rupiah((float) $w['balance_after']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <form method="post" action="<?= e($verifyBase) ?>" class="mt-3">
          <?= \App\Core\Csrf::field() ?>
          <button type="submit" class="text-[10px] text-brand-600 hover:underline"><i class="fa-solid fa-lock mr-0.5"></i>Tutup (kembali ke menu PIN)</button>
        </form>
      <?php endif; ?>

      <?php if (!$showBalance && !$showHistory): ?>
        <?php if ($hasPin): ?>
          <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <button type="button" onclick="document.getElementById('pinBox').dataset.action='saldo';togglePinBox()"
              class="px-3 py-3 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow transition"><i class="fa-solid fa-wallet mr-1"></i>CEK SALDO</button>
            <button type="button" onclick="document.getElementById('pinBox').dataset.action='topup';togglePinBox()"
              class="px-3 py-3 rounded-xl bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold shadow transition"><i class="fa-solid fa-plus mr-1"></i>TOP UP</button>
            <a href="<?= e($baseUrl) ?>/marketplace"
              class="px-3 py-3 rounded-xl bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold shadow transition text-center"><i class="fa-solid fa-cart-shopping mr-1"></i>TRANSAKSI</a>
            <button type="button" onclick="document.getElementById('pinBox').dataset.action='riwayat';togglePinBox()"
              class="px-3 py-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold shadow transition"><i class="fa-solid fa-clock-rotate-left mr-1"></i>RIWAYAT</button>
          </div>

          <div id="pinBox" data-action="saldo" class="hidden mt-4 rounded-2xl border border-brand-200 bg-brand-50/60 p-5">
            <p class="text-xs font-bold text-slate-700 mb-1"><i class="fa-solid fa-keyboard mr-1 text-brand-600"></i>Masukkan PIN Transaksi (6 digit)</p>
            <p class="text-[10px] text-slate-500 mb-3" id="pinHint">Untuk menampilkan saldo Anda.</p>
            <form method="post" action="<?= e($verifyBase) ?>/saldo" class="flex flex-col sm:flex-row gap-2 sm:items-center">
              <?= \App\Core\Csrf::field() ?>
              <input type="password" name="wallet_pin" required maxlength="6" minlength="6" inputmode="numeric" pattern="\d{6}"
                autocomplete="off" placeholder="&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;"
                class="w-full sm:w-40 px-4 py-2.5 text-center text-lg tracking-[0.5em] font-bold rounded-xl border border-slate-300 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none">
              <button type="submit" class="px-5 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow transition whitespace-nowrap">
                <i class="fa-solid fa-arrow-right-to-bracket mr-1"></i>Verifikasi
              </button>
            </form>
            <p class="text-[9px] text-slate-400 mt-2"><i class="fa-solid fa-shield-halved mr-0.5"></i>Percobaan PIN salah dibatasi 5 kali, lalu PIN terkunci sementara.</p>
          </div>

          <div id="topupBox" class="hidden mt-4 rounded-2xl border border-amber-200 bg-amber-50/60 p-5">
            <p class="text-xs font-bold text-slate-700 mb-1"><i class="fa-solid fa-circle-plus mr-1 text-amber-500"></i>Ajukan Top Up Saldo</p>
            <p class="text-[10px] text-slate-500 mb-3">Saldo bertambah setelah admin/kasir memproses pengajuan ini (uang diterima kas).</p>
            <form method="post" action="<?= e($verifyBase) ?>/topup" class="grid grid-cols-1 sm:grid-cols-[1fr_1fr_auto_auto] gap-2 items-center">
              <?= \App\Core\Csrf::field() ?>
              <input type="number" name="amount" required min="1000" max="100000000" step="1000" placeholder="Nominal (min 1.000)"
                class="px-4 py-2.5 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-amber-500 outline-none">
              <input type="text" name="note" maxlength="255" placeholder="Keterangan (opsional)"
                class="px-4 py-2.5 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-amber-500 outline-none">
              <input type="password" name="wallet_pin" required maxlength="6" minlength="6" inputmode="numeric" pattern="\d{6}"
                autocomplete="off" placeholder="PIN 6 digit"
                class="px-4 py-2.5 text-sm tracking-[0.4em] text-center font-bold rounded-xl border border-slate-300 focus:ring-2 focus:ring-amber-500 outline-none">
              <button type="submit" class="px-5 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold shadow transition whitespace-nowrap">
                <i class="fa-solid fa-paper-plane mr-1"></i>Ajukan
              </button>
            </form>
          </div>

          <script>
            function togglePinBox() {
              var box = document.getElementById('pinBox');
              var tBox = document.getElementById('topupBox');
              var hint = document.getElementById('pinHint');
              var action = box.dataset.action;
              tBox.classList.add('hidden');
              if (action === 'topup') { tBox.classList.remove('hidden'); box.classList.add('hidden'); return; }
              hint.textContent = action === 'riwayat'
                ? 'Untuk menampilkan riwayat mutasi saldo Anda.'
                : 'Untuk menampilkan saldo Anda.';
              box.querySelector('form').setAttribute('action', '<?= e($verifyBase) ?>/' + (action === 'riwayat' ? 'riwayat' : 'saldo'));
              box.classList.toggle('hidden');
            }
          </script>
        <?php else: ?>
          <div class="rounded-2xl bg-slate-100 p-5 text-center">
            <i class="fa-solid fa-key text-slate-300 text-2xl"></i>
            <p class="text-xs text-slate-600 font-semibold mt-2">PIN belum dibuat. Silakan buat PIN transaksi terlebih dahulu.</p>
            <p class="text-[10px] text-slate-400 mt-1">Buat PIN melalui <b>Portal Anggota</b> setelah login, lalu kembali ke halaman ini.</p>
            <a href="<?= e($baseUrl) ?>/portal" class="inline-block mt-3 px-4 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow">Buka Portal Anggota</a>
          </div>
        <?php endif; ?>
      <?php endif; ?>
    </div>

    <?php if ($canSeeFinance && $finance !== null): ?>
      <div class="glass-card rounded-2xl p-6 shadow-sm">
        <h2 class="font-display text-sm font-bold text-slate-800 mb-4"><i class="fa-solid fa-wallet mr-1 text-brand-600"></i>Keuangan Anggota <span class="text-[10px] font-normal text-slate-400">(akses penuh)</span></h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-xs">
          <div class="bg-brand-50 rounded-xl p-3"><p class="text-[10px] text-slate-500 font-bold uppercase">Simpanan Pokok</p><p class="font-bold text-brand-600 mt-1"><?= rupiah($finance['simpanan_pokok'] ?? 0) ?></p></div>
          <div class="bg-brand-50 rounded-xl p-3"><p class="text-[10px] text-slate-500 font-bold uppercase">Simpanan Wajib</p><p class="font-bold text-brand-600 mt-1"><?= rupiah($finance['simpanan_wajib'] ?? 0) ?></p></div>
          <div class="bg-brand-50 rounded-xl p-3"><p class="text-[10px] text-slate-500 font-bold uppercase">Simpanan Sukarela</p><p class="font-bold text-brand-600 mt-1"><?= rupiah($finance['simpanan_sukarela'] ?? 0) ?></p></div>
          <div class="bg-brand-600 text-white rounded-xl p-3"><p class="text-[10px] font-bold uppercase opacity-80">Total Simpanan</p><p class="font-bold mt-1"><?= rupiah($finance['total_simpanan'] ?? 0) ?></p></div>
          <div class="bg-slate-100 rounded-xl p-3"><p class="text-[10px] text-slate-500 font-bold uppercase">Total Pinjaman</p><p class="font-bold text-slate-700 mt-1"><?= rupiah($finance['total_pinjaman'] ?? 0) ?></p></div>
          <div class="bg-slate-100 rounded-xl p-3"><p class="text-[10px] text-slate-500 font-bold uppercase">Sisa Pinjaman</p><p class="font-bold text-slate-700 mt-1"><?= rupiah($finance['sisa_pinjaman'] ?? 0) ?></p></div>
          <div class="bg-slate-100 rounded-xl p-3"><p class="text-[10px] text-slate-500 font-bold uppercase">Total Angsuran</p><p class="font-bold text-slate-700 mt-1"><?= rupiah($finance['total_angsuran'] ?? 0) ?></p></div>
          <div class="bg-gold/20 rounded-xl p-3"><p class="text-[10px] text-slate-500 font-bold uppercase">SHU</p><p class="font-bold text-amber-600 mt-1"><?= rupiah($finance['shu_total'] ?? 0) ?></p></div>
        </div>
        <div class="flex flex-wrap items-center gap-x-6 gap-y-1 mt-3 text-[11px] text-slate-500">
          <span>Status pinjaman: <b class="text-slate-700"><?= e((string) ($finance['status_pinjaman'] ?? '-')) ?></b></span>
          <span>Pembayaran terakhir: <b class="text-slate-700"><?= e(tanggal($finance['pembayaran_terakhir'] ?? null)) ?></b></span>
        </div>

        <?php if ($transactions !== []): ?>
          <div class="mt-4">
            <p class="text-[10px] font-bold text-slate-500 uppercase mb-1">Transaksi Terakhir</p>
            <table class="w-full text-xs text-left">
              <thead class="bg-slate-100 text-slate-500 uppercase text-[9px]">
                <tr><th class="p-2 rounded-l-lg">Tanggal</th><th class="p-2">Modul</th><th class="p-2">Ref</th><th class="p-2">Jenis</th><th class="p-2 text-right rounded-r-lg">Nominal</th></tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <?php foreach ($transactions as $t): ?>
                  <tr>
                    <td class="p-2 text-slate-500"><?= e(tanggal((string) ($t['tanggal'] ?? ''))) ?></td>
                    <td class="p-2"><span class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-600 text-[9px] font-bold"><?= e((string) ($t['modul'] ?? '')) ?></span></td>
                    <td class="p-2 font-mono text-[10px]"><?= e((string) ($t['ref'] ?? '-')) ?></td>
                    <td class="p-2"><?= e((string) ($t['jenis'] ?? '')) ?></td>
                    <td class="p-2 text-right font-semibold"><?= rupiah($t['nominal'] ?? 0) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <div class="glass-card rounded-2xl p-5 shadow-sm text-center">
        <i class="fa-solid fa-lock text-slate-300 text-2xl"></i>
        <p class="text-xs text-slate-500 mt-2">Data keuangan bersifat rahasia dan hanya tampil kepada anggota bersangkutan<br>atau pengurus dengan izin khusus setelah login.</p>
        <a href="<?= e($baseUrl) ?>/login" class="inline-block mt-3 px-4 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow">Login Pengurus</a>
      </div>
    <?php endif; ?>

    <p class="text-center text-[10px] text-slate-400">
      Verifikasi resmi &copy; <?= date('Y') ?> KUTT SUKA MAKMUR Grati - Pasuruan.
      <a class="text-brand-600 hover:underline" href="<?= e($baseUrl) ?>/berita">Portal Berita</a>
    </p>
  </div>
</main>
<?php PortalLayout::footer($brand); ?>
