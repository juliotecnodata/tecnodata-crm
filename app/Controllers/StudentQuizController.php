<?php
namespace Tecnodata\Lms\Controllers;

use Tecnodata\Lms\Core\Auth;
use Tecnodata\Lms\Core\Clock;
use Tecnodata\Lms\Core\Csrf;
use Tecnodata\Lms\Core\Database;
use Tecnodata\Lms\Core\View;
use Tecnodata\Lms\Services\BiometricPolicyService;
use Tecnodata\Lms\Services\ProgressService;

final class StudentQuizController
{
    public function show(string $enrollmentId,string $activityId): void
    {
        $user=Auth::requireLogin();
        [$en,$activity,$quiz]=$this->context((int)$enrollmentId,(int)$activityId,(int)$user['id']);

        $bio=(new BiometricPolicyService())->requirementForEnrollment((int)$en['id'],'before_quiz');

        $attempt=Database::fetch(
            "SELECT * FROM quiz_attempts
              WHERE quiz_id=? AND enrollment_id=? AND status='inprogress'
              ORDER BY id DESC LIMIT 1",
            [$quiz['id'],$en['id']]
        );

        if(!$attempt){
            $attempt=$this->createAttempt($quiz,$en);
        }

        $ids=json_decode($attempt['question_order_json'],true)?:[];
        $questions=$this->loadQuestions($ids);

        View::render('quizzes/student',compact('en','activity','quiz','attempt','questions','bio'));
    }

    public function submit(string $enrollmentId,string $activityId): void
    {
        $user=Auth::requireLogin();
        Csrf::verify();
        [$en,$activity,$quiz]=$this->context((int)$enrollmentId,(int)$activityId,(int)$user['id']);

        $attempt=Database::fetch(
            "SELECT * FROM quiz_attempts
              WHERE quiz_id=? AND enrollment_id=? AND status='inprogress'
              ORDER BY id DESC LIMIT 1",
            [$quiz['id'],$en['id']]
        );
        if(!$attempt) throw new \RuntimeException('Tentativa em andamento não encontrada.');

        $ids=json_decode($attempt['question_order_json'],true)?:[];
        $questions=$this->loadQuestions($ids);

        $total=0.0;
        $earned=0.0;
        $responses=(array)($_POST['q']??[]);

        $pdo=Database::pdo();
        $pdo->beginTransaction();

        try{
            foreach($questions as $q){
                $mark=max(0.01,(float)$q['default_mark']);
                $total+=$mark;

                $response=$responses[$q['id']]??null;
                $fraction=$this->gradeQuestion($q,$response);
                $questionMark=$mark*$fraction;
                $earned+=$questionMark;

                Database::execute(
                    "INSERT INTO quiz_attempt_answers(
                        attempt_id,question_id,response_json,fraction,mark,answered_at
                     ) VALUES(?,?,?,?,?,?)
                     ON DUPLICATE KEY UPDATE
                        response_json=VALUES(response_json),
                        fraction=VALUES(fraction),
                        mark=VALUES(mark),
                        answered_at=VALUES(answered_at)",
                    [
                        $attempt['id'],
                        $q['id'],
                        json_encode($response,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
                        $fraction,
                        $questionMark,
                        Clock::sql()
                    ]
                );
            }

            $percent=$total>0?round(($earned/$total)*100,3):0;
            $score=round(((float)$quiz['grade_max'])*$percent/100,4);
            $passMark=$quiz['grade_pass']!==null?(float)$quiz['grade_pass']:0;
            $passed=$score >= $passMark;

            Database::execute(
                "UPDATE quiz_attempts
                    SET status='finished',score=?,percent=?,passed=?,finished_at=?
                  WHERE id=?",
                [$score,$percent,$passed?1:0,Clock::sql(),$attempt['id']]
            );

            $current=Database::fetch("SELECT final_score FROM enrollments WHERE id=?",[$en['id']]);
            $best=max((float)($current['final_score']??0),$score);
            Database::execute(
                "UPDATE enrollments SET final_score=?,updated_at=? WHERE id=?",
                [$best,Clock::sql(),$en['id']]
            );

            if($passed){
                (new ProgressService())->completeActivity((int)$en['id'],(int)$activity['id'],100);
            }

            $pdo->commit();
        }catch(\Throwable $e){
            if($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }

        redirect('/student/enrollment/'.$en['id'].'/quiz/'.$activity['id'].'/result/'.$attempt['id']);
    }

    public function result(string $enrollmentId,string $activityId,string $attemptId): void
    {
        $user=Auth::requireLogin();
        [$en,$activity,$quiz]=$this->context((int)$enrollmentId,(int)$activityId,(int)$user['id']);

        $attempt=Database::fetch(
            "SELECT * FROM quiz_attempts
              WHERE id=? AND quiz_id=? AND enrollment_id=?",
            [(int)$attemptId,$quiz['id'],$en['id']]
        );
        if(!$attempt) throw new \RuntimeException('Tentativa não encontrada.');

        $answers=Database::all(
            "SELECT qa.*,q.name,q.question_html,q.type
               FROM quiz_attempt_answers qa
               JOIN questions q ON q.id=qa.question_id
              WHERE qa.attempt_id=?
              ORDER BY qa.id",
            [$attempt['id']]
        );

        View::render('quizzes/result',compact('en','activity','quiz','attempt','answers'));
    }

    private function createAttempt(array $quiz,array $en): array
    {
        $count=(int)(Database::fetch(
            "SELECT COUNT(*) c FROM quiz_attempts WHERE quiz_id=? AND enrollment_id=?",
            [$quiz['id'],$en['id']]
        )['c']??0);

        $allowed=(int)$quiz['attempts_allowed'];
        if($allowed>0 && $count >= $allowed){
            throw new \RuntimeException('Limite de tentativas atingido.');
        }

        $ids=$this->buildQuestionOrder((int)$quiz['id']);
        if(!$ids) throw new \RuntimeException('A avaliação ainda não possui questões.');

        $settings=json_decode($quiz['settings_json']?:'{}',true)?:[];
        if(!empty($settings['shuffle_questions'])) shuffle($ids);

        Database::execute(
            "INSERT INTO quiz_attempts(
                quiz_id,enrollment_id,attempt_no,status,question_order_json,started_at,created_at
             ) VALUES(?,?,?,'inprogress',?,?,?)",
            [
                $quiz['id'],
                $en['id'],
                $count+1,
                json_encode($ids),
                Clock::sql(),
                Clock::sql()
            ]
        );

        return Database::fetch("SELECT * FROM quiz_attempts WHERE id=?",[Database::id()]);
    }

