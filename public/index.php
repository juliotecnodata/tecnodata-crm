<?php
declare(strict_types=1);

$host=$_SERVER['HTTP_HOST']??'localhost';
$isLocal=str_contains($host,'localhost')||str_contains($host,'127.0.0.1');

if($isLocal){
 ini_set('display_errors','1');
 error_reporting(E_ALL);
}

try{
 require dirname(__DIR__).'/app/bootstrap.php';
 $router=new Router();
 require APP_ROOT.'/routes.php';

 $uri=parse_url($_SERVER['REQUEST_URI']??'/',PHP_URL_PATH)?:'/';
 $base=parse_url(APP_URL,PHP_URL_PATH)?:'';
 if($base!==''&&str_starts_with($uri,$base))$uri=substr($uri,strlen($base))?:'/';

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
  echo '<h1>Erro interno</h1><p>O CRM encontrou um erro de execução.</p>';
 }
}