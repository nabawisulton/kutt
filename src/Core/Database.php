<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

/**
 * Thin PDO singleton wrapper used by every model.
 * Reads connection settings from environment (config('db.*')).
 */
final class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $host = (string) config('db.host');
        $port = (string) config('db.port');
        $name = (string) config('db.name');
        $charset = (string) config('db.charset');
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $host, $port, $name, $charset);

        try {
            self::$pdo = new PDO(
                $dsn,
                (string) config('db.user'),
                (string) config('db.password'),
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
        } catch (PDOException $e) {
            throw new RuntimeException(
                'Koneksi database gagal: ' . $e->getMessage() .
                '. Pastikan MySQL/MariaDB berjalan dan kredensial di .env sudah benar.',
                0,
                $e
            );
        }

        return self::$pdo;
    }

    /** Run a prepared SELECT and return all rows. */
    public static function all(string $sql, array $params = []): array
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /** Run a prepared SELECT and return the first row (or null). */
    public static function first(string $sql, array $params = []): ?array
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /** Run a prepared scalar SELECT. */
    public static function scalar(string $sql, array $params = []): mixed
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchColumn();
    }

    /** Execute an INSERT/UPDATE/DELETE and return affected row count. */
    public static function exec(string $sql, array $params = []): int
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount();
    }

    /** Insert helper: returns the new auto-increment id. */
    public static function insert(string $table, array $data): int
    {
        $columns = array_keys($data);
        $placeholders = array_map(static fn (string $c): string => ':' . $c, $columns);
        $sql = sprintf(
            'INSERT INTO `%s` (%s) VALUES (%s)',
            $table,
            implode(', ', array_map(static fn (string $c): string => '`' . $c . '`', $columns)),
            implode(', ', $placeholders)
        );
        self::exec($sql, $data);

        return (int) self::pdo()->lastInsertId();
    }

    public static function beginTransaction(): void
    {
        self::pdo()->beginTransaction();
    }

    public static function commit(): void
    {
        if (self::pdo()->inTransaction()) {
            self::pdo()->commit();
        }
    }

    public static function rollBack(): void
    {
        if (self::pdo()->inTransaction()) {
            self::pdo()->rollBack();
        }
    }
}
