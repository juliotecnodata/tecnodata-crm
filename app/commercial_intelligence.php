<?php
declare(strict_types=1);

/**
 * Núcleo da Inteligência Comercial Tecnodata.
 *
 * Responsabilidades desta fase:
 * - espelhar usuários e Contas do CRM Omie;
 * - reconciliar Conta CRM x cliente Geral pelo CPF/CNPJ;
 * - mapear usuário Tecnodata x usuário CRM;
 * - tornar nCodVend a autoridade da carteira comercial;
 * - manter classificação CFC/Revendedor com auditoria e outbox.
 */
final class CommercialSchema {
 private static bool $ready=false;

 public static function ensure(): void{
  if(self::$ready)return;

  $clientColumns=[];foreach(DB::all("SHOW COLUMNS FROM clients") as $row)$clientColumns[(string)$row['Field']]=true;
  if(!isset($clientColumns['crm_account_code']))DB::exec("ALTER TABLE clients ADD COLUMN crm_account_code VARCHAR(80) NULL AFTER omie_seller_code");
  if(!isset($clientColumns['crm_owner_omie_code']))DB::exec("ALTER TABLE clients ADD COLUMN crm_owner_omie_code VARCHAR(80) NULL AFTER crm_account_code");
  if(!isset($clientColumns['crm_owner_user_id']))DB::exec("ALTER TABLE clients ADD COLUMN crm_owner_user_id INT UNSIGNED NULL AFTER crm_owner_omie_code");

  $clientIndexes=[];foreach(DB::all("SHOW INDEX FROM clients") as $row)$clientIndexes[(string)$row['Key_name']]=true;
  if(!isset($clientIndexes['idx_clients_crm_owner']))DB::exec("ALTER TABLE clients ADD INDEX idx_clients_crm_owner(crm_owner_user_id,active,crm_inactive)");
  if(!isset($clientIndexes['idx_clients_crm_account']))DB::exec("ALTER TABLE clients ADD INDEX idx_clients_crm_account(crm_account_code)");

  $userColumns=[];foreach(DB::all("SHOW COLUMNS FROM users") as $row)$userColumns[(string)$row['Field']]=true;
  if(!isset($userColumns['crm_user_omie_code']))DB::exec("ALTER TABLE users ADD COLUMN crm_user_omie_code VARCHAR(80) NULL AFTER seller_omie_code");
  $userIndexes=[];foreach(DB::all("SHOW INDEX FROM users") as $row)$userIndexes[(string)$row['Key_name']]=true;
  if(!isset($userIndexes['idx_users_crm_user']))DB::exec("ALTER TABLE users ADD INDEX idx_users_crm_user(crm_user_omie_code,active)");

  DB::exec("CREATE TABLE IF NOT EXISTS crm_users(
   omie_code VARCHAR(80) PRIMARY KEY,
   name VARCHAR(160) NOT NULL,
   email VARCHAR(190) NULL,
   phone VARCHAR(45) NULL,
   mobile VARCHAR(45) NULL,
   annual_goal DECIMAL(15,2) NOT NULL DEFAULT 0,
   active TINYINT(1) NOT NULL DEFAULT 1,
   raw_json JSON NULL,
   updated_at DATETIME NOT NULL,
   INDEX idx_crm_users_email(email),
   INDEX idx_crm_users_active(active,name)
  )");

  DB::exec("CREATE TABLE IF NOT EXISTS crm_accounts(
   omie_code VARCHAR(80) PRIMARY KEY,
   integration_code VARCHAR(100) NULL,
   name VARCHAR(190) NOT NULL,
   trade_name VARCHAR(190) NULL,
   document VARCHAR(30) NULL,
   crm_user_code VARCHAR(80) NULL,
   vertical_code VARCHAR(80) NULL,
   telemarketing_code VARCHAR(80) NULL,
   notes TEXT NULL,
   raw_json JSON NULL,
   updated_at DATETIME NOT NULL,
   INDEX idx_crm_accounts_document(document),
   INDEX idx_crm_accounts_user(crm_user_code),
   INDEX idx_crm_accounts_integration(integration_code)
  )");

  DB::exec("CREATE TABLE IF NOT EXISTS crm_account_links(
   crm_account_code VARCHAR(80) PRIMARY KEY,
   client_id BIGINT UNSIGNED NOT NULL,
   link_method VARCHAR(30) NOT NULL DEFAULT 'document',
   confidence TINYINT UNSIGNED NOT NULL DEFAULT 100,
   is_primary TINYINT(1) NOT NULL DEFAULT 1,
   verified_by_user_id INT UNSIGNED NULL,
   verified_at DATETIME NULL,
   updated_at DATETIME NOT NULL,
   FOREIGN KEY(client_id) REFERENCES clients(id) ON DELETE CASCADE,
   FOREIGN KEY(verified_by_user_id) REFERENCES users(id) ON DELETE SET NULL,
   INDEX idx_crm_links_client(client_id,is_primary)
  )");

  DB::exec("CREATE TABLE IF NOT EXISTS crm_contacts(
   omie_code VARCHAR(80) PRIMARY KEY,
   integration_code VARCHAR(100) NULL,
   crm_account_code VARCHAR(80) NOT NULL,
   crm_user_code VARCHAR(80) NULL,
   name VARCHAR(120) NULL,
   last_name VARCHAR(120) NULL,
   position_name VARCHAR(120) NULL,
   email VARCHAR(200) NULL,
   phone VARCHAR(40) NULL,
   mobile VARCHAR(40) NULL,
   raw_json JSON NULL,
   updated_at DATETIME NOT NULL,
   INDEX idx_crm_contacts_account(crm_account_code),
   INDEX idx_crm_contacts_email(email)
  )");

  DB::exec("CREATE TABLE IF NOT EXISTS user_omie_identity(
   user_id INT UNSIGNED PRIMARY KEY,
   sales_seller_code VARCHAR(80) NULL,
   crm_user_code VARCHAR(80) NULL,
   match_method VARCHAR(30) NOT NULL DEFAULT 'manual',
   confidence TINYINT UNSIGNED NOT NULL DEFAULT 100,
   verified_at DATETIME NULL,
   updated_at DATETIME NOT NULL,
   FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
   INDEX idx_user_identity_sales(sales_seller_code),
   INDEX idx_user_identity_crm(crm_user_code)
  )");

  DB::exec("CREATE TABLE IF NOT EXISTS client_commercial_profiles(
   client_id BIGINT UNSIGNED PRIMARY KEY,
   is_cfc TINYINT(1) NOT NULL DEFAULT 0,
   is_reseller TINYINT(1) NOT NULL DEFAULT 0,
   strategic_notes TEXT NULL,
   classification_source VARCHAR(30) NOT NULL DEFAULT 'local',
   updated_by_user_id INT UNSIGNED NULL,
   updated_at DATETIME NOT NULL,
   FOREIGN KEY(client_id) REFERENCES clients(id) ON DELETE CASCADE,
   FOREIGN KEY(updated_by_user_id) REFERENCES users(id) ON DELETE SET NULL,
   INDEX idx_client_profile_class(is_cfc,is_reseller)
  )");

  DB::exec("CREATE TABLE IF NOT EXISTS client_commercial_audit(
   id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
   client_id BIGINT UNSIGNED NOT NULL,
   actor_user_id INT UNSIGNED NULL,
   field_name VARCHAR(80) NOT NULL,
   previous_value TEXT NULL,
   new_value TEXT NULL,
   source VARCHAR(30) NOT NULL DEFAULT 'tecnodata',
   sync_status ENUM('pending','synced','error','ignored') NOT NULL DEFAULT 'pending',
   synced_at DATETIME NULL,
   sync_error TEXT NULL,
   created_at DATETIME NOT NULL,
   FOREIGN KEY(client_id) REFERENCES clients(id) ON DELETE CASCADE,
   FOREIGN KEY(actor_user_id) REFERENCES users(id) ON DELETE SET NULL,
   INDEX idx_commercial_audit_client(client_id,created_at),
   INDEX idx_commercial_audit_sync(sync_status,created_at)
  )");

  DB::exec("CREATE TABLE IF NOT EXISTS sync_outbox(
   id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
   entity_type VARCHAR(50) NOT NULL,
   entity_id VARCHAR(100) NOT NULL,
   operation VARCHAR(50) NOT NULL,
   payload_json JSON NOT NULL,
   status ENUM('pending','processing','synced','error','ignored') NOT NULL DEFAULT 'pending',
   attempts INT UNSIGNED NOT NULL DEFAULT 0,
   last_error TEXT NULL,
   next_attempt_at DATETIME NULL,
   created_at DATETIME NOT NULL,
   updated_at DATETIME NOT NULL,
   synced_at DATETIME NULL,
   UNIQUE KEY uq_sync_outbox_event(entity_type,entity_id,operation,status),
   INDEX idx_sync_outbox_status(status,next_attempt_at,id)
  )");

  // Aproveita classificações antigas somente como semente. A nova estrutura
  // passa a ser a autoridade local e nunca depende exclusivamente de tags.
  DB::exec("INSERT IGNORE INTO client_commercial_profiles(client_id,is_cfc,is_reseller,classification_source,updated_at)
            SELECT c.id,
             CASE WHEN EXISTS(SELECT 1 FROM client_tags t WHERE t.client_id=c.id AND t.tag_key='cfc') THEN 1 ELSE 0 END,
             CASE WHEN EXISTS(SELECT 1 FROM client_tags t WHERE t.client_id=c.id AND t.tag_key='revendedor') THEN 1 ELSE 0 END,
             'legacy_tags',NOW()
            FROM clients c WHERE c.active=1");

  self::$ready=true;
 }
}

final class CommercialIdentityService {
 private static function normalizeEmail(mixed $value): string{
  return mb_strtolower(trim((string)$value),'UTF-8');
 }

