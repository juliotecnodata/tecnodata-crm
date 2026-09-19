<?php
namespace Tecnodata\Lms\Controllers\Api;

use Tecnodata\Lms\Core\ApiAuth;
use Tecnodata\Lms\Core\Clock;
use Tecnodata\Lms\Core\Database;

final class EnrollmentApiController
{
    public function upsert(): void
    {
        $client=ApiAuth::client('enrollments:write');
        $in=ApiAuth::input();
        $cpf=preg_replace('/\D+/','',(string)($in['student']['cpf']??$in['cpf']??''));
        $code=(string)($in['course']['code']??$in['course_code']??'');
        $user=Database::fetch("SELECT * FROM users WHERE cpf=?",[$cpf]);
        if(!$user) ApiAuth::deny(404,'STUDENT_NOT_FOUND','Aluno não encontrado.');
        $course=Database::fetch("SELECT * FROM courses WHERE code=? OR shortname=? LIMIT 1",[$code,$code]);
        if(!$course) ApiAuth::deny(404,'COURSE_NOT_FOUND','Curso não encontrado.',['code'=>$code]);

        $idem=trim((string)($_SERVER['HTTP_IDEMPOTENCY_KEY']??''));
        if($idem){
            $prev=Database::fetch("SELECT response_json FROM idempotency_keys WHERE api_client_id=? AND idem_key=?",[$client['id'],$idem]);
            if($prev) ApiAuth::json(json_decode($prev['response_json'],true)?:[]);
        }

        $en=Database::fetch("SELECT * FROM enrollments WHERE user_id=? AND course_id=? AND status IN('active','completed') ORDER BY id DESC LIMIT 1",[$user['id'],$course['id']]);
        $created=false;
        if(!$en){
            Database::execute("INSERT INTO enrollments(user_id,course_id,status,progress_percent,started_at,created_at,updated_at) VALUES(?,?,'active',0,?,?,?)",[$user['id'],$course['id'],Clock::sql(),Clock::sql(),Clock::sql()]);
            $en=['id'=>Database::id(),'status'=>'active','progress_percent'=>0]; $created=true;
        }
        $source=(string)($in['source']['system']??'api');
        $external=(string)($in['source']['external_id']??'');
        if($external!==''){
            Database::execute("INSERT IGNORE INTO enrollment_external_refs(enrollment_id,source_system,external_id,created_at) VALUES(?,?,?,?)",[$en['id'],$source,$external,Clock::sql()]);
        }
        $response=['success'=>true,'enrollment'=>['id'=>(int)$en['id'],'created'=>$created,'status'=>$en['status']]];
        if($idem){
            Database::execute("INSERT IGNORE INTO idempotency_keys(api_client_id,idem_key,response_json,created_at) VALUES(?,?,?,?)",[$client['id'],$idem,json_encode($response),Clock::sql()]);
        }
        ApiAuth::json($response);
    }

    public function progress(string $id): void
    {
        ApiAuth::client('enrollments:read');
        $e=Database::fetch("SELECT e.*,u.cpf,u.name student,c.code course_code,c.name course_name FROM enrollments e JOIN users u ON u.id=e.user_id JOIN courses c ON c.id=e.course_id WHERE e.id=?",[(int)$id]);
        if(!$e) ApiAuth::deny(404,'ENROLLMENT_NOT_FOUND','Matrícula não encontrada.');
        ApiAuth::json(['success'=>true,'enrollment'=>['id'=>(int)$e['id'],'status'=>$e['status'],'progress'=>(float)$e['progress_percent'],'started_at'=>\Tecnodata\Lms\Core\Clock::iso($e['started_at']),'completed_at'=>\Tecnodata\Lms\Core\Clock::iso($e['completed_at'])],'student'=>['cpf'=>$e['cpf'],'name'=>$e['student']],'course'=>['code'=>$e['course_code'],'name'=>$e['course_name']]]);
    }

    public function eligibility(): void
    {
        ApiAuth::client('certification:read');
        $cpf=preg_replace('/\D+/','',(string)($_GET['cpf']??''));
        $code=(string)($_GET['course']??'');
        $e=Database::fetch("SELECT e.*,c.code,c.name FROM enrollments e JOIN users u ON u.id=e.user_id JOIN courses c ON c.id=e.course_id WHERE u.cpf=? AND (c.code=? OR c.shortname=?) ORDER BY e.id DESC LIMIT 1",[$cpf,$code,$code]);
        if(!$e) ApiAuth::deny(404,'ENROLLMENT_NOT_FOUND','Matrícula não encontrada.');
        $eligible=$e['status']==='completed' && (float)$e['progress_percent']>=100;
        ApiAuth::json(['success'=>true,'eligible'=>$eligible,'enrollment_id'=>(int)$e['id'],'status'=>$e['status'],'progress'=>(float)$e['progress_percent'],'started_at'=>\Tecnodata\Lms\Core\Clock::iso($e['started_at']),'completed_at'=>\Tecnodata\Lms\Core\Clock::iso($e['completed_at'])]);
    }
}
