<?php
namespace Tecnodata\Lms\Core;

use PDO;
use RuntimeException;

final class BiometricDatabase
{
    private static ?PDO $pdo = null;

    public static function configured(): bool
    {
        return trim((string) envv('BIO_DB_DATABASE','')) !== ''
            && trim((string) envv('BIO_DB_USERNAME','')) !== '';
    }

    public static function boot(): void
    {
        if (self::$pdo) return;

        if (!self::configured()) {
            throw new RuntimeException('Banco biométrico ainda não configurado.');
        }

        $host=(string)envv('BIO_DB_HOST','127.0.0.1');
        $port=(string)envv('BIO_DB_PORT','3306');
        $db=(string)envv('BIO_DB_DATABASE','');
        $charset=(string)envv('BIO_DB_CHARSET','utf8mb4');

        $dsn="mysql:host={$host};port={$port};dbname={$db};charset={$charset}";

        self::$pdo=new PDO(
            $dsn,
            (string)envv('BIO_DB_USERNAME',''),
            (string)envv('BIO_DB_PASSWORD',''),
            [
                PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES=>false,
            ]
        );

        self::$pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
        self::$pdo->exec("SET SESSION time_zone='-03:00'");
    }

    public static function pdo(): PDO
    {
        self::boot();
        return self::$pdo;
    }

    public static function fetch(string $sql,array $params=[]): ?array
    {
        $stmt=self::pdo()->prepare($sql);
        $stmt->execute($params);
        $row=$stmt->fetch();
        return $row===false?null:$row;
    }

    public static function all(string $sql,array $params=[]): array
    {
        $stmt=self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function execute(string $sql,array $params=[]): bool
    {
        $stmt=self::pdo()->prepare($sql);
        return $stmt->execute($params);
    }

    public static function id(): int
    {
        return (int)self::pdo()->lastInsertId();
    }

    public static function ping(): bool
    {
        if (!self::configured()) return false;
        try {
            return (bool)self::fetch("SELECT 1 ok");
        } catch (\Throwable) {
            return false;
        }
    }

    public static function installSchema(): void
    {
        $file=base_path('database/biometric/schema.sql');
        if(!is_file($file)) throw new RuntimeException('Schema biométrico não encontrado.');

        $sql=(string)file_get_contents($file);
        $lines=preg_split('/\R/',$sql)?:[];
        $clean=[];
        foreach($lines as $line){
            if(preg_match('/^\s*--/',$line)) continue;
            $clean[]=$line;
        }
        $sql=implode("\n",$clean);

        foreach(preg_split('/;\s*(?:\r?\n|$)/',$sql)?:[] as $statement){
            $statement=trim($statement);
            if($statement!=='') self::pdo()->exec($statement);
        }
    }
}
