SET NAMES utf8mb4;
SET SESSION time_zone='-03:00';

CREATE TABLE IF NOT EXISTS organizations (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(180) NOT NULL,
 code VARCHAR(80) NULL UNIQUE,
 status VARCHAR(20) NOT NULL DEFAULT 'active',
 created_at DATETIME NOT NULL,
 updated_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 organization_id BIGINT UNSIGNED NULL,
 user_type VARCHAR(30) NOT NULL DEFAULT 'student',
 cpf CHAR(11) NULL,
 username VARCHAR(120) NULL,
 name VARCHAR(180) NOT NULL,
 email VARCHAR(190) NULL,
 password_hash VARCHAR(255) NOT NULL,
 status VARCHAR(20) NOT NULL DEFAULT 'active',
 last_login_at DATETIME NULL,
 created_at DATETIME NOT NULL,
 updated_at DATETIME NULL,
 UNIQUE KEY uq_users_cpf (cpf),
 UNIQUE KEY uq_users_username (username),
 KEY ix_users_email (email),
 KEY ix_users_type_status (user_type,status),
 CONSTRAINT fk_users_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS roles (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 slug VARCHAR(80) NOT NULL UNIQUE,
 name VARCHAR(120) NOT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO roles(slug,name) VALUES
('super_admin','Super Administrador'),('admin','Administrador'),('manager','Gestor'),
('coordinator','Coordenador'),('teacher_editor','Professor editor'),('teacher','Professor'),
('tutor','Tutor/Monitor'),('support','Suporte'),('student','Aluno');

CREATE TABLE IF NOT EXISTS permissions (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 slug VARCHAR(120) NOT NULL UNIQUE,
 name VARCHAR(180) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS role_permissions (
 role_id BIGINT UNSIGNED NOT NULL,
 permission_id BIGINT UNSIGNED NOT NULL,
 PRIMARY KEY(role_id,permission_id),
 CONSTRAINT fk_rp_role FOREIGN KEY(role_id) REFERENCES roles(id) ON DELETE CASCADE,
 CONSTRAINT fk_rp_perm FOREIGN KEY(permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_role_assignments (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id BIGINT UNSIGNED NOT NULL,
 role_id BIGINT UNSIGNED NOT NULL,
 context_type VARCHAR(30) NOT NULL DEFAULT 'system',
 context_id BIGINT UNSIGNED NULL,
 created_at DATETIME NOT NULL,
 KEY ix_ura_user (user_id),
 KEY ix_ura_context (context_type,context_id),
 CONSTRAINT fk_ura_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
 CONSTRAINT fk_ura_role FOREIGN KEY(role_id) REFERENCES roles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS courses (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 organization_id BIGINT UNSIGNED NULL,
 code VARCHAR(100) NOT NULL,
 name VARCHAR(220) NOT NULL,
 shortname VARCHAR(120) NULL,
 summary MEDIUMTEXT NULL,
 status VARCHAR(30) NOT NULL DEFAULT 'draft',
 navigation_mode VARCHAR(30) NOT NULL DEFAULT 'linear',
 source_system VARCHAR(40) NULL,
 source_id VARCHAR(120) NULL,
 created_at DATETIME NOT NULL,
 updated_at DATETIME NULL,
 UNIQUE KEY uq_course_code (code),
 KEY ix_courses_status (status),
 KEY ix_courses_source (source_system,source_id),
 CONSTRAINT fk_course_org FOREIGN KEY(organization_id) REFERENCES organizations(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS course_sections (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 course_id BIGINT UNSIGNED NOT NULL,
 parent_id BIGINT UNSIGNED NULL,
 title VARCHAR(220) NOT NULL,
 summary MEDIUMTEXT NULL,
 position INT NOT NULL DEFAULT 0,
 visible TINYINT(1) NOT NULL DEFAULT 1,
 availability_json JSON NULL,
 source_id VARCHAR(120) NULL,
 created_at DATETIME NOT NULL,
 updated_at DATETIME NULL,
 KEY ix_sections_course_pos (course_id,position),
 CONSTRAINT fk_section_course FOREIGN KEY(course_id) REFERENCES courses(id) ON DELETE CASCADE,
 CONSTRAINT fk_section_parent FOREIGN KEY(parent_id) REFERENCES course_sections(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS course_activities (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 course_id BIGINT UNSIGNED NOT NULL,
 section_id BIGINT UNSIGNED NOT NULL,
 type VARCHAR(60) NOT NULL,
 title VARCHAR(220) NOT NULL,
 description MEDIUMTEXT NULL,
 position INT NOT NULL DEFAULT 0,
 visible TINYINT(1) NOT NULL DEFAULT 1,
 completion_mode VARCHAR(40) NOT NULL DEFAULT 'manual',
 content_json JSON NULL,
 settings_json JSON NULL,
 availability_json JSON NULL,
 source_id VARCHAR(120) NULL,
 created_at DATETIME NOT NULL,
 updated_at DATETIME NULL,
 KEY ix_activity_course (course_id),
 KEY ix_activity_section_pos (section_id,position),
 KEY ix_activity_type (type),
 CONSTRAINT fk_activity_course FOREIGN KEY(course_id) REFERENCES courses(id) ON DELETE CASCADE,
 CONSTRAINT fk_activity_section FOREIGN KEY(section_id) REFERENCES course_sections(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS question_categories (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 course_id BIGINT UNSIGNED NULL,
 parent_id BIGINT UNSIGNED NULL,
 name VARCHAR(220) NOT NULL,
 source_id VARCHAR(120) NULL,
 created_at DATETIME NOT NULL,
 KEY ix_qcat_course (course_id),
 CONSTRAINT fk_qcat_course FOREIGN KEY(course_id) REFERENCES courses(id) ON DELETE CASCADE,
 CONSTRAINT fk_qcat_parent FOREIGN KEY(parent_id) REFERENCES question_categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS questions (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 category_id BIGINT UNSIGNED NULL,
 type VARCHAR(60) NOT NULL,
 name VARCHAR(255) NULL,
 question_html MEDIUMTEXT NOT NULL,
 default_mark DECIMAL(10,4) NOT NULL DEFAULT 1,
 settings_json JSON NULL,
 source_id VARCHAR(120) NULL,
 created_at DATETIME NOT NULL,
 updated_at DATETIME NULL,
 KEY ix_question_cat (category_id),
 KEY ix_question_type (type),
 CONSTRAINT fk_question_cat FOREIGN KEY(category_id) REFERENCES question_categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS question_answers (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 question_id BIGINT UNSIGNED NOT NULL,
 answer_html MEDIUMTEXT NOT NULL,
 fraction DECIMAL(10,6) NOT NULL DEFAULT 0,
 feedback_html MEDIUMTEXT NULL,
 position INT NOT NULL DEFAULT 0,
 source_id VARCHAR(120) NULL,
 CONSTRAINT fk_answer_question FOREIGN KEY(question_id) REFERENCES questions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS quizzes (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 activity_id BIGINT UNSIGNED NOT NULL UNIQUE,
 grade_max DECIMAL(10,4) NOT NULL DEFAULT 100,
 grade_pass DECIMAL(10,4) NULL,
 attempts_allowed INT NOT NULL DEFAULT 0,
 settings_json JSON NULL,
 CONSTRAINT fk_quiz_activity FOREIGN KEY(activity_id) REFERENCES course_activities(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS quiz_slots (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 quiz_id BIGINT UNSIGNED NOT NULL,
 question_id BIGINT UNSIGNED NULL,
 category_id BIGINT UNSIGNED NULL,
 random_count INT NOT NULL DEFAULT 0,
 position INT NOT NULL DEFAULT 0,
 CONSTRAINT fk_slot_quiz FOREIGN KEY(quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE,
 CONSTRAINT fk_slot_question FOREIGN KEY(question_id) REFERENCES questions(id) ON DELETE CASCADE,
 CONSTRAINT fk_slot_category FOREIGN KEY(category_id) REFERENCES question_categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS enrollments (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id BIGINT UNSIGNED NOT NULL,
 course_id BIGINT UNSIGNED NOT NULL,
 status VARCHAR(30) NOT NULL DEFAULT 'active',
 progress_percent DECIMAL(5,2) NOT NULL DEFAULT 0,
 final_score DECIMAL(7,2) NULL,
 started_at DATETIME NULL,
 expires_at DATETIME NULL,
 completed_at DATETIME NULL,
 created_at DATETIME NOT NULL,
 updated_at DATETIME NULL,
 KEY ix_enrollment_user_status (user_id,status),
 KEY ix_enrollment_course_status (course_id,status),
 CONSTRAINT fk_enrollment_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
 CONSTRAINT fk_enrollment_course FOREIGN KEY(course_id) REFERENCES courses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS enrollment_external_refs (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 enrollment_id BIGINT UNSIGNED NOT NULL,
 source_system VARCHAR(80) NOT NULL,
 external_id VARCHAR(160) NOT NULL,
 created_at DATETIME NOT NULL,
 UNIQUE KEY uq_external_ref(source_system,external_id),
 CONSTRAINT fk_external_enrollment FOREIGN KEY(enrollment_id) REFERENCES enrollments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS activity_progress (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 enrollment_id BIGINT UNSIGNED NOT NULL,
 activity_id BIGINT UNSIGNED NOT NULL,
 progress_percent DECIMAL(5,2) NOT NULL DEFAULT 0,
 completed TINYINT(1) NOT NULL DEFAULT 0,
 seconds_spent INT UNSIGNED NOT NULL DEFAULT 0,
 last_position INT UNSIGNED NULL,
 completed_at DATETIME NULL,
 updated_at DATETIME NOT NULL,
 UNIQUE KEY uq_activity_progress(enrollment_id,activity_id),
 KEY ix_progress_activity(activity_id),
 CONSTRAINT fk_progress_enrollment FOREIGN KEY(enrollment_id) REFERENCES enrollments(id) ON DELETE CASCADE,
 CONSTRAINT fk_progress_activity FOREIGN KEY(activity_id) REFERENCES course_activities(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS study_sessions (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 enrollment_id BIGINT UNSIGNED NOT NULL,
 session_token CHAR(64) NOT NULL,
 ip_address VARCHAR(45) NULL,
 user_agent VARCHAR(500) NULL,
 started_at DATETIME NOT NULL,
 last_seen_at DATETIME NOT NULL,
 ended_at DATETIME NULL,
 UNIQUE KEY uq_study_token(session_token),
 KEY ix_study_enrollment(enrollment_id),
 CONSTRAINT fk_study_enrollment FOREIGN KEY(enrollment_id) REFERENCES enrollments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS study_events (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 enrollment_id BIGINT UNSIGNED NOT NULL,
 activity_id BIGINT UNSIGNED NULL,
 event_type VARCHAR(80) NOT NULL,
 event_data JSON NULL,
 ip_address VARCHAR(45) NULL,
 created_at DATETIME NOT NULL,
 KEY ix_event_enrollment_time(enrollment_id,created_at),
 KEY ix_event_type(event_type),
 CONSTRAINT fk_event_enrollment FOREIGN KEY(enrollment_id) REFERENCES enrollments(id) ON DELETE CASCADE,
 CONSTRAINT fk_event_activity FOREIGN KEY(activity_id) REFERENCES course_activities(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS api_clients (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(180) NOT NULL,
 token_hash CHAR(64) NOT NULL UNIQUE,
 scopes_json JSON NOT NULL,
 enabled TINYINT(1) NOT NULL DEFAULT 1,
 last_used_at DATETIME NULL,
 created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS idempotency_keys (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 api_client_id BIGINT UNSIGNED NOT NULL,
 idem_key VARCHAR(190) NOT NULL,
 response_json JSON NOT NULL,
 created_at DATETIME NOT NULL,
 UNIQUE KEY uq_idempotency(api_client_id,idem_key),
 CONSTRAINT fk_idem_client FOREIGN KEY(api_client_id) REFERENCES api_clients(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS imports (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 source_system VARCHAR(40) NOT NULL,
 original_name VARCHAR(255) NOT NULL,
 stored_path VARCHAR(500) NOT NULL,
 sha256 CHAR(64) NOT NULL,
 status VARCHAR(30) NOT NULL,
 analysis_json JSON NULL,
 target_course_id BIGINT UNSIGNED NULL,
 created_at DATETIME NOT NULL,
 completed_at DATETIME NULL,
 UNIQUE KEY uq_import_hash(sha256),
 KEY ix_import_status(status),
 CONSTRAINT fk_import_course FOREIGN KEY(target_course_id) REFERENCES courses(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS legacy_mappings (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 source_system VARCHAR(40) NOT NULL,
 source_type VARCHAR(80) NOT NULL,
 source_id VARCHAR(160) NOT NULL,
 target_type VARCHAR(80) NOT NULL,
 target_id BIGINT UNSIGNED NOT NULL,
 created_at DATETIME NOT NULL,
 UNIQUE KEY uq_legacy(source_system,source_type,source_id),
 KEY ix_legacy_target(target_type,target_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_events (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id BIGINT UNSIGNED NULL,
 event_type VARCHAR(100) NOT NULL,
 entity_type VARCHAR(80) NULL,
 entity_id BIGINT UNSIGNED NULL,
 ip_address VARCHAR(45) NULL,
 user_agent VARCHAR(500) NULL,
 data_json JSON NULL,
 created_at DATETIME NOT NULL,
 KEY ix_audit_event_time(event_type,created_at),
 KEY ix_audit_entity(entity_type,entity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
