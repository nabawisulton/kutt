<?php
/**
 * Detail produk publik. Variables: $product, $related, $seoTitle, $seoDesc.
 */

use App\Support\PortalLayout;

$product = (array) ($product ?? []);
$related = (array) ($related ?? []);
$brand = \App\Models\Setting::all();
$navItems = [
    ['path' => '/', 'label' => 'Beranda'],
    ['path' => '/berita', 'label' => 'Berita & Kegiatan'],
    ['path' => '/marketplace', 'label' => 'Belanja Produk'],
    ['path' => '/login', 'label' => 'Portal Anggota'],
];
?>

<?php PortalLayout::header($brand, $navItems, '/marketplace'); ?>

<main class="pt-28 pb-16">
  <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

    <nav class="text-xs text-slate-400 font-semibold">
      <a href="/marketplace" class="hover:text-kutt-primary transition">Marketplace</a>
      <i class="fa-solid fa-chevron-right text-[8px] mx-1.5"></i>
      <?php if (!empty($product['category'])): ?>
        <a href="/marketplace?kategori=<?= rawurlencode((string) $product['category']) ?>" class="hover:text-kutt-primary transition"><?= e((string) $product['category']) ?></a>
        <i class="fa-solid fa-chevron-right text-[8px] mx-1.5"></i>
      <?php endif; ?>
      <span class="text-slate-600 dark:text-slate-300"><?= e((string) $product['name']) ?></span>
    </nav>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
      <div class="glass-card rounded-3xl overflow-hidden shadow-lg">
        <?php if (!empty($product['image_path'])): ?>
          <img src="<?= e(news_image_src((string) $product['image_path'])) ?>" alt="<?= e((string) $product['name']) ?>" class="w-full h-80 md:h-96 object-cover">
        <?php else: ?>
          <div class="w-full h-80 md:h-96 flex items-center justify-center text-6xl text-slate-300 bg-slate-50"><i class="fa-solid fa-box"></i></div>
        <?php endif; ?>
      </div>

      <div class="space-y-4">
        <?php if (!empty($product['category'])): ?>
          <span class="inline-block px-3 py-1 rounded-lg bg-emerald-50 border border-emerald-200 text-[10px] font-bold text-kutt-primary uppercase"><?= e((string) $product['category']) ?></span>
        <?php endif; ?>
        <h1 class="text-2xl sm:text-3xl font-extrabold font-poppins text-slate-900"><?= e((string) $product['name']) ?></h1>
        <p class="text-3xl font-extrabold font-poppins text-kutt-primary"><?= rupiah((float) $product['price']) ?></p>
        <p class="text-xs text-slate-400 font-mono">SKU: <?= e((string) $product['sku']) ?> · Satuan: <?= e((string) $product['unit']) ?></p>

        <div class="glass-card rounded-2xl p-4 text-xs text-slate-600 dark:text-slate-300 leading-relaxed">
          <?= nl2br(e((string) ($product['description'] ?? 'Produk unggulan koperasi KUTT Suka Makmur.'))) ?>
        </div>

        <div class="flex items-center gap-2 text-xs font-bold <?= (int) $product['stock'] <= 5 ? 'text-amber-600' : 'text-emerald-600' ?>">
          <i class="fa-solid <?= (int) $product['stock'] <= 5 ? 'fa-triangle-exclamation' : 'fa-circle-check' ?>"></i>
          Stok tersedia: <?= (int) $product['stock'] ?> <?= e((string) $product['unit']) ?>
        </div>

        <form method="post" action="/marketplace/checkout" class="glass-card rounded-2xl p-5 space-y-3">
          <?= \App\Core\Csrf::field() ?>
          <input type="hidden" name="items" value="<?= e(json_encode([['product_id' => (int) $product['id'], 'quantity' => 1]])) ?>">
          <label class="text-[10px] font-bold text-slate-500 uppercase block">Jumlah (<?= e((string) $product['unit']) ?>)</label>
          <input type="number" name="qty_manual" min="1" max="<?= (int) $product['stock'] ?>" value="1"
            oninput="this.form.items.value = JSON.stringify([{product_id: <?= (int) $product['id'] ?>, quantity: Math.max(1, parseInt(this.value) || 1)}])"
            class="w-32 px-3 py-2.5 text-xs border rounded-xl bg-white dark:bg-slate-800">
          <div class="grid grid-cols-1 gap-2 pt-1">
            <div>
              <label class="text-[10px] font-bold text-slate-500 uppercase block">Nama Anda *</label>
              <input type="text" name="buyer_name" required maxlength="120" class="w-full mt-1 px-3 py-2.5 text-xs border rounded-xl bg-white dark:bg-slate-800">
            </div>
            <div>
              <label class="text-[10px] font-bold text-slate-500 uppercase block">No. WhatsApp *</label>
              <input type="tel" name="buyer_phone" required maxlength="30" placeholder="08xxxxxxxxxx" class="w-full mt-1 px-3 py-2.5 text-xs border rounded-xl bg-white dark:bg-slate-800">
            </div>
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
          <button class="w-full py-3.5 rounded-2xl bg-brand-600 hover:bg-brand-700 text-white text-sm font-bold shadow-xl shadow-emerald-700/25 transition flex items-center justify-center gap-2">
            <i class="fa-solid fa-cart-shopping"></i>Pesan Sekarang (konfirmasi via WhatsApp)
          </button>
        </form>
      </div>
    </div>

    <?php if ($related !== []): ?>
      <div class="space-y-4 pt-4">
        <h2 class="font-poppins font-extrabold text-slate-900 text-lg">Produk Terkait</h2>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-5">
          <?php foreach ($related as $p): $p = (array) $p; ?>
            <a href="/marketplace/<?= (int) $p['id'] ?>" class="group glass-card rounded-2xl overflow-hidden shadow-sm hover:shadow-xl transition flex flex-col">
              <div class="h-32 bg-slate-100 overflow-hidden">
                <?php if (!empty($p['image_path'])): ?>
                  <img src="<?= e(news_image_src((string) $p['image_path'])) ?>" alt="" class="w-full h-full object-cover group-hover:scale-105 transition duration-500" loading="lazy">
                <?php else: ?>
                  <div class="w-full h-full flex items-center justify-center text-3xl text-slate-300"><i class="fa-solid fa-box"></i></div>
                <?php endif; ?>
              </div>
              <div class="p-3 space-y-1">
                <h3 class="text-xs font-bold text-slate-800 line-clamp-2 group-hover:text-kutt-primary transition"><?= e((string) $p['name']) ?></h3>
                <p class="text-xs font-extrabold text-kutt-primary"><?= rupiah((float) $p['price']) ?></p>
              </div>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>
</main>

<?php PortalLayout::footer($brand); ?>
