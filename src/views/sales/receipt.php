<?php

declare(strict_types=1);

/**
 * Struk / invoice thermal 80mm — halaman mandiri siap cetak.
 * Dipakai: SalesController::receipt() via viewPlain (tanpa layout dashboard).
 * Auto window.print() saat dibuka; tombol bantu hanya tampil di layar.
 *
 * @var array               $order   baris sales_orders (+ cashier_name)
 * @var array<int, array>   $items   sales_order_items
 * @var float               $total   SUM(items.total_price)
 * @var array<string,string> $setting settings map
 * @var array|null          $member  data anggota (jika transaksi pakai saldo)
 */
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Struk <?= htmlspecialchars((string) $order['order_no']) ?></title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    html, body { background: #e2e8f0; }
    body {
        font-family: 'Courier New', Courier, monospace;
        color: #0f172a;
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 16px 10px 40px;
    }
    .sheet {
        width: 72mm;
        max-width: 100%;
        background: #ffffff;
        padding: 4mm 3mm;
        box-shadow: 0 2px 14px rgba(15, 23, 42, .18);
        font-size: 12px;
        line-height: 1.5;
    }
    .sheet .c  { text-align: center; }
    .sheet .b  { font-weight: 700; }
    .sheet .lg { font-size: 14px; letter-spacing: .5px; }
    .sheet .sm { font-size: 10.5px; }
    .sheet hr { border: none; border-top: 1px dashed #0f172a; margin: 6px 0; }
    .sheet .row { display: flex; justify-content: space-between; gap: 8px; }
    .sheet .row span:last-child { text-align: right; white-space: nowrap; }
    .sheet .row div { min-width: 0; }
    .sheet .row div span { display: block; }
    .sheet .item-head { display: flex; justify-content: space-between; gap: 8px; }
    .sheet .indent { padding-left: 4mm; }
    .sheet .total { font-size: 14px; font-weight: 700; }
    .sheet .mt { margin-top: 6px; }
    .toolbar { margin-bottom: 14px; display: flex; gap: 8px; flex-wrap: wrap; justify-content: center; }
    .toolbar a, .toolbar button {
        font: 600 12px/1 system-ui, sans-serif;
        padding: 9px 16px;
        border-radius: 8px;
        border: 1px solid #cbd5e1;
        background: #ffffff;
        color: #0f172a;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .toolbar .primary { background: #b45309; border-color: #b45309; color: #fff; }
    .toolbar a:hover, .toolbar button:hover { filter: brightness(.96); }

    @media print {
        html, body { background: #fff; padding: 0; }
        body { display: block; }
        .toolbar { display: none !important; }
        .sheet { box-shadow: none; width: auto; max-width: none; padding: 0; }
        @page { size: 80mm auto; margin: 3mm; }
    }
</style>
</head>
<body>
<div class="toolbar">
    <button type="button" class="primary" onclick="window.print()">&#128424; Cetak Struk</button>
    <a href="/sales/receipt/<?= (int) $order['id'] ?>/plain" target="_blank">&#128196; Struk Teks (ESC/POS)</a>
    <a href="/sales">&#8592; Kembali ke Kasir</a>
</div>

<div class="sheet">
    <div class="c b lg"><?= htmlspecialchars(mb_strtoupper((string) ($setting['brandName'] ?? 'KUTT SUKA MAKMUR'))) ?></div>
    <?php $addr = trim((string) ($setting['footerAddress'] ?? '')); if ($addr !== ''): ?>
        <div class="c sm"><?= nl2br(htmlspecialchars($addr)) ?></div>
    <?php endif; ?>
    <?php $phone = trim((string) ($setting['footerPhone'] ?? '')); if ($phone !== ''): ?>
        <div class="c sm">Telp/WA: <?= htmlspecialchars($phone) ?></div>
    <?php endif; ?>
    <hr>

    <div class="row"><span>No</span><span class="b"><?= htmlspecialchars((string) $order['order_no']) ?></span></div>
    <div class="row"><span>Tanggal</span><span><?= htmlspecialchars(date('d/m/Y H:i', strtotime((string) $order['created_at']))) ?></span></div>
    <div class="row"><span>Kasir</span><span><?= htmlspecialchars((string) ($order['cashier_name'] ?? '-')) ?></span></div>
    <div class="row"><span>Pembeli</span><div><span><?= htmlspecialchars((string) $order['buyer_name']) ?></span><?php if (!empty($order['buyer_phone'])): ?><span class="sm"><?= htmlspecialchars((string) $order['buyer_phone']) ?></span><?php endif; ?></div></div>
    <?php if ($member !== null): ?>
        <div class="row"><span>Anggota</span><div><span><?= htmlspecialchars((string) ($member['full_name'] ?? '-')) ?></span><span class="sm"><?= htmlspecialchars((string) ($member['member_no'] ?? '')) ?></span></div></div>
    <?php endif; ?>
    <div class="row"><span>Saluran</span><span><?= htmlspecialchars((string) $order['channel']) ?></span></div>
    <hr>

    <?php foreach ($items as $it): ?>
        <div class="item-head">
            <div>
                <span class="b"><?= htmlspecialchars((string) $it['product_name']) ?></span>
            </div>
            <span><?= htmlspecialchars(rtrim(rtrim(number_format((float) $it['quantity'], 0, ',', '.'), '0'), '.')) ?> x <?= htmlspecialchars(number_format((float) $it['unit_price'], 0, ',', '.')) ?></span>
        </div>
        <div class="row indent sm"><span><?= htmlspecialchars(number_format((float) $it['total_price'], 0, ',', '.')) ?></span></div>
    <?php endforeach; ?>
    <hr>
    <div class="row total"><span>TOTAL</span><span>Rp <?= htmlspecialchars(number_format($total, 0, ',', '.')) ?></span></div>
    <div class="row mt"><span>Metode Bayar</span><span class="b"><?= htmlspecialchars((string) $order['payment_method']) ?></span></div>
    <?php if ((string) $order['payment_method'] === 'SALDO' && $member !== null): ?>
        <div class="row sm"><span>Saldo tersisa</span><span>Rp <?= htmlspecialchars(number_format((float) ($walletBalanceAfter ?? 0), 0, ',', '.')) ?></span></div>
    <?php endif; ?>
    <hr>

    <div class="c">Terima kasih telah berbelanja</div>
    <div class="c sm"><?= htmlspecialchars((string) ($setting['footerCopyright'] ?? '- KUTT SUKA MAKMUR -')) ?></div>
    <div class="c sm mt">Simpan struk ini sebagai bukti pembayaran</div>
</div>

<script>
    window.addEventListener('load', function () { window.print(); });
</script>
</body>
</html>
