<?php

declare(strict_types=1);

/**
 * E2E probe: login -> buka form berita -> POST multipart (dengan gambar).
 * Cookie session ditulis ulang dari Set-Cookie pada SETIAP respons
 * (login memanggil session_regenerate_id, jadi cookie berubah).
 *
 * Usage: php tests/e2e_news_upload.php [baseUrl]
 * Exit 0 = create sukses (302 -> /news), 1 = gagal.
 */

$base = rtrim($argv[1] ?? 'http://127.0.0.1:8080', '/');

function http_req(string $url, ?string $body, array $headers, string &$jar): array
{
    $h = $headers;
    if ($jar !== '') {
        $h[] = 'Cookie: ' . $jar;
    }
    $ctx = stream_context_create(['http' => [
        'method' => $body !== null ? 'POST' : 'GET',
        'header' => implode("\r\n", $h) . "\r\n",
        'content' => $body,
        'ignore_errors' => true,
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

$jar = '';
[, , $html] = http_req($base . '/login', null, [], $jar);
if (!preg_match('/name="csrf_token" value="([a-f0-9]+)"/', $html, $m)) {
    fwrite(STDERR, "FAIL: form login tidak punya csrf token\n");
    exit(1);
}
[$cLogin, $locLogin] = http_req($base . '/login', http_build_query([
    'username' => 'admin', 'password' => 'admin123', 'csrf_token' => $m[1],
]), ['Content-Type: application/x-www-form-urlencoded'], $jar);
echo "login=$cLogin loc=$locLogin\n";
if ($cLogin !== 302 || !str_contains((string) $locLogin, '/dashboard')) {
    fwrite(STDERR, "FAIL: login tidak sukses\n");
    exit(1);
}

[, , $html] = http_req($base . '/news/create', null, [], $jar);
if (!str_contains($html, 'name="title"')) {
    fwrite(STDERR, "FAIL: form create tidak terbuka (login session hilang?) len=" . strlen($html) . "\n");
    exit(1);
}
if (!preg_match('/name="csrf_token" value="([a-f0-9]+)"/', $html, $m2)) {
    fwrite(STDERR, "FAIL: form create tanpa csrf token\n");
    exit(1);
}

// PNG 1x1 valid yang di-encode ulang oleh Uploader.
$png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
$b = '----X' . bin2hex(random_bytes(6));
$body = "--$b\r\nContent-Disposition: form-data; name=\"title\"\r\n\r\nUjiUploadE2E " . date('His') . "\r\n"
    . "--$b\r\nContent-Disposition: form-data; name=\"excerpt\"\r\n\r\nRingkasan uji E2E upload\r\n"
    . "--$b\r\nContent-Disposition: form-data; name=\"body\"\r\n\r\n<p>Isi uji upload E2E.</p>\r\n"
    . "--$b\r\nContent-Disposition: form-data; name=\"status\"\r\n\r\nPUBLISHED\r\n"
    . "--$b\r\nContent-Disposition: form-data; name=\"tags\"\r\n\r\nuji, e2e\r\n"
    . "--$b\r\nContent-Disposition: form-data; name=\"csrf_token\"\r\n\r\n{$m2[1]}\r\n"
    . "--$b\r\nContent-Disposition: form-data; name=\"image\"; filename=\"tiny.png\"\r\nContent-Type: image/png\r\n\r\n$png\r\n"
    . "--$b--\r\n";
[$cCreate, $locCreate, $resp] = http_req($base . '/news/create', $body, ['Content-Type: multipart/form-data; boundary=' . $b], $jar);
echo "create=$cCreate loc=$locCreate\n";
if ($cCreate !== 302 || !str_contains((string) $locCreate, '/news')) {
    fwrite(STDERR, "FAIL: create tidak 302->/news. Body head:\n" . substr($resp, 0, 300) . "\n");
    exit(1);
}

echo "E2E NEWS UPLOAD: PASS\n";
exit(0);
