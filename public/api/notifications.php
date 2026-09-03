<?php
require dirname(__DIR__,2).'/app/bootstrap.php';
use Tecnodata\CRM\Core\Auth;
use Tecnodata\CRM\Core\Security;
use Tecnodata\CRM\Services\NotificationService;
header('Content-Type: application/json; charset=utf-8');
Auth::requireLogin();
$body=json_decode(file_get_contents('php://input'),true)?:[];
if(!Security::verifyCsrf($body['_token']??null)){http_response_code(419);echo json_encode(['ok'=>false,'error'=>'Sessão expirada']);exit;}
try{echo json_encode(['ok'=>true]+NotificationService::poll((int)Auth::id()),JSON_UNESCAPED_UNICODE);}
catch(Throwable $e){http_response_code(500);echo json_encode(['ok'=>false,'error'=>'Não foi possível consultar os alertas.'],JSON_UNESCAPED_UNICODE);}
