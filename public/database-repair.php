<?php
declare(strict_types=1);

define('APP_ROOT',dirname(__DIR__));
$configFile=APP_ROOT.'/config/config.php';
if(!is_file($configFile)){http_response_code(500);exit('config/config.php não encontrado.');}
$config=require $configFile;
$installer=$config['installer']??[];
if(empty($installer['enabled']))exit('Ative temporariamente installer.enabled em config/config.php.');
$token=(string)($_GET['token']??'');
if($token===''||!hash_equals((string)($installer['token']??''),$token)){http_response_code(403);exit('Token inválido.');}

date_default_timezone_set((string)($config['app']['timezone']??'America/Sao_Paulo'));
$host=$_SERVER['HTTP_HOST']??'localhost';
$isLocal=str_contains($host,'localhost')||str_contains($host,'127.0.0.1');
$db=$config['database'][$isLocal?'local':'production']??null;
if(!is_array($db))exit('Configuração de banco inválida.');
$prefix=preg_replace('/[^A-Za-z0-9_]/','',(string)($config['database']['table_prefix']??'tdcrm_'));
if($prefix===null)$prefix='tdcrm_';

$pdo=new PDO(
 sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s',$db['host'],$db['port'],$db['database'],$db['charset']??'utf8mb4'),
 $db['username'],$db['password'],
 [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]
);

function qi(string $name): string{return '`'.str_replace('`','``',$name).'`';}
function tableExists(PDO $pdo,string $table): bool{
 $s=$pdo->prepare('SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? LIMIT 1');
 $s->execute([$table]);return (bool)$s->fetchColumn();
}
function cols(PDO $pdo,string $table): array{
 if(!tableExists($pdo,$table))return [];
 $r=$pdo->query('SHOW COLUMNS FROM '.qi($table))->fetchAll();
 $out=[];foreach($r as $row)$out[(string)$row['Field']]=$row;return $out;
}
function idxExists(PDO $pdo,string $table,string $idx): bool{
 $s=$pdo->prepare('SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND INDEX_NAME=? LIMIT 1');
 $s->execute([$table,$idx]);return (bool)$s->fetchColumn();
}
function rowCountSafe(PDO $pdo,string $table): int{
 if(!tableExists($pdo,$table))return 0;
 return (int)$pdo->query('SELECT COUNT(*) FROM '.qi($table))->fetchColumn();
}
function execStep(PDO $pdo,array &$log,string $label,string $sql): void{
 try{$pdo->exec($sql);$log[]=['ok'=>true,'label'=>$label];}
 catch(Throwable $e){$log[]=['ok'=>false,'label'=>$label,'error'=>$e->getMessage()];}
}
function addCol(PDO $pdo,array &$log,string $table,string $column,string $definition): void{
 $c=cols($pdo,$table);if(isset($c[$column]))return;
 execStep($pdo,$log,$table.'.'.$column,'ALTER TABLE '.qi($table).' ADD COLUMN '.qi($column).' '.$definition);
}
function modifyCol(PDO $pdo,array &$log,string $table,string $column,string $definition): void{
 $c=cols($pdo,$table);if(!isset($c[$column]))return;
 execStep($pdo,$log,'ajustar '.$table.'.'.$column,'ALTER TABLE '.qi($table).' MODIFY COLUMN '.qi($column).' '.$definition);
}

$log=[];
$logicalTables=[
 'users','sellers','clients','client_metrics','products','categories','financial_accounts','order_stages',
 'payment_terms','tax_scenarios','stock_locations','payment_methods','document_types','orders','service_orders',
 'financial_movements','activities','tasks','collection_cases','collection_actions','settings','sync_state',
 'omie_order_logs','goals','collection_assignment_log','order_profiles'
];

// 1) Cria tudo que estiver realmente ausente a partir do schema oficial da rebuild-clean.
$schema=file_get_contents(APP_ROOT.'/database/schema.sql');
if($schema===false)exit('database/schema.sql não encontrado.');
foreach($logicalTables as $logical){
 $schema=preg_replace('/(?<![A-Za-z0-9_])'.preg_quote($logical,'/').'(?![A-Za-z0-9_])/i',$prefix.$logical,$schema);
}
foreach(preg_split('/;\s*(?:\r?\n|$)/',$schema)?:[] as $statement){
 $statement=trim($statement);if($statement==='')continue;
 try{$pdo->exec($statement);}catch(Throwable $e){$log[]=['ok'=>false,'label'=>'schema base','error'=>$e->getMessage()];}
}