 public static function reconcileUsers(): array{
  CommercialSchema::ensure();
  $crmUsers=DB::all("SELECT omie_code,name,email FROM crm_users WHERE active=1 ORDER BY name");
  $localUsers=DB::all("SELECT id,name,email,seller_omie_code,crm_user_omie_code,role,active FROM users WHERE active=1 AND role IN('seller','supervisor','admin')");

  $crmByEmail=[];$crmByName=[];
  foreach($crmUsers as $crm){
   $email=self::normalizeEmail($crm['email']??'');if($email!=='')$crmByEmail[$email][]=$crm;
   $name=crm_normalize_key((string)($crm['name']??''));if($name!=='')$crmByName[$name][]=$crm;
  }

  $linked=0;$ambiguous=0;
  foreach($localUsers as $user){
   $userId=(int)$user['id'];
   $current=trim((string)($user['crm_user_omie_code']??''));
   if($current!==''&&DB::one("SELECT 1 FROM crm_users WHERE omie_code=? AND active=1",[$current])){
    DB::exec("INSERT INTO user_omie_identity(user_id,sales_seller_code,crm_user_code,match_method,confidence,verified_at,updated_at)
              VALUES(?,?,?,'existing',100,NOW(),NOW())
              ON DUPLICATE KEY UPDATE sales_seller_code=VALUES(sales_seller_code),crm_user_code=VALUES(crm_user_code),updated_at=NOW()",
     [$userId,$user['seller_omie_code']??null,$current]);
    $linked++;continue;
   }

   $matches=[];$method='';$confidence=0;
   $email=self::normalizeEmail($user['email']??'');
   if($email!==''&&count($crmByEmail[$email]??[])===1){$matches=$crmByEmail[$email];$method='email';$confidence=100;}
   if(!$matches){
    $name=crm_normalize_key((string)($user['name']??''));
    if($name!==''&&count($crmByName[$name]??[])===1){$matches=$crmByName[$name];$method='name';$confidence=85;}
   }
   if(count($matches)!==1){$ambiguous++;continue;}
   $crmCode=(string)$matches[0]['omie_code'];
   DB::exec("UPDATE users SET crm_user_omie_code=?,updated_at=NOW() WHERE id=?",[$crmCode,$userId]);
   DB::exec("INSERT INTO user_omie_identity(user_id,sales_seller_code,crm_user_code,match_method,confidence,verified_at,updated_at)
             VALUES(?,?,?,?,?,NOW(),NOW())
             ON DUPLICATE KEY UPDATE sales_seller_code=VALUES(sales_seller_code),crm_user_code=VALUES(crm_user_code),match_method=VALUES(match_method),confidence=VALUES(confidence),verified_at=NOW(),updated_at=NOW()",
    [$userId,$user['seller_omie_code']??null,$crmCode,$method,$confidence]);
   $linked++;
  }
  return ['linked'=>$linked,'ambiguous'=>$ambiguous,'local_users'=>count($localUsers),'crm_users'=>count($crmUsers)];
 }
}

final class CommercialPortfolioService {
 private static ?array $clientDocumentMap=null;

