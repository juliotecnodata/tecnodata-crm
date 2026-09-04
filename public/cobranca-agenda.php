<?php
require dirname(__DIR__).'/app/bootstrap.php';require APP_ROOT.'/app/Support/helpers.php';
use Tecnodata\CRM\Core\Auth;use Tecnodata\CRM\Core\DB;
Auth::requireLogin();if(!Auth::can('collector','supervisor','admin')){http_response_code(403);exit('Sem acesso');}$u=Auth::user();
$sql="SELECT t.*,c.name,c.uf,c.omie_code FROM tasks t JOIN clients c ON c.id=t.client_id WHERE t.status='pending' AND t.title LIKE 'Cobrança:%'";$p=[];
if($u['role']==='collector'){$sql.=" AND t.user_id=?";$p[]=$u['id'];}$sql.=" ORDER BY t.due_at";$tasks=DB::all($sql,$p);
$today=0;$late=0;foreach($tasks as $t){if(substr($t['due_at'],0,10)===date('Y-m-d'))$today++;if(strtotime($t['due_at'])<time())$late++;}
include '_layout.php';?>
<div class="page-heading"><div><div class="eyebrow">AGENDA DE COBRANÇA</div><h1>Agenda de cobrança</h1><p>Aqui ficam apenas os retornos que precisam acontecer em uma data e horário. Itens vencidos aparecem como atrasados e devem ser priorizados.</p></div><a class="btn btn-dark" href="cobranca.php"><i class="fa-solid fa-list"></i>Voltar para cobrança</a></div>
<div class="stat-grid mb-4"><div class="stat-card"><span>Hoje</span><strong><?=$today?></strong><small>retornos programados</small></div><div class="stat-card"><span>Atrasados</span><strong><?=$late?></strong><small>precisam de atenção</small></div><div class="stat-card"><span>Total pendente</span><strong><?=count($tasks)?></strong><small>próximos retornos</small></div></div>
<div class="panel-card"><div class="table-responsive data-table-wrap"><table class="table modern-table data-table mb-0" data-entity="retornos" data-page-length="25" data-order-column="0"><thead><tr><th>Quando</th><th>Cliente</th><th>UF</th><th>Situação</th><th class="no-sort"></th></tr></thead><tbody><?php foreach($tasks as $t):$isLate=strtotime($t['due_at'])<time();?><tr><td data-order="<?=e($t['due_at'])?>"><strong><?=date('d/m/Y H:i',strtotime($t['due_at']))?></strong></td><td><?=e($t['name'])?><small class="table-sub"><?=e($t['omie_code'])?></small></td><td><?=e($t['uf'])?></td><td><?=$isLate?'<span class="status-pill status-overdue">Atrasado</span>':'<span class="status-pill status-partial">Programado</span>'?></td><td><a class="btn btn-dark btn-sm" href="cobranca-cliente.php?id=<?=$t['client_id']?>">Atender</a></td></tr><?php endforeach;?></tbody></table></div></div>
<?php include '_footer.php';?>
