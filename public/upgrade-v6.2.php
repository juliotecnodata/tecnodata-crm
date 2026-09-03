<?php
require dirname(__DIR__).'/app/bootstrap.php';
use Tecnodata\CRM\Core\DB;

$cfg=$GLOBALS['config']['installer'];
if(empty($cfg['enabled'])) exit('Ative temporariamente installer.enabled para atualizar.');
if(!hash_equals((string)$cfg['token'],(string)($_GET['token']??''))){http_response_code(403);exit('Token inválido.');}

try {
    // Corrige registros legados, se existirem.
    DB::exec("UPDATE financial_movements SET status='PAGTO_PARCIAL' WHERE UPPER(status)='PAGTOPARCIAL'");

    // Corrige contexto salvo do módulo financeiro sem perder o progresso.
    $state=DB::fetch("SELECT * FROM sync_states WHERE module_key='financial'");
    if($state && !empty($state['context_json'])){
        $ctx=json_decode((string)$state['context_json'],true);
        if(is_array($ctx)){
            $statuses=$ctx['statuses']??[];
            $statuses=array_values(array_unique(array_map(
                fn($v)=>mb_strtoupper((string)$v)==='PAGTOPARCIAL'?'PAGTO_PARCIAL':mb_strtoupper((string)$v),
                $statuses
            )));
            if(!$statuses) $statuses=['ATRASADO','PAGTO_PARCIAL'];
            $ctx['statuses']=$statuses;
            DB::exec("UPDATE sync_states SET context_json=?,last_error=NULL,status='idle',updated_at=NOW() WHERE module_key='financial'",
                [json_encode($ctx,JSON_UNESCAPED_UNICODE)]);
        }
    }

    // Libera apenas execução financeira que tenha ficado em erro/running.
    DB::exec("UPDATE sync_runs SET status='error',finished_at=COALESCE(finished_at,NOW()),
              error_message=COALESCE(error_message,'Execução financeira liberada pelo upgrade V6.2.')
              WHERE module_key='financial' AND status='running'");

    echo '<h2>Atualização V6.2 concluída.</h2>';
    echo '<p>Status financeiro Omie corrigido para <strong>PAGTO_PARCIAL</strong>.</p>';
    echo '<p>O progresso salvo foi preservado quando possível.</p>';
    echo '<p>Desative novamente <code>installer.enabled</code> e retome o módulo <a href="'.APP_URL.'/sync.php">Financeiro</a>.</p>';
} catch(Throwable $e) {
    http_response_code(500);
    echo '<h2>Erro no upgrade V6.2</h2><pre>'.htmlspecialchars($e->getMessage()).'</pre>';
}
