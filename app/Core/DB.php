<?php
namespace Tecnodata\CRM\Core;

use PDO;
use PDOException;

final class DB {
    private static ?PDO $pdo = null;

    public static function conn(): PDO {
        if (self::$pdo) return self::$pdo;
        $cfg = $GLOBALS['config']['database'][APP_ENV];
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $cfg['host'], $cfg['port'], $cfg['database'], $cfg['charset']
        );
        self::$pdo = new PDO($dsn, $cfg['username'], $cfg['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        // Mantém parâmetros/literais na mesma collation padrão do projeto.
        self::$pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
        self::$pdo->exec("SET collation_connection = 'utf8mb4_unicode_ci'");
        return self::$pdo;
    }

    public static function fetch(string $sql, array $params=[]): ?array {
        $st = self::conn()->prepare($sql); $st->execute($params);
        $r = $st->fetch(); return $r ?: null;
    }
    public static function all(string $sql, array $params=[]): array {
        $st = self::conn()->prepare($sql); $st->execute($params);
        return $st->fetchAll();
    }
    public static function exec(string $sql, array $params=[]): int {
        $st = self::conn()->prepare($sql); $st->execute($params);
        return $st->rowCount();
    }
}
