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
$signal=in_array($signalRaw,['all','mine','attended','agreement','promise','payment','paid_agreement','unattended'],true)?$signalRaw:'all';
$sellerFilter=trim((string)($_GET['seller']??''));
$accountFilter=trim((string)($_GET['account']??''));
$financialFilter=trim((string)($_GET['financial']??'all'));
if(!in_array($financialFilter,['all','overdue','partial'],true))$financialFilter='all';
$ufFilter=strtoupper(trim((string)($_GET['uf']??'')));

$summary=CollectionService::portfolioSummary();
$work=CollectionService::monthForUser((int)$u['id'],$month);
$sellers=DB::all("SELECT omie_code,name FROM sellers WHERE active=1 ORDER BY name");
$accounts=DB::all("SELECT omie_code,name FROM financial_accounts WHERE selected=1 AND active=1 ORDER BY name");
$ufs=DB::all("SELECT DISTINCT uf FROM clients WHERE uf IS NOT NULL AND uf<>'' ORDER BY uf");

include '_layout.php';?>
<div class="page-heading">
 <div><div class="eyebrow">COBRANÇA</div><h1>Cobrança</h1><p>Aqui você encontra os clientes com pendências financeiras e decide quem precisa ser atendido agora. Use os filtros para localizar acordos, promessas, clientes já trabalhados ou ainda sem contato.</p></div>
 <div class="d-flex gap-2 flex-wrap">
   <a class="btn btn-outline-secondary" href="cobranca-agenda.php"><i class="fa-regular fa-calendar-check"></i>Agenda</a>
   <a class="btn btn-dark" href="<?=APP_URL?>/cobranca.php?<?=e(http_build_query(array_merge($_GET,['signal'=>'mine'])))?>"><i class="fa-solid fa-user-check"></i>Meus atendimentos</a>
   <?php if(Auth::can('supervisor','admin')):?><a class="btn btn-outline-secondary" href="cobranca-equipe.php"><i class="fa-solid fa-chart-line"></i>Desempenho</a><?php endif;?>
 </div>
</div>

<div class="stat-grid stat-grid-4 mb-4">
 <div class="stat-card"><span>Em cobrança</span><strong><?=number_format($summary['open_clients'],0,',','.')?></strong><small>clientes com saldo pendente</small></div>
 <div class="stat-card"><span>Saldo a recuperar</span><strong><?=money($summary['amount'])?></strong><small>já considerando recebimentos locais</small></div>
 <div class="stat-card"><span>Pagamento parcial</span><strong><?=number_format($summary['partial_clients']??0,0,',','.')?></strong><small><?=money($summary['partial_paid']??0)?> já recebido na Omie</small></div>
 <div class="stat-card"><span>Recuperado por mim</span><strong><?=money($work['recovered'])?></strong><small><?=$work['worked']?> clientes trabalhados • <?=number_format($work['amount_percent'],1,',','.')?>% da meta</small></div>
</div>

