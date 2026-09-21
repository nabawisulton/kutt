<?php
/**
 * Keranjang & checkout publik. Mendukung dua mode:
 *  - JS: katalog menyimpan pilihan di localStorage (kutt_cart) → form submit
 *    mengirim items sebagai JSON.
 *  - Tanpa JS: query ?add=<productId> menambah satu item (server-side di view
 *    ini hanya untuk tampilan; validasi asli tetap di controller checkout).
 * Variables: $seoTitle, $seoDesc.
 */

use App\Support\PortalLayout;

$brand = \App\Models\Setting::all();
$navItems = [
    ['path' => '/', 'label' => 'Beranda'],
    ['path' => '/berita', 'label' => 'Berita & Kegiatan'],
    ['path' => '/marketplace', 'label' => 'Belanja Produk'],
    ['path' => '/login', 'label' => 'Portal Anggota'],
];

// Katalog ringkas untuk JS (id, nama, harga, stok, satuan).
$catalogJs = App\Core\Database::all(
    'SELECT id, name, price, stock, unit FROM products WHERE is_active = 1 AND stock > 0 ORDER BY name ASC LIMIT 300'
);
?>

<?php PortalLayout::header($brand, $navItems, '/marketplace'); ?>

<main class="pt-28 pb-16">
  <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-extrabold font-poppins text-slate-900"><i class="fa-solid fa-cart-shopping mr-2 text-kutt-primary"></i>Keranjang Anda</h1>
      <a href="/marketplace" class="text-xs font-bold text-kutt-primary hover:underline"><i class="fa-solid fa-arrow-left mr-1"></i>Lanjut Belanja</a>
    </div>

    <div id="cartSummary" class="glass-card rounded-2xl p-5 shadow-sm space-y-2">
      <p class="text-xs text-slate-400 text-center py-6">Memuat keranjang...</p>
    </div>

    <form method="post" action="/marketplace/checkout" class="glass-card rounded-2xl p-5 space-y-3">
      <?= App\Core\Csrf::field() ?>
      <input type="hidden" name="items" id="cartItemsInput" value="[]">
      <h2 class="font-display text-sm font-bold text-slate-800 dark:text-slate-100"><i class="fa-solid fa-user mr-1 text-kutt-primary"></i>Data Pembeli</h2>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
        <div>
          <label class="text-[10px] font-bold text-slate-500 uppercase block">Nama Lengkap *</label>
          <input type="text" name="buyer_name" required maxlength="120" class="w-full mt-1 px-3 py-2.5 text-xs border rounded-xl bg-white dark:bg-slate-800">
        </div>
        <div>
          <label class="text-[10px] font-bold text-slate-500 uppercase block">No. WhatsApp *</label>
          <input type="tel" name="buyer_phone" required maxlength="30" placeholder="08xxxxxxxxxx" class="w-full mt-1 px-3 py-2.5 text-xs border rounded-xl bg-white dark:bg-slate-800">
        </div>
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
        <div>
          <label class="text-[10px] font-bold text-slate-500 uppercase block">Metode Bayar</label>
          <select name="payment_method" class="w-full mt-1 px-3 py-2.5 text-xs border rounded-xl bg-white dark:bg-slate-800">
            <option value="COD">COD / Bayar di Tempat</option>
            <option value="TRANSFER">Transfer Bank</option>
            <option value="QRIS">QRIS</option>
          </select>
        </div>
        <div>
          <label class="text-[10px] font-bold text-slate-500 uppercase block">Catatan (opsional)</label>
          <input type="text" name="buyer_note" maxlength="255" class="w-full mt-1 px-3 py-2.5 text-xs border rounded-xl bg-white dark:bg-slate-800">
        </div>
      </div>
      <button id="btnSubmitCart" class="w-full py-3.5 rounded-2xl bg-brand-600 hover:bg-brand-700 text-white text-sm font-bold shadow-xl shadow-emerald-700/25 transition flex items-center justify-center gap-2 disabled:opacity-40" disabled>
        <i class="fa-brands fa-whatsapp text-base"></i>Buat Pesanan &amp; Konfirmasi via WhatsApp
      </button>
      <p class="text-[10px] text-slate-400 text-center">Pesanan langsung tercatat di sistem &amp; stok otomatis dipotong. Admin akan menghubungi Anda.</p>
    </form>
  </div>
</main>

<script>
(function () {
  var CATALOG = <?= json_encode($catalogJs, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
  var byId = {};
  CATALOG.forEach(function (p) { byId[p.id] = p; });

  function readCart() {
    try { return JSON.parse(localStorage.getItem('kutt_cart') || '[]'); } catch (e) { return []; }
  }
  function fmt(n) { return 'Rp ' + Number(n).toLocaleString('id-ID'); }

  function render() {
    var cart = readCart().filter(function (i) { return byId[i.product_id]; });
    var wrap = document.getElementById('cartSummary');
    if (!cart.length) {
      wrap.innerHTML = '<div class="text-center py-8"><i class="fa-solid fa-cart-plus text-4xl text-slate-300 mb-3"></i><p class="text-sm text-slate-400 font-semibold">Keranjang masih kosong.</p><a href="/marketplace" class="inline-block mt-3 px-5 py-2.5 rounded-xl bg-kutt-primary text-white text-xs font-bold shadow">Belanja Sekarang</a></div>';
      document.getElementById('btnSubmitCart').disabled = true;
      document.getElementById('cartItemsInput').value = '[]';
      return;
    }
    var total = 0;
    var html = '<table class="w-full text-xs"><thead><tr class="text-left text-slate-400 border-b border-slate-100"><th class="py-2 font-semibold">Produk</th><th class="py-2 font-semibold text-center">Qty</th><th class="py-2 font-semibold text-right">Subtotal</th></tr></thead><tbody>';
    cart.forEach(function (i) {
      var p = byId[i.product_id];
      var sub = p.price * i.quantity;
      total += sub;
      html += '<tr class="border-b border-slate-50"><td class="py-2.5 font-bold text-slate-700">' + p.name + '</td><td class="py-2.5 text-center">' + i.quantity + ' ' + p.unit + '</td><td class="py-2.5 text-right font-bold text-slate-700">' + fmt(sub) + '</td></tr>';
    });
    html += '</tbody><tfoot><tr><td colspan="2" class="py-3 text-right font-bold">TOTAL</td><td class="py-3 text-right font-extrabold text-kutt-primary text-sm">' + fmt(total) + '</td></tr></tfoot></table>';
    wrap.innerHTML = html;
    document.getElementById('cartItemsInput').value = JSON.stringify(cart);
    document.getElementById('btnSubmitCart').disabled = false;
  }

  render();
})();
</script>

<?php PortalLayout::footer($brand); ?>
