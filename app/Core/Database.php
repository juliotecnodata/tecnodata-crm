<?php
namespace Tecnodata\Lms\Core;

use PDO;

final class Database
{
    private static ?PDO $pdo = null;

    public static function boot(): void
    {
        if (self::$pdo) {
            return;
        }
        $host = (string) envv('DB_HOST', '127.0.0.1');
        $port = (string) envv('DB_PORT', '3306');
        $db = (string) envv('DB_DATABASE', '');
        $charset = (string) envv('DB_CHARSET', 'utf8mb4');
        $dsn = "mysql:host={$host};port={$port};dbname={$db};charset={$charset}";
        self::$pdo = new PDO($dsn, (string) envv('DB_USERNAME', ''), (string) envv('DB_PASSWORD', ''), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        self::$pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
        self::$pdo->exec("SET SESSION time_zone = '-03:00'");
    }

    public static function pdo(): PDO
    {
        self::boot();
        return self::$pdo;
    }

    public static function fetch(string $sql, array $params = []): ?array
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public static function all(string $sql, array $params = []): array
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function execute(string $sql, array $params = []): bool
    {
        $stmt = self::pdo()->prepare($sql);
        return $stmt->execute($params);
    }

    public static function id(): int
    {
        return (int) self::pdo()->lastInsertId();
    }
}
