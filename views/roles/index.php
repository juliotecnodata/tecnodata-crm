<?php use Tecnodata\Lms\Core\Csrf; ?>
<div class="page-heading"><div><span class="eyebrow">PERMISSÕES</span><h1>Papéis e capacidades</h1><p>Controle centralizado no estilo de contextos do Moodle, com interface mais simples.</p></div></div>
<?php foreach($roles as $r):?>
<section class="panel mb-3">
<div class="d-flex justify-content-between align-items-center"><div><h2 class="mb-0"><?=e($r['name'])?></h2><small class="text-secondary"><?=e($r['slug'])?></small></div><?php if($r['slug']==='super_admin'):?><span class="status-badge">Acesso total</span><?php endif;?></div>
<?php if($r['slug']!=='super_admin'):?>
<form method="post" action="/admin/roles/<?=$r['id']?>" class="mt-3">
<?=Csrf::field()?>
<div class="permission-grid">
<?php foreach($permissions as $p):?><label class="permission-item"><input type="checkbox" name="permissions[]" value="<?=$p['id']?>" <?=!empty($matrix[$r['id']][$p['id']])?'checked':''?>><span><strong><?=e($p['name'])?></strong><small><?=e($p['slug'])?></small></span></label><?php endforeach;?>
</div>
<button class="btn btn-brand mt-3">Salvar permissões</button>
</form>
<?php endif;?>
</section>
<?php endforeach;?>
