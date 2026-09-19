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
    session_name((string) envv('SESSION_NAME', 'tecnodata_lms'));
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => filter_var(envv('SESSION_SECURE', 'true'), FILTER_VALIDATE_BOOL),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

Database::boot();
Clock::boot();

set_exception_handler(static function (Throwable $e): void {
    $line = sprintf("[%s] %s in %s:%d\n%s\n", date('Y-m-d H:i:s'), $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString());
    @file_put_contents(BASE_PATH . '/storage/app.log', $line, FILE_APPEND);
    http_response_code(500);
    if (filter_var(envv('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOL)) {
        echo '<pre>' . htmlspecialchars($line, ENT_QUOTES, 'UTF-8') . '</pre>';
    } else {
        echo 'Erro interno. Consulte o log da aplicação.';
    }
});

function base_path(string $path = ''): string {
    return BASE_PATH . ($path ? '/' . ltrim($path, '/') : '');
}

function envv(string $key, mixed $default = null): mixed {
    return $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key) ?: $default;
}

function e(mixed $value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $url): never {
    header('Location: ' . $url);
    exit;
}
