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
use App\Controllers\MarketplaceController;
use App\Controllers\MiscController;
use App\Controllers\NewsController;
use App\Controllers\PortalController;
use App\Controllers\PublicNewsController;
use App\Controllers\SalesController;
use App\Controllers\SavingsController;
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

// Profil sendiri (menu profil di navbar dashboard/portal): nama, email, foto
$router->post('/profile/update', UserController::class, 'updateProfile');
$router->post('/profile/avatar', UserController::class, 'updateAvatar');

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
// Aksi finansial dari hasil scan QR — SEMUA wajib PIN 6 digit (QR hanya identifikasi)
$router->post('/anggota/verify/{token}/saldo', CardController::class, 'verifySaldo');
$router->post('/anggota/verify/{token}/riwayat', CardController::class, 'verifyHistory');
$router->post('/anggota/verify/{token}/topup', CardController::class, 'verifyTopup');

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

// Marketplace publik (multi-marketplace, tanpa login)
// Statis dulu sebelum {id} agar tidak tertelan parameter.
$router->get('/marketplace', MarketplaceController::class, 'index');
$router->get('/marketplace/keranjang', MarketplaceController::class, 'cart');
$router->post('/marketplace/checkout', MarketplaceController::class, 'checkout');
$router->get('/marketplace/sukses', MarketplaceController::class, 'success');
$router->get('/marketplace/{id}', MarketplaceController::class, 'detail');

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
$router->post('/settings/assets', SettingsController::class, 'saveAssets');
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
// Saldo & transaksi anggota (portal): PIN 6 digit + riwayat + pengajuan top up
$router->get('/portal/wallet', MemberPortalController::class, 'wallet');
$router->post('/portal/wallet/pin', MemberPortalController::class, 'savePin');
$router->post('/portal/wallet/topup', MemberPortalController::class, 'topupRequest');

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

// Produk & stok (dashboard)
$router->get('/products', SalesController::class, 'products');
$router->post('/products', SalesController::class, 'saveProduct');
$router->get('/products/stock/{id}', SalesController::class, 'stockCard');
$router->post('/products/stock/{id}', SalesController::class, 'adjustStock');
$router->post('/products/delete/{id}', SalesController::class, 'deleteProduct');

// Kasir POS multi-kasir + pesanan + laporan penjualan
$router->get('/sales/pos', SalesController::class, 'pos');
$router->post('/sales/pos', SalesController::class, 'storePos');

// Struk thermal 80mm per transaksi (HTML auto-print / teks ESC-POS)
$router->get('/sales/receipt/{id}', SalesController::class, 'receipt');
$router->get('/sales/receipt/{id}/plain', SalesController::class, 'receiptPlainAction');

// Scan barcode produk utk kasir (JSON lookup)
$router->get('/sales/pos/product/{code}', SalesController::class, 'posProduct');
// Scan QR kartu anggota (identifikasi saja — PIN diminta saat bayar saldo)
$router->get('/sales/pos/member/{token}', SalesController::class, 'posMember');
// Top up saldo kasir (kas masuk + jurnal) & proses pengajuan top up anggota
$router->post('/sales/pos/topup', SalesController::class, 'topup');
$router->post('/sales/topup-requests/{id}/process', SalesController::class, 'processTopup');
// Hapus permanen transaksi (khusus Super Admin)
$router->post('/sales/{id}/delete', SalesController::class, 'destroyOrder');
$router->get('/sales', SalesController::class, 'orders');
$router->get('/sales/reports', SalesController::class, 'reports');
$router->get('/sales/export/orders', SalesController::class, 'exportOrders');
$router->get('/sales/export/stock', SalesController::class, 'exportStock');
$router->get('/sales/{id}', SalesController::class, 'orderDetail');
$router->post('/sales/{id}/status', SalesController::class, 'updateStatus');

// Tabungan Uang: pos saldo koperasi + laporan keluar-masuk
$router->get('/tabungan', SavingsController::class, 'index');
$router->post('/tabungan/move', SavingsController::class, 'move');
$router->post('/tabungan/adjust', SavingsController::class, 'adjust');
$router->get('/tabungan/export', SavingsController::class, 'export');

// Catch-all: any uncaught Throwable (incl. model/DB errors) → themed 500.
try {
    $router->dispatch();
} catch (Throwable $e) {
    \App\Core\ErrorHandler::render(500, $e);
}
