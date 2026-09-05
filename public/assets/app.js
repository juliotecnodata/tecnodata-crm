document.addEventListener('DOMContentLoaded',()=>{
  const paginateTable=(table,pageSize=10)=>{
    if(table.dataset.pagerReady==='1')return;
    const tbody=table.tBodies[0];if(!tbody)return;
    const rows=Array.from(tbody.rows);if(rows.length===0)return;
    table.dataset.pagerReady='1';

    const card=table.closest('.table-card');if(!card)return;
    card.dataset.paginated='1';

    const scroll=document.createElement('div');
    scroll.className='table-scroll';
    table.parentNode.insertBefore(scroll,table);
    scroll.appendChild(table);

    const pager=document.createElement('div');
    pager.className='table-pager';
    const info=document.createElement('div');info.className='table-pager-info';
    const actions=document.createElement('div');actions.className='table-pager-actions';
    pager.append(info,actions);card.appendChild(pager);

    let current=1;
    const totalPages=Math.max(1,Math.ceil(rows.length/pageSize));

    const render=()=>{
      current=Math.min(Math.max(1,current),totalPages);
      const start=(current-1)*pageSize,end=start+pageSize;
      rows.forEach((row,index)=>row.hidden=index<start||index>=end);
      const shownStart=rows.length?start+1:0,shownEnd=Math.min(end,rows.length);
      info.textContent=`Exibindo ${shownStart}–${shownEnd} de ${rows.length} • 10 por página`;
      actions.innerHTML='';

      const make=(label,page,disabled=false,active=false)=>{
        const b=document.createElement('button');b.type='button';b.textContent=label;b.disabled=disabled;b.classList.toggle('active',active);
        b.addEventListener('click',()=>{current=page;render();card.scrollIntoView({behavior:'smooth',block:'start'});});
        actions.appendChild(b);
      };
      make('‹',current-1,current===1);
      const from=Math.max(1,Math.min(current-2,totalPages-4)),to=Math.min(totalPages,from+4);
      for(let p=from;p<=to;p++)make(String(p),p,false,p===current);
      make('›',current+1,current===totalPages);
    };
    render();
  };

  document.querySelectorAll('.table-card table').forEach(table=>paginateTable(table,10));
  document.querySelector('[data-menu]')?.addEventListener('click',()=>document.querySelector('.sidebar')?.classList.toggle('open'));

  document.querySelectorAll('[data-sync]').forEach(btn=>btn.addEventListener('click',async()=>{
    const module=btn.dataset.sync;let page=1;btn.disabled=true;
    const state=document.getElementById('sync-state-'+module);
    try{
      while(true){
        state.textContent='Página '+page;
        const body=new URLSearchParams({_token:window.CSRF,module,page:String(page)});
        const response=await fetch(window.APP_URL+'/api/sync',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body});
        const data=await response.json();
        if(!response.ok||!data.ok)throw new Error(data.error||'Falha na sincronização');
        state.textContent=data.done?'Concluído':(data.total_pages?'Página '+data.page+'/'+data.total_pages:'Processando...');
        if(data.done)break;
        page++;
      }
      setTimeout(()=>location.reload(),500);
    }catch(error){state.textContent=error.message;btn.disabled=false;}
  }));

  const form=document.getElementById('orderForm');
  if(!form)return;

  const meta=window.ORDER_META||{categories:[],taxes:[],stocks:[],profiles:[]};
  const money=value=>new Intl.NumberFormat('pt-BR',{style:'currency',currency:'BRL'}).format(Number(value||0));
  const esc=value=>{const d=document.createElement('div');d.textContent=String(value??'');return d.innerHTML;};
  const options=(rows,valueKey,labelKey,current,blank='Padrão do pedido')=>{
    let html='<option value="">'+esc(blank)+'</option>';
    rows.forEach(row=>{const v=String(row[valueKey]??'');html+='<option value="'+esc(v)+'" '+(String(current??'')===v?'selected':'')+'>'+esc(row[labelKey]??v)+'</option>';});
    return html;
  };
  const profileSelect=document.getElementById('orderProfile');
  const clientSearch=document.getElementById('clientSearch'),clientResults=document.getElementById('clientResults'),clientId=document.getElementById('clientId'),clientSelected=document.getElementById('clientSelected');
  const productSearch=document.getElementById('productSearch'),productResults=document.getElementById('productResults'),itemsJson=document.getElementById('itemsJson'),itemsEl=document.getElementById('orderItems');
  const grandTotal=document.getElementById('grandTotal'),financialTotal=document.getElementById('financialTotal'),fiscalTotal=document.getElementById('fiscalTotal');

  let items=Array.isArray(window.ORDER_OLD_ITEMS)?window.ORDER_OLD_ITEMS:[];
  let clientTimer,productTimer;

  function currentProfile(){
    const opt=profileSelect?.selectedOptions?.[0];
    return {
      no_stock:opt?.dataset.noStock==='S',
      no_finance:opt?.dataset.noFinance==='S',
      no_total:opt?.dataset.noTotal==='S',
      reserve_stock:opt?.dataset.reserve==='S'
    };
  }
  function normalizeItem(item){
    const p=currentProfile();
    return {
      product_id:Number(item.product_id),
      description:item.description||'Produto',
      sku:item.sku||'',
      unit:item.unit||'UN',
      quantity:Number(item.quantity||1),
      unit_price:Number(item.unit_price||0),
      discount_type:item.discount_type||'V',
      discount_value:Number(item.discount_value??item.discount??0),
      no_stock:item.no_stock??p.no_stock,
      no_finance:item.no_finance??p.no_finance,
      no_total:item.no_total??p.no_total,
      reserve_stock:item.reserve_stock??p.reserve_stock,
      category_code:item.category_code||'',
      tax_scenario_code:item.tax_scenario_code||'',
      stock_location_code:item.stock_location_code||'',
      purchase_order_number:item.purchase_order_number||'',
      purchase_order_item:Number(item.purchase_order_item||0),
      fiscal_notes:item.fiscal_notes||'',
      cfop:item.cfop||'',
      ncm:item.ncm||''
    };
  }
  items=items.map(normalizeItem);

  async function api(path){
    const response=await fetch(window.APP_URL+path,{credentials:'same-origin'});
    const data=await response.json();
    if(!response.ok)throw new Error(data.error||'Falha');
    return data;
  }
  function chooseClient(client){
    clientId.value=client.id;
    clientSelected.innerHTML='<strong>'+esc(client.name)+'</strong><small>'+esc([client.document,client.city,client.uf].filter(Boolean).join(' • '))+'</small>';
    clientResults.innerHTML='';clientSearch.value='';
  }
  async function findClients(){
    const q=clientSearch.value.trim();if(q.length<2){clientResults.innerHTML='';return;}
    const data=await api('/api/clients?q='+encodeURIComponent(q));
    clientResults.innerHTML=data.items.map(c=>'<button type="button" class="search-result" data-client="'+encodeURIComponent(JSON.stringify(c))+'"><span><strong>'+esc(c.name)+'</strong><small>'+esc(c.document||'')+'</small></span><i class="fa-solid fa-plus"></i></button>').join('');
    clientResults.querySelectorAll('[data-client]').forEach(btn=>btn.onclick=()=>chooseClient(JSON.parse(decodeURIComponent(btn.dataset.client))));
  }
  clientSearch?.addEventListener('input',()=>{clearTimeout(clientTimer);clientTimer=setTimeout(findClients,250);});

  function addProduct(product){
    const profile=currentProfile();
    items.push(normalizeItem({
      product_id:Number(product.id),description:product.description,sku:product.sku||product.omie_code,unit:product.unit||'UN',
      quantity:1,unit_price:Number(product.unit_price||0),discount_type:'V',discount_value:0,
      no_stock:profile.no_stock,no_finance:profile.no_finance,no_total:profile.no_total,reserve_stock:profile.reserve_stock
    }));
    productSearch.value='';productResults.innerHTML='';render();
  }
  async function findProducts(){
    const q=productSearch.value.trim();if(q.length<2){productResults.innerHTML='';return;}
    const data=await api('/api/products?q='+encodeURIComponent(q));
    productResults.innerHTML=data.items.map(p=>'<button type="button" class="search-result" data-product="'+encodeURIComponent(JSON.stringify(p))+'"><span><strong>'+esc(p.description)+'</strong><small>'+esc(p.sku||p.omie_code)+' • '+money(p.unit_price)+(p.stock_qty!==null?' • estoque '+Number(p.stock_qty).toLocaleString('pt-BR'):'')+'</small></span><i class="fa-solid fa-plus"></i></button>').join('');
    productResults.querySelectorAll('[data-product]').forEach(btn=>btn.onclick=()=>addProduct(JSON.parse(decodeURIComponent(btn.dataset.product))));
  }
  productSearch?.addEventListener('input',()=>{clearTimeout(productTimer);productTimer=setTimeout(findProducts,250);});

  profileSelect?.addEventListener('change',()=>{
    if(!items.length)return;
    if(!confirm('Aplicar as regras deste tipo aos itens que já estão no pedido?'))return;
    const p=currentProfile();
    items.forEach(i=>{i.no_stock=p.no_stock;i.no_finance=p.no_finance;i.no_total=p.no_total;i.reserve_stock=p.reserve_stock;});
    render();
  });

  function lineGross(item){return Number(item.quantity||0)*Number(item.unit_price||0);}
  function lineDiscount(item){const gross=lineGross(item),v=Math.max(0,Number(item.discount_value||0));return item.discount_type==='P'?gross*Math.min(100,v)/100:v;}
  function lineNet(item){return Math.max(0,lineGross(item)-lineDiscount(item));}
  function bindInput(row,selector,event,handler){
    const el=row.querySelector(selector);if(el)el.addEventListener(event,e=>handler(e.target));
  }

  function render(){
    itemsJson.value=JSON.stringify(items);
    if(!items.length){
      itemsEl.innerHTML='<div class="order-empty"><i class="fa-solid fa-box-open"></i><strong>Nenhum item</strong><span>Pesquise um produto acima para iniciar.</span></div>';
    }else{
      itemsEl.innerHTML=items.map((item,index)=>{
        const net=lineNet(item);
        const badges=[
          item.no_stock?'<b class="rule-badge warn">Sem estoque</b>':'<b class="rule-badge ok">Movimenta estoque</b>',
          item.no_finance?'<b class="rule-badge warn">Sem financeiro</b>':'<b class="rule-badge ok">Gera financeiro</b>',
          item.no_total?'<b class="rule-badge neutral">Fora total NF-e</b>':''
        ].join('');
        return '<article class="order-item-card" data-index="'+index+'">'+
          '<div class="order-item-head"><div><strong>'+(index+1)+'. '+esc(item.description)+'</strong><small>'+esc(item.sku)+' • '+esc(item.unit)+'</small><div class="rule-badges">'+badges+'</div></div><div class="order-item-total"><span>Total</span><strong>'+money(net)+'</strong></div></div>'+
          '<div class="order-item-basic">'+
           '<div><label>Quantidade</label><input class="form-control qty" type="number" min=".0001" step=".0001" value="'+item.quantity+'"></div>'+
           '<div><label>Preço unitário</label><input class="form-control price" type="number" min="0" step=".01" value="'+item.unit_price.toFixed(2)+'"></div>'+
           '<div><label>Desconto</label><div class="discount-field"><select class="form-select discount-type"><option value="V" '+(item.discount_type==='V'?'selected':'')+'>R$</option><option value="P" '+(item.discount_type==='P'?'selected':'')+'>%</option></select><input class="form-control discount-value" type="number" min="0" step=".01" value="'+item.discount_value+'"></div></div>'+
           '<div class="item-buttons"><button type="button" class="btn btn-light duplicate" title="Duplicar"><i class="fa-regular fa-copy"></i></button><button type="button" class="btn btn-light remove" title="Remover"><i class="fa-solid fa-trash"></i></button></div>'+
          '</div>'+
          '<details class="item-rules"><summary>Regras do item <span>estoque • financeiro • fiscal</span></summary>'+
           '<div class="item-rule-switches">'+
            '<label><input type="checkbox" class="no-stock" '+(item.no_stock?'checked':'')+'><span><strong>Não movimentar estoque</strong><small>Omie: nao_movimentar_estoque</small></span></label>'+
            '<label><input type="checkbox" class="no-finance" '+(item.no_finance?'checked':'')+'><span><strong>Não gerar financeiro</strong><small>Omie: nao_gerar_financeiro</small></span></label>'+
            '<label><input type="checkbox" class="no-total" '+(item.no_total?'checked':'')+'><span><strong>Não somar na NF-e</strong><small>Omie: nao_somar_total</small></span></label>'+
            '<label><input type="checkbox" class="reserve-stock" '+(item.reserve_stock?'checked':'')+'><span><strong>Reservar estoque</strong><small>Omie: reservado</small></span></label>'+
           '</div>'+
           '<div class="item-advanced-grid">'+
            '<div><label>Categoria do item</label><select class="form-select item-category">'+options(meta.categories,'code','description',item.category_code)+'</select></div>'+
            '<div><label>Cenário fiscal do item</label><select class="form-select item-tax">'+options(meta.taxes,'omie_code','name',item.tax_scenario_code)+'</select></div>'+
            '<div><label>Local de estoque</label><select class="form-select item-stock">'+options(meta.stocks,'omie_code','name',item.stock_location_code)+'</select></div>'+
            '<div><label>CFOP</label><input class="form-control item-cfop" value="'+esc(item.cfop)+'" placeholder="Opcional"></div>'+
            '<div><label>NCM</label><input class="form-control item-ncm" value="'+esc(item.ncm)+'" placeholder="Usa cadastro se vazio"></div>'+
            '<div><label>Pedido de compra</label><input class="form-control item-po" maxlength="15" value="'+esc(item.purchase_order_number)+'"></div>'+
            '<div><label>Item pedido compra</label><input class="form-control item-po-item" type="number" min="0" value="'+(item.purchase_order_item||'')+'"></div>'+
            '<div class="wide"><label>Informações para NF-e deste item</label><textarea class="form-control item-notes" rows="2">'+esc(item.fiscal_notes)+'</textarea></div>'+
           '</div>'+
          '</details>'+
        '</article>';
      }).join('');
    }

    itemsEl.querySelectorAll('.order-item-card').forEach(row=>{
      const index=Number(row.dataset.index),item=items[index];
      bindInput(row,'.qty','change',el=>{item.quantity=Number(el.value||0);render();});
      bindInput(row,'.price','change',el=>{item.unit_price=Number(el.value||0);render();});
      bindInput(row,'.discount-type','change',el=>{item.discount_type=el.value;render();});
      bindInput(row,'.discount-value','change',el=>{item.discount_value=Number(el.value||0);render();});
      bindInput(row,'.no-stock','change',el=>{item.no_stock=el.checked;render();});
      bindInput(row,'.no-finance','change',el=>{item.no_finance=el.checked;render();});
      bindInput(row,'.no-total','change',el=>{item.no_total=el.checked;render();});
      bindInput(row,'.reserve-stock','change',el=>{item.reserve_stock=el.checked;render();});
      bindInput(row,'.item-category','change',el=>{item.category_code=el.value;itemsJson.value=JSON.stringify(items);});
      bindInput(row,'.item-tax','change',el=>{item.tax_scenario_code=el.value;itemsJson.value=JSON.stringify(items);});
      bindInput(row,'.item-stock','change',el=>{item.stock_location_code=el.value;itemsJson.value=JSON.stringify(items);});
      bindInput(row,'.item-cfop','change',el=>{item.cfop=el.value.trim();itemsJson.value=JSON.stringify(items);});
      bindInput(row,'.item-ncm','change',el=>{item.ncm=el.value.trim();itemsJson.value=JSON.stringify(items);});
      bindInput(row,'.item-po','change',el=>{item.purchase_order_number=el.value.trim();itemsJson.value=JSON.stringify(items);});
      bindInput(row,'.item-po-item','change',el=>{item.purchase_order_item=Number(el.value||0);itemsJson.value=JSON.stringify(items);});
      bindInput(row,'.item-notes','change',el=>{item.fiscal_notes=el.value;itemsJson.value=JSON.stringify(items);});
      row.querySelector('.remove')?.addEventListener('click',()=>{items.splice(index,1);render();});
      row.querySelector('.duplicate')?.addEventListener('click',()=>{items.splice(index+1,0,JSON.parse(JSON.stringify(item)));render();});
    });

    const commercial=items.reduce((sum,item)=>sum+lineNet(item),0);
    const financial=items.reduce((sum,item)=>sum+(item.no_finance?0:lineNet(item)),0);
    const fiscal=items.reduce((sum,item)=>sum+(item.no_total?0:lineNet(item)),0);
    grandTotal.textContent=money(commercial);
    financialTotal.textContent=money(financial);
    fiscalTotal.textContent=money(fiscal);
    itemsJson.value=JSON.stringify(items);
  }

  render();
  if(window.ORDER_PREFILL_CLIENT){
    api('/api/clients?q='+encodeURIComponent(String(window.ORDER_PREFILL_CLIENT))).then(data=>{
      const client=data.items.find(x=>Number(x.id)===Number(window.ORDER_PREFILL_CLIENT));if(client)chooseClient(client);
    }).catch(()=>{});
  }
});