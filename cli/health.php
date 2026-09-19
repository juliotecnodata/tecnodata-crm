<?php
declare(strict_types=1);

define('CLI_SCRIPT', true);
require dirname(__DIR__) . '/app/bootstrap.php';

use Tecnodata\Lms\Core\BiometricDatabase;
use Tecnodata\Lms\Core\Clock;
use Tecnodata\Lms\Core\Database;
use Tecnodata\Lms\Services\MigrationService;

if(PHP_SAPI!=='cli')exit;

$result=[
    'time'=>Clock::now()->format(DATE_ATOM),
    'php'=>PHP_VERSION,
    'database'=>(bool)Database::fetch("SELECT 1 ok"),
    'timezone'=>(string)envv('APP_TIMEZONE','America/Sao_Paulo'),
    'pending_migrations'=>array_map('basename',(new MigrationService())->pending()),
    'biometric_database'=>BiometricDatabase::configured()
        ? (BiometricDatabase::ping()?'ok':'error')
        : 'not_configured',
];

echo json_encode($result,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
