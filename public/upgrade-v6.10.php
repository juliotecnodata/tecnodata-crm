<?php
require dirname(__DIR__).'/app/bootstrap.php';

use Tecnodata\CRM\Core\DB;

$cfg=$GLOBALS['config']['installer'];
if(empty($cfg['enabled']))exit('Ative temporariamente installer.enabled para atualizar.');
if(!hash_equals((string)$cfg['token'],(string)($_GET['token']??''))){http_response_code(403);exit('Token inválido.');}

function extractTags610(array $payload): array {
    $out=[];$sources=[];
    foreach(['tags','tag','tags_cliente','clientes_tags'] as $key){
        if(array_key_exists($key,$payload))$sources[]=$payload[$key];
    }
    $walk=function($value) use (&$walk,&$out): void {
        if(is_string($value)){
            $value=trim($value);
            if($value!=='')$out[mb_strtolower($value)]=$value;
            return;
        }
        if(!is_array($value))return;
        foreach($value as $k=>$v){
            $key=mb_strtolower((string)$k);
            if(in_array($key,['tag','ctag','nome','descricao','nome_tag'],true) && is_scalar($v)){
                $tag=trim((string)$v);
                if($tag!=='')$out[mb_strtolower($tag)]=$tag;
            }elseif(is_array($v)){
                $walk($v);
            }
        }
    };
    foreach($sources as $source)$walk($source);
    ksort($out,SORT_NATURAL|SORT_FLAG_CASE);
    return array_values($out);
}

try{
 DB::conn()->exec("CREATE TABLE IF NOT EXISTS client_tags (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  client_id BIGINT UNSIGNED NOT NULL,
  tag VARCHAR(160) NOT NULL,
  created_at DATETIME NOT NULL,
  UNIQUE KEY uq_client_tag(client_id,tag),
  INDEX idx_client_tags_tag(tag),
  CONSTRAINT fk_client_tags_client FOREIGN KEY(client_id) REFERENCES clients(id) ON DELETE CASCADE
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

 $cursor=max(0,(int)($_GET['cursor']??0));
 $batch=max(50,min(500,(int)($_GET['batch']??200)));

 $clients=DB::all("SELECT id,raw_json FROM clients
   WHERE raw_json IS NOT NULL AND id>?
   ORDER BY id ASC
   LIMIT ".$batch,[$cursor]);

 if(!$clients){
   $totals=DB::fetch("SELECT COUNT(DISTINCT client_id) clients_with_tags,COUNT(*) tag_links FROM client_tags")??[];
   echo '<h2>Atualização V6.10 concluída.</h2>';
   echo '<p>Filtro por tags do cadastro Omie ativado na gestão de clientes.</p>';
   echo '<p><strong>'.number_format((int)($totals['clients_with_tags']??0),0,',','.').'</strong> cliente(s) com tags e <strong>'.number_format((int)($totals['tag_links']??0),0,',','.').'</strong> vínculo(s) carregados.</p>';
   echo '<p>A distribuição em massa pode ser feita por tag, UF, situação, financeiro e clientes sem vendedor.</p>';
   echo '<p>Desative novamente <code>installer.enabled</code>.</p>';
   exit;
 }

 $pdo=DB::conn();
 $delete=$pdo->prepare("DELETE FROM client_tags WHERE client_id=?");
 $insert=$pdo->prepare("INSERT IGNORE INTO client_tags(client_id,tag,created_at) VALUES(?,?,NOW())");
 $processed=0;$clientsWithTags=0;$tagCount=0;$lastId=$cursor;

 $pdo->beginTransaction();
 try{
   foreach($clients as $client){
     $clientId=(int)$client['id'];
     $lastId=$clientId;
     $payload=json_decode((string)$client['raw_json'],true);
     if(!is_array($payload)){ $processed++; continue; }

     $tags=extractTags610($payload);
     $delete->execute([$clientId]);

     if($tags){
       foreach($tags as $tag){
         $insert->execute([$clientId,$tag]);
         $tagCount++;
       }
       $clientsWithTags++;
     }
     $processed++;
   }
   $pdo->commit();
 }catch(Throwable $e){
   if($pdo->inTransaction())$pdo->rollBack();
   throw $e;
 }

 $remaining=(int)(DB::fetch("SELECT COUNT(*) n FROM clients WHERE raw_json IS NOT NULL AND id>?",[$lastId])['n']??0);
 $done=$remaining===0;
 $token=urlencode((string)$cfg['token']);
 $next=APP_URL.'/upgrade-v6.10.php?token='.$token.'&cursor='.$lastId.'&batch='.$batch;

 echo '<!doctype html><html lang="pt-br"><head><meta charset="utf-8">';
 if(!$done)echo '<meta http-equiv="refresh" content="1;url='.htmlspecialchars($next).'">';
 echo '<meta name="viewport" content="width=device-width,initial-scale=1"><title>Upgrade V6.10</title>';
 echo '<style>body{font-family:Arial,sans-serif;background:#f6f7f8;color:#121B25;padding:32px}.box{max-width:760px;margin:auto;background:#fff;border:1px solid #e4e7ec;border-radius:10px;padding:24px}strong{color:#121B25}.bar{height:10px;background:#eef0f2;border-radius:10px;overflow:hidden}.bar span{display:block;height:100%;background:#BDD630}</style></head><body><div class="box">';
 echo '<h2>'.($done?'Atualização V6.10 concluída.':'Atualizando tags em lotes...').'</h2>';
 echo '<p>Lote processado: <strong>'.$processed.'</strong> clientes • <strong>'.$clientsWithTags.'</strong> com tags • <strong>'.$tagCount.'</strong> vínculos.</p>';
 echo '<p>Restantes: <strong>'.number_format($remaining,0,',','.').'</strong></p>';
 if(!$done){
   echo '<div class="bar"><span style="width:100%"></span></div>';
   echo '<p>O próximo lote será iniciado automaticamente em 1 segundo. Não feche esta aba.</p>';
   echo '<p><a href="'.htmlspecialchars($next).'">Continuar manualmente</a></p>';
 }else{
   $totals=DB::fetch("SELECT COUNT(DISTINCT client_id) clients_with_tags,COUNT(*) tag_links FROM client_tags")??[];
   echo '<p>Total final: <strong>'.number_format((int)($totals['clients_with_tags']??0),0,',','.').'</strong> cliente(s) com tags e <strong>'.number_format((int)($totals['tag_links']??0),0,',','.').'</strong> vínculo(s).</p>';
   echo '<p>Desative novamente <code>installer.enabled</code>.</p>';
 }
 echo '</div></body></html>';

}catch(Throwable $e){
 http_response_code(500);
 echo '<h2>Erro no upgrade V6.10</h2><pre>'.htmlspecialchars($e->getMessage()).'</pre>';
}