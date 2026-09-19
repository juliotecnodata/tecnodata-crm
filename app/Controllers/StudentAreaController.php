<?php
namespace Tecnodata\Lms\Controllers;

use Tecnodata\Lms\Core\Auth;
use Tecnodata\Lms\Core\Clock;
use Tecnodata\Lms\Core\Csrf;
use Tecnodata\Lms\Core\Database;
use Tecnodata\Lms\Core\View;

final class StudentAreaController
{
    public function home(): void
    {
        $u=Auth::requireLogin();
        $courses=Database::all("SELECT e.id enrollment_id,e.progress_percent,e.status,c.* FROM enrollments e JOIN courses c ON c.id=e.course_id WHERE e.user_id=? AND e.status IN('active','completed') ORDER BY e.id DESC",[$u['id']]);
        View::render('student/home',['user'=>$u,'courses'=>$courses]);
    }

    public function course(string $id): void
    {
        $u=Auth::requireLogin();
        $en=Database::fetch("SELECT e.*,c.name,c.summary FROM enrollments e JOIN courses c ON c.id=e.course_id WHERE e.id=? AND e.user_id=?",[(int)$id,$u['id']]);
        if(!$en){http_response_code(404);exit('Matrícula não encontrada.');}
        $sections=Database::all("SELECT * FROM course_sections WHERE course_id=? AND visible=1 ORDER BY position,id",[$en['course_id']]);
        foreach($sections as &$s){
            $s['activities']=Database::all("SELECT a.*,COALESCE(p.completed,0) completed FROM course_activities a LEFT JOIN activity_progress p ON p.activity_id=a.id AND p.enrollment_id=? WHERE a.section_id=? AND a.visible=1 ORDER BY a.position,a.id",[$en['id'],$s['id']]);
        }
        View::render('student/course',['enrollment'=>$en,'sections'=>$sections]);
    }

    public function complete(string $id): void
    {
        $u=Auth::requireLogin(); Csrf::verify();
        $activity=Database::fetch("SELECT a.*,e.id enrollment_id,e.user_id,e.course_id FROM course_activities a JOIN enrollments e ON e.course_id=a.course_id WHERE a.id=? AND e.id=? AND e.user_id=?",[(int)$id,(int)($_POST['enrollment_id']??0),$u['id']]);
        if(!$activity){http_response_code(404);exit('Atividade inválida.');}
        Database::execute("INSERT INTO activity_progress(enrollment_id,activity_id,completed,completed_at,updated_at) VALUES(?,?,1,?,?) ON DUPLICATE KEY UPDATE completed=1,completed_at=VALUES(completed_at),updated_at=VALUES(updated_at)",[$activity['enrollment_id'],$activity['id'],Clock::sql(),Clock::sql()]);
        $tot=(int)(Database::fetch("SELECT COUNT(*) c FROM course_activities WHERE course_id=? AND visible=1",[$activity['course_id']])['c']??0);
        $done=(int)(Database::fetch("SELECT COUNT(*) c FROM activity_progress p JOIN course_activities a ON a.id=p.activity_id WHERE p.enrollment_id=? AND a.course_id=? AND p.completed=1",[$activity['enrollment_id'],$activity['course_id']])['c']??0);
        $pct=$tot?round($done*100/$tot,2):0;
        Database::execute("UPDATE enrollments SET progress_percent=?,updated_at=? WHERE id=?",[$pct,Clock::sql(),$activity['enrollment_id']]);
        redirect('/student/course/'.$activity['enrollment_id']);
    }
}
