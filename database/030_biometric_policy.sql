-- Configuração de biometria no banco principal do LMS.
-- Dados biométricos e tentativas NÃO ficam aqui.

CREATE TABLE IF NOT EXISTS biometric_profiles (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 code VARCHAR(80) NOT NULL UNIQUE,
 name VARCHAR(160) NOT NULL,
 description VARCHAR(500) NULL,
 verification_method VARCHAR(60) NOT NULL DEFAULT 'face_match_liveness',
 first_access_required TINYINT(1) NOT NULL DEFAULT 0,
 course_entry_required TINYINT(1) NOT NULL DEFAULT 0,
 before_quiz_required TINYINT(1) NOT NULL DEFAULT 0,
 periodic_minutes INT UNSIGNED NULL,
 random_min_minutes INT UNSIGNED NULL,
 random_max_minutes INT UNSIGNED NULL,
 max_failures INT UNSIGNED NOT NULL DEFAULT 3,
 min_confidence DECIMAL(6,4) NULL,
 liveness_required TINYINT(1) NOT NULL DEFAULT 1,
 enabled TINYINT(1) NOT NULL DEFAULT 1,
 created_at DATETIME NOT NULL,
 updated_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS biometric_profile_role_rules (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 profile_id BIGINT UNSIGNED NOT NULL,
 role_id BIGINT UNSIGNED NOT NULL,
 action VARCHAR(20) NOT NULL DEFAULT 'require',
 created_at DATETIME NOT NULL,
 UNIQUE KEY uq_bio_profile_role(profile_id,role_id),
 CONSTRAINT fk_bio_role_profile FOREIGN KEY(profile_id) REFERENCES biometric_profiles(id) ON DELETE CASCADE,
 CONSTRAINT fk_bio_role_role FOREIGN KEY(role_id) REFERENCES roles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS course_biometric_settings (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 course_id BIGINT UNSIGNED NOT NULL UNIQUE,
 profile_id BIGINT UNSIGNED NOT NULL,
 enabled TINYINT(1) NOT NULL DEFAULT 1,
 overrides_json JSON NULL,
 created_at DATETIME NOT NULL,
 updated_at DATETIME NULL,
 CONSTRAINT fk_course_bio_course FOREIGN KEY(course_id) REFERENCES courses(id) ON DELETE CASCADE,
 CONSTRAINT fk_course_bio_profile FOREIGN KEY(profile_id) REFERENCES biometric_profiles(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO biometric_profiles
(code,name,description,verification_method,first_access_required,course_entry_required,before_quiz_required,periodic_minutes,random_min_minutes,random_max_minutes,max_failures,min_confidence,liveness_required,enabled,created_at)
VALUES
('BIO_NONE','Sem biometria','Curso passa pelo motor de validação, mas não exige biometria.','none',0,0,0,NULL,NULL,NULL,3,NULL,0,1,NOW()),
('BIO_STANDARD','Facial padrão','Validação facial no primeiro acesso e entrada do curso.','face_match_liveness',1,1,0,NULL,NULL,NULL,3,NULL,1,1,NOW()),
('BIO_REGULATED','Facial regulamentado','Perfil para cursos que exigem validações adicionais durante a jornada.','face_match_liveness',1,1,1,240,NULL,NULL,3,NULL,1,1,NOW());

-- Por padrão, somente o papel aluno é obrigado; perfis administrativos/profissionais são bypass.
INSERT IGNORE INTO biometric_profile_role_rules(profile_id,role_id,action,created_at)
SELECT p.id,r.id,'require',NOW()
FROM biometric_profiles p JOIN roles r ON r.slug='student'
WHERE p.code IN ('BIO_STANDARD','BIO_REGULATED');

INSERT IGNORE INTO biometric_profile_role_rules(profile_id,role_id,action,created_at)
SELECT p.id,r.id,'bypass',NOW()
FROM biometric_profiles p
JOIN roles r ON r.slug IN ('super_admin','admin','manager','coordinator','teacher_editor','teacher','tutor','support')
WHERE p.code IN ('BIO_STANDARD','BIO_REGULATED');
