<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Roles;
use App\Core\Validator;
use App\Models\Card;
use App\Models\Member;
use App\Models\MemberWallet;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Savings;
use App\Models\Setting;
use App\Models\StockMovement;
use App\Support\ExcelExport;
use App\Support\Uploader;
use RuntimeException;

/**
 * Dashboard produk & penjualan:
 *  - CRUD produk + kartu stok (Purchases/Adjustment)
 *  - Kasir POS multi-kasir (setiap user mencatat transaksinya sendiri)
 *  - Daftar order semua channel + update status
 *  - Laporan penjualan per kasir/produk/hari + export Excel
 */
final class SalesController extends Controller
{
    // ================================================== PRODUK & STOK

    public function products(): void
    {
        Roles::requirePermission('products');
        $user = (array) Auth::user();

        $q = trim((string) ($_GET['q'] ?? ''));
        $category = trim((string) ($_GET['kategori'] ?? ''));
        $products = Product::all();
        if ($q !== '') {
            $needle = mb_strtolower($q);
            $products = array_values(array_filter($products, static function (array $p) use ($needle): bool {
                return str_contains(mb_strtolower((string) $p['name']), $needle)
                    || str_contains(mb_strtolower((string) $p['sku']), $needle);
            }));
        }
        if ($category !== '') {
            $products = array_values(array_filter($products, static fn (array $p): bool => (string) $p['category'] === $category));
        }

        $summary = Product::inventorySummary();

        $this->view('sales/products', [
            'pageTitle'    => 'Produk & Stok',
            'pageSubtitle' => 'Katalog multi-marketplace: harga, stok, dan status publikasi',
            'products'     => $products,
            'categories'   => Product::categories(),
            'category'     => $category,
            'q'            => $q,
            'summary'      => $summary,
            'canManage'    => Roles::can($user['role'], 'product.manage'),
            'allowedViews' => Roles::allowedViews($user['role']),
            'activeView'   => 'products',
        ]);
    }

    public function saveProduct(): void
    {
        Roles::requirePermission('product.manage');
        Csrf::validate();
        $user = (array) Auth::user();

        [$ok, $data, $errors] = Validator::check($_POST, [
            'name'       => 'required|max:160',
            'category'   => 'max:60',
            'price'      => 'required|numeric',
            'cost_price' => 'numeric',
            'stock'      => 'numeric',
            'min_stock'  => 'numeric',
            'unit'       => 'max:20',
            'sku'        => 'max:40',
            'description' => 'max:2000',
        ]);
        if (!$ok || (float) $data['price'] < 0) {
            flash_set('error', reset($errors) ?: 'Data produk belum lengkap.');
            redirect('/products');
        }

        $id = (int) ($_POST['id'] ?? 0);
        $isUpdate = $id > 0;
        $imagePath = null;
        try {
            $imagePath = Uploader::image($_FILES['image'] ?? null, 'products');
        } catch (RuntimeException $e) {
            flash_set('error', $e->getMessage());
            redirect('/products');
        }

        $name = (string) $data['name'];
        $category = $data['category'] !== '' ? (string) $data['category'] : null;
        $description = $data['description'] !== '' ? (string) $data['description'] : null;
        $price = round((float) $data['price'], 2);
        $costPrice = ($data['cost_price'] ?? '') !== '' ? round((float) $data['cost_price'], 2) : null;
        $minStock = max(0, (int) ($data['min_stock'] ?? 5));
        $unit = trim((string) ($data['unit'] ?? 'pcs')) !== '' ? (string) $data['unit'] : 'pcs';
        $isActive = ($_POST['is_active'] ?? '0') === '1' ? 1 : 0;

        Database::beginTransaction();
        try {
            if ($isUpdate) {
                $existing = Product::find($id);
                if ($existing === null) {
                    throw new RuntimeException('Produk tidak ditemukan.');
                }
                // Stok produk existing diubah lewat menu koreksi stok (audit),
                // bukan lewat form ini.
                $sql = 'UPDATE products SET name = ?, category = ?, description = ?, price = ?,
                        cost_price = ?, min_stock = ?, unit = ?, is_active = ?';
                $params = [$name, $category, $description, $price, $costPrice, $minStock, $unit, $isActive];
                if ($imagePath !== null) {
                    $sql .= ', image_path = ?';
                    $params[] = $imagePath;
                }
                $sql .= ', updated_at = NOW() WHERE id = ?';
                $params[] = $id;
                Database::exec($sql, $params);
                $sku = (string) $existing['sku'];
            } else {
                $sku = trim((string) ($data['sku'] ?? '')) !== ''
                    ? (string) $data['sku']
                    : $this->nextSku();
                if (Product::findBySku($sku) !== null) {
                    throw new RuntimeException('SKU sudah digunakan produk lain.');
                }
                $payload = [
                    'sku'        => $sku,
                    'name'       => $name,
                    'category'   => $category,
                    'description' => $description,
                    'price'      => $price,
                    'cost_price' => $costPrice,
                    'min_stock'  => $minStock,
                    'unit'       => $unit,
                    'is_active'  => $isActive,
                    'stock'      => max(0, (int) ($data['stock'] ?? 0)),
                    'created_by' => (int) $user['id'],
                    'created_at' => date('Y-m-d H:i:s'),
                ];
                if ($imagePath !== null) {
                    $payload['image_path'] = $imagePath;
                }
                $id = Product::create($payload, (int) $user['id']);
            }
            Database::commit();
        } catch (RuntimeException $e) {
            Database::rollBack();
            if ($imagePath !== null) {
                Uploader::delete($imagePath);
            }
            flash_set('error', $e->getMessage());
            redirect('/products');
        } catch (\Throwable $e) {
            Database::rollBack();
            error_log('[KUTT][Product] save failed: ' . $e->getMessage());
            flash_set('error', 'Terjadi kesalahan. Silakan coba kembali.');
            redirect('/products');
        }

        if ($imagePath !== null && $isUpdate) {
            Uploader::delete((string) ($existing['image_path'] ?? ''));
        }

        Audit::log($isUpdate ? 'UPDATE' : 'CREATE', 'Produk disimpan: ' . $name . ' (' . $sku . ')', 'PRODUK', $sku);
        flash_set('success', 'Data berhasil disimpan.');
        redirect('/products');
    }

