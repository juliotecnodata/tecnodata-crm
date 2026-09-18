<?php
declare(strict_types=1);

final class GoogleAuth {
 private const AUTHORIZATION_ENDPOINT='https://accounts.google.com/o/oauth2/v2/auth';
 private const TOKEN_ENDPOINT='https://oauth2.googleapis.com/token';
 private const JWKS_ENDPOINT='https://www.googleapis.com/oauth2/v3/certs';
 private const ISSUERS=['https://accounts.google.com','accounts.google.com'];

 private static function settings(): array{
  $config=(array)($GLOBALS['config']['google_auth']??[]);
  $clientId=trim((string)(getenv('TDCRM_GOOGLE_CLIENT_ID')?:($config['client_id']??'')));
  $clientSecret=trim((string)(getenv('TDCRM_GOOGLE_CLIENT_SECRET')?:($config['client_secret']??'')));
  $domain=mb_strtolower(trim((string)(getenv('TDCRM_GOOGLE_DOMAIN')?:($config['hosted_domain']??''))));
  $enabledValue=getenv('TDCRM_GOOGLE_ENABLED');
  $enabled=$enabledValue!==false&&$enabledValue!==''?filter_var($enabledValue,FILTER_VALIDATE_BOOL):(bool)($config['enabled']??true);
  return compact('clientId','clientSecret','domain','enabled');
 }

 public static function configured(): bool{
  $settings=self::settings();
  return $settings['enabled']&&$settings['clientId']!==''&&$settings['clientSecret']!==''&&$settings['domain']!=='';
 }

 public static function redirectUri(): string{return APP_URL.'/auth/google/callback';}

 public static function authorizationUrl(): string{
  $settings=self::settings();
  if(!self::configured())throw new RuntimeException('O login Google ainda não foi configurado pelo administrador.');
  $state=bin2hex(random_bytes(32));$nonce=bin2hex(random_bytes(32));
  $_SESSION['google_oauth']=['state_hash'=>hash('sha256',$state),'nonce'=>$nonce,'created_at'=>time()];
  return self::AUTHORIZATION_ENDPOINT.'?'.http_build_query([
   'client_id'=>$settings['clientId'],'redirect_uri'=>self::redirectUri(),'response_type'=>'code',
   'scope'=>'openid email profile','state'=>$state,'nonce'=>$nonce,'hd'=>$settings['domain'],
   'include_granted_scopes'=>'true'
  ],'','&',PHP_QUERY_RFC3986);
 }

 public static function complete(array $query): array{
  $pending=$_SESSION['google_oauth']??null;unset($_SESSION['google_oauth']);
  $state=(string)($query['state']??'');
  if(!is_array($pending)||$state===''||!hash_equals((string)($pending['state_hash']??''),hash('sha256',$state))||time()-(int)($pending['created_at']??0)>600)throw new RuntimeException('A tentativa de login expirou. Tente novamente.');
  if(isset($query['error']))throw new RuntimeException('O acesso pela conta Google foi cancelado ou não autorizado.');
  $code=(string)($query['code']??'');if($code==='')throw new RuntimeException('O Google não retornou o código de autorização.');
  $settings=self::settings();if(!self::configured())throw new RuntimeException('O login Google ainda não foi configurado pelo administrador.');
  $tokens=self::requestJson(self::TOKEN_ENDPOINT,[
   'code'=>$code,'client_id'=>$settings['clientId'],'client_secret'=>$settings['clientSecret'],
   'redirect_uri'=>self::redirectUri(),'grant_type'=>'authorization_code'
  ]);
  $idToken=(string)($tokens['id_token']??'');if($idToken==='')throw new RuntimeException('O Google não retornou uma identidade válida.');
  return self::verifyIdToken($idToken,(string)$pending['nonce'],$settings);
 }

 private static function requestJson(string $url,?array $post=null): array{
  $ch=curl_init($url);$options=[CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>10,CURLOPT_TIMEOUT=>20,CURLOPT_HTTPHEADER=>['Accept: application/json']];
  if(defined('CURLOPT_PROTOCOLS'))$options[CURLOPT_PROTOCOLS]=CURLPROTO_HTTPS;
  if($post!==null){$options[CURLOPT_POST]=true;$options[CURLOPT_HTTPHEADER]=['Accept: application/json','Content-Type: application/x-www-form-urlencoded'];$options[CURLOPT_POSTFIELDS]=http_build_query($post,'','&',PHP_QUERY_RFC3986);}
  curl_setopt_array($ch,$options);$raw=curl_exec($ch);$status=(int)curl_getinfo($ch,CURLINFO_RESPONSE_CODE);$error=curl_error($ch);curl_close($ch);
  if($raw===false||$error!==''||$status<200||$status>=300)throw new RuntimeException('Não foi possível validar a conta Google agora.');
  $decoded=json_decode((string)$raw,true);if(!is_array($decoded))throw new RuntimeException('O Google retornou uma resposta inválida.');
  return $decoded;
 }

