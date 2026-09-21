<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use RuntimeException;

/**
 * Kartu stok: satu-satunya jalur mutasi stok produk.
 * Menulis kolom products.stock dan baris audit stock_movements secara
 * atomik (SELECT ... FOR UPDATE mencegah race antar kasir).
 */
final class StockMovement
{
    /**
     * Terapkan perubahan stok (+ masuk / - keluar) dan catat kartu stok.
     * Melempar RuntimeException bila stok tidak mencukupi.
     *
     * @throws RuntimeException
     */
    public static function apply(int $productId, int $changeQty, string $reason, ?string $reference, string $note, int $userId): int
    {
        $changeQty = (int) $changeQty;
        if ($changeQty === 0) {
            return self::currentStock($productId);
        }

        // FOR UPDATE: baris produk terkunci sampai commit/rollback transaksi
        // pemanggil, sehingga dua kasir tidak bisa menjual stok yang sama.
        $row = Database::first(
            'SELECT stock FROM products WHERE id = ? FOR UPDATE',
            [$productId]
        );
        if ($row === null) {
            throw new RuntimeException('Produk tidak ditemukan.');
        }

        $after = (int) $row['stock'] + $changeQty;
        if ($after < 0) {
            throw new RuntimeException('Stok tidak mencukupi (sisa ' . (int) $row['stock'] . ').');
        }

        Database::exec('UPDATE products SET stock = ?, updated_at = NOW() WHERE id = ?', [$after, $productId]);
        Database::insert('stock_movements', [
            'product_id'  => $productId,
            'user_id'     => $userId > 0 ? $userId : null, // 0 = tanpa user (checkout marketplace publik)
            'change_qty'  => $changeQty,
            'stock_after' => $after,
            'reason'      => $reason,
            'reference'   => $reference,
            'note'        => mb_substr($note, 0, 255),
            'created_at'  => date('Y-m-d H:i:s'),
        ]);

        return $after;
    }

    public static function currentStock(int $productId): int
    {
        return (int) (Database::scalar('SELECT stock FROM products WHERE id = ?', [$productId]) ?? 0);
    }

    /** Kartu stok satu produk (terbaru dulu). */
    public static function forProduct(int $productId, int $limit = 50): array
    {
        return Database::all(
            'SELECT sm.*, u.full_name AS user_name FROM stock_movements sm
             LEFT JOIN users u ON u.id = sm.user_id
             WHERE sm.product_id = ?
             ORDER BY sm.id DESC LIMIT ' . max(1, $limit),
            [$productId]
        );
    }
}
