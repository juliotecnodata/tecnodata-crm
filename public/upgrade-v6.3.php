<?php
require dirname(__DIR__).'/app/bootstrap.php';
use Tecnodata\CRM\Core\DB;
$cfg=$GLOBALS['config']['installer'];
if(empty($cfg['enabled']))exit('Ative temporariamente installer.enabled para atualizar.');
if(!hash_equals((string)$cfg['token'],(string)($_GET['token']??''))){http_response_code(403);exit('Token inválido.');}
try{
 $cols=DB::all("SHOW COLUMNS FROM tasks");$names=array_column($cols,'Field');
 foreach([
  'pre_notified_at'=>"ALTER TABLE tasks ADD COLUMN pre_notified_at DATETIME NULL AFTER status",
  'due_notified_at'=>"ALTER TABLE tasks ADD COLUMN due_notified_at DATETIME NULL AFTER pre_notified_at",
  'reminder_notified_at'=>"ALTER TABLE tasks ADD COLUMN reminder_notified_at DATETIME NULL AFTER due_notified_at"
 ] as $name=>$sql){if(!in_array($name,$names,true))DB::conn()->exec($sql);}
 DB::conn()->exec("CREATE TABLE IF NOT EXISTS user_notification_settings(
  user_id INT UNSIGNED PRIMARY KEY,browser_enabled TINYINT(1) NOT NULL DEFAULT 1,
  sound_enabled TINYINT(1) NOT NULL DEFAULT 1,volume TINYINT UNSIGNED NOT NULL DEFAULT 70,
  pre_alert_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 10,repeat_after_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 15,
  updated_at DATETIME NOT NULL,FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
 )ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
 $d=$GLOBALS['config']['alerts']??[];$browser=!empty($d['default_browser_enabled'])?1:0;$sound=!empty($d['default_sound_enabled'])?1:0;$volume=(int)($d['default_volume']??70);$pre=(int)($d['default_pre_alert_minutes']??10);$repeat=(int)($d['default_repeat_after_minutes']??15);
 foreach(DB::all("SELECT id FROM users WHERE active=1") as $u)DB::exec("INSERT IGNORE INTO user_notification_settings(user_id,browser_enabled,sound_enabled,volume,pre_alert_minutes,repeat_after_minutes,updated_at)VALUES(?,?,?,?,?,?,NOW())",[$u['id'],$browser,$sound,$volume,$pre,$repeat]);
 echo '<h2>Atualização V6.3 concluída.</h2><p>Central de alertas instalada e retornos existentes preservados.</p><p>Desative novamente <code>installer.enabled</code> e acesse <a href="'.APP_URL.'/alertas.php">Alertas</a>.</p>';
}catch(Throwable $e){http_response_code(500);echo '<h2>Erro no upgrade V6.3</h2><pre>'.htmlspecialchars($e->getMessage()).'</pre>';}
