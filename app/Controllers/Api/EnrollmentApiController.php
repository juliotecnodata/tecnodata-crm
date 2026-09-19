<?php
namespace Tecnodata\Lms\Controllers\Api;

use Tecnodata\Lms\Core\ApiAuth;
use Tecnodata\Lms\Core\Clock;
use Tecnodata\Lms\Core\Database;
use Tecnodata\Lms\Services\CourseRuleService;

final class EnrollmentApiController
{
    public function upsert(): void
    {
        $client=ApiAuth::client('enrollments:write');
        $in=ApiAuth::input();

        $cpf=preg_replace('/\D+/','',(string)($in['student']['cpf']??$in['cpf']??''));
        $code=trim((string)($in['course']['code']??$in['course_code']??''));

        if(strlen($cpf)!==11)ApiAuth::deny(422,'INVALID_CPF','CPF deve conter 11 dígitos.');
        if($code==='')ApiAuth::deny(422,'COURSE_REQUIRED','Código do curso é obrigatório.');

        $idem=trim((string)($_SERVER['HTTP_IDEMPOTENCY_KEY']??''));
        if($idem!==''){
            $prev=Database::fetch("SELECT response_json FROM idempotency_keys WHERE api_client_id=? AND idem_key=?",[$client['id'],$idem]);
            if($prev)ApiAuth::json(json_decode($prev['response_json'],true)?:[]);
        }

        $pdo=Database::pdo();
        $pdo->beginTransaction();

        try{
            $user=Database::fetch("SELECT * FROM users WHERE cpf=?",[$cpf]);
            $studentCreated=false;

            if(!$user){
                $name=trim((string)($in['student']['name']??''));
                if($name===''){
                    $pdo->rollBack();
                    ApiAuth::deny(404,'STUDENT_NOT_FOUND','Aluno não encontrado. Envie student.name para criá-lo nesta mesma operação.');
                }
                $email=trim((string)($in['student']['email']??''));
                Database::execute(
                    "INSERT INTO users(user_type,cpf,username,name,email,password_hash,status,created_at,updated_at)
                     VALUES('student',?,?,?,?,?,'active',?,?)",
                    [$cpf,$cpf,$name,$email!==''?$email:null,password_hash($cpf,PASSWORD_DEFAULT),Clock::sql(),Clock::sql()]
                );
                $user=Database::fetch("SELECT * FROM users WHERE id=?",[Database::id()]);
                $studentCreated=true;
            }else{
                $name=trim((string)($in['student']['name']??''));
                $email=trim((string)($in['student']['email']??''));
                if($name!==''||$email!==''){
                    Database::execute(
                        "UPDATE users SET name=CASE WHEN ?<>'' THEN ? ELSE name END,
                                          email=CASE WHEN ?<>'' THEN ? ELSE email END,
                                          updated_at=? WHERE id=?",
                        [$name,$name,$email,$email,Clock::sql(),$user['id']]
                    );
                }
            }

            $course=Database::fetch("SELECT * FROM courses WHERE code=? OR shortname=? LIMIT 1",[$code,$code]);
            if(!$course){
                $pdo->rollBack();
                ApiAuth::deny(404,'COURSE_NOT_FOUND','Curso não encontrado.',['code'=>$code]);
            }

            $source=trim((string)($in['source']['system']??'api'));
            $external=trim((string)($in['source']['external_id']??''));

            if($external!==''){
                $byRef=Database::fetch(
                    "SELECT e.* FROM enrollment_external_refs r
                     JOIN enrollments e ON e.id=r.enrollment_id
                     WHERE r.source_system=? AND r.external_id=? LIMIT 1",
                    [$source,$external]
                );
                if($byRef){
                    $pdo->commit();
                    $response=$this->response($byRef,false,$studentCreated,$user,$course);
                    $this->rememberIdempotency((int)$client['id'],$idem,$response);
                    ApiAuth::json($response);
                }
            }

            $en=Database::fetch(
                "SELECT * FROM enrollments WHERE user_id=? AND course_id=? AND status IN('active','completed') ORDER BY id DESC LIMIT 1",
                [$user['id'],$course['id']]
            );

            $created=false;
            if(!$en){
                $started=Clock::sql();
                $expires=(new CourseRuleService())->expiresAt((int)$course['id'],$started);
                Database::execute(
                    "INSERT INTO enrollments(user_id,course_id,status,progress_percent,started_at,expires_at,created_at,updated_at)
                     VALUES(?,?,'active',0,?,?,?,?,?)",
                    [$user['id'],$course['id'],$started,$expires,Clock::sql(),Clock::sql()]
                );
                $en=Database::fetch("SELECT * FROM enrollments WHERE id=?",[Database::id()]);
                $created=true;
                Database::execute(
                    "INSERT INTO enrollment_status_history(enrollment_id,old_status,new_status,reason,changed_by,created_at)
                     VALUES(?,NULL,'active','Criada via API',NULL,?)",
                    [$en['id'],Clock::sql()]
                );
            }

            $this->assignStudentRole((int)$user['id'],(int)$course['id']);

            if($external!==''){
                Database::execute(
                    "INSERT IGNORE INTO enrollment_external_refs(enrollment_id,source_system,external_id,created_at)
                     VALUES(?,?,?,?)",
                    [$en['id'],$source,$external,Clock::sql()]
                );
            }

            $pdo->commit();
            $response=$this->response($en,$created,$studentCreated,$user,$course);
            $this->rememberIdempotency((int)$client['id'],$idem,$response);
            ApiAuth::json($response);

        }catch(\Throwable $e){
            if($pdo->inTransaction())$pdo->rollBack();
            throw $e;
        }
    }

