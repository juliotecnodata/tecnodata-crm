CREATE TABLE IF NOT EXISTS users(id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,name VARCHAR(120) NOT NULL,email VARCHAR(190) NOT NULL,password_hash VARCHAR(255) NOT NULL,role ENUM('admin','supervisor','seller','collector') NOT NULL,seller_omie_code VARCHAR(80) NULL,crm_user_omie_code VARCHAR(80) NULL,active TINYINT(1) NOT NULL DEFAULT 1,last_login_at DATETIME NULL,created_at DATETIME NOT NULL,updated_at DATETIME NOT NULL,UNIQUE KEY uq_users_email(email),INDEX idx_users_crm_user(crm_user_omie_code,active));
CREATE TABLE IF NOT EXISTS sellers(omie_code VARCHAR(80) PRIMARY KEY,name VARCHAR(160) NOT NULL,email VARCHAR(190) NULL,active TINYINT(1) NOT NULL DEFAULT 1,raw_json JSON NULL,updated_at DATETIME NOT NULL);
CREATE TABLE IF NOT EXISTS crm_users(omie_code VARCHAR(80) PRIMARY KEY,name VARCHAR(160) NOT NULL,email VARCHAR(190) NULL,phone VARCHAR(45) NULL,mobile VARCHAR(45) NULL,annual_goal DECIMAL(15,2) NOT NULL DEFAULT 0,active TINYINT(1) NOT NULL DEFAULT 1,raw_json JSON NULL,last_seen_token VARCHAR(64) NULL,updated_at DATETIME NOT NULL,INDEX idx_crm_users_email(email),INDEX idx_crm_users_active(active,name));
CREATE TABLE IF NOT EXISTS crm_accounts(omie_code VARCHAR(80) PRIMARY KEY,integration_code VARCHAR(100) NULL,name VARCHAR(190) NOT NULL,trade_name VARCHAR(190) NULL,document VARCHAR(30) NULL,crm_user_code VARCHAR(80) NULL,vertical_code VARCHAR(80) NULL,telemarketing_code VARCHAR(80) NULL,notes TEXT NULL,active TINYINT(1) NOT NULL DEFAULT 1,raw_json JSON NULL,last_seen_token VARCHAR(64) NULL,updated_at DATETIME NOT NULL,INDEX idx_crm_accounts_document(document),INDEX idx_crm_accounts_user(crm_user_code),INDEX idx_crm_accounts_integration(integration_code));
CREATE TABLE IF NOT EXISTS clients(id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,omie_code VARCHAR(80) NOT NULL,name VARCHAR(190) NOT NULL,legal_name VARCHAR(190) NULL,document VARCHAR(30) NULL,email VARCHAR(1000) NULL,phone VARCHAR(40) NULL,city VARCHAR(100) NULL,uf CHAR(2) NULL,seller_omie_code VARCHAR(80) NULL,omie_seller_code VARCHAR(80) NULL,crm_account_code VARCHAR(80) NULL,crm_owner_omie_code VARCHAR(80) NULL,crm_owner_user_id INT UNSIGNED NULL,portfolio_locked TINYINT(1) NOT NULL DEFAULT 0,active TINYINT(1) NOT NULL DEFAULT 1,crm_inactive TINYINT(1) NOT NULL DEFAULT 0,crm_inactivated_at DATETIME NULL,crm_inactivated_by INT UNSIGNED NULL,created_at DATETIME NULL,omie_created_at DATETIME NULL,raw_json JSON NULL,updated_at DATETIME NOT NULL,UNIQUE KEY uq_clients_omie(omie_code),INDEX idx_clients_name(name),INDEX idx_clients_seller(seller_omie_code,active),INDEX idx_clients_omie_seller(omie_seller_code,active),INDEX idx_clients_crm_owner(crm_owner_user_id,active,crm_inactive),INDEX idx_clients_crm_account(crm_account_code),INDEX idx_clients_active(active,id),INDEX idx_clients_active_uf(active,uf),INDEX idx_clients_crm_active(crm_inactive,active,id));

