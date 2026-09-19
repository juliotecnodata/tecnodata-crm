<?php
namespace Tecnodata\Lms\Services;

use DateTimeImmutable;
use Tecnodata\Lms\Core\Clock;
use Tecnodata\Lms\Core\Database;

final class CourseRuleService
{
    public function policy(int $courseId): array
    {
        $p=Database::fetch("SELECT * FROM course_policies WHERE course_id=?",[$courseId]);
        return $p?:[
            'course_id'=>$courseId,
            'min_days'=>0,
            'max_days'=>null,
            'require_sequential'=>0,
            'completion_percent'=>100,
            'final_score_required'=>null,
            'allow_retake'=>1,
            'settings_json'=>'{}',
        ];
    }

    public function expiresAt(int $courseId,?string $startedAt=null): ?string
    {
        $p=$this->policy($courseId);
        $max=(int)($p['max_days']??0);
        if($max<=0)return null;

        $start=$startedAt
            ? new DateTimeImmutable($startedAt)
            : Clock::now();

        return $start->modify('+'.$max.' days')->format('Y-m-d H:i:s');
    }

    public function eligibility(int $enrollmentId): array
    {
        $e=Database::fetch(
            "SELECT e.*,c.name course_name,c.navigation_mode
               FROM enrollments e
               JOIN courses c ON c.id=e.course_id
              WHERE e.id=?",
            [$enrollmentId]
        );
        if(!$e)throw new \RuntimeException('Matrícula não encontrada.');

        $p=$this->policy((int)$e['course_id']);
        $reasons=[];

        if(in_array($e['status'],['cancelled','suspended','expired'],true)){
            $reasons[]='ENROLLMENT_'.strtoupper($e['status']);
        }

        if((float)$e['progress_percent']<(float)$p['completion_percent']){
            $reasons[]='PROGRESS_INCOMPLETE';
        }

        if($p['final_score_required']!==null){
            if($e['final_score']===null||(float)$e['final_score']<(float)$p['final_score_required']){
                $reasons[]='FINAL_SCORE_BELOW_REQUIRED';
            }
        }

        $days=$this->calendarDays((string)($e['started_at']?:$e['created_at']));
        if((int)$p['min_days']>0 && $days<(int)$p['min_days']){
            $reasons[]='MINIMUM_DAYS_NOT_REACHED';
        }

        $expired=false;
        if($e['expires_at']){
            $expired=Clock::now()->getTimestamp()>(new DateTimeImmutable($e['expires_at']))->getTimestamp();
        }elseif((int)($p['max_days']??0)>0){
            $expired=$days>(int)$p['max_days'];
        }
        if($expired)$reasons[]='ENROLLMENT_EXPIRED';

        return [
            'eligible'=>$reasons===[],
            'reason_codes'=>array_values(array_unique($reasons)),
            'days_in_course'=>$days,
            'min_days'=>(int)$p['min_days'],
            'max_days'=>$p['max_days']!==null?(int)$p['max_days']:null,
            'required_progress'=>(float)$p['completion_percent'],
            'required_score'=>$p['final_score_required']!==null?(float)$p['final_score_required']:null,
            'expires_at'=>$e['expires_at'],
        ];
    }

    public function canAccessActivity(int $enrollmentId,int $activityId): array
    {
        $e=Database::fetch(
            "SELECT e.*,c.navigation_mode
               FROM enrollments e JOIN courses c ON c.id=e.course_id
              WHERE e.id=?",
            [$enrollmentId]
        );
        if(!$e)return ['allowed'=>false,'reason'=>'ENROLLMENT_NOT_FOUND'];

        $p=$this->policy((int)$e['course_id']);
        if($e['status']!=='active' && $e['status']!=='completed'){
            return ['allowed'=>false,'reason'=>'ENROLLMENT_NOT_ACTIVE'];
        }

        $elig=$this->eligibilityForAccess($e,$p);
        if(!$elig['allowed'])return $elig;

        if($e['navigation_mode']==='free' && empty($p['require_sequential'])){
            return ['allowed'=>true,'reason'=>null];
        }

        $rows=Database::all(
            "SELECT a.id,
                    COALESCE(parent.position,s.position) major_pos,
                    CASE WHEN s.parent_id IS NULL THEN 0 ELSE 1 END depth_pos,
                    s.position section_pos,a.position activity_pos
               FROM course_activities a
               JOIN course_sections s ON s.id=a.section_id
               LEFT JOIN course_sections parent ON parent.id=s.parent_id
              WHERE a.course_id=? AND a.visible=1 AND s.visible=1
              ORDER BY major_pos,depth_pos,section_pos,activity_pos,a.id",
            [$e['course_id']]
        );

        foreach($rows as $row){
            if((int)$row['id']===$activityId)return ['allowed'=>true,'reason'=>null];

            $done=Database::fetch(
                "SELECT completed FROM activity_progress
                  WHERE enrollment_id=? AND activity_id=?",
                [$enrollmentId,$row['id']]
            );
            if(!$done||!(int)$done['completed']){
                return ['allowed'=>false,'reason'=>'PREVIOUS_ACTIVITY_INCOMPLETE','blocked_by'=>(int)$row['id']];
            }
        }

        return ['allowed'=>false,'reason'=>'ACTIVITY_NOT_FOUND'];
    }

    private function eligibilityForAccess(array $e,array $p): array
    {
        if($e['expires_at'] && Clock::now()->getTimestamp()>(new DateTimeImmutable($e['expires_at']))->getTimestamp()){
            return ['allowed'=>false,'reason'=>'ENROLLMENT_EXPIRED'];
        }
        return ['allowed'=>true,'reason'=>null];
    }

    private function calendarDays(string $start): int
    {
        if($start==='')return 0;
        $a=(new DateTimeImmutable($start))->setTime(0,0);
        $b=Clock::now()->setTime(0,0);
        if($b<$a)return 0;
        return $a->diff($b)->days+1;
    }
}