    public function progress(string $id): void
    {
        ApiAuth::client('enrollments:read');

        $e=Database::fetch(
            "SELECT e.*,u.cpf,u.name student,c.code course_code,c.name course_name
               FROM enrollments e JOIN users u ON u.id=e.user_id JOIN courses c ON c.id=e.course_id
              WHERE e.id=?",
            [(int)$id]
        );
        if(!$e)ApiAuth::deny(404,'ENROLLMENT_NOT_FOUND','Matrícula não encontrada.');

        $activities=Database::all(
            "SELECT a.id,a.type,a.title,COALESCE(p.progress_percent,0) progress_percent,
                    COALESCE(p.completed,0) completed,p.seconds_spent,p.completed_at
               FROM course_activities a
               LEFT JOIN activity_progress p ON p.activity_id=a.id AND p.enrollment_id=?
              WHERE a.course_id=? AND a.visible=1 ORDER BY a.section_id,a.position,a.id",
            [$e['id'],$e['course_id']]
        );

        $studySeconds=(int)(Database::fetch(
            "SELECT COALESCE(SUM(active_seconds),0) s FROM study_sessions WHERE enrollment_id=?",
            [$e['id']]
        )['s']??0);

        $rule=(new CourseRuleService())->eligibility((int)$e['id']);

        ApiAuth::json([
            'success'=>true,
            'enrollment'=>[
                'id'=>(int)$e['id'],'status'=>$e['status'],'progress'=>(float)$e['progress_percent'],
                'final_score'=>$e['final_score']!==null?(float)$e['final_score']:null,
                'study_seconds'=>$studySeconds,
                'started_at'=>Clock::iso($e['started_at']),'expires_at'=>Clock::iso($e['expires_at']),
                'completed_at'=>Clock::iso($e['completed_at'])
            ],
            'student'=>['cpf'=>$e['cpf'],'name'=>$e['student']],
            'course'=>['code'=>$e['course_code'],'name'=>$e['course_name']],
            'rules'=>$rule,
            'activities'=>$activities,
        ]);
    }

    public function eligibility(): void
    {
        ApiAuth::client('certification:read');

        $cpf=preg_replace('/\D+/','',(string)($_GET['cpf']??''));
        $code=trim((string)($_GET['course']??''));

        $e=Database::fetch(
            "SELECT e.*,c.code,c.name,u.name student_name,u.cpf
               FROM enrollments e JOIN users u ON u.id=e.user_id JOIN courses c ON c.id=e.course_id
              WHERE u.cpf=? AND (c.code=? OR c.shortname=?) ORDER BY e.id DESC LIMIT 1",
            [$cpf,$code,$code]
        );
        if(!$e)ApiAuth::deny(404,'ENROLLMENT_NOT_FOUND','Matrícula não encontrada.');

        $rules=(new CourseRuleService())->eligibility((int)$e['id']);

        ApiAuth::json([
            'success'=>true,
            'eligible'=>$rules['eligible'],
            'reason_codes'=>$rules['reason_codes'],
            'rules'=>$rules,
            'enrollment'=>[
                'id'=>(int)$e['id'],'status'=>$e['status'],'progress'=>(float)$e['progress_percent'],
                'final_score'=>$e['final_score']!==null?(float)$e['final_score']:null,
                'started_at'=>Clock::iso($e['started_at']),'expires_at'=>Clock::iso($e['expires_at']),
                'completed_at'=>Clock::iso($e['completed_at'])
            ],
            'student'=>['cpf'=>$e['cpf'],'name'=>$e['student_name']],
            'course'=>['code'=>$e['code'],'name'=>$e['name']],
        ]);
    }

    private function response(array $en,bool $created,bool $studentCreated,array $user,array $course): array
    {
        return [
            'success'=>true,
            'student'=>['id'=>(int)$user['id'],'created'=>$studentCreated,'cpf'=>$user['cpf']],
            'enrollment'=>[
                'id'=>(int)$en['id'],'created'=>$created,'status'=>$en['status'],
                'progress'=>(float)($en['progress_percent']??0),
                'started_at'=>Clock::iso($en['started_at']??null),
                'expires_at'=>Clock::iso($en['expires_at']??null)
            ],
            'course'=>['id'=>(int)$course['id'],'code'=>$course['code'],'name'=>$course['name']],
        ];
    }

    private function rememberIdempotency(int $clientId,string $idem,array $response): void
    {
        if($idem==='')return;
        Database::execute(
            "INSERT IGNORE INTO idempotency_keys(api_client_id,idem_key,response_json,created_at) VALUES(?,?,?,?)",
            [$clientId,$idem,json_encode($response,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),Clock::sql()]
        );
    }

    private function assignStudentRole(int $userId,int $courseId): void
    {
        $role=Database::fetch("SELECT id FROM roles WHERE slug='student'");
        if(!$role)return;
        if(!Database::fetch(
            "SELECT 1 FROM user_role_assignments WHERE user_id=? AND role_id=? AND context_type='course' AND context_id=? LIMIT 1",
            [$userId,$role['id'],$courseId]
        )){
            Database::execute(
                "INSERT INTO user_role_assignments(user_id,role_id,context_type,context_id,created_at) VALUES(?,?,'course',?,?)",
                [$userId,$role['id'],$courseId,Clock::sql()]
            );
        }
    }
}
