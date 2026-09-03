<?php
require dirname(__DIR__,2).'/app/bootstrap.php';
require APP_ROOT.'/app/Support/helpers.php';

use Tecnodata\CRM\Core\Auth;
use Tecnodata\CRM\Core\DB;

Auth::requireLogin();
if(!Auth::can('collector','supervisor','admin')){http_response_code(403);header('Content-Type: application/json');echo json_encode(['error'=>'Sem acesso']);exit;}

header('Content-Type: application/json; charset=utf-8');
$u=Auth::user();
$draw=max(0,(int)($_GET['draw']??0));
$start=max(0,(int)($_GET['start']??0));
$length=(int)($_GET['length']??25);if($length<10)$length=10;if($length>100)$length=100;
$search=trim((string)($_GET['search']['value']??''));

$viewRaw=(string)($_GET['view']??'open');
$signalRaw=(string)($_GET['signal']??'all');
$view=in_array($viewRaw,['open','all','settled'],true)?$viewRaw:'open';
$signal=in_array($signalRaw,['all','mine','attended','agreement','promise','unattended'],true)?$signalRaw:'all';
$month=(string)($_GET['month']??date('Y-m'));if(!preg_match('/^\d{4}-\d{2}$/',$month))$month=date('Y-m');
$monthStart=$month.'-01';$monthNext=date('Y-m-d',strtotime($monthStart.' +1 month'));
$seller=trim((string)($_GET['seller']??''));$uf=strtoupper(trim((string)($_GET['uf']??'')));

