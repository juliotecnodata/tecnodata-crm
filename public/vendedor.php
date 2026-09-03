<?php
require dirname(__DIR__).'/app/bootstrap.php';
require APP_ROOT.'/app/Support/helpers.php';
use Tecnodata\CRM\Core\Auth;
use Tecnodata\CRM\Core\DB;
use Tecnodata\CRM\Core\Security;
use Tecnodata\CRM\Services\GoalService;

Auth::requireLogin();
if(!Auth::can('supervisor','admin')){http_response_code(403);exit;}
$id=(int)($_GET['id']??$_POST['seller_id']??0);
$month=$_GET['month']??$_POST['month_ref']??date('Y-m');
$seller=DB::fetch('SELECT * FROM sellers WHERE id=?',[$id]);
if(!$seller){http_response_code(404);exit('Vendedor não encontrado.');}
$message='';$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 if(!Security::verifyCsrf($_POST['_token']??null)){$error='Sua sessão expirou. Recarregue a página.';}
 else{
  try{
   $action=(string)($_POST['form_action']??'');
   if($action==='profile'){
    $mode=in_array($_POST['goal_mode']??'', ['sales','collection','sales_collection'],true)?$_POST['goal_mode']:'collection';
    DB::exec('UPDATE sellers SET active=?,goal_mode=?,is_virtual=?,updated_at=NOW() WHERE id=?',[!empty($_POST['active'])?1:0,$mode,!empty($_POST['is_virtual'])?1:0,$id]);
    $message='Perfil do vendedor atualizado.';
   }elseif($action==='goal'){
    $money=fn($value)=>(float)str_replace(',','.',(string)$value);
    DB::exec("INSERT INTO seller_goals(month_ref,seller_omie_code,goal1,goal2,goal3,collection_goal,debtor_contact_goal,created_at,updated_at)
              VALUES(?,?,?,?,?, ?,?,NOW(),NOW()) ON DUPLICATE KEY UPDATE goal1=VALUES(goal1),goal2=VALUES(goal2),goal3=VALUES(goal3),
              collection_goal=VALUES(collection_goal),debtor_contact_goal=VALUES(debtor_contact_goal),updated_at=NOW()",
      [$month,$seller['omie_code'],$money($_POST['goal1']??0),$money($_POST['goal2']??0),$seller['goal_mode']==='sales'?$money($_POST['goal3']??0):0,$money($_POST['collection_goal']??0),max(0,(int)($_POST['debtor_contact_goal']??0))]);
    $message='Metas do mês salvas.';
   }elseif($action==='link_user'){
    $userId=(int)($_POST['user_id']??0);
    DB::exec('UPDATE users SET seller_omie_code=NULL WHERE seller_omie_code=?',[$seller['omie_code']]);
    if($userId) DB::exec("UPDATE users SET seller_omie_code=? WHERE id=? AND role='seller'",[$seller['omie_code'],$userId]);
    $message='Usuário vinculado atualizado.';
   }elseif($action==='collection_action'){
    if(!GoalService::hasCollection((string)$seller['goal_mode'])) throw new RuntimeException('Este perfil não possui permissão de cobrança.');
    $clientId=(int)($_POST['client_id']??0);
    $type=(string)($_POST['action_type']??'contact');
    if(!in_array($type,['contact','promise','agreement','payment'],true)) throw new RuntimeException('Ação inválida.');
    $debtor=DB::fetch("SELECT c.id FROM clients c INNER JOIN financial_movements fm ON fm.client_omie_code=c.omie_code INNER JOIN financial_accounts fa ON fa.omie_code=fm.account_omie_code AND fa.selected=1 WHERE c.id=? AND UPPER(fm.status) IN('ATRASADO','PAGTO_PARCIAL') LIMIT 1",[$clientId]);
    if(!$debtor) throw new RuntimeException('Selecione um devedor da carteira deste vendedor.');
    $amount=(float)str_replace(',','.',(string)($_POST['amount']??0));
    if($type==='payment'&&$amount<=0) throw new RuntimeException('Informe o valor recuperado.');
    DB::exec('INSERT INTO collection_actions(seller_omie_code,client_id,user_id,action_type,amount,promised_for,notes,created_at) VALUES(?,?,?,?,?,?,?,NOW())',
      [$seller['omie_code'],$clientId,Auth::id(),$type,max(0,$amount),($_POST['promised_for']??'')?:null,trim((string)($_POST['notes']??''))]);
    $message='Ação de cobrança registrada.';
   }
  }catch(Throwable $e){$error=$e->getMessage();}
 }
 $seller=DB::fetch('SELECT * FROM sellers WHERE id=?',[$id]);
}
$goal=GoalService::sellerMonth((string)$seller['omie_code'],$month);
$hasSales=GoalService::hasSales((string)$seller['goal_mode']);$hasCollection=GoalService::hasCollection((string)$seller['goal_mode']);
$stored=DB::fetch('SELECT * FROM seller_goals WHERE month_ref=? AND seller_omie_code=?',[$month,$seller['omie_code']])??[];
$users=DB::all("SELECT id,name,email,seller_omie_code FROM users WHERE role='seller' AND active=1 ORDER BY name");
$linked=DB::fetch("SELECT id,name,email FROM users WHERE seller_omie_code=? AND active=1 LIMIT 1",[$seller['omie_code']]);
$debtors=$hasCollection?DB::all("SELECT c.id,c.name,c.omie_code,MIN(fm.due_date) due_date,SUM(fm.amount) overdue_amount
                  FROM clients c INNER JOIN financial_movements fm ON fm.client_omie_code=c.omie_code INNER JOIN financial_accounts fa ON fa.omie_code=fm.account_omie_code AND fa.selected=1
                  WHERE UPPER(fm.status) IN('ATRASADO','PAGTO_PARCIAL') GROUP BY c.id,c.name,c.omie_code ORDER BY overdue_amount DESC"):[];
$actions=DB::all("SELECT ca.*,c.name client_name,u.name user_name FROM collection_actions ca INNER JOIN clients c ON c.id=ca.client_id INNER JOIN users u ON u.id=ca.user_id WHERE ca.deleted_at IS NULL AND ca.seller_omie_code=? ORDER BY ca.created_at DESC LIMIT 30",[$seller['omie_code']]);
$monthStart=$month.'-01';$monthEnd=date('Y-m-t',strtotime($monthStart));
$monthlyOrders=DB::all("SELECT o.*,c.name client_name FROM orders o LEFT JOIN clients c ON c.omie_code=o.client_omie_code WHERE o.seller_omie_code=? AND o.order_date BETWEEN ? AND ? ORDER BY o.order_date DESC,o.id DESC",[$seller['omie_code'],$monthStart,$monthEnd]);
$monthlyServices=DB::all("SELECT so.*,c.name client_name FROM service_orders so LEFT JOIN clients c ON c.omie_code=so.client_omie_code WHERE so.seller_omie_code=? AND so.inclusion_date BETWEEN ? AND ? ORDER BY so.inclusion_date DESC,so.id DESC",[$seller['omie_code'],$monthStart,$monthEnd]);
include '_layout.php';?>
<?php if($message):?><div class="alert alert-success alert-modern"><i class="fa-solid fa-circle-check"></i><?=e($message)?></div><?php endif;?>
<?php if($error):?><div class="alert alert-danger alert-modern"><i class="fa-solid fa-circle-exclamation"></i><?=e($error)?></div><?php endif;?>
<div class="page-heading">
 <div><a class="back-link" href="<?=APP_URL?>/vendedores.php"><i class="fa-solid fa-arrow-left"></i> Vendedores</a><div class="d-flex align-items-center gap-3 mt-3"><div class="avatar avatar-lg"><?=e(mb_strtoupper(mb_substr($seller['name'],0,1)))?></div><div><div class="d-flex align-items-center gap-2"><h1><?=e($seller['name'])?></h1><?php if($seller['is_virtual']):?><span class="badge-soft">Virtual</span><?php endif;?></div><p><?=e($seller['email']??'Sem e-mail na Omie')?> • código <?=e($seller['omie_code'])?></p></div></div></div>
 <input class="form-control month-control" type="month" value="<?=e($month)?>" onchange="location.href='?id=<?=$id?>&month='+this.value">
</div>
<div class="stat-grid stat-grid-4 mb-4">
 <?php if($hasCollection):?>
 <div class="stat-card"><span>Carteira devedora</span><strong><?=money($goal['debtor_amount'])?></strong><small><?=$goal['debtor_count']?> clientes</small></div>
 <div class="stat-card"><span>Recuperado no mês</span><strong><?=money($goal['collection_realized'])?></strong><small><?=number_format($goal['collection_percent'],1,',','.')?>% da meta</small></div>
 <div class="stat-card"><span>Devedores trabalhados</span><strong><?=$goal['debtors_worked']?></strong><small>meta de <?=$goal['contact_goal']?></small></div>
 <?php endif;?>
 <?php if($hasSales):?>
 <div class="stat-card"><span>Vendas no mês</span><strong><?=money($goal['realized'])?></strong><small>Produtos <?=money($goal['product_realized'])?> • serviços <?=money($goal['service_realized'])?></small></div>
 <?php endif;?>
</div>
<div class="row g-4">
 <div class="col-xl-4">
  <div class="panel-card mb-4"><div class="panel-header"><div><span>PERFIL</span><h2>Configuração</h2></div></div><div class="panel-body">
   <form method="post"><input type="hidden" name="_token" value="<?=Security::csrf()?>"><input type="hidden" name="seller_id" value="<?=$id?>"><input type="hidden" name="month_ref" value="<?=e($month)?>"><input type="hidden" name="form_action" value="profile">
    <label class="form-label">Modelo de meta</label><select class="form-select mb-3" name="goal_mode"><option value="sales" <?=$seller['goal_mode']==='sales'?'selected':''?>>Somente vendas</option><option value="collection" <?=$seller['goal_mode']==='collection'?'selected':''?>>Somente cobrança</option><option value="sales_collection" <?=$seller['goal_mode']==='sales_collection'?'selected':''?>>Vendas + cobrança</option></select>
    <label class="toggle-row"><span><strong>Vendedor ativo</strong><small>Aparece nas metas e na equipe</small></span><input class="form-check-input" type="checkbox" name="active" value="1" <?=$seller['active']?'checked':''?>></label>
    <label class="toggle-row mt-2"><span><strong>Vendedor virtual</strong><small>Opera por integrações, sem login obrigatório</small></span><input class="form-check-input" type="checkbox" name="is_virtual" value="1" <?=$seller['is_virtual']?'checked':''?>></label>
    <button class="btn btn-primary w-100 mt-3">Salvar perfil</button>
   </form>
  </div></div>
  <div class="panel-card"><div class="panel-header"><div><span>ACESSO</span><h2>Usuário vinculado</h2></div></div><div class="panel-body"><form method="post"><input type="hidden" name="_token" value="<?=Security::csrf()?>"><input type="hidden" name="seller_id" value="<?=$id?>"><input type="hidden" name="month_ref" value="<?=e($month)?>"><input type="hidden" name="form_action" value="link_user"><select class="form-select mb-3" name="user_id"><option value="">Sem usuário</option><?php foreach($users as $user):?><option value="<?=$user['id']?>" <?=($linked['id']??0)==$user['id']?'selected':''?>><?=e($user['name'])?> • <?=e($user['email'])?></option><?php endforeach;?></select><button class="btn btn-secondary w-100">Atualizar vínculo</button></form></div></div>
 </div>
 <div class="col-xl-8">
  <div class="panel-card mb-4"><div class="panel-header"><div><span>METAS • <?=date('m/Y',strtotime($month.'-01'))?></span><h2>Objetivos do vendedor</h2></div><span class="badge-soft"><?=e(['sales'=>'Somente vendas','collection'=>'Somente cobrança','sales_collection'=>'Vendas + cobrança'][$seller['goal_mode']]??'Cobrança')?></span></div><div class="panel-body">
   <form method="post"><input type="hidden" name="_token" value="<?=Security::csrf()?>"><input type="hidden" name="seller_id" value="<?=$id?>"><input type="hidden" name="month_ref" value="<?=e($month)?>"><input type="hidden" name="form_action" value="goal">
    <div class="row g-3"><?php if(GoalService::hasSales((string)$seller['goal_mode'])):?><div class="col-md-4"><label class="form-label">Meta 1 de vendas</label><div class="input-group"><span class="input-group-text">R$</span><input class="form-control" type="number" min="0" step="0.01" name="goal1" value="<?=e((string)($stored['goal1']??0))?>"></div></div><div class="col-md-4"><label class="form-label">Meta 2 de vendas</label><div class="input-group"><span class="input-group-text">R$</span><input class="form-control" type="number" min="0" step="0.01" name="goal2" value="<?=e((string)($stored['goal2']??0))?>"></div></div><?php if($seller['goal_mode']==='sales'):?><div class="col-md-4"><label class="form-label">Meta 3 de vendas</label><div class="input-group"><span class="input-group-text">R$</span><input class="form-control" type="number" min="0" step="0.01" name="goal3" value="<?=e((string)($stored['goal3']??0))?>"></div></div><?php endif;?><?php endif;?>
     <?php if(GoalService::hasCollection((string)$seller['goal_mode'])):?><div class="col-md-6"><label class="form-label">Meta de valor recuperado</label><div class="input-group"><span class="input-group-text">R$</span><input class="form-control" type="number" min="0" step="0.01" name="collection_goal" value="<?=e((string)($stored['collection_goal']??0))?>"></div></div><div class="col-md-6"><label class="form-label">Meta de devedores trabalhados</label><input class="form-control" type="number" min="0" name="debtor_contact_goal" value="<?=e((string)($stored['debtor_contact_goal']??0))?>"></div><?php endif;?>
    </div><button class="btn btn-primary mt-3">Salvar metas</button>
   </form>
  </div></div>
  <?php if($hasSales):?><div class="panel-card mb-4"><div class="panel-header"><div><span>PRODUTOS</span><h2>Pedidos incluídos na Omie</h2></div><strong><?=count($monthlyOrders)?> pedidos • <?=money($goal['product_realized'])?></strong></div><div class="table-responsive data-table-wrap"><table class="table modern-table data-table mb-0" data-entity="pedidos" data-page-length="5" data-order-column="0"><thead><tr><th>Inclusão</th><th>Pedido</th><th>Cliente</th><th>Status</th><th class="text-end">Valor</th></tr></thead><tbody><?php foreach($monthlyOrders as $order):$ignored=!GoalService::isOrderCounted($order);$displayStatus=GoalService::orderStageCode($order)==='00'?'ORÇAMENTO':($order['status']?:'ABERTO');?><tr class="<?=$ignored?'order-ignored':''?>"><td data-order="<?=e($order['order_date'])?>"><?=brdate($order['order_date'])?></td><td><strong><?=e($order['omie_order_code'])?></strong></td><td><?=e($order['client_name']??'Cliente não localizado')?></td><td><span class="badge-soft <?=$ignored?'badge-muted':''?>"><?=e($displayStatus)?></span><?php if($ignored):?><small class="table-sub">fora da meta</small><?php endif;?></td><td class="text-end" data-order="<?=(float)$order['total']?>"><strong><?=money($order['total'])?></strong></td></tr><?php endforeach;?></tbody></table></div></div>
  <div class="panel-card mb-4"><div class="panel-header"><div><span>SERVIÇOS</span><h2>Ordens de Serviço incluídas</h2></div><strong><?=count($monthlyServices)?> OS • <?=money($goal['service_realized'])?></strong></div><div class="table-responsive data-table-wrap"><table class="table modern-table data-table mb-0" data-entity="serviços" data-page-length="5" data-order-column="0"><thead><tr><th>Inclusão</th><th>OS</th><th>Cliente</th><th>Serviço</th><th>Status</th><th class="text-end">Valor</th></tr></thead><tbody><?php foreach($monthlyServices as $service):$ignored=!GoalService::isServiceOrderCounted($service);?><tr class="<?=$ignored?'order-ignored':''?>"><td data-order="<?=e($service['inclusion_date'])?>"><?=brdate($service['inclusion_date'])?></td><td><strong><?=e($service['display_number']?:$service['omie_service_order_code'])?></strong></td><td><?=e($service['client_name']??'Cliente não localizado')?></td><td><?=e($service['service_description']?:'Serviço sem descrição')?></td><td><span class="badge-soft <?=$ignored?'badge-muted':''?>"><?=e($service['stage_code']==='00'?'ORÇAMENTO':$service['status'])?></span><?php if($ignored):?><small class="table-sub">fora da meta</small><?php endif;?></td><td class="text-end" data-order="<?=(float)$service['total']?>"><strong><?=money($service['total'])?></strong></td></tr><?php endforeach;?></tbody></table></div></div><?php endif;?>
  <?php if($hasCollection):?>
  <div class="panel-card mb-4"><div class="panel-header"><div><span>COBRANÇA</span><h2>Registrar nova ação</h2></div></div><div class="panel-body">
   <?php if(!$debtors):?><div class="empty-inline">Nenhum devedor atribuído a este vendedor na conta financeira selecionada.</div><?php else:?><form method="post"><input type="hidden" name="_token" value="<?=Security::csrf()?>"><input type="hidden" name="seller_id" value="<?=$id?>"><input type="hidden" name="month_ref" value="<?=e($month)?>"><input type="hidden" name="form_action" value="collection_action"><div class="row g-3"><div class="col-md-6"><label class="form-label">Devedor</label><select class="form-select" name="client_id" required><option value="">Selecione</option><?php foreach($debtors as $debtor):?><option value="<?=$debtor['id']?>"><?=e($debtor['name'])?> • <?=money($debtor['overdue_amount'])?></option><?php endforeach;?></select></div><div class="col-md-3"><label class="form-label">Ação</label><select class="form-select" name="action_type"><option value="contact">Contato</option><option value="promise">Promessa</option><option value="agreement">Acordo</option><option value="payment">Pagamento</option></select></div><div class="col-md-3"><label class="form-label">Valor</label><input class="form-control" name="amount" type="number" min="0" step="0.01" value="0"></div><div class="col-md-4"><label class="form-label">Data prometida</label><input class="form-control" name="promised_for" type="date"></div><div class="col-md-8"><label class="form-label">Observação</label><input class="form-control" name="notes" placeholder="Resumo objetivo da conversa"></div></div><button class="btn btn-primary mt-3">Registrar ação</button></form><?php endif;?>
  </div></div>
  <div class="panel-card"><div class="panel-header"><div><span>HISTÓRICO</span><h2>Ações recentes</h2></div></div><div class="activity-list"><?php if(!$actions):?><div class="empty-inline m-3">Nenhuma ação de cobrança registrada.</div><?php endif;?><?php $labels=['contact'=>'Contato','promise'=>'Promessa','agreement'=>'Acordo','payment'=>'Pagamento'];foreach($actions as $item):?><div class="activity-row"><div class="activity-icon type-<?=e($item['action_type'])?>"><i class="fa-solid <?=e($item['action_type']==='payment'?'fa-circle-dollar-to-slot':($item['action_type']==='promise'?'fa-calendar-check':'fa-comment-dots'))?>"></i></div><div><strong><?=e($labels[$item['action_type']]??$item['action_type'])?> • <?=e($item['client_name'])?></strong><p><?=e($item['notes']??'Sem observação')?><?php if((float)$item['amount']>0):?> • <?=money($item['amount'])?><?php endif;?></p></div><time><?=date('d/m H:i',strtotime($item['created_at']))?></time></div><?php endforeach;?></div></div>
  <?php endif;?>
 </div>
</div>
<?php include '_footer.php';?>
