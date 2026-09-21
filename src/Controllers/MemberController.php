<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Roles;
use App\Core\Validator;
use App\Models\Card;
use App\Models\Member;
use App\Support\ExcelExport;
use App\Support\Uploader;
use RuntimeException;

/**
 * Master data anggota - CRUD lengkap + kartu & QR.
 */
final class MemberController extends Controller
{
    public function index(): void
    {
        Roles::requirePermission('member.view');

        $user = (array) Auth::user();
        $filters = [
            'q'      => trim((string) ($_GET['q'] ?? '')),
            'status' => (string) ($_GET['status'] ?? ''),
            'group'  => trim((string) ($_GET['group'] ?? '')),
        ];
        $page = max(1, (int) ($_GET['page'] ?? 1));

        $result = Member::paginate($filters, $page, 10);

        $this->view('members/index', [
            'pageTitle'    => 'Master Data Anggota',
            'pageSubtitle' => 'Database terpusat keanggotaan peternak & tani',
            'result'       => $result,
            'filters'      => $filters,
            'allowedViews' => Roles::allowedViews($user['role']),
            'activeView'   => 'members',
        ]);
    }

    public function createForm(): void
    {
        Roles::requirePermission('member.create');

        $user = (array) Auth::user();
        $this->view('members/form', [
            'pageTitle'    => 'Tambah Anggota',
            'pageSubtitle' => 'Registrasi anggota baru',
            'member'       => null,
            'nextNo'       => Member::nextMemberNo(),
            'allowedViews' => Roles::allowedViews($user['role']),
            'activeView'   => 'members',
        ]);
    }

    public function editForm(string $id): void
    {
        Roles::requirePermission('member.edit');

        $user = (array) Auth::user();
        $member = Member::find((int) $id);
        if ($member === null) {
            flash_set('error', 'Anggota tidak ditemukan.');
            redirect('/members');
        }

        $this->view('members/form', [
            'pageTitle'    => 'Edit Anggota',
            'pageSubtitle' => $member['member_no'] . ' - ' . $member['full_name'],
            'member'       => $member,
            'nextNo'       => $member['member_no'],
            'allowedViews' => Roles::allowedViews($user['role']),
            'activeView'   => 'members',
        ]);
    }

    public function save(): void
    {
        Roles::requirePermission('member.create');
        Csrf::validate();

        [$ok, $data, $errors] = Validator::check($_POST, [
            'member_no'  => 'required|max:20',
            'full_name'  => 'required|max:120',
            'nik'        => 'digits:16',
            'phone'      => 'max:20',
            'email'      => 'email|max:120',
            'status'     => 'required|in:AKTIF,CALON,NONAKTIF,KELUAR',
            'birth_date' => 'date',
        ]);

        if (!$ok) {
            flash_set('error', 'Data belum lengkap: ' . (implode(' ', $errors) ?: 'periksa isian.'));
            flash_old_input($_POST);
            redirect('/members/create');
        }

        if (Member::findByMemberNo((string) $data['member_no']) !== null) {
            flash_set('error', 'Nomor anggota sudah terdaftar (duplikat).');
            flash_old_input($_POST);
            redirect('/members/create');
        }

        if (($data['nik'] ?? '') !== '' && Database_nik_exists($data['nik'])) {
            flash_set('error', 'NIK sudah terdaftar untuk anggota lain.');
            flash_old_input($_POST);
            redirect('/members/create');
        }

        try {
            $photoPath = Uploader::image($_FILES['photo'] ?? null, 'members');
        } catch (RuntimeException $e) {
            flash_set('error', $e->getMessage());
            flash_old_input($_POST);
            redirect('/members/create');
        }

        $id = Member::create([
            'member_no'   => $data['member_no'],
            'nik'         => $data['nik'] !== '' ? $data['nik'] : null,
            'full_name'   => $data['full_name'],
            'birth_place' => $_POST['birth_place'] ?? null,
            'birth_date'  => $data['birth_date'] !== '' ? $data['birth_date'] : null,
            'gender'      => in_array($_POST['gender'] ?? '', ['L', 'P'], true) ? $_POST['gender'] : null,
            'address'     => $_POST['address'] ?? null,
            'phone'       => $data['phone'] !== '' ? $data['phone'] : null,
            'email'       => $data['email'] !== '' ? $data['email'] : null,
            'occupation'  => $_POST['occupation'] ?? null,
            'jabatan_internal'  => $this->jabatanInternalOrNull($_POST['jabatan_internal'] ?? ''),
            'relasi_eksternal'  => $this->relasiEksternal($_POST['relasi_eksternal'] ?? ''),
            'jabatan_eksternal' => trim((string) ($_POST['jabatan_eksternal'] ?? '')) !== '' ? mb_substr(trim((string) $_POST['jabatan_eksternal']), 0, 60) : null,
            'group_name'  => $_POST['group_name'] ?? null,
            'status'      => $data['status'],
            'photo_path'  => $photoPath,
            'joined_at'   => ($_POST['joined_at'] ?? '') !== '' ? $_POST['joined_at'] : date('Y-m-d'),
            'created_by'  => Auth::id(),
        ]);

        Audit::log('CREATE', 'Anggota baru: ' . $data['full_name'], 'MEMBERS', (string) $id);
        \App\Models\Notification::push(
            'Anggota baru: ' . $data['full_name'],
            'Anggota ' . $data['member_no'] . ' telah terdaftar.',
            'SUCCESS',
            '/members/' . $id . '/card',
            ['role' => \App\Core\Roles::ADMIN]
        );

        flash_set('success', 'Data berhasil disimpan.');
        redirect('/members');
    }

