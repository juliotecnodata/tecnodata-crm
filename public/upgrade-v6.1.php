<?php
require dirname(__DIR__).'/app/bootstrap.php';
use Tecnodata\CRM\Core\DB;

$cfg=$GLOBALS['config']['installer'];
if(empty($cfg['enabled'])) exit('Ative temporariamente installer.enabled para atualizar.');
if(!hash_equals((string)$cfg['token'],(string)($_GET['token']??''))){http_response_code(403);exit('Token inválido.');}

$tables = [
    'users','sellers','clients','orders','service_orders',
    'financial_movements','client_metrics','activities','tasks',
    'monthly_goals','seller_goals','sync_runs','sync_states',
    'financial_accounts','collection_actions','collection_user_goals',
    'collection_client_adjustments'
];

try {
    $pdo=DB::conn();
    $db=$pdo->query('SELECT DATABASE()')->fetchColumn();

    // Banco padrão
    $pdo->exec("ALTER DATABASE `{$db}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

    $done=[];
    foreach($tables as $table){
        $exists=DB::fetch("SELECT COUNT(*) n FROM information_schema.tables WHERE table_schema=? AND table_name=?",[$db,$table]);
        if(!($exists['n']??0)) continue;

        // Converte textos da tabela para uma única collation.
        // InnoDB mantém dados e índices; chaves numéricas/FKs não são afetadas.
        $pdo->exec("ALTER TABLE `{$table}` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $done[]=$table;
    }

    echo '<h2>Atualização V6.1 concluída.</h2>';
    echo '<p>Collation padronizada para <strong>utf8mb4_unicode_ci</strong>.</p>';
    echo '<p>Tabelas ajustadas: '.count($done).'</p>';
    echo '<p>Desative novamente <code>installer.enabled</code> e acesse <a href="'.APP_URL.'/cobranca.php">Cobrança</a>.</p>';
} catch(Throwable $e) {
    http_response_code(500);
    echo '<h2>Erro no upgrade V6.1</h2><pre>'.htmlspecialchars($e->getMessage()).'</pre>';
}
