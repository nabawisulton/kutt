<?php

/**
 * KUTT SUKA MAKMUR - Web Installer (cPanel friendly).
 *
 * Langkah: cek PHP/ekstensi -> uji koneksi DB -> jalankan migrasi + seed
 * -> buat akun admin pertama -> selesai.
 *
 * PENTING: HAPUS FILE INI setelah instalasi selesai.
 */

declare(strict_types=1);

session_start();

$baseDir = dirname(__DIR__ ?? '') ?: dirname(__FILE__);
$baseDir = __DIR__;
$step = (int) ($_GET['step'] ?? 1);
$errors = [];
$ok = [];

$phpOk = PHP_VERSION_ID >= 80100;
$extensions = [
    'pdo_mysql' => extension_loaded('pdo_mysql'),
    'mbstring'  => extension_loaded('mbstring'),
    'openssl'   => extension_loaded('openssl'),
    'gd'        => extension_loaded('gd'),
    'fileinfo'  => extension_loaded('fileinfo'),
];
$extOk = !in_array(false, $extensions, true);

$writable = [];
foreach (['storage', 'storage/logs', 'storage/uploads', 'public/uploads'] as $dir) {
    $path = $baseDir . '/' . $dir;
    if (!is_dir($path)) {
        @mkdir($path, 0775, true);
    }
    $writable[$dir] = is_dir($path) && is_writable($path);
}
$writableOk = !in_array(false, $writable, true);

/** Nilai POST aman untuk dirender kembali ke form. */
function v(string $key, string $default = ''): string
{
    return htmlspecialchars((string) ($_POST[$key] ?? $default), ENT_QUOTES, 'UTF-8');
}

