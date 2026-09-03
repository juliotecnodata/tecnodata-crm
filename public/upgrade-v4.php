<?php
require dirname(__DIR__).'/app/bootstrap.php';
use Tecnodata\CRM\Core\DB;

$cfg=$GLOBALS['config']['installer'];
if(empty($cfg['enabled'])) exit('Ative temporariamente installer.enabled para atualizar.');
if(!hash_equals((string)$cfg['token'],(string)($_GET['token']??''))){http_response_code(403);exit('Token inválido.');}

try{
    $columns=array_column(DB::all('SHOW COLUMNS FROM sellers'),'Field');
    if(!in_array('is_virtual',$columns,true)) DB::conn()->exec("ALTER TABLE sellers ADD COLUMN is_virtual TINYINT(1) NOT NULL DEFAULT 0 AFTER goal_mode");
    DB::conn()->exec("CREATE TABLE IF NOT EXISTS service_orders (
      id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      omie_service_order_code VARCHAR(120) NOT NULL UNIQUE,
      display_number VARCHAR(60) NULL,
      client_omie_code VARCHAR(80) NULL,
      seller_omie_code VARCHAR(80) NULL,
      inclusion_date DATE NULL,
      total DECIMAL(15,2) NOT NULL DEFAULT 0,
      status VARCHAR(60) NULL,
      stage_code VARCHAR(10) NULL,
      service_description TEXT NULL,
      external_reference VARCHAR(120) NULL,
      raw_json JSON NULL,
      updated_at DATETIME NOT NULL,
      INDEX idx_service_client(client_omie_code),
      INDEX idx_service_seller_date(seller_omie_code,inclusion_date),
      INDEX idx_service_date(inclusion_date),
      INDEX idx_service_status(status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    DB::exec("UPDATE sellers SET active=1,is_virtual=1,goal_mode='sales_collection' WHERE omie_code='594326005' OR name IN('EAD Reciclagem','Suporte - Pet Cursos')");
    echo '<h2>Atualização V4 concluída.</h2><p>Ordens de Serviço separadas e vendedor virtual configurado.</p>';
}catch(Throwable $e){http_response_code(500);echo '<h2>Erro no upgrade</h2><pre>'.htmlspecialchars($e->getMessage()).'</pre>';}
