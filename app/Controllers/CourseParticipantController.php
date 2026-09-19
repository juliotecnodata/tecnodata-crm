<?php
namespace Tecnodata\Lms\Controllers;

use Tecnodata\Lms\Core\Auth;
use Tecnodata\Lms\Core\Clock;
use Tecnodata\Lms\Core\Csrf;
use Tecnodata\Lms\Core\Database;
use Tecnodata\Lms\Core\View;
use Tecnodata\Lms\Services\Audit;

final class CourseParticipantController
{
    public function index(string $courseId): void
    {
        Auth::requireAdmin();
        $course=$this->course((int)$courseId);

        $staff=Database::all(
            "SELECT id,name,email FROM users
              WHERE user_type<>'student' AND status='active'
              ORDER BY name"
        );
        $roles=Database::all(
            "SELECT id,slug,name FROM roles
              WHERE slug IN('manager','coordinator','teacher_editor','teacher','tutor','support')
              ORDER BY name"
        );
        $assigned=Database::all(
            "SELECT ura.id,u.id user_id,u.name,u.email,r.id role_id,r.slug,r.name role_name
               FROM user_role_assignments ura
               JOIN users u ON u.id=ura.user_id
               JOIN roles r ON r.id=ura.role_id
              WHERE ura.context_type='course' AND ura.context_id=?
                AND r.slug<>'student'
              ORDER BY r.name,u.name",
            [$course['id']]
        );

        View::render('courses/participants',compact('course','staff','roles','assigned'));
    }

    public function store(string $courseId): void
    {
        Auth::requireAdmin();
        Csrf::verify();
        $course=$this->course((int)$courseId);

        $userId=(int)($_POST['user_id']??0);
        $roleId=(int)($_POST['role_id']??0);

        $user=Database::fetch("SELECT id FROM users WHERE id=? AND user_type<>'student' AND status='active'",[$userId]);
        $role=Database::fetch(
            "SELECT * FROM roles WHERE id=? AND slug IN('manager','coordinator','teacher_editor','teacher','tutor','support')",
            [$roleId]
        );
        if(!$user||!$role)throw new \RuntimeException('Usuário ou papel inválido.');

        Database::execute(
            "INSERT INTO user_role_assignments(user_id,role_id,context_type,context_id,created_at)
             SELECT ?,?,'course',?,?
             WHERE NOT EXISTS(
               SELECT 1 FROM user_role_assignments
                WHERE user_id=? AND role_id=? AND context_type='course' AND context_id=?
             )",
            [$userId,$roleId,$course['id'],Clock::sql(),$userId,$roleId,$course['id']]
        );

        Audit::log('course.participant_assigned','course',$course['id'],['user_id'=>$userId,'role'=>$role['slug']]);
        redirect('/admin/courses/'.$course['id'].'/participants');
    }

    public function delete(string $assignmentId): void
    {
        Auth::requireAdmin();
        Csrf::verify();

        $row=Database::fetch(
            "SELECT ura.*,r.slug FROM user_role_assignments ura
               JOIN roles r ON r.id=ura.role_id
              WHERE ura.id=? AND ura.context_type='course' AND r.slug<>'student'",
            [(int)$assignmentId]
        );
        if(!$row)throw new \RuntimeException('Atribuição não encontrada.');

        Database::execute("DELETE FROM user_role_assignments WHERE id=?",[(int)$assignmentId]);
        Audit::log('course.participant_removed','course',(int)$row['context_id'],['user_id'=>$row['user_id'],'role'=>$row['slug']]);
        redirect('/admin/courses/'.$row['context_id'].'/participants');
    }

    private function course(int $id): array
    {
        $c=Database::fetch("SELECT * FROM courses WHERE id=?",[$id]);
        if(!$c)throw new \RuntimeException('Curso não encontrado.');
        return $c;
    }
}
