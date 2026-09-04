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

 $clients=DB::all("SELECT id,raw_json FROM clients WHERE raw_json IS NOT NULL");
 $clientsWithTags=0;$tagCount=0;
 foreach($clients as $client){
   $payload=json_decode((string)$client['raw_json'],true);
   if(!is_array($payload))continue;
   $tags=extractTags610($payload);
   if(!$tags)continue;
   DB::exec("DELETE FROM client_tags WHERE client_id=?",[(int)$client['id']]);
   foreach($tags as $tag){
     DB::exec("INSERT IGNORE INTO client_tags(client_id,tag,created_at) VALUES(?,?,NOW())",[(int)$client['id'],$tag]);
     $tagCount++;
   }
   $clientsWithTags++;
 }

 echo '<h2>Atualização V6.10 concluída.</h2>';
 echo '<p>Filtro por tags do cadastro Omie ativado na gestão de clientes.</p>';
 echo '<p><strong>'.$clientsWithTags.'</strong> cliente(s) tiveram tags identificadas e <strong>'.$tagCount.'</strong> vínculo(s) de tag foram carregados.</p>';
 echo '<p>A distribuição em massa pode ser feita por tag, UF, situação, financeiro e clientes sem vendedor.</p>';
 echo '<p>Desative novamente <code>installer.enabled</code>.</p>';
}catch(Throwable $e){
 http_response_code(500);
 echo '<h2>Erro no upgrade V6.10</h2><pre>'.htmlspecialchars($e->getMessage()).'</pre>';
}