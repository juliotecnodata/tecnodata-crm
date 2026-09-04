<?php
require dirname(__DIR__,2).'/app/bootstrap.php';

use Tecnodata\CRM\Core\Auth;
use Tecnodata\CRM\Core\Security;
use Tecnodata\CRM\Services\CollectionService;

Auth::requireLogin();
if(!Auth::can('collector','supervisor','admin')){http_response_code(403);exit('Sem acesso');}

if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);exit('Método inválido.');}
if(!Security::verifyCsrf($_POST['_token']??null)){http_response_code(419);exit('Sessão expirada.');}

$id=(int)($_POST['id']??0);
if($id<=0){http_response_code(400);exit('Atendimento inválido.');}

try{
 CollectionService::deleteAction($id,(int)Auth::id());
 header('Location: '.APP_URL.'/cobranca-atendimentos.php?deleted=1');
 exit;
}catch(Throwable $e){
 http_response_code(422);
 exit($e->getMessage());
}
