<?php
declare(strict_types=1);
require dirname(__DIR__).'/app/bootstrap.php';
$c=$GLOBALS['config']['installer'];
if(empty($c['enabled']))exit('Instalador desativado.');
if(!hash_equals((string)$c['token'],(string)($_GET['token']??''))){http_response_code(403);exit('Token inválido.');}
try{
 $sql=file_get_contents(APP_ROOT.'/database/schema.sql');if($sql===false)throw new RuntimeException('schema.sql ausente.');
 foreach(preg_split('/;\s*(?:\r?\n|$)/',$sql)?:[] as $s){$s=trim($s);if($s!=='')DB::conn()->exec(DB::sql($s));}
 $a=$c['admin'];$email=mb_strtolower(trim((string)$a['email']));
 if(!DB::one("SELECT id FROM users WHERE email=?",[$email]))DB::exec("INSERT INTO users(name,email,password_hash,role,active,created_at,updated_at) VALUES(?,?,?,'admin',1,NOW(),NOW())",[(string)$a['name'],$email,password_hash((string)$a['password'],PASSWORD_DEFAULT)]);
 echo '<h2>Instalação concluída.</h2><p>Banco existente preservado. As tabelas novas usam o prefixo <strong>'.e(DB::prefix()).'</strong>.</p><p>Desative o instalador e acesse <a href="'.e(APP_URL).'">'.e(APP_URL).'</a>.</p>';
}catch(Throwable $e){http_response_code(500);echo '<h2>Erro</h2><pre>'.e($e->getMessage()).'</pre>';}
