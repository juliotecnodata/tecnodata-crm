<?php
require dirname(__DIR__).'/app/bootstrap.php';
require APP_ROOT.'/app/Support/helpers.php';

use Tecnodata\CRM\Core\Auth;
use Tecnodata\CRM\Services\GoalService;
use Tecnodata\CRM\Services\CollectionService;

Auth::requireLogin();
$user=Auth::user();
$month=date('Y-m');

if(($user['role']??'')==='collector'){
 $goal=CollectionService::monthForUser((int)$user['id'],$month);
 include '_layout.php';?>
 <div class="page-heading"><div><div class="eyebrow">RESULTADO • <?=date('m/Y')?></div><h1>Meu resultado</h1><p>Sua meta de recuperação em uma única leitura.</p></div><a class="btn btn-primary" href="<?=APP_URL?>/cobranca.php"><i class="fa-solid fa-arrow-right"></i>Trabalhar carteira</a></div>
 <div class="result-focus">
  <div class="result-focus-main"><span>RECUPERADO NO MÊS</span><strong><?=money($goal['recovered'])?></strong><small>de <?=money($goal['amount_goal'])?> • faltam <?=money($goal['amount_missing'])?></small><div class="result-progress"><span style="width:<?=min(100,$goal['amount_percent'])?>%"></span></div></div>
  <div><span>Atingimento</span><strong><?=number_format($goal['amount_percent'],1,',','.')?>%</strong></div>
  <div><span>Clientes</span><strong><?=$goal['worked']?></strong><small>meta <?=$goal['contact_goal']?></small></div>
  <div><span>Acordos</span><strong><?=$goal['agreements']?></strong><small><?=$goal['actions']?> ações</small></div>
 </div>
 <?php include '_footer.php';exit;
}

$code=(string)($user['seller_omie_code']??'');
$goal=GoalService::sellerMonth($code?:'__none__',$month);
include '_layout.php';?>

<div class="page-heading">
 <div><div class="eyebrow">RESULTADO • <?=date('m/Y')?></div><h1>Meu resultado</h1><p>Meta, realizado e ritmo. Pedidos e clientes ficam nas telas de trabalho.</p></div>
 <div class="d-flex gap-2"><a class="btn btn-outline-secondary" href="<?=APP_URL?>/pedidos.php">Pedidos</a><a class="btn btn-primary" href="<?=APP_URL?>/pedido-novo.php"><i class="fa-solid fa-plus"></i>Novo pedido</a></div>
</div>

<?php if($code===''):?><div class="alert alert-warning alert-modern"><i class="fa-solid fa-link"></i>Seu usuário ainda não está vinculado a um vendedor.</div><?php endif;?>

<div class="result-focus">
 <?php if($goal['has_sales']):?>
 <div class="result-focus-main"><span>VENDAS NO MÊS</span><strong><?=money($goal['realized'])?></strong><small>meta atual <?=money($goal['current'])?> • faltam <?=money($goal['missing'])?></small><div class="result-progress"><span style="width:<?=min(100,$goal['percent'])?>%"></span></div></div>
 <div><span>Atingimento</span><strong><?=number_format($goal['percent'],1,',','.')?>%</strong></div>
 <div><span>Produtos</span><strong><?=money($goal['product_realized'])?></strong><small>pedidos válidos</small></div>
 <div><span>Serviços</span><strong><?=money($goal['service_realized'])?></strong><small>OS válidas</small></div>
 <?php endif;?>
</div>

<?php if($goal['has_sales']):?>
<div class="result-next mt-4">
 <div><span>Próxima referência</span><strong><?=money($goal['current'])?></strong><small>meta que está sendo perseguida agora</small></div>
 <div><span>Faltam</span><strong><?=money($goal['missing'])?></strong><small>para alcançar a referência atual</small></div>
 <div><span>Necessário por dia útil</span><strong><?=money($goal['daily_need'])?></strong><small><?=$goal['days']?> dia(s) útil(eis) restante(s)</small></div>
</div>
<?php endif;?>

<?php if($goal['has_collection']):?>
<div class="panel-card mt-4"><div class="panel-header"><div><span>COBRANÇA</span><h2>Recuperação atribuída a você</h2></div><a class="btn btn-outline-secondary btn-sm" href="<?=APP_URL?>/cobranca.php">Abrir cobrança</a></div><div class="result-inline"><span><small>Recuperado</small><strong><?=money($goal['collection_realized'])?></strong></span><span><small>Meta</small><strong><?=money($goal['collection_goal'])?></strong></span><span><small>Atingimento</small><strong><?=number_format($goal['collection_percent'],1,',','.')?>%</strong></span><span><small>Clientes trabalhados</small><strong><?=$goal['debtors_worked']?></strong></span></div></div>
<?php endif;?>

<?php include '_footer.php';?>