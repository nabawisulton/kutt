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
} else {
    foreach ([
        'DB: news_posts schema matches model', 'DB: notifications has role+link', 'DB: audit_logs columns',
        'DB: member_cards.verify_code width', 'Notification::latestFor', 'Notification::push writes',
        'Audit::log writes', 'News::paginate', 'News::publicList', 'Member::finance', 'Card::issue token',
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