 private static function clientDocumentMap(): array{
  if(self::$clientDocumentMap!==null)return self::$clientDocumentMap;
  $map=[];
  foreach(DB::all("SELECT id,document FROM clients WHERE active=1 AND crm_inactive=0 AND document IS NOT NULL AND TRIM(document)<>''") as $client){
   $document=crm_digits((string)$client['document']);if($document==='')continue;$map[$document][]=(int)$client['id'];
  }
  return self::$clientDocumentMap=$map;
 }
 public static function syncUsersPage(int $page=1): array{
  CommercialSchema::ensure();$page=max(1,$page);$omie=new OmieClient();
  $data=$omie->call('crm_users','ListarUsuarios',[
   'pagina'=>$page,'registros_por_pagina'=>50,'apenas_importado_api'=>'N','apenas_ativos'=>'S'
  ]);
  $items=(array)($data['cadastros']??[]);
  foreach($items as $row){
   if(!is_array($row))continue;$code=trim((string)($row['nCodigo']??''));if($code==='')continue;
   DB::exec("INSERT INTO crm_users(omie_code,name,email,phone,mobile,annual_goal,active,raw_json,updated_at)
             VALUES(?,?,?,?,?,?,1,?,NOW())
             ON DUPLICATE KEY UPDATE name=VALUES(name),email=VALUES(email),phone=VALUES(phone),mobile=VALUES(mobile),annual_goal=VALUES(annual_goal),active=1,raw_json=VALUES(raw_json),updated_at=NOW()",
    [$code,(string)($row['cNome']??$code),$row['cEmail']??null,$row['cTelefone']??null,$row['cCelular']??null,(float)($row['nMetaAnual']??0),json_encode($row,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)]);
  }
  $total=max(1,(int)($data['total_de_paginas']??1));$done=$page>=$total;
  if($done)CommercialIdentityService::reconcileUsers();
  self::saveState('crm_users',$page,$total,count($items),$done);
  return ['module'=>'crm_users','page'=>$page,'total_pages'=>$total,'count'=>count($items),'done'=>$done];
 }

