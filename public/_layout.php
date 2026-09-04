<?php
use Tecnodata\CRM\Core\Auth;
use Tecnodata\CRM\Core\DB;
require_once APP_ROOT.'/app/Support/helpers.php';
$u=Auth::user();
$sellerProfile=($u['role']??'')==='seller'&&!empty($u['seller_omie_code'])?DB::fetch('SELECT goal_mode FROM sellers WHERE omie_code=? AND active=1',[$u['seller_omie_code']]):null;
$sellerMode=(string)($sellerProfile['goal_mode']??'sales_collection');
$isCollector=($u['role']??'')==='collector';
$navHasSales=$isCollector?false:(($u['role']??'')!=='seller'||in_array($sellerMode,['sales','sales_collection'],true));
$navHasCollection=$isCollector?true:(($u['role']??'')!=='seller'||in_array($sellerMode,['collection','sales_collection'],true));
$sync=DB::fetch("SELECT * FROM sync_runs WHERE status='success' ORDER BY id DESC LIMIT 1");
$current=basename($_SERVER['PHP_SELF']);
function navActive(string ...$files):string{global $current;return in_array($current,$files,true)?'active':'';}
$roleLabels=['seller'=>'Vendedor','collector'=>'Cobrança','supervisor'=>'Supervisor','admin'=>'Administrador'];
$pageLabels=['index.php'=>'Visão geral','carteira.php'=>'Carteira','agenda.php'=>'Agenda','resultado.php'=>'Meu resultado','pedidos.php'=>'Pedidos','servicos.php'=>'Serviços','equipe.php'=>'Equipe','metas.php'=>'Metas','vendedores.php'=>'Vendedores','vendedor.php'=>'Vendedor','configuracoes.php'=>'Configurações','sync.php'=>'Sincronização','usuarios.php'=>'Usuários','cobranca.php'=>'Cobrança','cobranca-cliente.php'=>'Atendimento de cobrança','cobranca-atendimento.php'=>'Detalhe do atendimento','cobranca-agenda.php'=>'Agenda de cobrança','cobranca-atendimentos.php'=>'Atendimentos de cobrança','cobranca-equipe.php'=>'Desempenho da cobrança','alertas.php'=>'Alertas','gestao.php'=>'Visão de gestão','atendimento-editar.php'=>'Editar atendimento'];
?>
<!doctype html><html lang="pt-br"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="theme-color" content="#121B25"><title><?=e($pageLabels[$current]??$GLOBALS['config']['app']['name'])?> • <?=e($GLOBALS['config']['app']['name'])?></title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.0/css/all.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/3.0.3/css/dataTables.dataTables.min.css" rel="stylesheet">
<link href="<?=APP_URL?>/assets/css/app.css" rel="stylesheet">
</head><body>
<div class="td-shell">
<aside class="td-sidebar">
 <div class="sidebar-head">
 <a class="td-brand" href="<?=APP_URL?>/index.php"><span class="brand-mark">T</span><span class="brand-copy"><strong>Tecnodata</strong><small>Revenue workspace</small></span></a>
 <button class="sidebar-collapse" type="button" id="sidebarCollapse" aria-label="Recolher menu" title="Recolher menu"><i class="fa-solid fa-chevron-left"></i></button>
 </div>
 <nav class="td-nav">
  <div class="nav-label">TRABALHO</div>
  <a class="<?=navActive('index.php')?>" href="<?=APP_URL?>/index.php"><i class="fa-solid fa-grid-2"></i><span>Visão geral</span></a>
  <a class="<?=navActive('carteira.php','cliente.php')?>" href="<?=APP_URL?>/carteira.php"><i class="fa-solid fa-address-book"></i><span><?=$sellerMode==='collection'?'Carteira de cobrança':'Minha carteira'?></span></a>
  <?php if($navHasSales):?><a class="<?=navActive('agenda.php')?>" href="<?=APP_URL?>/agenda.php"><i class="fa-regular fa-calendar-check"></i><span>Minha agenda</span></a><?php endif;?>
  <a class="<?=navActive('resultado.php')?>" href="<?=APP_URL?>/resultado.php"><i class="fa-solid fa-chart-line"></i><span>Meu resultado</span></a>
  <?php if(Auth::can('collector','supervisor','admin')):?><a class="<?=navActive('cobranca.php','cobranca-cliente.php')?>" href="<?=APP_URL?>/cobranca.php"><i class="fa-solid fa-hand-holding-dollar"></i><span>Cobrança</span></a><a class="<?=navActive('cobranca-atendimentos.php','cobranca-atendimento.php','atendimento-editar.php')?>" href="<?=APP_URL?>/cobranca-atendimentos.php"><i class="fa-solid fa-clock-rotate-left"></i><span>Atendimentos</span></a><a class="<?=navActive('cobranca-agenda.php')?>" href="<?=APP_URL?>/cobranca-agenda.php"><i class="fa-regular fa-calendar-check"></i><span>Agenda de cobrança</span></a><?php endif;?>
  <?php if($navHasSales):?>
  <a class="<?=navActive('pedidos.php')?>" href="<?=APP_URL?>/pedidos.php"><i class="fa-solid fa-receipt"></i><span>Pedidos</span></a>
  <a class="<?=navActive('servicos.php')?>" href="<?=APP_URL?>/servicos.php"><i class="fa-solid fa-graduation-cap"></i><span>Serviços</span></a>
  <?php endif;?>
  <?php if(Auth::can('supervisor','admin')):?>
  <div class="nav-label">GESTÃO</div>
  <a class="<?=navActive('equipe.php')?>" href="<?=APP_URL?>/equipe.php"><i class="fa-solid fa-users"></i><span>Equipe</span></a>
  <a class="<?=navActive('vendedores.php','vendedor.php')?>" href="<?=APP_URL?>/vendedores.php"><i class="fa-solid fa-user-tie"></i><span>Vendedores</span></a>
  <a class="<?=navActive('metas.php')?>" href="<?=APP_URL?>/metas.php"><i class="fa-solid fa-bullseye"></i><span>Metas do mês</span></a>
  <a class="<?=navActive('configuracoes.php')?>" href="<?=APP_URL?>/configuracoes.php"><i class="fa-solid fa-sliders"></i><span>Configurações</span></a>
  <?php endif;?>
  <?php if(Auth::can('admin')):?>
  <div class="nav-label">ADMINISTRAÇÃO</div>
  <a class="<?=navActive('sync.php')?>" href="<?=APP_URL?>/sync.php"><i class="fa-solid fa-arrows-rotate"></i><span>Sincronização Omie</span></a>
  <a class="<?=navActive('usuarios.php')?>" href="<?=APP_URL?>/usuarios.php"><i class="fa-solid fa-user-shield"></i><span>Usuários</span></a>
  <?php endif;?>
 </nav>
 <div class="sidebar-status"><span class="pulse-dot"></span><div><strong>Base local ativa</strong><small><?=$sync?'Atualizada em '.date('d/m',strtotime($sync['finished_at'])).' às '.date('H:i',strtotime($sync['finished_at'])):'Aguardando sincronização'?></small></div></div>
