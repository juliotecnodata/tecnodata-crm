<?php use Tecnodata\Lms\Core\Csrf; ?>
<div class="page-heading"><div><span class="eyebrow">PESSOAS</span><h1>Alunos</h1><p>Cadastro central. A listagem é paginada no servidor para suportar grande volume.</p></div></div>
<?php if($message):?><div class="alert alert-success"><?=e($message)?></div><?php endif;?>
<section class="panel mb-4"><h2>Novo aluno</h2><form method="post" action="/admin/students" class="row g-2 align-items-end"><?=Csrf::field()?><div class="col-md-3"><label class="form-label">CPF</label><input class="form-control" name="cpf" inputmode="numeric" required></div><div class="col-md-4"><label class="form-label">Nome</label><input class="form-control" name="name" required></div><div class="col-md-3"><label class="form-label">E-mail</label><input class="form-control" type="email" name="email"></div><div class="col-md-2"><button class="btn btn-brand w-100">Criar aluno</button></div></form></section>
<section class="panel"><div class="table-responsive"><table class="table align-middle" id="studentsTable"><thead><tr><th>ID</th><th>Nome</th><th>CPF</th><th>E-mail</th><th>Matrículas</th><th>Ativas</th><th>Status</th><th></th></tr></thead></table></div></section>
<script>
document.addEventListener('DOMContentLoaded',()=>{
 new DataTable('#studentsTable',{
  serverSide:true,processing:true,pageLength:25,searchDelay:350,
  ajax:'/admin/data/students',
  columns:[
   {data:'id'},{data:'name',render:d=>'<strong>'+escapeHtml(d)+'</strong>'},{data:'cpf'},{data:'email',defaultContent:'—'},
   {data:'enrollments'},{data:'active_enrollments'},
   {data:'status',render:d=>'<span class="status-badge">'+escapeHtml(d)+'</span>'},
   {data:'actions',orderable:false,searchable:false}
  ],
  language:{search:'Buscar:',lengthMenu:'Mostrar _MENU_',info:'_START_–_END_ de _TOTAL_',processing:'Carregando...',zeroRecords:'Nenhum resultado',paginate:{next:'Próxima',previous:'Anterior'}}
 });
});
function escapeHtml(v){const d=document.createElement('div');d.textContent=v??'';return d.innerHTML}
</script>
