<?php
declare(strict_types=1);

define('CLI_SCRIPT', true);
require dirname(__DIR__) . '/app/bootstrap.php';

use Tecnodata\Lms\Core\Clock;
use Tecnodata\Lms\Core\Database;
use Tecnodata\Lms\Services\MigrationService;

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$started=microtime(true);
$now=Clock::sql();
$stats=[
    'migrations'=>[],
    'expired_enrollments'=>0,
    'closed_sessions'=>0,
    'idempotency_deleted'=>0,
    'api_requests_deleted'=>0,
    'import_files_deleted'=>0,
];

try {
    $stats['migrations']=(new MigrationService())->runPending();

    $toExpire=Database::all(
        "SELECT id,status FROM enrollments
          WHERE status='active' AND expires_at IS NOT NULL AND expires_at < ?",
        [$now]
    );

    $pdo=Database::pdo();
    $pdo->beginTransaction();
    try {
        foreach($toExpire as $e){
            Database::execute(
                "UPDATE enrollments SET status='expired',updated_at=? WHERE id=? AND status='active'",
                [$now,$e['id']]
            );
            Database::execute(
                "INSERT INTO enrollment_status_history(
                    enrollment_id,old_status,new_status,reason,changed_by,created_at
                 ) VALUES(?,'active','expired','Prazo da matrícula encerrado',NULL,?)",
                [$e['id'],$now]
            );
            $stats['expired_enrollments']++;
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if($pdo->inTransaction())$pdo->rollBack();
        throw $e;
    }

    $stmt=Database::pdo()->prepare(
        "UPDATE study_sessions
            SET ended_at=last_seen_at
          WHERE ended_at IS NULL
            AND last_seen_at < DATE_SUB(?,INTERVAL 15 MINUTE)"
    );
    $stmt->execute([$now]);
    $stats['closed_sessions']=$stmt->rowCount();

    $stmt=Database::pdo()->prepare(
        "DELETE FROM idempotency_keys
          WHERE created_at < DATE_SUB(?,INTERVAL 30 DAY)"
    );
    $stmt->execute([$now]);
    $stats['idempotency_deleted']=$stmt->rowCount();

    $stmt=Database::pdo()->prepare(
        "DELETE FROM api_requests
          WHERE created_at < DATE_SUB(?,INTERVAL 180 DAY)"
    );
    $stmt->execute([$now]);
    $stats['api_requests_deleted']=$stmt->rowCount();

    $retention=max(1,(int)envv('IMPORT_FILE_RETENTION_DAYS','7'));
    $imports=Database::all(
        "SELECT id,stored_path FROM imports
          WHERE status IN('completed','error')
            AND stored_path <> ''
            AND created_at < DATE_SUB(?,INTERVAL {$retention} DAY)",
        [$now]
    );

    foreach($imports as $import){
        $path=base_path((string)$import['stored_path']);
        if(is_file($path)){
            @unlink($path);
        }
        Database::execute("UPDATE imports SET stored_path='' WHERE id=?",[$import['id']]);
        $stats['import_files_deleted']++;
    }

    $duration=(int)round((microtime(true)-$started)*1000);
    echo '['.$now."] Tecnodata LMS cron OK ({$duration}ms)\n";
    echo json_encode($stats,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT)."\n";
    exit(0);

} catch (Throwable $e) {
    $line='['.Clock::sql().'] CRON ERROR: '.$e->getMessage()."\n".$e->getTraceAsString()."\n";
    @file_put_contents(base_path('storage/cron.log'),$line,FILE_APPEND);
    fwrite(STDERR,$line);
    exit(1);
}
