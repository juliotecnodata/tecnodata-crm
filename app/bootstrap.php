<?php
declare(strict_types=1);

$config = require dirname(__DIR__) . '/config/config.php';

date_default_timezone_set($config['app']['timezone'] ?? 'America/Sao_Paulo');

$isProduction = ($config['app']['environment'] ?? 'auto') === 'production'
    || (($config['app']['environment'] ?? 'auto') === 'auto'
        && isset($_SERVER['HTTP_HOST'])
        && stripos($_SERVER['HTTP_HOST'], 'tecnodataeducacional.com.br') !== false);

define('APP_ROOT', dirname(__DIR__));
define('APP_ENV', $isProduction ? 'production' : 'local');
define('APP_URL', rtrim($isProduction ? $config['app']['production_url'] : $config['app']['local_url'], '/'));

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name($config['app']['session_name'] ?? 'tecnodata_crm');
    session_set_cookie_params([
        'httponly' => true,
        'secure' => $isProduction,
        'samesite' => 'Lax',
        'path' => '/',
    ]);
    session_start();
}

spl_autoload_register(function (string $class): void {
    $prefix = 'Tecnodata\\CRM\\';
    if (!str_starts_with($class, $prefix)) return;
    $relative = substr($class, strlen($prefix));
    $file = APP_ROOT . '/app/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) require $file;
});

$GLOBALS['config'] = $config;
