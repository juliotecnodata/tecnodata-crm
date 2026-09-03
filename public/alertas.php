<?php
require dirname(__DIR__).'/app/bootstrap.php';
require APP_ROOT.'/app/Support/helpers.php';
use Tecnodata\CRM\Core\Auth;
use Tecnodata\CRM\Core\Security;
use Tecnodata\CRM\Services\NotificationService;
Auth::requireLogin();$u=Auth::user();$msg='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 if(!Security::verifyCsrf($_POST['_token']??null))$msg='Sessão expirada. Atualize a página.';
 else{NotificationService::saveSettings((int)$u['id'],$_POST);$msg='Preferências de alerta salvas.';}
}
$settings=NotificationService::settings((int)$u['id']);
include '_layout.php';?>
<?php if($msg):?><div class="alert alert-success alert-modern"><i class="fa-solid fa-circle-check"></i><?=e($msg)?></div><?php endif;?>
<div class="page-heading"><div><div class="eyebrow">PREFERÊNCIAS</div><h1>Alertas de atendimento</h1><p>Retornos comerciais e de cobrança, sem depender da Omie.</p></div></div>
<div class="row g-4">
<div class="col-xl-7"><div class="panel-card"><div class="panel-header"><div><span>NAVEGADOR</span><h2>Como você quer ser avisado</h2></div><span class="status-pill badge-muted" id="browserPermissionStatus">Verificando…</span></div><div class="panel-body">
<div class="alert-permission-box mb-4"><div class="alert-permission-icon"><i class="fa-regular fa-bell"></i></div><div><strong>Notificação nativa do navegador</strong><small>Com o CRM aberto em qualquer aba, o aviso aparece mesmo se você estiver trabalhando em outra página.</small></div><button type="button" class="btn btn-dark btn-sm" id="enableBrowserNotifications">Ativar</button></div>
<form method="post"><input type="hidden" name="_token" value="<?=Security::csrf()?>">
<div class="settings-list">
<label class="setting-row"><div><strong>Notificação do navegador</strong><small>Mostrar aviso do sistema operacional.</small></div><span class="form-check form-switch"><input class="form-check-input" type="checkbox" name="browser_enabled" value="1" <?=$settings['browser_enabled']?'checked':''?>></span></label>
<label class="setting-row"><div><strong>Som do CRM</strong><small>Toque curto somente em alertas importantes.</small></div><span class="form-check form-switch"><input class="form-check-input" type="checkbox" name="sound_enabled" value="1" <?=$settings['sound_enabled']?'checked':''?>></span></label>
<div class="setting-row"><div><strong>Volume</strong><small>Apenas para o toque gerado pelo CRM.</small></div><div class="volume-control"><input type="range" min="0" max="100" step="5" name="volume" value="<?=(int)$settings['volume']?>" id="alertVolume"><span id="alertVolumeValue"><?=(int)$settings['volume']?>%</span></div></div>
<div class="setting-row"><div><strong>Avisar antes</strong><small>Primeiro aviso antes do horário agendado.</small></div><select class="form-select setting-select" name="pre_alert_minutes"><?php foreach([0=>'Não avisar antes',5=>'5 minutos',10=>'10 minutos',15=>'15 minutos',30=>'30 minutos'] as $v=>$l):?><option value="<?=$v?>" <?=(int)$settings['pre_alert_minutes']===$v?'selected':''?>><?=$l?></option><?php endforeach;?></select></div>
<div class="setting-row"><div><strong>Repetir se continuar pendente</strong><small>Uma repetição; depois permanece como atrasado no sino.</small></div><select class="form-select setting-select" name="repeat_after_minutes"><?php foreach([0=>'Não repetir',15=>'15 minutos',30=>'30 minutos',60=>'60 minutos'] as $v=>$l):?><option value="<?=$v?>" <?=(int)$settings['repeat_after_minutes']===$v?'selected':''?>><?=$l?></option><?php endforeach;?></select></div>
</div>
<div class="d-flex gap-2 mt-4"><button class="btn btn-dark"><i class="fa-solid fa-floppy-disk"></i>Salvar preferências</button><button class="btn btn-outline-secondary" type="button" id="testAlert"><i class="fa-regular fa-bell"></i>Testar alerta</button></div>
</form></div></div></div>
<div class="col-xl-5"><div class="panel-card"><div class="panel-header"><div><span>COMPORTAMENTO</span><h2>Fluxo adotado</h2></div></div><div class="panel-body"><div class="alert-flow">
<div><span>1</span><p><strong>Antes</strong><small>Aviso preventivo no tempo configurado.</small></p></div>
<div><span>2</span><p><strong>No horário</strong><small>Notificação + som + contador no sino.</small></p></div>
<div><span>3</span><p><strong>Se não for atendido</strong><small>Uma repetição e depois permanece marcado como atrasado.</small></p></div>
<div><span>4</span><p><strong>Ao registrar o atendimento</strong><small>O retorno correspondente é concluído automaticamente.</small></p></div>
</div><div class="form-hint mt-4"><i class="fa-solid fa-shield-halved"></i>Os alertas consultam somente o banco local a cada minuto. Nenhuma chamada é feita à Omie.</div></div></div></div>
</div>
<script>
document.addEventListener('DOMContentLoaded',()=>{
 const status=document.getElementById('browserPermissionStatus'),enable=document.getElementById('enableBrowserNotifications'),volume=document.getElementById('alertVolume'),volumeValue=document.getElementById('alertVolumeValue'),test=document.getElementById('testAlert');
 function updateStatus(){
  if(!('Notification'in window)){status.textContent='Não suportado';status.className='status-pill status-overdue';enable.disabled=true;return;}
  const p=Notification.permission;status.textContent=p==='granted'?'Ativado':(p==='denied'?'Bloqueado':'Não autorizado');status.className='status-pill '+(p==='granted'?'status-paid':(p==='denied'?'status-overdue':'status-partial'));enable.textContent=p==='granted'?'Ativado':'Ativar';enable.disabled=p==='granted';
 }
 enable?.addEventListener('click',async()=>{if('Notification'in window){await Notification.requestPermission();updateStatus();}});
 volume?.addEventListener('input',()=>volumeValue.textContent=volume.value+'%');
 test?.addEventListener('click',async()=>{if(window.TDCRMAlerts){await window.TDCRMAlerts.unlockSound();window.TDCRMAlerts.playChime(Number(volume?.value||70));window.TDCRMAlerts.showBrowserNotification('Teste de alerta','Tecnodata CRM • seus alertas estão funcionando.',location.href,'tdcrm-test');}});
 updateStatus();
});
</script>
<?php include '_footer.php';?>
