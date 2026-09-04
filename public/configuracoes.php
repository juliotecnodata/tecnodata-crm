<?php
require dirname(__DIR__).'/app/bootstrap.php';require APP_ROOT.'/app/Support/helpers.php';
use Tecnodata\CRM\Core\Auth;use Tecnodata\CRM\Core\DB;use Tecnodata\CRM\Core\Security;use Tecnodata\CRM\Services\FinancialAccountService;use Tecnodata\CRM\Services\SalesOrderService;
Auth::requireLogin();if(!Auth::can('supervisor','admin')){http_response_code(403);exit;}$message='';$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 if(!Security::verifyCsrf($_POST['_token']??null))$error='Sua sessão expirou. Recarregue a página.';
 else try{$action=$_POST['form_action']??'';if($action==='refresh_accounts'){$count=FinancialAccountService::refresh();$message="{$count} contas atualizadas a partir da Omie.";}elseif($action==='select_accounts'){FinancialAccountService::selectMany((array)($_POST['account_codes']??[]));$message='Contas selecionadas. O financeiro foi preparado para uma nova carga.';}
 elseif($action==='save_order_settings'){SalesOrderService::saveSettings($_POST,Auth::id());$message='Padrões do pedido de venda salvos.';}
 }catch(Throwable $exception){$error=$exception->getMessage();}
}
$accounts=FinancialAccountService::all();$selected=FinancialAccountService::selectedAll();
$orderSettings=SalesOrderService::settings();$orderReady=SalesOrderService::readiness();
$stages=DB::all("SELECT stage_code,stage_name FROM order_stage_catalog WHERE active=1 ORDER BY stage_code");
$categories=DB::all("SELECT code,description FROM sales_categories WHERE active=1 ORDER BY description");
$paymentTerms=DB::all("SELECT code,description,installments,days_list FROM sales_payment_terms WHERE active=1 AND code<>'999' ORDER BY description");
$paymentMethods=DB::all("SELECT code,description FROM payment_methods ORDER BY description");
$documentTypes=DB::all("SELECT code,description FROM document_types ORDER BY description");
$taxScenarios=DB::all("SELECT omie_code,name,is_default FROM tax_scenarios WHERE active=1 ORDER BY is_default DESC,name");
$stockLocations=DB::all("SELECT omie_code,name,is_default FROM stock_locations WHERE active=1 AND sale_enabled=1 ORDER BY is_default DESC,name");
include '_layout.php';?>
<?php if($message):?><div class="alert alert-success alert-modern"><i class="fa-solid fa-circle-check"></i><?=e($message)?></div><?php endif;?>
<?php if($error):?><div class="alert alert-danger alert-modern"><i class="fa-solid fa-circle-exclamation"></i><?=e($error)?></div><?php endif;?>
<div class="page-heading"><div><div class="eyebrow">INTEGRAÇÃO</div><h1>Configurações financeiras</h1><p>Escolha quais contas da Omie formam a carteira de inadimplência.</p></div><form method="post"><input type="hidden" name="_token" value="<?=Security::csrf()?>"><input type="hidden" name="form_action" value="refresh_accounts"><button class="btn btn-secondary"><i class="fa-solid fa-rotate"></i> Atualizar contas da Omie</button></form></div>
<div class="row g-4"><div class="col-xl-8"><div class="panel-card"><div class="panel-header"><div><span>CONTAS DE ORIGEM</span><h2>Selecione uma ou mais contas</h2></div></div><div class="panel-body"><p class="text-secondary">Serão importados apenas títulos <strong>atrasados</strong> e com <strong>pagamento parcial</strong> das contas marcadas, limitados aos últimos 3 anos.</p>
<?php if(!$accounts):?><div class="empty-inline">Clique em “Atualizar contas da Omie” para carregar as opções.</div><?php else:?><form method="post"><input type="hidden" name="_token" value="<?=Security::csrf()?>"><input type="hidden" name="form_action" value="select_accounts"><div class="account-list"><?php foreach($accounts as $account):?><label class="account-option <?=$account['selected']?'selected':''?>"><input type="checkbox" name="account_codes[]" value="<?=e($account['omie_code'])?>" <?=$account['selected']?'checked':''?> <?=$account['active']?'':'disabled'?>><span class="account-icon"><i class="fa-solid fa-building-columns"></i></span><span><strong><?=e($account['name'])?></strong><small><?=e($account['account_type']?:'Conta Omie')?> • <?=e($account['omie_code'])?></small></span><span class="status-dot <?=$account['active']?'is-active':'is-inactive'?>"><?=$account['active']?'Ativa':'Inativa'?></span></label><?php endforeach;?></div><button class="btn btn-primary mt-3">Salvar contas selecionadas</button></form><?php endif;?></div></div></div>
<div class="col-xl-4"><div class="panel-card"><div class="panel-header"><div><span>REGRA ATUAL</span><h2>Escopo da cobrança</h2></div></div><div class="panel-body"><div class="scope-item"><i class="fa-solid fa-building-columns"></i><div><small>Contas selecionadas</small><strong><?=count($selected)?:'Nenhuma'?></strong><?php if($selected):?><small><?=e(implode(', ',array_column($selected,'name')))?></small><?php endif;?></div></div><div class="scope-item"><i class="fa-solid fa-filter-circle-dollar"></i><div><small>Status importados</small><strong>Atrasado e parcial</strong></div></div><div class="scope-item"><i class="fa-regular fa-calendar"></i><div><small>Período</small><strong>Últimos 3 anos</strong></div></div><div class="scope-item"><i class="fa-solid fa-database"></i><div><small>Consulta das telas</small><strong>Somente banco local</strong></div></div></div></div></div></div>
<div class="panel-card mt-4">
 <div class="panel-header">
  <div><span>PEDIDO DE VENDA</span><h2>Padrões para integração com a Omie</h2></div>
  <span class="status-pill <?=$orderReady['ready']?'status-paid':'status-partial'?>"><?=$orderReady['ready']?'Pronto para pedido real':'Configuração pendente'?></span>
 </div>
 <div class="panel-body">
  <p class="text-secondary">O vendedor escolhe cliente, produtos e condição de pagamento. As regras fiscais e operacionais abaixo completam o pedido automaticamente.</p>
  <?php if(!$orderReady['ready']):?><div class="management-note mb-3"><i class="fa-solid fa-circle-info"></i><div><strong>Falta concluir</strong><span><?=e(implode(', ',$orderReady['issues']))?>. Sincronize os cadastros auxiliares antes de salvar.</span></div></div><?php endif;?>
  <form method="post" class="order-settings-grid">
   <input type="hidden" name="_token" value="<?=Security::csrf()?>">
   <input type="hidden" name="form_action" value="save_order_settings">

   <div><label class="form-label">Etapa padrão</label><select class="form-select" name="default_stage_code" required><option value="">Selecione...</option><?php foreach($stages as $s):?><option value="<?=e($s['stage_code'])?>" <?=$orderSettings['default_stage_code']===$s['stage_code']?'selected':''?>><?=e($s['stage_code'].' • '.$s['stage_name'])?></option><?php endforeach;?></select></div>
   <div><label class="form-label">Categoria padrão</label><select class="form-select" name="default_category_code" required><option value="">Selecione...</option><?php foreach($categories as $cat):?><option value="<?=e($cat['code'])?>" <?=$orderSettings['default_category_code']===$cat['code']?'selected':''?>><?=e($cat['description'])?></option><?php endforeach;?></select></div>
   <div><label class="form-label">Conta corrente padrão</label><select class="form-select" name="default_account_code" required><option value="">Selecione...</option><?php foreach($accounts as $account):?><option value="<?=e($account['omie_code'])?>" <?=$orderSettings['default_account_code']===$account['omie_code']?'selected':''?>><?=e($account['name'])?></option><?php endforeach;?></select></div>

   <div><label class="form-label">Condição de pagamento padrão</label><select class="form-select" name="default_payment_term_code" required><option value="">Selecione...</option><?php foreach($paymentTerms as $term):?><option value="<?=e($term['code'])?>" <?=$orderSettings['default_payment_term_code']===$term['code']?'selected':''?>><?=e($term['description'])?><?=$term['days_list']?' • '.e($term['days_list']):''?></option><?php endforeach;?></select></div>
   <div><label class="form-label">Meio de pagamento padrão</label><select class="form-select" name="default_payment_method_code"><option value="">Não definir</option><?php foreach($paymentMethods as $pm):?><option value="<?=e($pm['code'])?>" <?=$orderSettings['default_payment_method_code']===$pm['code']?'selected':''?>><?=e($pm['description'])?></option><?php endforeach;?></select></div>
   <div><label class="form-label">Tipo de documento</label><select class="form-select" name="default_document_type_code"><option value="">Padrão Omie</option><?php foreach($documentTypes as $doc):?><option value="<?=e($doc['code'])?>" <?=$orderSettings['default_document_type_code']===$doc['code']?'selected':''?>><?=e($doc['description'])?></option><?php endforeach;?></select></div>

   <div><label class="form-label">Cenário fiscal</label><select class="form-select" name="default_tax_scenario_code"><option value="">Usar cenário padrão da Omie</option><?php foreach($taxScenarios as $tax):?><option value="<?=e($tax['omie_code'])?>" <?=$orderSettings['default_tax_scenario_code']===$tax['omie_code']?'selected':''?>><?=e($tax['name'])?><?=$tax['is_default']?' • padrão':''?></option><?php endforeach;?></select></div>
   <div><label class="form-label">Local de estoque</label><select class="form-select" name="default_stock_location_code"><option value="">Usar padrão da Omie</option><?php foreach($stockLocations as $loc):?><option value="<?=e($loc['omie_code'])?>" <?=$orderSettings['default_stock_location_code']===$loc['omie_code']?'selected':''?>><?=e($loc['name'])?><?=$loc['is_default']?' • padrão':''?></option><?php endforeach;?></select></div>
   <div><label class="form-label">Frete padrão</label><select class="form-select" name="freight_mode">
    <option value="9" <?=$orderSettings['freight_mode']==='9'?'selected':''?>>Sem frete</option>
    <option value="0" <?=$orderSettings['freight_mode']==='0'?'selected':''?>>CIF • remetente</option>
    <option value="1" <?=$orderSettings['freight_mode']==='1'?'selected':''?>>FOB • destinatário</option>
    <option value="2" <?=$orderSettings['freight_mode']==='2'?'selected':''?>>Por conta de terceiros</option>
    <option value="3" <?=$orderSettings['freight_mode']==='3'?'selected':''?>>Transporte próprio • remetente</option>
    <option value="4" <?=$orderSettings['freight_mode']==='4'?'selected':''?>>Transporte próprio • destinatário</option>
   </select></div>

   <div><label class="form-label">Consumidor final</label><select class="form-select" name="consumer_final"><option value="S" <?=$orderSettings['consumer_final']==='S'?'selected':''?>>Sim</option><option value="N" <?=$orderSettings['consumer_final']==='N'?'selected':''?>>Não</option></select></div>
   <div><label class="form-label">Enviar e-mail pela Omie</label><select class="form-select" name="send_email"><option value="N" <?=$orderSettings['send_email']==='N'?'selected':''?>>Não</option><option value="S" <?=$orderSettings['send_email']==='S'?'selected':''?>>Sim</option></select></div>
   <div class="d-flex align-items-end"><label class="form-check mb-2"><input class="form-check-input" type="checkbox" name="allow_seller_freight" value="1" <?=!empty($orderSettings['allow_seller_freight'])?'checked':''?>><span class="ms-2">Vendedor pode alterar modalidade do frete</span></label></div>

   <div class="order-settings-action"><button class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i>Salvar padrões de pedido</button></div>
  </form>
 </div>
</div>
<?php include '_footer.php';?>
