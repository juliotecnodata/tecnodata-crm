SET NAMES utf8mb4;

ALTER TABLE sellers ADD COLUMN is_virtual TINYINT(1) NOT NULL DEFAULT 0 AFTER goal_mode;

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

UPDATE sellers SET active=1,is_virtual=1,goal_mode='sales_collection'
WHERE omie_code='594326005' OR name IN('EAD Reciclagem','Suporte - Pet Cursos');