try {
$paramsBase=[$monthStart,$monthNext,(int)$u['id'],$monthStart,$monthNext,$monthStart,$monthNext,$monthStart,$monthNext];

$baseSql="SELECT
 c.id,c.omie_code,c.name,c.uf,m.last_purchase_at,m.days_without_purchase,m.seller_omie_code,s.name seller_name,
 COALESCE(d.omie_debt,0) omie_debt,COALESCE(adj.pending_received,0) pending_received,
 GREATEST(0,COALESCE(d.omie_debt,0)-COALESCE(adj.pending_received,0)) effective_debt,
 a.last_contact,a.attended_period_at,a.mine_period_at,a.agreement_period_at,a.promise_period_at,
 la.last_result,la.last_user_name
FROM clients c
LEFT JOIN client_metrics m ON m.client_id=c.id
LEFT JOIN sellers s ON s.omie_code=m.seller_omie_code
LEFT JOIN (
 SELECT fm.client_omie_code,SUM(fm.amount) omie_debt
 FROM financial_movements fm
 INNER JOIN financial_accounts fa ON fa.omie_code=fm.account_omie_code AND fa.selected=1 AND fa.active=1
 WHERE fm.status IN('ATRASADO','PAGTO_PARCIAL')
 GROUP BY fm.client_omie_code
) d ON d.client_omie_code=c.omie_code
LEFT JOIN collection_client_adjustments adj ON adj.client_id=c.id
LEFT JOIN (
 SELECT ca.client_id,
   MAX(ca.created_at) last_contact,
   MAX(CASE WHEN ca.created_at>=? AND ca.created_at<? THEN ca.created_at END) attended_period_at,
   MAX(CASE WHEN ca.user_id=? AND ca.created_at>=? AND ca.created_at<? THEN ca.created_at END) mine_period_at,
   MAX(CASE WHEN ca.result='acordo' AND ca.created_at>=? AND ca.created_at<? THEN ca.created_at END) agreement_period_at,
   MAX(CASE WHEN ca.result='promessa' AND ca.created_at>=? AND ca.created_at<? THEN ca.created_at END) promise_period_at
 FROM collection_actions ca
 WHERE ca.deleted_at IS NULL
 GROUP BY ca.client_id
) a ON a.client_id=c.id
LEFT JOIN (
 SELECT ca.client_id,ca.result last_result,u.name last_user_name
 FROM collection_actions ca
 INNER JOIN users u ON u.id=ca.user_id
 INNER JOIN (
   SELECT client_id,MAX(id) max_id FROM collection_actions WHERE deleted_at IS NULL GROUP BY client_id
 ) z ON z.max_id=ca.id
) la ON la.client_id=c.id";

$fixed=["(COALESCE(d.omie_debt,0)>0 OR a.last_contact>=DATE_SUB(NOW(),INTERVAL 180 DAY))"];
$fixedParams=$paramsBase;
if($view==='open')$fixed[]="GREATEST(0,COALESCE(d.omie_debt,0)-COALESCE(adj.pending_received,0))>0.009";
elseif($view==='settled')$fixed[]="GREATEST(0,COALESCE(d.omie_debt,0)-COALESCE(adj.pending_received,0))<=0.009";

if($signal==='mine')$fixed[]="a.mine_period_at IS NOT NULL";
elseif($signal==='attended')$fixed[]="a.attended_period_at IS NOT NULL";
elseif($signal==='agreement')$fixed[]="a.agreement_period_at IS NOT NULL";
elseif($signal==='promise')$fixed[]="a.promise_period_at IS NOT NULL";
elseif($signal==='unattended')$fixed[]="a.attended_period_at IS NULL";

if($seller!==''){$fixed[]="m.seller_omie_code=?";$fixedParams[]=$seller;}
if($uf!==''){$fixed[]="c.uf=?";$fixedParams[]=$uf;}

$whereFixed=' WHERE '.implode(' AND ',$fixed);
$countTotal=DB::fetch("SELECT COUNT(*) n FROM ($baseSql$whereFixed) q",$fixedParams);
$recordsTotal=(int)($countTotal['n']??0);

$filtered=$fixed;$filteredParams=$fixedParams;
if($search!==''){
 $filtered[]="(c.name LIKE ? OR c.omie_code LIKE ? OR s.name LIKE ? OR c.document LIKE ?)";
 $like='%'.$search.'%';array_push($filteredParams,$like,$like,$like,$like);
}
$whereFiltered=' WHERE '.implode(' AND ',$filtered);
$countFiltered=DB::fetch("SELECT COUNT(*) n FROM ($baseSql$whereFiltered) q",$filteredParams);
$recordsFiltered=(int)($countFiltered['n']??0);

$orderMap=[
 0=>'c.name',1=>'seller_name',2=>'c.uf',3=>'m.last_purchase_at',4=>'m.days_without_purchase',
 5=>'effective_debt',6=>'effective_debt',7=>'a.last_contact',8=>'a.last_contact'
];
$orderIdx=(int)($_GET['order'][0]['column']??8);
$orderDir=strtolower((string)($_GET['order'][0]['dir']??'desc'))==='asc'?'ASC':'DESC';
$orderBy=$orderMap[$orderIdx]??'a.last_contact';

$sql="$baseSql$whereFiltered ORDER BY $orderBy $orderDir,c.name ASC LIMIT $length OFFSET $start";
$rows=DB::all($sql,$filteredParams);

$data=[];
foreach($rows as $r){
 $settled=(float)$r['effective_debt']<=0.009;
 $financial=$settled
  ? '<span class="status-pill status-paid"><i class="fa-solid fa-circle-check"></i>Quitado</span>'
  : ((float)$r['pending_received']>0
      ? '<span class="status-pill status-partial"><i class="fa-solid fa-clock-rotate-left"></i>Parcial</span>'
      : '<span class="status-pill status-overdue"><i class="fa-solid fa-triangle-exclamation"></i>Vencido</span>');
 $signals=[];
 if($r['mine_period_at'])$signals[]='<span class="signal-badge signal-mine"><i class="fa-solid fa-user-check"></i>Meu atendimento</span>';
 elseif($r['attended_period_at'])$signals[]='<span class="signal-badge signal-attended"><i class="fa-solid fa-check"></i>Atendido</span>';
 if($r['agreement_period_at'])$signals[]='<span class="signal-badge signal-agreement"><i class="fa-solid fa-handshake"></i>Acordo</span>';
 if($r['promise_period_at'])$signals[]='<span class="signal-badge signal-promise"><i class="fa-regular fa-calendar-check"></i>Promessa</span>';
 if(!$signals)$signals[]='<span class="signal-badge signal-muted">Não trabalhado</span>';
 $last=$r['last_contact']
   ? '<strong>'.date('d/m/Y H:i',strtotime($r['last_contact'])).'</strong><small class="table-sub">'.e($r['last_user_name']?:'').' • '.e(str_replace('_',' ',(string)$r['last_result'])).'</small>'
   : '<span class="text-secondary">—</span>';
 $person='<div class="table-person"><span class="avatar avatar-sm">'.e(mb_strtoupper(mb_substr((string)$r['name'],0,1))).'</span><div><strong>'.e($r['name']).'</strong><small>'.e($r['omie_code']).'</small></div></div>';
 $amount='<strong class="'.($settled?'text-success':'text-danger').'">'.money($r['effective_debt']).'</strong>';
 if((float)$r['pending_received']>0)$amount.='<small class="table-sub">'.money($r['pending_received']).' recebido localmente</small>';
 $data[]=[
   $person,
   e($r['seller_name']??'—'),
   '<span class="badge-muted">'.e($r['uf']?:'—').'</span>',
   $r['last_purchase_at']?date('d/m/Y',strtotime($r['last_purchase_at'])):'—',
   $r['days_without_purchase']!==null?(string)(int)$r['days_without_purchase']:'—',
   $financial,
   '<div class="text-end">'.$amount.'</div>',
   '<div class="signal-stack">'.implode('',$signals).'</div>',
   $last,
   '<a class="btn btn-dark btn-sm" href="'.APP_URL.'/cobranca-cliente.php?id='.(int)$r['id'].'"><i class="fa-solid fa-headset"></i>Atender</a>'
 ];
}

echo json_encode(['draw'=>$draw,'recordsTotal'=>$recordsTotal,'recordsFiltered'=>$recordsFiltered,'data'=>$data],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(500);
    error_log('[CRM collection-table] '.$e->getMessage());
    echo json_encode([
        'draw'=>$draw,
        'recordsTotal'=>0,
        'recordsFiltered'=>0,
        'data'=>[],
        'error'=>'Falha ao carregar a carteira de cobrança. Verifique se o upgrade V6.5 foi executado.'
    ],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
}
