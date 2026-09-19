<?php
namespace Tecnodata\Lms\Services;

use Tecnodata\Lms\Core\Auth;
use Tecnodata\Lms\Core\BiometricDatabase;
use Tecnodata\Lms\Core\Clock;
use Tecnodata\Lms\Core\Database;

final class BiometricPolicyService
{
    public function ensureCoursePolicy(int $courseId): void
    {
        $exists=Database::fetch(
            "SELECT id FROM course_biometric_settings WHERE course_id=?",
            [$courseId]
        );
        if($exists) return;

        $profile=Database::fetch("SELECT id FROM biometric_profiles WHERE code='BIO_NONE'");
        if(!$profile) return;

        Database::execute(
            "INSERT INTO course_biometric_settings(
                course_id,profile_id,enabled,overrides_json,created_at,updated_at
             ) VALUES(?,?,1,NULL,?,?)",
            [$courseId,$profile['id'],Clock::sql(),Clock::sql()]
        );
    }

    public function coursePolicy(int $courseId): array
    {
        $this->ensureCoursePolicy($courseId);

        $policy=Database::fetch(
            "SELECT s.course_id,s.enabled course_enabled,s.overrides_json,
                    p.*
               FROM course_biometric_settings s
               JOIN biometric_profiles p ON p.id=s.profile_id
              WHERE s.course_id=?",
            [$courseId]
        );

        return $policy ?: [
            'code'=>'BIO_NONE',
            'name'=>'Sem biometria',
            'verification_method'=>'none',
            'course_enabled'=>0,
        ];
    }

    public function userAction(int $userId,int $courseId): string
    {
        $policy=$this->coursePolicy($courseId);
        if(empty($policy['course_enabled']) || empty($policy['enabled']) || $policy['code']==='BIO_NONE'){
            return 'bypass';
        }

        $rule=Database::fetch(
            "SELECT rr.action
               FROM user_role_assignments ura
               JOIN roles r ON r.id=ura.role_id
               JOIN biometric_profile_role_rules rr ON rr.role_id=r.id
              WHERE ura.user_id=?
                AND rr.profile_id=?
                AND (
                    ura.context_type='system'
                    OR (ura.context_type='course' AND ura.context_id=?)
                )
              ORDER BY CASE rr.action WHEN 'bypass' THEN 0 ELSE 1 END
              LIMIT 1",
            [$userId,$policy['id'],$courseId]
        );

        return (string)($rule['action']??'require');
    }

    public function requirementForEnrollment(int $enrollmentId,string $checkpoint='course_entry'): array
    {
        $en=Database::fetch(
            "SELECT e.id,e.user_id,e.course_id,e.status,c.name course_name
               FROM enrollments e
               JOIN courses c ON c.id=e.course_id
              WHERE e.id=?",
            [$enrollmentId]
        );

        if(!$en) throw new \RuntimeException('Matrícula não encontrada.');

        $policy=$this->coursePolicy((int)$en['course_id']);
        $action=$this->userAction((int)$en['user_id'],(int)$en['course_id']);

        $required=$action==='require' && $this->checkpointRequired($policy,$checkpoint);

        $latest=null;
        if($required && BiometricDatabase::configured() && BiometricDatabase::ping()){
            $latest=BiometricDatabase::fetch(
                "SELECT challenge_uuid,status,verified_at,created_at,expires_at
                   FROM biometric_challenges
                  WHERE lms_enrollment_id=? AND checkpoint=?
                  ORDER BY id DESC LIMIT 1",
                [$enrollmentId,$checkpoint]
            );
        }

        return [
            'required'=>$required,
            'checkpoint'=>$checkpoint,
            'profile'=>[
                'code'=>$policy['code'],
                'name'=>$policy['name'],
                'verification_method'=>$policy['verification_method'],
                'liveness_required'=>(bool)($policy['liveness_required']??false),
                'max_failures'=>(int)($policy['max_failures']??3),
                'periodic_minutes'=>isset($policy['periodic_minutes'])?(int)$policy['periodic_minutes']:null,
            ],
            'role_action'=>$action,
            'biometric_database'=>BiometricDatabase::configured() ? (BiometricDatabase::ping()?'ready':'unavailable') : 'not_configured',
            'latest_validation'=>$latest,
        ];
    }

    private function checkpointRequired(array $policy,string $checkpoint): bool
    {
        return match($checkpoint){
            'first_access'=>(bool)($policy['first_access_required']??false),
            'course_entry'=>(bool)($policy['course_entry_required']??false),
            'before_quiz'=>(bool)($policy['before_quiz_required']??false),
            'periodic'=>(int)($policy['periodic_minutes']??0)>0,
            'random'=>(int)($policy['random_min_minutes']??0)>0,
            default=>false,
        };
    }
}
