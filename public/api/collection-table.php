<?php
declare(strict_types=1);

require dirname(__DIR__,2).'/app/bootstrap.php';
require APP_ROOT.'/app/Support/helpers.php';

use Tecnodata\CRM\Core\Auth;
use Tecnodata\CRM\Core\DB;

Auth::requireLogin();
header('Content-Type: application/json; charset=utf-8');

if(!Auth::can('collector','supervisor','admin')){
    http_response_code(403);
    echo json_encode(['draw'=>(int)($_GET['draw']??0),'recordsTotal'=>0,'recordsFiltered'=>0,'data'=>[],'error'=>'Sem acesso.']);
    exit;
}

$u=Auth::user();
$draw=max(0,(int)($_GET['draw']??0));
$start=max(0,(int)($_GET['start']??0));
$length=(int)($_GET['length']??25);
$length=max(10,min(100,$length));
$search=trim((string)($_GET['search']['value']??''));

$viewRaw=(string)($_GET['view']??'open');
$signalRaw=(string)($_GET['signal']??'all');
$view=in_array($viewRaw,['open','all','settled'],true)?$viewRaw:'open';
$signal=in_array($signalRaw,['all','mine','attended','agreement','promise','unattended'],true)?$signalRaw:'all';

$month=(string)($_GET['month']??date('Y-m'));
if(!preg_match('/^\d{4}-\d{2}$/',$month))$month=date('Y-m');
$monthStart=$month.'-01';
$monthNext=date('Y-m-d',strtotime($monthStart.' +1 month'));
$seller=trim((string)($_GET['seller']??''));
$uf=strtoupper(trim((string)($_GET['uf']??'')));
$userId=(int)$u['id'];

