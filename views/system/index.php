<?php use Tecnodata\Lms\Core\Csrf; ?>
<div class="page-heading">
  <div>
    <span class="eyebrow">SISTEMA</span>
    <h1>Configurações e atualizações</h1>
    <p>Estado técnico, migrations e padrões globais.</p>
  </div>
</div>
<?php if($message):?><div class="alert alert-success"><?=e($message)?></div><?php endif;?>

<div class="row g-3 mb-4">
  <div class="col-md-4"><div class="metric"><span>Ambiente</span><strong style="font-size:20px"><?=e(envv('APP_ENV','production'))?></strong></div></div>
  <div class="col-md-4"><div class="metric"><span>Atualizações pendentes</span><strong><?=count($pending)?></strong></div></div>
  <div class="col-md-4"><div class="metric"><span>Banco biométrico</span><strong style="font-size:20px"><?=$bioReady?'Conectado':($bioConfigured?'Indisponível':'Não configurado')?></strong></div></div>
</div>

<section class="panel mb-4">
<h2>Atualizações de banco</h2>
<?php if($pending):?>
<p class="text-secondary"><?=e(implode(', ',array_map('basename',$pending)))?></p>
<form method="post" action="/admin/system/update"><?=Csrf::field()?><button class="btn btn-brand">Aplicar atualizações</button></form>
<?php else:?><p class="text-success mb-0">Banco atualizado.</p><?php endif;?>
</section>

<section class="panel">
<h2>Padrões globais</h2>
<?php $map=[];foreach($settings as $s)$map[$s['setting_key']]=$s['setting_value'];?>
<form method="post" action="/admin/system/settings" class="row g-3">
<?=Csrf::field()?>
<div class="col-md-6"><label class="form-label">URL do suporte</label><input class="form-control" name="support_url" value="<?=e($map['support_url']??'')?>"></div>
<div class="col-md-6"><label class="form-label">E-mail do suporte</label><input class="form-control" name="support_email" value="<?=e($map['support_email']??'')?>"></div>
<div class="col-md-4"><label class="form-label">Prazo padrão de matrícula (dias)</label><input class="form-control" type="number" min="0" name="default_enrollment_days" value="<?=e($map['default_enrollment_days']??'45')?>"></div>
<div class="col-md-4"><label class="form-label">Aluno pode editar perfil?</label><select class="form-select" name="allow_student_profile_edit"><option value="0" <?=($map['allow_student_profile_edit']??'0')==='0'?'selected':''?>>Não</option><option value="1" <?=($map['allow_student_profile_edit']??'0')==='1'?'selected':''?>>Sim</option></select></div>
<div class="col-12"><label class="form-label">Mensagem de manutenção</label><textarea class="form-control" rows="3" name="maintenance_message"><?=e($map['maintenance_message']??'')?></textarea></div>
<div class="col-12"><button class="btn btn-brand">Salvar configurações</button></div>
</form>
</section>
