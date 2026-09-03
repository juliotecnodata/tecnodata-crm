<?php
require dirname(__DIR__) . '/app/bootstrap.php';
use Tecnodata\CRM\Core\DB;
$cfg=$GLOBALS['config']['installer'];
if(empty($cfg['enabled'])) exit('Instalador desativado.');
if(!hash_equals((string)$cfg['token'],(string)($_GET['token']??''))) {http_response_code(403);exit('Token inválido.');}
try{
 $sql=file_get_contents(APP_ROOT.'/database/schema.sql');
 DB::conn()->exec($sql);
 $admin=$cfg['admin'];
 $exists=DB::fetch("SELECT id FROM users WHERE email=?",[$admin['email']]);
 if(!$exists){
  DB::exec("INSERT INTO users(name,email,password_hash,role,active,created_at) VALUES (?,?,?,'admin',1,NOW())",
   [$admin['name'],mb_strtolower($admin['email']),password_hash($admin['password'],PASSWORD_DEFAULT)]);
 }
 echo '<h2>Instalação concluída.</h2><p>Desative installer.enabled em config/config.php e acesse <a href="'.APP_URL.'/login.php">o login</a>.</p>';
}catch(Throwable $e){
 http_response_code(500); echo '<h2>Erro na instalação</h2><pre>'.htmlspecialchars($e->getMessage()).'</pre>';
}
