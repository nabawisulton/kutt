<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Roles;
use App\Models\Notification;
use App\Models\Setting;
use App\Support\ExcelExport;

/**
 * Modul keuangan inti: Simpanan, Pinjaman + Angsuran, Kas (double-entry),
 * dan SHU. Semua transaksi berjalan dalam transaksi DB + jurnal seimbang.
 */
final class FinController extends Controller
{
    // ================================================================ SIMPANAN

    public function savings(): void
    {
        Roles::requirePermission('simpanan');
        $user = (array) Auth::user();

        $filters = [
            'q'      => trim((string) ($_GET['q'] ?? '')),
            'jenis'  => (string) ($_GET['jenis'] ?? ''),
            'member' => (int) ($_GET['member'] ?? 0),
        ];

        $where = ['1=1'];
        $params = [];
        if ($filters['q'] !== '') {
            $where[] = '(m.full_name LIKE ? OR m.member_no LIKE ? OR t.trans_no LIKE ?)';
            $like = '%' . $filters['q'] . '%';
            array_push($params, $like, $like, $like);
        }
        if (in_array($filters['jenis'], ['POKOK', 'WAJIB', 'SUKARELA'], true)) {
            $where[] = 't.jenis = ?';
            $params[] = $filters['jenis'];
        }
        if ($filters['member'] > 0) {
            $where[] = 't.member_id = ?';
            $params[] = $filters['member'];
        }

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 20;

        $whereSql = implode(' AND ', $where);
        $total = (int) Database::scalar(
            "SELECT COUNT(*) FROM savings_transactions t
             JOIN members m ON m.id = t.member_id WHERE $whereSql",
            $params
        );
        $rows = Database::all(
            "SELECT t.*, m.member_no, m.full_name,
                    COALESCE(u.full_name, '-') AS operator_name
             FROM savings_transactions t
             JOIN members m ON m.id = t.member_id
             LEFT JOIN users u ON u.id = t.operator_user_id
             WHERE $whereSql
             ORDER BY t.tanggal_trans DESC, t.id DESC
             LIMIT $perPage OFFSET " . (($page - 1) * $perPage),
            $params
        );

        // Saldo per jenis (SETOR - TARIK).
        $balances = [];
        foreach (Database::all(
            "SELECT jenis, SUM(CASE WHEN tipe_transaksi = 'SETOR' THEN nominal ELSE -nominal END) AS saldo
             FROM savings_transactions GROUP BY jenis"
        ) as $row) {
            $balances[$row['jenis']] = (float) $row['saldo'];
        }

        $members = Database::all("SELECT id, member_no, full_name FROM members WHERE status = 'AKTIF' ORDER BY full_name");

        $this->view('fin/simpanan', [
            'pageTitle'    => 'Simpanan & Tabungan',
            'pageSubtitle' => 'Simpanan pokok, wajib, dan sukarela anggota',
            'rows'         => $rows,
            'balances'     => $balances,
            'members'      => $members,
            'filters'      => $filters,
            'page'         => $page,
            'totalPages'   => (int) max(1, ceil($total / $perPage)),
            'total'        => $total,
            'canCreate'    => Roles::can($user['role'], 'finance.create'),
            'allowedViews' => Roles::allowedViews($user['role']),
            'activeView'   => 'simpanan',
        ]);
    }

    public function saveSavings(): void
    {
        Roles::requirePermission('finance.create');
        Csrf::validate();
        $user = (array) Auth::user();

        [$ok, $data, $errors] = \App\Core\Validator::check($_POST, [
            'member_id'      => 'required|numeric',
            'jenis'          => 'required|in:POKOK,WAJIB,SUKARELA',
            'tipe_transaksi' => 'required|in:SETOR,TARIK',
            'nominal'        => 'required|numeric',
            'tanggal_trans'  => 'required|date',
        ]);
        if (!$ok) {
            flash_set('error', reset($errors) ?: 'Data belum lengkap.');
            redirect('/simpanan');
        }

        $nominal = (float) $data['nominal'];
        if ($nominal <= 0) {
            flash_set('error', 'Nominal harus lebih besar dari nol.');
            redirect('/simpanan');
        }

        $member = Database::first('SELECT * FROM members WHERE id = ?', [(int) $data['member_id']]);
        if ($member === null) {
            flash_set('error', 'Anggota tidak ditemukan.');
            redirect('/simpanan');
        }

        $transNo = $this->nextNo('SPB', 'savings_transactions', 'trans_no');

        Database::beginTransaction();
        try {
            Database::insert('savings_transactions', [
                'trans_no'        => $transNo,
                'member_id'       => (int) $member['id'],
                'jenis'           => $data['jenis'],
                'tipe_transaksi'  => $data['tipe_transaksi'],
                'nominal'         => $nominal,
                'tanggal_trans'   => $data['tanggal_trans'],
                'operator_user_id'=> (int) $user['id'],
                'keterangan'      => mb_substr(trim((string) ($_POST['keterangan'] ?? '')), 0, 255),
                'created_at'      => date('Y-m-d H:i:s'),
            ]);

            // Jurnal: kas vs simpanan anggota (liabilitas koperasi).
            $akunKas = '1101';
            $akunSimpanan = $data['jenis'] === 'POKOK' ? '2101' : ($data['jenis'] === 'WAJIB' ? '2102' : '2103');
            if ($data['tipe_transaksi'] === 'SETOR') {
                $this->writeJournal($data['tanggal_trans'], $transNo, [
                    [$akunKas, $nominal, 0.0],
                    [$akunSimpanan, 0.0, $nominal],
                ], 'Setoran simpanan ' . $member['member_no']);
            } else {
                $this->writeJournal($data['tanggal_trans'], $transNo, [
                    [$akunSimpanan, $nominal, 0.0],
                    [$akunKas, 0.0, $nominal],
                ], 'Penarikan simpanan ' . $member['member_no']);
            }

            Database::commit();
        } catch (\Throwable $e) {
            Database::rollBack();
            error_log('[KUTT][Savings] save failed: ' . $e->getMessage());
            flash_set('error', 'Terjadi kesalahan. Silakan coba kembali.');
            redirect('/simpanan');
        }

        Audit::log('CREATE', 'Transaksi simpanan ' . $transNo . ' (' . $data['tipe_transaksi'] . ' ' . $data['jenis'] . ') anggota ' . $member['member_no'], 'SIMPANAN', $transNo);
        Notification::push(
            $data['tipe_transaksi'] === 'SETOR' ? 'Setoran simpanan baru' : 'Penarikan simpanan',
            $member['full_name'] . ' - ' . rupiah($nominal) . ' (' . $data['jenis'] . ').',
            'INFO', '/simpanan', ['role' => Roles::ADMIN]
        );
        // (type enum di DB: INFO/SUCCESS/WARNING/DANGER)
        flash_set('success', 'Data berhasil disimpan. No: ' . $transNo);
        redirect('/simpanan');
    }

