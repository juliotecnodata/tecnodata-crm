<?php
require dirname(__DIR__) . '/app/bootstrap.php';
use Tecnodata\CRM\Core\Auth; use Tecnodata\CRM\Core\DB;
Auth::requireLogin(); $u=Auth::user();
$tasks=DB::all("SELECT t.*,c.name,c.uf FROM tasks t JOIN clients c ON c.id=t.client_id WHERE t.user_id=? AND t.status='pending' ORDER BY t.due_at ASC",[$u['id']]);
include '_layout.php';?>
<h1 class="h3 mb-1">Minha agenda</h1><div class="text-secondary mb-4">Somente retornos comerciais. Sem agenda pesada.</div>
<div class="card"><div class="card-body">
<?php if(!$tasks):?><div class="py-5 text-center text-secondary">Nenhum retorno pendente.</div><?php endif;?>
<?php foreach($tasks as $t):$late=strtotime($t['due_at'])<time();?>
<div class="d-flex justify-content-between align-items-center gap-3 border-top py-3">
<div><div class="small <?=$late?'text-danger':'text-secondary'?>"><?=date('d/m/Y H:i',strtotime($t['due_at']))?><?=$late?' • atrasado':''?></div><strong><?=e($t['name'])?></strong><div class="small text-secondary"><?=e($t['uf'])?> • <?=e($t['title'])?></div></div>
<a class="btn btn-dark btn-sm" href="cliente.php?id=<?=$t['client_id']?>">Atender</a></div><?php endforeach;?>
</div></div><?php include '_footer.php';?>
