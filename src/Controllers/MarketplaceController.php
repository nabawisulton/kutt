<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Csrf;
use App\Core\Database;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Setting;
use RuntimeException;

/**
 * Marketplace publik (tanpa login): katalog produk, detail, dan checkout.
 * Pembayaran/transaksi dilanjutkan via WhatsApp ke admin (konfirmasi manual
 * oleh kasir/admin di dashboard) — pola umum marketplace koperasi tanpa
 * payment gateway.
 */
final class MarketplaceController extends Controller
{
    public function index(): void
    {
        $q = trim((string) ($_GET['q'] ?? ''));
        $category = trim((string) ($_GET['kategori'] ?? ''));

        $this->viewPlain('public/marketplace', [
            'seoTitle'   => 'Produk & Marketplace - ' . (string) Setting::get('brandName', 'KUTT SUKA MAKMUR'),
            'seoDesc'    => 'Katalog produk susu, pakan, dan kebutuhan ternak KUTT Suka Makmur. Pesan langsung via WhatsApp.',
            'products'   => Product::publicCatalog($q, $category),
            'categories' => Product::publicCategories(),
            'activeCategory' => $category,
            'q'          => $q,
        ]);
    }

    /**
     * Halaman keranjang + form checkout (tanpa JS pun tetap bisa: form non-JS
     * mengirim item_0..N). State keranjang disimpan di session (diisi oleh JS
     * katalog, atau via POST add). Untuk flow tanpa-JS, checkout langsung dari
     * halaman ini.
     */
    public function cart(): void
    {
        $this->viewPlain('public/cart', [
            'seoTitle'   => 'Keranjang & Checkout - ' . (string) Setting::get('brandName', 'KUTT SUKA MAKMUR'),
            'seoDesc'    => 'Selesaikan pesanan Anda. Konfirmasi cepat via WhatsApp.',
            'categories' => Product::publicCategories(),
            'activeCategory' => '',
            'q'          => '',
        ]);
    }

    public function detail(string $id): void
    {
        $product = Product::find((int) $id);
        if ($product === null || (int) $product['is_active'] !== 1 || (int) $product['stock'] <= 0) {
            $this->index();

            return;
        }

        $this->viewPlain('public/product_detail', [
            'seoTitle'   => $product['name'] . ' - ' . (string) Setting::get('brandName', 'KUTT SUKA MAKMUR'),
            'seoDesc'    => mb_substr(strip_tags((string) ($product['description'] ?? '')), 0, 200),
            'product'    => $product,
            'related'    => array_slice(
                array_filter(
                    Product::publicCatalog('', (string) ($product['category'] ?? '')),
                    static fn (array $p): bool => (int) $p['id'] !== (int) $product['id']
                ),
                0,
                4
            ),
        ]);
    }