    // ================================================================ PINJAMAN

    public function loans(): void
    {
        Roles::requirePermission('pinjaman');
        $user = (array) Auth::user();

        $status = (string) ($_GET['status'] ?? '');
        $q = trim((string) ($_GET['q'] ?? ''));
        $where = ['1=1'];
        $params = [];
        if (in_array($status, ['PENDING', 'APPROVED', 'REJECTED', 'DISBURSED', 'LUNAS'], true)) {
            $where[] = 'l.status = ?';
            $params[] = $status;
        }
        if ($q !== '') {
            $where[] = '(m.full_name LIKE ? OR m.member_no LIKE ? OR l.loan_no LIKE ?)';
            $like = '%' . $q . '%';
            array_push($params, $like, $like, $like);
        }
        $whereSql = implode(' AND ', $where);

        $rows = Database::all(
            "SELECT l.*, m.member_no, m.full_name,
                    (SELECT COALESCE(SUM(s.pokok_due),0) FROM loan_schedules s WHERE s.loan_id = l.id AND s.status = 'PAID') AS pokok_dibayar
             FROM loans l JOIN members m ON m.id = l.member_id
             WHERE $whereSql
             ORDER BY l.id DESC
             LIMIT 100",
            $params
        );

        $members = Database::all("SELECT id, member_no, full_name FROM members WHERE status = 'AKTIF' ORDER BY full_name");
        $pending = (int) Database::scalar("SELECT COUNT(*) FROM loans WHERE status = 'PENDING'");

        $this->view('fin/pinjaman', [
            'pageTitle'    => 'Pinjaman & Approval',
            'pageSubtitle' => 'Pengajuan, persetujuan, dan pelunasan pinjaman anggota',
            'rows'         => $rows,
            'members'      => $members,
            'status'       => $status,
            'q'            => $q,
            'pending'      => $pending,
            'canApprove'   => Roles::can($user['role'], 'finance.edit'),
            'canCreate'    => Roles::can($user['role'], 'finance.create'),
            'allowedViews' => Roles::allowedViews($user['role']),
            'activeView'   => 'pinjaman',
        ]);
    }

    public function saveLoan(): void
    {
        Roles::requirePermission('finance.create');
        Csrf::validate();

        [$ok, $data, $errors] = \App\Core\Validator::check($_POST, [
            'member_id'      => 'required|numeric',
            'pokok_pinjaman' => 'required|numeric',
            'bunga_pertahun' => 'required|numeric',
            'tenor_bulan'    => 'required|numeric',
            'sistem_bunga'   => 'required|in:FLAT,MENURUN',
            'tanggal_pengajuan' => 'required|date',
        ]);
        if (!$ok) {
            flash_set('error', reset($errors) ?: 'Data belum lengkap.');
            redirect('/pinjaman');
        }

        $pokok = (float) $data['pokok_pinjaman'];
        if ($pokok <= 0 || (int) $data['tenor_bulan'] < 1) {
            flash_set('error', 'Plafon dan tenor tidak valid.');
            redirect('/pinjaman');
        }

        $loanNo = $this->nextNo('PJN', 'loans', 'loan_no');
        Database::insert('loans', [
            'loan_no'           => $loanNo,
            'member_id'         => (int) $data['member_id'],
            'pokok_pinjaman'    => $pokok,
            'bunga_pertahun'    => (float) $data['bunga_pertahun'],
            'tenor_bulan'       => (int) $data['tenor_bulan'],
            'sistem_bunga'      => $data['sistem_bunga'],
            'status'            => 'PENDING',
            'tanggal_pengajuan' => $data['tanggal_pengajuan'],
            'keperluan'         => mb_substr(trim((string) ($_POST['keperluan'] ?? '')), 0, 255),
            'agunan'            => mb_substr(trim((string) ($_POST['agunan'] ?? '')), 0, 255),
            'created_at'        => date('Y-m-d H:i:s'),
        ]);
        $loanId = (int) Database::pdo()->lastInsertId();

        Audit::log('CREATE', 'Pengajuan pinjaman ' . $loanNo . ' sebesar ' . rupiah($pokok), 'PINJAMAN', $loanNo);
        Notification::push('Pengajuan pinjaman baru', 'Pinjaman ' . $loanNo . ' menunggu persetujuan (' . rupiah($pokok) . ').', 'WARNING', '/pinjaman', ['role' => Roles::ADMIN]);
        flash_set('success', 'Data berhasil disimpan. No: ' . $loanNo);
        redirect('/pinjaman');
    }

