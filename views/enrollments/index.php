<?php use Tecnodata\Lms\Core\Csrf; ?>
<div class="page-heading"><div><span class="eyebrow">ACADÊMICO</span><h1>Matrículas</h1><p>Listagem server-side para milhares de matrículas e integrações contínuas.</p></div></div>
<section class="panel mb-4"><h2>Nova matrícula manual</h2><form method="post" action="/admin/enrollments" class="row g-2 align-items-end"><?=Csrf::field()?><div class="col-md-4"><label class="form-label">CPF do aluno</label><input class="form-control" name="cpf" inputmode="numeric" maxlength="14" required></div><div class="col-md-6"><label class="form-label">Curso</label><select class="form-select" name="course_id" required><option value="">Selecione...</option><?php foreach($courses as $c):?><option value="<?=$c['id']?>"><?=e($c['name'])?> · <?=e($c['code'])?></option><?php endforeach;?></select></div><div class="col-md-2"><button class="btn btn-brand w-100">Matricular</button></div></form></section>
<section class="panel"><div class="table-responsive"><table class="table align-middle" id="enrollmentsTable"><thead><tr><th>ID</th><th>Aluno</th><th>CPF</th><th>Curso</th><th>Progresso</th><th>Início</th><th>Validade</th><th>Status</th><th></th></tr></thead></table></div></section>
<script>
document.addEventListener('DOMContentLoaded',()=>{
 new DataTable('#enrollmentsTable',{
  serverSide:true,processing:true,pageLength:25,searchDelay:350,
  ajax:'/admin/data/enrollments',
  columns:[
   {data:'id'},{data:'student',render:d=>'<strong>'+escapeHtml(d)+'</strong>'},{data:'cpf'},
   {data:'course',render:(d,t,r)=>'<strong>'+escapeHtml(d)+'</strong><small class="d-block text-secondary">'+escapeHtml(r.course_code)+'</small>'},
   {data:'progress_percent',render:d=>escapeHtml(d)+'%'},{data:'started_at',defaultContent:'—'},{data:'expires_at',defaultContent:'—'},
   {data:'status',render:d=>'<span class="status-badge">'+escapeHtml(d)+'</span>'},{data:'actions',orderable:false,searchable:false}
  ],
  language:{search:'Buscar:',lengthMenu:'Mostrar _MENU_',info:'_START_–_END_ de _TOTAL_',processing:'Carregando...',zeroRecords:'Nenhum resultado',paginate:{next:'Próxima',previous:'Anterior'}}
 });
});
function escapeHtml(v){const d=document.createElement('div');d.textContent=v??'';return d.innerHTML}
</script>
