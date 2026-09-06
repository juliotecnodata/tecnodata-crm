<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=utf-8');
ini_set('display_errors','1');
error_reporting(E_ALL);

function esc($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function pill($ok){return $ok?'<span class="ok">OK</span>':'<span class="bad">ERRO</span>';}

$checks=[];
$root=dirname(__DIR__);
$configFile=$root.'/config/config.php';

$checks[]=['PHP >= 8.2',version_compare(PHP_VERSION,'8.2.0','>='),PHP_VERSION];
$checks[]=['PDO MySQL',extension_loaded('pdo_mysql'),extension_loaded('pdo_mysql')?'carregado':'ausente'];
$checks[]=['cURL',extension_loaded('curl'),extension_loaded('curl')?'carregado':'ausente'];
$checks[]=['mbstring',extension_loaded('mbstring'),extension_loaded('mbstring')?'carregado':'ausente'];
$checks[]=['config/config.php',is_file($configFile),$configFile];

$config=null;$pdo=null;$dbInfo='';$prefix='';
if(is_file($configFile)){
 try{
  $config=require $configFile;
  $host=$_SERVER['HTTP_HOST']??'localhost';
  $local=strpos($host,'localhost')!==false||strpos($host,'127.0.0.1')!==false;
  $db=$config['database'][$local?'local':'production']??null;
  $prefix=preg_replace('/[^A-Za-z0-9_]/','',(string)($config['database']['table_prefix']??'tdcrm_'));
  if(!is_array($db))throw new RuntimeException('Configuração database inválida.');
  $dbInfo=(string)$db['database'].' @ '.(string)$db['host'];
  $pdo=new PDO(
   sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s',$db['host'],$db['port'],$db['database'],$db['charset']??'utf8mb4'),
   $db['username'],$db['password'],
   [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]
  );
  $checks[]=['Conexão com banco',true,$dbInfo];
 }catch(Throwable $e){
  $checks[]=['Conexão com banco',false,$e->getMessage()];
 }
}

$tables=[];
if($pdo){
 $expected=['users','sellers','clients','orders','service_orders','financial_movements','activities','tasks','collection_cases','collection_actions','settings','sync_state','goals'];
 foreach($expected as $logical){
  $table=$prefix.$logical;
  try{
   $st=$pdo->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?');
   $st->execute([$table]);$exists=(bool)$st->fetchColumn();
   $count=null;$columns=[];
   if($exists){
    $count=(int)$pdo->query('SELECT COUNT(*) FROM `'.str_replace('`','``',$table).'`')->fetchColumn();
    $rows=$pdo->query('SHOW COLUMNS FROM `'.str_replace('`','``',$table).'`')->fetchAll();
    $columns=array_map(fn($r)=>(string)$r['Field'],$rows);
   }
   $tables[]=['name'=>$table,'exists'=>$exists,'count'=>$count,'columns'=>$columns];
  }catch(Throwable $e){
   $tables[]=['name'=>$table,'exists'=>false,'count'=>null,'columns'=>[],'error'=>$e->getMessage()];
  }
 }

 foreach(['service_orders','orders','clients'] as $legacy){
  try{
   $st=$pdo->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?');
   $st->execute([$legacy]);$exists=(bool)$st->fetchColumn();
   if($exists){
    $count=(int)$pdo->query('SELECT COUNT(*) FROM `'.$legacy.'`')->fetchColumn();
    $rows=$pdo->query('SHOW COLUMNS FROM `'.$legacy.'`')->fetchAll();
    $tables[]=['name'=>$legacy.' (legada sem prefixo)','exists'=>true,'count'=>$count,'columns'=>array_map(fn($r)=>(string)$r['Field'],$rows)];
   }
  }catch(Throwable $e){}
 }
}

$phpFiles=['app/core.php','app/services.php','app/views.php','routes.php','public/index.php'];
$fileChecks=[];
foreach($phpFiles as $rel){
 $path=$root.'/'.$rel;
 $fileChecks[]=['name'=>$rel,'exists'=>is_file($path),'size'=>is_file($path)?filesize($path):0,'modified'=>is_file($path)?date('Y-m-d H:i:s',filemtime($path)):'—'];
}

?><!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Diagnóstico Tecnodata CRM</title>
<style>
body{font-family:Arial,sans-serif;background:#f4f6f7;color:#253038;margin:0;padding:24px}.wrap{max-width:1200px;margin:auto}.card{background:#fff;border:1px solid #dfe5e8;border-radius:10px;padding:18px;margin-bottom:14px}h1,h2{margin-top:0}.ok{color:#2f7d64;font-weight:700}.bad{color:#c55252;font-weight:700}table{width:100%;border-collapse:collapse}th,td{padding:9px;border-bottom:1px solid #e5eaec;text-align:left;vertical-align:top;font-size:13px}th{background:#f8fafb}code{font-size:11px;word-break:break-all}.cols{max-width:650px;white-space:normal}
</style></head><body><div class="wrap">
<div class="card"><h1>Diagnóstico independente do CRM</h1><p>Este arquivo não carrega o aplicativo. Se esta página abre, PHP/servidor estão executando.</p></div>
<div class="card"><h2>Ambiente</h2><table><tr><th>Teste</th><th>Status</th><th>Detalhe</th></tr>
<?php foreach($checks as $c):?><tr><td><?=esc($c[0])?></td><td><?=pill((bool)$c[1])?></td><td><?=esc($c[2])?></td></tr><?php endforeach;?>
</table></div>
<div class="card"><h2>Tabelas</h2><p>Prefixo configurado: <code><?=esc($prefix?:'(vazio)')?></code></p><table><tr><th>Tabela</th><th>Status</th><th>Registros</th><th>Colunas</th></tr>
<?php foreach($tables as $t):?><tr><td><code><?=esc($t['name'])?></code></td><td><?=pill((bool)$t['exists'])?></td><td><?=esc($t['count']===null?'—':$t['count'])?></td><td class="cols"><code><?=esc(implode(', ',$t['columns']))?></code><?php if(!empty($t['error'])):?><br><span class="bad"><?=esc($t['error'])?></span><?php endif;?></td></tr><?php endforeach;?>
</table></div>
<div class="card"><h2>Arquivos principais</h2><table><tr><th>Arquivo</th><th>Status</th><th>Tamanho</th><th>Alterado</th></tr>
<?php foreach($fileChecks as $f):?><tr><td><code><?=esc($f['name'])?></code></td><td><?=pill($f['exists'])?></td><td><?=esc($f['size'])?></td><td><?=esc($f['modified'])?></td></tr><?php endforeach;?></table></div>
</div></body></html>