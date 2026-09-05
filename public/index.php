<?php
declare(strict_types=1);
require dirname(__DIR__).'/app/bootstrap.php';
$router=new Router();
require APP_ROOT.'/routes.php';
$uri=parse_url($_SERVER['REQUEST_URI']??'/',PHP_URL_PATH)?:'/';
$base=parse_url(APP_URL,PHP_URL_PATH)?:'';
if($base!==''&&str_starts_with($uri,$base))$uri=substr($uri,strlen($base))?:'/';
$router->dispatch($_SERVER['REQUEST_METHOD']??'GET',$uri);
