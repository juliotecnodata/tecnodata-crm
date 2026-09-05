<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/app/bootstrap.php';
require APP_ROOT.'/app/Support/helpers.php';

use Tecnodata\CRM\Core\Auth;
use Tecnodata\CRM\Core\DB;

Auth::requireLogin();
header('Content-Type: application/json; charset=utf-8');
if(!Auth::can('supervisor','admin')){http_response_code(403);echo json_encode(['data'=>[],'recordsTotal'=>0,'recordsFiltered'=>0]);exit;}

$draw=max(0,(int)($_GET['draw']??0));
$start=max(0,(int)($_GET['start']??0));
$length=max(10,min(100,(int)($_GET['length']??25)));
$search=trim((string)($_GET['search']['value']??''));
$month=(string)($_GET['month']??date('Y-m')); if(!preg_match('/^\d{4}-\d{2}$/',$month))$month=date('Y-m');
$uf=strtoupper(trim((string)($_GET['uf']??'')));
$tag=trim((string)($_GET['tag']??''));
$seller=trim((string)($_GET['seller']??''));
$status=trim((string)($_GET['status']??''));
$finance=trim((string)($_GET['finance']??''));
$source=trim((string)($_GET['source']??''));

$effectiveSeller="CASE WHEN pa.id IS NOT NULL THEN pa.seller_omie_code ELSE m.seller_omie_code END";
$base=" FROM clients c
 LEFT JOIN client_metrics m ON m.client_id=c.id
 LEFT JOIN client_portfolio_assignments pa ON pa.client_id=c.id AND pa.month_ref=?
 LEFT JOIN sellers sm ON sm.omie_code=$effectiveSeller
 LEFT JOIN sellers so ON so.omie_code=m.seller_omie_code";
$where=['c.active=1']; $params=[$month];

if($uf!==''){$where[]='c.uf=?';$params[]=$uf;}
if($tag!==''){$where[]='EXISTS(SELECT 1 FROM client_tags ct WHERE ct.client_id=c.id AND ct.tag=?)';$params[]=$tag;}
if($seller==='__unassigned__')$where[]="$effectiveSeller IS NULL";
elseif($seller==='__assigned__')$where[]="$effectiveSeller IS NOT NULL";
elseif($seller!==''){$where[]="$effectiveSeller=?";$params[]=$seller;}
if(in_array($status,['normal','attention','reactivate'],true)){$where[]='m.commercial_status=?';$params[]=$status;}
if($finance==='overdue')$where[]='COALESCE(m.overdue_amount,0)>0';
elseif($finance==='open')$where[]='COALESCE(m.open_amount,0)>0 AND COALESCE(m.overdue_amount,0)<=0';
elseif($finance==='clear')$where[]='COALESCE(m.open_amount,0)<=0 AND COALESCE(m.overdue_amount,0)<=0';
if($source==='month')$where[]='pa.id IS NOT NULL';
elseif($source==='omie')$where[]='pa.id IS NULL';

$fixed=' WHERE '.implode(' AND ',$where);

