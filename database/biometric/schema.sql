SET NAMES utf8mb4;
SET SESSION time_zone='-03:00';

-- Banco separado do LMS.
-- Não armazena senha do usuário do LMS e não cria foreign keys cruzadas.

CREATE TABLE IF NOT EXISTS biometric_subjects (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 lms_user_id BIGINT UNSIGNED NOT NULL,
 subject_key CHAR(64) NOT NULL,
 provider VARCHAR(80) NULL,
 provider_subject_id VARCHAR(190) NULL,
 status VARCHAR(30) NOT NULL DEFAULT 'pending',
 created_at DATETIME NOT NULL,
 updated_at DATETIME NULL,
 UNIQUE KEY uq_bio_subject_lms(lms_user_id),
 UNIQUE KEY uq_bio_subject_key(subject_key),
 KEY ix_bio_subject_provider(provider,provider_subject_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS biometric_references (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 subject_id BIGINT UNSIGNED NOT NULL,
 method VARCHAR(60) NOT NULL DEFAULT 'face',
 template_reference VARCHAR(500) NULL,
 capture_reference VARCHAR(500) NULL,
 template_hash CHAR(64) NULL,
 status VARCHAR(30) NOT NULL DEFAULT 'active',
 metadata_json JSON NULL,
 enrolled_at DATETIME NOT NULL,
 expires_at DATETIME NULL,
 deleted_at DATETIME NULL,
 KEY ix_bio_ref_subject(subject_id,status),
 CONSTRAINT fk_bio_ref_subject FOREIGN KEY(subject_id) REFERENCES biometric_subjects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS biometric_challenges (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 challenge_uuid CHAR(36) NOT NULL,
 lms_user_id BIGINT UNSIGNED NOT NULL,
 lms_enrollment_id BIGINT UNSIGNED NULL,
 lms_course_id BIGINT UNSIGNED NULL,
 profile_code VARCHAR(80) NOT NULL,
 checkpoint VARCHAR(60) NOT NULL,
 verification_method VARCHAR(60) NOT NULL,
 status VARCHAR(30) NOT NULL DEFAULT 'pending',
 required_confidence DECIMAL(6,4) NULL,
 liveness_required TINYINT(1) NOT NULL DEFAULT 1,
 provider VARCHAR(80) NULL,
 provider_session_id VARCHAR(190) NULL,
 attempts_count INT UNSIGNED NOT NULL DEFAULT 0,
 ip_address VARCHAR(45) NULL,
 user_agent VARCHAR(500) NULL,
 created_at DATETIME NOT NULL,
 expires_at DATETIME NOT NULL,
 verified_at DATETIME NULL,
 KEY ix_bio_challenge_user_time(lms_user_id,created_at),
 KEY ix_bio_challenge_enrollment(lms_enrollment_id,status),
 KEY ix_bio_challenge_course(lms_course_id,status),
 UNIQUE KEY uq_bio_challenge_uuid(challenge_uuid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS biometric_attempts (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 challenge_id BIGINT UNSIGNED NOT NULL,
 attempt_no INT UNSIGNED NOT NULL,
 result VARCHAR(30) NOT NULL,
 match_score DECIMAL(8,5) NULL,
 liveness_score DECIMAL(8,5) NULL,
 failure_code VARCHAR(100) NULL,
 capture_reference VARCHAR(500) NULL,
 provider_response_json JSON NULL,
 created_at DATETIME NOT NULL,
 UNIQUE KEY uq_bio_attempt(challenge_id,attempt_no),
 KEY ix_bio_attempt_result(result,created_at),
 CONSTRAINT fk_bio_attempt_challenge FOREIGN KEY(challenge_id) REFERENCES biometric_challenges(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS biometric_audit_events (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 lms_user_id BIGINT UNSIGNED NULL,
 challenge_id BIGINT UNSIGNED NULL,
 event_type VARCHAR(100) NOT NULL,
 event_data JSON NULL,
 ip_address VARCHAR(45) NULL,
 created_at DATETIME NOT NULL,
 KEY ix_bio_audit_user_time(lms_user_id,created_at),
 KEY ix_bio_audit_type_time(event_type,created_at),
 CONSTRAINT fk_bio_audit_challenge FOREIGN KEY(challenge_id) REFERENCES biometric_challenges(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
