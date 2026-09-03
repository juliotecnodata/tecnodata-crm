<?php
require dirname(__DIR__) . '/app/bootstrap.php';
require APP_ROOT . '/app/Support/helpers.php';
use Tecnodata\CRM\Core\Auth;
use Tecnodata\CRM\Core\Security;
if(Auth::check()) redirect('/index.php');
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 if(!Security::verifyCsrf($_POST['_token']??null)) $error='Sessão expirada. Atualize a página.';
 elseif(Auth::login($_POST['email']??'',$_POST['password']??'')) redirect('/index.php');
 else $error='E-mail ou senha inválidos.';
}
?><!doctype html><html lang="pt-br"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Acesso • Tecnodata</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.0/css/all.min.css" rel="stylesheet">
<link href="<?=APP_URL?>/assets/css/app.css" rel="stylesheet"></head>
<body class="d-flex align-items-center justify-content-center min-vh-100 p-3">
<div class="card p-4" style="width:100%;max-width:420px">
<div class="mb-4"><div class="fw-bold fs-4">Tecnodata <span style="color:#8fa800">Carteira</span></div><div class="text-secondary">Acesso comercial</div></div>
<?php if($error):?><div class="alert alert-danger"><?=e($error)?></div><?php endif;?>
<form method="post">
<input type="hidden" name="_token" value="<?=Security::csrf()?>">
<div class="mb-3"><label class="form-label">E-mail</label><input class="form-control" type="email" name="email" required autofocus></div>
<div class="mb-3"><label class="form-label">Senha</label><input class="form-control" type="password" name="password" required></div>
<button class="btn w-100" style="background:#BDD630;color:#121B25;font-weight:700">Entrar</button>
</form></div></body></html>
