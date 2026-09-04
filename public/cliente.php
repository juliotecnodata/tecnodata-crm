<?php
require dirname(__DIR__).'/app/bootstrap.php';
require APP_ROOT.'/app/Support/helpers.php';

use Tecnodata\CRM\Core\Auth;
use Tecnodata\CRM\Core\DB;
use Tecnodata\CRM\Core\Security;
use Tecnodata\CRM\Services\InteractionService;
use Tecnodata\CRM\Services\PurchaseCycleService;

Auth::requireLogin();
$u=Auth::user();
$id=(int)($_GET['id']??0);
$month=date('Y-m');

$c=DB::fetch("SELECT c.*,m.*,
 CASE WHEN pa.id IS NOT NULL THEN pa.seller_omie_code ELSE m.seller_omie_code END effective_seller_code,
 s.name seller_name
 FROM clients c
 LEFT JOIN client_metrics m ON m.client_id=c.id
 LEFT JOIN client_portfolio_assignments pa ON pa.client_id=c.id AND pa.month_ref=?
 LEFT JOIN sellers s ON s.omie_code=(CASE WHEN pa.id IS NOT NULL THEN pa.seller_omie_code ELSE m.seller_omie_code END)
 WHERE c.id=?",[$month,$id]);
if(!$c){http_response_code(404);exit('Cliente não encontrado.');}

if(($u['role']??'')==='seller' && (string)($c['effective_seller_code']??'')!==(string)($u['seller_omie_code']??'')){
 http_response_code(403);exit('Este cliente não pertence à sua carteira do mês.');
}
if(($u['role']??'')==='collector'){header('Location: '.APP_URL.'/cobranca-cliente.php?id='.$id);exit;}

$msg='';$err='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 if(!Security::verifyCsrf($_POST['_token']??null))$err='Sua sessão expirou. Recarregue a página.';
 else try{
   InteractionService::registerSales(
     $id,(int)$u['id'],
     (string)($_POST['type']??'ligacao'),
     (string)($_POST['result']??'falou'),
     trim((string)($_POST['notes']??'')),
     ($_POST['next_at']??'')?:null
   );
   $msg='Atendimento registrado.';
 }catch(Throwable $e){$err=$e->getMessage();}
}