    public function approveLoan(string $id): void
    {
        Roles::requirePermission('finance.edit');
        Csrf::validate();
        $user = (array) Auth::user();

        $loan = Database::first('SELECT * FROM loans WHERE id = ?', [(int) $id]);
        if ($loan === null || $loan['status'] !== 'PENDING') {
            flash_set('error', 'Pengajuan tidak ditemukan atau sudah diproses.');
            redirect('/pinjaman');
        }

        Database::exec(
            "UPDATE loans SET status = 'APPROVED', approved_by = ?, approved_at = NOW() WHERE id = ?",
            [(int) $user['id'], (int) $id]
        );
        Audit::log('UPDATE', 'Pinjaman disetujui: ' . $loan['loan_no'], 'PINJAMAN', $loan['loan_no']);
        Notification::push('Pinjaman disetujui', 'Pinjaman ' . $loan['loan_no'] . ' disetujui, menunggu pencairan.', 'INFO', '/pinjaman', ['role' => Roles::BENDAHARA]);
        flash_set('success', 'Data berhasil diperbarui. Pinjaman disetujui.');
        redirect('/pinjaman');
    }

    public function rejectLoan(string $id): void
    {
        Roles::requirePermission('finance.edit');
        Csrf::validate();
        $user = (array) Auth::user();

        $loan = Database::first('SELECT * FROM loans WHERE id = ?', [(int) $id]);
        if ($loan === null || $loan['status'] !== 'PENDING') {
            flash_set('error', 'Pengajuan tidak ditemukan atau sudah diproses.');
            redirect('/pinjaman');
        }

        Database::exec("UPDATE loans SET status = 'REJECTED', approved_by = ?, approved_at = NOW() WHERE id = ?", [(int) $user['id'], (int) $id]);
        Audit::log('UPDATE', 'Pinjaman ditolak: ' . $loan['loan_no'], 'PINJAMAN', $loan['loan_no']);
        flash_set('success', 'Pinjaman ditolak.');
        redirect('/pinjaman');
    }

    /** Pencairan pinjaman: status DISBURSED + generate jadwal angsuran + jurnal. */
    public function disburseLoan(string $id): void
    {
        Roles::requirePermission('finance.edit');
        Csrf::validate();
        $user = (array) Auth::user();

        $loan = Database::first('SELECT * FROM loans WHERE id = ?', [(int) $id]);
        if ($loan === null || $loan['status'] !== 'APPROVED') {
            flash_set('error', 'Pinjaman harus berstatus APPROVED sebelum pencairan.');
            redirect('/pinjaman');
        }

        $tanggal = (string) ($_POST['tanggal_disburse'] ?? date('Y-m-d'));
        if (strtotime($tanggal) === false) {
            $tanggal = date('Y-m-d');
        }

        $pokok = (float) $loan['pokok_pinjaman'];
        $bungaTahun = (float) $loan['bunga_pertahun'];
        $tenor = (int) $loan['tenor_bulan'];
        $sistem = (string) $loan['sistem_bunga'];

        // Jadwal angsuran:
        // FLAT    -> pokok tetap, bunga = pokok * (bungaTahun/100) / 12 per bulan
        // MENURUN -> pokok tetap, bunga = sisa pokok * (bungaTahun/100) / 12
        $pokokPerBulan = round($pokok / $tenor, 2);
        $sisa = $pokok;

        Database::beginTransaction();
        try {
            foreach (range(1, $tenor) as $ke) {
                $bungaBulan = $sistem === 'FLAT'
                    ? round($pokok * $bungaTahun / 100 / 12, 2)
                    : round($sisa * $bungaTahun / 100 / 12, 2);
                $pokokDue = $ke === $tenor ? $sisa : $pokokPerBulan; // bulan terakhir menyerap pembulatan
                Database::insert('loan_schedules', [
                    'loan_id'     => (int) $loan['id'],
                    'angsuran_ke' => $ke,
                    'jatuh_tempo' => date('Y-m-d', strtotime($tanggal . ' +' . $ke . ' months')),
                    'pokok_due'   => $pokokDue,
                    'bunga_due'   => $bungaBulan,
                    'status'      => 'UNPAID',
                ]);
                $sisa = round($sisa - $pokokDue, 2);
            }

            Database::exec("UPDATE loans SET status = 'DISBURSED', tanggal_disburse = ? WHERE id = ?", [$tanggal, (int) $loan['id']]);

            // Jurnal pencairan: kas keluar, piutang anggota masuk.
            $this->writeJournal($tanggal, $loan['loan_no'], [
                ['1103', $pokok, 0.0],
                ['1101', 0.0, $pokok],
            ], 'Pencairan pinjaman ' . $loan['loan_no']);

            Database::commit();
        } catch (\Throwable $e) {
            Database::rollBack();
            error_log('[KUTT][Loan] disburse failed: ' . $e->getMessage());
            flash_set('error', 'Terjadi kesalahan. Silakan coba kembali.');
            redirect('/pinjaman');
        }

        Audit::log('UPDATE', 'Pinjaman dicairkan: ' . $loan['loan_no'] . ' (' . rupiah($pokok) . ')', 'PINJAMAN', $loan['loan_no']);
        flash_set('success', 'Pinjaman dicairkan. Jadwal angsuran ' . $tenor . ' bulan dibuat.');
        redirect('/pinjaman');
    }

