<?php
require dirname(__DIR__).'/app/bootstrap.php';
use Tecnodata\CRM\Core\DB;
$cfg=$GLOBALS['config']['installer'];
if(empty($cfg['enabled']))exit('Ative temporariamente installer.enabled para atualizar.');
if(!hash_equals((string)$cfg['token'],(string)($_GET['token']??''))){http_response_code(403);exit('Token inválido.');}
try{
 $pdo=DB::conn();
 $role=DB::fetch("SHOW COLUMNS FROM users LIKE 'role'");
 if($role && strpos((string)$role['Type'],'collector')===false)$pdo->exec("ALTER TABLE users MODIFY COLUMN role ENUM('seller','collector','supervisor','admin') NOT NULL DEFAULT 'seller'");
 $cols=array_column(DB::all("SHOW COLUMNS FROM collection_actions"),'Field');
 $seller=DB::fetch("SHOW COLUMNS FROM collection_actions LIKE 'seller_omie_code'");
 if($seller && strtoupper((string)$seller['Null'])!=='YES')$pdo->exec("ALTER TABLE collection_actions MODIFY COLUMN seller_omie_code VARCHAR(80) NULL");
 if(!in_array('channel',$cols,true))$pdo->exec("ALTER TABLE collection_actions ADD COLUMN channel ENUM('ligacao','whatsapp','email','outro') NOT NULL DEFAULT 'ligacao' AFTER action_type");
 if(!in_array('result',$cols,true))$pdo->exec("ALTER TABLE collection_actions ADD COLUMN result ENUM('falou','nao_atendeu','promessa','acordo','pagamento','sem_previsao') NOT NULL DEFAULT 'falou' AFTER channel");
 if(!in_array('debt_before',$cols,true))$pdo->exec("ALTER TABLE collection_actions ADD COLUMN debt_before DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER amount");
 if(!in_array('debt_after',$cols,true))$pdo->exec("ALTER TABLE collection_actions ADD COLUMN debt_after DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER debt_before");
 $pdo->exec("CREATE TABLE IF NOT EXISTS collection_user_goals(id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,user_id INT UNSIGNED NOT NULL,month_ref CHAR(7) NOT NULL,amount_goal DECIMAL(15,2) NOT NULL DEFAULT 0,contact_goal INT NOT NULL DEFAULT 0,created_at DATETIME NOT NULL,updated_at DATETIME NOT NULL,UNIQUE KEY uq_collection_user_goal(user_id,month_ref),FOREIGN KEY(user_id) REFERENCES users(id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
 $pdo->exec("CREATE TABLE IF NOT EXISTS collection_client_adjustments(client_id BIGINT UNSIGNED PRIMARY KEY,pending_received DECIMAL(15,2) NOT NULL DEFAULT 0,baseline_omie_debt DECIMAL(15,2) NOT NULL DEFAULT 0,last_payment_at DATETIME NULL,updated_at DATETIME NOT NULL,FOREIGN KEY(client_id) REFERENCES clients(id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
 echo '<h2>V6 instalada com sucesso.</h2><p>Perfil Cobrança, carteira geral de devedores, metas individuais e abatimento local ativados.</p><p>Desative novamente installer.enabled e <a href="'.APP_URL.'/cobranca.php">abra Cobrança</a>.</p>';
}catch(Throwable $e){http_response_code(500);echo '<h2>Erro</h2><pre>'.htmlspecialchars($e->getMessage()).'</pre>';}
