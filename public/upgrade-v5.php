<?php
require dirname(__DIR__).'/app/bootstrap.php';
use Tecnodata\CRM\Core\DB;
$cfg=$GLOBALS['config']['installer'];
if(empty($cfg['enabled'])) exit('Ative temporariamente installer.enabled para atualizar.');
if(!hash_equals((string)$cfg['token'],(string)($_GET['token']??''))){http_response_code(403);exit('Token inválido.');}
try{
    DB::conn()->exec("ALTER TABLE sellers MODIFY COLUMN goal_mode ENUM('sales','collection','sales_collection') NOT NULL DEFAULT 'sales_collection'");
    echo '<h2>Atualização V5 concluída.</h2><p>Perfis de vendas e cobrança separados.</p>';
}catch(Throwable $e){http_response_code(500);echo '<h2>Erro no upgrade</h2><pre>'.htmlspecialchars($e->getMessage()).'</pre>';}
