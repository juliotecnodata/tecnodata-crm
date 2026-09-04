<?php
require dirname(__DIR__).'/app/bootstrap.php';
require APP_ROOT.'/app/Support/helpers.php';

use Tecnodata\CRM\Core\Auth;
use Tecnodata\CRM\Core\DB;
use Tecnodata\CRM\Services\GoalService;

Auth::requireLogin();
$user=Auth::user();

$month=(string)($_GET['month']??date('Y-m'));
if(!preg_match('/^\d{4}-\d{2}$/',$month))$month=date('Y-m');

$profile=$user['role']==='seller'&&!empty($user['seller_omie_code'])
    ? DB::fetch('SELECT goal_mode FROM sellers WHERE omie_code=? AND active=1',[$user['seller_omie_code']])
    : null;
if($user['role']==='seller'&&($profile['goal_mode']??'')==='collection'){
    http_response_code(403);
    exit('Este perfil possui somente cobrança.');
}

$canManage=Auth::can('supervisor','admin');
$sellers=$canManage?DB::all('SELECT omie_code,name FROM sellers WHERE active=1 ORDER BY name'):[];
$sellerCode=$canManage?(string)($_GET['seller']??''):(string)($user['seller_omie_code']??'');
if($canManage&&$sellerCode!==''&&!array_filter($sellers,fn($seller)=>(string)$seller['omie_code']===$sellerCode))$sellerCode='';

$start=$month.'-01';
$end=date('Y-m-t',strtotime($start));
$params=[$start,$end];
$where='o.order_date BETWEEN ? AND ?';
if(!$canManage&&$sellerCode==='')$where.=' AND 1=0';
elseif($sellerCode!==''){$where.=' AND o.seller_omie_code=?';$params[]=$sellerCode;}

