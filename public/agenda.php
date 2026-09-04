<?php
require dirname(__DIR__).'/app/bootstrap.php';
require APP_ROOT.'/app/Support/helpers.php';

use Tecnodata\CRM\Core\Auth;
use Tecnodata\CRM\Core\DB;

Auth::requireLogin();
$u=Auth::user();
if(($u['role']??'')==='collector'){header('Location: '.APP_URL.'/cobranca-agenda.php');exit;}

$view=(string)($_GET['view']??'today');
if(!in_array($view,['today','late','next','all'],true))$view='today';

$counts=DB::fetch("SELECT
 SUM(due_at<NOW()) late_count,
 SUM(due_at>=CURDATE() AND due_at<DATE_ADD(CURDATE(),INTERVAL 1 DAY)) today_count,
 SUM(due_at>=DATE_ADD(CURDATE(),INTERVAL 1 DAY)) next_count
 FROM tasks WHERE user_id=? AND status='pending'",[(int)$u['id']])??[];

$where="t.user_id=? AND t.status='pending'";
$params=[(int)$u['id']];
if($view==='late')$where.=" AND t.due_at<NOW()";
elseif($view==='today')$where.=" AND t.due_at>=CURDATE() AND t.due_at<DATE_ADD(CURDATE(),INTERVAL 1 DAY)";
elseif($view==='next')$where.=" AND t.due_at>=DATE_ADD(CURDATE(),INTERVAL 1 DAY)";

$tasks=DB::all("SELECT t.*,c.name,c.uf,c.phone,m.last_purchase_at,m.revenue_12m
               FROM tasks t
               JOIN clients c ON c.id=t.client_id
               LEFT JOIN client_metrics m ON m.client_id=c.id
               WHERE $where ORDER BY t.due_at ASC",$params);

include '_layout.php';?>
<div class="page-heading">
 <div><div class="eyebrow">AGENDA</div><h1>Retornos</h1><p>Somente o que exige ação. Abra o cliente, faça o contato e registre o resultado.</p></div>
 <a class="btn btn-primary" href="<?=APP_URL?>/carteira.php"><i class="fa-solid fa-users"></i>Clientes</a>
</div>

<div class="agenda-tabs mb-3">
 <a class="<?=$view==='today'?'active':''?>" href="?view=today"><span>Hoje</span><strong><?=number_format((int)($counts['today_count']??0),0,',','.')?></strong></a>
 <a class="<?=$view==='late'?'active is-danger':''?>" href="?view=late"><span>Atrasados</span><strong><?=number_format((int)($counts['late_count']??0),0,',','.')?></strong></a>
 <a class="<?=$view==='next'?'active':''?>" href="?view=next"><span>Próximos</span><strong><?=number_format((int)($counts['next_count']??0),0,',','.')?></strong></a>
 <a class="<?=$view==='all'?'active':''?>" href="?view=all"><span>Todos</span><i class="fa-solid fa-list"></i></a>
</div>

<div class="panel-card">
 <div class="agenda-list">
 <?php foreach($tasks as $t):$late=strtotime((string)$t['due_at'])<time();?>
  <a class="agenda-row <?=$late?'is-late':''?>" href="<?=APP_URL?>/cliente.php?id=<?=$t['client_id']?>">
   <div class="agenda-time"><strong><?=date('H:i',strtotime((string)$t['due_at']))?></strong><small><?=date('d/m',strtotime((string)$t['due_at']))?></small></div>
   <div class="agenda-client"><strong><?=e($t['name'])?></strong><small><?=e($t['uf']?:'—')?> • <?=e($t['title'])?></small></div>
   <div class="agenda-context"><strong><?=money($t['revenue_12m']??0)?></strong><small>receita 12m</small></div>
   <span class="agenda-action"><?=$late?'Atrasado':'Atender'?><i class="fa-solid fa-arrow-right"></i></span>
  </a>
 <?php endforeach;?>
 <?php if(!$tasks):?><div class="empty-state"><i class="fa-regular fa-circle-check"></i><h2>Nada pendente aqui</h2><p>Você não possui retornos neste filtro.</p></div><?php endif;?>
 </div>
</div>
<?php include '_footer.php';?>