 public static function syncAccountsPage(int $page=1): array{
  CommercialSchema::ensure();$page=max(1,$page);$omie=new OmieClient();
  $data=$omie->call('crm_accounts','ListarContas',[
   'pagina'=>$page,'registros_por_pagina'=>50,'apenas_importado_api'=>'N'
  ]);
  $items=(array)($data['cadastros']??[]);
  $stats=['linked'=>0,'unlinked'=>0,'ambiguous'=>0,'owner_linked'=>0,'profiles_seeded'=>0];

  foreach($items as $row){
   if(!is_array($row))continue;
   $ident=is_array($row['identificacao']??null)?$row['identificacao']:[];
   $code=trim((string)($ident['nCod']??''));if($code==='')continue;
   $document=crm_digits((string)($ident['cDoc']??''));
   $crmUserCode=trim((string)($ident['nCodVend']??''));
   DB::exec("INSERT INTO crm_accounts(omie_code,integration_code,name,trade_name,document,crm_user_code,vertical_code,telemarketing_code,notes,raw_json,updated_at)
             VALUES(?,?,?,?,?,?,?,?,?,?,NOW())
             ON DUPLICATE KEY UPDATE integration_code=VALUES(integration_code),name=VALUES(name),trade_name=VALUES(trade_name),document=VALUES(document),crm_user_code=VALUES(crm_user_code),vertical_code=VALUES(vertical_code),telemarketing_code=VALUES(telemarketing_code),notes=VALUES(notes),raw_json=VALUES(raw_json),updated_at=NOW()",
    [$code,$ident['cCodInt']??null,(string)($ident['cNome']??$ident['cNomeFantasia']??$code),$ident['cNomeFantasia']??null,$document!==''?$document:null,$crmUserCode!==''?$crmUserCode:null,$ident['nCodVert']??null,$ident['nCodTelemkt']??null,$ident['cObs']??null,json_encode($row,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)]);

   $clientId=self::reconcileAccountClient($code,$document,$stats);
   if($clientId>0){
    $ownerUserId=0;
    if($crmUserCode!=='')$ownerUserId=(int)(DB::scalar("SELECT id FROM users WHERE crm_user_omie_code=? AND active=1 ORDER BY FIELD(role,'seller','supervisor','admin') LIMIT 1",[$crmUserCode])??0);
    DB::exec("UPDATE clients SET crm_account_code=?,crm_owner_omie_code=?,crm_owner_user_id=?,updated_at=updated_at WHERE id=?",
     [$code,$crmUserCode!==''?$crmUserCode:null,$ownerUserId?:null,$clientId]);
    if($ownerUserId>0)$stats['owner_linked']++;
    if(self::seedProfileFromAccount($clientId,$row))$stats['profiles_seeded']++;
   }

   self::syncEmbeddedContacts($code,$row);
  }

  $total=max(1,(int)($data['total_de_paginas']??1));$done=$page>=$total;
  self::saveState('crm_accounts',$page,$total,count($items),$done,$stats);
  return ['module'=>'crm_accounts','page'=>$page,'total_pages'=>$total,'count'=>count($items),'done'=>$done,'stats'=>$stats];
 }

