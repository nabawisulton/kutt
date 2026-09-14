<?php

/**
 * Shell cetak/PDF bersama untuk semua laporan.
 * Variabel: $title, $subtitle, $userName, $content (dirender view anak).
 * Dipakai via view('reports/layout_print', ['content' => View::render(...)]).
 */

use App\Core\View;

$title    = (string) ($title ?? 'Laporan');
$subtitle = (string) ($subtitle ?? '');
$userName = (string) ($userName ?? 'Sistem');
if (!isset($content) || $content === '') {
    $content = $childView ?? '';
    $content = View::render($content, $childData ?? []);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title><?= e($title) ?> - KUTT SUKA MAKMUR</title>
  <link rel="icon" href="data:,">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    * { box-sizing: border-box; }
    body { font-family: Inter, Arial, sans-serif; margin: 0; color: #1e293b; background: #f1f5f9; }
    .sheet { background: #fff; max-width: 900px; margin: 24px auto; padding: 28px 32px; box-shadow: 0 1px 4px rgba(0,0,0,.12); }
    .head { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 3px solid #0b7a3e; padding-bottom: 10px; }
    .brand { display: flex; gap: 10px; align-items: center; }
    .logo { width: 42px; height: 42px; border-radius: 10px; background: #0b7a3e; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 20px; }
    h1 { font-size: 16px; margin: 0; color: #0b7a3e; }
    h2 { font-size: 13px; margin: 2px 0 0; color: #475569; font-weight: 600; }
    .rpt-title { font-size: 14px; font-weight: 700; margin: 14px 0 2px; }
    .meta { font-size: 10px; color: #64748b; margin-bottom: 12px; }
    table { width: 100%; border-collapse: collapse; font-size: 10.5px; }
    th { background: #0b7a3e; color: #fff; padding: 6px 7px; text-align: left; font-weight: 600; }
    td { padding: 5px 7px; border-bottom: 1px solid #e2e8f0; }
    tr:nth-child(even) td { background: #f8fafc; }
    .num { text-align: right; font-variant-numeric: tabular-nums; }
    .foot { margin-top: 18px; font-size: 9px; color: #94a3b8; display: flex; justify-content: space-between; }
    @media print {
      body { background: #fff; }
      .sheet { margin: 0; box-shadow: none; max-width: none; }
      .noprint { display: none !important; }
      @page { margin: 12mm; }
    }
  </style>
</head>
<body>
  <div class="noprint" style="max-width:900px;margin:14px auto 0;display:flex;gap:8px;">
    <button onclick="window.print()" style="padding:8px 14px;border:0;border-radius:10px;background:#0b7a3e;color:#fff;font-weight:700;font-size:12px;cursor:pointer;">Cetak / Simpan PDF</button>
    <a href="javascript:history.back()" style="padding:8px 14px;border-radius:10px;background:#e2e8f0;color:#475569;font-weight:700;font-size:12px;text-decoration:none;">Kembali</a>
  </div>

  <div class="sheet">
    <div class="head">
      <div class="brand">
        <div class="logo"><i class="fa-solid fa-cow"></i></div>
        <div>
          <h1>KUTT SUKA MAKMUR</h1>
          <h2>Koperasi Usaha Tani Ternak - Grati, Pasuruan</h2>
        </div>
      </div>
      <div style="text-align:right;font-size:9px;color:#64748b;">
        Dicetak: <?= e(date('d/m/Y H:i')) ?><br>
        Oleh: <?= e($userName) ?>
      </div>
    </div>

    <div class="rpt-title"><?= e($title) ?></div>
    <?php if ($subtitle !== ''): ?><div class="meta"><?= e($subtitle) ?></div><?php endif; ?>

    <?= $content ?>

    <div class="foot">
      <span>Dokumen dicetak dari Sistem Informasi KUTT SUKA MAKMUR</span>
      <span>Halaman <script>document.write(1)</script></span>
    </div>
  </div>
</body>
</html>
