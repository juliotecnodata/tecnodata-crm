<?php
require dirname(__DIR__).'/app/bootstrap.php';

use Tecnodata\CRM\Core\DB;

$cfg=$GLOBALS['config']['installer'];
if(empty($cfg['enabled']))exit('Ative temporariamente installer.enabled para atualizar.');
if(!hash_equals((string)$cfg['token'],(string)($_GET['token']??''))){http_response_code(403);exit('Token inválido.');}

function colExists618(string $table,string $column): bool {
 $db=(string)DB::conn()->query('SELECT DATABASE()')->fetchColumn();
 return (bool)DB::fetch("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=? AND TABLE_NAME=? AND COLUMN_NAME=? LIMIT 1",[$db,$table,$column]);
}
function idxExists618(string $table,string $index): bool {
 $db=(string)DB::conn()->query('SELECT DATABASE()')->fetchColumn();
 return (bool)DB::fetch("SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=? AND TABLE_NAME=? AND INDEX_NAME=? LIMIT 1",[$db,$table,$index]);
}

try{
 if(!colExists618('collection_actions','assigned_user_id')){
   DB::conn()->exec("ALTER TABLE collection_actions ADD COLUMN assigned_user_id INT UNSIGNED NULL AFTER user_id");
 }
 if(!colExists618('collection_actions','assigned_at')){
   DB::conn()->exec("ALTER TABLE collection_actions ADD COLUMN assigned_at DATETIME NULL AFTER assigned_user_id");
 }
 if(!colExists618('collection_actions','assigned_by')){
   DB::conn()->exec("ALTER TABLE collection_actions ADD COLUMN assigned_by INT UNSIGNED NULL AFTER assigned_at");
 }
 if(!idxExists618('collection_actions','idx_collection_assigned_deleted_date')){
   DB::conn()->exec("ALTER TABLE collection_actions ADD INDEX idx_collection_assigned_deleted_date(assigned_user_id,deleted_at,created_at,client_id)");
 }

 DB::exec("UPDATE collection_actions
           SET assigned_user_id=user_id,
               assigned_at=COALESCE(assigned_at,created_at),
               assigned_by=COALESCE(assigned_by,user_id)
           WHERE assigned_user_id IS NULL");

 echo '<h2>Atualização V6.18 concluída.</h2>';
 echo '<p>Agora o atendimento preserva <strong>quem realizou</strong> e também possui <strong>responsável atual</strong> separado.</p>';
 echo '<p>Os registros existentes foram atribuídos ao próprio autor para manter a operação consistente.</p>';
 echo '<p>Desative novamente <code>installer.enabled</code>.</p>';
}catch(Throwable $e){
 http_response_code(500);
 echo '<h2>Erro no upgrade V6.18</h2><pre>'.htmlspecialchars($e->getMessage()).'</pre>';
}