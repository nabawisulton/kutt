<?php

declare(strict_types=1);

/**
 * KUTT SUKA MAKMUR - Front Controller
 * All HTTP requests are routed through this file.
 */

require dirname(__DIR__) . '/src/bootstrap.php';

use App\Controllers\AuthController;
use App\Controllers\CardController;
use App\Controllers\CmsController;
use App\Controllers\DashboardController;
use App\Controllers\FinController;
use App\Controllers\LookupController;
use App\Controllers\MemberController;
use App\Controllers\MemberPortalController;
use App\Controllers\MiscController;
use App\Controllers\NewsController;
use App\Controllers\PortalController;
use App\Controllers\PublicNewsController;
use App\Controllers\UserController;
use App\Core\Auth;
use App\Controllers\ReportController;
use App\Controllers\SettingsController;
use App\Controllers\SupportController;
use App\Core\Router;

$router = new Router();

// Guard keamanan global: password masih bawaan seed? Paksa ganti dulu.
// (Kecuali route ganti password/logout sendiri yang didefinisikan di bawah.)
if (Auth::mustChangePassword()) {
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $allowed = ['/password/change', '/logout'];
    if (!in_array($path, $allowed, true)) {
        header('Location: /password/change');
        exit;
    }
}

// Portal landing page (desain legacy GAS, konten dari MySQL).
$router->get('/', PortalController::class, 'index');

// Dashboard: login diperlukan, guest diarahkan ke /login.
$router->get('/dashboard', DashboardController::class, 'index');

// Auth
$router->get('/login', AuthController::class, 'showLogin');
$router->post('/login', AuthController::class, 'login');
$router->post('/logout', AuthController::class, 'logout');

// Ganti password milik sendiri (wajib saat login pertama dengan password seed)
$router->get('/password/change', UserController::class, 'showChangePassword');
$router->post('/password/change', UserController::class, 'changePassword');

// Dashboard
// Dashboard exports
$router->get('/dashboard/export/excel', DashboardController::class, 'exportExcel');
$router->get('/dashboard/print', ReportController::class, 'dashboardPrint');
$router->get('/dashboard/export/pdf', ReportController::class, 'dashboardPdf');
$router->get('/members/export/pdf', ReportController::class, 'membersPdf');

// Master data anggota (CRUD + kartu/QR + export)
$router->get('/members', MemberController::class, 'index');
$router->get('/members/create', MemberController::class, 'createForm');
$router->post('/members/create', MemberController::class, 'save');
$router->get('/members/edit/{id}', MemberController::class, 'editForm');
$router->post('/members/edit/{id}', MemberController::class, 'update');
$router->post('/members/delete/{id}', MemberController::class, 'destroy');
$router->get('/members/{id}/card', MemberController::class, 'card');
$router->get('/members/{id}/card/print', MemberController::class, 'cardPrint');
$router->get('/members/card/print', MemberController::class, 'cardPrintBatch');
$router->post('/members/{id}/card/rotate', MemberController::class, 'rotateQr');
$router->get('/members/export/excel', MemberController::class, 'exportExcel');
$router->get('/members/print', ReportController::class, 'membersPrint');
$router->get('/members/scan', CardController::class, 'scan');
$router->get('/members/lookup', LookupController::class, 'member');

// Kartu anggota: QR image + halaman verifikasi publik (token acak, bukan NIK).
$router->get('/qr/{token}', CardController::class, 'qr');
$router->get('/anggota/verify/{token}', CardController::class, 'verify');

// Modul keuangan: simpanan, pinjaman/angsuran, kas, akuntansi, SHU
$router->get('/simpanan', FinController::class, 'savings');
$router->post('/simpanan', FinController::class, 'saveSavings');
$router->get('/simpanan/export/excel', FinController::class, 'exportSavings');
$router->get('/pinjaman', FinController::class, 'loans');
$router->post('/pinjaman', FinController::class, 'saveLoan');
$router->post('/pinjaman/approve/{id}', FinController::class, 'approveLoan');
$router->post('/pinjaman/reject/{id}', FinController::class, 'rejectLoan');
$router->post('/pinjaman/disburse/{id}', FinController::class, 'disburseLoan');
$router->get('/pinjaman/detail/{id}', FinController::class, 'loanDetail');
$router->post('/pinjaman/detail/{id}/pay', FinController::class, 'paySchedule');
$router->get('/pinjaman/export/excel', FinController::class, 'exportLoans');
$router->get('/kas', FinController::class, 'cash');
$router->post('/kas', FinController::class, 'saveCash');
$router->get('/kas/export/excel', FinController::class, 'exportCash');
$router->get('/akuntansi', FinController::class, 'journal');
$router->post('/akuntansi', FinController::class, 'saveJournal');
$router->get('/akuntansi/export/excel', FinController::class, 'exportJournal');
$router->get('/shu', FinController::class, 'shu');
$router->post('/shu', FinController::class, 'saveShu');
$router->get('/shu/export/excel', FinController::class, 'exportShu');

