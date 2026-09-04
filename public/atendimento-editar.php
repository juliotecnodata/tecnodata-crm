<?php
require dirname(__DIR__).'/app/bootstrap.php';
require APP_ROOT.'/app/Support/helpers.php';
use Tecnodata\CRM\Core\Auth;
use Tecnodata\CRM\Core\DB;
use Tecnodata\CRM\Core\Security;
use Tecnodata\CRM\Services\InteractionService;
use Tecnodata\CRM\Services\CollectionService;

Auth::requireLogin();$u=Auth::user();
$kind=(string)($_GET['kind']??$_POST['kind']??'sales');
$id=(int)($_GET['id']??$_POST['id']??0);
if(!in_array($kind,['sales','collection'],true)||$id<=0){http_response_code(400);exit('Registro inválido.');}

if($kind==='sales'){
 $row=DB::fetch("SELECT a.*,c.name client_name FROM activities a JOIN clients c ON c.id=a.client_id WHERE a.id=? AND a.deleted_at IS NULL",[$id]);
}else{
 $row=DB::fetch("SELECT ca.*,c.name client_name,author.name author_name,assigned.name assigned_name
                 FROM collection_actions ca
                 JOIN clients c ON c.id=ca.client_id
                 JOIN users author ON author.id=ca.user_id
                 LEFT JOIN users assigned ON assigned.id=ca.assigned_user_id
                 WHERE ca.id=? AND ca.deleted_at IS NULL",[$id]);
}
if(!$row){http_response_code(404);exit('Atendimento não encontrado.');}
$can=(int)$row['user_id']===(int)$u['id']||Auth::can('supervisor','admin');
if(!$can){http_response_code(403);exit('Sem permissão.');}
$back=$kind==='sales'?'cliente.php?id='.(int)$row['client_id']:'cobranca-cliente.php?id='.(int)$row['client_id'];
$err='';

if($_SERVER['REQUEST_METHOD']==='POST'){
 if(!Security::verifyCsrf($_POST['_token']??null))$err='Sessão expirada.';
 else try{
   if(($_POST['operation']??'save')==='delete'){
      if($kind==='sales')InteractionService::deleteSales($id,(int)$u['id']);
      else CollectionService::deleteAction($id,(int)$u['id']);
      header('Location: '.APP_URL.'/'.$back.'&msg=deleted');exit;
   }
   if($kind==='sales'){
      InteractionService::updateSales($id,(int)$u['id'],(string)($_POST['type']??'ligacao'),(string)($_POST['result']??'falou'),trim((string)($_POST['notes']??'')));
   }else{
      $assignedUserId=Auth::can('supervisor','admin')?(int)($_POST['assigned_user_id']??0):null;
      CollectionService::updateAction($id,(int)$u['id'],(string)($_POST['channel']??'ligacao'),(string)($_POST['result']??'falou'),
          (float)str_replace(',','.',(string)($_POST['amount']??0)),($_POST['promised_for']??'')?:null,trim((string)($_POST['notes']??'')),$assignedUserId);
   }
   header('Location: '.APP_URL.'/'.$back.'&msg=updated');exit;
 }catch(Throwable $e){$err=$e->getMessage();}
}
$assignees=$kind==='collection'&&Auth::can('supervisor','admin')
 ? DB::all("SELECT id,name,role FROM users WHERE active=1 AND role IN('collector','supervisor','admin') ORDER BY CASE role WHEN 'collector' THEN 1 WHEN 'supervisor' THEN 2 ELSE 3 END,name")
 : [];
include '_layout.php';?>
<?php if($err):?><div class="alert alert-danger alert-modern"><i class="fa-solid fa-triangle-exclamation"></i><?=e($err)?></div><?php endif;?>
<div class="page-heading"><div><a class="back-link" href="<?=APP_URL?>/<?=$back?>"><i class="fa-solid fa-arrow-left"></i>Voltar ao cliente</a><div class="eyebrow mt-3">CORREÇÃO DE ATENDIMENTO</div><h1><?=e($row['client_name'])?></h1><p>Edite um registro já fechado sem perder a trilha de auditoria.</p></div></div>
<div class="row"><div class="col-xl-7"><div class="panel-card"><div class="panel-header"><div><span><?=$kind==='sales'?'VENDAS':'COBRANÇA'?></span><h2>Editar atendimento</h2></div><span class="status-pill badge-muted"><?=date('d/m/Y H:i',strtotime($row['created_at']))?></span></div><div class="panel-body">
<form method="post"><input type="hidden" name="_token" value="<?=Security::csrf()?>"><input type="hidden" name="kind" value="<?=e($kind)?>"><input type="hidden" name="id" value="<?=$id?>">
<?php if($kind==='sales'):?>
<div class="mb-3"><label class="form-label">Canal</label><select class="form-select" name="type"><?php foreach(['ligacao'=>'Ligação','whatsapp'=>'WhatsApp','email'=>'E-mail','visita'=>'Visita'] as $v=>$l):?><option value="<?=$v?>" <?=$row['type']===$v?'selected':''?>><?=$l?></option><?php endforeach;?></select></div>
<div class="mb-3"><label class="form-label">Resultado</label><select class="form-select" name="result"><?php foreach(['falou'=>'Falou','nao_atendeu'=>'Não atendeu','interessado'=>'Interessado','acordo'=>'Acordo fechado','sem_interesse'=>'Sem interesse'] as $v=>$l):?><option value="<?=$v?>" <?=$row['result']===$v?'selected':''?>><?=$l?></option><?php endforeach;?></select></div>
<?php else:?>
<div class="ownership-box mb-4">
 <div><span>Realizado por</span><strong><?=e($row['author_name']??'—')?></strong><small><?=date('d/m/Y H:i',strtotime($row['created_at']))?> • autoria preservada</small></div>
 <div><span>Responsável atual</span>
 <?php if(Auth::can('supervisor','admin')):?>
  <select class="form-select mt-1" name="assigned_user_id">
   <?php foreach($assignees as $person):?><option value="<?=$person['id']?>" <?=(int)($row['assigned_user_id']??$row['user_id'])===(int)$person['id']?'selected':''?>><?=e($person['name'])?> • <?=e($person['role']==='collector'?'Cobrança':($person['role']==='supervisor'?'Supervisor':'Admin'))?></option><?php endforeach;?>
  </select>
  <small>Reatribuir não altera quem realizou o atendimento. A mudança fica na auditoria.</small>
 <?php else:?>
  <strong><?=e($row['assigned_name']??$row['author_name']??'—')?></strong><small>responsável atual pelo acompanhamento</small>
 <?php endif;?>
 </div>
</div>
<div class="mb-3"><label class="form-label">Canal</label><select class="form-select" name="channel"><?php foreach(['ligacao'=>'Ligação','whatsapp'=>'WhatsApp','email'=>'E-mail','outro'=>'Outro'] as $v=>$l):?><option value="<?=$v?>" <?=$row['channel']===$v?'selected':''?>><?=$l?></option><?php endforeach;?></select></div>
<div class="mb-3"><label class="form-label">Resultado</label><select class="form-select" name="result"><?php foreach(['falou'=>'Falou','nao_atendeu'=>'Não atendeu','sem_previsao'=>'Sem previsão','promessa'=>'Promessa de pagamento','acordo'=>'Acordo realizado','pagamento'=>'Pagamento recebido'] as $v=>$l):?><option value="<?=$v?>" <?=$row['result']===$v?'selected':''?>><?=$l?></option><?php endforeach;?></select></div>
<div class="row g-2"><div class="col-md-6 mb-3"><label class="form-label">Valor recebido</label><input class="form-control" type="number" min="0" step="0.01" name="amount" value="<?=e((string)$row['amount'])?>"></div><div class="col-md-6 mb-3"><label class="form-label">Promessa para</label><input class="form-control" type="date" name="promised_for" value="<?=e((string)$row['promised_for'])?>"></div></div>
<?php endif;?>
<div class="mb-3"><label class="form-label">Anotação</label><textarea class="form-control" rows="5" name="notes"><?=e((string)$row['notes'])?></textarea></div>
<div class="d-flex flex-wrap justify-content-between gap-2"><button class="btn btn-dark" name="operation" value="save"><i class="fa-solid fa-floppy-disk"></i>Salvar correção</button><button class="btn btn-outline-danger" type="submit" name="operation" value="delete" data-confirm="Excluir este atendimento? O registro sairá dos indicadores, mas a auditoria será preservada."><i class="fa-regular fa-trash-can"></i>Excluir atendimento</button></div>
</form></div></div></div>
<div class="col-xl-5"><div class="panel-card"><div class="panel-body"><div class="form-hint"><i class="fa-solid fa-shield-halved"></i>Alterações e exclusões ficam registradas em auditoria com usuário, data, valores anteriores e novos.</div><?php if($kind==='collection'&&(float)$row['amount']>0):?><div class="alert alert-light border mt-3 mb-0"><strong>Pagamento</strong><br><small>Se o valor ainda estiver pendente de reconciliação com o Omie, a correção ajustará automaticamente o saldo local e a meta de cobrança.</small></div><?php endif;?></div></div></div></div>
<?php include '_footer.php';?>
