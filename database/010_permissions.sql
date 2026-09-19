-- Permissões padrão do Tecnodata LMS v0.2
INSERT IGNORE INTO permissions(slug,name) VALUES
('dashboard.view','Ver painel'),
('course.view','Ver cursos'),
('course.create','Criar cursos'),
('course.edit','Editar cursos'),
('course.publish','Publicar cursos'),
('course.manage_content','Gerenciar conteúdo'),
('student.view','Ver alunos'),
('student.edit','Editar alunos'),
('enrollment.view','Ver matrículas'),
('enrollment.create','Criar matrículas'),
('enrollment.edit','Editar matrículas'),
('progress.view','Ver progresso'),
('quiz.manage','Gerenciar avaliações'),
('report.view','Ver relatórios'),
('import.moodle','Importar Moodle'),
('api.manage','Gerenciar integrações API'),
('audit.view','Ver auditoria');

-- Super Admin e Admin recebem tudo.
INSERT IGNORE INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM roles r CROSS JOIN permissions p
WHERE r.slug IN ('super_admin','admin');

-- Gestor.
INSERT IGNORE INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM roles r JOIN permissions p
ON p.slug IN ('dashboard.view','course.view','student.view','enrollment.view','progress.view','report.view')
WHERE r.slug='manager';

-- Coordenador.
INSERT IGNORE INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM roles r JOIN permissions p
ON p.slug IN ('course.view','student.view','enrollment.view','enrollment.create','progress.view','report.view')
WHERE r.slug='coordinator';

-- Professor editor.
INSERT IGNORE INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM roles r JOIN permissions p
ON p.slug IN ('course.view','course.edit','course.manage_content','progress.view','quiz.manage')
WHERE r.slug='teacher_editor';

-- Professor.
INSERT IGNORE INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM roles r JOIN permissions p
ON p.slug IN ('course.view','progress.view')
WHERE r.slug='teacher';

-- Tutor.
INSERT IGNORE INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM roles r JOIN permissions p
ON p.slug IN ('course.view','student.view','progress.view')
WHERE r.slug='tutor';

-- Suporte.
INSERT IGNORE INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM roles r JOIN permissions p
ON p.slug IN ('student.view','enrollment.view','progress.view','audit.view')
WHERE r.slug='support';
