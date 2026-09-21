<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Roles;
use App\Models\Notification;
use App\Models\User;
use App\Support\ExcelExport;
use App\Support\Uploader;
use RuntimeException;

/**
 * Manajemen user sistem (SUPER_ADMIN saja): CRUD akun, reset password,
 * aktivasi, dan proteksi diri (tidak bisa menghapus/menonaktifkan diri
 * sendiri atau menghapus SUPER_ADMIN terakhir).
 */
final class UserController extends Controller
{
    public function index(): void
    {
        Roles::requirePermission('users');
        $user = (array) Auth::user();

        $this->view('users/index', [
            'pageTitle'    => 'Kelola User Sistem',
            'pageSubtitle' => 'Akun pengguna dashboard: admin, bendahara, ketua, staff',
            'users'        => User::all(),
            'roles'        => Roles::ALL,
            'allowedViews' => Roles::allowedViews($user['role']),
            'activeView'   => 'users',
        ]);
    }

    public function createForm(): void
    {
        Roles::requirePermission('users');
        $user = (array) Auth::user();

        $this->view('users/form', [
            'pageTitle'    => 'Tambah User',
            'pageSubtitle' => 'Buat akun dashboard baru',
            'editUser'     => null,
            'roles'        => Roles::ALL,
            'allowedViews' => Roles::allowedViews($user['role']),
            'activeView'   => 'users',
        ]);
    }

    public function save(): void
    {
        Roles::requirePermission('users');
        Csrf::validate();
        $me = (array) Auth::user();

        [$ok, $data, $errors] = \App\Core\Validator::check($_POST, [
            'username'  => 'required|username',
            'email'     => 'required|email|max:120',
            'full_name' => 'required|max:120',
            'role'      => 'required|in:' . implode(',', Roles::ALL),
            'password'  => 'required|min:6|max:100',
        ]);

        if (!$ok) {
            flash_set('error', reset($errors) ?: 'Data belum lengkap.');
            flash_old_input($_POST);
            redirect('/users/create');
        }

        if (User::findByLogin((string) $data['username']) !== null) {
            flash_set('error', 'Username sudah digunakan.');
            flash_old_input($_POST);
            redirect('/users/create');
        }

        $existsEmail = Database::scalar('SELECT COUNT(*) FROM users WHERE email = ?', [$data['email']]);
        if ((int) $existsEmail > 0) {
            flash_set('error', 'Email sudah digunakan.');
            flash_old_input($_POST);
            redirect('/users/create');
        }

        $nextId = (int) (Database::scalar('SELECT COALESCE(MAX(id), 0) + 1 FROM users') ?: 1);
        $userIdCode = 'USR-' . str_pad((string) $nextId, 3, '0', STR_PAD_LEFT);

        try {
            Database::insert('users', [
                'user_id'       => $userIdCode,
                'username'      => (string) $data['username'],
                'email'         => (string) $data['email'],
                'password_hash' => password_hash((string) $data['password'], PASSWORD_DEFAULT),
                'role'          => (string) $data['role'],
                'full_name'     => (string) $data['full_name'],
                'is_active'     => 1,
                'created_at'    => date('Y-m-d H:i:s'),
            ]);
        } catch (RuntimeException $e) {
            error_log('[KUTT][User] create failed: ' . $e->getMessage());
            flash_set('error', 'Terjadi kesalahan. Silakan coba kembali.');
            redirect('/users/create');
        }

        Audit::log('CREATE', 'User baru dibuat: ' . $data['username'] . ' (' . $data['role'] . ')', 'USERS', Database::pdo()->lastInsertId() ?: null);
        Notification::push('User baru', 'Akun ' . $data['username'] . ' (' . role_label((string) $data['role']) . ') ditambahkan oleh ' . $me['username'] . '.', 'INFO', '/users', ['role' => Roles::SUPER_ADMIN]);
        flash_set('success', 'Data berhasil disimpan.');
        redirect('/users');
    }