    public function update(string $id): void
    {
        Roles::requirePermission('member.edit');
        Csrf::validate();

        $member = Member::find((int) $id);
        if ($member === null) {
            flash_set('error', 'Anggota tidak ditemukan.');
            redirect('/members');
        }

        [$ok, $data, $errors] = Validator::check($_POST, [
            'full_name' => 'required|max:120',
            'nik'       => 'digits:16',
            'phone'     => 'max:20',
            'email'     => 'email|max:120',
            'status'    => 'required|in:AKTIF,CALON,NONAKTIF,KELUAR',
            'birth_date' => 'date',
        ]);

        if (!$ok) {
            flash_set('error', 'Data belum lengkap: ' . (implode(' ', $errors) ?: 'periksa isian.'));
            redirect('/members/edit/' . $id);
        }

        if (($data['nik'] ?? '') !== '' && Database_nik_exists($data['nik'], (int) $id)) {
            flash_set('error', 'NIK sudah terdaftar untuk anggota lain.');
            redirect('/members/edit/' . $id);
        }

        try {
            $photoPath = Uploader::image($_FILES['photo'] ?? null, 'members');
        } catch (RuntimeException $e) {
            flash_set('error', $e->getMessage());
            redirect('/members/edit/' . $id);
        }

        $payload = [
            'nik'         => $data['nik'] !== '' ? $data['nik'] : null,
            'full_name'   => $data['full_name'],
            'birth_place' => $_POST['birth_place'] ?? null,
            'birth_date'  => $data['birth_date'] !== '' ? $data['birth_date'] : null,
            'gender'      => in_array($_POST['gender'] ?? '', ['L', 'P'], true) ? $_POST['gender'] : null,
            'address'     => $_POST['address'] ?? null,
            'phone'       => $data['phone'] !== '' ? $data['phone'] : null,
            'email'       => $data['email'] !== '' ? $data['email'] : null,
            'occupation'  => $_POST['occupation'] ?? null,
            'jabatan_internal'  => $this->jabatanInternalOrNull($_POST['jabatan_internal'] ?? ''),
            'relasi_eksternal'  => $this->relasiEksternal($_POST['relasi_eksternal'] ?? ''),
            'jabatan_eksternal' => trim((string) ($_POST['jabatan_eksternal'] ?? '')) !== '' ? mb_substr(trim((string) $_POST['jabatan_eksternal']), 0, 60) : null,
            'group_name'  => $_POST['group_name'] ?? null,
            'status'      => $data['status'],
        ];

        if ($photoPath !== null) {
            Uploader::delete((string) $member['photo_path']);
            $payload['photo_path'] = $photoPath;
        }

        Member::update((int) $id, $payload);
        Audit::log('UPDATE', 'Data anggota diubah: ' . $data['full_name'], 'MEMBERS', $id);
        \App\Models\Notification::push(
            'Data anggota diperbarui',
            $data['full_name'] . ' (' . $member['member_no'] . ') diperbarui.',
            'INFO',
            '/members/' . $id . '/card',
            ['role' => \App\Core\Roles::ADMIN]
        );

        flash_set('success', 'Data berhasil diperbarui.');
        redirect('/members');
    }

    public function destroy(string $id): void
    {
        Roles::requirePermission('member.delete');
        Csrf::validate();

        $member = Member::find((int) $id);
        if ($member !== null) {
            Member::delete((int) $id);
            Audit::log('DELETE', 'Anggota dihapus: ' . $member['full_name'], 'MEMBERS', $id);
            \App\Models\Notification::push(
                'Anggota dihapus',
                $member['full_name'] . ' (' . $member['member_no'] . ') telah dihapus.',
                'WARNING',
                '/members',
                ['role' => \App\Core\Roles::ADMIN]
            );
        }

        flash_set('success', 'Data berhasil dihapus.');
        redirect('/members');
    }

    /**
     * Whitelist jabatan internal koperasi (label panjang dipakai di kartu).
     * Nilai di luar daftar -> null (anggota biasa).
     */
    private const JABATAN_INTERNAL = [
        'KETUA', 'WAKIL KETUA', 'SEKRETARIS', 'BENDAHARA', 'PENGAWAS',
        'PENGURUS', 'KOORDINATOR KELOMPOK', 'PENGAWAS QUALITAS SUSU',
    ];

    /** Whitelist relasi eksternal sesuai ENUM kolom. */
    private const RELASI_EKSTERNAL = ['KARYAWAN', 'KONSUMEN', 'PEMASOK', 'MITRA', 'PIHAK_LAIN', 'TIDAK_ADA'];

