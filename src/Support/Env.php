<?php

declare(strict_types=1);

namespace App\Support;

final class Env
{
    /** @var array<string,string> */
    private static array $vars = [];

    private static bool $loaded = false;

    public static function load(string $envFile): void
    {
        // Real environment variables always win over the .env file.
        self::$vars = $_ENV + $_SERVER ?? [];
        self::$loaded = true;

        // .env.local (if present) overrides .env, mirroring common dotenv
        // conventions. Both files are optional.
        $dir = rtrim(dirname($envFile), '/');
        foreach (array_unique([basename($envFile), '.env.local']) as $file) {
            $path = $dir . '/' . $file;
            if (!is_file($path)) {
                continue;
            }

            $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#')) {
                    continue;
                }
                $pos = strpos($line, '=');
                if ($pos === false) {
                    continue;
                }
                $key = trim(substr($line, 0, $pos));
                $value = trim(substr($line, $pos + 1));
                $value = trim($value, "\"'");

                if (!array_key_exists($key, self::$vars) && getenv($key) === false) {
                    self::$vars[$key] = $value;
                }
            }
        }

        // Alias DB_PASS <-> DB_PASSWORD: konfigurasi dari installer web maupun
        // panduan manual (kedua ejaan) selalu terbaca aplikasi.
        // Prioritas: real env (getenv) > .env.local/.env file.
        $pass = null;
        foreach (['DB_PASSWORD', 'DB_PASS'] as $k) {
            $v = getenv($k);
            if (is_string($v) && $v !== '') {
                $pass = $v;
                break;
            }
        }
        if ($pass === null) {
            foreach (['DB_PASSWORD', 'DB_PASS'] as $k) {
                if (isset(self::$vars[$k]) && self::$vars[$k] !== '') {
                    $pass = self::$vars[$k];
                    break;
                }
            }
        }
        if ($pass !== null) {
            foreach (['DB_PASSWORD', 'DB_PASS'] as $k) {
                if ((self::$vars[$k] ?? '') === '' && getenv($k) === false) {
                    self::$vars[$k] = $pass;
                }
            }
        }
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        // Values set via putenv()/$_ENV AFTER load (e.g. tests, installer) win.
        $fromProcessEnv = getenv($key);
        if ($fromProcessEnv !== false && $fromProcessEnv !== '' && self::$loaded) {
            return $fromProcessEnv;
        }
        if (isset(self::$vars[$key]) && self::$vars[$key] !== '') {
            return self::$vars[$key];
        }
        if ($fromProcessEnv !== false && $fromProcessEnv !== '') {
            return $fromProcessEnv;
        }

        return $default;
    }

    /**
     * Persist key/values into the project .env file (used by the installer).
     * Values are escaped; existing keys are replaced, unknown lines preserved.
     *
     * @param array<string,string> $values
     */
    public static function set(array $values, string $envFile = BASE_PATH . '/.env'): bool
    {
        $lines = is_file($envFile) ? (file($envFile, FILE_IGNORE_NEW_LINES) ?: []) : [];
        $existingKeys = [];

        foreach ($lines as $i => $line) {
            $trimmed = ltrim($line);
            if ($trimmed === '' || str_starts_with($trimmed, '#') || !str_contains($line, '=')) {
                continue;
            }
            $key = trim(substr($line, 0, (int) strpos($line, '=')));
            $existingKeys[$key] = $i;
        }

        foreach ($values as $key => $value) {
            $safeValue = (strpbrk($value, " \t\"'#$") !== false || $value === '')
                ? '"' . addslashes($value) . '"'
                : $value;
            $line = $key . '=' . $safeValue;

            if (isset($existingKeys[$key])) {
                $lines[$existingKeys[$key]] = $line;
            } else {
                $lines[] = $line;
            }
        }

        return (bool) file_put_contents($envFile, implode("\n", $lines) . "\n", LOCK_EX);
    }
}
