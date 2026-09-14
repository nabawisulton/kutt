<?php

/**
 * Form tambah/edit anggota. Variables: member (null = create), nextNo.
 */

use App\Core\Csrf;

$member = $member ?? null;
$isEdit = $member !== null;
$nextNo = (string) ($nextNo ?? '');
?>
<div class="glass-card rounded-2xl p-5 shadow-sm">
  <form method="post" enctype="multipart/form-data"
    action="<?= $isEdit ? '/members/edit/' . (int) $member['id'] : '/members/create' ?>"
    class="grid grid-cols-1 md:grid-cols-2 gap-4" onsubmit="return true;">

    <?= Csrf::field() ?>

    <div>
      <label class="text-[10px] font-bold text-slate-500 uppercase">No. Anggota *</label>
      <input type="text" name="member_no" value="<?= e($isEdit ? (string) $member['member_no'] : $nextNo) ?>"
        <?= $isEdit ? 'readonly' : '' ?>
        class="w-full mt-1 px-3 py-2 text-xs border rounded-xl bg-slate-50 dark:bg-slate-800 font-mono <?= $isEdit ? 'opacity-70' : '' ?>">
    </div>

    <div>
      <label class="text-[10px] font-bold text-slate-500 uppercase">NIK (16 digit)</label>
      <input type="text" name="nik" maxlength="16" value="<?= e($member['nik'] ?? '') ?>"
        class="w-full mt-1 px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800 font-mono">
    </div>

    <div class="md:col-span-2">
      <label class="text-[10px] font-bold text-slate-500 uppercase">Nama Lengkap *</label>
      <input type="text" name="full_name" required maxlength="120" value="<?= e($member['full_name'] ?? '') ?>"
        class="w-full mt-1 px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800">
    </div>

    <div>
      <label class="text-[10px] font-bold text-slate-500 uppercase">Tempat Lahir</label>
      <input type="text" name="birth_place" maxlength="120" value="<?= e($member['birth_place'] ?? '') ?>"
        class="w-full mt-1 px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800">
    </div>

    <div>
      <label class="text-[10px] font-bold text-slate-500 uppercase">Tanggal Lahir</label>
      <input type="date" name="birth_date" value="<?= e($member['birth_date'] ?? '') ?>"
        class="w-full mt-1 px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800">
    </div>

    <div>
      <label class="text-[10px] font-bold text-slate-500 uppercase">Jenis Kelamin</label>
      <select name="gender" class="w-full mt-1 px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800">
        <option value="">-</option>
        <option value="L" <?= ($member['gender'] ?? '') === 'L' ? 'selected' : '' ?>>Laki-laki</option>
        <option value="P" <?= ($member['gender'] ?? '') === 'P' ? 'selected' : '' ?>>Perempuan</option>
      </select>
    </div>

    <div>
      <label class="text-[10px] font-bold text-slate-500 uppercase">Pekerjaan</label>
      <input type="text" name="occupation" maxlength="80" value="<?= e($member['occupation'] ?? '') ?>"
        class="w-full mt-1 px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800">
    </div>

    <div>
      <label class="text-[10px] font-bold text-slate-500 uppercase">Jabatan di Koperasi</label>
      <select name="jabatan_internal" class="w-full mt-1 px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800">
        <option value="">- Anggota Biasa -</option>
        <?php foreach (['KETUA' => 'Ketua', 'WAKIL KETUA' => 'Wakil Ketua', 'SEKRETARIS' => 'Sekretaris', 'BENDAHARA' => 'Bendahara', 'PENGAWAS' => 'Pengawas', 'PENGURUS' => 'Pengurus', 'KOORDINATOR KELOMPOK' => 'Koordinator Kelompok', 'PENGAWAS QUALITAS SUSU' => 'Pengawas Kualitas Susu'] as $v => $l): ?>
          <option value="<?= $v ?>" <?= ($member['jabatan_internal'] ?? '') === $v ? 'selected' : '' ?>><?= $l ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div>
      <label class="text-[10px] font-bold text-slate-500 uppercase">Hubungan Eksternal</label>
      <select name="relasi_eksternal" class="w-full mt-1 px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800">
        <?php foreach (['TIDAK_ADA' => 'Tidak Ada', 'KARYAWAN' => 'Karyawan', 'KONSUMEN' => 'Konsumen / Client', 'PEMASOK' => 'Pemasok', 'MITRA' => 'Mitra Usaha', 'PIHAK_LAIN' => 'Pihak Lain'] as $v => $l): ?>
          <option value="<?= $v ?>" <?= ($member['relasi_eksternal'] ?? 'TIDAK_ADA') === $v ? 'selected' : '' ?>><?= $l ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div>
      <label class="text-[10px] font-bold text-slate-500 uppercase">Jabatan Eksternal (mis. PT/Kantor)</label>
      <input type="text" name="jabatan_eksternal" maxlength="60" value="<?= e($member['jabatan_eksternal'] ?? '') ?>"
        placeholder="mis. Karyawan PT Susu Jaya / Pemilik Warung"
        class="w-full mt-1 px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800">
    </div>

    <div class="md:col-span-2">
      <label class="text-[10px] font-bold text-slate-500 uppercase">Alamat</label>
      <textarea name="address" rows="2" maxlength="255"
        class="w-full mt-1 px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800"><?= e($member['address'] ?? '') ?></textarea>
    </div>

    <div>
      <label class="text-[10px] font-bold text-slate-500 uppercase">No. HP</label>
      <input type="text" name="phone" maxlength="20" value="<?= e($member['phone'] ?? '') ?>"
        class="w-full mt-1 px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800 font-mono">
    </div>

    <div>
      <label class="text-[10px] font-bold text-slate-500 uppercase">Email</label>
      <input type="email" name="email" maxlength="120" value="<?= e($member['email'] ?? '') ?>"
        class="w-full mt-1 px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800">
    </div>

    <div>
      <label class="text-[10px] font-bold text-slate-500 uppercase">Kelompok Tani</label>
      <input type="text" name="group_name" maxlength="120" value="<?= e($member['group_name'] ?? '') ?>"
        class="w-full mt-1 px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800">
    </div>

    <div>
      <label class="text-[10px] font-bold text-slate-500 uppercase">Status *</label>
      <select name="status" required class="w-full mt-1 px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800">
        <?php foreach (['AKTIF' => 'Aktif', 'CALON' => 'Calon', 'NONAKTIF' => 'Nonaktif', 'KELUAR' => 'Keluar'] as $v => $l): ?>
          <option value="<?= $v ?>" <?= ($member['status'] ?? 'CALON') === $v ? 'selected' : '' ?>><?= $l ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div>
      <label class="text-[10px] font-bold text-slate-500 uppercase">Tanggal Bergabung</label>
      <input type="date" name="joined_at" value="<?= e($member['joined_at'] ?? date('Y-m-d')) ?>"
        class="w-full mt-1 px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800">
    </div>

    <div>
      <label class="text-[10px] font-bold text-slate-500 uppercase">Foto Anggota (4x6, JPG/PNG/WEBP, maks 3MB)</label>
      <input type="file" name="photo" accept="image/jpeg,image/png,image/webp"
        class="w-full mt-1 px-3 py-1.5 text-xs border rounded-xl bg-white dark:bg-slate-800 file:mr-2 file:px-2 file:py-1 file:rounded-lg file:border-0 file:bg-brand-50 file:text-brand-600 file:text-[10px] file:font-bold">
      <?php if ($isEdit && !empty($member['photo_path'])): ?>
        <div class="mt-2 flex items-center space-x-2">
          <img src="/<?= e($member['photo_path']) ?>" class="w-14 h-[84px] object-cover rounded-lg border border-slate-200" alt="Foto">
          <span class="text-[10px] text-slate-400">Foto saat ini (rasio 4x6). Unggah baru untuk mengganti.</span>
        </div>
      <?php endif; ?>
    </div>

    <div class="md:col-span-2 flex items-center justify-end space-x-2 pt-2 border-t border-slate-100 dark:border-slate-800">
      <a href="/members" class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-xs font-bold">Batal</a>
      <button type="submit" class="px-5 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow-md shadow-brand-600/20">
        <i class="fa-solid fa-floppy-disk mr-1"></i><?= $isEdit ? 'Simpan Perubahan' : 'Simpan Anggota' ?>
      </button>
    </div>
  </form>
</div>