CREATE TABLE IF NOT EXISTS crm_account_links(crm_account_code VARCHAR(80) PRIMARY KEY,client_id BIGINT UNSIGNED NOT NULL,link_method VARCHAR(30) NOT NULL DEFAULT 'document',confidence TINYINT UNSIGNED NOT NULL DEFAULT 100,is_primary TINYINT(1) NOT NULL DEFAULT 1,verified_by_user_id INT UNSIGNED NULL,verified_at DATETIME NULL,updated_at DATETIME NOT NULL,FOREIGN KEY(client_id) REFERENCES clients(id) ON DELETE CASCADE,FOREIGN KEY(verified_by_user_id) REFERENCES users(id) ON DELETE SET NULL,INDEX idx_crm_links_client(client_id,is_primary));
CREATE TABLE IF NOT EXISTS crm_account_link_audit(id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,crm_account_code VARCHAR(80) NOT NULL,previous_client_id BIGINT UNSIGNED NULL,client_id BIGINT UNSIGNED NULL,action VARCHAR(30) NOT NULL,link_method VARCHAR(30) NOT NULL,actor_user_id INT UNSIGNED NULL,notes VARCHAR(500) NULL,created_at DATETIME NOT NULL,FOREIGN KEY(previous_client_id) REFERENCES clients(id) ON DELETE SET NULL,FOREIGN KEY(client_id) REFERENCES clients(id) ON DELETE SET NULL,FOREIGN KEY(actor_user_id) REFERENCES users(id) ON DELETE SET NULL,INDEX idx_crm_link_audit_account(crm_account_code,created_at),INDEX idx_crm_link_audit_client(client_id,created_at));
CREATE TABLE IF NOT EXISTS crm_account_commercial_profiles(crm_account_code VARCHAR(80) PRIMARY KEY,is_cfc TINYINT(1) NOT NULL DEFAULT 0,is_reseller TINYINT(1) NOT NULL DEFAULT 0,strategic_notes TEXT NULL,classification_source VARCHAR(30) NOT NULL DEFAULT 'local',updated_by_user_id INT UNSIGNED NULL,updated_at DATETIME NOT NULL,FOREIGN KEY(updated_by_user_id) REFERENCES users(id) ON DELETE SET NULL,INDEX idx_crm_account_profile_class(is_cfc,is_reseller));
CREATE TABLE IF NOT EXISTS crm_account_commercial_audit(id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,crm_account_code VARCHAR(80) NOT NULL,client_id BIGINT UNSIGNED NULL,actor_user_id INT UNSIGNED NULL,field_name VARCHAR(80) NOT NULL,previous_value TEXT NULL,new_value TEXT NULL,source VARCHAR(30) NOT NULL DEFAULT 'tecnodata',sync_status ENUM('pending','synced','error','ignored') NOT NULL DEFAULT 'pending',synced_at DATETIME NULL,sync_error TEXT NULL,created_at DATETIME NOT NULL,FOREIGN KEY(client_id) REFERENCES clients(id) ON DELETE SET NULL,FOREIGN KEY(actor_user_id) REFERENCES users(id) ON DELETE SET NULL,INDEX idx_crm_account_audit_account(crm_account_code,created_at),INDEX idx_crm_account_audit_sync(sync_status,created_at));
CREATE TABLE IF NOT EXISTS crm_account_notes(id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,crm_account_code VARCHAR(80) NOT NULL,client_id BIGINT UNSIGNED NULL,user_id INT UNSIGNED NOT NULL,note TEXT NOT NULL,created_at DATETIME NOT NULL,updated_at DATETIME NULL,FOREIGN KEY(client_id) REFERENCES clients(id) ON DELETE SET NULL,FOREIGN KEY(user_id) REFERENCES users(id),INDEX idx_account_notes_account_date(crm_account_code,created_at,id));
CREATE TABLE IF NOT EXISTS crm_contacts(omie_code VARCHAR(80) PRIMARY KEY,integration_code VARCHAR(100) NULL,crm_account_code VARCHAR(80) NOT NULL,crm_user_code VARCHAR(80) NULL,name VARCHAR(120) NULL,last_name VARCHAR(120) NULL,position_name VARCHAR(120) NULL,email VARCHAR(200) NULL,phone VARCHAR(40) NULL,mobile VARCHAR(40) NULL,raw_json JSON NULL,updated_at DATETIME NOT NULL,INDEX idx_crm_contacts_account(crm_account_code),INDEX idx_crm_contacts_email(email));
CREATE TABLE IF NOT EXISTS user_omie_identity(user_id INT UNSIGNED PRIMARY KEY,sales_seller_code VARCHAR(80) NULL,crm_user_code VARCHAR(80) NULL,match_method VARCHAR(30) NOT NULL DEFAULT 'manual',confidence TINYINT UNSIGNED NOT NULL DEFAULT 100,verified_at DATETIME NULL,updated_at DATETIME NOT NULL,FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,INDEX idx_user_identity_sales(sales_seller_code),INDEX idx_user_identity_crm(crm_user_code));
CREATE TABLE IF NOT EXISTS client_commercial_profiles(client_id BIGINT UNSIGNED PRIMARY KEY,is_cfc TINYINT(1) NOT NULL DEFAULT 0,is_reseller TINYINT(1) NOT NULL DEFAULT 0,strategic_notes TEXT NULL,classification_source VARCHAR(30) NOT NULL DEFAULT 'local',updated_by_user_id INT UNSIGNED NULL,updated_at DATETIME NOT NULL,FOREIGN KEY(client_id) REFERENCES clients(id) ON DELETE CASCADE,FOREIGN KEY(updated_by_user_id) REFERENCES users(id) ON DELETE SET NULL,INDEX idx_client_profile_class(is_cfc,is_reseller));
CREATE TABLE IF NOT EXISTS client_commercial_audit(id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,client_id BIGINT UNSIGNED NOT NULL,actor_user_id INT UNSIGNED NULL,field_name VARCHAR(80) NOT NULL,previous_value TEXT NULL,new_value TEXT NULL,source VARCHAR(30) NOT NULL DEFAULT 'tecnodata',sync_status ENUM('pending','synced','error','ignored') NOT NULL DEFAULT 'pending',synced_at DATETIME NULL,sync_error TEXT NULL,created_at DATETIME NOT NULL,FOREIGN KEY(client_id) REFERENCES clients(id) ON DELETE CASCADE,FOREIGN KEY(actor_user_id) REFERENCES users(id) ON DELETE SET NULL,INDEX idx_commercial_audit_client(client_id,created_at),INDEX idx_commercial_audit_sync(sync_status,created_at));
CREATE TABLE IF NOT EXISTS sync_outbox(id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,entity_type VARCHAR(50) NOT NULL,entity_id VARCHAR(100) NOT NULL,operation VARCHAR(50) NOT NULL,payload_json JSON NOT NULL,status ENUM('pending','processing','synced','error','ignored') NOT NULL DEFAULT 'pending',attempts INT UNSIGNED NOT NULL DEFAULT 0,last_error TEXT NULL,next_attempt_at DATETIME NULL,created_at DATETIME NOT NULL,updated_at DATETIME NOT NULL,synced_at DATETIME NULL,INDEX idx_sync_outbox_entity(entity_type,entity_id,operation,status),INDEX idx_sync_outbox_status(status,next_attempt_at,id));
CREATE TABLE IF NOT EXISTS client_tags(client_id BIGINT UNSIGNED NOT NULL,tag_key VARCHAR(190) NOT NULL,tag VARCHAR(190) NOT NULL,PRIMARY KEY(client_id,tag_key),INDEX idx_client_tags_key(tag_key,client_id),FOREIGN KEY(client_id) REFERENCES clients(id) ON DELETE CASCADE);
CREATE TABLE IF NOT EXISTS client_portfolio_assignments(id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,month_ref CHAR(7) NOT NULL,client_id BIGINT UNSIGNED NOT NULL,seller_omie_code VARCHAR(80) NULL,created_by INT UNSIGNED NULL,updated_by INT UNSIGNED NULL,created_at DATETIME NOT NULL,updated_at DATETIME NOT NULL,UNIQUE KEY uq_client_portfolio_month(month_ref,client_id),INDEX idx_portfolio_month_seller(month_ref,seller_omie_code),INDEX idx_portfolio_client_month(client_id,month_ref),FOREIGN KEY(client_id) REFERENCES clients(id) ON DELETE CASCADE);
CREATE TABLE IF NOT EXISTS client_seller_audit(id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,client_id BIGINT UNSIGNED NOT NULL,actor_user_id INT UNSIGNED NULL,change_type VARCHAR(40) NOT NULL,month_ref CHAR(7) NULL,previous_seller_omie_code VARCHAR(80) NULL,new_seller_omie_code VARCHAR(80) NULL,previous_omie_seller_code VARCHAR(80) NULL,new_omie_seller_code VARCHAR(80) NULL,notes VARCHAR(255) NULL,created_at DATETIME NOT NULL,INDEX idx_client_seller_audit_client(client_id,created_at),INDEX idx_client_seller_audit_month(month_ref,created_at),FOREIGN KEY(client_id) REFERENCES clients(id) ON DELETE CASCADE,FOREIGN KEY(actor_user_id) REFERENCES users(id) ON DELETE SET NULL);
CREATE TABLE IF NOT EXISTS client_metrics(client_id BIGINT UNSIGNED PRIMARY KEY,first_purchase_at DATE NULL,last_purchase_at DATE NULL,revenue_12m DECIMAL(15,2) NOT NULL DEFAULT 0,orders_12m INT NOT NULL DEFAULT 0,avg_ticket_12m DECIMAL(15,2) NOT NULL DEFAULT 0,avg_interval_days DECIMAL(10,2) NULL,updated_at DATETIME NOT NULL,FOREIGN KEY(client_id) REFERENCES clients(id) ON DELETE CASCADE);
CREATE TABLE IF NOT EXISTS products(id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,omie_code VARCHAR(80) NOT NULL,sku VARCHAR(120) NULL,description VARCHAR(255) NOT NULL,unit VARCHAR(20) NULL,ncm VARCHAR(30) NULL,unit_price DECIMAL(15,4) NOT NULL DEFAULT 0,stock_qty DECIMAL(15,4) NULL,active TINYINT(1) NOT NULL DEFAULT 1,raw_json JSON NULL,updated_at DATETIME NOT NULL,UNIQUE KEY uq_products_omie(omie_code),INDEX idx_products_description(description));
CREATE TABLE IF NOT EXISTS categories(code VARCHAR(80) PRIMARY KEY,description VARCHAR(255) NOT NULL,active TINYINT(1) NOT NULL DEFAULT 1,raw_json JSON NULL,updated_at DATETIME NOT NULL);
CREATE TABLE IF NOT EXISTS departments(code VARCHAR(80) PRIMARY KEY,description VARCHAR(255) NOT NULL,structure VARCHAR(255) NULL,active TINYINT(1) NOT NULL DEFAULT 1,raw_json JSON NULL,updated_at DATETIME NOT NULL);
CREATE TABLE IF NOT EXISTS financial_accounts(omie_code VARCHAR(80) PRIMARY KEY,name VARCHAR(160) NOT NULL,account_type VARCHAR(10) NULL,active TINYINT(1) NOT NULL DEFAULT 1,selected TINYINT(1) NOT NULL DEFAULT 0,raw_json JSON NULL,updated_at DATETIME NOT NULL);
CREATE TABLE IF NOT EXISTS order_stages(code VARCHAR(10) PRIMARY KEY,name VARCHAR(120) NOT NULL,active TINYINT(1) NOT NULL DEFAULT 1,raw_json JSON NULL,updated_at DATETIME NOT NULL);
CREATE TABLE IF NOT EXISTS payment_terms(code VARCHAR(3) PRIMARY KEY,description VARCHAR(120) NOT NULL,installments INT NOT NULL DEFAULT 0,days_list VARCHAR(120) NULL,active TINYINT(1) NOT NULL DEFAULT 1,raw_json JSON NULL,updated_at DATETIME NOT NULL);
CREATE TABLE IF NOT EXISTS tax_scenarios(omie_code VARCHAR(80) PRIMARY KEY,name VARCHAR(120) NOT NULL,is_default TINYINT(1) NOT NULL DEFAULT 0,active TINYINT(1) NOT NULL DEFAULT 1,raw_json JSON NULL,updated_at DATETIME NOT NULL);
CREATE TABLE IF NOT EXISTS stock_locations(omie_code VARCHAR(80) PRIMARY KEY,name VARCHAR(250) NOT NULL,sale_enabled TINYINT(1) NOT NULL DEFAULT 0,is_default TINYINT(1) NOT NULL DEFAULT 0,active TINYINT(1) NOT NULL DEFAULT 1,raw_json JSON NULL,updated_at DATETIME NOT NULL);
CREATE TABLE IF NOT EXISTS payment_methods(code VARCHAR(4) PRIMARY KEY,description VARCHAR(100) NOT NULL,raw_json JSON NULL,updated_at DATETIME NOT NULL);
CREATE TABLE IF NOT EXISTS document_types(code VARCHAR(8) PRIMARY KEY,description VARCHAR(100) NOT NULL,raw_json JSON NULL,updated_at DATETIME NOT NULL);
CREATE TABLE IF NOT EXISTS orders(id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,omie_code VARCHAR(80) NOT NULL,number VARCHAR(30) NULL,client_omie_code VARCHAR(80) NULL,seller_omie_code VARCHAR(80) NULL,order_date DATE NULL,forecast_date DATE NULL,total DECIMAL(15,2) NOT NULL DEFAULT 0,status VARCHAR(30) NULL,stage_code VARCHAR(10) NULL,raw_json JSON NULL,updated_at DATETIME NOT NULL,UNIQUE KEY uq_orders_omie(omie_code),INDEX idx_orders_date(order_date),INDEX idx_orders_seller_date(seller_omie_code,order_date),INDEX idx_orders_client_date(client_omie_code,order_date));
CREATE TABLE IF NOT EXISTS local_order_drafts(
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 request_token VARCHAR(80) NOT NULL,
 created_by INT UNSIGNED NOT NULL,
 client_id BIGINT UNSIGNED NULL,
 seller_omie_code VARCHAR(80) NULL,
 status ENUM('draft','sent') NOT NULL DEFAULT 'draft',
 total DECIMAL(15,2) NOT NULL DEFAULT 0,
 form_json JSON NOT NULL,
 omie_code VARCHAR(80) NULL,
 omie_number VARCHAR(30) NULL,
 created_at DATETIME NOT NULL,
 updated_at DATETIME NOT NULL,
 sent_at DATETIME NULL,
 UNIQUE KEY uq_local_order_draft_token(request_token),
 INDEX idx_local_order_draft_user_status(created_by,status),
 INDEX idx_local_order_draft_updated(updated_at)
);
CREATE TABLE IF NOT EXISTS service_orders(id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,omie_code VARCHAR(80) NOT NULL,client_omie_code VARCHAR(80) NULL,seller_omie_code VARCHAR(80) NULL,service_date DATE NULL,total DECIMAL(15,2) NOT NULL DEFAULT 0,status VARCHAR(40) NULL,raw_json JSON NULL,updated_at DATETIME NOT NULL,UNIQUE KEY uq_service_orders_omie(omie_code),INDEX idx_service_orders_date(service_date),INDEX idx_service_orders_seller_date(seller_omie_code,service_date),INDEX idx_service_orders_client_date(client_omie_code,service_date));
CREATE TABLE IF NOT EXISTS financial_movements(id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,omie_code VARCHAR(100) NOT NULL,client_omie_code VARCHAR(80) NULL,account_omie_code VARCHAR(80) NULL,seller_omie_code VARCHAR(80) NULL,due_date DATE NULL,open_amount DECIMAL(15,2) NOT NULL DEFAULT 0,paid_amount DECIMAL(15,2) NOT NULL DEFAULT 0,status VARCHAR(30) NOT NULL,last_seen_token VARCHAR(64) NULL,raw_json JSON NULL,updated_at DATETIME NOT NULL,UNIQUE KEY uq_fin_omie(omie_code),INDEX idx_fin_client_status(client_omie_code,status),INDEX idx_fin_seen(last_seen_token));
CREATE TABLE IF NOT EXISTS activities(id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,client_id BIGINT UNSIGNED NULL,crm_account_code VARCHAR(80) NULL,user_id INT UNSIGNED NOT NULL,activity_type VARCHAR(30) NOT NULL DEFAULT 'contact_completed',category_code VARCHAR(50) NULL,channel VARCHAR(30) NOT NULL,result VARCHAR(40) NOT NULL,outcome_code VARCHAR(40) NULL,notes TEXT NULL,next_at DATETIME NULL,created_at DATETIME NOT NULL,FOREIGN KEY(client_id) REFERENCES clients(id) ON DELETE CASCADE,FOREIGN KEY(user_id) REFERENCES users(id),INDEX idx_activities_client_date(client_id,created_at,id),INDEX idx_activities_account_date(crm_account_code,created_at,id),INDEX idx_activities_account_type(crm_account_code,activity_type,created_at));
CREATE TABLE IF NOT EXISTS commercial_sales(id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,activity_id BIGINT UNSIGNED NOT NULL,client_id BIGINT UNSIGNED NULL,crm_account_code VARCHAR(80) NOT NULL,user_id INT UNSIGNED NOT NULL,sold_at DATETIME NOT NULL,sale_type VARCHAR(30) NOT NULL,amount DECIMAL(15,2) NOT NULL DEFAULT 0,discount DECIMAL(15,2) NOT NULL DEFAULT 0,commercial_condition VARCHAR(40) NOT NULL,negotiation_details TEXT NULL,future_promise TINYINT(1) NOT NULL DEFAULT 0,created_at DATETIME NOT NULL,UNIQUE KEY uq_commercial_sales_activity(activity_id),INDEX idx_commercial_sales_date_user(sold_at,user_id),INDEX idx_commercial_sales_account(crm_account_code,sold_at),FOREIGN KEY(activity_id) REFERENCES activities(id) ON DELETE CASCADE,FOREIGN KEY(client_id) REFERENCES clients(id) ON DELETE SET NULL,FOREIGN KEY(user_id) REFERENCES users(id));
CREATE TABLE IF NOT EXISTS commercial_partner_work(id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,activity_id BIGINT UNSIGNED NOT NULL,client_id BIGINT UNSIGNED NULL,crm_account_code VARCHAR(80) NOT NULL,user_id INT UNSIGNED NOT NULL,description TEXT NOT NULL,next_step VARCHAR(500) NULL,created_at DATETIME NOT NULL,UNIQUE KEY uq_partner_work_activity(activity_id),INDEX idx_partner_work_account_date(crm_account_code,created_at),INDEX idx_partner_work_user_date(user_id,created_at),FOREIGN KEY(activity_id) REFERENCES activities(id) ON DELETE CASCADE,FOREIGN KEY(client_id) REFERENCES clients(id) ON DELETE SET NULL,FOREIGN KEY(user_id) REFERENCES users(id));
CREATE TABLE IF NOT EXISTS commercial_partner_work_items(id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,work_id BIGINT UNSIGNED NOT NULL,category_code VARCHAR(40) NOT NULL,subtype_code VARCHAR(80) NOT NULL,INDEX idx_partner_work_items_work(work_id),INDEX idx_partner_work_items_category(category_code,subtype_code),FOREIGN KEY(work_id) REFERENCES commercial_partner_work(id) ON DELETE CASCADE);
CREATE TABLE IF NOT EXISTS tasks(id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,client_id BIGINT UNSIGNED NULL,crm_account_code VARCHAR(80) NULL,assigned_user_id INT UNSIGNED NOT NULL,created_by_user_id INT UNSIGNED NULL,type ENUM('sales','collection') NOT NULL,task_type_code VARCHAR(50) NULL,source_activity_id BIGINT UNSIGNED NULL,title TEXT NOT NULL,due_at DATETIME NOT NULL,status ENUM('pending','done','cancelled') NOT NULL DEFAULT 'pending',completion_result_code VARCHAR(40) NULL,completion_notes TEXT NULL,completed_by_user_id INT UNSIGNED NULL,created_at DATETIME NOT NULL,completed_at DATETIME NULL,updated_at DATETIME NULL,FOREIGN KEY(client_id) REFERENCES clients(id) ON DELETE CASCADE,FOREIGN KEY(assigned_user_id) REFERENCES users(id),INDEX idx_tasks_user_status_date(assigned_user_id,status,due_at),INDEX idx_tasks_client_status_due(client_id,status,type,due_at,id),INDEX idx_tasks_account_status_due(crm_account_code,status,due_at,id),INDEX idx_tasks_status_due(status,due_at),INDEX idx_tasks_created_user_type(created_at,assigned_user_id,type,status),INDEX idx_tasks_task_type(task_type_code),INDEX idx_tasks_source_activity(source_activity_id));
CREATE TABLE IF NOT EXISTS collection_cases(client_id BIGINT UNSIGNED PRIMARY KEY,open_amount DECIMAL(15,2) NOT NULL DEFAULT 0,partial_paid DECIMAL(15,2) NOT NULL DEFAULT 0,max_overdue_days INT NOT NULL DEFAULT 0,status ENUM('open','settled') NOT NULL DEFAULT 'open',assigned_user_id INT UNSIGNED NULL,assigned_at DATETIME NULL,updated_at DATETIME NOT NULL,FOREIGN KEY(client_id) REFERENCES clients(id) ON DELETE CASCADE,FOREIGN KEY(assigned_user_id) REFERENCES users(id) ON DELETE SET NULL,INDEX idx_collection_status_assigned_delay(status,assigned_user_id,max_overdue_days));
CREATE TABLE IF NOT EXISTS collection_actions(id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,client_id BIGINT UNSIGNED NOT NULL,author_user_id INT UNSIGNED NOT NULL,assigned_user_id INT UNSIGNED NOT NULL,channel VARCHAR(30) NOT NULL,result VARCHAR(40) NOT NULL,amount DECIMAL(15,2) NOT NULL DEFAULT 0,promise_date DATE NULL,local_status ENUM('none','pending','reconciled','cancelled') NOT NULL DEFAULT 'none',reconciled_at DATETIME NULL,recorded_at DATETIME NULL,notes TEXT NULL,created_at DATETIME NOT NULL,FOREIGN KEY(client_id) REFERENCES clients(id) ON DELETE CASCADE,FOREIGN KEY(author_user_id) REFERENCES users(id),FOREIGN KEY(assigned_user_id) REFERENCES users(id),INDEX idx_ca_client_date(client_id,created_at,id),INDEX idx_ca_result_date_user(result,created_at,assigned_user_id),INDEX idx_ca_created_assigned(created_at,assigned_user_id,client_id));
CREATE TABLE IF NOT EXISTS settings(setting_key VARCHAR(80) PRIMARY KEY,value_json JSON NOT NULL,updated_at DATETIME NOT NULL);
CREATE TABLE IF NOT EXISTS sync_state(module_key VARCHAR(40) PRIMARY KEY,last_page INT NOT NULL DEFAULT 0,total_pages INT NOT NULL DEFAULT 0,last_count INT NOT NULL DEFAULT 0,context_json JSON NULL,last_success_at DATETIME NULL,last_error TEXT NULL);
CREATE TABLE IF NOT EXISTS omie_order_logs(id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,integration_code VARCHAR(60) NOT NULL,omie_order_code VARCHAR(80) NULL,omie_order_number VARCHAR(30) NULL,client_id BIGINT UNSIGNED NOT NULL,seller_omie_code VARCHAR(80) NOT NULL,user_id INT UNSIGNED NOT NULL,total DECIMAL(15,2) NOT NULL DEFAULT 0,request_json JSON NOT NULL,response_json JSON NULL,status ENUM('success','error') NOT NULL,error_message TEXT NULL,created_at DATETIME NOT NULL,UNIQUE KEY uq_omie_order_integration(integration_code));

