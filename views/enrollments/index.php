<?php use Tecnodata\Lms\Core\Csrf; ?>
<div class="page-heading">
  <div>
    <span class="eyebrow">ACADÊMICO</span>
    <h1>Matrículas</h1>
    <p>Controle manual e auditoria das matrículas recebidas pelas integrações.</p>
  </div>
</div>

<section class="panel mb-4">
  <h2>Nova matrícula manual</h2>
  <form method="post" action="/admin/enrollments" class="row g-2 align-items-end">
    <?=Csrf::field()?>
    <div class="col-md-4">
      <label class="form-label">CPF do aluno</label>
      <input class="form-control" name="cpf" inputmode="numeric" maxlength="14" required>
    </div>
    <div class="col-md-6">
      <label class="form-label">Curso</label>
      <select class="form-select" name="course_id" required>
        <option value="">Selecione...</option>
        <?php foreach($courses as $c): ?>
          <option value="<?=$c['id']?>"><?=e($c['name'])?> · <?=e($c['code'])?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <button class="btn btn-brand w-100">Matricular</button>
    </div>
  </form>
</section>

<section class="panel">
  <div class="table-responsive">
    <table class="table align-middle datatable">
      <thead>
        <tr>
          <th>ID</th><th>Aluno</th><th>CPF</th><th>Curso</th><th>Progresso</th><th>Status</th><th>Ação</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach($enrollments as $e): ?>
        <tr>
          <td><?=$e['id']?></td>
          <td><strong><?=e($e['student'])?></strong></td>
          <td><?=e($e['cpf'])?></td>
          <td><?=e($e['course'])?><small class="d-block text-secondary"><?=e($e['course_code'])?></small></td>
          <td><?=e($e['progress_percent'])?>%</td>
          <td><span class="status-badge"><?=e($e['status'])?></span></td>
          <td>
            <form class="d-flex gap-2" method="post" action="/admin/enrollments/<?=$e['id']?>/status">
              <?=Csrf::field()?>
              <select class="form-select form-select-sm" name="status">
                <?php foreach(['active'=>'Ativa','suspended'=>'Suspensa','cancelled'=>'Cancelada','completed'=>'Concluída'] as $v=>$label): ?>
                  <option value="<?=$v?>" <?=$e['status']===$v?'selected':''?>><?=$label?></option>
                <?php endforeach; ?>
              </select>
              <button class="btn btn-sm btn-outline-secondary">Salvar</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
