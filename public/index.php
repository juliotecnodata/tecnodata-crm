<?php
require dirname(__DIR__) . '/app/bootstrap.php';
require APP_ROOT.'/app/Support/helpers.php';

use Tecnodata\CRM\Core\Auth;
use Tecnodata\CRM\Core\DB;
use Tecnodata\CRM\Services\GoalService;
use Tecnodata\CRM\Services\PurchaseCycleService;

Auth::requireLogin();
$u=Auth::user();

if(in_array(($u['role']??''),['supervisor','admin'],true)){header('Location: '.APP_URL.'/gestao.php');exit;}
if(($u['role']??'')==='collector'){header('Location: '.APP_URL.'/cobranca.php');exit;}

$profile=$u['role']==='seller'&&!empty($u['seller_omie_code'])
 ? DB::fetch('SELECT goal_mode FROM sellers WHERE omie_code=? AND active=1',[$u['seller_omie_code']])
 : null;
$mode=(string)($profile['goal_mode']??'sales_collection');
if($mode==='collection'){header('Location: '.APP_URL.'/cobranca.php');exit;}

$seller=(string)($u['seller_omie_code']??'');
$month=date('Y-m');
$monthStart=$month.'-01';
$monthNext=date('Y-m-d',strtotime($monthStart.' +1 month'));
$goal=$seller!==''?GoalService::sellerMonth($seller,$month):null;

$sql="SELECT c.id,c.name,c.omie_code,c.uf,c.phone,m.*,
 (SELECT MAX(a.created_at) FROM activities a WHERE a.client_id=c.id AND a.deleted_at IS NULL) last_contact,
 (SELECT COUNT(*) FROM tasks t WHERE t.client_id=c.id AND t.user_id=? AND t.status='pending') pending_tasks
 FROM clients c
 JOIN client_metrics m ON m.client_id=c.id
 LEFT JOIN client_portfolio_assignments pa ON pa.client_id=c.id AND pa.month_ref=?
 WHERE (CASE WHEN pa.id IS NOT NULL THEN pa.seller_omie_code ELSE m.seller_omie_code END)=?";
$rows=DB::all($sql,[(int)$u['id'],$month,$seller]);

$summary=['total'=>count($rows),'due'=>0,'overdue'=>0,'attention'=>0,'revenue'=>0.0];
foreach($rows as &$row){
    $row['cycle']=PurchaseCycleService::analyze($row['last_purchase_at']??null,$row['avg_interval_days']??null,isset($row['days_without_purchase'])?(int)$row['days_without_purchase']:null);
    $row['priority_score']=PurchaseCycleService::priorityScore($row);
    if($row['cycle']['status']==='due')$summary['due']++;
    if($row['cycle']['status']==='overdue')$summary['overdue']++;
    if(in_array((string)$row['commercial_status'],['attention','reactivate'],true))$summary['attention']++;
    $summary['revenue']+=(float)$row['revenue_12m'];
}
unset($row);
usort($rows,fn($a,$b)=>($b['priority_score']<=>$a['priority_score']) ?: ((float)$b['revenue_12m']<=>(float)$a['revenue_12m']));
$priority=array_slice($rows,0,8);

$today=(int)(DB::fetch("SELECT COUNT(*) n FROM tasks WHERE user_id=? AND status='pending' AND due_at>=CURDATE() AND due_at<DATE_ADD(CURDATE(),INTERVAL 1 DAY)",[(int)$u['id']])['n']??0);
$late=(int)(DB::fetch("SELECT COUNT(*) n FROM tasks WHERE user_id=? AND status='pending' AND due_at<NOW()",[(int)$u['id']])['n']??0);

include '_layout.php';?>
<div class="seller-cockpit-head mb-4">
 <div>
  <div class="eyebrow">CENTRAL COMERCIAL • <?=date('m/Y')?></div>
  <h1>Olá, <?=e(explode(' ',trim((string)$u['name']))[0]??'')?>.</h1>
  <p>Seu dia organizado por ciclo de compra, prioridade de carteira, retornos e meta.</p>
 </div>
 <div class="seller-cockpit-actions">
  <a class="btn btn-outline-secondary" href="<?=APP_URL?>/agenda.php"><i class="fa-regular fa-calendar-check"></i>Agenda <?=$late>0?'• '.$late.' atrasado(s)':''?></a>
  <a class="btn btn-primary" href="<?=APP_URL?>/carteira.php?cycle=due"><i class="fa-solid fa-bolt"></i>Trabalhar oportunidades</a>
 </div>
</div>

