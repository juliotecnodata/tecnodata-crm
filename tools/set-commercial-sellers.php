<?php
declare(strict_types=1);

/**
 * Define quais usuários do CRM Omie são vendedores comerciais ativos no Tecnodata.
 * Não altera o Omie.
 *
 * Listar usuários CRM e configuração atual:
 *   php tools/set-commercial-sellers.php --list
 *
 * Definir vendedores ativos:
 *   php tools/set-commercial-sellers.php --codes=2403587771,712952964
 */
if(PHP_SAPI!=='cli'){fwrite(STDERR,"Execute via CLI.\n");exit(1);}
require dirname(__DIR__).'/app/bootstrap.php';
CommercialSchema::ensure();

$options=getopt('',['list','codes:']);
$list=array_key_exists('list',$options);
$codesRaw=trim((string)($options['codes']??''));

function ssection(string $title): void{echo "\n".$title."\n".str_repeat('=',mb_strlen($title))."\n";}
function srow(string $label,mixed $value): void{echo str_pad($label,42).': '.$value."\n";}

try{
 if($list||$codesRaw===''){
  $configured=CommercialPortfolioService::activeCrmSellerCodes();
  ssection('USUÁRIOS CRM OMIE');
  foreach(DB::all("SELECT omie_code,name,email,active FROM crm_users ORDER BY active DESC,name") as $row){
   $code=(string)$row['omie_code'];$mark=in_array($code,$configured,true)?'*':' ';
   echo $mark.' '.$code.' | '.$row['name'].' | '.($row['email']?:'sem e-mail').' | '.((int)$row['active']===1?'CRM ativo':'CRM inativo')."\n";
  }
  ssection('VENDEDORES OPERACIONAIS CONFIGURADOS');
  if(!$configured)echo "- nenhum\n";
  foreach($configured as $code){
   $u=DB::one("SELECT name FROM crm_users WHERE omie_code=? LIMIT 1",[$code]);
   echo '- '.$code.' | '.($u['name']??'não encontrado')."\n";
  }
  if($codesRaw==='')exit(0);
 }

 $codes=array_values(array_unique(array_filter(array_map('trim',explode(',',$codesRaw)),static fn($v)=>$v!=='')));
 $payload=CommercialPortfolioService::setActiveCrmSellerCodes($codes,0,'Configuração operacional via CLI');

 ssection('CONFIGURAÇÃO APLICADA');
 foreach($payload['sellers'] as $seller)echo '- '.$seller['code'].' | '.$seller['name']."\n";

 $health=CommercialPortfolioService::health();
 ssection('CARTEIRA OPERACIONAL');
 srow('Vendedores ativos', $health['active_crm_sellers']??0);
 srow('Clientes com vendedor ativo', $health['clients_with_crm_owner']??0);
 srow('Clientes com responsável CRM legado/inativo', $health['clients_with_stale_crm_owner']??0);
 srow('Clientes sem vendedor operacional', $health['clients_without_crm_owner']??0);

 echo "\nNenhuma alteração foi enviada ao Omie.\n";
}catch(Throwable $e){
 fwrite(STDERR,"\nERRO: ".$e->getMessage()."\n");exit(1);
}