$orders=DB::all("SELECT o.*,c.id client_id,c.name client_name,s.name seller_name
 FROM orders o
 LEFT JOIN clients c ON c.omie_code=o.client_omie_code
 LEFT JOIN sellers s ON s.omie_code=o.seller_omie_code
 WHERE {$where}
 ORDER BY o.order_date DESC,o.id DESC",$params);

foreach($orders as &$order){
    $order['stage_code']=GoalService::orderStageCode($order);
    $order['display_stage']=trim((string)($order['stage_name']??''))!==''
        ? (string)$order['stage_name']
        : ($order['stage_code']!==''?'Etapa '.$order['stage_code']:'Sem etapa');
}
unset($order);

$valid=array_values(array_filter($orders,fn($order)=>GoalService::isOrderCounted($order)));
$total=array_sum(array_map(fn($order)=>(float)$order['total'],$valid));
$excluded=count($orders)-count($valid);
$average=count($valid)?$total/count($valid):0;

$stages=[];
foreach($orders as $order){
    $key=$order['stage_code'].'|'.$order['display_stage'];
    if(!isset($stages[$key]))$stages[$key]=[
        'code'=>$order['stage_code'],
        'name'=>$order['display_stage'],
        'count'=>0,
        'total'=>0,
    ];
    $stages[$key]['count']++;
    $stages[$key]['total']+=(float)$order['total'];
}
uasort($stages,fn($a,$b)=>strcmp((string)$a['code'],(string)$b['code']));

include '_layout.php';
?>
<div class="page-heading">
 <div>
  <div class="eyebrow">PEDIDOS • <?=date('m/Y',strtotime($start))?></div>
  <h1>Pedidos por etapa</h1>
  <p>O Omie organiza o Pedido de Venda pelas etapas do processo de faturamento. Esta tela usa a etapa real do pedido, com a descrição configurada no Omie.</p>
 </div>
 <div class="d-flex gap-2 flex-wrap"><a class="btn btn-primary" href="<?=APP_URL?>/pedido-novo.php"><i class="fa-solid fa-plus"></i>Novo pedido</a><?php if(Auth::can('admin')):?><a class="btn btn-secondary" href="<?=APP_URL?>/sync.php"><i class="fa-solid fa-arrows-rotate"></i>Atualizar</a><?php endif;?></div>
</div>

<form class="toolbar-card orders-toolbar mb-4" method="get">
 <div><label class="form-label">Mês de inclusão</label><input class="form-control month-control" type="month" name="month" value="<?=e($month)?>"></div>
 <?php if($canManage):?>
 <div><label class="form-label">Vendedor</label><select class="form-select" name="seller"><option value="">Todos os vendedores</option><?php foreach($sellers as $seller):?><option value="<?=e($seller['omie_code'])?>" <?=$sellerCode===(string)$seller['omie_code']?'selected':''?>><?=e($seller['name'])?></option><?php endforeach;?></select></div>
 <?php endif;?>
 <button class="btn btn-primary"><i class="fa-solid fa-filter"></i>Aplicar</button>
</form>

<div class="stat-grid stat-grid-4 mb-4">
 <div class="stat-card"><span>Valor considerado</span><strong><?=money($total)?></strong><small><?=count($valid)?> pedidos em etapa válida</small></div>
 <div class="stat-card"><span>Pedidos incluídos</span><strong><?=number_format(count($orders),0,',','.')?></strong><small>no período selecionado</small></div>
 <div class="stat-card"><span>Ticket médio</span><strong><?=money($average)?></strong><small>pedidos considerados</small></div>
 <div class="stat-card"><span>Etapas presentes</span><strong><?=number_format(count($stages),0,',','.')?></strong><small>no período selecionado</small></div>
</div>

<?php if($stages):?>
<div class="stage-summary-grid mb-4">
 <?php foreach($stages as $stage):?>
 <button type="button" class="stage-summary-card" data-stage-filter="<?=e($stage['name'])?>">
  <span>Etapa <?=e($stage['code']?:'—')?></span>
  <strong><?=e($stage['name'])?></strong>
  <small><?=number_format($stage['count'],0,',','.')?> pedido(s) • <?=money($stage['total'])?></small>
 </button>
 <?php endforeach;?>
</div>
<?php endif;?>

<div class="panel-card">
 <div class="panel-header orders-table-header">
  <div><span>DETALHAMENTO</span><h2>Lista de pedidos por etapa</h2></div>
  <div class="status-filter">
   <label for="orderStatusFilter">Etapa</label>
   <select class="form-select form-select-sm" id="orderStatusFilter">
    <option value="">Todas as etapas</option>
    <?php foreach($stages as $stage):?><option value="<?=e($stage['name'])?>"><?=e(($stage['code']?:'—').' • '.$stage['name'])?></option><?php endforeach;?>
   </select>
  </div>
 </div>
 <div class="table-responsive data-table-wrap">
  <table id="ordersTable" class="table modern-table data-table orders-table mb-0"
   data-entity="pedidos" data-page-length="25" data-order-column="0"
   data-status-filter="#orderStatusFilter" data-status-column="<?=$canManage?4:3?>">
   <thead><tr>
    <th>Inclusão</th><th>Pedido</th><th>Cliente</th>
    <?php if($canManage):?><th>Vendedor</th><?php endif;?>
    <th>Etapa</th><th class="text-end">Valor</th><th class="no-sort"></th>
   </tr></thead>
   <tbody>
   <?php foreach($orders as $order):
      $isExcluded=!GoalService::isOrderCounted($order);
      $stageClass=$isExcluded?'badge-muted':'badge-success';
   ?>
   <tr class="<?=$isExcluded?'order-ignored':''?>">
    <td data-order="<?=e((string)$order['order_date'])?>"><strong><?=brdate($order['order_date'])?></strong><small class="table-sub">incluído na Omie</small></td>
    <td><span class="order-number">#<?=e($order['omie_order_code'])?></span></td>
    <td><div class="order-client"><span class="avatar avatar-sm"><?=e(mb_strtoupper(mb_substr((string)($order['client_name']??'?'),0,1)))?></span><span><?=e($order['client_name']??'Cliente não localizado')?></span></div></td>
    <?php if($canManage):?><td><?=e($order['seller_name']??'Sem vendedor')?></td><?php endif;?>
    <td>
      <span class="badge-soft <?=$stageClass?>"><?=e($order['display_stage'])?></span>
      <small class="table-sub">Código <?=e($order['stage_code']?:'—')?><?php if(!empty($order['stage_changed_at'])):?> • movido em <?=date('d/m H:i',strtotime($order['stage_changed_at']))?><?php endif;?></small>
      <?php if($isExcluded):?><small class="table-sub">pedido cancelado/devolvido/denegado</small><?php endif;?>
    </td>
    <td class="text-end" data-order="<?=(float)$order['total']?>"><strong class="order-value"><?=money($order['total'])?></strong></td>
    <td><?php if($order['client_id']):?><a class="icon-button" href="<?=APP_URL?>/cliente.php?id=<?=$order['client_id']?>" title="Abrir cliente"><i class="fa-solid fa-arrow-up-right-from-square"></i></a><?php endif;?></td>
   </tr>
   <?php endforeach;?>
   </tbody>
  </table>
 </div>
</div>
<?php include '_footer.php';?>