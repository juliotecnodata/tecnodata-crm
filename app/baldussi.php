<?php
declare(strict_types=1);

final class BaldussiService {
 private static function config(): array{
  $config=(array)($GLOBALS['config']['baldussi']??[]);
  $username=trim((string)(getenv('TDCRM_BALDUSSI_USER')?:($config['username']??'')));
  $token=trim((string)(getenv('TDCRM_BALDUSSI_TOKEN')?:($config['token']??'')));
  $baseUrl=rtrim(trim((string)($config['base_url']??'')),'/');
  if(empty($config['enabled']))throw new RuntimeException('A integração Baldussi está desativada.');
  if($baseUrl===''||!str_starts_with($baseUrl,'https://'))throw new RuntimeException('A URL segura da API Baldussi não foi configurada.');
  if($username===''||$token==='')throw new RuntimeException('As credenciais da API Baldussi não foram configuradas.');
  $testExtension=preg_replace('/\D+/','',(string)($config['test_extension']??''))??'';
  $areaCode=preg_replace('/\D+/','',(string)($config['area_code']??'41'))??'41';
  if(strlen($areaCode)!==2)throw new RuntimeException('O DDD local da integração Baldussi é inválido.');
  return ['base_url'=>$baseUrl,'username'=>$username,'token'=>$token,'test_extension'=>$testExtension,'area_code'=>$areaCode,'timeout'=>max(5,min(60,(int)($config['timeout']??20)))];
 }

 public static function publicConfig(): array{
  try{
   $config=self::config();$host=(string)(parse_url($config['base_url'],PHP_URL_HOST)?:'cloud.baldussi.com.br');
   $username=$config['username'];$masked=mb_substr($username,0,min(4,mb_strlen($username))).str_repeat('•',max(4,mb_strlen($username)-4));
   return ['configured'=>true,'host'=>$host,'username_masked'=>$masked,'token_masked'=>'••••••••'.substr($config['token'],-4),'test_extension'=>$config['test_extension'],'area_code'=>$config['area_code']];
  }catch(Throwable $e){return ['configured'=>false,'host'=>'cloud.baldussi.com.br','username_masked'=>'Não configurado','token_masked'=>'Não configurado','error'=>$e->getMessage()];}
 }

 public static function userExtension(array $user): ?string{
  CommercialSchema::ensure();
  $row=DB::one("SELECT baldussi_extension,baldussi_enabled FROM users WHERE id=? AND active=1 LIMIT 1",[(int)($user['id']??0)]);
  $extension=preg_replace('/\D+/','',(string)($row['baldussi_extension']??''))??'';
  if(!empty($row['baldussi_enabled'])&&$extension!=='')return $extension;
  // Mantém o administrador de homologação operacional durante a transição.
  // Os demais perfis sempre exigem um ramal próprio vinculado pelo administrador.
  $firstAdminId=(int)(DB::scalar("SELECT MIN(id) FROM users WHERE active=1 AND role='admin'")??0);
  if(($user['role']??'')==='admin'&&(int)($user['id']??0)===$firstAdminId){
   try{$fallback=self::config()['test_extension'];return $fallback!==''?$fallback:null;}catch(Throwable $e){}
  }
  return null;
 }

 private static function request(array $parameters): array{
  $config=self::config();
  $query=array_merge(['usuario'=>$config['username'],'token'=>$config['token']],$parameters);
  $handle=curl_init($config['base_url'].'?'.http_build_query($query));
  curl_setopt_array($handle,[
   CURLOPT_RETURNTRANSFER=>true,
   CURLOPT_CONNECTTIMEOUT=>min(10,$config['timeout']),
   CURLOPT_TIMEOUT=>$config['timeout'],
   CURLOPT_HTTPHEADER=>['Accept: application/json','Cache-Control: no-cache'],
  ]);
  $body=curl_exec($handle);$curlError=curl_error($handle);$status=(int)curl_getinfo($handle,CURLINFO_RESPONSE_CODE);curl_close($handle);
  if($body===false||$curlError!=='')throw new RuntimeException('Não foi possível conectar ao PABX Baldussi.');
  $data=json_decode((string)$body,true);
  if(!is_array($data))throw new RuntimeException('A API Baldussi retornou uma resposta inválida.');
  unset($data['usuario'],$data['token'],$data['autenticacao']);
  $data['_http_status']=$status;
  return $data;
 }

 private static function apiSucceeded(array $response): bool{
  $status=(int)($response['http_response_code']??$response['_http_status']??0);
  return $status>=200&&$status<300;
 }

 private static function responseMessage(array $response,string $fallback): string{
  $message=trim((string)($response['mensagem']??''));
  return mb_substr($message!==''?$message:$fallback,0,300);
 }

 public static function listExtensions(string $extensionId='0'): array{
  $extensionId=preg_replace('/\D+/','',$extensionId)?:'0';
  $response=self::request(['acao'=>'listar_ramais','ramal_id'=>$extensionId]);
  if(!self::apiSucceeded($response))throw new RuntimeException(self::responseMessage($response,'A API não permitiu consultar os ramais.'));
  $rows=[];
  foreach((array)($response['dados']??[]) as $row){
   if(!is_array($row))continue;
   $rows[]=[
    'id'=>(string)($row['ramal_id']??''),
    'number'=>(string)($row['numero']??''),
    'name'=>(string)($row['nome']??''),
    'group'=>(string)($row['grupo_ramais']??''),
    'area_code'=>(string)($row['codigo_area']??''),
    'external_caller_id'=>(string)($row['caller_id_externo']??''),
    'recording'=>(string)($row['gravar_chamadas']??'0')==='1',
    'blocked'=>(string)($row['bloquear_ramal']??'0')==='1',
   ];
  }
  return ['rows'=>$rows,'total'=>(int)($response['qtd_total_resultados']??count($rows)),'returned'=>(int)($response['qtd_resultados_retornados']??count($rows))];
 }

 public static function dialPlan(string $destination): array{
  $destination=preg_replace('/\D+/','',$destination)??'';
  if(!in_array(strlen($destination),[10,11],true))throw new RuntimeException('Informe DDD + telefone, com 10 ou 11 dígitos.');
  $config=self::config();$destinationArea=substr($destination,0,2);$subscriber=substr($destination,2);
  $localCall=hash_equals($config['area_code'],$destinationArea);
  return ['input'=>$destination,'dial_number'=>$localCall?$subscriber:'0'.$destination,'route_label'=>$localCall?'Chamada local (DDD '.$config['area_code'].')':'Longa distância (0 + DDD)'];
 }

 public static function dial(string $origin,string $destination): array{
  $origin=preg_replace('/\D+/','',$origin)??'';$destination=preg_replace('/\D+/','',$destination)??'';
  if(strlen($origin)<2||strlen($origin)>10)throw new RuntimeException('Informe um ramal de origem válido.');
  $plan=self::dialPlan($destination);
  $response=self::request(['acao'=>'discar_numero','numero_ramal_origem'=>$origin,'numero_destino'=>$plan['dial_number']]);
  $ok=self::apiSucceeded($response);
  return ['ok'=>$ok,'status'=>(int)($response['http_response_code']??$response['_http_status']??0),'message'=>self::responseMessage($response,$ok?'Solicitação de chamada enviada ao PABX.':'O PABX recusou a solicitação de chamada.'),'origin'=>$origin,'destination_masked'=>substr($destination,0,2).str_repeat('•',max(0,strlen($destination)-6)).substr($destination,-4),'route_label'=>$plan['route_label']];
 }
}
