<?php
require dirname(__DIR__).'/app/bootstrap.php';
require APP_ROOT.'/app/Support/helpers.php';

use Tecnodata\CRM\Core\Auth;
use Tecnodata\CRM\Core\DB;
use Tecnodata\CRM\Services\SalesOrderService;

Auth::requireLogin();
if(!Auth::can('seller','supervisor','admin')){http_response_code(403);exit('Sem acesso');}
$u=Auth::user();

$readiness=SalesOrderService::readiness();
$settings=$readiness['settings'];
$stages=DB::all("SELECT stage_code,stage_name FROM order_stage_catalog WHERE active=1 ORDER BY stage_code");
$categories=DB::all("SELECT code,description FROM sales_categories WHERE active=1 ORDER BY description");
$accounts=DB::all("SELECT omie_code,name FROM financial_accounts WHERE active=1 ORDER BY name");
$sellers=Auth::can('supervisor','admin')?DB::all("SELECT omie_code,name FROM sellers WHERE active=1 ORDER BY name"):[];
$paymentTerms=DB::all("SELECT code,description,days_list FROM sales_payment_terms WHERE active=1 AND code<>'999' ORDER BY description");
$paymentMethods=DB::all("SELECT code,description FROM payment_methods ORDER BY description");
$taxScenarios=DB::all("SELECT omie_code,name,is_default FROM tax_scenarios WHERE active=1 ORDER BY is_default DESC,name");
$stockLocations=DB::all("SELECT omie_code,name,is_default FROM stock_locations WHERE active=1 AND sale_enabled=1 ORDER BY is_default DESC,name");
$requestToken=date('YmdHis').'-'.strtoupper(substr(bin2hex(random_bytes(5)),0,10));
$prefillClient=(int)($_GET['client_id']??0);
$prefill=null;
if($prefillClient){
 if(($u['role']??'')==='seller'){
  $prefill=DB::fetch("SELECT c.id,c.name,c.document,c.uf,c.city,c.omie_code
                     FROM clients c
                     LEFT JOIN client_metrics m ON m.client_id=c.id
                     LEFT JOIN client_portfolio_assignments pa ON pa.client_id=c.id AND pa.month_ref=?
                     WHERE c.id=? AND c.active=1
                       AND (CASE WHEN pa.id IS NOT NULL THEN pa.seller_omie_code ELSE m.seller_omie_code END)=?",
                    [date('Y-m'),$prefillClient,(string)($u['seller_omie_code']??'')]);
 }else{
  $prefill=DB::fetch("SELECT id,name,document,uf,city,omie_code FROM clients WHERE id=? AND active=1",[$prefillClient]);
 }
}
$canEditCommercial=Auth::can('supervisor','admin');

include '_layout.php';?>

<div class="page-heading">
 <div>
  <div class="eyebrow">PEDIDOS</div>
  <h1>Novo pedido</h1>
  <p>Crie o pedido aqui e envie direto para a Omie. Cliente, produtos e valores são lidos da base local para manter a operação rápida.</p>
 </div>
 <a class="btn btn-outline-secondary" href="<?=APP_URL?>/pedidos.php"><i class="fa-solid fa-arrow-left"></i>Voltar aos pedidos</a>
</div>

<?php if(!$readiness['ready']):?>
<div class="alert alert-warning border-0 order-ready-alert">
 <i class="fa-solid fa-triangle-exclamation"></i>
 <div><strong>Falta configurar o pedido integrado.</strong><span><?=e(implode(', ',$readiness['issues']))?>.</span><?php if(Auth::can('admin','supervisor')):?><a href="<?=APP_URL?>/configuracoes.php">Abrir configurações</a><?php endif;?></div>
</div>
<?php endif;?>

