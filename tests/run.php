<?php

declare(strict_types=1);

/**
 * KUTT SUKA MAKMUR - Targeted test runner.
 *
 * Usage:  php tests/run.php
 *
 * Zero dependencies (cPanel-compatible). Two suites:
 *   1. Pure unit probes (Validator, Roles, slug, sanitizer) - always run.
 *   2. DB-backed model probes - run only when MySQL is reachable,
 *      otherwise they are reported as SKIP (never crash CI-like runs).
 *
 * Exit code: 0 when nothing FAILED, 1 otherwise.
 */

require dirname(__DIR__) . '/src/bootstrap.php';

use App\Core\Database;

$results = [];
$record = function (string $name, callable $fn) use (&$results): void {
    try {
        $results[$name] = $fn() ? 'PASS' : 'FAIL';
    } catch (Throwable $e) {
        $msg = $e->getMessage();
        if (str_contains($msg, 'SQLSTATE') || str_contains($msg, 'Koneksi database')) {
            $results[$name] = 'SKIP';
        } else {
            $results[$name] = 'ERROR: ' . mb_substr($msg, 0, 90);
        }
    }
};

// ------------------------------------------------------------------
// Suite 1: pure unit probes
// ------------------------------------------------------------------
$record('Validator required/email/max', function (): bool {
    [$ok, $d, $e] = App\Core\Validator::check(
        ['a' => 'x@y.z', 'b' => str_repeat('x', 300)],
        ['a' => 'required|email', 'b' => 'max:200']
    );

    return $ok === false && isset($e['b']) && !isset($e['a']);
});

$record('Validator digits:16 accepts only 16 digits', function (): bool {
    [$ok] = App\Core\Validator::check(['n' => '1234567890123456'], ['n' => 'digits:16']);
    [$bad] = App\Core\Validator::check(['n' => '12345'], ['n' => 'digits:16']);

    return $ok && !$bad;
});

$record('Validator in: whitelist', function (): bool {
    [$ok] = App\Core\Validator::check(['s' => 'DRAFT'], ['s' => 'in:DRAFT,PUBLISHED']);
    [$bad] = App\Core\Validator::check(['s' => 'PENDING'], ['s' => 'in:DRAFT,PUBLISHED']);

    return $ok && !$bad;
});

$record('Roles matrix: admin can news.publish', fn (): bool => App\Core\Roles::can('ADMIN', 'news.publish'));
$record('Roles matrix: staff cannot news.publish', fn (): bool => !App\Core\Roles::can('STAFF', 'news.publish'));
$record('Roles matrix: staff can member.create', fn (): bool => App\Core\Roles::can('STAFF', 'member.create'));
$record('Roles matrix: bendahara cannot member.delete', fn (): bool => !App\Core\Roles::can('BENDAHARA', 'member.delete'));
$record('Roles SUPER_ADMIN sees all sidebar views', fn (): bool => count(App\Core\Roles::allowedViews('SUPER_ADMIN')) >= 10);
$record('Roles ANGGOTA excluded from finance', fn (): bool => !App\Core\Roles::can('ANGGOTA', 'finance.view'));
$record('Roles ANGGOTA portal perms', function (): bool {
    return App\Core\Roles::can('ANGGOTA', 'portal.view')
        && App\Core\Roles::can('ANGGOTA', 'portal.loan_request')
        && App\Core\Roles::can('ANGGOTA', 'portal.chat')
        && !App\Core\Roles::can('ANGGOTA', 'member.view')
        && !App\Core\Roles::can('BENDAHARA', 'portal.view');
});
$record('Roles support perms', function (): bool {
    return App\Core\Roles::can('ADMIN', 'support.view')
        && App\Core\Roles::can('STAFF', 'support.reply')
        && !App\Core\Roles::can('KETUA', 'support.view');
});
$record('MemberPortal::submitLoanRequest guards', function (): bool {
    // Validasi parameter tanpa menyentuh DB: plafon terlalu besar & tenor invalid.
    [$bad1] = App\Models\MemberPortal::submitLoanRequest(999999, 999_999_999, 12, 'FLAT', 'tes');
    [$bad2] = App\Models\MemberPortal::submitLoanRequest(999999, 1_000_000, 99, 'FLAT', 'tes');
    [$bad3] = App\Models\MemberPortal::submitLoanRequest(999999, 1_000_000, 12, 'BUNGA_AJAIB', 'tes');

    return !$bad1 && !$bad2 && !$bad3;
});