try{
 $total=(int)(DB::fetch("SELECT COUNT(*) n FROM clients c WHERE c.active=1")['n']??0);
 $w=$where;$p=$params;
 if($search!==''){
   $w[]='(c.name LIKE ? OR c.legal_name LIKE ? OR c.document LIKE ? OR c.city LIKE ? OR c.omie_code LIKE ? OR sm.name LIKE ? OR so.name LIKE ? OR EXISTS(SELECT 1 FROM client_tags cts WHERE cts.client_id=c.id AND cts.tag LIKE ?))';
   $like='%'.$search.'%'; array_push($p,$like,$like,$like,$like,$like,$like,$like,$like);
 }
 $final=' WHERE '.implode(' AND ',$w);
 $filtered=(int)(DB::fetch("SELECT COUNT(*) n $base $final",$p)['n']??0);

 $select="SELECT c.id,c.name,c.legal_name,c.omie_code,c.uf,c.city,c.document,c.phone,c.email,
 m.last_purchase_at,m.days_without_purchase,m.orders_12m,m.revenue_12m,m.avg_ticket_12m,m.overdue_amount,m.open_amount,m.max_overdue_days,m.commercial_status,
 pa.id assignment_id,pa.seller_omie_code month_seller_code,sm.name effective_seller_name,so.name omie_seller_name,
 (SELECT GROUP_CONCAT(ct.tag ORDER BY ct.tag SEPARATOR ' • ') FROM client_tags ct WHERE ct.client_id=c.id) tags";
 $orderMap=[1=>'c.name',2=>'c.uf',4=>'sm.name',5=>'m.last_purchase_at',6=>'m.days_without_purchase',7=>'m.revenue_12m',8=>'m.overdue_amount',9=>'m.commercial_status'];
 $idx=(int)($_GET['order'][0]['column']??1);
 $dir=strtolower((string)($_GET['order'][0]['dir']??'asc'))==='desc'?'DESC':'ASC';
 $order=$orderMap[$idx]??'c.name';

 $rows=DB::all("$select $base $final ORDER BY $order $dir,c.name ASC LIMIT $length OFFSET $start",$p);
 $labels=['normal'=>'Normal','attention'=>'Atenção','reactivate'=>'Reativar'];
 $data=[];
 foreach($rows as $r){
   $client='<strong>'.e($r['name']).'</strong><small class="table-sub">'.e($r['document']?:$r['omie_code']).'</small>';
   if(!empty($r['phone'])||!empty($r['email']))$client.='<small class="table-sub">'.e($r['phone']?:$r['email']).'</small>';
   $place='<strong>'.e($r['uf']?:'—').'</strong><small class="table-sub">'.e($r['city']?:'—').'</small>';
   $sellerName=$r['effective_seller_name']?:'Sem vendedor';
   $portfolio='<strong>'.e($sellerName).'</strong><small class="table-sub">'.($r['assignment_id']?($r['month_seller_code']===null?'Sem vendedor no mês':'Carteira definida no mês'):($r['effective_seller_name']?'Vendedor-base Omie':'Não distribuído')).'</small>';
   $financeHtml=(float)$r['overdue_amount']>0
     ? '<span class="status-pill status-overdue">'.money($r['overdue_amount']).' vencido</span><small class="table-sub">'.(int)$r['max_overdue_days'].' dia(s) de atraso</small>'
     : ((float)$r['open_amount']>0
       ? '<span class="status-pill status-partial">'.money($r['open_amount']).' em aberto</span>'
       : '<span class="status-pill status-paid">Em dia</span>');
   $statusClass=$r['commercial_status']==='reactivate'?'status-overdue':($r['commercial_status']==='attention'?'status-partial':'status-paid');
   $checkbox='<input class="form-check-input portfolio-row-check" type="checkbox" value="'.(int)$r['id'].'" aria-label="Selecionar '.e($r['name']).'">';
   $tagsHtml=trim((string)($r['tags']??''))!==''?'<div class="tag-stack">'.implode('',array_map(fn($v)=>'<span class="client-tag">'.e(trim($v)).'</span>',explode(' • ',(string)$r['tags']))).'</div>':'<span class="text-secondary">—</span>';
   $revenue='<div class="text-end"><strong>'.money((float)$r['revenue_12m']).'</strong><small class="table-sub">'.(int)$r['orders_12m'].' pedido(s) • ticket '.money((float)$r['avg_ticket_12m']).'</small></div>';
   $data[]=[
     $checkbox,$client,$place,$tagsHtml,$portfolio,
     $r['last_purchase_at']?date('d/m/Y',strtotime((string)$r['last_purchase_at'])):'—',
     $r['days_without_purchase']!==null?(string)(int)$r['days_without_purchase']:'—',
     $revenue,
     $financeHtml,
     '<span class="status-pill '.$statusClass.'">'.e($labels[$r['commercial_status']]??'—').'</span>',
     '<div class="d-flex gap-1 justify-content-end"><a class="btn btn-outline-secondary btn-sm" href="'.APP_URL.'/cliente.php?id='.(int)$r['id'].'" title="Ver"><i class="fa-regular fa-eye"></i></a><a class="btn btn-outline-secondary btn-sm" href="'.APP_URL.'/cliente-cadastro.php?id='.(int)$r['id'].'" title="Editar"><i class="fa-regular fa-pen-to-square"></i></a></div>'
   ];
 }
 echo json_encode(['draw'=>$draw,'recordsTotal'=>$total,'recordsFiltered'=>$filtered,'data'=>$data],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
}catch(Throwable $e){
 http_response_code(500); error_log('[CRM clients-table] '.$e->getMessage());
 echo json_encode(['draw'=>$draw,'recordsTotal'=>0,'recordsFiltered'=>0,'data'=>[],'error'=>'Falha ao carregar clientes. '.$e->getMessage()],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
}