    /** Koreksi stok (audit penuh via kartu stok). */
    public function adjustStock(string $id): void
    {
        Roles::requirePermission('product.manage');
        Csrf::validate();
        $user = (array) Auth::user();

        $product = Product::find((int) $id);
        if ($product === null) {
            flash_set('error', 'Produk tidak ditemukan.');
            redirect('/products');
        }

        $mode = ($_POST['mode'] ?? 'IN') === 'OUT' ? 'OUT' : 'IN';
        $qty = abs((int) ($_POST['qty'] ?? 0));
        if ($qty <= 0) {
            flash_set('error', 'Jumlah koreksi harus lebih dari nol.');
            redirect('/products/stock/' . (int) $id);
        }

        $change = $mode === 'IN' ? $qty : -$qty;
        try {
            Database::beginTransaction();
            StockMovement::apply(
                (int) $product['id'],
                $change,
                'ADJUSTMENT',
                null,
                mb_substr(trim((string) ($_POST['note'] ?? '')), 0, 255) ?: ('Koreksi stok ' . $mode),
                (int) $user['id']
            );
            Database::commit();
        } catch (RuntimeException $e) {
            Database::rollBack();
            flash_set('error', $e->getMessage());
            redirect('/products/stock/' . (int) $id);
        } catch (\Throwable $e) {
            Database::rollBack();
            error_log('[KUTT][Stock] adjust failed: ' . $e->getMessage());
            flash_set('error', 'Terjadi kesalahan. Silakan coba kembali.');
            redirect('/products/stock/' . (int) $id);
        }

        Audit::log('UPDATE', 'Koreksi stok ' . $product['sku'] . ' (' . ($mode === 'IN' ? '+' : '-') . $qty . ')', 'STOK', $product['sku']);

        // Peringatan stok kritis ke admin setelah koreksi.
        $after = StockMovement::currentStock((int) $product['id']);
        if ($after <= (int) $product['min_stock']) {
            \App\Models\Notification::push(
                'Stok kritis: ' . $product['name'],
                'Stok ' . $product['sku'] . ' tersisa ' . $after . ' ' . $product['unit'] . ' (min. ' . (int) $product['min_stock'] . '). Segera restock.',
                'WARNING',
                '/products/stock/' . (int) $product['id'],
                ['role' => Roles::SUPER_ADMIN]
            );
        }

        flash_set('success', 'Stok berhasil dikoreksi. Stok sekarang: ' . $after . ' ' . $product['unit'] . '.');
        redirect('/products/stock/' . (int) $id);
    }

    /** Kartu stok satu produk. */
    public function stockCard(string $id): void
    {
        Roles::requirePermission('products');
        $user = (array) Auth::user();

        $product = Product::find((int) $id);
        if ($product === null) {
            flash_set('error', 'Produk tidak ditemukan.');
            redirect('/products');
        }

        $this->view('sales/stock_card', [
            'pageTitle'    => 'Kartu Stok: ' . $product['name'],
            'pageSubtitle' => $product['sku'] . ' — stok saat ini ' . (int) $product['stock'] . ' ' . $product['unit'],
            'product'      => $product,
            'movements'    => StockMovement::forProduct((int) $id, 100),
            'canManage'    => Roles::can($user['role'], 'product.manage'),
            'allowedViews' => Roles::allowedViews($user['role']),
            'activeView'   => 'products',
        ]);
    }

    public function deleteProduct(string $id): void
    {
        Roles::requirePermission('product.manage');
        Csrf::validate();

        $product = Product::find((int) $id);
        if ($product === null) {
            flash_set('error', 'Produk tidak ditemukan.');
            redirect('/products');
        }

        // Produk yang pernah terjual tidak boleh dihapus (integritas laporan);
        // dinonaktifkan saja.
        $sold = (int) Database::scalar(
            'SELECT COUNT(*) FROM sales_order_items WHERE product_id = ?',
            [(int) $id]
        );
        if ($sold > 0) {
            Database::exec('UPDATE products SET is_active = 0, updated_at = NOW() WHERE id = ?', [(int) $id]);
            Audit::log('UPDATE', 'Produk dinonaktifkan (riwayat penjualan): ' . $product['sku'], 'PRODUK', $product['sku']);
            flash_set('info', 'Produk pernah terjual, sehingga dinonaktifkan (bukan dihapus) agar riwayat penjualan tetap utuh.');
            redirect('/products');
        }

        Database::exec('DELETE FROM products WHERE id = ?', [(int) $id]);
        Uploader::delete((string) ($product['image_path'] ?? ''));
        Audit::log('DELETE', 'Produk dihapus: ' . $product['sku'], 'PRODUK', $product['sku']);
        flash_set('success', 'Data berhasil dihapus.');
        redirect('/products');
    }

