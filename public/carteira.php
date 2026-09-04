<?php
require dirname(__DIR__) . '/app/bootstrap.php';
use Tecnodata\CRM\Core\Auth; use Tecnodata\CRM\Core\DB; use Tecnodata\CRM\Services\PurchaseCycleService;
Auth::requireLogin(); $u=Auth::user();
$sellerProfile=$u['role']==='seller'&&$u['seller_omie_code']?DB::fetch('SELECT goal_mode FROM sellers WHERE omie_code=? AND active=1',[$u['seller_omie_code']]):null;
$sellerMode=(string)($sellerProfile['goal_mode']??'sales_collection');
$filter=$_GET['status']??'';$finance=$_GET['finance']??'all';$signal=$_GET['signal']??'all';$cycleFilter=$_GET['cycle']??'all';$monthRef=date('Y-m');$monthStart=date('Y-m-01');$monthNext=date('Y-m-01',strtotime('+1 month'));
$sql="SELECT c.id,c.name,c.omie_code,c.uf,c.phone,m.*,s.name seller_name,
 COALESCE(fd.open_amount,0) fin_open_omie,
 COALESCE(fd.overdue_amount,0) fin_overdue_omie,
 COALESCE(fd.max_overdue_days,0) fin_max_overdue_days,
 COALESCE(adj.pending_received,0) fin_pending_received,
 GREATEST(0,COALESCE(fd.open_amount,0)-COALESCE(adj.pending_received,0)) fin_open_amount,
 GREATEST(0,COALESCE(fd.overdue_amount,0)-COALESCE(adj.pending_received,0)) fin_overdue_amount,
 (SELECT MAX(created_at) FROM activities a WHERE a.client_id=c.id AND a.deleted_at IS NULL) last_contact,
 (SELECT MAX(created_at) FROM activities a WHERE a.client_id=c.id AND a.deleted_at IS NULL AND a.created_at>='$monthStart' AND a.created_at<'$monthNext') attended_month_at,
 (SELECT MAX(created_at) FROM activities a WHERE a.client_id=c.id AND a.deleted_at IS NULL AND a.result='acordo' AND a.created_at>='$monthStart' AND a.created_at<'$monthNext') agreement_month_at
 FROM clients c
 JOIN client_metrics m ON m.client_id=c.id
 LEFT JOIN client_portfolio_assignments pa ON pa.client_id=c.id AND pa.month_ref=?
 LEFT JOIN sellers s ON s.omie_code=(CASE WHEN pa.id IS NOT NULL THEN pa.seller_omie_code ELSE m.seller_omie_code END)
 LEFT JOIN (
   SELECT fm.client_omie_code,
     SUM(CASE WHEN UPPER(fm.status) IN('EMABERTO','ATRASADO','AVENCER','VENCEHOJE','PAGTO_PARCIAL') THEN fm.amount ELSE 0 END) open_amount,
     SUM(CASE WHEN UPPER(fm.status) IN('ATRASADO','PAGTO_PARCIAL') THEN fm.amount ELSE 0 END) overdue_amount,
     MAX(CASE WHEN UPPER(fm.status) IN('ATRASADO','PAGTO_PARCIAL') AND fm.due_date IS NOT NULL THEN GREATEST(0,DATEDIFF(CURDATE(),fm.due_date)) ELSE 0 END) max_overdue_days
   FROM financial_movements fm
   INNER JOIN financial_accounts fa ON fa.omie_code=fm.account_omie_code AND fa.selected=1 AND fa.active=1
   GROUP BY fm.client_omie_code
 ) fd ON fd.client_omie_code=c.omie_code
 LEFT JOIN collection_client_adjustments adj ON adj.client_id=c.id
 WHERE 1=1";