<div class="order-builder">
 <section class="order-builder-main">
  <div class="panel-card mb-3">
   <div class="panel-header"><div><span>1. CLIENTE</span><h2>Quem está comprando?</h2></div><button type="button" class="btn btn-outline-secondary btn-sm" id="clearOrderClient"><i class="fa-solid fa-xmark"></i>Limpar</button></div>
   <div class="panel-body">
    <div class="order-search-wrap">
     <label class="form-label">Buscar cliente</label>
     <div class="order-search-input"><i class="fa-solid fa-magnifying-glass"></i><input type="search" class="form-control" id="orderClientSearch" autocomplete="off" placeholder="Nome, CPF/CNPJ ou código..."></div>
     <div class="order-search-results" id="orderClientResults"></div>
    </div>
    <div class="order-selected-client <?=!$prefill?'d-none':''?>" id="orderSelectedClient">
     <span class="avatar"><?=e(mb_strtoupper(mb_substr((string)($prefill['name']??'C'),0,1)))?></span>
     <div><strong id="selectedClientName"><?=e($prefill['name']??'')?></strong><small id="selectedClientMeta"><?=e(trim(($prefill['document']??'').' • '.($prefill['city']??'').' / '.($prefill['uf']??''),' •/'))?></small></div>
     <span class="status-pill status-paid"><i class="fa-solid fa-circle-check"></i>Selecionado</span>
    </div>
    <input type="hidden" id="orderClientId" value="<?=$prefill?(int)$prefill['id']:0?>">
   </div>
  </div>

  <div class="panel-card">
   <div class="panel-header"><div><span>2. ITENS</span><h2>Produtos do pedido</h2></div><span class="order-item-count" id="orderItemCount">0 itens</span></div>
   <div class="panel-body">
    <div class="order-search-wrap mb-3">
     <label class="form-label">Adicionar produto</label>
     <div class="order-search-input"><i class="fa-solid fa-magnifying-glass"></i><input type="search" class="form-control" id="orderProductSearch" autocomplete="off" placeholder="Produto, código ou descrição..."></div>
     <div class="order-search-results" id="orderProductResults"></div>
    </div>

    <div class="order-items" id="orderItems">
     <div class="order-empty" id="orderEmpty"><i class="fa-solid fa-basket-shopping"></i><strong>Nenhum produto ainda</strong><span>Busque um produto acima e clique para adicionar.</span></div>
    </div>
   </div>
  </div>
 </section>

 <aside class="order-builder-side">
  <div class="order-summary-card">
   <div class="order-summary-head"><div><span>RESUMO</span><h2>Pedido</h2></div><i class="fa-solid fa-receipt"></i></div>

   <div class="order-summary-fields">
    <?php if(Auth::can('supervisor','admin')):?>
    <div><label class="form-label">Vendedor</label><select class="form-select" id="orderSeller"><option value="">Selecione...</option><?php foreach($sellers as $seller):?><option value="<?=e($seller['omie_code'])?>"><?=e($seller['name'])?></option><?php endforeach;?></select></div>
    <?php endif;?>

    <div><label class="form-label">Previsão de faturamento</label><input class="form-control" type="date" id="orderForecast" min="<?=date('Y-m-d')?>" value="<?=date('Y-m-d')?>"></div>
    <div><label class="form-label">Condição de pagamento</label><select class="form-select" id="orderPaymentTerm" required><option value="">Selecione...</option><?php foreach($paymentTerms as $term):?><option value="<?=e($term['code'])?>" <?=$settings['default_payment_term_code']===$term['code']?'selected':''?>><?=e($term['description'])?><?=$term['days_list']?' • '.e($term['days_list']):''?></option><?php endforeach;?></select></div>
    <div><label class="form-label">Meio de pagamento</label><select class="form-select" id="orderPaymentMethod"><option value="">Padrão Omie</option><?php foreach($paymentMethods as $pm):?><option value="<?=e($pm['code'])?>" <?=$settings['default_payment_method_code']===$pm['code']?'selected':''?>><?=e($pm['description'])?></option><?php endforeach;?></select></div>

    <?php if(Auth::can('supervisor','admin')):?>
    <details class="order-advanced">
     <summary>Opções fiscais e operacionais</summary>
     <div class="mt-3"><label class="form-label">Etapa</label><select class="form-select" id="orderStage"><?php foreach($stages as $stage):?><option value="<?=e($stage['stage_code'])?>" <?=$settings['default_stage_code']===$stage['stage_code']?'selected':''?>><?=e($stage['stage_code'].' • '.$stage['stage_name'])?></option><?php endforeach;?></select></div>
     <div class="mt-3"><label class="form-label">Categoria</label><select class="form-select" id="orderCategory"><?php foreach($categories as $category):?><option value="<?=e($category['code'])?>" <?=$settings['default_category_code']===$category['code']?'selected':''?>><?=e($category['description'])?></option><?php endforeach;?></select></div>
     <div class="mt-3"><label class="form-label">Conta corrente</label><select class="form-select" id="orderAccount"><?php foreach($accounts as $account):?><option value="<?=e($account['omie_code'])?>" <?=$settings['default_account_code']===$account['omie_code']?'selected':''?>><?=e($account['name'])?></option><?php endforeach;?></select></div>
     <div class="mt-3"><label class="form-label">Cenário fiscal</label><select class="form-select" id="orderTaxScenario"><option value="">Padrão Omie</option><?php foreach($taxScenarios as $tax):?><option value="<?=e($tax['omie_code'])?>" <?=$settings['default_tax_scenario_code']===$tax['omie_code']?'selected':''?>><?=e($tax['name'])?></option><?php endforeach;?></select></div>
     <div class="mt-3"><label class="form-label">Local de estoque</label><select class="form-select" id="orderStockLocation"><option value="">Padrão Omie</option><?php foreach($stockLocations as $loc):?><option value="<?=e($loc['omie_code'])?>" <?=$settings['default_stock_location_code']===$loc['omie_code']?'selected':''?>><?=e($loc['name'])?></option><?php endforeach;?></select></div>
    </details>
    <?php else:?>
     <input type="hidden" id="orderStage" value="<?=e((string)$settings['default_stage_code'])?>">
     <input type="hidden" id="orderCategory" value="<?=e((string)$settings['default_category_code'])?>">
     <input type="hidden" id="orderAccount" value="<?=e((string)$settings['default_account_code'])?>">
     <input type="hidden" id="orderTaxScenario" value="<?=e((string)($settings['default_tax_scenario_code']??''))?>">
     <input type="hidden" id="orderStockLocation" value="<?=e((string)($settings['default_stock_location_code']??''))?>">
     <div class="order-default-rule"><i class="fa-solid fa-shield"></i><span>Etapa, categoria, conta, cenário fiscal e estoque seguem os padrões da empresa.</span></div>
    <?php endif;?>

    <?php if(Auth::can('supervisor','admin')||!empty($settings['allow_seller_freight'])):?>
    <div><label class="form-label">Entrega / frete</label><select class="form-select" id="orderFreightMode">
      <option value="9" <?=$settings['freight_mode']==='9'?'selected':''?>>Sem frete</option>
      <option value="0" <?=$settings['freight_mode']==='0'?'selected':''?>>CIF • remetente</option>
      <option value="1" <?=$settings['freight_mode']==='1'?'selected':''?>>FOB • destinatário</option>
      <option value="2" <?=$settings['freight_mode']==='2'?'selected':''?>>Terceiros</option>
      <option value="3" <?=$settings['freight_mode']==='3'?'selected':''?>>Próprio • remetente</option>
      <option value="4" <?=$settings['freight_mode']==='4'?'selected':''?>>Próprio • destinatário</option>
    </select></div>
    <?php else:?><input type="hidden" id="orderFreightMode" value="<?=e((string)$settings['freight_mode'])?>"><?php endif;?>

    <details class="order-advanced">
     <summary>Mais informações</summary>
     <div class="mt-3"><label class="form-label">Pedido do cliente</label><input class="form-control" id="orderCustomerNumber" maxlength="30"></div>
     <div class="mt-3"><label class="form-label">Contato</label><input class="form-control" id="orderContact" maxlength="100"></div>
     <div class="mt-3"><label class="form-label">Contrato</label><input class="form-control" id="orderContract" maxlength="20"></div>
     <div class="mt-3"><label class="form-label">Observação</label><textarea class="form-control" id="orderNotes" rows="3" placeholder="Opcional"></textarea></div>
    </details>
    <input type="hidden" id="orderRequestToken" value="<?=e($requestToken)?>">
   </div>

   <div class="order-total-box">
    <span>Total do pedido</span>
    <strong id="orderGrandTotal">R$ 0,00</strong>
    <small id="orderSummaryText">Adicione produtos para continuar.</small>
   </div>

   <button class="btn btn-primary w-100 order-submit" type="button" id="submitOrder" <?=$readiness['ready']?'':'disabled'?>><i class="fa-solid fa-paper-plane"></i>Enviar pedido para Omie</button>
   <div class="order-submit-status" id="orderSubmitStatus"></div>
  </div>
 </aside>
