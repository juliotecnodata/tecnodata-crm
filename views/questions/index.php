<?php use Tecnodata\Lms\Core\Csrf; ?>
<div class="page-heading">
<div><span class="eyebrow">BANCO DE QUESTÕES</span><h1><?=e($course['name'])?></h1><p>Crie categorias e questões reutilizáveis em avaliações.</p></div>
<div class="d-flex gap-2 flex-wrap"><a class="btn btn-outline-secondary" href="/admin/courses/<?=$course['id']?>">Curso</a><a class="btn btn-outline-secondary" href="/admin/courses/<?=$course['id']?>/questions/export">Exportar JSON</a></div>
</div>
<?php if($message):?><div class="alert alert-success"><?=e($message)?></div><?php endif;?>

<div class="row g-3">
<div class="col-xl-4">
<section class="panel mb-3">
<h2>Categorias</h2>
<form method="post" action="/admin/courses/<?=$course['id']?>/question-categories" class="row g-2">
<?=Csrf::field()?>
<div class="col-12"><input class="form-control" name="name" placeholder="Nome da categoria" required></div>
<div class="col-12"><select class="form-select" name="parent_id"><option value="0">Sem categoria pai</option><?php foreach($categories as $c):?><option value="<?=$c['id']?>"><?=e($c['name'])?></option><?php endforeach;?></select></div>
<div class="col-12"><button class="btn btn-brand w-100">Criar categoria</button></div>
</form>
<hr>
<?php foreach($categories as $c):?><div class="d-flex justify-content-between py-2 border-bottom"><span><?=e($c['name'])?></span><strong><?=$c['question_count']?></strong></div><?php endforeach;?>
</section>

<section class="panel">
<h2>Importar banco</h2>
<form method="post" enctype="multipart/form-data" action="/admin/courses/<?=$course['id']?>/questions/import">
<?=Csrf::field()?>
<input class="form-control mb-2" type="file" name="bank" accept=".json" required>
<button class="btn btn-outline-secondary w-100">Importar JSON Tecnodata</button>
</form>
</section>
</div>

<div class="col-xl-8">
<section class="panel mb-3">
<h2>Nova questão</h2>
<form method="post" action="/admin/courses/<?=$course['id']?>/questions" id="questionForm">
<?=Csrf::field()?>
<div class="row g-2">
<div class="col-md-5"><label class="form-label">Categoria</label><select class="form-select" name="category_id" required><option value="">Selecione...</option><?php foreach($categories as $c):?><option value="<?=$c['id']?>"><?=e($c['name'])?></option><?php endforeach;?></select></div>
<div class="col-md-4"><label class="form-label">Tipo</label><select class="form-select" name="type" id="questionType"><option value="multichoice">Múltipla escolha</option><option value="truefalse">Verdadeiro / Falso</option><option value="shortanswer">Resposta curta</option><option value="numerical">Numérica</option><option value="matching">Associação</option></select></div>
<div class="col-md-3"><label class="form-label">Valor</label><input class="form-control" type="number" step="0.01" min="0.01" name="default_mark" value="1"></div>
<div class="col-12"><label class="form-label">Nome interno</label><input class="form-control" name="name" placeholder="Ex.: Legislação 001"></div>
<div class="col-12"><label class="form-label">Enunciado</label><textarea class="form-control" name="question_html" rows="5" required></textarea></div>
</div>

<div id="standardAnswers" class="mt-3">
<div class="d-flex justify-content-between"><h3 class="h6">Respostas</h3><button class="btn btn-sm btn-outline-secondary" type="button" id="addAnswer">+ Resposta</button></div>
<div id="answerRows"></div>
<div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="single" id="single" checked><label class="form-check-label" for="single">Uma única resposta</label></div>
<div class="form-check"><input class="form-check-input" type="checkbox" name="shuffle_answers" id="shuffle" checked><label class="form-check-label" for="shuffle">Embaralhar alternativas</label></div>
<div class="form-check"><input class="form-check-input" type="checkbox" name="case_sensitive" id="case"><label class="form-check-label" for="case">Resposta curta diferencia maiúsculas/minúsculas</label></div>
</div>

