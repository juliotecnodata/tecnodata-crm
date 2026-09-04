<?php
require dirname(__DIR__).'/app/bootstrap.php';

use Tecnodata\CRM\Core\DB;

$cfg=$GLOBALS['config']['installer'];
if(empty($cfg['enabled']))exit('Ative temporariamente installer.enabled para atualizar.');
if(!hash_equals((string)$cfg['token'],(string)($_GET['token']??''))){http_response_code(403);exit('Token inválido.');}

function colExists71(string $table,string $column): bool {
 $db=(string)DB::conn()->query('SELECT DATABASE()')->fetchColumn();
 return (bool)DB::fetch("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=? AND TABLE_NAME=? AND COLUMN_NAME=? LIMIT 1",[$db,$table,$column]);
}

try{
 DB::conn()->exec("CREATE TABLE IF NOT EXISTS sales_payment_terms (
  code VARCHAR(3) PRIMARY KEY,description VARCHAR(120) NOT NULL,installments INT NOT NULL DEFAULT 0,
  days_list VARCHAR(120) NULL,shift_days INT NOT NULL DEFAULT 0,active TINYINT(1) NOT NULL DEFAULT 1,
  raw_json JSON NULL,updated_at DATETIME NOT NULL,
  INDEX idx_sales_payment_terms_active(active,description)
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

 DB::conn()->exec("CREATE TABLE IF NOT EXISTS tax_scenarios (
  omie_code VARCHAR(80) PRIMARY KEY,name VARCHAR(120) NOT NULL,is_default TINYINT(1) NOT NULL DEFAULT 0,
  active TINYINT(1) NOT NULL DEFAULT 1,raw_json JSON NULL,updated_at DATETIME NOT NULL,
  INDEX idx_tax_scenarios_active(active,is_default,name)
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

 DB::conn()->exec("CREATE TABLE IF NOT EXISTS stock_locations (
  omie_code VARCHAR(80) PRIMARY KEY,code VARCHAR(50) NULL,name VARCHAR(250) NOT NULL,
  sale_enabled TINYINT(1) NOT NULL DEFAULT 0,is_default TINYINT(1) NOT NULL DEFAULT 0,
  active TINYINT(1) NOT NULL DEFAULT 1,raw_json JSON NULL,updated_at DATETIME NOT NULL,
  INDEX idx_stock_locations_sale(active,sale_enabled,is_default,name)
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

 DB::conn()->exec("CREATE TABLE IF NOT EXISTS payment_methods (
  code VARCHAR(2) PRIMARY KEY,description VARCHAR(100) NOT NULL,raw_json JSON NULL,updated_at DATETIME NOT NULL
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

 DB::conn()->exec("CREATE TABLE IF NOT EXISTS document_types (
  code VARCHAR(5) PRIMARY KEY,description VARCHAR(100) NOT NULL,raw_json JSON NULL,updated_at DATETIME NOT NULL
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

 $columns=[
  'default_payment_term_code'=>"VARCHAR(3) NULL AFTER installment_code",
  'default_payment_method_code'=>"VARCHAR(2) NULL AFTER default_payment_term_code",
  'default_document_type_code'=>"VARCHAR(5) NULL AFTER default_payment_method_code",
  'default_tax_scenario_code'=>"VARCHAR(80) NULL AFTER default_document_type_code",
  'default_stock_location_code'=>"VARCHAR(80) NULL AFTER default_tax_scenario_code",
  'allow_seller_freight'=>"TINYINT(1) NOT NULL DEFAULT 0 AFTER freight_mode",
 ];
 foreach($columns as $column=>$definition){
  if(!colExists71('sales_order_settings',$column)){
   DB::conn()->exec("ALTER TABLE sales_order_settings ADD COLUMN {$column} {$definition}");
  }
 }

 foreach(['payment_terms','tax_scenarios','stock_locations','payment_methods','document_types'] as $module){
  if(!DB::fetch("SELECT 1 FROM sync_states WHERE module_key=? LIMIT 1",[$module])){
   DB::exec("INSERT INTO sync_states(module_key,status,current_page,total_pages,processed,created_at,updated_at)
             VALUES(?,'idle',0,0,0,NOW(),NOW())",[$module]);
  }
 }

 // O antigo 999 era apenas um placeholder e não pode criar pedido real sem lista_parcelas.
 DB::exec("UPDATE sales_order_settings SET installment_code=NULL WHERE installment_code='999'");

 echo '<h2>Atualização V7.1 concluída.</h2>';
 echo '<p>A integração de pedidos está preparada para usar condições de pagamento, cenário fiscal, local de estoque, meio de pagamento e tipo de documento da Omie.</p>';
 echo '<p>Agora sincronize os cinco novos cadastros auxiliares e salve os padrões do Pedido de Venda.</p>';
 echo '<p>Desative novamente <code>installer.enabled</code>.</p>';
}catch(Throwable $e){
 http_response_code(500);
 echo '<h2>Erro no upgrade V7.1</h2><pre>'.htmlspecialchars($e->getMessage()).'</pre>';
}