CREATE TABLE IF NOT EXISTS goals(
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id INT UNSIGNED NOT NULL,
 month_ref CHAR(7) NOT NULL,
 sales_goal DECIMAL(15,2) NOT NULL DEFAULT 0,
 collection_goal DECIMAL(15,2) NOT NULL DEFAULT 0,
 contact_goal INT NOT NULL DEFAULT 0,
 updated_by INT UNSIGNED NULL,
 updated_at DATETIME NOT NULL,
 UNIQUE KEY uq_goals_user_month(user_id,month_ref),
 FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
 FOREIGN KEY(updated_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS virtual_seller_goals(
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 seller_omie_code VARCHAR(80) NOT NULL,
 month_ref CHAR(7) NOT NULL,
 sales_goal DECIMAL(15,2) NOT NULL DEFAULT 0,
 updated_by INT UNSIGNED NULL,
 updated_at DATETIME NOT NULL,
 UNIQUE KEY uq_virtual_seller_goal(seller_omie_code,month_ref),
 FOREIGN KEY(updated_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS collection_assignment_log(
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 client_id BIGINT UNSIGNED NOT NULL,
 from_user_id INT UNSIGNED NULL,
 to_user_id INT UNSIGNED NOT NULL,
 changed_by INT UNSIGNED NOT NULL,
 created_at DATETIME NOT NULL,
 FOREIGN KEY(client_id) REFERENCES clients(id) ON DELETE CASCADE,
 FOREIGN KEY(from_user_id) REFERENCES users(id) ON DELETE SET NULL,
 FOREIGN KEY(to_user_id) REFERENCES users(id),
 FOREIGN KEY(changed_by) REFERENCES users(id),
 INDEX idx_collection_assignment_client_date(client_id,created_at)
);

CREATE TABLE IF NOT EXISTS order_profiles(
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 code VARCHAR(40) NOT NULL,
 name VARCHAR(120) NOT NULL,
 description VARCHAR(255) NULL,
 default_no_stock CHAR(1) NOT NULL DEFAULT 'N',
 default_no_finance CHAR(1) NOT NULL DEFAULT 'N',
 default_no_total CHAR(1) NOT NULL DEFAULT 'N',
 default_reserve_stock CHAR(1) NOT NULL DEFAULT 'N',
 active TINYINT(1) NOT NULL DEFAULT 1,
 created_at DATETIME NOT NULL,
 updated_at DATETIME NOT NULL,
 UNIQUE KEY uq_order_profiles_code(code)
);
INSERT IGNORE INTO order_profiles(code,name,description,default_no_stock,default_no_finance,default_no_total,default_reserve_stock,active,created_at,updated_at) VALUES
('NORMAL','Venda normal','Movimenta estoque e gera financeiro normalmente.','N','N','N','N',1,NOW(),NOW()),
('SEM_ESTOQUE','Venda sem movimento de estoque','Gera financeiro, mas não baixa estoque ao faturar.','S','N','N','N',1,NOW(),NOW()),
('SEM_FINANCEIRO','Remessa sem financeiro','Movimenta estoque, mas o item não gera conta a receber.','N','S','N','N',1,NOW(),NOW()),
('INFORMATIVO','Item informativo','Não movimenta estoque, não gera financeiro e não soma no total da NF-e.','S','S','S','N',1,NOW(),NOW());