<div class="seller-command-grid mb-4">
 <div class="seller-goal-card">
  <div class="seller-goal-top"><div><span>RESULTADO DO MÊS</span><strong><?=money($goal['realized']??0)?></strong></div><span class="goal-percent"><?=number_format((float)($goal['percent']??0),1,',','.')?>%</span></div>
  <div class="seller-goal-track"><span style="width:<?=min(100,max(0,(float)($goal['percent']??0)))?>%"></span></div>
  <div class="seller-goal-meta"><span>Meta atual <strong><?=money($goal['current']??0)?></strong></span><span>Faltam <strong><?=money($goal['missing']??0)?></strong></span><span>Necessário/dia <strong><?=money($goal['daily_need']??0)?></strong></span></div>
 </div>
 <div class="seller-signal-card"><span>Janela de compra</span><strong><?=$summary['due']?></strong><small>clientes no momento provável de recompra</small><a href="carteira.php?cycle=due">Ver agora <i class="fa-solid fa-arrow-right"></i></a></div>
 <div class="seller-signal-card is-critical"><span>Ciclo vencido</span><strong><?=$summary['overdue']?></strong><small>clientes que passaram da recompra esperada</small><a href="carteira.php?cycle=overdue">Priorizar <i class="fa-solid fa-arrow-right"></i></a></div>
 <div class="seller-signal-card"><span>Agenda de hoje</span><strong><?=$today?></strong><small><?=$late?> retorno(s) atrasado(s)</small><a href="agenda.php">Abrir agenda <i class="fa-solid fa-arrow-right"></i></a></div>
</div>

<div class="row g-4">
 <div class="col-xl-8">
  <div class="panel-card">
   <div class="panel-header"><div><span>PRIORIDADES</span><h2>Quem trabalhar agora</h2></div><a class="btn btn-outline-secondary btn-sm" href="carteira.php">Abrir carteira completa</a></div>
   <div class="priority-worklist">
   <?php foreach($priority as $row):$cycle=$row['cycle'];?>
    <a class="priority-work-row" href="<?=APP_URL?>/cliente.php?id=<?=$row['id']?>">
     <span class="priority-rank"><?=e(mb_strtoupper(mb_substr((string)$row['name'],0,1)))?></span>
     <span class="priority-copy">
      <strong><?=e($row['name'])?></strong>
      <small><?=e($row['uf']?:'—')?> • <?=money($row['revenue_12m'])?> em 12 meses • <?=$row['orders_12m']?> compra(s)</small>
     </span>
     <span class="cycle-chip cycle-<?=$cycle['tone']?>"><?=e($cycle['label'])?></span>
     <span class="priority-cycle">
      <strong><?=$cycle['expected_date']?brdate($cycle['expected_date']):'—'?></strong>
      <small><?=$cycle['interval']?'ciclo médio '.$cycle['interval'].' dias':'sem histórico suficiente'?></small>
     </span>
     <i class="fa-solid fa-arrow-right"></i>
    </a>
   <?php endforeach;?>
   <?php if(!$priority):?><div class="empty-state"><i class="fa-solid fa-address-book"></i><h2>Carteira sem dados</h2><p>Atualize os indicadores para formar as prioridades comerciais.</p></div><?php endif;?>
   </div>
  </div>
 </div>
 <div class="col-xl-4">
  <div class="panel-card mb-4"><div class="panel-header"><div><span>CARTEIRA</span><h2>Leitura rápida</h2></div></div><div class="panel-body">
   <div class="cockpit-list">
    <div><span>Clientes na carteira</span><strong><?=number_format($summary['total'],0,',','.')?></strong></div>
    <div><span>Precisam de atenção</span><strong><?=number_format($summary['attention'],0,',','.')?></strong></div>
    <div><span>Receita 12 meses</span><strong><?=money($summary['revenue'])?></strong></div>
    <div><span>Retornos atrasados</span><strong class="<?=$late>0?'text-danger':''?>"><?=$late?></strong></div>
   </div>
  </div></div>
  <div class="panel-card"><div class="panel-header"><div><span>CICLO DE COMPRA</span><h2>Como interpretar</h2></div></div><div class="panel-body">
   <div class="cycle-legend">
    <div><span class="cycle-dot is-success"></span><p><strong>Dentro do ciclo</strong><small>Ainda está no período normal entre compras.</small></p></div>
    <div><span class="cycle-dot is-info"></span><p><strong>Hora de aproximar</strong><small>A recompra provável está chegando.</small></p></div>
    <div><span class="cycle-dot is-warning"></span><p><strong>Janela de compra</strong><small>Momento ideal para contato comercial.</small></p></div>
    <div><span class="cycle-dot is-danger"></span><p><strong>Ciclo vencido</strong><small>A recompra esperada passou; cliente vira prioridade.</small></p></div>
   </div>
  </div></div>
 </div>
</div>
<?php include '_footer.php';?>