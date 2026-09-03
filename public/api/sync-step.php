<?php
require dirname(__DIR__,2).'/app/bootstrap.php';
use Tecnodata\CRM\Core\Auth;
use Tecnodata\CRM\Core\Security;
use Tecnodata\CRM\Services\ModularSyncService;

header('Content-Type: application/json; charset=utf-8');
Auth::requireLogin();
if(!Auth::can('admin')){http_response_code(403);echo json_encode(['ok'=>false,'error'=>'Sem acesso']);exit;}
$body=json_decode(file_get_contents('php://input'),true)?:[];
if(!Security::verifyCsrf($body['_token']??null)){http_response_code(419);echo json_encode(['ok'=>false,'error'=>'Sessão expirada']);exit;}
try{
 $r=(new ModularSyncService())->step((int)($body['run_id']??0));
 echo json_encode(['ok'=>true]+$r,JSON_UNESCAPED_UNICODE);
}catch(Throwable $e){http_response_code(422);echo json_encode(['ok'=>false,'error'=>$e->getMessage()],JSON_UNESCAPED_UNICODE);}
