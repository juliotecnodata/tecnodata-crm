<?php
namespace Tecnodata\Lms\Services;

use Tecnodata\Lms\Core\Clock;
use Tecnodata\Lms\Core\Database;

final class MigrationService
{
    public function ensureTable(): void
    {
        Database::pdo()->exec(
            "CREATE TABLE IF NOT EXISTS schema_migrations (
                migration VARCHAR(190) PRIMARY KEY,
                applied_at DATETIME NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    public function files(): array
    {
        $files=glob(base_path('database/*.sql'))?:[];
        sort($files,SORT_NATURAL);
        return $files;
    }

    public function pending(): array
    {
        $this->ensureTable();
        $applied=array_column(
            Database::all("SELECT migration FROM schema_migrations"),
            'migration'
        );
        return array_values(array_filter(
            $this->files(),
            fn(string $f)=>!in_array(basename($f),$applied,true)
        ));
    }

    public function runPending(): array
    {
        $this->ensureTable();
        $done=[];

        foreach($this->pending() as $file){
            $this->runFile($file);
            Database::execute(
                "INSERT IGNORE INTO schema_migrations(migration,applied_at) VALUES(?,?)",
                [basename($file),Clock::sql()]
            );
            $done[]=basename($file);
        }
        return $done;
    }

    private function runFile(string $file): void
    {
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
            if($statement==='') continue;
            Database::pdo()->exec($statement);
        }
    }
}
