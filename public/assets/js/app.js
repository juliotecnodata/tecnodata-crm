document.addEventListener('DOMContentLoaded',()=>{
  document.querySelectorAll('[data-confirm]').forEach(el=>el.addEventListener('click',e=>{if(!confirm(el.dataset.confirm))e.preventDefault();}));

  const sidebarButton=document.querySelector('.sidebar-toggle');
  if(sidebarButton) sidebarButton.addEventListener('click',()=>document.body.classList.toggle('sidebar-open'));

  const sidebarCollapse=document.getElementById('sidebarCollapse');
  const applySidebarState=()=>{
    if(window.innerWidth<992){document.body.classList.remove('sidebar-collapsed');return;}
    const collapsed=localStorage.getItem('tdcrm-sidebar-collapsed')==='1';
    document.body.classList.toggle('sidebar-collapsed',collapsed);
    if(sidebarCollapse){
      sidebarCollapse.setAttribute('aria-label',collapsed?'Expandir menu':'Recolher menu');
      sidebarCollapse.setAttribute('title',collapsed?'Expandir menu':'Recolher menu');
    }
  };
  applySidebarState();
  sidebarCollapse?.addEventListener('click',()=>{
    localStorage.setItem('tdcrm-sidebar-collapsed',document.body.classList.contains('sidebar-collapsed')?'0':'1');
    applySidebarState();
  });
  window.addEventListener('resize',applySidebarState);

  document.querySelectorAll('table.data-table').forEach(table=>{
    if(typeof DataTable==='undefined') return;

    const noSort=[];
    table.querySelectorAll('thead th').forEach((th,i)=>{if(th.classList.contains('no-sort'))noSort.push(i);});
    const orderColumn=Number(table.dataset.orderColumn ?? 0);
    const orderDir=(table.dataset.orderDir||'desc').toLowerCase()==='asc'?'asc':'desc';
    const pageLength=Number(table.dataset.pageLength ?? 25);
    const entity=table.dataset.entity || 'registros';
    const serverSide=table.dataset.serverSide==='1';

    const options={
      pageLength,
      lengthMenu:[10,25,50,100],
      order:[[orderColumn,orderDir]],
      columnDefs:noSort.length?[{targets:noSort,orderable:false,searchable:false}]:[],
      language:{
        search:'Buscar',searchPlaceholder:`Buscar ${entity}...`,lengthMenu:'_MENU_ por página',
        info:'_START_–_END_ de _TOTAL_',infoEmpty:'Nenhum registro',infoFiltered:'(filtrado de _MAX_)',
        zeroRecords:'Nenhum resultado encontrado',emptyTable:'Nenhum dado disponível',
        paginate:{first:'Primeira',last:'Última',next:'Próxima',previous:'Anterior'},
        processing:'Carregando...'
      }
    };

    if(serverSide){
      options.processing=true;
      options.serverSide=true;
      options.searchDelay=350;
      options.ajax={
        url:table.dataset.ajax,
        data:d=>{
          if(table.id==='collectionTable'){
            d.view=document.querySelector('#collectionView .active')?.dataset.value||'open';
            d.signal=document.getElementById('collectionSignal')?.value||'all';
            d.month=document.getElementById('collectionMonth')?.value||'';
            d.seller=document.getElementById('collectionSeller')?.value||'';
            d.uf=document.getElementById('collectionUf')?.value||'';
          }
          if(table.id==='collectionActionsTable'){
            d.month=document.getElementById('actionMonth')?.value||'';
            d.result=document.getElementById('actionResult')?.value||'';
            d.user_id=document.getElementById('actionUser')?.value||'0';
          }
          if(table.id==='clientsManagementTable'){
            d.month=document.getElementById('portfolioMonth')?.value||'';
            d.uf=document.getElementById('portfolioUf')?.value||'';
            d.tag=document.getElementById('portfolioTag')?.value||'';
            d.seller=document.getElementById('portfolioSellerFilter')?.value||'';
            d.status=document.getElementById('portfolioStatus')?.value||'';
            d.finance=document.getElementById('portfolioFinance')?.value||'';
            d.source=document.getElementById('portfolioSource')?.value||'';
          }
        }
      };
    }

    const dt=new DataTable(table,options);

    if(table.id==='clientsManagementTable'){
      const selected=new Set();
      const selectPage=document.getElementById('portfolioSelectPage');
      const updateSelection=()=>{
        const info=document.getElementById('portfolioSelectionInfo');
        if(info) info.textContent=selected.size?selected.size+' cliente(s) selecionado(s).':'Selecione clientes ou aplique a distribuição ao filtro atual.';
      };
      const reloadClients=()=>{
        selected.clear();
        if(selectPage) selectPage.checked=false;
        updateSelection();
        dt.ajax.reload(null,true);
      };

      document.getElementById('portfolioApply')?.addEventListener('click',()=>{
        const status=document.getElementById('portfolioFilterStatus');
        if(status)status.innerHTML='<i class="fa-solid fa-spinner fa-spin"></i> Aplicando filtros...';
        selected.clear();
        if(selectPage)selectPage.checked=false;
        updateSelection();
        dt.ajax.reload(()=>{
          if(status)status.innerHTML='<i class="fa-solid fa-check"></i> Filtros aplicados. A tabela abaixo mostra somente os clientes que correspondem aos critérios selecionados.';
        },true);
      });

      document.getElementById('portfolioMonth')?.addEventListener('change',e=>{
        location.href='?month='+encodeURIComponent(e.target.value);
      });

      document.getElementById('portfolioClear')?.addEventListener('click',()=>{
        ['portfolioUf','portfolioTag','portfolioSellerFilter','portfolioStatus','portfolioFinance','portfolioSource']
          .forEach(id=>{const el=document.getElementById(id);if(el)el.value='';});
        reloadClients();
      });

      table.addEventListener('change',e=>{
        if(e.target.classList.contains('portfolio-row-check')){
          const id=Number(e.target.value);
          if(e.target.checked) selected.add(id); else selected.delete(id);
          updateSelection();
        }
      });

      selectPage?.addEventListener('change',e=>{
        table.querySelectorAll('.portfolio-row-check').forEach(ch=>{
          ch.checked=e.target.checked;
          const id=Number(ch.value);
          if(e.target.checked) selected.add(id); else selected.delete(id);
        });
        updateSelection();
      });

      const assign=async mode=>{
        const assignSelect=document.getElementById('portfolioAssignSeller');
        const seller=assignSelect?.value||'';
        if(!seller){alert('Escolha o vendedor ou a ação desejada.');return;}
        if(mode==='selected'&&!selected.size){alert('Selecione ao menos um cliente.');return;}
        const label=assignSelect?.selectedOptions?.[0]?.textContent||'a opção escolhida';
        const msg=mode==='selected'
          ?'Aplicar '+label+' a '+selected.size+' cliente(s)?'
          :'Aplicar '+label+' a TODOS os clientes do filtro atual?';
        if(!confirm(msg))return;

        const payload={
          _token:window.TDCRM_CONFIG.csrf,
          mode,seller,
          month:document.getElementById('portfolioMonth')?.value||'',
          ids:[...selected],
          filters:{
            uf:document.getElementById('portfolioUf')?.value||'',
            tag:document.getElementById('portfolioTag')?.value||'',
            seller:document.getElementById('portfolioSellerFilter')?.value||'',
            status:document.getElementById('portfolioStatus')?.value||'',
            finance:document.getElementById('portfolioFinance')?.value||'',
            source:document.getElementById('portfolioSource')?.value||'',
            search:dt.search()||''
          }
        };

        try{
          const r=await fetch(window.TDCRM_CONFIG.baseUrl+'/api/client-portfolio-assign.php',{
            method:'POST',
            headers:{'Content-Type':'application/json','Accept':'application/json'},
            credentials:'same-origin',
            body:JSON.stringify(payload)
          });
          const data=await r.json();
          if(!r.ok||!data.ok){alert(data.error||'Falha ao distribuir carteira.');return;}
          alert(data.message||'Carteira atualizada.');
          reloadClients();
        }catch(e){
          alert('Falha de comunicação ao distribuir a carteira.');
        }
      };

      document.getElementById('portfolioAssignSelected')?.addEventListener('click',()=>assign('selected'));
      document.getElementById('portfolioAssignFiltered')?.addEventListener('click',()=>assign('filtered'));
    }

    if(table.id==='collectionActionsTable'){
      const reloadActions=()=>dt.ajax.reload(null,true);
      document.getElementById('actionApply')?.addEventListener('click',reloadActions);
      document.getElementById('actionClear')?.addEventListener('click',()=>{
        const m=document.getElementById('actionMonth'),r=document.getElementById('actionResult');
        if(m)m.value=new Date().toISOString().slice(0,7);
        if(r)r.value='';
        reloadActions();
      });
    }

    if(table.id==='collectionTable'){
      const reload=()=>dt.ajax.reload(null,true);
      document.getElementById('collectionApply')?.addEventListener('click',reload);
      document.querySelectorAll('#collectionView button').forEach(btn=>btn.addEventListener('click',()=>{
        document.querySelectorAll('#collectionView button').forEach(b=>b.classList.remove('active'));
        btn.classList.add('active');
        reload();
      }));
      document.getElementById('showMyWork')?.addEventListener('click',()=>{
        const s=document.getElementById('collectionSignal');if(s)s.value='mine';reload();
      });
      document.getElementById('collectionClear')?.addEventListener('click',()=>{
        const s=document.getElementById('collectionSignal'),m=document.getElementById('collectionMonth'),v=document.getElementById('collectionSeller'),uf=document.getElementById('collectionUf');
        if(s)s.value='all';
        if(m)m.value=new Date().toISOString().slice(0,7);
        if(v)v.value='';
        if(uf)uf.value='';
        document.querySelectorAll('#collectionView button').forEach(b=>b.classList.toggle('active',b.dataset.value==='open'));
        reload();
      });
    }
  });

  const role=document.querySelector('select[name="role"]');
  const seller=document.querySelector('select[name="seller_omie_code"]');
  if(role&&seller){
    const update=()=>{
      const enabled=role.value==='seller';
      seller.disabled=!enabled;
      if(!enabled)seller.value='';
      seller.closest('.mb-3')?.classList.toggle('opacity-50',!enabled);
    };
    role.addEventListener('change',update);
    update();
  }
});

