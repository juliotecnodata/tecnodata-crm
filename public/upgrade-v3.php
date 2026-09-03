<?php
require dirname(__DIR__).'/app/bootstrap.php';
use Tecnodata\CRM\Core\DB;

$cfg=$GLOBALS['config']['installer'];
if(empty($cfg['enabled'])) exit('Ative temporariamente installer.enabled para atualizar.');
if(!hash_equals((string)$cfg['token'],(string)($_GET['token']??''))){http_response_code(403);exit('Token inválido.');}

$messages=[];
$addColumn=function(string $table,string $column,string $definition)use(&$messages):void{
    $columns=array_column(DB::all("SHOW COLUMNS FROM {$table}"),'Field');
    if(!in_array($column,$columns,true)){
        DB::conn()->exec("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
        $messages[]="{$table}.{$column} criado";
    }
};
$addIndex=function(string $table,string $index,string $definition)use(&$messages):void{
    $indexes=array_column(DB::all("SHOW INDEX FROM {$table}"),'Key_name');
    if(!in_array($index,$indexes,true)){
        DB::conn()->exec("ALTER TABLE {$table} ADD INDEX {$index} {$definition}");
        $messages[]="Índice {$index} criado";
    }
};

try{
    $addColumn('sellers','omie_active','TINYINT(1) NOT NULL DEFAULT 1 AFTER active');
    $addColumn('sellers','goal_mode',"ENUM('collection','sales_collection') NOT NULL DEFAULT 'sales_collection' AFTER omie_active");
    $addColumn('seller_goals','collection_goal','DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER goal3');
    $addColumn('seller_goals','debtor_contact_goal','INT NOT NULL DEFAULT 0 AFTER collection_goal');
    $addColumn('financial_movements','account_omie_code','VARCHAR(80) NULL AFTER status');
    $addColumn('financial_movements','last_seen_run_id','BIGINT UNSIGNED NULL AFTER account_omie_code');
    $addColumn('sync_states','context_json','JSON NULL AFTER last_success_at');
    $addIndex('financial_movements','idx_fin_account','(account_omie_code)');
    $addIndex('financial_movements','idx_fin_seen','(last_seen_run_id)');

    DB::conn()->exec("CREATE TABLE IF NOT EXISTS financial_accounts (
      omie_code VARCHAR(80) PRIMARY KEY,
      name VARCHAR(160) NOT NULL,
      account_type VARCHAR(10) NULL,
      active TINYINT(1) NOT NULL DEFAULT 1,
      selected TINYINT(1) NOT NULL DEFAULT 0,
      updated_at DATETIME NOT NULL,
      INDEX idx_fin_account_selected(selected,active)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    DB::conn()->exec("CREATE TABLE IF NOT EXISTS collection_actions (
      id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      seller_omie_code VARCHAR(80) NOT NULL,
      client_id BIGINT UNSIGNED NOT NULL,
      user_id INT UNSIGNED NOT NULL,
      action_type ENUM('contact','promise','agreement','payment') NOT NULL,
      amount DECIMAL(15,2) NOT NULL DEFAULT 0,
      promised_for DATE NULL,
      notes TEXT NULL,
      created_at DATETIME NOT NULL,
      FOREIGN KEY(client_id) REFERENCES clients(id) ON DELETE CASCADE,
      FOREIGN KEY(user_id) REFERENCES users(id),
      INDEX idx_collection_seller_date(seller_omie_code,created_at),
      INDEX idx_collection_client(client_id),
      INDEX idx_collection_type_date(action_type,created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    DB::exec("UPDATE sellers SET omie_active=CASE WHEN JSON_UNQUOTE(JSON_EXTRACT(raw_json,'$.inativo'))='S' THEN 0 ELSE 1 END");
    $messages[]='Estrutura comercial V3 pronta';

    echo '<h2>Atualização V3 concluída.</h2><ul>';
    foreach($messages as $message) echo '<li>'.htmlspecialchars($message).'</li>';
    echo '</ul>';
}catch(Throwable $e){
    http_response_code(500);
    echo '<h2>Erro no upgrade</h2><pre>'.htmlspecialchars($e->getMessage()).'</pre>';
}
