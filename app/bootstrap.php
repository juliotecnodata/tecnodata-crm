<?php
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

spl_autoload_register(static function (string $class): void {
    $prefix = 'Tecnodata\\Lms\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $file = BASE_PATH . '/app/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

use Tecnodata\Lms\Core\Env;
use Tecnodata\Lms\Core\Database;
use Tecnodata\Lms\Core\Clock;

Env::load(BASE_PATH . '/.env');

date_default_timezone_set((string) envv('APP_TIMEZONE', 'America/Sao_Paulo'));

if (PHP_SAPI !== 'cli' && session_status() !== PHP_SESSION_ACTIVE) {
    $secureDefault = str_starts_with((string) envv('APP_URL', ''), 'https://');
    $secure = env_bool('SESSION_SECURE', $secureDefault);

    session_name((string) envv('SESSION_NAME', 'tecnodata_lms'));
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

Database::boot();
Clock::boot();

set_exception_handler(static function (Throwable $e): void {
    $traceId = 'TDM-' . date('Ymd-His') . '-' . strtoupper(bin2hex(random_bytes(3)));
    $line = sprintf(
        "[%s] [%s] %s in %s:%d\n%s\n",
        date('Y-m-d H:i:s'),
        $traceId,
        $e->getMessage(),
        $e->getFile(),
        $e->getLine(),
        $e->getTraceAsString()
    );
    @file_put_contents(BASE_PATH . '/storage/app.log', $line, FILE_APPEND);

    $uri = (string) ($_SERVER['REQUEST_URI'] ?? '');
    if (str_starts_with($uri, '/api/')) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'error' => [
                'code' => 'INTERNAL_ERROR',
                'message' => 'Erro interno.',
                'trace_id' => $traceId,
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return;
    }

    http_response_code(500);
    if (env_bool('APP_DEBUG', false)) {
        echo '<pre>' . htmlspecialchars($line, ENT_QUOTES, 'UTF-8') . '</pre>';
    } else {
        echo 'Erro interno. Código: ' . htmlspecialchars($traceId, ENT_QUOTES, 'UTF-8');
    }
});

function base_path(string $path = ''): string
{
    return BASE_PATH . ($path ? '/' . ltrim($path, '/') : '');
}

function envv(string $key, mixed $default = null): mixed
{
    if (array_key_exists($key, $_ENV)) return $_ENV[$key];
    if (array_key_exists($key, $_SERVER)) return $_SERVER[$key];
    $value = getenv($key);
    return $value === false ? $default : $value;
}

function env_bool(string $key, bool $default = false): bool
{
    $raw = envv($key, $default ? 'true' : 'false');
    return filter_var($raw, FILTER_VALIDATE_BOOL);
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function app_url(string $path = ''): string
{
    $base = rtrim((string) envv('APP_URL', ''), '/');
    if ($path === '') return $base;
    return $base . '/' . ltrim($path, '/');
}

function safe_mode(): bool
{
    return env_bool('LOCAL_SAFE_MODE', false);
}

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}
