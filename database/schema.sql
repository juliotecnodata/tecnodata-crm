CREATE TABLE IF NOT EXISTS users(id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,name VARCHAR(120) NOT NULL,email VARCHAR(190) NOT NULL,password_hash VARCHAR(255) NOT NULL,role ENUM('admin','supervisor','seller','collector') NOT NULL,seller_omie_code VARCHAR(80) NULL,active TINYINT(1) NOT NULL DEFAULT 1,last_login_at DATETIME NULL,created_at DATETIME NOT NULL,updated_at DATETIME NOT NULL,UNIQUE KEY uq_users_email(email));
CREATE TABLE IF NOT EXISTS sellers(omie_code VARCHAR(80) PRIMARY KEY,name VARCHAR(160) NOT NULL,email VARCHAR(190) NULL,active TINYINT(1) NOT NULL DEFAULT 1,raw_json JSON NULL,updated_at DATETIME NOT NULL);
CREATE TABLE IF NOT EXISTS clients(id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,omie_code VARCHAR(80) NOT NULL,name VARCHAR(190) NOT NULL,legal_name VARCHAR(190) NULL,document VARCHAR(30) NULL,email VARCHAR(190) NULL,phone VARCHAR(40) NULL,city VARCHAR(100) NULL,uf CHAR(2) NULL,seller_omie_code VARCHAR(80) NULL,active TINYINT(1) NOT NULL DEFAULT 1,raw_json JSON NULL,updated_at DATETIME NOT NULL,UNIQUE KEY uq_clients_omie(omie_code),INDEX idx_clients_name(name),INDEX idx_clients_seller(seller_omie_code,active));
CREATE TABLE IF NOT EXISTS client_metrics(client_id BIGINT UNSIGNED PRIMARY KEY,last_purchase_at DATE NULL,revenue_12m DECIMAL(15,2) NOT NULL DEFAULT 0,orders_12m INT NOT NULL DEFAULT 0,avg_ticket_12m DECIMAL(15,2) NOT NULL DEFAULT 0,avg_interval_days DECIMAL(10,2) NULL,updated_at DATETIME NOT NULL,FOREIGN KEY(client_id) REFERENCES clients(id) ON DELETE CASCADE);
CREATE TABLE IF NOT EXISTS products(id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,omie_code VARCHAR(80) NOT NULL,sku VARCHAR(120) NULL,description VARCHAR(255) NOT NULL,unit VARCHAR(20) NULL,ncm VARCHAR(30) NULL,unit_price DECIMAL(15,4) NOT NULL DEFAULT 0,stock_qty DECIMAL(15,4) NULL,active TINYINT(1) NOT NULL DEFAULT 1,raw_json JSON NULL,updated_at DATETIME NOT NULL,UNIQUE KEY uq_products_omie(omie_code),INDEX idx_products_description(description));
CREATE TABLE IF NOT EXISTS categories(code VARCHAR(80) PRIMARY KEY,description VARCHAR(255) NOT NULL,active TINYINT(1) NOT NULL DEFAULT 1,raw_json JSON NULL,updated_at DATETIME NOT NULL);
CREATE TABLE IF NOT EXISTS financial_accounts(omie_code VARCHAR(80) PRIMARY KEY,name VARCHAR(160) NOT NULL,account_type VARCHAR(10) NULL,active TINYINT(1) NOT NULL DEFAULT 1,selected TINYINT(1) NOT NULL DEFAULT 0,raw_json JSON NULL,updated_at DATETIME NOT NULL);
CREATE TABLE IF NOT EXISTS order_stages(code VARCHAR(10) PRIMARY KEY,name VARCHAR(120) NOT NULL,active TINYINT(1) NOT NULL DEFAULT 1,raw_json JSON NULL,updated_at DATETIME NOT NULL);
CREATE TABLE IF NOT EXISTS payment_terms(code VARCHAR(3) PRIMARY KEY,description VARCHAR(120) NOT NULL,installments INT NOT NULL DEFAULT 0,days_list VARCHAR(120) NULL,active TINYINT(1) NOT NULL DEFAULT 1,raw_json JSON NULL,updated_at DATETIME NOT NULL);
CREATE TABLE IF NOT EXISTS tax_scenarios(omie_code VARCHAR(80) PRIMARY KEY,name VARCHAR(120) NOT NULL,is_default TINYINT(1) NOT NULL DEFAULT 0,active TINYINT(1) NOT NULL DEFAULT 1,raw_json JSON NULL,updated_at DATETIME NOT NULL);
CREATE TABLE IF NOT EXISTS stock_locations(omie_code VARCHAR(80) PRIMARY KEY,name VARCHAR(250) NOT NULL,sale_enabled TINYINT(1) NOT NULL DEFAULT 0,is_default TINYINT(1) NOT NULL DEFAULT 0,active TINYINT(1) NOT NULL DEFAULT 1,raw_json JSON NULL,updated_at DATETIME NOT NULL);
CREATE TABLE IF NOT EXISTS payment_methods(code VARCHAR(4) PRIMARY KEY,description VARCHAR(100) NOT NULL,raw_json JSON NULL,updated_at DATETIME NOT NULL);
CREATE TABLE IF NOT EXISTS document_types(code VARCHAR(8) PRIMARY KEY,description VARCHAR(100) NOT NULL,raw_json JSON NULL,updated_at DATETIME NOT NULL);
CREATE TABLE IF NOT EXISTS orders(id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,omie_code VARCHAR(80) NOT NULL,number VARCHAR(30) NULL,client_omie_code VARCHAR(80) NULL,seller_omie_code VARCHAR(80) NULL,order_date DATE NULL,forecast_date DATE NULL,total DECIMAL(15,2) NOT NULL DEFAULT 0,status VARCHAR(30) NULL,stage_code VARCHAR(10) NULL,raw_json JSON NULL,updated_at DATETIME NOT NULL,UNIQUE KEY uq_orders_omie(omie_code),INDEX idx_orders_seller_date(seller_omie_code,order_date));
CREATE TABLE IF NOT EXISTS service_orders(id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,omie_code VARCHAR(80) NOT NULL,client_omie_code VARCHAR(80) NULL,seller_omie_code VARCHAR(80) NULL,service_date DATE NULL,total DECIMAL(15,2) NOT NULL DEFAULT 0,status VARCHAR(40) NULL,raw_json JSON NULL,updated_at DATETIME NOT NULL,UNIQUE KEY uq_service_orders_omie(omie_code));
CREATE TABLE IF NOT EXISTS financial_movements(id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,omie_code VARCHAR(100) NOT NULL,client_omie_code VARCHAR(80) NULL,account_omie_code VARCHAR(80) NULL,seller_omie_code VARCHAR(80) NULL,due_date DATE NULL,open_amount DECIMAL(15,2) NOT NULL DEFAULT 0,paid_amount DECIMAL(15,2) NOT NULL DEFAULT 0,status VARCHAR(30) NOT NULL,last_seen_token VARCHAR(64) NULL,raw_json JSON NULL,updated_at DATETIME NOT NULL,UNIQUE KEY uq_fin_omie(omie_code),INDEX idx_fin_client_status(client_omie_code,status),INDEX idx_fin_seen(last_seen_token));
CREATE TABLE IF NOT EXISTS activities(id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,client_id BIGINT UNSIGNED NOT NULL,user_id INT UNSIGNED NOT NULL,channel VARCHAR(30) NOT NULL,result VARCHAR(40) NOT NULL,notes TEXT NULL,next_at DATETIME NULL,created_at DATETIME NOT NULL,FOREIGN KEY(client_id) REFERENCES clients(id) ON DELETE CASCADE,FOREIGN KEY(user_id) REFERENCES users(id),INDEX idx_activities_client_date(client_id,created_at));
CREATE TABLE IF NOT EXISTS tasks(id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,client_id BIGINT UNSIGNED NOT NULL,assigned_user_id INT UNSIGNED NOT NULL,type ENUM('sales','collection') NOT NULL,title VARCHAR(180) NOT NULL,due_at DATETIME NOT NULL,status ENUM('pending','done','cancelled') NOT NULL DEFAULT 'pending',created_at DATETIME NOT NULL,completed_at DATETIME NULL,FOREIGN KEY(client_id) REFERENCES clients(id) ON DELETE CASCADE,FOREIGN KEY(assigned_user_id) REFERENCES users(id),INDEX idx_tasks_user_status_date(assigned_user_id,status,due_at));
CREATE TABLE IF NOT EXISTS collection_cases(client_id BIGINT UNSIGNED PRIMARY KEY,open_amount DECIMAL(15,2) NOT NULL DEFAULT 0,partial_paid DECIMAL(15,2) NOT NULL DEFAULT 0,max_overdue_days INT NOT NULL DEFAULT 0,status ENUM('open','settled') NOT NULL DEFAULT 'open',assigned_user_id INT UNSIGNED NULL,assigned_at DATETIME NULL,updated_at DATETIME NOT NULL,FOREIGN KEY(client_id) REFERENCES clients(id) ON DELETE CASCADE,FOREIGN KEY(assigned_user_id) REFERENCES users(id) ON DELETE SET NULL);
CREATE TABLE IF NOT EXISTS collection_actions(id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,client_id BIGINT UNSIGNED NOT NULL,author_user_id INT UNSIGNED NOT NULL,assigned_user_id INT UNSIGNED NOT NULL,channel VARCHAR(30) NOT NULL,result ENUM('contact','promise','agreement','payment','no_answer') NOT NULL,amount DECIMAL(15,2) NOT NULL DEFAULT 0,promise_date DATE NULL,notes TEXT NULL,created_at DATETIME NOT NULL,FOREIGN KEY(client_id) REFERENCES clients(id) ON DELETE CASCADE,FOREIGN KEY(author_user_id) REFERENCES users(id),FOREIGN KEY(assigned_user_id) REFERENCES users(id),INDEX idx_ca_client_date(client_id,created_at));
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
