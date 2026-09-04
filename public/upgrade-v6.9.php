<?php
require dirname(__DIR__).'/app/bootstrap.php';

use Tecnodata\CRM\Core\DB;

$cfg=$GLOBALS['config']['installer'];
if(empty($cfg['enabled']))exit('Ative temporariamente installer.enabled para atualizar.');
if(!hash_equals((string)$cfg['token'],(string)($_GET['token']??''))){http_response_code(403);exit('Token inválido.');}

try{
 DB::conn()->exec("CREATE TABLE IF NOT EXISTS client_portfolio_assignments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  month_ref CHAR(7) NOT NULL,
  client_id BIGINT UNSIGNED NOT NULL,
  seller_omie_code VARCHAR(80) NULL,
  created_by INT UNSIGNED NULL,
  updated_by INT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  UNIQUE KEY uq_client_portfolio_month(month_ref,client_id),
  INDEX idx_portfolio_month_seller(month_ref,seller_omie_code),
  INDEX idx_portfolio_client_month(client_id,month_ref),
  CONSTRAINT fk_portfolio_client FOREIGN KEY(client_id) REFERENCES clients(id) ON DELETE CASCADE,
  CONSTRAINT fk_portfolio_created_by FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_portfolio_updated_by FOREIGN KEY(updated_by) REFERENCES users(id) ON DELETE SET NULL
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

 $month=date('Y-m');
 DB::exec("INSERT INTO client_portfolio_assignments(month_ref,client_id,seller_omie_code,created_by,updated_by,created_at,updated_at)
  SELECT ?,cm.client_id,cm.seller_omie_code,NULL,NULL,NOW(),NOW()
  FROM client_metrics cm
  WHERE cm.seller_omie_code IS NOT NULL
    AND NOT EXISTS(SELECT 1 FROM client_portfolio_assignments pa WHERE pa.month_ref=? AND pa.client_id=cm.client_id)",[$month,$month]);

 echo '<h2>Atualização V6.9 concluída.</h2>';
 echo '<p>A gestão mensal de carteiras por cliente e vendedor foi criada.</p>';
 echo '<p>A carteira atual foi inicializada com o vendedor-base já existente para evitar mudanças inesperadas.</p>';
 echo '<p>Desative novamente <code>installer.enabled</code>.</p>';
}catch(Throwable $e){
 http_response_code(500);
 echo '<h2>Erro no upgrade V6.9</h2><pre>'.htmlspecialchars($e->getMessage()).'</pre>';
}