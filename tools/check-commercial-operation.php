<?php
declare(strict_types=1);

/**
 * Verifica a infraestrutura do Módulo 2 sem criar atividades.
 * Não acessa nem altera o Omie.
 *
 * Uso:
 *   php tools/check-commercial-operation.php
 */
if(PHP_SAPI!=='cli'){fwrite(STDERR,"Execute via CLI.\n");exit(1);}
require dirname(__DIR__).'/app/bootstrap.php';

function csec(string $title): void{echo "\n".$title."\n".str_repeat('=',mb_strlen($title))."\n";}
function crow(string $label,mixed $value): void{echo str_pad($label,48).': '.$value."\n";}
function hascol(string $table,string $column): bool{
 foreach(DB::all("SHOW COLUMNS FROM ".$table) as $row)if((string)$row['Field']===$column)return true;
 return false;
}

try{
 CommercialSchema::ensure();

 csec('MÓDULO 2 — OPERAÇÃO COMERCIAL');
 crow('Consulta API Omie','NÃO');
 crow('Escrita no Omie','NÃO');

 $checks=[
  'activities.crm_account_code'=>hascol('activities','crm_account_code'),
  'activities.activity_type'=>hascol('activities','activity_type'),
  'activities.category_code'=>hascol('activities','category_code'),
  'activities.outcome_code'=>hascol('activities','outcome_code'),
  'tasks.crm_account_code'=>hascol('tasks','crm_account_code'),
  'tasks.source_activity_id'=>hascol('tasks','source_activity_id'),
  'crm_account_notes'=>(bool)DB::scalar("SELECT COUNT(*)>=0 FROM crm_account_notes"),
 ];
 csec('ESTRUTURA');
 foreach($checks as $label=>$ok)crow($label,$ok?'OK':'FALTA');

 $activities=(int)(DB::scalar("SELECT COUNT(*) FROM activities")??0);
 $accountActivities=(int)(DB::scalar("SELECT COUNT(*) FROM activities WHERE crm_account_code IS NOT NULL")??0);
 $legacyActivities=(int)(DB::scalar("SELECT COUNT(*) FROM activities WHERE crm_account_code IS NULL")??0);
 $tasks=(int)(DB::scalar("SELECT COUNT(*) FROM tasks")??0);
 $accountTasks=(int)(DB::scalar("SELECT COUNT(*) FROM tasks WHERE crm_account_code IS NOT NULL")??0);
 $prospectTasks=(int)(DB::scalar("SELECT COUNT(*) FROM tasks WHERE crm_account_code IS NOT NULL AND client_id IS NULL")??0);

 csec('HISTÓRICO');
 crow('Atividades totais',$activities);
 crow('Atividades ligadas a Conta CRM',$accountActivities);
 crow('Atividades legadas sem Conta CRM',$legacyActivities);
 crow('Tarefas totais',$tasks);
 crow('Tarefas ligadas a Conta CRM',$accountTasks);
 crow('Tarefas de prospects sem Cliente Geral',$prospectTasks);
 crow('Notas livres',(int)(DB::scalar("SELECT COUNT(*) FROM crm_account_notes")??0));

 csec('CARTEIRA OPERACIONAL');
 foreach(CommercialPortfolioService::activeCrmSellerCodes() as $code){
  $crm=DB::one("SELECT name FROM crm_users WHERE omie_code=? LIMIT 1",[$code]);
  $local=DB::one("SELECT id,name FROM users WHERE crm_user_omie_code=? AND active=1 AND role='seller' LIMIT 1",[$code]);
  $accounts=(int)(DB::scalar("SELECT COUNT(*) FROM crm_accounts WHERE active=1 AND crm_user_code=?",[$code])??0);
  echo '- '.$code.' | '.($crm['name']??'CRM desconhecido').' | local '.($local['name']??'NÃO MAPEADO').' | contas '.$accounts."\n";
 }

 csec('RESULTADO');
 $missing=array_keys(array_filter($checks,static fn($ok)=>!$ok));
 crow('Status',$missing?'ATENÇÃO':'OK');
 if($missing)echo 'Faltando: '.implode(', ',$missing)."\n";
 else echo "Estrutura pronta para registrar atividades em Cliente ou Prospect.\n";
}catch(Throwable $e){
 fwrite(STDERR,"\nERRO: ".$e->getMessage()."\n");exit(1);
}
