<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/app/bootstrap.php';

use Tecnodata\CRM\Core\Auth;
use Tecnodata\CRM\Core\DB;
use Tecnodata\CRM\Core\Security;

Auth::requireLogin();
header('Content-Type: application/json; charset=utf-8');
if(!Auth::can('supervisor','admin')){http_response_code(403);echo json_encode(['ok'=>false,'error'=>'Sem acesso']);exit;}

$body=json_decode(file_get_contents('php://input'),true)?:[];
if(!Security::verifyCsrf($body['_token']??null)){http_response_code(419);echo json_encode(['ok'=>false,'error'=>'Sessão expirada']);exit;}

$month=(string)($body['month']??date('Y-m'));
if(!preg_match('/^\d{4}-\d{2}$/',$month)){http_response_code(422);echo json_encode(['ok'=>false,'error'=>'Mês inválido']);exit;}

$mode=(string)($body['mode']??'selected');
$seller=(string)($body['seller']??'');
if($seller===''){http_response_code(422);echo json_encode(['ok'=>false,'error'=>'Escolha o vendedor ou a ação desejada.']);exit;}

$sellerCode=null;
$removeOverride=$seller==='__omie__';
if(!$removeOverride && $seller!=='__unassigned__'){
 $valid=DB::fetch("SELECT omie_code FROM sellers WHERE omie_code=? AND active=1 AND is_virtual=0",[$seller]);
 if(!$valid){http_response_code(422);echo json_encode(['ok'=>false,'error'=>'Vendedor inválido ou inativo.']);exit;}
 $sellerCode=(string)$valid['omie_code'];
}

try{
 $ids=[];
 if($mode==='selected'){
   foreach(($body['ids']??[]) as $id){$id=(int)$id;if($id>0)$ids[$id]=$id;}
   $ids=array_values($ids);
 }else{
   $uf=strtoupper(trim((string)($body['filters']['uf']??'')));
   $tag=trim((string)($body['filters']['tag']??''));
   $sellerFilter=trim((string)($body['filters']['seller']??''));
   $status=trim((string)($body['filters']['status']??''));
   $finance=trim((string)($body['filters']['finance']??''));
   $source=trim((string)($body['filters']['source']??''));
   $search=trim((string)($body['filters']['search']??''));
   $effective="CASE WHEN pa.id IS NOT NULL THEN pa.seller_omie_code ELSE m.seller_omie_code END";
   $sql="SELECT c.id FROM clients c
    LEFT JOIN client_metrics m ON m.client_id=c.id
    LEFT JOIN client_portfolio_assignments pa ON pa.client_id=c.id AND pa.month_ref=?
    LEFT JOIN sellers sm ON sm.omie_code=$effective
    LEFT JOIN sellers so ON so.omie_code=m.seller_omie_code
    WHERE c.active=1";
   $p=[$month];
   if($uf!==''){$sql.=' AND c.uf=?';$p[]=$uf;}
   if($tag!==''){$sql.=' AND EXISTS(SELECT 1 FROM client_tags ct WHERE ct.client_id=c.id AND ct.tag=?)';$p[]=$tag;}
   if($sellerFilter==='__unassigned__')$sql.=" AND $effective IS NULL";
   elseif($sellerFilter==='__assigned__')$sql.=" AND $effective IS NOT NULL";
   elseif($sellerFilter!==''){$sql.=" AND $effective=?";$p[]=$sellerFilter;}
   if(in_array($status,['normal','attention','reactivate'],true)){$sql.=' AND m.commercial_status=?';$p[]=$status;}
   if($finance==='overdue')$sql.=' AND COALESCE(m.overdue_amount,0)>0';
   elseif($finance==='clear')$sql.=' AND COALESCE(m.overdue_amount,0)<=0';
   if($source==='month')$sql.=' AND pa.id IS NOT NULL';
   elseif($source==='omie')$sql.=' AND pa.id IS NULL';
   if($search!==''){
     $sql.=' AND (c.name LIKE ? OR c.legal_name LIKE ? OR c.document LIKE ? OR c.city LIKE ? OR c.omie_code LIKE ? OR sm.name LIKE ? OR so.name LIKE ?)';
     $like='%'.$search.'%';array_push($p,$like,$like,$like,$like,$like,$like,$like);
   }
   $ids=array_map(fn($r)=>(int)$r['id'],DB::all($sql,$p));
 }

 if(!$ids){echo json_encode(['ok'=>true,'updated'=>0,'message'=>'Nenhum cliente corresponde à seleção.']);exit;}

 $pdo=DB::conn();$pdo->beginTransaction();
 try{
   if($removeOverride){
     $chunks=array_chunk($ids,500);
     foreach($chunks as $chunk){
       $placeholders=implode(',',array_fill(0,count($chunk),'?'));
       DB::exec("DELETE FROM client_portfolio_assignments WHERE month_ref=? AND client_id IN ($placeholders)",array_merge([$month],$chunk));
     }
   }else{
     $stmt=$pdo->prepare("INSERT INTO client_portfolio_assignments(month_ref,client_id,seller_omie_code,created_by,updated_by,created_at,updated_at)
       VALUES(?,?,?,?,?,NOW(),NOW())
       ON DUPLICATE KEY UPDATE seller_omie_code=VALUES(seller_omie_code),updated_by=VALUES(updated_by),updated_at=NOW()");
     foreach($ids as $id)$stmt->execute([$month,$id,$sellerCode,Auth::id(),Auth::id()]);
   }
   $pdo->commit();
 }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}

 echo json_encode(['ok'=>true,'updated'=>count($ids),'message'=>count($ids).' cliente(s) atualizado(s) para '.$month.'.'],JSON_UNESCAPED_UNICODE);
}catch(Throwable $e){
 http_response_code(500);error_log('[CRM portfolio-assign] '.$e->getMessage());
 echo json_encode(['ok'=>false,'error'=>'Falha ao distribuir carteira. '.$e->getMessage()],JSON_UNESCAPED_UNICODE);
}