 private static function verifyIdToken(string $jwt,string $expectedNonce,array $settings): array{
  $parts=explode('.',$jwt);if(count($parts)!==3)throw new RuntimeException('A identidade retornada pelo Google é inválida.');
  [$encodedHeader,$encodedPayload,$encodedSignature]=$parts;
  $header=json_decode(self::base64UrlDecode($encodedHeader),true);$claims=json_decode(self::base64UrlDecode($encodedPayload),true);
  if(!is_array($header)||!is_array($claims)||($header['alg']??'')!=='RS256'||empty($header['kid']))throw new RuntimeException('A assinatura da conta Google é inválida.');
  $key=self::signingKey((string)$header['kid']);$signature=self::base64UrlDecode($encodedSignature);
  if(openssl_verify($encodedHeader.'.'.$encodedPayload,$signature,$key,OPENSSL_ALGO_SHA256)!==1)throw new RuntimeException('A assinatura da conta Google não pôde ser confirmada.');
  $now=time();$issuer=(string)($claims['iss']??'');$audience=$claims['aud']??null;$audiences=is_array($audience)?array_map('strval',$audience):[(string)$audience];
  if(!in_array($issuer,self::ISSUERS,true)||!in_array($settings['clientId'],$audiences,true))throw new RuntimeException('A identidade Google não pertence a este CRM.');
  if(count($audiences)>1&&(string)($claims['azp']??'')!==$settings['clientId'])throw new RuntimeException('A identidade Google foi emitida para outra aplicação.');
  if((int)($claims['exp']??0)<$now-60||(int)($claims['iat']??0)>$now+60)throw new RuntimeException('A identidade Google expirou. Tente novamente.');
  if(!hash_equals($expectedNonce,(string)($claims['nonce']??'')))throw new RuntimeException('A confirmação do login Google não corresponde a esta sessão.');
  if(mb_strtolower((string)($claims['hd']??''))!==$settings['domain'])throw new RuntimeException('Use a conta Google corporativa da empresa.');
  $verified=$claims['email_verified']??false;if(!($verified===true||$verified==='true'||$verified===1||$verified==='1'))throw new RuntimeException('O e-mail da conta Google não está confirmado.');
  $email=mb_strtolower(trim((string)($claims['email']??'')));if(!filter_var($email,FILTER_VALIDATE_EMAIL)||empty($claims['sub']))throw new RuntimeException('A conta Google não possui um e-mail válido.');
  return ['email'=>$email,'subject'=>(string)$claims['sub'],'name'=>(string)($claims['name']??''),'picture'=>(string)($claims['picture']??'')];
 }

 private static function signingKey(string $kid): string{
  $jwks=self::jwks(false);$key=self::findKey($jwks,$kid);
  if(!$key){$jwks=self::jwks(true);$key=self::findKey($jwks,$kid);}
  if(!$key)throw new RuntimeException('A chave de segurança do Google não foi encontrada.');
  return self::jwkToPem($key);
 }

 private static function jwks(bool $force): array{
  $cacheFile=APP_ROOT.'/storage/cache/google-oidc-jwks.json';
  if(!$force&&is_file($cacheFile)){
   $cached=json_decode((string)file_get_contents($cacheFile),true);
   if(is_array($cached)&&($cached['expires_at']??0)>time()&&is_array($cached['jwks']??null))return $cached['jwks'];
  }
  $jwks=self::requestJson(self::JWKS_ENDPOINT);$dir=dirname($cacheFile);if(!is_dir($dir))@mkdir($dir,0775,true);
  @file_put_contents($cacheFile,json_encode(['expires_at'=>time()+21600,'jwks'=>$jwks],JSON_UNESCAPED_SLASHES),LOCK_EX);
  return $jwks;
 }

 private static function findKey(array $jwks,string $kid): ?array{
  foreach((array)($jwks['keys']??[]) as $key)if(is_array($key)&&hash_equals((string)($key['kid']??''),$kid)&&($key['kty']??'')==='RSA'&&($key['use']??'sig')==='sig')return $key;
  return null;
 }

 private static function jwkToPem(array $jwk): string{
  $modulus=self::base64UrlDecode((string)($jwk['n']??''));$exponent=self::base64UrlDecode((string)($jwk['e']??''));
  if($modulus===''||$exponent==='')throw new RuntimeException('A chave pública do Google é inválida.');
  $rsa=self::asn1Sequence(self::asn1Integer($modulus).self::asn1Integer($exponent));
  $algorithm=hex2bin('300d06092a864886f70d0101010500');$subject=self::asn1Sequence($algorithm.self::asn1BitString($rsa));
  return "-----BEGIN PUBLIC KEY-----\n".chunk_split(base64_encode($subject),64,"\n")."-----END PUBLIC KEY-----\n";
 }

 private static function asn1Integer(string $value): string{$value=ltrim($value,"\x00");if($value===''||(ord($value[0])&0x80))$value="\x00".$value;return "\x02".self::asn1Length(strlen($value)).$value;}
 private static function asn1BitString(string $value): string{$value="\x00".$value;return "\x03".self::asn1Length(strlen($value)).$value;}
 private static function asn1Sequence(string $value): string{return "\x30".self::asn1Length(strlen($value)).$value;}
 private static function asn1Length(int $length): string{if($length<128)return chr($length);$encoded='';while($length>0){$encoded=chr($length&0xff).$encoded;$length>>=8;}return chr(0x80|strlen($encoded)).$encoded;}
 private static function base64UrlDecode(string $value): string{$decoded=base64_decode(strtr($value,'-_','+/').str_repeat('=',(4-strlen($value)%4)%4),true);if($decoded===false)throw new RuntimeException('A identidade Google contém dados inválidos.');return $decoded;}
}
