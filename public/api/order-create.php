<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/app/bootstrap.php';
use Tecnodata\CRM\Core\Auth;
use Tecnodata\CRM\Core\Security;
use Tecnodata\CRM\Services\SalesOrderService;
Auth::requireLogin();
header('Content-Type: application/json; charset=utf-8');
if(!Auth::can('seller','supervisor','admin')){http_response_code(403);echo json_encode(['ok'=>false,'error'=>'Sem acesso.']);exit;}
$body=json_decode(file_get_contents('php://input'),true)?:[];
if(!Security::verifyCsrf($body['_token']??null)){http_response_code(419);echo json_encode(['ok'=>false,'error'=>'Sessão expirada.']);exit;}
$u=Auth::user();
$forcedSeller=($u['role']??'')==='seller'?(string)($u['seller_omie_code']??''):null;
if(($u['role']??'')==='seller'&&$forcedSeller===''){http_response_code(422);echo json_encode(['ok'=>false,'error'=>'Seu usuário não está vinculado a um vendedor Omie.']);exit;}
try{
 $result=SalesOrderService::create($body,(int)$u['id'],$forcedSeller);
 echo json_encode(['ok'=>true,'message'=>'Pedido incluído na Omie com sucesso.']+$result,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
}catch(Throwable $e){
 http_response_code(422);
 echo json_encode(['ok'=>false,'error'=>$e->getMessage()],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
}
