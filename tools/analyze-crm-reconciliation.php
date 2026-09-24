<?php
declare(strict_types=1);

/**
 * Diagnóstico local de reconciliação CRM Omie x Cliente Geral.
 * Não chama a API e não altera dados.
 *
 * Uso:
 *   php tools/analyze-crm-reconciliation.php
 */
if(PHP_SAPI!=='cli'){fwrite(STDERR,"Execute via CLI.\n");exit(1);}
require dirname(__DIR__).'/app/bootstrap.php';
CommercialSchema::ensure();

function asection(string $title): void{echo "\n".$title."\n".str_repeat('=',mb_strlen($title))."\n";}
function arow(string $label,mixed $value): void{echo str_pad($label,50).': '.$value."\n";}
function adigits(mixed $v): string{return preg_replace('/\D+/','',(string)$v)?:'';}
function anorm(mixed $v): string{
 $s=mb_strtolower(trim((string)$v),'UTF-8');
 $ascii=@iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$s);if(is_string($ascii)&&$ascii!=='')$s=$ascii;
 return trim((string)preg_replace('/[^a-z0-9]+/',' ',$s));
}
function aemails(mixed $v): array{
 $parts=preg_split('/[;,\s]+/',mb_strtolower((string)$v,'UTF-8'))?:[];
 return array_values(array_unique(array_filter($parts,static fn($x)=>filter_var($x,FILTER_VALIDATE_EMAIL))));
}
function acodePrefix(mixed $v): string{
 if(preg_match('/\bC\s*0*([0-9]{2,8})\b/i',(string)$v,$m))return 'C'.str_pad($m[1],5,'0',STR_PAD_LEFT);
 return '';
}
function rawGeneralIntegration(array $client): string{
 $raw=json_decode((string)($client['raw_json']??''),true);if(!is_array($raw))return '';
 foreach([$raw,$raw['remote_snapshot']??null,$raw['request']??null] as $node){
  if(is_array($node)){
   $v=trim((string)($node['codigo_cliente_integracao']??''));
   if($v!=='')return $v;
  }
 }
 return '';
}
function rawCrmEmail(array $account): string{
 $raw=json_decode((string)($account['raw_json']??''),true);if(!is_array($raw))return '';
 return trim((string)($raw['telefone_email']['cEmail']??''));
}
function rawCrmPhone(array $account): string{
 $raw=json_decode((string)($account['raw_json']??''),true);if(!is_array($raw))return '';
 $tel=$raw['telefone_email']??[];
 return adigits((string)($tel['cDDDTel']??'').(string)($tel['cNumTel']??''));
}

$clients=DB::all("SELECT id,omie_code,name,legal_name,document,email,phone,active,crm_inactive,raw_json FROM clients");
$accounts=DB::all("SELECT a.*,l.client_id FROM crm_accounts a LEFT JOIN crm_account_links l ON l.crm_account_code=a.omie_code WHERE a.active=1");

$clientById=[];$docActive=[];$docAll=[];$integration=[];$omie=[];$prefix=[];$name=[];$email=[];$phone=[];
foreach($clients as $c){
 $id=(int)$c['id'];$clientById[$id]=$c;$doc=adigits($c['document']??'');
 if($doc!==''){$docAll[$doc][]=$id;if((int)$c['active']===1)$docActive[$doc][]=$id;}
 $omieCode=trim((string)($c['omie_code']??''));if($omieCode!=='')$omie[$omieCode][]=$id;
 $int=trim(rawGeneralIntegration($c));if($int!=='')$integration[$int][]=$id;
 foreach([acodePrefix($int),acodePrefix($c['name']??''),acodePrefix($c['legal_name']??'')] as $p)if($p!=='')$prefix[$p][]=$id;
 foreach([anorm($c['name']??''),anorm($c['legal_name']??'')] as $n)if($n!=='')$name[$n][]=$id;
 foreach(aemails($c['email']??'') as $e)$email[$e][]=$id;
 $ph=adigits($c['phone']??'');if(strlen($ph)>=10)$phone[$ph][]=$id;
}
foreach([$docActive,$docAll,$integration,$omie,$prefix,$name,$email,$phone] as &$map)foreach($map as &$ids)$ids=array_values(array_unique($ids));
unset($map,$ids);

$known=0;$knownIntSameGeneralInt=0;$knownIntSameOmie=0;$knownPrefixGeneral=0;$knownNameExact=0;
$unlinked=0;$inactiveDoc=0;$intGeneral=0;$intOmie=0;$prefixUnique=0;$nameUnique=0;$emailUnique=0;$phoneUnique=0;
$samples=['inactive_doc'=>[],'integration'=>[],'prefix'=>[],'name'=>[]];
$localMultiAccounts=[];$ownerByClient=[];$crmDocCounts=[];

