<?php use Tecnodata\Lms\Core\Csrf; ?>
<div class="page-heading">
<div><span class="eyebrow">CONSTRUTOR DE CURSO</span><h1><?=e($course['name'])?></h1><p><?=e($course['summary'])?></p></div>
<div class="d-flex gap-2 flex-wrap"><span class="status-badge"><?=e($course['status'])?></span><a class="btn btn-outline-secondary" href="/admin/courses/<?=$course['id']?>/questions">Banco de questões</a><a class="btn btn-outline-secondary" href="/admin/courses/<?=$course['id']?>/participants">Equipe</a><a class="btn btn-outline-secondary" href="/admin/courses/<?=$course['id']?>/settings">Regras</a><a class="btn btn-outline-secondary" href="/admin/courses">Voltar</a></div>
</div>
<div class="builder">
<?php foreach($sections as $s):?>
<section class="builder-section <?=$s['parent_id']?'builder-subsection':''?>">
<div class="builder-section-head">
<div><i class="bi bi-grip-vertical"></i><strong><?=e($s['title'])?></strong><?php if($s['parent_id']):?><span class="type-pill">Subseção</span><?php endif;?></div>
<div class="d-flex align-items-center gap-2"><span><?=count($s['activities'])?> itens</span><details class="inline-details"><summary class="btn btn-sm btn-outline-secondary">Editar seção</summary><div class="inline-editor wide"><form method="post" action="/admin/sections/<?=$s['id']?>" class="row g-2"><?=Csrf::field()?><div class="col-md-6"><label class="form-label">Título</label><input class="form-control" name="title" value="<?=e($s['title'])?>" required></div><div class="col-md-3"><label class="form-label">Posição</label><input class="form-control" type="number" min="0" name="position" value="<?=$s['position']?>"></div><div class="col-md-3 d-flex align-items-end"><div class="form-check"><input class="form-check-input" type="checkbox" name="visible" id="sv<?=$s['id']?>" <?=$s['visible']?'checked':''?>><label class="form-check-label" for="sv<?=$s['id']?>">Visível</label></div></div><div class="col-12"><label class="form-label">Resumo</label><textarea class="form-control" name="summary"><?=e($s['summary'])?></textarea></div><div class="col-12"><button class="btn btn-brand">Salvar seção</button></div></form><form method="post" action="/admin/sections/<?=$s['id']?>/delete" class="mt-2"><?=Csrf::field()?><button class="btn btn-sm btn-outline-danger" onclick="return confirm('Excluir seção e conteúdos sem progresso?')">Excluir seção</button></form></div></details></div>
</div>

