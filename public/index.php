<?php
require dirname(__DIR__) . '/app/bootstrap.php';
use Tecnodata\CRM\Core\Auth;
use Tecnodata\CRM\Core\DB;
Auth::requireLogin(); $currentUser=Auth::user(); if(in_array(($currentUser['role']??''),['supervisor','admin'],true)){header('Location: '.APP_URL.'/gestao.php');exit;} if(($currentUser['role']??'')==='collector'){header('Location: '.APP_URL.'/cobranca.php');exit;} $u=Auth::user();
$homeProfile=$u['role']==='seller'&&!empty($u['seller_omie_code'])?DB::fetch('SELECT goal_mode FROM sellers WHERE omie_code=? AND active=1',[$u['seller_omie_code']]):null;
if(($homeProfile['goal_mode']??'')==='collection'){header('Location: '.APP_URL.'/carteira.php');exit;}
$seller=$u['seller_omie_code'];
$where=$seller?"WHERE m.seller_omie_code=?":"";
$params=$seller?[$seller]:[];
$counts=DB::fetch("SELECT COUNT(*) total,
 SUM(m.commercial_status='attention') attention,
 SUM(m.commercial_status='reactivate') reactivate
 FROM client_metrics m $where",$params)??[];
$today=DB::fetch("SELECT COUNT(*) n FROM tasks WHERE user_id=? AND status='pending' AND due_at < DATE_ADD(CURDATE(),INTERVAL 1 DAY)",[$u['id']])['n']??0;
$late=DB::fetch("SELECT COUNT(*) n FROM tasks WHERE user_id=? AND status='pending' AND due_at < CURDATE()",[$u['id']])['n']??0;
$priority=DB::all("SELECT c.*,m.* FROM client_metrics m JOIN clients c ON c.id=m.client_id $where ORDER BY
 (m.commercial_status='reactivate') DESC,(m.commercial_status='attention') DESC,m.revenue_12m DESC LIMIT 8",$params);
include '_layout.php';?>
<div class="d-flex justify-content-between align-items-end mb-4"><div><h1 class="h3 mb-1">Olá, <?=e(explode(' ',$u['name'])[0])?>.</h1><div class="text-secondary">Sua mesa de trabalho comercial.</div></div></div>
<div class="row g-3 mb-4">
 <div class="col-6 col-xl-3"><div class="card metric"><div class="label">Minha carteira</div><div class="value"><?=number_format((int)($counts['total']??0),0,',','.')?></div></div></div>
 <div class="col-6 col-xl-3"><div class="card metric"><div class="label">Para atenção</div><div class="value"><?=number_format((int)(($counts['attention']??0)+($counts['reactivate']??0)),0,',','.')?></div></div></div>
 <div class="col-6 col-xl-3"><div class="card metric"><div class="label">Retornos hoje</div><div class="value"><?=number_format((int)$today,0,',','.')?></div></div></div>
 <div class="col-6 col-xl-3"><div class="card metric"><div class="label">Atrasados</div><div class="value"><?=number_format((int)$late,0,',','.')?></div></div></div>
</div>
<div class="card"><div class="card-body">
<div class="d-flex justify-content-between align-items-center mb-3"><div><h2 class="h5 mb-0">Para trabalhar agora</h2><small class="text-secondary">Prioridades calculadas com os dados locais.</small></div></div>
<?php if(!$priority):?><div class="text-secondary py-4">Sincronize a Omie para formar sua carteira.</div><?php endif;?>
<?php foreach($priority as $c):?>
<div class="d-flex align-items-center justify-content-between border-top py-3 gap-3">
 <div><strong><?=e($c['name'])?></strong><div class="small text-secondary"><?=e($c['uf'])?> • <?=($c['days_without_purchase']===null?'Sem compra':$c['days_without_purchase'].' dias sem comprar')?> • <?=money($c['revenue_12m'])?> em 12m</div></div>
 <a class="btn btn-dark btn-sm" href="<?=APP_URL?>/cliente.php?id=<?=$c['client_id']?>">Atender</a>
</div>
<?php endforeach;?>
</div></div>
<?php include '_footer.php';?>
