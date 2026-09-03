<?php
require dirname(__DIR__).'/app/bootstrap.php';
require APP_ROOT.'/app/Support/helpers.php';
use Tecnodata\CRM\Core\Auth;
use Tecnodata\CRM\Core\DB;
use Tecnodata\CRM\Services\CollectionService;
Auth::requireLogin();
$u=Auth::user();
if(!Auth::can('collector','supervisor','admin')){http_response_code(403);exit('Sem acesso');}
$view=$_GET['view']??'open';$signal=$_GET['signal']??'all';$monthStart=date('Y-m-01');$monthNext=date('Y-m-01',strtotime('+1 month'));
$sql="SELECT c.id,c.omie_code,c.name,c.uf,m.last_purchase_at,m.days_without_purchase,m.seller_omie_code,s.name seller_name,
 (SELECT MAX(ca.created_at) FROM collection_actions ca WHERE ca.client_id=c.id AND ca.deleted_at IS NULL) last_contact,
 (SELECT MAX(ca.created_at) FROM collection_actions ca WHERE ca.client_id=c.id AND ca.deleted_at IS NULL AND ca.created_at>='$monthStart' AND ca.created_at<'$monthNext') attended_month_at,
 (SELECT MAX(ca.created_at) FROM collection_actions ca WHERE ca.client_id=c.id AND ca.deleted_at IS NULL AND ca.result='acordo' AND ca.created_at>='$monthStart' AND ca.created_at<'$monthNext') agreement_month_at
 FROM clients c
 LEFT JOIN client_metrics m ON m.client_id=c.id
 LEFT JOIN sellers s ON s.omie_code=m.seller_omie_code
 WHERE (EXISTS(SELECT 1 FROM financial_movements fm INNER JOIN financial_accounts fa ON fa.omie_code=fm.account_omie_code AND fa.selected=1 AND fa.active=1 WHERE fm.client_omie_code=c.omie_code AND UPPER(fm.status) IN('ATRASADO','PAGTO_PARCIAL')) OR EXISTS(SELECT 1 FROM collection_actions ca2 WHERE ca2.client_id=c.id AND ca2.deleted_at IS NULL AND ca2.created_at>=DATE_SUB(NOW(),INTERVAL 180 DAY)))
 ORDER BY c.name";
$base=DB::all($sql);$rows=[];$summary=['open'=>0,'settled'=>0,'amount'=>0,'worked_month'=>0];
$month=date('Y-m');
foreach($base as $r){$d=CollectionService::debtState((int)$r['id'],(string)$r['omie_code']);$r+=$d;$isSettled=$d['effective_debt']<=0.009;if($isSettled)$summary['settled']++;else{$summary['open']++;$summary['amount']+=$d['effective_debt'];}
 if($view==='open'&&$isSettled)continue;if($view==='settled'&&!$isSettled)continue;
 if($signal==='attended'&&!$r['attended_month_at'])continue;
 if($signal==='agreement'&&!$r['agreement_month_at'])continue;
 if($signal==='unattended'&&$r['attended_month_at'])continue;
 $rows[]=$r;}
$collectorGoal=($u['role']??'')==='collector'?CollectionService::monthForUser((int)$u['id'],$month):null;
$monthMetricStart=$month.'-01';
$monthMetricNext=date('Y-m-d',strtotime($monthMetricStart.' +1 month'));
$summary['worked_month']=(int)(DB::fetch("SELECT COUNT(DISTINCT client_id) n FROM collection_actions WHERE created_at>=? AND created_at<?",[$monthMetricStart,$monthMetricNext])['n']??0);
include '_layout.php';?>
<div class="page-heading"><div><div class="eyebrow">COBRANÇA</div><h1>Carteira de devedores</h1><p>Todos os clientes vencidos das contas financeiras selecionadas, independentemente do vendedor.</p></div><div class="d-flex gap-2"><a class="btn btn-outline-secondary" href="cobranca-agenda.php"><i class="fa-regular fa-calendar-check"></i>Agenda</a><?php if(Auth::can('supervisor','admin')):?><a class="btn btn-dark" href="cobranca-equipe.php"><i class="fa-solid fa-chart-line"></i>Desempenho</a><?php endif;?></div></div>
<div class="stat-grid stat-grid-4 mb-4">
 <div class="stat-card"><span>Em cobrança</span><strong><?=$summary['open']?></strong><small>clientes com saldo pendente</small></div>
 <div class="stat-card"><span>Saldo a recuperar</span><strong><?=money($summary['amount'])?></strong><small>já considerando recebimentos locais</small></div>
 <?php if($collectorGoal):?><div class="stat-card"><span>Recuperado no mês</span><strong><?=money($collectorGoal['recovered'])?></strong><small><?=number_format($collectorGoal['amount_percent'],1,',','.')?>% da meta de <?=money($collectorGoal['amount_goal'])?></small></div><?php else:?><div class="stat-card"><span>Trabalhados no mês</span><strong><?=$summary['worked_month']?></strong><small>clientes únicos</small></div><?php endif;?>
 <div class="stat-card"><span>Quitados localmente</span><strong><?=$summary['settled']?></strong><small>aguardando ou já refletidos no Omie</small></div>