 private static function reconcileAccountClient(string $accountCode,string $document,array &$stats): int{
  $existing=DB::one("SELECT client_id,link_method FROM crm_account_links WHERE crm_account_code=? LIMIT 1",[$accountCode]);
  if($existing){
   $clientId=(int)$existing['client_id'];
   if(DB::one("SELECT 1 FROM clients WHERE id=? AND active=1",[$clientId])){$stats['linked']++;return $clientId;}
  }
  if($document===''){$stats['unlinked']++;return 0;}

  $candidates=array_values(array_unique(self::clientDocumentMap()[$document]??[]));
  if(count($candidates)!==1){
   if(count($candidates)>1)$stats['ambiguous']++;else $stats['unlinked']++;
   return 0;
  }
  $clientId=$candidates[0];
  DB::exec("INSERT INTO crm_account_links(crm_account_code,client_id,link_method,confidence,is_primary,verified_at,updated_at)
            VALUES(?,?,'document',100,1,NOW(),NOW())
            ON DUPLICATE KEY UPDATE client_id=VALUES(client_id),link_method='document',confidence=100,verified_at=NOW(),updated_at=NOW()",
   [$accountCode,$clientId]);
  $stats['linked']++;return $clientId;
 }

 private static function seedProfileFromAccount(int $clientId,array $account): bool{
  $current=DB::one("SELECT * FROM client_commercial_profiles WHERE client_id=?",[$clientId]);
  $pending=(int)(DB::scalar("SELECT COUNT(*) FROM client_commercial_audit WHERE client_id=? AND field_name='classification' AND sync_status IN('pending','error')",[$clientId])??0);
  if($pending>0)return false;

  $characteristics=[];
  foreach((array)($account['caracteristicas']??[]) as $item){
   if(!is_array($item))continue;$key=crm_normalize_key((string)($item['campo']??''));$characteristics[$key]=crm_yes((string)($item['conteudo']??''));
  }
  $tags=[];foreach((array)($account['tags']??[]) as $item){$tag=crm_normalize_key((string)(is_array($item)?($item['tag']??''):$item));if($tag!=='')$tags[$tag]=true;}

  $hasExplicit=array_key_exists('td cfc',$characteristics)||array_key_exists('td revendedor',$characteristics);
  if($hasExplicit){
   $isCfc=!empty($characteristics['td cfc']);$isReseller=!empty($characteristics['td revendedor']);$source='omie_crm_characteristics';
  }elseif(isset($tags['cfc'])||isset($tags['revendedor'])){
   $isCfc=isset($tags['cfc']);$isReseller=isset($tags['revendedor']);$source='omie_crm_tags';
  }else return false;

  $oldCfc=(int)($current['is_cfc']??0);$oldRes=(int)($current['is_reseller']??0);
  DB::exec("INSERT INTO client_commercial_profiles(client_id,is_cfc,is_reseller,classification_source,updated_at)
            VALUES(?,?,?,?,NOW())
            ON DUPLICATE KEY UPDATE is_cfc=VALUES(is_cfc),is_reseller=VALUES(is_reseller),classification_source=VALUES(classification_source),updated_at=NOW()",
   [$clientId,$isCfc?1:0,$isReseller?1:0,$source]);
  if($current&&($oldCfc!==($isCfc?1:0)||$oldRes!==($isReseller?1:0))){
   DB::exec("INSERT INTO client_commercial_audit(client_id,field_name,previous_value,new_value,source,sync_status,synced_at,created_at)
             VALUES(?,'classification',?,?,?,'synced',NOW(),NOW())",
    [$clientId,json_encode(['cfc'=>$oldCfc===1,'reseller'=>$oldRes===1]),json_encode(['cfc'=>$isCfc,'reseller'=>$isReseller]),$source]);
  }
  return true;
 }