    public function loanDetail(string $id): void
    {
        Roles::requirePermission('pinjaman');
        $user = (array) Auth::user();

        $loan = Database::first(
            'SELECT l.*, m.member_no, m.full_name FROM loans l JOIN members m ON m.id = l.member_id WHERE l.id = ?',
            [(int) $id]
        );
        if ($loan === null) {
            flash_set('error', 'Pinjaman tidak ditemukan.');
            redirect('/pinjaman');
        }

        $schedules = Database::all('SELECT * FROM loan_schedules WHERE loan_id = ? ORDER BY angsuran_ke', [(int) $id]);
        $payments = Database::all(
            'SELECT p.*, u.full_name AS operator FROM loan_payments p LEFT JOIN users u ON u.id = p.operator_user_id WHERE p.loan_id = ? ORDER BY p.tanggal_bayar DESC, p.id DESC',
            [(int) $id]
        );

        $this->view('fin/pinjaman_detail', [
            'pageTitle'    => 'Detail Pinjaman ' . $loan['loan_no'],
            'pageSubtitle' => $loan['full_name'] . ' (' . $loan['member_no'] . ')',
            'loan'         => $loan,
            'schedules'    => $schedules,
            'payments'     => $payments,
            'canPay'       => Roles::can($user['role'], 'finance.create'),
            'allowedViews' => Roles::allowedViews($user['role']),
            'activeView'   => 'pinjaman',
        ]);
    }

    /** Pembayaran angsuran: jadwal terakhir UNPAID yang jatuh tempo paling dekat. */
    public function paySchedule(string $id): void
    {
        Roles::requirePermission('finance.create');
        Csrf::validate();
        $user = (array) Auth::user();

        $loan = Database::first('SELECT * FROM loans WHERE id = ?', [(int) $id]);
        if ($loan === null || $loan['status'] !== 'DISBURSED') {
            flash_set('error', 'Pinjaman tidak dalam status aktif (DISBURSED).');
            redirect('/pinjaman/detail/' . (int) $id);
        }

        $schedule = Database::first(
            "SELECT * FROM loan_schedules WHERE loan_id = ? AND status = 'UNPAID' ORDER BY angsuran_ke LIMIT 1",
            [(int) $id]
        );
        if ($schedule === null) {
            flash_set('error', 'Semua angsuran sudah lunas.');
            redirect('/pinjaman/detail/' . (int) $id);
        }

        $denda = max(0.0, (float) ($_POST['denda'] ?? 0));
        $tanggal = (string) ($_POST['tanggal_bayar'] ?? date('Y-m-d'));
        if (strtotime($tanggal) === false) {
            $tanggal = date('Y-m-d');
        }

        $pokok = (float) $schedule['pokok_due'];
        $bunga = (float) $schedule['bunga_due'];
        $total = $pokok + $bunga + $denda;
        $paymentNo = $this->nextNo('ANG', 'loan_payments', 'payment_no');

        Database::beginTransaction();
        try {
            Database::insert('loan_payments', [
                'payment_no'      => $paymentNo,
                'loan_id'         => (int) $loan['id'],
                'schedule_id'     => (int) $schedule['id'],
                'angsuran_ke'     => (int) $schedule['angsuran_ke'],
                'bayar_pokok'     => $pokok,
                'bayar_bunga'     => $bunga,
                'denda'           => $denda,
                'total_bayar'     => $total,
                'tanggal_bayar'   => $tanggal,
                'operator_user_id'=> (int) $user['id'],
                'keterangan'      => mb_substr(trim((string) ($_POST['keterangan'] ?? '')), 0, 255),
                'created_at'      => date('Y-m-d H:i:s'),
            ]);
            Database::exec("UPDATE loan_schedules SET status = 'PAID', paid_at = NOW() WHERE id = ?", [(int) $schedule['id']]);

            $remaining = (int) Database::scalar("SELECT COUNT(*) FROM loan_schedules WHERE loan_id = ? AND status = 'UNPAID'", [(int) $loan['id']]);
            if ($remaining === 0) {
                Database::exec("UPDATE loans SET status = 'LUNAS' WHERE id = ?", [(int) $loan['id']]);
            }

            // Jurnal: kas masuk, bunga = pendapatan, pokok mengurangi piutang.
            $lines = [
                ['1101', $total, 0.0],
                ['1103', 0.0, $pokok],
                ['4101', 0.0, $bunga],
            ];
            if ($denda > 0) {
                $lines[] = ['4102', 0.0, $denda];
            }
            $this->writeJournal($tanggal, $paymentNo, $lines, 'Angsuran ke-' . $schedule['angsuran_ke'] . ' ' . $loan['loan_no']);

            Database::commit();
        } catch (\Throwable $e) {
            Database::rollBack();
            error_log('[KUTT][Loan] payment failed: ' . $e->getMessage());
            flash_set('error', 'Terjadi kesalahan. Silakan coba kembali.');
            redirect('/pinjaman/detail/' . (int) $id);
        }

        Audit::log('CREATE', 'Pembayaran angsuran ' . $paymentNo . ' (ke-' . $schedule['angsuran_ke'] . ') ' . $loan['loan_no'], 'ANGSURAN', $paymentNo);
        Notification::push('Angsuran diterima', 'Angsuran ke-' . $schedule['angsuran_ke'] . ' pinjaman ' . $loan['loan_no'] . ' dibayar (' . rupiah($total) . ').', 'INFO', '/pinjaman/detail/' . (int) $id, ['role' => Roles::ADMIN]);
        flash_set('success', 'Pembayaran berhasil disimpan. No: ' . $paymentNo . ($remaining === 0 ? ' Pinjaman LUNAS.' : ''));
        redirect('/pinjaman/detail/' . (int) $id);
    }