</div>
<div class="toolbar-card mb-3">
 <div class="segmented-control"><a class="<?=$view==='open'?'active':''?>" href="?view=open&signal=<?=e($signal)?>">Pendentes</a><a class="<?=$view==='all'?'active':''?>" href="?view=all&signal=<?=e($signal)?>">Todos</a><a class="<?=$view==='settled'?'active':''?>" href="?view=settled&signal=<?=e($signal)?>">Quitados</a></div>
 <form class="ms-auto"><select class="form-select" name="signal" onchange="location.href='?view=<?=e($view)?>&signal='+this.value"><option value="all" <?=$signal==='all'?'selected':''?>>Todos os atendimentos</option><option value="attended" <?=$signal==='attended'?'selected':''?>>Atendidos no mês</option><option value="agreement" <?=$signal==='agreement'?'selected':''?>>Acordo fechado</option><option value="unattended" <?=$signal==='unattended'?'selected':''?>>Sem atendimento no mês</option></select></form>
 <div class="small text-secondary"><i class="fa-solid fa-circle-info me-1"></i>O valor local é reconciliado na próxima sincronização financeira.</div>
</div>
<div class="panel-card"><div class="table-responsive data-table-wrap">
<table class="table modern-table data-table collection-table mb-0" data-entity="devedores" data-page-length="25" data-order-column="6">
<thead><tr><th>Cliente</th><th>Vendedor</th><th>UF</th><th>Última compra</th><th>Dias</th><th>Financeiro</th><th class="text-end">Valor devido</th><th>Sinalização</th><th>Último contato</th><th class="no-sort"></th></tr></thead><tbody>
<?php foreach($rows as $r):$settled=$r['effective_debt']<=0.009;?>
<tr>
 <td><div class="table-person"><span class="avatar avatar-sm"><?=e(mb_strtoupper(mb_substr($r['name'],0,1)))?></span><div><strong><?=e($r['name'])?></strong><small><?=e($r['omie_code'])?></small></div></div></td>
 <td><?=e($r['seller_name']??'—')?></td><td><span class="badge-muted"><?=e($r['uf']?:'—')?></span></td>
 <td data-order="<?=e((string)$r['last_purchase_at'])?>"><?=brdate($r['last_purchase_at'])?></td><td data-order="<?=$r['days_without_purchase']??99999?>"><?=$r['days_without_purchase']??'—'?></td>
 <td><?=$settled?'<span class="status-pill status-paid"><i class="fa-solid fa-circle-check"></i> Quitado</span>':($r['pending_received']>0?'<span class="status-pill status-partial"><i class="fa-solid fa-clock-rotate-left"></i> Parcial</span>':'<span class="status-pill status-overdue"><i class="fa-solid fa-triangle-exclamation"></i> Vencido</span>')?></td>
 <td class="text-end" data-order="<?=$r['effective_debt']?>"><strong class="<?=$settled?'text-success':'text-danger'?>"><?=money($r['effective_debt'])?></strong><?php if($r['pending_received']>0):?><small class="table-sub"><?=money($r['pending_received'])?> recebido localmente</small><?php endif;?></td>
 <td><div class="signal-stack"><?php if($r['attended_month_at']):?><span class="signal-badge signal-attended"><i class="fa-solid fa-check"></i>Atendido</span><?php endif;?><?php if($r['agreement_month_at']):?><span class="signal-badge signal-agreement"><i class="fa-solid fa-handshake"></i>Acordo</span><?php endif;?><?php if(!$r['attended_month_at']):?><span class="signal-badge signal-muted">Não trabalhado</span><?php endif;?></div></td>
 <td data-order="<?=e((string)$r['last_contact'])?>"><?=brdate($r['last_contact'])?></td>
 <td><a class="btn btn-dark btn-sm" href="cobranca-cliente.php?id=<?=$r['id']?>"><i class="fa-solid fa-headset"></i> Atender</a></td>
</tr><?php endforeach;?></tbody></table></div></div>
<?php include '_footer.php';?>