 private static function syncEmbeddedContacts(string $accountCode,array $account): void{
  foreach((array)($account['contatos']??[]) as $row){
   if(!is_array($row))continue;$code=trim((string)($row['id']??''));if($code==='')continue;
   DB::exec("INSERT INTO crm_contacts(omie_code,integration_code,crm_account_code,crm_user_code,name,last_name,position_name,email,phone,mobile,raw_json,updated_at)
             VALUES(?,?,?,?,?,?,?,?,?,?,?,NOW())
             ON DUPLICATE KEY UPDATE integration_code=VALUES(integration_code),crm_account_code=VALUES(crm_account_code),crm_user_code=VALUES(crm_user_code),name=VALUES(name),last_name=VALUES(last_name),position_name=VALUES(position_name),email=VALUES(email),phone=VALUES(phone),mobile=VALUES(mobile),raw_json=VALUES(raw_json),updated_at=NOW()",
    [$code,$row['cod_int']??null,$accountCode,$row['id_vend']??null,$row['nome']??null,$row['sobrenome']??null,$row['cargo']??null,$row['email']??null,trim((string)($row['ddd_tel']??'').' '.(string)($row['telefone']??'')),trim((string)($row['ddd_cel1']??'').' '.(string)($row['celular1']??'')),json_encode($row,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)]);
  }
 }

 private static function saveState(string $module,int $page,int $total,int $count,bool $done,array $context=[]): void{
  DB::exec("INSERT INTO sync_state(module_key,last_page,total_pages,last_count,context_json,last_success_at,last_error)
            VALUES(?,?,?,?,?,IF(?,NOW(),NULL),NULL)
            ON DUPLICATE KEY UPDATE last_page=VALUES(last_page),total_pages=VALUES(total_pages),last_count=VALUES(last_count),context_json=VALUES(context_json),last_success_at=IF(VALUES(last_success_at) IS NULL,last_success_at,VALUES(last_success_at)),last_error=NULL",
   [$module,$page,$total,$count,$context?json_encode($context,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES):null,$done?1:0]);
 }

 public static function runFullBase(): array{
  CommercialSchema::ensure();$result=[];
  foreach(['crm_users','crm_accounts'] as $module){
   $page=1;$processed=0;
   do{
    $r=$module==='crm_users'?self::syncUsersPage($page):self::syncAccountsPage($page);
    $processed+=(int)($r['count']??0);$page++;
   }while(empty($r['done'])&&$page<=10000);
   $result[$module]=['processed'=>$processed,'last'=>$r];
  }
  return $result;
 }

 public static function crmPortfolioReady(): bool{
  CommercialSchema::ensure();
  $state=DB::one("SELECT last_success_at,last_error FROM sync_state WHERE module_key='crm_accounts' LIMIT 1");
  return $state&&!empty($state['last_success_at'])&&empty($state['last_error'])&&(int)(DB::scalar("SELECT COUNT(*) FROM crm_accounts")??0)>0;
 }

 public static function sellerPortfolioCondition(array $user,string $alias='c',?string $month=null): array{
  if(!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/',$alias))throw new InvalidArgumentException('Alias inválido.');
  if(self::crmPortfolioReady()){
   return [$alias.'.crm_owner_user_id=?',[(int)($user['id']??0)],'omie_crm'];
  }
  $legacyCode=trim((string)($user['seller_omie_code']??''))?:'__NO_SELLER_LINK__';
  return ['('.client_effective_seller_sql($alias,$month).')=?',[$legacyCode],'legacy'];
 }

 public static function canSellerWorkClient(array $user,int $clientId): bool{
  if(($user['role']??'')!=='seller')return true;
  CommercialSchema::ensure();
  if(self::crmPortfolioReady()){
   return (int)(DB::scalar("SELECT COUNT(*) FROM clients WHERE id=? AND active=1 AND crm_inactive=0 AND crm_owner_user_id=?",[$clientId,(int)($user['id']??0)])??0)>0;
  }
  $effective=ClientPortfolioService::effectiveSellerCode($clientId);
  return $effective===''||$effective===trim((string)($user['seller_omie_code']??''));
 }

 public static function health(): array{
  CommercialSchema::ensure();
  return [
   'crm_users'=>(int)(DB::scalar("SELECT COUNT(*) FROM crm_users WHERE active=1")??0),
   'crm_accounts'=>(int)(DB::scalar("SELECT COUNT(*) FROM crm_accounts")??0),
   'linked_accounts'=>(int)(DB::scalar("SELECT COUNT(*) FROM crm_account_links")??0),
   'unlinked_accounts'=>(int)(DB::scalar("SELECT COUNT(*) FROM crm_accounts a LEFT JOIN crm_account_links l ON l.crm_account_code=a.omie_code WHERE l.crm_account_code IS NULL")??0),
   'clients_with_crm_owner'=>(int)(DB::scalar("SELECT COUNT(*) FROM clients WHERE active=1 AND crm_inactive=0 AND crm_owner_user_id IS NOT NULL")??0),
   'clients_without_crm_owner'=>(int)(DB::scalar("SELECT COUNT(*) FROM clients WHERE active=1 AND crm_inactive=0 AND crm_owner_user_id IS NULL")??0),
   'profiles'=>(int)(DB::scalar("SELECT COUNT(*) FROM client_commercial_profiles")??0),
   'cfc'=>(int)(DB::scalar("SELECT COUNT(*) FROM client_commercial_profiles WHERE is_cfc=1")??0),
   'resellers'=>(int)(DB::scalar("SELECT COUNT(*) FROM client_commercial_profiles WHERE is_reseller=1")??0),
   'both'=>(int)(DB::scalar("SELECT COUNT(*) FROM client_commercial_profiles WHERE is_cfc=1 AND is_reseller=1")??0),
   'outbox_pending'=>(int)(DB::scalar("SELECT COUNT(*) FROM sync_outbox WHERE status IN('pending','error')")??0),
  ];
 }
}

final class CommercialClassificationService {
 public static function get(int $clientId): array{
  CommercialSchema::ensure();
  return DB::one("SELECT * FROM client_commercial_profiles WHERE client_id=?",[$clientId])??[
   'client_id'=>$clientId,'is_cfc'=>0,'is_reseller'=>0,'strategic_notes'=>null,'classification_source'=>'local'
  ];
 }