    // ================================================================ KAS

    public function cash(): void
    {
        Roles::requirePermission('kas');
        $user = (array) Auth::user();

        $jenis = (string) ($_GET['jenis'] ?? '');
        $where = ['1=1'];
        $params = [];
        if (in_array($jenis, ['MASUK', 'KELUAR'], true)) {
            $where[] = 'c.jenis_kas = ?';
            $params[] = $jenis;
        }

        $rows = Database::all(
            'SELECT c.*, a.account_name FROM cash_transactions c
             LEFT JOIN chart_of_accounts a ON a.account_code = c.account_code
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY c.tanggal_trans DESC, c.id DESC LIMIT 100',
            $params
        );

        $masuk = (float) Database::scalar("SELECT COALESCE(SUM(nominal),0) FROM cash_transactions WHERE jenis_kas = 'MASUK'");
        $keluar = (float) Database::scalar("SELECT COALESCE(SUM(nominal),0) FROM cash_transactions WHERE jenis_kas = 'KELUAR'");
        $accounts = Database::all("SELECT account_code, account_name FROM chart_of_accounts WHERE is_active = 1 ORDER BY account_code");

        $this->view('fin/kas', [
            'pageTitle'    => 'Kas Masuk / Keluar',
            'pageSubtitle' => 'Buku kas operasional koperasi',
            'rows'         => $rows,
            'saldo'        => $masuk - $keluar,
            'masuk'        => $masuk,
            'keluar'       => $keluar,
            'accounts'     => $accounts,
            'jenis'        => $jenis,
            'canCreate'    => Roles::can($user['role'], 'finance.create'),
            'allowedViews' => Roles::allowedViews($user['role']),
            'activeView'   => 'kas',
        ]);
    }

    public function saveCash(): void
    {
        Roles::requirePermission('finance.create');
        Csrf::validate();
        $user = (array) Auth::user();

        [$ok, $data, $errors] = \App\Core\Validator::check($_POST, [
            'jenis_kas'     => 'required|in:MASUK,KELUAR',
            'account_code'  => 'required|max:10',
            'nominal'       => 'required|numeric',
            'tanggal_trans' => 'required|date',
        ]);
        if (!$ok) {
            flash_set('error', reset($errors) ?: 'Data belum lengkap.');
            redirect('/kas');
        }

        $nominal = (float) $data['nominal'];
        if ($nominal <= 0) {
            flash_set('error', 'Nominal harus lebih besar dari nol.');
            redirect('/kas');
        }

        $account = Database::first('SELECT * FROM chart_of_accounts WHERE account_code = ?', [$data['account_code']]);
        if ($account === null) {
            flash_set('error', 'Kategori akun tidak valid.');
            redirect('/kas');
        }

        $voucherNo = $this->nextNo('KAS', 'cash_transactions', 'voucher_no');

        Database::beginTransaction();
        try {
            Database::insert('cash_transactions', [
                'voucher_no'    => $voucherNo,
                'jenis_kas'     => $data['jenis_kas'],
                'account_code'  => $data['account_code'],
                'nominal'       => $nominal,
                'keterangan'    => mb_substr(trim((string) ($_POST['keterangan'] ?? '')), 0, 255),
                'tanggal_trans' => $data['tanggal_trans'],
                'created_by'    => (int) $user['id'],
                'created_at'    => date('Y-m-d H:i:s'),
            ]);

            // Jurnal kas masuk  : kas (D), akun terkait (K)
            // Jurnal kas keluar : akun terkait (D), kas (K)
            if ($data['jenis_kas'] === 'MASUK') {
                $this->writeJournal($data['tanggal_trans'], $voucherNo, [
                    ['1101', $nominal, 0.0],
                    [$data['account_code'], 0.0, $nominal],
                ], 'Kas masuk ' . $voucherNo);
            } else {
                $this->writeJournal($data['tanggal_trans'], $voucherNo, [
                    [$data['account_code'], $nominal, 0.0],
                    ['1101', 0.0, $nominal],
                ], 'Kas keluar ' . $voucherNo);
            }

            Database::commit();
        } catch (\Throwable $e) {
            Database::rollBack();
            error_log('[KUTT][Cash] save failed: ' . $e->getMessage());
            flash_set('error', 'Terjadi kesalahan. Silakan coba kembali.');
            redirect('/kas');
        }

        Audit::log('CREATE', 'Transaksi kas ' . $voucherNo . ' (' . $data['jenis_kas'] . ' ' . rupiah($nominal) . ')', 'KAS', $voucherNo);
        flash_set('success', 'Data berhasil disimpan. No: ' . $voucherNo);
        redirect('/kas');
    }

    // ================================================================ AKUNTANSI