    // ================================================== KASIR POS (MULTI)

    /** Terminal kasir: pilih produk, keranjang, bayar — per user login. */
    public function pos(): void
    {
        Roles::requirePermission('sales.pos');
        $user = (array) Auth::user();

        $today = date('Y-m-d');
        $myToday = (int) Database::scalar(
            "SELECT COUNT(*) FROM sales_orders WHERE channel = 'POS' AND cashier_user_id = ? AND DATE(created_at) = ?",
            [(int) $user['id'], $today]
        );
        $myTodayTotal = (float) Database::scalar(
            "SELECT COALESCE(SUM(soi.total_price), 0) FROM sales_order_items soi
             JOIN sales_orders so ON so.id = soi.order_id
             WHERE so.channel = 'POS' AND so.cashier_user_id = ? AND DATE(so.created_at) = ? AND so.status != 'CANCELLED'",
            [(int) $user['id'], $today]
        );

        $catalog = Database::all(
            'SELECT id, sku, barcode, name, price, stock, unit, image_path FROM products
             WHERE is_active = 1 AND stock > 0 ORDER BY name ASC LIMIT 300'
        );

        $this->view('sales/pos', [
            'pageTitle'     => 'Kasir (POS)',
            'pageSubtitle'  => 'Transaksi penjualan langsung — ' . $user['full_name'],
            'catalog'       => $catalog,
            'myToday'       => $myToday,
            'myTodayTotal'  => $myTodayTotal,
            'pendingTopups' => MemberWallet::pendingTopups(10),
            'savingsAccounts' => Savings::options(),
            'allowedViews'  => Roles::allowedViews($user['role']),
            'activeView'    => 'sales_pos',
        ]);
    }

    /** Simpan transaksi POS (JSON keranjang dari UI kasir). */
    public function storePos(): void
    {
        Roles::requirePermission('sales.pos');
        Csrf::validate();
        $user = (array) Auth::user();

        $items = json_decode((string) ($_POST['items'] ?? '[]'), true);
        if (!is_array($items) || $items === []) {
            flash_set('error', 'Keranjang kosong.');
            redirect('/sales/pos');
        }

        $payment = (string) ($_POST['payment_method'] ?? 'CASH');
        if (!in_array($payment, Sale::PAYMENTS, true)) {
            $payment = 'CASH';
        }
        $buyerName = mb_substr(trim((string) ($_POST['buyer_name'] ?? '')), 0, 120);
        $buyerPhone = mb_substr(trim((string) ($_POST['buyer_phone'] ?? '')), 0, 30);

        // Pembayaran SALDO ANGGOTA: wajib scan anggota + verifikasi PIN 6 digit.
        $memberId = 0;
        if ($payment === 'SALDO') {
            $memberId = (int) ($_POST['member_id'] ?? 0);
            if ($memberId <= 0) {
                flash_set('error', 'Scan kartu anggota terlebih dahulu untuk pembayaran saldo.');
                redirect('/sales/pos');
            }
            try {
                MemberWallet::verifyPin($memberId, trim((string) ($_POST['wallet_pin'] ?? '')));
            } catch (RuntimeException $e) {
                flash_set('error', $e->getMessage());
                redirect('/sales/pos');
            }
        }

        // Pembayaran TABUNGAN: potong dari akun tabungan terpilih.
        $savingsAccountId = 0;
        if ($payment === 'TABUNGAN') {
            $savingsAccountId = (int) ($_POST['savings_account_id'] ?? 0);
            $account = $savingsAccountId > 0 ? Savings::find($savingsAccountId) : null;
            if ($account === null || (int) $account['is_active'] !== 1) {
                flash_set('error', 'Pilih akun tabungan yang aktif untuk pembayaran.');
                redirect('/sales/pos');
            }
        }

        try {
            [$orderId, $orderNo] = Sale::createPosOrder([
                'channel'        => 'POS',
                'buyer_name'     => $buyerName !== '' ? $buyerName : 'Pelanggan Langsung',
                'buyer_phone'    => $buyerPhone,
                'cashier_user_id' => (int) $user['id'],
                'payment_method' => $payment,
                'member_id'      => $memberId > 0 ? $memberId : null,
                'savings_account_id' => $savingsAccountId > 0 ? $savingsAccountId : null,
            ], $items, (int) $user['id']);
        } catch (RuntimeException $e) {
            flash_set('error', $e->getMessage());
            redirect('/sales/pos');
        }

        $payNote = match ($payment) {
            'SALDO'    => ' (bayar saldo anggota)',
            'TABUNGAN' => ' (bayar tabungan)',
            default    => '',
        };
        Audit::log('CREATE', 'Transaksi kasir ' . $orderNo . ' oleh ' . $user['username'] . $payNote, 'PENJUALAN', $orderNo);
        flash_set('success', 'Transaksi berhasil. No: ' . $orderNo . ' — <a href="/sales/receipt/' . $orderId . '" class="underline font-bold">Cetak Struk</a>');
        redirect('/sales/pos');
    }

