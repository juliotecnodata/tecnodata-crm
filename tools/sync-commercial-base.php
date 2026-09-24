<?php
declare(strict_types=1);

/**
 * Sincronização segura da base comercial CRM Omie.
 *
 * Somente leitura por padrão:
 *   php tools/sync-commercial-base.php
 *
 * Exibir apenas a saúde local:
 *   php tools/sync-commercial-base.php --status
 *
 * Processar alterações Tecnodata -> Omie (CFC/Revendedor etc.):
 *   php tools/sync-commercial-base.php --push
 */
if(PHP_SAPI!=='cli'){fwrite(STDERR,"Execute via CLI.\n");exit(1);}
$root=dirname(__DIR__);
require $root.'/app/bootstrap.php';

$options=getopt('',['status','push','users-only','accounts-only']);
$statusOnly=array_key_exists('status',$options);
$push=array_key_exists('push',$options);
$usersOnly=array_key_exists('users-only',$options);
$accountsOnly=array_key_exists('accounts-only',$options);

function cliSection(string $title): void{echo "\n".$title."\n".str_repeat('=',mb_strlen($title))."\n";}
function cliRow(string $label,mixed $value): void{echo str_pad($label,42).': '.$value."\n";}

try{
 CommercialSchema::ensure();

 if($statusOnly){
  cliSection('SAÚDE DA INTELIGÊNCIA COMERCIAL');
  foreach(CommercialPortfolioService::health() as $key=>$value)cliRow($key,$value);
  exit(0);
 }

 cliSection('TECNODATA — SINCRONIZAÇÃO BASE CRM OMIE');
 cliRow('Modo de entrada','Omie -> Tecnodata');
 cliRow('Escrita no Omie',$push?'SIM, somente fila pendente':'NÃO');

 $summary=[];
 if(!$accountsOnly){
  $page=1;$processed=0;
  do{
   $result=CommercialPortfolioService::syncUsersPage($page);
   $processed+=(int)($result['count']??0);
   echo "CRM Usuários: página {$page}/".(int)($result['total_pages']??0)." — ".(int)($result['count']??0)." registros\n";
   $page++;
  }while(empty($result['done'])&&$page<=1000);
  $summary['crm_users']=$processed;
 }

 if(!$usersOnly){
  $page=1;$processed=0;$totals=['linked'=>0,'unlinked'=>0,'ambiguous'=>0,'owner_linked'=>0,'profiles_seeded'=>0];
  do{
   $result=CommercialPortfolioService::syncAccountsPage($page);
   $processed+=(int)($result['count']??0);
   foreach($totals as $key=>$value)$totals[$key]+=(int)($result['stats'][$key]??0);
   echo "CRM Contas: página {$page}/".(int)($result['total_pages']??0)." — ".(int)($result['count']??0)." registros";
   if(!empty($result['stats']))echo " | vínculos ".(int)$result['stats']['linked']." | sem vínculo ".(int)$result['stats']['unlinked']." | ambíguos ".(int)$result['stats']['ambiguous'];
   echo "\n";
   $page++;
  }while(empty($result['done'])&&$page<=10000);
  $summary['crm_accounts']=$processed;
  $summary['account_stats']=$totals;
 }

 $identity=CommercialIdentityService::reconcileUsers();
 $summary['identity']=$identity;

 if($push){
  cliSection('SAÍDA TECNODATA -> OMIE');
  $outbox=CommercialOutboxService::process(500);
  foreach($outbox as $key=>$value)cliRow($key,$value);
  $summary['outbox']=$outbox;
 }

 cliSection('RESULTADO');
 foreach(CommercialPortfolioService::health() as $key=>$value)cliRow($key,$value);

 cliSection('IDENTIDADE DE USUÁRIOS');
 cliRow('Usuários locais analisados',$identity['local_users']??0);
 cliRow('Usuários CRM ativos',$identity['crm_users']??0);
 cliRow('Vínculos resolvidos',$identity['linked']??0);
 cliRow('Pendentes/ambíguos',$identity['ambiguous']??0);

 $unlinked=DB::all("SELECT a.omie_code,a.name,a.trade_name,a.document,a.crm_user_code
                    FROM crm_accounts a
                    LEFT JOIN crm_account_links l ON l.crm_account_code=a.omie_code
                    WHERE l.crm_account_code IS NULL
                    ORDER BY a.name LIMIT 30");
 if($unlinked){
  cliSection('AMOSTRA — CONTAS CRM SEM CLIENTE GERAL VINCULADO');
  foreach($unlinked as $row){
   echo '- Conta '.$row['omie_code'].' | '.($row['trade_name']?:$row['name']).' | doc '.($row['document']?:'SEM_DOC').' | resp '.($row['crm_user_code']?:'SEM_RESP')."\n";
  }
 }

 $owners=DB::all("SELECT cu.omie_code,cu.name,cu.email,
                         COUNT(a.omie_code) accounts,
                         COUNT(l.client_id) linked_clients,
                         COUNT(CASE WHEN c.crm_owner_user_id IS NOT NULL THEN 1 END) local_owner_clients,
                         MAX(u.name) local_user
                  FROM crm_users cu
                  LEFT JOIN crm_accounts a ON a.crm_user_code=cu.omie_code
                  LEFT JOIN crm_account_links l ON l.crm_account_code=a.omie_code
                  LEFT JOIN clients c ON c.id=l.client_id
                  LEFT JOIN users u ON u.crm_user_omie_code=cu.omie_code AND u.active=1
                  GROUP BY cu.omie_code,cu.name,cu.email
                  ORDER BY accounts DESC,cu.name");
 if($owners){
  cliSection('CARTEIRA POR RESPONSÁVEL CRM');
  foreach($owners as $row){
   echo '- '.$row['omie_code'].' | '.$row['name'].' | contas '.(int)$row['accounts'].' | vinculadas '.(int)$row['linked_clients'].' | usuário Tecnodata '.($row['local_user']?:'NÃO MAPEADO')."\n";
  }
 }

 echo "\nSincronização concluída.\n";
}catch(Throwable $e){
 fwrite(STDERR,"\nERRO: ".$e->getMessage()."\n");
 exit(1);
}
