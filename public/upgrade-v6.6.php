<?php
require dirname(__DIR__).'/app/bootstrap.php';
use Tecnodata\CRM\Core\DB;

$cfg=$GLOBALS['config']['installer'];
if(empty($cfg['enabled']))exit('Ative temporariamente installer.enabled para atualizar.');
if(!hash_equals((string)$cfg['token'],(string)($_GET['token']??''))){http_response_code(403);exit('Token inválido.');}

function idx66(string $table,string $index): bool {
 $db=(string)DB::conn()->query('SELECT DATABASE()')->fetchColumn();
 return (bool)DB::fetch("SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=? AND TABLE_NAME=? AND INDEX_NAME=? LIMIT 1",[$db,$table,$index]);
}
function addIdx66(string $table,string $index,string $sql): void {if(!idx66($table,$index))DB::conn()->exec($sql);}

try{
 addIdx66('collection_actions','idx_collection_client_result_date',"ALTER TABLE collection_actions ADD INDEX idx_collection_client_result_date(client_id,deleted_at,result,created_at)");
 addIdx66('collection_actions','idx_collection_client_user_date',"ALTER TABLE collection_actions ADD INDEX idx_collection_client_user_date(client_id,deleted_at,user_id,created_at)");
 addIdx66('collection_actions','idx_collection_client_latest',"ALTER TABLE collection_actions ADD INDEX idx_collection_client_latest(client_id,deleted_at,id)");
 echo '<h2>Atualização V6.6 concluída.</h2><p>Filtros de acordo/promessa, histórico de atendimentos e índices de cobrança foram atualizados.</p><p>Desative novamente <code>installer.enabled</code>.</p>';
}catch(Throwable $e){http_response_code(500);echo '<h2>Erro no upgrade V6.6</h2><pre>'.htmlspecialchars($e->getMessage()).'</pre>';}
