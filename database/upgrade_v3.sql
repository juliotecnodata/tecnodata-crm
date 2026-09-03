SET NAMES utf8mb4;

ALTER TABLE sellers
 ADD COLUMN omie_active TINYINT(1) NOT NULL DEFAULT 1 AFTER active,
 ADD COLUMN goal_mode ENUM('collection','sales_collection') NOT NULL DEFAULT 'sales_collection' AFTER omie_active;

ALTER TABLE seller_goals
 ADD COLUMN collection_goal DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER goal3,
 ADD COLUMN debtor_contact_goal INT NOT NULL DEFAULT 0 AFTER collection_goal;

ALTER TABLE financial_movements
 ADD COLUMN account_omie_code VARCHAR(80) NULL AFTER status,
 ADD COLUMN last_seen_run_id BIGINT UNSIGNED NULL AFTER account_omie_code,
 ADD INDEX idx_fin_account(account_omie_code),
 ADD INDEX idx_fin_seen(last_seen_run_id);

ALTER TABLE sync_states ADD COLUMN context_json JSON NULL AFTER last_success_at;

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
 seller_omie_code VARCHAR(80) NOT NULL,
 client_id BIGINT UNSIGNED NOT NULL,
 user_id INT UNSIGNED NOT NULL,
 action_type ENUM('contact','promise','agreement','payment') NOT NULL,
 amount DECIMAL(15,2) NOT NULL DEFAULT 0,
 promised_for DATE NULL,
 notes TEXT NULL,
 created_at DATETIME NOT NULL,
 FOREIGN KEY(client_id) REFERENCES clients(id) ON DELETE CASCADE,
 FOREIGN KEY(user_id) REFERENCES users(id),
 INDEX idx_collection_seller_date(seller_omie_code,created_at),
 INDEX idx_collection_client(client_id),
 INDEX idx_collection_type_date(action_type,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
