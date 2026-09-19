ALTER TABLE study_sessions
    ADD COLUMN IF NOT EXISTS active_seconds INT UNSIGNED NOT NULL DEFAULT 0 AFTER ended_at;

CREATE INDEX IF NOT EXISTS ix_study_last_seen ON study_sessions(last_seen_at);

INSERT IGNORE INTO permissions(slug,name) VALUES
('teaching.view','Acompanhar cursos atribuídos');
INSERT IGNORE INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM roles r JOIN permissions p ON p.slug='teaching.view'
WHERE r.slug IN('manager','coordinator','teacher_editor','teacher','tutor','support');