 public static function update(int $clientId,bool $isCfc,bool $isReseller,int $actorUserId,string $notes=''): array{
  CommercialSchema::ensure();
  $client=DB::one("SELECT id,name,crm_account_code FROM clients WHERE id=? AND active=1 AND crm_inactive=0",[$clientId]);
  if(!$client)throw new RuntimeException('Cliente não encontrado.');
  $old=self::get($clientId);
  $previous=['cfc'=>(int)$old['is_cfc']===1,'reseller'=>(int)$old['is_reseller']===1];
  $next=['cfc'=>$isCfc,'reseller'=>$isReseller];
  if($previous===$next&&trim((string)($old['strategic_notes']??''))===trim($notes))return self::get($clientId);

  DB::exec("INSERT INTO client_commercial_profiles(client_id,is_cfc,is_reseller,strategic_notes,classification_source,updated_by_user_id,updated_at)
            VALUES(?,?,?,?, 'tecnodata',?,NOW())
            ON DUPLICATE KEY UPDATE is_cfc=VALUES(is_cfc),is_reseller=VALUES(is_reseller),strategic_notes=VALUES(strategic_notes),classification_source='tecnodata',updated_by_user_id=VALUES(updated_by_user_id),updated_at=NOW()",
   [$clientId,$isCfc?1:0,$isReseller?1:0,$notes!==''?mb_substr($notes,0,10000):null,$actorUserId?:null]);

  if($previous!==$next){
   DB::exec("INSERT INTO client_commercial_audit(client_id,actor_user_id,field_name,previous_value,new_value,source,sync_status,created_at)
             VALUES(?,?,'classification',?,?,'tecnodata','pending',NOW())",
    [$clientId,$actorUserId?:null,json_encode($previous,JSON_UNESCAPED_UNICODE),json_encode($next,JSON_UNESCAPED_UNICODE)]);
   self::enqueueClassification($clientId,$next);
  }
  return self::get($clientId);
 }

 private static function enqueueClassification(int $clientId,array $classification): void{
  $payload=['client_id'=>$clientId,'cfc'=>!empty($classification['cfc']),'reseller'=>!empty($classification['reseller'])];
  // Um único evento pendente por cliente: a fila sempre carrega o estado mais recente.
  $existing=DB::one("SELECT id FROM sync_outbox WHERE entity_type='client_classification' AND entity_id=? AND operation='upsert_omie_crm_characteristics' AND status IN('pending','error') ORDER BY id DESC LIMIT 1",[(string)$clientId]);
  if($existing){
   DB::exec("UPDATE sync_outbox SET payload_json=?,status='pending',attempts=0,last_error=NULL,next_attempt_at=NULL,updated_at=NOW() WHERE id=?",
    [json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),(int)$existing['id']]);
  }else{
   DB::exec("INSERT INTO sync_outbox(entity_type,entity_id,operation,payload_json,status,attempts,created_at,updated_at)
             VALUES('client_classification',?,'upsert_omie_crm_characteristics',?,'pending',0,NOW(),NOW())",
    [(string)$clientId,json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)]);
  }
 }
}

