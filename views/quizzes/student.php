<?php use Tecnodata\Lms\Core\Csrf; ?>
<div class="page-heading"><div><span class="eyebrow">AVALIAÇÃO</span><h1><?=e($activity['title'])?></h1><p>Tentativa <?=$attempt['attempt_no']?> · Nota para aprovação: <?=e($quiz['grade_pass']??0)?> / <?=e($quiz['grade_max'])?></p></div><a class="btn btn-outline-secondary" href="/student/course/<?=$en['id']?>">Voltar ao curso</a></div>
<?php if(!empty($bio['required'])):?><div class="alert alert-warning"><strong>Validação de identidade exigida neste checkpoint.</strong> Perfil: <?=e($bio['profile']['name'])?>. O conector facial deve validar antes da prova em produção.</div><?php endif;?>
<form method="post" action="/student/enrollment/<?=$en['id']?>/quiz/<?=$activity['id']?>/submit"><?=Csrf::field()?>
<div class="quiz-questions">
<?php foreach($questions as $idx=>$q):$settings=json_decode($q['settings_json']?:'{}',true)?:[];?>
<section class="panel quiz-question">
<div class="question-number">Questão <?=$idx+1?></div>
<div class="question-text"><?=$q['question_html']?></div>
<?php if(in_array($q['type'],['multichoice','truefalse'],true)):?>
<div class="answer-options"><?php foreach($q['answers'] as $a):?><label class="answer-option"><input type="<?=!empty($settings['single'])||$q['type']==='truefalse'?'radio':'checkbox'?>" name="q[<?=$q['id']?>]<?=empty($settings['single'])&&$q['type']!=='truefalse'?'[]':''?>" value="<?=$a['id']?>"><span><?=e(strip_tags($a['answer_html']))?></span></label><?php endforeach;?></div>
<?php elseif($q['type']==='shortanswer'):?><input class="form-control" name="q[<?=$q['id']?>]" autocomplete="off">
<?php elseif($q['type']==='numerical'):?><input class="form-control" name="q[<?=$q['id']?>]" inputmode="decimal">
<?php elseif($q['type']==='matching'):$pairs=(array)($settings['pairs']??[]);$opts=array_column($pairs,'right');shuffle($opts);foreach($pairs as $i=>$pair):?><div class="row g-2 align-items-center mb-2"><div class="col-md-6"><?=e($pair['left'])?></div><div class="col-md-6"><select class="form-select" name="q[<?=$q['id']?>][<?=$i?>]"><option value="">Selecione...</option><?php foreach($opts as $o):?><option value="<?=e($o)?>"><?=e($o)?></option><?php endforeach;?></select></div></div><?php endforeach;?>
<?php endif;?>
</section>
<?php endforeach;?>
</div>
<div class="d-flex justify-content-end mt-3"><button class="btn btn-brand btn-lg" onclick="return confirm('Finalizar e enviar esta tentativa?')">Finalizar avaliação</button></div>
</form>