// Portal berita (admin)
$router->get('/news', NewsController::class, 'index');
$router->get('/news/create', NewsController::class, 'createForm');
$router->post('/news/create', NewsController::class, 'save');
$router->get('/news/edit/{id}', NewsController::class, 'editForm');
$router->post('/news/edit/{id}', NewsController::class, 'update');
$router->post('/news/publish/{id}', NewsController::class, 'publish');
$router->post('/news/unpublish/{id}', NewsController::class, 'unpublish');
$router->post('/news/delete/{id}', NewsController::class, 'destroy');
$router->get('/news/comments/{id}', NewsController::class, 'comments');
$router->post('/news/comments/{id}/status', NewsController::class, 'commentStatus');
$router->post('/news/comments/{id}/delete', NewsController::class, 'commentDelete');
$router->get('/news/export/excel', NewsController::class, 'exportExcel');

// Portal berita (publik) + engagement
$router->get('/berita', PublicNewsController::class, 'index');
$router->get('/berita/feed', PublicNewsController::class, 'feed');
$router->get('/berita/{slug}', PublicNewsController::class, 'detail');
$router->post('/berita/{id}/like', PublicNewsController::class, 'like');
$router->post('/berita/{id}/share', PublicNewsController::class, 'share');
$router->post('/berita/{id}/comment', PublicNewsController::class, 'comment');

// CMS Landing Page (portal publik)
$router->get('/cms', CmsController::class, 'index');
$router->post('/cms/text', CmsController::class, 'saveText');
$router->post('/cms/units', CmsController::class, 'saveUnits');
$router->post('/cms/gallery', CmsController::class, 'saveGallery');
$router->post('/cms/videos', CmsController::class, 'saveVideos');
$router->post('/cms/products', CmsController::class, 'saveProducts');

// Pengaturan (identitas + kartu anggota)
$router->get('/settings', SettingsController::class, 'index');
$router->post('/settings/general', SettingsController::class, 'saveGeneral');
$router->post('/settings/card-background', SettingsController::class, 'saveCardBackground');

// Notifikasi & log aktivitas
$router->get('/notifications', MiscController::class, 'notifications');
$router->post('/notifications/read', MiscController::class, 'notificationsRead');
$router->post('/notifications/clear', MiscController::class, 'notificationsClear');
$router->get('/activity', MiscController::class, 'activity');

// Portal anggota (role ANGGOTA): data milik sendiri + pengajuan + chat
$router->get('/portal', MemberPortalController::class, 'dashboard');
$router->post('/portal/loan-request', MemberPortalController::class, 'submitLoan');
$router->get('/portal/chat', MemberPortalController::class, 'chat');
$router->post('/portal/chat', MemberPortalController::class, 'sendChat');

// Komunikasi anggota (sisi admin)
$router->get('/support', SupportController::class, 'inbox');
$router->get('/support/{memberId}', SupportController::class, 'thread');
$router->post('/support/{memberId}/reply', SupportController::class, 'reply');

// Manajemen user (multi-user admin, hanya SUPER_ADMIN)
$router->get('/users', UserController::class, 'index');
$router->get('/users/create', UserController::class, 'createForm');
$router->post('/users/create', UserController::class, 'save');
$router->get('/users/edit/{id}', UserController::class, 'editForm');
$router->post('/users/edit/{id}', UserController::class, 'update');
$router->post('/users/delete/{id}', UserController::class, 'destroy');
$router->post('/users/toggle/{id}', UserController::class, 'toggle');
$router->post('/users/password/{id}', UserController::class, 'resetPassword');
$router->get('/users/export/excel', UserController::class, 'exportExcel');

// Catch-all: any uncaught Throwable (incl. model/DB errors) → themed 500.
try {
    $router->dispatch();
} catch (Throwable $e) {
    \App\Core\ErrorHandler::render(500, $e);
}