<div id="trueFalseFields" class="mt-3 d-none"><label class="form-label">Resposta correta</label><select class="form-select" name="truefalse_correct"><option value="true">Verdadeiro</option><option value="false">Falso</option></select></div>
<div id="numericalFields" class="mt-3 d-none"><label class="form-label">Tolerância numérica</label><input class="form-control" type="number" step="0.0001" min="0" name="tolerance" value="0"></div>
<div id="matchingFields" class="mt-3 d-none"><div class="d-flex justify-content-between"><h3 class="h6">Pares</h3><button class="btn btn-sm btn-outline-secondary" type="button" id="addPair">+ Par</button></div><div id="pairRows"></div></div>

<button class="btn btn-brand mt-3">Salvar questão</button>
</form>
</section>
</div>
</div>

<section class="panel mt-3"><h2>Questões cadastradas</h2><div class="table-responsive"><table class="table align-middle datatable"><thead><tr><th>ID</th><th>Questão</th><th>Categoria</th><th>Tipo</th><th>Respostas</th><th></th></tr></thead><tbody>
<?php foreach($questions as $q):?><tr><td><?=$q['id']?></td><td><strong><?=e($q['name']?:'Sem nome')?></strong><small class="d-block text-secondary"><?=e(mb_strimwidth(strip_tags($q['question_html']),0,100,'…'))?></small></td><td><?=e($q['category_name'])?></td><td><span class="type-pill"><?=e($q['type'])?></span></td><td><?=$q['answer_count']?></td><td class="text-end"><div class="d-flex gap-1 justify-content-end"><a class="btn btn-sm btn-outline-secondary" href="/admin/questions/<?=$q['id']?>/edit">Editar</a><form method="post" action="/admin/questions/<?=$q['id']?>/delete" onsubmit="return confirm('Excluir esta questão?')"><?=Csrf::field()?><button class="btn btn-sm btn-outline-danger">Excluir</button></form></div></td></tr><?php endforeach;?>
</tbody></table></div></section>

<script>
document.addEventListener('DOMContentLoaded',()=>{
 const type=document.getElementById('questionType'), rows=document.getElementById('answerRows'), pairRows=document.getElementById('pairRows');
 const addAnswer=()=>{const d=document.createElement('div');d.className='answer-row';d.innerHTML='<input class="form-control" name="answer_text[]" placeholder="Resposta"><select class="form-select" name="answer_fraction[]"><option value="100">100%</option><option value="50">50%</option><option value="33.333">33,333%</option><option value="0" selected>0%</option><option value="-50">-50%</option><option value="-100">-100%</option></select><input class="form-control" name="answer_feedback[]" placeholder="Feedback"><button type="button" class="btn btn-outline-danger remove-row">×</button>';rows.appendChild(d)};
 const addPair=()=>{const d=document.createElement('div');d.className='answer-row';d.innerHTML='<input class="form-control" name="match_left[]" placeholder="Item / pergunta"><input class="form-control" name="match_right[]" placeholder="Correspondência"><span></span><button type="button" class="btn btn-outline-danger remove-row">×</button>';pairRows.appendChild(d)};
 document.getElementById('addAnswer').addEventListener('click',addAnswer); document.getElementById('addPair').addEventListener('click',addPair);
 document.addEventListener('click',e=>{if(e.target.classList.contains('remove-row'))e.target.parentElement.remove()});
 function sync(){const v=type.value;document.getElementById('standardAnswers').classList.toggle('d-none',v==='truefalse'||v==='matching');document.getElementById('trueFalseFields').classList.toggle('d-none',v!=='truefalse');document.getElementById('numericalFields').classList.toggle('d-none',v!=='numerical');document.getElementById('matchingFields').classList.toggle('d-none',v!=='matching')}
 type.addEventListener('change',sync); for(let i=0;i<4;i++)addAnswer(); for(let i=0;i<3;i++)addPair(); sync();
});
</script>