    private function buildQuestionOrder(int $quizId): array
    {
        $slots=Database::all(
            "SELECT * FROM quiz_slots WHERE quiz_id=? ORDER BY position,id",
            [$quizId]
        );

        $ids=[];
        foreach($slots as $slot){
            if($slot['question_id']){
                $ids[]=(int)$slot['question_id'];
                continue;
            }

            if($slot['category_id'] && (int)$slot['random_count']>0){
                $pool=array_map(
                    'intval',
                    array_column(
                        Database::all(
                            "SELECT id FROM questions WHERE category_id=? ORDER BY id",
                            [$slot['category_id']]
                        ),
                        'id'
                    )
                );
                shuffle($pool);
                $ids=array_merge($ids,array_slice($pool,0,(int)$slot['random_count']));
            }
        }
        return array_values(array_unique($ids));
    }

    private function loadQuestions(array $ids): array
    {
        $out=[];
        foreach($ids as $id){
            $q=Database::fetch("SELECT * FROM questions WHERE id=?",[(int)$id]);
            if(!$q) continue;
            $q['answers']=Database::all(
                "SELECT * FROM question_answers WHERE question_id=? ORDER BY position,id",
                [$q['id']]
            );
            $out[]=$q;
        }
        return $out;
    }

    private function gradeQuestion(array $q,mixed $response): float
    {
        $settings=json_decode($q['settings_json']?:'{}',true)?:[];

        if($q['type']==='multichoice' || $q['type']==='truefalse'){
            $selected=is_array($response)?$response:[$response];
            $fraction=0.0;
            foreach($q['answers'] as $a){
                if(in_array((string)$a['id'],array_map('strval',$selected),true)){
                    $fraction+=(float)$a['fraction'];
                }
            }
            return max(0,min(1,$fraction));
        }

        if($q['type']==='shortanswer'){
            $given=trim((string)$response);
            $case=!empty($settings['case_sensitive']);
            foreach($q['answers'] as $a){
                $expected=trim(strip_tags((string)$a['answer_html']));
                $ok=$case ? $given===$expected : mb_strtolower($given)===mb_strtolower($expected);
                if($ok) return max(0,min(1,(float)$a['fraction']));
            }
            return 0;
        }

        if($q['type']==='numerical'){
            $given=str_replace(',','.',trim((string)$response));
            if(!is_numeric($given)) return 0;
            $tol=(float)($settings['tolerance']??0);
            foreach($q['answers'] as $a){
                $expected=str_replace(',','.',strip_tags((string)$a['answer_html']));
                if(is_numeric($expected) && abs((float)$given-(float)$expected)<=$tol){
                    return max(0,min(1,(float)$a['fraction']));
                }
            }
            return 0;
        }

        if($q['type']==='matching'){
            $pairs=(array)($settings['pairs']??[]);
            if(!$pairs || !is_array($response)) return 0;
            $hits=0;
            foreach($pairs as $i=>$pair){
                if(trim((string)($response[$i]??''))===trim((string)($pair['right']??''))) $hits++;
            }
            return count($pairs)?$hits/count($pairs):0;
        }

        return 0;
    }

    private function context(int $enrollmentId,int $activityId,int $userId): array
    {
        $en=Database::fetch(
            "SELECT e.*,c.name course_name
               FROM enrollments e
               JOIN courses c ON c.id=e.course_id
              WHERE e.id=? AND e.user_id=? AND e.status IN('active','completed')",
            [$enrollmentId,$userId]
        );
        if(!$en) throw new \RuntimeException('Matrícula inválida.');

        $activity=Database::fetch(
            "SELECT * FROM course_activities WHERE id=? AND course_id=? AND type='quiz'",
            [$activityId,$en['course_id']]
        );
        if(!$activity) throw new \RuntimeException('Avaliação inválida.');

        $quiz=Database::fetch("SELECT * FROM quizzes WHERE activity_id=?",[$activityId]);
        if(!$quiz) throw new \RuntimeException('Configuração da avaliação não encontrada.');

        return [$en,$activity,$quiz];
    }
}
