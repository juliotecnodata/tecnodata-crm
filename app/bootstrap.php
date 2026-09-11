<?php
declare(strict_types=1);

define('APP_ROOT',dirname(__DIR__));

$configFile=APP_ROOT.'/config/config.php';
if(!is_file($configFile)){
 http_response_code(500);
 exit('Arquivo config/config.php não encontrado. Copie config/config.example.php para config/config.php e configure o ambiente.');
}

$GLOBALS['config']=require $configFile;
$cfg=$GLOBALS['config']['app']??[];

$host=strtolower((string)($_SERVER['HTTP_HOST']??'localhost'));
$host=preg_replace('/:\\d+$/','',$host)?:'localhost';
$isLocal=in_array($host,['localhost','127.0.0.1','::1'],true)
 || str_ends_with($host,'.local')
 || str_ends_with($host,'.test');

define('APP_ENV',$isLocal?'local':'production');
define('APP_IS_LOCAL',$isLocal);

date_default_timezone_set((string)($cfg['timezone']??'America/Sao_Paulo'));

$envUrl=trim((string)(getenv('TDCRM_APP_URL')?:''));
$configUrl=trim((string)($isLocal?($cfg['local_url']??''):($cfg['production_url']??'')));
$appUrl=$envUrl!==''?$envUrl:$configUrl;

if($appUrl===''){
 $https=(!$isLocal)&&(
  (!empty($_SERVER['HTTPS'])&&strtolower((string)$_SERVER['HTTPS'])!=='off')
  || strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO']??''))==='https'
 );
 $scheme=$https?'https':'http';
 $scriptDir=str_replace('\\','/',dirname((string)($_SERVER['SCRIPT_NAME']??'/')));
 $scriptDir=preg_replace('#/public$#','',$scriptDir)?:'';
 $scriptDir=$scriptDir==='/'?'':rtrim($scriptDir,'/');
 $appUrl=$scheme.'://'.($_SERVER['HTTP_HOST']??'localhost').$scriptDir;
}

define('APP_URL',rtrim($appUrl,'/'));

if(session_status()!==PHP_SESSION_ACTIVE){
 session_name((string)($cfg['session_name']??'tecnodata_crm'));
 $cookiePath=parse_url(APP_URL,PHP_URL_PATH)?:'/';
 if($cookiePath==='')$cookiePath='/';
 session_set_cookie_params([
  'httponly'=>true,
  'secure'=>!$isLocal,
  'samesite'=>'Lax',
  'path'=>rtrim($cookiePath,'/').'/' ,
 ]);
 session_start();
}

require APP_ROOT.'/app/core.php';
require APP_ROOT.'/app/services.php';
require APP_ROOT.'/app/views.php';
