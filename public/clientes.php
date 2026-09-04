<?php
require dirname(__DIR__).'/app/bootstrap.php';
require APP_ROOT.'/app/Support/helpers.php';

use Tecnodata\CRM\Core\Auth;
use Tecnodata\CRM\Core\DB;

Auth::requireLogin();
if(!Auth::can('supervisor','admin')){http_response_code(403);exit('Sem acesso');}

$month=(string)($_GET['month']??date('Y-m'));
if(!preg_match('/^\d{4}-\d{2}$/',$month))$month=date('Y-m');

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
<div class="page-heading">
 <div><div class="eyebrow">GESTÃO DE CARTEIRAS • <?=date('m/Y',strtotime($month.'-01'))?></div><h1>Clientes</h1>
 <p>Defina a carteira comercial de cada mês. Você pode filtrar um estado, escolher um vendedor e redistribuir os clientes sem alterar o vendedor-base vindo da Omie.</p></div>
 <input class="form-control month-control" id="portfolioMonth" type="month" value="<?=e($month)?>">
</div>

<div class="management-note mb-4"><i class="fa-solid fa-circle-info"></i><div><strong>Como funciona</strong><span>A regra mensal tem prioridade sobre a Omie apenas no mês escolhido. Para começar com a base sem vendedor, filtre uma tag, estado ou qualquer combinação, escolha o vendedor e use <b>Todos filtrados</b>. Assim você distribui centenas de clientes de uma vez.</span></div></div>

<div class="stat-grid stat-grid-4 mb-4">
 <div class="stat-card compact-stat"><span>Clientes ativos</span><strong><?=number_format((int)($stats['total']??0),0,',','.')?></strong><small>base disponível</small></div>
 <div class="stat-card compact-stat"><span>Definidos no mês</span><strong><?=number_format((int)($stats['configured_month']??0),0,',','.')?></strong><small>com regra mensal própria</small></div>
 <div class="stat-card compact-stat"><span>Sem vendedor</span><strong><?=number_format((int)($stats['unassigned']??0),0,',','.')?></strong><small>prioridade para distribuir em massa</small></div>
 <div class="stat-card compact-stat"><span>Estados</span><strong><?=number_format((int)($stats['states']??0),0,',','.')?></strong><small>para gestão regional</small></div>
</div>

<div class="panel-card mb-3">
 <div class="portfolio-filter-grid">
  <div><label class="form-label">Estado</label><select class="form-select" id="portfolioUf"><option value="">Todos</option><?php foreach($ufs as $r):?><option value="<?=e($r['uf'])?>"><?=e($r['uf'])?></option><?php endforeach;?></select></div>
  <div><label class="form-label">Tag do cadastro</label><select class="form-select" id="portfolioTag"><option value="">Todas as tags</option><?php foreach($tags as $t):?><option value="<?=e($t['tag'])?>"><?=e($t['tag'])?> (<?=number_format((int)$t['total'],0,',','.')?>)</option><?php endforeach;?></select></div>
  <div><label class="form-label">Carteira do mês</label><select class="form-select" id="portfolioSellerFilter"><option value="">Todos</option><option value="__unassigned__">Sem vendedor</option><?php foreach($sellers as $s):?><option value="<?=e($s['omie_code'])?>"><?=e($s['name'])?></option><?php endforeach;?></select></div>
  <div><label class="form-label">Situação comercial</label><select class="form-select" id="portfolioStatus"><option value="">Todas</option><option value="normal">Normal</option><option value="attention">Atenção</option><option value="reactivate">Reativar</option></select></div>
  <div><label class="form-label">Financeiro</label><select class="form-select" id="portfolioFinance"><option value="">Todos</option><option value="overdue">Com vencido</option><option value="clear">Sem vencido</option></select></div>
  <div><label class="form-label">Origem</label><select class="form-select" id="portfolioSource"><option value="">Todas</option><option value="month">Definida no mês</option><option value="omie">Vendedor-base Omie</option></select></div>
  <div class="portfolio-filter-actions">
    <button class="btn btn-dark" type="button" id="portfolioApply"><i class="fa-solid fa-filter"></i>Filtrar</button>
    <button class="btn btn-outline-secondary" type="button" id="portfolioClear"><i class="fa-solid fa-rotate-left"></i>Limpar</button>
   </div>
 </div>
</div>

<div class="portfolio-assignment-bar mb-3">
 <div class="portfolio-assignment-copy"><strong>Distribuir carteira</strong><span id="portfolioSelectionInfo">Selecione clientes ou aplique a distribuição ao filtro atual.</span></div>
 <select class="form-select" id="portfolioAssignSeller">
  <option value="">Escolha o vendedor...</option>
  <?php foreach($sellers as $s):?><option value="<?=e($s['omie_code'])?>"><?=e($s['name'])?></option><?php endforeach;?>
  <option value="__unassigned__">Sem vendedor neste mês</option>
  <option value="__omie__">Voltar a usar vendedor-base Omie</option>
 </select>
 <button class="btn btn-outline-secondary" type="button" id="portfolioAssignSelected"><i class="fa-solid fa-user-check"></i>Selecionados</button>
 <button class="btn btn-dark" type="button" id="portfolioAssignFiltered"><i class="fa-solid fa-users-gear"></i>Todos filtrados</button>
</div>

<div class="filter-result-strip mb-2"><span id="portfolioFilterStatus"><i class="fa-solid fa-circle-info"></i>Selecione os filtros e clique em <strong>Filtrar</strong>.</span></div>
<div class="panel-card"><div class="table-responsive data-table-wrap">
<table class="table modern-table data-table portfolio-table mb-0" id="clientsManagementTable"
 data-server-side="1" data-ajax="<?=APP_URL?>/api/clients-table.php" data-entity="clientes" data-page-length="25" data-order-column="1">
<thead><tr>
 <th class="no-sort"><input class="form-check-input" type="checkbox" id="portfolioSelectPage"></th>
 <th>Cliente</th><th>UF / Cidade</th><th>Tags</th><th>Carteira do mês</th><th>Origem</th><th>Vendedor Omie</th>
 <th>Última compra</th><th>Dias</th><th class="text-end">Receita 12m</th><th>Financeiro</th><th>Situação</th><th class="no-sort"></th>
</tr></thead><tbody></tbody></table>
</div></div>
<?php include '_footer.php';?>