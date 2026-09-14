<?php

declare(strict_types=1);

/**
 * Shared HTTP client untuk test E2E (stream wrapper, tanpa dependency curl).
 * Cookie session ditulis ulang dari Set-Cookie pada SETIAP respons
 * (login memanggil session_regenerate_id).
 */

if (!function_exists('http_req')) {
    function http_req(string $url, ?string $body, array $headers, string &$jar): array
    {
        $h = $headers;
        if ($jar !== '') {
            $h[] = 'Cookie: ' . $jar;
        }
        $ctx = stream_context_create(['http' => [
            'method'          => $body !== null ? 'POST' : 'GET',
            'header'          => implode("\r\n", $h) . "\r\n",
            'content'         => $body,
            'ignore_errors'   => true,
            'follow_location' => 0,
        ]]);
        $resp = @file_get_contents($url, false, $ctx);
        $code = 0;
        $loc = '';
        foreach ($http_response_header ?? [] as $hh) {
            if (preg_match('#HTTP/\S+\s+(\d+)#', $hh, $m)) {
                $code = (int) $m[1];
            }
            if (stripos($hh, 'Location:') === 0) {
                $loc = trim(substr($hh, 9));
            }
            if (stripos($hh, 'Set-Cookie:') === 0) {
                $pair = trim(explode(';', substr($hh, 11))[0]);
                if (str_starts_with($pair, 'KUTTSESSID')) {
                    $jar = $pair; // simpan versi TERBARU (session_regenerate)
                }
            }
        }

        return [$code, $loc, (string) $resp];
    }
}

if (!function_exists('csrf_from')) {
    /** Ambil token CSRF pertama dari sebuah halaman HTML. */
    function csrf_from(string $html): string
    {
        if (preg_match('/name="csrf_token" value="([a-f0-9]+)"/', $html, $m)) {
            return $m[1];
        }
        return '';
    }
}

if (!function_exists('login_user')) {
    /**
     * Login E2E yang SADAR alur "wajib ganti password".
     *
     * Alur: login (seed pass) -> dipaksa ke /password/change -> ganti ke
     * $testPass -> sesi siap dipakai. Jika run sebelumnya gagal di tengah
     * (password tertinggal di $testPass), pulihkan dulu lalu ulangi alur.
     * Di akhir E2E, panggil restore_password() untuk mengembalikan seed pass.
     *
     * Returns: [$ok, $redirectSetelahLogin, $jar]
     */
    function login_user(string $base, string $username, string $seedPass, string $testPass, string &$jar): array
    {
        $jar = '';
        [$c, , $html] = http_req($base . '/login', null, [], $jar);
        $token = csrf_from($html);
        if ($c !== 200 || $token === '') {
            return [false, 'login page gagal (' . $c . ')', $jar];
        }

        [$c, $loc] = http_req($base . '/login', http_build_query([
            'username' => $username, 'password' => $seedPass, 'csrf_token' => $token,
        ]), ['Content-Type: application/x-www-form-urlencoded'], $jar);

        if ($c !== 302) {
            // Recovery: password mungkin tertinggal di $testPass dari run crash.
            $jar = '';
            $html = http_req($base . '/login', null, [], $jar)[2];
            $token = csrf_from($html);
            [$c2] = http_req($base . '/login', http_build_query([
                'username' => $username, 'password' => $testPass, 'csrf_token' => $token,
            ]), ['Content-Type: application/x-www-form-urlencoded'], $jar);
            if ($c2 !== 302) {
                return [false, 'login gagal dgn seed & test pass', $jar];
            }
            // Kembalikan ke seed pass (state bersih), lalu login ulang.
            $token2 = csrf_from(http_req($base . '/password/change', null, [], $jar)[2]);
            if ($token2 === '') {
                return [false, 'recovery: form ganti password tidak terbuka', $jar];
            }
            http_req($base . '/password/change', http_build_query([
                'current_password' => $testPass, 'new_password' => $seedPass,
                'confirm_password' => $seedPass, 'csrf_token' => $token2,
            ]), ['Content-Type: application/x-www-form-urlencoded'], $jar);

            $jar = '';
            $html = http_req($base . '/login', null, [], $jar)[2];
            $token = csrf_from($html);
            [$c, $loc] = http_req($base . '/login', http_build_query([
                'username' => $username, 'password' => $seedPass, 'csrf_token' => $token,
            ]), ['Content-Type: application/x-www-form-urlencoded'], $jar);
            if ($c !== 302) {
                return [false, 'recovery gagal', $jar];
            }
        }

        // Deteksi mode "wajib ganti password": guard global memintersep
        // request berikutnya ke /password/change (loc login selalu /dashboard).
        [, $locDash] = http_req($base . '/dashboard', null, [], $jar);
        if (str_contains((string) $locDash, '/password/change')) {
            $token2 = csrf_from(http_req($base . '/password/change', null, [], $jar)[2]);
            if ($token2 === '') {
                return [false, 'form ganti password tidak terbuka', $jar];
            }
            [$c2, $loc2] = http_req($base . '/password/change', http_build_query([
                'current_password' => $seedPass, 'new_password' => $testPass,
                'confirm_password' => $testPass, 'csrf_token' => $token2,
            ]), ['Content-Type: application/x-www-form-urlencoded'], $jar);
            if ($c2 !== 302 || str_contains((string) $loc2, '/password/change')) {
                return [false, 'ganti password gagal (' . $c2 . ' ' . $loc2 . ')', $jar];
            }
            return [true, (string) $loc2, $jar];
        }

        return [true, (string) $loc, $jar];
    }
}

if (!function_exists('restore_password')) {
    /** Kembalikan password akun ke seed pass (dipanggil di akhir E2E). */
    function restore_password(string $base, string $seedPass, string $testPass, string &$jar): void
    {
        $token = csrf_from(http_req($base . '/password/change', null, [], $jar)[2]);
        if ($token !== '') {
            http_req($base . '/password/change', http_build_query([
                'current_password' => $testPass, 'new_password' => $seedPass,
                'confirm_password' => $seedPass, 'csrf_token' => $token,
            ]), ['Content-Type: application/x-www-form-urlencoded'], $jar);
        }
    }
}
