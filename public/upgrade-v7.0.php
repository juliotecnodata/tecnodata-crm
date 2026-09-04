<?php
require dirname(__DIR__).'/app/bootstrap.php';

use Tecnodata\CRM\Core\DB;

$cfg=$GLOBALS['config']['installer'];
if(empty($cfg['enabled']))exit('Ative temporariamente installer.enabled para atualizar.');
if(!hash_equals((string)$cfg['token'],(string)($_GET['token']??''))){http_response_code(403);exit('Token inválido.');}

try{
 DB::conn()->exec("ALTER TABLE interaction_audit MODIFY COLUMN operation ENUM('create','update','delete','reassign') NOT NULL");
 DB::conn()->exec("CREATE TABLE IF NOT EXISTS products (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  omie_code VARCHAR(80) NOT NULL,
  integration_code VARCHAR(120) NULL,
  internal_code VARCHAR(120) NULL,
  description VARCHAR(255) NOT NULL,
  unit VARCHAR(20) NULL,
  ncm VARCHAR(30) NULL,
  cfop VARCHAR(30) NULL,
  unit_price DECIMAL(15,4) NOT NULL DEFAULT 0,
  active TINYINT(1) NOT NULL DEFAULT 1,
  raw_json JSON NULL,
  updated_at DATETIME NOT NULL,
  UNIQUE KEY uq_products_omie(omie_code),
  INDEX idx_products_description(description),
  INDEX idx_products_internal_code(internal_code),
  INDEX idx_products_active(active)
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

 DB::conn()->exec("CREATE TABLE IF NOT EXISTS sales_categories (
  code VARCHAR(80) PRIMARY KEY,
  description VARCHAR(255) NOT NULL,
  nature VARCHAR(80) NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  raw_json JSON NULL,
  updated_at DATETIME NOT NULL,
  INDEX idx_sales_categories_active(active)
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

 DB::conn()->exec("CREATE TABLE IF NOT EXISTS sales_order_settings (
  id TINYINT UNSIGNED PRIMARY KEY,
  default_stage_code VARCHAR(10) NULL,
  default_category_code VARCHAR(80) NULL,
  default_account_code VARCHAR(80) NULL,
  installment_code VARCHAR(30) NULL,
  consumer_final CHAR(1) NOT NULL DEFAULT 'S',
  send_email CHAR(1) NOT NULL DEFAULT 'N',
  freight_mode VARCHAR(10) NOT NULL DEFAULT '9',
  updated_by INT UNSIGNED NULL,
  updated_at DATETIME NOT NULL,
  INDEX idx_order_settings_updated_by(updated_by)
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

 DB::conn()->exec("CREATE TABLE IF NOT EXISTS order_creation_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  integration_code VARCHAR(120) NOT NULL,
  omie_order_code VARCHAR(80) NULL,
  omie_order_number VARCHAR(80) NULL,
  client_id BIGINT UNSIGNED NOT NULL,
  seller_omie_code VARCHAR(80) NULL,
  created_by INT UNSIGNED NOT NULL,
  total DECIMAL(15,2) NOT NULL DEFAULT 0,
  status ENUM('success','error') NOT NULL,
  request_json JSON NULL,
  response_json JSON NULL,
  error_message TEXT NULL,
  created_at DATETIME NOT NULL,
  UNIQUE KEY uq_order_creation_integration(integration_code),
  INDEX idx_order_creation_user_date(created_by,created_at),
  INDEX idx_order_creation_client_date(client_id,created_at)
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

 DB::exec("INSERT INTO sales_order_settings
  (id,installment_code,consumer_final,send_email,freight_mode,updated_at)
  VALUES(1,'999','S','N','9',NOW())
  ON DUPLICATE KEY UPDATE id=id");

 foreach(['products','categories'] as $module){
   $exists=DB::fetch("SELECT 1 FROM sync_states WHERE module_key=? LIMIT 1",[$module]);
   if(!$exists)DB::exec("INSERT INTO sync_states(module_key,status,current_page,total_pages,processed,created_at,updated_at)
                         VALUES(?,'idle',0,0,0,NOW(),NOW())",[$module]);
 }

 echo '<h2>Atualização V7.0 concluída.</h2>';
 echo '<p>Catálogo de produtos, categorias e estrutura para novos pedidos Omie foram criados.</p>';
 echo '<p>Agora execute a sincronização dos módulos <strong>Produtos</strong> e <strong>Categorias</strong>, depois configure os padrões de pedido.</p>';
 echo '<p>Desative novamente <code>installer.enabled</code>.</p>';
}catch(Throwable $e){
 http_response_code(500);
 echo '<h2>Erro no upgrade V7.0</h2><pre>'.htmlspecialchars($e->getMessage()).'</pre>';
}