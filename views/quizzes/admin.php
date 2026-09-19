<?php use Tecnodata\Lms\Core\Csrf; $settings=json_decode($quiz['settings_json']?:'{}',true)?:[]; ?>
<div class="page-heading"><div><span class="eyebrow">AVALIAÇÃO</span><h1><?=e($activity['title'])?></h1><p><?=e($activity['course_name'])?></p></div><div class="d-flex gap-2"><a class="btn btn-outline-secondary" href="/admin/courses/<?=$activity['course_id']?>/questions">Banco de questões</a><a class="btn btn-outline-secondary" href="/admin/courses/<?=$activity['course_id']?>">Curso</a></div></div>
<div class="row g-3">
<div class="col-xl-4"><section class="panel"><h2>Configuração</h2><form method="post" action="/admin/activities/<?=$activity['id']?>/quiz/settings"><?=Csrf::field()?>
<div class="mb-2"><label class="form-label">Nota máxima</label><input class="form-control" type="number" step=".01" name="grade_max" value="<?=e($quiz['grade_max'])?>"></div>
<div class="mb-2"><label class="form-label">Nota para aprovação</label><input class="form-control" type="number" step=".01" name="grade_pass" value="<?=e($quiz['grade_pass'])?>"></div>
<div class="mb-2"><label class="form-label">Tentativas (0 = ilimitadas)</label><input class="form-control" type="number" min="0" name="attempts_allowed" value="<?=e($quiz['attempts_allowed'])?>"></div>
<div class="form-check"><input class="form-check-input" type="checkbox" name="shuffle_questions" id="sq" <?=!empty($settings['shuffle_questions'])?'checked':''?>><label class="form-check-label" for="sq">Embaralhar questões</label></div>
<div class="form-check"><input class="form-check-input" type="checkbox" name="show_feedback" id="sf" <?=!empty($settings['show_feedback'])?'checked':''?>><label class="form-check-label" for="sf">Mostrar feedback</label></div>
<div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="show_correct_answer" id="sca" <?=!empty($settings['show_correct_answer'])?'checked':''?>><label class="form-check-label" for="sca">Mostrar resposta correta</label></div>
<button class="btn btn-brand w-100">Salvar avaliação</button>
</form></section></div>

<div class="col-xl-8">
<section class="panel mb-3"><h2>Adicionar questões</h2>
<form method="post" action="/admin/activities/<?=$activity['id']?>/quiz/slots" class="row g-2 align-items-end"><?=Csrf::field()?>
<div class="col-md-3"><label class="form-label">Modo</label><select class="form-select" name="mode" id="slotMode"><option value="question">Questão específica</option><option value="random">Sorteio por categoria</option></select></div>
<div class="col-md-6 slot-question"><label class="form-label">Questão</label><select class="form-select" name="question_id"><option value="">Selecione...</option><?php foreach($questions as $q):?><option value="<?=$q['id']?>"><?=e($q['category_name'].' · '.($q['name']?:mb_strimwidth(strip_tags($q['question_html']),0,70,'…')))?></option><?php endforeach;?></select></div>
<div class="col-md-4 slot-random d-none"><label class="form-label">Categoria</label><select class="form-select" name="category_id"><option value="">Selecione...</option><?php foreach($categories as $c):?><option value="<?=$c['id']?>"><?=e($c['name'])?> (<?=$c['question_count']?>)</option><?php endforeach;?></select></div>
<div class="col-md-2 slot-random d-none"><label class="form-label">Sortear</label><input class="form-control" type="number" min="1" name="random_count" value="1"></div>
<div class="col-md-3"><button class="btn btn-brand w-100">Adicionar</button></div>
</form>
</section>

<section class="panel"><h2>Composição da prova</h2><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Ordem</th><th>Origem</th><th>Conteúdo</th><th></th></tr></thead><tbody>
<?php foreach($slots as $s):?><tr><td><?=$s['position']?></td><td><?=$s['question_id']?'Questão fixa':'Sorteio'?></td><td><?php if($s['question_id']):?><strong><?=e($s['question_name']?:'Questão #'.$s['question_id'])?></strong><small class="d-block text-secondary"><?=e($s['question_type'])?></small><?php else:?><strong><?=e($s['category_name'])?></strong><small class="d-block text-secondary">Sortear <?=$s['random_count']?> questão(ões)</small><?php endif;?></td><td class="text-end"><form method="post" action="/admin/quiz-slots/<?=$s['id']?>/delete"><?=Csrf::field()?><button class="btn btn-sm btn-outline-danger">Remover</button></form></td></tr><?php endforeach;?>
</tbody></table></div></section>
</div></div>
<script>document.addEventListener('DOMContentLoaded',()=>{const s=document.getElementById('slotMode');function x(){document.querySelectorAll('.slot-question').forEach(e=>e.classList.toggle('d-none',s.value!=='question'));document.querySelectorAll('.slot-random').forEach(e=>e.classList.toggle('d-none',s.value!=='random'))}s.addEventListener('change',x);x()})</script>
