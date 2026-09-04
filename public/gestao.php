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
$start=$month.'-01';
$next=date('Y-m-d',strtotime($start.' +1 month'));

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
$unassigned=(int)(DB::fetch("SELECT COUNT(*) n
 FROM client_portfolio_assignments pa
 WHERE pa.month_ref=? AND pa.seller_omie_code IS NULL",[$month])['n']??0);

$sellers=DB::all("SELECT s.*,u.name user_name FROM sellers s LEFT JOIN users u ON u.seller_omie_code=s.omie_code AND u.active=1 WHERE s.active=1 ORDER BY s.name");
$collectors=DB::all("SELECT * FROM users WHERE role='collector' AND active=1 ORDER BY name");

$salesRows=[];
foreach($sellers as $seller){
    $goal=GoalService::sellerMonth((string)$seller['omie_code'],$month);
    if(!$goal['has_sales'])continue;
    $uid=DB::fetch("SELECT id FROM users WHERE seller_omie_code=? AND active=1 LIMIT 1",[$seller['omie_code']]);
    $worked=0;$agreements=0;
    if($uid){
        $stats=DB::fetch("SELECT COUNT(DISTINCT client_id) worked,
                                 COUNT(DISTINCT CASE WHEN result='acordo' THEN client_id END) agreements
                          FROM activities
                          WHERE user_id=? AND deleted_at IS NULL AND created_at>=? AND created_at<?",
                         [$uid['id'],$start,$next])??[];
        $worked=(int)($stats['worked']??0);
        $agreements=(int)($stats['agreements']??0);
    }
    $salesRows[]=['seller'=>$seller,'goal'=>$goal,'worked'=>$worked,'agreements'=>$agreements];
}
usort($salesRows,fn($a,$b)=>($b['goal']['percent']<=>$a['goal']['percent']));

include '_layout.php';?>

<div class="command-head mb-4">
 <div>
  <div class="eyebrow">CENTRAL DE GESTÃO • <?=date('m/Y',strtotime($start))?></div>
  <h1>Operação Tecnodata</h1>
  <p>Uma visão executiva para decidir onde atuar: receita, equipe comercial, cobrança e pendências operacionais.</p>
 </div>
 <form method="get" class="command-period"><label>Período</label><input class="form-control" type="month" name="month" value="<?=e($month)?>" onchange="this.form.submit()"></form>
</div>

<div class="command-hero mb-4">
 <div class="command-hero-main">
  <span>RESULTADO GERAL DO MÊS</span>
  <div class="command-value-row"><strong><?=money($general['realized'])?></strong><b><?=number_format($general['percent'],1,',','.')?>%</b></div>
  <p>Meta <?=money($general['goal'])?> • faltam <?=money($general['missing'])?></p>
  <div class="command-progress"><span style="width:<?=min(100,max(0,$general['percent']))?>%"></span></div>
  <div class="command-meta">
   <span><small>Vendas + serviços</small><strong><?=money($general['sales_realized'])?></strong></span>
   <span><small>Recuperado</small><strong><?=money($general['collection_realized'])?></strong></span>
   <span><small>Necessário/dia útil</small><strong><?=money($general['daily_need'])?></strong></span>
  </div>
 </div>
 <div class="command-alerts">
  <a href="<?=APP_URL?>/agenda.php" class="<?=$late>0?'is-critical':''?>"><span><i class="fa-regular fa-clock"></i>Retornos atrasados</span><strong><?=$late?></strong><small>exigem ação imediata</small></a>
  <a href="<?=APP_URL?>/cobranca.php"><span><i class="fa-solid fa-hand-holding-dollar"></i>Saldo em cobrança</span><strong><?=money($portfolio['amount'])?></strong><small><?=$portfolio['open_clients']?> clientes pendentes</small></a>
  <a href="<?=APP_URL?>/clientes.php?month=<?=e($month)?>"><span><i class="fa-solid fa-user-plus"></i>Sem carteira do mês</span><strong><?=$unassigned?></strong><small>clientes sem responsável definido</small></a>
 </div>
</div>

<div class="command-shortcuts mb-4">
 <a href="<?=APP_URL?>/clientes.php"><i class="fa-solid fa-users"></i><span><strong>Clientes</strong><small>carteiras, tags e distribuição</small></span><i class="fa-solid fa-arrow-right"></i></a>
 <a href="<?=APP_URL?>/pedidos.php"><i class="fa-solid fa-receipt"></i><span><strong>Pedidos</strong><small>acompanhar etapas da Omie</small></span><i class="fa-solid fa-arrow-right"></i></a>
 <a href="<?=APP_URL?>/servicos.php"><i class="fa-solid fa-graduation-cap"></i><span><strong>Serviços</strong><small>ordens e faturamento</small></span><i class="fa-solid fa-arrow-right"></i></a>
 <a href="<?=APP_URL?>/cobranca.php"><i class="fa-solid fa-hand-holding-dollar"></i><span><strong>Cobrança</strong><small>pendências, acordos e recuperação</small></span><i class="fa-solid fa-arrow-right"></i></a>
</div>

<div class="row g-4">
 <div class="col-xxl-8">
  <div class="panel-card h-100">
   <div class="panel-header command-panel-head">
    <div><span>COMERCIAL</span><h2>Ritmo da equipe de vendas</h2><p>Quem está acima, dentro ou abaixo do ritmo esperado.</p></div>
    <a class="btn btn-outline-secondary btn-sm" href="<?=APP_URL?>/equipe.php?month=<?=e($month)?>">Abrir equipe</a>
   </div>
   <div class="command-team-list">
   <?php foreach($salesRows as $row):
      $seller=$row['seller'];$goal=$row['goal'];
      $tone=$goal['percent']>=100?'success':($goal['percent']>=70?'warning':'danger');
   ?>
    <a class="command-team-row" href="<?=APP_URL?>/vendedor.php?code=<?=urlencode((string)$seller['omie_code'])?>&month=<?=e($month)?>">
     <span class="avatar avatar-sm"><?=e(mb_strtoupper(mb_substr((string)$seller['name'],0,1)))?></span>
     <span class="command-person"><strong><?=e($seller['name'])?></strong><small><?=$row['worked']?> clientes • <?=$row['agreements']?> acordo(s)</small></span>
     <span class="command-money"><strong><?=money($goal['realized'])?></strong><small>de <?=money($goal['current'])?></small></span>
     <span class="command-rhythm is-<?=$tone?>"><?=number_format($goal['percent'],1,',','.')?>%</span>
     <i class="fa-solid fa-chevron-right"></i>
    </a>
   <?php endforeach;?>
   </div>
  </div>
 </div>

 <div class="col-xxl-4">
  <div class="panel-card h-100">
   <div class="panel-header command-panel-head">
    <div><span>COBRANÇA</span><h2>Recuperação por responsável</h2><p>Meta e produção atribuídas ao responsável atual.</p></div>
    <a class="btn btn-outline-secondary btn-sm" href="<?=APP_URL?>/cobranca-equipe.php?month=<?=e($month)?>">Detalhes</a>
   </div>
   <div class="command-collector-list">
   <?php foreach($collectors as $collector):$cg=CollectionService::monthForUser((int)$collector['id'],$month);?>
    <div class="command-collector-row">
     <span class="avatar avatar-sm"><?=e(mb_strtoupper(mb_substr((string)$collector['name'],0,1)))?></span>
     <div><strong><?=e($collector['name'])?></strong><small><?=$cg['worked']?> clientes • <?=$cg['agreements']?> acordo(s)</small></div>
     <div class="text-end"><strong><?=money($cg['recovered'])?></strong><small><?=number_format($cg['amount_percent'],1,',','.')?>% da meta</small></div>
    </div>
   <?php endforeach;?>
   <?php if(!$collectors):?><div class="empty-inline m-3">Nenhum usuário de cobrança ativo.</div><?php endif;?>
   </div>
  </div>
 </div>
</div>

<div class="command-bottom-grid mt-4">
 <div class="command-mini-card"><span>Clientes trabalhados</span><strong><?=(int)($engagement['clients_worked']??0)?></strong><small>clientes únicos no mês</small></div>
 <div class="command-mini-card"><span>Acordos registrados</span><strong><?=(int)($engagement['agreements']??0)?></strong><small>vendas + cobrança</small></div>
 <div class="command-mini-card"><span>Retornos para hoje</span><strong><?=$today?></strong><small><?=$late?> já estão atrasados</small></div>
 <div class="command-mini-card"><span>Parciais na cobrança</span><strong><?=number_format($portfolio['partial_clients']??0,0,',','.')?></strong><small><?=money($portfolio['partial_paid']??0)?> já recebido</small></div>
</div>

<?php include '_footer.php';?>