try{
    $pdo=DB::conn();
    $dbName=(string)$pdo->query('SELECT DATABASE()')->fetchColumn();
    $hasFinSeller=(bool)DB::fetch("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=? AND TABLE_NAME='financial_movements' AND COLUMN_NAME='seller_omie_code' LIMIT 1",[$dbName]);
    $finSellerExpr=$hasFinSeller?"fm.seller_omie_code":"NULL";
    $qStart=$pdo->quote($monthStart);
    $qNext=$pdo->quote($monthNext);

    $debtJoin="LEFT JOIN (
        SELECT fm.client_omie_code,
               SUM(fm.amount) omie_debt,
               COUNT(DISTINCT NULLIF($finSellerExpr,'')) debt_seller_count,
               MAX(NULLIF($finSellerExpr,'')) debt_seller_code,
               COUNT(DISTINCT fm.account_omie_code) debt_account_count,
               GROUP_CONCAT(DISTINCT COALESCE(NULLIF(fa.name,''),fm.account_omie_code) ORDER BY COALESCE(NULLIF(fa.name,''),fm.account_omie_code) SEPARATOR ' • ') debt_account_names
        FROM financial_movements fm
        INNER JOIN financial_accounts fa
          ON fa.omie_code=fm.account_omie_code AND fa.selected=1 AND fa.active=1
        WHERE fm.status IN('ATRASADO','PAGTO_PARCIAL')
        GROUP BY fm.client_omie_code
    ) d ON d.client_omie_code=c.omie_code";

    $from=" FROM clients c
        LEFT JOIN client_metrics m ON m.client_id=c.id
        $debtJoin
        LEFT JOIN sellers s ON s.omie_code=d.debt_seller_code
        LEFT JOIN collection_client_adjustments adj ON adj.client_id=c.id
        LEFT JOIN collection_actions la
          ON la.id=(SELECT MAX(lx.id) FROM collection_actions lx WHERE lx.client_id=c.id AND lx.deleted_at IS NULL)
        LEFT JOIN users lu ON lu.id=la.user_id";

    $effective="GREATEST(0,COALESCE(d.omie_debt,0)-COALESCE(adj.pending_received,0))";

    $where=[];
    $params=[];
    $where[]="(COALESCE(d.omie_debt,0)>0 OR la.created_at>=DATE_SUB(NOW(),INTERVAL 180 DAY))";

    if($view==='open')$where[]="$effective>0.009";
    elseif($view==='settled')$where[]="$effective<=0.009";

    $periodExists="EXISTS(SELECT 1 FROM collection_actions ca WHERE ca.client_id=c.id AND ca.deleted_at IS NULL AND ca.created_at>=$qStart AND ca.created_at<$qNext)";
    if($signal==='mine'){
        $where[]="EXISTS(SELECT 1 FROM collection_actions ca WHERE ca.client_id=c.id AND ca.user_id=$userId AND ca.deleted_at IS NULL AND ca.created_at>=$qStart AND ca.created_at<$qNext)";
    }elseif($signal==='attended'){
        $where[]=$periodExists;
    }elseif($signal==='agreement'){
        $where[]="EXISTS(SELECT 1 FROM collection_actions ca WHERE ca.client_id=c.id AND ca.deleted_at IS NULL AND ca.result='acordo' AND ca.created_at>=$qStart AND ca.created_at<$qNext)";
    }elseif($signal==='promise'){
        $where[]="EXISTS(SELECT 1 FROM collection_actions ca WHERE ca.client_id=c.id AND ca.deleted_at IS NULL AND ca.result='promessa' AND ca.created_at>=$qStart AND ca.created_at<$qNext)";
    }elseif($signal==='unattended'){
        $where[]="NOT $periodExists";
    }

    if($seller!==''){
        if(!$hasFinSeller){
            $where[]="1=0";
        }else{
            $where[]="EXISTS(
                SELECT 1 FROM financial_movements fs
                INNER JOIN financial_accounts fas ON fas.omie_code=fs.account_omie_code AND fas.selected=1 AND fas.active=1
                WHERE fs.client_omie_code=c.omie_code
                  AND fs.status IN('ATRASADO','PAGTO_PARCIAL')
                  AND fs.seller_omie_code=?
            )";
            $params[]=$seller;
        }
    }
    if($uf!==''){$where[]='c.uf=?';$params[]=$uf;}

    $customWhere=' WHERE '.implode(' AND ',$where);
    $total=(int)(DB::fetch("SELECT COUNT(*) n $from $customWhere",$params)['n']??0);

    $filteredWhere=$where;
    $filteredParams=$params;
    if($search!==''){
        $filteredWhere[]='(c.name LIKE ? OR c.omie_code LIKE ? OR c.document LIKE ? OR s.name LIKE ? OR lu.name LIKE ? OR d.debt_account_names LIKE ?)';
        $like='%'.$search.'%';
        array_push($filteredParams,$like,$like,$like,$like,$like,$like);
    }
    $finalWhere=' WHERE '.implode(' AND ',$filteredWhere);
    $filtered=(int)(DB::fetch("SELECT COUNT(*) n $from $finalWhere",$filteredParams)['n']??0);

    $select="SELECT c.id,c.omie_code,c.name,c.uf,c.document,
        m.last_purchase_at,m.days_without_purchase,
        d.debt_seller_count,d.debt_seller_code,s.name debt_seller_name,
        d.debt_account_count,d.debt_account_names,
        COALESCE(d.omie_debt,0) omie_debt,COALESCE(adj.pending_received,0) pending_received,
        $effective effective_debt,
        la.id last_action_id,la.created_at last_contact,la.result last_result,lu.name last_user_name,
        EXISTS(SELECT 1 FROM collection_actions ca WHERE ca.client_id=c.id AND ca.deleted_at IS NULL AND ca.created_at>=$qStart AND ca.created_at<$qNext) attended_period,
        EXISTS(SELECT 1 FROM collection_actions ca WHERE ca.client_id=c.id AND ca.user_id=$userId AND ca.deleted_at IS NULL AND ca.created_at>=$qStart AND ca.created_at<$qNext) mine_period,
        EXISTS(SELECT 1 FROM collection_actions ca WHERE ca.client_id=c.id AND ca.deleted_at IS NULL AND ca.result='acordo' AND ca.created_at>=$qStart AND ca.created_at<$qNext) agreement_period,
        EXISTS(SELECT 1 FROM collection_actions ca WHERE ca.client_id=c.id AND ca.deleted_at IS NULL AND ca.result='promessa' AND ca.created_at>=$qStart AND ca.created_at<$qNext) promise_period";

    $orderMap=[
        0=>'c.name',1=>'s.name',2=>'d.debt_account_names',3=>'c.uf',4=>'m.last_purchase_at',5=>'m.days_without_purchase',
        6=>'effective_debt',7=>'effective_debt',8=>'la.created_at',9=>'la.created_at'
    ];
    $orderIdx=(int)($_GET['order'][0]['column']??9);
    $orderDir=strtolower((string)($_GET['order'][0]['dir']??'desc'))==='asc'?'ASC':'DESC';
    $orderBy=$orderMap[$orderIdx]??'la.created_at';

    $rows=DB::all("$select $from $finalWhere ORDER BY $orderBy $orderDir,c.name ASC LIMIT $length OFFSET $start",$filteredParams);

    $resultLabels=[
        'falou'=>'Falou com o cliente','nao_atendeu'=>'Não atendeu','sem_previsao'=>'Sem previsão',
        'promessa'=>'Promessa de pagamento','acordo'=>'Acordo realizado','pagamento'=>'Pagamento recebido'
    ];
    $data=[];
    foreach($rows as $r){
        $settled=(float)$r['effective_debt']<=0.009;
        $financial=$settled
            ? '<span class="status-pill status-paid"><i class="fa-solid fa-circle-check"></i>Quitado</span>'
            : ((float)$r['pending_received']>0
                ? '<span class="status-pill status-partial"><i class="fa-solid fa-clock-rotate-left"></i>Parcial</span>'
                : '<span class="status-pill status-overdue"><i class="fa-solid fa-triangle-exclamation"></i>Vencido</span>');

        $signals=[];
        if((int)$r['mine_period']===1)$signals[]='<span class="signal-badge signal-mine"><i class="fa-solid fa-user-check"></i>Meu atendimento</span>';
        elseif((int)$r['attended_period']===1)$signals[]='<span class="signal-badge signal-attended"><i class="fa-solid fa-check"></i>Atendido</span>';
        if((int)$r['agreement_period']===1)$signals[]='<span class="signal-badge signal-agreement"><i class="fa-solid fa-handshake"></i>Acordo</span>';
        if((int)$r['promise_period']===1)$signals[]='<span class="signal-badge signal-promise"><i class="fa-regular fa-calendar-check"></i>Promessa</span>';
        if(!$signals)$signals[]='<span class="signal-badge signal-muted">Não trabalhado</span>';

        $last='<span class="text-secondary">—</span>';
        if($r['last_contact']){
            $last='<strong>'.date('d/m/Y H:i',strtotime((string)$r['last_contact'])).'</strong>'
                .'<small class="table-sub">'.e($r['last_user_name']?:'').' • '.e($resultLabels[$r['last_result']]??(string)$r['last_result']).'</small>';
        }

        $person='<div class="table-person"><span class="avatar avatar-sm">'.e(mb_strtoupper(mb_substr((string)$r['name'],0,1))).'</span><div><strong>'.e($r['name']).'</strong><small>'.e($r['omie_code']).'</small></div></div>';
        $amount='<strong class="'.($settled?'text-success':'text-danger').'">'.money($r['effective_debt']).'</strong>';
        if((float)$r['pending_received']>0)$amount.='<small class="table-sub">'.money($r['pending_received']).' recebido localmente</small>';

        $data[]=[
            $person,
            ((int)($r['debt_seller_count']??0)>1
                ? '<span class="status-pill status-partial">Vários vendedores</span>'
                : ((int)($r['debt_seller_count']??0)===1
                    ? e(trim(explode(' ',trim((string)($r['debt_seller_name']?:$r['debt_seller_code'])))[0]??''))
                    : '<span class="text-secondary">Não informado na dívida</span>')),
            (!empty($r['debt_account_names'])
                ? '<span class="debt-account-name">'.e($r['debt_account_names']).'</span>'
                : '<span class="text-secondary">—</span>'),
            '<span class="badge-muted">'.e($r['uf']?:'—').'</span>',
            $r['last_purchase_at']?date('d/m/Y',strtotime((string)$r['last_purchase_at'])):'—',
            $r['days_without_purchase']!==null?(string)(int)$r['days_without_purchase']:'—',
            $financial,
            '<div class="text-end">'.$amount.'</div>',
            '<div class="signal-stack">'.implode('',$signals).'</div>',
            $last,
            '<a class="btn btn-dark btn-sm" href="'.APP_URL.'/cobranca-cliente.php?id='.(int)$r['id'].'"><i class="fa-solid fa-headset"></i>Atender</a>'
        ];
    }

    echo json_encode(['draw'=>$draw,'recordsTotal'=>$total,'recordsFiltered'=>$filtered,'data'=>$data],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
}catch(Throwable $e){
    http_response_code(500);
    error_log('[CRM collection-table] '.$e->getMessage());
    echo json_encode([
        'draw'=>$draw,'recordsTotal'=>0,'recordsFiltered'=>0,'data'=>[],
        'error'=>'Não foi possível consultar a cobrança. '.$e->getMessage()
    ],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
}
