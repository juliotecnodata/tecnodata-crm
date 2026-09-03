<?php
require dirname(__DIR__).'/app/bootstrap.php';
use Tecnodata\CRM\Core\DB;

$cfg=$GLOBALS['config']['installer'];
if(empty($cfg['enabled'])) exit('Ative temporariamente installer.enabled para atualizar.');
if(!hash_equals((string)$cfg['token'],(string)($_GET['token']??''))){http_response_code(403);exit('Token inválido.');}

$messages=[];
try{
    $cols=DB::all("SHOW COLUMNS FROM sync_runs");
    $names=array_column($cols,'Field');

    if(!in_array('module_key',$names,true)){
        DB::conn()->exec("ALTER TABLE sync_runs ADD COLUMN module_key VARCHAR(40) NULL AFTER started_by");
        $messages[]='module_key criado';
    }
    if(!in_array('heartbeat_at',$names,true)){
        DB::conn()->exec("ALTER TABLE sync_runs ADD COLUMN heartbeat_at DATETIME NULL AFTER started_at");
        $messages[]='heartbeat_at criado';
    }

    DB::conn()->exec("CREATE TABLE IF NOT EXISTS sync_states (
      id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      module_key VARCHAR(40) NOT NULL UNIQUE,
      status ENUM('idle','running','success','error') NOT NULL DEFAULT 'idle',
      current_page INT NOT NULL DEFAULT 0,
      total_pages INT NOT NULL DEFAULT 0,
      processed BIGINT NOT NULL DEFAULT 0,
      last_error TEXT NULL,
      last_success_at DATETIME NULL,
      created_at DATETIME NOT NULL,
      updated_at DATETIME NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $messages[]='sync_states pronta';

    // execução velha presa deixa de bloquear a V2
    DB::exec("UPDATE sync_runs SET status='error',finished_at=NOW(),
              error_message='Execução V1 interrompida; encerrada durante upgrade V2.'
              WHERE status='running'");

    echo '<h2>Atualização V2 concluída.</h2><ul>';
    foreach($messages as $m) echo '<li>'.htmlspecialchars($m).'</li>';
    echo '</ul><p>Agora desative novamente installer.enabled e acesse <a href="'.APP_URL.'/sync.php">Sincronização Omie</a>.</p>';
}catch(Throwable $e){
    http_response_code(500);
    echo '<h2>Erro no upgrade</h2><pre>'.htmlspecialchars($e->getMessage()).'</pre>';
}
