<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use RuntimeException;

/**
 * Sales order (penjualan multi-channel).
 * - Channel MARKETPLACE: order dari checkout publik (status NEW, stok dipotong
 *   saat order dibuat agar tidak overbook, dikembalikan bila dibatalkan).
 * - Channel POS: transaksi kasir langsung PAID/COMPLETED + stok dipotong.
 *
 * TOTAL order SELALU dihitung dari SUM(items.total_price) — tidak pernah
 * disimpan di kolom header, sehingga tidak bisa desinkron.
 */
final class Sale
{
    public const CHANNELS = ['MARKETPLACE', 'POS'];
    public const PAYMENTS = ['CASH', 'TRANSFER', 'QRIS', 'COD', 'SALDO', 'TABUNGAN'];
    public const STATUSES = ['NEW', 'PAID', 'PROCESSING', 'COMPLETED', 'CANCELLED'];

    public static function find(int $id): ?array
    {
        return Database::first(
            'SELECT so.*, u.full_name AS cashier_name
             FROM sales_orders so LEFT JOIN users u ON u.id = so.cashier_user_id
             WHERE so.id = ?',
            [$id]
        );
    }

    public static function findByOrderNo(string $orderNo): ?array
    {
        return Database::first(
            'SELECT so.*, u.full_name AS cashier_name
             FROM sales_orders so LEFT JOIN users u ON u.id = so.cashier_user_id
             WHERE so.order_no = ?',
            [$orderNo]
        );
    }

    public static function items(int $orderId): array
    {
        return Database::all(
            'SELECT soi.*, p.image_path FROM sales_order_items soi
             LEFT JOIN products p ON p.id = soi.product_id
             WHERE soi.order_id = ? ORDER BY soi.id',
            [$orderId]
        );
    }

    public static function total(array $order): float
    {
        return (float) (Database::scalar(
            'SELECT COALESCE(SUM(total_price), 0) FROM sales_order_items WHERE order_id = ?',
            [(int) $order['id']]
        ) ?? 0);
    }

    public static function itemCount(int $orderId): int
    {
        return (int) (Database::scalar(
            'SELECT COALESCE(SUM(quantity), 0) FROM sales_order_items WHERE order_id = ?',
            [$orderId]
        ) ?? 0);
    }

    /** Daftar order utk dashboard dengan filter + pagination ringan. */
    public static function paginate(array $filters, int $page = 1, int $perPage = 20): array
    {
        $where = ['1=1'];
        $params = [];
        if (($filters['status'] ?? '') !== '' && in_array($filters['status'], self::STATUSES, true)) {
            $where[] = 'so.status = ?';
            $params[] = $filters['status'];
        }
        if (($filters['channel'] ?? '') !== '' && in_array($filters['channel'], self::CHANNELS, true)) {
            $where[] = 'so.channel = ?';
            $params[] = $filters['channel'];
        }
        if (($filters['cashier'] ?? 0) > 0) {
            $where[] = 'so.cashier_user_id = ?';
            $params[] = (int) $filters['cashier'];
        }
        if (($filters['q'] ?? '') !== '') {
            $where[] = '(so.order_no LIKE ? OR so.buyer_name LIKE ? OR so.buyer_phone LIKE ?)';
            $like = '%' . $filters['q'] . '%';
            array_push($params, $like, $like, $like);
        }
        if (($filters['from'] ?? '') !== '') {
            $where[] = 'DATE(so.created_at) >= ?';
            $params[] = $filters['from'];
        }
        if (($filters['to'] ?? '') !== '') {
            $where[] = 'DATE(so.created_at) <= ?';
            $params[] = $filters['to'];
        }

        $whereSql = implode(' AND ', $where);
        $total = (int) Database::scalar("SELECT COUNT(*) FROM sales_orders so WHERE $whereSql", $params);
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $pages);

        $rows = Database::all(
            "SELECT so.*, u.full_name AS cashier_name,
                    COALESCE((SELECT SUM(soi.total_price) FROM sales_order_items soi WHERE soi.order_id = so.id), 0) AS total_amount,
                    COALESCE((SELECT SUM(soi.quantity) FROM sales_order_items soi WHERE soi.order_id = so.id), 0) AS item_count
             FROM sales_orders so
             LEFT JOIN users u ON u.id = so.cashier_user_id
             WHERE $whereSql
             ORDER BY so.id DESC
             LIMIT $perPage OFFSET " . (($page - 1) * $perPage),
            $params
        );

