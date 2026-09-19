<?php use Tecnodata\Lms\Core\Csrf; ?>
<div class="page-heading"><div><span class="eyebrow">EQUIPE</span><h1>Usuários internos</h1><p>Administradores, gestores, professores, tutores e suporte.</p></div></div>
<section class="panel mb-4">
<h2>Novo usuário interno</h2>
<form method="post" action="/admin/staff" class="row g-2 align-items-end">
<?=Csrf::field()?>
<div class="col-md-3"><label class="form-label">Nome</label><input class="form-control" name="name" required></div>
<div class="col-md-3"><label class="form-label">E-mail</label><input class="form-control" type="email" name="email" required></div>
<div class="col-md-2"><label class="form-label">Senha inicial</label><input class="form-control" type="password" name="password" minlength="8" required></div>
<div class="col-md-2"><label class="form-label">Papel</label><select class="form-select" name="role_id"><?php foreach($roles as $r):?><option value="<?=$r['id']?>"><?=e($r['name'])?></option><?php endforeach;?></select></div>
<div class="col-md-2"><button class="btn btn-brand w-100">Criar usuário</button></div>
</form>
</section>
<section class="panel"><div class="table-responsive"><table class="table align-middle datatable"><thead><tr><th>Nome</th><th>E-mail</th><th>Papel</th><th>Status</th><th>Ações</th></tr></thead><tbody>
<?php foreach($users as $u):?><tr><td><strong><?=e($u['name'])?></strong></td><td><?=e($u['email'])?></td><td><?=e($u['roles']?:'—')?></td><td><span class="status-badge"><?=e($u['status'])?></span></td><td><div class="d-flex gap-2 flex-wrap"><form method="post" action="/admin/staff/<?=$u['id']?>/role" class="d-flex gap-1"><?=Csrf::field()?><select class="form-select form-select-sm" name="role_id"><?php foreach($roles as $r):?><option value="<?=$r['id']?>"><?=e($r['name'])?></option><?php endforeach;?></select><button class="btn btn-sm btn-outline-secondary">Papel</button></form><?php if((int)$u['id']!==TecnodataLmsCoreAuth::id()):?><form method="post" action="/admin/staff/<?=$u['id']?>/status"><?=Csrf::field()?><input type="hidden" name="status" value="<?=$u['status']==='active'?'inactive':'active'?>"><button class="btn btn-sm btn-outline-secondary"><?=$u['status']==='active'?'Desativar':'Ativar'?></button></form><?php endif;?></div></td></tr><?php endforeach;?>
</tbody></table></div></section>
