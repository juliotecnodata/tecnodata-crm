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
$viewRaw=(string)($_GET['view']??'open');
$signalRaw=(string)($_GET['signal']??'all');
$view=in_array($viewRaw,['open','all','settled'],true)?$viewRaw:'open';
$signal=in_array($signalRaw,['all','mine','attended','agreement','promise','unattended'],true)?$signalRaw:'all';

$summary=CollectionService::portfolioSummary();
$work=CollectionService::monthForUser((int)$u['id'],$month);
$sellers=DB::all("SELECT omie_code,name FROM sellers WHERE active=1 ORDER BY name");
$ufs=DB::all("SELECT DISTINCT uf FROM clients WHERE uf IS NOT NULL AND uf<>'' ORDER BY uf");

include '_layout.php';?>
<div class="page-heading">
 <div><div class="eyebrow">COBRANÇA</div><h1>Cobrança</h1><p>Aqui você encontra os clientes com pendências financeiras e decide quem precisa ser atendido agora. Use os filtros para localizar acordos, promessas, clientes já trabalhados ou ainda sem contato.</p></div>
 <div class="d-flex gap-2 flex-wrap">
   <a class="btn btn-outline-secondary" href="cobranca-agenda.php"><i class="fa-regular fa-calendar-check"></i>Agenda</a>
   <button class="btn btn-dark" type="button" id="showMyWork"><i class="fa-solid fa-user-check"></i>Meus atendimentos</button>
   <?php if(Auth::can('supervisor','admin')):?><a class="btn btn-outline-secondary" href="cobranca-equipe.php"><i class="fa-solid fa-chart-line"></i>Desempenho</a><?php endif;?>
 </div>
</div>

<div class="stat-grid stat-grid-4 mb-4">
 <div class="stat-card"><span>Em cobrança</span><strong><?=number_format($summary['open_clients'],0,',','.')?></strong><small>clientes com saldo pendente</small></div>
 <div class="stat-card"><span>Saldo a recuperar</span><strong><?=money($summary['amount'])?></strong><small>já considerando recebimentos locais</small></div>
 <div class="stat-card"><span>Meus atendimentos</span><strong><?=$work['worked']?></strong><small><?=$work['agreements']?> acordos • <?=$work['promises']?> promessas</small></div>
 <div class="stat-card"><span>Recuperado por mim</span><strong><?=money($work['recovered'])?></strong><small><?=number_format($work['amount_percent'],1,',','.')?>% da minha meta no mês</small></div>
</div>

<div class="panel-card mb-3">
 <div class="collection-filter-head">
  <div class="segmented-control" id="collectionView">
   <button type="button" data-value="open" class="<?=$view==='open'?'active':''?>">Pendentes</button>
   <button type="button" data-value="all" class="<?=$view==='all'?'active':''?>">Todos</button>
   <button type="button" data-value="settled" class="<?=$view==='settled'?'active':''?>">Quitados</button>
  </div>
  <div class="small text-secondary"><i class="fa-solid fa-database me-1"></i>Dados locais • nenhuma consulta ao Omie ao filtrar</div>
 </div>
 <div class="collection-filter-grid">
  <div><label class="form-label">Atendimento</label><select class="form-select" id="collectionSignal">
   <option value="all" <?=$signal==='all'?'selected':''?>>Todos</option>
   <option value="mine" <?=$signal==='mine'?'selected':''?>>Atendidos por mim</option>
   <option value="attended" <?=$signal==='attended'?'selected':''?>>Atendidos por qualquer pessoa</option>
   <option value="agreement" <?=$signal==='agreement'?'selected':''?>>Acordo fechado</option>
   <option value="promise" <?=$signal==='promise'?'selected':''?>>Promessa de pagamento</option>
   <option value="unattended" <?=$signal==='unattended'?'selected':''?>>Sem atendimento no período</option>
  </select></div>
  <div><label class="form-label">Período do atendimento</label><input class="form-control" id="collectionMonth" type="month" value="<?=e($month)?>"></div>
  <div><label class="form-label">Vendedor</label><select class="form-select" id="collectionSeller"><option value="">Todos</option><?php foreach($sellers as $s):?><option value="<?=e($s['omie_code'])?>"><?=e($s['name'])?></option><?php endforeach;?></select></div>
  <div><label class="form-label">UF</label><select class="form-select" id="collectionUf"><option value="">Todas</option><?php foreach($ufs as $row):?><option value="<?=e($row['uf'])?>"><?=e($row['uf'])?></option><?php endforeach;?></select></div>
  <div class="collection-filter-actions"><button class="btn btn-dark" type="button" id="collectionApply"><i class="fa-solid fa-filter"></i>Filtrar</button><button class="btn btn-outline-secondary" type="button" id="collectionClear"><i class="fa-solid fa-rotate-left"></i>Limpar filtros</button></div>
 </div>
</div>

<div class="panel-card">
 <div class="table-responsive data-table-wrap">
  <table class="table modern-table data-table collection-table mb-0"
         id="collectionTable"
         data-server-side="1"
         data-ajax="<?=APP_URL?>/api/collection-table.php"
         data-entity="clientes"
         data-page-length="25"
         data-order-column="8">
   <thead><tr>
    <th>Cliente</th><th>Vendedor</th><th>UF</th><th>Última compra</th><th>Dias</th>
    <th>Financeiro</th><th class="text-end">Valor devido</th><th>Sinalização</th>
    <th>Último contato</th><th class="no-sort"></th>
   </tr></thead><tbody></tbody>
  </table>
 </div>
</div>
<?php include '_footer.php';?>