    /**
     * Lookup anggota untuk kasir (scan QR kartu anggota di POS).
     * Menerima token QR mentah (32 hex), URL verifikasi penuh, atau no. anggota.
     * JSON: {found, member:{id, member_no, name, balance, has_pin}}
     */
    public function posMember(string $token): void
    {
        Roles::requirePermission('sales.pos');

        header('Content-Type: application/json; charset=UTF-8');
        $token = trim(rawurldecode($token));
        if (preg_match('#/anggota/verify/([0-9a-f]{32})#i', $token, $m)) {
            $token = $m[1]; // QR berisi URL verifikasi — ambil tokennya saja.
        }

        $member = null;
        if (preg_match('/^[0-9a-f]{32}$/i', $token)) {
            $member = Card::memberByToken(strtolower($token));
        }
        if ($member === null) {
            $member = Database::first(
                'SELECT id, member_no, full_name FROM members WHERE member_no = ? OR nia = ? LIMIT 1',
                [$token, $token]
            );
        }

        if ($member === null) {
            echo json_encode(['found' => false, 'message' => 'Anggota tidak ditemukan. Kartu belum terdaftar atau token salah.']);
            exit;
        }

        echo json_encode([
            'found' => true,
            'member' => [
                'id'        => (int) $member['id'],
                'member_no' => (string) $member['member_no'],
                'name'      => (string) $member['full_name'],
                'balance'   => MemberWallet::balance((int) $member['id']),
                'has_pin'   => MemberWallet::hasPin((int) $member['id']),
            ],
        ]);
        exit;
    }

    /**
     * Top up saldo langsung oleh kasir/admin (uang kas diterima):
     * wallet bertambah + voucher kas masuk + jurnal (1101 D / 2104 K).
     */
    public function topup(): void
    {
        Roles::requirePermission('sales.pos');
        Csrf::validate();
        $user = (array) Auth::user();

        $member = Database::first('SELECT * FROM members WHERE id = ?', [(int) ($_POST['member_id'] ?? 0)]);
        if ($member === null) {
            flash_set('error', 'Anggota tidak ditemukan. Scan kartu anggota terlebih dahulu.');
            redirect('/sales/pos');
        }

        $amount = round((float) ($_POST['amount'] ?? 0), 2);
        if ($amount <= 0 || $amount > 100000000) {
            flash_set('error', 'Nominal top up tidak valid.');
            redirect('/sales/pos');
        }

        $note = mb_substr(trim((string) ($_POST['note'] ?? '')), 0, 255) ?: 'Top up saldo oleh kasir ' . $user['full_name'];

        // Nomor voucher KAS dihitung dari MAX sehingga bisa berbenturan bila
        // dua top up berjalan bersamaan; UNIQUE uq_ct_voucher_no menolaknya.
        // Ulangi maksimal 3x dengan nomor baru — aman karena semua penulisan
        // dalam satu transaksi yang di-rollback penuh saat benturan.
        $attempts = 0;
        while (true) {
            $attempts++;
            Database::beginTransaction();
            try {
                $voucherNo = MemberWallet::nextNo('KAS', 'cash_transactions', 'voucher_no');
                $result = MemberWallet::apply(
                    (int) $member['id'],
                    $amount,
                    'TOPUP',
                    $note,
                    $voucherNo,
                    null,
                    (int) $user['id']
                );

                // Uang kas: voucher kas masuk + jurnal ganda (kas D, kewajiban saldo K).
                Database::insert('cash_transactions', [
                    'voucher_no'    => $voucherNo,
                    'jenis_kas'     => 'MASUK',
                    'account_code'  => '2104',
                    'nominal'       => $amount,
                    'keterangan'    => 'Top up saldo ' . $member['member_no'] . ' — ' . $note,
                    'tanggal_trans' => date('Y-m-d'),
                    'created_by'    => (int) $user['id'],
                    'created_at'    => date('Y-m-d H:i:s'),
                ]);
                $this->writeTopupJournal(date('Y-m-d'), $voucherNo, $amount, 'Top up saldo anggota ' . $member['member_no']);

                Database::commit();
                break;
            } catch (\PDOException $e) {
                Database::rollBack();
                if ($attempts < 3 && self::isDuplicateKey($e)) {
                    continue; // benturan nomor voucher — coba nomor berikutnya
                }
                error_log('[KUTT][Wallet] topup failed: ' . $e->getMessage());
                flash_set('error', 'Top up gagal. Silakan coba kembali.');
                redirect('/sales/pos');
            } catch (\Throwable $e) {
                Database::rollBack();
                error_log('[KUTT][Wallet] topup failed: ' . $e->getMessage());
                flash_set('error', 'Top up gagal. Silakan coba kembali.');
                redirect('/sales/pos');
            }
        }

        Audit::log('CREATE', 'Top up saldo ' . $member['member_no'] . ' ' . rupiah($amount) . ' (' . $result['transaction_no'] . ')', 'SALDO', $result['transaction_no']);
        flash_set('success', 'Top up berhasil. Saldo ' . $member['full_name'] . ' sekarang ' . rupiah($result['balance_after']) . '.');
        redirect('/sales/pos');
    }

