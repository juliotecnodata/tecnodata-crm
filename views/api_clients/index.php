<?php use Tecnodata\Lms\Core\Csrf; ?>
<div class="page-heading"><div><span class="eyebrow">API-FIRST</span><h1>Integrações</h1><p>Um token por sistema, com escopos mínimos e rotação segura.</p></div></div>
<?php if($message):?><div class="alert alert-success"><?=e($message)?></div><?php endif;?>
<?php if($token):?><div class="alert alert-warning"><strong>Copie agora.</strong> Este token só será mostrado uma vez.<div class="token-box mt-2"><?=e($token)?></div></div><?php endif;?>

<section class="panel mb-4">
<h2>Novo cliente API</h2>
<form method="post" action="/admin/api-clients">
<?=Csrf::field()?>
<div class="row g-3">
<div class="col-md-5"><label class="form-label">Nome</label><input class="form-control" name="name" placeholder="Ex.: WooCommerce produção" required></div>
<div class="col-md-7"><label class="form-label">Escopos</label><div class="scope-grid"><?php foreach($allowedScopes as $s):?><label class="form-check"><input class="form-check-input" type="checkbox" name="scopes[]" value="<?=e($s)?>" <?=$s==='*'?'':'checked'?>> <span><?=e($s)?></span></label><?php endforeach;?></div></div>
<div class="col-12"><button class="btn btn-brand">Gerar token</button></div>
</div>
</form>
</section>

<section class="panel">
<div class="table-responsive"><table class="table align-middle"><thead><tr><th>Cliente</th><th>Escopos</th><th>Último uso</th><th>Status</th><th>Ações</th></tr></thead><tbody>
<?php foreach($clients as $c):$selected=json_decode($c['scopes_json']?:'[]',true)?:[];?>
<tr>
<td><strong><?=e($c['name'])?></strong><small class="d-block text-secondary">ID <?=$c['id']?> · criado <?=e($c['created_at'])?></small></td>
<td><?php foreach($selected as $s):?><span class="type-pill"><?=e($s)?></span><?php endforeach;?></td>
<td><?=e($c['last_used_at']?:'Nunca')?></td>
<td><span class="status-badge"><?=$c['enabled']?'Ativo':'Inativo'?></span></td>
<td>
<details class="inline-details"><summary class="btn btn-sm btn-outline-secondary">Gerenciar</summary>
<div class="inline-editor wide">
<form method="post" action="/admin/api-clients/<?=$c['id']?>" class="row g-2">
<?=Csrf::field()?>
<div class="col-12"><label class="form-label">Nome</label><input class="form-control" name="name" value="<?=e($c['name'])?>" required></div>
<div class="col-12"><label class="form-label">Escopos</label><div class="scope-grid"><?php foreach($allowedScopes as $s):?><label class="form-check"><input class="form-check-input" type="checkbox" name="scopes[]" value="<?=e($s)?>" <?=in_array($s,$selected,true)?'checked':''?>> <span><?=e($s)?></span></label><?php endforeach;?></div></div>
<div class="col-12"><button class="btn btn-brand">Salvar</button></div>
</form>
<div class="d-flex gap-2 mt-3">
<form method="post" action="/admin/api-clients/<?=$c['id']?>/status"><?=Csrf::field()?><input type="hidden" name="enabled" value="<?=$c['enabled']?'0':'1'?>"><button class="btn btn-sm btn-outline-secondary"><?=$c['enabled']?'Desativar':'Ativar'?></button></form>
<form method="post" action="/admin/api-clients/<?=$c['id']?>/rotate" onsubmit="return confirm('O token anterior será invalidado imediatamente. Continuar?')"><?=Csrf::field()?><button class="btn btn-sm btn-outline-warning">Rotacionar token</button></form>
</div>
</div></details>
</td>
</tr>
<?php endforeach;?>
</tbody></table></div>
</section>