$p=[$monthRef];
if($u['role']==='seller' && $u['seller_omie_code']){
 if($sellerMode==='collection'){
   $sql.=" AND EXISTS(
     SELECT 1 FROM financial_movements fm
     INNER JOIN financial_accounts fa ON fa.omie_code=fm.account_omie_code AND fa.selected=1 AND fa.active=1
     WHERE fm.client_omie_code=c.omie_code
       AND UPPER(fm.status) IN('ATRASADO','PAGTO_PARCIAL')
       AND fm.seller_omie_code=?
   )";
   $p[]=$u['seller_omie_code'];
 }else{
   $sql.=" AND (CASE WHEN pa.id IS NOT NULL THEN pa.seller_omie_code ELSE m.seller_omie_code END)=?";
   $p[]=$u['seller_omie_code'];
 }
}
if(in_array($filter,['normal','attention','reactivate'],true)){$sql.=" AND m.commercial_status=?";$p[]=$filter;}
if($finance==='overdue')$sql.=" AND GREATEST(0,COALESCE(fd.overdue_amount,0)-COALESCE(adj.pending_received,0))>0";
elseif($finance==='clear')$sql.=" AND GREATEST(0,COALESCE(fd.overdue_amount,0)-COALESCE(adj.pending_received,0))<=0";
if($signal==='attended')$sql.=" AND EXISTS(SELECT 1 FROM activities ax WHERE ax.client_id=c.id AND ax.deleted_at IS NULL AND ax.created_at>=? AND ax.created_at<?)";
elseif($signal==='agreement')$sql.=" AND EXISTS(SELECT 1 FROM activities ax WHERE ax.client_id=c.id AND ax.deleted_at IS NULL AND ax.result='acordo' AND ax.created_at>=? AND ax.created_at<?)";
elseif($signal==='unattended')$sql.=" AND NOT EXISTS(SELECT 1 FROM activities ax WHERE ax.client_id=c.id AND ax.deleted_at IS NULL AND ax.created_at>=? AND ax.created_at<?)";
if(in_array($signal,['attended','agreement','unattended'],true)){$p[]=$monthStart;$p[]=$monthNext;}
$sql.=" ORDER BY FIELD(m.commercial_status,'reactivate','attention','normal'), m.revenue_12m DESC";
$rows=DB::all($sql,$p);
foreach($rows as &$row){
 $row['cycle']=PurchaseCycleService::analyze($row['last_purchase_at']??null,$row['avg_interval_days']??null,isset($row['days_without_purchase'])?(int)$row['days_without_purchase']:null);
}
unset($row);
if(in_array($cycleFilter,['on_track','due_soon','due','overdue','unknown'],true)){
 $rows=array_values(array_filter($rows,fn($row)=>($row['cycle']['status']??'unknown')===$cycleFilter));
}
include '_layout.php';?>
<div class="page-heading">
 <div>
  <div class="eyebrow">CARTEIRA COMERCIAL</div>
  <h1><?=$sellerMode==='collection'?'Carteira de cobrança':'Minha carteira'?></h1>
  <p><?=$sellerMode==='collection'?'Clientes inadimplentes das contas financeiras selecionadas.':'Priorize pelo momento de recompra, situação financeira e histórico de relacionamento. O ciclo é calculado a partir do intervalo médio entre compras.'?></p>
 </div>
 <?php if($sellerMode!=='collection'):?><a class="btn btn-primary" href="<?=APP_URL?>/agenda.php"><i class="fa-regular fa-calendar-check"></i>Minha agenda</a><?php endif;?>
</div>
<?php if($sellerMode!=='collection'):?>
<form class="portfolio-work-filter mb-4" method="get">
 <div class="filter-group-wide">
  <label class="form-label">Momento de compra</label>
  <select class="form-select" name="cycle">
   <option value="all" <?=$cycleFilter==='all'?'selected':''?>>Todos os momentos</option>
   <option value="due" <?=$cycleFilter==='due'?'selected':''?>>Janela de compra</option>
   <option value="overdue" <?=$cycleFilter==='overdue'?'selected':''?>>Ciclo vencido</option>
   <option value="due_soon" <?=$cycleFilter==='due_soon'?'selected':''?>>Hora de aproximar</option>
   <option value="on_track" <?=$cycleFilter==='on_track'?'selected':''?>>Dentro do ciclo</option>
   <option value="unknown" <?=$cycleFilter==='unknown'?'selected':''?>>Sem ciclo definido</option>
  </select>
 </div>
 <div><label class="form-label">Situação comercial</label><select class="form-select" name="status"><option value="">Todas</option><option value="attention" <?=$filter==='attention'?'selected':''?>>Atenção</option><option value="reactivate" <?=$filter==='reactivate'?'selected':''?>>Reativar</option><option value="normal" <?=$filter==='normal'?'selected':''?>>Normal</option></select></div>
 <div><label class="form-label">Financeiro</label><select class="form-select" name="finance"><option value="all" <?=$finance==='all'?'selected':''?>>Todos</option><option value="overdue" <?=$finance==='overdue'?'selected':''?>>Com valor vencido</option><option value="clear" <?=$finance==='clear'?'selected':''?>>Sem valor vencido</option></select></div>
 <div><label class="form-label">Atendimento</label><select class="form-select" name="signal"><option value="all" <?=$signal==='all'?'selected':''?>>Todos</option><option value="attended" <?=$signal==='attended'?'selected':''?>>Atendidos no mês</option><option value="agreement" <?=$signal==='agreement'?'selected':''?>>Acordo fechado</option><option value="unattended" <?=$signal==='unattended'?'selected':''?>>Sem atendimento no mês</option></select></div>
 <div class="portfolio-work-actions"><button class="btn btn-primary"><i class="fa-solid fa-filter"></i>Aplicar</button><a class="btn btn-outline-secondary" href="<?=APP_URL?>/carteira.php"><i class="fa-solid fa-rotate-left"></i>Limpar</a></div>
