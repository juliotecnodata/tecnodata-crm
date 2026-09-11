<?php
declare(strict_types=1);

$host=strtolower((string)($_SERVER['HTTP_HOST']??'localhost'));
$host=preg_replace('/:\\d+$/','',$host)?:'localhost';
$isLocal=in_array($host,['localhost','127.0.0.1','::1'],true)||str_ends_with($host,'.local')||str_ends_with($host,'.test');

if($isLocal){
 ini_set('display_errors','1');
 error_reporting(E_ALL);
}

try{
 require dirname(__DIR__).'/app/bootstrap.php';
 $router=new Router();
 require APP_ROOT.'/routes.php';

 $uri=parse_url($_SERVER['REQUEST_URI']??'/',PHP_URL_PATH)?:'/';
 $base=rtrim((string)(parse_url(APP_URL,PHP_URL_PATH)?:''),'/');
 if($base!==''&&($uri===$base||str_starts_with($uri,$base.'/')))$uri=substr($uri,strlen($base))?:'/';
 if($uri==='/index.php')$uri='/';
 if(str_starts_with($uri,'/index.php/'))$uri=substr($uri,10)?:'/';

 $router->dispatch($_SERVER['REQUEST_METHOD']??'GET',$uri);
}catch(Throwable $e){
 http_response_code(500);
 if($isLocal){
  echo '<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
  echo '<title>Erro Tecnodata CRM</title><style>body{font-family:Arial;background:#f4f6f7;color:#253038;padding:32px}.box{max-width:1000px;margin:auto;background:#fff;border:1px solid #dfe5e8;border-radius:10px;padding:22px}h1{color:#c55252}pre{white-space:pre-wrap;word-break:break-word;background:#f8fafb;padding:14px;border-radius:8px}</style></head><body><div class="box">';
  echo '<h1>Erro de execução do CRM</h1>';
  echo '<p><strong>'.htmlspecialchars(get_class($e),ENT_QUOTES,'UTF-8').'</strong></p>';
  echo '<pre>'.htmlspecialchars($e->getMessage(),ENT_QUOTES,'UTF-8')."

".htmlspecialchars($e->getFile(),ENT_QUOTES,'UTF-8').':'.$e->getLine().'</pre>';
  echo '</div></body></html>';
 }else{
  $errorId=date('YmdHis').'-'.substr(hash('sha256',get_class($e).'|'.$e->getFile().'|'.$e->getLine().'|'.$e->getMessage()),0,10);
  $logDir=APP_ROOT.'/storage/logs';
  if(!is_dir($logDir))@mkdir($logDir,0750,true);
  $logLine=sprintf("[%s] [%s] %s: %s in %s:%d%s",date('c'),$errorId,get_class($e),$e->getMessage(),$e->getFile(),$e->getLine(),PHP_EOL);
  @file_put_contents($logDir.'/app-error.log',$logLine,FILE_APPEND|LOCK_EX);

  $safeClass=htmlspecialchars(get_class($e),ENT_QUOTES,'UTF-8');
  $safeFile=htmlspecialchars(basename($e->getFile()),ENT_QUOTES,'UTF-8');
  $safeLine=(int)$e->getLine();
  $safeId=htmlspecialchars($errorId,ENT_QUOTES,'UTF-8');

  echo '<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
  echo '<title>Erro Tecnodata CRM</title><style>body{margin:0;font-family:Inter,Arial,sans-serif;background:#f5f8fb;color:#122634;padding:32px}.box{max-width:760px;margin:8vh auto;background:#fff;border:1px solid #dce5ec;border-radius:10px;padding:28px}.icon{width:46px;height:46px;display:grid;place-items:center;border-radius:10px;background:#fff0eb;color:#ea5127;font-size:22px}h1{margin:18px 0 8px;font-size:26px}p{color:#6f808d}.meta{margin-top:18px;padding:14px;border:1px solid #e2e9ee;border-radius:8px;background:#f9fbfc;font-size:13px;line-height:1.8}.meta strong{color:#122634}</style></head><body><div class="box">';
  echo '<div class="icon">!</div><h1>Erro interno</h1><p>O CRM encontrou um erro de execução. Os dados técnicos abaixo não exibem credenciais.</p>';
  echo '<div class="meta"><strong>Tipo:</strong> '.$safeClass.'<br><strong>Arquivo:</strong> '.$safeFile.'<br><strong>Linha:</strong> '.$safeLine.'<br><strong>Código:</strong> '.$safeId.'</div>';
  echo '</div></body></html>';
 }
}