    /** Proses pengajuan top up anggota: approve (saldo+kas) / reject. */
    public function processTopup(string $id): void
    {
        Roles::requirePermission('sales.pos');
        Csrf::validate();
        $user = (array) Auth::user();

        $request = MemberWallet::findTopup((int) $id);
        if ($request === null || (string) $request['status'] !== 'PENDING') {
            flash_set('error', 'Pengajuan tidak ditemukan atau sudah diproses.');
            redirect('/sales/pos');
        }

        $action = ($_POST['action'] ?? '') === 'approve' ? 'APPROVED' : 'REJECTED';
        if ($action === 'APPROVED') {
            $amount = (float) $request['amount'];

            // Anti double-approve (race): kunci baris pengajuan (FOR UPDATE) dan
            // cek ulang status DI DALAM transaksi. Klik ganda/klik bersamaan
            // hanya memproses satu kali — yang kedua melihat status sudah berubah.
            // Nomor voucher KAS bisa berbenturan saat bersamaan (MAX-based);
            // ulangi maksimal 3x dengan nomor baru, rollback penuh tiap percobaan.
            $alreadyProcessed = false;
            $attempts = 0;
            while (true) {
                $attempts++;
                Database::beginTransaction();
                try {
                    $fresh = Database::first(
                        'SELECT status FROM topup_requests WHERE id = ? FOR UPDATE',
                        [(int) $request['id']]
                    );
                    if ($fresh === null || (string) $fresh['status'] !== 'PENDING') {
                        // Sudah diproses request lain — jangan top up dua kali.
                        // Keluar dengan flag; redirect dilakukan SETELAH loop
                        // (redirect melempar exception yang tak boleh tertangkap
                        // catch di bawah).
                        Database::rollBack();
                        $alreadyProcessed = true;
                        break;
                    }

                    $voucherNo = MemberWallet::nextNo('KAS', 'cash_transactions', 'voucher_no');
                    $result = MemberWallet::apply(
                        (int) $request['member_id'],
                        $amount,
                        'TOPUP',
                        'Top up disetujui dari pengajuan ' . $request['request_no'],
                        $voucherNo,
                        null,
                        (int) $user['id']
                    );
                    Database::insert('cash_transactions', [
                        'voucher_no'    => $voucherNo,
                        'jenis_kas'     => 'MASUK',
                        'account_code'  => '2104',
                        'nominal'       => $amount,
                        'keterangan'    => 'Top up saldo ' . $request['member_no'] . ' — pengajuan ' . $request['request_no'],
                        'tanggal_trans' => date('Y-m-d'),
                        'created_by'    => (int) $user['id'],
                        'created_at'    => date('Y-m-d H:i:s'),
                    ]);
                    $this->writeTopupJournal(date('Y-m-d'), $voucherNo, $amount, 'Top up saldo anggota ' . $request['member_no'] . ' (' . $request['request_no'] . ')');
                    Database::exec(
                        'UPDATE topup_requests SET status = ?, processed_by = ?, processed_at = NOW() WHERE id = ?',
                        ['APPROVED', (int) $user['id'], (int) $request['id']]
                    );
                    Database::commit();
                    break;
                } catch (\PDOException $e) {
                    Database::rollBack();
                    if ($attempts < 3 && self::isDuplicateKey($e)) {
                        continue; // benturan nomor voucher — coba nomor berikutnya
                    }
                    error_log('[KUTT][Wallet] approve topup failed: ' . $e->getMessage());
                    flash_set('error', 'Gagal menyetujui pengajuan top up.');
                    redirect('/sales/pos');
                } catch (\Throwable $e) {
                    Database::rollBack();
                    error_log('[KUTT][Wallet] approve topup failed: ' . $e->getMessage());
                    flash_set('error', 'Gagal menyetujui pengajuan top up.');
                    redirect('/sales/pos');
                }
            }
            if ($alreadyProcessed) {
                flash_set('info', 'Pengajuan ' . $request['request_no'] . ' sudah diproses sebelumnya.');
                redirect('/sales/pos');
            }
            Audit::log('UPDATE', 'Pengajuan top up disetujui: ' . $request['request_no'] . ' ' . rupiah($amount), 'SALDO', (string) $request['request_no']);
            flash_set('success', 'Pengajuan ' . $request['request_no'] . ' disetujui. Saldo ' . $request['full_name'] . ' sekarang ' . rupiah($result['balance_after']) . '.');
        } else {
            // REJECT juga terkunci FOR UPDATE supaya tidak bisa menimpa hasil
            // approve yang diproses petugas lain pada saat yang bersamaan.
            $rejectConflict = false;
            Database::beginTransaction();
            try {
                $fresh = Database::first(
                    'SELECT status FROM topup_requests WHERE id = ? FOR UPDATE',
                    [(int) $request['id']]
                );
                if ($fresh === null || (string) $fresh['status'] !== 'PENDING') {
                    Database::rollBack();
                    $rejectConflict = true;
                } else {
                    Database::exec(
                        'UPDATE topup_requests SET status = ?, processed_by = ?, processed_at = NOW() WHERE id = ?',
                        ['REJECTED', (int) $user['id'], (int) $request['id']]
                    );
                    Database::commit();
                }
            } catch (\Throwable $e) {
                Database::rollBack();
                error_log('[KUTT][Wallet] reject topup failed: ' . $e->getMessage());
                flash_set('error', 'Gagal menolak pengajuan top up.');
                redirect('/sales/pos');
            }
            if ($rejectConflict) {
                flash_set('info', 'Pengajuan ' . $request['request_no'] . ' sudah diproses sebelumnya.');
                redirect('/sales/pos');
            }
            Audit::log('UPDATE', 'Pengajuan top up ditolak: ' . $request['request_no'], 'SALDO', (string) $request['request_no']);
            flash_set('info', 'Pengajuan ' . $request['request_no'] . ' ditolak.');
        }

        redirect('/sales/pos');
    }

    /** Hapus permanen transaksi — KHUSUS SUPER_ADMIN. */
    public function destroyOrder(string $id): void
    {
        Roles::requirePermission('sales.view');
        $user = (array) Auth::user();
        if (($user['role'] ?? '') !== Roles::SUPER_ADMIN) {
            flash_set('error', 'Akses ditolak: hanya Super Admin yang dapat menghapus transaksi.');
            redirect('/sales/' . (int) $id);
        }
        Csrf::validate();

        try {
            Sale::destroy((int) $id, (int) $user['id']);
        } catch (RuntimeException $e) {
            flash_set('error', $e->getMessage());
            redirect('/sales');
        }

        Audit::log('DELETE', 'Transaksi dihapus permanen oleh Super Admin (' . $user['username'] . ')', 'PENJUALAN', (string) (int) $id);
        flash_set('success', 'Transaksi berhasil dihapus permanen. Stok dan saldo anggota sudah disesuaikan.');
        redirect('/sales');
    }

