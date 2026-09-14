<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Validator;

/**
 * Authentication controller: login & logout.
 */
final class AuthController extends Controller
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            redirect('/dashboard');
        }

        $this->viewPlain('auth/login', [
            'title'     => 'Login Portal KUTT',
            'error'     => flash_take()['message'] ?? null,
        ]);
    }

    public function login(): void
    {
        if (Auth::check()) {
            redirect('/dashboard');
        }

        Csrf::validate();

        [$ok, $data, $errors] = Validator::check($_POST, [
            'username' => 'required|max:120',
            'password' => 'required|max:200',
        ]);

        if (!$ok) {
            flash_set('error', 'Username dan password wajib diisi.');
            redirect('/login');
        }

        if (!Auth::throttleAllow((string) $data['username'])) {
            flash_set('error', 'Terlalu banyak percobaan login. Coba lagi dalam '
                . Auth::throttleRemainingSeconds() . ' detik.');
            redirect('/login');
        }

        if (Auth::attempt((string) $data['username'], (string) $data['password'])) {
            flash_clear_old();
            redirect('/dashboard');
        }

        flash_set('error', 'Username/Email tidak ditemukan, akun nonaktif, atau password salah.');
        redirect('/login');
    }

    public function logout(): void
    {
        Csrf::validate();
        Auth::logout();
        flash_set('info', 'Anda berhasil logout.');
        redirect('/login');
    }
}