<div class="activity-list">
<?php foreach($s['activities'] as $a):$data=json_decode($a['content_json']?:'{}',true)?:[];?>
<div class="activity-row">
<div class="activity-icon"><i class="bi <?=$a['type']==='video'?'bi-play-btn':($a['type']==='quiz'?'bi-ui-checks':'bi-file-earmark-text')?>"></i></div>
<div class="flex-grow-1"><strong><?=e($a['title'])?></strong><small><?=e($a['type'])?> · posição <?=$a['position']?></small></div>
<?php if($a['type']==='quiz'):?><a class="btn btn-sm btn-outline-secondary" href="/admin/activities/<?=$a['id']?>/quiz">Configurar prova</a><?php endif;?>
<details class="inline-details"><summary class="btn btn-sm btn-outline-secondary">Editar</summary><div class="inline-editor wide"><form method="post" action="/admin/activities/<?=$a['id']?>" class="row g-2"><?=Csrf::field()?>
<div class="col-md-8"><label class="form-label">Título</label><input class="form-control" name="title" value="<?=e($a['title'])?>" required></div>
<div class="col-md-2"><label class="form-label">Posição</label><input class="form-control" type="number" name="position" min="0" value="<?=$a['position']?>"></div>
<div class="col-md-2 d-flex align-items-end"><div class="form-check"><input class="form-check-input" type="checkbox" name="visible" id="av<?=$a['id']?>" <?=$a['visible']?'checked':''?>><label class="form-check-label" for="av<?=$a['id']?>">Visível</label></div></div>
<div class="col-12"><label class="form-label">Descrição</label><input class="form-control" name="description" value="<?=e($a['description'])?>"></div>
<?php if(in_array($a['type'],['video','url'],true)):?><div class="col-12"><label class="form-label">URL</label><input class="form-control" name="url" value="<?=e($data['url']??'')?>"></div>
<?php elseif($a['type']==='resource'):?><div class="col-md-6"><label class="form-label">Arquivo</label><select class="form-select" name="file_id"><option value="">Selecione...</option><?php foreach($files as $f):?><option value="<?=$f['id']?>" <?=($data['file_id']??0)==$f['id']?'selected':''?>><?=e($f['original_name'])?></option><?php endforeach;?></select></div><div class="col-md-6"><label class="form-label">Texto</label><input class="form-control" name="content" value="<?=e($data['html']??'')?>"></div>
<?php else:?><div class="col-12"><label class="form-label">Conteúdo</label><textarea class="form-control" rows="4" name="content"><?=e($data['html']??'')?></textarea></div><?php endif;?>
<input type="hidden" name="completion_mode" value="<?=e($a['completion_mode'])?>">
<div class="col-12"><button class="btn btn-brand">Salvar atividade</button></div>
</form><form method="post" action="/admin/activities/<?=$a['id']?>/delete" class="mt-2"><?=Csrf::field()?><button class="btn btn-sm btn-outline-danger" onclick="return confirm('Excluir atividade sem progresso?')">Excluir atividade</button></form></div></details>
<span class="status-badge"><?=$a['visible']?'Visível':'Oculto'?></span>
</div>
<?php endforeach;?>

<details class="activity-editor"><summary>+ Adicionar atividade</summary><form class="row g-2 mt-2" method="post" action="/admin/courses/<?=$course['id']?>/activities"><?=Csrf::field()?><input type="hidden" name="section_id" value="<?=$s['id']?>">
<div class="col-md-3"><label class="form-label">Tipo</label><select class="form-select activity-type" name="type"><option value="page">Página / Texto</option><option value="video">Vídeo Video Front</option><option value="url">URL externa</option><option value="quiz">Quiz / Prova</option><option value="resource">Arquivo / Resource</option><option value="book">Livro</option><option value="lesson">Lição</option><option value="h5pactivity">H5P</option><option value="scorm">SCORM</option><option value="label">Rótulo</option><option value="folder">Pasta</option></select></div>
<div class="col-md-5"><label class="form-label">Título</label><input class="form-control" name="title" required></div>
<div class="col-md-4"><label class="form-label">URL</label><input class="form-control" name="url" placeholder="Video Front ou URL externa"></div>
<div class="col-md-5"><label class="form-label">Arquivo existente</label><select class="form-select" name="file_id"><option value="">Nenhum / não se aplica</option><?php foreach($files as $f):?><option value="<?=$f['id']?>"><?=e($f['original_name'])?></option><?php endforeach;?></select></div>
<div class="col-md-7"><label class="form-label">Conteúdo / observação</label><input class="form-control" name="content"></div>
<div class="col-12"><button class="btn btn-brand">Adicionar atividade</button></div>
</form></details>
</div></section>
<?php endforeach;?>
</div>
<section class="panel mt-4"><h2>Adicionar seção ou subseção</h2><form class="row g-2" method="post" action="/admin/courses/<?=$course['id']?>/sections"><?=Csrf::field()?><div class="col-md-5"><label class="form-label">Nome</label><input class="form-control" name="title" required></div><div class="col-md-5"><label class="form-label">Dentro de</label><select class="form-select" name="parent_id"><option value="0">Seção principal</option><?php foreach($sections as $parent):if($parent['parent_id'])continue;?><option value="<?=$parent['id']?>"><?=e($parent['title'])?></option><?php endforeach;?></select></div><div class="col-md-2 d-flex align-items-end"><button class="btn btn-brand w-100">Adicionar</button></div></form></section>
