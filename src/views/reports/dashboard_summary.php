<?php

/**
 * Fragment ringkasan dashboard untuk cetak/PDF.
 * Variables: metrics, recent.
 */
$metrics = $metrics ?? [];
$recent  = $recent ?? [];
?>
<div class="grid" style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:14px;">
  <?php foreach ($metrics as $label => $value): ?>
    <div style="border:1px solid #e2e8f0;border-radius:10px;padding:8px 10px;">
      <div style="font-size:8.5px;font-weight:700;color:#64748b;text-transform:uppercase;"><?= e(ucfirst(preg_replace('/([a-z])([A-Z])/', '$1 $2', (string) $label))) ?></div>
      <div style="font-size:13px;font-weight:700;color:#0b7a3e;margin-top:2px;"><?= e((string) $value) ?></div>
    </div>
  <?php endforeach; ?>
</div>

<table>
  <thead>
    <tr><th>Tanggal</th><th>Modul</th><th>Ref</th><th>Jenis</th><th style="text-align:right;">Nominal</th></tr>
  </thead>
  <tbody>
    <?php if ($recent === []): ?>
      <tr><td colspan="5" style="text-align:center;color:#94a3b8;padding:12px;">Belum ada transaksi.</td></tr>
    <?php else: ?>
      <?php foreach ($recent as $t): ?>
        <tr>
          <td><?= e(tanggal((string) ($t['tanggal'] ?? ''))) ?></td>
          <td><?= e((string) ($t['modul'] ?? '')) ?></td>
          <td style="font-family:monospace;"><?= e((string) ($t['ref'] ?? '-')) ?></td>
          <td><?= e((string) ($t['jenis'] ?? '')) ?></td>
          <td class="num" style="text-align:right;"><?= rupiah($t['nominal'] ?? 0) ?></td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
  </tbody>
</table>