foreach($accounts as $a){
 $doc=adigits($a['document']??'');if($doc!=='')$crmDocCounts[$doc]=($crmDocCounts[$doc]??0)+1;
 $clientId=(int)($a['client_id']??0);
 if($clientId>0&&isset($clientById[$clientId])){
  $known++;$c=$clientById[$clientId];$crmInt=trim((string)($a['integration_code']??''));$genInt=trim(rawGeneralIntegration($c));
  if($crmInt!==''&&$genInt!==''&&$crmInt===$genInt)$knownIntSameGeneralInt++;
  if($crmInt!==''&&$crmInt===trim((string)$c['omie_code']))$knownIntSameOmie++;
  $ap=acodePrefix((string)($a['trade_name']??'').' '.(string)($a['name']??'').' '.$crmInt);
  $cp=acodePrefix($genInt.' '.(string)$c['name'].' '.(string)$c['legal_name']);
  if($ap!==''&&$cp!==''&&$ap===$cp)$knownPrefixGeneral++;
  if(anorm($a['trade_name']??$a['name']??'')!==''&&in_array(anorm($a['trade_name']??$a['name']??''),[anorm($c['name']??''),anorm($c['legal_name']??'')],true))$knownNameExact++;
  $localMultiAccounts[$clientId][]=(string)$a['omie_code'];
  $owner=trim((string)($a['crm_user_code']??''));if($owner!=='')$ownerByClient[$clientId][$owner]=true;
  continue;
 }
 $unlinked++;
 $crmInt=trim((string)($a['integration_code']??''));
 $trade=(string)($a['trade_name']??'');$accountName=(string)($a['name']??'');
 if($doc!==''&&!isset($docActive[$doc])&&count($docAll[$doc]??[])===1){
  $cid=$docAll[$doc][0];if((int)($clientById[$cid]['active']??0)===0){
   $inactiveDoc++;if(count($samples['inactive_doc'])<12)$samples['inactive_doc'][]=[$a,$clientById[$cid]];
  }
 }
 if($crmInt!==''&&count($integration[$crmInt]??[])===1){
  $intGeneral++;if(count($samples['integration'])<12)$samples['integration'][]=[$a,$clientById[$integration[$crmInt][0]]];
 }
 if($crmInt!==''&&count($omie[$crmInt]??[])===1)$intOmie++;
 $p=acodePrefix($trade.' '.$accountName.' '.$crmInt);
 if($p!==''&&count($prefix[$p]??[])===1){
  $prefixUnique++;if(count($samples['prefix'])<12)$samples['prefix'][]=[$a,$clientById[$prefix[$p][0]]];
 }
 $candidates=[];
 foreach([anorm($trade),anorm($accountName)] as $n)if($n!==''&&count($name[$n]??[])===1)$candidates=array_merge($candidates,$name[$n]);
 $candidates=array_values(array_unique($candidates));
 if(count($candidates)===1){
  $nameUnique++;if(count($samples['name'])<12)$samples['name'][]=[$a,$clientById[$candidates[0]]];
 }
 foreach(aemails(rawCrmEmail($a)) as $e)if(count($email[$e]??[])===1){$emailUnique++;break;}
 $ph=rawCrmPhone($a);if(strlen($ph)>=10&&count($phone[$ph]??[])===1)$phoneUnique++;
}

$multiClientCount=0;$conflictingOwners=0;$maxAccounts=0;
foreach($localMultiAccounts as $cid=>$accs){
 $qty=count(array_unique($accs));if($qty>1){$multiClientCount++;$maxAccounts=max($maxAccounts,$qty);}
 if(count($ownerByClient[$cid]??[])>1)$conflictingOwners++;
}
$duplicateCrmDocs=count(array_filter($crmDocCounts,static fn($n)=>$n>1));

asection('RECONCILIAÇÃO CRM OMIE x CLIENTE GERAL — ANÁLISE LOCAL');
arow('Contas CRM ativas em cache',count($accounts));
arow('Clientes Geral no cache',count($clients));
arow('Vínculos atuais', $known);
arow('Contas ainda sem vínculo',$unlinked);

asection('VALIDAÇÃO DOS IDENTIFICADORES NOS VÍNCULOS JÁ CONHECIDOS');
arow('cCodInt CRM = codigo_cliente_integracao Geral',$knownIntSameGeneralInt.' / '.$known);
arow('cCodInt CRM = codigo_cliente_omie Geral',$knownIntSameOmie.' / '.$known);
arow('Prefixo Cxxxxx coincide',$knownPrefixGeneral.' / '.$known);
arow('Nome exato coincide',$knownNameExact.' / '.$known);

asection('POTENCIAL DE RECUPERAÇÃO ENTRE OS NÃO VINCULADOS');
arow('Documento existe em cliente Geral INATIVO único',$inactiveDoc);
arow('cCodInt CRM casa com integração Geral única',$intGeneral);
arow('cCodInt CRM casa com código Omie Geral único',$intOmie);
arow('Prefixo Cxxxxx casa com cliente único',$prefixUnique);
arow('Nome exato casa com cliente único',$nameUnique);
arow('E-mail CRM casa com cliente único',$emailUnique);
arow('Telefone CRM casa com cliente único',$phoneUnique);

asection('DUPLICIDADE / CONFLITO');
arow('Documentos repetidos entre Contas CRM',$duplicateCrmDocs);
arow('Clientes locais ligados a >1 Conta CRM',$multiClientCount);
arow('Clientes com >1 responsável CRM nos vínculos',$conflictingOwners);
arow('Máximo de Contas CRM para um mesmo cliente local',$maxAccounts);

foreach($samples as $type=>$rows){
 if(!$rows)continue;
 asection('AMOSTRA — '.strtoupper(str_replace('_',' ',$type)));
 foreach($rows as [$a,$c]){
  echo '- CRM '.($a['omie_code']??'').' | '.($a['trade_name']?:$a['name']).' | doc '.($a['document']?:'SEM_DOC').' | cCodInt '.($a['integration_code']?:'SEM_CODINT')."\n";
  echo '  Geral '.($c['omie_code']??'').' | '.($c['name']??'').' | doc '.($c['document']?:'SEM_DOC').' | ativo '.((int)($c['active']??0)===1?'SIM':'NÃO').' | codInt '.(rawGeneralIntegration($c)?:'SEM_CODINT')."\n";
 }
}

echo "\nNenhum dado foi alterado.\n";
