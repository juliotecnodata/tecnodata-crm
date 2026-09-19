<?php
namespace Tecnodata\Lms\Controllers;

use Tecnodata\Lms\Core\Auth;
use Tecnodata\Lms\Core\Database;
use Tecnodata\Lms\Core\View;

final class TeachingController
{
    public function index(): void
    {
        $u=Auth::requireLogin();
        $courses=Database::all(
            "SELECT DISTINCT c.*,r.name role_name,r.slug role_slug
               FROM user_role_assignments ura
               JOIN roles r ON r.id=ura.role_id
               JOIN courses c ON c.id=ura.context_id
              WHERE ura.user_id=? AND ura.context_type='course'
                AND r.slug IN('manager','coordinator','teacher_editor','teacher','tutor','support')
                AND c.status<>'archived'
              ORDER BY c.name",
            [$u['id']]
        );

        if(!$courses && !Auth::isAdmin((int)$u['id'])){
            redirect('/student');
        }

        View::render('teaching/index',['courses'=>$courses,'user'=>$u]);
    }

    public function course(string $id): void
    {
        $u=Auth::requireLogin();
        if(!Auth::isAdmin((int)$u['id'])){
            $assigned=Database::fetch(
                "SELECT r.slug role_slug
                   FROM user_role_assignments ura
                   JOIN roles r ON r.id=ura.role_id
                  WHERE ura.user_id=? AND ura.context_type='course' AND ura.context_id=?
                    AND r.slug IN('manager','coordinator','teacher_editor','teacher','tutor','support')
                  LIMIT 1",
                [$u['id'],(int)$id]
            );
            if(!$assigned){http_response_code(403);exit('Acesso negado.');}
        }

        $course=Database::fetch("SELECT * FROM courses WHERE id=?",[(int)$id]);
        if(!$course)throw new \RuntimeException('Curso não encontrado.');

        $stats=[
            'enrollments'=>(int)(Database::fetch("SELECT COUNT(*) c FROM enrollments WHERE course_id=?",[$course['id']])['c']??0),
            'active'=>(int)(Database::fetch("SELECT COUNT(*) c FROM enrollments WHERE course_id=? AND status='active'",[$course['id']])['c']??0),
            'completed'=>(int)(Database::fetch("SELECT COUNT(*) c FROM enrollments WHERE course_id=? AND status='completed'",[$course['id']])['c']??0),
            'progress'=>(float)(Database::fetch("SELECT ROUND(AVG(progress_percent),1) v FROM enrollments WHERE course_id=?",[$course['id']])['v']??0),
        ];

        $students=Database::all(
            "SELECT e.id,u.name,u.cpf,e.status,e.progress_percent,e.final_score,e.started_at,e.completed_at
               FROM enrollments e
               JOIN users u ON u.id=e.user_id
              WHERE e.course_id=?
              ORDER BY e.status,u.name
              LIMIT 1500",
            [$course['id']]
        );

        View::render('teaching/course',compact('course','stats','students'));
    }
}
