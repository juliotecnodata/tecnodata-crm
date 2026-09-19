<?php
namespace Tecnodata\Lms\Controllers;

use Tecnodata\Lms\Core\Auth;
use Tecnodata\Lms\Core\Clock;
use Tecnodata\Lms\Core\Csrf;
use Tecnodata\Lms\Core\Database;
use Tecnodata\Lms\Core\View;
use Tecnodata\Lms\Services\ProgressService;

final class StudentAreaController
{
    public function home(): void
    {
        $u=Auth::requireLogin();
        $courses=Database::all(
            "SELECT e.id enrollment_id,e.progress_percent,e.status,e.completed_at,c.*
               FROM enrollments e JOIN courses c ON c.id=e.course_id
              WHERE e.user_id=? AND e.status IN('active','completed')
              ORDER BY CASE WHEN e.status='active' THEN 0 ELSE 1 END,e.id DESC",
            [$u['id']]
        );
        View::render('student/home',['user'=>$u,'courses'=>$courses]);
    }

    public function course(string $id): void
    {
        $u=Auth::requireLogin();
        $en=Database::fetch(
            "SELECT e.*,c.name,c.summary,c.navigation_mode
               FROM enrollments e JOIN courses c ON c.id=e.course_id
              WHERE e.id=? AND e.user_id=?",
            [(int)$id,$u['id']]
        );
        if(!$en){http_response_code(404);exit('Matrícula não encontrada.');}

        Database::execute(
            "INSERT INTO study_events(enrollment_id,activity_id,event_type,event_data,ip_address,created_at)
             VALUES(?,NULL,'course.opened','{}',?,?)",
            [$en['id'],(string)($_SERVER['REMOTE_ADDR']??''),Clock::sql()]
        );

        $sections=Database::all(
            "SELECT * FROM course_sections WHERE course_id=? AND visible=1
             ORDER BY parent_id IS NOT NULL,parent_id,position,id",
            [$en['course_id']]
        );
        $byId=[];
        foreach($sections as $s){
            $s['activities']=Database::all(
                "SELECT a.*,COALESCE(p.completed,0) completed,
                        COALESCE(p.progress_percent,0) activity_progress,
                        COALESCE(p.seconds_spent,0) seconds_spent
                   FROM course_activities a
                   LEFT JOIN activity_progress p ON p.activity_id=a.id AND p.enrollment_id=?
                  WHERE a.section_id=? AND a.visible=1 ORDER BY a.position,a.id",
                [$en['id'],$s['id']]
            );
            $s['children']=[];
            $byId[(int)$s['id']]=$s;
        }
        $tree=[];
        foreach($byId as $sid=>&$s){
            $parent=(int)($s['parent_id']??0);
            if($parent&&isset($byId[$parent]))$byId[$parent]['children'][]=&$s;
            else $tree[]=&$s;
        }
        unset($s);
        View::render('student/course',['enrollment'=>$en,'sections'=>$tree]);
    }

    public function complete(string $id): void
    {
        $u=Auth::requireLogin();
        Csrf::verify();

        $activity=Database::fetch(
            "SELECT a.*,e.id enrollment_id,e.user_id,e.course_id
               FROM course_activities a
               JOIN enrollments e ON e.course_id=a.course_id
              WHERE a.id=? AND e.id=? AND e.user_id=?",
            [(int)$id,(int)($_POST['enrollment_id']??0),$u['id']]
        );
        if(!$activity){http_response_code(404);exit('Atividade inválida.');}
        if($activity['type']==='quiz')throw new \RuntimeException('A avaliação deve ser concluída pelo motor de quiz.');

        (new ProgressService())->completeActivity((int)$activity['enrollment_id'],(int)$activity['id'],100);

        Database::execute(
            "INSERT INTO study_events(enrollment_id,activity_id,event_type,event_data,ip_address,created_at)
             VALUES(?,?,'activity.completed','{}',?,?)",
            [$activity['enrollment_id'],$activity['id'],(string)($_SERVER['REMOTE_ADDR']??''),Clock::sql()]
        );

        redirect('/student/course/'.$activity['enrollment_id']);
    }
}
