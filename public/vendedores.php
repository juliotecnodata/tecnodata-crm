<?php
require dirname(__DIR__).'/app/bootstrap.php';
require APP_ROOT.'/app/Support/helpers.php';
use Tecnodata\CRM\Core\Auth;
use Tecnodata\CRM\Core\DB;
use Tecnodata\CRM\Services\GoalService;

Auth::requireLogin();
if(!Auth::can('supervisor','admin')){http_response_code(403);exit;}
$month=$_GET['month']??date('Y-m');
$filter=$_GET['status']??'active';
$where=$filter==='all'?'1=1':($filter==='inactive'?'s.active=0':'s.active=1');
$sellers=DB::all("SELECT s.*,
 (SELECT u.name FROM users u WHERE u.seller_omie_code=s.omie_code AND u.active=1 ORDER BY u.id LIMIT 1) user_name,
 (SELECT COUNT(*) FROM client_metrics cm
   LEFT JOIN client_portfolio_assignments pa ON pa.client_id=cm.client_id AND pa.month_ref=?
   WHERE (CASE WHEN pa.id IS NOT NULL THEN pa.seller_omie_code ELSE cm.seller_omie_code END)=s.omie_code) portfolio_count
 FROM sellers s WHERE {$where} ORDER BY s.active DESC,s.name",[$month]);
$counts=DB::fetch("SELECT COUNT(*) total,SUM(active=1) active_count,SUM(active=0) inactive_count,SUM(is_virtual=1) virtual_count FROM sellers")??[];
include '_layout.php';?>
<div class="page-heading">
 <div><div class="eyebrow">GESTÃO COMERCIAL</div><h1>Vendedores</h1><p>Perfis, metas e tamanho da carteira do mês. A distribuição de clientes é administrada na tela Clientes.</p></div>
 <div class="d-flex gap-2"><a class="btn btn-outline-secondary" href="<?=APP_URL?>/clientes.php?month=<?=e($month)?>"><i class="fa-solid fa-address-book"></i>Gerenciar carteiras</a><a class="btn btn-primary" href="<?=APP_URL?>/configuracoes.php"><i class="fa-solid fa-sliders"></i>Configurar financeiro</a></div>
</div>
<div class="stat-grid stat-grid-4 mb-4">
 <div class="stat-card"><span>Total</span><strong><?=number_format((int)($counts['total']??0),0,',','.')?></strong><small>sincronizados da Omie</small></div>
 <div class="stat-card"><span>Ativos no CRM</span><strong><?=number_format((int)($counts['active_count']??0),0,',','.')?></strong><small>com acesso às metas</small></div>
 <div class="stat-card"><span>Inativos</span><strong><?=number_format((int)($counts['inactive_count']??0),0,',','.')?></strong><small>fora da operação atual</small></div>
 <div class="stat-card"><span>Virtuais</span><strong><?=number_format((int)($counts['virtual_count']??0),0,',','.')?></strong><small>pedidos vindos de integrações</small></div>
</div>
<div class="toolbar-card mb-4">
 <div class="segmented-control">
  <a class="<?=$filter==='active'?'active':''?>" href="?status=active&month=<?=e($month)?>">Ativos</a>
  <a class="<?=$filter==='inactive'?'active':''?>" href="?status=inactive&month=<?=e($month)?>">Inativos</a>
  <a class="<?=$filter==='all'?'active':''?>" href="?status=all&month=<?=e($month)?>">Todos</a>
 </div>
 <input class="form-control search-control" id="sellerSearch" placeholder="Buscar vendedor…" autocomplete="off">
 <input class="form-control month-control" type="month" value="<?=e($month)?>" onchange="location.href='?status=<?=e($filter)?>&month='+this.value">
</div>
<div class="seller-grid" id="sellerGrid">
<?php foreach($sellers as $seller):$goal=GoalService::sellerMonth((string)$seller['omie_code'],$month);$collection=$seller['goal_mode']==='collection';$modeLabel=['sales'=>'Somente vendas','collection'=>'Somente cobrança','sales_collection'=>'Vendas + cobrança'][$seller['goal_mode']]??'Perfil';?>
 <a class="seller-card" data-search="<?=e(mb_strtolower($seller['name']))?>" href="<?=APP_URL?>/vendedor.php?id=<?=$seller['id']?>&month=<?=e($month)?>">
  <div class="seller-card-top"><div class="avatar"><?=e(mb_strtoupper(mb_substr($seller['name'],0,1)))?></div><span class="status-dot <?=$seller['active']?'is-active':'is-inactive'?>"><?=$seller['active']?'Ativo':'Inativo'?></span></div>
  <h2><?=e($seller['name'])?></h2><p><?=e($seller['is_virtual']?'Operação virtual / integração':($seller['user_name']??'Sem usuário vinculado'))?></p>
  <div class="seller-meta"><span><i class="fa-solid <?=$collection?'fa-hand-holding-dollar':'fa-chart-line'?>"></i> <?=e($modeLabel)?></span><span><?=number_format((int)$seller['portfolio_count'],0,',','.')?> clientes</span></div>
  <div class="mini-progress"><span style="width:<?=min(100,$collection?$goal['collection_percent']:$goal['percent'])?>%"></span></div>
  <div class="seller-card-footer"><strong><?=$collection?money($goal['collection_realized']):money($goal['realized'])?></strong><span>Ver detalhes <i class="fa-solid fa-arrow-right"></i></span></div>
 </a>
<?php endforeach;?>
<?php if(!$sellers):?><div class="empty-state"><i class="fa-regular fa-user"></i><h2>Nenhum vendedor neste filtro</h2><p>Altere o filtro acima para visualizar outros perfis.</p></div><?php endif;?>
</div>
<script>document.getElementById('sellerSearch').addEventListener('input',function(){const q=this.value.toLocaleLowerCase('pt-BR');document.querySelectorAll('.seller-card').forEach(card=>card.hidden=!card.dataset.search.includes(q));});</script>
<?php include '_footer.php';?>
