CREATE TABLE IF NOT EXISTS enrollment_status_history (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 enrollment_id BIGINT UNSIGNED NOT NULL,
 old_status VARCHAR(30) NULL,
 new_status VARCHAR(30) NOT NULL,
 reason VARCHAR(255) NULL,
 changed_by BIGINT UNSIGNED NULL,
 created_at DATETIME NOT NULL,
 KEY ix_enrhist_enrollment_time(enrollment_id,created_at),
 CONSTRAINT fk_enrhist_enrollment FOREIGN KEY(enrollment_id) REFERENCES enrollments(id) ON DELETE CASCADE,
 CONSTRAINT fk_enrhist_user FOREIGN KEY(changed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS api_requests (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 api_client_id BIGINT UNSIGNED NULL,
 trace_id VARCHAR(64) NOT NULL,
 method VARCHAR(10) NOT NULL,
 path VARCHAR(500) NOT NULL,
 ip_address VARCHAR(45) NULL,
 status_code INT NULL,
 duration_ms INT NULL,
 created_at DATETIME NOT NULL,
 UNIQUE KEY uq_api_trace(trace_id),
 KEY ix_api_client_time(api_client_id,created_at),
 KEY ix_api_path_time(path(190),created_at),
 CONSTRAINT fk_api_request_client FOREIGN KEY(api_client_id) REFERENCES api_clients(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
