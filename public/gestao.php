<?php
require dirname(__DIR__).'/app/bootstrap.php';
require APP_ROOT.'/app/Support/helpers.php';
use Tecnodata\CRM\Core\Auth;
use Tecnodata\CRM\Core\DB;
use Tecnodata\CRM\Services\GoalService;
use Tecnodata\CRM\Services\CollectionService;

Auth::requireLogin();
if(!Auth::can('supervisor','admin')){http_response_code(403);exit('Sem acesso');}

$month=(string)($_GET['month']??date('Y-m'));
if(!preg_match('/^\d{4}-\d{2}$/',$month))$month=date('Y-m');
$start=$month.'-01';$next=date('Y-m-d',strtotime($start.' +1 month'));
$general=GoalService::generalMonth($month);
$portfolio=CollectionService::portfolioSummary();

$engagement=DB::fetch("SELECT
  COUNT(DISTINCT client_id) clients_worked,
  SUM(kind='sales') sales_actions,
  SUM(kind='collection') collection_actions,
  SUM(agreement=1) agreements
FROM (
  SELECT client_id,'sales' kind,(result='acordo') agreement
  FROM activities WHERE deleted_at IS NULL AND created_at>=? AND created_at<?
  UNION ALL
  SELECT client_id,'collection' kind,(result='acordo') agreement
  FROM collection_actions WHERE deleted_at IS NULL AND created_at>=? AND created_at<?
) x",[$start,$next,$start,$next])??[];

$late=(int)(DB::fetch("SELECT COUNT(*) n FROM tasks WHERE status='pending' AND due_at<NOW()")['n']??0);
$today=(int)(DB::fetch("SELECT COUNT(*) n FROM tasks WHERE status='pending' AND due_at>=CURDATE() AND due_at<DATE_ADD(CURDATE(),INTERVAL 1 DAY)")['n']??0);

$sellers=DB::all("SELECT s.*,u.name user_name FROM sellers s LEFT JOIN users u ON u.seller_omie_code=s.omie_code AND u.active=1 WHERE s.active=1 ORDER BY s.name");
$collectors=DB::all("SELECT * FROM users WHERE role='collector' AND active=1 ORDER BY name");

include '_layout.php';?>
<div class="page-heading">
 <div><div class="eyebrow">VISÃO DE GESTÃO • <?=date('m/Y',strtotime($start))?></div><h1>Operação comercial em uma tela</h1><p>Meta geral, vendas, recuperação, esforço e pendências sem excesso de CRM.</p></div>
 <input class="form-control month-control" type="month" value="<?=e($month)?>" onchange="location.href='?month='+this.value">
</div>

<div class="management-goal mb-4">
 <div class="management-goal-main">
  <span>META GERAL</span>
  <strong><?=money($general['realized'])?></strong>
  <small>de <?=money($general['goal'])?> • <?=number_format($general['percent'],1,',','.')?>% atingido</small>
  <div class="td-progress mt-3"><span style="width:<?=min(100,$general['percent'])?>%"></span></div>
 </div>
 <div class="management-goal-detail"><span>Vendas + serviços</span><strong><?=money($general['sales_realized'])?></strong><small>Pedidos e OS válidos</small></div>
 <div class="management-goal-detail"><span>Recuperado</span><strong><?=money($general['collection_realized'])?></strong><small>Também compõe a meta geral</small></div>
 <div class="management-goal-detail"><span>Faltam</span><strong><?=money($general['missing'])?></strong><small><?=money($general['daily_need'])?> por dia útil</small></div>
</div>

<div class="stat-grid stat-grid-4 mb-4">
 <div class="stat-card"><span>Clientes atendidos</span><strong><?=(int)($engagement['clients_worked']??0)?></strong><small>clientes únicos no mês</small></div>
 <div class="stat-card"><span>Acordos fechados</span><strong><?=(int)($engagement['agreements']??0)?></strong><small>vendas + cobrança</small></div>
 <div class="stat-card"><span>Retornos hoje</span><strong><?=$today?></strong><small><?=$late?> atrasados agora</small></div>
 <div class="stat-card"><span>Carteira de cobrança</span><strong><?=money($portfolio['amount'])?></strong><small><?=$portfolio['open_clients']?> devedores pendentes</small></div>
</div>

<div class="row g-4">
 <div class="col-xl-7">
  <div class="panel-card">
   <div class="panel-header"><div><span>VENDAS</span><h2>Ritmo por vendedor</h2></div><a href="<?=APP_URL?>/equipe.php?month=<?=e($month)?>">Ver equipe</a></div>
   <div class="table-responsive"><table class="table modern-table mb-0"><thead><tr><th>Vendedor</th><th>Realizado</th><th>Meta</th><th>Ritmo</th><th>Atendidos</th><th>Acordos</th></tr></thead><tbody>
   <?php foreach($sellers as $seller):
      $goal=GoalService::sellerMonth((string)$seller['omie_code'],$month);
      if(!$goal['has_sales'])continue;
      $uid=DB::fetch("SELECT id FROM users WHERE seller_omie_code=? AND active=1 LIMIT 1",[$seller['omie_code']]);
      $worked=0;$agreements=0;
      if($uid){
       $stats=DB::fetch("SELECT COUNT(DISTINCT client_id) worked,COUNT(DISTINCT CASE WHEN result='acordo' THEN client_id END) agreements
                         FROM activities WHERE user_id=? AND deleted_at IS NULL AND created_at>=? AND created_at<?",
                         [$uid['id'],$start,$next])??[];
       $worked=(int)($stats['worked']??0);$agreements=(int)($stats['agreements']??0);
      }?>
    <tr><td><strong><?=e($seller['name'])?></strong></td><td><?=money($goal['realized'])?></td><td><?=money($goal['current'])?></td>
    <td><span class="status-pill <?=$goal['percent']>=100?'status-paid':($goal['percent']>=70?'status-partial':'status-overdue')?>"><?=number_format($goal['percent'],1,',','.')?>%</span></td>
    <td><?=$worked?></td><td><?=$agreements?></td></tr>
   <?php endforeach;?></tbody></table></div>
  </div>
 </div>
 <div class="col-xl-5">
  <div class="panel-card">
   <div class="panel-header"><div><span>COBRANÇA</span><h2>Recuperação por responsável</h2></div><a href="<?=APP_URL?>/cobranca-equipe.php?month=<?=e($month)?>">Ver cobrança</a></div>
   <div class="management-list">
   <?php foreach($collectors as $collector):$cg=CollectionService::monthForUser((int)$collector['id'],$month);?>
    <div class="management-list-row"><span class="avatar avatar-sm"><?=e(mb_strtoupper(mb_substr($collector['name'],0,1)))?></span><div><strong><?=e($collector['name'])?></strong><small><?=$cg['worked']?> atendidos • <?=$cg['agreements']?> acordos</small></div><div class="text-end"><strong><?=money($cg['recovered'])?></strong><small><?=number_format($cg['amount_percent'],1,',','.')?>% da meta</small></div></div>
   <?php endforeach;?>
   <?php if(!$collectors):?><div class="empty-inline">Nenhum usuário de cobrança ativo.</div><?php endif;?>
   </div>
  </div>
 </div>
</div>
<?php include '_footer.php';?>
