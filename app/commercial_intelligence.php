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

 private static function ensureLinkAuditTable(): void{
  DB::exec("CREATE TABLE IF NOT EXISTS crm_account_link_audit(
   id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
   crm_account_code VARCHAR(80) NOT NULL,
   previous_client_id BIGINT UNSIGNED NULL,
   client_id BIGINT UNSIGNED NULL,
   action VARCHAR(30) NOT NULL,
   link_method VARCHAR(30) NOT NULL,
   actor_user_id INT UNSIGNED NULL,
   notes VARCHAR(500) NULL,
   created_at DATETIME NOT NULL,
   FOREIGN KEY(previous_client_id) REFERENCES clients(id) ON DELETE SET NULL,
   FOREIGN KEY(client_id) REFERENCES clients(id) ON DELETE SET NULL,
   FOREIGN KEY(actor_user_id) REFERENCES users(id) ON DELETE SET NULL,
   INDEX idx_crm_link_audit_account(crm_account_code,created_at),
   INDEX idx_crm_link_audit_client(client_id,created_at)
  )");
 }

 public static function ensure(): void{
  if(self::$ready)return;
  $schemaVersion=11;$stateRaw=null;
  try{$stateRaw=DB::scalar("SELECT value_json FROM settings WHERE setting_key='commercial_intelligence_schema_version' LIMIT 1");}catch(Throwable $e){}
  $state=$stateRaw?json_decode((string)$stateRaw,true):null;
  if(is_array($state)&&(int)($state['version']??0)>=$schemaVersion){self::ensureLinkAuditTable();self::$ready=true;return;}

  $clientColumns=[];foreach(DB::all("SHOW COLUMNS FROM clients") as $row)$clientColumns[(string)$row['Field']]=true;
  if(!isset($clientColumns['crm_account_code']))DB::exec("ALTER TABLE clients ADD COLUMN crm_account_code VARCHAR(80) NULL AFTER omie_seller_code");
  if(!isset($clientColumns['crm_owner_omie_code']))DB::exec("ALTER TABLE clients ADD COLUMN crm_owner_omie_code VARCHAR(80) NULL AFTER crm_account_code");
  if(!isset($clientColumns['crm_owner_user_id']))DB::exec("ALTER TABLE clients ADD COLUMN crm_owner_user_id INT UNSIGNED NULL AFTER crm_owner_omie_code");

  $clientIndexes=[];foreach(DB::all("SHOW INDEX FROM clients") as $row)$clientIndexes[(string)$row['Key_name']]=true;
  if(!isset($clientIndexes['idx_clients_crm_owner']))DB::exec("ALTER TABLE clients ADD INDEX idx_clients_crm_owner(crm_owner_user_id,active,crm_inactive)");
  if(!isset($clientIndexes['idx_clients_crm_account']))DB::exec("ALTER TABLE clients ADD INDEX idx_clients_crm_account(crm_account_code)");

  // Bancos antigos podem ter client_metrics criado antes de first_purchase_at.
  // Mantemos a tabela compatível com o schema atual sem exigir recriação.
  $metricColumns=[];foreach(DB::all("SHOW COLUMNS FROM client_metrics") as $row)$metricColumns[(string)$row['Field']]=true;
  if(!isset($metricColumns['first_purchase_at']))DB::exec("ALTER TABLE client_metrics ADD COLUMN first_purchase_at DATE NULL AFTER client_id");
  if(!isset($metricColumns['last_purchase_at']))DB::exec("ALTER TABLE client_metrics ADD COLUMN last_purchase_at DATE NULL AFTER first_purchase_at");
  if(!isset($metricColumns['revenue_12m']))DB::exec("ALTER TABLE client_metrics ADD COLUMN revenue_12m DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER last_purchase_at");
  if(!isset($metricColumns['orders_12m']))DB::exec("ALTER TABLE client_metrics ADD COLUMN orders_12m INT NOT NULL DEFAULT 0 AFTER revenue_12m");
  if(!isset($metricColumns['avg_ticket_12m']))DB::exec("ALTER TABLE client_metrics ADD COLUMN avg_ticket_12m DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER orders_12m");
  if(!isset($metricColumns['avg_interval_days']))DB::exec("ALTER TABLE client_metrics ADD COLUMN avg_interval_days DECIMAL(10,2) NULL AFTER avg_ticket_12m");

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
   last_seen_token VARCHAR(64) NULL,
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
   active TINYINT(1) NOT NULL DEFAULT 1,
   raw_json JSON NULL,
   last_seen_token VARCHAR(64) NULL,
   updated_at DATETIME NOT NULL,
   INDEX idx_crm_accounts_document(document),
   INDEX idx_crm_accounts_user(crm_user_code),
   INDEX idx_crm_accounts_integration(integration_code)
  )");

  $crmUserColumns=[];foreach(DB::all("SHOW COLUMNS FROM crm_users") as $row)$crmUserColumns[(string)$row['Field']]=true;
  if(!isset($crmUserColumns['last_seen_token']))DB::exec("ALTER TABLE crm_users ADD COLUMN last_seen_token VARCHAR(64) NULL AFTER raw_json");
  $crmAccountColumns=[];foreach(DB::all("SHOW COLUMNS FROM crm_accounts") as $row)$crmAccountColumns[(string)$row['Field']]=true;
  if(!isset($crmAccountColumns['active']))DB::exec("ALTER TABLE crm_accounts ADD COLUMN active TINYINT(1) NOT NULL DEFAULT 1 AFTER notes");
  if(!isset($crmAccountColumns['last_seen_token']))DB::exec("ALTER TABLE crm_accounts ADD COLUMN last_seen_token VARCHAR(64) NULL AFTER raw_json");

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

  self::ensureLinkAuditTable();

  DB::exec("CREATE TABLE IF NOT EXISTS crm_account_commercial_profiles(
   crm_account_code VARCHAR(80) PRIMARY KEY,
   is_cfc TINYINT(1) NOT NULL DEFAULT 0,
   is_reseller TINYINT(1) NOT NULL DEFAULT 0,
   strategic_notes TEXT NULL,
   classification_source VARCHAR(30) NOT NULL DEFAULT 'local',
   updated_by_user_id INT UNSIGNED NULL,
   updated_at DATETIME NOT NULL,
   FOREIGN KEY(updated_by_user_id) REFERENCES users(id) ON DELETE SET NULL,
   INDEX idx_crm_account_profile_class(is_cfc,is_reseller)
  )");

  DB::exec("CREATE TABLE IF NOT EXISTS crm_account_commercial_audit(
   id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
   crm_account_code VARCHAR(80) NOT NULL,
   client_id BIGINT UNSIGNED NULL,
   actor_user_id INT UNSIGNED NULL,
   field_name VARCHAR(80) NOT NULL,
   previous_value TEXT NULL,
   new_value TEXT NULL,
   source VARCHAR(30) NOT NULL DEFAULT 'tecnodata',
   sync_status ENUM('pending','synced','error','ignored') NOT NULL DEFAULT 'pending',
   synced_at DATETIME NULL,
   sync_error TEXT NULL,
   created_at DATETIME NOT NULL,
   FOREIGN KEY(client_id) REFERENCES clients(id) ON DELETE SET NULL,
   FOREIGN KEY(actor_user_id) REFERENCES users(id) ON DELETE SET NULL,
   INDEX idx_crm_account_audit_account(crm_account_code,created_at),
   INDEX idx_crm_account_audit_sync(sync_status,created_at)
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
   INDEX idx_sync_outbox_entity(entity_type,entity_id,operation,status),
   INDEX idx_sync_outbox_status(status,next_attempt_at,id)
  )");

  // Aproveita classificações antigas somente como semente. A nova estrutura
  // passa a ser a autoridade local e nunca depende exclusivamente de tags.
  $outboxIndexes=[];foreach(DB::all("SHOW INDEX FROM sync_outbox") as $index)$outboxIndexes[(string)($index['Key_name']??'')]=(int)($index['Non_unique']??1);
  if(isset($outboxIndexes['uq_sync_outbox_event']))DB::exec("ALTER TABLE sync_outbox DROP INDEX uq_sync_outbox_event");
  if(!isset($outboxIndexes['idx_sync_outbox_entity']))DB::exec("ALTER TABLE sync_outbox ADD INDEX idx_sync_outbox_entity(entity_type,entity_id,operation,status)");

  DB::exec("INSERT IGNORE INTO client_commercial_profiles(client_id,is_cfc,is_reseller,classification_source,updated_at)
            SELECT c.id,
             CASE WHEN EXISTS(SELECT 1 FROM client_tags t WHERE t.client_id=c.id AND t.tag_key='cfc') THEN 1 ELSE 0 END,
             CASE WHEN EXISTS(SELECT 1 FROM client_tags t WHERE t.client_id=c.id AND t.tag_key='revendedor') THEN 1 ELSE 0 END,
             'legacy_tags',NOW()
            FROM clients c WHERE c.active=1");

  DB::exec("INSERT IGNORE INTO crm_account_commercial_profiles(crm_account_code,is_cfc,is_reseller,strategic_notes,classification_source,updated_at)
            SELECT l.crm_account_code,p.is_cfc,p.is_reseller,p.strategic_notes,
                   CASE WHEN p.classification_source='local' THEN 'linked_client' ELSE p.classification_source END,NOW()
            FROM crm_account_links l
            JOIN client_commercial_profiles p ON p.client_id=l.client_id");

  $activityColumns=[];foreach(DB::all("SHOW COLUMNS FROM activities") as $row)$activityColumns[(string)$row['Field']]=$row;
  if(!isset($activityColumns['crm_account_code']))DB::exec("ALTER TABLE activities ADD COLUMN crm_account_code VARCHAR(80) NULL AFTER client_id");
  if(!isset($activityColumns['activity_type']))DB::exec("ALTER TABLE activities ADD COLUMN activity_type VARCHAR(30) NOT NULL DEFAULT 'contact_completed' AFTER user_id");
  if(!isset($activityColumns['category_code']))DB::exec("ALTER TABLE activities ADD COLUMN category_code VARCHAR(50) NULL AFTER activity_type");
  if(!isset($activityColumns['outcome_code']))DB::exec("ALTER TABLE activities ADD COLUMN outcome_code VARCHAR(40) NULL AFTER result");
  if(isset($activityColumns['client_id'])&&strtoupper((string)($activityColumns['client_id']['Null']??''))!=='YES')DB::exec("ALTER TABLE activities MODIFY COLUMN client_id BIGINT UNSIGNED NULL");
  $activityIndexes=[];foreach(DB::all("SHOW INDEX FROM activities") as $row)$activityIndexes[(string)$row['Key_name']]=true;
  if(!isset($activityIndexes['idx_activities_account_date']))DB::exec("ALTER TABLE activities ADD INDEX idx_activities_account_date(crm_account_code,created_at,id)");
  if(!isset($activityIndexes['idx_activities_account_type']))DB::exec("ALTER TABLE activities ADD INDEX idx_activities_account_type(crm_account_code,activity_type,created_at)");

  $taskColumns=[];foreach(DB::all("SHOW COLUMNS FROM tasks") as $row)$taskColumns[(string)$row['Field']]=$row;
  if(!isset($taskColumns['crm_account_code']))DB::exec("ALTER TABLE tasks ADD COLUMN crm_account_code VARCHAR(80) NULL AFTER client_id");
  if(!isset($taskColumns['source_activity_id']))DB::exec("ALTER TABLE tasks ADD COLUMN source_activity_id BIGINT UNSIGNED NULL AFTER task_type_code");
  if(isset($taskColumns['client_id'])&&strtoupper((string)($taskColumns['client_id']['Null']??''))!=='YES')DB::exec("ALTER TABLE tasks MODIFY COLUMN client_id BIGINT UNSIGNED NULL");
  $taskIndexes=[];foreach(DB::all("SHOW INDEX FROM tasks") as $row)$taskIndexes[(string)$row['Key_name']]=true;
  if(!isset($taskIndexes['idx_tasks_account_status_due']))DB::exec("ALTER TABLE tasks ADD INDEX idx_tasks_account_status_due(crm_account_code,status,due_at,id)");
  if(!isset($taskIndexes['idx_tasks_source_activity']))DB::exec("ALTER TABLE tasks ADD INDEX idx_tasks_source_activity(source_activity_id)");

  DB::exec("CREATE TABLE IF NOT EXISTS crm_account_notes(
   id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
   crm_account_code VARCHAR(80) NOT NULL,
   client_id BIGINT UNSIGNED NULL,
   user_id INT UNSIGNED NOT NULL,
   note TEXT NOT NULL,
   created_at DATETIME NOT NULL,
   updated_at DATETIME NULL,
   FOREIGN KEY(client_id) REFERENCES clients(id) ON DELETE SET NULL,
   FOREIGN KEY(user_id) REFERENCES users(id),
   INDEX idx_account_notes_account_date(crm_account_code,created_at,id)
  )");

  DB::exec("CREATE TABLE IF NOT EXISTS commercial_sales(
   id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
   activity_id BIGINT UNSIGNED NOT NULL,
   client_id BIGINT UNSIGNED NULL,
   crm_account_code VARCHAR(80) NOT NULL,
   user_id INT UNSIGNED NOT NULL,
   sold_at DATETIME NOT NULL,
   sale_type VARCHAR(30) NOT NULL,
   amount DECIMAL(15,2) NOT NULL DEFAULT 0,
   discount DECIMAL(15,2) NOT NULL DEFAULT 0,
   commercial_condition VARCHAR(40) NOT NULL,
   negotiation_details TEXT NULL,
   future_promise TINYINT(1) NOT NULL DEFAULT 0,
   created_at DATETIME NOT NULL,
   UNIQUE KEY uq_commercial_sales_activity(activity_id),
   INDEX idx_commercial_sales_date_user(sold_at,user_id),
   INDEX idx_commercial_sales_account(crm_account_code,sold_at),
   FOREIGN KEY(activity_id) REFERENCES activities(id) ON DELETE CASCADE,
   FOREIGN KEY(client_id) REFERENCES clients(id) ON DELETE SET NULL,
   FOREIGN KEY(user_id) REFERENCES users(id)
  )");

  DB::exec("CREATE TABLE IF NOT EXISTS commercial_partner_work(
   id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
   activity_id BIGINT UNSIGNED NOT NULL,
   client_id BIGINT UNSIGNED NULL,
   crm_account_code VARCHAR(80) NOT NULL,
   user_id INT UNSIGNED NOT NULL,
   description TEXT NOT NULL,
   next_step VARCHAR(500) NULL,
   created_at DATETIME NOT NULL,
   UNIQUE KEY uq_partner_work_activity(activity_id),
   INDEX idx_partner_work_account_date(crm_account_code,created_at),
   INDEX idx_partner_work_user_date(user_id,created_at),
   FOREIGN KEY(activity_id) REFERENCES activities(id) ON DELETE CASCADE,
   FOREIGN KEY(client_id) REFERENCES clients(id) ON DELETE SET NULL,
   FOREIGN KEY(user_id) REFERENCES users(id)
  )");

  DB::exec("CREATE TABLE IF NOT EXISTS commercial_partner_work_items(
   id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
   work_id BIGINT UNSIGNED NOT NULL,
   category_code VARCHAR(40) NOT NULL,
   subtype_code VARCHAR(80) NOT NULL,
   INDEX idx_partner_work_items_work(work_id),
   INDEX idx_partner_work_items_category(category_code,subtype_code),
   FOREIGN KEY(work_id) REFERENCES commercial_partner_work(id) ON DELETE CASCADE
  )");

  // Preserva o histórico existente e conecta as identidades CRM/Vendas sem recriar registros.
  // Só associa registros legados quando o Cliente Geral possui uma única Conta CRM ativa.
  DB::exec("UPDATE activities a
            JOIN (SELECT l.client_id,MIN(l.crm_account_code) crm_account_code
                  FROM crm_account_links l
                  JOIN crm_accounts ca ON ca.omie_code=l.crm_account_code AND ca.active=1
                  GROUP BY l.client_id HAVING COUNT(*)=1) l ON l.client_id=a.client_id
            SET a.crm_account_code=l.crm_account_code
            WHERE a.crm_account_code IS NULL");
  DB::exec("UPDATE tasks t
            JOIN (SELECT l.client_id,MIN(l.crm_account_code) crm_account_code
                  FROM crm_account_links l
                  JOIN crm_accounts ca ON ca.omie_code=l.crm_account_code AND ca.active=1
                  GROUP BY l.client_id HAVING COUNT(*)=1) l ON l.client_id=t.client_id
            SET t.crm_account_code=l.crm_account_code
            WHERE t.crm_account_code IS NULL AND t.type='sales'");

  // Se o histórico já conhece a Conta CRM, completa o vínculo com Cliente Geral quando existir.
  DB::exec("UPDATE activities a
            JOIN crm_account_links l ON l.crm_account_code=a.crm_account_code
            JOIN crm_accounts ca ON ca.omie_code=l.crm_account_code AND ca.active=1
            SET a.client_id=l.client_id
            WHERE a.crm_account_code IS NOT NULL AND a.client_id IS NULL");
  DB::exec("UPDATE tasks t
            JOIN crm_account_links l ON l.crm_account_code=t.crm_account_code
            JOIN crm_accounts ca ON ca.omie_code=l.crm_account_code AND ca.active=1
            SET t.client_id=l.client_id
            WHERE t.crm_account_code IS NOT NULL AND t.client_id IS NULL");

  if(!DB::scalar("SELECT 1 FROM settings WHERE setting_key='commercial_active_crm_sellers' LIMIT 1")){
   DB::exec("INSERT INTO settings(setting_key,value_json,updated_at) VALUES('commercial_active_crm_sellers',?,NOW())",
    [json_encode([
      'crm_user_codes'=>['2403587771','712952964'],
      'sellers'=>[['code'=>'2403587771','name'=>'Pamela'],['code'=>'712952964','name'=>'Jessica Ribeiro']],
      'changed_at'=>date('c'),'changed_by'=>null,'notes'=>'Equipe comercial ativa informada em 24/09/2026'
    ],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)]);
  }

  DB::exec("INSERT INTO settings(setting_key,value_json,updated_at) VALUES('commercial_intelligence_schema_version',?,NOW())
            ON DUPLICATE KEY UPDATE value_json=VALUES(value_json),updated_at=NOW()",
   [json_encode(['version'=>$schemaVersion],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)]);

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

 public static function activeCrmSellerCodes(): array{
  CommercialSchema::ensure();
  $raw=DB::scalar("SELECT value_json FROM settings WHERE setting_key='commercial_active_crm_sellers' LIMIT 1");
  $data=$raw?json_decode((string)$raw,true):null;
  $codes=is_array($data)?(array)($data['crm_user_codes']??[]):[];
  return array_values(array_unique(array_filter(array_map(static fn($v)=>trim((string)$v),$codes),static fn($v)=>$v!=='')));
 }

 public static function setActiveCrmSellerCodes(array $codes,int $actorUserId=0,string $notes=''): array{
  CommercialSchema::ensure();
  $codes=array_values(array_unique(array_filter(array_map(static fn($v)=>trim((string)$v),$codes),static fn($v)=>$v!=='')));
  if(!$codes)throw new RuntimeException('Informe pelo menos um vendedor CRM ativo.');
  $valid=[];
  foreach($codes as $code){
   $u=DB::one("SELECT omie_code,name,email,active FROM crm_users WHERE omie_code=? LIMIT 1",[$code]);
   if(!$u)throw new RuntimeException('Usuário CRM não encontrado: '.$code);
   if((int)$u['active']!==1)throw new RuntimeException('Usuário CRM está inativo no cache: '.$code.' · '.$u['name']);
   $valid[]=['code'=>$code,'name'=>(string)$u['name'],'email'=>(string)($u['email']??'')];
  }
  $payload=['crm_user_codes'=>$codes,'sellers'=>$valid,'changed_at'=>date('c'),'changed_by'=>$actorUserId?:null,'notes'=>trim($notes)];
  DB::exec("INSERT INTO settings(setting_key,value_json,updated_at) VALUES('commercial_active_crm_sellers',?,NOW())
            ON DUPLICATE KEY UPDATE value_json=VALUES(value_json),updated_at=NOW()",
   [json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)]);
  self::rebuildOperationalOwners();
  return $payload;
 }

 public static function rebuildOperationalOwners(): array{
  CommercialSchema::ensure();
  $codes=self::activeCrmSellerCodes();
  DB::exec("UPDATE clients SET crm_owner_user_id=NULL WHERE crm_account_code IS NOT NULL");
  if(!$codes)return ['active_sellers'=>0,'mapped_clients'=>0,'stale_owner_clients'=>(int)(DB::scalar("SELECT COUNT(*) FROM clients WHERE active=1 AND crm_inactive=0 AND crm_owner_omie_code IS NOT NULL AND crm_owner_user_id IS NULL")??0)];

  $ph=implode(',',array_fill(0,count($codes),'?'));
  DB::exec("UPDATE clients c
            JOIN (
             SELECT l.client_id,MIN(l.crm_account_code) crm_account_code
             FROM crm_account_links l
             GROUP BY l.client_id
             HAVING COUNT(*)=1
            ) x ON x.client_id=c.id
            JOIN crm_accounts a ON a.omie_code=x.crm_account_code AND a.active=1
            JOIN users u ON u.crm_user_omie_code=a.crm_user_code AND u.active=1 AND u.role='seller'
            SET c.crm_owner_user_id=u.id,c.crm_owner_omie_code=a.crm_user_code,c.crm_account_code=a.omie_code
            WHERE a.crm_user_code IN (".$ph.")", $codes);

  return [
   'active_sellers'=>count($codes),
   'mapped_clients'=>(int)(DB::scalar("SELECT COUNT(*) FROM clients WHERE active=1 AND crm_inactive=0 AND crm_owner_user_id IS NOT NULL")??0),
   'stale_owner_clients'=>(int)(DB::scalar("SELECT COUNT(*) FROM clients WHERE active=1 AND crm_inactive=0 AND crm_owner_omie_code IS NOT NULL AND crm_owner_user_id IS NULL")??0),
  ];
 }

 private static function snapshotToken(string $module,int $page): string{
  if($page===1)return bin2hex(random_bytes(16));
  $state=DB::one("SELECT context_json FROM sync_state WHERE module_key=? LIMIT 1",[$module]);
  $ctx=$state&&!empty($state['context_json'])?json_decode((string)$state['context_json'],true):null;
  $token=is_array($ctx)?trim((string)($ctx['sync_token']??'')):'';
  if($token==='')throw new RuntimeException('Snapshot de '.$module.' não possui token de retomada. Reinicie a sincronização pela página 1.');
  return $token;
 }

 private static function clientDocumentMap(): array{
  if(self::$clientDocumentMap!==null)return self::$clientDocumentMap;
  $map=['active'=>[],'operational'=>[]];
  foreach(DB::all("SELECT id,document,crm_inactive FROM clients WHERE active=1 AND document IS NOT NULL AND TRIM(document)<>''") as $client){
   $document=crm_digits((string)$client['document']);if($document==='')continue;
   $map['active'][$document][]=(int)$client['id'];
   if(empty($client['crm_inactive']))$map['operational'][$document][]=(int)$client['id'];
  }
  return self::$clientDocumentMap=$map;
 }
 public static function syncUsersPage(int $page=1): array{
  CommercialSchema::ensure();$page=max(1,$page);$omie=new OmieClient();$syncToken=self::snapshotToken('crm_users',$page);
  $data=$omie->call('crm_users','ListarUsuarios',[
   'pagina'=>$page,'registros_por_pagina'=>50,'apenas_importado_api'=>'N','apenas_ativos'=>'S'
  ]);
  $items=(array)($data['cadastros']??[]);
  foreach($items as $row){
   if(!is_array($row))continue;$code=trim((string)($row['nCodigo']??''));if($code==='')continue;
   DB::exec("INSERT INTO crm_users(omie_code,name,email,phone,mobile,annual_goal,active,raw_json,last_seen_token,updated_at)
             VALUES(?,?,?,?,?,?,1,?,?,NOW())
             ON DUPLICATE KEY UPDATE name=VALUES(name),email=VALUES(email),phone=VALUES(phone),mobile=VALUES(mobile),annual_goal=VALUES(annual_goal),active=1,raw_json=VALUES(raw_json),last_seen_token=VALUES(last_seen_token),updated_at=NOW()",
    [$code,(string)($row['cNome']??$code),$row['cEmail']??null,$row['cTelefone']??null,$row['cCelular']??null,(float)($row['nMetaAnual']??0),json_encode($row,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),$syncToken]);
  }
  $total=max(1,(int)($data['total_de_paginas']??1));$done=$page>=$total;
  if($done){
   DB::exec("UPDATE crm_users SET active=0 WHERE last_seen_token IS NULL OR last_seen_token<>?",[$syncToken]);
   DB::exec("UPDATE users u LEFT JOIN crm_users cu ON cu.omie_code=u.crm_user_omie_code AND cu.active=1 SET u.crm_user_omie_code=NULL WHERE u.crm_user_omie_code IS NOT NULL AND cu.omie_code IS NULL");
   CommercialIdentityService::reconcileUsers();
  }
  self::saveState('crm_users',$page,$total,count($items),$done,['sync_token'=>$syncToken]);
  return ['module'=>'crm_users','page'=>$page,'total_pages'=>$total,'count'=>count($items),'done'=>$done];
 }

 public static function syncAccountsPage(int $page=1): array{
  CommercialSchema::ensure();$page=max(1,$page);$omie=new OmieClient();$syncToken=self::snapshotToken('crm_accounts',$page);
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
   DB::exec("INSERT INTO crm_accounts(omie_code,integration_code,name,trade_name,document,crm_user_code,vertical_code,telemarketing_code,notes,active,raw_json,last_seen_token,updated_at)
             VALUES(?,?,?,?,?,?,?,?,?,1,?,?,NOW())
             ON DUPLICATE KEY UPDATE integration_code=VALUES(integration_code),name=VALUES(name),trade_name=VALUES(trade_name),document=VALUES(document),crm_user_code=VALUES(crm_user_code),vertical_code=VALUES(vertical_code),telemarketing_code=VALUES(telemarketing_code),notes=VALUES(notes),active=1,raw_json=VALUES(raw_json),last_seen_token=VALUES(last_seen_token),updated_at=NOW()",
    [$code,$ident['cCodInt']??null,(string)($ident['cNome']??$ident['cNomeFantasia']??$code),$ident['cNomeFantasia']??null,$document!==''?$document:null,$crmUserCode!==''?$crmUserCode:null,$ident['nCodVert']??null,$ident['nCodTelemkt']??null,$ident['cObs']??null,json_encode($row,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),$syncToken]);

   self::seedAccountProfileFromAccount($code,$row);
   $clientId=self::reconcileAccountClient($code,$document,$stats);
   if($clientId>0){
    $ownerUserId=0;$activeSellerCodes=self::activeCrmSellerCodes();
    if($crmUserCode!==''&&in_array($crmUserCode,$activeSellerCodes,true)){
     $ownerUserId=(int)(DB::scalar("SELECT u.id FROM users u JOIN crm_users cu ON cu.omie_code=u.crm_user_omie_code AND cu.active=1 WHERE u.crm_user_omie_code=? AND u.active=1 AND u.role='seller' LIMIT 1",[$crmUserCode])??0);
    }
    $before=DB::one("SELECT crm_account_code,crm_owner_omie_code,crm_owner_user_id FROM clients WHERE id=? LIMIT 1",[$clientId])??[];
    $previousOwner=trim((string)($before['crm_owner_omie_code']??''));$nextOwner=$crmUserCode;
    DB::exec("UPDATE clients SET crm_account_code=?,crm_owner_omie_code=?,crm_owner_user_id=?,updated_at=updated_at WHERE id=?",
     [$code,$crmUserCode!==''?$crmUserCode:null,$ownerUserId?:null,$clientId]);
    if($previousOwner!==$nextOwner){
     $previousName=$previousOwner!==''?(DB::scalar("SELECT name FROM crm_users WHERE omie_code=?",[$previousOwner])?:$previousOwner):'Sem responsável';
     $nextName=$nextOwner!==''?(DB::scalar("SELECT name FROM crm_users WHERE omie_code=?",[$nextOwner])?:$nextOwner):'Sem responsável';
     DB::exec("INSERT INTO client_commercial_audit(client_id,field_name,previous_value,new_value,source,sync_status,synced_at,created_at)
               VALUES(?,'responsible',?,?,'omie_crm','synced',NOW(),NOW())",
      [$clientId,json_encode(['code'=>$previousOwner?:null,'name'=>$previousName],JSON_UNESCAPED_UNICODE),json_encode(['code'=>$nextOwner?:null,'name'=>$nextName],JSON_UNESCAPED_UNICODE)]);
    }
    if($ownerUserId>0)$stats['owner_linked']++;
    if(self::mirrorAccountProfileToClient($code,$clientId))$stats['profiles_seeded']++;
   }

   self::syncEmbeddedContacts($code,$row);
  }

  $total=max(1,(int)($data['total_de_paginas']??1));$done=$page>=$total;
  if($done){
   DB::exec("UPDATE crm_accounts SET active=0 WHERE last_seen_token IS NULL OR last_seen_token<>?",[$syncToken]);
   DB::exec("UPDATE clients c JOIN crm_account_links l ON l.client_id=c.id JOIN crm_accounts a ON a.omie_code=l.crm_account_code AND a.active=0 SET c.crm_owner_omie_code=NULL,c.crm_owner_user_id=NULL WHERE c.active=1");
   self::rebuildOperationalOwners();
   $history=CommercialAccountService::reconcileLinkedHistory();
   $stats['history_activities_linked']=(int)($history['activities_account_backfilled']??0);
   $stats['history_tasks_linked']=(int)($history['tasks_account_backfilled']??0);
  }
  $stats['sync_token']=$syncToken;
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

  $maps=self::clientDocumentMap();
  $operational=array_values(array_unique($maps['operational'][$document]??[]));
  $active=array_values(array_unique($maps['active'][$document]??[]));
  if(count($operational)===1)$clientId=$operational[0];
  elseif(count($operational)===0&&count($active)===1)$clientId=$active[0];
  else{
   $count=max(count($operational),count($active));
   if($count>1)$stats['ambiguous']++;else $stats['unlinked']++;
   return 0;
  }
  DB::exec("INSERT INTO crm_account_links(crm_account_code,client_id,link_method,confidence,is_primary,verified_at,updated_at)
            VALUES(?,?,'document',100,1,NOW(),NOW())
            ON DUPLICATE KEY UPDATE client_id=VALUES(client_id),link_method='document',confidence=100,verified_at=NOW(),updated_at=NOW()",
   [$accountCode,$clientId]);
  $stats['linked']++;return $clientId;
 }

 private static function seedAccountProfileFromAccount(string $accountCode,array $account): bool{
  $current=DB::one("SELECT * FROM crm_account_commercial_profiles WHERE crm_account_code=?",[$accountCode]);
  $pending=(int)(DB::scalar("SELECT COUNT(*) FROM crm_account_commercial_audit WHERE crm_account_code=? AND field_name='classification' AND sync_status IN('pending','error')",[$accountCode])??0);
  if($pending>0)return false;
  $characteristics=[];
  foreach((array)($account['caracteristicas']??[]) as $item){
   if(!is_array($item))continue;$key=crm_normalize_key((string)($item['campo']??''));$characteristics[$key]=crm_yes((string)($item['conteudo']??''));
  }
  $tags=[];foreach((array)($account['tags']??[]) as $item){$tag=crm_normalize_key((string)(is_array($item)?($item['tag']??''):$item));if($tag!=='')$tags[$tag]=true;}
  $hasExplicit=array_key_exists('td cfc',$characteristics)||array_key_exists('td revendedor',$characteristics);
  if($hasExplicit){$isCfc=!empty($characteristics['td cfc']);$isReseller=!empty($characteristics['td revendedor']);$source='omie_crm_characteristics';}
  elseif(isset($tags['cfc'])||isset($tags['revendedor'])){$isCfc=isset($tags['cfc']);$isReseller=isset($tags['revendedor']);$source='omie_crm_tags';}
  else return false;

  $oldCfc=(int)($current['is_cfc']??0);$oldRes=(int)($current['is_reseller']??0);
  DB::exec("INSERT INTO crm_account_commercial_profiles(crm_account_code,is_cfc,is_reseller,classification_source,updated_at)
            VALUES(?,?,?,?,NOW())
            ON DUPLICATE KEY UPDATE is_cfc=VALUES(is_cfc),is_reseller=VALUES(is_reseller),classification_source=VALUES(classification_source),updated_at=NOW()",
   [$accountCode,$isCfc?1:0,$isReseller?1:0,$source]);
  if($current&&($oldCfc!==($isCfc?1:0)||$oldRes!==($isReseller?1:0))){
   DB::exec("INSERT INTO crm_account_commercial_audit(crm_account_code,field_name,previous_value,new_value,source,sync_status,synced_at,created_at)
             VALUES(?,'classification',?,?,?,'synced',NOW(),NOW())",
    [$accountCode,json_encode(['cfc'=>$oldCfc===1,'reseller'=>$oldRes===1]),json_encode(['cfc'=>$isCfc,'reseller'=>$isReseller]),$source]);
  }
  return true;
 }

 private static function mirrorAccountProfileToClient(string $accountCode,int $clientId): bool{
  $profile=DB::one("SELECT * FROM crm_account_commercial_profiles WHERE crm_account_code=?",[$accountCode]);
  if(!$profile)return false;
  $pending=(int)(DB::scalar("SELECT COUNT(*) FROM client_commercial_audit WHERE client_id=? AND field_name='classification' AND sync_status IN('pending','error')",[$clientId])??0);
  if($pending>0)return false;
  DB::exec("INSERT INTO client_commercial_profiles(client_id,is_cfc,is_reseller,strategic_notes,classification_source,updated_at)
            VALUES(?,?,?,?,?,NOW())
            ON DUPLICATE KEY UPDATE is_cfc=VALUES(is_cfc),is_reseller=VALUES(is_reseller),strategic_notes=COALESCE(client_commercial_profiles.strategic_notes,VALUES(strategic_notes)),classification_source=VALUES(classification_source),updated_at=NOW()",
   [$clientId,(int)$profile['is_cfc'],(int)$profile['is_reseller'],$profile['strategic_notes']??null,(string)$profile['classification_source']]);
  return true;
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
  // Contatos são cache da Conta CRM. Recria o conjunto da conta para não manter
  // pessoas removidas no Omie como contatos ativos localmente.
  DB::exec("DELETE FROM crm_contacts WHERE crm_account_code=?",[$accountCode]);
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

 public static function reconcileCachedLinks(?callable $progress=null,int $batchSize=500): array{
  CommercialSchema::ensure();self::$clientDocumentMap=null;$batchSize=max(50,min(1000,$batchSize));
  $stats=['linked'=>0,'unlinked'=>0,'ambiguous'=>0,'new_links'=>0,'profiles_mirrored'=>0];
  $accounts=DB::all("SELECT a.omie_code,a.document
                     FROM crm_accounts a
                     LEFT JOIN crm_account_links l ON l.crm_account_code=a.omie_code
                     WHERE a.active=1 AND l.crm_account_code IS NULL
                     ORDER BY a.omie_code");
  $total=count($accounts);$maps=self::clientDocumentMap();$values=[];$params=[];$processed=0;

  $flush=function()use(&$values,&$params,&$stats){
   if(!$values)return;
   DB::exec("INSERT IGNORE INTO crm_account_links(crm_account_code,client_id,link_method,confidence,is_primary,verified_at,updated_at)
             VALUES ".implode(',',$values),$params);
   $stats['new_links']+=count($values);$values=[];$params=[];
  };

  foreach($accounts as $account){
   $processed++;$document=crm_digits((string)($account['document']??''));
   if($document===''){$stats['unlinked']++;}
   else{
    $operational=array_values(array_unique($maps['operational'][$document]??[]));
    $active=array_values(array_unique($maps['active'][$document]??[]));$clientId=0;
    if(count($operational)===1)$clientId=$operational[0];
    elseif(count($operational)===0&&count($active)===1)$clientId=$active[0];
    else{if(max(count($operational),count($active))>1)$stats['ambiguous']++;else $stats['unlinked']++;}
    if($clientId>0){$stats['linked']++;$values[]='(?,?,\'document\',100,1,NOW(),NOW())';array_push($params,(string)$account['omie_code'],$clientId);if(count($values)>=$batchSize)$flush();}
   }
   if($progress&&($processed%500===0||$processed===$total))$progress('links',$processed,$total);
  }
  $flush();

  // Só denormaliza para clients quando há uma única Conta CRM ligada ao cliente.
  DB::exec("UPDATE clients c
            JOIN (
             SELECT l.client_id,MIN(l.crm_account_code) crm_account_code
             FROM crm_account_links l
             GROUP BY l.client_id
             HAVING COUNT(*)=1
            ) x ON x.client_id=c.id
            JOIN crm_accounts a ON a.omie_code=x.crm_account_code AND a.active=1
            LEFT JOIN users u ON u.crm_user_omie_code=a.crm_user_code AND u.active=1 AND u.role='seller'
            SET c.crm_account_code=a.omie_code,
                c.crm_owner_omie_code=a.crm_user_code,
                c.crm_owner_user_id=CASE
                 WHEN a.crm_user_code IN (".implode(',',array_fill(0,max(1,count(self::activeCrmSellerCodes())),'?')).") THEN u.id
                 ELSE NULL END",
   self::activeCrmSellerCodes()?:['__NONE__']);

  self::rebuildOperationalOwners();
  $history=CommercialAccountService::reconcileLinkedHistory();
  $stats['history_activities_linked']=(int)($history['activities_account_backfilled']??0);
  $stats['history_tasks_linked']=(int)($history['tasks_account_backfilled']??0);
  $stats['history_client_refs_linked']=(int)($history['activities_client_backfilled']??0)+(int)($history['tasks_client_backfilled']??0);
  $stats['ambiguous_history_preserved']=(int)($history['ambiguous_clients_preserved']??0);
  $stats['profiles_mirrored']=(int)(DB::scalar("SELECT COUNT(*) FROM crm_account_links l JOIN crm_account_commercial_profiles ap ON ap.crm_account_code=l.crm_account_code")??0);
  $stats['remaining_unlinked']=(int)(DB::scalar("SELECT COUNT(*) FROM crm_accounts a LEFT JOIN crm_account_links l ON l.crm_account_code=a.omie_code WHERE a.active=1 AND l.crm_account_code IS NULL")??0);
  return $stats;
 }

 public static function finalizeCachedPortfolio(): array{
  CommercialSchema::ensure();
  $activeCodes=self::activeCrmSellerCodes();
  $placeholders=implode(',',array_fill(0,max(1,count($activeCodes)),'?'));
  DB::exec("UPDATE clients c
            LEFT JOIN (
             SELECT l.client_id,MIN(l.crm_account_code) crm_account_code
             FROM crm_account_links l
             GROUP BY l.client_id
             HAVING COUNT(*)=1
            ) x ON x.client_id=c.id
            LEFT JOIN crm_accounts a ON a.omie_code=x.crm_account_code AND a.active=1
            LEFT JOIN users u ON u.crm_user_omie_code=a.crm_user_code AND u.active=1 AND u.role='seller'
            SET c.crm_account_code=CASE WHEN x.crm_account_code IS NOT NULL THEN a.omie_code ELSE c.crm_account_code END,
                c.crm_owner_omie_code=CASE WHEN x.crm_account_code IS NOT NULL THEN a.crm_user_code ELSE c.crm_owner_omie_code END,
                c.crm_owner_user_id=CASE
                 WHEN x.crm_account_code IS NOT NULL AND a.crm_user_code IN (".$placeholders.") THEN u.id
                 WHEN x.crm_account_code IS NOT NULL THEN NULL
                 ELSE c.crm_owner_user_id END
            WHERE c.active=1",
   $activeCodes?:['__NONE__']);
  $owners=self::rebuildOperationalOwners();
  $history=CommercialAccountService::reconcileLinkedHistory();
  return [
   'owners'=>$owners,
   'health'=>self::health(),
   'history'=>$history,
   'linked_accounts'=>(int)(DB::scalar("SELECT COUNT(*) FROM crm_account_links")??0),
   'remaining_unlinked'=>(int)(DB::scalar("SELECT COUNT(*) FROM crm_accounts a LEFT JOIN crm_account_links l ON l.crm_account_code=a.omie_code WHERE a.active=1 AND l.crm_account_code IS NULL")??0),
  ];
 }

 public static function rebuildCachedProfiles(?callable $progress=null,int $batchSize=300): array{
  CommercialSchema::ensure();$batchSize=max(50,min(1000,$batchSize));
  $rows=DB::all("SELECT omie_code,raw_json FROM crm_accounts WHERE active=1 ORDER BY omie_code");
  $total=count($rows);$processed=0;$classified=0;$written=0;
  $pending=[];
  foreach(DB::all("SELECT DISTINCT crm_account_code FROM crm_account_commercial_audit WHERE field_name='classification' AND sync_status IN('pending','error')") as $p)$pending[(string)$p['crm_account_code']]=true;

  $values=[];$params=[];
  $flush=function()use(&$values,&$params,&$written){
   if(!$values)return;
   DB::exec("INSERT INTO crm_account_commercial_profiles(crm_account_code,is_cfc,is_reseller,classification_source,updated_at) VALUES ".implode(',',$values)."
             ON DUPLICATE KEY UPDATE is_cfc=VALUES(is_cfc),is_reseller=VALUES(is_reseller),classification_source=VALUES(classification_source),updated_at=NOW()",$params);
   $written+=count($values);$values=[];$params=[];
  };

  foreach($rows as $row){
   $processed++;$accountCode=(string)$row['omie_code'];
   if(isset($pending[$accountCode])){if($progress&&($processed%500===0||$processed===$total))$progress('profiles',$processed,$total);continue;}
   $raw=json_decode((string)($row['raw_json']??''),true);
   if(is_array($raw)){
    $characteristics=[];
    foreach((array)($raw['caracteristicas']??[]) as $item){
     if(!is_array($item))continue;$key=crm_normalize_key((string)($item['campo']??''));$characteristics[$key]=crm_yes((string)($item['conteudo']??''));
    }
    $tags=[];foreach((array)($raw['tags']??[]) as $item){$tag=crm_normalize_key((string)(is_array($item)?($item['tag']??''):$item));if($tag!=='')$tags[$tag]=true;}
    $hasExplicit=array_key_exists('td cfc',$characteristics)||array_key_exists('td revendedor',$characteristics);
    if($hasExplicit){$isCfc=!empty($characteristics['td cfc']);$isReseller=!empty($characteristics['td revendedor']);$source='omie_crm_characteristics';}
    elseif(isset($tags['cfc'])||isset($tags['revendedor'])){$isCfc=isset($tags['cfc']);$isReseller=isset($tags['revendedor']);$source='omie_crm_tags';}
    else{$isCfc=$isReseller=false;$source='';}
    if($source!==''){
     $classified++;$values[]='(?,?,?,?,NOW())';array_push($params,$accountCode,$isCfc?1:0,$isReseller?1:0,$source);
     if(count($values)>=$batchSize)$flush();
    }
   }
   if($progress&&($processed%500===0||$processed===$total))$progress('profiles',$processed,$total);
  }
  $flush();

  $mirrored=(int)(DB::scalar("SELECT COUNT(*) FROM crm_account_links l JOIN crm_account_commercial_profiles ap ON ap.crm_account_code=l.crm_account_code")??0);
  DB::exec("INSERT INTO client_commercial_profiles(client_id,is_cfc,is_reseller,strategic_notes,classification_source,updated_at)
            SELECT l.client_id,ap.is_cfc,ap.is_reseller,ap.strategic_notes,ap.classification_source,NOW()
            FROM crm_account_links l
            JOIN crm_account_commercial_profiles ap ON ap.crm_account_code=l.crm_account_code
            WHERE NOT EXISTS(
             SELECT 1 FROM client_commercial_audit ca
             WHERE ca.client_id=l.client_id AND ca.field_name='classification' AND ca.sync_status IN('pending','error')
            )
            ON DUPLICATE KEY UPDATE is_cfc=VALUES(is_cfc),is_reseller=VALUES(is_reseller),
             strategic_notes=COALESCE(client_commercial_profiles.strategic_notes,VALUES(strategic_notes)),classification_source=VALUES(classification_source),updated_at=NOW()");

  return ['processed'=>$processed,'classified_in_cache'=>$classified,'account_profiles_written'=>$written,'client_profiles_mirrored'=>$mirrored];
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
  $raw=DB::scalar("SELECT value_json FROM settings WHERE setting_key='commercial_crm_portfolio_authority' LIMIT 1");
  if(!$raw)return false;
  $value=json_decode((string)$raw,true);
  return is_array($value)&&!empty($value['enabled']);
 }

 public static function crmSyncComplete(): bool{
  CommercialSchema::ensure();
  $state=DB::one("SELECT last_success_at,last_error FROM sync_state WHERE module_key='crm_accounts' LIMIT 1");
  return $state&&!empty($state['last_success_at'])&&empty($state['last_error'])&&(int)(DB::scalar("SELECT COUNT(*) FROM crm_accounts WHERE active=1")??0)>0;
 }

 public static function setCrmPortfolioAuthority(bool $enabled,int $actorUserId=0,string $notes=''): void{
  CommercialSchema::ensure();
  $payload=['enabled'=>$enabled,'changed_at'=>date('c'),'changed_by'=>$actorUserId?:null,'notes'=>trim($notes)];
  DB::exec("INSERT INTO settings(setting_key,value_json,updated_at) VALUES('commercial_crm_portfolio_authority',?,NOW())
            ON DUPLICATE KEY UPDATE value_json=VALUES(value_json),updated_at=NOW()",
   [json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)]);
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
   'crm_accounts'=>(int)(DB::scalar("SELECT COUNT(*) FROM crm_accounts WHERE active=1")??0),
   'linked_accounts'=>(int)(DB::scalar("SELECT COUNT(*) FROM crm_account_links")??0),
   'unlinked_accounts'=>(int)(DB::scalar("SELECT COUNT(*) FROM crm_accounts a LEFT JOIN crm_account_links l ON l.crm_account_code=a.omie_code WHERE a.active=1 AND l.crm_account_code IS NULL")??0),
   'clients_with_crm_owner'=>(int)(DB::scalar("SELECT COUNT(*) FROM clients WHERE active=1 AND crm_inactive=0 AND crm_owner_user_id IS NOT NULL")??0),
   'clients_without_crm_owner'=>(int)(DB::scalar("SELECT COUNT(*) FROM clients WHERE active=1 AND crm_inactive=0 AND crm_owner_user_id IS NULL")??0),
   'active_crm_sellers'=>count(self::activeCrmSellerCodes()),
   'clients_with_stale_crm_owner'=>(int)(DB::scalar("SELECT COUNT(*) FROM clients WHERE active=1 AND crm_inactive=0 AND crm_owner_omie_code IS NOT NULL AND crm_owner_user_id IS NULL")??0),
   'profiles'=>(int)(DB::scalar("SELECT COUNT(*) FROM crm_account_commercial_profiles")??0),
   'cfc'=>(int)(DB::scalar("SELECT COUNT(*) FROM crm_account_commercial_profiles WHERE is_cfc=1")??0),
   'resellers'=>(int)(DB::scalar("SELECT COUNT(*) FROM crm_account_commercial_profiles WHERE is_reseller=1")??0),
   'both'=>(int)(DB::scalar("SELECT COUNT(*) FROM crm_account_commercial_profiles WHERE is_cfc=1 AND is_reseller=1")??0),
   'crm_sync_complete'=>self::crmSyncComplete()?1:0,
   'crm_portfolio_authority'=>self::crmPortfolioReady()?1:0,
   'outbox_pending'=>(int)(DB::scalar("SELECT COUNT(*) FROM sync_outbox WHERE status IN('pending','error')")??0),
  ];
 }
}

final class CommercialHomeService {
 public static function build(array $user,array $filters=[]): array{
  CommercialSchema::ensure();
  $userId=(int)($user['id']??0);$crmUserCode=trim((string)($user['crm_user_omie_code']??''));
  $empty=['today'=>['count'=>0,'items'=>[]],'overdue'=>['count'=>0,'items'=>[]],'stale'=>['count'=>0,'items'=>[]],'attention'=>['count'=>0,'items'=>[]],'portfolio'=>['rows'=>[],'total'=>0,'page'=>1,'pages'=>1,'per_page'=>5,'filters'=>[]]];
  if(($user['role']??'')!=='seller'||$userId<=0||$crmUserCode==='')return $empty;

  $taskJoin=" FROM tasks t
              LEFT JOIN crm_accounts a ON a.omie_code=t.crm_account_code
              LEFT JOIN crm_account_links l ON l.crm_account_code=a.omie_code
              LEFT JOIN clients c ON c.id=COALESCE(t.client_id,l.client_id)
              WHERE t.status='pending' AND t.type='sales' AND t.assigned_user_id=?";

  $todayCount=(int)(DB::scalar("SELECT COUNT(*)".$taskJoin." AND t.due_at>=CURDATE() AND t.due_at<CURDATE()+INTERVAL 1 DAY",[$userId])??0);
  $overdueCount=(int)(DB::scalar("SELECT COUNT(*)".$taskJoin." AND t.due_at<CURDATE()",[$userId])??0);
  $taskSelect="SELECT t.id,t.due_at,t.title,t.crm_account_code,COALESCE(t.client_id,l.client_id) client_id,
                      COALESCE(NULLIF(a.trade_name,''),NULLIF(a.name,''),c.name,'Conta sem nome') account_name".$taskJoin;
  $todayItems=DB::all($taskSelect." AND t.due_at>=CURDATE() AND t.due_at<CURDATE()+INTERVAL 1 DAY ORDER BY t.due_at,t.id LIMIT 3",[$userId]);
  $overdueItems=DB::all($taskSelect." AND t.due_at<CURDATE() ORDER BY t.due_at,t.id LIMIT 3",[$userId]);
  $activityJoin=" FROM crm_accounts a
                  LEFT JOIN crm_account_links l ON l.crm_account_code=a.omie_code
                  LEFT JOIN clients c ON c.id=l.client_id
                  LEFT JOIN (
                   SELECT crm_account_code,MAX(created_at) last_contact_at
                   FROM activities
                   WHERE crm_account_code IS NOT NULL AND activity_type IN('contact_completed','follow_up')
                   GROUP BY crm_account_code
                  ) act ON act.crm_account_code=a.omie_code
                  WHERE a.active=1 AND a.crm_user_code=?";
  $staleCondition=" AND (act.last_contact_at IS NULL OR act.last_contact_at<CURDATE()-INTERVAL 30 DAY)";
  $staleCount=(int)(DB::scalar("SELECT COUNT(*)".$activityJoin.$staleCondition,[$crmUserCode])??0);
  $staleItems=DB::all("SELECT a.omie_code crm_account_code,l.client_id,
                             COALESCE(NULLIF(a.trade_name,''),NULLIF(a.name,''),'Conta sem nome') account_name,
                             act.last_contact_at,
                             CASE WHEN act.last_contact_at IS NULL THEN NULL ELSE DATEDIFF(CURDATE(),DATE(act.last_contact_at)) END days_without_contact
                      ".$activityJoin.$staleCondition."
                      ORDER BY act.last_contact_at IS NOT NULL,act.last_contact_at ASC,a.trade_name,a.name LIMIT 3",[$crmUserCode]);

  $attentionFrom=" FROM tasks t
                   LEFT JOIN activities source_activity ON source_activity.id=t.source_activity_id
                   LEFT JOIN commercial_sales sale ON sale.activity_id=source_activity.id AND sale.future_promise=1
                   LEFT JOIN crm_accounts a ON a.omie_code=t.crm_account_code
                   LEFT JOIN crm_account_links l ON l.crm_account_code=a.omie_code
                   LEFT JOIN clients c ON c.id=COALESCE(t.client_id,l.client_id)
                   WHERE t.status='pending' AND t.type='sales' AND t.assigned_user_id=?
                     AND (t.due_at<NOW() OR sale.id IS NOT NULL)";
  $attentionCount=(int)(DB::scalar("SELECT COUNT(DISTINCT t.id)".$attentionFrom,[$userId])??0);
  $attentionItems=DB::all("SELECT DISTINCT t.id,t.due_at,t.title,t.crm_account_code,COALESCE(t.client_id,l.client_id) client_id,
                                  COALESCE(NULLIF(a.trade_name,''),NULLIF(a.name,''),c.name,'Conta sem nome') account_name,
                                  CASE WHEN sale.id IS NOT NULL THEN 'Promessa de venda' ELSE 'Retorno atrasado' END attention_reason
                           ".$attentionFrom."
                           ORDER BY sale.id IS NULL,t.due_at,t.id LIMIT 3",[$userId]);

  // A Home é uma agenda de decisão, não uma segunda carteira pesquisável.
  // Mostra apenas o recorte mais urgente; busca, filtros e paginação vivem em Minha Carteira.
  $portfolioFilters=['page'=>1,'per_page'=>5,'scope'=>'active','q'=>'','sort'=>'urgent'];
  $portfolio=CommercialAccountService::portfolio($user,$portfolioFilters);
  $portfolio['filters']=['home_q'=>'','home_type'=>'all','home_order'=>'urgent','home_per_page'=>5];

  return [
   'today'=>['count'=>$todayCount,'items'=>$todayItems],
   'overdue'=>['count'=>$overdueCount,'items'=>$overdueItems],
   'stale'=>['count'=>$staleCount,'items'=>$staleItems],
   'attention'=>['count'=>$attentionCount,'items'=>$attentionItems],
   'portfolio'=>$portfolio,
  ];
 }
}

final class CommercialAccountService {
 public static function canWork(array $user,string $accountCode): bool{
  CommercialSchema::ensure();
  if(in_array((string)($user['role']??''),['admin','supervisor'],true))return true;
  if(($user['role']??'')!=='seller')return false;
  $crmUserCode=trim((string)($user['crm_user_omie_code']??''));
  if($crmUserCode===''||!in_array($crmUserCode,CommercialPortfolioService::activeCrmSellerCodes(),true))return false;
  return (int)(DB::scalar("SELECT COUNT(*) FROM crm_accounts WHERE omie_code=? AND active=1 AND crm_user_code=?",[$accountCode,$crmUserCode])??0)>0;
 }

 public static function get(string $accountCode): ?array{
  CommercialSchema::ensure();
  return DB::one("SELECT a.*,l.client_id,l.link_method,l.confidence link_confidence,l.is_primary link_is_primary,l.verified_at link_verified_at,
                         c.name client_name,c.legal_name client_legal_name,c.omie_code client_omie_code,c.active client_active,c.crm_inactive,
                         m.first_purchase_at,m.last_purchase_at,m.revenue_12m,m.orders_12m,m.avg_ticket_12m,m.avg_interval_days,
                         cc.open_amount collection_open_amount,cc.max_overdue_days collection_overdue_days,cc.status collection_status,
                         cu.name owner_name,cu.email owner_email,
                         COALESCE(ap.is_cfc,0) is_cfc,COALESCE(ap.is_reseller,0) is_reseller,
                         ap.strategic_notes,ap.classification_source,ap.updated_at profile_updated_at
                  FROM crm_accounts a
                  LEFT JOIN crm_account_links l ON l.crm_account_code=a.omie_code
                  LEFT JOIN clients c ON c.id=l.client_id
                  LEFT JOIN client_metrics m ON m.client_id=c.id
                  LEFT JOIN collection_cases cc ON cc.client_id=c.id
                  LEFT JOIN crm_users cu ON cu.omie_code=a.crm_user_code
                  LEFT JOIN crm_account_commercial_profiles ap ON ap.crm_account_code=a.omie_code
                  WHERE a.omie_code=? LIMIT 1",[$accountCode]);
 }

 public static function linkAudit(string $accountCode,int $limit=20): array{
  CommercialSchema::ensure();$limit=max(1,min(100,$limit));
  return DB::all("SELECT la.*,u.name actor_name,pc.name previous_client_name,c.name client_name,
                         pc.omie_code previous_client_omie_code,c.omie_code client_omie_code
                  FROM crm_account_link_audit la
                  LEFT JOIN users u ON u.id=la.actor_user_id
                  LEFT JOIN clients pc ON pc.id=la.previous_client_id
                  LEFT JOIN clients c ON c.id=la.client_id
                  WHERE la.crm_account_code=?
                  ORDER BY la.created_at DESC,la.id DESC LIMIT ".$limit,[$accountCode]);
 }

 public static function linkClient(string $accountCode,int $clientId,int $actorUserId,bool $confirmDocumentMismatch=false,string $notes=''): array{
  CommercialSchema::ensure();$accountCode=trim($accountCode);$notes=trim($notes);
  $account=DB::one("SELECT omie_code,name,trade_name,document FROM crm_accounts WHERE omie_code=? AND active=1 LIMIT 1",[$accountCode]);
  if(!$account)throw new RuntimeException('Conta CRM não encontrada ou inativa.');
  $client=DB::one("SELECT id,omie_code,name,document,active,crm_inactive FROM clients WHERE id=? LIMIT 1",[$clientId]);
  if(!$client||empty($client['active']))throw new RuntimeException('Selecione um Cliente Geral ativo da Omie.');
  if(!empty($client['crm_inactive']))throw new RuntimeException('O Cliente Geral está inativo no CRM. Reative-o antes de criar o vínculo.');

  $accountDocument=crm_digits((string)($account['document']??''));$clientDocument=crm_digits((string)($client['document']??''));
  $documentMismatch=$accountDocument===''||$clientDocument===''||$accountDocument!==$clientDocument;
  if($documentMismatch&&!$confirmDocumentMismatch)throw new RuntimeException('Os documentos não coincidem ou estão ausentes. Confirme a divergência para realizar um vínculo manual auditado.');

  $existing=DB::one("SELECT client_id FROM crm_account_links WHERE crm_account_code=? LIMIT 1",[$accountCode]);
  $previousClientId=(int)($existing['client_id']??0);$action=$previousClientId>0?($previousClientId===$clientId?'verified':'relinked'):'linked';
  $primary=(int)(DB::scalar("SELECT COUNT(*) FROM crm_account_links WHERE client_id=? AND is_primary=1 AND crm_account_code<>?",[$clientId,$accountCode])??0)===0?1:0;
  $method=$documentMismatch?'manual_review':'document_manual';

  $ownsTransaction=!DB::conn()->inTransaction();if($ownsTransaction)DB::conn()->beginTransaction();
  try{
   DB::exec("INSERT INTO crm_account_links(crm_account_code,client_id,link_method,confidence,is_primary,verified_by_user_id,verified_at,updated_at)
             VALUES(?,?,?,?,?,?,NOW(),NOW())
             ON DUPLICATE KEY UPDATE client_id=VALUES(client_id),link_method=VALUES(link_method),confidence=VALUES(confidence),is_primary=VALUES(is_primary),verified_by_user_id=VALUES(verified_by_user_id),verified_at=NOW(),updated_at=NOW()",
    [$accountCode,$clientId,$method,$documentMismatch?80:100,$primary,$actorUserId?:null]);
   DB::exec("INSERT INTO crm_account_link_audit(crm_account_code,previous_client_id,client_id,action,link_method,actor_user_id,notes,created_at)
             VALUES(?,?,?,?,?,?,?,NOW())",
    [$accountCode,$previousClientId?:null,$clientId,$action,$method,$actorUserId?:null,$notes!==''?$notes:null]);
   self::refreshClientProjection($clientId);
   if($previousClientId>0&&$previousClientId!==$clientId)self::refreshClientProjection($previousClientId);
   self::reconcileLinkedHistory($accountCode);
   if($ownsTransaction)DB::conn()->commit();
  }catch(Throwable $e){if($ownsTransaction&&DB::conn()->inTransaction())DB::conn()->rollBack();throw $e;}
  return self::get($accountCode)??[];
 }

 public static function unlinkClient(string $accountCode,int $actorUserId,string $notes=''): void{
  CommercialSchema::ensure();$existing=DB::one("SELECT client_id,link_method FROM crm_account_links WHERE crm_account_code=? LIMIT 1",[$accountCode]);
  if(!$existing)throw new RuntimeException('Esta Conta CRM já está sem vínculo com Cliente Geral.');
  $clientId=(int)$existing['client_id'];$notes=trim($notes);
  $ownsTransaction=!DB::conn()->inTransaction();if($ownsTransaction)DB::conn()->beginTransaction();
  try{
   DB::exec("DELETE FROM crm_account_links WHERE crm_account_code=?",[$accountCode]);
   DB::exec("INSERT INTO crm_account_link_audit(crm_account_code,previous_client_id,client_id,action,link_method,actor_user_id,notes,created_at)
             VALUES(?,?,NULL,'unlinked',?,?,?,NOW())",
    [$accountCode,$clientId,(string)$existing['link_method'],$actorUserId?:null,$notes!==''?$notes:null]);
   self::refreshClientProjection($clientId);if($ownsTransaction)DB::conn()->commit();
  }catch(Throwable $e){if($ownsTransaction&&DB::conn()->inTransaction())DB::conn()->rollBack();throw $e;}
 }

 private static function refreshClientProjection(int $clientId): void{
  if($clientId<=0)return;
  $accounts=DB::all("SELECT a.omie_code,a.crm_user_code,u.id owner_user_id
                     FROM crm_account_links l
                     JOIN crm_accounts a ON a.omie_code=l.crm_account_code AND a.active=1
                     LEFT JOIN users u ON u.crm_user_omie_code=a.crm_user_code AND u.active=1 AND u.role='seller'
                     WHERE l.client_id=? ORDER BY l.is_primary DESC,a.omie_code",[$clientId]);
  if(count($accounts)!==1){DB::exec("UPDATE clients SET crm_account_code=NULL,crm_owner_omie_code=NULL,crm_owner_user_id=NULL WHERE id=?",[$clientId]);return;}
  $account=$accounts[0];DB::exec("UPDATE clients SET crm_account_code=?,crm_owner_omie_code=?,crm_owner_user_id=? WHERE id=?",[
   $account['omie_code'],$account['crm_user_code']?:null,(int)($account['owner_user_id']??0)?:null,$clientId
  ]);
 }

 public static function contacts(string $accountCode): array{
  CommercialSchema::ensure();
  return DB::all("SELECT * FROM crm_contacts WHERE crm_account_code=? ORDER BY name,last_name",[$accountCode]);
 }

 /**
  * Completa somente referências ausentes entre Conta CRM e Cliente Geral.
  * IDs, datas, responsáveis, status, textos e prazos existentes nunca são alterados.
  * Quando um Cliente Geral possui mais de uma Conta CRM ativa, o histórico legado é preservado sem adivinhação.
  */
 public static function reconcileLinkedHistory(?string $accountCode=null): array{
  CommercialSchema::ensure();$accountCode=$accountCode!==null?trim($accountCode):null;
  $stats=['activities_account_backfilled'=>0,'tasks_account_backfilled'=>0,'activities_client_backfilled'=>0,'tasks_client_backfilled'=>0,'ambiguous_clients_preserved'=>0];

  if($accountCode!==null&&$accountCode!==''){
   $link=DB::one("SELECT l.client_id FROM crm_account_links l JOIN crm_accounts a ON a.omie_code=l.crm_account_code AND a.active=1 WHERE l.crm_account_code=? LIMIT 1",[$accountCode]);
   if(!$link)return $stats;
   $clientId=(int)$link['client_id'];
   $stats['activities_client_backfilled']=DB::exec("UPDATE activities SET client_id=? WHERE crm_account_code=? AND client_id IS NULL",[$clientId,$accountCode]);
   $stats['tasks_client_backfilled']=DB::exec("UPDATE tasks SET client_id=? WHERE crm_account_code=? AND client_id IS NULL",[$clientId,$accountCode]);

   $activeLinks=(int)(DB::scalar("SELECT COUNT(*) FROM crm_account_links l JOIN crm_accounts a ON a.omie_code=l.crm_account_code AND a.active=1 WHERE l.client_id=?",[$clientId])??0);
   if($activeLinks===1){
    $stats['activities_account_backfilled']=DB::exec("UPDATE activities SET crm_account_code=? WHERE client_id=? AND crm_account_code IS NULL",[$accountCode,$clientId]);
    $stats['tasks_account_backfilled']=DB::exec("UPDATE tasks SET crm_account_code=? WHERE client_id=? AND crm_account_code IS NULL AND type='sales'",[$accountCode,$clientId]);
   }elseif($activeLinks>1)$stats['ambiguous_clients_preserved']=1;
   return $stats;
  }

  $stats['activities_client_backfilled']=DB::exec("UPDATE activities a
    JOIN crm_account_links l ON l.crm_account_code=a.crm_account_code
    JOIN crm_accounts ca ON ca.omie_code=l.crm_account_code AND ca.active=1
    SET a.client_id=l.client_id
    WHERE a.crm_account_code IS NOT NULL AND a.client_id IS NULL");
  $stats['tasks_client_backfilled']=DB::exec("UPDATE tasks t
    JOIN crm_account_links l ON l.crm_account_code=t.crm_account_code
    JOIN crm_accounts ca ON ca.omie_code=l.crm_account_code AND ca.active=1
    SET t.client_id=l.client_id
    WHERE t.crm_account_code IS NOT NULL AND t.client_id IS NULL");

  $unique="SELECT l.client_id,MIN(l.crm_account_code) crm_account_code
           FROM crm_account_links l
           JOIN crm_accounts ca ON ca.omie_code=l.crm_account_code AND ca.active=1
           GROUP BY l.client_id HAVING COUNT(*)=1";
  $stats['activities_account_backfilled']=DB::exec("UPDATE activities a JOIN (".$unique.") x ON x.client_id=a.client_id SET a.crm_account_code=x.crm_account_code WHERE a.crm_account_code IS NULL");
  $stats['tasks_account_backfilled']=DB::exec("UPDATE tasks t JOIN (".$unique.") x ON x.client_id=t.client_id SET t.crm_account_code=x.crm_account_code WHERE t.crm_account_code IS NULL AND t.type='sales'");
  $stats['ambiguous_clients_preserved']=(int)(DB::scalar("SELECT COUNT(*) FROM (
    SELECT l.client_id FROM crm_account_links l JOIN crm_accounts ca ON ca.omie_code=l.crm_account_code AND ca.active=1
    GROUP BY l.client_id HAVING COUNT(*)>1
   ) x")??0);
  return $stats;
 }

 /**
  * Estado consolidado da identidade comercial.
  * CRM Omie é a raiz; Cliente/Vendas, financeiro, contatos, tarefas e histórico complementam a mesma Conta CRM.
  */
 public static function ecosystem(string $accountCode): array{
  CommercialSchema::ensure();$account=self::get($accountCode);
  if(!$account)return ['has_crm'=>false,'has_contacts'=>false,'has_sales_client'=>false,'has_sales_history'=>false,'has_financial'=>false,'has_commercial_history'=>false];

  $clientId=(int)($account['client_id']??0);$clientOmieCode=trim((string)($account['client_omie_code']??''));
  $contacts=(int)(DB::scalar("SELECT COUNT(*) FROM crm_contacts WHERE crm_account_code=?",[$accountCode])??0);
  $activities=(int)(DB::scalar("SELECT COUNT(*) FROM activities WHERE crm_account_code=?",[$accountCode])??0);
  $tasks=(int)(DB::scalar("SELECT COUNT(*) FROM tasks WHERE crm_account_code=? AND type='sales'",[$accountCode])??0);
  $pendingTasks=(int)(DB::scalar("SELECT COUNT(*) FROM tasks WHERE crm_account_code=? AND type='sales' AND status='pending'",[$accountCode])??0);

  $orders=0;$services=0;$orderAmount=0.0;$serviceAmount=0.0;$financialTitles=0;$financialOpen=0.0;$lastOrder=null;$lastService=null;
  if($clientOmieCode!==''){
   $o=DB::one("SELECT COUNT(*) total,COALESCE(SUM(total),0) amount,MAX(order_date) last_at FROM orders WHERE client_omie_code=?",[$clientOmieCode])??[];
   $s=DB::one("SELECT COUNT(*) total,COALESCE(SUM(total),0) amount,MAX(service_date) last_at FROM service_orders WHERE client_omie_code=?",[$clientOmieCode])??[];
   $f=DB::one("SELECT COUNT(*) total,COALESCE(SUM(open_amount),0) open_amount FROM financial_movements WHERE client_omie_code=? AND open_amount>0",[$clientOmieCode])??[];
   $orders=(int)($o['total']??0);$orderAmount=(float)($o['amount']??0);$lastOrder=$o['last_at']??null;
   $services=(int)($s['total']??0);$serviceAmount=(float)($s['amount']??0);$lastService=$s['last_at']??null;
   $financialTitles=(int)($f['total']??0);$financialOpen=(float)($f['open_amount']??0);
  }

  $legacyActivities=$clientId>0?(int)(DB::scalar("SELECT COUNT(*) FROM activities WHERE client_id=? AND crm_account_code IS NULL",[$clientId])??0):0;
  $legacyTasks=$clientId>0?(int)(DB::scalar("SELECT COUNT(*) FROM tasks WHERE client_id=? AND crm_account_code IS NULL AND type='sales'",[$clientId])??0):0;

  return [
   'has_crm'=>true,'crm_account_code'=>$accountCode,'crm_owner_code'=>(string)($account['crm_user_code']??''),'crm_owner_name'=>(string)($account['owner_name']??''),
   'contacts_count'=>$contacts,'has_contacts'=>$contacts>0,
   'client_id'=>$clientId,'client_omie_code'=>$clientOmieCode,
   'sales_client_active'=>$clientId>0&&(int)($account['client_active']??0)===1&&empty($account['crm_inactive']),
   'has_sales_client'=>$clientId>0&&$clientOmieCode!==''&&(int)($account['client_active']??0)===1&&empty($account['crm_inactive']),
   'orders_count'=>$orders,'orders_amount'=>$orderAmount,'last_order_at'=>$lastOrder,
   'services_count'=>$services,'services_amount'=>$serviceAmount,'last_service_at'=>$lastService,'has_sales_history'=>($orders+$services)>0,
   'financial_titles_count'=>$financialTitles,'financial_open_amount'=>$financialOpen,'has_financial'=>$financialTitles>0,
   'collection_open'=>(string)($account['collection_status']??'')==='open','collection_open_amount'=>(float)($account['collection_open_amount']??0),
   'activities_count'=>$activities,'tasks_count'=>$tasks,'pending_tasks_count'=>$pendingTasks,'has_commercial_history'=>($activities+$tasks)>0,
   'legacy_activities_without_account'=>$legacyActivities,'legacy_tasks_without_account'=>$legacyTasks,
   'identity_complete'=>$clientId>0&&$clientOmieCode!==''&&($legacyActivities+$legacyTasks)===0
  ];
 }

 public static function audit(string $accountCode,int $limit=50): array{
  CommercialSchema::ensure();$limit=max(1,min(200,$limit));
  return DB::all("SELECT a.*,u.name actor_name FROM crm_account_commercial_audit a LEFT JOIN users u ON u.id=a.actor_user_id WHERE a.crm_account_code=? ORDER BY a.created_at DESC,a.id DESC LIMIT ".$limit,[$accountCode]);
 }

 public static function profile(string $accountCode): array{
  CommercialSchema::ensure();
  return DB::one("SELECT * FROM crm_account_commercial_profiles WHERE crm_account_code=?",[$accountCode])??[
   'crm_account_code'=>$accountCode,'is_cfc'=>0,'is_reseller'=>0,'strategic_notes'=>null,'classification_source'=>'local'
  ];
 }

 public static function updateProfile(string $accountCode,bool $isCfc,bool $isReseller,int $actorUserId,string $notes=''): array{
  CommercialSchema::ensure();
  $account=DB::one("SELECT omie_code FROM crm_accounts WHERE omie_code=? AND active=1 LIMIT 1",[$accountCode]);
  if(!$account)throw new RuntimeException('Conta CRM não encontrada ou inativa.');
  $old=self::profile($accountCode);
  $previous=['cfc'=>(int)$old['is_cfc']===1,'reseller'=>(int)$old['is_reseller']===1];
  $next=['cfc'=>$isCfc,'reseller'=>$isReseller];
  $notes=trim($notes);if(mb_strlen($notes)>10000)throw new RuntimeException('A observação estratégica deve ter até 10.000 caracteres.');

  DB::exec("INSERT INTO crm_account_commercial_profiles(crm_account_code,is_cfc,is_reseller,strategic_notes,classification_source,updated_by_user_id,updated_at)
            VALUES(?,?,?,?, 'tecnodata',?,NOW())
            ON DUPLICATE KEY UPDATE is_cfc=VALUES(is_cfc),is_reseller=VALUES(is_reseller),strategic_notes=VALUES(strategic_notes),classification_source='tecnodata',updated_by_user_id=VALUES(updated_by_user_id),updated_at=NOW()",
   [$accountCode,$isCfc?1:0,$isReseller?1:0,$notes!==''?$notes:null,$actorUserId?:null]);

  $clientId=(int)(DB::scalar("SELECT client_id FROM crm_account_links WHERE crm_account_code=? LIMIT 1",[$accountCode])??0);
  if($previous!==$next){
   DB::exec("INSERT INTO crm_account_commercial_audit(crm_account_code,client_id,actor_user_id,field_name,previous_value,new_value,source,sync_status,created_at)
             VALUES(?,?,?,'classification',?,?,'tecnodata','pending',NOW())",
    [$accountCode,$clientId?:null,$actorUserId?:null,json_encode($previous,JSON_UNESCAPED_UNICODE),json_encode($next,JSON_UNESCAPED_UNICODE)]);
   self::enqueueProfile($accountCode,$next);
  }

  $previousNotes=trim((string)($old['strategic_notes']??''));
  if($previousNotes!==$notes){
   DB::exec("INSERT INTO crm_account_commercial_audit(crm_account_code,client_id,actor_user_id,field_name,previous_value,new_value,source,sync_status,synced_at,created_at)
             VALUES(?,?,?,'strategic_notes',?,?,'tecnodata','ignored',NOW(),NOW())",
    [$accountCode,$clientId?:null,$actorUserId?:null,$previousNotes!==''?$previousNotes:null,$notes!==''?$notes:null]);
  }

  if($clientId>0){
   DB::exec("INSERT INTO client_commercial_profiles(client_id,is_cfc,is_reseller,strategic_notes,classification_source,updated_by_user_id,updated_at)
             VALUES(?,?,?,?, 'tecnodata',?,NOW())
             ON DUPLICATE KEY UPDATE is_cfc=VALUES(is_cfc),is_reseller=VALUES(is_reseller),strategic_notes=VALUES(strategic_notes),classification_source='tecnodata',updated_by_user_id=VALUES(updated_by_user_id),updated_at=NOW()",
    [$clientId,$isCfc?1:0,$isReseller?1:0,$notes!==''?$notes:null,$actorUserId?:null]);
  }
  return self::profile($accountCode);
 }

 private static function enqueueProfile(string $accountCode,array $classification): void{
  $payload=['crm_account_code'=>$accountCode,'cfc'=>!empty($classification['cfc']),'reseller'=>!empty($classification['reseller'])];
  $existing=DB::one("SELECT id FROM sync_outbox WHERE entity_type='crm_account_classification' AND entity_id=? AND operation='upsert_omie_crm_characteristics' AND status IN('pending','error') ORDER BY id DESC LIMIT 1",[$accountCode]);
  $json=json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
  if($existing)DB::exec("UPDATE sync_outbox SET payload_json=?,status='pending',attempts=0,last_error=NULL,next_attempt_at=NULL,updated_at=NOW() WHERE id=?",[$json,(int)$existing['id']]);
  else DB::exec("INSERT INTO sync_outbox(entity_type,entity_id,operation,payload_json,status,attempts,created_at,updated_at) VALUES('crm_account_classification',?,'upsert_omie_crm_characteristics',?,'pending',0,NOW(),NOW())",[$accountCode,$json]);
 }

 public static function portfolio(array $user,array $filters=[]): array{
  CommercialSchema::ensure();
  $page=max(1,(int)($filters['page']??1));$perPage=max(5,min(100,(int)($filters['per_page']??25)));
  $q=trim((string)($filters['q']??''));$classification=(string)($filters['classification']??'all');
  if(!in_array($classification,['all','cfc','reseller','both','unclassified'],true))$classification='all';
  $link=(string)($filters['link']??'all');if(!in_array($link,['all','linked','prospect'],true))$link='all';
  $attention=(string)($filters['attention']??'all');if(!in_array($attention,['all','overdue','today','never','stale30','upcoming','unplanned'],true))$attention='all';
  $owner=trim((string)($filters['owner']??''));
  $sort=(string)($filters['sort']??'urgent');if(!in_array($sort,['urgent','stale','next'],true))$sort='urgent';
  $scope=(string)($filters['scope']??'active');if(!in_array($scope,['active','legacy','all'],true))$scope='active';

  $where=['a.active=1'];$params=[];
  $role=(string)($user['role']??'');
  if($role==='seller'){
   $crmUserCode=trim((string)($user['crm_user_omie_code']??''));
   if($crmUserCode===''||!in_array($crmUserCode,CommercialPortfolioService::activeCrmSellerCodes(),true))return ['rows'=>[],'total'=>0,'page'=>1,'pages'=>1,'stats'=>self::stats([],[]),'filters'=>compact('q','classification','link','attention','owner','scope','sort')];
   $where[]='a.crm_user_code=?';$params[]=$crmUserCode;
  }else{
   $activeCodes=CommercialPortfolioService::activeCrmSellerCodes();
   if($owner!==''){$where[]='a.crm_user_code=?';$params[]=$owner;}
   elseif($scope==='active'){
    if($activeCodes){$where[]='a.crm_user_code IN ('.implode(',',array_fill(0,count($activeCodes),'?')).')';array_push($params,...$activeCodes);}
    else $where[]='1=0';
   }elseif($scope==='legacy'){
    if($activeCodes){$where[]="(a.crm_user_code IS NULL OR a.crm_user_code NOT IN (".implode(',',array_fill(0,count($activeCodes),'?')).')';array_push($params,...$activeCodes);}
   }
  }

  if($link==='linked')$where[]='l.client_id IS NOT NULL';
  elseif($link==='prospect')$where[]='l.client_id IS NULL';

  if($classification==='cfc')$where[]='COALESCE(ap.is_cfc,0)=1';
  elseif($classification==='reseller')$where[]='COALESCE(ap.is_reseller,0)=1';
  elseif($classification==='both')$where[]='COALESCE(ap.is_cfc,0)=1 AND COALESCE(ap.is_reseller,0)=1';
  elseif($classification==='unclassified')$where[]='COALESCE(ap.is_cfc,0)=0 AND COALESCE(ap.is_reseller,0)=0';

  if($q!==''){
   $like='%'.$q.'%';$digits=crm_digits($q);
   $parts=['a.name LIKE ?','a.trade_name LIKE ?','a.document LIKE ?','cu.name LIKE ?','c.name LIKE ?'];array_push($params,$like,$like,$like,$like,$like);
   if($digits!==''){$parts[]="REPLACE(REPLACE(REPLACE(REPLACE(a.document,'.',''),'/',''),'-',''),' ','') LIKE ?";$params[]='%'.$digits.'%';}
   $where[]='('.implode(' OR ',$parts).')';
  }

  $statsWhere=$where;$statsParams=$params;
  if($attention==='overdue')$where[]="nt.next_due_at<CURDATE()";
  elseif($attention==='today')$where[]="nt.next_due_at>=CURDATE() AND nt.next_due_at<CURDATE()+INTERVAL 1 DAY";
  elseif($attention==='never')$where[]='act.last_contact_at IS NULL';
  elseif($attention==='stale30')$where[]="(act.last_contact_at IS NULL OR act.last_contact_at<CURDATE()-INTERVAL 30 DAY)";
  elseif($attention==='upcoming')$where[]="nt.next_due_at>=CURDATE()+INTERVAL 1 DAY";
  elseif($attention==='unplanned')$where[]='nt.next_due_at IS NULL';

  $whereSql=implode(' AND ',$where);
  $join=" FROM crm_accounts a
          LEFT JOIN crm_account_links l ON l.crm_account_code=a.omie_code
          LEFT JOIN clients c ON c.id=l.client_id
          LEFT JOIN client_metrics m ON m.client_id=c.id
          LEFT JOIN crm_users cu ON cu.omie_code=a.crm_user_code
          LEFT JOIN crm_account_commercial_profiles ap ON ap.crm_account_code=a.omie_code
          LEFT JOIN (
           SELECT crm_account_code,MAX(created_at) last_contact_at
           FROM activities
           WHERE crm_account_code IS NOT NULL AND activity_type IN('contact_completed','follow_up')
           GROUP BY crm_account_code
          ) act ON act.crm_account_code=a.omie_code
          LEFT JOIN (
           SELECT crm_account_code,MIN(due_at) next_due_at
           FROM tasks
           WHERE crm_account_code IS NOT NULL AND status='pending' AND type='sales'
           GROUP BY crm_account_code
          ) nt ON nt.crm_account_code=a.omie_code";

  $total=(int)(DB::scalar("SELECT COUNT(*)".$join." WHERE ".$whereSql,$params)??0);
  $pages=max(1,(int)ceil($total/$perPage));$page=min($page,$pages);$offset=($page-1)*$perPage;
  $rows=DB::all("SELECT a.omie_code,a.integration_code,a.name,a.trade_name,a.document,a.crm_user_code,a.updated_at,
                        l.client_id,c.name client_name,c.omie_code client_omie_code,c.active client_active,c.crm_inactive,
                        cu.name owner_name,cu.email owner_email,
                        COALESCE(ap.is_cfc,0) is_cfc,COALESCE(ap.is_reseller,0) is_reseller,ap.classification_source,
                        m.first_purchase_at,m.last_purchase_at,m.revenue_12m,m.orders_12m,m.avg_ticket_12m,m.avg_interval_days,
                        act.last_contact_at,nt.next_due_at,
                        CASE WHEN act.last_contact_at IS NULL THEN 999999 ELSE DATEDIFF(CURDATE(),DATE(act.last_contact_at)) END days_without_contact
                 ".$join."
                 WHERE ".$whereSql."
                 ORDER BY ".
                  ($sort==='stale'
                   ?"CASE WHEN act.last_contact_at IS NULL THEN 0 ELSE 1 END,act.last_contact_at ASC,nt.next_due_at ASC"
                   :($sort==='next'
                   ?"CASE WHEN nt.next_due_at IS NULL THEN 1 ELSE 0 END,nt.next_due_at ASC,act.last_contact_at ASC"
                    :"CASE
                        WHEN nt.next_due_at<CURDATE() THEN 0
                        WHEN act.last_contact_at IS NULL OR act.last_contact_at<CURDATE()-INTERVAL 30 DAY THEN 1
                        WHEN nt.next_due_at>=CURDATE() AND nt.next_due_at<CURDATE()+INTERVAL 1 DAY THEN 2
                        ELSE 3
                       END,
                       CASE WHEN nt.next_due_at<CURDATE() THEN nt.next_due_at END ASC,
                       CASE WHEN act.last_contact_at IS NULL OR act.last_contact_at<CURDATE()-INTERVAL 30 DAY THEN act.last_contact_at END ASC,
                       nt.next_due_at ASC"))
                  .",a.trade_name ASC,a.name ASC
                 LIMIT ".$perPage." OFFSET ".$offset,$params);

  return ['rows'=>$rows,'total'=>$total,'page'=>$page,'pages'=>$pages,'per_page'=>$perPage,'stats'=>self::stats($statsWhere,$statsParams),'filters'=>compact('q','classification','link','attention','owner','scope','sort')];
 }

 private static function stats(array $where,array $params): array{
  if(!$where)return ['total'=>0,'linked'=>0,'prospects'=>0,'never_contacted'=>0,'over60'=>0,'overdue_count'=>0,'today_count'=>0,'upcoming_count'=>0,'unplanned_count'=>0];
  $sql=implode(' AND ',$where);
  $join=" FROM crm_accounts a
          LEFT JOIN crm_account_links l ON l.crm_account_code=a.omie_code
          LEFT JOIN clients c ON c.id=l.client_id
          LEFT JOIN crm_users cu ON cu.omie_code=a.crm_user_code
          LEFT JOIN crm_account_commercial_profiles ap ON ap.crm_account_code=a.omie_code
          LEFT JOIN (
           SELECT crm_account_code,MAX(created_at) last_contact_at
           FROM activities
           WHERE crm_account_code IS NOT NULL AND activity_type IN('contact_completed','follow_up')
           GROUP BY crm_account_code
          ) act ON act.crm_account_code=a.omie_code
          LEFT JOIN (
           SELECT crm_account_code,MIN(due_at) next_due_at
           FROM tasks
           WHERE crm_account_code IS NOT NULL AND status='pending'
           GROUP BY crm_account_code
          ) nt ON nt.crm_account_code=a.omie_code";
  return DB::one("SELECT COUNT(*) total,
                         SUM(CASE WHEN l.client_id IS NOT NULL THEN 1 ELSE 0 END) linked,
                         SUM(CASE WHEN l.client_id IS NULL THEN 1 ELSE 0 END) prospects,
                         SUM(CASE WHEN act.last_contact_at IS NULL THEN 1 ELSE 0 END) never_contacted,
                         SUM(CASE WHEN act.last_contact_at IS NOT NULL AND DATEDIFF(CURDATE(),DATE(act.last_contact_at))>60 THEN 1 ELSE 0 END) over60,
                         SUM(CASE WHEN nt.next_due_at<CURDATE() THEN 1 ELSE 0 END) overdue_count,
                         SUM(CASE WHEN nt.next_due_at>=CURDATE() AND nt.next_due_at<CURDATE()+INTERVAL 1 DAY THEN 1 ELSE 0 END) today_count,
                         SUM(CASE WHEN nt.next_due_at>=CURDATE()+INTERVAL 1 DAY THEN 1 ELSE 0 END) upcoming_count,
                         SUM(CASE WHEN nt.next_due_at IS NULL THEN 1 ELSE 0 END) unplanned_count
                  ".$join." WHERE ".$sql,$params)??['total'=>0,'linked'=>0,'prospects'=>0,'never_contacted'=>0,'over60'=>0,'overdue_count'=>0,'today_count'=>0,'upcoming_count'=>0,'unplanned_count'=>0];
 }

 public static function owners(): array{
  CommercialSchema::ensure();
  return DB::all("SELECT cu.omie_code,cu.name,cu.email,
                         COUNT(a.omie_code) account_count,
                         CASE WHEN cu.omie_code IN (".implode(',',array_fill(0,max(1,count(CommercialPortfolioService::activeCrmSellerCodes()))),'?').") THEN 1 ELSE 0 END operational
                  FROM crm_users cu
                  LEFT JOIN crm_accounts a ON a.crm_user_code=cu.omie_code AND a.active=1
                  GROUP BY cu.omie_code,cu.name,cu.email
                  HAVING account_count>0
                  ORDER BY operational DESC,account_count DESC,cu.name",
   CommercialPortfolioService::activeCrmSellerCodes()?:['__NONE__']);
 }
}

final class CommercialActivityService {
 public static function channels(): array{
  return [
   ['code'=>'whatsapp','label'=>'WhatsApp','icon'=>'fa-brands fa-whatsapp'],
   ['code'=>'phone','label'=>'Ligação','icon'=>'fa-phone'],
   ['code'=>'email','label'=>'E-mail','icon'=>'fa-envelope'],
   ['code'=>'presential','label'=>'Presencial','icon'=>'fa-user-group'],
   ['code'=>'other','label'=>'Outro','icon'=>'fa-ellipsis'],
  ];
 }

 public static function channelLabel(string $code): string{
  if($code==='video')return 'Videoconferência';
  foreach(self::channels() as $item)if($item['code']===$code)return $item['label'];
  return $code;
 }

 public static function types(): array{
  return [
   ['code'=>'contact_attempt','label'=>'Tentativa de contato','icon'=>'fa-phone'],
   ['code'=>'contact_completed','label'=>'Contato realizado','icon'=>'fa-comment-dots'],
   ['code'=>'follow_up','label'=>'Follow-up / Atividade','icon'=>'fa-clipboard-check'],
   ['code'=>'sale','label'=>'Venda','icon'=>'fa-dollar-sign'],
  ];
 }

 public static function categories(string $type=''): array{
  $types=['contact_attempt','contact_completed','follow_up','sale'];
  $all=[
   ['code'=>'commercial','label'=>'Comercial','types'=>$types],
   ['code'=>'relationship','label'=>'Relacionamento','types'=>$types],
   ['code'=>'follow_up','label'=>'Follow-up','types'=>$types],
   ['code'=>'support','label'=>'Suporte','types'=>$types],
   ['code'=>'update','label'=>'Atualização','types'=>$types],
   ['code'=>'other','label'=>'Outro','types'=>$types],
  ];
  if($type==='')return $all;
  return array_values(array_filter($all,static fn($item)=>in_array($type,$item['types'],true)));
 }

 public static function outcomes(): array{
  return [
   ['code'=>'attempt','label'=>'Tentativa registrada','types'=>['contact_attempt']],
   ['code'=>'no_answer','label'=>'Não atendeu','types'=>['contact_attempt']],
   ['code'=>'busy','label'=>'Ocupado / indisponível','types'=>['contact_attempt']],
   ['code'=>'wrong_contact','label'=>'Contato incorreto','types'=>['contact_attempt']],
   ['code'=>'message_left','label'=>'Mensagem enviada','types'=>['contact_attempt']],
   ['code'=>'contact','label'=>'Contato realizado','types'=>['contact_completed']],
   ['code'=>'interested','label'=>'Interessado','types'=>['contact_completed']],
   ['code'=>'not_interested','label'=>'Sem interesse agora','types'=>['contact_completed']],
   ['code'=>'information','label'=>'Informação prestada','types'=>['contact_completed']],
   ['code'=>'pending','label'=>'Pendente de retorno','types'=>['contact_completed','follow_up']],
   ['code'=>'resolved','label'=>'Resolvido','types'=>['contact_completed','follow_up']],
   ['code'=>'progress','label'=>'Em andamento','types'=>['follow_up']],
   ['code'=>'sale','label'=>'Venda iniciada','types'=>['sale']],
  ];
 }

 public static function activityTypeLabel(string $code): string{
  $extended=['orientation'=>'Orientação','activation'=>'Ativação','campaign'=>'Campanha','material'=>'Material','access'=>'Acesso','other'=>'Outros'];
  if(isset($extended[$code]))return $extended[$code];
  foreach(self::types() as $item)if($item['code']===$code)return $item['label'];
  return $code;
 }
 public static function categoryLabel(?string $code): string{
  foreach(self::categories() as $item)if($item['code']===$code)return $item['label'];
  return (string)$code;
 }
 public static function outcomeLabel(?string $code): string{
  foreach(self::outcomes() as $item)if($item['code']===$code)return $item['label'];
  return (string)$code;
 }

 public static function record(string $accountCode,array $user,array $data): array{
  CommercialSchema::ensure();
  if(!CommercialAccountService::canWork($user,$accountCode))throw new RuntimeException('Esta Conta CRM não pertence à sua carteira operacional.');
  $account=CommercialAccountService::get($accountCode);if(!$account)throw new RuntimeException('Conta CRM não encontrada.');
  $clientId=(int)($account['client_id']??0);
  $clientOperational=$clientId>0&&(int)($account['client_active']??0)===1&&empty($account['crm_inactive']);
  $type=trim((string)($data['activity_type']??''));$validTypes=array_column(self::types(),'code');
  if(!in_array($type,$validTypes,true))throw new RuntimeException('Selecione um tipo de atividade válido.');
  if($type==='sale'&&!$clientOperational)throw new RuntimeException('A venda exige um Cliente Geral Omie ativo e vinculado à Conta CRM.');

  $channel=trim((string)($data['channel']??''));
  $validChannels=array_column(self::channels(),'code');
  if(!in_array($channel,$validChannels,true))throw new RuntimeException('Selecione um canal válido.');

  $category=trim((string)($data['category_code']??''));
  $validCategories=array_column(self::categories($type),'code');
  if(!in_array($category,$validCategories,true))throw new RuntimeException('Selecione um tipo de contato válido.');

  $defaultOutcomes=['contact_attempt'=>'attempt','contact_completed'=>'contact','follow_up'=>'progress','sale'=>'sale'];
  $outcome=trim((string)($data['outcome_code']??''));if($outcome==='')$outcome=$defaultOutcomes[$type]??'progress';
  $validOutcomes=array_values(array_filter(self::outcomes(),static fn($item)=>in_array($type,$item['types'],true)));
  if(!in_array($outcome,array_column($validOutcomes,'code'),true))$outcome=$defaultOutcomes[$type]??$outcome;

  $notes=trim((string)($data['notes']??''));if(mb_strlen($notes)>2000)throw new RuntimeException('O registro da atividade deve ter até 2.000 caracteres.');
  $scheduleFieldPresent=array_key_exists('schedule_return',$data);
  $scheduleRequested=!empty($data['schedule_return']);
  $nextAt=trim((string)($data['next_at']??''));
  if($scheduleFieldPresent){
   if($scheduleRequested){
    $nextDateRaw=trim((string)($data['next_date']??''));$nextTimeRaw=trim((string)($data['next_time']??''));
    if($nextDateRaw===''||$nextTimeRaw==='')throw new RuntimeException('Informe a data e o horário do retorno.');
    $nextAt=$nextDateRaw.'T'.$nextTimeRaw;
   }else $nextAt='';
  }
  $returnNote=trim((string)($data['return_note']??''));if(mb_strlen($returnNote)>2000)throw new RuntimeException('A observação do retorno deve ter até 2.000 caracteres.');
  $nextDate=null;
  if($nextAt!==''){
   $nextDate=DateTime::createFromFormat('Y-m-d\TH:i',$nextAt);
   if(!$nextDate||$nextDate->format('Y-m-d\TH:i')!==$nextAt||$nextDate->getTimestamp()<time()-60)throw new RuntimeException('Informe uma data e hora futura válida para o retorno.');
  }
  $saleData=$type==='sale'?CommercialSaleService::normalize($data,$nextDate!==null):null;
  $completedTaskId=(int)($data['complete_task_id']??0);
  if($completedTaskId>0&&!DB::one("SELECT id FROM tasks WHERE id=? AND type='sales' AND status='pending' AND crm_account_code=?",[$completedTaskId,$accountCode]))throw new RuntimeException('O retorno informado já foi concluído ou não pertence a esta conta.');
  $pdo=DB::conn();$ownsTransaction=!$pdo->inTransaction();if($ownsTransaction)$pdo->beginTransaction();
  try{
  DB::exec("INSERT INTO activities(client_id,crm_account_code,user_id,activity_type,category_code,channel,result,outcome_code,notes,next_at,created_at)
            VALUES(?,?,?,?,?,?,?,?,?,?,NOW())",
   [$clientId?:null,$accountCode,(int)$user['id'],$type,$category!==''?$category:null,$channel,$outcome,$outcome,$notes!==''?$notes:null,$nextDate?$nextDate->format('Y-m-d H:i:00'):null]);
  $activityId=(int)DB::conn()->lastInsertId();
  if($saleData!==null)CommercialSaleService::attach($activityId,$accountCode,$clientId,(int)$user['id'],$saleData);

  $taskId=0;
  if($nextDate){
   $assignedId=(int)($data['assigned_user_id']??0);
   if(($user['role']??'')==='seller')$assignedId=(int)$user['id'];
   if($assignedId<=0){
    $assignedId=(int)(DB::scalar("SELECT id FROM users WHERE crm_user_omie_code=? AND active=1 AND role='seller' LIMIT 1",[(string)($account['crm_user_code']??'')])??0);
   }
   if($assignedId<=0)$assignedId=(int)$user['id'];
   $assigned=DB::one("SELECT id,name,role FROM users WHERE id=? AND active=1",[$assignedId]);
   if(!$assigned||!in_array((string)$assigned['role'],['seller','supervisor','admin'],true))throw new RuntimeException('Responsável do retorno inválido.');
   $title='Retorno · '.self::activityTypeLabel($type);
   if($category!=='')$title.=' · '.self::categoryLabel($category);
   if($returnNote!=='')$title='Retorno · '.$returnNote;
   DB::exec("INSERT INTO tasks(client_id,crm_account_code,assigned_user_id,created_by_user_id,type,task_type_code,source_activity_id,title,due_at,status,created_at,updated_at)
             VALUES(?,?,?,?,'sales','return',?,?,?,'pending',NOW(),NOW())",
    [$clientId?:null,$accountCode,$assignedId,(int)$user['id'],$activityId,$title,$nextDate->format('Y-m-d H:i:00')]);
   $taskId=(int)DB::conn()->lastInsertId();
  }

  if($completedTaskId>0){
   $changed=DB::exec("UPDATE tasks SET status='done',completion_result_code='contact_completed',completion_notes=?,completed_by_user_id=?,completed_at=NOW(),updated_at=NOW() WHERE id=? AND type='sales' AND status='pending' AND crm_account_code=?",
    [$notes!==''?$notes:'Retorno concluído com registro de atividade',(int)$user['id'],$completedTaskId,$accountCode]);
   if(!$changed)throw new RuntimeException('O retorno informado já foi concluído ou não pertence a esta conta.');
  }
  if($ownsTransaction)$pdo->commit();
  }catch(Throwable $e){if($ownsTransaction&&$pdo->inTransaction())$pdo->rollBack();throw $e;}

  return ['activity_id'=>$activityId,'task_id'=>$taskId,'completed_task_id'=>$completedTaskId,'account_code'=>$accountCode,'client_id'=>$clientId?:null,'activity_type'=>$type];
 }

 public static function addNote(string $accountCode,array $user,string $note): int{
  CommercialSchema::ensure();
  if(!CommercialAccountService::canWork($user,$accountCode))throw new RuntimeException('Esta Conta CRM não pertence à sua carteira operacional.');
  $note=trim($note);if($note==='')throw new RuntimeException('Digite uma observação.');if(mb_strlen($note)>10000)throw new RuntimeException('A observação deve ter até 10.000 caracteres.');
  $clientId=(int)(DB::scalar("SELECT client_id FROM crm_account_links WHERE crm_account_code=? LIMIT 1",[$accountCode])??0);
  DB::exec("INSERT INTO crm_account_notes(crm_account_code,client_id,user_id,note,created_at) VALUES(?,?,?,?,NOW())",[$accountCode,$clientId?:null,(int)$user['id'],$note]);
  return (int)DB::conn()->lastInsertId();
 }

 public static function assignableUsers(array $user): array{
  $role=(string)($user['role']??'');
  if($role==='seller')return DB::all("SELECT id,name,role FROM users WHERE id=? AND active=1",[(int)$user['id']]);
  $codes=CommercialPortfolioService::activeCrmSellerCodes();
  $where=["active=1","role IN('seller','supervisor','admin')"];$params=[];
  if($codes){
   $where[]="(role IN('supervisor','admin') OR crm_user_omie_code IN (".implode(',',array_fill(0,count($codes),'?'))."))";
   array_push($params,...$codes);
  }else $where[]="role IN('supervisor','admin')";
  return DB::all("SELECT id,name,role,crm_user_omie_code FROM users WHERE ".implode(' AND ',$where)." ORDER BY CASE WHEN id=? THEN 0 ELSE 1 END,FIELD(role,'seller','supervisor','admin'),name",array_merge($params,[(int)($user['id']??0)]));
 }

 public static function history(string $accountCode,int $limit=100): array{
  CommercialSchema::ensure();$limit=max(1,min(300,$limit));
  return DB::all("SELECT a.*,u.name user_name,
                         t.id return_task_id,t.status return_task_status,t.due_at return_due_at
                  FROM activities a
                  JOIN users u ON u.id=a.user_id
                  LEFT JOIN tasks t ON t.source_activity_id=a.id
                  WHERE a.crm_account_code=?
                  ORDER BY a.created_at DESC,a.id DESC LIMIT ".$limit,[$accountCode]);
 }

 public static function notes(string $accountCode,int $limit=100): array{
  CommercialSchema::ensure();$limit=max(1,min(300,$limit));
  return DB::all("SELECT n.*,u.name user_name FROM crm_account_notes n JOIN users u ON u.id=n.user_id WHERE n.crm_account_code=? ORDER BY n.created_at DESC,n.id DESC LIMIT ".$limit,[$accountCode]);
 }
}

final class CommercialSaleService {
 public static function types(): array{return ['material'=>'Material','ead'=>'EAD','material_ead'=>'Material + EAD'];}
 public static function conditions(): array{return ['cash'=>'À vista','invoice_30'=>'Boleto 30 dias','special'=>'Condição especial'];}
 private static function decimal(mixed $value): float{
  $value=trim((string)$value);if($value==='')return 0.0;
  $value=str_replace(['R$',' '],'',$value);if(str_contains($value,',')&&str_contains($value,'.'))$value=str_replace('.','',$value);
  return round((float)str_replace(',','.',$value),2);
 }
 public static function normalize(array $data,bool $hasReturn=false): array{
  $type=(string)($data['sale_type']??'');if(!isset(self::types()[$type]))throw new RuntimeException('Selecione o tipo da venda.');
  $amount=self::decimal($data['sale_amount']??$data['amount']??0);if($amount<=0)throw new RuntimeException('Informe um valor de venda maior que zero.');
  $discount=self::decimal($data['sale_discount']??$data['discount']??0);if($discount<0||$discount>$amount)throw new RuntimeException('O desconto da venda é inválido.');
  $condition=(string)($data['commercial_condition']??'');if(!isset(self::conditions()[$condition]))throw new RuntimeException('Selecione a condição comercial.');
  $details=trim((string)($data['negotiation_details']??$data['notes']??''));if(mb_strlen($details)>5000)throw new RuntimeException('Os detalhes da negociação devem ter até 5.000 caracteres.');
  $future=!empty($data['future_promise']);if($future&&!$hasReturn)throw new RuntimeException('Uma promessa de venda futura precisa de acompanhamento agendado.');
  $soldAt=trim((string)($data['sold_at']??''));
  if($soldAt==='')$soldAt=date('Y-m-d H:i:s');else{$date=DateTime::createFromFormat('Y-m-d\TH:i',$soldAt);if(!$date)throw new RuntimeException('Informe data e hora válidas para a venda.');$soldAt=$date->format('Y-m-d H:i:00');}
  return compact('type','amount','discount','condition','details','future','soldAt');
 }
 public static function attach(int $activityId,string $accountCode,int $clientId,int $userId,array $sale): int{
  DB::exec("INSERT INTO commercial_sales(activity_id,client_id,crm_account_code,user_id,sold_at,sale_type,amount,discount,commercial_condition,negotiation_details,future_promise,created_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,NOW())",[$activityId,$clientId?:null,$accountCode,$userId,$sale['soldAt'],$sale['type'],$sale['amount'],$sale['discount'],$sale['condition'],$sale['details']!==''?$sale['details']:null,$sale['future']?1:0]);
  return (int)DB::conn()->lastInsertId();
 }
 public static function listing(array $user,array $filters=[]): array{
  CommercialSchema::ensure();$where=['1=1'];$params=[];$role=(string)($user['role']??'');
  if($role==='seller'){$where[]='s.user_id=?';$params[]=(int)$user['id'];}
  $period=(string)($filters['period']??date('Y-m'));if(!preg_match('/^\d{4}-\d{2}$/',$period))$period=date('Y-m');
  $start=$period.'-01';$end=date('Y-m-d',strtotime($start.' +1 month'));$where[]='s.sold_at>=? AND s.sold_at<?';array_push($params,$start,$end);
  $seller=(int)($filters['seller_id']??0);if($seller>0&&$role!=='seller'){$where[]='s.user_id=?';$params[]=$seller;}
  $type=(string)($filters['sale_type']??'all');if(isset(self::types()[$type])){$where[]='s.sale_type=?';$params[]=$type;}else $type='all';
  $q=trim((string)($filters['q']??''));if($q!==''){$where[]='(a.name LIKE ? OR a.document LIKE ?)';$like='%'.$q.'%';array_push($params,$like,$like);}
  $sql=implode(' AND ',$where);
  $rows=DB::all("SELECT s.*,a.name account_name,a.trade_name,a.document,u.name user_name FROM commercial_sales s JOIN crm_accounts a ON a.omie_code=s.crm_account_code JOIN users u ON u.id=s.user_id WHERE ".$sql." ORDER BY s.sold_at DESC,s.id DESC LIMIT 500",$params);
  $summary=DB::one("SELECT COUNT(*) sale_count,COALESCE(SUM(s.amount),0) total_amount,COALESCE(SUM(CASE WHEN s.sale_type IN('material','material_ead') THEN s.amount ELSE 0 END),0) material_amount,COALESCE(SUM(CASE WHEN s.sale_type IN('ead','material_ead') THEN s.amount ELSE 0 END),0) ead_amount FROM commercial_sales s JOIN crm_accounts a ON a.omie_code=s.crm_account_code WHERE ".$sql,$params)??[];
  return ['rows'=>$rows,'summary'=>$summary,'filters'=>['period'=>$period,'seller_id'=>$seller,'sale_type'=>$type,'q'=>$q]];
 }
}

final class CommercialPartnerService {
 public static function catalog(): array{return [
  'access'=>['label'=>'Acesso','items'=>['access_guidance'=>'Orientação de acesso','has_access'=>'Possui acesso','knows_access'=>'Sabe acessar','access_recovery'=>'Recuperação de acesso','access_problem'=>'Problema de acesso']],
  'products'=>['label'=>'Produtos','items'=>['knows_products'=>'Conhece os produtos','product_difference'=>'Diferença entre produtos','which_product'=>'Qual produto oferecer','product_update'=>'Atualização de produto','commercial_guidance'=>'Orientação comercial']],
  'sales'=>['label'=>'Vendas','items'=>['knows_selling'=>'Sabe como vender','approach_guidance'=>'Orientação de abordagem','sales_questions'=>'Dúvidas sobre venda']],
  'campaigns'=>['label'=>'Campanhas','items'=>['campaign_presentation'=>'Apresentação de campanha','campaign_update'=>'Atualização de campanha','condition_explanation'=>'Explicação de condições','promotion'=>'Divulgação de promoção']],
  'materials'=>['label'=>'Materiais','items'=>['art_delivery'=>'Envio de artes','art_update'=>'Atualização de artes','commercial_material'=>'Envio de material comercial','material_guidance'=>'Orientação de uso dos materiais']],
  'activation'=>['label'=>'Ativação','items'=>['partner_activation'=>'Ativação de parceiro','partner_recovery'=>'Recuperação de parceiro','difficulty'=>'Identificação de dificuldade','partner_followup'=>'Acompanhamento de parceiro']],
 ];}
 public static function listing(array $user,array $filters=[]): array{
  CommercialSchema::ensure();$where=['a.active=1','p.is_reseller=1'];$params=[];
  if(($user['role']??'')==='seller'){$where[]='a.crm_user_code=?';$params[]=(string)($user['crm_user_omie_code']??'__NONE__');}
  $q=trim((string)($filters['q']??''));if($q!==''){$where[]='(a.name LIKE ? OR a.trade_name LIKE ? OR a.document LIKE ?)';$like='%'.$q.'%';array_push($params,$like,$like,$like);}
  $status=(string)($filters['status']??'all');$having='';if($status==='active')$having='HAVING days_without_work<=14';elseif($status==='activation')$having='HAVING days_without_work BETWEEN 15 AND 30';elseif($status==='reactivation')$having='HAVING days_without_work>30 OR last_work_at IS NULL';else $status='all';
  $rows=DB::all("SELECT a.omie_code,a.name,a.trade_name,a.document,l.client_id,p.is_cfc,p.is_reseller,cu.name owner_name,w.created_at last_work_at,w.description last_work_description,w.next_step,DATEDIFF(CURDATE(),DATE(w.created_at)) days_without_work FROM crm_accounts a JOIN crm_account_commercial_profiles p ON p.crm_account_code=a.omie_code LEFT JOIN crm_account_links l ON l.crm_account_code=a.omie_code LEFT JOIN crm_users cu ON cu.omie_code=a.crm_user_code LEFT JOIN commercial_partner_work w ON w.id=(SELECT MAX(w2.id) FROM commercial_partner_work w2 WHERE w2.crm_account_code=a.omie_code) WHERE ".implode(' AND ',$where)." ".$having." ORDER BY CASE WHEN w.created_at IS NULL THEN 0 ELSE 1 END,DATEDIFF(CURDATE(),DATE(w.created_at)) DESC,a.name LIMIT 500",$params);
  return ['rows'=>$rows,'filters'=>['q'=>$q,'status'=>$status]];
 }
 public static function record(string $accountCode,array $user,array $data): array{
  if(!CommercialAccountService::canWork($user,$accountCode))throw new RuntimeException('Este parceiro não pertence à carteira autorizada.');
  $account=CommercialAccountService::get($accountCode);if(!$account||empty($account['is_reseller']))throw new RuntimeException('A conta selecionada não está classificada como Revendedor.');
  $catalog=self::catalog();$items=$data['work_items']??[];if(!is_array($items))$items=[];$selected=[];
  foreach($items as $raw){[$category,$subtype]=array_pad(explode(':',(string)$raw,2),2,'');if(isset($catalog[$category]['items'][$subtype]))$selected[$category.':'.$subtype]=[$category,$subtype];}
  if(!$selected)throw new RuntimeException('Selecione ao menos um trabalho realizado.');
  $description=trim((string)($data['description']??''));if($description===''||mb_strlen($description)>5000)throw new RuntimeException('Descreva o trabalho realizado em até 5.000 caracteres.');
  $nextStep=trim((string)($data['next_step']??''));if(mb_strlen($nextStep)>500)throw new RuntimeException('O próximo passo deve ter até 500 caracteres.');
  $clientId=(int)($account['client_id']??0);$primary=array_values($selected)[0][0];$activityType=['activation'=>'activation','campaigns'=>'campaign','materials'=>'material','access'=>'access','products'=>'orientation','sales'=>'orientation'][$primary]??'follow_up';
  $due=null;if(!empty($data['schedule_return'])){$date=trim((string)($data['next_date']??''));$time=trim((string)($data['next_time']??''));$due=DateTime::createFromFormat('Y-m-d\TH:i',$date.'T'.$time);if(!$due||$due->getTimestamp()<time()-60)throw new RuntimeException('Informe data e hora futuras para o retorno.');}
  $pdo=DB::conn();$ownsTransaction=!$pdo->inTransaction();if($ownsTransaction)$pdo->beginTransaction();
  try{
  DB::exec("INSERT INTO activities(client_id,crm_account_code,user_id,activity_type,category_code,channel,result,outcome_code,notes,next_at,created_at) VALUES(?,?,?,?,?,'other','progress','progress',?,?,NOW())",[$clientId?:null,$accountCode,(int)$user['id'],$activityType,$primary,$description,$due?$due->format('Y-m-d H:i:00'):null]);
  $activityId=(int)DB::conn()->lastInsertId();DB::exec("INSERT INTO commercial_partner_work(activity_id,client_id,crm_account_code,user_id,description,next_step,created_at) VALUES(?,?,?,?,?,?,NOW())",[$activityId,$clientId?:null,$accountCode,(int)$user['id'],$description,$nextStep!==''?$nextStep:null]);$workId=(int)DB::conn()->lastInsertId();
  foreach($selected as [$category,$subtype])DB::exec("INSERT INTO commercial_partner_work_items(work_id,category_code,subtype_code) VALUES(?,?,?)",[$workId,$category,$subtype]);
  $taskId=0;if($due){$title=$nextStep!==''?$nextStep:'Acompanhar desenvolvimento do parceiro';DB::exec("INSERT INTO tasks(client_id,crm_account_code,assigned_user_id,created_by_user_id,type,task_type_code,source_activity_id,title,due_at,status,created_at,updated_at) VALUES(?,?,?,?,'sales','return',?,?,?,'pending',NOW(),NOW())",[$clientId?:null,$accountCode,(int)$user['id'],(int)$user['id'],$activityId,$title,$due->format('Y-m-d H:i:00')]);$taskId=(int)DB::conn()->lastInsertId();}
  if($ownsTransaction)$pdo->commit();
  }catch(Throwable $e){if($ownsTransaction&&$pdo->inTransaction())$pdo->rollBack();throw $e;}
  return compact('workId','activityId','taskId');
 }
}

final class CommercialManagementService {
 public static function build(array $filters=[]): array{
  CommercialSchema::ensure();$period=(string)($filters['period']??'month');$today=date('Y-m-d');
  if($period==='today'){$start=$today;$end=date('Y-m-d',strtotime($today.' +1 day'));}
  elseif($period==='yesterday'){$start=date('Y-m-d',strtotime($today.' -1 day'));$end=$today;}
  elseif($period==='week'){$start=date('Y-m-d',strtotime('monday this week'));$end=date('Y-m-d',strtotime($start.' +7 days'));}
  elseif($period==='custom'){$start=(string)($filters['start']??$today);$end=date('Y-m-d',strtotime((string)($filters['end']??$today).' +1 day'));}
  else{$period='month';$start=date('Y-m-01');$end=date('Y-m-d',strtotime($start.' +1 month'));}
  $seller=(int)($filters['seller_id']??0);$activity=(string)($filters['activity_type']??'all');$channel=(string)($filters['channel']??'all');$saleType=(string)($filters['sale_type']??'all');$classification=(string)($filters['classification']??'all');
  $aw=['a.created_at>=?','a.created_at<?'];$ap=[$start,$end];if($seller>0){$aw[]='a.user_id=?';$ap[]=$seller;}if($activity!=='all'){$aw[]='a.activity_type=?';$ap[]=$activity;}if($channel!=='all'){$aw[]='a.channel=?';$ap[]=$channel;}
  if($classification==='cfc')$aw[]='p.is_cfc=1';elseif($classification==='reseller')$aw[]='p.is_reseller=1';elseif($classification==='both')$aw[]='p.is_cfc=1 AND p.is_reseller=1';
  $activityJoin=" FROM activities a JOIN users u ON u.id=a.user_id LEFT JOIN crm_account_commercial_profiles p ON p.crm_account_code=a.crm_account_code WHERE ".implode(' AND ',$aw);
  $metrics=DB::one("SELECT COUNT(*) activities,COALESCE(SUM(a.activity_type='contact_attempt'),0) attempts,COALESCE(SUM(a.activity_type='contact_completed'),0) contacts,COALESCE(SUM(a.activity_type='follow_up'),0) followups,COALESCE(SUM(a.activity_type='orientation'),0) orientations,COALESCE(SUM(a.activity_type='activation'),0) activations".$activityJoin,$ap)??[];
  $sw=['s.sold_at>=?','s.sold_at<?'];$sp=[$start,$end];if($seller>0){$sw[]='s.user_id=?';$sp[]=$seller;}if(isset(CommercialSaleService::types()[$saleType])){$sw[]='s.sale_type=?';$sp[]=$saleType;}
  if($classification==='cfc')$sw[]='sp.is_cfc=1';elseif($classification==='reseller')$sw[]='sp.is_reseller=1';elseif($classification==='both')$sw[]='sp.is_cfc=1 AND sp.is_reseller=1';
  if($activity!=='all'&&$activity!=='sale')$sw[]='1=0';if($channel!=='all'){$sw[]='sa.channel=?';$sp[]=$channel;}
  $saleFrom=" FROM commercial_sales s JOIN activities sa ON sa.id=s.activity_id LEFT JOIN crm_account_commercial_profiles sp ON sp.crm_account_code=s.crm_account_code WHERE ".implode(' AND ',$sw);
  $sales=DB::one("SELECT COUNT(*) sales,COALESCE(SUM(s.amount),0) sales_amount,COALESCE(SUM(CASE WHEN s.sale_type IN('ead','material_ead') THEN s.amount ELSE 0 END),0) ead_amount".$saleFrom,$sp)??[];
  $taskSql=$seller>0?' AND assigned_user_id=?':'';$taskParams=[$start,$end];if($seller>0)$taskParams[]=$seller;
  $tasks=DB::one("SELECT COALESCE(SUM(status='pending' AND due_at<CURDATE()),0) overdue,COALESCE(SUM(status='pending'),0) pending,COALESCE(SUM(status='done' AND completed_at>=? AND completed_at<?),0) completed FROM tasks WHERE type='sales'".$taskSql,$taskParams)??[];
  $partnerSql=$seller>0?' AND user_id=?':'';$partnerParams=[$start,$end];if($seller>0)$partnerParams[]=$seller;
  $partnersWorked=(int)(DB::scalar("SELECT COUNT(DISTINCT crm_account_code) FROM commercial_partner_work WHERE created_at>=? AND created_at<?".$partnerSql,$partnerParams)??0);
  $partnersStale=(int)(DB::scalar("SELECT COUNT(*) FROM crm_account_commercial_profiles p JOIN crm_accounts ca ON ca.omie_code=p.crm_account_code LEFT JOIN (SELECT crm_account_code,MAX(created_at) last_at FROM commercial_partner_work GROUP BY crm_account_code) w ON w.crm_account_code=ca.omie_code WHERE p.is_reseller=1 AND ca.active=1 AND (w.last_at IS NULL OR w.last_at<CURDATE()-INTERVAL 30 DAY)")??0);
  $rows=DB::all("SELECT u.id,u.name,COALESCE(SUM(a.activity_type='contact_completed'),0) contacts,COALESCE(SUM(a.activity_type='contact_attempt'),0) attempts,COALESCE(SUM(a.activity_type='follow_up'),0) followups,COALESCE(SUM(a.activity_type='orientation'),0) orientations,COALESCE(SUM(a.activity_type='activation'),0) activations,(SELECT COUNT(*) FROM commercial_sales s WHERE s.user_id=u.id AND s.sold_at>=? AND s.sold_at<?) sales,(SELECT COALESCE(SUM(s.amount),0) FROM commercial_sales s WHERE s.user_id=u.id AND s.sold_at>=? AND s.sold_at<?) sales_amount FROM users u LEFT JOIN activities a ON a.user_id=u.id AND a.created_at>=? AND a.created_at<? WHERE u.active=1 AND u.role='seller' ".($seller>0?'AND u.id='.(int)$seller:'')." GROUP BY u.id,u.name ORDER BY u.name",[$start,$end,$start,$end,$start,$end]);
  $day=(string)($filters['day']??$today);if(!preg_match('/^\d{4}-\d{2}-\d{2}$/',$day))$day=$today;$daySeller=(int)($filters['day_seller_id']??$seller);$dw=['a.created_at>=?','a.created_at<?'];$dp=[$day,date('Y-m-d',strtotime($day.' +1 day'))];if($daySeller>0){$dw[]='a.user_id=?';$dp[]=$daySeller;}
  $daily=DB::all("SELECT a.*,u.name user_name,COALESCE(ca.trade_name,ca.name,'Conta') account_name FROM activities a JOIN users u ON u.id=a.user_id LEFT JOIN crm_accounts ca ON ca.omie_code=a.crm_account_code WHERE ".implode(' AND ',$dw)." ORDER BY a.created_at",$dp);
  return ['filters'=>compact('period','start','end','seller','activity','channel','saleType','classification','day','daySeller'),'metrics'=>array_merge($metrics,$sales,['overdue'=>(int)($tasks['overdue']??0),'pending'=>(int)($tasks['pending']??0),'completed'=>(int)($tasks['completed']??0),'partners_worked'=>$partnersWorked,'partners_stale'=>$partnersStale]),'rows'=>$rows,'daily'=>$daily];
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
  $previousNotes=trim((string)($old['strategic_notes']??''));
  if($previousNotes!==trim($notes)){
   DB::exec("INSERT INTO client_commercial_audit(client_id,actor_user_id,field_name,previous_value,new_value,source,sync_status,synced_at,created_at)
             VALUES(?,?,'strategic_notes',?,?,'tecnodata','ignored',NOW(),NOW())",
    [$clientId,$actorUserId?:null,$previousNotes!==''?$previousNotes:null,$notes!==''?$notes:null]);
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
    }elseif($row['entity_type']==='crm_account_classification'){
     DB::exec("UPDATE crm_account_commercial_audit SET sync_status='synced',synced_at=NOW(),sync_error=NULL WHERE crm_account_code=? AND field_name='classification' AND sync_status IN('pending','error')",[(string)$row['entity_id']]);
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
    }elseif($row['entity_type']==='crm_account_classification'){
     DB::exec("UPDATE crm_account_commercial_audit SET sync_status='error',sync_error=? WHERE crm_account_code=? AND field_name='classification' AND sync_status='pending'",
      [mb_substr($e->getMessage(),0,4000),(string)$row['entity_id']]);
    }
    $errors++;
   }
  }
  return ['processed'=>count($rows),'synced'=>$done,'errors'=>$errors,'remaining'=>(int)(DB::scalar("SELECT COUNT(*) FROM sync_outbox WHERE status IN('pending','error')")??0)];
 }

 private static function pushClassification(array $payload): void{
  $accountCode=(int)($payload['crm_account_code']??0);
  if($accountCode<=0){
   $clientId=(int)($payload['client_id']??0);
   $client=DB::one("SELECT id,crm_account_code FROM clients WHERE id=? AND active=1 AND crm_inactive=0",[$clientId]);
   if(!$client||empty($client['crm_account_code']))throw new RuntimeException('Cliente ainda não possui Conta CRM Omie vinculada.');
   $accountCode=(int)$client['crm_account_code'];
  }
  if($accountCode<=0||!DB::one("SELECT 1 FROM crm_accounts WHERE omie_code=? AND active=1",[(string)$accountCode]))throw new RuntimeException('Código da Conta CRM inválido.');

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