</aside>
<main class="td-main">
 <header class="td-topbar">
  <div class="topbar-title"><button class="sidebar-toggle" type="button" aria-label="Abrir menu"><i class="fa-solid fa-bars"></i></button><div><small>TECNODATA CRM</small><strong><?=e($pageLabels[$current]??'Workspace')?></strong></div></div>
  <div class="topbar-actions">
   <div class="sync-chip"><i class="fa-solid fa-cloud-arrow-down"></i><span><?=$sync?'Dados locais atualizados':'Sincronização pendente'?></span></div>
   <div class="notification-wrap">
    <button class="icon-button notification-button" id="notificationBell" type="button" aria-label="Alertas" aria-expanded="false"><i class="fa-regular fa-bell"></i><span class="notification-count d-none" id="notificationCount">0</span></button>
    <div class="notification-panel" id="notificationPanel" hidden>
      <div class="notification-panel-head"><div><strong>Alertas</strong><small id="notificationSummary">Consultando retornos…</small></div><a href="<?=APP_URL?>/alertas.php" title="Configurar alertas"><i class="fa-solid fa-sliders"></i></a></div>
      <div class="notification-list" id="notificationList"><div class="notification-empty">Nenhum retorno pendente.</div></div>
      <div class="notification-panel-foot"><?php if($isCollector):?><a href="<?=APP_URL?>/cobranca-agenda.php">Abrir agenda</a><?php else:?><a href="<?=APP_URL?>/agenda.php">Abrir agenda</a><?php endif;?><button type="button" id="enableAlertsQuick">Ativar notificações</button></div>
    </div>
   </div>
   <div class="user-menu"><span class="avatar avatar-sm"><?=e(mb_strtoupper(mb_substr((string)($u['name']??'U'),0,1)))?></span><span class="user-copy"><strong><?=e($u['name']??'')?></strong><small><?=e($roleLabels[$u['role']??'']??'Usuário')?></small></span><a class="icon-button" href="<?=APP_URL?>/logout.php" title="Sair"><i class="fa-solid fa-arrow-right-from-bracket"></i></a></div>
  </div>
 </header>
 <div class="td-content">
