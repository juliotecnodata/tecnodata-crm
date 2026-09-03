<?php
require dirname(__DIR__) . '/app/bootstrap.php';
require APP_ROOT . '/app/Support/helpers.php';
use Tecnodata\CRM\Core\Auth; use Tecnodata\CRM\Core\DB; use Tecnodata\CRM\Core\Security;
Auth::requireLogin(); if(!Auth::can('admin')){http_response_code(403);exit;}
if($_SERVER['REQUEST_METHOD']==='POST' && Security::verifyCsrf($_POST['_token']??null)){
 $name=trim($_POST['name']);$email=mb_strtolower(trim($_POST['email']));$role=$_POST['role'];if(!in_array($role,['seller','collector','supervisor','admin'],true))$role='seller';$seller=$role==='seller'?($_POST['seller_omie_code']?:null):null;
 $hash=password_hash($_POST['password'],PASSWORD_DEFAULT);
 DB::exec("INSERT INTO users(name,email,password_hash,role,seller_omie_code,active,created_at) VALUES (?,?,?,?,?,1,NOW())",[$name,$email,$hash,$role,$seller]);
 redirect('/usuarios.php');
}
$users=DB::all("SELECT u.*,s.name seller_name FROM users u LEFT JOIN sellers s ON s.omie_code=u.seller_omie_code ORDER BY u.name");
$sellers=DB::all("SELECT * FROM sellers WHERE active=1 ORDER BY name");
include '_layout.php';?>
<h1 class="h3 mb-4">Usuários</h1><div class="row g-4"><div class="col-lg-7"><div class="card"><div class="table-responsive data-table-wrap"><table class="table modern-table data-table mb-0" data-entity="usuários" data-page-length="10"><thead><tr><th>Nome</th><th>Perfil</th><th>Vendedor Omie</th><th>Último acesso</th></tr></thead><tbody><?php foreach($users as $u):?><tr><td><strong><?=e($u['name'])?></strong><div class="small text-secondary"><?=e($u['email'])?></div></td><td><?=e(['seller'=>'Vendedor','collector'=>'Cobrança','supervisor'=>'Supervisor','admin'=>'Administrador'][$u['role']]??$u['role'])?></td><td><?=e($u['seller_name']??'—')?></td><td><?=$u['last_login_at']?date('d/m/Y H:i',strtotime($u['last_login_at'])):'—'?></td></tr><?php endforeach;?></tbody></table></div></div></div>
<div class="col-lg-5"><div class="card"><div class="card-body"><h2 class="h5">Novo usuário</h2><form method="post"><input type="hidden" name="_token" value="<?=Security::csrf()?>"><div class="mb-3"><label class="form-label">Nome</label><input class="form-control" name="name" required></div><div class="mb-3"><label class="form-label">E-mail</label><input class="form-control" type="email" name="email" required></div><div class="mb-3"><label class="form-label">Senha</label><input class="form-control" type="password" name="password" required></div><div class="mb-3"><label class="form-label">Perfil</label><select class="form-select" name="role"><option value="seller">Vendedor</option><option value="collector">Cobrança</option><option value="supervisor">Supervisor</option><option value="admin">Administrador</option></select></div><div class="mb-3"><label class="form-label">Vendedor Omie</label><select class="form-select" name="seller_omie_code"><option value="">Não vincular</option><?php foreach($sellers as $s):?><option value="<?=e($s['omie_code'])?>"><?=e($s['name'])?></option><?php endforeach;?></select></div><button class="btn btn-dark">Criar usuário</button></form></div></div></div></div>
<?php include '_footer.php';?>
