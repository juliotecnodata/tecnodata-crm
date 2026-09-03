<?php
require dirname(__DIR__).'/app/bootstrap.php';
use Tecnodata\CRM\Core\DB;

$cfg=$GLOBALS['config']['installer'];
if(empty($cfg['enabled'])) exit('Ative temporariamente installer.enabled para atualizar.');
if(!hash_equals((string)$cfg['token'],(string)($_GET['token']??''))){
    http_response_code(403);
    exit('Token inválido.');
}

function columnExists(string $table,string $column): bool {
    $db=(string)DB::conn()->query('SELECT DATABASE()')->fetchColumn();
    return (bool)DB::fetch(
        "SELECT 1
         FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA=? AND TABLE_NAME=? AND COLUMN_NAME=?
         LIMIT 1",
        [$db,$table,$column]
    );
}

function addColumnIfMissing(string $table,string $column,string $sql): void {
    if(!columnExists($table,$column)){
        DB::conn()->exec($sql);
    }
}

try{
    $pdo=DB::conn();

    // Garante que o ENUM comercial comporte "acordo".
    $pdo->exec("ALTER TABLE activities
                MODIFY result ENUM('falou','nao_atendeu','interessado','sem_interesse','acordo') NOT NULL");

    addColumnIfMissing('activities','updated_by',
        "ALTER TABLE activities ADD COLUMN updated_by INT UNSIGNED NULL AFTER updated_at");
    addColumnIfMissing('activities','deleted_at',
        "ALTER TABLE activities ADD COLUMN deleted_at DATETIME NULL AFTER updated_by");
    addColumnIfMissing('activities','deleted_by',
        "ALTER TABLE activities ADD COLUMN deleted_by INT UNSIGNED NULL AFTER deleted_at");

    addColumnIfMissing('collection_actions','pending_amount',
        "ALTER TABLE collection_actions ADD COLUMN pending_amount DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER amount");
    addColumnIfMissing('collection_actions','updated_at',
        "ALTER TABLE collection_actions ADD COLUMN updated_at DATETIME NULL AFTER created_at");
    addColumnIfMissing('collection_actions','updated_by',
        "ALTER TABLE collection_actions ADD COLUMN updated_by INT UNSIGNED NULL AFTER updated_at");
    addColumnIfMissing('collection_actions','deleted_at',
        "ALTER TABLE collection_actions ADD COLUMN deleted_at DATETIME NULL AFTER updated_by");
    addColumnIfMissing('collection_actions','deleted_by',
        "ALTER TABLE collection_actions ADD COLUMN deleted_by INT UNSIGNED NULL AFTER deleted_at");

    $pdo->exec("CREATE TABLE IF NOT EXISTS interaction_audit(
      id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      entity_type ENUM('sales_activity','collection_action') NOT NULL,
      entity_id BIGINT UNSIGNED NOT NULL,
      operation ENUM('create','update','delete') NOT NULL,
      user_id INT UNSIGNED NOT NULL,
      before_json JSON NULL,
      after_json JSON NULL,
      created_at DATETIME NOT NULL,
      FOREIGN KEY(user_id) REFERENCES users(id),
      INDEX idx_interaction_audit_entity(entity_type,entity_id),
      INDEX idx_interaction_audit_user_date(user_id,created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Só reconstrói pending_amount se a coluna existir e houver ajustes.
    $pdo->exec("UPDATE collection_actions
                SET pending_amount=0
                WHERE action_type='payment' AND deleted_at IS NULL");

    $adjustments=DB::all("SELECT * FROM collection_client_adjustments WHERE pending_received>0");
    foreach($adjustments as $adj){
        $remaining=(float)$adj['pending_received'];
        $payments=DB::all(
            "SELECT id,amount
             FROM collection_actions
             WHERE client_id=? AND action_type='payment' AND deleted_at IS NULL
             ORDER BY created_at DESC,id DESC",
            [$adj['client_id']]
        );

        foreach($payments as $pay){
            if($remaining<=0) break;
            $take=min($remaining,(float)$pay['amount']);
            DB::exec("UPDATE collection_actions SET pending_amount=? WHERE id=?",[$take,$pay['id']]);
            $remaining-=$take;
        }
    }

    echo '<h2>Atualização V6.4.1 concluída.</h2>';
    echo '<p>O erro de sintaxe do MariaDB foi corrigido.</p>';
    echo '<p>Visão de gestão, edição/exclusão auditável e sinalizações estão prontas.</p>';
    echo '<p>Desative novamente <code>installer.enabled</code> e acesse <a href="'.APP_URL.'/gestao.php">Visão de gestão</a>.</p>';

}catch(Throwable $e){
    http_response_code(500);
    echo '<h2>Erro no upgrade V6.4.1</h2><pre>'.htmlspecialchars($e->getMessage()).'</pre>';
}
