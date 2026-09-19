<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';

use Tecnodata\Lms\Core\Clock;
use Tecnodata\Lms\Core\Database;

$lock=base_path('storage/installed.lock');
if(is_file($lock)){http_response_code(404);exit('Instalação já concluída.');}

$error=null;
if($_SERVER['REQUEST_METHOD']==='POST'){
    try{
        $sql=file_get_contents(base_path('database/schema.sql'));
        foreach(preg_split('/;\s*(?:\r?\n|$)/',$sql) as $statement){
            $statement=trim($statement);
            if($statement!=='' && !str_starts_with($statement,'--')) Database::pdo()->exec($statement);
        }
        $name=trim((string)($_POST['name']??'Administrador'));
        $email=trim((string)($_POST['email']??''));
        $password=(string)($_POST['password']??'');
        if($email===''||strlen($password)<8) throw new RuntimeException('Informe e-mail e senha com pelo menos 8 caracteres.');
        Database::execute("INSERT INTO users(user_type,username,name,email,password_hash,status,created_at,updated_at) VALUES('staff',?,?,?,?,'active',?,?)",[$email,$name,$email,password_hash($password,PASSWORD_DEFAULT),Clock::sql(),Clock::sql()]);
        $uid=Database::id();
        $role=Database::fetch("SELECT id FROM roles WHERE slug='super_admin'");
        Database::execute("INSERT INTO user_role_assignments(user_id,role_id,context_type,context_id,created_at) VALUES(?,?,'system',NULL,?)",[$uid,$role['id'],Clock::sql()]);
        if(!is_dir(base_path('storage'))) mkdir(base_path('storage'),0775,true);
        file_put_contents($lock,"installed ".Clock::sql());
        header('Location: /login');exit;
    }catch(Throwable $e){$error=$e->getMessage();}
}
?><!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Instalar Tecnodata LMS</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="assets/css/app.css"></head><body class="auth-body"><main class="auth-card"><div class="brand-mark">T</div><h1>Instalar Tecnodata LMS</h1><p class="text-secondary">Banco local, timezone America/Sao_Paulo.</p><?php if($error):?><div class="alert alert-danger"><?=e($error)?></div><?php endif;?><form method="post"><div class="mb-3"><label class="form-label">Nome</label><input class="form-control" name="name" required></div><div class="mb-3"><label class="form-label">E-mail</label><input class="form-control" type="email" name="email" required></div><div class="mb-4"><label class="form-label">Senha</label><input class="form-control" type="password" name="password" minlength="8" required></div><button class="btn btn-brand w-100">Criar banco e administrador</button></form></main></body></html>
