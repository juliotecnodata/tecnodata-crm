<?php
require dirname(__DIR__).'/app/bootstrap.php';
require APP_ROOT.'/app/Support/helpers.php';

use Tecnodata\CRM\Core\Auth;
use Tecnodata\CRM\Core\DB;

Auth::requireLogin();
if(!Auth::can('supervisor','admin')){http_response_code(403);exit('Sem acesso');}

$month=(string)($_GET['month']??date('Y-m'));
if(!preg_match('/^\d{4}-\d{2}$/',$month))$month=date('Y-m');
$uf=strtoupper(trim((string)($_GET['uf']??'')));
$tag=trim((string)($_GET['tag']??''));
$sellerFilter=trim((string)($_GET['seller']??''));
$statusFilter=trim((string)($_GET['status']??''));
$financeFilter=trim((string)($_GET['finance']??''));

$sellers=DB::all("SELECT omie_code,name FROM sellers WHERE active=1 AND is_virtual=0 ORDER BY name");
$ufs=DB::all("SELECT DISTINCT uf FROM clients WHERE active=1 AND uf IS NOT NULL AND uf<>'' ORDER BY uf");
$tags=DB::all("SELECT tag,COUNT(*) total FROM client_tags GROUP BY tag ORDER BY tag");
$stats=DB::fetch("SELECT COUNT(*) total,
 SUM(pa.id IS NOT NULL) configured_month,
 SUM(CASE WHEN (CASE WHEN pa.id IS NOT NULL THEN pa.seller_omie_code ELSE m.seller_omie_code END) IS NULL THEN 1 ELSE 0 END) unassigned,
 COUNT(DISTINCT c.uf) states
 FROM clients c
 LEFT JOIN client_metrics m ON m.client_id=c.id
 LEFT JOIN client_portfolio_assignments pa ON pa.client_id=c.id AND pa.month_ref=?
 WHERE c.active=1",[$month])??[];

include '_layout.php';?>
<?php if(isset($_GET['deleted'])):?><div class="alert alert-success alert-modern"><i class="fa-solid fa-circle-check"></i>Cliente excluído da Omie e desativado no CRM.</div><?php endif;?>
<div class="page-heading">
 <div><div class="eyebrow">CLIENTES • <?=date('m/Y',strtotime($month.'-01'))?></div><h1>Clientes</h1><p>Consulte a base e distribua a carteira mensal sem alterar o cadastro da Omie.</p></div>
 <div class="d-flex align-items-center gap-2 flex-wrap"><a class="btn btn-primary" href="<?=APP_URL?>/cliente-cadastro.php"><i class="fa-solid fa-user-plus"></i>Novo cliente</a><div class="compact-page-stats"><span><strong><?=number_format((int)($stats['total']??0),0,',','.')?></strong><small>ativos</small></span><span class="<?=($stats['unassigned']??0)>0?'is-alert':''?>"><strong><?=number_format((int)($stats['unassigned']??0),0,',','.')?></strong><small>sem vendedor</small></span></div></div>
</div>

<div class="panel-card mb-3">
 <form class="portfolio-filters-form" method="get" action="<?=APP_URL?>/clientes.php">
  <div><label class="form-label">Mês</label><input class="form-control" type="month" name="month" value="<?=e($month)?>"></div>
  <div><label class="form-label">Estado</label><select class="form-select" name="uf"><option value="">Todos</option><?php foreach($ufs as $r):?><option value="<?=e($r['uf'])?>" <?=$uf===$r['uf']?'selected':''?>><?=e($r['uf'])?></option><?php endforeach;?></select></div>
  <div><label class="form-label">Tag do cadastro</label><select class="form-select" name="tag"><option value="">Todas as tags</option><?php foreach($tags as $t):?><option value="<?=e($t['tag'])?>" <?=$tag===$t['tag']?'selected':''?>><?=e($t['tag'])?> (<?=number_format((int)$t['total'],0,',','.')?>)</option><?php endforeach;?></select></div>
  <div><label class="form-label">Carteira do mês</label><select class="form-select" name="seller"><option value="">Todos</option><option value="__unassigned__" <?=$sellerFilter==='__unassigned__'?'selected':''?>>Sem vendedor</option><option value="__assigned__" <?=$sellerFilter==='__assigned__'?'selected':''?>>Com vendedor</option><?php foreach($sellers as $s):?><option value="<?=e($s['omie_code'])?>" <?=$sellerFilter===$s['omie_code']?'selected':''?>><?=e($s['name'])?></option><?php endforeach;?></select></div>
  <div><label class="form-label">Situação comercial</label><select class="form-select" name="status"><option value="">Todas</option><option value="normal" <?=$statusFilter==='normal'?'selected':''?>>Normal</option><option value="attention" <?=$statusFilter==='attention'?'selected':''?>>Atenção</option><option value="reactivate" <?=$statusFilter==='reactivate'?'selected':''?>>Reativar</option></select></div>
  <div><label class="form-label">Financeiro</label><select class="form-select" name="finance"><option value="">Todos</option><option value="overdue" <?=$financeFilter==='overdue'?'selected':''?>>Com vencido</option><option value="open" <?=$financeFilter==='open'?'selected':''?>>Em aberto</option><option value="clear" <?=$financeFilter==='clear'?'selected':''?>>Em dia</option></select></div>
  <div class="portfolio-filter-actions"><button class="btn btn-dark" type="submit"><i class="fa-solid fa-filter"></i>Filtrar</button><a class="btn btn-outline-secondary" href="<?=APP_URL?>/clientes.php?month=<?=e($month)?>"><i class="fa-solid fa-rotate-left"></i>Limpar</a></div>
 </form>
</div>

<div class="portfolio-assignment-bar mb-3">
 <div class="portfolio-assignment-copy"><strong>Distribuir</strong><span id="portfolioSelectionInfo">Selecione clientes ou use todos os filtrados.</span></div>
 <select class="form-select" id="portfolioAssignSeller">
  <option value="">Escolha o vendedor...</option>
  <?php foreach($sellers as $s):?><option value="<?=e($s['omie_code'])?>"><?=e($s['name'])?></option><?php endforeach;?>
  <option value="__unassigned__">Sem vendedor neste mês</option>
  <option value="__omie__">Voltar a usar vendedor-base Omie</option>
 </select>
 <button class="btn btn-outline-secondary" type="button" id="portfolioAssignSelected"><i class="fa-solid fa-user-check"></i>Selecionados</button>
 <button class="btn btn-dark" type="button" id="portfolioAssignFiltered"><i class="fa-solid fa-users-gear"></i>Aplicar aos filtrados</button>
</div>

<input type="hidden" id="portfolioMonth" value="<?=e($month)?>">
<input type="hidden" id="portfolioUf" value="<?=e($uf)?>">
<input type="hidden" id="portfolioTag" value="<?=e($tag)?>">
<input type="hidden" id="portfolioSellerFilter" value="<?=e($sellerFilter)?>">
<input type="hidden" id="portfolioStatus" value="<?=e($statusFilter)?>">
<input type="hidden" id="portfolioFinance" value="<?=e($financeFilter)?>">
<div class="panel-card"><div class="table-responsive data-table-wrap">
<table class="table modern-table data-table portfolio-table mb-0" id="clientsManagementTable"
 data-server-side="1" data-ajax="<?=APP_URL?>/api/clients-table.php?<?=e(http_build_query(['month'=>$month,'uf'=>$uf,'tag'=>$tag,'seller'=>$sellerFilter,'status'=>$statusFilter,'finance'=>$financeFilter]))?>" data-entity="clientes" data-page-length="25" data-order-column="1" data-order-dir="asc">
<thead><tr>
 <th class="no-sort"><input class="form-check-input" type="checkbox" id="portfolioSelectPage"></th>
 <th>Cliente</th><th>Local</th><th>Tags</th><th>Responsável</th>
 <th>Última compra</th><th>Dias</th><th class="text-end">Receita 12m</th><th>Financeiro</th><th>Situação</th><th class="no-sort"></th>
</tr></thead><tbody></tbody></table>
</div></div>
<?php include '_footer.php';?>