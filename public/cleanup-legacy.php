<?php
declare(strict_types=1);
require dirname(__DIR__).'/app/bootstrap.php';

$cfg=$GLOBALS['config']['installer']??[];
if(empty($cfg['enabled']))exit('Ative temporariamente installer.enabled para executar a limpeza.');
if(!hash_equals((string)($cfg['token']??''),(string)($_GET['token']??''))){http_response_code(403);exit('Token inválido.');}

$prefix=DB::prefix();
$db=(string)DB::conn()->query('SELECT DATABASE()')->fetchColumn();
$legacy=[
  "users",
  "sellers",
  "clients",
  "orders",
  "service_orders",
  "financial_movements",
  "client_metrics",
  "activities",
  "tasks",
  "user_notification_settings",
  "monthly_goals",
  "seller_goals",
  "sync_runs",
  "sync_states",
  "financial_accounts",
  "collection_actions",
  "collection_user_goals",
  "collection_client_adjustments",
  "interaction_audit",
  "client_portfolio_assignments",
  "client_tags",
  "order_stage_catalog",
  "products",
  "sales_categories",
  "sales_order_settings",
  "order_creation_logs",
  "sales_payment_terms",
  "tax_scenarios",
  "stock_locations",
  "payment_methods",
  "document_types"
];

$all=DB::all("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=? AND TABLE_TYPE='BASE TABLE' ORDER BY TABLE_NAME",[$db]);
$allNames=array_map(fn($r)=>(string)$r['TABLE_NAME'],$all);
$newTables=array_values(array_filter($allNames,fn($t)=>str_starts_with($t,$prefix)));
$legacyExisting=array_values(array_intersect($legacy,$allNames));
$unknown=array_values(array_filter($allNames,fn($t)=>!str_starts_with($t,$prefix)&&!in_array($t,$legacy,true)));

$counts=[];
foreach($legacyExisting as $table){
 try{$counts[$table]=(int)DB::conn()->query("SELECT COUNT(*) FROM `".$table."`")->fetchColumn();}
 catch(Throwable){$counts[$table]=null;}
}

$done=false;$error=null;$dropped=[];
if($_SERVER['REQUEST_METHOD']==='POST'){
 $confirm=(string)($_POST['confirm']??'');
 if(!hash_equals('APAGAR LEGADO',$confirm)){
  $error='Digite exatamente APAGAR LEGADO para confirmar.';
 }elseif($unknown){
  $error='Existem tabelas não reconhecidas fora do prefixo '.$prefix.'. Por segurança, nenhuma tabela foi removida.';
 }else{
  try{
   DB::conn()->exec('SET FOREIGN_KEY_CHECKS=0');
   foreach($legacyExisting as $table){
    DB::conn()->exec("DROP TABLE IF EXISTS `".$table."`");
    $dropped[]=$table;
   }
   DB::conn()->exec('SET FOREIGN_KEY_CHECKS=1');
   $done=true;
  }catch(Throwable $e){
   try{DB::conn()->exec('SET FOREIGN_KEY_CHECKS=1');}catch(Throwable){}
   $error=$e->getMessage();
  }
 }
}

function h(mixed $v): string{return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
?><!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Limpeza do legado • Tecnodata CRM</title>
<style>
body{font-family:Arial,Helvetica,sans-serif;background:#f5f7f8;color:#17252c;margin:0;padding:28px}
.wrap{max-width:980px;margin:auto}.card{background:#fff;border:1px solid #e2e8eb;border-radius:10px;padding:20px;margin-bottom:14px}
h1{margin:0 0 6px;font-size:26px}h2{font-size:16px;margin:0 0 12px}.muted{color:#667680}.ok{color:#007b49}.danger{color:#c83f1d}
table{width:100%;border-collapse:collapse}th,td{text-align:left;padding:9px 8px;border-bottom:1px solid #e8edef;font-size:13px}th{font-size:10px;text-transform:uppercase;color:#667680}
code{background:#eef2f4;padding:2px 5px;border-radius:5px}.alert{padding:12px;border-radius:9px;margin-bottom:14px}.alert-danger{background:#fff0ec;color:#9d351d}.alert-ok{background:#edf8f2;color:#006d40}
input{width:100%;padding:11px;border:1px solid #cfd9dd;border-radius:8px;margin:6px 0 10px}.btn{border:0;border-radius:8px;padding:10px 14px;font-weight:700;cursor:pointer}.btn-danger{background:#ea5127;color:#fff}
</style>
</head>
<body><div class="wrap">
<div class="card">
 <div class="muted">BANCO ATUAL</div>
 <h1>Limpeza do CRM legado</h1>
 <p>Banco: <strong><?=h($db)?></strong> • Prefixo preservado: <code><?=h($prefix)?></code></p>
 <p class="muted">Esta ferramenta remove somente as tabelas conhecidas do CRM antigo. As tabelas <?=h($prefix)?>* nunca são removidas.</p>
</div>

<?php if($done):?>
<div class="alert alert-ok"><strong>Limpeza concluída.</strong> <?=count($dropped)?> tabela(s) legada(s) removida(s). Desative novamente <code>installer.enabled</code>.</div>
<div class="card"><h2>Tabelas removidas</h2><p><?=h(implode(', ',$dropped)?:'Nenhuma')?></p></div>
<?php else:?>
<?php if($error):?><div class="alert alert-danger"><strong>Não executado:</strong> <?=h($error)?></div><?php endif;?>

<div class="card">
 <h2>Tabelas novas preservadas</h2>
 <p class="ok"><strong><?=count($newTables)?></strong> tabela(s) com prefixo <code><?=h($prefix)?></code>.</p>
 <p class="muted"><?=h(implode(', ',$newTables)?:'Nenhuma tabela nova encontrada. Rode o install.php antes de limpar o legado.')?></p>
</div>

<div class="card">
 <h2>Legado que será apagado</h2>
 <?php if(!$legacyExisting):?><p class="ok">Nenhuma tabela antiga conhecida encontrada.</p>
 <?php else:?><table><thead><tr><th>Tabela</th><th>Registros</th></tr></thead><tbody>
 <?php foreach($legacyExisting as $table):?><tr><td><code><?=h($table)?></code></td><td><?=is_int($counts[$table])?number_format($counts[$table],0,',','.'):'—'?></td></tr><?php endforeach;?>
 </tbody></table><?php endif;?>
</div>

<?php if($unknown):?>
<div class="card">
 <h2 class="danger">Tabelas não reconhecidas</h2>
 <p>Por segurança, a limpeza está bloqueada enquanto existirem tabelas fora do prefixo que não fazem parte do legado conhecido:</p>
 <p><?=h(implode(', ',$unknown))?></p>
</div>
<?php elseif($legacyExisting&&$newTables):?>
<div class="card">
 <h2>Confirmação destrutiva</h2>
 <p class="danger"><strong>Os dados das tabelas antigas serão excluídos definitivamente.</strong></p>
 <form method="post">
  <label>Digite <strong>APAGAR LEGADO</strong></label>
  <input name="confirm" autocomplete="off" required>
  <button class="btn btn-danger">Apagar tabelas antigas</button>
 </form>
</div>
<?php elseif(!$newTables):?>
<div class="alert alert-danger">As tabelas novas ainda não foram encontradas. Execute o instalador da rebuild-clean antes de qualquer limpeza.</div>
<?php endif;?>
<?php endif;?>
</div></body></html>
