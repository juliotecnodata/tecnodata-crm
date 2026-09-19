<?php use Tecnodata\Lms\Core\Csrf;
$settings=json_decode($question['settings_json']?:'{}',true)?:[];
$trueCorrect='true';
if($question['type']==='truefalse'){
 foreach($answers as $a){
  if((float)$a['fraction']>0 && mb_strtolower(strip_tags($a['answer_html']))==='falso')$trueCorrect='false';
 }
}
$pairs=(array)($settings['pairs']??[]);
?>
<div class="page-heading">
<div><span class="eyebrow">BANCO DE QUESTÕES</span><h1>Editar questão #<?=$question['id']?></h1><p><?=e($question['category_name'])?> · <?=e($question['type'])?></p></div>
<a class="btn btn-outline-secondary" href="/admin/courses/<?=$question['course_id']?>/questions">Voltar</a>
</div>
<section class="panel form-panel">
<div class="alert alert-info">Se a questão já tiver respostas de alunos, o sistema bloqueará a edição para preservar o histórico.</div>
<form method="post" action="/admin/questions/<?=$question['id']?>">
<?=Csrf::field()?>
<div class="row g-3">
<div class="col-md-7"><label class="form-label">Categoria</label><select class="form-select" name="category_id" required><?php foreach($categories as $c):?><option value="<?=$c['id']?>" <?=$c['id']==$question['category_id']?'selected':''?>><?=e($c['name'])?></option><?php endforeach;?></select></div>
<div class="col-md-5"><label class="form-label">Valor</label><input class="form-control" type="number" step=".01" min=".01" name="default_mark" value="<?=e($question['default_mark'])?>"></div>
<div class="col-12"><label class="form-label">Nome interno</label><input class="form-control" name="name" value="<?=e($question['name'])?>"></div>
<div class="col-12"><label class="form-label">Enunciado</label><textarea class="form-control" rows="6" name="question_html" required><?=e($question['question_html'])?></textarea></div>
</div>

<?php if($question['type']==='truefalse'):?>
<div class="mt-3"><label class="form-label">Resposta correta</label><select class="form-select" name="truefalse_correct"><option value="true" <?=$trueCorrect==='true'?'selected':''?>>Verdadeiro</option><option value="false" <?=$trueCorrect==='false'?'selected':''?>>Falso</option></select></div>

<?php elseif($question['type']==='matching'):?>
<div class="mt-3"><h2>Associações</h2><div id="pairRows"><?php foreach($pairs as $p):?><div class="answer-row"><input class="form-control" name="match_left[]" value="<?=e($p['left']??'')?>"><input class="form-control" name="match_right[]" value="<?=e($p['right']??'')?>"><span></span><button type="button" class="btn btn-outline-danger remove-row">×</button></div><?php endforeach;?></div><button class="btn btn-sm btn-outline-secondary" type="button" id="addPair">+ Par</button></div>

<?php else:?>
<div class="mt-3"><h2>Respostas</h2><div id="answerRows">
<?php foreach($answers as $a):?><div class="answer-row"><input class="form-control" name="answer_text[]" value="<?=e($a['answer_html'])?>"><input class="form-control" type="number" step=".001" name="answer_fraction[]" value="<?=e((float)$a['fraction']*100)?>"><input class="form-control" name="answer_feedback[]" value="<?=e($a['feedback_html'])?>"><button type="button" class="btn btn-outline-danger remove-row">×</button></div><?php endforeach;?>
</div><button class="btn btn-sm btn-outline-secondary" type="button" id="addAnswer">+ Resposta</button>
<div class="form-check mt-3"><input class="form-check-input" type="checkbox" name="single" id="single" <?=!empty($settings['single'])?'checked':''?>><label class="form-check-label" for="single">Uma única resposta</label></div>
<div class="form-check"><input class="form-check-input" type="checkbox" name="shuffle_answers" id="shuffle" <?=!empty($settings['shuffle_answers'])?'checked':''?>><label class="form-check-label" for="shuffle">Embaralhar alternativas</label></div>
<div class="form-check"><input class="form-check-input" type="checkbox" name="case_sensitive" id="case" <?=!empty($settings['case_sensitive'])?'checked':''?>><label class="form-check-label" for="case">Diferenciar maiúsculas/minúsculas</label></div>
<?php if($question['type']==='numerical'):?><div class="mt-2"><label class="form-label">Tolerância</label><input class="form-control" type="number" step=".0001" min="0" name="tolerance" value="<?=e($settings['tolerance']??0)?>"></div><?php endif;?>
</div>
<?php endif;?>

<button class="btn btn-brand mt-4">Salvar alterações</button>
</form>
</section>
<script>
document.addEventListener('DOMContentLoaded',()=>{
 document.addEventListener('click',e=>{if(e.target.classList.contains('remove-row'))e.target.parentElement.remove()});
 const add=document.getElementById('addAnswer');if(add)add.addEventListener('click',()=>{const d=document.createElement('div');d.className='answer-row';d.innerHTML='<input class="form-control" name="answer_text[]" placeholder="Resposta"><input class="form-control" type="number" step=".001" name="answer_fraction[]" value="0"><input class="form-control" name="answer_feedback[]" placeholder="Feedback"><button type="button" class="btn btn-outline-danger remove-row">×</button>';document.getElementById('answerRows').appendChild(d)});
 const pair=document.getElementById('addPair');if(pair)pair.addEventListener('click',()=>{const d=document.createElement('div');d.className='answer-row';d.innerHTML='<input class="form-control" name="match_left[]" placeholder="Item"><input class="form-control" name="match_right[]" placeholder="Correspondência"><span></span><button type="button" class="btn btn-outline-danger remove-row">×</button>';document.getElementById('pairRows').appendChild(d)});
});
</script>