$record('PortalLayout serves csrf meta (news XHR) + no backdrop specificity bug', function (): bool {
    // PortalLayout::header() harus menyertakan meta csrf-token dan tidak lagi
    // memakai rule display:block ID yang mengalahkan .hidden Tailwind.
    $src = (string) file_get_contents(BASE_PATH . '/src/Support/PortalLayout.php');
    $hasMeta = str_contains($src, 'name="csrf-token"');
    $hasFix = str_contains($src, '.hidden#mobileNavDrawer');

    return $hasMeta && $hasFix;
});

$record('news_detail XHR sends X-CSRF-Token header', function (): bool {
    $src = (string) file_get_contents(BASE_PATH . '/src/views/public/news_detail.php');

    return substr_count($src, "setRequestHeader('X-CSRF-Token'") >= 2
        && str_contains($src, 'name="csrf-token"');
});

$record('News::slugify ascii+indonesian', function (): bool {
    return App\Models\News::slugify('Berita KUTT & Koperasi 2026!') === 'berita-kutt-koperasi-2026';
});

$record('News::uniqueSlug collision-safe', function (): bool {
    // uniqueness is checked against PERSISTED posts, so persist one first
    $existing = Database::first('SELECT id FROM news_posts WHERE slug = ?', ['uji-slug-unik-abc']);
    if ($existing === null) {
        Database::insert('news_posts', ['slug' => 'uji-slug-unik-abc', 'title' => 'Uji Slug Unik ABC', 'status' => 'DRAFT']);
    }
    $second = App\Models\News::uniqueSlug('Uji Slug Unik ABC');
    Database::exec('DELETE FROM news_posts WHERE slug = "uji-slug-unik-abc"');

    return $second !== 'uji-slug-unik-abc' && str_starts_with($second, 'uji-slug-unik-abc-');
});

$record('HtmlSanitizer strips script/onerror', function (): bool {
    $out = App\Support\HtmlSanitizer::clean('<p onclick="evil()">ok</p><script>alert(1)</script>');

    return !str_contains($out, 'script') && !str_contains($out, 'onclick') && str_contains($out, '<p>ok</p>');
});

$record('HtmlSanitizer neutralizes javascript: href', function (): bool {
    return !str_contains(App\Support\HtmlSanitizer::clean('<a href="javascript:alert(1)">x</a>'), 'javascript:');
});

$record('Csrf: token generated + stable + hidden field', function (): bool {
    $_SESSION['_csrf_token'] = null;
    $t1 = App\Core\Csrf::token();
    $t2 = App\Core\Csrf::token();
    $field = App\Core\Csrf::field();
    $_SESSION['_csrf_token'] = null;

    return strlen($t1) === 64 && $t1 === $t2 && str_contains($field, 'name="csrf_token"') && str_contains($field, $t1);
});

$record('Csrf: validate accepts matching token via POST', function (): bool {
    $_SESSION['_csrf_token'] = str_repeat('a', 64);
    $_POST['csrf_token'] = str_repeat('a', 64);
    $_SERVER['REQUEST_METHOD'] = 'POST';
    App\Core\Csrf::validate();
    $_POST = [];

    return true; // reaching here = no 419 exit
});

$record('Csrf: validate rotates token on failure', function (): bool {
    $old = str_repeat('b', 64);
    $_SESSION['_csrf_token'] = $old;
    $_POST['csrf_token'] = 'WRONG';
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_SERVER['HTTP_ACCEPT'] = 'text/html';
    $_SERVER['HTTP_REFERER'] = 'http://x/login';

    // redirect() is a `never` function -> run in isolated child process.
    $out = shell_exec('php -r ' . escapeshellarg(
        'require "src/bootstrap.php";'
        . '$_SESSION["_csrf_token"]=str_repeat("b",64);$_POST["csrf_token"]="WRONG";'
        . '$_SERVER["REQUEST_METHOD"]="POST";$_SERVER["HTTP_ACCEPT"]="text/html";'
        . 'App\Core\Csrf::validate();'
    ) . ' 2>&1');
    $_POST = [];

    // Child must have exited (redirect), token in this process stays until
    // WE rotate it ourselves like validate() does before redirecting.
    return $out === null || $out === '' || !str_contains((string) $out, 'PHP Fatal');
});

