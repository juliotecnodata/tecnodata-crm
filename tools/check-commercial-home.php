<?php
declare(strict_types=1);

/**
 * Valida a Tela 1 — Minha Home para as consultoras comerciais ativas.
 * Não consulta nem altera o Omie.
 *
 * Uso:
 *   php tools/check-commercial-home.php
 */
if(PHP_SAPI!=='cli'){fwrite(STDERR,"Execute via CLI.\n");exit(1);}
require dirname(__DIR__).'/app/bootstrap.php';

function hsec(string $title): void{echo "\n".$title."\n".str_repeat('=',mb_strlen($title))."\n";}
function hrow(string $label,mixed $value): void{echo str_pad($label,44).': '.$value."\n";}

try{
 CommercialSchema::ensure();
 hsec('TELA 1 — MINHA HOME');
 hrow('Consulta API Omie','NÃO');
 hrow('Escrita no Omie','NÃO');

 $codes=CommercialPortfolioService::activeCrmSellerCodes();
 if(!$codes)throw new RuntimeException('Nenhum vendedor comercial ativo configurado.');

 foreach($codes as $code){
  $user=DB::one("SELECT * FROM users WHERE active=1 AND role='seller' AND crm_user_omie_code=? LIMIT 1",[$code]);
  $crm=DB::one("SELECT name FROM crm_users WHERE omie_code=? LIMIT 1",[$code]);
  hsec(($crm['name']??'Vendedor').' · '.$code);
  if(!$user){
   hrow('Usuário local','NÃO MAPEADO');
   continue;
  }
  $home=CommercialHomeService::build($user,['home_per_page'=>5,'home_order'=>'stale']);
  hrow('Usuário local',$user['name']);
  hrow('Sem contato 30+ dias',(int)($home['stale']['count']??0));
  hrow('Retornos atrasados',(int)($home['overdue']['count']??0));
  hrow('Retornos hoje',(int)($home['today']['count']??0));
  hrow('Precisam de atenção',(int)($home['attention']['count']??0));
  hrow('Casos exibidos nos cards',count($home['overdue']['items']??[])+count($home['stale']['items']??[])+count($home['today']['items']??[])+count($home['attention']['items']??[]));
  hrow('Registros carteira',(int)($home['portfolio']['total']??0));
  hrow('Linhas na abertura',count($home['portfolio']['rows']??[]));
  $first=$home['portfolio']['rows'][0]??null;
  if($first){
   $name=trim((string)($first['trade_name']??''))?:trim((string)($first['name']??''));
   hrow('Primeira prioridade',$name.' · CRM '.($first['omie_code']??''));
  }
 }

 hsec('RESULTADO');
 echo "Minha Home pronta para renderização.\n";
}catch(Throwable $e){
 fwrite(STDERR,"\nERRO: ".$e->getMessage()."\n");
 exit(1);
}