    public function journal(): void
    {
        Roles::requirePermission('akuntansi');
        $user = (array) Auth::user();

        $from = (string) ($_GET['from'] ?? date('Y-m-01'));
        $to = (string) ($_GET['to'] ?? date('Y-m-d'));
        if (strtotime($from) === false) { $from = date('Y-m-01'); }
        if (strtotime($to) === false) { $to = date('Y-m-d'); }

        $rows = Database::all(
            'SELECT j.*, a.account_name FROM journal_entries j
             LEFT JOIN chart_of_accounts a ON a.account_code = j.account_code
             WHERE j.tanggal BETWEEN ? AND ?
             ORDER BY j.tanggal ASC, j.id ASC
             LIMIT 500',
            [$from, $to]
        );

        $totalDebit = (float) Database::scalar('SELECT COALESCE(SUM(debet),0) FROM journal_entries WHERE tanggal BETWEEN ? AND ?', [$from, $to]);
        $totalKredit = (float) Database::scalar('SELECT COALESCE(SUM(kredit),0) FROM journal_entries WHERE tanggal BETWEEN ? AND ?', [$from, $to]);

        // Buku besar (grouping per akun)
        $ledger = [];
        foreach ($rows as $r) {
            $code = (string) $r['account_code'];
            $ledger[$code] ??= ['name' => $r['account_name'] ?? $code, 'debet' => 0.0, 'kredit' => 0.0];
            $ledger[$code]['debet'] += (float) $r['debet'];
            $ledger[$code]['kredit'] += (float) $r['kredit'];
        }

        $accounts = Database::all("SELECT account_code, account_name, account_category, normal_balance FROM chart_of_accounts WHERE is_active = 1 ORDER BY account_code");

        $this->view('fin/jurnal', [
            'pageTitle'    => 'Jurnal & Buku Besar',
            'pageSubtitle' => 'Pembukuan double-entry koperasi',
            'rows'         => $rows,
            'ledger'       => $ledger,
            'accounts'     => $accounts,
            'from'         => $from,
            'to'           => $to,
            'totalDebit'   => $totalDebit,
            'totalKredit'  => $totalKredit,
            'balanced'     => abs($totalDebit - $totalKredit) < 0.01,
            'canCreate'    => Roles::can($user['role'], 'finance.create'),
            'allowedViews' => Roles::allowedViews($user['role']),
            'activeView'   => 'akuntansi',
        ]);
    }

    /** Jurnal manual oleh bendahara (validasi seimbang). */
    public function saveJournal(): void
    {
        Roles::requirePermission('finance.edit');
        Csrf::validate();
        $user = (array) Auth::user();

        $tanggal = (string) ($_POST['tanggal'] ?? date('Y-m-d'));
        $keterangan = mb_substr(trim((string) ($_POST['keterangan'] ?? '')), 0, 255);
        $lines = [];
        $totalD = 0.0;
        $totalK = 0.0;

        foreach (($_POST['lines'] ?? []) as $line) {
            $code = trim((string) ($line['account'] ?? ''));
            $debet = (float) ($line['debet'] ?? 0);
            $kredit = (float) ($line['kredit'] ?? 0);
            if ($code === '' || ($debet <= 0 && $kredit <= 0) || ($debet > 0 && $kredit > 0)) {
                continue;
            }
            $lines[] = [$code, $debet, $kredit];
            $totalD += $debet;
            $totalK += $kredit;
        }

        if (count($lines) < 2 || abs($totalD - $totalK) >= 0.01) {
            flash_set('error', 'Jurnal tidak seimbang atau belum lengkap (minimal 2 baris, debit = kredit).');
            redirect('/akuntansi');
        }

        $journalId = $this->nextNo('JRN', 'journal_entries', 'journal_id');
        $this->writeJournal($tanggal, $journalId, $lines, $keterangan, $journalId);

        Audit::log('CREATE', 'Jurnal manual ' . $journalId . ' (' . rupiah($totalD) . ')', 'AKUNTANSI', $journalId);
        flash_set('success', 'Data berhasil disimpan. No: ' . $journalId);
        redirect('/akuntansi');
    }

    // ================================================================ SHU

    public function shu(): void
    {
        Roles::requirePermission('shu');
        $user = (array) Auth::user();

        $tahun = (int) ($_GET['tahun'] ?? date('Y'));
        if ($tahun < 2000 || $tahun > (int) date('Y') + 1) {
            $tahun = (int) date('Y');
        }

        // Total SHU tahun berjalan dari laba (pendapatan - beban) via jurnal.
        $pendapatan = (float) Database::scalar(
            "SELECT COALESCE(SUM(kredit - debet),0) FROM journal_entries j
             JOIN chart_of_accounts a ON a.account_code = j.account_code
             WHERE a.account_category = 'REVENUE' AND YEAR(j.tanggal) = ?",
            [$tahun]
        );
        $beban = (float) Database::scalar(
            "SELECT COALESCE(SUM(debet - kredit),0) FROM journal_entries j
             JOIN chart_of_accounts a ON a.account_code = j.account_code
             WHERE a.account_category = 'EXPENSE' AND YEAR(j.tanggal) = ?",
            [$tahun]
        );
        $laba = $pendapatan - $beban;
        $shuTotal = max(0.0, $laba);

        // Porsi anggota (komponen jasa 25% + modal 25% => 50% untuk anggota, default legacy).
        $porsiAnggota = (float) (Setting::get('shu_member_share', '50') ?: '50');
        $shuAnggota = $shuTotal * $porsiAnggota / 100;

        // Kontribusi per anggota: jasa = setoran susu (simulasi 500/liter) & modal = saldo simpanan.
        $contrib = Database::all(
            "SELECT m.id, m.member_no, m.full_name,
                    COALESCE((SELECT SUM(CASE WHEN t.tipe_transaksi='SETOR' THEN t.nominal ELSE -t.nominal END)
                              FROM savings_transactions t WHERE t.member_id = m.id), 0) AS simpanan
             FROM members m WHERE m.status = 'AKTIF'
             ORDER BY m.member_no
             LIMIT 200"
        );
        $totalSimpanan = array_sum(array_map(static fn ($r) => (float) $r['simpanan'], $contrib));

        $distribusi = [];
        foreach ($contrib as $r) {
            $simpanan = (float) $r['simpanan'];
            $share = $totalSimpanan > 0 ? $simpanan / $totalSimpanan : 0.0;
            $distribusi[] = [
                'member_no' => $r['member_no'],
                'full_name' => $r['full_name'],
                'simpanan'  => $simpanan,
                'jasa_modal' => round($shuAnggota * 0.5 * $share, 2),
                'jasa_anggota' => round($shuAnggota * 0.5 / max(1, count($contrib)), 2),
                'total'     => round($shuAnggota * 0.5 * $share + $shuAnggota * 0.5 / max(1, count($contrib)), 2),
            ];
        }

        $saved = Database::all(
            'SELECT d.*, m.member_no, m.full_name FROM shu_distributions d
             JOIN members m ON m.id = d.member_id
             WHERE d.tahun_buku = ?
             ORDER BY m.member_no',
            [$tahun]
        );

        $this->view('fin/shu', [
            'pageTitle'    => 'Kalkulasi Bagi SHU ' . $tahun,
            'pageSubtitle' => 'Sisa Hasil Usaha koperasi (laba ' . rupiah($laba) . ')',
            'tahun'        => $tahun,
            'laba'         => $laba,
            'shuTotal'     => $shuTotal,
            'shuAnggota'   => $shuAnggota,
            'porsiAnggota' => $porsiAnggota,
            'distribusi'   => $distribusi,
            'saved'        => $saved,
            'canEdit'      => Roles::can($user['role'], 'finance.edit'),
            'allowedViews' => Roles::allowedViews($user['role']),
            'activeView'   => 'shu',
        ]);
    }