// 2) Normaliza tabelas que podem ter vindo do CRM legado.
$t=$prefix.'clients';
addCol($pdo,$log,$t,'seller_omie_code','VARCHAR(80) NULL AFTER uf');

$t=$prefix.'products';
addCol($pdo,$log,$t,'sku','VARCHAR(120) NULL AFTER omie_code');
$c=cols($pdo,$t);
if(isset($c['internal_code'])&&isset($c['sku']))execStep($pdo,$log,'migrar SKU legado','UPDATE '.qi($t).' SET sku=COALESCE(NULLIF(sku,\'\'),internal_code) WHERE sku IS NULL OR sku=\'\'');

$t=$prefix.'financial_accounts';
addCol($pdo,$log,$t,'raw_json','JSON NULL');

$t=$prefix.'orders';
addCol($pdo,$log,$t,'omie_code','VARCHAR(80) NULL');
addCol($pdo,$log,$t,'number','VARCHAR(30) NULL');
addCol($pdo,$log,$t,'forecast_date','DATE NULL');
$c=cols($pdo,$t);
if(isset($c['omie_order_code'])&&isset($c['omie_code'])){
 execStep($pdo,$log,'migrar código legado de pedidos','UPDATE '.qi($t).' SET omie_code=COALESCE(NULLIF(omie_code,\'\'),omie_order_code) WHERE omie_code IS NULL OR omie_code=\'\'');
}
if(isset($c['omie_code'])&&!idxExists($pdo,$t,'uq_orders_omie')){
 execStep($pdo,$log,'índice único de pedidos','ALTER TABLE '.qi($t).' ADD UNIQUE KEY uq_orders_omie(omie_code)');
}

$t=$prefix.'service_orders';
addCol($pdo,$log,$t,'omie_code','VARCHAR(80) NULL');
addCol($pdo,$log,$t,'service_date','DATE NULL');
$c=cols($pdo,$t);
if(isset($c['omie_service_order_code'])&&isset($c['omie_code'])){
 execStep($pdo,$log,'migrar código legado de serviços','UPDATE '.qi($t).' SET omie_code=COALESCE(NULLIF(omie_code,\'\'),omie_service_order_code) WHERE omie_code IS NULL OR omie_code=\'\'');
}
if(isset($c['inclusion_date'])&&isset($c['service_date'])){
 execStep($pdo,$log,'migrar data legada de serviços','UPDATE '.qi($t).' SET service_date=COALESCE(service_date,inclusion_date) WHERE service_date IS NULL');
}
if(isset($c['omie_code'])&&!idxExists($pdo,$t,'uq_service_orders_omie')){
 execStep($pdo,$log,'índice único de serviços','ALTER TABLE '.qi($t).' ADD UNIQUE KEY uq_service_orders_omie(omie_code)');
}

$t=$prefix.'financial_movements';
addCol($pdo,$log,$t,'open_amount','DECIMAL(15,2) NOT NULL DEFAULT 0');
addCol($pdo,$log,$t,'last_seen_token','VARCHAR(64) NULL');
$c=cols($pdo,$t);
if(isset($c['original_amount'])&&isset($c['paid_amount'])&&isset($c['open_amount'])){
 execStep($pdo,$log,'recalcular saldo financeiro legado',
  'UPDATE '.qi($t).' SET open_amount=GREATEST(COALESCE(original_amount,0)-COALESCE(paid_amount,0),0) WHERE open_amount=0');
}

$t=$prefix.'activities';
addCol($pdo,$log,$t,'channel','VARCHAR(30) NULL');
addCol($pdo,$log,$t,'next_at','DATETIME NULL');
$c=cols($pdo,$t);
if(isset($c['type'])&&isset($c['channel']))execStep($pdo,$log,'migrar canal de atividades','UPDATE '.qi($t).' SET channel=COALESCE(NULLIF(channel,\'\'),type)');
modifyCol($pdo,$log,$t,'result','VARCHAR(40) NOT NULL');

