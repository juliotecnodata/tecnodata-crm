<?php
require dirname(__DIR__).'/app/bootstrap.php';
require APP_ROOT.'/app/Support/helpers.php';

use Tecnodata\CRM\Core\Auth;
use Tecnodata\CRM\Core\DB;
use Tecnodata\CRM\Core\Security;

Auth::requireLogin();
if(!Auth::can('collector','supervisor','admin')){http_response_code(403);exit('Sem acesso');}
$u=Auth::user();
$id=(int)($_GET['id']??0);

$row=DB::fetch("SELECT ca.*,c.name client_name,c.omie_code,c.uf,c.phone,
 author.name user_name,assigned.name assigned_name,assigner.name assigned_by_name,m.seller_omie_code,s.name seller_name
 FROM collection_actions ca
 JOIN clients c ON c.id=ca.client_id
 JOIN users author ON author.id=ca.user_id
 LEFT JOIN users assigned ON assigned.id=ca.assigned_user_id
 LEFT JOIN users assigner ON assigner.id=ca.assigned_by
 LEFT JOIN client_metrics m ON m.client_id=c.id
 LEFT JOIN sellers s ON s.omie_code=m.seller_omie_code
 WHERE ca.id=? AND ca.deleted_at IS NULL",[$id]);

if(!$row){http_response_code(404);exit('Atendimento não encontrado.');}
$canManage=(int)$row['user_id']===(int)$u['id']||(int)($row['assigned_user_id']??0)===(int)$u['id']||Auth::can('supervisor','admin');

$labels=['falou'=>'Falou com o cliente','nao_atendeu'=>'Não atendeu','sem_previsao'=>'Sem previsão','promessa'=>'Promessa de pagamento','acordo'=>'Acordo realizado','pagamento'=>'Pagamento recebido'];
$channels=['ligacao'=>'Ligação','whatsapp'=>'WhatsApp','email'=>'E-mail','outro'=>'Outro'];

include '_layout.php';?>
<div class="page-heading">
 <div><a class="back-link" href="<?=APP_URL?>/cobranca-atendimentos.php"><i class="fa-solid fa-arrow-left"></i>Atendimentos</a>
 <div class="eyebrow mt-3">DETALHE DO ATENDIMENTO</div><h1><?=e($row['client_name'])?></h1>
 <p><?=date('d/m/Y H:i',strtotime($row['created_at']))?> • <?=e($row['user_name'])?></p></div>
 <div class="d-flex gap-2 flex-wrap">
  <a class="btn btn-outline-secondary" href="<?=APP_URL?>/cobranca-cliente.php?id=<?=$row['client_id']?>"><i class="fa-solid fa-address-card"></i>Ver cliente</a>
  <?php if($canManage):?><a class="btn btn-dark" href="<?=APP_URL?>/atendimento-editar.php?kind=collection&id=<?=$row['id']?>"><i class="fa-solid fa-pen"></i>Editar</a><?php endif;?>
 </div>
</div>

<div class="row g-4">
 <div class="col-xl-8">
  <div class="panel-card">
   <div class="panel-header"><div><span>ATENDIMENTO</span><h2><?=e($labels[$row['result']]??$row['result'])?></h2></div>
    <span class="status-pill <?=in_array($row['result'],['acordo','pagamento'],true)?'status-paid':($row['result']==='promessa'?'status-partial':'badge-muted')?>"><?=e($labels[$row['result']]??$row['result'])?></span>
   </div>
   <div class="panel-body">
    <div class="detail-grid">
     <div><span>Realizado por</span><strong><?=e($row['user_name'])?></strong><small><?=date('d/m/Y H:i',strtotime($row['created_at']))?></small></div>
     <div><span>Responsável atual</span><strong><?=e($row['assigned_name']??$row['user_name'])?></strong><?php if(!empty($row['assigned_at'])):?><small>atribuído em <?=date('d/m/Y H:i',strtotime($row['assigned_at']))?><?=!empty($row['assigned_by_name'])?' por '.e($row['assigned_by_name']):''?></small><?php endif;?></div>
     <div><span>Canal</span><strong><?=e($channels[$row['channel']]??$row['channel'])?></strong></div>
     <div><span>Registro original</span><strong><?=date('d/m/Y H:i',strtotime($row['created_at']))?></strong><?php if(!empty($row['updated_at'])):?><small>última edição <?=date('d/m/Y H:i',strtotime($row['updated_at']))?></small><?php endif;?></div>
     <div><span>Vendedor</span><strong><?=e($row['seller_name']??'—')?></strong></div>
     <?php if($row['promised_for']):?><div><span>Promessa para</span><strong><?=date('d/m/Y',strtotime($row['promised_for']))?></strong></div><?php endif;?>
     <?php if((float)$row['amount']>0):?><div><span>Valor recebido</span><strong><?=money($row['amount'])?></strong></div><?php endif;?>
    </div>
    <div class="detail-note mt-4"><span>Anotação</span><p><?=trim((string)$row['notes'])!==''?nl2br(e($row['notes'])):'Nenhuma anotação registrada.'?></p></div>
   </div>
  </div>
 </div>
 <div class="col-xl-4">
  <div class="panel-card mb-4"><div class="panel-header"><div><span>CLIENTE</span><h2>Referência</h2></div></div><div class="panel-body">
   <div class="detail-list">
    <div><span>Código Omie</span><strong><?=e($row['omie_code'])?></strong></div>
    <div><span>UF</span><strong><?=e($row['uf']?:'—')?></strong></div>
    <div><span>Vendedor</span><strong><?=e($row['seller_name']??'—')?></strong></div>
   </div>
  </div></div>
  <?php if($canManage):?>
  <div class="panel-card danger-zone"><div class="panel-header"><div><span>CONTROLE</span><h2>Excluir atendimento</h2></div></div><div class="panel-body">
   <p class="text-secondary">A exclusão é lógica: o atendimento sai dos indicadores e filtros, mas permanece registrado na auditoria.</p>
   <form method="post" action="<?=APP_URL?>/api/collection-action-delete.php" onsubmit="return confirm('Excluir este atendimento? Esta ação será registrada na auditoria.');">
    <input type="hidden" name="_token" value="<?=Security::csrf()?>"><input type="hidden" name="id" value="<?=$row['id']?>">
    <button class="btn btn-outline-danger w-100"><i class="fa-regular fa-trash-can"></i>Excluir atendimento</button>
   </form>
  </div></div>
  <?php endif;?>
 </div>
</div>
<?php include '_footer.php';?>
