<?php
require dirname(__DIR__).'/app/bootstrap.php';

use Tecnodata\CRM\Core\DB;

$cfg=$GLOBALS['config']['installer'];
if(empty($cfg['enabled']))exit('Ative temporariamente installer.enabled para atualizar.');
if(!hash_equals((string)$cfg['token'],(string)($_GET['token']??''))){http_response_code(403);exit('Token inválido.');}

function colExists614(string $table,string $column): bool {
    $db=(string)DB::conn()->query('SELECT DATABASE()')->fetchColumn();
    return (bool)DB::fetch("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=? AND TABLE_NAME=? AND COLUMN_NAME=? LIMIT 1",[$db,$table,$column]);
}
function idxExists614(string $table,string $index): bool {
    $db=(string)DB::conn()->query('SELECT DATABASE()')->fetchColumn();
    return (bool)DB::fetch("SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=? AND TABLE_NAME=? AND INDEX_NAME=? LIMIT 1",[$db,$table,$index]);
}
function extractSeller614(array $payload): ?string {
    foreach(['cCodVendedor','nCodVendedor','codigo_vendedor','codigo_vendedor_omie','nCodigoVendedor','codVendedor','vendedor_omie_code'] as $key){
        if(isset($payload[$key]) && trim((string)$payload[$key])!=='')return trim((string)$payload[$key]);
    }
    foreach(['detalhes','cabecalho','dadosTitulo','titulo','origem'] as $group){
        if(!isset($payload[$group]) || !is_array($payload[$group]))continue;
        foreach(['cCodVendedor','nCodVendedor','codigo_vendedor','codigo_vendedor_omie','nCodigoVendedor','codVendedor','vendedor_omie_code'] as $key){
            if(isset($payload[$group][$key]) && trim((string)$payload[$group][$key])!=='')return trim((string)$payload[$group][$key]);
        }
    }
    return null;
}

try{
    if(!colExists614('financial_movements','seller_omie_code')){
        DB::conn()->exec("ALTER TABLE financial_movements ADD COLUMN seller_omie_code VARCHAR(80) NULL AFTER status");
    }
    if(!idxExists614('financial_movements','idx_fin_seller_status')){
        DB::conn()->exec("ALTER TABLE financial_movements ADD INDEX idx_fin_seller_status(seller_omie_code,status)");
    }

    $cursor=max(0,(int)($_GET['cursor']??0));
    $batch=max(100,min(1000,(int)($_GET['batch']??500)));
    $rows=DB::all("SELECT id,raw_json FROM financial_movements WHERE id>? ORDER BY id ASC LIMIT ".$batch,[$cursor]);

    if(!$rows){
        $mapped=(int)(DB::fetch("SELECT COUNT(*) n FROM financial_movements WHERE seller_omie_code IS NOT NULL AND seller_omie_code<>''")['n']??0);
        echo '<h2>Atualização V6.14 concluída.</h2>';
        echo '<p>Vendedor por movimento financeiro ativado.</p>';
        echo '<p><strong>'.number_format($mapped,0,',','.').'</strong> movimento(s) financeiro(s) possuem vendedor identificado.</p>';
        echo '<p>Nas próximas sincronizações o CRM tentará capturar automaticamente o vendedor informado pelo Omie.</p>';
        echo '<p>Desative novamente <code>installer.enabled</code>.</p>';
        exit;
    }

    $pdo=DB::conn();
    $stmt=$pdo->prepare("UPDATE financial_movements SET seller_omie_code=? WHERE id=?");
    $last=$cursor;$found=0;
    foreach($rows as $row){
        $last=(int)$row['id'];
        $payload=json_decode((string)$row['raw_json'],true);
        if(!is_array($payload))continue;
        $seller=extractSeller614($payload);
        if($seller!==null){$stmt->execute([$seller,$last]);$found++;}
    }

    $remaining=(int)(DB::fetch("SELECT COUNT(*) n FROM financial_movements WHERE id>?",[$last])['n']??0);
    $token=urlencode((string)$cfg['token']);
    $next=APP_URL.'/upgrade-v6.14.php?token='.$token.'&cursor='.$last.'&batch='.$batch;
    echo '<!doctype html><html lang="pt-br"><head><meta charset="utf-8">';
    if($remaining>0)echo '<meta http-equiv="refresh" content="1;url='.htmlspecialchars($next).'">';
    echo '<meta name="viewport" content="width=device-width,initial-scale=1"><title>Upgrade V6.14</title></head><body style="font-family:Arial;padding:32px">';
    echo '<h2>'.($remaining>0?'Mapeando vendedor dos títulos...':'Atualização V6.14 concluída.').'</h2>';
    echo '<p>Lote: '.count($rows).' movimentos • '.$found.' vendedor(es) identificados • '.$remaining.' restantes.</p>';
    if($remaining>0)echo '<p>O próximo lote será aberto automaticamente.</p>';
    else echo '<p>Desative novamente <code>installer.enabled</code>.</p>';
    echo '</body></html>';
}catch(Throwable $e){
    http_response_code(500);
    echo '<h2>Erro no upgrade V6.14</h2><pre>'.htmlspecialchars($e->getMessage()).'</pre>';
}