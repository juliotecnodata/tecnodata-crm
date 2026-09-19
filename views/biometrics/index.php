<?php use Tecnodata\Lms\Core\Csrf; ?>
<div class="page-heading">
  <div>
    <span class="eyebrow">VALIDAÇÃO DE IDENTIDADE</span>
    <h1>Perfis de biometria</h1>
    <p>O LMS guarda regras. Capturas, referências e tentativas ficam em um banco biométrico separado.</p>
  </div>
</div>

<?php if($message): ?><div class="alert alert-success"><?=e($message)?></div><?php endif; ?>

<section class="panel mb-4">
  <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
    <div>
      <h2>Banco biométrico separado</h2>
      <?php if(!$dbConfigured): ?>
        <p class="text-secondary mb-0">Ainda não configurado. Preencha BIO_DB_* no .env.</p>
      <?php elseif($dbReady): ?>
        <p class="text-success mb-0"><strong>Conectado.</strong> A estrutura pode ser usada pelo motor facial.</p>
      <?php else: ?>
        <p class="text-danger mb-0"><strong>Configurado, mas indisponível.</strong> Revise host, usuário, senha e permissão remota.</p>
      <?php endif; ?>
    </div>
    <?php if($dbConfigured): ?>
    <form method="post" action="/admin/biometric-profiles/install-database">
      <?=Csrf::field()?>
      <button class="btn btn-outline-secondary">Inicializar / conferir estrutura</button>
    </form>
    <?php endif; ?>
  </div>
</section>

<div class="row g-3">
<?php foreach($profiles as $p): ?>
<div class="col-xl-4">
<section class="panel h-100">
  <div class="d-flex justify-content-between gap-2">
    <div>
      <span class="type-pill"><?=e($p['code'])?></span>
      <h2 class="mt-2"><?=e($p['name'])?></h2>
    </div>
    <span class="status-badge"><?=$p['enabled']?'Ativo':'Inativo'?></span>
  </div>

  <form method="post" action="/admin/biometric-profiles/<?=$p['id']?>">
    <?=Csrf::field()?>
    <div class="mb-2">
      <label class="form-label">Nome</label>
      <input class="form-control" name="name" value="<?=e($p['name'])?>" required>
    </div>
    <div class="mb-2">
      <label class="form-label">Descrição</label>
      <textarea class="form-control" name="description" rows="2"><?=e($p['description'])?></textarea>
    </div>
    <div class="mb-2">
      <label class="form-label">Método</label>
      <select class="form-select" name="verification_method">
        <?php foreach(['none'=>'Nenhum','face_match'=>'Comparação facial','face_match_liveness'=>'Facial + prova de vida'] as $v=>$label): ?>
        <option value="<?=$v?>" <?=$p['verification_method']===$v?'selected':''?>><?=$label?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="form-check"><input class="form-check-input" type="checkbox" name="first_access_required" id="fa<?=$p['id']?>" <?=$p['first_access_required']?'checked':''?>><label class="form-check-label" for="fa<?=$p['id']?>">Primeiro acesso</label></div>
    <div class="form-check"><input class="form-check-input" type="checkbox" name="course_entry_required" id="ce<?=$p['id']?>" <?=$p['course_entry_required']?'checked':''?>><label class="form-check-label" for="ce<?=$p['id']?>">Entrada no curso</label></div>
    <div class="form-check"><input class="form-check-input" type="checkbox" name="before_quiz_required" id="bq<?=$p['id']?>" <?=$p['before_quiz_required']?'checked':''?>><label class="form-check-label" for="bq<?=$p['id']?>">Antes de prova/quiz</label></div>
    <div class="form-check"><input class="form-check-input" type="checkbox" name="liveness_required" id="lv<?=$p['id']?>" <?=$p['liveness_required']?'checked':''?>><label class="form-check-label" for="lv<?=$p['id']?>">Exigir prova de vida</label></div>
    <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="enabled" id="en<?=$p['id']?>" <?=$p['enabled']?'checked':''?>><label class="form-check-label" for="en<?=$p['id']?>">Perfil ativo</label></div>

    <div class="row g-2">
      <div class="col-6"><label class="form-label">Periódica (min)</label><input class="form-control" type="number" min="1" name="periodic_minutes" value="<?=e($p['periodic_minutes'])?>"></div>
      <div class="col-6"><label class="form-label">Máx. falhas</label><input class="form-control" type="number" min="1" name="max_failures" value="<?=e($p['max_failures'])?>"></div>
      <div class="col-6"><label class="form-label">Aleatória mín.</label><input class="form-control" type="number" min="1" name="random_min_minutes" value="<?=e($p['random_min_minutes'])?>"></div>
      <div class="col-6"><label class="form-label">Aleatória máx.</label><input class="form-control" type="number" min="1" name="random_max_minutes" value="<?=e($p['random_max_minutes'])?>"></div>
    </div>

    <button class="btn btn-brand mt-3">Salvar perfil</button>
  </form>
</section>
</div>
<?php endforeach; ?>
</div>

<section class="panel mt-4">
<h2>Cursos e perfil atual</h2>
<div class="table-responsive">
<table class="table align-middle datatable">
<thead><tr><th>Curso</th><th>Código</th><th>Perfil</th><th>Biometria</th></tr></thead>
<tbody>
<?php foreach($courses as $c): ?>
<tr>
<td><strong><?=e($c['name'])?></strong></td>
<td><?=e($c['code'])?></td>
<td><?=e($c['profile_name']?:'Ainda não definido')?></td>
<td><span class="status-badge"><?=$c['biometric_enabled']?'Ativa':'Não exige'?></span></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</section>