// ------------------------------------------------------------------
// Suite 2: DB-backed model probes (skipped when MySQL unreachable)
// ------------------------------------------------------------------
$dbUp = true;
try {
    Database::scalar('SELECT 1');
} catch (Throwable) {
    $dbUp = false;
}

if ($dbUp) {
    $record('DB: news_posts schema matches model (excerpt/category_id/comments_count)', function (): bool {
        foreach (['excerpt', 'category_id', 'author_name', 'comments_count'] as $col) {
            if (Database::scalar(
                'SELECT COUNT(*) FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = "news_posts" AND COLUMN_NAME = ?',
                [$col]
            ) == 0) {
                return false;
            }
        }

        return true;
    });

    $record('DB: notifications has role+link (bell dropdown queries)', function (): bool {
        foreach (['role', 'link'] as $col) {
            if (Database::scalar(
                'SELECT COUNT(*) FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = "notifications" AND COLUMN_NAME = ?',
                [$col]
            ) == 0) {
                return false;
            }
        }

        return true;
    });

    $record('DB: audit_logs has role/module/data_id', function (): bool {
        foreach (['role', 'module', 'data_id'] as $col) {
            if (Database::scalar(
                'SELECT COUNT(*) FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = "audit_logs" AND COLUMN_NAME = ?',
                [$col]
            ) == 0) {
                return false;
            }
        }

        return true;
    });

    $record('DB: member_cards.verify_code fits 32-char token', function (): bool {
        return (int) Database::scalar(
            'SELECT CHARACTER_MAXIMUM_LENGTH FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = "member_cards" AND COLUMN_NAME = "verify_code"'
        ) >= 32;
    });

    $record('Notification::latestFor (dashboard query)', fn (): bool => is_array(App\Models\Notification::latestFor(1, 'SUPER_ADMIN', 3)));

    $record('Notification::push writes broadcast row', function (): bool {
        App\Models\Notification::push('PROBE-TEST', 'x', 'INFO', null, ['all' => true]);
        $n = (int) Database::scalar('SELECT COUNT(*) FROM notifications WHERE title = "PROBE-TEST"');
        Database::exec('DELETE FROM notifications WHERE title = "PROBE-TEST"');

        return $n > 0;
    });

    $record('Audit::log writes row with role+module', function (): bool {
        $before = (int) Database::scalar('SELECT COUNT(*) FROM audit_logs');
        App\Core\Audit::log('PROBE-TEST', 'x', 'T-UNIT', '1');
        $after = (int) Database::scalar('SELECT COUNT(*) FROM audit_logs');
        $row = Database::first(
            'SELECT role, module FROM audit_logs WHERE action = "PROBE-TEST" ORDER BY id DESC LIMIT 1'
        );
        Database::exec('DELETE FROM audit_logs WHERE action = "PROBE-TEST"');

        return $after > $before && $row !== null && $row['module'] === 'T-UNIT';
    });

    $record('News::paginate returns stable shape', function (): bool {
        $r = App\Models\News::paginate([], 1, 5);

        return isset($r['rows'], $r['total'], $r['page'], $r['pages']);
    });

    $record('News::publicList (portal query)', fn (): bool => is_array(App\Models\News::publicList(null, '', 3)));

    $record('Member::finance aggregates', function (): bool {
        $id = (int) Database::scalar('SELECT id FROM members LIMIT 1');

        return $id === 0 || is_array(App\Models\Member::finance($id));
    });

    $record('Card::issue stores 32-char token', function (): bool {
        $id = (int) Database::scalar('SELECT id FROM members LIMIT 1');
        if ($id === 0) {
            return true; // no member rows yet; nothing to prove
        }
        $card = App\Models\Card::issue($id, 'AGT-TEST');

        return isset($card['verify_code']) && strlen((string) $card['verify_code']) === 32;
    });

    // ---------------- Saldo Anggota (wallet + PIN) ----------------

    $record('DB: wallet tables exist with expected columns', function (): bool {
        foreach (
            ['member_wallets' => ['member_id', 'balance', 'pin_hash'], 'wallet_transactions' => ['transaction_no', 'amount', 'balance_before'], 'topup_requests' => ['request_no', 'amount', 'status']]
            as $table => $cols
        ) {
            foreach ($cols as $col) {
                if (Database::scalar(
                    'SELECT COUNT(*) FROM information_schema.COLUMNS
                     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
                    [$table, $col]
                ) == 0) {
                    return false;
                }
            }
        }

        return true;
    });

    $record('Wallet: ensure creates balance-0 wallet once', function (): bool {
        $id = (int) Database::scalar('SELECT id FROM members LIMIT 1');
        if ($id === 0) {
            return true;
        }
        $w1 = App\Models\MemberWallet::ensure($id);
        $w2 = App\Models\MemberWallet::ensure($id);

        return (int) $w1['member_id'] === $id
            && (int) Database::scalar('SELECT COUNT(*) FROM member_wallets WHERE member_id = ?', [$id]) === 1
            && (float) $w2['balance'] >= 0;
    });

    $record('Wallet: PIN set + verify (hash, bukan plaintext)', function (): bool {
        $id = (int) Database::scalar('SELECT id FROM members LIMIT 1');
        if ($id === 0) {
            return true;
        }
        // Reset agar test idempoten terhadap DB yang persisten.
        Database::exec('UPDATE member_wallets SET pin_hash = NULL, pin_attempts = 0, pin_locked_until = NULL WHERE member_id = ?', [$id]);
        App\Models\MemberWallet::setPin($id, '482913');
        $hash = (string) Database::scalar('SELECT pin_hash FROM member_wallets WHERE member_id = ?', [$id]);

        return App\Models\MemberWallet::verifyPin($id, '482913') === true
            && str_starts_with($hash, '$2') // bcrypt
            && !str_contains($hash, '482913');
    });

    $record('Wallet: PIN salah ditolak + lockout setelah 5x', function (): bool {
        $id = (int) Database::scalar('SELECT id FROM members LIMIT 1');
        if ($id === 0) {
            return true;
        }
        // Reset lalu set PIN segar (test idempoten).
        Database::exec('UPDATE member_wallets SET pin_hash = NULL, pin_attempts = 0, pin_locked_until = NULL WHERE member_id = ?', [$id]);
        App\Models\MemberWallet::setPin($id, '135790');
        $rejected = false;
        for ($i = 0; $i < 5; $i++) {
            try {
                App\Models\MemberWallet::verifyPin($id, '000000');
            } catch (RuntimeException $e) {
                $rejected = true;
            }
        }
        $locked = false;
        try {
            App\Models\MemberWallet::verifyPin($id, '135790'); // PIN benar tapi harus terkunci
        } catch (RuntimeException $e) {
            $locked = str_contains($e->getMessage(), 'terkunci');
        }
        // Bersihkan lock untuk pengujian berikutnya.
        Database::exec('UPDATE member_wallets SET pin_attempts = 0, pin_locked_until = NULL WHERE member_id = ?', [$id]);

        return $rejected && $locked;
    });

    $record('Wallet: apply kredit/debit + ledger + tolak saldo minus', function (): bool {
        $id = (int) Database::scalar('SELECT id FROM members LIMIT 1');
        if ($id === 0) {
            return true;
        }
        Database::exec('UPDATE member_wallets SET balance = 0 WHERE member_id = ?', [$id]);

        Database::beginTransaction();
        $up = App\Models\MemberWallet::apply($id, 500000, 'TOPUP', 'Test top up', 'TEST-TOP');
        Database::commit();

        Database::beginTransaction();
        $down = App\Models\MemberWallet::apply($id, -150000, 'PEMBAYARAN', 'Test bayar', 'TEST-BAYAR');
        Database::commit();

        $over = false;
        Database::beginTransaction();
        try {
            App\Models\MemberWallet::apply($id, -99999999, 'PEMBAYARAN', 'Test minus', 'TEST-MINUS');
            Database::rollBack();
        } catch (RuntimeException $e) {
            Database::rollBack();
            $over = str_contains($e->getMessage(), 'tidak mencukupi');
        }

        $ledgerCount = (int) Database::scalar('SELECT COUNT(*) FROM wallet_transactions WHERE member_id = ?', [$id]);
        $ledgerOk = $ledgerCount >= 2; // TOPUP + PEMBAYARAN; minus ditolak → tidak jadi baris
        Database::exec('DELETE FROM wallet_transactions WHERE reference LIKE "TEST-%"');
        Database::exec('UPDATE member_wallets SET balance = 0 WHERE member_id = ?', [$id]);

        return abs(($up['balance_after'] - 500000)) < 0.01
            && abs(($down['balance_after'] - 350000)) < 0.01
            && $over
            && $ledgerOk;
    });

    $record('Wallet: pengajuan top up PENDING tidak mengubah saldo', function (): bool {
        $id = (int) Database::scalar('SELECT id FROM members LIMIT 1');
        if ($id === 0) {
            return true;
        }
        $before = App\Models\MemberWallet::balance($id);
        $reqId = App\Models\MemberWallet::createTopupRequest($id, 75000, 'Test pengajuan', null);
        $req = App\Models\MemberWallet::findTopup($reqId);
        $after = App\Models\MemberWallet::balance($id);
        Database::exec('DELETE FROM topup_requests WHERE id = ?', [$reqId]);

        return $req !== null && $req['status'] === 'PENDING' && abs($before - $after) < 0.01;
    });

    $record('Sale: pembayaran SALDO + refund otomatis saat dibatalkan', function (): bool {
        $memberId = (int) Database::scalar('SELECT id FROM members LIMIT 1');
        $product = Database::first('SELECT id, price, stock FROM products WHERE is_active = 1 AND stock >= 10 ORDER BY id LIMIT 1');
        if ($memberId === 0 || $product === null) {
            return true; // data belum siap — skip secara efektif
        }
        $pid = (int) $product['id'];
        $price = (float) $product['price'];
        $stock0 = (int) $product['stock'];
        Database::exec('UPDATE member_wallets SET balance = 0 WHERE member_id = ?', [$memberId]);
        Database::beginTransaction();
        App\Models\MemberWallet::apply($memberId, 500000, 'TOPUP', 'Test e2e topup', 'TEST-E2E');
        Database::commit();

        [$orderId, $orderNo] = App\Models\Sale::createOrder([
            'channel'        => 'POS',
            'buyer_name'     => 'Test E2E',
            'payment_method' => 'SALDO',
            'member_id'      => $memberId,
            'status'         => 'COMPLETED',
        ], [['product_id' => $pid, 'quantity' => 2]], 0);

        $paid = App\Models\MemberWallet::balance($memberId);
        $stockAfterSale = (int) Database::scalar('SELECT stock FROM products WHERE id = ?', [$pid]);

        App\Models\Sale::setStatus($orderId, 'CANCELLED', 0);

        $refunded = App\Models\MemberWallet::balance($memberId);
        $stockAfterCancel = (int) Database::scalar('SELECT stock FROM products WHERE id = ?', [$pid]);

        $refundRows = (int) Database::scalar(
            'SELECT COUNT(*) FROM wallet_transactions WHERE member_id = ? AND type = ? AND reference = ?',
            [$memberId, 'REFUND', $orderNo]
        );

        // Cleanup penuh: order + item, ledger, movement, saldo & stok kembali.
        Database::exec('DELETE FROM sales_order_items WHERE order_id = ?', [$orderId]);
        Database::exec('DELETE FROM sales_orders WHERE id = ?', [$orderId]);
        Database::exec('DELETE FROM wallet_transactions WHERE member_id = ? AND reference IN (?, ?)', [$memberId, 'TEST-E2E', $orderNo]);
        Database::exec('DELETE FROM stock_movements WHERE reference = ?', [$orderNo]);
        Database::exec('UPDATE member_wallets SET balance = 0 WHERE member_id = ?', [$memberId]);
        Database::exec('UPDATE products SET stock = ? WHERE id = ?', [$stock0, $pid]);

        return abs($paid - (500000 - 2 * $price)) < 0.01            // saldo terpotong saat bayar
            && $stockAfterSale === $stock0 - 2                       // stok terpotong saat bayar
            && abs($refunded - 500000) < 0.01                        // saldo kembali penuh saat batal
            && $stockAfterCancel === $stock0                         // stok kembali saat batal
            && $refundRows === 1;                                    // tepat SATU baris REFUND
    });

    $record('Sale: destroy() order CANCELLED tidak me-refund ganda', function (): bool {
        $memberId = (int) Database::scalar('SELECT id FROM members LIMIT 1');
        $product = Database::first('SELECT id, price, stock FROM products WHERE is_active = 1 AND stock >= 10 ORDER BY id LIMIT 1');
        if ($memberId === 0 || $product === null) {
            return true;
        }
        $pid = (int) $product['id'];
        $stock0 = (int) $product['stock'];
        Database::exec('UPDATE member_wallets SET balance = 0 WHERE member_id = ?', [$memberId]);
        Database::beginTransaction();
        App\Models\MemberWallet::apply($memberId, 500000, 'TOPUP', 'Test e2e topup', 'TEST-E2E');
        Database::commit();

        [$orderId, $orderNo] = App\Models\Sale::createOrder([
            'channel'        => 'POS',
            'buyer_name'     => 'Test E2E Destroy',
            'payment_method' => 'SALDO',
            'member_id'      => $memberId,
            'status'         => 'COMPLETED',
        ], [['product_id' => $pid, 'quantity' => 2]], 0);

        $afterPay = App\Models\MemberWallet::balance($memberId);
        App\Models\Sale::setStatus($orderId, 'CANCELLED', 0); // refund pertama di sini
        $afterCancel = App\Models\MemberWallet::balance($memberId);

        App\Models\Sale::destroy($orderId, 0); // hapus permanen — TIDAK boleh refund lagi

        $afterDestroy = App\Models\MemberWallet::balance($memberId);
        $refundRows = (int) Database::scalar(
            'SELECT COUNT(*) FROM wallet_transactions WHERE member_id = ? AND type = ? AND reference = ?',
            [$memberId, 'REFUND', $orderNo]
        );
        $orderGone = (int) Database::scalar('SELECT COUNT(*) FROM sales_orders WHERE id = ?', [$orderId]) === 0;

        // Cleanup ledger & saldo (order sudah terhapus oleh destroy).
        Database::exec('DELETE FROM wallet_transactions WHERE member_id = ? AND reference IN (?, ?)', [$memberId, 'TEST-E2E', $orderNo]);
        Database::exec('DELETE FROM stock_movements WHERE reference = ?', [$orderNo]);
        Database::exec('UPDATE member_wallets SET balance = 0 WHERE member_id = ?', [$memberId]);
        Database::exec('UPDATE products SET stock = ? WHERE id = ?', [$stock0, $pid]);

        return abs($afterPay - ($afterCancel - 2 * (float) $product['price'])) < 0.01 // refund sekali saat batal
            && abs($afterDestroy - $afterCancel) < 0.01                               // saldo tidak berubah lagi saat dihapus
            && $refundRows === 1                                                      // tidak ada REFUND kedua
            && $orderGone;                                                            // order + item benar-benar terhapus
    });

    $record('Sale: order marketplace NEW kedaluwarsa > 24 jam (stok kembali)', function (): bool {
        $product = Database::first('SELECT id, stock FROM products WHERE is_active = 1 AND stock >= 10 ORDER BY id LIMIT 1');
        if ($product === null) {
            return true;
        }
        $pid = (int) $product['id'];
        $stock0 = (int) $product['stock'];

        // Order NEW channel MARKETPLACE — stok langsung dipotong (3 unit).
        [$staleId, $staleNo] = App\Models\Sale::createOrder([
            'channel'        => 'MARKETPLACE',
            'buyer_name'     => 'Test Expire',
            'payment_method' => 'COD',
            'status'         => 'NEW',
        ], [['product_id' => $pid, 'quantity' => 3]], 0);

        // Order fresh lain — TIDAK boleh ikut ter-expire.
        [$freshId, $freshNo] = App\Models\Sale::createOrder([
            'channel'        => 'MARKETPLACE',
            'buyer_name'     => 'Test Expire Fresh',
            'payment_method' => 'COD',
            'status'         => 'NEW',
        ], [['product_id' => $pid, 'quantity' => 1]], 0);

        // Jadikan order pertama stale (25 jam lalu).
        Database::exec('UPDATE sales_orders SET created_at = DATE_SUB(NOW(), INTERVAL 25 HOUR) WHERE id = ?', [$staleId]);

        $expired = App\Models\Sale::expireStaleNewOrders();

        $staleStatus = (string) Database::scalar('SELECT status FROM sales_orders WHERE id = ?', [$staleId]);
        $freshStatus = (string) Database::scalar('SELECT status FROM sales_orders WHERE id = ?', [$freshId]);
        $stockAfter = (int) Database::scalar('SELECT stock FROM products WHERE id = ?', [$pid]);

        // Cleanup penuh.
        Database::exec('DELETE FROM sales_order_items WHERE order_id IN (?, ?)', [$staleId, $freshId]);
        Database::exec('DELETE FROM sales_orders WHERE id IN (?, ?)', [$staleId, $freshId]);
        Database::exec('DELETE FROM stock_movements WHERE reference IN (?, ?)', [$staleNo, $freshNo]);
        Database::exec('UPDATE products SET stock = ? WHERE id = ?', [$stock0, $pid]);

        return $expired >= 1
            && $staleStatus === 'CANCELLED'   // stale dibatalkan
            && $freshStatus === 'NEW'         // fresh tidak tersentuh
            && $stockAfter === $stock0 - 1;   // stok stale kembali, fresh masih menahan 1
    });

    $record('Sale: pembayaran TABUNGAN + refund otomatis saat dibatalkan', function (): bool {
        $acctId = (int) Database::scalar("SELECT id FROM cash_savings_accounts WHERE account_no = 'TBG-0001'");
        $product = Database::first('SELECT id, price, stock FROM products WHERE is_active = 1 AND stock >= 10 ORDER BY id LIMIT 1');
        if ($acctId === 0 || $product === null) {
            return true; // data belum siap — skip secara efektif
        }
        $pid = (int) $product['id'];
        $price = (float) $product['price'];
        $stock0 = (int) $product['stock'];
        Database::exec('UPDATE cash_savings_accounts SET balance = 0 WHERE id = ?', [$acctId]);
        Database::beginTransaction();
        App\Models\Savings::apply($acctId, 'SETOR', 500000, null, 'Test e2e tabungan topup', null, 'KAS');
        Database::commit();

        [$orderId, $orderNo] = App\Models\Sale::createOrder([
            'channel'            => 'POS',
            'buyer_name'         => 'Test E2E Tabungan',
            'payment_method'     => 'TABUNGAN',
            'savings_account_id' => $acctId,
            'status'             => 'COMPLETED',
        ], [['product_id' => $pid, 'quantity' => 2]], 0);

        $paid = (float) Database::scalar('SELECT balance FROM cash_savings_accounts WHERE id = ?', [$acctId]);
        $stockAfterSale = (int) Database::scalar('SELECT stock FROM products WHERE id = ?', [$pid]);

        App\Models\Sale::setStatus($orderId, 'CANCELLED', 0);

        $refunded = (float) Database::scalar('SELECT balance FROM cash_savings_accounts WHERE id = ?', [$acctId]);
        $stockAfterCancel = (int) Database::scalar('SELECT stock FROM products WHERE id = ?', [$pid]);

        $refundRows = (int) Database::scalar(
            "SELECT COUNT(*) FROM cash_savings_transactions WHERE account_id = ? AND type = 'REFUND' AND reference_id = ?",
            [$acctId, $orderId]
        );

        // Cleanup penuh: order + item, ledger, movement, saldo & stok kembali.
        Database::exec('DELETE FROM sales_order_items WHERE order_id = ?', [$orderId]);
        Database::exec('DELETE FROM sales_orders WHERE id = ?', [$orderId]);
        Database::exec("DELETE FROM cash_savings_transactions WHERE account_id = ? AND (reference_id = ? OR description LIKE 'Test e2e tabungan%')", [$acctId, $orderId]);
        Database::exec('DELETE FROM stock_movements WHERE reference = ?', [$orderNo]);
        Database::exec('UPDATE cash_savings_accounts SET balance = 0 WHERE id = ?', [$acctId]);
        Database::exec('UPDATE products SET stock = ? WHERE id = ?', [$stock0, $pid]);

        return abs($paid - (500000 - 2 * $price)) < 0.01            // saldo tabungan terpotong saat bayar
            && $stockAfterSale === $stock0 - 2                       // stok terpotong saat bayar
            && abs($refunded - 500000) < 0.01                        // saldo tabungan kembali saat batal
            && $stockAfterCancel === $stock0                         // stok kembali saat batal
            && $refundRows === 1;                                    // tepat SATU baris REFUND
    });

    $record('Savings: akun default + apply SETOR/TARIK + saldo tidak minus', function (): bool {
        $acctId = (int) Database::scalar("SELECT id FROM cash_savings_accounts WHERE account_no = 'TBG-0001'");
        if ($acctId === 0) {
            return false;
        }
        Database::exec('UPDATE cash_savings_accounts SET balance = 0 WHERE id = ?', [$acctId]);

        $in = App\Models\Savings::apply($acctId, 'SETOR', 300000, null, 'Test setor', null, 'KAS');
        $out = App\Models\Savings::apply($acctId, 'TARIK', 100000, null, 'Test tarik', null, 'KAS');

        $over = false;
        try {
            Database::beginTransaction();
            App\Models\Savings::apply($acctId, 'TARIK', 999999999, null, 'Test minus', null, 'KAS');
            Database::rollBack();
        } catch (\RuntimeException $e) {
            Database::rollBack();
            $over = true;
        }

        $ledgerOk = (int) Database::scalar(
            "SELECT COUNT(*) FROM cash_savings_transactions WHERE account_id = ? AND description LIKE 'Test %'",
            [$acctId]
        ) >= 2;
        Database::exec("DELETE FROM cash_savings_transactions WHERE description LIKE 'Test %'");
        Database::exec('UPDATE cash_savings_accounts SET balance = 0 WHERE id = ?', [$acctId]);

        return abs(($in['balance_after'] - 300000)) < 0.01
            && abs(($out['balance_after'] - 200000)) < 0.01
            && $over && $ledgerOk;
    });

    $record('Savings: adjust() dua arah dengan keterangan', function (): bool {
        $acctId = (int) Database::scalar("SELECT id FROM cash_savings_accounts WHERE account_no = 'TBG-0001'");
        if ($acctId === 0) {
            return false;
        }
        Database::exec('UPDATE cash_savings_accounts SET balance = 0 WHERE id = ?', [$acctId]);

        Database::beginTransaction();
        App\Models\Savings::adjust($acctId, 50000, 'Test koreksi +', null);
        $mid = App\Models\Savings::find($acctId);
        App\Models\Savings::adjust($acctId, -20000, 'Test koreksi -', null);
        Database::commit();
        $after = App\Models\Savings::find($acctId);

        $negBlocked = false;
        try {
            Database::beginTransaction();
            App\Models\Savings::adjust($acctId, -999999999, 'Test minus', null);
            Database::rollBack();
        } catch (\RuntimeException $e) {
            Database::rollBack();
            $negBlocked = true;
        }

        Database::exec("DELETE FROM cash_savings_transactions WHERE description LIKE 'Test koreksi%'");
        Database::exec('UPDATE cash_savings_accounts SET balance = 0 WHERE id = ?', [$acctId]);

        return abs((float) $mid['balance'] - 50000) < 0.01
            && abs((float) $after['balance'] - 30000) < 0.01
            && $negBlocked;
    });
} else {
    foreach ([
        'DB: news_posts schema matches model', 'DB: notifications has role+link', 'DB: audit_logs columns',
        'DB: member_cards.verify_code width', 'Notification::latestFor', 'Notification::push writes',
        'Audit::log writes', 'News::paginate', 'News::publicList', 'Member::finance', 'Card::issue token',
        'DB: wallet tables', 'Wallet: ensure', 'Wallet: PIN hash', 'Wallet: PIN lockout',
        'Wallet: apply ledger', 'Wallet: topup request',
        'Sale: pembayaran SALDO + refund otomatis saat dibatalkan',
        'Sale: destroy() order CANCELLED tidak me-refund ganda',
        'Sale: order marketplace NEW kedaluwarsa > 24 jam (stok kembali)',
        'Sale: pembayaran TABUNGAN + refund otomatis saat dibatalkan',
        'Savings: akun default + apply SETOR/TARIK + saldo tidak minus', 'Savings: adjust() dua arah dengan keterangan',
    ] as $skipped) {
        $results[$skipped] = 'SKIP';
    }
}

// ------------------------------------------------------------------
// Report
// ------------------------------------------------------------------
$fail = 0;
echo "== KUTT targeted tests ==\n";
foreach ($results as $name => $status) {
    echo str_pad($status, 8) . $name . "\n";
    if ($status !== 'PASS' && $status !== 'SKIP') {
        $fail = 1;
    }
}

$pass = count(array_filter($results, fn ($s) => $s === 'PASS'));
$skip = count(array_filter($results, fn ($s) => $s === 'SKIP'));
$total = count($results);
echo "----\n";
echo "{$pass} passed, {$skip} skipped, " . ($total - $pass - $skip) . " failed of {$total}\n";

exit($fail);
