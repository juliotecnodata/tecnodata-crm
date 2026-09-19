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
        $course=$this->course((int)$id);
        $sections=Database::all("SELECT * FROM course_sections WHERE course_id=? ORDER BY parent_id IS NOT NULL,parent_id,position,id",[$course['id']]);
        foreach($sections as &$s)$s['activities']=Database::all("SELECT * FROM course_activities WHERE section_id=? ORDER BY position,id",[$s['id']]);
        unset($s);
        $files=Database::all("SELECT id,original_name,mime_type,size_bytes FROM course_files WHERE course_id=? ORDER BY original_name",[$course['id']]);
        View::render('courses/show',compact('course','sections','files'));
    }

    public function section(string $id): void
    {
        $this->admin();Csrf::verify();
        $course=$this->course((int)$id);
        $parentId=(int)($_POST['parent_id']??0);
        if($parentId>0&&!Database::fetch("SELECT id FROM course_sections WHERE id=? AND course_id=?",[$parentId,$course['id']]))throw new \RuntimeException('Seção pai inválida.');
        $params=$parentId>0?[$course['id'],$parentId]:[$course['id']];
        $position=(int)(Database::fetch("SELECT COALESCE(MAX(position),0)+1 p FROM course_sections WHERE course_id=? AND ".($parentId>0?"parent_id=?":"parent_id IS NULL"),$params)['p']??1);
        Database::execute(
            "INSERT INTO course_sections(course_id,parent_id,title,summary,position,visible,created_at,updated_at) VALUES(?,?,?,?,?,1,?,?)",
            [$course['id'],$parentId?:null,trim((string)($_POST['title']??'Nova seção')),trim((string)($_POST['summary']??'')),$position,Clock::sql(),Clock::sql()]
        );
        Audit::log('course.section_created','course',$course['id']);
        redirect('/admin/courses/'.$course['id']);
    }

    public function updateSection(string $sectionId): void
    {
        $this->admin();Csrf::verify();
        $s=Database::fetch("SELECT * FROM course_sections WHERE id=?",[(int)$sectionId]);
        if(!$s)throw new \RuntimeException('Seção não encontrada.');

        $parent=(int)($_POST['parent_id']??0);
        if($parent===(int)$sectionId)throw new \RuntimeException('A seção não pode ser pai de si mesma.');
        if($parent>0&&!Database::fetch("SELECT 1 FROM course_sections WHERE id=? AND course_id=?",[$parent,$s['course_id']]))throw new \RuntimeException('Seção pai inválida.');

        Database::execute(
            "UPDATE course_sections SET parent_id=?,title=?,summary=?,position=?,visible=?,updated_at=? WHERE id=?",
            [
                $parent?:null,
                trim((string)($_POST['title']??$s['title'])),
                trim((string)($_POST['summary']??$s['summary'])),
                max(0,(int)($_POST['position']??$s['position'])),
                isset($_POST['visible'])?1:0,
                Clock::sql(),
                (int)$sectionId
            ]
        );
        Audit::log('course.section_updated','course_section',(int)$sectionId);
        redirect('/admin/courses/'.$s['course_id']);
    }

    public function deleteSection(string $sectionId): void
    {
        $this->admin();Csrf::verify();
        $s=Database::fetch("SELECT * FROM course_sections WHERE id=?",[(int)$sectionId]);
        if(!$s)throw new \RuntimeException('Seção não encontrada.');

        $progress=Database::fetch(
            "SELECT 1 FROM activity_progress p
               JOIN course_activities a ON a.id=p.activity_id
              WHERE a.section_id=? LIMIT 1",
            [(int)$sectionId]
        );
        if($progress)throw new \RuntimeException('Esta seção já possui progresso de alunos. Oculte-a em vez de excluir.');

        Database::execute("DELETE FROM course_sections WHERE id=?",[(int)$sectionId]);
        Audit::log('course.section_deleted','course_section',(int)$sectionId);
        redirect('/admin/courses/'.$s['course_id']);
    }

    public function activity(string $id): void
    {
        $this->admin();Csrf::verify();
        $course=$this->course((int)$id);
        $section=(int)($_POST['section_id']??0);
        if(!Database::fetch("SELECT id FROM course_sections WHERE id=? AND course_id=?",[$section,$course['id']]))throw new \RuntimeException('Seção inválida.');

        $position=(int)(Database::fetch("SELECT COALESCE(MAX(position),0)+1 p FROM course_activities WHERE section_id=?",[$section])['p']??1);
        $type=(string)($_POST['type']??'page');
        $allowed=['page','video','url','quiz','resource','book','lesson','h5pactivity','scorm','label','folder'];
        if(!in_array($type,$allowed,true))throw new \RuntimeException('Tipo de atividade inválido.');

        $payload=$this->activityPayload($course['id'],$type,$_POST);
        $settings=$type==='video'?['provider'=>'videofront']:[];

        Database::execute(
            "INSERT INTO course_activities(course_id,section_id,type,title,description,position,visible,completion_mode,content_json,settings_json,created_at,updated_at)
             VALUES(?,?,?,?,?,?,1,'manual',?,?,?,?)",
            [$course['id'],$section,$type,trim((string)($_POST['title']??'Atividade')),trim((string)($_POST['description']??'')),$position,json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),json_encode($settings,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),Clock::sql(),Clock::sql()]
        );
        $activityId=Database::id();
        Audit::log('course.activity_created','course_activity',$activityId,['course_id'=>$course['id'],'type'=>$type]);

        if($type==='quiz'){
            Database::execute("INSERT INTO quizzes(activity_id,grade_max,grade_pass,attempts_allowed,settings_json) VALUES(?,100,70,0,'{}')",[$activityId]);
            redirect('/admin/activities/'.$activityId.'/quiz');
        }
        redirect('/admin/courses/'.$course['id']);
    }

    public function updateActivity(string $activityId): void
    {
        $this->admin();Csrf::verify();
        $a=Database::fetch("SELECT * FROM course_activities WHERE id=?",[(int)$activityId]);
        if(!$a)throw new \RuntimeException('Atividade não encontrada.');

        $payload=$this->activityPayload((int)$a['course_id'],(string)$a['type'],$_POST);
        $settings=json_decode($a['settings_json']?:'{}',true)?:[];
        if($a['type']==='video')$settings['provider']='videofront';

        Database::execute(
            "UPDATE course_activities SET title=?,description=?,position=?,visible=?,completion_mode=?,content_json=?,settings_json=?,updated_at=? WHERE id=?",
            [
                trim((string)($_POST['title']??$a['title'])),
                trim((string)($_POST['description']??$a['description'])),
                max(0,(int)($_POST['position']??$a['position'])),
                isset($_POST['visible'])?1:0,
                (string)($_POST['completion_mode']??$a['completion_mode']),
                json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
                json_encode($settings,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
                Clock::sql(),
                (int)$activityId
            ]
        );
        Audit::log('course.activity_updated','course_activity',(int)$activityId);
        redirect('/admin/courses/'.$a['course_id']);
    }

    public function deleteActivity(string $activityId): void
    {
        $this->admin();Csrf::verify();
        $a=Database::fetch("SELECT * FROM course_activities WHERE id=?",[(int)$activityId]);
        if(!$a)throw new \RuntimeException('Atividade não encontrada.');

        if(Database::fetch("SELECT 1 FROM activity_progress WHERE activity_id=? LIMIT 1",[(int)$activityId])){
            throw new \RuntimeException('Atividade já possui progresso de alunos. Oculte-a em vez de excluir.');
        }

        Database::execute("DELETE FROM course_activities WHERE id=?",[(int)$activityId]);
        Audit::log('course.activity_deleted','course_activity',(int)$activityId);
        redirect('/admin/courses/'.$a['course_id']);
    }

    private function activityPayload(int $courseId,string $type,array $input): array
    {
        if(in_array($type,['video','url'],true)){
            return ['url'=>trim((string)($input['url']??''))];
        }
        if($type==='resource'){
            $fileId=(int)($input['file_id']??0);
            if($fileId&&!Database::fetch("SELECT 1 FROM course_files WHERE id=? AND course_id=?",[$fileId,$courseId])){
                throw new \RuntimeException('Arquivo inválido.');
            }
            return ['file_id'=>$fileId?:null,'html'=>(string)($input['content']??'')];
        }
        return ['html'=>(string)($input['content']??'')];
    }

    private function course(int $id): array
    {
        $c=Database::fetch("SELECT * FROM courses WHERE id=?",[$id]);
        if(!$c)throw new \RuntimeException('Curso não encontrado.');
        return $c;
    }
}
