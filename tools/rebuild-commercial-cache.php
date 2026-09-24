<?php
declare(strict_types=1);

/**
 * Reconstrói a carteira comercial usando somente o cache local já baixado.
 * Não chama API Omie e não escreve no Omie.
 *
 * Uso:
 *   php tools/rebuild-commercial-cache.php
 */
if(PHP_SAPI!=='cli'){fwrite(STDERR,"Execute via CLI.\n");exit(1);}
require dirname(__DIR__).'/app/bootstrap.php';

function rsection(string $title): void{echo "\n".$title."\n".str_repeat('=',mb_strlen($title))."\n";}
function rrow(string $label,mixed $value): void{echo str_pad($label,46).': '.$value."\n";}

try{
 CommercialSchema::ensure();

 rsection('RECONSTRUÇÃO LOCAL DA CARTEIRA COMERCIAL');
 rrow('Origem','cache local CRM Omie');
 rrow('Consulta API Omie','NÃO');
 rrow('Escrita no Omie','NÃO');

 $progress=function(string $stage,int $done,int $total): void{
  $label=$stage==='profiles'?'Perfis':'Vínculos';
  $pct=$total>0?number_format(($done/$total)*100,1,',','.'): '100,0';
  echo sprintf("%s: %d / %d (%s%%)\n",$label,$done,$total,$pct);
  if(function_exists('ob_flush'))@ob_flush();flush();
 };
 $profiles=CommercialPortfolioService::rebuildCachedProfiles($progress,300);
 rsection('PERFIS COMERCIAIS');
 foreach($profiles as $k=>$v)rrow($k,$v);

 $links=CommercialPortfolioService::reconcileCachedLinks($progress,500);
 rsection('RECONCILIAÇÃO DE VÍNCULOS');
 foreach($links as $k=>$v)rrow($k,$v);

 $owners=CommercialPortfolioService::rebuildOperationalOwners();
 rsection('EQUIPE OPERACIONAL');
 foreach($owners as $k=>$v)rrow($k,$v);

 $health=CommercialPortfolioService::health();
 rsection('SAÚDE FINAL');
 foreach($health as $k=>$v)rrow($k,$v);

 rsection('CARTEIRA POR VENDEDOR ATIVO');
 foreach(CommercialPortfolioService::activeCrmSellerCodes() as $code){
  $user=DB::one("SELECT name,email FROM crm_users WHERE omie_code=? LIMIT 1",[$code]);
  $accounts=(int)(DB::scalar("SELECT COUNT(*) FROM crm_accounts WHERE active=1 AND crm_user_code=?",[$code])??0);
  $linked=(int)(DB::scalar("SELECT COUNT(*) FROM crm_accounts a JOIN crm_account_links l ON l.crm_account_code=a.omie_code WHERE a.active=1 AND a.crm_user_code=?",[$code])??0);
  $prospects=$accounts-$linked;
  echo '- '.$code.' | '.($user['name']??'desconhecido').' | contas '.$accounts.' | clientes vinculados '.$linked.' | prospects/contas CRM '.$prospects."\n";
 }

 echo "\nReconstrução concluída sem acessar ou alterar o Omie.\n";
}catch(Throwable $e){
 fwrite(STDERR,"\nERRO: ".$e->getMessage()."\n");exit(1);
}