    /**
     * Lookup produk untuk kasir (scan barcode produk). JSON:
     * {found, product:{id, name, price, stock, unit}}.
     * Menerima barcode produk, SKU, atau "BC-00042" (hasil generate otomatis).
     */
    public function posProduct(string $code): void
    {
        Roles::requirePermission('sales.pos');

        header('Content-Type: application/json; charset=UTF-8');
        $code = trim(rawurldecode($code));

        $product = Product::findByBarcode($code);
        if ($product === null) {
            $product = Product::findBySku($code);
        }
        if ($product === null && preg_match('/^BC-(\d+)$/i', $code, $m)) {
            $product = Product::find((int) $m[1]);
            if ($product !== null && ((int) $product['is_active'] !== 1 || (int) $product['stock'] <= 0)) {
                $product = null; // barcode otomatis hanya valid utk produk aktif berstok
            }
        }

        if ($product === null) {
            echo json_encode(['found' => false, 'message' => 'Produk tidak ditemukan. Barcode belum terdaftar.']);
            exit;
        }
        if ((int) $product['stock'] <= 0) {
            echo json_encode(['found' => false, 'message' => 'Stok produk "' . $product['name'] . '" habis.']);
            exit;
        }

        echo json_encode([
            'found'   => true,
            'product' => [
                'id'    => (int) $product['id'],
                'name'  => (string) $product['name'],
                'price' => (float) $product['price'],
                'stock' => (int) $product['stock'],
                'unit'  => (string) $product['unit'],
            ],
        ]);
        exit;
    }

    /** Struk teks ESC/POS via route /sales/receipt/{id}/plain. */
    public function receiptPlainAction(string $id): void
    {
        $this->receipt($id, 'plain');
    }

    /**
     * Struk/invoice thermal 80mm — tampil di browser & siap cetak.
     * $format: 'html' (auto window.print()) atau 'plain' (teks ESC/POS 42 kolom).
     */
    public function receipt(string $id, string $format = 'html'): void
    {
        Roles::requirePermission('sales.pos');

        $order = Sale::find((int) $id);
        if ($order === null) {
            flash_set('error', 'Pesanan tidak ditemukan.');
            redirect('/sales');
        }

        $items = Sale::items((int) $id);
        $total = Sale::total((array) $order);
        $setting = Setting::all();

        $member = null;
        if ((int) ($order['member_id'] ?? 0) > 0) {
            $member = Member::find((int) $order['member_id']);
        }

        if ($format === 'plain') {
            header('Content-Type: text/plain; charset=UTF-8');
            echo $this->receiptPlain((array) $order, $items, $total, $setting);
            exit;
        }

        $walletBalanceAfter = null;
        if ((string) $order['payment_method'] === 'SALDO' && $member !== null) {
            $walletBalanceAfter = MemberWallet::balance((int) $member['id']);
        }

        $this->viewPlain('sales/receipt', [
            'order'              => $order,
            'items'              => $items,
            'total'              => $total,
            'setting'            => $setting,
            'member'             => $member,
            'walletBalanceAfter' => $walletBalanceAfter,
        ]);
        exit;
    }

    /** Struk teks 42 kolom (ESC/POS friendly) utk printer thermal. */
    private function receiptPlain(array $order, array $items, float $total, array $setting): string
    {
        $w = 42;
        $brand = (string) ($setting['brandName'] ?? 'KUTT SUKA MAKMUR');
        $addr = (string) ($setting['footerAddress'] ?? '');
        $phone = (string) ($setting['footerPhone'] ?? '');
        $wrap = static function (string $s, int $width) use ($w): array {
            $words = preg_split('/\s+/', trim($s)) ?: [];
            $lines = [];
            $cur = '';
            foreach ($words as $word) {
                if ($cur === '') {
                    $cur = $word;
                } elseif (mb_strlen($cur . ' ' . $word) <= $width) {
                    $cur .= ' ' . $word;
                } else {
                    $lines[] = $cur;
                    $cur = $word;
                }
            }
            if ($cur !== '') {
                $lines[] = $cur;
            }

            return $lines;
        };
        $line = static fn (): string => str_repeat('-', $w);
        $row = static function (string $l, string $r) use ($w): string {
            $space = $w - mb_strlen($l) - mb_strlen($r);

            return $space > 0 ? $l . str_repeat(' ', $space) . $r : $l . ' ' . $r;
        };
        $center = static function (string $s) use ($w): string {
            $pad = max(0, intdiv($w - mb_strlen($s), 2));

            return str_repeat(' ', $pad) . $s;
        };

        $out = [];
        foreach ($wrap($brand, $w) as $l) {
            $out[] = $center(strtoupper($l));
        }
        foreach ($wrap($addr, $w) as $l) {
            $out[] = $center($l);
        }
        if ($phone !== '') {
            $out[] = $center('Telp/WA: ' . $phone);
        }
        $out[] = $line();
        $out[] = $row('No', (string) $order['order_no']);
        $out[] = $row('Tanggal', date('d/m/Y H:i', strtotime((string) $order['created_at'])));
        $out[] = $row('Kasir', (string) ($order['cashier_name'] ?? '-'));
        $out[] = $row('Pembeli', (string) $order['buyer_name']);
        $out[] = $line();
        foreach ($items as $it) {
            $nameLines = $wrap((string) $it['product_name'], $w - 14);
            $qty = rtrim(rtrim(number_format((float) $it['quantity'], 0, ',', '.'), '0'), '.');
            $qtyPrice = $qty . ' x ' . number_format((float) $it['unit_price'], 0, ',', '.');
            $out[] = $row($nameLines[0] ?? '', $qtyPrice);
            $count = count($nameLines);
            for ($i = 1; $i < $count; $i++) {
                $out[] = '  ' . $nameLines[$i];
            }
            $out[] = $row('   ' . number_format((float) $it['total_price'], 0, ',', '.'), '');
        }
        $out[] = $line();
        $out[] = $row('TOTAL', 'Rp ' . number_format($total, 0, ',', '.'));
        $out[] = $row('Bayar', (string) $order['payment_method']);
        $out[] = $line();
        $out[] = $center('Terima kasih telah berbelanja');
        $out[] = $center('- KUTT SUKA MAKMUR -');
        $out[] = '';

        return implode("\n", $out) . "\n";
    }