$t=$prefix.'tasks';
addCol($pdo,$log,$t,'assigned_user_id','INT UNSIGNED NULL');
addCol($pdo,$log,$t,'type',"ENUM('sales','collection') NOT NULL DEFAULT 'sales'");
$c=cols($pdo,$t);
if(isset($c['user_id'])&&isset($c['assigned_user_id']))execStep($pdo,$log,'migrar responsável de tarefas','UPDATE '.qi($t).' SET assigned_user_id=COALESCE(assigned_user_id,user_id)');
modifyCol($pdo,$log,$t,'assigned_user_id','INT UNSIGNED NOT NULL');

$t=$prefix.'collection_actions';
addCol($pdo,$log,$t,'author_user_id','INT UNSIGNED NULL');
addCol($pdo,$log,$t,'promise_date','DATE NULL');
$c=cols($pdo,$t);
if(isset($c['user_id'])&&isset($c['author_user_id']))execStep($pdo,$log,'migrar autor de cobrança','UPDATE '.qi($t).' SET author_user_id=COALESCE(author_user_id,user_id)');
if(isset($c['promised_for'])&&isset($c['promise_date']))execStep($pdo,$log,'migrar promessa de cobrança','UPDATE '.qi($t).' SET promise_date=COALESCE(promise_date,promised_for)');
if(isset($c['user_id'])&&isset($c['assigned_user_id']))execStep($pdo,$log,'preencher responsável de cobrança','UPDATE '.qi($t).' SET assigned_user_id=COALESCE(assigned_user_id,user_id)');
modifyCol($pdo,$log,$t,'channel','VARCHAR(30) NOT NULL');
modifyCol($pdo,$log,$t,'result','VARCHAR(40) NOT NULL');
modifyCol($pdo,$log,$t,'author_user_id','INT UNSIGNED NOT NULL');
modifyCol($pdo,$log,$t,'assigned_user_id','INT UNSIGNED NOT NULL');

// Normaliza valores legados para os valores usados pela rebuild-clean.
if(tableExists($pdo,$t)){
 execStep($pdo,$log,'normalizar canais de cobrança',
  "UPDATE ".qi($t)." SET channel=CASE channel WHEN 'ligacao' THEN 'phone' WHEN 'outro' THEN 'other' ELSE channel END");
 execStep($pdo,$log,'normalizar resultados de cobrança',
  "UPDATE ".qi($t)." SET result=CASE result WHEN 'falou' THEN 'contact' WHEN 'nao_atendeu' THEN 'no_answer' WHEN 'promessa' THEN 'promise' WHEN 'acordo' THEN 'agreement' WHEN 'pagamento' THEN 'payment' WHEN 'sem_previsao' THEN 'contact' ELSE result END");
}

$t=$prefix.'payment_methods';modifyCol($pdo,$log,$t,'code','VARCHAR(4) NOT NULL');
$t=$prefix.'document_types';modifyCol($pdo,$log,$t,'code','VARCHAR(8) NOT NULL');

