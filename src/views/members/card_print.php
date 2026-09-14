<?php

/**
 * Lembar cetak kartu anggota gaya percetakan (NPWP/KTP):
 * kedua sisi kartu ukuran sebenarnya (85.6 × 53.98 mm) dengan garis potong,
 * siap dicetak / dijadikan PDF untuk dikirim ke percetakan.
 * Variables: members (list dengan card+cardBg+cardBackBg), brand.
 */

$brand = $brand ?? \App\Models\Setting::all();
$brandName = (string) ($brand['brandName'] ?? 'KUTT SUKA MAKMUR');

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Cetak Kartu Anggota - <?= e($brandName) ?></title>
<link rel="icon" href="data:,">
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: Arial, Helvetica, sans-serif; background: #e2e8f0; color: #0f172a; }

  .toolbar { position: sticky; top: 0; z-index: 50; background: #0b7a3e; color: #fff;
             padding: 10px 16px; display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
  .toolbar strong { font-size: 13px; margin-right: auto; }
  .toolbar button, .toolbar a { padding: 8px 14px; border: 0; border-radius: 8px;
             background: #ffffff22; color: #fff; font-weight: 700; font-size: 12px; cursor: pointer; text-decoration: none; }
  .toolbar button:hover, .toolbar a:hover { background: #ffffff38; }
  .toolbar .hint { font-size: 10px; opacity: .85; }

  .sheet { max-width: 1060px; margin: 18px auto; background: #fff; padding: 24px; border-radius: 8px; }

  .member-block { margin-bottom: 26px; page-break-inside: avoid; }
  .member-name { font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 8px; }

  .card-row { display: flex; gap: 18px; flex-wrap: wrap; }

  /* Ukuran fisik kartu ID-1: 85.6mm x 53.98mm */
  .pcard { width: 85.6mm; height: 53.98mm; border-radius: 3.18mm; overflow: hidden;
           position: relative; flex: 0 0 auto; outline: 1px dashed #94a3b8; outline-offset: 2mm; color: #fff; }
  .pcard .bg { position: absolute; inset: 0; background-size: cover; background-position: center; }
  .pcard .ov { position: absolute; inset: 0; }
  .pcard .in { position: relative; height: 100%; display: flex; flex-direction: column;
               padding: 3.2mm; background: linear-gradient(135deg, rgba(5,46,26,.50), rgba(11,122,62,.30)); }

  .hd { display: flex; justify-content: space-between; align-items: flex-start; }
  .hd .brandline { display: flex; gap: 1.6mm; align-items: center; }
  .logo { width: 7.5mm; height: 7.5mm; border-radius: 1.8mm; background: rgba(255,255,255,.92);
          display: flex; align-items: center; justify-content: center; }
  .logo img { width: 100%; height: 100%; object-fit: contain; }
  .bn { font-size: 2.6mm; font-weight: 700; line-height: 1.15; }
  .sb { font-size: 1.8mm; text-transform: uppercase; letter-spacing: .3mm; opacity: .85; }
  .ser { font-family: monospace; font-size: 1.9mm; background: rgba(255,255,255,.2); border-radius: 1mm; padding: .4mm 1.2mm; }

  .mid { flex: 1; display: flex; align-items: center; justify-content: space-between; gap: 2.5mm; margin-top: 1.4mm; }
  .photo { width: 13.5mm; height: 20.2mm; object-fit: cover; border-radius: 1.4mm;
           border: 0.5mm solid rgba(255,255,255,.75); box-shadow: 0 .5mm 1.5mm rgba(0,0,0,.35); }
  .photo-ph { width: 13.5mm; height: 20.2mm; border-radius: 1.4mm; border: 0.5mm solid rgba(255,255,255,.75);
              background: rgba(255,255,255,.2); display: flex; align-items: center; justify-content: center;
              font-size: 1.7mm; text-align: center; opacity: .85; }
  .info { flex: 1; min-width: 0; }
  .lbl { font-size: 1.75mm; text-transform: uppercase; opacity: .8; }
  .val { font-size: 2.5mm; font-weight: 700; margin-bottom: 1mm; }
  .nm  { font-size: 3.2mm; font-weight: 800; line-height: 1.1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
  .jb  { font-size: 2mm; font-weight: 700; color: #ffd54d; margin-top: .4mm; }
  .adr { font-size: 1.8mm; opacity: .85; margin-top: .5mm; overflow: hidden; max-height: 3.8mm; }
  .badges { margin-top: 1mm; display: flex; gap: 1mm; align-items: center; }
  .st  { font-size: 1.8mm; font-weight: 700; background: #ffc107; color: #1e293b; border-radius: .9mm; padding: .3mm 1.2mm; }
  .dt  { font-size: 1.8mm; opacity: .85; }
  .qr  { width: 13.5mm; height: 13.5mm; background: #fff; border-radius: 1.2mm; padding: .6mm; }
  .qrc { text-align: center; }
  .qrc p { font-size: 1.5mm; opacity: .85; margin-top: .4mm; }

  .ft { display: flex; justify-content: space-between; font-size: 1.6mm; opacity: .8;
        border-top: .25mm solid rgba(255,255,255,.3); padding-top: .8mm; }

  /* sisi belakang */
  .tin  { position: relative; height: 100%; display: flex; flex-direction: column;
          padding: 3.2mm; background: linear-gradient(160deg, rgba(5,46,26,.55), rgba(11,122,62,.35)); }
  .tin h4 { font-size: 2.4mm; display: flex; justify-content: space-between; align-items: center; }
  .tin ol { flex: 1; font-size: 1.8mm; line-height: 1.5; opacity: .92; list-style: decimal inside; margin-top: 1.2mm; }
  .box { background: rgba(255,255,255,.12); border: .25mm solid rgba(255,255,255,.18); border-radius: 1.6mm; padding: 1.6mm; }
  .box .lbl2 { font-size: 1.7mm; text-transform: uppercase; letter-spacing: .2mm; opacity: .8; }
  .box .rel { font-size: 2.2mm; font-weight: 700; margin-top: .5mm; }
  .box .rel span { color: #ffd54d; }
  .box .int { font-size: 1.8mm; opacity: .9; margin-top: .4mm; }
  .bt { display: flex; justify-content: space-between; align-items: flex-end;
        border-top: .25mm solid rgba(255,255,255,.3); padding-top: .8mm; margin-top: 1.2mm; }
  .bt .addr { font-size: 1.5mm; opacity: .8; line-height: 1.35; }
  .bt .qr2 { width: 9.5mm; height: 9.5mm; background: #fff; border-radius: 1mm; padding: .5mm; }

  .cutnote { font-size: 10px; color: #64748b; margin-top: 6px; }

  @media print {
    body { background: #fff; }
    .toolbar, .cutnote, .member-name { display: none !important; }
    .sheet { margin: 0; padding: 6mm; max-width: none; border-radius: 0; }
    .pcard { outline: 0.25mm dashed #9ca3af; outline-offset: 1.5mm; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    @page { size: A4; margin: 10mm; }
  }
</style>
</head>
<body>
<div class="toolbar">
  <strong>Cetak Kartu Anggota — depan &amp; belakang (85.6 × 53.98 mm)</strong>
  <span class="hint">Gunakan kertas A4, aktifkan &ldquo;Background graphics&rdquo; di dialog cetak. Garis putus-putus = tanda potong.</span>
  <button onclick="window.print()">Cetak / Simpan PDF</button>
  <a href="javascript:history.back()">Kembali</a>
</div>

<div class="sheet">
<?php foreach ($members as $m): ?>
  <?php
    $bgF = $m['cardBg'];
    $bgB = $m['cardBackBg'] ?? $m['cardBg'];
    $bgStyle = fn (array $bg): string => ($bg['type'] ?? 'color') === 'image' && !empty($bg['image'])
        ? "background-image:url('/" . e((string) $bg['image']) . "');"
        : 'background-color:' . e((string) ($bg['color'] ?? '#0b7a3e')) . ';';
    $ovStyle = fn (array $bg): string => ($bg['type'] ?? 'color') === 'image'
        ? 'background:rgba(5,46,26,' . e((string) ($bg['opacity'] ?? 0.25)) . ');' : '';
    $jabatan = trim((string) ($m['member']['jabatan_internal'] ?? ''));
    $relasiMap = ['KARYAWAN' => 'Karyawan', 'KONSUMEN' => 'Konsumen / Client', 'PEMASOK' => 'Pemasok', 'MITRA' => 'Mitra Usaha', 'PIHAK_LAIN' => 'Pihak Lain'];
    $relasi = $relasiMap[(string) ($m['member']['relasi_eksternal'] ?? 'TIDAK_ADA')] ?? null;
    $jbEksternal = trim((string) ($m['member']['jabatan_eksternal'] ?? ''));
    $photoFile = BASE_PATH . '/public/' . ($m['member']['photo_path'] ?? '');
    $logoFile  = BASE_PATH . '/public/' . ($brand['logoImage'] ?? '');
  ?>
  <div class="member-block">
    <p class="member-name"><?= e((string) $m['member']['member_no']) ?> — <?= e((string) $m['member']['full_name']) ?></p>
    <div class="card-row">

      <!-- DEPAN -->
      <div class="pcard">
        <div class="bg" style="<?= $bgStyle($bgF) ?>"></div>
        <?php if ($ovStyle($bgF) !== ''): ?><div class="ov" style="<?= $ovStyle($bgF) ?>"></div><?php endif; ?>
        <div class="in">
          <div class="hd">
            <div class="brandline">
              <div class="logo">
                <?php if (!empty($brand['logoImage']) && is_file($logoFile)): ?>
                  <img src="/<?= e((string) $brand['logoImage']) ?>" alt="">
                <?php else: ?>
                  <span style="font-size:4mm;color:#0b7a3e;">◆</span>
                <?php endif; ?>
              </div>
              <div>
                <p class="bn"><?= e($brandName) ?></p>
                <p class="sb">Kartu Anggota Resmi</p>
              </div>
            </div>
            <span class="ser"><?= e((string) ($m['card']['card_serial'] ?? '-')) ?></span>
          </div>
          <div class="mid">
            <?php if (!empty($m['member']['photo_path']) && is_file($photoFile)): ?>
              <img class="photo" src="/<?= e((string) $m['member']['photo_path']) ?>" alt="">
            <?php else: ?>
              <div class="photo-ph">Foto<br>4x6</div>
            <?php endif; ?>
            <div class="info">
              <p class="lbl">No. Anggota</p>
              <p class="val" style="font-family:monospace;"><?= e((string) $m['member']['member_no']) ?></p>
              <p class="lbl">Nama</p>
              <p class="nm"><?= e((string) $m['member']['full_name']) ?></p>
              <?php if ($jabatan !== ''): ?><p class="jb"><?= e($jabatan) ?></p><?php endif; ?>
              <p class="adr"><?= e((string) ($m['member']['address'] ?? '')) ?></p>
              <div class="badges">
                <span class="st"><?= e((string) $m['member']['status']) ?></span>
                <span class="dt"><?= e(tanggal((string) ($m['member']['joined_at'] ?? ''))) ?></span>
              </div>
            </div>
            <div class="qrc">
              <img class="qr" src="/qr/<?= e((string) ($m['card']['verify_code'] ?? '')) ?>" alt="QR">
              <p>Pindai verifikasi</p>
            </div>
          </div>
          <div class="ft">
            <span><?= e($brandName) ?> Grati - Pasuruan</span>
            <span>NIA: <?= e((string) ($m['member']['nia'] ?? '-')) ?></span>
          </div>
        </div>
      </div>

      <!-- BELAKANG -->
      <div class="pcard">
        <div class="bg" style="<?= $bgStyle($bgB) ?>"></div>
        <?php if ($ovStyle($bgB) !== ''): ?><div class="ov" style="<?= $ovStyle($bgB) ?>"></div><?php endif; ?>
        <div class="tin">
          <h4>SYARAT &amp; KETENTUAN <span class="ser"><?= e((string) $m['member']['member_no']) ?></span></h4>
          <ol>
            <li>Kartu ini adalah identitas resmi anggota <?= e($brandName) ?> dan wajib dibawa saat bertransaksi.</li>
            <li>Kartu tidak boleh dipindahtangankan; penggunaan oleh orang lain tidak sah.</li>
            <li>Apabila kartu hilang, segera laporkan ke pengurus untuk penerbitan kartu pengganti.</li>
          </ol>
          <div class="box">
            <p class="lbl2">Hubungan Eksternal</p>
            <?php if ($relasi !== null): ?>
              <p class="rel"><span>&#9679;</span> <?= e($relasi) ?><?php if ($jbEksternal !== ''): ?> — <?= e($jbEksternal) ?><?php endif; ?></p>
            <?php else: ?>
              <p class="rel" style="opacity:.75;">Tidak tercatat</p>
            <?php endif; ?>
            <?php if ($jabatan !== ''): ?>
              <p class="int">Jabatan internal: <strong><?= e($jabatan) ?></strong></p>
            <?php endif; ?>
          </div>
          <div class="bt">
            <div class="addr">
              <?= e($brandName) ?><br>
              Jl. Raya Grati No. 128, Pasuruan<br>
              (0343) 481123
            </div>
            <img class="qr2" src="/qr/<?= e((string) ($m['card']['verify_code'] ?? '')) ?>" alt="QR">
          </div>
        </div>
      </div>

    </div>
    <p class="cutnote">Potong mengikuti garis putus-putus. Sisi belakang = pencerminan horizontal saat laminasi (posisi QR di kiri bawah setelah dibalik).</p>
  </div>
<?php endforeach; ?>
</div>
</body>
</html>