    // ================================================== DAFTAR ORDER

    public function orders(): void
    {
        Roles::requirePermission('sales.view');
        $user = (array) Auth::user();

        // Pembersihan lazy: order marketplace NEW menggantung > 24 jam dibatalkan
        // otomatis (stok dikembalikan) agar tidak menahan stok tanpa batas.
        Sale::expireStaleNewOrders();

        $filters = [
            'q'       => trim((string) ($_GET['q'] ?? '')),
            'status'  => (string) ($_GET['status'] ?? ''),
            'channel' => (string) ($_GET['channel'] ?? ''),
            'cashier' => (int) ($_GET['cashier'] ?? 0),
            'from'    => (string) ($_GET['from'] ?? ''),
            'to'      => (string) ($_GET['to'] ?? ''),
        ];
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $result = Sale::paginate($filters, $page, 20);

        $cashiers = Database::all(
            "SELECT DISTINCT u.id, u.full_name FROM users u
             JOIN sales_orders so ON so.cashier_user_id = u.id ORDER BY u.full_name"
        );
        $newCount = (int) Database::scalar("SELECT COUNT(*) FROM sales_orders WHERE status = 'NEW'");

        $this->view('sales/orders', [
            'pageTitle'    => 'Pesanan Penjualan',
            'pageSubtitle' => 'Marketplace & kasir — status, konfirmasi, dan pembatalan',
            'result'       => $result,
            'filters'      => $filters,
            'cashiers'     => $cashiers,
            'newCount'     => $newCount,
            'statuses'     => Sale::STATUSES,
            'canManage'    => Roles::can($user['role'], 'sales.manage'),
            'canDelete'    => ($user['role'] ?? '') === Roles::SUPER_ADMIN,
            'allowedViews' => Roles::allowedViews($user['role']),
            'activeView'   => 'sales_orders',
        ]);
    }

    /** Detail order (partial/modal). */
    public function orderDetail(string $id): void
    {
        Roles::requirePermission('sales.view');
        $user = (array) Auth::user();

        $order = Sale::find((int) $id);
        if ($order === null) {
            flash_set('error', 'Pesanan tidak ditemukan.');
            redirect('/sales');
        }

        $this->view('sales/order_detail', [
            'pageTitle'    => 'Pesanan ' . $order['order_no'],
            'pageSubtitle' => $order['channel'] . ' — ' . $order['buyer_name'],
            'order'        => $order,
            'items'        => Sale::items((int) $id),
            'total'        => Sale::total((array) $order),
            'canManage'    => Roles::can($user['role'], 'sales.manage'),
            'canDelete'    => ($user['role'] ?? '') === Roles::SUPER_ADMIN,
            'allowedViews' => Roles::allowedViews($user['role']),
            'activeView'   => 'sales_orders',
        ]);
    }

    public function updateStatus(string $id): void
    {
        Roles::requirePermission('sales.manage');
        Csrf::validate();
        $user = (array) Auth::user();

        $status = (string) ($_POST['status'] ?? '');
        try {
            Sale::setStatus((int) $id, $status, (int) $user['id']);
        } catch (RuntimeException $e) {
            flash_set('error', $e->getMessage());
            redirect('/sales/' . (int) $id);
        }

        Audit::log('UPDATE', 'Status pesanan ' . $status . ' untuk order #' . (int) $id, 'PENJUALAN', (string) (int) $id);
        flash_set('success', 'Status pesanan diperbarui: ' . $status . '.');
        redirect('/sales/' . (int) $id);
    }

    // ================================================== LAPORAN

    public function reports(): void
    {
        Roles::requirePermission('sales.view');
        $user = (array) Auth::user();

        $from = (string) ($_GET['from'] ?? date('Y-m-01'));
        $to = (string) ($_GET['to'] ?? date('Y-m-d'));
        if (strtotime($from) === false) { $from = date('Y-m-01'); }
        if (strtotime($to) === false) { $to = date('Y-m-d'); }
        $cashier = (int) ($_GET['cashier'] ?? 0);

        $report = Sale::report($from, $to, $cashier > 0 ? $cashier : null);
        $cashiers = Database::all(
            "SELECT DISTINCT u.id, u.full_name FROM users u
             JOIN sales_orders so ON so.cashier_user_id = u.id ORDER BY u.full_name"
        );
        $inventory = Product::inventorySummary();

        $this->view('sales/reports', [
            'pageTitle'    => 'Laporan Penjualan & Stok',
            'pageSubtitle' => tanggal($from) . ' — ' . tanggal($to),
            'report'       => $report,
            'from'         => $from,
            'to'           => $to,
            'cashier'      => $cashier,
            'cashiers'     => $cashiers,
            'inventory'    => $inventory,
            'canExport'    => Roles::can($user['role'], 'report.export'),
            'allowedViews' => Roles::allowedViews($user['role']),
            'activeView'   => 'sales_reports',
        ]);
    }