        return ['rows' => $rows, 'total' => $total, 'page' => $page, 'pages' => $pages];
    }

    /**
     * Ringkasan penjualan per periode utk laporan dashboard.
     * @return array{revenue:float, paidRevenue:float, orders:int, byDay:list<array<string,mixed>>, byCashier:list<array<string,mixed>>, byProduct:list<array<string,mixed>>, byChannel:array<string,float>}
     */
    public static function report(string $from, string $to, ?int $cashierId = null): array
    {
        $scope = 'so.status NOT IN (\'NEW\', \'CANCELLED\')';
        $params = [$from, $to];
        $cashierSql = '';
        if ($cashierId !== null && $cashierId > 0) {
            $cashierSql = ' AND so.cashier_user_id = ?';
            $params[] = $cashierId;
        }

        $revenue = (float) Database::scalar(
            "SELECT COALESCE(SUM(soi.total_price), 0)
             FROM sales_order_items soi JOIN sales_orders so ON so.id = soi.order_id
             WHERE DATE(so.created_at) BETWEEN ? AND ? AND $scope $cashierSql",
            $params
        );
        $paidRevenue = (float) Database::scalar(
            "SELECT COALESCE(SUM(soi.total_price), 0)
             FROM sales_order_items soi JOIN sales_orders so ON so.id = soi.order_id
             WHERE DATE(so.created_at) BETWEEN ? AND ? AND so.status IN ('PAID','PROCESSING','COMPLETED') $cashierSql",
            $params
        );
        $orders = (int) Database::scalar(
            "SELECT COUNT(*) FROM sales_orders so
             WHERE DATE(so.created_at) BETWEEN ? AND ? AND $scope $cashierSql",
            $params
        );

        $byDay = Database::all(
            "SELECT DATE(so.created_at) AS tanggal, COUNT(DISTINCT so.id) AS orders,
                    COALESCE(SUM(soi.total_price), 0) AS total
             FROM sales_orders so JOIN sales_order_items soi ON soi.order_id = so.id
             WHERE DATE(so.created_at) BETWEEN ? AND ? AND $scope $cashierSql
             GROUP BY DATE(so.created_at) ORDER BY tanggal ASC",
            $params
        );

        $byCashier = Database::all(
            "SELECT COALESCE(u.full_name, '(Marketplace)') AS kasir, COUNT(DISTINCT so.id) AS orders,
                    COALESCE(SUM(soi.total_price), 0) AS total
             FROM sales_orders so
             JOIN sales_order_items soi ON soi.order_id = so.id
             LEFT JOIN users u ON u.id = so.cashier_user_id
             WHERE DATE(so.created_at) BETWEEN ? AND ? AND $scope $cashierSql
             GROUP BY u.id ORDER BY total DESC",
            $params
        );

        $byProduct = Database::all(
            "SELECT soi.product_name, soi.product_id,
                    COALESCE(SUM(soi.quantity), 0) AS qty,
                    COALESCE(SUM(soi.total_price), 0) AS total
             FROM sales_order_items soi JOIN sales_orders so ON so.id = soi.order_id
             WHERE DATE(so.created_at) BETWEEN ? AND ? AND $scope $cashierSql
             GROUP BY soi.product_id, soi.product_name ORDER BY total DESC LIMIT 10",
            $params
        );

        $byChannel = [];
        foreach (self::CHANNELS as $ch) {
            // Placeholder order: from, to, channel, [cashier] — channel harus
            // sebelum param kasir karena posisinya lebih awal di SQL.
            $channelParams = [$from, $to, $ch];
            if ($cashierId !== null && $cashierId > 0) {
                $channelParams[] = $cashierId;
            }
            $byChannel[$ch] = (float) Database::scalar(
                "SELECT COALESCE(SUM(soi.total_price), 0)
                 FROM sales_order_items soi JOIN sales_orders so ON so.id = soi.order_id
                 WHERE DATE(so.created_at) BETWEEN ? AND ? AND so.channel = ? AND $scope $cashierSql",
                $channelParams
            );
        }

        return [
            'revenue'     => $revenue,
            'paidRevenue' => $paidRevenue,
            'orders'      => $orders,
            'byDay'       => $byDay,
            'byCashier'   => $byCashier,
            'byProduct'   => $byProduct,
            'byChannel'   => $byChannel,
        ];
    }

    /**
     * Buat order + item (snapshot harga) + potong stok, dalam transaksi DB.
     * $items: list of ['product_id' => int, 'quantity' => int].
     * Pembayaran SALDO: saldo anggota dipotong di transaksi yang sama
     * (row-locked); transaksi ditolak bila saldo tidak mencukupi.
     *
     * @param list<array{product_id:int, quantity:int}> $items
     * @return array{0: int, 1: string} [orderId, orderNo]
     * @throws RuntimeException bila item tidak valid / stok kurang / saldo kurang
     */
    public static function createOrder(array $header, array $items, int $userId): array
    {
        $items = self::normalizeItems($items);
        if ($items === []) {
            throw new RuntimeException('Keranjang kosong atau item tidak valid.');
        }

        $isSaldo = (($header['payment_method'] ?? '') === 'SALDO');
        $memberId = (int) ($header['member_id'] ?? 0);
        if ($isSaldo && $memberId <= 0) {
            throw new RuntimeException('Pembayaran saldo memerlukan anggota teridentifikasi.');
        }
        $isTabungan = (($header['payment_method'] ?? '') === 'TABUNGAN');
        $savingsAccountId = (int) ($header['savings_account_id'] ?? 0);
        if ($isTabungan && $savingsAccountId <= 0) {
            throw new RuntimeException('Pembayaran tabungan memerlukan akun tabungan.');
        }

        Database::beginTransaction();
        try {
            $orderNo = self::nextOrderNo();
            $orderId = Database::insert('sales_orders', $header + [
                'order_no'      => $orderNo,
                'stock_applied' => 0,
                'created_at'    => date('Y-m-d H:i:s'),
            ]);
            $orderId = (int) $orderId;

            $total = 0.0;
            foreach ($items as $item) {
                $product = Database::first('SELECT * FROM products WHERE id = ? FOR UPDATE', [$item['product_id']]);
                if ($product === null || (int) $product['is_active'] !== 1) {
                    throw new RuntimeException('Produk tidak tersedia: #' . $item['product_id']);
                }

                $qty = $item['quantity'];
                $lineTotal = round((float) $product['price'] * $qty, 2);
                $total += $lineTotal;

                Database::insert('sales_order_items', [
                    'order_id'     => $orderId,
                    'product_id'   => (int) $product['id'],
                    'product_name' => (string) $product['name'],
                    'unit_price'   => (float) $product['price'],
                    'quantity'     => $qty,
                    'total_price'  => $lineTotal,
                ]);

                StockMovement::apply((int) $product['id'], -$qty, 'SALE', $orderNo, 'Penjualan ' . $orderNo, $userId);
            }

            if ($isSaldo) {
                MemberWallet::apply(
                    $memberId,
                    -round($total, 2),
                    'PEMBAYARAN',
                    'Pembayaran order ' . $orderNo,
                    $orderNo,
                    $orderId,
                    $userId > 0 ? $userId : null
                );
            }

            if ($isTabungan) {
                Savings::apply(
                    $savingsAccountId,
                    'PEMBAYARAN',
                    round($total, 2),
                    $orderId,
                    'Pembayaran order ' . $orderNo,
                    $userId > 0 ? $userId : null
                );
            }

            Database::exec('UPDATE sales_orders SET stock_applied = 1 WHERE id = ?', [$orderId]);
            Database::commit();

            return [$orderId, $orderNo];
        } catch (RuntimeException $e) {
            Database::rollBack();
            throw $e;
        } catch (\Throwable $e) {
            Database::rollBack();
            error_log('[KUTT][Sale] createOrder failed: ' . $e->getMessage());
            throw new RuntimeException('Terjadi kesalahan saat menyimpan pesanan. Silakan coba kembali.');
        }
    }

    /** POS: tandai lunas saat transaksi dibuat (cashier selesai bayar). */
    public static function createPosOrder(array $header, array $items, int $userId): array
    {
        $header['status'] = 'COMPLETED';
        $header['payment_method'] = in_array($header['payment_method'] ?? 'CASH', self::PAYMENTS, true)
            ? $header['payment_method'] : 'CASH';
        $header['paid_at'] = date('Y-m-d H:i:s');
        $header['completed_at'] = date('Y-m-d H:i:s');

        return self::createOrder($header, $items, $userId);
    }

    /**
     * Update status order dengan konsistensi stok.
     * - ke CANCELLED: kembalikan stok bila sebelumnya sudah dipotong.
     * - COMPLETED: set completed_at.
     */
    public static function setStatus(int $orderId, string $status, int $userId): void
    {
        if (!in_array($status, self::STATUSES, true)) {
            throw new RuntimeException('Status tidak valid.');
        }
        $order = self::find($orderId);
        if ($order === null) {
            throw new RuntimeException('Order tidak ditemukan.');
        }
        if ($order['status'] === $status) {
            return;
        }
        if ($order['status'] === 'CANCELLED') {
            throw new RuntimeException('Order sudah dibatalkan dan tidak bisa diubah lagi.');
        }

        Database::beginTransaction();
        try {
            if ($status === 'CANCELLED' && (int) $order['stock_applied'] === 1) {
                foreach (self::items($orderId) as $item) {
                    if ($item['product_id'] !== null) {
                        StockMovement::apply(
                            (int) $item['product_id'],
                            (int) $item['quantity'],
                            'CANCEL',
                            (string) $order['order_no'],
                            'Pembatalan order ' . $order['order_no'],
                            $userId
                        );
                    }
                }
            }

            // Refund saldo anggota saat pembatalan (sekali saja — order yang
            // sudah CANCELLED tidak bisa diubah lagi, dicek di atas).
            if ($status === 'CANCELLED' && (string) $order['payment_method'] === 'SALDO'
                && (int) ($order['member_id'] ?? 0) > 0) {
                MemberWallet::apply(
                    (int) $order['member_id'],
                    (float) self::total($order),
                    'REFUND',
                    'Refund pembatalan order ' . $order['order_no'],
                    (string) $order['order_no'],
                    $orderId,
                    $userId > 0 ? $userId : null
                );
            }

            // Refund tabungan saat pembatalan.
            if ($status === 'CANCELLED' && (string) $order['payment_method'] === 'TABUNGAN'
                && (int) ($order['savings_account_id'] ?? 0) > 0) {
                Savings::apply(
                    (int) $order['savings_account_id'],
                    'REFUND',
                    (float) self::total($order),
                    $orderId,
                    'Refund pembatalan order ' . $order['order_no'],
                    $userId > 0 ? $userId : null
                );
            }

            $extra = match ($status) {
                'PAID'      => ', paid_at = NOW()',
                'COMPLETED' => ', completed_at = NOW()',
                default     => '',
            };
            Database::exec("UPDATE sales_orders SET status = ? $extra WHERE id = ?", [$status, $orderId]);
            Database::commit();
        } catch (\Throwable $e) {
            Database::rollBack();
            if ($e instanceof RuntimeException) {
                throw $e;
            }
            error_log('[KUTT][Sale] setStatus failed: ' . $e->getMessage());
            throw new RuntimeException('Gagal memperbarui status pesanan.');
        }
    }

    /**
     * Hapus permanen satu transaksi (khusus SUPER_ADMIN).
     * Menjamin konsistensi: stok dikembalikan bila belum dikembalikan,
     * saldo anggota direfund bila dibayar dengan saldo dan belum direfund,
     * lalu order + item dihapus (wallet_transactions tetap tersimpan sebagai
     * jejak audit dengan order_id menjadi NULL via ON DELETE SET NULL).
     *
     * @throws RuntimeException
     */
    public static function destroy(int $orderId, int $userId): void
    {
        $order = self::find($orderId);
        if ($order === null) {
            throw new RuntimeException('Pesanan tidak ditemukan.');
        }
        // Order CANCELLED boleh dihapus (pembersihan data): stok & saldo sudah
        // dikembalikan saat pembatalan, jadi tidak ada yang perlu dipulihkan.
        // Order aktif dihapus lewat jalur pemulihan di bawah.
        $isCancelled = ((string) $order['status'] === 'CANCELLED');

        Database::beginTransaction();
        try {
            if (!$isCancelled && (int) $order['stock_applied'] === 1) {
                foreach (self::items($orderId) as $item) {
                    if ($item['product_id'] !== null) {
                        StockMovement::apply(
                            (int) $item['product_id'],
                            (int) $item['quantity'],
                            'CANCEL',
                            (string) $order['order_no'],
                            'Penghapusan order ' . $order['order_no'] . ' oleh Super Admin',
                            $userId
                        );
                    }
                }
            }

            if (!$isCancelled && (string) $order['payment_method'] === 'SALDO' && (int) ($order['member_id'] ?? 0) > 0) {
                MemberWallet::apply(
                    (int) $order['member_id'],
                    (float) self::total($order),
                    'REFUND',
                    'Penghapusan order ' . $order['order_no'] . ' (Super Admin)',
                    (string) $order['order_no'],
                    $orderId,
                    $userId > 0 ? $userId : null
                );
            }

            if (!$isCancelled && (string) $order['payment_method'] === 'TABUNGAN' && (int) ($order['savings_account_id'] ?? 0) > 0) {
                Savings::apply(
                    (int) $order['savings_account_id'],
                    'REFUND',
                    (float) self::total($order),
                    $orderId,
                    'Penghapusan order ' . $order['order_no'] . ' (Super Admin)',
                    $userId > 0 ? $userId : null
                );
            }

            Database::exec('DELETE FROM sales_orders WHERE id = ?', [$orderId]);
            Database::commit();
        } catch (\Throwable $e) {
            Database::rollBack();
            if ($e instanceof RuntimeException) {
                throw $e;
            }
            error_log('[KUTT][Sale] destroy failed: ' . $e->getMessage());
            throw new RuntimeException('Gagal menghapus pesanan.');
        }
    }

    /**
     * Batalkan otomatis order MARKETPLACE berstatus NEW yang menggantung
     * lebih dari $hours jam. Checkout publik memotong stok sejak order
     * dibuat; tanpa pembatalan otomatis stok tertahan tanpa batas waktu.
     * Pembatalan lewat setStatus() sehingga stok (dan saldo, bila relevan)
     * dikembalikan dengan jalur refund yang sudah teruji. Dipanggil lazy
     * (dashboard & daftar pesanan) — tidak perlu cron.
     *
     * @return int jumlah order yang dibatalkan
     */
    public static function expireStaleNewOrders(int $hours = 24): int
    {
        $stale = Database::all(
            "SELECT id FROM sales_orders
             WHERE status = 'NEW' AND channel = 'MARKETPLACE' AND stock_applied = 1
               AND created_at < (NOW() - INTERVAL " . max(1, $hours) . " HOUR)
             ORDER BY id ASC LIMIT 100"
        );

        $count = 0;
        foreach ($stale as $row) {
            try {
                self::setStatus((int) $row['id'], 'CANCELLED', 0);
                $count++;
            } catch (\Throwable $e) {
                // Satu order bermasalah tidak boleh menghentikan batch.
                error_log('[KUTT][Sale] expire order #' . $row['id'] . ' gagal: ' . $e->getMessage());
            }
        }

        return $count;
    }

    /**
     * Normalisasi & validasi item keranjang (server-side truth).
     * @return list<array{product_id:int, quantity:int}>
     */
    private static function normalizeItems(array $raw): array
    {
        $out = [];
        foreach ($raw as $item) {
            if (!is_array($item)) {
                continue;
            }
            $pid = (int) ($item['product_id'] ?? 0);
            $qty = (int) ($item['quantity'] ?? 0);
            if ($pid <= 0 || $qty <= 0 || $qty > 999) {
                continue;
            }
            $out[$pid] = ['product_id' => $pid, 'quantity' => ($out[$pid]['quantity'] ?? 0) + $qty];
        }

        return array_values($out);
    }

    /** No order unik: JUAL-YYYYMMDD-#### */
    public static function nextOrderNo(): string
    {
        $datePart = date('Ymd');
        $max = (int) (Database::scalar(
            "SELECT COALESCE(MAX(CAST(SUBSTRING_INDEX(order_no, '-', -1) AS UNSIGNED)), 0)
             FROM sales_orders WHERE order_no LIKE ?",
            ['JUAL-' . $datePart . '-%']
        ) ?? 0);

        return 'JUAL-' . $datePart . '-' . str_pad((string) ($max + 1), 4, '0', STR_PAD_LEFT);
    }
}