    public function editForm(string $id): void
    {
        Roles::requirePermission('users');
        $me = (array) Auth::user();
        $editUser = User::find((int) $id);
        if ($editUser === null) {
            flash_set('error', 'User tidak ditemukan.');
            redirect('/users');
        }

        $this->view('users/form', [
            'pageTitle'    => 'Edit User',
            'pageSubtitle' => 'Ubah data akun: ' . $editUser['username'],
            'editUser'     => $editUser,
            'roles'        => Roles::ALL,
            'allowedViews' => Roles::allowedViews($me['role']),
            'activeView'   => 'users',
        ]);
    }

    public function update(string $id): void
    {
        Roles::requirePermission('users');
        Csrf::validate();
        $me = (array) Auth::user();
        $target = User::find((int) $id);
        if ($target === null) {
            flash_set('error', 'User tidak ditemukan.');
            redirect('/users');
        }

        [$ok, $data, $errors] = \App\Core\Validator::check($_POST, [
            'username'  => 'required|username',
            'email'     => 'required|email|max:120',
            'full_name' => 'required|max:120',
            'role'      => 'required|in:' . implode(',', Roles::ALL),
        ]);

        if (!$ok) {
            flash_set('error', reset($errors) ?: 'Data belum lengkap.');
            redirect('/users/edit/' . (int) $id);
        }

        // Proteksi: tidak boleh menurunkan role diri sendiri, dan SUPER_ADMIN
        // terakhir tidak boleh diturunkan/dinonaktifkan.
        if ((int) $id === (int) $me['id'] && $data['role'] !== Roles::SUPER_ADMIN) {
            flash_set('error', 'Anda tidak dapat mengubah role akun sendiri.');
            redirect('/users/edit/' . (int) $id);
        }
        if ($target['role'] === Roles::SUPER_ADMIN && $data['role'] !== Roles::SUPER_ADMIN) {
            $superCount = (int) Database::scalar(
                "SELECT COUNT(*) FROM users WHERE role = 'SUPER_ADMIN' AND is_active = 1 AND id != ?",
                [(int) $id]
            );
            if ($superCount === 0) {
                flash_set('error', 'Minimal harus ada satu Super Admin aktif.');
                redirect('/users/edit/' . (int) $id);
            }
        }

        $dupUser = Database::scalar('SELECT COUNT(*) FROM users WHERE (username = ? OR email = ?) AND id != ?', [
            $data['username'], $data['email'], (int) $id,
        ]);
        if ((int) $dupUser > 0) {
            flash_set('error', 'Username atau email sudah digunakan akun lain.');
            redirect('/users/edit/' . (int) $id);
        }

        Database::exec(
            'UPDATE users SET username = ?, email = ?, full_name = ?, role = ?, updated_at = NOW() WHERE id = ?',
            [$data['username'], $data['email'], $data['full_name'], $data['role'], (int) $id]
        );

        // Optional password change.
        $password = trim((string) ($_POST['password'] ?? ''));
        if ($password !== '') {
            if (mb_strlen($password) < 6) {
                flash_set('error', 'Password minimal 6 karakter.');
                redirect('/users/edit/' . (int) $id);
            }
            User::updatePasswordHash((int) $id, password_hash($password, PASSWORD_DEFAULT));
        }

        Audit::log('UPDATE', 'User diperbarui: ' . $data['username'], 'USERS', (string) (int) $id);
        flash_set('success', 'Data berhasil diperbarui.');
        redirect('/users');
    }

    public function destroy(string $id): void
    {
        Roles::requirePermission('users');
        Csrf::validate();
        $me = (array) Auth::user();

        if ((int) $id === (int) $me['id']) {
            flash_set('error', 'Anda tidak dapat menghapus akun sendiri.');
            redirect('/users');
        }

        $target = User::find((int) $id);
        if ($target === null) {
            flash_set('error', 'User tidak ditemukan.');
            redirect('/users');
        }

        if ($target['role'] === Roles::SUPER_ADMIN) {
            $superCount = (int) Database::scalar(
                "SELECT COUNT(*) FROM users WHERE role = 'SUPER_ADMIN' AND is_active = 1 AND id != ?",
                [(int) $id]
            );
            if ($superCount === 0) {
                flash_set('error', 'Minimal harus ada satu Super Admin aktif.');
                redirect('/users');
            }
        }

        Database::exec('DELETE FROM users WHERE id = ?', [(int) $id]);
        Audit::log('DELETE', 'User dihapus: ' . $target['username'], 'USERS', (string) (int) $id);
        flash_set('success', 'Data berhasil dihapus.');
        redirect('/users');
    }

