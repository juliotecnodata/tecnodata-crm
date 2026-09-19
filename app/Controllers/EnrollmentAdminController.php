<?php
namespace Tecnodata\Lms\Controllers;

use Tecnodata\Lms\Core\Auth;
use Tecnodata\Lms\Core\Clock;
use Tecnodata\Lms\Core\Csrf;
use Tecnodata\Lms\Core\Database;
use Tecnodata\Lms\Core\View;
use Tecnodata\Lms\Services\Audit;

final class EnrollmentAdminController
{
    private function admin(): array
    {
        return Auth::requireAdmin();
    }

    public function index(): void
    {
        $this->admin();

        $rows = Database::all(
            "SELECT e.*,u.name student,u.cpf,c.name course,c.code course_code
               FROM enrollments e
               JOIN users u ON u.id=e.user_id
               JOIN courses c ON c.id=e.course_id
              ORDER BY e.id DESC
              LIMIT 1000"
        );

        $courses = Database::all(
            "SELECT id,code,name FROM courses WHERE status<>'archived' ORDER BY name"
        );

        View::render('enrollments/index', [
            'enrollments' => $rows,
            'courses' => $courses,
        ]);
    }

    public function store(): void
    {
        $admin = $this->admin();
        Csrf::verify();

        $cpf = preg_replace('/\D+/', '', (string) ($_POST['cpf'] ?? ''));
        $courseId = (int) ($_POST['course_id'] ?? 0);

        if (strlen($cpf) !== 11 || $courseId <= 0) {
            throw new \RuntimeException('Informe CPF e curso.');
        }

        $user = Database::fetch("SELECT * FROM users WHERE cpf=?", [$cpf]);
        if (!$user) {
            throw new \RuntimeException('Aluno não encontrado. Cadastre-o pela API ou importe antes de matricular.');
        }

        $existing = Database::fetch(
            "SELECT * FROM enrollments
              WHERE user_id=? AND course_id=? AND status IN('active','completed')
              ORDER BY id DESC LIMIT 1",
            [$user['id'], $courseId]
        );

        if ($existing) {
            redirect('/admin/enrollments');
        }

        Database::execute(
            "INSERT INTO enrollments(user_id,course_id,status,progress_percent,started_at,created_at,updated_at)
             VALUES(?,?,'active',0,?,?,?)",
            [$user['id'], $courseId, Clock::sql(), Clock::sql(), Clock::sql()]
        );

        $enrollmentId = Database::id();
        $this->assignStudentRole((int) $user['id'], $courseId);

        Database::execute(
            "INSERT INTO enrollment_status_history(enrollment_id,old_status,new_status,reason,changed_by,created_at)
             VALUES(?,NULL,'active','Matrícula criada manualmente',?,?)",
            [$enrollmentId, $admin['id'], Clock::sql()]
        );

        Audit::log('enrollment.created', 'enrollment', $enrollmentId);
        redirect('/admin/enrollments');
    }

    public function status(string $id): void
    {
        $admin = $this->admin();
        Csrf::verify();

        $allowed = ['active','suspended','cancelled','completed'];
        $new = (string) ($_POST['status'] ?? '');
        if (!in_array($new, $allowed, true)) {
            throw new \RuntimeException('Status inválido.');
        }

        $en = Database::fetch("SELECT * FROM enrollments WHERE id=?", [(int) $id]);
        if (!$en) {
            throw new \RuntimeException('Matrícula não encontrada.');
        }

        $completedAt = $new === 'completed' ? Clock::sql() : $en['completed_at'];

        Database::execute(
            "UPDATE enrollments SET status=?,completed_at=?,updated_at=? WHERE id=?",
            [$new, $completedAt, Clock::sql(), (int) $id]
        );

        Database::execute(
            "INSERT INTO enrollment_status_history(enrollment_id,old_status,new_status,reason,changed_by,created_at)
             VALUES(?,?,?,?,?,?)",
            [(int) $id, $en['status'], $new, trim((string) ($_POST['reason'] ?? '')), $admin['id'], Clock::sql()]
        );

        Audit::log('enrollment.status_changed', 'enrollment', (int) $id, ['from'=>$en['status'],'to'=>$new]);
        redirect('/admin/enrollments');
    }

    private function assignStudentRole(int $userId, int $courseId): void
    {
        $role = Database::fetch("SELECT id FROM roles WHERE slug='student'");
        if (!$role) return;

        $exists = Database::fetch(
            "SELECT 1 FROM user_role_assignments
              WHERE user_id=? AND role_id=? AND context_type='course' AND context_id=? LIMIT 1",
            [$userId, $role['id'], $courseId]
        );

        if (!$exists) {
            Database::execute(
                "INSERT INTO user_role_assignments(user_id,role_id,context_type,context_id,created_at)
                 VALUES(?,?,'course',?,?)",
                [$userId, $role['id'], $courseId, Clock::sql()]
            );
        }
    }
}
