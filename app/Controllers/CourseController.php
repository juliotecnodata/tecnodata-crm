<?php
namespace Tecnodata\Lms\Controllers;

use Tecnodata\Lms\Core\Auth;
use Tecnodata\Lms\Core\Clock;
use Tecnodata\Lms\Core\Csrf;
use Tecnodata\Lms\Core\Database;
use Tecnodata\Lms\Core\View;
use Tecnodata\Lms\Services\Audit;

final class CourseController
{
    private function admin(): array { $u=Auth::requireLogin(); if(!Auth::isAdmin()) { http_response_code(403); exit('Acesso negado.'); } return $u; }

    public function index(): void
    {
        $this->admin();
        View::render('courses/index',['courses'=>Database::all("SELECT * FROM courses ORDER BY id DESC")]);
    }

    public function create(): void
    {
        $this->admin();
        View::render('courses/create');
    }

    public function store(): void
    {
        $this->admin(); Csrf::verify();
        Database::execute("INSERT INTO courses(code,name,shortname,summary,status,created_at,updated_at) VALUES(?,?,?,?,?,?,?)",[
            trim($_POST['code']??''),trim($_POST['name']??''),trim($_POST['shortname']??''),trim($_POST['summary']??''),'draft',Clock::sql(),Clock::sql()
        ]);
        $id=Database::id(); Audit::log('course.created','course',$id);
        redirect('/admin/courses/'.$id);
    }

    public function show(string $id): void
    {
        $this->admin();
        $course=Database::fetch("SELECT * FROM courses WHERE id=?",[(int)$id]);
        if(!$course){http_response_code(404);exit('Curso não encontrado.');}
        $sections=Database::all("SELECT * FROM course_sections WHERE course_id=? ORDER BY position,id",[(int)$id]);
        foreach($sections as &$s){
            $s['activities']=Database::all("SELECT * FROM course_activities WHERE section_id=? ORDER BY position,id",[$s['id']]);
        }
        View::render('courses/show',['course'=>$course,'sections'=>$sections]);
    }

    public function section(string $id): void
    {
        $this->admin(); Csrf::verify();
        $position=(int)(Database::fetch("SELECT COALESCE(MAX(position),0)+1 p FROM course_sections WHERE course_id=?",[(int)$id])['p']??1);
        Database::execute("INSERT INTO course_sections(course_id,title,summary,position,visible,created_at,updated_at) VALUES(?,?,?,?,1,?,?)",[(int)$id,trim($_POST['title']??'Nova seção'),trim($_POST['summary']??''),$position,Clock::sql(),Clock::sql()]);
        redirect('/admin/courses/'.$id);
    }

    public function activity(string $id): void
    {
        $this->admin(); Csrf::verify();
        $section=(int)($_POST['section_id']??0);
        $position=(int)(Database::fetch("SELECT COALESCE(MAX(position),0)+1 p FROM course_activities WHERE section_id=?",[$section])['p']??1);
        $type=(string)($_POST['type']??'page');
        $payload=$type==='video'?['url'=>trim($_POST['url']??'')]:['html'=>(string)($_POST['content']??'')];
        Database::execute("INSERT INTO course_activities(course_id,section_id,type,title,position,visible,content_json,settings_json,created_at,updated_at) VALUES(?,?,?,?,?,1,?,'{}',?,?)",[(int)$id,$section,$type,trim($_POST['title']??'Atividade'),$position,json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),Clock::sql(),Clock::sql()]);
        redirect('/admin/courses/'.$id);
    }
}
