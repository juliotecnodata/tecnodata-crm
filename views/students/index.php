<?php use Tecnodata\Lms\Core\Csrf; ?>
<div class="page-heading"><div><span class="eyebrow">PESSOAS</span><h1>Alunos</h1><p>Cadastro central, acesso, status e matrículas.</p></div></div>
<?php if($message):?><div class="alert alert-success"><?=e($message)?></div><?php endif;?>
<section class="panel mb-4">
<h2>Novo aluno</h2>
<form method="post" action="/admin/students" class="row g-2 align-items-end">
<?=Csrf::field()?>
<div class="col-md-3"><label class="form-label">CPF</label><input class="form-control" name="cpf" inputmode="numeric" required></div>
<div class="col-md-4"><label class="form-label">Nome</label><input class="form-control" name="name" required></div>
<div class="col-md-3"><label class="form-label">E-mail</label><input class="form-control" type="email" name="email"></div>
<div class="col-md-2"><button class="btn btn-brand w-100">Criar aluno</button></div>
</form>
<p class="form-text mt-2 mb-0">Por compatibilidade operacional, a senha inicial é o CPF. Isso pode ser alterado por política futuramente.</p>
</section>
<section class="panel"><div class="table-responsive"><table class="table align-middle datatable"><thead><tr><th>Aluno</th><th>CPF</th><th>E-mail</th><th>Matrículas</th><th>Ativas</th><th>Status</th><th>Ações</th></tr></thead><tbody>
<?php foreach($students as $s):?><tr>
<td><strong><?=e($s['name'])?></strong><small class="d-block text-secondary">ID <?=$s['id']?></small></td>
<td><?=e($s['cpf'])?></td><td><?=e($s['email']?:'—')?></td><td><?=$s['enrollments']?></td><td><?=$s['active_enrollments']??0?></td><td><span class="status-badge"><?=e($s['status'])?></span></td>
<td><details><summary class="btn btn-sm btn-outline-secondary">Editar</summary><div class="inline-editor"><form method="post" action="/admin/students/<?=$s['id']?>" class="row g-2"><?=Csrf::field()?><div class="col-12"><input class="form-control" name="name" value="<?=e($s['name'])?>" required></div><div class="col-12"><input class="form-control" type="email" name="email" value="<?=e($s['email'])?>"></div><div class="col-7"><select class="form-select" name="status"><option value="active" <?=$s['status']==='active'?'selected':''?>>Ativo</option><option value="inactive" <?=$s['status']==='inactive'?'selected':''?>>Inativo</option></select></div><div class="col-5"><button class="btn btn-brand w-100">Salvar</button></div></form><form method="post" action="/admin/students/<?=$s['id']?>/reset-password" class="mt-2"><?=Csrf::field()?><button class="btn btn-sm btn-outline-warning w-100" onclick="return confirm('Redefinir senha para o CPF?')">Senha = CPF</button></form></div></details></td>
</tr><?php endforeach;?>
</tbody></table></div></section>
