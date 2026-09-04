<?php
require dirname(__DIR__).'/app/bootstrap.php';

use Tecnodata\CRM\Core\DB;

$cfg=$GLOBALS['config']['installer'];
if(empty($cfg['enabled']))exit('Ative temporariamente installer.enabled para atualizar.');
if(!hash_equals((string)$cfg['token'],(string)($_GET['token']??''))){http_response_code(403);exit('Token inválido.');}

function colExists617(string $table,string $column): bool {
 $db=(string)DB::conn()->query('SELECT DATABASE()')->fetchColumn();
 return (bool)DB::fetch("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=? AND TABLE_NAME=? AND COLUMN_NAME=? LIMIT 1",[$db,$table,$column]);
}

try{
 if(!colExists617('financial_movements','original_amount')){
   DB::conn()->exec("ALTER TABLE financial_movements ADD COLUMN original_amount DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER amount");
 }
 if(!colExists617('financial_movements','paid_amount')){
   DB::conn()->exec("ALTER TABLE financial_movements ADD COLUMN paid_amount DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER original_amount");
 }

 $cursor=max(0,(int)($_GET['cursor']??0));
 $batch=max(100,min(1000,(int)($_GET['batch']??500)));
 $rows=DB::all("SELECT id,amount,raw_json FROM financial_movements WHERE id>? ORDER BY id ASC LIMIT ".$batch,[$cursor]);

 if(!$rows){
   DB::exec("DELETE FROM sync_states WHERE module_key='financial'");
   $partial=(int)(DB::fetch("SELECT COUNT(*) n FROM financial_movements WHERE status='PAGTO_PARCIAL'")['n']??0);
   echo '<h2>Atualização V6.17 concluída.</h2>';
   echo '<p>O financeiro foi preparado para reconhecer corretamente pagamentos parciais.</p>';
   echo '<p><strong>'.number_format($partial,0,',','.').'</strong> título(s) atuais já estão identificados como parcial.</p>';
   echo '<p>Agora, no Admin, use <strong>Sincronização Omie → Financeiro → Limpar e reconstruir</strong>.</p>';
   echo '<p>Desative novamente <code>installer.enabled</code>.</p>';
   exit;
 }

 $stmt=DB::conn()->prepare("UPDATE financial_movements SET original_amount=?,paid_amount=?,status=? WHERE id=?");
 $last=$cursor;$partials=0;
 foreach($rows as $row){
   $last=(int)$row['id'];
   $raw=json_decode((string)$row['raw_json'],true);
   if(!is_array($raw))continue;
   $d=is_array($raw['detalhes']??null)?$raw['detalhes']:$raw;
   $s=is_array($raw['resumo']??null)?$raw['resumo']:[];
   $open=(float)($s['nValAberto']??$row['amount']);
   $paid=(float)($s['nValPago']??0);
   $original=(float)($d['nValorTitulo']??($open+$paid));
   $api=mb_strtoupper((string)($d['cStatus']??''));
   $status=($open>0.009&&$paid>0.009)||$api==='PAGTOPARCIAL'?'PAGTO_PARCIAL':($api?:'ATRASADO');
   if($status==='PAGTO_PARCIAL')$partials++;
   $stmt->execute([$original,$paid,$status,$last]);
 }

 $remaining=(int)(DB::fetch("SELECT COUNT(*) n FROM financial_movements WHERE id>?",[$last])['n']??0);
 $token=urlencode((string)$cfg['token']);
 $next=APP_URL.'/upgrade-v6.17.php?token='.$token.'&cursor='.$last.'&batch='.$batch;

 echo '<!doctype html><html lang="pt-br"><head><meta charset="utf-8">';
 echo '<meta http-equiv="refresh" content="1;url='.htmlspecialchars($next).'">';
 echo '<meta name="viewport" content="width=device-width,initial-scale=1"><title>Upgrade V6.17</title>';
 echo '<style>body{font-family:Arial,sans-serif;background:#f6f8f9;color:#14232b;padding:32px}.box{max-width:760px;margin:auto;background:#fff;border:1px solid #e4e9ec;border-radius:10px;padding:24px}strong{color:#14232b}</style></head><body><div class="box">';
 echo '<h2>Atualizando o financeiro em lotes...</h2>';
 echo '<p>Lote processado: <strong>'.count($rows).'</strong> título(s) • parciais encontrados neste lote: <strong>'.$partials.'</strong>.</p>';
 echo '<p>Restantes: <strong>'.number_format($remaining,0,',','.').'</strong></p>';
 echo '<p>O próximo lote será aberto automaticamente. Não feche esta aba.</p>';
 echo '<p><a href="'.htmlspecialchars($next).'">Continuar manualmente</a></p>';
 echo '</div></body></html>';
}catch(Throwable $e){
 http_response_code(500);
 echo '<h2>Erro no upgrade V6.17</h2><pre>'.htmlspecialchars($e->getMessage()).'</pre>';
}