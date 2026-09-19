<?php
namespace Tecnodata\Lms\Services;

use Tecnodata\Lms\Core\Clock;
use Tecnodata\Lms\Core\Database;

final class ProgressService
{
    public function completeActivity(int $enrollmentId,int $activityId,float $progress=100.0,int $seconds=0): array
    {
        $activity=Database::fetch(
            "SELECT a.*,e.course_id,e.user_id
               FROM course_activities a JOIN enrollments e ON e.course_id=a.course_id
              WHERE a.id=? AND e.id=?",
            [$activityId,$enrollmentId]
        );
        if(!$activity)throw new \RuntimeException('Atividade/matrícula inválida.');

        $access=(new CourseRuleService())->canAccessActivity($enrollmentId,$activityId);
        if(!$access['allowed'])throw new \RuntimeException('Atividade bloqueada pela regra de sequência ou prazo.');

        $completed=$progress>=100?1:0;
        Database::execute(
            "INSERT INTO activity_progress(
                enrollment_id,activity_id,progress_percent,completed,seconds_spent,completed_at,updated_at
             ) VALUES(?,?,?,?,?,?,?)
             ON DUPLICATE KEY UPDATE
                progress_percent=GREATEST(progress_percent,VALUES(progress_percent)),
                completed=GREATEST(completed,VALUES(completed)),
                seconds_spent=seconds_spent+VALUES(seconds_spent),
                completed_at=CASE WHEN completed_at IS NULL AND VALUES(completed)=1 THEN VALUES(completed_at) ELSE completed_at END,
                updated_at=VALUES(updated_at)",
            [$enrollmentId,$activityId,min(100,max(0,$progress)),$completed,max(0,$seconds),$completed?Clock::sql():null,Clock::sql()]
        );
        return $this->recalculateEnrollment($enrollmentId);
    }

    public function recalculateEnrollment(int $enrollmentId): array
    {
        $en=Database::fetch("SELECT * FROM enrollments WHERE id=?",[$enrollmentId]);
        if(!$en)throw new \RuntimeException('Matrícula não encontrada.');

        $tot=(int)(Database::fetch("SELECT COUNT(*) c FROM course_activities WHERE course_id=? AND visible=1",[$en['course_id']])['c']??0);
        $done=(int)(Database::fetch(
            "SELECT COUNT(*) c FROM activity_progress p
              JOIN course_activities a ON a.id=p.activity_id
             WHERE p.enrollment_id=? AND a.course_id=? AND a.visible=1 AND p.completed=1",
            [$enrollmentId,$en['course_id']]
        )['c']??0);

        $pct=$tot?round($done*100/$tot,2):0;
        Database::execute("UPDATE enrollments SET progress_percent=?,updated_at=? WHERE id=?",[$pct,Clock::sql(),$enrollmentId]);

        $rule=(new CourseRuleService())->eligibility($enrollmentId);
        $current=Database::fetch("SELECT status,completed_at FROM enrollments WHERE id=?",[$enrollmentId]);

        if($rule['eligible'] && !in_array($current['status'],['cancelled','suspended','expired'],true)){
            Database::execute(
                "UPDATE enrollments SET status='completed',completed_at=COALESCE(completed_at,?),updated_at=? WHERE id=?",
                [Clock::sql(),Clock::sql(),$enrollmentId]
            );
            $status='completed';
        }else{
            $status=$current['status']==='completed'?'active':$current['status'];
            if($current['status']==='completed'){
                Database::execute("UPDATE enrollments SET status='active',completed_at=NULL,updated_at=? WHERE id=?",[Clock::sql(),$enrollmentId]);
            }
        }

        return ['total'=>$tot,'completed'=>$done,'progress'=>$pct,'status'=>$status,'rules'=>$rule];
    }
}
