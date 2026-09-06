<?php
declare(strict_types=1);
header('Content-Type: text/html; charset=utf-8');
ini_set('display_errors','1');
error_reporting(E_ALL);

$root=dirname(__DIR__);
$configFile=$root.'/config/config.php';
if(!is_file($configFile))exit('config ausente');
$config=require $configFile;
$expected=(string)($config['installer']['token']??'');
$given=(string)($_GET['token']??'');
if($expected===''||$given===''||!hash_equals($expected,$given)){http_response_code(403);exit('Token inválido.');}

$tests=[];
function runTest(string $name,callable $fn,array &$tests): void{
 $t=microtime(true);
 try{
  $r=$fn();
  $tests[]=['name'=>$name,'ok'=>true,'time'=>round((microtime(true)-$t)*1000,1),'detail'=>is_scalar($r)?(string)$r:json_encode($r,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)];
 }catch(Throwable $e){
  $tests[]=['name'=>$name,'ok'=>false,'time'=>round((microtime(true)-$t)*1000,1),'detail'=>get_class($e).': '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()];
 }
}

runTest('Carregar bootstrap',function()use($root){
 require_once $root.'/app/bootstrap.php';
 return 'bootstrap carregado';
},$tests);

if(class_exists('DB')){
 runTest('DB simples',fn()=>DB::scalar("SELECT COUNT(*) FROM service_orders"),$tests);
 runTest('Serviços mês atual',function(){
  $start=date('Y-m-01');$next=date('Y-m-d',strtotime($start.' +1 month'));
  return DB::one("SELECT COUNT(*) qtd,COALESCE(SUM(total),0) total FROM service_orders WHERE service_date>=? AND service_date<?",[$start,$next]);
 },$tests);
 runTest('JOIN serviços/clientes/vendedores',function(){
  $start=date('Y-m-01');$next=date('Y-m-d',strtotime($start.' +1 month'));
  return DB::all("SELECT so.id,so.omie_code,c.name client_name,s.name seller_name
                  FROM service_orders so
                  LEFT JOIN clients c ON c.omie_code=so.client_omie_code
                  LEFT JOIN sellers s ON s.omie_code=so.seller_omie_code
                  WHERE so.service_date>=? AND so.service_date<?
                  ORDER BY so.service_date DESC,so.id DESC LIMIT 10",[$start,$next]);
 },$tests);
}

if(class_exists('GoalService')){
 runTest('GoalService managementMonth',fn()=>GoalService::managementMonth(date('Y-m')),$tests);
}
if(function_exists('render')){
 runTest('Render services mínimo',function(){
  ob_start();
  render('services',[
   'rows'=>[],'month'=>date('Y-m'),'currentMonth'=>date('Y-m'),'months'=>[],
   'total'=>0,'valid'=>0,'cancelled'=>0,'withoutSeller'=>0,'totalRows'=>0,
   'health'=>['total_table'=>6300,'null_dates'=>0,'null_sellers'=>0],
   'serviceSchema'=>['code'=>'omie_code','date'=>'service_date'],'serviceError'=>null
  ]);
  $html=ob_get_clean();
  return 'HTML bytes: '.strlen((string)$html);
 },$tests);
}

?><!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Runtime CRM</title><style>
body{font-family:Arial,sans-serif;background:#f4f6f7;padding:24px;color:#253038}.wrap{max-width:1100px;margin:auto}.card{background:#fff;border:1px solid #dfe5e8;border-radius:10px;padding:18px;margin-bottom:12px}.ok{color:#2f7d64}.bad{color:#c55252}pre{white-space:pre-wrap;word-break:break-word;background:#f8fafb;padding:10px;border-radius:8px;font-size:12px}
</style></head><body><div class="wrap"><div class="card"><h1>Runtime do CRM</h1><p>Testa o aplicativo real, não apenas o banco.</p></div>
<?php foreach($tests as $t):?><div class="card"><h2 class="<?=$t['ok']?'ok':'bad'?>"><?=htmlspecialchars($t['name'])?> — <?=$t['ok']?'OK':'ERRO'?> — <?=htmlspecialchars((string)$t['time'])?> ms</h2><pre><?=htmlspecialchars((string)$t['detail'])?></pre></div><?php endforeach;?>
</div></body></html>