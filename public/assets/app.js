document.addEventListener('DOMContentLoaded',()=>{
  const appBase=(()=>{try{return new URL(window.APP_URL||location.origin).pathname.replace(/^\/+|\/+$/g,'');}catch(e){return '';}})();
  const routeParts=location.pathname.replace(/^\/+|\/+$/g,'').split('/').filter(Boolean);
  const baseParts=appBase.split('/').filter(Boolean);
  const relativeParts=routeParts.slice(baseParts.length);
  const section=relativeParts[0]||'dashboard';
  let page=section;
  if(section==='my-portfolio')page='clients';
  if(section==='clients')page=relativeParts[1]==='new'||relativeParts[2]==='edit'?'client-editor':(relativeParts[1]?'client-detail':'clients');
  if(section==='orders')page=relativeParts[1]==='new'?'order-new':(relativeParts[1]?'order-detail':'orders');
  if(section==='collection'&&relativeParts[1])page='collection-case';
  document.body.dataset.page=page.replace(/[^a-z0-9-]/g,'');

  const pageTopbarTools=document.querySelector('[data-topbar-tools]');
  const globalTopbarActions=document.querySelector('.topbar-actions');
  if(pageTopbarTools&&globalTopbarActions)globalTopbarActions.prepend(pageTopbarTools);

  const contactDialog=document.querySelector('[data-contact-dialog]');
  const contactScheduleForm=document.querySelector('[data-contact-schedule-form]');
  if(contactDialog&&contactScheduleForm){
    const taskInput=contactScheduleForm.querySelector('[name="task_id"]');
    const sellerInput=contactScheduleForm.querySelector('[name="assigned_user_id"]');
    const titleInput=contactScheduleForm.querySelector('[name="title"]');
    const dueInput=contactScheduleForm.querySelector('[name="due_at"]');
    const dialogTitle=contactDialog.querySelector('[data-contact-dialog-title]');
    const dialogClient=contactDialog.querySelector('[data-contact-dialog-client]');
    document.querySelectorAll('[data-contact-schedule]').forEach(button=>button.addEventListener('click',()=>{
      try{
        const data=JSON.parse(button.dataset.contactSchedule||'{}');
        contactScheduleForm.action=(window.APP_URL||'')+'/contact-monitoring/'+Number(data.client_id)+'/schedule';
        taskInput.value=Number(data.task_id||0)||'';
        sellerInput.value=Number(data.assigned_user_id||0)||'';
        titleInput.value=data.title||'Próximo contato';
        dueInput.value=data.due_at||'';
        dialogTitle.textContent=data.task_id?'Reagendar próximo contato':'Agendar próximo contato';
        dialogClient.textContent=data.client_name||'';
        contactDialog.showModal();
      }catch(error){showNotice('danger','Não foi possível abrir o agendamento','Atualize a página e tente novamente.');}
    }));
    contactDialog.querySelectorAll('[data-contact-dialog-close]').forEach(button=>button.addEventListener('click',()=>contactDialog.close()));
    contactDialog.addEventListener('click',event=>{if(event.target===contactDialog)contactDialog.close();});
  }

  const clientStateFilter=document.querySelector('[data-client-state-filter]');
  const clientTagFilter=document.querySelector('[data-client-tag-filter]');
  clientTagFilter?.addEventListener('change',()=>clientTagFilter.form?.requestSubmit());
  const monitorForm=document.querySelector('.tdset-monitor-form');
  if(monitorForm){
    const monitorChecks=Array.from(monitorForm.querySelectorAll('[name="monitor_user_ids[]"]'));
    const monitorCount=monitorForm.querySelector('[data-monitor-selected]');
    const updateMonitorCount=()=>{if(monitorCount)monitorCount.textContent=String(monitorChecks.filter(input=>input.checked).length);};
    monitorChecks.forEach(input=>input.addEventListener('change',updateMonitorCount));
    monitorForm.addEventListener('submit',event=>{if(!monitorChecks.some(input=>input.checked)){event.preventDefault();showNotice('warning','Selecione um participante','Marque pelo menos um usuário de vendas ou cobrança para o acompanhamento.');}});
  }
  const clearClientDddParams=url=>Array.from(new Set(url.searchParams.keys())).forEach(key=>{if(/^ddds(?:\[\d*\])?$/.test(key))url.searchParams.delete(key);});
  if(clientStateFilter){
    clientStateFilter.addEventListener('change',()=>{
      const url=new URL(location.href);
      const state=clientStateFilter.value.trim();
      const clientTable=document.querySelector('.clients-datatable');
      const tableSearch=String(clientTable?._dataTable?.search?.()??'').trim();
      if(state)url.searchParams.set('uf',state);else url.searchParams.delete('uf');
      clearClientDddParams(url);
      if(tableSearch)url.searchParams.set('q',tableSearch);else url.searchParams.delete('q');
      url.searchParams.delete('page');
      location.assign(url.toString());
    });
  }
  document.querySelector('[data-client-ddd-apply]')?.addEventListener('click',()=>{
    const url=new URL(location.href);
    const state=clientStateFilter?.value.trim()||'';
    const selected=Array.from(document.querySelectorAll('.clients-ddd-picker input[name="ddds[]"]:checked')).map(input=>input.value);
    const clientTable=document.querySelector('.clients-datatable');
    const tableSearch=String(clientTable?._dataTable?.search?.()??'').trim();
    clearClientDddParams(url);
    selected.forEach(ddd=>url.searchParams.append('ddds[]',ddd));
    if(state)url.searchParams.set('uf',state);
    if(tableSearch)url.searchParams.set('q',tableSearch);else url.searchParams.delete('q');
    url.searchParams.delete('page');
    location.assign(url.toString());
  });

  document.querySelectorAll('[data-result-days]').forEach(form=>{
    const days=Array.from(form.querySelectorAll('input[name="days[]"]:not(:disabled)'));
    const counter=form.querySelector('[data-result-day-count]');
    const updateCount=()=>{
      const selected=days.filter(day=>day.checked).length;
      if(counter)counter.textContent=selected?(selected===1?'1 dia selecionado':selected+' dias selecionados'):'Mês inteiro selecionado';
    };
    days.forEach(day=>day.addEventListener('change',updateCount));
    updateCount();
  });

  const noticeMeta={
    success:{title:'Operação concluída',icon:'fa-circle-check'},
    danger:{title:'Não foi possível concluir',icon:'fa-circle-exclamation'},
    error:{title:'Não foi possível concluir',icon:'fa-circle-exclamation'},
    warning:{title:'Atenção',icon:'fa-triangle-exclamation'},
    info:{title:'Informação',icon:'fa-circle-info'}
  };
  const enhanceAlert=alert=>{
    if(!alert||alert.dataset.alertEnhanced==='1')return;
    const type=['danger','warning','success','info'].find(name=>alert.classList.contains('alert-'+name))||'info';
    const meta=noticeMeta[type];
    const body=document.createElement('div');body.className='app-alert-body';
    const existingIcon=alert.querySelector(':scope > i');
    Array.from(alert.childNodes).forEach(node=>{if(node!==existingIcon)body.appendChild(node);});
    if(!body.querySelector('strong')){const title=document.createElement('strong');title.textContent=meta.title;body.prepend(title);}
    const icon=document.createElement('span');icon.className='app-alert-icon';icon.innerHTML='<i class="fa-solid '+meta.icon+'"></i>';
    const close=document.createElement('button');close.type='button';close.className='app-alert-close';close.setAttribute('aria-label','Fechar mensagem');close.innerHTML='<i class="fa-solid fa-xmark"></i>';close.addEventListener('click',()=>alert.remove());
    alert.textContent='';alert.append(icon,body,close);alert.classList.add('app-alert');alert.dataset.alertEnhanced='1';alert.setAttribute('role',type==='danger'?'alert':'status');
  };
  document.querySelectorAll('.alert').forEach(enhanceAlert);
  const showNotice=(type,title,message,duration)=>{
    const normalized=type==='error'?'danger':(noticeMeta[type]?type:'info');
    let region=document.querySelector('[data-app-notices]');
    if(!region){region=document.createElement('div');region.className='app-notices';region.dataset.appNotices='';region.setAttribute('aria-live','polite');document.body.appendChild(region);}
    const notice=document.createElement('div');notice.className='alert alert-'+normalized+' app-toast';
    const safeTitle=String(title||noticeMeta[normalized].title);const safeMessage=String(message||'');
    notice.innerHTML='<strong></strong><span></span>';notice.querySelector('strong').textContent=safeTitle;notice.querySelector('span').textContent=safeMessage;
    region.prepend(notice);enhanceAlert(notice);
    requestAnimationFrame(()=>notice.classList.add('show'));
    const timeout=Number(duration)||(normalized==='danger'?10000:6500);
    setTimeout(()=>{notice.classList.remove('show');setTimeout(()=>notice.remove(),220);},timeout);
    return notice;
  };
  window.appNotify=showNotice;
  let lastValidationNotice=0;
  document.addEventListener('invalid',event=>{
    event.preventDefault();
    const field=event.target;
    field.classList?.add('is-invalid');
    const now=Date.now();
    if(now-lastValidationNotice<300)return;
    lastValidationNotice=now;
    const label=String(field.labels?.[0]?.textContent||field.getAttribute?.('aria-label')||field.name||'campo').replace(/\s*\*\s*$/,'').trim();
    const message=field.validity?.valueMissing?'Preencha o campo '+label+'.':field.validity?.typeMismatch?'Informe um valor válido em '+label+'.':field.validationMessage||'Revise o campo '+label+'.';
    showNotice('danger','Dados obrigatórios',message,9000);
    setTimeout(()=>field.focus?.(),0);
  },true);
  document.addEventListener('input',event=>event.target.classList?.remove('is-invalid'));
  document.addEventListener('change',event=>event.target.classList?.remove('is-invalid'));

  const freightDefaultSelect=document.querySelector('[data-freight-default-autosave]');
  if(freightDefaultSelect){
    const status=freightDefaultSelect.parentElement?.querySelector('[data-freight-default-status]');
    let savedValue=freightDefaultSelect.value;
    freightDefaultSelect.addEventListener('change',async()=>{
      const requestedValue=freightDefaultSelect.value;
      freightDefaultSelect.disabled=true;
      if(status){status.className='saving';status.textContent='Salvando...';}
      try{
        const body=new URLSearchParams({_token:window.CSRF||'',freight_mode:requestedValue});
        const response=await fetch((window.APP_URL||'')+'/settings/freight-default',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded;charset=UTF-8','Accept':'application/json'},body});
        const data=await response.json().catch(()=>({}));
        if(!response.ok||!data.ok)throw new Error(data.message||'Não foi possível salvar.');
        savedValue=String(data.mode??requestedValue);
        if(status){status.className='saved';status.textContent='Salvo: '+String(data.label||'frete atualizado');}
        showNotice('success','Frete padrão atualizado',String(data.label||'Configuração salva com sucesso.'));
      }catch(error){
        freightDefaultSelect.value=savedValue;
        const message=error?.message||'Erro ao salvar o frete padrão.';
        if(status){status.className='error';status.textContent=message;}
        showNotice('danger','Erro ao salvar o frete',message);
      }finally{freightDefaultSelect.disabled=false;}
    });
  }

  document.querySelectorAll('.tdcrm-sidebar nav a, .sidebar nav a').forEach(link=>{
    try{
      const current=new URL(location.href).pathname.replace(/\/$/,'');
      const target=new URL(link.href).pathname.replace(/\/$/,'');
      if(target===current||(target!=='/'&&current.startsWith(target+'/')))link.classList.add('active');
    }catch(e){}
  });

  const navGroups=Array.from(document.querySelectorAll('[data-nav-group]'));
  const navGroupKey=group=>'tecnodata-nav-group-'+(group.dataset.navGroup||'menu');
  const activeNavGroup=navGroups.find(group=>group.querySelector('a.active'))||null;
  let preferredNavGroup=activeNavGroup;
  if(!preferredNavGroup){
    preferredNavGroup=navGroups.find(group=>{try{return localStorage.getItem(navGroupKey(group))==='1';}catch(e){return false;}})||navGroups.find(group=>group.dataset.defaultOpen==='1')||null;
  }
  navGroups.forEach(group=>{
    const toggle=group.querySelector('.tdcrm-nav-group-toggle');
    if(!toggle)return;
    const hasActive=!!group.querySelector('a.active');
    group.classList.toggle('has-active',hasActive);
    const setExpanded=expanded=>{
      group.classList.toggle('is-open',expanded);
      toggle.setAttribute('aria-expanded',expanded?'true':'false');
    };
    setExpanded(group===preferredNavGroup);
    try{localStorage.setItem(navGroupKey(group),group===preferredNavGroup?'1':'0');}catch(e){}
    toggle.addEventListener('click',()=>{
      const wasOpen=group.classList.contains('is-open');
      const wasCollapsed=document.body.classList.contains('sidebar-collapsed');
      if(wasCollapsed)document.querySelector('[data-menu]')?.click();
      navGroups.forEach(other=>{
        const expanded=other===group&&(wasCollapsed||!wasOpen);
        other.classList.toggle('is-open',expanded);
        other.querySelector('.tdcrm-nav-group-toggle')?.setAttribute('aria-expanded',expanded?'true':'false');
        try{localStorage.setItem(navGroupKey(other),expanded?'1':'0');}catch(e){}
      });
    });
  });

  if(window.DataTable?.ext)window.DataTable.ext.errMode='none';
  const initDataTable=table=>{
    if(!window.DataTable)return null;
      if(table.dataset.dtReady==='1'||table.hasAttribute('data-no-datatable')||!table.tHead)return;
      if(table.closest('details:not([open])'))return null;
      table.dataset.dtReady='1';
      const orderColumn=Number.parseInt(table.dataset.orderColumn??'',10);
      const orderDirection=table.dataset.orderDirection==='asc'?'asc':'desc';
      const lengthChange=table.dataset.lengthChange!=='0';
      const serverUrl=table.dataset.serverUrl||'';
      const options={
        pageLength:Number.parseInt(table.dataset.pageLength??'5',10)||5,
        lengthChange,
        lengthMenu:[[5,10,25,50],[5,10,25,50]],
        searching:true,
        ordering:true,
        paging:true,
        info:true,
        autoWidth:false,
        order:Number.isInteger(orderColumn)?[[orderColumn,orderDirection]]:[],
        language:{
          search:'',
          searchPlaceholder:'Buscar nesta tabela...',
          lengthMenu:'Mostrar _MENU_ registros',
          info:'Exibindo _START_–_END_ de _TOTAL_',
          infoEmpty:'Nenhum registro',
          infoFiltered:'(filtrado de _MAX_)',
          zeroRecords:'Nenhum registro encontrado',
          emptyTable:'Nenhum registro disponível',
          paginate:{first:'Primeira',last:'Última',next:'›',previous:'‹'}
        }
      };
      if(table.dataset.search)options.search={search:table.dataset.search};
      if(serverUrl){options.processing=true;options.serverSide=true;options.ajax={url:serverUrl,dataSrc:'data',error:()=>showNotice('danger','Erro ao carregar a tabela','Não foi possível consultar os registros. Tente novamente.')};}
      table._dataTable=new DataTable(table,options);
      return table._dataTable;
  };
  document.querySelectorAll('.table-card table').forEach(initDataTable);
  document.querySelectorAll('[data-datatable-container]').forEach(container=>container.addEventListener('toggle',()=>{
    if(!container.open)return;
    container.querySelectorAll('.table-card table').forEach(table=>{
      const instance=initDataTable(table)||table._dataTable;
      setTimeout(()=>{try{instance?.columns?.adjust();}catch(e){}},0);
    });
  }));

  const sidebar=document.querySelector('.tdcrm-sidebar, .sidebar');
  const menuToggle=document.querySelector('[data-menu]');
  const menuBackdrop=document.querySelector('[data-menu-backdrop]');
  const isMobileMenu=()=>window.matchMedia('(max-width:900px)').matches;
  let tableAdjustTimer=0;
  const adjustVisibleTables=()=>{
    window.clearTimeout(tableAdjustTimer);
    requestAnimationFrame(()=>document.querySelectorAll('.table-card table').forEach(table=>{try{table._dataTable?.columns?.adjust();}catch(e){}}));
    tableAdjustTimer=window.setTimeout(()=>document.querySelectorAll('.table-card table').forEach(table=>{try{table._dataTable?.columns?.adjust();}catch(e){}}),230);
  };
  const setSidebarOpen=open=>{
    if(!sidebar||!menuToggle)return;
    if(isMobileMenu()){
      sidebar.classList.toggle('open',open);
      document.body.classList.toggle('menu-open',open);
    }else{
      document.body.classList.toggle('sidebar-collapsed',!open);
      try{localStorage.setItem('tecnodata-sidebar-collapsed',open?'0':'1');}catch(e){}
    }
    menuToggle.setAttribute('aria-expanded',open?'true':'false');
    menuToggle.setAttribute('title',open?'Fechar menu':'Abrir menu');
    adjustVisibleTables();
  };
  if(sidebar&&menuToggle){
    let collapsed=false;
    try{collapsed=localStorage.getItem('tecnodata-sidebar-collapsed')==='1';}catch(e){}
    if(!isMobileMenu())setSidebarOpen(!collapsed);
    else menuToggle.setAttribute('aria-expanded','false');
    menuToggle.addEventListener('click',()=>setSidebarOpen(isMobileMenu()?!sidebar.classList.contains('open'):document.body.classList.contains('sidebar-collapsed')));
    document.querySelector('[data-menu-close]')?.addEventListener('click',()=>setSidebarOpen(false));
    menuBackdrop?.addEventListener('click',()=>setSidebarOpen(false));
    sidebar.querySelectorAll('nav a').forEach(link=>link.addEventListener('click',()=>{if(isMobileMenu())setSidebarOpen(false);}));
    document.addEventListener('keydown',event=>{if(event.key==='Escape'&&isMobileMenu()&&sidebar.classList.contains('open'))setSidebarOpen(false);});
    window.addEventListener('resize',()=>{
      if(isMobileMenu()){
        sidebar.classList.remove('open');document.body.classList.remove('menu-open');menuToggle.setAttribute('aria-expanded','false');
      }else{
        let saved=false;try{saved=localStorage.getItem('tecnodata-sidebar-collapsed')==='1';}catch(e){}
        setSidebarOpen(!saved);
      }
      adjustVisibleTables();
    });
    document.querySelector('.tdcrm-main, .main')?.addEventListener('transitionend',event=>{if(event.propertyName==='margin-left')adjustVisibleTables();});
  }
  const ensureConfirmModal=()=>{
    let modal=document.getElementById('appConfirmModal');
    if(modal)return modal;
    modal=document.createElement('div');
    modal.id='appConfirmModal';
    modal.className='app-confirm-backdrop';
    modal.innerHTML='<div class="app-confirm-card" role="dialog" aria-modal="true" aria-labelledby="appConfirmTitle"><div class="app-confirm-icon"><i class="fa-solid fa-triangle-exclamation"></i></div><div class="app-confirm-copy"><strong id="appConfirmTitle">Confirmar operação</strong><span data-confirm-message></span></div><div class="app-confirm-actions"><button type="button" class="btn btn-outline-secondary" data-confirm-cancel>Cancelar</button><button type="button" class="btn btn-danger" data-confirm-ok>Confirmar</button></div></div>';
    document.body.appendChild(modal);
    return modal;
  };
  const askConfirm=message=>new Promise(resolve=>{
    const modal=ensureConfirmModal();
    const msg=modal.querySelector('[data-confirm-message]');
    const ok=modal.querySelector('[data-confirm-ok]');
    const cancel=modal.querySelector('[data-confirm-cancel]');
    msg.textContent=message||'Confirmar operação?';
    modal.classList.add('show');
    const finish=value=>{
      modal.classList.remove('show');
      ok.removeEventListener('click',onOk);
      cancel.removeEventListener('click',onCancel);
      modal.removeEventListener('click',onBackdrop);
      document.removeEventListener('keydown',onKey);
      resolve(value);
    };
    const onOk=()=>finish(true);
    const onCancel=()=>finish(false);
    const onBackdrop=e=>{if(e.target===modal)finish(false);};
    const onKey=e=>{if(e.key==='Escape')finish(false);};
    ok.addEventListener('click',onOk);
    cancel.addEventListener('click',onCancel);
    modal.addEventListener('click',onBackdrop);
    document.addEventListener('keydown',onKey);
    setTimeout(()=>ok.focus(),0);
  });
  document.addEventListener('click',async e=>{
    const el=e.target.closest?.('[data-confirm]');
    if(!el)return;
    e.preventDefault();
    if(await askConfirm(el.dataset.confirm||'Confirmar operação?')){
      if(el.tagName==='BUTTON'&&el.form)el.form.requestSubmit(el);
      else if(el.tagName==='A'&&el.href)location.href=el.href;
    }
  });
  document.addEventListener('submit',event=>{
    const form=event.target;
    if(!(form instanceof HTMLFormElement))return;
    const button=event.submitter?.matches?.('[data-submit-loading]')?event.submitter:form.querySelector('[data-submit-loading]');
    if(!button)return;
    if(form.dataset.submitting==='1'){event.preventDefault();return;}
    form.dataset.submitting='1';
    button.disabled=true;
    button.setAttribute('aria-busy','true');
    button.classList.add('is-submit-loading');
    button.innerHTML=`<span class="submit-spinner" aria-hidden="true"></span><span>${button.dataset.submitLoading||'Processando...'}</span>`;
  });

  const clientCreateForm=document.getElementById('clientCreateForm');
  if(clientCreateForm){
    clientCreateForm.querySelector('[data-real-client-send]')?.addEventListener('click',async e=>{
      e.preventDefault();if(await askConfirm('Este botão enviará este cliente de verdade para a Omie. Deseja continuar?'))clientCreateForm.requestSubmit(e.currentTarget);
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
    const setStatus=(el,msg,type='')=>{
      if(!el)return;
      el.textContent=msg;el.classList.remove('ok','error','loading');if(type)el.classList.add(type);
      if(type==='ok')showNotice('success','Consulta concluída',msg);
      if(type==='error')showNotice('danger','Verifique os dados',msg);
    };
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


  const syncButtons=document.querySelectorAll('[data-sync-action]');
  if(syncButtons.length){
    const setCardBusy=(card,busy)=>{
      card?.classList.toggle('is-running',busy);
      card?.querySelectorAll('[data-sync-action]').forEach(b=>{
        if(busy){b.dataset.wasDisabled=b.disabled?'1':'0';b.disabled=true;}
        else if(b.dataset.wasDisabled!=='1')b.disabled=false;
      });
    };
    const setSyncAlert=(card,type,title,message)=>{
      const alert=card?.querySelector('[data-sync-alert]');
      if(!alert)return;
      alert.className='sync-flow-alert sync-flow-alert-'+type;
      const icon=alert.querySelector('i');
      if(icon)icon.className='fa-solid '+(type==='error'?'fa-circle-exclamation':type==='success'?'fa-circle-check':'fa-circle-info');
      const t=alert.querySelector('[data-sync-alert-title]');
      const m=alert.querySelector('[data-sync-alert-message]');
      if(t)t.textContent=title;
      if(m)m.textContent=message;
      const badge=card.querySelector('[data-sync-badge]');
      if(badge){
        badge.className='sync-status sync-status-'+(type==='error'?'error':type==='success'?'success':'idle');
        badge.textContent=type==='error'?'Erro':type==='success'?'Sincronizado':'Processando';
      }
      showNotice(type,title,message,type==='info'?4500:0);
    };
    const updateProgress=(card,data,label)=>{
      const wrap=card?.querySelector('[data-sync-progress-wrap]');
      if(!wrap)return;
      wrap.hidden=false;
      const total=Math.max(0,Number(data.total_pages||0));
      const page=Math.max(0,Number(data.page||0));
      const percent=data.done?100:(total>0?Math.min(99,Math.round((page/total)*100)):Math.min(95,page*8));
      const txt=card.querySelector('[data-sync-progress-label]');
      const pct=card.querySelector('[data-sync-progress-percent]');
      const bar=card.querySelector('[data-sync-progress-bar]');
      if(txt)txt.textContent=data.done?'Concluído':(total>0?label+' • página '+page+' de '+total:label+' • página '+page);
      if(pct)pct.textContent=percent+'%';
      if(bar)bar.style.width=percent+'%';
    };
    syncButtons.forEach(btn=>btn.addEventListener('click',async e=>{
      e.preventDefault();
      const module=btn.dataset.module;
      const action=btn.dataset.syncAction||'sync';
      if(!module)return;
      if(btn.dataset.syncConfirm&&!(await askConfirm(btn.dataset.syncConfirm)))return;
      const card=btn.closest('[data-sync-card]');
      const labels={sync:'Sincronizando',period:'Sincronizando período',catchup:'Atualizando lacuna',last5:'Buscando últimos 5 dias',resume:'Retomando',reset:'Zerando estado',full:'Executando carga completa'};
      let page=action==='resume'?0:1;
      let iterations=0;
      setCardBusy(card,true);
      setSyncAlert(card,'info',labels[action]||'Processando','A operação foi iniciada. Aguarde o processamento deste módulo.');
      try{
        while(true){
          iterations++;
          if(iterations>500)throw new Error('A sincronização ultrapassou o limite seguro de 500 páginas e foi interrompida.');
          const body=new URLSearchParams({_token:window.CSRF,module,action,page:String(page)});
          if(action==='period'){
            const dateFrom=card?.querySelector('[data-sync-date-from]')?.value||'';
            const dateTo=card?.querySelector('[data-sync-date-to]')?.value||'';
            if(!dateFrom||!dateTo)throw new Error('Informe a data inicial e a data final.');
            body.set('date_from',dateFrom);body.set('date_to',dateTo);
          }
          const controller=new AbortController();
          const requestTimeout=setTimeout(()=>controller.abort(),180000);
          let response;
          try{response=await fetch(window.APP_URL+'/api/sync',{
            method:'POST',
            headers:{'Content-Type':'application/x-www-form-urlencoded'},
            body,
            signal:controller.signal
          });}
          catch(error){if(error?.name==='AbortError')throw new Error('O lote excedeu 180 segundos. Aguarde dois minutos antes de usar Retomar, pois o servidor pode estar concluindo as gravações.');throw error;}
          finally{clearTimeout(requestTimeout);}
          const data=await response.json().catch(()=>({ok:false,error:'Resposta inválida do servidor.'}));
          if(!response.ok||!data.ok)throw new Error(data.error||'Falha na sincronização.');
          if(action==='reset'){
            setSyncAlert(card,'success','Módulo zerado',data.message||'Os dados locais do módulo foram excluídos e nenhuma nova sincronização foi iniciada.');
            setCardBusy(card,false);
            setTimeout(()=>location.reload(),650);
            return;
          }
          updateProgress(card,data,labels[action]||'Processando');
          const responsePage=Math.max(0,Number(data.page||page));
          const responseTotal=Math.max(0,Number(data.total_pages||0));
          const completed=data.done===true||data.done===1||data.done==='1'||(responseTotal>0&&responsePage>=responseTotal);
          if(completed){
            const count=Number(data.count||0);
            setSyncAlert(card,'success','Sincronização concluída',count+' registro(s) processado(s) no último lote. O estado do módulo foi atualizado.');
            setCardBusy(card,false);
            setTimeout(()=>location.reload(),850);
            return;
          }
          const nextPage=responsePage+1;
          if(nextPage<=page)throw new Error('A Omie repetiu a mesma página. A sincronização foi interrompida com segurança.');
          page=nextPage;
        }
      }catch(error){
        setSyncAlert(card,'error','Falha na sincronização',error.message||'O fluxo foi interrompido. Use Retomar após corrigir a causa.');
        const wrap=card?.querySelector('[data-sync-progress-wrap]');
        if(wrap)wrap.hidden=true;
        setCardBusy(card,false);
      }
    }));
  }

  const recoveryForm=document.querySelector('[data-collection-recovery-form]');
  if(recoveryForm){
    const recoverySearch=recoveryForm.querySelector('[data-recovery-client-search]');
    const recoveryResults=recoveryForm.querySelector('[data-recovery-client-results]');
    const recoverySelected=recoveryForm.querySelector('[data-recovery-client-selected]');
    const recoveryClientId=recoveryForm.querySelector('[data-recovery-client-id]');
    const recoveryAssigned=recoveryForm.querySelector('[name="assigned_user_id"]');
    const recoveryEsc=value=>{const node=document.createElement('div');node.textContent=String(value??'');return node.innerHTML;};
    let recoveryTimer;
    const selectRecoveryClient=client=>{
      recoveryClientId.value=String(client.id||'');
      if(client.collection_assigned_user_id&&!recoveryAssigned.value)recoveryAssigned.value=String(client.collection_assigned_user_id);
      recoverySelected.innerHTML='<span><strong>'+recoveryEsc(client.name)+'</strong><small>'+recoveryEsc([client.client_integration_code||client.omie_code,client.document,client.city,client.uf].filter(Boolean).join(' • '))+'</small></span><button type="button" title="Trocar cliente"><i class="fa-solid fa-rotate"></i></button>';
      recoveryResults.innerHTML='';recoverySearch.value='';
      recoverySelected.querySelector('button')?.addEventListener('click',()=>{recoveryClientId.value='';recoverySelected.innerHTML='';recoverySearch.focus();});
    };
    const findRecoveryClients=async()=>{
      const query=recoverySearch.value.trim();if(query.length<2){recoveryResults.innerHTML='';return;}
      try{
        const response=await fetch(window.APP_URL+'/api/clients?q='+encodeURIComponent(query),{credentials:'same-origin'});
        const data=await response.json().catch(()=>({error:'Resposta inválida do servidor.'}));
        if(!response.ok)throw new Error(data.error||'Não foi possível buscar clientes.');
        recoveryResults.innerHTML=(data.items||[]).map(client=>'<button type="button" class="search-result" data-recovery-client="'+encodeURIComponent(JSON.stringify(client))+'"><span><strong>'+recoveryEsc(client.name)+'</strong><small>'+recoveryEsc([client.client_integration_code||client.omie_code,client.document].filter(Boolean).join(' • '))+'</small></span><i class="fa-solid fa-plus"></i></button>').join('')||'<div class="search-result-empty">Nenhum cliente encontrado.</div>';
        recoveryResults.querySelectorAll('[data-recovery-client]').forEach(button=>button.addEventListener('click',()=>selectRecoveryClient(JSON.parse(decodeURIComponent(button.dataset.recoveryClient)))));
      }catch(error){recoveryResults.innerHTML='';showNotice('danger','Erro ao buscar clientes',error.message);}
    };
    recoverySearch.addEventListener('input',()=>{clearTimeout(recoveryTimer);recoveryTimer=setTimeout(findRecoveryClients,250);});
    recoveryForm.addEventListener('submit',event=>{if(!recoveryClientId.value){event.preventDefault();showNotice('warning','Selecione o cliente','Busque pelo código, nome ou CPF/CNPJ e escolha um cliente da lista.');recoverySearch.focus();}});
    if(window.COLLECTION_RECOVERY_OLD_CLIENT){
      fetch(window.APP_URL+'/api/clients?q='+encodeURIComponent(String(window.COLLECTION_RECOVERY_OLD_CLIENT)),{credentials:'same-origin'}).then(response=>response.json()).then(data=>{const client=(data.items||[]).find(item=>Number(item.id)===Number(window.COLLECTION_RECOVERY_OLD_CLIENT));if(client)selectRecoveryClient(client);}).catch(()=>{});
    }
  }

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
  const itemsJson=document.getElementById('itemsJson'),itemsEl=document.getElementById('orderItems');
  const selectAllOrderItems=document.getElementById('selectAllOrderItems'),selectedOrderItemsCount=document.getElementById('selectedOrderItemsCount'),orderItemsBulk=document.getElementById('orderItemsBulk');
  const grandTotal=document.getElementById('grandTotal'),discountTotal=document.getElementById('discountTotal'),orderGrandTotal=document.getElementById('orderGrandTotal'),financialTotal=document.getElementById('financialTotal'),fiscalTotal=document.getElementById('fiscalTotal'),clientEmailPreview=document.getElementById('clientEmailPreview');
  const departmentsJson=document.getElementById('departmentsJson'),departmentDistribution=document.getElementById('departmentDistribution'),departmentTotal=document.getElementById('departmentTotal'),addDepartment=document.getElementById('addDepartment');
  const departmentCatalog=Array.isArray(window.ORDER_DEPARTMENTS)?window.ORDER_DEPARTMENTS:[];
  const paymentTerm=document.getElementById('orderPaymentTerm'),forecastDate=document.getElementById('orderForecastDate'),installmentRows=document.getElementById('installmentRows'),rebuildInstallments=document.getElementById('rebuildInstallments'),installmentPaymentMethod=document.getElementById('installmentPaymentMethod');
  const installmentsJson=document.getElementById('installmentsJson'),customInstallments=document.getElementById('customInstallments'),installmentTotal=document.getElementById('installmentTotal'),installmentPercent=document.getElementById('installmentPercent'),installmentBalance=document.getElementById('installmentBalance'),installmentMode=document.getElementById('installmentMode');
  const netWeightInput=document.getElementById('orderNetWeight'),grossWeightInput=document.getElementById('orderGrossWeight');

  let items=Array.isArray(window.ORDER_OLD_ITEMS)?window.ORDER_OLD_ITEMS:[];
  let newItemRowOpen=items.length===0;
  const selectedOrderItems=new Set();let itemUidSequence=0;
  let installmentData=[];let customInstallmentMode=customInstallments?.value==='S';let installmentTargetCents=0;
  try{const parsed=JSON.parse(installmentsJson?.value||'[]');if(Array.isArray(parsed))installmentData=parsed;}catch(e){}
  if(!installmentData.length)customInstallmentMode=false;
  let clientTimer,productTimer;

  let departmentRows=[];
  try{
    const parsed=JSON.parse(departmentsJson?.value||'[]');
    if(Array.isArray(parsed))departmentRows=parsed.filter(r=>r&&r.code);
  }catch(e){}
  if(!departmentRows.length&&departmentCatalog.length){
    departmentRows=[{code:String(departmentCatalog[0].code),percent:100}];
  }

  function syncDepartments(){
    if(departmentsJson)departmentsJson.value=JSON.stringify(departmentRows.map(r=>({code:String(r.code||''),percent:Number(r.percent||0)})));
    if(!departmentDistribution)return;
    const availableOptions=current=>{
      let html='<option value="">Selecione</option>';
      departmentCatalog.forEach(d=>{
        const code=String(d.code||'');
        html+='<option value="'+esc(code)+'" '+(String(current||'')===code?'selected':'')+'>'+esc(d.description||code)+'</option>';
      });
      return html;
    };
    departmentDistribution.innerHTML=departmentRows.map((row,index)=>
      '<div class="department-row">'+
       '<div><label>Departamento</label><select class="form-select" data-department-code="'+index+'">'+availableOptions(row.code)+'</select></div>'+
       '<div><label>Percentual</label><div class="department-percent-field"><input class="form-control" type="number" min="0.01" max="100" step="0.01" value="'+esc(Number(row.percent||0))+'" data-department-percent="'+index+'"><span>%</span></div></div>'+
       '<button type="button" class="btn btn-light department-remove" data-department-remove="'+index+'" title="Remover"><i class="fa-regular fa-trash-can"></i></button>'+
      '</div>'
    ).join('');
    departmentDistribution.querySelectorAll('[data-department-code]').forEach(el=>el.addEventListener('change',()=>{
      departmentRows[Number(el.dataset.departmentCode)].code=el.value;syncDepartments();
    }));
    departmentDistribution.querySelectorAll('[data-department-percent]').forEach(el=>el.addEventListener('input',()=>{
      departmentRows[Number(el.dataset.departmentPercent)].percent=Number(el.value||0);
      updateDepartmentTotal();
      if(departmentsJson)departmentsJson.value=JSON.stringify(departmentRows.map(r=>({code:String(r.code||''),percent:Number(r.percent||0)})));
    }));
    departmentDistribution.querySelectorAll('[data-department-remove]').forEach(el=>el.addEventListener('click',()=>{
      departmentRows.splice(Number(el.dataset.departmentRemove),1);
      if(!departmentRows.length&&departmentCatalog.length)departmentRows=[{code:String(departmentCatalog[0].code),percent:100}];
      syncDepartments();
    }));
    updateDepartmentTotal();
  }
  function updateDepartmentTotal(){
    const total=departmentRows.reduce((sum,row)=>sum+Number(row.percent||0),0);
    if(departmentTotal){
      departmentTotal.textContent=total.toLocaleString('pt-BR',{minimumFractionDigits:2,maximumFractionDigits:2})+'%';
      departmentTotal.classList.toggle('is-valid',Math.abs(total-100)<=0.01);
      departmentTotal.classList.toggle('is-invalid',Math.abs(total-100)>0.01);
    }
  }
  addDepartment?.addEventListener('click',()=>{
    const used=new Set(departmentRows.map(r=>String(r.code)));
    const next=departmentCatalog.find(d=>!used.has(String(d.code)))||departmentCatalog[0];
    if(!next)return;
    departmentRows.push({code:String(next.code),percent:0});
    syncDepartments();
  });
  syncDepartments();

  function currentProfile(){
    const opt=profileSelect?.selectedOptions?.[0];
    return {
      no_stock:opt?.dataset.noStock==='S',
      no_finance:opt?.dataset.noFinance==='S',
      no_total:opt?.dataset.noTotal==='S',
      reserve_stock:opt?.dataset.reserve==='S'
    };
  }
  function newItemUiId(){itemUidSequence++;return 'item-'+Date.now().toString(36)+'-'+itemUidSequence.toString(36)+'-'+Math.random().toString(36).slice(2,7);}
  function normalizeItem(item){
    const p=currentProfile();
    return {
      _ui_id:item._ui_id||newItemUiId(),
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
      ncm:item.ncm||'',
      unit_net_weight:Number(item.unit_net_weight??item.net_weight??0),
      unit_gross_weight:Number(item.unit_gross_weight??item.gross_weight??0)
    };
  }
  items=items.map(normalizeItem);

  function updateItemsBulkBar(){
    const available=new Set(items.map(item=>item._ui_id));
    [...selectedOrderItems].forEach(id=>{if(!available.has(id))selectedOrderItems.delete(id);});
    const count=selectedOrderItems.size;
    if(selectedOrderItemsCount)selectedOrderItemsCount.textContent=count+' '+(count===1?'selecionado':'selecionados');
    if(orderItemsBulk)orderItemsBulk.hidden=count===0;
    if(selectAllOrderItems){selectAllOrderItems.checked=items.length>0&&count===items.length;selectAllOrderItems.indeterminate=count>0&&count<items.length;selectAllOrderItems.disabled=!items.length;}
    orderItemsBulk?.querySelectorAll('[data-bulk-field]').forEach(button=>button.disabled=count===0);
  }
  selectAllOrderItems?.addEventListener('change',()=>{selectedOrderItems.clear();if(selectAllOrderItems.checked)items.forEach(item=>selectedOrderItems.add(item._ui_id));render();});
  orderItemsBulk?.querySelectorAll('[data-bulk-field]').forEach(button=>button.addEventListener('click',()=>{
    const field=button.dataset.bulkField,value=button.dataset.bulkValue==='1';
    items.forEach(item=>{if(selectedOrderItems.has(item._ui_id))item[field]=value;});
    render();
  }));

  async function api(path){
    const response=await fetch(window.APP_URL+path,{credentials:'same-origin'});
    const data=await response.json().catch(()=>({error:'O servidor retornou uma resposta inválida.'}));
    if(!response.ok)throw new Error(data.error||'Não foi possível concluir a consulta.');
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
    try{
      const data=await api('/api/clients?q='+encodeURIComponent(q));
      clientResults.innerHTML=data.items.map(c=>'<button type="button" class="search-result" data-client="'+encodeURIComponent(JSON.stringify(c))+'"><span><strong>'+esc(c.name)+'</strong><small>'+esc(c.document||'')+'</small></span><i class="fa-solid fa-plus"></i></button>').join('');
      clientResults.querySelectorAll('[data-client]').forEach(btn=>btn.onclick=()=>chooseClient(JSON.parse(decodeURIComponent(btn.dataset.client))));
    }catch(error){clientResults.innerHTML='';showNotice('danger','Erro ao buscar clientes',error.message);}
  }
  clientSearch?.addEventListener('input',()=>{clearTimeout(clientTimer);clientTimer=setTimeout(findClients,250);});

  function addProduct(product){
    const profile=currentProfile();
    items.push(normalizeItem({
      product_id:Number(product.id),description:product.description,sku:product.sku||product.omie_code,unit:product.unit||'UN',
      quantity:1,unit_price:Number(product.unit_price||0),discount_type:'V',discount_value:0,
      no_stock:profile.no_stock,no_finance:profile.no_finance,no_total:profile.no_total,reserve_stock:profile.reserve_stock,
      unit_net_weight:Number(product.net_weight||0),unit_gross_weight:Number(product.gross_weight||0)
    }));
    newItemRowOpen=false;render();
  }
  async function findProducts(input,results){
    const q=input.value.trim();if(q.length<2){results.innerHTML='';return [];}
    try{
      const data=await api('/api/products?q='+encodeURIComponent(q));
      results.innerHTML=data.items.map(p=>'<button type="button" class="search-result" data-product="'+encodeURIComponent(JSON.stringify(p))+'"><span><strong>'+esc(p.description)+'</strong><small>'+esc(p.sku||p.omie_code)+' • '+money(p.unit_price)+(p.stock_qty!==null?' • estoque '+Number(p.stock_qty).toLocaleString('pt-BR'):'')+'</small></span><i class="fa-solid fa-arrow-turn-down"></i></button>').join('');
      results.querySelectorAll('[data-product]').forEach(btn=>btn.onclick=()=>addProduct(JSON.parse(decodeURIComponent(btn.dataset.product))));
      return data.items;
    }catch(error){results.innerHTML='';showNotice('danger','Erro ao buscar produtos',error.message);return [];}
  }

  profileSelect?.addEventListener('change',async()=>{
    if(!items.length)return;
    if(!(await askConfirm('Aplicar as regras deste tipo aos itens que já estão no pedido?')))return;
    const p=currentProfile();
    items.forEach(i=>{i.no_stock=p.no_stock;i.no_finance=p.no_finance;i.no_total=p.no_total;i.reserve_stock=p.reserve_stock;});
    render();
  });

  function lineGross(item){return Number(item.quantity||0)*Number(item.unit_price||0);}
  function lineDiscount(item){const gross=lineGross(item),v=Math.max(0,Number(item.discount_value||0));return item.discount_type==='P'?gross*Math.min(100,v)/100:v;}
  function lineNet(item){return Math.max(0,lineGross(item)-lineDiscount(item));}
  function installmentDays(){
    const option=paymentTerm?.selectedOptions?.[0];
    if(!option||!option.value)return [];
    const count=Math.max(0,Number(option.dataset.installments||0));
    let days=String(option.dataset.days||'').match(/\d+/g)?.map(Number)||[];
    if(!days.length&&count)days=Array.from({length:count},(_,index)=>index*28);
    if(count&&days.length>count)days=days.slice(0,count);
    while(count&&days.length<count)days.push((days[days.length-1]||0)+28);
    return days;
  }
  function installmentDateValue(baseValue,days){
    if(!baseValue)return '';
    const date=new Date(baseValue+'T12:00:00');
    if(Number.isNaN(date.getTime()))return '';
    date.setDate(date.getDate()+Number(days||0));
    return date.getFullYear()+'-'+String(date.getMonth()+1).padStart(2,'0')+'-'+String(date.getDate()).padStart(2,'0');
  }
  function installmentSelect(current){
    const methods=Array.isArray(meta.paymentMethods)?meta.paymentMethods:[];
    let html='<select class="installment-method" aria-label="Meio de pagamento"><option value="">&lt;não informado&gt;</option>';
    methods.forEach(method=>{const value=String(method.code??'');html+='<option value="'+esc(value)+'" '+(value===String(current||'')?'selected':'')+'>'+esc(method.description||value)+'</option>';});
    return html+'</select>';
  }
  function automaticInstallments(totalCents){
    const days=installmentDays(),count=days.length;
    if(!count||totalCents<=0)return [];
    const base=Math.round(totalCents/count),method=installmentPaymentMethod?.value||'';
    return days.map((day,index)=>({
      value:(index===count-1?totalCents-base*(count-1):base)/100,
      due_date:installmentDateValue(forecastDate?.value,day),payment_method:method,generate_boleto:true
    }));
  }
  function syncInstallments(){
    if(customInstallments)customInstallments.value=customInstallmentMode?'S':'N';
    if(installmentsJson)installmentsJson.value=JSON.stringify(customInstallmentMode?installmentData:[]);
  }
  function markInstallmentsCustom(){customInstallmentMode=true;syncInstallments();}
  function redistributeInstallmentsAfter(index){
    const remainingRows=installmentData.length-index-1;if(remainingRows<=0)return;
    const used=installmentData.slice(0,index+1).reduce((sum,row)=>sum+Math.round(Number(row.value||0)*100),0),remaining=installmentTargetCents-used;
    if(remaining<remainingRows)return;
    const base=Math.round(remaining/remainingRows);
    for(let position=index+1;position<installmentData.length;position++)installmentData[position].value=(position===installmentData.length-1?remaining-base*(remainingRows-1):base)/100;
  }
  function updateInstallmentSummary(){
    const sum=installmentData.reduce((total,row)=>total+Math.round(Number(row.value||0)*100),0),difference=installmentTargetCents-sum;
    const percent=installmentTargetCents>0?sum/installmentTargetCents*100:0;
    if(installmentTotal)installmentTotal.textContent=money(sum/100);
    if(installmentPercent)installmentPercent.textContent=percent.toLocaleString('pt-BR',{minimumFractionDigits:2,maximumFractionDigits:2})+'%';
    if(installmentMode)installmentMode.textContent=customInstallmentMode?'Personalizado • condição Omie 999':'Automático pela condição';
    if(installmentBalance){installmentBalance.textContent=difference===0?'Valores conferidos':'Diferença: '+money(difference/100);installmentBalance.className=difference===0?'is-valid':'is-invalid';}
  }
  function renderInstallments(total,reset=false){
    if(!installmentRows)return;
    installmentTargetCents=Math.max(0,Math.round(Number(total||0)*100));
    if(reset)customInstallmentMode=false;
    if(!customInstallmentMode)installmentData=automaticInstallments(installmentTargetCents);
    if(!installmentData.length||installmentTargetCents<=0){
      installmentRows.innerHTML='<tr><td colspan="8" class="installments-empty">Selecione a condição de pagamento e inclua os itens do pedido.</td></tr>';syncInstallments();updateInstallmentSummary();return;
    }
    const count=installmentData.length,documentCode=String(meta.defaults?.document_type||'');
    const documentRow=(Array.isArray(meta.documentTypes)?meta.documentTypes:[]).find(row=>String(row.code??'')===documentCode);
    const documentLabel=documentRow?.description||'<não informado>';
    installmentRows.innerHTML=installmentData.map((row,index)=>{
      const cents=Math.round(Number(row.value||0)*100),percent=installmentTargetCents?cents/installmentTargetCents*100:0;
      const status=index===0&&!row.generate_boleto&&customInstallmentMode?'Entrada':'Previsão';
      return '<tr class="'+(index===0?'active':'')+'" data-installment="'+index+'"><td><span class="installment-status">'+status+'</span></td><td><strong>'+String(index+1).padStart(3,'0')+'/'+String(count).padStart(3,'0')+'</strong></td><td><input class="installment-date" type="date" value="'+esc(row.due_date||'')+'"></td><td class="numeric"><input class="installment-value" type="number" min="0.01" step="0.01" value="'+(cents/100).toFixed(2)+'"></td><td class="numeric"><span class="installment-row-percent">'+percent.toLocaleString('pt-BR',{minimumFractionDigits:2,maximumFractionDigits:2})+'%</span></td><td><em>'+esc(documentLabel)+'</em></td><td>'+installmentSelect(row.payment_method||'')+'</td><td class="center"><label class="installment-boleto-switch"><input type="checkbox" '+(row.generate_boleto?'checked':'')+'><span>'+(row.generate_boleto?'Sim':'Não')+'</span></label></td></tr>';
    }).join('');
    installmentRows.querySelectorAll('[data-installment]').forEach(tableRow=>{
      const index=Number(tableRow.dataset.installment),row=installmentData[index];
      tableRow.querySelector('.installment-value')?.addEventListener('change',event=>{row.value=Math.max(0,Number(event.target.value||0));redistributeInstallmentsAfter(index);markInstallmentsCustom();renderInstallments(installmentTargetCents/100);});
      tableRow.querySelector('.installment-date')?.addEventListener('change',event=>{row.due_date=event.target.value;markInstallmentsCustom();updateInstallmentSummary();});
      tableRow.querySelector('.installment-method')?.addEventListener('change',event=>{row.payment_method=event.target.value;markInstallmentsCustom();updateInstallmentSummary();});
      tableRow.querySelector('.installment-boleto-switch input')?.addEventListener('change',event=>{row.generate_boleto=event.target.checked;markInstallmentsCustom();renderInstallments(installmentTargetCents/100);});
    });
    syncInstallments();updateInstallmentSummary();
  }
  function bindInput(row,selector,event,handler){
    const el=row.querySelector(selector);if(el)el.addEventListener(event,e=>handler(e.target));
  }

  function render(){
    itemsJson.value=JSON.stringify(items);
    const columns='<div class="order-items-columns"><span></span><span>Produto</span><span>Quantidade</span><span>Preço unitário</span><span>Desconto</span><span>Estoque</span><span>Financeiro</span><span>Total</span><span>Ações</span></div>';
    const entryRow=newItemRowOpen?'<article class="order-item-table-row order-item-entry-row"><span class="order-item-entry-marker"><i class="fa-solid fa-plus"></i></span><div class="order-item-product-entry"><input class="form-control inline-product-input" placeholder="Pesquisar produto, SKU ou código" autocomplete="off"><small>Enter ou Tab para selecionar</small></div><span class="entry-placeholder">—</span><span class="entry-placeholder">—</span><span class="entry-placeholder">—</span><span class="entry-placeholder">—</span><span class="entry-placeholder">—</span><span class="entry-placeholder">—</span><button type="button" class="cancel-entry-row" title="Cancelar nova linha" '+(!items.length?'disabled':'')+'><i class="fa-solid fa-xmark"></i></button><div class="inline-product-results"></div></article>':'';
    if(!items.length){
      itemsEl.innerHTML='<div class="order-items-table">'+columns+entryRow+'</div>';
    }else{
      itemsEl.innerHTML='<div class="order-items-table">'+columns+items.map((item,index)=>{
        const net=lineNet(item);
        return '<article class="order-item-table-row '+(selectedOrderItems.has(item._ui_id)?'selected':'')+'" data-index="'+index+'">'+
          '<label class="order-item-selector" title="Selecionar item"><input type="checkbox" class="item-selector" '+(selectedOrderItems.has(item._ui_id)?'checked':'')+'><span></span></label>'+
          '<div class="order-item-product"><b>'+(index+1)+'</b><span><strong>'+esc(item.description)+'</strong><small>'+esc(item.sku)+' • '+esc(item.unit)+(item.no_total?' • fora do total fiscal':'')+'</small></span></div>'+
          '<div class="order-item-cell"><label>Quantidade</label><input class="form-control qty" type="number" min=".0001" step=".0001" value="'+item.quantity+'"></div>'+
          '<div class="order-item-cell"><label>Preço unitário</label><input class="form-control price" type="number" min="0" step=".01" value="'+item.unit_price.toFixed(2)+'"></div>'+
          '<div class="order-item-cell"><label>Desconto</label><div class="discount-field"><select class="form-select discount-type"><option value="V" '+(item.discount_type==='V'?'selected':'')+'>R$</option><option value="P" '+(item.discount_type==='P'?'selected':'')+'>%</option></select><input class="form-control discount-value" type="number" min="0" step=".01" value="'+item.discount_value+'"></div></div>'+
          '<button type="button" class="item-rule-toggle stock '+(item.no_stock?'off':'on')+'" title="Alterar movimentação de estoque"><i class="fa-solid '+(item.no_stock?'fa-box-open':'fa-box')+'"></i><span>'+(item.no_stock?'Não movimenta':'Movimenta')+'</span></button>'+
          '<button type="button" class="item-rule-toggle finance '+(item.no_finance?'off':'on')+'" title="Alterar geração financeira"><i class="fa-solid '+(item.no_finance?'fa-ban':'fa-sack-dollar')+'"></i><span>'+(item.no_finance?'Não gera':'Gera')+'</span></button>'+
          '<div class="order-item-row-total"><small>Total</small><strong>'+money(net)+'</strong></div>'+
          '<div class="item-buttons"><button type="button" class="btn btn-light add-order-item" title="Incluir novo item" aria-label="Incluir novo item"><i class="fa-solid fa-plus"></i></button><button type="button" class="btn btn-light duplicate" title="Duplicar"><i class="fa-regular fa-copy"></i></button><button type="button" class="btn btn-light remove" title="Remover"><i class="fa-solid fa-trash"></i></button></div>'+
          '<details class="item-rules"><summary><span><i class="fa-solid fa-sliders"></i>Configurações avançadas</span><small>Fiscal, estoque e identificação do item</small></summary>'+
           '<div class="item-rule-switches">'+
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
      }).join('')+entryRow+'</div>';
    }

    itemsEl.querySelectorAll('.add-order-item').forEach(button=>button.addEventListener('click',()=>{if(!newItemRowOpen){newItemRowOpen=true;render();}setTimeout(()=>itemsEl.querySelector('.inline-product-input')?.focus(),0);}));
    const entry=itemsEl.querySelector('.order-item-entry-row');
    if(entry){
      const input=entry.querySelector('.inline-product-input'),results=entry.querySelector('.inline-product-results');
      input?.addEventListener('input',()=>{clearTimeout(productTimer);productTimer=setTimeout(()=>findProducts(input,results),220);});
      input?.addEventListener('keydown',async event=>{
        if(event.key==='Escape'&&items.length){newItemRowOpen=false;render();return;}
        if(!['Enter','Tab'].includes(event.key)||input.value.trim().length<2)return;
        event.preventDefault();
        const first=results.querySelector('[data-product]');
        if(first){first.click();return;}
        const products=await findProducts(input,results);if(products[0])addProduct(products[0]);
      });
      entry.querySelector('.cancel-entry-row')?.addEventListener('click',()=>{if(!items.length)return;newItemRowOpen=false;render();});
    }
    itemsEl.querySelectorAll('.order-item-table-row').forEach(row=>{
      const index=Number(row.dataset.index),item=items[index];
      bindInput(row,'.item-selector','change',el=>{if(el.checked)selectedOrderItems.add(item._ui_id);else selectedOrderItems.delete(item._ui_id);updateItemsBulkBar();row.classList.toggle('selected',el.checked);});
      bindInput(row,'.qty','change',el=>{item.quantity=Number(el.value||0);render();});
      bindInput(row,'.price','change',el=>{item.unit_price=Number(el.value||0);render();});
      bindInput(row,'.discount-type','change',el=>{item.discount_type=el.value;render();});
      bindInput(row,'.discount-value','change',el=>{item.discount_value=Number(el.value||0);render();});
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
      row.querySelector('.stock')?.addEventListener('click',()=>{item.no_stock=!item.no_stock;render();});
      row.querySelector('.finance')?.addEventListener('click',()=>{item.no_finance=!item.no_finance;render();});
      row.querySelector('.remove')?.addEventListener('click',()=>{selectedOrderItems.delete(item._ui_id);items.splice(index,1);if(!items.length)newItemRowOpen=true;render();});
      row.querySelector('.duplicate')?.addEventListener('click',()=>{const copy=JSON.parse(JSON.stringify(item));copy._ui_id=newItemUiId();items.splice(index+1,0,copy);render();});
    });
    updateItemsBulkBar();

    const gross=items.reduce((sum,item)=>sum+lineGross(item),0);
    const discount=items.reduce((sum,item)=>sum+lineDiscount(item),0);
    const commercial=items.reduce((sum,item)=>sum+lineNet(item),0);
    const financial=items.reduce((sum,item)=>sum+(item.no_finance?0:lineNet(item)),0);
    const fiscal=items.reduce((sum,item)=>sum+(item.no_total?0:lineNet(item)),0);
    const netWeight=items.reduce((sum,item)=>sum+Number(item.quantity||0)*Number(item.unit_net_weight||0),0);
    const grossWeight=items.reduce((sum,item)=>sum+Number(item.quantity||0)*Number(item.unit_gross_weight||0),0);
    grandTotal.textContent=money(gross);
    if(discountTotal)discountTotal.textContent=money(discount);
    if(orderGrandTotal)orderGrandTotal.textContent=money(commercial);
    financialTotal.textContent=money(financial);
    fiscalTotal.textContent=money(fiscal);
    if(netWeightInput&&netWeightInput.dataset.manual!=='1')netWeightInput.value=netWeight>0?netWeight.toFixed(3).replace('.',','):'';
    if(grossWeightInput&&grossWeightInput.dataset.manual!=='1')grossWeightInput.value=grossWeight>0?grossWeight.toFixed(3).replace('.',','):'';
    itemsJson.value=JSON.stringify(items);
    renderInstallments(financial);
  }

  [netWeightInput,grossWeightInput].forEach(input=>input?.addEventListener('change',()=>{input.dataset.manual=input.value.trim()===''?'0':'1';render();}));
  paymentTerm?.addEventListener('change',()=>{customInstallmentMode=false;installmentData=[];render();});
  forecastDate?.addEventListener('change',render);
  rebuildInstallments?.addEventListener('click',()=>{customInstallmentMode=false;installmentData=[];render();});
  render();
  if(window.ORDER_PREFILL_CLIENT){
    api('/api/clients?q='+encodeURIComponent(String(window.ORDER_PREFILL_CLIENT))).then(data=>{
      const client=data.items.find(x=>Number(x.id)===Number(window.ORDER_PREFILL_CLIENT));if(client)chooseClient(client);
    }).catch(error=>showNotice('warning','Cliente não carregado',error.message||'Selecione o cliente novamente.'));
  }
});


/* Painel de notificações do topbar */
(()=>{
 const toggle=document.querySelector('[data-notification-toggle]');
 const panel=document.querySelector('[data-notification-panel]');
 if(!toggle||!panel)return;
 const close=()=>{panel.hidden=true;toggle.setAttribute('aria-expanded','false');};
 toggle.addEventListener('click',(event)=>{
  event.stopPropagation();
  const willOpen=panel.hidden;
  panel.hidden=!willOpen;
  toggle.setAttribute('aria-expanded',willOpen?'true':'false');
 });
 panel.addEventListener('click',event=>event.stopPropagation());
 document.addEventListener('click',close);
 document.addEventListener('keydown',event=>{if(event.key==='Escape')close();});
})();


/* Login: mostrar/ocultar senha */
(()=>{
 const button=document.querySelector('[data-password-toggle]');
 if(!button)return;
 const input=button.closest('.tdlogin-input')?.querySelector('input');
 if(!input)return;
 button.addEventListener('click',()=>{
  const show=input.type==='password';
  input.type=show?'text':'password';
  button.setAttribute('aria-label',show?'Ocultar senha':'Mostrar senha');
  button.setAttribute('title',show?'Ocultar senha':'Mostrar senha');
  const icon=button.querySelector('i');
  if(icon)icon.className=show?'fa-regular fa-eye-slash':'fa-regular fa-eye';
 });
})();


/* Oportunidades: modal de registro rápido */
(()=>{
 const modal=document.querySelector('[data-opp-modal]');
 const open=document.querySelector('[data-opp-new]');
 const close=document.querySelector('[data-opp-close]');
 if(!modal||!open)return;
 open.addEventListener('click',()=>modal.showModal());
 if(close)close.addEventListener('click',()=>modal.close());
 modal.addEventListener('click',e=>{if(e.target===modal)modal.close();});
})();


/* Oportunidades: busca incremental de clientes */
(()=>{
 const input=document.querySelector('[data-opp-client-search]');
 const hidden=document.querySelector('[data-opp-client-id]');
 const box=document.querySelector('[data-opp-client-results]');
 if(!input||!hidden||!box)return;
 let timer=null,controller=null;
 const hide=()=>{box.hidden=true;box.innerHTML='';};
 input.addEventListener('input',()=>{
  hidden.value='';const q=input.value.trim();
  clearTimeout(timer);if(q.length<2){hide();return;}
  timer=setTimeout(async()=>{
   try{
    if(controller)controller.abort();controller=new AbortController();
    const res=await fetch((input.dataset.api||'/api/clients')+'?q='+encodeURIComponent(q),{headers:{'Accept':'application/json'},signal:controller.signal});
    const data=await res.json();const items=Array.isArray(data.items)?data.items:[];
    if(!items.length){box.innerHTML='<div class="empty">Nenhum cliente encontrado</div>';box.hidden=false;return;}
    box.innerHTML=items.map(item=>'<button type="button" data-id="'+item.id+'" data-name="'+String(item.name||'').replace(/"/g,'&quot;')+'"><strong>'+String(item.name||'')+'</strong><small>'+[item.document,item.city,item.uf].filter(Boolean).join(' · ')+'</small></button>').join('');
    box.hidden=false;
   }catch(err){if(err.name!=='AbortError')hide();}
  },250);
 });
 box.addEventListener('click',e=>{
  const btn=e.target.closest('button[data-id]');if(!btn)return;
  hidden.value=btn.dataset.id;input.value=btn.dataset.name;hide();
 });
 document.addEventListener('click',e=>{if(!e.target.closest('.tdopp-client-search'))hide();});
})();
