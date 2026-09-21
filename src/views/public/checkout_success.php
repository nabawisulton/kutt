<?php
/**
 * Sukses checkout. Variables: $orderNo, $total, $waUrl.
 */

use App\Support\PortalLayout;

$brand = \App\Models\Setting::all();
$navItems = [
    ['path' => '/', 'label' => 'Beranda'],
    ['path' => '/berita', 'label' => 'Berita & Kegiatan'],
    ['path' => '/marketplace', 'label' => 'Belanja Produk'],
    ['path' => '/login', 'label' => 'Portal Anggota'],
];
?>

<?php PortalLayout::header($brand, $navItems, ''); ?>

<main class="pt-32 pb-20">
  <div class="max-w-md mx-auto px-4">
    <div class="glass-card rounded-3xl shadow-xl p-8 text-center space-y-4">
      <div class="w-20 h-20 mx-auto rounded-full bg-emerald-50 border border-emerald-200 flex items-center justify-center">
        <i class="fa-solid fa-circle-check text-4xl text-brand-600"></i>
      </div>
      <h1 class="text-xl font-extrabold font-poppins text-slate-900">Pesanan Berhasil Dibuat!</h1>
      <p class="text-xs text-slate-500 leading-relaxed">Terima kasih. Pesanan Anda sudah tercatat di sistem dan stok telah dipesan untuk Anda.</p>

      <div class="rounded-2xl bg-slate-50 border border-slate-200 p-4 space-y-1">
        <p class="text-[10px] font-bold text-slate-400 uppercase">No. Pesanan</p>
        <p class="font-mono font-extrabold text-brand-600 text-lg"><?= e((string) $orderNo) ?></p>
        <p class="text-[10px] font-bold text-slate-400 uppercase pt-2">Total</p>
        <p class="font-extrabold font-poppins text-slate-800 dark:text-slate-100"><?= rupiah((float) $total) ?></p>
      </div>

      <?php if ((string) $waUrl !== ''): ?>
        <a href="<?= e((string) $waUrl) ?>" target="_blank" rel="noopener"
          class="w-full py-3.5 rounded-2xl bg-emerald-500 hover:bg-emerald-600 text-white text-sm font-bold shadow-lg transition flex items-center justify-center gap-2">
          <i class="fa-brands fa-whatsapp text-lg"></i>Lanjutkan ke WhatsApp
        </a>
        <p class="text-[10px] text-slate-400">Konfirmasi pesanan Anda melalui WhatsApp agar diproses lebih cepat.</p>
      <?php endif; ?>

      <div class="flex gap-2 pt-2">
        <a href="/marketplace" class="flex-1 py-3 rounded-xl border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-50 transition">Belanja Lagi</a>
        <a href="/" class="flex-1 py-3 rounded-xl bg-kutt-primary hover:opacity-90 text-white text-xs font-bold shadow transition">Beranda</a>
      </div>
    </div>
  </div>
</main>

<?php PortalLayout::footer($brand); ?>
