<?php

/**
 * Fragment tabel anggota untuk cetak/PDF.
 * Variables: rows (anggota), filters.
 */
$rows = $rows ?? [];
?>
<table>
  <thead>
    <tr>
      <th>No. Anggota</th>
      <th>Nama</th>
      <th>NIK</th>
      <th>L/P</th>
      <th>No. HP</th>
      <th>Kelompok</th>
      <th>Status</th>
      <th>Bergabung</th>
    </tr>
  </thead>
  <tbody>
    <?php if ($rows === []): ?>
      <tr><td colspan="8" style="text-align:center;color:#94a3b8;padding:14px;">Tidak ada data sesuai filter.</td></tr>
    <?php else: ?>
      <?php foreach ($rows as $m): ?>
        <tr>
          <td style="font-family:monospace;"><?= e((string) $m['member_no']) ?></td>
          <td><?= e((string) $m['full_name']) ?></td>
          <td style="font-family:monospace;"><?= e((string) ($m['nik'] ?? '-')) ?></td>
          <td><?= e((string) ($m['gender'] ?? '-')) ?></td>
          <td style="font-family:monospace;"><?= e((string) ($m['phone'] ?? '-')) ?></td>
          <td><?= e((string) ($m['group_name'] ?? '-')) ?></td>
          <td><?= e((string) $m['status']) ?></td>
          <td><?= e(tanggal((string) ($m['joined_at'] ?? ''))) ?></td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
  </tbody>
</table>
<p style="margin-top:10px;font-size:10px;color:#64748b;">
  Total: <b><?= count($rows) ?></b> anggota
  <?php if (!empty($filters['status'])): ?> &middot; Filter status: <?= e((string) $filters['status']) ?><?php endif; ?>
  <?php if (!empty($filters['q'])): ?> &middot; Filter pencarian: "<?= e((string) $filters['q']) ?>"<?php endif; ?>
</p>
