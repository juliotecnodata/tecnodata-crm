<?php
namespace Tecnodata\Lms\Controllers;

use Tecnodata\Lms\Core\Auth;
use Tecnodata\Lms\Core\Clock;
use Tecnodata\Lms\Core\Csrf;
use Tecnodata\Lms\Core\Database;
use Tecnodata\Lms\Core\View;
use Tecnodata\Lms\Services\Audit;
use Tecnodata\Lms\Services\BiometricPolicyService;

final class CourseController
{
    private function admin(): array{return Auth::requireAdmin();}

    public function index(): void
    {
        $this->admin();
        View::render('courses/index',['courses'=>Database::all("SELECT * FROM courses ORDER BY id DESC")]);
    }

    public function create(): void{$this->admin();View::render('courses/create');}

    public function store(): void
    {
        $this->admin();Csrf::verify();
        $code=strtoupper(trim((string)($_POST['code']??'')));
        $name=trim((string)($_POST['name']??''));
        if($code===''||$name==='')throw new \RuntimeException('Código e nome são obrigatórios.');
        if(Database::fetch("SELECT 1 FROM courses WHERE code=?",[$code]))throw new \RuntimeException('Já existe um curso com este código.');

        Database::execute(
            "INSERT INTO courses(code,name,shortname,summary,status,created_at,updated_at) VALUES(?,?,?,?,?,?,?)",
            [$code,$name,trim((string)($_POST['shortname']??'')),trim((string)($_POST['summary']??'')),'draft',Clock::sql(),Clock::sql()]
        );
        $id=Database::id();
        Database::execute(
            "INSERT IGNORE INTO course_policies(course_id,min_days,max_days,require_sequential,completion_percent,allow_retake,settings_json,created_at,updated_at)
             VALUES(?,0,NULL,0,100,1,'{}',?,?)",
            [$id,Clock::sql(),Clock::sql()]
        );
        (new BiometricPolicyService())->ensureCoursePolicy($id);
        Audit::log('course.created','course',$id);
        redirect('/admin/courses/'.$id);
    }

    public function show(string $id): void
    {
        $this->admin();
        $course=Database::fetch("SELECT * FROM courses WHERE id=?",[(int)$id]);
        if(!$course){http_response_code(404);exit('Curso não encontrado.');}
        $sections=Database::all("SELECT * FROM course_sections WHERE course_id=? ORDER BY parent_id IS NOT NULL,parent_id,position,id",[(int)$id]);
        foreach($sections as &$s)$s['activities']=Database::all("SELECT * FROM course_activities WHERE section_id=? ORDER BY position,id",[$s['id']]);
        unset($s);
        View::render('courses/show',['course'=>$course,'sections'=>$sections]);
    }

    public function section(string $id): void
    {
        $this->admin();Csrf::verify();
        $parentId=(int)($_POST['parent_id']??0);
        if($parentId>0&&!Database::fetch("SELECT id FROM course_sections WHERE id=? AND course_id=?",[$parentId,(int)$id]))throw new \RuntimeException('Seção pai inválida.');
        $params=$parentId>0?[(int)$id,$parentId]:[(int)$id];
        $position=(int)(Database::fetch("SELECT COALESCE(MAX(position),0)+1 p FROM course_sections WHERE course_id=? AND ".($parentId>0?"parent_id=?":"parent_id IS NULL"),$params)['p']??1);
        Database::execute(
            "INSERT INTO course_sections(course_id,parent_id,title,summary,position,visible,created_at,updated_at) VALUES(?,?,?,?,?,1,?,?)",
            [(int)$id,$parentId?:null,trim((string)($_POST['title']??'Nova seção')),trim((string)($_POST['summary']??'')),$position,Clock::sql(),Clock::sql()]
        );
        redirect('/admin/courses/'.$id);
    }

    public function activity(string $id): void
    {
        $this->admin();Csrf::verify();
        $section=(int)($_POST['section_id']??0);
        if(!Database::fetch("SELECT id FROM course_sections WHERE id=? AND course_id=?",[$section,(int)$id]))throw new \RuntimeException('Seção inválida.');
        $position=(int)(Database::fetch("SELECT COALESCE(MAX(position),0)+1 p FROM course_activities WHERE section_id=?",[$section])['p']??1);
        $type=(string)($_POST['type']??'page');
        $allowed=['page','video','url','quiz','resource','book','lesson','h5pactivity','scorm','label','folder'];
        if(!in_array($type,$allowed,true))throw new \RuntimeException('Tipo de atividade inválido.');
        $payload=in_array($type,['video','url'],true)?['url'=>trim((string)($_POST['url']??''))]:['html'=>(string)($_POST['content']??'')];
        $settings=$type==='video'?['provider'=>'videofront']:[];
        Database::execute(
            "INSERT INTO course_activities(course_id,section_id,type,title,description,position,visible,completion_mode,content_json,settings_json,created_at,updated_at)
             VALUES(?,?,?,?,?,?,1,'manual',?,?,?,?)",
            [(int)$id,$section,$type,trim((string)($_POST['title']??'Atividade')),trim((string)($_POST['description']??'')),$position,json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),json_encode($settings,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),Clock::sql(),Clock::sql()]
        );
        $activityId=Database::id();
        if($type==='quiz'){
            Database::execute("INSERT INTO quizzes(activity_id,grade_max,grade_pass,attempts_allowed,settings_json) VALUES(?,100,70,0,'{}')",[$activityId]);
            redirect('/admin/activities/'.$activityId.'/quiz');
        }
        redirect('/admin/courses/'.$id);
    }
}
