<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Centralized server-side input validation.
 * Returns [ok(bool), data(array), errors(array<string,string>)].
 */
final class Validator
{
    /**
     * @param array<string,mixed>  $input
     * @param array<string,string> $rules rule => rule string "required|email|max:120"
     * @return array{0: bool, 1: array<string,mixed>, 2: array<string,string>}
     */
    public static function check(array $input, array $rules): array
    {
        $data = [];
        $errors = [];

        foreach ($rules as $field => $ruleString) {
            $rules = $ruleString === '' ? [] : explode('|', $ruleString);
            $value = $input[$field] ?? null;

            if (is_string($value)) {
                $value = trim($value);
            }

            $isEmpty = $value === null || $value === '' || $value === [];

            if (in_array('required', $rules, true) && $isEmpty) {
                $errors[$field] = 'Wajib diisi.';
                continue;
            }

            if ($isEmpty) {
                $data[$field] = null;
                continue;
            }

            foreach ($rules as $rule) {
                [$name, $param] = array_pad(explode(':', $rule, 2), 2, null);

                switch ($name) {
                    case 'required':
                    case 'nullable':
                        break;

                    case 'email':
                        if (!filter_var((string) $value, FILTER_VALIDATE_EMAIL)) {
                            $errors[$field] = 'Format email tidak valid.';
                        }
                        break;

                    case 'max':
                        if (mb_strlen((string) $value) > (int) $param) {
                            $errors[$field] = "Maksimal {$param} karakter.";
                        }
                        break;

                    case 'min':
                        if (mb_strlen((string) $value) < (int) $param) {
                            $errors[$field] = "Minimal {$param} karakter.";
                        }
                        break;

                    case 'numeric':
                        if (!is_numeric($value)) {
                            $errors[$field] = 'Harus berupa angka.';
                        }
                        break;

                    case 'digits':
                        if (!ctype_digit((string) $value)) {
                            $errors[$field] = "Harus tepat {$param} digit angka.";
                        } elseif (strlen((string) $value) !== (int) $param) {
                            $errors[$field] = "Harus tepat {$param} digit angka.";
                        }
                        break;

                    case 'date':
                        if (strtotime((string) $value) === false) {
                            $errors[$field] = 'Tanggal tidak valid.';
                        }
                        break;

                    case 'in':
                        $allowed = explode(',', (string) $param);
                        if (!in_array((string) $value, $allowed, true)) {
                            $errors[$field] = 'Nilai tidak valid.';
                        }
                        break;

                    case 'username':
                        if (!preg_match('/^[a-zA-Z0-9._-]{3,40}$/', (string) $value)) {
                            $errors[$field] = 'Hanya huruf, angka, titik, garis bawah, strip (3-40 karakter).';
                        }
                        break;
                }
            }

            $data[$field] = $value;
        }

        return [$errors === [], $data, $errors];
    }
}
