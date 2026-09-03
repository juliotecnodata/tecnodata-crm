<?php
require dirname(__DIR__).'/app/bootstrap.php';
use Tecnodata\CRM\Core\DB;

$cfg=$GLOBALS['config']['installer'];
if(empty($cfg['enabled'])) exit('Ative temporariamente installer.enabled para atualizar.');
if(!hash_equals((string)$cfg['token'],(string)($_GET['token']??''))){http_response_code(403);exit('Token inválido.');}

function indexExists65(string $table,string $index): bool {
    $db=(string)DB::conn()->query('SELECT DATABASE()')->fetchColumn();
    return (bool)DB::fetch("SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=? AND TABLE_NAME=? AND INDEX_NAME=? LIMIT 1",[$db,$table,$index]);
}
function addIndex65(string $table,string $index,string $sql): void {
    if(!indexExists65($table,$index)) DB::conn()->exec($sql);
}

try{
    addIndex65('financial_movements','idx_fin_collection',
        "ALTER TABLE financial_movements ADD INDEX idx_fin_collection(account_omie_code,status,client_omie_code)");
    addIndex65('financial_movements','idx_fin_client_status_account',
        "ALTER TABLE financial_movements ADD INDEX idx_fin_client_status_account(client_omie_code,status,account_omie_code)");
    addIndex65('collection_actions','idx_collection_client_deleted_date',
        "ALTER TABLE collection_actions ADD INDEX idx_collection_client_deleted_date(client_id,deleted_at,created_at)");
    addIndex65('collection_actions','idx_collection_user_deleted_date',
        "ALTER TABLE collection_actions ADD INDEX idx_collection_user_deleted_date(user_id,deleted_at,created_at,client_id)");
    addIndex65('collection_actions','idx_collection_result_deleted_date',
        "ALTER TABLE collection_actions ADD INDEX idx_collection_result_deleted_date(result,deleted_at,created_at,client_id)");

    echo '<h2>Atualização V6.5 concluída.</h2>';
    echo '<p>A carteira de cobrança agora usa paginação/filtros server-side e índices próprios.</p>';
    echo '<p>Desative novamente <code>installer.enabled</code> e abra <a href="'.APP_URL.'/cobranca.php">Cobrança</a>.</p>';
}catch(Throwable $e){
    http_response_code(500);
    echo '<h2>Erro no upgrade V6.5</h2><pre>'.htmlspecialchars($e->getMessage()).'</pre>';
}