    /**
     * Checkout: validasi ulang item & stok di server, buat order (stok
     * dipotong), lalu redirect ke WhatsApp dengan ringkasan pesanan.
     */
    public function checkout(): void
    {
        Csrf::validate();

        $items = [];
        $payload = $_POST['items'] ?? '[]';
        $decoded = json_decode((string) $payload, true);
        if (is_array($decoded)) {
            $items = $decoded;
        } else {
            // Fallback: item_0..N dari form non-JS.
            foreach ($_POST as $key => $value) {
                if (preg_match('/^item_(\d+)$/', (string) $key, $m)) {
                    $items[] = ['product_id' => (int) $m[1], 'quantity' => (int) $value];
                }
            }
        }

        $buyerName = mb_substr(trim((string) ($_POST['buyer_name'] ?? '')), 0, 120);
        $buyerPhone = mb_substr(trim((string) ($_POST['buyer_phone'] ?? '')), 0, 30);
        $buyerNote = mb_substr(trim((string) ($_POST['buyer_note'] ?? '')), 0, 255);

        if ($buyerName === '' || $buyerPhone === '') {
            flash_set('error', 'Nama dan nomor WhatsApp wajib diisi.');
            redirect('/marketplace');
        }

        try {
            [$orderId, $orderNo] = Sale::createOrder([
                'channel'        => 'MARKETPLACE',
                'buyer_name'     => $buyerName,
                'buyer_phone'    => $buyerPhone,
                'buyer_note'     => $buyerNote,
                'payment_method' => in_array(($_POST['payment_method'] ?? 'COD'), Sale::PAYMENTS, true) ? ($_POST['payment_method'] ?? 'COD') : 'COD',
                'status'         => 'NEW',
            ], $items, 0);
        } catch (RuntimeException $e) {
            flash_set('error', $e->getMessage());
            redirect('/marketplace');
        }

        $order = Sale::find($orderId);
        $total = Sale::total((array) $order);
        $lines = Sale::items($orderId);

        \App\Models\Notification::push(
            'Order marketplace baru: ' . $orderNo,
            $buyerName . ' (' . $buyerPhone . ') memesan ' . Sale::itemCount($orderId) . ' item — ' . rupiah($total),
            'INFO',
            '/sales',
            ['role' => \App\Core\Roles::ADMIN]
        );
        Audit::log('CREATE', 'Order marketplace ' . $orderNo . ' oleh ' . $buyerName . ' (' . rupiah($total) . ')', 'MARKETPLACE', $orderNo);

        // Susun pesan WhatsApp.
        $brandName = (string) Setting::get('brandName', 'KUTT SUKA MAKMUR');
        $waLines = [
            'Halo ' . $brandName . '!',
            'Saya ingin memesan:',
            '',
        ];
        foreach ($lines as $i => $line) {
            $waLines[] = ($i + 1) . '. ' . $line['product_name'] . ' x' . (int) $line['quantity'] . ' = ' . rupiah((float) $line['total_price']);
        }
        $waLines[] = '';
        $waLines[] = 'Total: ' . rupiah($total);
        $waLines[] = 'No. Order: ' . $orderNo;
        $waLines[] = 'Nama: ' . $buyerName;
        $waLines[] = 'Metode: ' . (string) $order['payment_method'];
        if ($buyerNote !== '') {
            $waLines[] = 'Catatan: ' . $buyerNote;
        }

        $phone = preg_replace('/[^0-9]/', '', (string) Setting::get('footerPhone', '')) ?: '';
        // footerPhone bisa berisi beberapa nomor; ambil nomor WA dari setting khusus bila ada.
        $waTarget = preg_replace('/[^0-9]/', '', (string) Setting::get('waOrderPhone', '')) ?: $phone;
        if ($waTarget !== '' && str_starts_with($waTarget, '0')) {
            $waTarget = '62' . substr($waTarget, 1);
        }

        $waUrl = $waTarget !== ''
            ? 'https://wa.me/' . $waTarget . '?text=' . rawurlencode(implode("\n", $waLines))
            : 'https://wa.me/?text=' . rawurlencode(implode("\n", $waLines));

        $_SESSION['checkout_result'] = [
            'order_no' => $orderNo,
            'total'    => $total,
            'wa_url'   => $waUrl,
        ];
        redirect('/marketplace/sukses');
    }

    /** Halaman sukses setelah checkout + tombol lanjut ke WhatsApp. */
    public function success(): void
    {
        $result = $_SESSION['checkout_result'] ?? null;
        if (!is_array($result)) {
            redirect('/marketplace');
        }
        unset($_SESSION['checkout_result']);

        $this->viewPlain('public/checkout_success', [
            'seoTitle' => 'Pesanan Diterima',
            'seoDesc'  => 'Pesanan berhasil dibuat.',
            'orderNo'  => (string) ($result['order_no'] ?? ''),
            'total'    => (float) ($result['total'] ?? 0),
            'waUrl'    => (string) ($result['wa_url'] ?? ''),
        ]);
    }
}