<div class="panel-card mb-3">
 <div class="collection-filter-head">
  <div class="segmented-control" id="collectionView">
   <a href="<?=APP_URL?>/cobranca.php?<?=e(http_build_query(array_merge($_GET,['view'=>'open'])))?>" class="<?=$view==='open'?'active':''?>">Pendentes</a>
   <a href="<?=APP_URL?>/cobranca.php?<?=e(http_build_query(array_merge($_GET,['view'=>'all'])))?>" class="<?=$view==='all'?'active':''?>">Todos</a>
   <a href="<?=APP_URL?>/cobranca.php?<?=e(http_build_query(array_merge($_GET,['view'=>'settled'])))?>" class="<?=$view==='settled'?'active':''?>">Quitados</a>
  </div>
  <div class="small text-secondary"><i class="fa-solid fa-database me-1"></i>Dados locais • nenhuma consulta ao Omie ao filtrar</div>
 </div>
 <form class="collection-filter-grid" method="get" action="<?=APP_URL?>/cobranca.php">
  <input type="hidden" name="view" value="<?=e($view)?>">
  <div><label class="form-label">Atendimento</label><select class="form-select" name="signal">
   <option value="all" <?=$signal==='all'?'selected':''?>>Todos</option>
   <option value="mine" <?=$signal==='mine'?'selected':''?>>Meus atendimentos / atribuídos a mim</option>
   <option value="attended" <?=$signal==='attended'?'selected':''?>>Atendidos por qualquer pessoa</option>
   <option value="agreement" <?=$signal==='agreement'?'selected':''?>>Acordo fechado</option>
   <option value="paid_agreement" <?=$signal==='paid_agreement'?'selected':''?>>Acordo pago</option>
   <option value="payment" <?=$signal==='payment'?'selected':''?>>Pagamento recebido</option>
   <option value="promise" <?=$signal==='promise'?'selected':''?>>Promessa de pagamento</option>
   <option value="unattended" <?=$signal==='unattended'?'selected':''?>>Sem atendimento no período</option>
  </select></div>
  <div><label class="form-label">Período do atendimento</label><input class="form-control" name="month" type="month" value="<?=e($month)?>"></div>
  <div><label class="form-label">Vendedor da dívida</label><select class="form-select" name="seller"><option value="">Todos</option><?php foreach($sellers as $s):?><option value="<?=e($s['omie_code'])?>" <?=$sellerFilter===(string)$s['omie_code']?'selected':''?>><?=e($s['name'])?></option><?php endforeach;?></select></div>
  <div><label class="form-label">Conta corrente</label><select class="form-select" name="account"><option value="">Todas</option><?php foreach($accounts as $a):?><option value="<?=e($a['omie_code'])?>" <?=$accountFilter===(string)$a['omie_code']?'selected':''?>><?=e($a['name'])?></option><?php endforeach;?></select></div>
  <div><label class="form-label">Situação financeira</label><select class="form-select" name="financial"><option value="all" <?=$financialFilter==='all'?'selected':''?>>Todas</option><option value="overdue" <?=$financialFilter==='overdue'?'selected':''?>>Vencido</option><option value="partial" <?=$financialFilter==='partial'?'selected':''?>>Pagamento parcial</option></select></div>
  <div><label class="form-label">UF</label><select class="form-select" name="uf"><option value="">Todas</option><?php foreach($ufs as $row):?><option value="<?=e($row['uf'])?>" <?=$ufFilter===(string)$row['uf']?'selected':''?>><?=e($row['uf'])?></option><?php endforeach;?></select></div>
  <div class="collection-filter-actions">
   <button class="btn btn-dark" type="submit"><i class="fa-solid fa-filter"></i>Filtrar</button>
   <a class="btn btn-outline-secondary" href="<?=APP_URL?>/cobranca.php"><i class="fa-solid fa-rotate-left"></i>Limpar</a>
  </div>
 </form>
</div>

<div class="panel-card">
 <div class="table-responsive data-table-wrap">
  <table class="table modern-table data-table collection-table mb-0"
         id="collectionTable"
         data-server-side="1"
         data-ajax="<?=APP_URL?>/api/collection-table.php?<?=e(http_build_query(['view'=>$view,'signal'=>$signal,'month'=>$month,'seller'=>$sellerFilter,'account'=>$accountFilter,'financial'=>$financialFilter,'uf'=>$ufFilter]))?>"
         data-entity="clientes"
         data-page-length="25"
         data-order-column="9">
   <thead><tr>
    <th>Cliente</th><th>Vendedor da dívida</th><th>Conta corrente</th><th>UF</th><th>Última compra</th><th>Atraso</th>
    <th>Financeiro</th><th class="text-end">Valor devido</th><th>Sinalização</th>
    <th>Último contato</th><th class="no-sort"></th>
   </tr></thead><tbody></tbody>
  </table>
 </div>
</div>
<?php include '_footer.php';?>
