<?php
require dirname(__DIR__).'/app/bootstrap.php';
use Tecnodata\CRM\Core\DB;

$cfg=$GLOBALS['config']['installer'];
if(empty($cfg['enabled']))exit('Ative temporariamente installer.enabled para atualizar.');
if(!hash_equals((string)$cfg['token'],(string)($_GET['token']??''))){http_response_code(403);exit('Token inválido.');}

function colExists615(string $table,string $column): bool {
 $db=(string)DB::conn()->query('SELECT DATABASE()')->fetchColumn();
 return (bool)DB::fetch("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=? AND TABLE_NAME=? AND COLUMN_NAME=? LIMIT 1",[$db,$table,$column]);
}
function idxExists615(string $table,string $index): bool {
 $db=(string)DB::conn()->query('SELECT DATABASE()')->fetchColumn();
 return (bool)DB::fetch("SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=? AND TABLE_NAME=? AND INDEX_NAME=? LIMIT 1",[$db,$table,$index]);
}
try{
 if(!colExists615('orders','stage_code'))DB::conn()->exec("ALTER TABLE orders ADD COLUMN stage_code VARCHAR(10) NULL AFTER status");
 if(!colExists615('orders','stage_name'))DB::conn()->exec("ALTER TABLE orders ADD COLUMN stage_name VARCHAR(120) NULL AFTER stage_code");
 if(!colExists615('orders','stage_changed_at'))DB::conn()->exec("ALTER TABLE orders ADD COLUMN stage_changed_at DATETIME NULL AFTER stage_name");
 if(!idxExists615('orders','idx_orders_stage'))DB::conn()->exec("ALTER TABLE orders ADD INDEX idx_orders_stage(stage_code,order_date)");

 DB::conn()->exec("CREATE TABLE IF NOT EXISTS order_stage_catalog(
   stage_code VARCHAR(10) PRIMARY KEY,
   stage_name VARCHAR(120) NOT NULL,
   default_name VARCHAR(120) NULL,
   active TINYINT(1) NOT NULL DEFAULT 1,
   updated_at DATETIME NOT NULL
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

 $rows=DB::all("SELECT id,raw_json FROM orders WHERE raw_json IS NOT NULL AND (stage_code IS NULL OR stage_code='')");
 $stmt=DB::conn()->prepare("UPDATE orders SET stage_code=? WHERE id=?");
 $updated=0;
 foreach($rows as $row){
   $raw=json_decode((string)$row['raw_json'],true);
   $code=trim((string)($raw['cabecalho']['etapa']??''));
   if($code==='')continue;
   $stmt->execute([str_pad($code,2,'0',STR_PAD_LEFT),(int)$row['id']]);
   $updated++;
 }

 echo '<h2>Atualização V6.15 concluída.</h2>';
 echo '<p>Pedidos agora possuem etapa própria, nome da etapa e data de mudança.</p>';
 echo '<p><strong>'.$updated.'</strong> pedido(s) existentes tiveram o código da etapa recuperado do raw_json.</p>';
 echo '<p>Agora execute a sincronização de <strong>Pedidos</strong> uma vez para carregar os nomes personalizados das etapas Omie e as mudanças de etapa de ontem/hoje.</p>';
 echo '<p>Desative novamente <code>installer.enabled</code>.</p>';
}catch(Throwable $e){
 http_response_code(500);
 echo '<h2>Erro no upgrade V6.15</h2><pre>'.htmlspecialchars($e->getMessage()).'</pre>';
}