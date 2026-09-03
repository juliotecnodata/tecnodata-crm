SET NAMES utf8mb4;
ALTER TABLE users MODIFY COLUMN role ENUM('seller','collector','supervisor','admin') NOT NULL DEFAULT 'seller';
ALTER TABLE collection_actions MODIFY COLUMN seller_omie_code VARCHAR(80) NULL;
ALTER TABLE collection_actions ADD COLUMN channel ENUM('ligacao','whatsapp','email','outro') NOT NULL DEFAULT 'ligacao' AFTER action_type;
ALTER TABLE collection_actions ADD COLUMN result ENUM('falou','nao_atendeu','promessa','acordo','pagamento','sem_previsao') NOT NULL DEFAULT 'falou' AFTER channel;
ALTER TABLE collection_actions ADD COLUMN debt_before DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER amount;
ALTER TABLE collection_actions ADD COLUMN debt_after DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER debt_before;
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
