<?php
namespace Tecnodata\Lms\Controllers;

use Tecnodata\Lms\Core\Auth;
use Tecnodata\Lms\Core\Clock;
use Tecnodata\Lms\Core\Csrf;
use Tecnodata\Lms\Core\Database;
use Tecnodata\Lms\Core\View;
use Tecnodata\Lms\Services\Audit;
use Tecnodata\Lms\Services\BiometricPolicyService;

final class CourseSettingsController
{
    public function show(string $id): void
    {
        Auth::requireAdmin();
        $course=$this->course((int)$id);
        (new BiometricPolicyService())->ensureCoursePolicy((int)$id);

        $policy=Database::fetch("SELECT * FROM course_policies WHERE course_id=?",[(int)$id]);
        $bio=Database::fetch(
            "SELECT s.*,p.code,p.name profile_name
               FROM course_biometric_settings s
               JOIN biometric_profiles p ON p.id=s.profile_id
              WHERE s.course_id=?",
            [(int)$id]
        );
        $bioProfiles=Database::all(
            "SELECT * FROM biometric_profiles WHERE enabled=1 ORDER BY id"
        );

        $files=Database::all(
            "SELECT cf.*,u.name uploaded_by_name
               FROM course_files cf
               LEFT JOIN users u ON u.id=cf.uploaded_by
              WHERE cf.course_id=?
              ORDER BY cf.id DESC",
            [(int)$id]
        );

        View::render('courses/settings',compact('course','policy','bio','bioProfiles','files'));
    }

    public function save(string $id): void
    {
        Auth::requireAdmin();
        Csrf::verify();
        $course=$this->course((int)$id);

        $status=(string)($_POST['status']??$course['status']);
        if(!in_array($status,['draft','published','hidden','archived'],true)){
            throw new \RuntimeException('Status inválido.');
        }

        $navigation=(string)($_POST['navigation_mode']??'linear');
        if(!in_array($navigation,['linear','free'],true)){
            throw new \RuntimeException('Navegação inválida.');
        }

        Database::execute(
            "UPDATE courses SET code=?,name=?,shortname=?,summary=?,status=?,navigation_mode=?,updated_at=? WHERE id=?",
            [
                strtoupper(trim((string)($_POST['code']??$course['code']))),
                trim((string)($_POST['name']??$course['name'])),
                trim((string)($_POST['shortname']??'')),
                trim((string)($_POST['summary']??'')),
                $status,
                $navigation,
                Clock::sql(),
                (int)$id
            ]
        );

        $maxDays=trim((string)($_POST['max_days']??''));
        $score=trim((string)($_POST['final_score_required']??''));

        Database::execute(
            "INSERT INTO course_policies(
                course_id,min_days,max_days,require_sequential,completion_percent,
                final_score_required,allow_retake,settings_json,created_at,updated_at
             ) VALUES(?,?,?,?,?,?,?,'{}',?,?)
             ON DUPLICATE KEY UPDATE
                min_days=VALUES(min_days),max_days=VALUES(max_days),
                require_sequential=VALUES(require_sequential),
                completion_percent=VALUES(completion_percent),
                final_score_required=VALUES(final_score_required),
                allow_retake=VALUES(allow_retake),updated_at=VALUES(updated_at)",
            [
                (int)$id,
                max(0,(int)($_POST['min_days']??0)),
                $maxDays!==''?max(1,(int)$maxDays):null,
                isset($_POST['require_sequential'])?1:0,
                min(100,max(0,(float)($_POST['completion_percent']??100))),
                $score!==''?(float)$score:null,
                isset($_POST['allow_retake'])?1:0,
                Clock::sql(),
                Clock::sql()
            ]
        );

        $profileId=(int)($_POST['biometric_profile_id']??0);
        $profile=Database::fetch("SELECT id FROM biometric_profiles WHERE id=?",[$profileId]);
        if($profile){
            Database::execute(
                "INSERT INTO course_biometric_settings(course_id,profile_id,enabled,overrides_json,created_at,updated_at)
                 VALUES(?,?,1,NULL,?,?)
                 ON DUPLICATE KEY UPDATE profile_id=VALUES(profile_id),enabled=1,updated_at=VALUES(updated_at)",
                [(int)$id,$profileId,Clock::sql(),Clock::sql()]
            );
        }

        Audit::log('course.settings_changed','course',(int)$id);
        redirect('/admin/courses/'.$id.'/settings');
    }

    private function course(int $id): array
    {
        $course=Database::fetch("SELECT * FROM courses WHERE id=?",[$id]);
        if(!$course) throw new \RuntimeException('Curso não encontrado.');
        return $course;
    }
}
