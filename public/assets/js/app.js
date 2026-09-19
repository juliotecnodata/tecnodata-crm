document.addEventListener('DOMContentLoaded',()=>{
 const b=document.getElementById('menuToggle'),s=document.getElementById('sidebar'),back=document.getElementById('sidebarBackdrop');
 const close=()=>{s?.classList.remove('open');back?.classList.remove('show')};
 if(b&&s)b.addEventListener('click',()=>{s.classList.toggle('open');back?.classList.toggle('show',s.classList.contains('open'))});
 back?.addEventListener('click',close);
 document.querySelectorAll('.datatable').forEach(t=>{if(window.DataTable)new DataTable(t,{pageLength:10,order:[],language:{search:'Buscar:',lengthMenu:'Mostrar _MENU_',info:'_START_–_END_ de _TOTAL_',infoEmpty:'Nenhum registro',zeroRecords:'Nenhum resultado',paginate:{next:'Próxima',previous:'Anterior'}}})});
});