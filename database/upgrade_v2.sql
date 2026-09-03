-- TECNODATA CRM V2 - atualização segura para instalações V1
SET NAMES utf8mb4;

ALTER TABLE sync_runs ADD COLUMN module_key VARCHAR(40) NULL AFTER started_by;
ALTER TABLE sync_runs ADD COLUMN heartbeat_at DATETIME NULL AFTER started_at;

CREATE INDEX idx_sync_status ON sync_runs(status);
CREATE INDEX idx_sync_module ON sync_runs(module_key);

CREATE TABLE IF NOT EXISTS sync_states (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 module_key VARCHAR(40) NOT NULL UNIQUE,
 status ENUM('idle','running','success','error') NOT NULL DEFAULT 'idle',
 current_page INT NOT NULL DEFAULT 0,
 total_pages INT NOT NULL DEFAULT 0,
 processed BIGINT NOT NULL DEFAULT 0,
 last_error TEXT NULL,
 last_success_at DATETIME NULL,
 created_at DATETIME NOT NULL,
 updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
