<?php
namespace Tecnodata\Lms\Controllers\Api;

use Tecnodata\Lms\Core\Clock;
use Tecnodata\Lms\Core\Database;

final class HealthApiController
{
    public function show(): void
    {
        $db = false;
        try {
            $db = (bool) Database::fetch("SELECT 1 ok");
        } catch (\Throwable) {
            $db = false;
        }

        http_response_code($db ? 200 : 503);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => $db,
            'service' => 'Tecnodata LMS',
            'environment' => (string) envv('APP_ENV','production'),
            'database' => $db ? 'ok' : 'error',
            'timezone' => (string) envv('APP_TIMEZONE','America/Sao_Paulo'),
            'time' => Clock::now()->format(DATE_ATOM),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