    private function jabatanInternalOrNull(string $value): ?string
    {
        $value = strtoupper(trim($value));

        return in_array($value, self::JABATAN_INTERNAL, true) ? $value : null;
    }

    private function relasiEksternal(string $value): string
    {
        $value = strtoupper(trim($value));

        return in_array($value, self::RELASI_EKSTERNAL, true) ? $value : 'TIDAK_ADA';
    }

    // --- Kartu anggota -------------------------------------------------------

    public function card(string $id): void
    {
        Roles::requirePermission('member.view');

        $user = (array) Auth::user();
        $member = Member::find((int) $id);
        if ($member === null) {
            flash_set('error', 'Anggota tidak ditemukan.');
            redirect('/members');
        }

        $card = Card::issue((int) $id, (string) $member['member_no']);

        $this->view('members/card', [
            'pageTitle'    => 'Kartu Anggota',
            'pageSubtitle' => $member['member_no'] . ' - ' . $member['full_name'],
            'member'       => $member,
            'card'         => $card,
            'cardBg'       => Card::backgroundSettings(),
            'cardBackBg'   => Card::backBackgroundSettings(),
            'allowedViews' => Roles::allowedViews($user['role']),
            'activeView'   => 'members',
        ]);
    }

    /** Lembar cetak satu anggota (gaya percetakan NPWP/KTP, depan+belakang). */
    public function cardPrint(string $id): void
    {
        Roles::requirePermission('member.view');

        $user = (array) Auth::user();
        $member = Member::find((int) $id);
        if ($member === null) {
            flash_set('error', 'Anggota tidak ditemukan.');
            redirect('/members');
        }

        $card = Card::issue((int) $id, (string) $member['member_no']);
        Audit::log('PRINT', 'Cetak kartu anggota: ' . $member['member_no'], 'MEMBERS', $id);

        echo \App\Core\View::render('members/card_print', [
            'members' => [[
                'member'     => $member,
                'card'       => $card,
                'cardBg'     => Card::backgroundSettings(),
                'cardBackBg' => Card::backBackgroundSettings(),
            ]],
            'brand' => \App\Models\Setting::all(),
            'currentUser' => $user,
        ]);
    }

    /** Lembar cetak massal: seluruh anggota aktif yang sudah punya kartu. */
    public function cardPrintBatch(): void
    {
        Roles::requirePermission('member.view');
        Roles::requirePermission('report.print');

        $rows = \App\Core\Database::all(
            'SELECT m.*, mc.card_serial, mc.verify_code
             FROM members m
             JOIN member_cards mc ON mc.member_id = m.id AND mc.is_active = 1
             WHERE m.status = \'AKTIF\'
             ORDER BY m.member_no ASC
             LIMIT 500'
        );

        $members = [];
        foreach ($rows as $m) {
            $members[] = [
                'member'     => $m,
                'card'       => ['card_serial' => $m['card_serial'], 'verify_code' => $m['verify_code']],
                'cardBg'     => Card::backgroundSettings(),
                'cardBackBg' => Card::backBackgroundSettings(),
            ];
        }

        Audit::log('PRINT', 'Cetak kartu anggota massal (' . count($members) . ' kartu)', 'MEMBERS');

        echo \App\Core\View::render('members/card_print', [
            'members' => $members,
            'brand'   => \App\Models\Setting::all(),
            'currentUser' => (array) Auth::user(),
        ]);
    }

    public function rotateQr(string $id): void
    {
        Roles::requirePermission('member.edit');
        Csrf::validate();

        Card::rotateToken((int) $id);
        Audit::log('UPDATE', 'Token QR kartu anggota di-rotate', 'MEMBERS', $id);
        flash_set('success', 'Token QR berhasil diperbarui (yang lama tidak berlaku).');
        redirect('/members/' . $id . '/card');
    }

    public function exportExcel(): void
    {
        Roles::requirePermission('report.export');

        $rows = Member::paginate($_GET, 1, 5000)['rows'];
        $out = [];
        foreach ($rows as $m) {
            $out[] = [
                $m['member_no'], $m['full_name'], $m['nik'], $m['gender'], $m['phone'],
                $m['group_name'], $m['status'], (string) $m['joined_at'],
            ];
        }

        Audit::log('EXPORT', 'Export Excel data anggota', 'MEMBERS');
        ExcelExport::download('anggota-kutt', 'Data Anggota',
            ['No. Anggota', 'Nama', 'NIK', 'L/P', 'No. HP', 'Kelompok', 'Status', 'Bergabung'],
            $out);
    }

}

/**
 * NIK uniqueness helper (shared by save/update).
 */
function Database_nik_exists(string $nik, ?int $ignoreId = null): bool
{
    $params = [$nik];
    $sql = 'SELECT COUNT(*) FROM members WHERE nik = ?';
    if ($ignoreId !== null) {
        $sql .= ' AND id <> ?';
        $params[] = $ignoreId;
    }

    return (int) \App\Core\Database::scalar($sql, $params) > 0;
}