    /** Simpan hasil distribusi SHU tahun terpilih (idempoten per tahun+anggota). */
    public function saveShu(): void
    {
        Roles::requirePermission('finance.edit');
        Csrf::validate();
        $user = (array) Auth::user();

        $tahun = (int) ($_POST['tahun'] ?? date('Y'));
        $payload = json_decode((string) ($_POST['payload'] ?? '[]'), true);
        if (!is_array($payload) || $payload === []) {
            flash_set('error', 'Tidak ada data distribusi untuk disimpan.');
            redirect('/shu?tahun=' . $tahun);
        }

        $count = 0;
        Database::beginTransaction();
        try {
            foreach ($payload as $row) {
                $member = Database::first('SELECT id FROM members WHERE member_no = ?', [(string) ($row['member_no'] ?? '')]);
                if ($member === null) {
                    continue;
                }
                $total = (float) ($row['total'] ?? 0);
                if ($total <= 0) {
                    continue;
                }
                $existing = Database::first(
                    'SELECT id FROM shu_distributions WHERE tahun_buku = ? AND member_id = ?',
                    [$tahun, (int) $member['id']]
                );
                if ($existing !== null) {
                    Database::exec(
                        'UPDATE shu_distributions SET jasa_modal = ?, jasa_anggota = ?, total_shu = ? WHERE id = ?',
                        [(float) ($row['jasa_modal'] ?? 0), (float) ($row['jasa_anggota'] ?? 0), $total, (int) $existing['id']]
                    );
                } else {
                    Database::insert('shu_distributions', [
                        'shu_no'       => $this->nextNo('SHU', 'shu_distributions', 'shu_no'),
                        'tahun_buku'   => $tahun,
                        'member_id'    => (int) $member['id'],
                        'jasa_modal'   => (float) ($row['jasa_modal'] ?? 0),
                        'jasa_anggota' => (float) ($row['jasa_anggota'] ?? 0),
                        'total_shu'    => $total,
                        'status_pencairan' => 'PENDING',
                        'created_by'   => (int) $user['id'],
                        'created_at'   => date('Y-m-d H:i:s'),
                    ]);
                }
                $count++;
            }
            Database::commit();
        } catch (\Throwable $e) {
            Database::rollBack();
            error_log('[KUTT][SHU] save failed: ' . $e->getMessage());
            flash_set('error', 'Terjadi kesalahan. Silakan coba kembali.');
            redirect('/shu?tahun=' . $tahun);
        }

        Audit::log('CREATE', 'Distribusi SHU ' . $tahun . ' disimpan (' . $count . ' anggota)', 'SHU', (string) $tahun);
        flash_set('success', 'Data berhasil disimpan (' . $count . ' anggota).');
        redirect('/shu?tahun=' . $tahun);
    }

    // ================================================================ EXPORTS