$acts=DB::all("SELECT a.*,u.name user_name FROM activities a JOIN users u ON u.id=a.user_id
               WHERE a.client_id=? AND a.deleted_at IS NULL ORDER BY a.created_at DESC LIMIT 20",[$id]);
$orders=DB::all("SELECT * FROM orders WHERE client_omie_code=? ORDER BY order_date DESC,id DESC LIMIT 8",[$c['omie_code']]);

$finance=DB::fetch("SELECT
 COALESCE(SUM(fm.amount),0) overdue_amount,
 MAX(CASE WHEN fm.due_date IS NOT NULL THEN GREATEST(0,DATEDIFF(CURDATE(),fm.due_date)) ELSE 0 END) max_days
 FROM financial_movements fm
 INNER JOIN financial_accounts fa ON fa.omie_code=fm.account_omie_code AND fa.selected=1 AND fa.active=1
 WHERE fm.client_omie_code=? AND fm.status IN('ATRASADO','PAGTO_PARCIAL')",[$c['omie_code']])??[];
$overdue=(float)($finance['overdue_amount']??0);
$cycle=PurchaseCycleService::analyze($c['last_purchase_at']??null,$c['avg_interval_days']??null,isset($c['days_without_purchase'])?(int)$c['days_without_purchase']:null);

include '_layout.php';?>

<?php if($msg):?><div class="alert alert-success alert-modern"><i class="fa-solid fa-circle-check"></i><?=e($msg)?></div><?php endif;?>
<?php if($err):?><div class="alert alert-danger alert-modern"><i class="fa-solid fa-triangle-exclamation"></i><?=e($err)?></div><?php endif;?>

<div class="client-work-head mb-4">
 <div>
  <a class="back-link" href="<?=APP_URL?>/carteira.php"><i class="fa-solid fa-arrow-left"></i>Clientes</a>
  <div class="eyebrow mt-3"><?=e($c['omie_code'])?> • <?=e($c['uf']?:'—')?></div>
  <h1><?=e($c['name'])?></h1>
  <p><?=e($c['seller_name']??'Sem responsável')?><?=$c['city']?' • '.e($c['city']):''?></p>
 </div>
 <div class="client-work-actions">
  <a class="btn btn-primary" href="<?=APP_URL?>/pedido-novo.php?client_id=<?=$c['id']?>"><i class="fa-solid fa-plus"></i>Novo pedido</a>
  <?php if($c['phone']):?><a class="btn btn-outline-secondary" href="tel:<?=preg_replace('/\D/','',$c['phone'])?>"><i class="fa-solid fa-phone"></i>Ligar</a><a class="btn btn-outline-secondary" target="_blank" href="https://wa.me/55<?=preg_replace('/\D/','',$c['phone'])?>"><i class="fa-brands fa-whatsapp"></i>WhatsApp</a><?php endif;?>
 </div>
</div>

<div class="client-snapshot mb-4">
 <div><span>Momento</span><strong><span class="cycle-chip cycle-<?=e($cycle['tone'])?>"><?=e($cycle['label'])?></span></strong><small><?=$cycle['expected_date']?'próxima compra estimada '.brdate($cycle['expected_date']):'sem previsão confiável'?></small></div>
 <div><span>Receita 12m</span><strong><?=money($c['revenue_12m']??0)?></strong><small><?=(int)($c['orders_12m']??0)?> compra(s) • ticket <?=money($c['avg_ticket_12m']??0)?></small></div>
 <div><span>Última compra</span><strong><?=brdate($c['last_purchase_at']??null)?></strong><small><?=$c['days_without_purchase']!==null?(int)$c['days_without_purchase'].' dias atrás':'sem histórico'?></small></div>
 <div><span>Financeiro</span><strong class="<?=$overdue>0?'text-danger':''?>"><?=$overdue>0?money($overdue).' vencido':'Em dia'?></strong><small><?=$overdue>0?((int)($finance['max_days']??0)).' dia(s) de atraso':'sem pendência na carteira de cobrança'?></small></div>
</div>

<?php if($overdue>0):?>
<div class="client-finance-warning mb-4"><i class="fa-solid fa-triangle-exclamation"></i><div><strong>Cliente com pendência financeira</strong><span>O vendedor enxerga o alerta, mas a negociação financeira permanece na área própria de Cobrança.</span></div><?php if(Auth::can('supervisor','admin')):?><a class="btn btn-outline-secondary btn-sm" href="<?=APP_URL?>/cobranca-cliente.php?id=<?=$c['id']?>">Abrir cobrança</a><?php endif;?></div>
<?php endif;?>

<div class="row g-4">
 <div class="col-xl-5">
  <div class="panel-card">
   <div class="panel-header"><div><span>ATENDIMENTO</span><h2>Registrar contato</h2></div></div>
   <div class="panel-body">
    <form method="post">
     <input type="hidden" name="_token" value="<?=Security::csrf()?>">
     <label class="form-label">Canal</label>
     <div class="sales-channel-grid mb-3">
      <?php foreach(['ligacao'=>['fa-phone','Ligação'],'whatsapp'=>['fa-brands fa-whatsapp','WhatsApp'],'email'=>['fa-envelope','E-mail'],'visita'=>['fa-location-dot','Visita']] as $v=>$a):?>
       <label><input class="btn-check" type="radio" name="type" value="<?=$v?>" <?=$v==='ligacao'?'checked':''?>><span><i class="fa-solid <?=$a[0]?>"></i><?=$a[1]?></span></label>
      <?php endforeach;?>
     </div>
     <div class="mb-3"><label class="form-label">Resultado</label><select class="form-select" name="result"><option value="falou">Falou com o cliente</option><option value="nao_atendeu">Não atendeu</option><option value="interessado">Interessado</option><option value="acordo">Venda / acordo fechado</option><option value="sem_interesse">Sem interesse</option></select></div>
     <div class="mb-3"><label class="form-label">Próximo retorno</label><input class="form-control" type="datetime-local" name="next_at"></div>
     <div class="mb-3"><label class="form-label">Anotação</label><textarea class="form-control" rows="4" name="notes" placeholder="Somente o que será útil no próximo contato"></textarea></div>
     <button class="btn btn-primary w-100"><i class="fa-solid fa-check"></i>Salvar atendimento</button>
    </form>
   </div>
  </div>
 </div>

 <div class="col-xl-7">
  <div class="panel-card mb-4">
   <div class="panel-header"><div><span>HISTÓRICO</span><h2>Últimos contatos</h2></div><strong><?=count($acts)?></strong></div>
   <div class="activity-list compact-activity-list">
    <?php foreach($acts as $a):?>
     <div class="activity-row">
      <div class="activity-icon"><i class="fa-solid <?=e(match($a['type']){'whatsapp'=>'fa-brands fa-whatsapp','email'=>'fa-envelope','visita'=>'fa-location-dot',default=>'fa-phone'})?>"></i></div>
      <div><strong><?=e(str_replace('_',' ',ucfirst((string)$a['result'])))?></strong><p><?=e($a['user_name'])?> • <?=date('d/m/Y H:i',strtotime($a['created_at']))?></p><?php if($a['notes']):?><p class="activity-note"><?=nl2br(e($a['notes']))?></p><?php endif;?></div>
      <?php if((int)$a['user_id']===(int)$u['id']||Auth::can('supervisor','admin')):?><a class="icon-button" href="<?=APP_URL?>/atendimento-editar.php?kind=sales&id=<?=$a['id']?>" title="Editar"><i class="fa-solid fa-pen"></i></a><?php endif;?>
     </div>
    <?php endforeach;?>
    <?php if(!$acts):?><div class="empty-inline m-3">Nenhum atendimento comercial registrado.</div><?php endif;?>
   </div>
  </div>

  <div class="panel-card">
   <div class="panel-header"><div><span>COMPRAS</span><h2>Últimos pedidos</h2></div><a class="btn btn-outline-secondary btn-sm" href="<?=APP_URL?>/pedidos.php">Ver pedidos</a></div>
   <div class="purchase-history-list">
    <?php foreach($orders as $o):?><div><span><strong><?=brdate($o['order_date'])?></strong><small><?=e($o['stage_name']?:('Etapa '.$o['stage_code']))?></small></span><strong><?=money($o['total'])?></strong></div><?php endforeach;?>
    <?php if(!$orders):?><div class="empty-inline m-3">Nenhum pedido encontrado.</div><?php endif;?>
   </div>
  </div>
 </div>
</div>
<?php include '_footer.php';?>