</form>
<?php endif;?>
<div class="card"><div class="table-responsive data-table-wrap">
<table class="table table-hover modern-table data-table portfolio-work-table mb-0" data-entity="clientes" data-page-length="25" data-order-column="2" data-order-dir="asc"><thead><tr><th>Cliente</th><th>UF</th><th>Momento de compra</th><th>Próxima compra</th><th>Histórico 12m</th><th>Financeiro</th><th>Relacionamento</th><th class="no-sort"></th></tr></thead><tbody>
<?php foreach($rows as $r):?>
<tr class="customer-row">
<td><div class="table-person"><span class="avatar avatar-sm"><?=e(mb_strtoupper(mb_substr((string)$r['name'],0,1)))?></span><div><strong><?=e($r['name'])?></strong><small><?=e($r['omie_code'])?> • última compra <?=brdate($r['last_purchase_at'])?></small></div></div></td>
<td><span class="badge-muted"><?=e($r['uf']?:'—')?></span></td>
<td><span class="cycle-chip cycle-<?=e($r['cycle']['tone'])?>"><?=e($r['cycle']['label'])?></span><small class="table-sub"><?=$r['cycle']['interval']?'ciclo médio '.$r['cycle']['interval'].' dias':'histórico insuficiente'?></small></td>
<td data-order="<?=e((string)($r['cycle']['expected_date']??'9999-12-31'))?>"><strong><?=$r['cycle']['expected_date']?brdate($r['cycle']['expected_date']):'—'?></strong><small class="table-sub"><?php if($r['cycle']['delta']!==null):?><?=$r['cycle']['delta']<0?abs($r['cycle']['delta']).' dias após a previsão':($r['cycle']['delta']===0?'previsão para hoje':$r['cycle']['delta'].' dias até a previsão')?><?php else:?>sem previsão<?php endif;?></small></td>
<td><strong><?=money($r['revenue_12m'])?></strong><small class="table-sub"><?=$r['orders_12m']?> compra(s) • ticket <?=money($r['avg_ticket_12m'])?></small></td>
<td><?php if((float)$r['fin_overdue_amount']>0):?>
 <span class="status-pill status-overdue"><i class="fa-solid fa-triangle-exclamation"></i><?=money($r['fin_overdue_amount'])?> vencido</span>
 <?php if((int)$r['fin_max_overdue_days']>0):?><small class="table-sub"><?=(int)$r['fin_max_overdue_days']?> dia(s) de atraso</small><?php endif;?>
 <?php elseif((float)$r['fin_open_amount']>0):?>
 <span class="status-pill status-partial"><?=money($r['fin_open_amount'])?> em aberto</span>
 <?php else:?><span class="status-pill status-paid">Em dia</span><?php endif;?>
</td>
<td><div class="signal-stack"><?php if($r['attended_month_at']):?><span class="signal-badge signal-attended"><i class="fa-solid fa-check"></i>Atendido</span><?php endif;?><?php if($r['agreement_month_at']):?><span class="signal-badge signal-agreement"><i class="fa-solid fa-handshake"></i>Acordo</span><?php endif;?><?php if(!$r['attended_month_at']):?><span class="signal-badge signal-muted">Não trabalhado</span><?php endif;?></div><small class="table-sub">último contato <?=brdate($r['last_contact'])?></small></td>
<td><a class="btn btn-primary btn-sm" href="cliente.php?id=<?=$r['id']?>"><i class="fa-solid fa-arrow-right"></i>Atender</a></td>
</tr><?php endforeach;?></tbody></table></div></div>
<?php include '_footer.php';?>
