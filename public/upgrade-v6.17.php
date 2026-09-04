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

 // Aproveita o raw_json atual para preencher os novos campos antes da reconstrução completa.
 $rows=DB::all("SELECT id,amount,raw_json FROM financial_movements WHERE raw_json IS NOT NULL");
 $stmt=DB::conn()->prepare("UPDATE financial_movements SET original_amount=?,paid_amount=?,status=? WHERE id=?");
 $partial=0;
 foreach($rows as $row){
   $raw=json_decode((string)$row['raw_json'],true);
   if(!is_array($raw))continue;
   $d=is_array($raw['detalhes']??null)?$raw['detalhes']:$raw;
   $s=is_array($raw['resumo']??null)?$raw['resumo']:[];
   $open=(float)($s['nValAberto']??$row['amount']);
   $paid=(float)($s['nValPago']??0);
   $original=(float)($d['nValorTitulo']??($open+$paid));
   $api=mb_strtoupper((string)($d['cStatus']??''));
   $status=($open>0.009&&$paid>0.009)||$api==='PAGTOPARCIAL'?'PAGTO_PARCIAL':($api?:'ATRASADO');
   if($status==='PAGTO_PARCIAL')$partial++;
   $stmt->execute([$original,$paid,$status,(int)$row['id']]);
 }

 // Força o próximo financeiro a iniciar limpo com a nova regra.
 DB::exec("DELETE FROM sync_states WHERE module_key='financial'");

 echo '<h2>Atualização V6.17 concluída.</h2>';
 echo '<p>O financeiro foi preparado para reconhecer corretamente pagamentos parciais.</p>';
 echo '<p><strong>'.number_format($partial,0,',','.').'</strong> título(s) atuais já foram identificados como parcial a partir do raw_json.</p>';
 echo '<p>Agora, no Admin, use <strong>Sincronização Omie → Financeiro → Limpar e reconstruir</strong>.</p>';
 echo '<p>Desative novamente <code>installer.enabled</code>.</p>';
}catch(Throwable $e){
 http_response_code(500);
 echo '<h2>Erro no upgrade V6.17</h2><pre>'.htmlspecialchars($e->getMessage()).'</pre>';
}