<?php

declare(strict_types=1);

/**
 * Shared HTTP client untuk test E2E (stream wrapper, tanpa dependency curl).
 * Cookie session ditulis ulang dari Set-Cookie pada SETIAP respons
 * (login memanggil session_regenerate_id).
 *
 * http_req(string $url, ?string $body, array $headers, string &$jar): array
 *   returns [$statusCode, $location, $body]
 */

if (!function_exists('http_req')) {
    function http_req(string $url, ?string $body, array $headers, string &$jar): array
    {
        $h = $headers;
        if ($jar !== '') {
            $h[] = 'Cookie: ' . $jar;
        }
        $ctx = stream_context_create(['http' => [
            'method'        => $body !== null ? 'POST' : 'GET',
            'header'        => implode("\r\n", $h) . "\r\n",
            'content'       => $body,
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
}
