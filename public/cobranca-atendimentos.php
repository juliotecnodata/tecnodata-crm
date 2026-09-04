<?php
require dirname(__DIR__).'/app/bootstrap.php';
require APP_ROOT.'/app/Support/helpers.php';

use Tecnodata\CRM\Core\Auth;
use Tecnodata\CRM\Core\DB;
use Tecnodata\CRM\Services\CollectionService;

Auth::requireLogin();
if(!Auth::can('collector','supervisor','admin')){http_response_code(403);exit('Sem acesso');}
$u=Auth::user();

$month=(string)($_GET['month']??date('Y-m'));
if(!preg_match('/^\d{4}-\d{2}$/',$month))$month=date('Y-m');

$selectedUser=(int)$u['id'];
if(Auth::can('supervisor','admin') && isset($_GET['user_id']))$selectedUser=max(0,(int)$_GET['user_id']);
if(($u['role']??'')==='collector')$selectedUser=(int)$u['id'];

$stats=$selectedUser>0?CollectionService::monthForUser($selectedUser,$month):[
 'worked'=>0,'actions'=>0,'agreements'=>0,'promises'=>0,'recovered'=>0,'amount_percent'=>0
];
$collectors=Auth::can('supervisor','admin')?DB::all("SELECT id,name FROM users WHERE role='collector' AND active=1 ORDER BY name"):[];

include '_layout.php';?>
<div class="page-heading">
 <div><a class="back-link" href="<?=APP_URL?>/cobranca.php"><i class="fa-solid fa-arrow-left"></i>Voltar para cobrança</a><div class="eyebrow mt-3">ATENDIMENTOS</div><h1><?=$selectedUser===(int)$u['id']?'Meus atendimentos':'Atendimentos da cobrança'?></h1><p>Esta é a sua memória de trabalho. Veja tudo o que já foi feito, filtre por acordo, promessa ou pagamento e abra qualquer registro para revisar, editar ou excluir.</p></div>
 <a class="btn btn-dark" href="<?=APP_URL?>/cobranca.php"><i class="fa-solid fa-headset"></i>Voltar à carteira</a>
</div>

<div class="stat-grid stat-grid-4 mb-4">
 <div class="stat-card"><span>Clientes trabalhados</span><strong><?=$stats['worked']?></strong><small>clientes únicos no período</small></div>
 <div class="stat-card"><span>Ações registradas</span><strong><?=$stats['actions']?></strong><small>histórico do período</small></div>
 <div class="stat-card"><span>Acordos / promessas</span><strong><?=$stats['agreements']?> / <?=$stats['promises']?></strong><small>negociações registradas</small></div>
 <div class="stat-card"><span>Recuperado</span><strong><?=money($stats['recovered'])?></strong><small><?=number_format($stats['amount_percent'],1,',','.')?>% da meta</small></div>
</div>

<div class="panel-card mb-3">
 <form class="collection-filter-grid collection-actions-filters" method="get" action="<?=APP_URL?>/cobranca-atendimentos.php">
  <div><label class="form-label">Período</label><input class="form-control" name="month" type="month" value="<?=e($month)?>"></div>
  <div><label class="form-label">Resultado</label><select class="form-select" name="result">
   <option value="">Todos os resultados</option><option value="acordo" <?=($_GET['result']??'')==='acordo'?'selected':''?>>Acordo realizado</option><option value="promessa" <?=($_GET['result']??'')==='promessa'?'selected':''?>>Promessa de pagamento</option><option value="pagamento" <?=($_GET['result']??'')==='pagamento'?'selected':''?>>Pagamento recebido</option><option value="falou" <?=($_GET['result']??'')==='falou'?'selected':''?>>Falou com o cliente</option><option value="nao_atendeu" <?=($_GET['result']??'')==='nao_atendeu'?'selected':''?>>Não atendeu</option><option value="sem_previsao" <?=($_GET['result']??'')==='sem_previsao'?'selected':''?>>Sem previsão</option>
  </select></div>
  <?php if(Auth::can('supervisor','admin')):?><div><label class="form-label">Responsável</label><select class="form-select" name="user_id"><option value="0">Toda a cobrança</option><?php foreach($collectors as $c):?><option value="<?=$c['id']?>" <?=$selectedUser===(int)$c['id']?'selected':''?>><?=e($c['name'])?></option><?php endforeach;?></select></div><?php else:?><input type="hidden" name="user_id" value="<?=$selectedUser?>"><?php endif;?>
  <div class="collection-filter-actions"><button class="btn btn-dark" type="submit"><i class="fa-solid fa-filter"></i>Filtrar</button><a class="btn btn-outline-secondary" href="<?=APP_URL?>/cobranca-atendimentos.php"><i class="fa-solid fa-rotate-left"></i>Limpar</a></div>
 </form>
</div>

<div class="panel-card">
 <div class="table-responsive data-table-wrap">
  <table class="table modern-table data-table collection-actions-table mb-0" id="collectionActionsTable"
   data-server-side="1" data-ajax="<?=APP_URL?>/api/collection-actions-table.php?<?=e(http_build_query(['month'=>$month,'result'=>(string)($_GET['result']??''),'user_id'=>$selectedUser]))?>" data-entity="atendimentos" data-page-length="25" data-order-column="0">
   <thead><tr><th>Data</th><th>Cliente</th><th>Resultado</th><th>Realizado por</th><th>Responsável atual</th><th>Promessa</th><th class="text-end">Recebido</th><th>Anotação</th><th class="no-sort"></th></tr></thead><tbody></tbody>
  </table>
 </div>
</div>
<?php include '_footer.php';?>
