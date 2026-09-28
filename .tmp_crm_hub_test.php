<?php
declare(strict_types=1);
$_SERVER['HTTP_HOST']='localhost';
require __DIR__.'/app/bootstrap.php';
$router=new Router();
require APP_ROOT.'/routes.php';
$property=new ReflectionProperty(Auth::class,'requestUser');
$property->setAccessible(true);
foreach(['admin','supervisor','seller'] as $role){
 $user=DB::one("SELECT * FROM users WHERE active=1 AND role=? ORDER BY id LIMIT 1",[$role]);
 if(!$user){echo $role."=missing\n";continue;}
 unset($user['password_hash'],$user['active']);
 $_SESSION['user']=$user;$property->setValue(null,$user);
 ob_start();$router->dispatch('GET','/crm');$html=(string)ob_get_clean();
 echo $role.'='.(str_contains($html,'Incluir nova conta')&&str_contains($html,'/commercial/accounts/new')?'ok':'failed')."\n";
}