    public function exportSavings(): void
    {
        Roles::requirePermission('finance.export');

        $rows = Database::all(
            "SELECT t.trans_no, t.tanggal_trans, m.member_no, m.full_name, t.jenis,
                    t.tipe_transaksi, t.nominal, t.keterangan
             FROM savings_transactions t JOIN members m ON m.id = t.member_id
             ORDER BY t.tanggal_trans DESC, t.id DESC LIMIT 5000"
        );

        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                $r['trans_no'], $r['tanggal_trans'], $r['member_no'], $r['full_name'],
                $r['jenis'], $r['tipe_transaksi'], (float) $r['nominal'], $r['keterangan'],
            ];
        }

        ExcelExport::download('simpanan', 'Laporan Simpanan KUTT Suka Makmur',
            ['No Transaksi', 'Tanggal', 'No Anggota', 'Nama', 'Jenis', 'Tipe', 'Nominal', 'Keterangan'], $out);
    }

    public function exportLoans(): void
    {
        Roles::requirePermission('finance.export');

        $rows = Database::all(
            'SELECT l.*, m.member_no, m.full_name FROM loans l JOIN members m ON m.id = l.member_id ORDER BY l.id DESC LIMIT 5000'
        );

        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                $r['loan_no'], $r['member_no'], $r['full_name'], (float) $r['pokok_pinjaman'],
                (float) $r['bunga_pertahun'], (int) $r['tenor_bulan'], $r['sistem_bunga'],
                $r['status'], $r['tanggal_pengajuan'], $r['keperluan'],
            ];
        }

        ExcelExport::download('pinjaman', 'Laporan Pinjaman KUTT Suka Makmur',
            ['No Pinjaman', 'No Anggota', 'Nama', 'Plafon', 'Bunga %/Thn', 'Tenor', 'Sistem', 'Status', 'Tgl Pengajuan', 'Keperluan'], $out);
    }

    public function exportCash(): void
    {
        Roles::requirePermission('finance.export');

        $rows = Database::all(
            'SELECT c.*, a.account_name FROM cash_transactions c LEFT JOIN chart_of_accounts a ON a.account_code = c.account_code ORDER BY c.tanggal_trans DESC, c.id DESC LIMIT 5000'
        );

        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                $r['voucher_no'], $r['tanggal_trans'], $r['jenis_kas'], $r['account_code'],
                (string) $r['account_name'], (float) $r['nominal'], $r['keterangan'],
            ];
        }

        ExcelExport::download('kas', 'Laporan Kas KUTT Suka Makmur',
            ['No Voucher', 'Tanggal', 'Jenis', 'Kode Akun', 'Nama Akun', 'Nominal', 'Keterangan'], $out);
    }

    public function exportJournal(): void
    {
        Roles::requirePermission('finance.export');

        $from = (string) ($_GET['from'] ?? date('Y-m-01'));
        $to = (string) ($_GET['to'] ?? date('Y-m-d'));
        if (strtotime($from) === false) { $from = date('Y-m-01'); }
        if (strtotime($to) === false) { $to = date('Y-m-d'); }

        $rows = Database::all(
            'SELECT j.*, a.account_name FROM journal_entries j LEFT JOIN chart_of_accounts a ON a.account_code = j.account_code WHERE j.tanggal BETWEEN ? AND ? ORDER BY j.tanggal, j.id LIMIT 10000',
            [$from, $to]
        );

        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                $r['journal_id'], $r['tanggal'], $r['account_code'], (string) $r['account_name'],
                $r['keterangan'], (float) $r['debet'], (float) $r['kredit'], $r['ref_voucher'],
            ];
        }

        ExcelExport::download('jurnal', 'Jurnal Umum KUTT Suka Makmur',
            ['Journal ID', 'Tanggal', 'Kode Akun', 'Nama Akun', 'Keterangan', 'Debit', 'Kredit', 'Ref'], $out);
    }

    public function exportShu(): void
    {
        Roles::requirePermission('finance.export');

        $tahun = (int) ($_GET['tahun'] ?? date('Y'));
        $rows = Database::all(
            'SELECT d.*, m.member_no, m.full_name FROM shu_distributions d JOIN members m ON m.id = d.member_id WHERE d.tahun_buku = ? ORDER BY m.member_no',
            [$tahun]
        );

        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                $r['shu_no'], (int) $r['tahun_buku'], $r['member_no'], $r['full_name'],
                (float) $r['jasa_modal'], (float) $r['jasa_anggota'], (float) $r['total_shu'], $r['status_pencairan'],
            ];
        }

        ExcelExport::download('shu_' . $tahun, 'Distribusi SHU ' . $tahun . ' KUTT Suka Makmur',
            ['No SHU', 'Tahun', 'No Anggota', 'Nama', 'Jasa Modal', 'Jasa Anggota', 'Total SHU', 'Status'], $out);
    }

    // ================================================================ helpers

    /**
     * Generate nomor urut transaksi: PREFIX-YYYYMMDD-#### unik.
     * Urutan numerik didapat dari segmen ke-3 (hindari suffix -2/-3 dari
     * baris jurnal ganda ikut terhitung sebagai nomor baru).
     */
    private function nextNo(string $prefix, string $table, string $column): string
    {
        $datePart = date('Ymd');
        $like = $prefix . '-' . $datePart . '-%';
        $rows = Database::all(
            "SELECT $column AS no FROM `$table` WHERE $column LIKE ?",
            [$like]
        );
        $max = 0;
        foreach ($rows as $row) {
            $parts = explode('-', (string) $row['no']);
            if (count($parts) >= 3 && ctype_digit($parts[2])) {
                $max = max($max, (int) $parts[2]);
            }
        }

        return $prefix . '-' . $datePart . '-' . str_pad((string) ($max + 1), 4, '0', STR_PAD_LEFT);
    }

    /**
     * Tulis baris jurnal double-entry. $lines: list of [account_code, debet, kredit].
     * journal_id unik per batch (uq_je_journal_id), jadi satu ID untuk satu grup
     * berita jurnal; ref voucher dicatat per baris.
     */
    private function writeJournal(string $tanggal, string $ref, array $lines, string $keterangan, ?string $journalId = null): void
    {
        $journalId ??= $this->nextNo('JRN', 'journal_entries', 'journal_id');
        $seq = 0;
        foreach ($lines as [$code, $debet, $kredit]) {
            if ($debet <= 0 && $kredit <= 0) {
                continue;
            }
            $seq++;
            $id = $seq === 1 ? $journalId : $journalId . '-' . $seq;
            Database::insert('journal_entries', [
                'journal_id' => $id,
                'ref_voucher' => mb_substr($ref, 0, 30),
                'tanggal'    => $tanggal,
                'account_code' => $code,
                'debet'      => $debet,
                'kredit'     => $kredit,
                'keterangan' => mb_substr($keterangan, 0, 255),
                'created_by' => (int) (Auth::user()['id'] ?? 0),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }
}
