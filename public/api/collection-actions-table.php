<?php
declare(strict_types=1);

require dirname(__DIR__,2).'/app/bootstrap.php';
require APP_ROOT.'/app/Support/helpers.php';

use Tecnodata\CRM\Core\Auth;
use Tecnodata\CRM\Core\DB;

Auth::requireLogin();
header('Content-Type: application/json; charset=utf-8');
if(!Auth::can('collector','supervisor','admin')){http_response_code(403);echo json_encode(['data'=>[],'recordsTotal'=>0,'recordsFiltered'=>0]);exit;}

$u=Auth::user();
$draw=max(0,(int)($_GET['draw']??0));$start=max(0,(int)($_GET['start']??0));
$length=max(10,min(100,(int)($_GET['length']??25)));
$search=trim((string)($_GET['search']['value']??''));
$month=(string)($_GET['month']??date('Y-m'));if(!preg_match('/^\d{4}-\d{2}$/',$month))$month=date('Y-m');
$from=$month.'-01';$to=date('Y-m-d',strtotime($from.' +1 month'));
$result=(string)($_GET['result']??'');
$allowed=['falou','nao_atendeu','promessa','acordo','pagamento','sem_previsao'];
if(!in_array($result,$allowed,true))$result='';

$userId=(int)($_GET['user_id']??0);
$collectorOnly=($u['role']??'')==='collector';

$where=['ca.deleted_at IS NULL','ca.created_at>=?','ca.created_at<?'];
$params=[$from,$to];
if($collectorOnly){
 $where[]='(ca.user_id=? OR ca.assigned_user_id=?)';
 $params[]=(int)$u['id'];$params[]=(int)$u['id'];
}elseif($userId>0){
 $where[]='COALESCE(ca.assigned_user_id,ca.user_id)=?';
 $params[]=$userId;
}
if($result!==''){$where[]='ca.result=?';$params[]=$result;}

$base=" FROM collection_actions ca
 JOIN clients c ON c.id=ca.client_id
 JOIN users author ON author.id=ca.user_id
 LEFT JOIN users assigned ON assigned.id=ca.assigned_user_id
 LEFT JOIN client_metrics m ON m.client_id=c.id
 LEFT JOIN sellers s ON s.omie_code=m.seller_omie_code";
$custom=' WHERE '.implode(' AND ',$where);

try{
 $total=(int)(DB::fetch("SELECT COUNT(*) n $base $custom",$params)['n']??0);
 $w=$where;$p=$params;
 if($search!==''){
  $w[]='(c.name LIKE ? OR c.omie_code LIKE ? OR author.name LIKE ? OR assigned.name LIKE ? OR ca.notes LIKE ? OR s.name LIKE ?)';
  $like='%'.$search.'%';array_push($p,$like,$like,$like,$like,$like,$like);
 }
 $final=' WHERE '.implode(' AND ',$w);
 $filtered=(int)(DB::fetch("SELECT COUNT(*) n $base $final",$p)['n']??0);
 $orderMap=[0=>'ca.created_at',1=>'c.name',2=>'ca.result',3=>'author.name',4=>'assigned.name',5=>'ca.promised_for',6=>'ca.amount',7=>'ca.created_at'];
 $idx=(int)($_GET['order'][0]['column']??0);$dir=strtolower((string)($_GET['order'][0]['dir']??'desc'))==='asc'?'ASC':'DESC';
 $order=$orderMap[$idx]??'ca.created_at';
 $rows=DB::all("SELECT ca.*,c.name client_name,c.omie_code,s.name seller_name,
                       author.name author_name,COALESCE(assigned.name,author.name) assigned_name
                $base $final ORDER BY $order $dir,ca.id DESC LIMIT $length OFFSET $start",$p);

 $labels=['falou'=>'Falou com o cliente','nao_atendeu'=>'Não atendeu','sem_previsao'=>'Sem previsão','promessa'=>'Promessa de pagamento','acordo'=>'Acordo realizado','pagamento'=>'Pagamento recebido'];
 $badges=['acordo'=>'signal-agreement','promessa'=>'signal-promise','pagamento'=>'status-paid'];
 $data=[];
 foreach($rows as $r){
  $badge=$badges[$r['result']]??'badge-muted';
  $resultHtml='<span class="signal-badge '.$badge.'">'.e($labels[$r['result']]??$r['result']).'</span>';
  $client='<strong>'.e($r['client_name']).'</strong><small class="table-sub">'.e($r['omie_code']).($r['seller_name']?' • '.e($r['seller_name']):'').'</small>';
  $notes=trim((string)$r['notes']);if(mb_strlen($notes)>120)$notes=mb_substr($notes,0,117).'…';
  $can=(int)$r['user_id']===(int)$u['id']||(int)($r['assigned_user_id']??0)===(int)$u['id']||Auth::can('supervisor','admin');
  $action='<div class="row-actions"><a class="btn btn-outline-secondary btn-sm" href="'.APP_URL.'/cobranca-atendimento.php?id='.(int)$r['id'].'"><i class="fa-regular fa-eye"></i>Ver</a>';
  if($can)$action.='<a class="btn btn-outline-secondary btn-sm" href="'.APP_URL.'/atendimento-editar.php?kind=collection&id='.(int)$r['id'].'"><i class="fa-solid fa-pen"></i>Editar</a>';
  $action.='</div>';
  $data[]=[
   date('d/m/Y H:i',strtotime((string)$r['created_at'])),$client,$resultHtml,
   '<strong>'.e($r['author_name']).'</strong><small class="table-sub">'.date('d/m/Y H:i',strtotime((string)$r['created_at'])).'</small>',
   '<strong>'.e($r['assigned_name']).'</strong>'.(!empty($r['assigned_at'])?'<small class="table-sub">desde '.date('d/m/Y H:i',strtotime((string)$r['assigned_at'])).'</small>':''),
   $r['promised_for']?date('d/m/Y',strtotime((string)$r['promised_for'])):'—',
   '<div class="text-end">'.((float)$r['amount']>0?'<strong>'.money($r['amount']).'</strong>':'—').'</div>',
   $notes!==''?e($notes):'<span class="text-secondary">—</span>',$action
  ];
 }
 echo json_encode(['draw'=>$draw,'recordsTotal'=>$total,'recordsFiltered'=>$filtered,'data'=>$data],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
}catch(Throwable $e){
 http_response_code(500);error_log('[CRM collection-actions-table] '.$e->getMessage());
 echo json_encode(['draw'=>$draw,'recordsTotal'=>0,'recordsFiltered'=>0,'data'=>[],'error'=>'Falha ao carregar os atendimentos. '.$e->getMessage()],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
}
