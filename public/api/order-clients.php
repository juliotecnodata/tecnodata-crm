<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/app/bootstrap.php';
use Tecnodata\CRM\Core\Auth;
use Tecnodata\CRM\Core\DB;
Auth::requireLogin();
header('Content-Type: application/json; charset=utf-8');
if(!Auth::can('seller','supervisor','admin')){http_response_code(403);echo json_encode(['items'=>[]]);exit;}
$q=trim((string)($_GET['q']??''));
$u=Auth::user();
$params=[];$where="c.active=1";
$joins=" LEFT JOIN client_metrics m ON m.client_id=c.id";
if(($u['role']??'')==='seller'){
 $joins.=" LEFT JOIN client_portfolio_assignments pa ON pa.client_id=c.id AND pa.month_ref=?";
 $params[]=date('Y-m');
 $where.=" AND (CASE WHEN pa.id IS NOT NULL THEN pa.seller_omie_code ELSE m.seller_omie_code END)=?";
 $params[]=(string)($u['seller_omie_code']??'');
}
if($q!==''){
 $where.=" AND (c.name LIKE ? OR c.legal_name LIKE ? OR c.document LIKE ? OR c.omie_code LIKE ?)";
 $like='%'.$q.'%';$params=[$like,$like,$like,$like];
}
$rows=DB::all("SELECT c.id,c.name,c.document,c.uf,c.city,c.omie_code,m.last_purchase_at,m.revenue_12m
              FROM clients c $joins
              WHERE $where ORDER BY c.name LIMIT 20",$params);
echo json_encode(['items'=>$rows],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