    public function toggle(string $id): void
    {
        Roles::requirePermission('users');
        Csrf::validate();
        $me = (array) Auth::user();

        if ((int) $id === (int) $me['id']) {
            flash_set('error', 'Anda tidak dapat menonaktifkan akun sendiri.');
            redirect('/users');
        }

        $target = User::find((int) $id);
        if ($target === null) {
            flash_set('error', 'User tidak ditemukan.');
            redirect('/users');
        }

        $newState = (int) $target['is_active'] === 1 ? 0 : 1;
        if ($newState === 0 && $target['role'] === Roles::SUPER_ADMIN) {
            $superCount = (int) Database::scalar(
                "SELECT COUNT(*) FROM users WHERE role = 'SUPER_ADMIN' AND is_active = 1 AND id != ?",
                [(int) $id]
            );
            if ($superCount === 0) {
                flash_set('error', 'Minimal harus ada satu Super Admin aktif.');
                redirect('/users');
            }
        }

        Database::exec('UPDATE users SET is_active = ?, updated_at = NOW() WHERE id = ?', [$newState, (int) $id]);
        Audit::log('UPDATE', 'User ' . ($newState === 1 ? 'diaktifkan' : 'dinonaktifkan') . ': ' . $target['username'], 'USERS', (string) (int) $id);
        flash_set('success', 'Data berhasil diperbarui.');
        redirect('/users');
    }

    public function resetPassword(string $id): void
    {
        Roles::requirePermission('users');
        Csrf::validate();
        $target = User::find((int) $id);
        if ($target === null) {
            flash_set('error', 'User tidak ditemukan.');
            redirect('/users');
        }

        $password = (string) ($_POST['password'] ?? '');
        if (mb_strlen($password) < 6) {
            flash_set('error', 'Password minimal 6 karakter.');
            redirect('/users');
        }

        User::updatePasswordHash((int) $id, password_hash($password, PASSWORD_DEFAULT));
        Audit::log('UPDATE', 'Password direset oleh admin untuk user: ' . $target['username'], 'USERS', (string) (int) $id);
        Notification::push('Password direset', 'Password akun ' . $target['username'] . ' direset oleh admin.', 'WARNING', '/users', ['user' => (int) $id]);
        flash_set('success', 'Password berhasil direset.');
        redirect('/users');
    }

    /**
     * Halaman ganti password milik sendiri (dipaksa saat login pertama
     * jika masih memakai password bawaan seed).
     */
    public function showChangePassword(): void
    {
        Auth::requireLogin();
        $this->viewPlain('auth/change_password', [
            'title' => 'Ganti Password - KUTT SUKA MAKMUR',
        ]);
    }

    /**
     * Perbarui nama & email milik sendiri dari menu profil (navbar).
     * Nama di session ikut diperbarui agar navbar langsung tampil baru.
     */
    public function updateProfile(): void
    {
        Auth::requireLogin();
        Csrf::validate();
        $me = (array) Auth::user();

        [$ok, $data, $errors] = Validator::check($_POST, [
            'full_name' => 'required|max:120',
            'email'     => 'required|email|max:120',
        ]);
        if (!$ok) {
            flash_set('error', reset($errors) ?: 'Nama dan email wajib diisi dengan benar.');
            redirect((string) $me['role'] === 'ANGGOTA' ? '/portal' : '/dashboard');
        }

        $email = (string) $data['email'];
        $dup = (int) Database::scalar(
            'SELECT COUNT(*) FROM users WHERE email = ? AND id != ?',
            [$email, (int) $me['id']]
        );
        if ($dup > 0) {
            flash_set('error', 'Email sudah digunakan akun lain.');
            redirect((string) $me['role'] === 'ANGGOTA' ? '/portal' : '/dashboard');
        }

        User::updateProfile((int) $me['id'], (string) $data['full_name'], $email);

        // Sinkronkan session agar navbar menampilkan identitas terbaru.
        $_SESSION['_auth_user']['full_name'] = (string) $data['full_name'];
        $_SESSION['_auth_user']['email'] = $email;

        Audit::log('UPDATE', 'Profil sendiri diperbarui: ' . $data['full_name'], 'USERS', (string) $me['id']);
        flash_set('success', 'Profil berhasil diperbarui.');
        redirect((string) $me['role'] === 'ANGGOTA' ? '/portal' : '/dashboard');
    }

