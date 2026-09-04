SET NAMES utf8mb4;
CREATE TABLE IF NOT EXISTS users (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(120) NOT NULL,
 email VARCHAR(190) NOT NULL UNIQUE,
 password_hash VARCHAR(255) NOT NULL,
 role ENUM('seller','collector','supervisor','admin') NOT NULL DEFAULT 'seller',
 seller_omie_code VARCHAR(80) NULL,
 active TINYINT(1) NOT NULL DEFAULT 1,
 last_login_at DATETIME NULL,
 created_at DATETIME NOT NULL,
 updated_at DATETIME NULL,
 INDEX idx_users_seller (seller_omie_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS sellers (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 omie_code VARCHAR(80) NOT NULL UNIQUE,
 name VARCHAR(160) NOT NULL,
 email VARCHAR(190) NULL,
 active TINYINT(1) NOT NULL DEFAULT 1,
 omie_active TINYINT(1) NOT NULL DEFAULT 1,
 goal_mode ENUM('sales','collection','sales_collection') NOT NULL DEFAULT 'sales_collection',
 is_virtual TINYINT(1) NOT NULL DEFAULT 0,
 raw_json JSON NULL,
 updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS clients (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 omie_code VARCHAR(80) NOT NULL UNIQUE,
 name VARCHAR(190) NOT NULL,
 legal_name VARCHAR(190) NULL,
 uf CHAR(2) NULL,
 city VARCHAR(120) NULL,
 phone VARCHAR(50) NULL,
 email VARCHAR(190) NULL,
 document VARCHAR(30) NULL,
 active TINYINT(1) NOT NULL DEFAULT 1,
 raw_json JSON NULL,
 updated_at DATETIME NOT NULL,
 INDEX idx_clients_name(name), INDEX idx_clients_uf(uf)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS orders (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 omie_order_code VARCHAR(120) NOT NULL UNIQUE,
 client_omie_code VARCHAR(80) NULL,
 seller_omie_code VARCHAR(80) NULL,
 order_date DATE NULL,
 total DECIMAL(15,2) NOT NULL DEFAULT 0,
 status VARCHAR(60) NULL,
 stage_code VARCHAR(10) NULL,
 stage_name VARCHAR(120) NULL,
 stage_changed_at DATETIME NULL,
 raw_json JSON NULL,
 updated_at DATETIME NOT NULL,
 INDEX idx_orders_client(client_omie_code),
 INDEX idx_orders_seller_date(seller_omie_code,order_date),
 INDEX idx_orders_date(order_date),
 INDEX idx_orders_stage(stage_code,order_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS service_orders (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 omie_service_order_code VARCHAR(120) NOT NULL UNIQUE,
 display_number VARCHAR(60) NULL,
 client_omie_code VARCHAR(80) NULL,
 seller_omie_code VARCHAR(80) NULL,
 inclusion_date DATE NULL,
 total DECIMAL(15,2) NOT NULL DEFAULT 0,
 status VARCHAR(60) NULL,
 stage_code VARCHAR(10) NULL,
 service_description TEXT NULL,
 external_reference VARCHAR(120) NULL,
 raw_json JSON NULL,
 updated_at DATETIME NOT NULL,
 INDEX idx_service_client(client_omie_code),
 INDEX idx_service_seller_date(seller_omie_code,inclusion_date),
 INDEX idx_service_date(inclusion_date),
 INDEX idx_service_status(status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS financial_movements (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 omie_code VARCHAR(120) NOT NULL UNIQUE,
 client_omie_code VARCHAR(80) NULL,
 due_date DATE NULL,
 payment_date DATE NULL,
 amount DECIMAL(15,2) NOT NULL DEFAULT 0,
 original_amount DECIMAL(15,2) NOT NULL DEFAULT 0,
 paid_amount DECIMAL(15,2) NOT NULL DEFAULT 0,
 status VARCHAR(60) NULL,
 seller_omie_code VARCHAR(80) NULL,
 account_omie_code VARCHAR(80) NULL,
 last_seen_run_id BIGINT UNSIGNED NULL,
 raw_json JSON NULL,
 updated_at DATETIME NOT NULL,
 INDEX idx_fin_client(client_omie_code),
 INDEX idx_fin_due(due_date),
 INDEX idx_fin_status(status),
 INDEX idx_fin_account(account_omie_code),
 INDEX idx_fin_seen(last_seen_run_id),
 INDEX idx_fin_collection(account_omie_code,status,client_omie_code),
 INDEX idx_fin_client_status_account(client_omie_code,status,account_omie_code),
 INDEX idx_fin_seller_status(seller_omie_code,status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS client_metrics (
 client_id BIGINT UNSIGNED PRIMARY KEY,
 seller_omie_code VARCHAR(80) NULL,
 first_purchase_at DATE NULL,
 last_purchase_at DATE NULL,
 days_without_purchase INT NULL,
 avg_interval_days DECIMAL(10,2) NULL,
 orders_12m INT NOT NULL DEFAULT 0,
 revenue_12m DECIMAL(15,2) NOT NULL DEFAULT 0,
 avg_ticket_12m DECIMAL(15,2) NOT NULL DEFAULT 0,
 open_amount DECIMAL(15,2) NOT NULL DEFAULT 0,
 overdue_amount DECIMAL(15,2) NOT NULL DEFAULT 0,
 max_overdue_days INT NOT NULL DEFAULT 0,
 commercial_status ENUM('normal','attention','reactivate') NOT NULL DEFAULT 'normal',
 updated_at DATETIME NOT NULL,
 CONSTRAINT fk_metric_client FOREIGN KEY(client_id) REFERENCES clients(id) ON DELETE CASCADE,
 INDEX idx_metric_seller(seller_omie_code),
 INDEX idx_metric_status(commercial_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS activities (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 client_id BIGINT UNSIGNED NOT NULL,
 user_id INT UNSIGNED NOT NULL,
 type ENUM('ligacao','whatsapp','email','visita') NOT NULL,
 result ENUM('falou','nao_atendeu','interessado','sem_interesse','acordo') NOT NULL,
 notes TEXT NULL,
 created_at DATETIME NOT NULL,
 updated_at DATETIME NULL,
 updated_by INT UNSIGNED NULL,
 deleted_at DATETIME NULL,
 deleted_by INT UNSIGNED NULL,
 FOREIGN KEY(client_id) REFERENCES clients(id) ON DELETE CASCADE,
 FOREIGN KEY(user_id) REFERENCES users(id),
 INDEX idx_activity_user_date(user_id,created_at),
 INDEX idx_activity_client(client_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS tasks (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 client_id BIGINT UNSIGNED NOT NULL,
 user_id INT UNSIGNED NOT NULL,
 title VARCHAR(160) NOT NULL,
 due_at DATETIME NOT NULL,
 status ENUM('pending','done','cancelled') NOT NULL DEFAULT 'pending',
 pre_notified_at DATETIME NULL,
 due_notified_at DATETIME NULL,
 reminder_notified_at DATETIME NULL,
 created_at DATETIME NOT NULL,
 completed_at DATETIME NULL,
 FOREIGN KEY(client_id) REFERENCES clients(id) ON DELETE CASCADE,
 FOREIGN KEY(user_id) REFERENCES users(id),
 INDEX idx_tasks_user_due(user_id,due_at,status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS user_notification_settings (
 user_id INT UNSIGNED PRIMARY KEY,
 browser_enabled TINYINT(1) NOT NULL DEFAULT 1,
 sound_enabled TINYINT(1) NOT NULL DEFAULT 1,
 volume TINYINT UNSIGNED NOT NULL DEFAULT 70,
 pre_alert_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 10,
 repeat_after_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 15,
 updated_at DATETIME NOT NULL,
 FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS monthly_goals (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 month_ref CHAR(7) NOT NULL UNIQUE,
 general_goal DECIMAL(15,2) NOT NULL DEFAULT 0,
 created_at DATETIME NOT NULL,
 updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS seller_goals (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 month_ref CHAR(7) NOT NULL,
 seller_omie_code VARCHAR(80) NOT NULL,
 goal1 DECIMAL(15,2) NOT NULL DEFAULT 0,
 goal2 DECIMAL(15,2) NOT NULL DEFAULT 0,
 goal3 DECIMAL(15,2) NOT NULL DEFAULT 0,
 collection_goal DECIMAL(15,2) NOT NULL DEFAULT 0,
 debtor_contact_goal INT NOT NULL DEFAULT 0,
 created_at DATETIME NOT NULL,
 updated_at DATETIME NOT NULL,
 UNIQUE KEY uq_seller_goal(month_ref,seller_omie_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS sync_runs (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 started_by INT UNSIGNED NULL,
 module_key VARCHAR(40) NULL,
 status ENUM('running','success','error') NOT NULL,
 started_at DATETIME NOT NULL,
 heartbeat_at DATETIME NULL,
 finished_at DATETIME NULL,
 stats_json JSON NULL,
 error_message TEXT NULL,
 FOREIGN KEY(started_by) REFERENCES users(id),
 INDEX idx_sync_status(status),
 INDEX idx_sync_module(module_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS sync_states (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 module_key VARCHAR(40) NOT NULL UNIQUE,
 status ENUM('idle','running','success','error') NOT NULL DEFAULT 'idle',
 current_page INT NOT NULL DEFAULT 0,
 total_pages INT NOT NULL DEFAULT 0,
 processed BIGINT NOT NULL DEFAULT 0,
 last_error TEXT NULL,
 last_success_at DATETIME NULL,
 context_json JSON NULL,
 created_at DATETIME NOT NULL,
 updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS financial_accounts (
 omie_code VARCHAR(80) PRIMARY KEY,
 name VARCHAR(160) NOT NULL,
 account_type VARCHAR(10) NULL,
 active TINYINT(1) NOT NULL DEFAULT 1,
 selected TINYINT(1) NOT NULL DEFAULT 0,
 updated_at DATETIME NOT NULL,
 INDEX idx_fin_account_selected(selected,active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS collection_actions (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 seller_omie_code VARCHAR(80) NULL,
 client_id BIGINT UNSIGNED NOT NULL,
 user_id INT UNSIGNED NOT NULL,
 action_type ENUM('contact','promise','agreement','payment') NOT NULL,
 channel ENUM('ligacao','whatsapp','email','outro') NOT NULL DEFAULT 'ligacao',
 result ENUM('falou','nao_atendeu','promessa','acordo','pagamento','sem_previsao') NOT NULL DEFAULT 'falou',
 amount DECIMAL(15,2) NOT NULL DEFAULT 0,
 pending_amount DECIMAL(15,2) NOT NULL DEFAULT 0,
 debt_before DECIMAL(15,2) NOT NULL DEFAULT 0,
 debt_after DECIMAL(15,2) NOT NULL DEFAULT 0,
 promised_for DATE NULL,
 notes TEXT NULL,
 created_at DATETIME NOT NULL,
 updated_at DATETIME NULL,
 updated_by INT UNSIGNED NULL,
 deleted_at DATETIME NULL,
 deleted_by INT UNSIGNED NULL,
 FOREIGN KEY(client_id) REFERENCES clients(id) ON DELETE CASCADE,
 FOREIGN KEY(user_id) REFERENCES users(id),
 INDEX idx_collection_seller_date(seller_omie_code,created_at),
 INDEX idx_collection_client(client_id),
 INDEX idx_collection_type_date(action_type,created_at),
 INDEX idx_collection_client_deleted_date(client_id,deleted_at,created_at),
 INDEX idx_collection_user_deleted_date(user_id,deleted_at,created_at,client_id),
 INDEX idx_collection_result_deleted_date(result,deleted_at,created_at,client_id),
 INDEX idx_collection_client_result_date(client_id,deleted_at,result,created_at),
 INDEX idx_collection_client_user_date(client_id,deleted_at,user_id,created_at),
 INDEX idx_collection_client_latest(client_id,deleted_at,id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS collection_user_goals (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id INT UNSIGNED NOT NULL,
 month_ref CHAR(7) NOT NULL,
 amount_goal DECIMAL(15,2) NOT NULL DEFAULT 0,
 contact_goal INT NOT NULL DEFAULT 0,
 created_at DATETIME NOT NULL,
 updated_at DATETIME NOT NULL,
 UNIQUE KEY uq_collection_user_goal(user_id,month_ref),
 FOREIGN KEY(user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS collection_client_adjustments (
 client_id BIGINT UNSIGNED PRIMARY KEY,
 pending_received DECIMAL(15,2) NOT NULL DEFAULT 0,
 baseline_omie_debt DECIMAL(15,2) NOT NULL DEFAULT 0,
 last_payment_at DATETIME NULL,
 updated_at DATETIME NOT NULL,
 FOREIGN KEY(client_id) REFERENCES clients(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE IF NOT EXISTS interaction_audit (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 entity_type ENUM('sales_activity','collection_action') NOT NULL,
 entity_id BIGINT UNSIGNED NOT NULL,
 operation ENUM('create','update','delete') NOT NULL,
 user_id INT UNSIGNED NOT NULL,
 before_json JSON NULL,
 after_json JSON NULL,
 created_at DATETIME NOT NULL,
 FOREIGN KEY(user_id) REFERENCES users(id),
 INDEX idx_interaction_audit_entity(entity_type,entity_id),
 INDEX idx_interaction_audit_user_date(user_id,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE IF NOT EXISTS client_portfolio_assignments (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 month_ref CHAR(7) NOT NULL,
 client_id BIGINT UNSIGNED NOT NULL,
 seller_omie_code VARCHAR(80) NULL,
 created_by INT UNSIGNED NULL,
 updated_by INT UNSIGNED NULL,
 created_at DATETIME NOT NULL,
 updated_at DATETIME NOT NULL,
 UNIQUE KEY uq_client_portfolio_month(month_ref,client_id),
 INDEX idx_portfolio_month_seller(month_ref,seller_omie_code),
 INDEX idx_portfolio_client_month(client_id,month_ref),
 CONSTRAINT fk_portfolio_client FOREIGN KEY(client_id) REFERENCES clients(id) ON DELETE CASCADE,
 CONSTRAINT fk_portfolio_created_by FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE SET NULL,
 CONSTRAINT fk_portfolio_updated_by FOREIGN KEY(updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE IF NOT EXISTS client_tags (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 client_id BIGINT UNSIGNED NOT NULL,
 tag VARCHAR(160) NOT NULL,
 created_at DATETIME NOT NULL,
 UNIQUE KEY uq_client_tag(client_id,tag),
 INDEX idx_client_tags_tag(tag),
 CONSTRAINT fk_client_tags_client FOREIGN KEY(client_id) REFERENCES clients(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE IF NOT EXISTS order_stage_catalog (
 stage_code VARCHAR(10) PRIMARY KEY,
 stage_name VARCHAR(120) NOT NULL,
 default_name VARCHAR(120) NULL,
 active TINYINT(1) NOT NULL DEFAULT 1,
 updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