/** Jalankan migrasi lewat subprocess PHP. */
function runMigrate(string $phpBin): array
{
    $cmd = escapeshellarg($phpBin) . ' ' . escapeshellarg(__DIR__ . '/database/migrate.php') . ' 2>&1';
    $output = [];
    $code = 0;
    exec($cmd, $output, $code);

    return [$code === 0, implode("\n", $output)];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedStep = (int) ($_POST['step'] ?? 1);

    if ($postedStep === 2) {
        $host = trim((string) ($_POST['db_host'] ?? 'localhost'));
        $name = trim((string) ($_POST['db_name'] ?? ''));
        $user = trim((string) ($_POST['db_user'] ?? ''));
        $pass = (string) ($_POST['db_pass'] ?? '');

        if ($name === '' || $user === '') {
            $errors[] = 'Nama database dan user database wajib diisi.';
        } else {
            try {
                $pdo = new PDO(
                    "mysql:host={$host};dbname={$name};charset=utf8mb4",
                    $user,
                    $pass,
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                );
                $_SESSION['install_db'] = ['host' => $host, 'name' => $name, 'user' => $user, 'pass' => $pass];
                header('Location: ?step=3');
                exit;
            } catch (PDOException $e) {
                $errors[] = 'Koneksi database gagal: ' . $e->getMessage();
            }
        }
        $step = 2;
    } elseif ($postedStep === 3) {
        $db = $_SESSION['install_db'] ?? null;
        if ($db === null) {
            header('Location: ?step=2');
            exit;
        }

        // Tulis .env (DB_PASSWORD = nama kanonik yang dibaca aplikasi)
        $envContent = "APP_ENV=production\n"
            . 'DB_HOST=' . $db['host'] . "\n"
            . 'DB_NAME=' . $db['name'] . "\n"
            . 'DB_USER=' . $db['user'] . "\n"
            . 'DB_PASSWORD=' . $db['pass'] . "\n";
        if (@file_put_contents($baseDir . '/.env', $envContent) === false) {
            $errors[] = 'Gagal menulis file .env. Buat manual sesuai README lalu ulangi.';
        }

        [$migOk, $migOut] = runMigrate(PHP_BINARY ?: 'php');
        if (!$migOk) {
            $errors[] = "Migrasi gagal:\n" . $migOut;
        }
        $ok[] = 'Migrasi & seed: OK';

        // Buat akun admin pertama (opsional bila admin sudah ada)
        $fullname = trim((string) ($_POST['admin_name'] ?? 'Administrator'));
        $username = trim((string) ($_POST['admin_user'] ?? ''));
        $email = trim((string) ($_POST['admin_email'] ?? ''));
        $password = (string) ($_POST['admin_pass'] ?? '');

        if ($username === '' || $password === '' || mb_strlen($password) < 6) {
            $errors[] = 'Username admin dan password (min. 6 karakter) wajib diisi.';
        } else {
            try {
                $pdo = new PDO(
                    "mysql:host={$db['host']};dbname={$db['name']};charset=utf8mb4",
                    $db['user'],
                    $db['pass'],
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                );
                $exists = $pdo->prepare('SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1');
                $exists->execute([$username, $email]);
                if ($exists->fetch()) {
                    $ok[] = 'User admin sudah ada - dilewati.';
                } else {
                    $nextId = (int) $pdo->query('SELECT COALESCE(MAX(id),0)+1 FROM users')->fetchColumn();
                    $ins = $pdo->prepare(
                        'INSERT INTO users (user_id, username, email, password_hash, role, full_name, is_active, created_at)
                         VALUES (?,?,?,?,?,?,1,NOW())'
                    );
                    $ins->execute([
                        'USR-' . str_pad((string) $nextId, 3, '0', STR_PAD_LEFT),
                        $username,
                        $email !== '' ? $email : $username . '@kutt.local',
                        password_hash($password, PASSWORD_DEFAULT),
                        'SUPER_ADMIN',
                        $fullname !== '' ? $fullname : 'Administrator',
                    ]);
                    $ok[] = 'Akun admin dibuat: ' . $username;
                }
            } catch (PDOException $e) {
                $errors[] = 'Gagal membuat admin: ' . $e->getMessage();
            }
        }

        if ($errors === []) {
            header('Location: ?step=done');
            exit;
        }
        $step = 3;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Installer - KUTT SUKA MAKMUR</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="min-h-screen bg-slate-100 text-slate-800 font-sans">
  <div class="max-w-xl mx-auto py-12 px-4">
    <div class="bg-white rounded-2xl shadow-lg p-8">
      <div class="flex items-center gap-3 mb-6">
        <div class="w-12 h-12 rounded-2xl bg-emerald-600 text-white flex items-center justify-center text-2xl shadow"><i class="fa-solid fa-cow"></i></div>
        <div>
          <h1 class="font-bold text-lg">KUTT SUKA MAKMUR</h1>
          <p class="text-xs text-slate-500">Installer Aplikasi Koperasi</p>
        </div>
      </div>

      <?php foreach ($errors as $err): ?>
        <div class="mb-4 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs p-4 whitespace-pre-wrap"><i class="fa-solid fa-circle-exclamation mr-1"></i><?= htmlspecialchars($err) ?></div>
      <?php endforeach; ?>
      <?php foreach ($ok as $msg): ?>
        <div class="mb-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs p-4"><i class="fa-solid fa-circle-check mr-1"></i><?= htmlspecialchars($msg) ?></div>
      <?php endforeach; ?>

      <?php if (isset($_GET['step']) && $_GET['step'] === 'done'): ?>
        <div class="rounded-xl bg-emerald-50 border border-emerald-200 p-5 text-center space-y-3">
          <i class="fa-solid fa-circle-check text-4xl text-emerald-600"></i>
          <h2 class="font-bold">Instalasi Selesai!</h2>
          <p class="text-xs text-slate-600">Aplikasi siap digunakan. Silakan login ke dashboard admin.</p>
          <div class="rounded-xl bg-yellow-50 border border-yellow-300 text-yellow-800 text-[11px] p-3 text-left">
            <strong><i class="fa-solid fa-triangle-exclamation mr-1"></i>KEAMANAN:</strong> hapus file <code>install.php</code> dari server sekarang.
          </div>
          <a href="public/index.php" class="inline-block px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold shadow">Buka Aplikasi</a>
        </div>

      <?php elseif ($step === 1): ?>
        <h2 class="font-bold text-sm mb-4"><i class="fa-solid fa-clipboard-check mr-1 text-emerald-600"></i>Langkah 1: Pemeriksaan Server</h2>
        <ul class="text-xs space-y-2 mb-6">
          <li class="flex justify-between items-center p-2.5 rounded-lg <?= $phpOk ? 'bg-emerald-50' : 'bg-red-50' ?>">
            <span>Versi PHP (min. 8.1)</span><strong class="<?= $phpOk ? 'text-emerald-600' : 'text-red-600' ?>"><?= PHP_VERSION ?> <?= $phpOk ? 'OK' : 'GAGAL' ?></strong>
          </li>
          <?php foreach ($extensions as $ext => $loaded): ?>
            <li class="flex justify-between items-center p-2.5 rounded-lg <?= $loaded ? 'bg-emerald-50' : 'bg-red-50' ?>">
              <span>Ekstensi <?= $ext ?></span><strong class="<?= $loaded ? 'text-emerald-600' : 'text-red-600' ?>"><?= $loaded ? 'Ada' : 'TIDAK ADA' ?></strong>
            </li>
          <?php endforeach; ?>
          <?php foreach ($writable as $dir => $isWritable): ?>
            <li class="flex justify-between items-center p-2.5 rounded-lg <?= $isWritable ? 'bg-emerald-50' : 'bg-red-50' ?>">
              <span>Folder <?= $dir ?> dapat ditulis</span><strong class="<?= $isWritable ? 'text-emerald-600' : 'text-red-600' ?>"><?= $isWritable ? 'OK' : 'GAGAL' ?></strong>
            </li>
          <?php endforeach; ?>
        </ul>
        <?php if ($phpOk && $extOk && $writableOk): ?>
          <a href="?step=2" class="block text-center w-full py-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold shadow">Lanjut ke Konfigurasi Database</a>
        <?php else: ?>
          <p class="text-xs text-red-600"><i class="fa-solid fa-triangle-exclamation mr-1"></i>Perbaiki persyaratan di atas terlebih dahulu (hubungi hosting Anda).</p>
        <?php endif; ?>

      <?php elseif ($step === 2): ?>
        <h2 class="font-bold text-sm mb-4"><i class="fa-solid fa-database mr-1 text-emerald-600"></i>Langkah 2: Koneksi Database</h2>
        <form method="post" class="space-y-3">
          <input type="hidden" name="step" value="2">
          <div>
            <label class="text-[10px] font-bold text-slate-500 uppercase">DB Host</label>
            <input type="text" name="db_host" value="<?= v('db_host', 'localhost') ?>" class="w-full mt-1 px-3 py-2.5 text-xs border rounded-xl" required>
          </div>
          <div>
            <label class="text-[10px] font-bold text-slate-500 uppercase">Nama Database *</label>
            <input type="text" name="db_name" value="<?= v('db_name') ?>" placeholder="cpaneluser_kutt" class="w-full mt-1 px-3 py-2.5 text-xs border rounded-xl" required>
          </div>
          <div>
            <label class="text-[10px] font-bold text-slate-500 uppercase">User Database *</label>
            <input type="text" name="db_user" value="<?= v('db_user') ?>" class="w-full mt-1 px-3 py-2.5 text-xs border rounded-xl" required>
          </div>
          <div>
            <label class="text-[10px] font-bold text-slate-500 uppercase">Password Database</label>
            <input type="password" name="db_pass" class="w-full mt-1 px-3 py-2.5 text-xs border rounded-xl">
          </div>
          <button class="w-full py-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold shadow">Uji Koneksi &amp; Lanjut</button>
        </form>

      <?php elseif ($step === 3): ?>
        <h2 class="font-bold text-sm mb-4"><i class="fa-solid fa-user-shield mr-1 text-emerald-600"></i>Langkah 3: Akun Admin &amp; Migrasi</h2>
        <form method="post" class="space-y-3">
          <input type="hidden" name="step" value="3">
          <div>
            <label class="text-[10px] font-bold text-slate-500 uppercase">Nama Lengkap Admin</label>
            <input type="text" name="admin_name" value="<?= v('admin_name', 'Administrator') ?>" class="w-full mt-1 px-3 py-2.5 text-xs border rounded-xl">
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="text-[10px] font-bold text-slate-500 uppercase">Username *</label>
              <input type="text" name="admin_user" value="<?= v('admin_user', 'admin') ?>" class="w-full mt-1 px-3 py-2.5 text-xs border rounded-xl" required>
            </div>
            <div>
              <label class="text-[10px] font-bold text-slate-500 uppercase">Email</label>
              <input type="email" name="admin_email" value="<?= v('admin_email') ?>" class="w-full mt-1 px-3 py-2.5 text-xs border rounded-xl">
            </div>
          </div>
          <div>
            <label class="text-[10px] font-bold text-slate-500 uppercase">Password Admin * (min. 6 karakter)</label>
            <input type="password" name="admin_pass" minlength="6" required class="w-full mt-1 px-3 py-2.5 text-xs border rounded-xl">
          </div>
          <button class="w-full py-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold shadow"><i class="fa-solid fa-bolt mr-1"></i>Jalankan Instalasi</button>
        </form>
      <?php endif; ?>

      <p class="mt-6 text-center text-[10px] text-slate-400">
        Langkah <?= isset($_GET['step']) && $_GET['step'] === 'done' ? 'selesai' : $step ?> dari 3 &middot;
        KUTT SUKA MAKMUR &copy; <?= date('Y') ?>
      </p>
    </div>
  </div>
</body>
</html>
