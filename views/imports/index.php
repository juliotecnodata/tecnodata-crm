<?php use Tecnodata\Lms\Core\Csrf; ?>
<div class="page-heading">
  <div>
    <span class="eyebrow">MIGRAÇÃO</span>
    <h1>Importar Moodle</h1>
    <p>Analise primeiro. Nenhum tipo desconhecido é descartado silenciosamente.</p>
  </div>
</div>

<?php if($message): ?>
<div class="alert alert-info"><?=e($message)?></div>
<?php endif; ?>

<?php if($analysis): $a=$analysis['data']; ?>
<section class="panel mb-4">
<div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
  <div>
    <h2><?=e($a['course']['fullname'])?></h2>
    <p class="text-secondary mb-2">
      <?=e($a['course']['shortname'])?>
      · <?=$a['sections_count']?> seções
      · <?=count($a['activities'])?> atividades
      · <?=$a['questions_count']?> questões
      · <?=$a['files_count']??0?> referências de arquivos
    </p>

    <?php foreach($a['activity_counts'] as $t=>$n): ?>
      <span class="type-pill"><?=e($t)?> <?=$n?></span>
    <?php endforeach; ?>

    <?php if(!empty($a['has_users'])): ?>
      <div class="alert alert-warning mt-3 mb-0">
        Este backup contém dados de usuários. Para migração estrutural, prefira um backup <strong>sem usuários (-nu)</strong>.
      </div>
    <?php endif; ?>
  </div>

  <span class="compat <?=$a['compatible']?'ok':'warn'?>">
    <?=$a['compatible']?'Estrutura compatível':'Revisão necessária'?>
  </span>
</div>

<?php if(!$a['compatible']): ?>
<div class="alert alert-warning mt-3">
  Sem conversor estrutural: <?=e(implode(', ',$a['unknown_types']))?>.
  A importação definitiva está bloqueada.
</div>
<?php else: ?>
<div class="alert alert-success mt-3">
  A estrutura detectada pode ser importada para um curso em rascunho. Revise o curso antes de publicar.
</div>
<form method="post" action="/admin/imports/<?=$analysis['id']?>/execute">
  <?=Csrf::field()?>
  <button class="btn btn-brand">Importar curso em rascunho</button>
</form>
<?php endif; ?>
</section>
<?php endif; ?>

<section class="panel mb-4">
<h2>Novo backup .mbz</h2>
<p class="text-secondary">Recomendado: backup sem usuários para estrutura e conteúdo.</p>
<form method="post" enctype="multipart/form-data" action="/admin/imports">
  <?=Csrf::field()?>
  <div class="input-group">
    <input class="form-control" type="file" name="backup" accept=".mbz" required>
    <button class="btn btn-brand">Analisar</button>
  </div>
</form>
</section>

<section class="panel">
<h2>Histórico</h2>
<div class="table-responsive">
<table class="table align-middle">
<thead><tr><th>#</th><th>Arquivo</th><th>Status</th><th>Data</th></tr></thead>
<tbody>
<?php foreach($imports as $i): ?>
<tr>
  <td><?=$i['id']?></td>
  <td><?=e($i['original_name'])?></td>
  <td><span class="status-badge"><?=e($i['status'])?></span></td>
  <td><?=e($i['created_at'])?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</section>
