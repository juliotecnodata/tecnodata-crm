document.addEventListener('DOMContentLoaded',()=>{
  const appBase=(()=>{try{return new URL(window.APP_URL||location.origin).pathname.replace(/^\/+|\/+$/g,'');}catch(e){return '';}})();
  const routeParts=location.pathname.replace(/^\/+|\/+$/g,'').split('/').filter(Boolean);
  const baseParts=appBase.split('/').filter(Boolean);
  const relativeParts=routeParts.slice(baseParts.length);
  const section=relativeParts[0]||'dashboard';
  let page=section;
  if(relativeParts.length===0&&document.body.dataset.page==='commercial_home')page='commercial-home';
  if(section==='my-portfolio'||section==='commercial-portfolio')page='commercial-portfolio';
  if(section==='commercial'&&relativeParts[1]==='accounts')page='commercial-account';
  if(section==='clients-ead-reciclagem'||section==='clients-suporte-pet')page='clients';
  if(section==='clients-audit')page='client-audit';
  if(section==='clients-sync')page='client-sync';
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
    document.addEventListener('click',event=>{
      const button=event.target.closest?.('[data-contact-schedule]');
      if(!button)return;
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
    });
    contactDialog.querySelectorAll('[data-contact-dialog-close]').forEach(button=>button.addEventListener('click',()=>contactDialog.close()));
    contactDialog.addEventListener('click',event=>{if(event.target===contactDialog)contactDialog.close();});
  }

  const clientStateFilter=document.querySelector('[data-client-state-filter]');
  const clientTagFilter=document.querySelector('[data-client-tag-filter]');
  clientTagFilter?.addEventListener('change',()=>clientTagFilter.form?.requestSubmit());
  document.querySelector('[data-client-uf-clear]')?.addEventListener('click',event=>{
    const root=event.currentTarget.closest('[data-client-uf-filter]');
    root?.querySelectorAll('input[name="ufs[]"]').forEach(input=>{input.checked=false;});
  });
  document.querySelector('[data-client-tags-clear]')?.addEventListener('click',event=>{
    const root=event.currentTarget.closest('[data-client-tag-multi]');
    root?.querySelectorAll('input[name="tags[]"]').forEach(input=>{input.checked=false;});
  });
  document.querySelector('[data-collection-tags-clear]')?.addEventListener('click',event=>{
    const root=event.currentTarget.closest('[data-collection-tag-filter]');
    root?.querySelectorAll('input[name="tags[]"]').forEach(input=>{input.checked=false;});
  });
  const monitorForm=document.querySelector('.tdset-monitor-form');
  if(monitorForm){
    const monitorChecks=Array.from(monitorForm.querySelectorAll('[name="monitor_user_ids[]"]'));
    const monitorCount=monitorForm.querySelector('[data-monitor-selected]');
    const updateMonitorCount=()=>{if(monitorCount)monitorCount.textContent=String(monitorChecks.filter(input=>input.checked).length);};
    monitorChecks.forEach(input=>input.addEventListener('change',updateMonitorCount));
    monitorForm.addEventListener('submit',event=>{if(!monitorChecks.some(input=>input.checked)){event.preventDefault();showNotice('warning','Selecione um participante','Marque pelo menos um usuário de vendas ou cobrança para o acompanhamento.');}});
  }
  const clearClientDddParams=url=>Array.from(new Set(url.searchParams.keys())).forEach(key=>{if(/^ddds(?:\[\d*\])?$/.test(key))url.searchParams.delete(key);});
  const clearClientUfParams=url=>Array.from(new Set(url.searchParams.keys())).forEach(key=>{if(key==='uf'||/^ufs(?:\[\d*\])?$/.test(key))url.searchParams.delete(key);});
  if(clientStateFilter){
    clientStateFilter.addEventListener('change',()=>{
      const url=new URL(location.href);
      const state=clientStateFilter.value.trim();
      const clientTable=document.querySelector('.clients-datatable');
      const tableSearch=String(clientTable?._dataTable?.search?.()??'').trim();
      clearClientUfParams(url);
      if(state)url.searchParams.set('uf',state);
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
    clearClientUfParams(url);
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

  document.querySelectorAll('[data-collection-bulk-delete]').forEach(form=>{
    const checks=Array.from(form.querySelectorAll('[data-collection-action-check]'));
    const selectAll=form.querySelector('[data-collection-select-all]');
    const counter=form.querySelector('[data-collection-selected-count]');
    const bulkButton=form.querySelector('[data-collection-bulk-submit]');
    const updateSelection=()=>{
      const selected=checks.filter(input=>input.checked).length;
      if(counter)counter.textContent=selected+' selecionado'+(selected===1?'':'s');
      if(bulkButton)bulkButton.disabled=selected===0;
      if(selectAll){selectAll.checked=checks.length>0&&selected===checks.length;selectAll.indeterminate=selected>0&&selected<checks.length;}
    };
    selectAll?.addEventListener('change',()=>{checks.forEach(input=>{input.checked=selectAll.checked;});updateSelection();});
    checks.forEach(input=>input.addEventListener('change',updateSelection));
    form.addEventListener('submit',event=>{
      const single=event.submitter?.name==='single_id';
      if(!single&&!checks.some(input=>input.checked)){event.preventDefault();showNotice('warning','Nenhum valor selecionado','Marque um ou mais lançamentos para excluir.');}
    });
    updateSelection();
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
  const setupCommercialActivityForm=form=>{
    if(!form||form.dataset.commercialActivityReady==='1')return;
    form.dataset.commercialActivityReady='1';
    const typeInputs=[...form.querySelectorAll('[name="activity_type"]')];
    const categorySelect=form.querySelector('[data-commercial-category]');
    const outcomeSelect=form.querySelector('[data-commercial-outcome]');
    const outcomeHidden=form.querySelector('[data-commercial-outcome-hidden]');
    const notes=form.querySelector('[data-commercial-notes]');
    const notesCount=form.querySelector('[data-commercial-notes-count]');
    const scheduleToggle=form.querySelector('[data-commercial-schedule-toggle]');
    const scheduleFields=form.querySelector('[data-commercial-schedule-fields]');
    const nextDate=form.querySelector('[data-commercial-next-date]');
    const nextTime=form.querySelector('[data-commercial-next-time]');
    const saleFields=form.querySelector('[data-commercial-sale-fields]');
    const futurePromise=form.querySelector('[data-commercial-future-promise]');
    const defaultOutcome={contact_attempt:'attempt',contact_completed:'contact',follow_up:'progress',sale:'sale'};
    const localToday=()=>{const d=new Date();return d.getFullYear()+'-'+String(d.getMonth()+1).padStart(2,'0')+'-'+String(d.getDate()).padStart(2,'0');};
    const refresh=()=>{
      const type=typeInputs.find(input=>input.checked)?.value||typeInputs.find(input=>!input.disabled)?.value||'contact_attempt';
      if(categorySelect){
        let firstVisible=null;
        [...categorySelect.options].forEach((option,index)=>{
          if(index===0&&option.value===''){
            option.hidden=true;option.disabled=true;return;
          }
          const types=String(option.dataset.types||'').split(',').filter(Boolean);
          const visible=!types.length||types.includes(type);
          option.hidden=!visible;option.disabled=!visible;
          if(visible&&!firstVisible)firstVisible=option;
        });
        if(![...categorySelect.options].some(option=>!option.disabled&&option.value===categorySelect.value))categorySelect.value=firstVisible?.value||'';
      }
      if(outcomeSelect){
        let firstVisible=null;
        [...outcomeSelect.options].forEach(option=>{
          const types=String(option.dataset.types||'').split(',').filter(Boolean);
          const visible=types.includes(type);
          option.hidden=!visible;option.disabled=!visible;
          if(visible&&!firstVisible)firstVisible=option;
        });
        if(![...outcomeSelect.options].some(option=>!option.disabled&&option.value===outcomeSelect.value))outcomeSelect.value=firstVisible?.value||'';
      }
      if(outcomeHidden)outcomeHidden.value=defaultOutcome[type]||'progress';
      if(saleFields){
        const selling=type==='sale';saleFields.hidden=!selling;
        saleFields.querySelectorAll('input,select,textarea').forEach(field=>{field.disabled=!selling;});
        saleFields.querySelectorAll('[name="sale_type"],[name="commercial_condition"],[name="sale_amount"]').forEach(field=>field.required=selling);
      }
    };
    const refreshCounter=()=>{
      if(notesCount)notesCount.textContent=String((notes?.value||'').length);
    };
    const refreshSchedule=()=>{
      if(!scheduleToggle||!scheduleFields)return;
      const enabled=scheduleToggle.checked;
      scheduleFields.hidden=!enabled;
      scheduleFields.querySelectorAll('input,select,textarea').forEach(field=>field.disabled=!enabled);
      if(nextDate){nextDate.required=enabled;nextDate.min=localToday();}
      if(nextTime)nextTime.required=enabled;
    };
    typeInputs.forEach(input=>input.addEventListener('change',refresh));
    notes?.addEventListener('input',refreshCounter);
    scheduleToggle?.addEventListener('change',refreshSchedule);
    futurePromise?.addEventListener('change',()=>{if(futurePromise.checked&&scheduleToggle){scheduleToggle.checked=true;scheduleToggle.dispatchEvent(new Event('change',{bubbles:true}));}});
    form.addEventListener('submit',event=>{
      if(scheduleToggle?.checked&&(!nextDate?.value||!nextTime?.value)){
        event.preventDefault();
        (nextDate&&!nextDate.value?nextDate:nextTime)?.focus();
        window.appNotify?.('warning','Retorno incompleto','Informe a data e o horário antes de salvar.');
      }
    });
    refresh();refreshCounter();refreshSchedule();
  };
  document.querySelectorAll('[data-commercial-activity-form]').forEach(setupCommercialActivityForm);

  const commercialActivityDialog=document.querySelector('[data-commercial-activity-dialog]');
  if(commercialActivityDialog){
    const form=commercialActivityDialog.querySelector('[data-commercial-activity-form]');
    const clientLabel=commercialActivityDialog.querySelector('[data-commercial-activity-client]');
    const ownerLabel=commercialActivityDialog.querySelector('[data-commercial-activity-owner]');
    const typeLabel=commercialActivityDialog.querySelector('[data-commercial-activity-type]');
    document.addEventListener('click',event=>{
      const button=event.target.closest?.('[data-commercial-activity-open]');
      if(!button||!form)return;
      const code=String(button.dataset.accountCode||'').trim();
      const name=String(button.dataset.accountName||'Conta CRM').trim();
      const owner=String(button.dataset.accountOwner||'Sem responsável').trim();
      const customerType=String(button.dataset.accountType||'Cliente').trim();
      const clientId=Number(button.dataset.clientId||0);
      if(!code)return;
      form.reset();
      form.action=(window.APP_URL||'')+'/commercial/accounts/'+encodeURIComponent(code)+'/activity';
      if(clientLabel)clientLabel.textContent=name;
      if(ownerLabel)ownerLabel.textContent=owner||'Sem responsável';
      if(typeLabel)typeLabel.textContent=customerType||'Cliente';
      const saleInput=form.querySelector('[name="activity_type"][value="sale"]');
      if(saleInput){
        saleInput.disabled=clientId<=0;
        saleInput.closest('label')?.classList.toggle('is-disabled',saleInput.disabled);
        saleInput.closest('label')?.setAttribute('title',saleInput.disabled?'A venda exige cliente vinculado':'Registrar venda');
      }
      setupCommercialActivityForm(form);
      const requestedType=String(button.dataset.activityType||'').trim();
      let requestedInput=requestedType?[...form.querySelectorAll('[name="activity_type"]')].find(input=>input.value===requestedType&&!input.disabled):null;
      const firstType=requestedInput||[...form.querySelectorAll('[name="activity_type"]')].find(input=>!input.disabled);
      if(firstType)firstType.checked=true;
      firstType?.dispatchEvent(new Event('change',{bubbles:true}));
      const counter=form.querySelector('[data-commercial-notes-count]');if(counter)counter.textContent='0';
      const completeTask=form.querySelector('[data-commercial-complete-task]');if(completeTask)completeTask.value=String(button.dataset.completeTaskId||'');
      const schedule=form.querySelector('[data-commercial-schedule-toggle]');if(schedule){schedule.checked=button.dataset.scheduleReturn!=='0';schedule.dispatchEvent(new Event('change',{bubbles:true}));}
      commercialActivityDialog.showModal();
    });
    commercialActivityDialog.querySelectorAll('[data-commercial-activity-close]').forEach(button=>button.addEventListener('click',()=>commercialActivityDialog.close()));
    commercialActivityDialog.addEventListener('click',event=>{if(event.target===commercialActivityDialog)commercialActivityDialog.close();});
    commercialActivityDialog.addEventListener('cancel',()=>commercialActivityDialog.close());
  }

  const partnerSyncDialog=document.querySelector('[data-partner-sync-dialog]');
  if(partnerSyncDialog){
    const opener=document.querySelector('[data-partner-sync-open]');
    const form=partnerSyncDialog.querySelector('[data-partner-sync-form]');
    const loading=partnerSyncDialog.querySelector('[data-partner-sync-loading]');
    const table=partnerSyncDialog.querySelector('[data-partner-sync-table]');
    const tbody=partnerSyncDialog.querySelector('[data-partner-sync-body]');
    const errorBox=partnerSyncDialog.querySelector('[data-partner-sync-error]');
    const summary=partnerSyncDialog.querySelector('[data-partner-sync-summary]');
    const search=partnerSyncDialog.querySelector('[data-partner-sync-search]');
    const selectAll=partnerSyncDialog.querySelector('[data-partner-sync-select-all]');
    const selectedCount=partnerSyncDialog.querySelector('[data-partner-sync-selected]');
    const saveButton=partnerSyncDialog.querySelector('[data-partner-sync-save]');
    const csrf=form?.querySelector('[name="_token"]')?.value||'';
    let previewRows=[];

    const setError=message=>{
      if(errorBox){errorBox.hidden=!message;errorBox.textContent=message||'';}
    };
    const checkboxes=()=>[...partnerSyncDialog.querySelectorAll('input[name="account_codes[]"]')];
    const refreshSelected=()=>{
      const boxes=checkboxes();const checked=boxes.filter(box=>box.checked).length;
      if(selectedCount)selectedCount.textContent=String(checked);
      if(saveButton)saveButton.disabled=checked===0;
      if(selectAll){
        selectAll.checked=boxes.length>0&&checked===boxes.length;
        selectAll.indeterminate=checked>0&&checked<boxes.length;
      }
    };
    const applySearch=()=>{
      const q=(search?.value||'').trim().toLocaleLowerCase('pt-BR');
      partnerSyncDialog.querySelectorAll('[data-partner-sync-row]').forEach(row=>{
        row.hidden=q!==''&&!String(row.dataset.search||'').includes(q);
      });
    };
    const statusMeta=status=>{
      if(status==='pending')return ['Pronto para validar','pending','fa-circle-check'];
      if(status==='already')return ['Já classificado','already','fa-circle-check'];
      if(status==='not_found')return ['Não localizado','not-found','fa-magnifying-glass'];
      return ['CNPJ inválido','invalid','fa-triangle-exclamation'];
    };
    const addText=(parent,tag,text,className='')=>{
      const el=document.createElement(tag);if(className)el.className=className;el.textContent=String(text??'');parent.appendChild(el);return el;
    };
    const renderRows=rows=>{
      if(!tbody)return;tbody.innerHTML='';previewRows=Array.isArray(rows)?rows:[];
      previewRows.forEach(item=>{
        const tr=document.createElement('tr');tr.dataset.partnerSyncRow='1';
        tr.dataset.status=String(item.status||'');
        tr.dataset.search=[item.source_name,item.document,item.account_name,item.account_code,item.owner_name,item.city_uf,item.current_classification].filter(Boolean).join(' ').toLocaleLowerCase('pt-BR');

        const tdSelect=document.createElement('td');tdSelect.className='select';
        if(item.selectable&&item.account_code){
          const label=document.createElement('label');label.className='cix-partner-sync-check';
          const input=document.createElement('input');input.type='checkbox';input.name='account_codes[]';input.value=String(item.account_code);input.checked=true;
          const mark=document.createElement('span');mark.innerHTML='<i class="fa-solid fa-check"></i>';
          label.append(input,mark);tdSelect.appendChild(label);
          input.addEventListener('change',refreshSelected);
        }else{
          const lock=document.createElement('span');lock.className='cix-partner-sync-lock';lock.innerHTML='<i class="fa-solid fa-minus"></i>';tdSelect.appendChild(lock);
        }
        tr.appendChild(tdSelect);

        const tdSource=document.createElement('td');addText(tdSource,'strong',item.source_name||'Sem nome');
        const sourceDetails=[item.city_uf,item.source_active?'Ativo na origem':'Inativo na origem'];
        if(Number(item.source_count||0)>1)sourceDetails.push(String(item.source_count)+' registros na origem');
        addText(tdSource,'small',sourceDetails.filter(Boolean).join(' · '));tr.appendChild(tdSource);

        const tdDoc=document.createElement('td');addText(tdDoc,'strong',item.document||'—');tr.appendChild(tdDoc);

        const tdCrm=document.createElement('td');
        if(item.account_code){
          addText(tdCrm,'strong',item.account_name||item.account_code);
          const crmDetails=['CRM '+item.account_code];if(item.duplicate_crm)crmDetails.push('CNPJ com mais de uma Conta CRM');
          addText(tdCrm,'small',crmDetails.join(' · '));
        }else{addText(tdCrm,'span','—','muted');}
        tr.appendChild(tdCrm);

        const tdOwner=document.createElement('td');addText(tdOwner,'span',item.owner_name||'Sem responsável');tr.appendChild(tdOwner);
        const tdClass=document.createElement('td');addText(tdClass,'span',item.current_classification||'—','cix-partner-sync-class');tr.appendChild(tdClass);

        const tdStatus=document.createElement('td');const [label,statusClass,icon]=statusMeta(item.status);
        const status=document.createElement('span');status.className='cix-partner-sync-status '+statusClass;
        status.innerHTML='<i class="fa-solid '+icon+'"></i>';addText(status,'b',label);tdStatus.appendChild(status);tr.appendChild(tdStatus);
        tbody.appendChild(tr);
      });
      if(!previewRows.length){
        const tr=document.createElement('tr');const td=document.createElement('td');td.colSpan=7;td.className='cix-empty';td.textContent='Nenhum cadastro retornado pela base de parceiros.';tr.appendChild(td);tbody.appendChild(tr);
      }
      applySearch();refreshSelected();
    };
    const renderSummary=data=>{
      if(!summary)return;
      const values=[
        [data?.source_rows||0,'registros em cfcs'],
        [data?.matched_accounts||0,'contas correspondentes'],
        [data?.pending_accounts||0,'selecionáveis'],
        [data?.not_found_documents||0,'revisar cadastro']
      ];
      [...summary.querySelectorAll('article')].forEach((article,index)=>{
        const strong=article.querySelector('strong');const span=article.querySelector('span');
        if(strong)strong.textContent=Number(values[index]?.[0]||0).toLocaleString('pt-BR');
        if(span)span.textContent=values[index]?.[1]||'';
      });
    };
    const loadPreview=async()=>{
      setError('');if(tbody)tbody.innerHTML='';if(search)search.value='';if(table)table.hidden=true;if(loading)loading.hidden=false;
      if(saveButton)saveButton.disabled=true;if(selectedCount)selectedCount.textContent='0';if(selectAll){selectAll.checked=true;selectAll.indeterminate=false;}
      renderSummary({});
      try{
        const payload=new FormData();payload.append('_token',csrf);
        const response=await fetch((window.APP_URL||'')+'/commercial-partners/sync-preview',{method:'POST',body:payload,headers:{Accept:'application/json'}});
        const body=await response.json().catch(()=>({ok:false,error:'Resposta inválida do servidor.'}));
        if(!response.ok||!body.ok)throw new Error(body.error||'Não foi possível consultar a base de parceiros.');
        renderSummary(body.data?.summary||{});renderRows(body.data?.rows||[]);
        if(table)table.hidden=false;
      }catch(error){
        setError(error?.message||'Não foi possível consultar a base de parceiros.');
      }finally{
        if(loading)loading.hidden=true;
      }
    };

    opener?.addEventListener('click',()=>{
      partnerSyncDialog.showModal();loadPreview();
    });
    partnerSyncDialog.querySelectorAll('[data-partner-sync-close]').forEach(button=>button.addEventListener('click',()=>partnerSyncDialog.close()));
    partnerSyncDialog.addEventListener('click',event=>{if(event.target===partnerSyncDialog)partnerSyncDialog.close();});
    search?.addEventListener('input',applySearch);
    selectAll?.addEventListener('change',()=>{
      checkboxes().forEach(box=>{box.checked=!!selectAll.checked;});refreshSelected();
    });
    form?.addEventListener('submit',async event=>{
      event.preventDefault();
      const selected=checkboxes().filter(box=>box.checked);
      if(!selected.length){refreshSelected();return;}
      const original=saveButton?.innerHTML||'';
      if(saveButton){saveButton.disabled=true;saveButton.innerHTML='<i class="fa-solid fa-spinner fa-spin"></i> Salvando...';}
      setError('');
      try{
        const response=await fetch(form.action,{method:'POST',body:new FormData(form),headers:{Accept:'application/json'}});
        const body=await response.json().catch(()=>({ok:false,error:'Resposta inválida do servidor.'}));
        if(!response.ok||!body.ok)throw new Error(body.error||'Não foi possível salvar os parceiros selecionados.');
        window.location.reload();
      }catch(error){
        setError(error?.message||'Não foi possível salvar os parceiros selecionados.');
        if(saveButton){saveButton.innerHTML=original;saveButton.disabled=false;}
      }
    });
  }

  const partnerDialog=document.querySelector('[data-partner-work-dialog]');
  if(partnerDialog){
    const form=partnerDialog.querySelector('[data-partner-work-form]');
    const schedule=partnerDialog.querySelector('[data-partner-schedule]');
    const fields=partnerDialog.querySelector('[data-partner-schedule-fields]');
    const refresh=()=>{const enabled=!!schedule?.checked;if(fields){fields.hidden=!enabled;fields.querySelectorAll('input').forEach(input=>{input.disabled=!enabled;input.required=enabled;});}};
    schedule?.addEventListener('change',refresh);refresh();
    document.addEventListener('click',event=>{const button=event.target.closest?.('[data-partner-work-open]');if(!button)return;form?.reset();if(form)form.action=(window.APP_URL||'')+'/commercial-partners/'+encodeURIComponent(button.dataset.accountCode||'')+'/work';const name=partnerDialog.querySelector('[data-partner-work-name]');if(name)name.textContent=button.dataset.accountName||'Parceiro';refresh();partnerDialog.showModal();});
    partnerDialog.querySelectorAll('[data-partner-work-close]').forEach(button=>button.addEventListener('click',()=>partnerDialog.close()));
    partnerDialog.addEventListener('click',event=>{if(event.target===partnerDialog)partnerDialog.close();});
  }

  const saleDialog=document.querySelector('[data-sale-dialog]');
  if(saleDialog){
    const form=saleDialog.querySelector('[data-sale-form]');const search=saleDialog.querySelector('[data-sale-account-search]');const results=saleDialog.querySelector('[data-sale-account-results]');const code=saleDialog.querySelector('[data-sale-account-code]');const selected=saleDialog.querySelector('[data-sale-selected-name]');const future=saleDialog.querySelector('[data-sale-future]');const followup=saleDialog.querySelector('[data-sale-followup]');let timer=0;
    const refreshFuture=()=>{const enabled=!!future?.checked;if(followup){followup.hidden=!enabled;followup.querySelectorAll('input').forEach(input=>{input.disabled=!enabled;input.required=enabled;});}};future?.addEventListener('change',refreshFuture);
    document.querySelector('[data-sale-open]')?.addEventListener('click',()=>{form?.reset();if(code)code.value='';if(selected)selected.textContent='Selecione o cliente';if(results)results.innerHTML='';refreshFuture();saleDialog.showModal();search?.focus();});
    search?.addEventListener('input',()=>{clearTimeout(timer);const q=search.value.trim();if(code)code.value='';if(q.length<2){if(results)results.innerHTML='';return;}timer=setTimeout(async()=>{try{const response=await fetch((window.APP_URL||'')+'/api/commercial/accounts/search?q='+encodeURIComponent(q),{headers:{Accept:'application/json'}});const body=await response.json();if(results)results.innerHTML=(body.items||[]).map((item,index)=>'<button type="button" data-sale-result="'+index+'"><strong></strong><small></small></button>').join('');[...results.querySelectorAll('[data-sale-result]')].forEach((button,index)=>{const item=body.items[index];button.querySelector('strong').textContent=item.name;button.querySelector('small').textContent=[item.document,item.owner_name].filter(Boolean).join(' · ');button.addEventListener('click',()=>{search.value=item.name;code.value=item.crm_account_code;selected.textContent=item.name;form.action=(window.APP_URL||'')+'/commercial-sales/'+encodeURIComponent(item.crm_account_code);results.innerHTML='';});});}catch(error){if(results)results.innerHTML='';}},250);});
    form?.addEventListener('submit',event=>{if(!code?.value){event.preventDefault();search?.focus();window.appNotify?.('warning','Selecione o cliente','Escolha um resultado da busca antes de registrar a venda.');}});
    saleDialog.querySelectorAll('[data-sale-close]').forEach(button=>button.addEventListener('click',()=>saleDialog.close()));saleDialog.addEventListener('click',event=>{if(event.target===saleDialog)saleDialog.close();});
  }

  const removeAuditRows=(ids=[])=>{
    const unique=[...new Set((ids||[]).map(Number).filter(Boolean))];
    unique.forEach(id=>{
      document.querySelectorAll('[data-audit-client-row="'+id+'"]').forEach(row=>{
        const group=row.closest('.tdaudit-group');
        row.remove();
        if(group&&!group.querySelector('[data-audit-client-row]'))group.remove();
      });
    });
  };
  const clientOmieModal=document.querySelector('[data-client-omie-modal]');
  const clientOmieOpeners=[...document.querySelectorAll('[data-client-omie-check]')];
  let preferredOmieTargetId=0,openClientOmieAudit=null,renderClientOmieAudit=null;
  if(clientOmieModal&&clientOmieOpeners.length){
    const body=clientOmieModal.querySelector('[data-client-omie-body]');
    const summary=clientOmieModal.querySelector('[data-client-omie-summary]');
    const batchDelete=clientOmieModal.querySelector('[data-client-omie-batch-delete]');
    let clientId=Number(clientOmieModal.dataset.clientId||clientOmieOpeners[0]?.dataset.clientId||0);
    let selectedInactiveOmieIds=new Set();
    const esc=value=>{const node=document.createElement('div');node.textContent=String(value??'');return node.innerHTML;};
    const localStatus=local=>{
      if(!local)return '<span class="tdc-omie-local none">Não vinculado no CRM</span>';
      if(!local.active)return '<span class="tdc-omie-local source-inactive">Inativo pela origem</span>';
      if(local.crm_inactive)return '<span class="tdc-omie-local crm-inactive">Inativo só no CRM</span>';
      return '<span class="tdc-omie-local active">Ativo no CRM</span>';
    };
    const render=audit=>{
      const remote=Array.isArray(audit?.remote)?audit.remote:[];
      const locals=Array.isArray(audit?.local)?audit.local:[];
      const activeLinked=remote.filter(item=>!item.inactive&&item.local).map(item=>Number(item.local.id||0)).filter(Boolean);
      const remoteCodes=new Set(remote.map(item=>String(item.omie_code||'')));
      const localOnly=locals.filter(item=>!remoteCodes.has(String(item.omie_code||'')));
      const missingLinked=localOnly.filter(item=>item.omie_code&&!String(item.omie_code).startsWith('LOCAL-')).map(item=>Number(item.id||0)).filter(Boolean);
      const inactiveLinked=remote.filter(item=>item.inactive&&item.local).map(item=>Number(item.local.id||0)).filter(Boolean);
      const removableLinked=[...new Set([...inactiveLinked,...missingLinked])];
      if(!activeLinked.includes(preferredOmieTargetId))preferredOmieTargetId=activeLinked.length===1?activeLinked[0]:0;
      selectedInactiveOmieIds=new Set(removableLinked);
      if(batchDelete){batchDelete.disabled=removableLinked.length===0;batchDelete.innerHTML='<i class="fa-solid fa-trash-can"></i>Excluir selecionados do CRM'+(removableLinked.length?' ('+removableLinked.length+')':'');}
      let html='<section class="tdc-omie-document"><span>CPF / CNPJ consultado</span><strong>'+esc(audit?.client?.document||audit?.document||'—')+'</strong><small>'+remote.length+' cadastro(s) retornado(s) pela Omie</small></section>';
      if(removableLinked.length){
        html+='<section class="tdc-omie-batchbar"><label><input type="checkbox" data-client-omie-select-all checked><span>Selecionar todos para limpeza</span></label><strong>'+removableLinked.length+' selecionado(s)</strong><small>Inclui cadastros inativos e também códigos locais que não foram encontrados na Omie. Todos serão processados em uma única chamada.</small></section>';
      }
      if(remote.length){
        html+='<div class="tdc-omie-results">';
        remote.forEach(item=>{
          const status=item.inactive?'inactive':'active';
          const linked=item.local;
          const linkedId=Number(linked?.id||0);
          const principal=linkedId>0&&preferredOmieTargetId===linkedId;
          html+='<article class="tdc-omie-result '+status+(item.is_current_code?' current':'')+'">'+
            '<header><div><span class="tdc-omie-status '+status+'"><i class="fa-solid '+(item.inactive?'fa-circle-xmark':'fa-circle-check')+'"></i>'+esc(item.status_label)+'</span>'+(item.is_current_code?'<b>Cadastro desta ficha</b>':'')+
            (item.inactive&&linked?'<label class="tdc-omie-select"><input type="checkbox" data-client-omie-select-inactive="'+linkedId+'" checked><span>Selecionado</span></label>':'')+
            '</div><strong>Omie '+esc(item.omie_code||'—')+'</strong></header>'+
            '<div class="tdc-omie-result-grid">'+
             '<div><small>Nome fantasia</small><strong>'+esc(item.name||'—')+'</strong></div>'+
             '<div><small>Razão social</small><strong>'+esc(item.legal_name||'—')+'</strong></div>'+
             '<div><small>E-mail</small><strong>'+esc(item.email||'—')+'</strong></div>'+
             '<div><small>Localização</small><strong>'+esc([item.city,item.uf].filter(Boolean).join(' / ')||'—')+'</strong></div>'+
            '</div>'+
            '<footer>'+localStatus(linked)+'<div class="tdc-omie-result-actions">'+

              (!item.inactive&&linked?'<button type="button" class="tdc-omie-keep-target '+(principal?'selected':'')+'" data-client-omie-keep-target="'+linkedId+'"><i class="fa-solid '+(principal?'fa-circle-check':'fa-thumbtack')+'"></i>'+(principal?'Principal escolhido':'Manter este no CRM')+'</button>':'')+
              (item.inactive&&linked?'<button type="button" data-client-omie-delete="'+linkedId+'"><i class="fa-solid fa-trash-can"></i>Excluir só do CRM</button>':'')+
            '</div></footer>'+
          '</article>';
        });
        html+='</div>';
      }else{
        html+='<div class="tdc-omie-empty"><i class="fa-solid fa-magnifying-glass"></i><strong>Nenhum cadastro encontrado na Omie</strong><p>O CRM não alterou nenhum registro local.</p></div>';
      }
      if(localOnly.length){
        html+='<section class="tdc-omie-local-only"><header><i class="fa-solid fa-database"></i><div><strong>Não encontrados na Omie</strong><small>Se o código não existir na consulta da Omie, ele pode ser excluído somente do CRM. A Omie não será alterada.</small></div></header>';
        localOnly.forEach(item=>{
          const deletable=item.omie_code&&!String(item.omie_code).startsWith('LOCAL-');
          const id=Number(item.id||0);
          html+='<div class="'+(deletable?'can-remove':'')+'"><span><strong>'+esc(item.name||'Cliente')+'</strong><small>'+esc(item.omie_code||'Sem código Omie')+'</small></span>'+
            (deletable?'<label class="tdc-omie-select missing"><input type="checkbox" data-client-omie-select-inactive="'+id+'" checked><span>Excluir do CRM</span></label>':localStatus(item))+
            '<div class="tdc-omie-local-actions">'+
            (deletable?'<button type="button" data-client-omie-delete="'+id+'"><i class="fa-solid fa-trash-can"></i>Excluir só do CRM</button>':'')+
            '</div></div>';
        });
        html+='</section>';
      }
      if(Number(audit?.remote_active_count||0)>1){
        html+='<div class="tdc-omie-warning"><i class="fa-solid fa-triangle-exclamation"></i><div><strong>A Omie possui mais de um cadastro ativo com este CPF/CNPJ.</strong><p>Inative manualmente no Omie o código que será descartado e consulte novamente esta linha. O CRM só libera a exclusão quando a Omie retornar o código como inativo.</p></div></div>';
      }
      body.innerHTML=html;
      summary.textContent=Number(audit?.remote_active_count||0)+' ativo(s) · '+Number(audit?.remote_inactive_count||0)+' inativo(s) na Omie';
    };
    renderClientOmieAudit=render;
    const load=async(targetId=clientId)=>{
      clientId=Number(targetId||0);preferredOmieTargetId=0;selectedInactiveOmieIds=new Set();clientOmieModal.dataset.clientId=String(clientId);
      body.innerHTML='<div class="tdc-omie-loading"><i class="fa-solid fa-circle-notch fa-spin"></i><span>Consultando a Omie em tempo real...</span></div>';
      summary.textContent='';
      if(!clientId){body.innerHTML='<div class="tdc-omie-empty error"><i class="fa-solid fa-triangle-exclamation"></i><strong>Cliente não identificado</strong><p>Não foi possível determinar qual cadastro consultar.</p></div>';return;}
      try{
        const response=await fetch((window.APP_URL||'')+'/api/clients/'+clientId+'/omie-check',{headers:{'Accept':'application/json'}});
        const data=await response.json().catch(()=>({ok:false,error:'Resposta inválida da Omie.'}));
        if(!response.ok||!data.ok)throw new Error(data.error||'Não foi possível consultar a Omie.');
        render(data.audit);
      }catch(error){
        body.innerHTML='<div class="tdc-omie-empty error"><i class="fa-solid fa-triangle-exclamation"></i><strong>Falha na consulta</strong><p>'+esc(error.message||'Tente novamente.')+'</p></div>';
        summary.textContent='Consulta não concluída';
        showNotice('danger','Consulta Omie não concluída',error.message||'Tente novamente.');
      }
    };
    openClientOmieAudit=targetId=>{
      const id=Number(targetId||0);if(!id)return;
      if(!clientOmieModal.open)clientOmieModal.showModal();load(id);
    };
    clientOmieOpeners.forEach(button=>button.addEventListener('click',()=>{
      openClientOmieAudit(Number(button.dataset.clientId||button.dataset.clientOmieCheck||clientId||0));
    }));
    body.addEventListener('click',event=>{
      const keep=event.target.closest?.('[data-client-omie-keep-target]');if(!keep)return;
      preferredOmieTargetId=Number(keep.dataset.clientOmieKeepTarget||0);
      body.querySelectorAll('[data-client-omie-keep-target]').forEach(button=>{
        const selected=Number(button.dataset.clientOmieKeepTarget||0)===preferredOmieTargetId;
        button.classList.toggle('selected',selected);
        button.innerHTML='<i class="fa-solid '+(selected?'fa-circle-check':'fa-thumbtack')+'"></i>'+(selected?'Principal escolhido':'Manter este no CRM');
      });
      showNotice('success','Cadastro principal definido','Este cadastro será usado como destino do histórico quando você remover duplicados já inativos ou inexistentes na Omie.');
    });
    body.addEventListener('change',event=>{
      const item=event.target.closest?.('[data-client-omie-select-inactive]');
      const all=event.target.closest?.('[data-client-omie-select-all]');
      if(all){
        selectedInactiveOmieIds=new Set();
        body.querySelectorAll('[data-client-omie-select-inactive]').forEach(input=>{input.checked=all.checked;if(all.checked)selectedInactiveOmieIds.add(Number(input.dataset.clientOmieSelectInactive||0));});
      }else if(item){
        const id=Number(item.dataset.clientOmieSelectInactive||0);
        if(item.checked)selectedInactiveOmieIds.add(id);else selectedInactiveOmieIds.delete(id);
        const allBox=body.querySelector('[data-client-omie-select-all]');
        const boxes=[...body.querySelectorAll('[data-client-omie-select-inactive]')];
        if(allBox)allBox.checked=boxes.length>0&&boxes.every(input=>input.checked);
      }else return;
      const count=selectedInactiveOmieIds.size;
      const barCount=body.querySelector('.tdc-omie-batchbar>strong');if(barCount)barCount.textContent=count+' cadastro(s) selecionado(s)';
      if(batchDelete){batchDelete.disabled=count===0;batchDelete.innerHTML='<i class="fa-solid fa-trash-can"></i>Excluir selecionados do CRM'+(count?' ('+count+')':'');}
    });
    clientOmieModal.querySelectorAll('[data-client-omie-close]').forEach(button=>button.addEventListener('click',()=>clientOmieModal.close()));
    clientOmieModal.addEventListener('click',event=>{if(event.target===clientOmieModal)clientOmieModal.close();});
    batchDelete?.addEventListener('click',async()=>{
      const ids=[...selectedInactiveOmieIds].filter(Boolean);
      if(!ids.length)return;
      if(!window.confirm('Excluir '+ids.length+' cadastro(s) SOMENTE do CRM em uma única operação? Entram os inativos e também os códigos não encontrados na Omie. A Omie não será alterada. Se houver cadastro principal escolhido, o histórico será consolidado nele; caso contrário, o histórico existente será arquivado antes da exclusão.'))return;
      const previous=batchDelete.innerHTML;batchDelete.disabled=true;batchDelete.innerHTML='<i class="fa-solid fa-circle-notch fa-spin"></i>Processando '+ids.length+'...';
      try{
        const payload=new URLSearchParams({_token:String(clientOmieModal.dataset.csrf||window.CSRF||'')});
        ids.forEach(id=>payload.append('source_ids[]',String(id)));
        if(preferredOmieTargetId>0)payload.set('target_client_id',String(preferredOmieTargetId));
        const response=await fetch((window.APP_URL||'')+'/api/clients/omie-batch-delete-inactive',{method:'POST',headers:{'Accept':'application/json','Content-Type':'application/x-www-form-urlencoded;charset=UTF-8'},credentials:'same-origin',body:payload.toString()});
        const data=await response.json().catch(()=>({ok:false,error:'Resposta inválida do servidor.'}));
        if(!response.ok||!data.ok)throw new Error(data.error||'Não foi possível processar os cadastros selecionados.');
        if(data.requires_target){
          batchDelete.disabled=false;batchDelete.innerHTML=previous;
          showNotice('warning','Escolha o cadastro principal',data.message||'Há mais de um cadastro ativo na Omie.');
          if(data.audit)render(data.audit);
          return;
        }
        const removedIds=(Array.isArray(data.removed)?data.removed:[]).map(item=>Number(item?.id||0)).filter(Boolean);
        removeAuditRows(removedIds.length?removedIds:ids);
        showNotice('success','Duplicados tratados em lote',data.message||ids.length+' cadastro(s) removido(s) somente do CRM.');
        batchDelete.innerHTML='<i class="fa-solid fa-check"></i>'+Number(data.removed_count||ids.length)+' removido(s)';
        clientOmieModal.close();
      }catch(error){
        batchDelete.disabled=false;batchDelete.innerHTML=previous;
        showNotice('danger','Não foi possível processar em lote',error.message||'Tente novamente.');
      }
    });

  }


  const removeInactiveOmieLocal=async button=>{
    const targetId=Number(button?.dataset?.clientOmieDelete||0);if(!targetId)return;
    if(!window.confirm('Excluir este cadastro SOMENTE do CRM? Se ele não for encontrado na Omie, a exclusão local continuará mesmo assim. A Omie não será alterada. O histórico será transferido para um cadastro principal quando houver; caso contrário, será arquivado antes da exclusão.'))return;
    const previous=button.innerHTML;button.disabled=true;button.innerHTML='<i class="fa-solid fa-circle-notch fa-spin"></i>Excluindo...';
    try{
      const token=String(clientOmieModal?.dataset?.csrf||window.CSRF||'');
      const insideOmieModal=!!button.closest?.('[data-client-omie-modal]');
      const payload=new URLSearchParams({_token:token});
      if(insideOmieModal&&preferredOmieTargetId>0)payload.set('target_client_id',String(preferredOmieTargetId));
      const response=await fetch((window.APP_URL||'')+'/api/clients/'+targetId+'/delete-inactive-omie-local',{method:'POST',headers:{'Accept':'application/json','Content-Type':'application/x-www-form-urlencoded;charset=UTF-8'},credentials:'same-origin',body:payload.toString()});
      const data=await response.json().catch(()=>({ok:false,error:'Resposta inválida do servidor.'}));
      if(!response.ok||!data.ok)throw new Error(data.error||'Não foi possível remover o cadastro inativo.');
      if(data.requires_target){
        button.disabled=false;button.innerHTML=previous;
        showNotice('warning','Escolha o cadastro que ficará',data.message||'Há mais de um cadastro ativo. Selecione qual deve permanecer no CRM.');
        if(data.audit&&clientOmieModal&&renderClientOmieAudit){
          clientOmieModal.dataset.clientId=String(targetId);
          if(!clientOmieModal.open)clientOmieModal.showModal();
          renderClientOmieAudit(data.audit);
        }
        return;
      }
      removeAuditRows([targetId]);
      showNotice('success','Cadastro inativo removido',data.message||'O cadastro foi removido somente do CRM.');
      if(clientOmieModal?.open)clientOmieModal.close();
    }catch(error){
      button.disabled=false;button.innerHTML=previous;
      showNotice('danger','Não foi possível excluir',error.message||'Tente novamente.');
    }
  };
  document.addEventListener('click',event=>{
    const button=event.target.closest?.('[data-client-omie-delete]');
    if(button)removeInactiveOmieLocal(button);
  });

  const productDetailModal=document.querySelector('[data-product-detail-modal]');
  if(productDetailModal){
    const detailBody=productDetailModal.querySelector('[data-product-detail-body]');
    const detailTitle=productDetailModal.querySelector('[data-product-detail-title]');
    const detailSubtitle=productDetailModal.querySelector('[data-product-detail-subtitle]');
    const detailEsc=value=>{const node=document.createElement('div');node.textContent=String(value??'');return node.innerHTML;};
    const renderProductDetails=product=>{
      detailTitle.textContent=product.name||'Detalhes do produto';
      detailSubtitle.textContent=(product.sku?'SKU '+product.sku+' • ':'')+(product.active?'Produto ativo':'Produto inativo');
      const notes=[
        '<section class="product-detail-text"><strong>Descrição detalhada do produto</strong><p>'+detailEsc(product.detailed_description||'Não informada na Omie.')+'</p></section>',
        '<section class="product-detail-text observations"><strong><i class="fa-regular fa-note-sticky"></i> Observações internas</strong><p>'+detailEsc(product.observations||'Não informadas na Omie.')+'</p></section>'
      ].join('');
      const groups=(product.groups||[]).map(group=>'<section class="product-detail-group"><header><span><i class="fa-solid '+detailEsc(group.icon||'fa-circle-info')+'"></i></span><strong>'+detailEsc(group.title||'Informações')+'</strong></header><div>'+((group.fields||[]).map(field=>'<article><small>'+detailEsc(field.label)+'</small><strong>'+detailEsc(field.value)+'</strong></article>').join(''))+'</div></section>').join('');
      detailBody.innerHTML=groups+notes||'<div class="product-detail-empty"><i class="fa-solid fa-box-open"></i><span>Nenhuma informação adicional disponível.</span></div>';
    };
    const openProductDetails=async id=>{
      if(!id)return;
      detailTitle.textContent='Detalhes do produto';detailSubtitle.textContent='Consultando o catálogo local';
      detailBody.innerHTML='<div class="product-detail-loading"><i class="fa-solid fa-spinner fa-spin"></i><span>Carregando detalhes...</span></div>';
      if(!productDetailModal.open)productDetailModal.showModal();
      try{
        const response=await fetch((window.APP_URL||'')+'/api/products/'+encodeURIComponent(id),{credentials:'same-origin',headers:{Accept:'application/json'}});
        const data=await response.json().catch(()=>({error:'O servidor retornou uma resposta inválida.'}));
        if(!response.ok||!data.product)throw new Error(data.error||'Não foi possível carregar o produto.');
        renderProductDetails(data.product);
      }catch(error){detailBody.innerHTML='<div class="product-detail-error"><i class="fa-solid fa-triangle-exclamation"></i><span>'+detailEsc(error.message||'Não foi possível carregar os detalhes.')+'</span></div>';showNotice('danger','Erro ao abrir o produto',error.message||'Tente novamente.');}
    };
    document.addEventListener('click',event=>{const trigger=event.target.closest?.('[data-product-details]');if(trigger){event.preventDefault();openProductDetails(trigger.dataset.productDetails);}});
    productDetailModal.querySelectorAll('[data-product-detail-close]').forEach(button=>button.addEventListener('click',()=>productDetailModal.close()));
    productDetailModal.addEventListener('click',event=>{if(event.target===productDetailModal)productDetailModal.close();});
  }

  const globalTaskModal=document.querySelector('[data-global-task-modal]');
  if(globalTaskModal){
    const form=globalTaskModal.querySelector('[data-global-task-form]');
    const clientIdInput=globalTaskModal.querySelector('[data-task-client-id]');
    const accountCodeInput=globalTaskModal.querySelector('[data-task-account-code]');
    const entityLabel=globalTaskModal.querySelector('[data-task-entity-label]');
    const clientSearch=globalTaskModal.querySelector('[data-task-client-search]');
    const clientResults=globalTaskModal.querySelector('[data-task-client-results]');
    const clientSelected=globalTaskModal.querySelector('[data-task-client-selected]');
    const dueInput=globalTaskModal.querySelector('[data-task-due]');
    const contextSelect=globalTaskModal.querySelector('[data-task-context]');
    const assignedSelect=globalTaskModal.querySelector('[data-task-assigned]');
    const typeSelect=globalTaskModal.querySelector('[data-task-type]');
    const base=String(window.APP_URL||'').replace(/\/$/,'');
    const esc=value=>{const node=document.createElement('div');node.textContent=String(value??'');return node.innerHTML;};
    const localDateTime=minutes=>{const date=new Date(Date.now()+minutes*60000);const pad=n=>String(n).padStart(2,'0');return date.getFullYear()+'-'+pad(date.getMonth()+1)+'-'+pad(date.getDate())+'T'+pad(date.getHours())+':'+pad(date.getMinutes());};
    let config=null,clientTimer=null,currentClient=null,currentAccount=null,openingTrigger=null;

    const clearClient=()=>{
      currentClient=null;currentAccount=null;clientIdInput.value='';accountCodeInput.value='';clientSelected.innerHTML='';clientSearch.hidden=false;clientSearch.value='';clientResults.innerHTML='';clientSearch.focus();
    };
    const selectClient=client=>{
      currentClient=client||null;currentAccount=null;clientIdInput.value=String(client?.id||'');accountCodeInput.value='';
      clientSearch.hidden=true;clientSearch.value='';clientResults.innerHTML='';
      const owner=client?.portfolio_seller_name?'Carteira: '+client.portfolio_seller_name:'';
      clientSelected.innerHTML='<div><strong>'+esc(client?.name||'Cliente')+'</strong><small>'+esc([client?.document,client?.city,client?.uf,owner].filter(Boolean).join(' • '))+'</small></div><button type="button" title="Trocar seleção"><i class="fa-solid fa-xmark"></i></button>';
      clientSelected.querySelector('button')?.addEventListener('click',clearClient);
    };
    const selectAccount=account=>{
      currentAccount=account||null;currentClient=null;
      accountCodeInput.value=String(account?.crm_account_code||account?.omie_code||'');
      clientIdInput.value=String(account?.client_id||'');
      clientSearch.hidden=true;clientSearch.value='';clientResults.innerHTML='';
      const owner=account?.owner_name?'Responsável: '+account.owner_name:'';
      const link=account?.client_id?'Cliente Geral vinculado':'Prospect / Conta CRM';
      clientSelected.innerHTML='<div><strong>'+esc(account?.name||account?.trade_name||'Conta CRM')+'</strong><small>'+esc([account?.document,account?.city,account?.uf,owner,link].filter(Boolean).join(' • '))+'</small></div><button type="button" title="Trocar seleção"><i class="fa-solid fa-xmark"></i></button>';
      clientSelected.querySelector('button')?.addEventListener('click',clearClient);
    };
    const renderSelectors=()=>{
      if(!config)return;
      const context=contextSelect.value||config.default_context||'sales';
      if(entityLabel)entityLabel.textContent=context==='collection'?'Cliente de cobrança':'Conta CRM / Cliente';
      clientSearch.placeholder=context==='collection'?'Busque cliente por nome, documento ou código':'Busque Conta CRM por nome, CNPJ/CPF ou responsável';
      const roleNeeded=context==='collection'?'collector':'seller';
      const users=(config.users||[]).filter(user=>{const userRole=String(user.role||'');return userRole===roleNeeded||userRole==='supervisor'||userRole==='admin';});
      const previous=assignedSelect.value;
      assignedSelect.innerHTML='<option value="">Selecione o responsável</option>'+users.map(user=>'<option value="'+Number(user.id)+'">'+esc(user.name)+(Number(user.id)===Number(config.current_user_id)?' · você':'')+'</option>').join('');
      const preferred=openingTrigger?.dataset.taskAssignedId||previous||String(config.current_user_id||'');
      if([...assignedSelect.options].some(option=>option.value===String(preferred)))assignedSelect.value=String(preferred);
      else if(users.length===1)assignedSelect.value=String(users[0].id);

      const types=(config.types||[]).filter(type=>type.active!==false&&(type.contexts||[]).includes(context));
      typeSelect.innerHTML='<option value="">Selecione o tipo</option>'+types.map(type=>'<option value="'+esc(type.code)+'">'+esc(type.label)+'</option>').join('');
      const preferredType=openingTrigger?.dataset.taskType||'';
      if(preferredType&&[...typeSelect.options].some(option=>option.value===preferredType))typeSelect.value=preferredType;
    };
    const loadConfig=async(clientId,accountCode)=>{
      const params=new URLSearchParams();
      if(clientId)params.set('client_id',String(clientId));
      if(accountCode)params.set('crm_account_code',String(accountCode));
      const url=base+'/api/tasks/form-context'+(params.toString()?'?'+params.toString():'');
      const response=await fetch(url,{credentials:'same-origin',headers:{Accept:'application/json'}});
      const data=await response.json().catch(()=>({}));if(!response.ok||!data.ok)throw new Error(data.error||'Não foi possível preparar a tarefa.');
      config=data;
      const requestedContext=openingTrigger?.dataset.taskContext||data.default_context||'sales';
      contextSelect.value=requestedContext;
      contextSelect.dataset.previousContext=requestedContext;
      contextSelect.disabled=['seller','collector'].includes(String(data.role||''));
      if(requestedContext==='sales'&&data.account)selectAccount({
        crm_account_code:data.account.omie_code,
        name:data.account.trade_name||data.account.name,
        document:data.account.document,
        owner_name:data.account.owner_name,
        client_id:data.account.client_id,
        city:data.account.client_city,
        uf:data.account.client_uf
      });
      else if(data.client)selectClient(data.client);
      renderSelectors();
    };
    const searchClients=async()=>{
      const query=clientSearch.value.trim();if(query.length<2){clientResults.innerHTML='';return;}
      const context=contextSelect.value||config?.default_context||'sales';
      const sales=context==='sales';
      clientResults.innerHTML='<div class="tdcrm-task-search-state">Buscando '+(sales?'Contas CRM':'clientes')+'...</div>';
      try{
        const endpoint=sales?base+'/api/commercial/accounts/search?q='+encodeURIComponent(query):base+'/api/clients?scope=task&q='+encodeURIComponent(query);
        const response=await fetch(endpoint,{credentials:'same-origin',headers:{Accept:'application/json'}});
        const data=await response.json().catch(()=>({}));if(!response.ok)throw new Error(data.error||'Falha na busca.');
        const items=Array.isArray(data.items)?data.items:[];
        if(sales){
          clientResults.innerHTML=items.length?items.map((account,index)=>'<button type="button" data-global-task-account="'+index+'"><span><strong>'+esc(account.name)+'</strong><small>'+esc([account.document,account.city,account.uf,account.owner_name?'Responsável: '+account.owner_name:'',account.client_id?'Cliente vinculado':'Prospect'].filter(Boolean).join(' • '))+'</small></span><i class="fa-solid fa-chevron-right"></i></button>').join(''):'<div class="tdcrm-task-search-state">Nenhuma Conta CRM encontrada.</div>';
          clientResults.querySelectorAll('[data-global-task-account]').forEach(button=>button.addEventListener('click',()=>selectAccount(items[Number(button.dataset.globalTaskAccount)]||{})));
        }else{
          clientResults.innerHTML=items.length?items.map((client,index)=>'<button type="button" data-global-task-client="'+index+'"><span><strong>'+esc(client.name)+'</strong><small>'+esc([client.document,client.city,client.uf,client.portfolio_seller_name?'Carteira: '+client.portfolio_seller_name:''].filter(Boolean).join(' • '))+'</small></span><i class="fa-solid fa-chevron-right"></i></button>').join(''):'<div class="tdcrm-task-search-state">Nenhum cliente encontrado.</div>';
          clientResults.querySelectorAll('[data-global-task-client]').forEach(button=>button.addEventListener('click',()=>selectClient(items[Number(button.dataset.globalTaskClient)]||{})));
        }
      }catch(error){clientResults.innerHTML='';showNotice('danger','Busca de tarefa',error.message||'Não foi possível consultar a base.');}
    };
    const openTask=async trigger=>{
      openingTrigger=trigger;config=null;currentClient=null;currentAccount=null;form?.reset();clientIdInput.value='';accountCodeInput.value='';clientSelected.innerHTML='';clientResults.innerHTML='';clientSearch.hidden=false;clientSearch.value='';
      dueInput.min=localDateTime(0);dueInput.value=localDateTime(30);
      assignedSelect.innerHTML='<option value="">Carregando responsáveis...</option>';typeSelect.innerHTML='<option value="">Carregando tipos...</option>';
      globalTaskModal.showModal();
      const clientId=Number(trigger?.dataset.taskClientId||0);
      const clientName=String(trigger?.dataset.taskClientName||'').trim();
      const accountCode=String(trigger?.dataset.taskAccountCode||'').trim();
      const accountName=String(trigger?.dataset.taskAccountName||'').trim();
      if(accountCode&&accountName)selectAccount({crm_account_code:accountCode,name:accountName,client_id:clientId||''});
      else if(clientId&&clientName)selectClient({id:clientId,name:clientName});
      try{await loadConfig(clientId,accountCode);}catch(error){showNotice('danger','Nova tarefa',error.message||'Não foi possível abrir o formulário.');globalTaskModal.close();}
    };

    document.addEventListener('click',event=>{const trigger=event.target.closest?.('[data-global-task-open]');if(trigger){event.preventDefault();openTask(trigger);}});
    contextSelect?.addEventListener('change',()=>{
      const previous=contextSelect.dataset.previousContext||'';
      const current=contextSelect.value;
      if(previous&&previous!==current)clearClient();
      contextSelect.dataset.previousContext=current;
      renderSelectors();
    });
    clientSearch?.addEventListener('input',()=>{clearTimeout(clientTimer);clientTimer=setTimeout(searchClients,240);});
    globalTaskModal.querySelectorAll('[data-global-task-close]').forEach(button=>button.addEventListener('click',()=>globalTaskModal.close()));
    globalTaskModal.addEventListener('click',event=>{if(event.target===globalTaskModal)globalTaskModal.close();});
    form?.addEventListener('submit',async event=>{
      event.preventDefault();
      const taskContext=contextSelect.value||'sales';
      if(taskContext==='sales'&&!accountCodeInput.value&&!clientIdInput.value){showNotice('warning','Selecione uma Conta CRM','Busque e selecione a Conta CRM antes de criar a tarefa.');clientSearch.hidden=false;clientSearch.focus();return;}
      if(taskContext==='collection'&&!clientIdInput.value){showNotice('warning','Selecione um cliente','Busque e selecione o cliente antes de criar a tarefa de cobrança.');clientSearch.hidden=false;clientSearch.focus();return;}
      const submit=form.querySelector('[type="submit"]');const original=submit?.innerHTML;if(submit){submit.disabled=true;submit.innerHTML='<i class="fa-solid fa-spinner fa-spin"></i>Criando...';}
      try{
        const data=new FormData(form);if(contextSelect.disabled)data.set('context',contextSelect.value);
        const response=await fetch(base+'/api/tasks',{method:'POST',credentials:'same-origin',headers:{Accept:'application/json'},body:data});
        const payload=await response.json().catch(()=>({}));if(!response.ok||!payload.ok)throw new Error(payload.error||'Não foi possível criar a tarefa.');
        globalTaskModal.close();showNotice('success','Tarefa criada',payload.message||'A tarefa foi criada com sucesso.',9000);
        if(document.body.dataset.page==='agenda')setTimeout(()=>location.reload(),700);
      }catch(error){showNotice('danger','Não foi possível criar a tarefa',error.message||'Revise os dados e tente novamente.',10000);}
      finally{if(submit){submit.disabled=false;submit.innerHTML=original;}}
    });
  }

  const agendaTaskModal=document.querySelector('[data-agenda-task-modal]');
  if(agendaTaskModal){
    const form=agendaTaskModal.querySelector('[data-agenda-task-form]');
    const modeInput=agendaTaskModal.querySelector('[data-agenda-task-mode]');
    const heading=agendaTaskModal.querySelector('[data-agenda-task-heading]');
    const icon=agendaTaskModal.querySelector('[data-agenda-task-icon]');
    const headClient=agendaTaskModal.querySelector('[data-agenda-task-client]');
    const headStatus=agendaTaskModal.querySelector('[data-agenda-task-status]');
    const clientName=agendaTaskModal.querySelector('[data-agenda-task-client-name]');
    const clientMeta=agendaTaskModal.querySelector('[data-agenda-task-client-meta]');
    const clientLink=agendaTaskModal.querySelector('[data-agenda-task-client-link]');
    const cardContext=agendaTaskModal.querySelector('[data-agenda-task-card-context]');
    const cardType=agendaTaskModal.querySelector('[data-agenda-task-card-type]');
    const cardAssigned=agendaTaskModal.querySelector('[data-agenda-task-card-assigned]');
    const cardDue=agendaTaskModal.querySelector('[data-agenda-task-card-due]');
    const created=agendaTaskModal.querySelector('[data-agenda-task-created]');
    const createdBy=agendaTaskModal.querySelector('[data-agenda-task-created-by]');
    const statusLabel=agendaTaskModal.querySelector('[data-agenda-task-status-label]');
    const updated=agendaTaskModal.querySelector('[data-agenda-task-updated]');
    const context=agendaTaskModal.querySelector('[data-agenda-task-context]');
    const type=agendaTaskModal.querySelector('[data-agenda-task-type]');
    const assigned=agendaTaskModal.querySelector('[data-agenda-task-assigned]');
    const due=agendaTaskModal.querySelector('[data-agenda-task-due]');
    const description=agendaTaskModal.querySelector('[data-agenda-task-description]');
    const completion=agendaTaskModal.querySelector('[data-agenda-task-completion]');
    const result=agendaTaskModal.querySelector('[data-agenda-task-result]');
    const notes=agendaTaskModal.querySelector('[data-agenda-task-notes]');
    const submit=agendaTaskModal.querySelector('[data-agenda-task-submit]');
    const base=String(window.APP_URL||'').replace(/\/$/,'');
    const esc=value=>{const node=document.createElement('div');node.textContent=String(value??'');return node.innerHTML;};
    let payload=null,taskId=0,currentMode='view';

    const setOptions=(select,items,value,labelKey='name',valueKey='id',emptyLabel='Selecione')=>{
      select.innerHTML='<option value="">'+esc(emptyLabel)+'</option>'+items.map(item=>'<option value="'+esc(item[valueKey])+'">'+esc(item[labelKey])+(item.active===false?' · inativo':'')+'</option>').join('');
      select.value=String(value??'');
    };
    const renderContextChoices=()=>{
      if(!payload)return;
      const ctx=context.value==='collection'?'collection':'sales';
      const task=payload.task||{};
      const users=payload.users?.[ctx]||[];
      const types=payload.types?.[ctx]||[];
      const previousAssigned=assigned.value||String(task.assigned_user_id||'');
      const previousType=type.value||String(task.task_type_code||'');
      setOptions(assigned,users,previousAssigned,'name','id','Selecione o responsável');
      setOptions(type,types,previousType,'label','code','Selecione o tipo');
      if(!assigned.value&&String(task.assigned_user_id||''))assigned.value=String(task.assigned_user_id);
      if(!type.value&&String(task.task_type_code||''))type.value=String(task.task_type_code);
    };
    const setMode=mode=>{
      currentMode=mode;modeInput.value=mode;agendaTaskModal.dataset.mode=mode;
      const task=payload?.task||{};
      const role=String(payload?.current_role||'');
      const isView=mode==='view',isEdit=mode==='edit',isReschedule=mode==='reschedule',isComplete=mode==='complete';
      const contextLocked=!isEdit||['seller','collector'].includes(role);
      context.disabled=contextLocked;
      type.disabled=!isEdit;
      assigned.disabled=!isEdit;
      due.disabled=!(isEdit||isReschedule);
      description.disabled=isView;
      description.readOnly=isView;
      completion.hidden=!isComplete;
      result.disabled=!isComplete;
      notes.disabled=!isComplete;
      submit.hidden=isView;
      const settings={
        view:['Tarefa completa','<i class="fa-solid fa-eye"></i>','Fechar',''],
        edit:['Editar tarefa completa','<i class="fa-solid fa-pen"></i>','Salvar tarefa','edit'],
        reschedule:['Reagendar tarefa','<i class="fa-regular fa-calendar-plus"></i>','Confirmar reagendamento','reschedule'],
        complete:['Concluir tarefa','<i class="fa-solid fa-check"></i>','Concluir tarefa','complete']
      }[mode]||[];
      heading.textContent=settings[0];icon.innerHTML=settings[1];
      submit.className='tda-btn tda-task-submit '+(settings[3]||'');
      submit.innerHTML=mode==='complete'?'<i class="fa-solid fa-check"></i><span>Concluir tarefa</span>':mode==='reschedule'?'<i class="fa-solid fa-calendar-check"></i><span>Confirmar reagendamento</span>':'<i class="fa-solid fa-floppy-disk"></i><span>Salvar tarefa</span>';
      if(isComplete){
        const results=payload?.results?.[task.context]||[];
        result.innerHTML='<option value="">Somente concluir tarefa</option>'+results.map(item=>'<option value="'+esc(item.code)+'">'+esc(item.label)+'</option>').join('');
        result.value=String(task.completion_result_code||'');
        notes.value=String(task.completion_notes||'');
      }
    };
    const populate=data=>{
      payload=data;const task=data.task||{};
      headClient.textContent=task.client_name||'Cliente';
      headStatus.textContent=task.status_label||'';
      clientName.textContent=task.client_name||'—';
      const city=String(task.client_city||'').trim(),uf=String(task.client_uf||'').trim();
      const cityUpper=city.toUpperCase(),ufUpper=uf.toUpperCase();
      const cityHasUf=city&&uf&&(cityUpper.includes('('+ufUpper+')')||cityUpper.endsWith(' / '+ufUpper)||cityUpper.endsWith(' - '+ufUpper));
      const location=city?(cityHasUf?city:(uf?city+' / '+uf:city)):uf;
      clientMeta.textContent=[task.client_document,location].filter(Boolean).join(' • ')||'Sem dados complementares';
      if(task.context==='collection')clientLink.href=base+'/collection/'+Number(task.client_id||0);
      else if(String(task.crm_account_code||''))clientLink.href=base+'/commercial/accounts/'+encodeURIComponent(String(task.crm_account_code));
      else clientLink.href=base+'/clients/'+Number(task.client_id||0);
      cardContext.textContent=task.context==='collection'?'Cobrança':'Comercial';
      cardType.textContent=task.task_type_label||'Não informado';
      cardAssigned.textContent=task.assigned_name||'Sem responsável';
      cardDue.textContent=task.due_at_label||'Sem prazo';
      created.textContent=task.created_at_label||'—';
      createdBy.textContent=task.created_by_name||'Não registrado';
      createdBy.title=task.created_by_known===false?'A autoria não existia no histórico original desta tarefa.':'';
      statusLabel.textContent=task.status_label||'—';
      updated.textContent=task.updated_at_label||'Sem alteração posterior';
      agendaTaskModal.dataset.taskStatus=String(task.status_tone||task.status||'');
      context.value=task.context||'sales';
      due.value=task.due_at||'';
      description.value=task.title||'';
      renderContextChoices();
      assigned.value=String(task.assigned_user_id||'');
      type.value=String(task.task_type_code||'');
      setMode(currentMode);
    };
    const loading=mode=>{
      currentMode=mode;payload=null;taskId=0;form.reset();
      heading.textContent='Carregando tarefa...';icon.innerHTML='<i class="fa-solid fa-spinner fa-spin"></i>';
      headClient.textContent='Aguarde';headStatus.textContent='';
      clientName.textContent='Carregando...';clientMeta.textContent='Consultando dados completos da tarefa';
      [cardContext,cardType,cardAssigned,cardDue].forEach(el=>{if(el)el.textContent='—';});
      created.textContent=createdBy.textContent=statusLabel.textContent=updated.textContent='—';agendaTaskModal.dataset.taskStatus='';
      [context,type,assigned,due,description,result,notes].forEach(el=>{if(el)el.disabled=true;});
      completion.hidden=true;submit.hidden=true;
    };
    const open=async button=>{
      const id=Number(button.dataset.taskId||0);if(!id)return;
      const mode=String(button.dataset.agendaTaskAction||'view');
      loading(mode);taskId=id;agendaTaskModal.showModal();
      try{
        const response=await fetch(base+'/api/tasks/'+id,{credentials:'same-origin',headers:{Accept:'application/json'}});
        const data=await response.json().catch(()=>({}));if(!response.ok||!data.ok)throw new Error(data.error||'Não foi possível carregar a tarefa.');
        populate(data);
      }catch(error){agendaTaskModal.close();showNotice('danger','Tarefa',error.message||'Não foi possível carregar a tarefa completa.',10000);}
    };
    document.addEventListener('click',event=>{
      const button=event.target.closest?.('[data-agenda-task-action]');if(!button)return;
      event.preventDefault();open(button);
    });
    context.addEventListener('change',()=>{if(payload)renderContextChoices();});
    agendaTaskModal.querySelectorAll('[data-agenda-task-close]').forEach(button=>button.addEventListener('click',()=>agendaTaskModal.close()));
    agendaTaskModal.addEventListener('click',event=>{if(event.target===agendaTaskModal)agendaTaskModal.close();});
    form.addEventListener('submit',async event=>{
      event.preventDefault();if(!payload||!taskId||currentMode==='view')return;
      const original=submit.innerHTML;submit.disabled=true;submit.innerHTML='<i class="fa-solid fa-spinner fa-spin"></i><span>Salvando...</span>';
      try{
        const data=new FormData(form);data.set('mode',currentMode);data.set('context',context.value);data.set('assigned_user_id',assigned.value);data.set('task_type_code',type.value);data.set('due_at',due.value);data.set('title',description.value);
        const url=currentMode==='complete'?base+'/api/tasks/'+taskId+'/complete':base+'/api/tasks/'+taskId;
        const response=await fetch(url,{method:'POST',credentials:'same-origin',headers:{Accept:'application/json'},body:data});
        const body=await response.json().catch(()=>({}));if(!response.ok||!body.ok)throw new Error(body.error||'Não foi possível salvar a tarefa.');
        agendaTaskModal.close();showNotice('success',currentMode==='complete'?'Tarefa concluída':'Tarefa atualizada',body.message||'Operação concluída com sucesso.',8000);
        setTimeout(()=>location.reload(),550);
      }catch(error){showNotice('danger','Não foi possível salvar',error.message||'Revise os dados e tente novamente.',10000);}
      finally{submit.disabled=false;submit.innerHTML=original;}
    });
  }

  const setupCommercialAccountPage=()=>{
    const page=document.querySelector('.tdf-page');if(!page)return;
    const tabs=[...page.querySelectorAll('[data-tdf-tab]')];
    const panels=[...page.querySelectorAll('[data-tdf-panel]')];
    const activate=name=>{
      const target=panels.find(panel=>panel.dataset.tdfPanel===name)||panels[0];if(!target)return;
      const current=target.dataset.tdfPanel;
      tabs.forEach(tab=>{const active=tab.dataset.tdfTab===current;tab.classList.toggle('active',active);tab.setAttribute('aria-selected',active?'true':'false');});
      panels.forEach(panel=>panel.classList.toggle('active',panel===target));
    };
    tabs.forEach(tab=>tab.addEventListener('click',()=>activate(tab.dataset.tdfTab||'overview')));
    page.querySelectorAll('[data-tdf-open-tab]').forEach(button=>button.addEventListener('click',()=>activate(button.dataset.tdfOpenTab||'overview')));

    const dialog=page.querySelector('[data-tdf-classification-dialog]');
    if(dialog){
      page.querySelectorAll('[data-tdf-classification-open]').forEach(button=>button.addEventListener('click',()=>dialog.showModal()));
      dialog.querySelectorAll('[data-tdf-classification-close]').forEach(button=>button.addEventListener('click',()=>dialog.close()));
      dialog.addEventListener('click',event=>{if(event.target===dialog)dialog.close();});
      if(new URLSearchParams(location.search).get('classify')==='1')setTimeout(()=>dialog.showModal(),0);
    }
  };
  setupCommercialAccountPage();

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
      const clientsHubSubpage=['clients-audit','clients-sync'].includes(section)&&target.endsWith('/clients');
      if(target===current||(target!=='/'&&current.startsWith(target+'/'))||clientsHubSubpage)link.classList.add('active');
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
      if(serverUrl){options.processing=true;options.serverSide=true;options.ajax={url:serverUrl,dataSrc:'data',error:xhr=>{let detail='Não foi possível consultar os registros. Tente novamente.';try{const payload=xhr?.responseJSON||JSON.parse(xhr?.responseText||'{}');if(payload?.error)detail=String(payload.error);}catch(e){}showNotice('danger','Erro ao carregar a tabela',detail);}};}
      table._dataTable=new DataTable(table,options);
      return table._dataTable;
  };
  document.querySelectorAll('.table-card table').forEach(initDataTable);

  const clientBulk=document.querySelector('[data-client-bulk]');
  const clientBulkTable=document.querySelector('.clients-datatable');
  if(clientBulk&&clientBulkTable?._dataTable){
    const dt=clientBulkTable._dataTable;
    const selected=new Set();
    const excluded=new Set();
    let allFiltered=false;
    let running=false;
    let lastSearch=String(dt.search?.()||'');
    const countLabel=clientBulk.querySelector('[data-client-selected-count]');
    const selectFilteredButton=clientBulk.querySelector('[data-client-select-filtered]');
    const clearButton=clientBulk.querySelector('[data-client-clear-selection]');
    const progress=clientBulk.querySelector('[data-client-bulk-progress]');
    const progressText=clientBulk.querySelector('[data-client-bulk-progress-text]');
    const sellerSelect=clientBulk.querySelector('[data-client-bulk-seller]');
    const tagOperation=clientBulk.querySelector('[data-client-tag-operation]');
    const tagsInput=clientBulk.querySelector('[data-client-bulk-tags]');
    const pageCheckbox=clientBulkTable.querySelector('[data-client-select-page]');
    const rowChecks=()=>Array.from(clientBulkTable.querySelectorAll('tbody [data-client-select]'));
    const recordsFiltered=()=>Number(dt.page?.info?.().recordsDisplay||0);
    const selectionCount=()=>allFiltered?Math.max(0,recordsFiltered()-excluded.size):selected.size;
    const syncSelection=()=>{
      rowChecks().forEach(check=>{const id=Number(check.value);check.checked=allFiltered?!excluded.has(id):selected.has(id);});
      const checks=rowChecks(),checked=checks.filter(check=>check.checked).length;
      if(pageCheckbox){pageCheckbox.checked=checks.length>0&&checked===checks.length;pageCheckbox.indeterminate=checked>0&&checked<checks.length;}
      const count=selectionCount();
      if(countLabel)countLabel.textContent=count+' selecionado'+(count===1?'':'s');
      if(selectFilteredButton){selectFilteredButton.hidden=allFiltered||recordsFiltered()===0;selectFilteredButton.textContent='Selecionar todos os '+recordsFiltered().toLocaleString('pt-BR')+' resultados filtrados';}
      if(clearButton)clearButton.hidden=count===0;
      clientBulk.querySelectorAll('[data-client-bulk-run]').forEach(button=>button.disabled=running||count===0);
    };
    const clearSelection=()=>{selected.clear();excluded.clear();allFiltered=false;syncSelection();};
    const currentFilters=()=>{
      const url=new URL(clientBulkTable.dataset.serverUrl,location.origin);
      const ufs=Array.from(url.searchParams.entries()).filter(([key])=>key==='ufs'||key.startsWith('ufs[')).map(([,value])=>value);
      const filterTags=Array.from(url.searchParams.entries()).filter(([key])=>key==='tags'||key.startsWith('tags[')).map(([,value])=>value);
      return {segment:url.searchParams.get('segment')||clientBulk.dataset.segment||'general',month:url.searchParams.get('month')||'',ufs:ufs.length?ufs:(url.searchParams.get('uf')?[url.searchParams.get('uf')]:[]),uf:url.searchParams.get('uf')||'',filter_tags:filterTags.length?filterTags:(url.searchParams.get('tag')?[url.searchParams.get('tag')]:[]),seller_filter:url.searchParams.get('seller_filter')||'',ddds:Array.from(url.searchParams.entries()).filter(([key])=>key==='ddds'||key.startsWith('ddds[')).map(([,value])=>value),search:String(dt.search?.()||'').trim()};
    };
    const payloadFor=(action,onlyId=null,cursor=0)=>({
      _token:clientBulk.dataset.csrf,
      action,
      selection_mode:onlyId!==null?'selected':(allFiltered?'filtered':'selected'),
      client_ids:onlyId!==null?[onlyId]:Array.from(selected),
      excluded_ids:onlyId!==null?[]:Array.from(excluded),
      cursor,
      ...currentFilters(),
      change_seller:onlyId===null&&action!=='sync'&&Boolean(sellerSelect&&sellerSelect.value!==''),
      seller_code:onlyId===null&&action!=='sync'?(sellerSelect?.value||''):'',
      tag_operation:onlyId===null&&action!=='sync'?(tagOperation?.value||'none'):'none',
      tags:onlyId===null&&action!=='sync'?(tagsInput?.value||''):'',
    });
    const runBulk=async(action,onlyId=null)=>{
      if(running)return;
      if(onlyId===null&&selectionCount()===0){showNotice('warning','Nenhum cliente selecionado','Marque os clientes que deseja alterar.');return;}
      const changesSeller=Boolean(sellerSelect&&sellerSelect.value!=='');
      const changesTags=(tagOperation?.value||'none')!=='none';
      if(onlyId===null&&action!=='sync'&&!changesSeller&&!changesTags){showNotice('warning','Escolha o que alterar','Defina o vendedor ou uma ação para as tags.');return;}
      if(onlyId===null&&action!=='sync'&&['add','remove'].includes(tagOperation?.value||'')&&!String(tagsInput?.value||'').trim()){showNotice('warning','Informe as tags','Digite pelo menos uma tag para continuar.');return;}
      const targetCount=onlyId!==null?1:selectionCount();
      const sellerLabel=changesSeller?sellerSelect?.selectedOptions?.[0]?.textContent?.trim()||'vendedor selecionado':'';
      let confirmation='';
      if(onlyId!==null)confirmation='Atualizar este cliente na Omie agora?';
      else if(action==='apply_sync')confirmation='Aplicar '+targetCount.toLocaleString('pt-BR')+' alteração(ões) no CRM'+(changesSeller?' para '+sellerLabel:'')+' e sincronizar a Omie agora?';
      else if(action==='sync')confirmation='Sincronizar com a Omie os '+targetCount.toLocaleString('pt-BR')+' cliente(s) selecionado(s) que já possuem alterações pendentes no CRM?';
      else confirmation='Aplicar no CRM '+targetCount.toLocaleString('pt-BR')+' alteração(ões)'+(changesSeller?' para '+sellerLabel:'')+' e deixar a Omie pendente para sincronização posterior?';
      if(!window.confirm(confirmation))return;
      running=true;syncSelection();if(progress)progress.hidden=false;
      let cursor=0,processed=0,succeeded=0,failed=0,errors=[],loops=0;
      try{
        do{
          if(progressText)progressText.textContent=(onlyId!==null?'Atualizando cliente na Omie...':'Processando '+processed.toLocaleString('pt-BR')+' de '+targetCount.toLocaleString('pt-BR')+'...');
          const response=await fetch(clientBulk.dataset.endpoint,{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json'},body:JSON.stringify(payloadFor(action,onlyId,cursor))});
          const data=await response.json().catch(()=>({success:false,error:'Resposta inválida do servidor.'}));
          if(!response.ok||!data.success)throw new Error(data.error||'Não foi possível concluir a operação.');
          processed+=Number(data.processed||0);succeeded+=Number(data.succeeded||0);failed+=Number(data.failed||0);errors=errors.concat(Array.isArray(data.errors)?data.errors:[]).slice(0,20);cursor=Number(data.next_cursor||0);loops++;
          if(data.done)break;
          if(loops>10000)throw new Error('A operação excedeu o limite seguro de lotes.');
        }while(true);
        clearSelection();dt.ajax?.reload?.(null,false);
        const detail=failed?failed+' cadastro(s) não concluído(s). '+errors.slice(0,3).map(item=>item.name+': '+item.message).join(' | '):'Todos os cadastros selecionados foram concluídos.';
        showNotice(failed?'warning':'success',succeeded.toLocaleString('pt-BR')+' cliente(s) processado(s)',detail);
      }catch(error){showNotice('danger','Operação interrompida',error.message||'Tente novamente.');}
      finally{running=false;if(progress)progress.hidden=true;syncSelection();}
    };
    clientBulkTable.addEventListener('change',event=>{
      const check=event.target.closest?.('[data-client-select]');
      if(check){const id=Number(check.value);if(allFiltered){if(check.checked)excluded.delete(id);else excluded.add(id);}else{if(check.checked)selected.add(id);else selected.delete(id);}syncSelection();return;}
      if(event.target.closest?.('[data-client-select-page]')){rowChecks().forEach(row=>{row.checked=event.target.checked;row.dispatchEvent(new Event('change',{bubbles:true}));});syncSelection();}
    });
    selectFilteredButton?.addEventListener('click',()=>{selected.clear();excluded.clear();allFiltered=true;syncSelection();});
    clearButton?.addEventListener('click',clearSelection);
    clientBulk.querySelectorAll('[data-client-bulk-run]').forEach(button=>button.addEventListener('click',()=>runBulk(button.dataset.clientBulkRun)));
    clientBulkTable.addEventListener('click',event=>{const button=event.target.closest?.('[data-client-omie-one]');if(button)runBulk('sync',Number(button.dataset.clientOmieOne));});
    dt.on?.('draw',syncSelection);
    dt.on?.('search',()=>{const next=String(dt.search?.()||'');if(next!==lastSearch){lastSearch=next;clearSelection();}});
    syncSelection();
  }
  const clientSyncHub=document.querySelector('[data-client-sync-hub]');
  if(clientSyncHub){
    const selected=new Set(),excluded=new Set();let allFiltered=false,running=false;
    const endpoint=clientSyncHub.dataset.endpoint,csrf=clientSyncHub.dataset.csrf,status=clientSyncHub.dataset.status||'all',query=clientSyncHub.dataset.query||'';
    const total=()=>Number(clientSyncHub.dataset.total||0);
    const countLabel=clientSyncHub.querySelector('[data-sync-count]');
    const selectFiltered=clientSyncHub.querySelector('[data-sync-select-filtered]');
    const clearButton=clientSyncHub.querySelector('[data-sync-clear]');
    const runButton=clientSyncHub.querySelector('[data-sync-run]');
    const progress=clientSyncHub.querySelector('[data-sync-progress]');
    const progressText=clientSyncHub.querySelector('[data-sync-progress-text]');
    const pageCheck=clientSyncHub.querySelector('[data-sync-page]');
    const rowChecks=()=>Array.from(clientSyncHub.querySelectorAll('[data-sync-select]'));
    const selectedCount=()=>allFiltered?Math.max(0,total()-excluded.size):selected.size;
    const paint=()=>{
      rowChecks().forEach(check=>{const id=Number(check.value);check.checked=allFiltered?!excluded.has(id):selected.has(id);});
      const rows=rowChecks(),checked=rows.filter(check=>check.checked).length;
      if(pageCheck){pageCheck.checked=rows.length>0&&checked===rows.length;pageCheck.indeterminate=checked>0&&checked<rows.length;}
      const count=selectedCount();if(countLabel)countLabel.textContent=count.toLocaleString('pt-BR')+' selecionado'+(count===1?'':'s');
      if(selectFiltered)selectFiltered.hidden=allFiltered||total()===0;
      if(clearButton)clearButton.hidden=count===0;
      if(runButton)runButton.disabled=running||count===0;
      clientSyncHub.querySelectorAll('[data-sync-one]').forEach(button=>button.disabled=running);
    };
    const clear=()=>{selected.clear();excluded.clear();allFiltered=false;paint();};
    rowChecks().forEach(check=>check.addEventListener('change',()=>{const id=Number(check.value);if(allFiltered){if(check.checked)excluded.delete(id);else excluded.add(id);}else{if(check.checked)selected.add(id);else selected.delete(id);}paint();}));
    pageCheck?.addEventListener('change',()=>{rowChecks().forEach(check=>{check.checked=pageCheck.checked;check.dispatchEvent(new Event('change'));});paint();});
    selectFiltered?.addEventListener('click',()=>{selected.clear();excluded.clear();allFiltered=true;paint();});
    clearButton?.addEventListener('click',clear);
    const execute=async(onlyId=null)=>{
      if(running)return;const target=onlyId!==null?1:selectedCount();if(target===0)return;
      const message=onlyId!==null?'Sincronizar este cliente com a Omie agora?':'Enviar '+target.toLocaleString('pt-BR')+' cliente(s) para a Omie agora? Os registros com erro continuarão nesta fila.';
      if(!window.confirm(message))return;
      running=true;paint();if(progress)progress.hidden=false;let cursor=0,processed=0,succeeded=0,failed=0,errors=[],loops=0;
      try{
        do{
          if(progressText)progressText.textContent='Processando '+processed.toLocaleString('pt-BR')+' de '+target.toLocaleString('pt-BR')+'...';
          const payload={_token:csrf,selection_mode:onlyId!==null?'selected':(allFiltered?'filtered':'selected'),client_ids:onlyId!==null?[onlyId]:Array.from(selected),excluded_ids:onlyId!==null?[]:Array.from(excluded),status,q:query,cursor};
          const response=await fetch(endpoint,{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json'},body:JSON.stringify(payload)});
          const data=await response.json().catch(()=>({success:false,error:'Resposta inválida do servidor.'}));
          if(!response.ok||!data.success)throw new Error(data.error||'Não foi possível concluir a sincronização.');
          processed+=Number(data.processed||0);succeeded+=Number(data.succeeded||0);failed+=Number(data.failed||0);errors=errors.concat(Array.isArray(data.errors)?data.errors:[]).slice(0,20);cursor=Number(data.next_cursor||0);loops++;
          if(data.done||onlyId!==null)break;if(loops>10000)throw new Error('A operação excedeu o limite seguro de lotes.');
        }while(true);
        const detail=failed?failed+' cliente(s) permaneceram para tratamento. '+errors.slice(0,3).map(item=>item.name+': '+item.message).join(' | '):'Todos os clientes enviados foram sincronizados.';
        showNotice(failed?'warning':'success',succeeded.toLocaleString('pt-BR')+' sincronizado(s)',detail);
        window.setTimeout(()=>location.reload(),700);
      }catch(error){showNotice('danger','Sincronização interrompida',error.message||'Tente novamente.');}
      finally{running=false;if(progress)progress.hidden=true;paint();}
    };
    runButton?.addEventListener('click',()=>execute(null));
    clientSyncHub.querySelectorAll('[data-sync-one]').forEach(button=>button.addEventListener('click',()=>execute(Number(button.dataset.syncOne))));
    paint();
  }

  document.querySelectorAll('[data-datatable-container]').forEach(container=>container.addEventListener('toggle',()=>{
    if(!container.open)return;
    container.querySelectorAll('.table-card table').forEach(table=>{
      const instance=initDataTable(table)||table._dataTable;
      setTimeout(()=>{try{instance?.columns?.adjust();}catch(e){}},0);
    });
  }));

  const sidebar=document.querySelector('.tdcrm-sidebar');
  const mainShell=document.querySelector('.tdcrm-main');
  const menuToggle=document.querySelector('[data-menu]');
  const menuBackdrop=document.querySelector('[data-menu-backdrop]');
  const sidebarStorageKey='tecnodata-sidebar-collapsed-v2';
  const sidebarMedia=window.matchMedia('(max-width:900px)');
  const isMobileMenu=()=>sidebarMedia.matches;
  let tableAdjustTimer=0;
  let tableAdjustLateTimer=0;

  const adjustVisibleTables=()=>{
    window.clearTimeout(tableAdjustTimer);
    window.clearTimeout(tableAdjustLateTimer);
    const adjust=()=>document.querySelectorAll('.table-card table').forEach(table=>{
      try{table._dataTable?.columns?.adjust();}catch(e){}
    });
    requestAnimationFrame(adjust);
    tableAdjustTimer=window.setTimeout(adjust,190);
    tableAdjustLateTimer=window.setTimeout(adjust,360);
  };

  const syncMenuButton=()=>{
    if(!menuToggle)return;
    const mobile=isMobileMenu();
    const open=mobile?sidebar?.classList.contains('open'):!document.body.classList.contains('sidebar-collapsed');
    menuToggle.setAttribute('aria-expanded',open?'true':'false');
    menuToggle.setAttribute('title',mobile?(open?'Fechar menu':'Abrir menu'):(open?'Recolher menu':'Expandir menu'));
    menuToggle.setAttribute('aria-label',mobile?(open?'Fechar menu':'Abrir menu'):(open?'Recolher menu lateral':'Expandir menu lateral'));
  };

  const setMobileSidebar=open=>{
    if(!sidebar)return;
    document.body.classList.remove('sidebar-collapsed');
    sidebar.classList.toggle('open',open);
    document.body.classList.toggle('menu-open',open);
    syncMenuButton();
    adjustVisibleTables();
  };

  const setDesktopCollapsed=(collapsed,persist=true)=>{
    if(!sidebar)return;
    sidebar.classList.remove('open');
    document.body.classList.remove('menu-open');
    document.body.classList.toggle('sidebar-collapsed',collapsed);
    if(persist){
      try{localStorage.setItem(sidebarStorageKey,collapsed?'1':'0');}catch(e){}
    }
    syncMenuButton();
    adjustVisibleTables();
  };

  const restoreSidebarState=()=>{
    if(!sidebar||!menuToggle)return;
    if(isMobileMenu()){
      setMobileSidebar(false);
      return;
    }
    let collapsed=false;
    try{
      const saved=localStorage.getItem(sidebarStorageKey);
      if(saved===null){
        const legacy=localStorage.getItem('tecnodata-sidebar-collapsed');
        collapsed=legacy==='1';
      }else collapsed=saved==='1';
    }catch(e){}
    setDesktopCollapsed(collapsed,false);
  };

  if(sidebar&&menuToggle){
    restoreSidebarState();

    menuToggle.addEventListener('click',()=>{
      if(isMobileMenu())setMobileSidebar(!sidebar.classList.contains('open'));
      else setDesktopCollapsed(!document.body.classList.contains('sidebar-collapsed'));
    });

    document.querySelector('[data-menu-close]')?.addEventListener('click',()=>setMobileSidebar(false));
    menuBackdrop?.addEventListener('click',()=>setMobileSidebar(false));

    sidebar.querySelectorAll('nav a').forEach(link=>link.addEventListener('click',()=>{
      if(isMobileMenu())setMobileSidebar(false);
    }));

    document.addEventListener('keydown',event=>{
      if(event.key==='Escape'&&isMobileMenu()&&sidebar.classList.contains('open'))setMobileSidebar(false);
    });

    const onSidebarBreakpointChange=()=>restoreSidebarState();
    if(typeof sidebarMedia.addEventListener==='function')sidebarMedia.addEventListener('change',onSidebarBreakpointChange);
    else sidebarMedia.addListener?.(onSidebarBreakpointChange);

    mainShell?.addEventListener('transitionend',event=>{
      if(event.propertyName==='margin-left'||event.propertyName==='width')adjustVisibleTables();
    });
    sidebar.addEventListener('transitionend',event=>{
      if(event.propertyName==='width'||event.propertyName==='transform')adjustVisibleTables();
    });
  }
  const ensureConfirmModal=()=>{
    let modal=document.getElementById('appConfirmModal');
    if(modal)return modal;
    modal=document.createElement('div');
    modal.id='appConfirmModal';
    modal.className='app-confirm-backdrop';
    modal.innerHTML='<div class="app-confirm-card" role="dialog" aria-modal="true" aria-labelledby="appConfirmTitle"><div class="app-confirm-icon" data-confirm-icon><i class="fa-solid fa-triangle-exclamation"></i></div><div class="app-confirm-copy"><strong id="appConfirmTitle" data-confirm-title>Confirmar operação</strong><span data-confirm-message></span></div><div class="app-confirm-actions"><button type="button" class="btn btn-outline-secondary" data-confirm-cancel>Cancelar</button><button type="button" class="btn btn-danger app-confirm-ok" data-confirm-ok>Confirmar</button></div></div>';
    document.body.appendChild(modal);
    return modal;
  };
  const askConfirm=(message,options={})=>new Promise(resolve=>{
    const modal=ensureConfirmModal();
    const msg=modal.querySelector('[data-confirm-message]');
    const title=modal.querySelector('[data-confirm-title]');
    const icon=modal.querySelector('[data-confirm-icon]');
    const ok=modal.querySelector('[data-confirm-ok]');
    const cancel=modal.querySelector('[data-confirm-cancel]');
    const tone=['success','danger','warning'].includes(options.tone)?options.tone:'warning';
    modal.dataset.tone=tone;
    title.textContent=options.title||'Confirmar operação';
    msg.textContent=message||'Confirmar operação?';
    ok.textContent=options.label||'Confirmar';
    ok.className='btn app-confirm-ok '+(tone==='success'?'btn-success':tone==='danger'?'btn-danger':'btn-warning');
    icon.innerHTML=tone==='success'?'<i class="fa-solid fa-check"></i>':tone==='danger'?'<i class="fa-regular fa-trash-can"></i>':'<i class="fa-solid fa-triangle-exclamation"></i>';
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
    if(await askConfirm(el.dataset.confirm||'Confirmar operação?',{
      title:el.dataset.confirmTitle||'Confirmar operação',
      label:el.dataset.confirmLabel||'Confirmar',
      tone:el.dataset.confirmTone||'warning'
    })){
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


  const singleClientSync=document.querySelector('[data-sync-one-client]');
  if(singleClientSync){
    const input=singleClientSync.querySelector('[data-sync-one-value]');
    const submit=singleClientSync.querySelector('[data-sync-one-submit]');
    const result=singleClientSync.querySelector('[data-sync-one-result]');
    const esc=value=>{const node=document.createElement('div');node.textContent=String(value??'');return node.innerHTML;};
    const setResult=(type,html)=>{
      if(!result)return;
      result.hidden=false;
      result.className='tdsync2-single-result '+type;
      result.innerHTML=html;
    };
    const run=async value=>{
      value=String(value??input?.value??'').trim();
      if(!value){setResult('error','<strong>Informe o código Omie ou CPF/CNPJ.</strong>');input?.focus();return;}
      if(input)input.value=value;
      const previous=submit?.innerHTML||'';
      if(submit){submit.disabled=true;submit.innerHTML='<i class="fa-solid fa-circle-notch fa-spin"></i>Consultando...';}
      setResult('loading','<i class="fa-solid fa-circle-notch fa-spin"></i><span>Consultando somente este cliente na Omie...</span>');
      try{
        const body=new URLSearchParams({_token:String(window.CSRF||''),value});
        const response=await fetch((window.APP_URL||'')+'/api/sync/client-one',{
          method:'POST',
          headers:{'Accept':'application/json','Content-Type':'application/x-www-form-urlencoded;charset=UTF-8'},
          credentials:'same-origin',
          body:body.toString()
        });
        const data=await response.json().catch(()=>({ok:false,error:'Resposta inválida do servidor.'}));
        if(!response.ok||!data.ok)throw new Error(data.error||'Não foi possível sincronizar este cliente.');
        if(data.requires_choice){
          const candidates=Array.isArray(data.candidates)?data.candidates:[];
          const cards=candidates.map(item=>'<button type="button" data-sync-one-code="'+esc(item.omie_code)+'"><span><strong>'+esc(item.name||'Cliente')+'</strong><small>Omie '+esc(item.omie_code||'—')+(item.inactive?' · inativo':' · ativo')+'</small></span><i class="fa-solid fa-arrow-right"></i></button>').join('');
          setResult('choice','<div class="tdsync2-single-result-head"><strong>'+esc(data.message||'Escolha o cadastro exato.')+'</strong><small>Nenhum desses cadastros foi alterado ainda.</small></div><div class="tdsync2-single-candidates">'+cards+'</div>');
          return;
        }
        const client=data.client||{};
        setResult(data.inactive?'warning':'success','<div class="tdsync2-single-result-head"><strong>'+esc(data.message||'Cliente sincronizado.')+'</strong><small>'+esc(client.name||'Cliente')+' · Omie '+esc(client.omie_code||'—')+(client.document?' · '+esc(client.document):'')+'</small></div>');
        showNotice(data.inactive?'warning':'success',data.inactive?'Cliente inativo':'Cliente sincronizado',data.message||'Sincronização pontual concluída.');
      }catch(error){
        setResult('error','<strong>'+esc(error.message||'Não foi possível sincronizar este cliente.')+'</strong>');
        showNotice('danger','Sincronização pontual não concluída',error.message||'Tente novamente.');
      }finally{
        if(submit){submit.disabled=false;submit.innerHTML=previous;}
      }
    };
    submit?.addEventListener('click',()=>run());
    input?.addEventListener('keydown',event=>{if(event.key==='Enter'){event.preventDefault();run();}});
    result?.addEventListener('click',event=>{
      const button=event.target.closest?.('[data-sync-one-code]');
      if(!button)return;
      run(button.dataset.syncOneCode||'');
    });
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
      const labels={sync:'Sincronizando',period:'Sincronizando período',catchup:'Atualizando lacuna',last5:'Buscando últimos 5 dias',resume:'Retomando',reset:'Zerando estado',full:'Executando carga completa',reconcile_clients:'Reconciliando todos os clientes',product_obs:'Carregando descrições e observações'};
      let page=action==='resume'?0:1;
      let iterations=0;
      const maxIterations=2000;
      setCardBusy(card,true);
      setSyncAlert(card,'info',labels[action]||'Processando','A operação foi iniciada. Aguarde o processamento deste módulo.');
      try{
        while(true){
          iterations++;
          if(iterations>maxIterations)throw new Error('A sincronização ultrapassou o limite seguro de '+maxIterations+' páginas e foi interrompida.');
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
            const count=Number(data.processed??data.count??0);
            const countLabel=data.processed!==undefined?' durante esta atualização.':' no último lote.';
            setSyncAlert(card,'success',data.validation?.ok?'Reconciliação validada':'Sincronização concluída',data.message||(count+' registro(s) processado(s)'+countLabel+' O estado do módulo foi atualizado.'));
            setCardBusy(card,false);
            setTimeout(()=>location.reload(),850);
            return;
          }
          const nextPage=responsePage+1;
          if(nextPage<=page)throw new Error('A Omie repetiu a mesma página. A sincronização foi interrompida com segurança.');
          page=nextPage;
          await new Promise(resolve=>setTimeout(resolve,900));
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

  const commercialLinkDialog=document.querySelector('[data-commercial-client-link-dialog]');
  if(commercialLinkDialog){
    const openButton=document.querySelector('[data-commercial-client-link-open]');
    const closeButtons=commercialLinkDialog.querySelectorAll('[data-commercial-client-link-close]');
    const linkForm=commercialLinkDialog.querySelector('[data-commercial-client-link-form]');
    const searchInput=commercialLinkDialog.querySelector('[data-commercial-client-link-search]');
    const results=commercialLinkDialog.querySelector('[data-commercial-client-link-results]');
    const selected=commercialLinkDialog.querySelector('[data-commercial-client-link-selected]');
    const clientId=commercialLinkDialog.querySelector('[data-commercial-client-link-id]');
    const escapeLink=value=>{const node=document.createElement('div');node.textContent=String(value??'');return node.innerHTML;};
    let linkTimer;
    const chooseClient=client=>{
      clientId.value=String(client.id||'');
      selected.hidden=false;
      selected.innerHTML='<span><small>CLIENTE GERAL SELECIONADO</small><strong>'+escapeLink(client.name)+'</strong><em>'+escapeLink([client.omie_code,client.document,client.city,client.uf].filter(Boolean).join(' • '))+'</em></span><button type="button" title="Trocar cliente"><i class="fa-solid fa-rotate"></i></button>';
      results.innerHTML='';searchInput.value='';
      selected.querySelector('button')?.addEventListener('click',()=>{clientId.value='';selected.hidden=true;selected.innerHTML='';searchInput.focus();});
    };
    const searchClients=async()=>{
      const query=searchInput.value.trim();if(query.length<2){results.innerHTML='';return;}
      try{
        const accountCode=String(commercialLinkDialog.dataset.commercialLinkAccount||'').trim();
        const endpoint=(window.APP_URL||'')+'/api/commercial/accounts/'+encodeURIComponent(accountCode)+'/link-candidates?q='+encodeURIComponent(query);
        const response=await fetch(endpoint,{credentials:'same-origin'});
        const data=await response.json().catch(()=>({error:'Resposta inválida do servidor.'}));
        if(!response.ok)throw new Error(data.error||data.message||'Não foi possível buscar os Clientes Gerais.');
        results.innerHTML=(data.items||[]).map(client=>'<button type="button" data-commercial-link-client="'+encodeURIComponent(JSON.stringify(client))+'"><span><strong>'+escapeLink(client.name)+'</strong><small>'+escapeLink([client.omie_code,client.document,client.city,client.uf].filter(Boolean).join(' • '))+'</small></span><i class="fa-solid fa-plus"></i></button>').join('')||'<p>'+escapeLink(data.message||'Nenhum Cliente Geral encontrado.')+'</p>';
        results.querySelectorAll('[data-commercial-link-client]').forEach(button=>button.addEventListener('click',()=>chooseClient(JSON.parse(decodeURIComponent(button.dataset.commercialLinkClient)))));
      }catch(error){results.innerHTML='<p>'+escapeLink(error.message||'Erro ao buscar clientes.')+'</p>';}
    };
    openButton?.addEventListener('click',()=>{commercialLinkDialog.showModal();searchInput.focus();});
    if(new URLSearchParams(location.search).get('link')==='1')setTimeout(()=>{commercialLinkDialog.showModal();searchInput.focus();},0);
    closeButtons.forEach(button=>button.addEventListener('click',()=>commercialLinkDialog.close()));
    searchInput.addEventListener('input',()=>{clearTimeout(linkTimer);linkTimer=setTimeout(searchClients,250);});
    linkForm.addEventListener('submit',event=>{if(!clientId.value){event.preventDefault();showNotice('warning','Selecione o Cliente Geral','Busque e escolha o cadastro da Omie que será usado por vendas e cobrança.');searchInput.focus();}});
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
  const freightValueInput=document.getElementById('orderFreightValue'),freightVolumes=document.getElementById('orderVolumes'),freightMode=document.getElementById('orderFreightMode'),carrierSelect=document.getElementById('orderCarrier'),orderNotes=document.getElementById('orderNotes');
  const freightQuoteButton=document.getElementById('openFreightQuote'),freightQuoteModal=document.getElementById('freightQuoteModal'),freightQuoteClose=document.getElementById('closeFreightQuote'),freightQuoteResults=document.getElementById('freightQuoteResults'),freightQuoteContext=document.getElementById('freightQuoteContext'),freightQuoteProviders=document.getElementById('freightQuoteProviders'),freightSelectedSummary=document.getElementById('freightSelectedSummary');

  let items=Array.isArray(window.ORDER_OLD_ITEMS)?window.ORDER_OLD_ITEMS:[];
  let newItemRowOpen=items.length===0;
  const selectedOrderItems=new Set();let itemUidSequence=0;
  let installmentData=[];let customInstallmentMode=customInstallments?.value==='S';let installmentTargetCents=0;
  try{const parsed=JSON.parse(installmentsJson?.value||'[]');if(Array.isArray(parsed))installmentData=parsed;}catch(e){}
  if(!installmentData.length)customInstallmentMode=false;
  let clientTimer,productTimer,currentCommercialTotal=0;

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

  const decimalValue=value=>{
    const raw=String(value??'').trim();
    if(!raw)return 0;
    return Number(raw.includes(',')?raw.replace(/\./g,'').replace(',','.'):raw)||0;
  };
  const formatDecimal=value=>Number(value||0).toFixed(2).replace('.',',');
  const deadlineLabel=value=>{const text=String(value??'').trim();return /^\d+$/.test(text)?text+' dia(s)':text;};
  const deliveryDateLabel=value=>{const text=String(value??'').trim();if(!text)return '';const date=new Date(text);return Number.isNaN(date.getTime())?'':date.toLocaleDateString('pt-BR');};
  const normalizeCarrier=value=>String(value??'').normalize('NFD').replace(/[\u0300-\u036f]/g,'').replace(/[^a-z0-9]/gi,'').toLowerCase();
  function quoteCarrierCode(quote){
    if(quote.omie_carrier_code&&Array.from(carrierSelect?.options||[]).some(option=>option.value===String(quote.omie_carrier_code)))return String(quote.omie_carrier_code);
    const tokens=[quote.label,quote.carrier].map(normalizeCarrier).filter(token=>token.length>=3);
    const option=Array.from(carrierSelect?.options||[]).find(candidate=>{
      const name=normalizeCarrier(candidate.textContent);return candidate.value&&tokens.some(token=>name.includes(token)||token.includes(name));
    });
    return option?.value||'';
  }
  function freightObservation(quote){
    const details=[
      'Transportadora: '+String(quote.label||quote.carrier||'Não informada'),
      'Valor: '+money(quote.value),
      quote.deadline?'Prazo: '+deadlineLabel(quote.deadline):'',
      quote.service?'Serviço: '+String(quote.service):'',
      quote.delivery_date?'Previsão: '+deliveryDateLabel(quote.delivery_date):'',
      quote.quote_id?'Cotação: '+String(quote.quote_id):'',
      'CEP: '+String(quote.destination_zip||''),
      'Peso: '+Number(quote.weight||0).toLocaleString('pt-BR',{maximumFractionDigits:3})+' kg',
      'Volumes: '+String(quote.volumes||1)
    ].filter(Boolean);
    return '[COTAÇÃO DE FRETE] '+details.join(' | ');
  }
  function applyFreightQuote(quote){
    const carrierCode=quoteCarrierCode(quote);
    if(!carrierCode){showNotice('warning','Transportadora não configurada','A cotação foi retornada por '+String(quote.label||quote.carrier)+', mas ela não está habilitada nas configurações do pedido.');return;}
    carrierSelect.value=carrierCode;
    freightValueInput.value=formatDecimal(quote.value);
    if(freightMode.value==='9')freightMode.value='0';
    if(orderNotes){
      const line=freightObservation(quote),current=orderNotes.value.replace(/^\[COTAÇÃO DE FRETE\].*(?:\r?\n)?/m,'').trim();
      orderNotes.value=(current?current+'\n\n':'')+line;
    }
    if(freightSelectedSummary)freightSelectedSummary.textContent=String(quote.label||quote.carrier)+' • '+money(quote.value)+(quote.deadline?' • '+deadlineLabel(quote.deadline):'');
    customInstallmentMode=false;installmentData=[];render();freightQuoteModal?.close();
    showNotice('success','Frete selecionado','Transportadora, valor, prazo e observação foram atualizados no pedido.');
  }
  function renderFreightQuotes(data){
    const quotes=(data.items||[]).map(quote=>({...quote,destination_zip:data.destination_zip,weight:data.weight,volumes:data.volumes}));
    const available=quotes.filter(quote=>quote.success&&Number.isFinite(Number(quote.value))).sort((a,b)=>Number(a.value)-Number(b.value));
    available.forEach((quote,index)=>quote.best=index===0);
    if(freightQuoteProviders){
      const providers=data.providers||{};
      freightQuoteProviders.innerHTML=Object.values(providers).map(provider=>'<span class="'+esc(provider.status||'disabled')+'"><i class="fa-solid fa-truck"></i><b>Transportadoras</b><em>'+esc(provider.status==='ok'?'Consultado':provider.message||'Indisponível')+'</em></span>').join('');
    }
    if(!available.length){
      const errors=quotes.map(quote=>quote.message).filter(Boolean).slice(0,3).join(' • ');
      freightQuoteResults.innerHTML='<div class="freight-quote-error"><i class="fa-solid fa-triangle-exclamation"></i><span>'+esc(errors||'Nenhuma transportadora retornou uma cotação válida.')+'</span></div>';return;
    }
    freightQuoteResults.innerHTML=available.map((quote,index)=>{
      const carrierCode=quoteCarrierCode(quote),carrierHint=carrierCode?'Disponível no pedido':'Cadastre esta transportadora nas configurações',delivery=deliveryDateLabel(quote.delivery_date);
      return '<article class="freight-quote-row '+(quote.best?'best ':'')+'" data-quote="'+index+'"><div class="quote-carrier"><span><i class="fa-solid fa-truck-fast"></i></span><div>'+(quote.best?'<small class="freight-quote-badge">MELHOR VALOR</small>':'')+'<strong>'+esc(quote.label||quote.carrier)+'</strong><small>'+esc((quote.service?quote.service+' • ':'')+carrierHint)+'</small></div></div><div class="quote-value"><small>Valor final</small><strong>'+money(quote.value)+'</strong></div><div class="quote-deadline"><small>Prazo estimado</small><strong>'+esc(quote.deadline?deadlineLabel(quote.deadline):'—')+'</strong>'+(delivery?'<em>até '+esc(delivery)+'</em>':'')+'</div><button type="button" '+(carrierCode?'':'disabled')+'><i class="fa-solid fa-check"></i> Escolher</button></article>';
    }).join('');
    freightQuoteResults.querySelectorAll('[data-quote]').forEach(row=>row.querySelector('button')?.addEventListener('click',()=>applyFreightQuote(available[Number(row.dataset.quote)])));
  }
  async function calculateFreight(){
    if(!clientId.value){showNotice('warning','Selecione o cliente','O destino do frete será obtido no cadastro do cliente.');clientSearch?.focus();return;}
    const weight=decimalValue(grossWeightInput?.value)||decimalValue(netWeightInput?.value),volumes=Math.max(1,Number(freightVolumes?.value||1));
    if(currentCommercialTotal<=0){showNotice('warning','Pedido sem valor','Inclua ao menos um produto antes de calcular o frete.');return;}
    if(weight<=0){showNotice('warning','Peso não informado','Informe o peso bruto ou líquido antes de calcular o frete.');grossWeightInput?.focus();return;}
    freightQuoteModal?.showModal();freightQuoteButton.disabled=true;
    freightQuoteContext.innerHTML='<span><i class="fa-solid fa-box"></i> '+money(currentCommercialTotal)+'</span><span><i class="fa-solid fa-weight-hanging"></i> '+weight.toLocaleString('pt-BR',{maximumFractionDigits:3})+' kg</span><span><i class="fa-solid fa-cubes"></i> '+volumes+' volume(s)</span>';
    freightQuoteResults.innerHTML='<div class="freight-quote-empty"><i class="fa-solid fa-spinner fa-spin"></i><span>Consultando transportadoras…</span></div>';
    if(freightQuoteProviders)freightQuoteProviders.innerHTML='<span class="loading"><i class="fa-solid fa-spinner fa-spin"></i><b>Consultando opções disponíveis</b></span>';
    try{
      const body=new URLSearchParams({_token:window.CSRF,client_id:String(clientId.value),value:String(currentCommercialTotal),weight:String(weight),volumes:String(volumes),freight_mode:String(freightMode?.value||'0')});
      const response=await fetch(window.APP_URL+'/api/freight/quote',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded','Accept':'application/json'},credentials:'same-origin',body});
      const data=await response.json().catch(()=>({ok:false,error:'A API de fretes retornou uma resposta inválida.'}));
      if(!response.ok||!data.ok)throw new Error(data.error||'Não foi possível calcular o frete.');
      renderFreightQuotes(data);
      freightQuoteContext.innerHTML+='<span><i class="fa-solid fa-location-dot"></i> CEP '+esc(data.destination_zip||'')+'</span>';
    }catch(error){freightQuoteResults.innerHTML='<div class="freight-quote-error"><i class="fa-solid fa-triangle-exclamation"></i><span>'+esc(error.message||'Falha ao calcular o frete.')+'</span></div>';}
    finally{freightQuoteButton.disabled=false;}
  }
  freightQuoteButton?.addEventListener('click',calculateFreight);
  freightQuoteClose?.addEventListener('click',()=>freightQuoteModal?.close());
  freightQuoteModal?.addEventListener('click',event=>{if(event.target===freightQuoteModal)freightQuoteModal.close();});
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
          '<div class="item-buttons"><button type="button" class="btn btn-light product-detail-trigger" data-product-details="'+item.product_id+'" title="Ver detalhes do produto" aria-label="Ver detalhes do produto"><i class="fa-solid fa-magnifying-glass"></i></button><button type="button" class="btn btn-light add-order-item" title="Incluir novo item" aria-label="Incluir novo item"><i class="fa-solid fa-plus"></i></button><button type="button" class="btn btn-light duplicate" title="Duplicar"><i class="fa-regular fa-copy"></i></button><button type="button" class="btn btn-light remove" title="Remover"><i class="fa-solid fa-trash"></i></button></div>'+
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
    const freightValue=Math.max(0,decimalValue(freightValueInput?.value));
    currentCommercialTotal=commercial;
    grandTotal.textContent=money(gross);
    if(discountTotal)discountTotal.textContent=money(discount);
    if(orderGrandTotal)orderGrandTotal.textContent=money(commercial+freightValue);
    financialTotal.textContent=money(financial+freightValue);
    fiscalTotal.textContent=money(fiscal+freightValue);
    if(netWeightInput&&netWeightInput.dataset.manual!=='1')netWeightInput.value=netWeight>0?netWeight.toFixed(3).replace('.',','):'';
    if(grossWeightInput&&grossWeightInput.dataset.manual!=='1')grossWeightInput.value=grossWeight>0?grossWeight.toFixed(3).replace('.',','):'';
    itemsJson.value=JSON.stringify(items);
    renderInstallments(financial+freightValue);
  }

  [netWeightInput,grossWeightInput].forEach(input=>input?.addEventListener('change',()=>{input.dataset.manual=input.value.trim()===''?'0':'1';render();}));
  freightValueInput?.addEventListener('change',()=>{customInstallmentMode=false;installmentData=[];render();});
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


/* GLOBAL CLIENT QUICK VIEW */
(()=>{
 const modal=document.querySelector('[data-client-quick-modal]');
 const activityModal=document.querySelector('[data-client-quick-activity-modal]');
 if(!modal)return;
 const base=String(window.APP_URL||'').replace(/\/$/,'');
 const basePath=(()=>{try{return new URL(base||location.origin,location.origin).pathname.replace(/\/$/,'');}catch(e){return '';}})();
 const q=(sel,root=modal)=>root.querySelector(sel);
 const qa=(sel,root=modal)=>[...root.querySelectorAll(sel)];
 const esc=value=>{const node=document.createElement('div');node.textContent=String(value??'');return node.innerHTML;};
 const digits=value=>String(value||'').replace(/\D+/g,'');
 const notify=(tone,title,message)=>window.appNotify?.(tone,title,message,9000);
 const dateLabel=value=>{if(!value)return '—';const d=new Date(String(value).replace(' ','T'));if(Number.isNaN(d.getTime()))return '—';return d.toLocaleDateString('pt-BR')+(String(value).includes(':')?' · '+d.toLocaleTimeString('pt-BR',{hour:'2-digit',minute:'2-digit'}):'');};
 const relative=value=>{if(!value)return '';const d=new Date(String(value).replace(' ','T'));if(Number.isNaN(d.getTime()))return '';const diff=Math.floor((Date.now()-d.getTime())/86400000);if(diff<=0)return 'Hoje';if(diff===1)return 'Há 1 dia';return 'Há '+diff+' dias';};
 const money=value=>Number(value||0).toLocaleString('pt-BR',{style:'currency',currency:'BRL'});
 const waUrl=value=>{let d=digits(value);if(!d)return '#';if((d.length===10||d.length===11)&&!d.startsWith('55'))d='55'+d;return 'https://wa.me/'+d;};
 const iconFor=channel=>({whatsapp:'fa-brands fa-whatsapp',phone:'fa-solid fa-phone',email:'fa-regular fa-envelope',presential:'fa-solid fa-user-group'}[channel]||'fa-regular fa-note-sticky');
 let currentRequest=null,currentPayload=null;

 const setTab=name=>{
  qa('[data-client-quick-tab]').forEach(button=>button.classList.toggle('active',button.dataset.clientQuickTab===name));
  qa('[data-client-quick-panel]').forEach(panel=>{const active=panel.dataset.clientQuickPanel===name;panel.classList.toggle('active',active);panel.hidden=!active;});
 };
 qa('[data-client-quick-tab]').forEach(button=>button.addEventListener('click',()=>setTab(button.dataset.clientQuickTab||'overview')));

 const setLink=(el,value,href,enabled=true)=>{
  if(!el)return;
  const label=el.querySelector('span');if(label)label.textContent=value||'Não informado';
  const active=!!value&&enabled;el.classList.toggle('disabled',!active);el.href=active?href:'#';el.setAttribute('aria-disabled',active?'false':'true');
 };
 const renderTimeline=items=>{
  const target=q('[data-client-quick-timeline]');if(!target)return;
  if(!items?.length){target.innerHTML='<div class="tdq-timeline-empty"><i class="fa-regular fa-clock"></i><br>Nenhuma interação registrada.</div>';return;}
  target.innerHTML=items.slice(0,7).map(item=>'<article class="tdq-timeline-item"><span class="tdq-timeline-icon '+esc(item.channel||'other')+'"><i class="'+iconFor(item.channel)+'"></i></span><div class="tdq-timeline-copy"><time>'+esc(dateLabel(item.created_at))+(item.user_name?' · '+esc(item.user_name):'')+'</time><strong>'+esc(item.channel_label||item.type_label||'Atividade')+'</strong><p>'+esc(item.notes||item.type_label||'Atividade registrada')+'</p></div></article>').join('');
 };
 const renderActivities=items=>{
  const target=q('[data-client-quick-activities]');if(!target)return;
  if(!items?.length){target.innerHTML='<div class="tdq-list-empty">Nenhuma atividade registrada.</div>';return;}
  target.innerHTML=items.map(item=>'<article class="tdq-list-row"><span><i class="'+iconFor(item.channel)+'"></i></span><div><strong>'+esc(item.type_label||'Atividade')+' · '+esc(item.channel_label||'Outro')+'</strong><small>'+esc(item.user_name||'Usuário')+'</small><p>'+esc(item.notes||'Sem observação adicional')+'</p></div><em>'+esc(dateLabel(item.created_at))+'</em></article>').join('');
 };
 const renderContacts=items=>{
  const target=q('[data-client-quick-contacts]');if(!target)return;
  if(!items?.length){target.innerHTML='<div class="tdq-list-empty">Nenhum contato da Conta CRM sincronizado.</div>';return;}
  target.innerHTML=items.map(item=>'<article class="tdq-list-row"><span><i class="fa-regular fa-user"></i></span><div><strong>'+esc(item.name||'Contato')+'</strong><small>'+esc(item.position||'Cargo não informado')+'</small><p>'+esc([item.mobile||item.phone,item.email].filter(Boolean).join(' · ')||'Sem telefone ou e-mail')+'</p></div><em>'+esc(item.mobile?'WhatsApp':(item.phone?'Telefone':'Contato'))+'</em></article>').join('');
 };
 const render=data=>{
  currentPayload=data;const client=data.client||{},contact=data.primary_contact||{},counts=data.counts||{};
  q('[data-client-quick-name]').textContent=client.name||'Cliente';
  q('[data-client-quick-meta]').textContent=[client.city&&client.uf?client.city+' / '+client.uf:(client.city||client.uf),client.crm_account_code?'CRM '+client.crm_account_code:'Cliente Geral'].filter(Boolean).join(' · ');
  const status=q('[data-client-quick-status]');status.textContent=client.status||'—';status.className='tdq-status '+String(client.status_tone||'neutral');
  q('[data-client-quick-classification]').textContent=client.classification||'Sem classificação';
  q('[data-client-quick-status-detail]').textContent=client.status||'—';
  q('[data-client-quick-owner]').textContent=client.owner_name||'Sem responsável';
  q('[data-client-quick-last-contact]').textContent=dateLabel(client.last_contact_at);
  q('[data-client-quick-last-contact-relative]').textContent=relative(client.last_contact_at);
  q('[data-client-quick-next-return]').textContent=dateLabel(client.next_return_at);
  q('[data-client-quick-next-return-title]').textContent=String(client.next_return_title||'').replace(/^Retorno\s*[·:\-]\s*/iu,'');
  q('[data-client-quick-document]').textContent=client.document||'Não informado';
  q('[data-client-quick-contact-name]').textContent=contact.name||client.name||'Cliente';
  q('[data-client-quick-contact-position]').textContent=contact.position||'Contato principal';
  q('[data-client-quick-notes]').textContent=client.strategic_notes||'Nenhuma observação estratégica registrada.';
  q('[data-client-quick-activities-count]').textContent=String(counts.activities||0);
  q('[data-client-quick-contacts-count]').textContent=String(counts.contacts||0);
  q('[data-client-quick-orders-count]').textContent=String(counts.orders||0);
  q('[data-client-quick-revenue]').textContent=money(client.revenue_12m);
  q('[data-client-quick-orders]').textContent=String(client.orders_12m||0);
  q('[data-client-quick-first-purchase]').textContent=dateLabel(client.first_purchase_at);
  q('[data-client-quick-last-purchase]').textContent=dateLabel(client.last_purchase_at);
  const canWork=!!client.can_work;
  setLink(q('[data-client-quick-phone]'),contact.phone,'tel:'+digits(contact.phone),canWork);
  setLink(q('[data-client-quick-mobile]'),contact.mobile,waUrl(contact.mobile),canWork);
  setLink(q('[data-client-quick-email]'),contact.email,'mailto:'+contact.email,canWork);
  const call=q('[data-client-quick-call]');call.href=contact.phone&&canWork?'tel:'+digits(contact.phone):'#';call.classList.toggle('disabled',!contact.phone||!canWork);
  const whatsapp=q('[data-client-quick-whatsapp]');whatsapp.href=contact.mobile&&canWork?waUrl(contact.mobile):'#';whatsapp.classList.toggle('disabled',!contact.mobile||!canWork);
  const activity=q('[data-client-quick-activity]');activity.disabled=!canWork||!client.crm_account_code;activity.classList.toggle('disabled',activity.disabled);
  const task=q('[data-client-quick-task]');task.disabled=!canWork;task.classList.toggle('disabled',task.disabled);
  const edit=q('[data-client-quick-edit]');edit.disabled=!client.can_edit;edit.classList.toggle('disabled',edit.disabled);edit.title=client.can_edit?'Editar cadastro sem sair da tela':(client.linked_client?'Sem permissão para editar':'Esta Conta CRM ainda não possui Cliente Geral vinculado');
  const full=q('[data-client-quick-full]');full.href=client.full_url||'#';
  renderTimeline(data.interactions||[]);renderActivities(data.interactions||[]);renderContacts(data.contacts||[]);
  setTab('overview');
 };
 const loading=()=>{
  currentPayload=null;q('[data-client-quick-name]').textContent='Carregando cliente...';q('[data-client-quick-meta]').textContent='Consultando CRM e dados vinculados';
  const status=q('[data-client-quick-status]');status.textContent='Carregando';status.className='tdq-status neutral';
  q('[data-client-quick-timeline]').innerHTML='<div class="tdq-timeline-empty"><i class="fa-solid fa-spinner fa-spin"></i><br>Carregando informações...</div>';
 };
 const load=async (params,mode='view')=>{
  currentRequest=params;
  if(mode==='view'){loading();if(!modal.open)modal.showModal();}
  const query=new URLSearchParams(params);
  try{
   const response=await fetch(base+'/api/client-quick-view?'+query.toString(),{credentials:'same-origin',headers:{Accept:'application/json'}});
   const raw=await response.text();let data={};
   try{data=raw?JSON.parse(raw):{};}catch(e){data={};}
   if(!response.ok||!data.ok){
    const clean=String(raw||'').replace(/<[^>]*>/g,' ').replace(/\s+/g,' ').trim();
    throw new Error(data.error||clean||'Não foi possível abrir a ficha do cliente.');
   }
   render(data);
   if(mode==='edit'){
    if(modal.open)modal.close();
    populateEdit();
   }
  }catch(error){if(modal.open)modal.close();notify('danger','Cliente',error.message||'Não foi possível carregar o cadastro.');}
 };
 const normalizedLocalPath=href=>{
  let url;try{url=new URL(href,location.origin);}catch(e){return null;}
  if(url.origin!==location.origin)return null;
  let path=url.pathname;if(basePath&&path.startsWith(basePath))path=path.slice(basePath.length)||'/';
  return {url,path};
 };
 const editParamsFromAnchor=anchor=>{
  if(!anchor||anchor.dataset.noClientModal!==undefined||anchor.target==='_blank'||anchor.hasAttribute('download'))return null;
  const parsed=normalizedLocalPath(anchor.href);if(!parsed)return null;
  const match=parsed.path.match(/^\/clients\/(\d+)\/edit\/?$/);
  return match?{client_id:match[1]}:null;
 };
 const paramsFromAnchor=anchor=>{
  if(!anchor||anchor.dataset.noClientModal!==undefined||anchor.target==='_blank'||anchor.hasAttribute('download'))return null;
  const parsed=normalizedLocalPath(anchor.href);if(!parsed)return null;
  let match=parsed.path.match(/^\/clients\/(\d+)\/?$/);if(match)return {client_id:match[1]};
  match=parsed.path.match(/^\/commercial\/accounts\/([^/]+)\/?$/);if(match)return {account_code:decodeURIComponent(match[1])};
  return null;
 };
 document.addEventListener('click',event=>{
  if(event.defaultPrevented||event.button!==0||event.ctrlKey||event.metaKey||event.shiftKey||event.altKey)return;
  const direct=event.target.closest?.('[data-client-quick-open]');
  if(direct){
   const accountCode=String(direct.dataset.clientQuickAccount||'').trim(),clientId=Number(direct.dataset.clientQuickId||0);
   if(accountCode||clientId){event.preventDefault();load(accountCode?{account_code:accountCode}:{client_id:String(clientId)});return;}
  }
  const anchor=event.target.closest?.('a[href]');
  const editParams=editParamsFromAnchor(anchor);
  if(editParams){event.preventDefault();load(editParams,'edit');return;}
  const params=paramsFromAnchor(anchor);if(!params)return;
  event.preventDefault();load(params,'view');
 });
 qa('[data-client-quick-close]').forEach(button=>button.addEventListener('click',()=>modal.close()));
 modal.addEventListener('click',event=>{if(event.target===modal)modal.close();});

 const taskButton=q('[data-client-quick-task]');
 taskButton?.addEventListener('click',()=>{
  const client=currentPayload?.client||{};if(!client.can_work)return;
  const trigger=document.createElement('button');trigger.type='button';trigger.hidden=true;trigger.dataset.globalTaskOpen='';
  if(client.id)trigger.dataset.taskClientId=String(client.id);
  trigger.dataset.taskClientName=client.name||'Cliente';
  if(client.crm_account_code){trigger.dataset.taskAccountCode=client.crm_account_code;trigger.dataset.taskAccountName=client.name||'Conta CRM';}
  trigger.dataset.taskContext='sales';document.body.appendChild(trigger);trigger.click();setTimeout(()=>trigger.remove(),0);
 });

 const editButton=q('[data-client-quick-edit]');
 const editModal=document.querySelector('[data-client-quick-edit-modal]');
 const editForm=editModal?.querySelector('[data-client-quick-edit-form]');
 const editClose=()=>editModal?.close();
 const editDigits=value=>String(value||'').replace(/\D+/g,'');
 const editFormatDoc=value=>{
  const n=editDigits(value).slice(0,14);
  if(n.length<=11)return n.replace(/(\d{3})(\d)/,'$1.$2').replace(/(\d{3})(\d)/,'$1.$2').replace(/(\d{3})(\d{1,2})$/,'$1-$2');
  return n.replace(/(\d{2})(\d)/,'$1.$2').replace(/(\d{3})(\d)/,'$1.$2').replace(/(\d{3})(\d)/,'$1/$2').replace(/(\d{4})(\d{1,2})$/,'$1-$2');
 };
 const editFormatPhone=value=>{const n=editDigits(value).slice(0,9);return n.length>8?n.replace(/(\d{5})(\d{1,4})$/,'$1-$2'):n.replace(/(\d{4})(\d{1,4})$/,'$1-$2');};
 const editFormatCep=value=>editDigits(value).slice(0,8).replace(/(\d{5})(\d)/,'$1-$2');
 const editField=name=>editForm?.elements?.namedItem(name);
 const editSetStatus=(selector,message,tone='')=>{
  const el=editForm?.querySelector(selector);if(!el)return;el.textContent=message;el.classList.remove('ok','error','loading');if(tone)el.classList.add(tone);
 };
 const editFill=(name,value,overwrite=true)=>{
  const el=editField(name);if(!el||value===undefined||value===null||String(value).trim()==='')return;
  if(!overwrite&&String(el.value||'').trim()!=='')return;
  el.value=String(value);el.dispatchEvent(new Event('input',{bubbles:true}));
 };
 const editLookupJson=async path=>{
  const response=await fetch(base+path,{credentials:'same-origin',headers:{Accept:'application/json'}});
  const data=await response.json().catch(()=>({}));if(!response.ok||!data.ok)throw new Error(data.error||'Falha na consulta.');
  return data.data||{};
 };
 const lookupEditCnpj=async(auto=false)=>{
  const doc=editField('document'),digitsValue=editDigits(doc?.value);
  if(digitsValue.length!==14){if(!auto)editSetStatus('[data-client-edit-cnpj-status]','Informe um CNPJ com 14 dígitos.','error');return;}
  const button=editForm?.querySelector('[data-client-edit-cnpj-lookup]');
  try{
   if(button)button.disabled=true;editSetStatus('[data-client-edit-cnpj-status]','Consultando CNPJ...','loading');
   const data=await editLookupJson('/api/public/cnpj?value='+encodeURIComponent(digitsValue));
   editFill('legal_name',data.legal_name);editFill('trade_name',data.trade_name);editFill('email',data.email,false);
   editFill('phone_ddd',data.phone_ddd,false);editFill('phone_number',data.phone_number,false);editFill('zip_code',data.zip_code,false);
   editFill('address',data.address,false);editFill('address_number',data.address_number,false);editFill('complement',data.complement,false);
   editFill('neighborhood',data.neighborhood,false);editFill('city',data.city,false);editFill('uf',data.uf,false);
   formatCanonicalEditorValues();editSetStatus('[data-client-edit-cnpj-status]','CNPJ localizado. Revise os dados antes de salvar.','ok');
  }catch(error){editSetStatus('[data-client-edit-cnpj-status]',error.message||'Falha na consulta.','error');}
  finally{if(button)button.disabled=false;}
 };
 const lookupEditCep=async(auto=false)=>{
  const cep=editField('zip_code'),digitsValue=editDigits(cep?.value);
  if(digitsValue.length!==8){if(!auto)editSetStatus('[data-client-edit-cep-status]','Informe um CEP com 8 dígitos.','error');return;}
  const button=editForm?.querySelector('[data-client-edit-cep-lookup]');
  try{
   if(button)button.disabled=true;editSetStatus('[data-client-edit-cep-status]','Consultando CEP...','loading');
   const data=await editLookupJson('/api/public/cep?value='+encodeURIComponent(digitsValue));
   editFill('address',data.address,false);editFill('neighborhood',data.neighborhood,false);editFill('city',data.city,false);editFill('uf',data.uf,false);
   editSetStatus('[data-client-edit-cep-status]','Endereço localizado.','ok');
  }catch(error){editSetStatus('[data-client-edit-cep-status]',error.message||'Falha na consulta.','error');}
  finally{if(button)button.disabled=false;}
 };
 const formatCanonicalEditorValues=()=>{
  const doc=editField('document'),ddd=editField('phone_ddd'),number=editField('phone_number'),cep=editField('zip_code'),uf=editField('uf');
  if(doc)doc.value=editFormatDoc(doc.value);if(ddd)ddd.value=editDigits(ddd.value).slice(0,2);if(number)number.value=editFormatPhone(number.value);if(cep)cep.value=editFormatCep(cep.value);if(uf)uf.value=String(uf.value||'').toUpperCase().slice(0,2);
 };
 if(editForm){
  editForm.querySelector('[data-client-edit-cnpj-lookup]')?.addEventListener('click',()=>lookupEditCnpj(false));
  editForm.querySelector('[data-client-edit-cep-lookup]')?.addEventListener('click',()=>lookupEditCep(false));
  editField('document')?.addEventListener('input',event=>{event.currentTarget.value=editFormatDoc(event.currentTarget.value);});
  editField('phone_ddd')?.addEventListener('input',event=>{event.currentTarget.value=editDigits(event.currentTarget.value).slice(0,2);});
  editField('phone_number')?.addEventListener('input',event=>{event.currentTarget.value=editFormatPhone(event.currentTarget.value);});
  editField('zip_code')?.addEventListener('input',event=>{event.currentTarget.value=editFormatCep(event.currentTarget.value);if(editDigits(event.currentTarget.value).length===8)lookupEditCep(true);});
  editField('uf')?.addEventListener('input',event=>{event.currentTarget.value=String(event.currentTarget.value||'').toUpperCase().slice(0,2);});
 }

 const populateEdit=()=>{
  const client=currentPayload?.client||{},formData=currentPayload?.edit_form||{},sellerOptions=currentPayload?.seller_options||[];
  if(!editModal||!editForm||!client.can_edit||!client.id||!formData)return;
  editForm.reset();editForm.action=base+'/api/client-quick-view/'+encodeURIComponent(String(client.id))+'/update';
  editModal.querySelector('[data-client-quick-edit-name]').textContent=client.name||'Cliente';
  const fields=['document','legal_name','trade_name','email','contact_name','phone_ddd','phone_number','zip_code','address','address_number','complement','neighborhood','city','uf','tags','strategic_notes','notes'];
  fields.forEach(name=>{const input=editForm.elements.namedItem(name);if(input)input.value=String(formData[name]??'');});
  const cfc=editForm.elements.namedItem('is_cfc'),reseller=editForm.elements.namedItem('is_reseller');
  if(cfc)cfc.checked=!!Number(formData.is_cfc||0);
  if(reseller)reseller.checked=!!Number(formData.is_reseller||0);
  const seller=editForm.querySelector('[data-client-quick-edit-seller]');
  if(seller){
   seller.innerHTML='<option value="">Sem vendedor</option>'+sellerOptions.map(item=>'<option value="'+esc(item.omie_code)+'">'+esc(item.name)+'</option>').join('');
   seller.value=String(formData.seller_omie_code??'');
  }
  formatCanonicalEditorValues();
  editModal.showModal();
 };
 editButton?.addEventListener('click',populateEdit);
 editModal?.querySelectorAll('[data-client-quick-edit-close]').forEach(button=>button.addEventListener('click',editClose));
 editModal?.addEventListener('click',event=>{if(event.target===editModal)editClose();});
 editForm?.addEventListener('submit',async event=>{
  event.preventDefault();if(!currentPayload?.client?.can_edit||!currentPayload?.client?.id)return;
  const submit=editForm.querySelector('[type="submit"]');const original=submit?.innerHTML;
  if(submit){submit.disabled=true;submit.innerHTML='<i class="fa-solid fa-spinner fa-spin"></i>Salvando...';}
  try{
   const response=await fetch(editForm.action,{method:'POST',credentials:'same-origin',headers:{Accept:'application/json'},body:new FormData(editForm)});
   const data=await response.json().catch(()=>({}));if(!response.ok||!data.ok)throw new Error(data.error||'Não foi possível salvar o cadastro.');
   editClose();notify('success','Cadastro atualizado',data.message||'As alterações foram salvas no CRM.');
   if(currentRequest)await load(currentRequest);
  }catch(error){notify('danger','Editar cadastro',error.message||'Revise os campos e tente novamente.');}
  finally{if(submit){submit.disabled=false;submit.innerHTML=original;}}
 });

 const activityButton=q('[data-client-quick-activity]');
 const activityForm=activityModal?.querySelector('[data-client-quick-activity-form]');
 activityButton?.addEventListener('click',()=>{
  const client=currentPayload?.client||{};if(!client.can_work||!client.crm_account_code||!activityModal||!activityForm)return;
  activityForm.reset();activityForm.querySelector('[data-client-quick-activity-account]').value=client.crm_account_code;
  activityModal.querySelector('[data-client-quick-activity-name]').textContent=client.name||'Cliente';
  const next=activityForm.querySelector('[data-client-quick-activity-next]');if(next){const d=new Date(Date.now()+86400000);d.setMinutes(0,0,0);next.min=new Date(Date.now()-new Date().getTimezoneOffset()*60000).toISOString().slice(0,16);next.value='';}
  activityModal.showModal();
 });
 activityModal?.querySelectorAll('[data-client-quick-activity-close]').forEach(button=>button.addEventListener('click',()=>activityModal.close()));
 activityModal?.addEventListener('click',event=>{if(event.target===activityModal)activityModal.close();});
 activityForm?.addEventListener('submit',async event=>{
  event.preventDefault();const submit=activityForm.querySelector('[type="submit"]');const original=submit?.innerHTML;if(submit){submit.disabled=true;submit.innerHTML='<i class="fa-solid fa-spinner fa-spin"></i>Salvando...';}
  try{
   const formData=new FormData(activityForm);
   const response=await fetch(base+'/api/client-quick-view/activity',{method:'POST',credentials:'same-origin',headers:{Accept:'application/json'},body:formData});
   const data=await response.json().catch(()=>({}));if(!response.ok||!data.ok)throw new Error(data.error||'Não foi possível registrar a atividade.');
   activityModal.close();notify('success','Atividade registrada',data.message||'O histórico do cliente foi atualizado.');
   if(currentRequest)load(currentRequest);
  }catch(error){notify('danger','Atividade',error.message||'Não foi possível registrar a atividade.');}
  finally{if(submit){submit.disabled=false;submit.innerHTML=original;}}
 });

 const autoEdit=new URLSearchParams(location.search).get('edit')==='1';
 if(autoEdit){
  let path=location.pathname;if(basePath&&path.startsWith(basePath))path=path.slice(basePath.length)||'/';
  const match=path.match(/^\/clients\/(\d+)\/?$/);
  if(match){
   const cleanUrl=new URL(location.href);cleanUrl.searchParams.delete('edit');history.replaceState(null,'',cleanUrl.pathname+(cleanUrl.searchParams.toString()?'?'+cleanUrl.searchParams.toString():'')+cleanUrl.hash);
   load({client_id:match[1]},'edit');
  }
 }
})();
