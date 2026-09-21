<?php
/**
 * Kasir POS multi-kasir + pembayaran SALDO ANGGOTA & TABUNGAN.
 * Variables: $catalog, $myToday, $myTodayTotal, $pendingTopups, $savingsAccounts.
 * Keranjang di sisi kiri, katalog + pencarian di kanan. Submit form normal
 * (items sebagai JSON) sehingga tetap berfungsi tanpa fetch().
 * Pembayaran SALDO: scan QR kartu anggota (identifikasi) + PIN 6 digit
 * (verifikasi, dicek ulang server-side sebelum saldo dipotong).
 * Pembayaran TABUNGAN: potong dari akun tabungan (server-side, row-locked).
 * Scan barcode produk: buka kamera (BarcodeDetector) atau scanner USB langsung
 * ke input — hasil otomatis ditambahkan ke keranjang.
 */

use App\Core\Csrf;

$catalog = (array) ($catalog ?? []);
$myToday = (int) ($myToday ?? 0);
$myTodayTotal = (float) ($myTodayTotal ?? 0);
$pendingTopups = (array) ($pendingTopups ?? []);
$savingsAccounts = array_map(static fn ($a) => (array) $a, (array) ($savingsAccounts ?? []));
?>

<div class="grid grid-cols-1 lg:grid-cols-5 gap-4">
  <!-- Keranjang -->
  <div class="lg:col-span-2 space-y-4">
    <div class="glass-card rounded-2xl p-5 shadow-sm">
      <div class="flex items-center justify-between mb-4">
        <h3 class="font-display text-sm font-bold text-slate-800 dark:text-slate-100"><i class="fa-solid fa-cart-shopping mr-1 text-brand-600"></i>Keranjang</h3>
        <span class="text-[10px] px-2 py-1 rounded-lg bg-emerald-50 text-emerald-700 font-bold">Hari ini: <?= $myToday ?> trx · <?= rupiah($myTodayTotal) ?></span>
      </div>
      <div id="cartList" class="space-y-2 min-h-[120px] max-h-72 overflow-y-auto custom-scrollbar">
        <p id="cartEmpty" class="text-xs text-slate-400 text-center py-8">Klik produk di kanan untuk menambahkan.</p>
      </div>
      <div class="border-t border-slate-100 dark:border-slate-800 mt-3 pt-3 space-y-1.5 text-xs">
        <div class="flex justify-between text-slate-500"><span>Total Item</span><span id="cartCount" class="font-bold">0</span></div>
        <div class="flex justify-between items-center text-sm">
          <span class="font-bold text-slate-700 dark:text-slate-200">TOTAL</span>
          <span id="cartTotal" class="font-extrabold font-poppins text-brand-600 text-lg">Rp 0</span>
        </div>
      </div>
    </div>

    <form method="post" action="/sales/pos" class="glass-card rounded-2xl p-5 shadow-sm space-y-3" id="posForm">
      <?= Csrf::field() ?>
      <input type="hidden" name="items" id="cartItemsInput" value="[]">
      <input type="hidden" name="member_id" id="memberIdInput" value="">
      <h3 class="font-display text-sm font-bold text-slate-800 dark:text-slate-100"><i class="fa-solid fa-user mr-1 text-brand-600"></i>Pembeli &amp; Pembayaran</h3>
      <div class="grid grid-cols-2 gap-2">
        <div>
          <label class="text-[10px] font-bold text-slate-500 uppercase">Nama Pembeli</label>
          <input type="text" name="buyer_name" id="buyerName" maxlength="120" placeholder="Pelanggan Langsung" class="w-full mt-1 px-3 py-2.5 text-xs border rounded-xl bg-white dark:bg-slate-800">
        </div>
        <div>
          <label class="text-[10px] font-bold text-slate-500 uppercase">No. WhatsApp</label>
          <input type="tel" name="buyer_phone" maxlength="30" placeholder="08xxxxxxxxxx" class="w-full mt-1 px-3 py-2.5 text-xs border rounded-xl bg-white dark:bg-slate-800">
        </div>
      </div>
      <div>
        <label class="text-[10px] font-bold text-slate-500 uppercase">Metode Bayar *</label>
        <select name="payment_method" id="paymentMethod" class="w-full mt-1 px-3 py-2.5 text-xs border rounded-xl bg-white dark:bg-slate-800">
          <option value="CASH">Tunai</option>
          <option value="TRANSFER">Transfer</option>
          <option value="QRIS">QRIS</option>
          <option value="SALDO">Saldo Anggota</option>
          <option value="TABUNGAN">Tabungan KUTT</option>
        </select>
      </div>

      <!-- Panel tabungan: hanya utk pembayaran TABUNGAN -->
      <div id="savingsBox" class="hidden rounded-2xl border border-sky-300 bg-sky-50/70 dark:bg-slate-800 p-3 space-y-2">
        <p class="text-[10px] font-bold uppercase text-sky-600"><i class="fa-solid fa-piggy-bank mr-1"></i>Pembayaran dari Tabungan</p>
        <select name="savings_account_id" id="savingsAccount" class="w-full px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-900">
          <?php if ($savingsAccounts === []): ?>
            <option value="">- Belum ada akun tabungan aktif -</option>
          <?php else: foreach ($savingsAccounts as $sa): ?>
            <option value="<?= (int) $sa['id'] ?>" data-balance="<?= (float) $sa['balance'] ?>">
              <?= e($sa['account_no']) ?> — <?= e($sa['name']) ?> (<?= rupiah((float) $sa['balance']) ?>)
            </option>
          <?php endforeach; endif; ?>
        </select>
        <p class="text-[9px] text-slate-400">Saldo tabungan dipotong otomatis di server; pembayaran ditolak bila saldo tidak cukup.</p>
      </div>

      <!-- Panel anggota: hanya relevan utk pembayaran SALDO (tampil otomatis) -->
      <div id="memberBox" class="hidden rounded-2xl border border-amber-300 bg-amber-50/70 dark:bg-slate-800 p-3 space-y-2">
        <p class="text-[10px] font-bold uppercase text-amber-600"><i class="fa-solid fa-qrcode mr-1"></i>Scan Kartu Anggota</p>
        <div class="flex gap-2">
          <input type="text" id="memberScan" placeholder="Scan QR / tempel token / no. anggota" autocomplete="off"
            class="flex-1 px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-900">
          <button type="button" id="btnScanMember" class="px-3 py-2 rounded-xl bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold"><i class="fa-solid fa-magnifying-glass"></i></button>
        </div>
        <div id="memberInfo" class="hidden rounded-xl bg-white dark:bg-slate-900 border border-slate-200 p-3">
          <p class="text-xs font-bold text-slate-800 dark:text-slate-100" id="memberName">—</p>
          <p class="text-[10px] font-mono text-slate-500" id="memberNo">—</p>
          <p class="text-[11px] mt-1">Saldo: <b id="memberBalance" class="text-brand-600">Rp 0</b>
            <span id="memberNoPin" class="hidden ml-2 text-[10px] text-red-500 font-bold"><i class="fa-solid fa-triangle-exclamation mr-0.5"></i>PIN belum dibuat — minta anggota buat PIN di portal.</span>
          </p>
        </div>
        <div id="memberPinRow" class="hidden">
          <label class="text-[10px] font-bold text-slate-500 uppercase">PIN Transaksi Anggota (6 digit) *</label>
          <input type="password" name="wallet_pin" id="walletPin" maxlength="6" minlength="6" inputmode="numeric" pattern="\d{6}" autocomplete="off" placeholder="&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;"
            class="w-full mt-1 px-3 py-2.5 text-center tracking-[0.5em] text-sm font-bold border rounded-xl bg-white dark:bg-slate-900">
          <p class="text-[9px] text-slate-400 mt-1">PIN diverifikasi ulang di server sebelum saldo dipotong.</p>
        </div>
        <p id="memberMsg" class="hidden text-[10px] font-semibold text-red-500"></p>
      </div>

      <button type="submit" id="btnCheckout" class="w-full py-3 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow-lg disabled:opacity-40" disabled>
        <i class="fa-solid fa-cash-register mr-1"></i>Proses Transaksi
      </button>
      <p class="text-[10px] text-slate-400 text-center">Stok otomatis dipotong; nomor order dicatat atas nama Anda sebagai kasir.</p>
    </form>

    <!-- Top up saldo anggota (uang diterima kas) -->
    <form method="post" action="/sales/pos/topup" class="glass-card rounded-2xl p-5 shadow-sm space-y-3">
      <?= Csrf::field() ?>
      <input type="hidden" name="member_id" id="topupMemberId" value="">
      <h3 class="font-display text-sm font-bold text-slate-800 dark:text-slate-100"><i class="fa-solid fa-circle-plus mr-1 text-amber-500"></i>Top Up Saldo Anggota</h3>
      <div>
        <label class="text-[10px] font-bold text-slate-500 uppercase">Anggota</label>
        <div class="flex gap-2 mt-1">
          <input type="text" id="topupScan" placeholder="Scan QR / token / no. anggota" autocomplete="off"
            class="flex-1 px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800">
          <button type="button" id="btnTopupScan" class="px-3 py-2 rounded-xl bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold"><i class="fa-solid fa-qrcode"></i></button>
        </div>
        <p class="text-[11px] mt-1.5" id="topupTarget">Belum ada anggota dipilih.</p>
      </div>
      <div class="grid grid-cols-2 gap-2">
        <div>
          <label class="text-[10px] font-bold text-slate-500 uppercase">Nominal (Rp) *</label>
          <input type="number" name="amount" required min="1000" max="100000000" step="1000" placeholder="100000" class="w-full mt-1 px-3 py-2.5 text-xs border rounded-xl bg-white dark:bg-slate-800">
        </div>
        <div>
          <label class="text-[10px] font-bold text-slate-500 uppercase">Keterangan</label>
          <input type="text" name="note" maxlength="255" placeholder="mis. setoran tunai" class="w-full mt-1 px-3 py-2.5 text-xs border rounded-xl bg-white dark:bg-slate-800">
        </div>
      </div>
      <button type="submit" id="btnTopup" class="w-full py-2.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold shadow disabled:opacity-40" disabled>
        <i class="fa-solid fa-wallet mr-1"></i>Proses Top Up (Kas Masuk)
      </button>
    </form>

    <?php if ($pendingTopups !== []): ?>
      <div class="glass-card rounded-2xl p-5 shadow-sm">
        <h3 class="font-display text-sm font-bold text-slate-800 dark:text-slate-100 mb-3"><i class="fa-solid fa-inbox mr-1 text-sky-500"></i>Pengajuan Top Up Menunggu</h3>
        <div class="space-y-2">
          <?php foreach ($pendingTopups as $pr): ?>
            <div class="flex items-center justify-between gap-2 rounded-xl bg-slate-50 dark:bg-slate-800 px-3 py-2">
              <div class="min-w-0">
                <p class="text-xs font-bold text-slate-700 dark:text-slate-200 truncate"><?= e((string) $pr['full_name']) ?> <span class="font-mono text-[9px] text-slate-400"><?= e((string) $pr['member_no']) ?></span></p>
                <p class="text-[10px] text-slate-400 font-mono"><?= e((string) $pr['request_no']) ?><?= ($pr['note'] ?? '') !== '' && $pr['note'] !== null ? ' · ' . e((string) $pr['note']) : '' ?></p>
              </div>
              <span class="text-xs font-extrabold text-brand-600 whitespace-nowrap"><?= rupiah((float) $pr['amount']) ?></span>
              <div class="flex gap-1">
                <form method="post" action="/sales/topup-requests/<?= (int) $pr['id'] ?>/process" onsubmit="return confirm('Setujui top up <?= rupiah((float) $pr['amount']) ?> untuk <?= e((string) $pr['full_name']) ?>?')">
                  <?= Csrf::field() ?>
                  <input type="hidden" name="action" value="approve">
                  <button class="px-2 py-1.5 rounded-lg bg-brand-600 hover:bg-brand-700 text-white text-[10px] font-bold">Setujui</button>
                </form>
                <form method="post" action="/sales/topup-requests/<?= (int) $pr['id'] ?>/process" onsubmit="return confirm('Tolak pengajuan ini?')">
                  <?= Csrf::field() ?>
                  <input type="hidden" name="action" value="reject">
                  <button class="px-2 py-1.5 rounded-lg bg-red-50 hover:bg-red-100 text-red-600 text-[10px] font-bold">Tolak</button>
                </form>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <!-- Katalog -->
  <div class="lg:col-span-3 space-y-4">
    <div class="glass-card rounded-2xl p-4 shadow-sm">
      <div class="flex gap-2">
        <input type="text" id="posSearch" placeholder="Cari produk..." class="w-full px-4 py-2.5 text-xs border rounded-xl bg-white dark:bg-slate-800">
        <button type="button" id="btnOpenScanner" title="Scan barcode produk (kamera)"
          class="px-3.5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold whitespace-nowrap">
          <i class="fa-solid fa-barcode"></i><span class="ml-1.5 hidden sm:inline">Scan</span>
        </button>
      </div>
      <p id="scanHint" class="hidden mt-2 text-[10px] text-amber-600 font-semibold"><i class="fa-solid fa-gun mr-1"></i>Scanner USB siap — tembak barcode langsung dari input pencarian.</p>
    </div>
    <div id="posGrid" class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-3">
      <?php foreach ($catalog as $c): $c = (array) $c; ?>
        <button type="button" class="pos-item glass-card rounded-2xl p-3 text-left hover:border-brand-500 hover:shadow-lg transition group"
          data-id="<?= (int) $c['id'] ?>" data-name="<?= e((string) $c['name']) ?>" data-price="<?= (float) $c['price'] ?>"
          data-stock="<?= (int) $c['stock'] ?>" data-unit="<?= e((string) $c['unit']) ?>">
          <?php if (!empty($c['image_path'])): ?>
            <img src="<?= e(news_image_src((string) $c['image_path'])) ?>" alt="" class="w-full h-20 object-cover rounded-xl mb-2">
          <?php else: ?>
            <div class="w-full h-20 rounded-xl mb-2 bg-emerald-50 text-brand-600 flex items-center justify-center text-xl"><i class="fa-solid fa-box"></i></div>
          <?php endif; ?>
          <p class="text-xs font-bold text-slate-700 dark:text-slate-200 leading-tight group-hover:text-brand-600 transition line-clamp-2"><?= e((string) $c['name']) ?></p>
          <p class="text-[11px] font-bold text-amber-500 mt-1"><?= rupiah((float) $c['price']) ?></p>
          <p class="text-[10px] text-slate-400">Stok: <?= (int) $c['stock'] ?> <?= e((string) $c['unit']) ?></p>
        </button>
      <?php endforeach; ?>
      <?php if ($catalog === []): ?>
        <p class="col-span-full text-center text-xs text-slate-400 py-10">Tidak ada produk aktif berstok. Tambahkan produk & stok terlebih dahulu.</p>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