final class CommercialOutboxService {
 public static function process(int $limit=100): array{
  CommercialSchema::ensure();$limit=max(1,min(500,$limit));
  $rows=DB::all("SELECT * FROM sync_outbox WHERE status IN('pending','error') AND (next_attempt_at IS NULL OR next_attempt_at<=NOW()) ORDER BY id LIMIT ".$limit);
  $done=0;$errors=0;
  foreach($rows as $row){
   $id=(int)$row['id'];DB::exec("UPDATE sync_outbox SET status='processing',attempts=attempts+1,updated_at=NOW() WHERE id=?",[$id]);
   try{
    $payload=json_decode((string)$row['payload_json'],true);if(!is_array($payload))throw new RuntimeException('Payload inválido.');
    if($row['operation']==='upsert_omie_crm_characteristics')self::pushClassification($payload);
    else throw new RuntimeException('Operação de outbox ainda não implementada: '.$row['operation']);
    DB::exec("UPDATE sync_outbox SET status='synced',synced_at=NOW(),last_error=NULL,updated_at=NOW() WHERE id=?",[$id]);
    if($row['entity_type']==='client_classification'){
     DB::exec("UPDATE client_commercial_audit SET sync_status='synced',synced_at=NOW(),sync_error=NULL WHERE client_id=? AND field_name='classification' AND sync_status IN('pending','error')",[(int)$row['entity_id']]);
    }
    $done++;
   }catch(Throwable $e){
    $delay=min(1440,max(5,(int)pow(2,min(8,(int)$row['attempts']))*5));
    $nextAttempt=date('Y-m-d H:i:s',time()+($delay*60));
    DB::exec("UPDATE sync_outbox SET status='error',last_error=?,next_attempt_at=?,updated_at=NOW() WHERE id=?",
     [mb_substr($e->getMessage(),0,4000),$nextAttempt,$id]);
    if($row['entity_type']==='client_classification'){
     DB::exec("UPDATE client_commercial_audit SET sync_status='error',sync_error=? WHERE client_id=? AND field_name='classification' AND sync_status='pending'",
      [mb_substr($e->getMessage(),0,4000),(int)$row['entity_id']]);
    }
    $errors++;
   }
  }
  return ['processed'=>count($rows),'synced'=>$done,'errors'=>$errors,'remaining'=>(int)(DB::scalar("SELECT COUNT(*) FROM sync_outbox WHERE status IN('pending','error')")??0)];
 }

 private static function pushClassification(array $payload): void{
  $clientId=(int)($payload['client_id']??0);
  $client=DB::one("SELECT id,crm_account_code FROM clients WHERE id=? AND active=1 AND crm_inactive=0",[$clientId]);
  if(!$client||empty($client['crm_account_code']))throw new RuntimeException('Cliente ainda não possui Conta CRM Omie vinculada.');
  $accountCode=(int)$client['crm_account_code'];if($accountCode<=0)throw new RuntimeException('Código da Conta CRM inválido.');

  $omie=new OmieClient();
  $current=$omie->call('crm_account_characteristics','ConsultarCaractConta',['nCod'=>$accountCode]);
  $map=[];foreach((array)($current['caracteristicas']??[]) as $row)if(is_array($row))$map[crm_normalize_key((string)($row['campo']??''))]=$row;

  foreach(['TD_CFC'=>!empty($payload['cfc']),'TD_REVENDEDOR'=>!empty($payload['reseller'])] as $field=>$flag){
   $key=crm_normalize_key($field);$params=['nCod'=>$accountCode,'campo'=>$field,'conteudo'=>$flag?'SIM':'NAO'];
   if(isset($map[$key]))$omie->call('crm_account_characteristics','AlterarCaractConta',$params);
   else $omie->call('crm_account_characteristics','IncluirCaractConta',$params);
  }
 }
}

function crm_digits(mixed $value): string{return preg_replace('/\D+/','',(string)$value)?:'';}
function crm_normalize_key(string $value): string{
 $value=mb_strtolower(trim($value),'UTF-8');
 $ascii=@iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$value);if(is_string($ascii)&&$ascii!=='')$value=$ascii;
 return trim((string)preg_replace('/[^a-z0-9]+/',' ',$value));
}
function crm_yes(string $value): bool{return in_array(crm_normalize_key($value),['sim','s','yes','y','1','true','ativo'],true);}
