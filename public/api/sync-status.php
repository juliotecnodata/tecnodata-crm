<?php
require dirname(__DIR__,2).'/app/bootstrap.php';
use Tecnodata\CRM\Core\Auth;
use Tecnodata\CRM\Services\ModularSyncService;
header('Content-Type: application/json; charset=utf-8');
Auth::requireLogin();
if(!Auth::can('admin')){http_response_code(403);echo json_encode(['ok'=>false]);exit;}
echo json_encode(['ok'=>true]+(new ModularSyncService())->status(),JSON_UNESCAPED_UNICODE);
