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

<div class="collection-workbench mb-4">
 <div class="collection-workbench-main">
  <span>SALDO ATUAL DA CARTEIRA</span>
  <strong><?=money($summary['amount'])?></strong>
  <small><?=number_format($summary['open_clients'],0,',','.')?> clientes com saldo pendente nas contas selecionadas</small>
 </div>
 <div class="collection-workbench-card is-warning"><span>Pagamento parcial</span><strong><?=number_format($summary['partial_clients']??0,0,',','.')?></strong><small><?=money($summary['partial_paid']??0)?> já recebido na Omie</small></div>
 <div class="collection-workbench-card is-success"><span>Meu recuperado</span><strong><?=money($work['recovered'])?></strong><small><?=number_format($work['amount_percent'],1,',','.')?>% da meta do mês</small></div>
 <div class="collection-workbench-card is-info"><span>Clientes trabalhados</span><strong><?=$work['worked']?></strong><small><?=$work['agreements']?> acordo(s) • <?=$work['promises']?> promessa(s)</small></div>
</div>

<div class="collection-toolbar mb-3">
 <div class="collection-quick-filters">
  <a class="<?=$view==='open'&&$signal==='all'&&$financialFilter==='all'?'active':''?>" href="<?=APP_URL?>/cobranca.php?view=open">Pendentes</a>
  <a class="<?=$financialFilter==='partial'?'active':''?>" href="<?=APP_URL?>/cobranca.php?view=open&financial=partial">Parciais</a>
  <a class="<?=$signal==='agreement'?'active':''?>" href="<?=APP_URL?>/cobranca.php?view=all&signal=agreement&month=<?=e($month)?>">Acordos</a>
  <a class="<?=$signal==='paid_agreement'?'active':''?>" href="<?=APP_URL?>/cobranca.php?view=all&signal=paid_agreement&month=<?=e($month)?>">Acordos pagos</a>
  <a class="<?=$view==='settled'?'active':''?>" href="<?=APP_URL?>/cobranca.php?view=settled">Quitados</a>
 </div>

 <form method="get" action="<?=APP_URL?>/cobranca.php" class="collection-search-filters">
  <input type="hidden" name="view" value="<?=e($view)?>">
  <div><label class="form-label">Atendimento</label><select class="form-select" name="signal">
   <option value="all" <?=$signal==='all'?'selected':''?>>Todos</option>
   <option value="mine" <?=$signal==='mine'?'selected':''?>>Meus / atribuídos a mim</option>
   <option value="unattended" <?=$signal==='unattended'?'selected':''?>>Não trabalhados</option>
   <option value="promise" <?=$signal==='promise'?'selected':''?>>Promessas</option>
   <option value="agreement" <?=$signal==='agreement'?'selected':''?>>Acordos</option>
   <option value="paid_agreement" <?=$signal==='paid_agreement'?'selected':''?>>Acordos pagos</option>
   <option value="payment" <?=$signal==='payment'?'selected':''?>>Pagamentos</option>
  </select></div>
  <div><label class="form-label">Conta</label><select class="form-select" name="account"><option value="">Todas</option><?php foreach($accounts as $a):?><option value="<?=e($a['omie_code'])?>" <?=$accountFilter===(string)$a['omie_code']?'selected':''?>><?=e($a['name'])?></option><?php endforeach;?></select></div>
  <button class="btn btn-primary" type="submit"><i class="fa-solid fa-filter"></i>Aplicar</button>
  <details class="collection-more-filters" <?=($sellerFilter!==''||$ufFilter!==''||$financialFilter!=='all'||$month!==date('Y-m'))?'open':''?>>
   <summary>Mais filtros</summary>
   <div class="collection-more-grid">
    <div><label class="form-label">Período</label><input class="form-control" name="month" type="month" value="<?=e($month)?>"></div>
    <div><label class="form-label">Vendedor da dívida</label><select class="form-select" name="seller"><option value="">Todos</option><?php foreach($sellers as $s):?><option value="<?=e($s['omie_code'])?>" <?=$sellerFilter===(string)$s['omie_code']?'selected':''?>><?=e($s['name'])?></option><?php endforeach;?></select></div>
    <div><label class="form-label">Financeiro</label><select class="form-select" name="financial"><option value="all" <?=$financialFilter==='all'?'selected':''?>>Todos</option><option value="overdue" <?=$financialFilter==='overdue'?'selected':''?>>Vencido</option><option value="partial" <?=$financialFilter==='partial'?'selected':''?>>Pagamento parcial</option></select></div>
    <div><label class="form-label">UF</label><select class="form-select" name="uf"><option value="">Todas</option><?php foreach($ufs as $row):?><option value="<?=e($row['uf'])?>" <?=$ufFilter===(string)$row['uf']?'selected':''?>><?=e($row['uf'])?></option><?php endforeach;?></select></div>
    <a class="btn btn-outline-secondary" href="<?=APP_URL?>/cobranca.php">Limpar filtros</a>
   </div>
  </details>
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
