<?php
declare(strict_types=1);
define('APP_ROOT',dirname(__DIR__));
$configFile=APP_ROOT.'/config/config.php';
if(!is_file($configFile)){http_response_code(500);exit('Arquivo config/config.php não encontrado.');}
$GLOBALS['config']=require $configFile;
$cfg=$GLOBALS['config']['app'];
date_default_timezone_set((string)($cfg['timezone']??'America/Sao_Paulo'));
$host=$_SERVER['HTTP_HOST']??'localhost';
$isLocal=str_contains($host,'localhost')||str_contains($host,'127.0.0.1');
define('APP_URL',rtrim((string)($isLocal?$cfg['local_url']:$cfg['production_url']),'/'));
if(session_status()!==PHP_SESSION_ACTIVE){
 session_name((string)($cfg['session_name']??'tecnodata_crm'));
 session_set_cookie_params(['httponly'=>true,'secure'=>!$isLocal,'samesite'=>'Lax','path'=>'/']);
 session_start();
}
require APP_ROOT.'/app/core.php';
require APP_ROOT.'/app/services.php';
require APP_ROOT.'/app/views.php';