</div>

<script>
document.addEventListener('DOMContentLoaded',()=>{
 const cfg=window.TDCRM_CONFIG||{};
 const money=v=>new Intl.NumberFormat('pt-BR',{style:'currency',currency:'BRL'}).format(Number(v||0));
 const esc=s=>{const d=document.createElement('div');d.textContent=String(s??'');return d.innerHTML;};
 const clientSearch=document.getElementById('orderClientSearch');
 const clientResults=document.getElementById('orderClientResults');
 const clientId=document.getElementById('orderClientId');
 const selectedClient=document.getElementById('orderSelectedClient');
 const selectedClientName=document.getElementById('selectedClientName');
 const selectedClientMeta=document.getElementById('selectedClientMeta');
 const productSearch=document.getElementById('orderProductSearch');
 const productResults=document.getElementById('orderProductResults');
 const itemsEl=document.getElementById('orderItems');
 const emptyEl=document.getElementById('orderEmpty');
 const itemCount=document.getElementById('orderItemCount');
 const totalEl=document.getElementById('orderGrandTotal');
 const summaryText=document.getElementById('orderSummaryText');
 const submit=document.getElementById('submitOrder');
 const status=document.getElementById('orderSubmitStatus');
 const canEditCommercial=<?=json_encode($canEditCommercial)?>;
 let items=[];
 let clientTimer,productTimer;

 function chooseClient(c){
   clientId.value=c.id;
   selectedClientName.textContent=c.name||'Cliente';
   const base=[c.document,c.city&&c.uf?c.city+' / '+c.uf:c.uf].filter(Boolean).join(' • ');
   selectedClientMeta.textContent=base+(Number(c.overdue_amount||0)>0?' • '+money(c.overdue_amount)+' vencido':' • financeiro em dia');
   selectedClient.classList.remove('d-none');
   clientResults.innerHTML='';
   clientSearch.value='';
 }
 document.getElementById('clearOrderClient')?.addEventListener('click',()=>{clientId.value='0';selectedClient.classList.add('d-none');clientSearch.focus();});

 async function searchClients(){
   const q=clientSearch.value.trim();if(q.length<2){clientResults.innerHTML='';return;}
   const r=await fetch(cfg.baseUrl+'/api/order-clients.php?q='+encodeURIComponent(q),{credentials:'same-origin'});
   const data=await r.json();
   clientResults.innerHTML=(data.items||[]).map(c=>`<button type="button" class="order-result-item" data-client='${encodeURIComponent(JSON.stringify(c))}'><span class="avatar avatar-sm">${esc((c.name||'?').slice(0,1).toUpperCase())}</span><span><strong>${esc(c.name)}</strong><small>${esc([c.document,c.city,c.uf].filter(Boolean).join(' • '))}</small></span><i class="fa-solid fa-plus"></i></button>`).join('')||'<div class="order-no-result">Nenhum cliente encontrado.</div>';
   clientResults.querySelectorAll('[data-client]').forEach(b=>b.addEventListener('click',()=>chooseClient(JSON.parse(decodeURIComponent(b.dataset.client)))));
 }
 clientSearch?.addEventListener('input',()=>{clearTimeout(clientTimer);clientTimer=setTimeout(searchClients,250);});

 function addProduct(p){
   const existing=items.find(i=>Number(i.product_id)===Number(p.id));
   if(existing){existing.quantity+=1;}else items.push({product_id:Number(p.id),description:p.description,code:p.internal_code||p.omie_code,unit:p.unit||'UN',stock_qty:p.stock_qty===null?null:Number(p.stock_qty),quantity:1,unit_price:Number(p.unit_price||0),discount:0});
   productSearch.value='';productResults.innerHTML='';renderItems();
 }
 async function searchProducts(){
   const q=productSearch.value.trim();if(q.length<2){productResults.innerHTML='';return;}
   const r=await fetch(cfg.baseUrl+'/api/order-products.php?q='+encodeURIComponent(q),{credentials:'same-origin'});
   const data=await r.json();
   productResults.innerHTML=(data.items||[]).map(p=>`<button type="button" class="order-result-item" data-product='${encodeURIComponent(JSON.stringify(p))}'><span class="order-product-icon"><i class="fa-solid fa-box"></i></span><span><strong>${esc(p.description)}</strong><small>${esc(p.internal_code||p.omie_code)} • ${esc(p.unit||'UN')} • ${money(p.unit_price)}${p.stock_qty!==null&&p.stock_qty!==undefined?' • estoque '+Number(p.stock_qty).toLocaleString('pt-BR'):''}</small></span><i class="fa-solid fa-plus"></i></button>`).join('')||'<div class="order-no-result">Nenhum produto encontrado. Atualize o módulo Produtos.</div>';
   productResults.querySelectorAll('[data-product]').forEach(b=>b.addEventListener('click',()=>addProduct(JSON.parse(decodeURIComponent(b.dataset.product)))));
 }
 productSearch?.addEventListener('input',()=>{clearTimeout(productTimer);productTimer=setTimeout(searchProducts,250);});

 function renderItems(){
   if(!items.length){
     itemsEl.innerHTML='';itemsEl.appendChild(emptyEl);emptyEl.classList.remove('d-none');
   }else{
     itemsEl.innerHTML=items.map((i,idx)=>`<div class="order-item-row" data-index="${idx}">
       <div class="order-item-main"><strong>${esc(i.description)}</strong><small>${esc(i.code)} • ${esc(i.unit)}${i.stock_qty!==null?' • estoque '+Number(i.stock_qty).toLocaleString('pt-BR'):''}</small></div>
       <div><label>Qtd.</label><input class="form-control order-qty" type="number" min="0.01" step="0.01" value="${i.quantity}"></div>
       <div><label>Unitário</label><input class="form-control order-price" type="number" min="0" step="0.01" value="${Number(i.unit_price).toFixed(2)}" ${canEditCommercial?'':'readonly'}></div>
       <div><label>Desconto</label><input class="form-control order-discount" type="number" min="0" step="0.01" value="${Number(i.discount).toFixed(2)}" ${canEditCommercial?'':'readonly'}></div>
       <div class="order-line-total"><span>Total</span><strong>${money(i.quantity*i.unit_price-i.discount)}</strong></div>
       <button type="button" class="icon-button order-remove" title="Remover"><i class="fa-solid fa-trash"></i></button>
     </div>`).join('');
     itemsEl.querySelectorAll('.order-item-row').forEach(row=>{
       const idx=Number(row.dataset.index);
       row.querySelector('.order-qty').addEventListener('input',e=>{items[idx].quantity=Math.max(0,Number(e.target.value||0));renderTotals();row.querySelector('.order-line-total strong').textContent=money(items[idx].quantity*items[idx].unit_price-items[idx].discount);});
       row.querySelector('.order-price').addEventListener('input',e=>{items[idx].unit_price=Math.max(0,Number(e.target.value||0));renderTotals();row.querySelector('.order-line-total strong').textContent=money(items[idx].quantity*items[idx].unit_price-items[idx].discount);});
       row.querySelector('.order-discount').addEventListener('input',e=>{items[idx].discount=Math.max(0,Number(e.target.value||0));renderTotals();row.querySelector('.order-line-total strong').textContent=money(items[idx].quantity*items[idx].unit_price-items[idx].discount);});
       row.querySelector('.order-remove').addEventListener('click',()=>{items.splice(idx,1);renderItems();});
     });
   }
   renderTotals();
 }
 function renderTotals(){
   const total=items.reduce((sum,i)=>sum+Math.max(0,i.quantity*i.unit_price-i.discount),0);
   totalEl.textContent=money(total);
   itemCount.textContent=items.length+' '+(items.length===1?'item':'itens');
   summaryText.textContent=items.length?items.length+' item(ns) no pedido.':'Adicione produtos para continuar.';
 }

 submit?.addEventListener('click',async()=>{
   if(!Number(clientId.value)){alert('Selecione o cliente.');return;}
   if(!items.length){alert('Adicione ao menos um produto.');return;}
   if(!document.getElementById('orderPaymentTerm').value){alert('Escolha a condição de pagamento.');return;}
   const seller=document.getElementById('orderSeller');
   if(seller && !seller.value){alert('Selecione o vendedor.');return;}
   submit.disabled=true;status.className='order-submit-status is-loading';status.innerHTML='<i class="fa-solid fa-spinner fa-spin"></i> Enviando para a Omie...';
   try{
     const payload={
       _token:cfg.csrf,
       client_id:Number(clientId.value),
       seller_omie_code:seller?.value||'',
       request_token:document.getElementById('orderRequestToken').value,
       forecast_date:document.getElementById('orderForecast').value,
       payment_term_code:document.getElementById('orderPaymentTerm').value,
       payment_method_code:document.getElementById('orderPaymentMethod').value,
       stage_code:document.getElementById('orderStage').value,
       category_code:document.getElementById('orderCategory').value,
       account_code:document.getElementById('orderAccount').value,
       tax_scenario_code:document.getElementById('orderTaxScenario').value,
       stock_location_code:document.getElementById('orderStockLocation').value,
       freight_mode:document.getElementById('orderFreightMode').value,
       customer_order_number:document.getElementById('orderCustomerNumber').value,
       contact:document.getElementById('orderContact').value,
       contract_number:document.getElementById('orderContract').value,
       notes:document.getElementById('orderNotes').value,
       items:items.map(i=>({product_id:i.product_id,quantity:i.quantity,unit_price:i.unit_price,discount:i.discount}))
     };
     const r=await fetch(cfg.baseUrl+'/api/order-create.php',{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json'},credentials:'same-origin',body:JSON.stringify(payload)});
     const data=await r.json();
     if(!r.ok||!data.ok)throw new Error(data.error||'Não foi possível incluir o pedido.');
     status.className='order-submit-status is-success';
     status.innerHTML='<i class="fa-solid fa-circle-check"></i><div><strong>Pedido enviado com sucesso.</strong><span>'+(data.order_number?'Pedido '+esc(data.order_number)+' • ':'')+money(data.total)+'</span></div>';
     submit.innerHTML='<i class="fa-solid fa-check"></i>Pedido criado';
     setTimeout(()=>location.href=cfg.baseUrl+'/pedidos.php',1800);
   }catch(e){
     submit.disabled=false;status.className='order-submit-status is-error';status.innerHTML='<i class="fa-solid fa-triangle-exclamation"></i><span>'+esc(e.message)+'</span>';
   }
 });
 renderItems();
});
</script>
<?php include '_footer.php';?>