document.addEventListener('DOMContentLoaded',()=>{
  document.querySelectorAll('.sidebar nav a').forEach(link=>{
    try{
      const current=new URL(location.href).pathname.replace(/\/$/,'');
      const target=new URL(link.href).pathname.replace(/\/$/,'');
      if(target===current||(target!=='/'&&current.startsWith(target+'/')))link.classList.add('active');
    }catch(e){}
  });

  if(window.DataTable){
    document.querySelectorAll('.table-card table').forEach(table=>{
      if(table.dataset.dtReady==='1'||!table.tHead)return;
      table.dataset.dtReady='1';
      new DataTable(table,{
        pageLength:10,
        lengthChange:false,
        searching:true,
        ordering:true,
        paging:true,
        info:true,
        autoWidth:false,
        order:[],
        language:{
          search:'',
          searchPlaceholder:'Buscar nesta tabela...',
          info:'Exibindo _START_–_END_ de _TOTAL_',
          infoEmpty:'Nenhum registro',
          infoFiltered:'(filtrado de _MAX_)',
          zeroRecords:'Nenhum registro encontrado',
          emptyTable:'Nenhum registro disponível',
          paginate:{first:'Primeira',last:'Última',next:'›',previous:'‹'}
        }
      });
    });
  }

  document.querySelector('[data-menu]')?.addEventListener('click',()=>document.querySelector('.sidebar')?.classList.toggle('open'));
  document.querySelectorAll('[data-confirm]').forEach(el=>el.addEventListener('click',e=>{if(!confirm(el.dataset.confirm||'Confirmar operação?'))e.preventDefault();}));

  const clientCreateForm=document.getElementById('clientCreateForm');
  if(clientCreateForm){
    clientCreateForm.querySelector('[data-real-client-send]')?.addEventListener('click',e=>{
      if(!confirm('Este botão enviará este cliente de verdade para a Omie. Deseja continuar?'))e.preventDefault();
    });
    const onlyDigits=v=>String(v||'').replace(/\D+/g,'');
    const doc=clientCreateForm.querySelector('[data-document]');
    const phoneDdd=clientCreateForm.querySelector('[data-phone-ddd]');
    const phoneNumber=clientCreateForm.querySelector('[data-phone-number]');
    const cep=clientCreateForm.querySelector('[data-cep]');
    const cnpjBtn=clientCreateForm.querySelector('[data-cnpj-lookup]');
    const cepBtn=clientCreateForm.querySelector('[data-cep-lookup]');
    const cnpjStatus=clientCreateForm.querySelector('[data-cnpj-status]');
    const cepStatus=clientCreateForm.querySelector('[data-cep-status]');
    const fields=name=>clientCreateForm.querySelector('[name="'+name+'"]');
    const formatDoc=v=>{
      const n=onlyDigits(v).slice(0,14);
      if(n.length<=11)return n.replace(/(\d{3})(\d)/,'$1.$2').replace(/(\d{3})(\d)/,'$1.$2').replace(/(\d{3})(\d{1,2})$/,'$1-$2');
      return n.replace(/(\d{2})(\d)/,'$1.$2').replace(/(\d{3})(\d)/,'$1.$2').replace(/(\d{3})(\d)/,'$1/$2').replace(/(\d{4})(\d{1,2})$/,'$1-$2');
    };
    const formatPhoneNumber=v=>{const n=onlyDigits(v).slice(0,9);return n.length>8?n.replace(/(\d{5})(\d{1,4})$/,'$1-$2'):n.replace(/(\d{4})(\d{1,4})$/,'$1-$2');};
    const formatCep=v=>onlyDigits(v).slice(0,8).replace(/(\d{5})(\d)/,'$1-$2');
    const setStatus=(el,msg,type='')=>{if(!el)return;el.textContent=msg;el.classList.remove('ok','error','loading');if(type)el.classList.add(type);};
    const setLoading=(btn,on)=>{if(!btn)return;btn.disabled=on;btn.classList.toggle('loading',on);btn.innerHTML=on?'<i class="fa-solid fa-spinner fa-spin"></i>':btn.dataset.icon||btn.innerHTML;};
    if(cnpjBtn)cnpjBtn.dataset.icon=cnpjBtn.innerHTML;
    if(cepBtn)cepBtn.dataset.icon=cepBtn.innerHTML;
    const fetchJson=async path=>{
      const res=await fetch(window.APP_URL+path,{credentials:'same-origin'});
      const data=await res.json().catch(()=>({ok:false,error:'Resposta inválida.'}));
      if(!res.ok||!data.ok)throw new Error(data.error||'Falha na consulta.');
      return data.data;
    };
    const fill=(name,value,overwrite=true)=>{
      const el=fields(name);if(!el||value===undefined||value===null||String(value).trim()==='')return;
      if(!overwrite&&String(el.value).trim()!=='')return;
      el.value=String(value);
      el.dispatchEvent(new Event('input',{bubbles:true}));
      el.classList.add('lookup-filled');
      setTimeout(()=>el.classList.remove('lookup-filled'),1400);
    };
    const lookupCep=async(auto=false)=>{
      const digits=onlyDigits(cep?.value);
      if(digits.length!==8){if(!auto)setStatus(cepStatus,'Informe um CEP com 8 dígitos.','error');return;}
      try{
        setLoading(cepBtn,true);setStatus(cepStatus,'Consultando CEP...','loading');
        const d=await fetchJson('/api/public/cep?value='+encodeURIComponent(digits));
        fill('address',d.address,false);fill('neighborhood',d.neighborhood,false);fill('city',d.city,false);fill('uf',d.uf,false);
        setStatus(cepStatus,'Endereço localizado e preenchido.','ok');
      }catch(e){setStatus(cepStatus,e.message,'error');}
      finally{setLoading(cepBtn,false);}
    };
    const lookupCnpj=async(auto=false)=>{
      const digits=onlyDigits(doc?.value);
      if(digits.length!==14){if(!auto&&digits.length!==11)setStatus(cnpjStatus,'Informe um CNPJ com 14 dígitos.','error');return;}
      try{
        setLoading(cnpjBtn,true);setStatus(cnpjStatus,'Consultando CNPJ...','loading');
        const d=await fetchJson('/api/public/cnpj?value='+encodeURIComponent(digits));
        fill('legal_name',d.legal_name);fill('trade_name',d.trade_name);fill('email',d.email,false);
        if(d.phone_ddd){const p=fields('phone_ddd');if(p&&!p.value){p.value=onlyDigits(d.phone_ddd).slice(0,2);p.classList.add('lookup-filled');setTimeout(()=>p.classList.remove('lookup-filled'),1400);}}
        if(d.phone_number){const p=fields('phone_number');if(p&&!p.value){p.value=formatPhoneNumber(d.phone_number);p.classList.add('lookup-filled');setTimeout(()=>p.classList.remove('lookup-filled'),1400);}}
        if(d.zip_code){const z=fields('zip_code');if(z&&!z.value){z.value=formatCep(d.zip_code);z.classList.add('lookup-filled');setTimeout(()=>z.classList.remove('lookup-filled'),1400);}}
        fill('address',d.address,false);fill('address_number',d.address_number,false);fill('complement',d.complement,false);fill('neighborhood',d.neighborhood,false);fill('city',d.city,false);fill('uf',d.uf,false);
        setStatus(cnpjStatus,'CNPJ localizado. Dados cadastrais preenchidos.','ok');
        if(d.zip_code)setStatus(cepStatus,'Endereço preenchido a partir do cadastro do CNPJ.','ok');
      }catch(e){setStatus(cnpjStatus,e.message,'error');}
      finally{setLoading(cnpjBtn,false);}
    };
    if(doc){
      doc.value=formatDoc(doc.value);
      let prev=onlyDigits(doc.value);
      doc.addEventListener('input',()=>{doc.value=formatDoc(doc.value);const n=onlyDigits(doc.value);if(n.length===14&&n!==prev){prev=n;lookupCnpj(true);}});
      doc.addEventListener('blur',()=>{const n=onlyDigits(doc.value);if(n.length===14&&n!==prev){prev=n;lookupCnpj(true);}});
    }
    if(phoneDdd){phoneDdd.value=onlyDigits(phoneDdd.value).slice(0,2);phoneDdd.addEventListener('input',()=>phoneDdd.value=onlyDigits(phoneDdd.value).slice(0,2));}
    if(phoneNumber){phoneNumber.value=formatPhoneNumber(phoneNumber.value);phoneNumber.addEventListener('input',()=>phoneNumber.value=formatPhoneNumber(phoneNumber.value));}
    if(cep){
      cep.value=formatCep(cep.value);
      let prevCep=onlyDigits(cep.value);
      cep.addEventListener('input',()=>{cep.value=formatCep(cep.value);const n=onlyDigits(cep.value);if(n.length===8&&n!==prevCep){prevCep=n;lookupCep(true);}});
      cep.addEventListener('blur',()=>{const n=onlyDigits(cep.value);if(n.length===8&&n!==prevCep){prevCep=n;lookupCep(true);}});
    }
    cnpjBtn?.addEventListener('click',()=>lookupCnpj(false));
    cepBtn?.addEventListener('click',()=>lookupCep(false));

    const tagEditor=clientCreateForm.querySelector('[data-tag-editor]');
    if(tagEditor){
      const input=tagEditor.querySelector('[data-tag-input]');
      const hidden=tagEditor.querySelector('[data-tag-hidden]');
      const chips=tagEditor.querySelector('[data-tag-chips]');
      let tags=String(hidden?.value||'').split(/[,;\n]+/).map(v=>v.trim()).filter(Boolean);
      tags=[...new Map(tags.map(tag=>[tag.toLocaleLowerCase('pt-BR'),tag])).values()].slice(0,20);
      const syncTags=()=>{
        if(hidden)hidden.value=tags.join(', ');
        if(chips)chips.innerHTML=tags.map((tag,index)=>'<span class="tag-chip">'+escHtml(tag)+'<button type="button" data-tag-remove="'+index+'" aria-label="Remover tag">×</button></span>').join('');
        chips?.querySelectorAll('[data-tag-remove]').forEach(btn=>btn.addEventListener('click',()=>{tags.splice(Number(btn.dataset.tagRemove),1);syncTags();}));
      };
      const escHtml=value=>{const d=document.createElement('div');d.textContent=value;return d.innerHTML;};
      const addTag=value=>{
        const tag=String(value||'').trim().replace(/^#+/,'');
        if(!tag)return;
        if(!tags.some(v=>v.toLocaleLowerCase('pt-BR')===tag.toLocaleLowerCase('pt-BR'))&&tags.length<20)tags.push(tag);
        if(input)input.value='';
        syncTags();
      };
      input?.addEventListener('keydown',e=>{
        if(e.key==='Enter'||e.key===','||e.key===';'){e.preventDefault();addTag(input.value);}
        if(e.key==='Backspace'&&input.value===''&&tags.length){tags.pop();syncTags();}
      });
      input?.addEventListener('blur',()=>addTag(input.value));
      syncTags();
    }
  }


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

  document.querySelectorAll('[data-order-tabs]').forEach(group=>{
    const buttons=Array.from(group.querySelectorAll('[data-order-tab]'));
    const panels=Array.from(group.querySelectorAll('[data-order-panel]'));
    const activate=name=>{
      buttons.forEach(btn=>{
        const on=btn.dataset.orderTab===name;
        btn.classList.toggle('active',on);
        btn.setAttribute('aria-selected',on?'true':'false');
      });
      panels.forEach(panel=>panel.classList.toggle('active',panel.dataset.orderPanel===name));
    };
    buttons.forEach(btn=>btn.addEventListener('click',()=>activate(btn.dataset.orderTab)));
  });

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
  const grandTotal=document.getElementById('grandTotal'),discountTotal=document.getElementById('discountTotal'),orderGrandTotal=document.getElementById('orderGrandTotal'),footerGrandTotal=document.getElementById('footerGrandTotal'),financialTotal=document.getElementById('financialTotal'),fiscalTotal=document.getElementById('fiscalTotal'),clientEmailPreview=document.getElementById('clientEmailPreview');

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
    if(clientEmailPreview)clientEmailPreview.value=client.email||'';
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

    const gross=items.reduce((sum,item)=>sum+lineGross(item),0);
    const discount=items.reduce((sum,item)=>sum+lineDiscount(item),0);
    const commercial=items.reduce((sum,item)=>sum+lineNet(item),0);
    const financial=items.reduce((sum,item)=>sum+(item.no_finance?0:lineNet(item)),0);
    const fiscal=items.reduce((sum,item)=>sum+(item.no_total?0:lineNet(item)),0);
    grandTotal.textContent=money(gross);
    if(discountTotal)discountTotal.textContent=money(discount);
    if(orderGrandTotal)orderGrandTotal.textContent=money(commercial);
    if(footerGrandTotal)footerGrandTotal.textContent=money(commercial);
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