(function () {
  var cart = {}; // id => {name, price, qty, stock, unit}
  var member = null; // {id, member_no, name, balance, has_pin}

  function fmt(n) { return 'Rp ' + Number(n).toLocaleString('id-ID'); }

  function render() {
    var list = document.getElementById('cartList');
    var empty = document.getElementById('cartEmpty');
    var items = Object.keys(cart).map(function (id) { return cart[id]; });
    if (empty) empty.style.display = items.length ? 'none' : 'block';
    list.querySelectorAll('.cart-row').forEach(function (el) { el.remove(); });
    var total = 0, count = 0;
    items.forEach(function (it) {
      total += it.price * it.qty;
      count += it.qty;
      var row = document.createElement('div');
      row.className = 'cart-row flex items-center gap-2 bg-slate-50 dark:bg-slate-800 rounded-xl px-3 py-2';
      row.innerHTML =
        '<div class="flex-1 min-w-0"><p class="text-xs font-bold text-slate-700 dark:text-slate-200 truncate">' +
        it.name.replace(/[&<>"']/g, function (c) { return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }) +
        '</p><p class="text-[10px] text-slate-400">' + fmt(it.price) + ' × ' + it.qty + '</p></div>' +
        '<div class="flex items-center gap-1">' +
        '<button type="button" data-act="dec" class="w-6 h-6 rounded-lg bg-white dark:bg-slate-700 border text-slate-600 text-xs font-bold">−</button>' +
        '<span class="w-6 text-center text-xs font-bold">' + it.qty + '</span>' +
        '<button type="button" data-act="inc" class="w-6 h-6 rounded-lg bg-white dark:bg-slate-700 border text-slate-600 text-xs font-bold">+</button>' +
        '<button type="button" data-act="del" class="w-6 h-6 rounded-lg bg-red-50 text-red-500 text-xs"><i class="fa-solid fa-xmark"></i></button>' +
        '</div>';
      row.querySelector('[data-act=inc]').onclick = function () { change(it.id, 1); };
      row.querySelector('[data-act=dec]').onclick = function () { change(it.id, -1); };
      row.querySelector('[data-act=del]').onclick = function () { change(it.id, -it.qty); };
      list.appendChild(row);
    });
    document.getElementById('cartTotal').textContent = fmt(total);
    document.getElementById('cartCount').textContent = count;
    document.getElementById('cartItemsInput').value = JSON.stringify(
      items.map(function (it) { return { product_id: it.id, quantity: it.qty }; })
    );
    document.getElementById('btnCheckout').disabled = items.length === 0;
  }

  function change(id, delta) {
    var it = cart[id];
    if (!it) return;
    it.qty += delta;
    if (it.qty <= 0) { delete cart[id]; }
    else if (it.qty > it.stock) { it.qty = it.stock; }
    render();
  }

  document.querySelectorAll('.pos-item').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var id = btn.dataset.id;
      if (!cart[id]) {
        cart[id] = { id: id, name: btn.dataset.name, price: Number(btn.dataset.price), qty: 0, stock: Number(btn.dataset.stock), unit: btn.dataset.unit };
      }
      change(id, 1);
    });
  });

  document.getElementById('posSearch').addEventListener('input', function () {
    var q = this.value.toLowerCase();
    document.querySelectorAll('.pos-item').forEach(function (btn) {
      var name = (btn.dataset.name || '').toLowerCase();
      btn.style.display = name.indexOf(q) !== -1 ? '' : 'none';
    });
  });

  // ---------- Panel anggota (pembayaran SALDO) ----------
  var paySel = document.getElementById('paymentMethod');
  var memberBox = document.getElementById('memberBox');
  var savingsBox = document.getElementById('savingsBox');

  function syncMemberBox() {
    var isSaldo = paySel.value === 'SALDO';
    var isTabungan = paySel.value === 'TABUNGAN';
    memberBox.classList.toggle('hidden', !isSaldo);
    savingsBox.classList.toggle('hidden', !isTabungan);
    document.getElementById('walletPin').required = isSaldo;
    var accSel = document.getElementById('savingsAccount');
    if (accSel) { accSel.required = isTabungan && accSel.options.length > 0 && accSel.options[0].value !== ''; }
    if (isSaldo && member) {
      document.getElementById('buyerName').value = member.name;
    }
  }
  paySel.addEventListener('change', syncMemberBox);

  function lookupMember(raw, onDone) {
    var msg = document.getElementById('memberMsg');
    msg.classList.add('hidden');
    fetch('/sales/pos/member/' + encodeURIComponent(raw.trim()))
      .then(function (r) { return r.json(); })
      .then(function (d) {
        if (!d.found) { showMemberMsg(d.message || 'Anggota tidak ditemukan.'); return; }
        member = d.member;
        document.getElementById('memberInfo').classList.remove('hidden');
        document.getElementById('memberName').textContent = member.name;
        document.getElementById('memberNo').textContent = member.member_no;
        document.getElementById('memberBalance').textContent = fmt(member.balance);
        document.getElementById('memberNoPin').classList.toggle('hidden', !!member.has_pin);
        document.getElementById('memberIdInput').value = member.id;
        document.getElementById('memberPinRow').classList.remove('hidden');
        document.getElementById('buyerName').value = member.name;
        onDone && onDone();
      })
      .catch(function () { showMemberMsg('Gagal menghubungi server. Coba lagi.'); });
  }

  function showMemberMsg(text) {
    var msg = document.getElementById('memberMsg');
    msg.textContent = text;
    msg.classList.remove('hidden');
  }

  document.getElementById('btnScanMember').addEventListener('click', function () {
    var raw = document.getElementById('memberScan').value;
    if (raw.trim() === '') { showMemberMsg('Scan atau tempel token QR terlebih dahulu.'); return; }
    lookupMember(raw);
  });
  document.getElementById('memberScan').addEventListener('keydown', function (e) {
    if (e.key === 'Enter') { e.preventDefault(); document.getElementById('btnScanMember').click(); }
  });

  // Top up panel: scan terpisah, isi target member id
  document.getElementById('btnTopupScan').addEventListener('click', function () {
    var raw = document.getElementById('topupScan').value;
    if (raw.trim() === '') { return; }
    fetch('/sales/pos/member/' + encodeURIComponent(raw.trim()))
      .then(function (r) { return r.json(); })
      .then(function (d) {
        if (!d.found) { document.getElementById('topupTarget').textContent = 'Anggota tidak ditemukan.'; document.getElementById('btnTopup').disabled = true; return; }
        document.getElementById('topupTarget').innerHTML = '<b class="text-slate-700 dark:text-slate-200">' +
          d.member.name.replace(/[&<>"']/g, function (c) { return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }) +
          '</b> <span class="font-mono text-[10px] text-slate-400">' + d.member.member_no + '</span> — saldo ' + fmt(d.member.balance);
        document.getElementById('topupMemberId').value = d.member.id;
        document.getElementById('btnTopup').disabled = false;
      })
      .catch(function () { document.getElementById('topupTarget').textContent = 'Gagal menghubungi server.'; });
  });
  document.getElementById('topupScan').addEventListener('keydown', function (e) {
    if (e.key === 'Enter') { e.preventDefault(); document.getElementById('btnTopupScan').click(); }
  });

  // Konfirmasi sebelum submit (khusus SALDO tampilkan saldo sebelum/sesudah)
  document.getElementById('posForm').addEventListener('submit', function (e) {
    if (paySel.value !== 'SALDO') {
      try { sessionStorage.setItem('posCart', document.getElementById('cartItemsInput').value); } catch (err) {}
      return;
    }
    e.preventDefault();
    if (!member || !member.id) { showMemberMsg('Scan kartu anggota terlebih dahulu.'); return; }
    if (!member.has_pin) { showMemberMsg('Anggota belum membuat PIN transaksi. Minta anggota membuat PIN di portal.'); return; }
    if ((document.getElementById('walletPin').value || '').length !== 6) { showMemberMsg('PIN transaksi 6 digit wajib diisi.'); return; }
    var total = 0;
    Object.keys(cart).forEach(function (id) { total += cart[id].price * cart[id].qty; });
    var after = member.balance - total;
    var lines = 'KONFIRMASI TRANSAKSI\n\nAnggota: ' + member.name + ' (' + member.member_no + ')\n' +
      'Total: ' + fmt(total) + '\n' +
      'Saldo sebelum: ' + fmt(member.balance) + '\n' +
      'Saldo setelah: ' + fmt(after);
    if (after < 0) {
      window.alert(lines + '\n\nSALDO TIDAK MENCUKUPI — transaksi akan ditolak server.');
      return;
    }
    if (window.confirm(lines + '\n\nLanjutkan? PIN akan diverifikasi server.')) {
      try { sessionStorage.setItem('posCart', document.getElementById('cartItemsInput').value); } catch (err) {}
      document.getElementById('posForm').submit();
    }
  });

  render();
  syncMemberBox();

  // ---------- Scan barcode produk ----------
  // 1) Scanner USB: input pencarian berperan sebagai penampung (Enter = lookup).
  // 2) Kamera: BarcodeDetector API (Chrome/Android); fallback pesan browser.
  var searchInput = document.getElementById('posSearch');
  var scanBuffer = '', scanTimer = null;
  searchInput.addEventListener('keydown', function (e) {
    if (e.key !== 'Enter') {
      // Deteksi kecepatan ketik scanner USB (burst karakter lalu Enter).
      scanBuffer += e.key.length === 1 ? e.key : '';
      clearTimeout(scanTimer);
      scanTimer = setTimeout(function () { scanBuffer = ''; }, 120);
      return;
    }
    e.preventDefault();
    var code = (scanBuffer.length > 3 ? scanBuffer : searchInput.value).trim();
    scanBuffer = '';
    if (code !== '') { lookupProduct(code); }
  });

  function lookupProduct(code) {
    fetch('/sales/pos/product/' + encodeURIComponent(code))
      .then(function (r) { return r.json(); })
      .then(function (d) {
        if (!d.found) { window.alert(d.message || 'Produk tidak ditemukan.'); return; }
        var p = d.product;
        var existing = document.querySelector('.pos-item[data-id="' + p.id + '"]');
        if (!existing) {
          searchInput.value = '';
          window.alert('Produk "' + p.name + '" tidak ada di katalog aktif.');
          return;
        }
        existing.click();
        searchInput.value = '';
        searchInput.focus();
      })
      .catch(function () { window.alert('Gagal menghubungi server.'); });
  }

  // Kamera scanner
  var scannerStream = null;
  document.getElementById('btnOpenScanner').addEventListener('click', function () {
    if (!('BarcodeDetector' in window)) {
      window.alert('Browser ini belum mendukung scan kamera (BarcodeDetector). Gunakan Chrome/Edge terbaru, atau tembakkan scanner USB langsung ke kolom pencarian.');
      return;
    }
    var existing = document.getElementById('camScanWrap');
    if (existing) { stopCamera(); return; }
    var wrap = document.createElement('div');
    wrap.id = 'camScanWrap';
    wrap.className = 'mt-3 rounded-xl overflow-hidden border border-slate-200 dark:border-slate-700 bg-black relative';
    wrap.innerHTML = '<video id="camScanVideo" playsinline class="w-full max-h-64 object-cover"></video>' +
      '<button type="button" id="btnStopCam" class="absolute top-2 right-2 px-2 py-1 rounded-lg bg-white/90 text-slate-800 text-[10px] font-bold">Tutup</button>' +
      '<p class="absolute bottom-2 left-0 right-0 text-center text-[10px] text-white/90">Arahkan barcode produk ke kamera…</p>';
    var panel = searchInput.closest('.glass-card');
    panel.appendChild(wrap);
    document.getElementById('btnStopCam').onclick = stopCamera;

    var detector = new window.BarcodeDetector();
    navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
      .then(function (stream) {
        scannerStream = stream;
        var video = document.getElementById('camScanVideo');
        video.srcObject = stream;
        return video.play();
      })
      .then(function () {
        var tick = function () {
          var video = document.getElementById('camScanVideo');
          if (!video || !scannerStream) { return; }
          detector.detect(video).then(function (codes) {
            if (codes && codes.length > 0) {
              var code = codes[0].rawValue;
              stopCamera();
              if (code) { lookupProduct(code); }
              return;
            }
            setTimeout(tick, 350);
          }).catch(function () { setTimeout(tick, 500); });
        };
        tick();
      })
      .catch(function () {
        window.alert('Tidak dapat mengakses kamera. Izinkan akses kamera atau gunakan scanner USB.');
        stopCamera();
      });
  });

  function stopCamera() {
    if (scannerStream) {
      scannerStream.getTracks().forEach(function (t) { t.stop(); });
      scannerStream = null;
    }
    var wrap = document.getElementById('camScanWrap');
    if (wrap) { wrap.remove(); }
  }
})();
</script>