    public function exportOrders(): void
    {
        Roles::requirePermission('report.export');

        $from = (string) ($_GET['from'] ?? date('Y-m-01'));
        $to = (string) ($_GET['to'] ?? date('Y-m-d'));
        if (strtotime($from) === false) { $from = date('Y-m-01'); }
        if (strtotime($to) === false) { $to = date('Y-m-d'); }

        $rows = Database::all(
            "SELECT so.order_no, so.channel, so.created_at, so.buyer_name, so.buyer_phone,
                    COALESCE(u.full_name, '-') AS kasir, so.payment_method, so.status,
                    COALESCE(SUM(soi.quantity), 0) AS qty, COALESCE(SUM(soi.total_price), 0) AS total
             FROM sales_orders so
             LEFT JOIN users u ON u.id = so.cashier_user_id
             LEFT JOIN sales_order_items soi ON soi.order_id = so.id
             WHERE DATE(so.created_at) BETWEEN ? AND ? AND so.status NOT IN ('NEW','CANCELLED')
             GROUP BY so.id ORDER BY so.id DESC LIMIT 10000",
            [$from, $to]
        );

        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                $r['order_no'], $r['channel'], (string) $r['created_at'], $r['buyer_name'],
                $r['buyer_phone'], (string) $r['kasir'], $r['payment_method'], $r['status'],
                (int) $r['qty'], (float) $r['total'],
            ];
        }

        Audit::log('EXPORT', 'Export penjualan ' . $from . ' s/d ' . $to, 'PENJUALAN');
        ExcelExport::download('penjualan-' . $from . '-' . $to, 'Laporan Penjualan',
            ['No Order', 'Channel', 'Tanggal', 'Pembeli', 'No HP', 'Kasir', 'Pembayaran', 'Status', 'Qty', 'Total (Rp)'], $out);
    }

    public function exportStock(): void
    {
        Roles::requirePermission('report.export');

        $rows = Database::all(
            'SELECT p.sku, p.name, p.category, p.unit, p.stock, p.min_stock, p.price, COALESCE(p.cost_price, 0) AS cost_price
             FROM products p ORDER BY p.name ASC LIMIT 5000'
        );

        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                $r['sku'], $r['name'], (string) $r['category'], $r['unit'],
                (int) $r['stock'], (int) $r['min_stock'], (float) $r['price'], (float) $r['cost_price'],
                (int) $r['stock'] <= (int) $r['min_stock'] ? 'PERLU RESTOCK' : 'AMAN',
            ];
        }

        ExcelExport::download('stok-produk', 'Laporan Stok Produk',
            ['SKU', 'Nama', 'Kategori', 'Satuan', 'Stok', 'Min', 'Harga Jual', 'Harga Pokok', 'Status'], $out);
    }

    // ================================================== helpers

    /** Deteksi error MySQL duplicate entry (pelanggaran UNIQUE) untuk retry nomor voucher. */
    private static function isDuplicateKey(\PDOException $e): bool
    {
        $driverCode = (int) ($e->errorInfo[1] ?? $e->getCode());

        return $driverCode === 1062 || $driverCode === 1022 || str_contains($e->getMessage(), 'Duplicate entry');
    }

    /** Jurnal ganda top up saldo: kas (D) vs kewajiban saldo belanja anggota (K). */
    private function writeTopupJournal(string $tanggal, string $voucherNo, float $amount, string $keterangan): void
    {
        $journalId = MemberWallet::nextNo('JRN', 'journal_entries', 'journal_id');
        Database::insert('journal_entries', [
            'journal_id'   => $journalId,
            'ref_voucher'  => mb_substr($voucherNo, 0, 30),
            'tanggal'      => $tanggal,
            'account_code' => '1101',
            'debet'        => $amount,
            'kredit'       => 0.0,
            'keterangan'   => mb_substr($keterangan, 0, 255),
            'created_by'   => (int) (Auth::user()['id'] ?? 0),
            'created_at'   => date('Y-m-d H:i:s'),
        ]);
        Database::insert('journal_entries', [
            'journal_id'   => $journalId . '-2',
            'ref_voucher'  => mb_substr($voucherNo, 0, 30),
            'tanggal'      => $tanggal,
            'account_code' => '2104',
            'debet'        => 0.0,
            'kredit'       => $amount,
            'keterangan'   => mb_substr($keterangan, 0, 255),
            'created_by'   => (int) (Auth::user()['id'] ?? 0),
            'created_at'   => date('Y-m-d H:i:s'),
        ]);
    }

    private function nextSku(): string
    {
        $max = (int) (Database::scalar(
            "SELECT COALESCE(MAX(CAST(SUBSTRING(sku, 5) AS UNSIGNED)), 0) FROM products WHERE sku LIKE 'PRD-%'"
        ) ?? 0);

        return 'PRD-' . str_pad((string) ($max + 1), 3, '0', STR_PAD_LEFT);
    }
}
