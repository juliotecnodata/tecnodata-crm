<?php
require dirname(__DIR__) . '/app/bootstrap.php';
require APP_ROOT . '/app/Support/helpers.php';
use Tecnodata\CRM\Core\Auth; use Tecnodata\CRM\Core\DB; use Tecnodata\CRM\Core\Security; use Tecnodata\CRM\Services\GoalService; use Tecnodata\CRM\Services\InteractionService; use Tecnodata\CRM\Services\PurchaseCycleService;
Auth::requireLogin(); $u=Auth::user(); $id=(int)($_GET['id']??0);
$c=DB::fetch("SELECT c.*,m.*,s.name seller_name FROM clients c LEFT JOIN client_metrics m ON m.client_id=c.id LEFT JOIN sellers s ON s.omie_code=m.seller_omie_code WHERE c.id=?",[$id]);
if(!$c){http_response_code(404);exit('Cliente não encontrado');}
$profile=$u['role']==='seller'&&$u['seller_omie_code']?DB::fetch('SELECT goal_mode FROM sellers WHERE omie_code=? AND active=1',[$u['seller_omie_code']]):null;
$mode=(string)($profile['goal_mode']??'sales_collection');$hasSales=$u['role']!=='seller'||GoalService::hasSales($mode);$hasCollection=$u['role']!=='seller'||GoalService::hasCollection($mode);
$isDebtor=(bool)DB::fetch("SELECT 1 found FROM financial_movements fm INNER JOIN financial_accounts fa ON fa.omie_code=fm.account_omie_code AND fa.selected=1 WHERE fm.client_omie_code=? AND UPPER(fm.status) IN('ATRASADO','PAGTO_PARCIAL') LIMIT 1",[$c['omie_code']]);
if($u['role']==='seller'){$own=(string)$c['seller_omie_code']===(string)$u['seller_omie_code'];if(!(($hasSales&&$own)||($hasCollection&&$isDebtor))){http_response_code(403);exit('Sem acesso');}}
$msg='';
if($_SERVER['REQUEST_METHOD']==='POST' && Security::verifyCsrf($_POST['_token']??null)){
 if(($_POST['form_action']??'commercial')==='collection'&&$hasCollection&&$isDebtor){
  $action=(string)($_POST['action_type']??'contact');if(!in_array($action,['contact','promise','agreement','payment'],true))$action='contact';
  $amount=max(0,(float)str_replace(',','.',(string)($_POST['amount']??0)));
  if($action==='payment'&&$amount<=0)$msg='Informe o valor recuperado para registrar o pagamento.';
  else{DB::exec("INSERT INTO collection_actions(seller_omie_code,client_id,user_id,action_type,amount,promised_for,notes,created_at) VALUES(?,?,?,?,?,?,?,NOW())",[(string)($u['seller_omie_code']??$c['seller_omie_code']),$id,$u['id'],$action,$amount,($_POST['promised_for']??'')?:null,trim((string)($_POST['notes']??''))]);$msg='Ação de cobrança registrada.';}
 }elseif($hasSales){
  $type=$_POST['type']??'ligacao';$result=$_POST['result']??'falou';$notes=trim($_POST['notes']??'');
  InteractionService::registerSales($id,(int)$u['id'],$type,$result,$notes,($_POST['next_at']??'')?:null);
  $msg='Atendimento salvo.';
 }
}
$acts=DB::all("SELECT a.*,u.name user_name FROM activities a JOIN users u ON u.id=a.user_id WHERE a.client_id=? AND a.deleted_at IS NULL ORDER BY a.created_at DESC LIMIT 30",[$id]);
$orders=DB::all("SELECT * FROM orders WHERE client_omie_code=? ORDER BY order_date DESC LIMIT 10",[$c['omie_code']]);
$debts=DB::all("SELECT fm.* FROM financial_movements fm INNER JOIN financial_accounts fa ON fa.omie_code=fm.account_omie_code AND fa.selected=1 WHERE fm.client_omie_code=? AND UPPER(fm.status) IN('ATRASADO','PAGTO_PARCIAL') ORDER BY fm.due_date",[$c['omie_code']]);
$collectionActions=$hasCollection?DB::all("SELECT ca.*,u.name user_name FROM collection_actions ca JOIN users u ON u.id=ca.user_id WHERE ca.client_id=? AND ca.deleted_at IS NULL ORDER BY ca.created_at DESC LIMIT 20",[$id]):[];
if(!$hasCollection)$debts=[];
$debtAmount=array_sum(array_map(fn($debt)=>(float)$debt['amount'],$debts));
$c['overdue_amount']=$debtAmount;$c['open_amount']=$debtAmount;
$cycle=PurchaseCycleService::analyze($c['last_purchase_at']??null,$c['avg_interval_days']??null,isset($c['days_without_purchase'])?(int)$c['days_without_purchase']:null);
include '_layout.php';?>
<?php if($msg):?><div class="alert alert-success"><?=$msg?></div><?php endif;?>
<div class="d-flex flex-wrap justify-content-between gap-3 mb-4">
<div><div class="kicker"><?=e($c['omie_code'])?> • <?=e($c['uf'])?></div><h1 class="h3 mb-1"><?=e($c['name'])?></h1><div class="text-secondary"><?=e($c['seller_name']??'Sem vendedor')?><?=($c['phone']?' • '.$c['phone']:'')?></div></div>
<div class="quick-actions d-flex gap-2 align-items-start"><?php if($c['phone']):?><a class="btn btn-outline-secondary" href="tel:<?=preg_replace('/\D/','',$c['phone'])?>"><i class="fa-solid fa-phone me-2"></i>Ligar</a><a class="btn btn-outline-secondary" target="_blank" href="https://wa.me/55<?=preg_replace('/\D/','',$c['phone'])?>"><i class="fa-brands fa-whatsapp me-2"></i>WhatsApp</a><?php endif;?></div>
</div>
<div class="client-intelligence-grid mb-4">
<div class="client-intelligence-card"><span>Momento de compra</span><strong><span class="cycle-chip cycle-<?=e($cycle['tone'])?>"><?=e($cycle['label'])?></span></strong><small><?=e($cycle['hint'])?></small></div>
<div class="client-intelligence-card"><span>Próxima compra estimada</span><strong><?=$cycle['expected_date']?brdate($cycle['expected_date']):'—'?></strong><small><?=$cycle['interval']?'ciclo médio de '.$cycle['interval'].' dias':'histórico insuficiente para estimativa'?></small></div>
<div class="client-intelligence-card"><span>Histórico 12 meses</span><strong><?=money($c['revenue_12m'])?></strong><small><?=$c['orders_12m']?> compra(s) • ticket <?=money($c['avg_ticket_12m'])?></small></div>
<div class="client-intelligence-card"><span>Financeiro</span><strong><?=$c['overdue_amount']>0?money($c['overdue_amount']).' vencido':'Em dia'?></strong><small><?=money($c['open_amount'])?> em aberto</small></div>
</div>
<div class="row g-4">
<div class="col-lg-5">
<?php if($debts&&!empty($c['seller_omie_code'])):?><div class="panel-card mb-4"><div class="panel-header"><div><span>INADIMPLÊNCIA</span><h2>Registrar cobrança</h2></div><strong><?=money($debtAmount)?></strong></div><div class="panel-body">
<form method="post"><input type="hidden" name="_token" value="<?=Security::csrf()?>"><input type="hidden" name="form_action" value="collection">
<div class="mb-3"><label class="form-label">Ação realizada</label><select class="form-select" name="action_type"><option value="contact">Contato</option><option value="promise">Promessa de pagamento</option><option value="agreement">Acordo</option><option value="payment">Pagamento confirmado</option></select></div>
<div class="row g-2 mb-3"><div class="col-6"><label class="form-label">Valor recuperado</label><input class="form-control" name="amount" type="number" min="0" step="0.01" value="0"></div><div class="col-6"><label class="form-label">Data prometida</label><input class="form-control" name="promised_for" type="date"></div></div>
<div class="mb-3"><label class="form-label">Observação</label><textarea class="form-control" rows="3" name="notes" placeholder="Resumo da negociação"></textarea></div><button class="btn btn-primary w-100">Salvar ação de cobrança</button>
</form></div></div><?php endif;?>
<?php if($hasSales):?><div class="card"><div class="card-body">
<h2 class="h5 mb-3">Novo atendimento</h2>
<form method="post"><input type="hidden" name="_token" value="<?=Security::csrf()?>"><input type="hidden" name="form_action" value="commercial">
<label class="form-label">O que você fez?</label>
<div class="row g-2 mb-3">
<?php foreach(['ligacao'=>'Ligação','whatsapp'=>'WhatsApp','email'=>'E-mail','visita'=>'Visita'] as $v=>$l):?>
<div class="col-6"><input class="btn-check" type="radio" name="type" value="<?=$v?>" id="t<?=$v?>" <?=$v==='ligacao'?'checked':''?>><label class="btn btn-outline-dark w-100" for="t<?=$v?>"><?=$l?></label></div>
<?php endforeach;?></div>
<div class="mb-3"><label class="form-label">Resultado</label><select class="form-select" name="result"><option value="falou">Falou</option><option value="nao_atendeu">Não atendeu</option><option value="interessado">Interessado</option><option value="acordo">Acordo fechado</option><option value="sem_interesse">Sem interesse</option></select></div>
<div class="mb-3"><label class="form-label">Anotação</label><textarea class="form-control" rows="4" name="notes" placeholder="Opcional quando não houver informação relevante"></textarea></div>
<div class="mb-3"><label class="form-label">Próximo retorno</label><input class="form-control" type="datetime-local" name="next_at"></div>
<button class="btn btn-dark w-100">Salvar atendimento</button>
</form></div></div><?php endif;?></div>
<div class="col-lg-7"><div class="card mb-4"><div class="card-body"><h2 class="h5 mb-3">Histórico</h2><div class="timeline">
<?php if($collectionActions):?><div class="mb-4"><div class="eyebrow mb-2">COBRANÇA</div><?php $collectionLabels=['contact'=>'Contato','promise'=>'Promessa','agreement'=>'Acordo','payment'=>'Pagamento'];foreach($collectionActions as $action):?><div class="activity-row px-0"><div class="activity-icon type-<?=e($action['action_type'])?>"><i class="fa-solid fa-hand-holding-dollar"></i></div><div><strong><?=e($collectionLabels[$action['action_type']]??$action['action_type'])?></strong><p><?=e($action['notes']?:'Sem observação')?><?php if((float)$action['amount']>0):?> • <?=money($action['amount'])?><?php endif;?></p></div><div class="activity-tail"><time><?=date('d/m H:i',strtotime($action['created_at']))?></time><?php if((int)$action['user_id']===(int)$u['id']||Auth::can('supervisor','admin')):?><a class="activity-edit-link" href="<?=APP_URL?>/atendimento-editar.php?kind=collection&id=<?=$action['id']?>"><i class="fa-solid fa-pen"></i></a><?php endif;?></div></div><?php endforeach;?></div><?php endif;?>
<?php if(!$acts):?><div class="text-secondary">Nenhum atendimento registrado.</div><?php endif;?>
<?php foreach($acts as $a):?><div class="timeline-item"><div class="d-flex justify-content-between gap-2"><div class="fw-semibold"><?=date('d/m/Y H:i',strtotime($a['created_at']))?> • <?=e($a['user_name'])?></div><?php if((int)$a['user_id']===(int)$u['id']||Auth::can('supervisor','admin')):?><a class="activity-edit-link" href="<?=APP_URL?>/atendimento-editar.php?kind=sales&id=<?=$a['id']?>" title="Editar atendimento"><i class="fa-solid fa-pen"></i></a><?php endif;?></div><div class="small text-secondary text-capitalize"><?=e(str_replace('_',' ',$a['type']))?> • <?=e(str_replace('_',' ',$a['result']))?><?php if($a['result']==='acordo'):?> <span class="signal-badge signal-agreement ms-1"><i class="fa-solid fa-handshake"></i>Acordo</span><?php endif;?></div><?php if($a['notes']):?><div class="mt-1"><?=nl2br(e($a['notes']))?></div><?php endif;?></div><?php endforeach;?>
</div></div></div>
<div class="card"><div class="card-body"><div class="d-flex justify-content-between align-items-center gap-3 mb-2"><div><div class="eyebrow">HISTÓRICO DE COMPRA</div><h2 class="h5 mb-0">Últimas compras</h2></div><span class="cycle-chip cycle-<?=e($cycle['tone'])?>"><?=e($cycle['label'])?></span></div><p class="text-secondary small mb-3"><?=$cycle['interval']?'Intervalo médio entre compras: '.$cycle['interval'].' dias. Próxima compra estimada: '.brdate($cycle['expected_date']).'.':'Ainda não há histórico suficiente para calcular um padrão confiável de recompra.'?></p><?php foreach($orders as $o):?><div class="d-flex justify-content-between border-top py-2"><span><?=brdate($o['order_date'])?></span><strong><?=money($o['total'])?></strong></div><?php endforeach;?></div></div>
</div></div>
<?php include '_footer.php';?>