/* V6.3 — Central de alertas */
(() => {
 const cfg=window.TDCRM_CONFIG||{};if(!cfg.baseUrl||!cfg.csrf)return;
 let audioCtx=null,panelOpen=false;

 async function unlockSound(){try{audioCtx=audioCtx||new(window.AudioContext||window.webkitAudioContext)();if(audioCtx.state==='suspended')await audioCtx.resume();return true;}catch(e){return false;}}
 function playChime(volume=70){try{audioCtx=audioCtx||new(window.AudioContext||window.webkitAudioContext)();const master=audioCtx.createGain();master.gain.value=Math.max(0,Math.min(1,Number(volume)/100))*.12;master.connect(audioCtx.destination);const now=audioCtx.currentTime;[[660,0],[880,.13]].forEach(([freq,offset])=>{const osc=audioCtx.createOscillator(),g=audioCtx.createGain();osc.type='sine';osc.frequency.value=freq;g.gain.setValueAtTime(.0001,now+offset);g.gain.exponentialRampToValueAtTime(.8,now+offset+.015);g.gain.exponentialRampToValueAtTime(.0001,now+offset+.16);osc.connect(g);g.connect(master);osc.start(now+offset);osc.stop(now+offset+.18);});}catch(e){}}
 async function sw(){if(!('serviceWorker'in navigator))return null;try{await navigator.serviceWorker.register(cfg.baseUrl+'/service-worker.js',{scope:cfg.baseUrl+'/'});return await navigator.serviceWorker.ready;}catch(e){return null;}}
 async function showBrowserNotification(title,body,url,tag){if(!('Notification'in window)||Notification.permission!=='granted')return false;try{const reg=await sw();if(reg?.showNotification){await reg.showNotification(title,{body,tag,renotify:true,data:{url},silent:true});return true;}const n=new Notification(title,{body,tag});n.onclick=()=>{window.focus();location.href=url;n.close();};return true;}catch(e){return false;}}
 function esc(v=''){const d=document.createElement('div');d.textContent=String(v);return d.innerHTML;}
 function render(items,counts){
  const list=document.getElementById('notificationList'),summary=document.getElementById('notificationSummary'),badge=document.getElementById('notificationCount');if(!list||!summary||!badge)return;
  const due=Number(counts?.due||0),today=Number(counts?.today||0);badge.textContent=String(due);badge.classList.toggle('d-none',due<=0);summary.textContent=due>0?`${due} atrasado${due===1?'':'s'} • ${today} hoje`:`${today} retorno${today===1?'':'s'} hoje`;
  if(!items?.length){list.innerHTML='<div class="notification-empty"><i class="fa-regular fa-circle-check"></i><span>Nenhum retorno pendente.</span></div>';return;}
  list.innerHTML=items.map(i=>`<a class="notification-item ${i.is_late?'is-late':''}" href="${esc(i.url)}"><span class="notification-item-icon"><i class="fa-solid ${i.collection?'fa-hand-holding-dollar':'fa-phone'}"></i></span><span class="notification-item-copy"><strong>${esc(i.client_name)}</strong><small>${esc(i.title)}</small><time>${esc(i.due_label)}${i.is_late?' • atrasado':''}</time></span><i class="fa-solid fa-chevron-right notification-arrow"></i></a>`).join('');
 }
 async function poll(){
  try{const r=await fetch(cfg.baseUrl+'/api/notifications.php',{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json'},credentials:'same-origin',body:JSON.stringify({_token:cfg.csrf})});if(!r.ok)return;const data=await r.json();if(!data.ok)return;render(data.items,data.counts);const st=data.settings||{};const important=(data.events||[]).some(e=>e.stage==='due'||e.stage==='reminder');if(important&&st.sound_enabled){await unlockSound();playChime(st.volume||70);}for(const ev of(data.events||[])){if(st.browser_enabled)await showBrowserNotification(ev.title,ev.body,ev.url,`tdcrm-task-${ev.task_id}-${ev.stage}`);}}catch(e){}
 }
 function bindBell(){
  const bell=document.getElementById('notificationBell'),panel=document.getElementById('notificationPanel'),quick=document.getElementById('enableAlertsQuick');if(!bell||!panel)return;
  bell.addEventListener('click',async e=>{e.stopPropagation();panelOpen=!panelOpen;panel.hidden=!panelOpen;bell.setAttribute('aria-expanded',panelOpen?'true':'false');if(panelOpen){await unlockSound();poll();}});
  panel.addEventListener('click',e=>e.stopPropagation());document.addEventListener('click',()=>{if(panelOpen){panelOpen=false;panel.hidden=true;bell.setAttribute('aria-expanded','false');}});
  quick?.addEventListener('click',async()=>{if('Notification'in window&&Notification.permission==='default')await Notification.requestPermission();await unlockSound();quick.textContent=Notification.permission==='granted'?'Notificações ativadas':'Ver configurações';if(Notification.permission==='denied')location.href=cfg.baseUrl+'/alertas.php';});
 }
 document.addEventListener('DOMContentLoaded',async()=>{bindBell();await sw();setTimeout(poll,1800);setInterval(poll,Math.max(30,Number(cfg.notificationPollSeconds||60))*1000);document.addEventListener('visibilitychange',()=>{if(document.visibilityState==='visible')poll();});document.addEventListener('pointerdown',unlockSound,{once:true});});
 window.TDCRMAlerts={unlockSound,playChime,showBrowserNotification,poll};
})();