    /** Upload foto profil sendiri (dipakai menu profil di navbar). */
    public function updateAvatar(): void
    {
        Auth::requireLogin();
        Csrf::validate();
        $me = (array) Auth::user();

        try {
            $path = Uploader::image($_FILES['avatar'] ?? null, 'avatars');
        } catch (RuntimeException $e) {
            flash_set('error', $e->getMessage());
            redirect((string) $me['role'] === 'ANGGOTA' ? '/portal' : '/dashboard');
        }

        if ($path === null) {
            flash_set('error', 'Pilih file foto terlebih dahulu.');
            redirect((string) $me['role'] === 'ANGGOTA' ? '/portal' : '/dashboard');
        }

        $old = User::find((int) $me['id'])['avatar_path'] ?? null;
        if ($old !== null && (string) $old !== '') {
            Uploader::delete((string) $old);
        }

        User::updateAvatar((int) $me['id'], $path);
        $_SESSION['_auth_user']['avatar_path'] = $path;

        Audit::log('UPDATE', 'Foto profil diperbarui: ' . $me['username'], 'USERS', (string) $me['id']);
        flash_set('success', 'Foto profil berhasil diperbarui.');
        redirect((string) $me['role'] === 'ANGGOTA' ? '/portal' : '/dashboard');
    }

    public function changePassword(): void
    {
        Auth::requireLogin();
        Csrf::validate();
        $me = (array) Auth::user();

        $current = (string) ($_POST['current_password'] ?? '');
        $new     = (string) ($_POST['new_password'] ?? '');
        $confirm = (string) ($_POST['confirm_password'] ?? '');

        if ($new !== $confirm) {
            flash_set('error', 'Konfirmasi password baru tidak sama.');
            redirect('/password/change');
        }
        if (mb_strlen($new) < 8) {
            flash_set('error', 'Password baru minimal 8 karakter.');
            redirect('/password/change');
        }
        if ($new === $current) {
            flash_set('error', 'Password baru harus berbeda dari password lama.');
            redirect('/password/change');
        }

        $row = User::find((int) $me['id']);
        if ($row === null || !password_verify($current, (string) $row['password_hash'])) {
            flash_set('error', 'Password saat ini salah.');
            redirect('/password/change');
        }

        User::updatePasswordHash((int) $me['id'], password_hash($new, PASSWORD_DEFAULT));
        Auth::setMustChangePassword(false);
        Audit::log('UPDATE', 'User mengganti password miliknya sendiri: ' . $me['username'], 'USERS', (string) $me['id']);
        flash_set('success', 'Password berhasil diperbarui. Terima kasih!');
        redirect((string) $me['role'] === 'ANGGOTA' ? '/portal' : '/dashboard');
    }

    public function exportExcel(): void
    {
        Roles::requirePermission('users');

        $rows = [];
        foreach (User::all() as $u) {
            $rows[] = [
                $u['user_id'], $u['username'], $u['full_name'], $u['email'],
                role_label((string) $u['role']), ((int) $u['is_active'] === 1 ? 'Aktif' : 'Nonaktif'),
                (string) $u['created_at'],
            ];
        }

        ExcelExport::download('data_user', 'Data User KUTT Suka Makmur', ['ID User', 'Username', 'Nama Lengkap', 'Email', 'Role', 'Status', 'Dibuat'], $rows);
    }
}
