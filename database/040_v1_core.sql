-- Tecnodata LMS v1.0 - núcleo administrativo, avaliações e arquivos.

CREATE TABLE IF NOT EXISTS system_settings (
 setting_key VARCHAR(160) PRIMARY KEY,
 setting_value MEDIUMTEXT NULL,
 value_type VARCHAR(30) NOT NULL DEFAULT 'string',
 updated_by BIGINT UNSIGNED NULL,
 updated_at DATETIME NOT NULL,
 KEY ix_settings_updated_by(updated_by),
 CONSTRAINT fk_settings_user FOREIGN KEY(updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS course_policies (
 course_id BIGINT UNSIGNED PRIMARY KEY,
 min_days INT UNSIGNED NOT NULL DEFAULT 0,
 max_days INT UNSIGNED NULL,
 require_sequential TINYINT(1) NOT NULL DEFAULT 0,
 completion_percent DECIMAL(5,2) NOT NULL DEFAULT 100,
 final_score_required DECIMAL(7,2) NULL,
 allow_retake TINYINT(1) NOT NULL DEFAULT 1,
 settings_json JSON NULL,
 created_at DATETIME NOT NULL,
 updated_at DATETIME NULL,
 CONSTRAINT fk_course_policy_course FOREIGN KEY(course_id) REFERENCES courses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS course_files (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 course_id BIGINT UNSIGNED NOT NULL,
 uploaded_by BIGINT UNSIGNED NULL,
 original_name VARCHAR(255) NOT NULL,
 stored_name VARCHAR(255) NOT NULL,
 disk_path VARCHAR(500) NOT NULL,
 mime_type VARCHAR(120) NULL,
 size_bytes BIGINT UNSIGNED NOT NULL DEFAULT 0,
 sha256 CHAR(64) NOT NULL,
 visibility VARCHAR(30) NOT NULL DEFAULT 'enrolled',
 source_system VARCHAR(40) NULL,
 source_id VARCHAR(160) NULL,
 created_at DATETIME NOT NULL,
 UNIQUE KEY uq_course_file_hash(course_id,sha256),
 KEY ix_course_file_course(course_id,created_at),
 CONSTRAINT fk_course_file_course FOREIGN KEY(course_id) REFERENCES courses(id) ON DELETE CASCADE,
 CONSTRAINT fk_course_file_user FOREIGN KEY(uploaded_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS quiz_attempts (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 quiz_id BIGINT UNSIGNED NOT NULL,
 enrollment_id BIGINT UNSIGNED NOT NULL,
 attempt_no INT UNSIGNED NOT NULL,
 status VARCHAR(30) NOT NULL DEFAULT 'inprogress',
 question_order_json JSON NOT NULL,
 score DECIMAL(10,4) NULL,
 percent DECIMAL(7,3) NULL,
 passed TINYINT(1) NULL,
 started_at DATETIME NOT NULL,
 finished_at DATETIME NULL,
 created_at DATETIME NOT NULL,
 UNIQUE KEY uq_quiz_attempt(quiz_id,enrollment_id,attempt_no),
 KEY ix_quiz_attempt_enrollment(enrollment_id,status),
 CONSTRAINT fk_quiz_attempt_quiz FOREIGN KEY(quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE,
 CONSTRAINT fk_quiz_attempt_enrollment FOREIGN KEY(enrollment_id) REFERENCES enrollments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS quiz_attempt_answers (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 attempt_id BIGINT UNSIGNED NOT NULL,
 question_id BIGINT UNSIGNED NOT NULL,
 response_json JSON NULL,
 fraction DECIMAL(10,6) NOT NULL DEFAULT 0,
 mark DECIMAL(10,4) NOT NULL DEFAULT 0,
 answered_at DATETIME NOT NULL,
 UNIQUE KEY uq_attempt_question(attempt_id,question_id),
 CONSTRAINT fk_attempt_answer_attempt FOREIGN KEY(attempt_id) REFERENCES quiz_attempts(id) ON DELETE CASCADE,
 CONSTRAINT fk_attempt_answer_question FOREIGN KEY(question_id) REFERENCES questions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS schema_migrations (
 migration VARCHAR(190) PRIMARY KEY,
 applied_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO permissions(slug,name) VALUES
('user.manage','Gerenciar usuários internos'),
('role.manage','Gerenciar papéis e permissões'),
('question.manage','Gerenciar banco de questões'),
('quiz.manage','Gerenciar avaliações'),
('course.settings','Gerenciar regras de curso'),
('file.manage','Gerenciar arquivos de curso'),
('system.manage','Gerenciar configurações do sistema'),
('biometric.manage','Gerenciar perfis biométricos');

INSERT IGNORE INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM roles r CROSS JOIN permissions p
WHERE r.slug IN ('super_admin','admin');
