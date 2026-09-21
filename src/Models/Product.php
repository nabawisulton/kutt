<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Product (katalog marketplace & kasir).
 * Seluruh mutasi stok WAJIB lewat StockMovement::apply() agar kartu stok
 * dan kolom stock selalu konsisten.
 */
final class Product
{
    public static function find(int $id): ?array
    {
        return Database::first('SELECT * FROM products WHERE id = ?', [$id]);
    }

    public static function findBySku(string $sku): ?array
    {
        return Database::first('SELECT * FROM products WHERE sku = ?', [$sku]);
    }

    /** Cari produk aktif berdasarkan barcode (scan kasir). */
    public static function findByBarcode(string $barcode): ?array
    {
        $barcode = trim($barcode);
        if ($barcode === '') {
            return null;
        }

        return Database::first(
            'SELECT * FROM products WHERE barcode = ? AND is_active = 1 LIMIT 1',
            [$barcode]
        );
    }

    /** Semua produk utk dashboard (termasuk nonaktif). */
    public static function all(): array
    {
        return Database::all('SELECT * FROM products ORDER BY name ASC, id ASC');
    }

    /** Katalog publik marketplace: aktif + stok > 0. */
    public static function publicCatalog(string $q = '', string $category = ''): array
    {
        $where = ["is_active = 1", "stock > 0"];
        $params = [];
        if ($q !== '') {
            $where[] = '(name LIKE ? OR description LIKE ? OR sku LIKE ?)';
            $like = '%' . $q . '%';
            array_push($params, $like, $like, $like);
        }
        if ($category !== '') {
            $where[] = 'category = ?';
            $params[] = $category;
        }

        return Database::all(
            'SELECT id, sku, barcode, name, category, description, price, stock, unit, image_path
             FROM products WHERE ' . implode(' AND ', $where) . '
             ORDER BY category ASC, name ASC LIMIT 200',
            $params
        );
    }

    /** @return list<string> kategori unik yang punya produk aktif. */
    public static function publicCategories(): array
    {
        $rows = Database::all(
            "SELECT DISTINCT category FROM products
             WHERE is_active = 1 AND stock > 0 AND category IS NOT NULL AND category != ''
             ORDER BY category"
        );

        return array_column($rows, 'category');
    }

    /** Daftar kategori unik utk filter dashboard. */
    public static function categories(): array
    {
        $rows = Database::all(
            "SELECT DISTINCT category FROM products
             WHERE category IS NOT NULL AND category != '' ORDER BY category"
        );

        return array_column($rows, 'category');
    }

    /** Buat produk + kartu stok awal (Purchases) dalam satu transaksi pemanggil. */
    public static function create(array $data, int $userId): int
    {
        $stock = (int) ($data['stock'] ?? 0);
        $id = Database::insert('products', $data);
        if ($stock > 0) {
            StockMovement::apply((int) $id, $stock, 'PURCHASE', null, 'Stok awal produk baru', $userId);
        }

        return (int) $id;
    }

    /**
     * Ringkasan stok utk dashboard: nilai persediaan, produk stok kritis.
     * @return array{totalProducts:int, activeProducts:int, lowStock:list<array<string,mixed>>, stockValue:float}
     */
    public static function inventorySummary(): array
    {
        $totalProducts = (int) Database::scalar('SELECT COUNT(*) FROM products');
        $activeProducts = (int) Database::scalar('SELECT COUNT(*) FROM products WHERE is_active = 1');
        $lowStock = Database::all(
            'SELECT id, sku, name, stock, min_stock, unit FROM products
             WHERE stock <= min_stock AND is_active = 1 ORDER BY (stock - min_stock) ASC LIMIT 20'
        );
        $stockValue = (float) Database::scalar(
            'SELECT COALESCE(SUM(stock * COALESCE(cost_price, price)), 0) FROM products'
        );

        return [
            'totalProducts'  => $totalProducts,
            'activeProducts' => $activeProducts,
            'lowStock'       => $lowStock,
            'stockValue'     => $stockValue,
        ];
    }
}
