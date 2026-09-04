<?php
require dirname(__DIR__).'/app/bootstrap.php';

use Tecnodata\CRM\Core\DB;

$cfg=$GLOBALS['config']['installer'];
if(empty($cfg['enabled']))exit('Ative temporariamente installer.enabled para atualizar.');
if(!hash_equals((string)$cfg['token'],(string)($_GET['token']??''))){http_response_code(403);exit('Token inválido.');}

function tableExists72(string $table): bool {
 $db=(string)DB::conn()->query('SELECT DATABASE()')->fetchColumn();
 return (bool)DB::fetch("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=? AND TABLE_NAME=? LIMIT 1",[$db,$table]);
}
function colExists72(string $table,string $column): bool {
 $db=(string)DB::conn()->query('SELECT DATABASE()')->fetchColumn();
 return (bool)DB::fetch("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=? AND TABLE_NAME=? AND COLUMN_NAME=? LIMIT 1",[$db,$table,$column]);
}
function addCol72(string $table,string $column,string $definition): void {
 if(!colExists72($table,$column))DB::conn()->exec("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
}

try{
 DB::conn()->exec("CREATE TABLE IF NOT EXISTS products (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  omie_code VARCHAR(80) NOT NULL,
  integration_code VARCHAR(120) NULL,
  internal_code VARCHAR(120) NULL,
  description VARCHAR(255) NOT NULL,
  unit VARCHAR(20) NULL,ncm VARCHAR(30) NULL,cfop VARCHAR(30) NULL,
  unit_price DECIMAL(15,4) NOT NULL DEFAULT 0,
  stock_qty DECIMAL(15,4) NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  raw_json JSON NULL,updated_at DATETIME NOT NULL,
  UNIQUE KEY uq_products_omie(omie_code),
  INDEX idx_products_description(description),
  INDEX idx_products_internal_code(internal_code),
  INDEX idx_products_active(active)
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
 if(!colExists72('products','stock_qty'))addCol72('products','stock_qty',"DECIMAL(15,4) NULL AFTER unit_price");

 DB::conn()->exec("CREATE TABLE IF NOT EXISTS sales_categories (
  code VARCHAR(80) PRIMARY KEY,description VARCHAR(255) NOT NULL,nature VARCHAR(80) NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,raw_json JSON NULL,updated_at DATETIME NOT NULL,
  INDEX idx_sales_categories_active(active)
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

 DB::conn()->exec("CREATE TABLE IF NOT EXISTS sales_order_settings (
  id TINYINT UNSIGNED PRIMARY KEY,
  default_stage_code VARCHAR(10) NULL,default_category_code VARCHAR(80) NULL,default_account_code VARCHAR(80) NULL,
  installment_code VARCHAR(30) NULL,
  default_payment_term_code VARCHAR(3) NULL,default_payment_method_code VARCHAR(2) NULL,
  default_document_type_code VARCHAR(5) NULL,default_tax_scenario_code VARCHAR(80) NULL,
  default_stock_location_code VARCHAR(80) NULL,consumer_final CHAR(1) NOT NULL DEFAULT 'S',
  send_email CHAR(1) NOT NULL DEFAULT 'N',freight_mode VARCHAR(10) NOT NULL DEFAULT '9',
  allow_seller_freight TINYINT(1) NOT NULL DEFAULT 0,updated_by INT UNSIGNED NULL,updated_at DATETIME NOT NULL
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

 $cols=[
  'default_payment_term_code'=>"VARCHAR(3) NULL AFTER installment_code",
  'default_payment_method_code'=>"VARCHAR(2) NULL AFTER default_payment_term_code",
  'default_document_type_code'=>"VARCHAR(5) NULL AFTER default_payment_method_code",
  'default_tax_scenario_code'=>"VARCHAR(80) NULL AFTER default_document_type_code",
  'default_stock_location_code'=>"VARCHAR(80) NULL AFTER default_tax_scenario_code",
  'allow_seller_freight'=>"TINYINT(1) NOT NULL DEFAULT 0 AFTER freight_mode",
 ];
 foreach($cols as $col=>$def)addCol72('sales_order_settings',$col,$def);

 DB::conn()->exec("CREATE TABLE IF NOT EXISTS order_creation_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,integration_code VARCHAR(120) NOT NULL,
  omie_order_code VARCHAR(80) NULL,omie_order_number VARCHAR(80) NULL,client_id BIGINT UNSIGNED NOT NULL,
  seller_omie_code VARCHAR(80) NULL,created_by INT UNSIGNED NOT NULL,total DECIMAL(15,2) NOT NULL DEFAULT 0,
  status ENUM('success','error') NOT NULL,request_json JSON NULL,response_json JSON NULL,error_message TEXT NULL,
  created_at DATETIME NOT NULL,UNIQUE KEY uq_order_creation_integration(integration_code),
  INDEX idx_order_creation_user_date(created_by,created_at),INDEX idx_order_creation_client_date(client_id,created_at)
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

 DB::conn()->exec("CREATE TABLE IF NOT EXISTS sales_payment_terms (
  code VARCHAR(3) PRIMARY KEY,description VARCHAR(120) NOT NULL,installments INT NOT NULL DEFAULT 0,
  days_list VARCHAR(120) NULL,shift_days INT NOT NULL DEFAULT 0,active TINYINT(1) NOT NULL DEFAULT 1,
  raw_json JSON NULL,updated_at DATETIME NOT NULL,INDEX idx_sales_payment_terms_active(active,description)
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

 DB::exec("INSERT INTO sales_order_settings(id,consumer_final,send_email,freight_mode,allow_seller_freight,updated_at)
           VALUES(1,'S','N','9',0,NOW()) ON DUPLICATE KEY UPDATE id=id");

 foreach(['products','categories','payment_terms','tax_scenarios','stock_locations','payment_methods','document_types'] as $module){
  if(!DB::fetch("SELECT 1 FROM sync_states WHERE module_key=? LIMIT 1",[$module])){
   DB::exec("INSERT INTO sync_states(module_key,status,current_page,total_pages,processed,created_at,updated_at)
             VALUES(?,'idle',0,0,0,NOW(),NOW())",[$module]);
  }else{
   DB::exec("UPDATE sync_states SET status='idle',current_page=0,total_pages=0,processed=0,last_error=NULL,context_json=NULL,updated_at=NOW() WHERE module_key=?",[$module]);
  }
 }

 DB::exec("UPDATE sales_order_settings SET installment_code=NULL WHERE installment_code='999'");

 echo '<h2>Atualização V7.2 concluída.</h2>';
 echo '<p>A estrutura de pedidos foi reconstruída de forma idempotente, inclusive para instalações que não concluíram V7.0/V7.1.</p>';
 echo '<p>Próximo passo: acesse <strong>Diagnóstico de Pedidos Omie</strong>, teste as APIs e só depois sincronize os cadastros.</p>';
 echo '<p>Desative novamente <code>installer.enabled</code>.</p>';
}catch(Throwable $e){
 http_response_code(500);
 echo '<h2>Erro no upgrade V7.2</h2><pre>'.htmlspecialchars($e->getMessage()).'</pre>';
}
