<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/app/bootstrap.php';
use Tecnodata\CRM\Core\Auth;
use Tecnodata\CRM\Core\DB;
Auth::requireLogin();
header('Content-Type: application/json; charset=utf-8');
if(!Auth::can('seller','supervisor','admin')){http_response_code(403);echo json_encode(['items'=>[]]);exit;}
$q=trim((string)($_GET['q']??''));
$params=[];$where="active=1";
if($q!==''){
 $where.=" AND (description LIKE ? OR internal_code LIKE ? OR integration_code LIKE ? OR omie_code LIKE ?)";
 $like='%'.$q.'%';$params=[$like,$like,$like,$like];
}
$rows=DB::all("SELECT id,omie_code,internal_code,description,unit,unit_price,ncm,cfop
              FROM products WHERE $where ORDER BY description LIMIT 30",$params);
echo json_encode(['items'=>$rows],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