// 3) Migra estado de sincronização antigo se existir na MESMA família de prefixo.
$oldSync=$prefix.'sync_states';$newSync=$prefix.'sync_state';
if(tableExists($pdo,$oldSync)&&tableExists($pdo,$newSync)){
 execStep($pdo,$log,'migrar estados de sincronização',
  'INSERT INTO '.qi($newSync).'(module_key,last_page,total_pages,last_count,context_json,last_success_at,last_error)
   SELECT module_key,current_page,total_pages,processed,context_json,last_success_at,last_error FROM '.qi($oldSync).'
   ON DUPLICATE KEY UPDATE last_page=VALUES(last_page),total_pages=VALUES(total_pages),last_count=VALUES(last_count),
   context_json=VALUES(context_json),last_success_at=VALUES(last_success_at),last_error=VALUES(last_error)');
}

// 4) Se o prefixo estiver ativo e houver base legada SEM prefixo, importa dados centrais quando o destino estiver vazio.
if($prefix!==''){
 $imports=[
  'sellers'=>[
   'cols'=>'omie_code,name,email,active,raw_json,updated_at',
   'select'=>"omie_code,name,email,active,raw_json,updated_at"
  ],
  'clients'=>[
   'cols'=>'omie_code,name,legal_name,document,email,phone,city,uf,active,raw_json,updated_at',
   'select'=>"omie_code,name,legal_name,document,email,phone,city,uf,active,raw_json,updated_at"
  ],
  'products'=>[
   'cols'=>'omie_code,sku,description,unit,ncm,unit_price,stock_qty,active,raw_json,updated_at',
   'select'=>"omie_code,COALESCE(internal_code,integration_code),description,unit,ncm,unit_price,stock_qty,active,raw_json,updated_at"
  ]
 ];
 foreach($imports as $logical=>$map){
  $src=$logical;$dst=$prefix.$logical;
  if(tableExists($pdo,$src)&&tableExists($pdo,$dst)&&rowCountSafe($pdo,$dst)===0){
   execStep($pdo,$log,'importar '.$logical.' legado',
    'INSERT IGNORE INTO '.qi($dst).'('.$map['cols'].') SELECT '.$map['select'].' FROM '.qi($src));
  }
 }

 // Pedidos: aceita schema legado.
 $src='orders';$dst=$prefix.'orders';
 if(tableExists($pdo,$src)&&tableExists($pdo,$dst)&&rowCountSafe($pdo,$dst)===0){
  $sc=cols($pdo,$src);$code=isset($sc['omie_code'])?'omie_code':'omie_order_code';
  execStep($pdo,$log,'importar pedidos legados',
   'INSERT IGNORE INTO '.qi($dst).'(omie_code,client_omie_code,seller_omie_code,order_date,total,status,stage_code,raw_json,updated_at)
    SELECT '.$code.',client_omie_code,seller_omie_code,order_date,total,status,stage_code,raw_json,updated_at FROM '.qi($src));
 }

 // Serviços: aceita schema legado e atual.
 $src='service_orders';$dst=$prefix.'service_orders';
 if(tableExists($pdo,$src)&&tableExists($pdo,$dst)&&rowCountSafe($pdo,$dst)===0){
  $sc=cols($pdo,$src);
  $code=isset($sc['omie_code'])?'omie_code':'omie_service_order_code';
  $date=isset($sc['service_date'])?'service_date':'inclusion_date';
  execStep($pdo,$log,'importar serviços legados',
   'INSERT IGNORE INTO '.qi($dst).'(omie_code,client_omie_code,seller_omie_code,service_date,total,status,raw_json,updated_at)
    SELECT '.$code.',client_omie_code,seller_omie_code,'.$date.',total,status,raw_json,updated_at FROM '.qi($src));
 }

 // Financeiro legado.
 $src='financial_movements';$dst=$prefix.'financial_movements';
 if(tableExists($pdo,$src)&&tableExists($pdo,$dst)&&rowCountSafe($pdo,$dst)===0){
  $sc=cols($pdo,$src);
  $openExpr=isset($sc['open_amount'])?'open_amount':(isset($sc['original_amount'])?'GREATEST(COALESCE(original_amount,0)-COALESCE(paid_amount,0),0)':'COALESCE(amount,0)');
  execStep($pdo,$log,'importar financeiro legado',
   'INSERT IGNORE INTO '.qi($dst).'(omie_code,client_omie_code,account_omie_code,seller_omie_code,due_date,open_amount,paid_amount,status,raw_json,updated_at)
    SELECT omie_code,client_omie_code,account_omie_code,seller_omie_code,due_date,'.$openExpr.',paid_amount,status,raw_json,updated_at FROM '.qi($src));
 }
}

// 5) Marca versão do schema.
$settings=$prefix.'settings';
if(tableExists($pdo,$settings)){
 $st=$pdo->prepare('INSERT INTO '.qi($settings)."(setting_key,value_json,updated_at) VALUES('schema_version',?,NOW())
                    ON DUPLICATE KEY UPDATE value_json=VALUES(value_json),updated_at=NOW()");
 $st->execute([json_encode(['version'=>'2026-09-06.1','repaired_at'=>date('c')],JSON_UNESCAPED_UNICODE)]);
}

// 6) Diagnóstico final.
$expected=[
 'users'=>['id','name','email','password_hash','role','seller_omie_code','active'],
 'sellers'=>['omie_code','name','active'],
 'clients'=>['id','omie_code','name','seller_omie_code','active'],
 'client_metrics'=>['client_id','last_purchase_at','revenue_12m','orders_12m','avg_ticket_12m','avg_interval_days'],
 'products'=>['id','omie_code','sku','description','unit_price','active'],
 'categories'=>['code','description','active'],
 'financial_accounts'=>['omie_code','name','active','selected','raw_json'],
 'order_stages'=>['code','name','active'],
 'payment_terms'=>['code','description','installments','active'],
 'tax_scenarios'=>['omie_code','name','active'],
 'stock_locations'=>['omie_code','name','active'],
 'payment_methods'=>['code','description'],
 'document_types'=>['code','description'],
 'orders'=>['id','omie_code','client_omie_code','seller_omie_code','order_date','total','status'],
 'service_orders'=>['id','omie_code','client_omie_code','seller_omie_code','service_date','total','status'],
 'financial_movements'=>['id','omie_code','client_omie_code','account_omie_code','seller_omie_code','due_date','open_amount','paid_amount','status','last_seen_token'],
 'activities'=>['id','client_id','user_id','channel','result','next_at','created_at'],
 'tasks'=>['id','client_id','assigned_user_id','type','title','due_at','status'],
 'collection_cases'=>['client_id','open_amount','status','assigned_user_id'],
 'collection_actions'=>['id','client_id','author_user_id','assigned_user_id','channel','result','amount','promise_date','created_at'],
 'settings'=>['setting_key','value_json'],
 'sync_state'=>['module_key','last_page','total_pages','last_count','context_json','last_success_at','last_error'],
 'omie_order_logs'=>['id','integration_code','status'],
 'goals'=>['id','user_id','month_ref','sales_goal','collection_goal','contact_goal'],
 'collection_assignment_log'=>['id','client_id','to_user_id','changed_by'],
 'order_profiles'=>['id','code','name','active']
];

$report=[];$allOk=true;
foreach($expected as $logical=>$required){
 $table=$prefix.$logical;$c=cols($pdo,$table);$missing=array_values(array_diff($required,array_keys($c)));
 $ok=tableExists($pdo,$table)&&!$missing;$allOk=$allOk&&$ok;
 $report[]=['table'=>$table,'ok'=>$ok,'rows'=>rowCountSafe($pdo,$table),'missing'=>$missing];
}

?><!doctype html>
<html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Reparo estrutural CRM</title>
<style>
body{font-family:Arial,sans-serif;background:#f4f6f7;color:#253038;margin:0;padding:28px}.wrap{max-width:1180px;margin:auto}
.card{background:#fff;border:1px solid #dfe5e8;border-radius:10px;padding:18px;margin-bottom:14px}.ok{color:#2f7d64}.bad{color:#c55252}
table{width:100%;border-collapse:collapse;background:#fff}th,td{border-bottom:1px solid #e8ecee;padding:9px 10px;text-align:left;font-size:13px}th{background:#f8fafb}
code{background:#eef2f4;padding:2px 5px;border-radius:5px}.pill{display:inline-block;padding:4px 7px;border-radius:999px;font-size:11px;font-weight:bold}
.pill.ok{background:#eaf5f0}.pill.bad{background:#fcedea}.log{font-size:12px;line-height:1.5}.log div{padding:5px 0;border-bottom:1px solid #eef1f2}
</style></head><body><div class="wrap">
<div class="card"><h1>Reparo estrutural do Tecnodata CRM</h1>
<p>Banco: <code><?=htmlspecialchars((string)$db['database'])?></code> • Prefixo ativo: <code><?=htmlspecialchars($prefix?:'(sem prefixo)')?></code></p>
<h2 class="<?=$allOk?'ok':'bad'?>"><?=$allOk?'Estrutura principal validada':'Ainda existem incompatibilidades'?></h2></div>
<div class="card"><h2>Tabelas</h2><table><thead><tr><th>Tabela</th><th>Status</th><th>Registros</th><th>Colunas faltando</th></tr></thead><tbody>
<?php foreach($report as $r):?><tr><td><code><?=htmlspecialchars($r['table'])?></code></td><td><span class="pill <?=$r['ok']?'ok':'bad'?>"><?=$r['ok']?'OK':'ERRO'?></span></td><td><?=number_format($r['rows'],0,',','.')?></td><td><?=htmlspecialchars(implode(', ',$r['missing']))?></td></tr><?php endforeach;?>
</tbody></table></div>
<div class="card"><h2>Operações executadas</h2><div class="log">
<?php foreach($log as $item):?><div class="<?=$item['ok']?'ok':'bad'?>"><?=$item['ok']?'✓':'✕'?> <?=htmlspecialchars($item['label'])?><?php if(!$item['ok']):?> — <?=htmlspecialchars($item['error']??'')?><?php endif;?></div><?php endforeach;?>
<?php if(!$log):?><div>Nenhum ajuste adicional foi necessário.</div><?php endif;?>
</div></div>
<p>Depois de validar, desative <code>installer.enabled</code> novamente.</p>
</div></body></html>
