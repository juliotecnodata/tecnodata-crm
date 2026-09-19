<div class="page-heading"><div><span class="eyebrow">RESULTADO</span><h1><?=e($activity['title'])?></h1><p>Tentativa <?=$attempt['attempt_no']?> finalizada em <?=e($attempt['finished_at'])?></p></div><a class="btn btn-outline-secondary" href="/student/course/<?=$en['id']?>">Voltar ao curso</a></div>
<div class="metric-grid">
<div class="metric"><span>Nota</span><strong><?=e($attempt['score'])?></strong></div>
<div class="metric"><span>Percentual</span><strong><?=e($attempt['percent'])?>%</strong></div>
<div class="metric"><span>Resultado</span><strong style="font-size:22px"><?=$attempt['passed']?'Aprovado':'Não aprovado'?></strong></div>
<div class="metric"><span>Nota mínima</span><strong><?=e($quiz['grade_pass']??0)?></strong></div>
</div>
<section class="panel"><h2>Resumo</h2><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Questão</th><th>Acerto</th><th>Nota</th></tr></thead><tbody><?php foreach($answers as $a):?><tr><td><?=e(mb_strimwidth(strip_tags($a['question_html']),0,120,'…'))?></td><td><?=round((float)$a['fraction']*100,1)?>%</td><td><?=e($a['mark'])?></td></tr><?php endforeach;?></tbody></table></div></section>